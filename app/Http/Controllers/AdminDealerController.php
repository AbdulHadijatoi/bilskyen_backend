<?php

namespace App\Http\Controllers;

use App\Constants\UserStatus;
use App\Constants\VehicleListStatus;
use App\Models\AuditActorType;
use App\Models\Dealer;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Admin Dealer Controller
 */
class AdminDealerController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    /**
     * List all dealers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dealer::with([
            'owner',
            'staff.user.roles',
            'subscriptions' => fn ($q) => $q->latest('id')->limit(1)->with('plan'),
            'subscriptionChangeRequests' => fn ($q) => $q->pending()->latest('id')->limit(1),
        ])->withCount([
            'vehicles',
            'vehicles as published_vehicles_count' => fn ($q) => $q->where('list_status_id', VehicleListStatus::PUBLISHED),
        ]);

        // Apply search filter
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('cvr', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                      $ownerQuery->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $dealers = $query->orderByDesc('id')->paginate($request->get('limit', 15));

        return $this->paginated($dealers);
    }

    /**
     * List dealers for dropdowns (id, name, email).
     * Name = owner name, or slug, or cvr. Email = owner email.
     */
    public function list(Request $request): JsonResponse
    {
        $dealers = Dealer::with('owner')
            ->orderBy('id')
            ->get();

        $items = $dealers->map(function (Dealer $dealer) {
            $name = $dealer->owner?->name ?? $dealer->slug ?? $dealer->cvr ?? 'Dealer #' . $dealer->id;
            $email = $dealer->owner?->email ?? '';
            return [
                'id' => $dealer->id,
                'name' => $name,
                'email' => $email,
            ];
        });

        return $this->success($items->values()->all());
    }

    /**
     * Get dealer details
     */
    public function show(int $id): JsonResponse
    {
        $dealer = Dealer::with([
            'owner',
            'staff.user.roles',
            'subscriptions.plan',
            'subscriptionChangeRequests' => fn ($q) => $q->pending()->latest('id')->limit(1),
            'vehicles',
        ])
            ->withCount([
                'vehicles as published_vehicles_count' => fn ($q) => $q->where('list_status_id', VehicleListStatus::PUBLISHED),
            ])
            ->findOrFail($id);

        return $this->success($dealer);
    }

    /**
     * Log in as the dealer owner (impersonation). Does not overwrite the admin refresh cookie.
     */
    public function impersonate(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();
        $dealer = Dealer::with('owner')->findOrFail($id);
        $owner = $dealer->owner;

        if (! $owner) {
            return $this->notFound(__('messages.errors.impersonate_dealer_no_owner'));
        }

        $owner->load('roles');

        if (($owner->banned ?? false) || (int) $owner->status_id === UserStatus::SUSPENDED) {
            return $this->forbidden(__('messages.errors.impersonate_dealer_owner_banned'));
        }

        if (! $owner->hasRole('dealer')) {
            return $this->forbidden(__('messages.errors.impersonate_dealer_owner_not_dealer'));
        }

        $token = JWTAuth::customClaims([
            'impersonated_by' => $admin->id,
            'impersonating_dealer_id' => $dealer->id,
        ])->fromUser($owner);

        $this->auditLogService->log(
            $admin->id,
            AuditActorType::ADMIN,
            'impersonate',
            'Dealer',
            $dealer->id,
            null,
            [
                'owner_user_id' => $owner->id,
                'owner_email' => $owner->email,
            ],
            $request,
            'User',
            $owner->id,
            'Admin logged in as dealer',
            ['impersonation', 'dealer']
        );

        return $this->success($this->panelAuthPayload($owner, $token, $dealer));
    }

    /**
     * @return array<string, mixed>
     */
    private function panelAuthPayload(User $user, string $token, Dealer $dealer): array
    {
        $subscriptionFeatures = $this->subscriptionFeatureService->getFeatures($dealer);
        if ($subscriptionFeatures === []) {
            $subscriptionFeatures = new \stdClass;
        }

        $dealerName = $user->name ?: ($dealer->slug ?: ('Dealer #'.$dealer->id));

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
                'phone' => $user->phone,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 30) * 60,
            'subscription_features' => $subscriptionFeatures,
            'impersonation' => [
                'dealer_id' => $dealer->id,
                'dealer_name' => $dealerName,
            ],
        ];
    }
}
