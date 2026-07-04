<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerDealQuote;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;

class DealerDealQuoteService
{
    public function listForLead(Dealer $dealer, int $leadId): Collection
    {
        return DealerDealQuote::with(['vehicle', 'createdBy'])
            ->where('dealer_id', $dealer->id)
            ->where('lead_id', $leadId)
            ->orderByDesc('id')
            ->get();
    }

    public function create(Dealer $dealer, User $user, Lead $lead, array $data): DealerDealQuote
    {
        $monthlyPayment = $data['monthly_payment'] ?? $this->estimateMonthlyPayment($data);

        return DealerDealQuote::create([
            'dealer_id' => $dealer->id,
            'lead_id' => $lead->id,
            'vehicle_id' => $data['vehicle_id'] ?? $lead->vehicle_id,
            'created_by_user_id' => $user->id,
            'list_price' => (int) ($data['list_price'] ?? 0),
            'discount_amount' => (int) ($data['discount_amount'] ?? 0),
            'trade_in_value' => (int) ($data['trade_in_value'] ?? 0),
            'finance_apr' => isset($data['finance_apr']) ? (float) $data['finance_apr'] : null,
            'finance_term_months' => isset($data['finance_term_months']) ? (int) $data['finance_term_months'] : null,
            'monthly_payment' => $monthlyPayment,
            'notes' => $data['notes'] ?? null,
            'status' => 'draft',
        ]);
    }

    public function update(DealerDealQuote $quote, array $data): DealerDealQuote
    {
        if ($quote->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft quotes can be edited.');
        }

        $quote->update(collect($data)->only([
            'list_price', 'discount_amount', 'trade_in_value',
            'finance_apr', 'finance_term_months', 'monthly_payment', 'notes', 'vehicle_id',
        ])->filter(fn ($v) => $v !== null)->all());

        if (! isset($data['monthly_payment']) && (isset($data['finance_apr']) || isset($data['finance_term_months']))) {
            $quote->monthly_payment = $this->estimateMonthlyPayment($quote->toArray());
            $quote->save();
        }

        return $quote->fresh(['vehicle', 'createdBy']);
    }

    public function markSent(DealerDealQuote $quote): DealerDealQuote
    {
        $quote->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $quote->fresh(['vehicle', 'createdBy']);
    }

    private function estimateMonthlyPayment(array $data): ?int
    {
        $apr = (float) ($data['finance_apr'] ?? 0);
        $months = (int) ($data['finance_term_months'] ?? 0);
        $principal = max(0, (int) ($data['list_price'] ?? 0) - (int) ($data['discount_amount'] ?? 0) - (int) ($data['trade_in_value'] ?? 0));

        if ($apr <= 0 || $months <= 0 || $principal <= 0) {
            return null;
        }

        $monthlyRate = ($apr / 100) / 12;
        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);

        return (int) round($payment);
    }
}
