<?php

namespace App\Services;

use App\Models\User;
use App\Models\Dealer;
use App\Models\DealerUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Dealer Context Service
 * Provides consistent dealer scoping and membership role resolution
 */
class DealerContextService
{
    /**
     * Get current dealer for authenticated user
     * Uses first dealer relationship (consistent with existing controllers)
     * 
     * @param User $user
     * @return Dealer|null
     */
    public function getCurrentDealer(User $user): ?Dealer
    {
        return $user->dealers()->first();
    }

    /**
     * Get dealer membership record (pivot) for user and dealer
     * 
     * @param User $user
     * @param Dealer|null $dealer
     * @return DealerUser|null
     */
    public function getDealerMembership(User $user, ?Dealer $dealer = null): ?DealerUser
    {
        if (!$dealer) {
            $dealer = $this->getCurrentDealer($user);
        }
        
        if (!$dealer) {
            return null;
        }

        return DealerUser::where('dealer_id', $dealer->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Get dealer membership role ID from pivot table
     * 
     * @param User $user
     * @param Dealer|null $dealer
     * @return int|null
     */
    public function getDealerMembershipRoleId(User $user, ?Dealer $dealer = null): ?int
    {
        $membership = $this->getDealerMembership($user, $dealer);
        return $membership?->role_id;
    }

    /**
     * Check if user is dealer admin (based on membership role or permissions)
     * Dealer Admin = ROLE_OWNER (1), ROLE_MANAGER (2), or users with dealer.staff.manage permission
     * 
     * @param User $user
     * @param Dealer|null $dealer
     * @return bool
     */
    public function isDealerAdmin(User $user, ?Dealer $dealer = null): bool
    {
        $membership = $this->getDealerMembership($user, $dealer);
        
        if (!$membership) {
            return false;
        }

        // Check if membership role is OWNER (1) or MANAGER (2)
        // Or check if user has admin permissions (dealer.staff.manage)
        return $membership->role_id === DealerUser::ROLE_OWNER 
            || $membership->role_id === DealerUser::ROLE_MANAGER
            || $user->hasPermissionTo('dealer.staff.manage');
    }

    /**
     * Check if user is dealer staff (based on membership role)
     * Dealer Staff = ROLE_STAFF (3) or users with "staff" Spatie role
     * 
     * @param User $user
     * @param Dealer|null $dealer
     * @return bool
     */
    public function isDealerStaff(User $user, ?Dealer $dealer = null): bool
    {
        $membership = $this->getDealerMembership($user, $dealer);
        
        if (!$membership) {
            return false;
        }

        // Check if membership role is STAFF (3) or user has "staff" Spatie role
        return $membership->role_id === DealerUser::ROLE_STAFF 
            || $user->hasRole('staff');
    }

    /**
     * Require dealer context - throws 404 if user has no dealer
     * Helper for controllers to ensure dealer exists
     * 
     * @param User $user
     * @return Dealer
     * @throws \RuntimeException
     */
    public function requireDealer(User $user): Dealer
    {
        $dealer = $this->getCurrentDealer($user);
        
        if (!$dealer) {
            throw new \RuntimeException('Dealer not found', 404);
        }
        
        return $dealer;
    }

    /**
     * Get full dealer context (dealer + membership + role info)
     * Useful for returning context in API responses
     * 
     * @param User $user
     * @return array|null
     */
    public function getDealerContext(User $user): ?array
    {
        $dealer = $this->getCurrentDealer($user);
        
        if (!$dealer) {
            return null;
        }

        $membership = $this->getDealerMembership($user, $dealer);
        
        return [
            'dealer' => $dealer,
            'membership' => $membership,
            'role_id' => $membership?->role_id,
            'is_admin' => $this->isDealerAdmin($user, $dealer),
            'is_staff' => $this->isDealerStaff($user, $dealer),
        ];
    }

    /**
     * Ensure user belongs to dealer and optionally check admin status
     * 
     * @param User $user
     * @param int $dealerId
     * @param bool $requireAdmin
     * @return Dealer
     * @throws \RuntimeException
     */
    public function ensureDealerAccess(User $user, int $dealerId, bool $requireAdmin = false): Dealer
    {
        $dealer = Dealer::findOrFail($dealerId);
        
        $membership = $this->getDealerMembership($user, $dealer);
        
        if (!$membership) {
            throw new \RuntimeException('User does not belong to this dealer', 403);
        }
        
        if ($requireAdmin && !$this->isDealerAdmin($user, $dealer)) {
            throw new \RuntimeException('Dealer admin access required', 403);
        }
        
        return $dealer;
    }
}
