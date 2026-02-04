<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DealerUser;
use App\Services\DealerContextService;
use App\Services\AuditLogService;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * Dealer Staff Controller
 * Manages dealer staff members with permission-based access control
 */
class DealerStaffController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private AuditLogService $auditLogService,
        private RolePermissionService $rolePermissionService
    ) {}

    /**
     * List all staff members for the current dealer
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        $staff = $dealer->users()
            ->withPivot('role_id', 'created_at')
            ->with('roles')
            ->paginate($request->get('limit', 15));

        // Transform to include membership role info
        $staff->getCollection()->transform(function ($user) use ($dealer) {
            $membership = $this->dealerContextService->getDealerMembership($user, $dealer);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'membership_role_id' => $membership?->role_id,
                'spatie_roles' => $user->roles->pluck('name'),
                'created_at' => $membership?->created_at,
            ];
        });

        return $this->paginated($staff);
    }

    /**
     * Add staff member - supports both attach existing user and invite/create
     * 
     * Request body:
     * - Option 1 (attach existing): { "user_id": 123, "membership_role_id": 1 }
     * - Option 2 (invite/create): { "email": "staff@example.com", "name": "John Doe", "membership_role_id": 3 }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check permission
        if (!$user->hasPermissionTo('dealer.staff.manage')) {
            return $this->forbidden('You do not have permission to manage staff');
        }

        // Validate request
        $request->validate([
            'user_id' => 'required_without:email|exists:users,id',
            'email' => 'required_without:user_id|email|unique:users,email',
            'name' => 'required_with:email|string|max:255',
            'membership_role_id' => 'required|integer|in:1,2,3', // ROLE_OWNER, ROLE_MANAGER, ROLE_STAFF
        ]);

        $targetUser = null;
        $isNewUser = false;

        if ($request->has('user_id')) {
            // Attach existing user
            $targetUser = User::findOrFail($request->user_id);
            
            // Check if user is already a member
            $existingMembership = DealerUser::where('dealer_id', $dealer->id)
                ->where('user_id', $targetUser->id)
                ->first();
            
            if ($existingMembership) {
                return $this->validationError(['user_id' => ['User is already a member of this dealer']]);
            }
        } else {
            // Invite/create new user
            $targetUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(uniqid()), // Temporary password, will be reset
            ]);
            $isNewUser = true;
        }

        // All staff members get "staff" role (regardless of membership_role_id)
        $membershipRoleId = $request->membership_role_id;

        // Create dealer membership
        $dealerUser = DealerUser::create([
            'dealer_id' => $dealer->id,
            'user_id' => $targetUser->id,
            'role_id' => $membershipRoleId, // This is the membership role (OWNER/MANAGER/STAFF)
            'created_at' => now(),
        ]);

        // Assign "staff" role to all staff members
        if (!$targetUser->hasRole('staff')) {
            $this->rolePermissionService->assignRoleToUser($targetUser, 'staff');
        }

        // If new user, send password reset token (invite flow)
        $resetToken = null;
        if ($isNewUser) {
            try {
                $resetToken = Password::createToken($targetUser);
                // In a real implementation, you would send an email here
                // Mail::to($targetUser)->send(new StaffInvitationMail($dealer, $resetToken));
            } catch (\Exception $e) {
                Log::warning('Failed to create password reset token for new staff', [
                    'user_id' => $targetUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Audit log
        try {
            $this->auditLogService->logCreate(
                $user,
                'DealerUser',
                $dealerUser->id,
                [
                    'dealer_id' => $dealer->id,
                    'user_id' => $targetUser->id,
                    'membership_role_id' => $membershipRoleId,
                    'is_new_user' => $isNewUser,
                ],
                $request,
                'Dealer',
                $dealer->id,
                "Staff member added to dealer: {$targetUser->name} ({$targetUser->email})",
                ['dealer', 'staff', 'management']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for staff addition', [
                'dealer_user_id' => $dealerUser->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->created([
            'message' => $isNewUser ? 'Staff member invited successfully' : 'Staff member added successfully',
            'dealer_user' => $dealerUser->load('user', 'role'),
            'reset_token' => $isNewUser ? $resetToken : null, // Only return token for new users
        ]);
    }

    /**
     * Invite a new staff member (alternative endpoint for clarity)
     */
    public function invite(Request $request): JsonResponse
    {
        // This is essentially the same as store() but with email/name required
        $request->merge(['email' => $request->input('email')]);
        $request->merge(['name' => $request->input('name')]);
        
        return $this->store($request);
    }

    /**
     * Update staff member role/permissions
     */
    public function update(int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check permission
        if (!$user->hasPermissionTo('dealer.staff.manage')) {
            return $this->forbidden('You do not have permission to manage staff');
        }

        $request->validate([
            'membership_role_id' => 'required|integer|in:1,2,3',
        ]);

        $dealerUser = DealerUser::where('dealer_id', $dealer->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $targetUser = User::findOrFail($userId);
        $oldRoleId = $dealerUser->role_id;
        $newRoleId = $request->membership_role_id;

        // Update membership role
        $dealerUser->update(['role_id' => $newRoleId]);

        // Ensure user has "staff" role (all staff members get this role)
        if (!$targetUser->hasRole('staff')) {
            $this->rolePermissionService->assignRoleToUser($targetUser, 'staff');
        }

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'DealerUser',
                $dealerUser->id,
                [
                    'membership_role_id' => $oldRoleId,
                ],
                [
                    'membership_role_id' => $newRoleId,
                ],
                $request,
                'Dealer',
                $dealer->id,
                "Staff member role updated: {$targetUser->name}",
                ['dealer', 'staff', 'management']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for staff update', [
                'dealer_user_id' => $dealerUser->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($dealerUser->load('user', 'role'));
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
            return $this->forbidden('You do not have permission to manage staff');
        }

        $dealerUser = DealerUser::where('dealer_id', $dealer->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $targetUser = User::findOrFail($userId);
        $membershipRoleId = $dealerUser->role_id;

        // Store data for audit log before deletion
        $dealerUserData = $dealerUser->toArray();

        // Delete membership
        $dealerUser->delete();

        // Note: We don't remove the Spatie role here as the user might belong to other dealers
        // If you want to remove it, check if user has other dealer memberships first

        // Audit log
        try {
            $this->auditLogService->logDelete(
                $user,
                'DealerUser',
                $dealerUserData['id'],
                $dealerUserData,
                $request,
                'Dealer',
                $dealer->id,
                "Staff member removed from dealer: {$targetUser->name} ({$targetUser->email})",
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
}

