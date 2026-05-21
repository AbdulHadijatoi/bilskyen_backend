<?php

namespace App\Support;

use App\Helpers\FormatHelper;
use App\Models\DealerSubscriptionChangeRequest;
use App\Models\PlanPriceHistory;

final class SubscriptionMailPresenter
{
    public static function formatPlanPriceLine(DealerSubscriptionChangeRequest $changeRequest): ?string
    {
        $plan = $changeRequest->requestedPlan;
        if (!$plan) {
            return null;
        }

        $billingCycle = $changeRequest->billing_cycle ?? 'monthly';

        $priceRow = $plan->priceHistory()
            ->where('billing_cycle', $billingCycle)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();

        if (!$priceRow instanceof PlanPriceHistory) {
            return null;
        }

        $amount = ((float) $priceRow->price) / 100;
        $currency = $priceRow->currency ?: 'DKK';
        $formatted = FormatHelper::formatCurrency($amount, $currency);

        $cycleLabel = $billingCycle === 'yearly'
            ? __('messages.mail.billing_cycle_yearly')
            : __('messages.mail.billing_cycle_monthly');

        return __('messages.mail.subscription_plan_price_line', [
            'price' => $formatted,
            'cycle' => $cycleLabel,
        ]);
    }
}
