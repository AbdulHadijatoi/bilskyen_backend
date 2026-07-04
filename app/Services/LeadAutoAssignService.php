<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerStaff;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;

class LeadAutoAssignService
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function assignIfEnabled(Lead $lead): void
    {
        if ($lead->assigned_user_id) {
            return;
        }

        $dealer = $lead->relationLoaded('dealer') ? $lead->dealer : Dealer::find($lead->dealer_id);
        if (! $dealer || ! $this->subscriptionFeatureService->hasFeature($dealer, 'lead_auto_assign')) {
            return;
        }

        $staffUserIds = DealerStaff::query()
            ->where('dealer_id', $dealer->id)
            ->pluck('user_id')
            ->filter()
            ->values();

        if ($staffUserIds->isEmpty()) {
            $lead->assigned_user_id = $dealer->user_id;
            $lead->saveQuietly();

            return;
        }

        $lock = Cache::lock('lead_auto_assign_lock:'.$dealer->id, 5);
        $lock->block(3, function () use ($lead, $dealer, $staffUserIds) {
            $cacheKey = 'lead_auto_assign:'.$dealer->id;
            $index = (int) Cache::get($cacheKey, 0);
            $lead->assigned_user_id = (int) $staffUserIds[$index % $staffUserIds->count()];
            Cache::put($cacheKey, ($index + 1) % $staffUserIds->count(), now()->addYear());
            $lead->saveQuietly();
        });
    }
}
