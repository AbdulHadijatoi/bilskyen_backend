<?php

namespace App\Http\Controllers;

use App\Constants\UserStatus;
use App\Models\User;
use App\Models\DealerStaff;
use App\Services\DealerContextService;
use App\Services\AuditLogService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Dealer Staff Controller
 * Manages dealer staff members
 */
class DealerStaffController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private AuditLogService $auditLogService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    /**
     * List all staff members for the current dealer
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check if staff_management feature is enabled
        if (!$this->subscriptionFeatureService->hasFeature($dealer, 'staff_management')) {
            return $this->error(
                __('messages.api.staff_management_not_in_plan'),
                [],
                403
            );
        }

        $staff = $dealer->staff()
            ->with('user')
            ->paginate($request->get('limit', 15));

        // Transform to include user details
        $staff->getCollection()->transform(function ($dealerStaff) {
            $user = $dealerStaff->user;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $dealerStaff->username,
                'phone' => $user->phone,
                'created_at' => $dealerStaff->created_at,
            ];
        });

        return $this->paginated($staff);
    }

    /**
     * Create new staff member
     * 
     * Request body:
     * { "name": "Staff Member", "email": "staff@example.com" (optional), "phone": "123456789" (optional), "password": "password123" }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check permission
        if (!$user->hasPermissionTo('dealer.staff.manage')) {
            return $this->forbidden(__('messages.errors.no_permission_manage_staff'));
        }

        // Validate request
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|max:128',
        ]);

        // Generate unique username: staff_{dealer_id}_{sequential_number}
        $username = $this->generateUniqueUsername($dealer->id);

        // Create new user
        $targetUser = User::create([
            'name' => $request->name,
            'email' => $request->email ? strtolower($request->email) : null,
            'username' => $username,
            'phone' => $request->phone,
            'password' => $request->password, // Will be automatically hashed by the model cast
            'status_id' => UserStatus::ACTIVE,
        ]);

        // Assign staff role to the newly created user
        if (!$targetUser->hasRole('staff')) {
            $targetUser->assignRole('staff'); 
        }

        // Create dealer staff record
        $dealerStaff = DealerStaff::create([
            'dealer_id' => $dealer->id,
            'user_id' => $targetUser->id,
            'username' => $username,
        ]);

        // Audit log
        try {
            $this->auditLogService->logCreate(
                $user,
                'DealerStaff',
                $dealerStaff->id,
                [
                    'dealer_id' => $dealer->id,
                    'user_id' => $targetUser->id,
                    'username' => $username,
                    'is_new_user' => true,
                ],
                $request,
                'Dealer',
                $dealer->id,
                "Staff member created: {$targetUser->name} (username: {$username})",
                ['dealer', 'staff', 'management']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for staff creation', [
                'dealer_staff_id' => $dealerStaff->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->created([
            'message' => __('messages.errors.staff_created_success'),
            'dealer_staff' => $dealerStaff->load('user'),
            'username' => $username,
        ]);
    }

    /**
     * Update staff member
     */
    public function update(int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check permission
        if (!$user->hasPermissionTo('dealer.staff.manage')) {
            return $this->forbidden(__('messages.errors.no_permission_manage_staff'));
        }

        $request->validate([
            'name' => 'sometimes|required|string|min:2|max:255',
            'phone' => 'nullable|string|max:30',
            'password' => 'sometimes|required|string|min:8|max:128',
        ]);

        $dealerStaff = DealerStaff::where('dealer_id', $dealer->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $targetUser = User::findOrFail($userId);

        // Store before state for audit log
        $beforeData = [
            'name' => $targetUser->name,
            'phone' => $targetUser->phone,
        ];

        // Update user details
        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }
        if ($request->has('password')) {
            $updateData['password'] = $request->password; // Will be automatically hashed
        }

        $targetUser->update($updateData);

        // Audit log
        try {
            $afterData = [
                'name' => $targetUser->name,
                'phone' => $targetUser->phone,
            ];
            if ($request->has('password')) {
                $afterData['password_changed'] = true;
            }

            $this->auditLogService->logUpdate(
                $user,
                'DealerStaff',
                $dealerStaff->id,
                $beforeData,
                $afterData,
                $request,
                'Dealer',
                $dealer->id,
                "Staff member updated: {$targetUser->name}",
                ['dealer', 'staff', 'management']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for staff update', [
                'dealer_staff_id' => $dealerStaff->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success([
            'dealer_staff' => $dealerStaff->load('user'),
            'user' => $targetUser->fresh(),
        ]);
    }

    /**
     * Remove staff member from dealer
     */
    public function destroy(int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check permission
        if (!$user->hasPermissionTo('dealer.staff.manage')) {
            return $this->forbidden(__('messages.errors.no_permission_manage_staff'));
        }

        $dealerStaff = DealerStaff::where('dealer_id', $dealer->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $targetUser = User::findOrFail($userId);

        // Store data for audit log before deletion
        $dealerStaffData = $dealerStaff->toArray();

        // Delete staff record
        $dealerStaff->delete();

        // Audit log
        try {
            $this->auditLogService->logDelete(
                $user,
                'DealerStaff',
                $dealerStaffData['id'],
                $dealerStaffData,
                $request,
                'Dealer',
                $dealer->id,
                "Staff member removed from dealer: {$targetUser->name} (username: {$dealerStaffData['username']})",
                ['dealer', 'staff', 'management']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for staff removal', [
                'dealer_id' => $dealer->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->noContent();
    }

    /**
     * Generate unique username for staff member
     * Format: staff_{dealer_id}_{sequential_number}
     * 
     * @param int $dealerId
     * @return string
     */
    private function generateUniqueUsername(int $dealerId): string
    {
        // Get the highest sequential number for this dealer from dealer_staff table
        $existingUsernames = DealerStaff::where('dealer_id', $dealerId)
            ->where('username', 'like', "staff_{$dealerId}_%")
            ->pluck('username')
            ->toArray();

        $maxNumber = 0;
        foreach ($existingUsernames as $username) {
            if (preg_match('/staff_' . $dealerId . '_(\d+)$/', $username, $matches)) {
                $number = (int)$matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        // Generate next sequential number
        $nextNumber = $maxNumber + 1;
        $username = sprintf('staff_%d_%03d', $dealerId, $nextNumber);

        // Double-check uniqueness (shouldn't happen, but safety check)
        $attempts = 0;
        while (DealerStaff::where('username', $username)->exists() && $attempts < 100) {
            $nextNumber++;
            $username = sprintf('staff_%d_%03d', $dealerId, $nextNumber);
            $attempts++;
        }

        if ($attempts >= 100) {
            throw new \Exception('Unable to generate unique username after 100 attempts');
        }

        return $username;
    }
}
