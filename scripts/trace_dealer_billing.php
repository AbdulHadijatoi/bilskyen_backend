<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\DealerInvoice;
use App\Models\DealerSubscription;
use App\Models\ListingBillingPeriod;
use App\Models\Vehicle;
use App\Services\ListingBillingService;
use App\Services\SubscriptionFeatureService;

$dealerId = (int) ($argv[1] ?? 98);
$doBackfill = in_array('--backfill', $argv, true);

$dealer = Dealer::find($dealerId);
if (! $dealer) {
    echo "Dealer #{$dealerId} not found\n";
    exit(1);
}

$featureService = app(SubscriptionFeatureService::class);
$subscription = $featureService->getActiveSubscription($dealer);
$plan = $subscription?->plan;

if ($doBackfill) {
    $billingService = app(ListingBillingService::class);
    $startedAt = $subscription?->starts_at;
    $count = $billingService->startBillingForPublishedVehicles($dealer, $startedAt);
    echo "Backfilled listing_billing_started_at on {$count} published vehicle(s).\n\n";
    $subscription = $featureService->getActiveSubscription($dealer);
    $plan = $subscription?->plan;
}

echo "=== DEALER #{$dealer->id} ===\n";
echo "CVR: {$dealer->cvr}\n";
echo "City: " . ($dealer->city ?? 'n/a') . "\n";
echo "User ID: " . ($dealer->user_id ?? 'n/a') . "\n\n";

echo "=== SUBSCRIPTION ===\n";
if (! $subscription) {
    echo "NO ACTIVE SUBSCRIPTION\n\n";
} else {
    echo "Subscription ID: {$subscription->id}\n";
    echo "Status ID: {$subscription->subscription_status_id}\n";
    echo "Billing cycle: " . ($subscription->billing_cycle ?? 'null') . "\n";
    echo "Starts: {$subscription->starts_at}\n";
    echo "Ends: " . ($subscription->ends_at ?? 'null') . "\n";
    echo "Plan: " . ($plan?->name ?? 'null') . " (slug: " . ($plan?->slug ?? 'null') . ")\n";
    echo "Plan billing_model: " . ($plan?->billing_model ?? 'null') . "\n";
    echo "Price per listing/day (øre): " . ($plan?->price_per_listing_per_day ?? 'null') . "\n";
    echo "Is usage daily plan: " . ($featureService->isUsageDailyPlan($dealer) ? 'YES' : 'NO') . "\n\n";
}

echo "=== VEHICLES ===\n";
$statusMap = [
    VehicleListStatus::DRAFT => 'draft',
    VehicleListStatus::PUBLISHED => 'published',
    VehicleListStatus::SOLD => 'sold',
    VehicleListStatus::ARCHIVED => 'archived',
    VehicleListStatus::PENDING_REVIEW => 'pending_review',
];

$vehicles = Vehicle::where('dealer_id', $dealerId)->orderBy('id')->get();
$byStatus = $vehicles->groupBy('list_status_id');
foreach ($byStatus as $statusId => $group) {
    $label = $statusMap[$statusId] ?? (string) $statusId;
    echo "  {$label}: {$group->count()}\n";
}

foreach ($vehicles as $v) {
    $st = $statusMap[$v->list_status_id] ?? $v->list_status_id;
    $lbs = $v->listing_billing_started_at?->toDateTimeString() ?? 'NULL';
    $pub = $v->published_at?->toDateTimeString() ?? 'NULL';
    echo "\n  Vehicle #{$v->id}\n";
    echo "    Title: {$v->title}\n";
    echo "    Registration: " . ($v->registration ?? 'n/a') . "\n";
    echo "    Status: {$st}\n";
    echo "    Published at: {$pub}\n";
    echo "    listing_billing_started_at: {$lbs}\n";
    echo "    Created: {$v->created_at}\n";
}

echo "\n=== LISTING BILLING PERIODS ===\n";
$periods = ListingBillingPeriod::where('dealer_id', $dealerId)->orderBy('billing_date')->get();
echo "Total rows: {$periods->count()}\n";
if ($periods->isNotEmpty()) {
    $byStatus = $periods->groupBy('status');
    foreach ($byStatus as $status => $group) {
        echo "  {$status}: {$group->count()} (total øre: {$group->sum('amount_cents')})\n";
    }
    echo "Sample (last 10):\n";
    foreach ($periods->take(-10) as $p) {
        echo "  vehicle #{$p->vehicle_id} date={$p->billing_date} amount={$p->amount_cents} status={$p->status}\n";
    }
}

echo "\n=== DEALER INVOICES ===\n";
$invoices = DealerInvoice::where('dealer_id', $dealerId)->orderByDesc('id')->get();
echo "Total invoices: {$invoices->count()}\n";
foreach ($invoices as $inv) {
    echo "  Invoice #{$inv->id} {$inv->period_start}–{$inv->period_end} total={$inv->total_cents} status={$inv->status}\n";
}

echo "\n=== ALL SUBSCRIPTIONS (history) ===\n";
$allSubs = DealerSubscription::where('dealer_id', $dealerId)->with('plan')->orderByDesc('id')->get();
foreach ($allSubs as $s) {
    echo "  #{$s->id} plan=" . ($s->plan?->name ?? '?') . " model=" . ($s->plan?->billing_model ?? '?') . " status={$s->subscription_status_id} cycle=" . ($s->billing_cycle ?? 'null') . "\n";
}
