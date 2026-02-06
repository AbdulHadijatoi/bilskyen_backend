<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\FeatureValueType;
use App\Constants\SubscriptionStatus;
use Illuminate\Support\Facades\Cache;

/**
 * Subscription Feature Service
 * Handles loading and checking subscription features for dealers
 */
class SubscriptionFeatureService
{
    /**
     * Get all subscription features for a dealer as key-value pairs
     * Returns empty array if dealer has no active subscription
     * 
     * @param Dealer $dealer
     * @return array<string, string> Feature key => value pairs
     */
    public function getFeatures(Dealer $dealer): array
    {
        // Use request-level cache to avoid repeated queries
        $cacheKey = "dealer_features_{$dealer->id}";
        
        return Cache::remember($cacheKey, 60, function () use ($dealer) {
            // Get active subscription (ACTIVE or TRIAL)
            $subscription = $dealer->subscriptions()
                ->whereIn('subscription_status_id', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
                ->latest()
                ->first();
            
            if (!$subscription || !$subscription->plan) {
                return [];
            }
            
            // Load plan features with feature details
            $planFeatures = $subscription->plan->planFeatures()
                ->with('feature.featureValueType')
                ->get();
            
            $features = [];
            foreach ($planFeatures as $planFeature) {
                if ($planFeature->feature) {
                    $features[$planFeature->feature->key] = $planFeature->value;
                }
            }
            
            return $features;
        });
    }
    
    /**
     * Get a single feature value for a dealer
     * 
     * @param Dealer $dealer
     * @param string $key Feature key (e.g., 'max_listings', 'audit_logs')
     * @param mixed $default Default value if feature not found
     * @return mixed Feature value or default
     */
    public function getFeature(Dealer $dealer, string $key, $default = null)
    {
        $features = $this->getFeatures($dealer);
        return $features[$key] ?? $default;
    }
    
    /**
     * Check if a boolean feature is enabled for a dealer
     * 
     * @param Dealer $dealer
     * @param string $key Feature key
     * @return bool True if feature exists and is enabled
     */
    public function hasFeature(Dealer $dealer, string $key): bool
    {
        $value = $this->getFeature($dealer, $key, 'false');
        
        // Handle string 'true'/'false' or actual boolean
        if (is_bool($value)) {
            return $value;
        }
        
        return strtolower((string)$value) === 'true' || $value === '1';
    }
    
    /**
     * Get a number feature limit for a dealer
     * 
     * @param Dealer $dealer
     * @param string $key Feature key (e.g., 'max_listings')
     * @param int $default Default limit if feature not found
     * @return int Feature limit as integer
     */
    public function getFeatureLimit(Dealer $dealer, string $key, int $default = 0): int
    {
        $value = $this->getFeature($dealer, $key, $default);
        
        // Convert to integer
        return (int) $value;
    }
    
    /**
     * Check if a feature limit allows an action
     * 
     * @param Dealer $dealer
     * @param string $key Feature key
     * @param int $currentCount Current count (e.g., number of published vehicles)
     * @return bool True if limit allows action (currentCount < limit)
     */
    public function checkFeatureLimit(Dealer $dealer, string $key, int $currentCount): bool
    {
        $limit = $this->getFeatureLimit($dealer, $key, 0);
        
        // If limit is 0, feature is disabled
        if ($limit === 0) {
            return false;
        }
        
        // Check if current count is less than limit
        return $currentCount < $limit;
    }
    
    /**
     * Clear cached features for a dealer
     * Call this when subscription changes
     * 
     * @param Dealer $dealer
     * @return void
     */
    public function clearCache(Dealer $dealer): void
    {
        $cacheKey = "dealer_features_{$dealer->id}";
        Cache::forget($cacheKey);
    }
}
