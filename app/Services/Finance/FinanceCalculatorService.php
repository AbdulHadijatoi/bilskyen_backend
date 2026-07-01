<?php

namespace App\Services\Finance;

use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\PlatformSettingService;

class FinanceCalculatorService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function settingsForLocale(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $disclaimerKey = str_starts_with($locale, 'da') ? 'disclaimer_da' : 'disclaimer_en';

        return [
            'calculator_enabled' => $this->isPlatformCalculatorEnabled(),
            'default_rate_pct' => (float) $this->platformSettingService->get('finance', 'default_rate_pct', 4.9),
            'min_rate_pct' => (float) $this->platformSettingService->get('finance', 'min_rate_pct', 2.9),
            'max_rate_pct' => (float) $this->platformSettingService->get('finance', 'max_rate_pct', 12.9),
            'default_term_months' => (int) $this->platformSettingService->get('finance', 'default_term_months', 60),
            'disclaimer' => (string) $this->platformSettingService->get('finance', $disclaimerKey, ''),
        ];
    }

    public function dealerFinanceUrl(?Dealer $dealer): ?string
    {
        return $dealer?->finance_partner_url;
    }

    public function isPlatformCalculatorEnabled(): bool
    {
        return filter_var(
            $this->platformSettingService->get('finance', 'calculator_enabled', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function isCalculatorEnabledForDealer(?Dealer $dealer): bool
    {
        if (! $this->isPlatformCalculatorEnabled()) {
            return false;
        }

        if ($dealer === null) {
            return true;
        }

        if ($dealer->finance_calculator_enabled === null) {
            return true;
        }

        return (bool) $dealer->finance_calculator_enabled;
    }

    public function shouldShowCalculatorForVehicle(Vehicle $vehicle): bool
    {
        if ((float) ($vehicle->price ?? 0) <= 0) {
            return false;
        }

        $vehicle->loadMissing('dealer');

        return $this->isCalculatorEnabledForDealer($vehicle->dealer);
    }

    /**
     * Standard amortizing monthly payment (price - down payment, annual rate, term in months).
     */
    public function calculateMonthlyPayment(
        float $price,
        float $downPayment = 0,
        ?float $annualRatePct = null,
        ?int $termMonths = null
    ): array {
        if ($annualRatePct === null || $termMonths === null) {
            $settings = $this->settingsForLocale();
            $annualRatePct = $annualRatePct ?? $settings['default_rate_pct'];
            $termMonths = $termMonths ?? $settings['default_term_months'];
        }

        $principal = max(0, $price - $downPayment);
        if ($principal <= 0 || $termMonths <= 0) {
            return [
                'monthly_payment' => 0,
                'principal' => $principal,
                'annual_rate_pct' => $annualRatePct,
                'term_months' => $termMonths,
                'total_paid' => 0,
                'total_interest' => 0,
            ];
        }

        $monthlyRate = ($annualRatePct / 100) / 12;
        if ($monthlyRate <= 0) {
            $monthly = $principal / $termMonths;
        } else {
            $monthly = $principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths))
                / (pow(1 + $monthlyRate, $termMonths) - 1);
        }

        $totalPaid = $monthly * $termMonths;
        $totalInterest = max(0, $totalPaid - $principal);

        return [
            'monthly_payment' => round($monthly, 2),
            'principal' => round($principal, 2),
            'annual_rate_pct' => $annualRatePct,
            'term_months' => $termMonths,
            'total_paid' => round($totalPaid, 2),
            'total_interest' => round($totalInterest, 2),
        ];
    }
}
