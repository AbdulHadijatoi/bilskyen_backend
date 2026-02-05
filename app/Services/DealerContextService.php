<?php

namespace App\Services;

use App\Models\User;
use App\Models\Dealer;
use App\Models\DealerStaff;

/**
 * Dealer Context Service
 * Provides consistent dealer scoping and access control
 */
class DealerContextService
{
    /**
     * Get current dealer for authenticated user
     * Uses first owned dealer (user is the owner)
     * 
     * @param User $user
     * @return Dealer|null
     */
    public function getCurrentDealer(User $user): ?Dealer
    {
        return $user->dealer;
    }

    /**
     * Check if user is dealer admin (owner or has permission)
     * 
     * @param User $user
     * @param Dealer|null $dealer
     * @return bool
     */
    public function isDealerAdmin(User $user, ?Dealer $dealer = null): bool
    {
        if (!$dealer) {
            $dealer = $this->getCurrentDealer($user);
        }
        
        if (!$dealer) {
            return false;
        }

        // Check if user owns the dealer or has admin permissions
        return $dealer->user_id === $user->id
            || $user->hasPermissionTo('dealer.staff.manage');
    }

    /**
     * Check if user is dealer staff
     * 
     * @param User $user
     * @param Dealer|null $dealer
     * @return bool
     */
    public function isDealerStaff(User $user, ?Dealer $dealer = null): bool
    {
        if (!$dealer) {
            $dealer = $this->getCurrentDealer($user);
        }
        
        if (!$dealer) {
            return false;
        }

        // Check if user has DealerStaff record for this dealer
        return DealerStaff::where('dealer_id', $dealer->id)
            ->where('user_id', $user->id)
            ->exists();
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
     * Get full dealer context (dealer + access info)
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

        return [
            'dealer' => $dealer,
            'is_owner' => $dealer->user_id === $user->id,
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
        
        // Check if user owns the dealer or is staff
        $isOwner = $dealer->user_id === $user->id;
        $isStaff = DealerStaff::where('dealer_id', $dealer->id)
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$isOwner && !$isStaff) {
            throw new \RuntimeException('User does not belong to this dealer', 403);
        }
        
        if ($requireAdmin && !$this->isDealerAdmin($user, $dealer)) {
            throw new \RuntimeException('Dealer admin access required', 403);
        }
        
        return $dealer;
    }
}
