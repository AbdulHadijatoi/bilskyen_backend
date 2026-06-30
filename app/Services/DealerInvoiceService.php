<?php

namespace App\Services;

use App\Constants\DealerInvoiceStatus;
use App\Constants\ListingBillingPeriodStatus;
use App\Models\Dealer;
use App\Models\DealerInvoice;
use App\Models\DealerInvoiceLine;
use App\Models\ListingBillingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DealerInvoiceService
{
    public function generateMonthlyInvoices(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $invoices = collect();

        $dealerIds = ListingBillingPeriod::query()
            ->where('status', ListingBillingPeriodStatus::PENDING)
            ->whereBetween('billing_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->distinct()
            ->pluck('dealer_id');

        foreach ($dealerIds as $dealerId) {
            $invoice = $this->generateInvoiceForDealer((int) $dealerId, $periodStart, $periodEnd);
            if ($invoice) {
                $invoices->push($invoice);
            }
        }

        return $invoices;
    }

    public function generateInvoiceForDealer(int $dealerId, Carbon $periodStart, Carbon $periodEnd): ?DealerInvoice
    {
        return DB::transaction(function () use ($dealerId, $periodStart, $periodEnd) {
            $periods = ListingBillingPeriod::query()
                ->where('dealer_id', $dealerId)
                ->where('status', ListingBillingPeriodStatus::PENDING)
                ->whereBetween('billing_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->lockForUpdate()
                ->get();

            if ($periods->isEmpty()) {
                return null;
            }

            $invoice = DealerInvoice::create([
                'dealer_id' => $dealerId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_cents' => $periods->sum('amount_cents'),
                'currency' => 'DKK',
                'status' => DealerInvoiceStatus::DRAFT,
            ]);

            $grouped = $periods->groupBy('vehicle_id');

            foreach ($grouped as $vehicleId => $vehiclePeriods) {
                $first = $vehiclePeriods->first();
                $days = $vehiclePeriods->count();
                $unitPrice = (int) $first->amount_cents;

                DealerInvoiceLine::create([
                    'dealer_invoice_id' => $invoice->id,
                    'vehicle_id' => $vehicleId,
                    'description' => 'Listing daily usage',
                    'days' => $days,
                    'unit_price_cents' => $unitPrice,
                    'line_total_cents' => $vehiclePeriods->sum('amount_cents'),
                    'created_at' => now(),
                ]);
            }

            ListingBillingPeriod::whereIn('id', $periods->pluck('id'))->update([
                'status' => ListingBillingPeriodStatus::INVOICED,
                'dealer_invoice_id' => $invoice->id,
            ]);

            return $invoice->load(['lines', 'dealer']);
        });
    }

    public function markSent(DealerInvoice $invoice): DealerInvoice
    {
        $invoice->update([
            'status' => DealerInvoiceStatus::SENT,
            'sent_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function markPaid(DealerInvoice $invoice, ?int $approvedBy = null): DealerInvoice
    {
        $invoice->update([
            'status' => DealerInvoiceStatus::PAID,
            'paid_at' => now(),
            'approved_by' => $approvedBy,
        ]);

        return $invoice->fresh();
    }

    public function markOverdueInvoices(): int
    {
        $graceDays = (int) config('marketplace.invoice_payment_grace_days', 14);
        $cutoff = now()->subDays($graceDays);

        return DealerInvoice::query()
            ->where('status', DealerInvoiceStatus::SENT)
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $cutoff)
            ->update(['status' => DealerInvoiceStatus::OVERDUE]);
    }

    public function dealerHasBlockingInvoice(Dealer $dealer): bool
    {
        if (! config('marketplace.block_publish_on_overdue_invoice', true)) {
            return false;
        }

        return DealerInvoice::query()
            ->where('dealer_id', $dealer->id)
            ->where('status', DealerInvoiceStatus::OVERDUE)
            ->exists();
    }
}
