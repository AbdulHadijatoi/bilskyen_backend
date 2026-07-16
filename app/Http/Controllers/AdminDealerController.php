<?php

namespace App\Http\Controllers;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Dealer Controller
 */
class AdminDealerController extends Controller
{
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
}
