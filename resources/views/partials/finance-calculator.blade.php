@php
    use App\Helpers\FormatHelper;

    $price = (float) ($vehicle->price ?? 0);
    $settings = $financeSettings ?? [];
    $estimate = $financeEstimate ?? ['monthly_payment' => 0, 'term_months' => 60, 'annual_rate_pct' => 4.9];
@endphp
<div class="finance-calculator-widget" data-calculator-id="{{ $calculatorId ?? 'finance-calculator' }}">
    <h3 class="text-lg font-semibold text-foreground mb-3">{{ __('messages.finance.calculator_title') }}</h3>
    <p class="text-2xl font-bold text-primary mb-1" data-finance-monthly>
        {{ FormatHelper::formatCurrency($estimate['monthly_payment'] ?? 0) }}
        <span class="text-sm font-normal text-muted-foreground">/ {{ __('messages.finance.per_month') }}</span>
    </p>
    <p class="text-xs text-muted-foreground mb-4" data-finance-meta>
        {{ __('messages.finance.estimate_meta', [
            'rate' => $estimate['annual_rate_pct'] ?? 4.9,
            'months' => $estimate['term_months'] ?? 60,
        ]) }}
    </p>

    <div class="space-y-3">
        <label class="block text-sm">
            <span class="text-muted-foreground">{{ __('messages.finance.down_payment') }}</span>
            <input type="range" min="0" max="{{ (int) max(0, $price) }}" step="1000" value="0" class="w-full" data-finance-down />
        </label>
        <label class="block text-sm">
            <span class="text-muted-foreground">{{ __('messages.finance.term_months') }}</span>
            <input type="range" min="12" max="96" step="12" value="{{ (int) ($settings['default_term_months'] ?? 60) }}" class="w-full" data-finance-term />
        </label>
        <label class="block text-sm">
            <span class="text-muted-foreground">{{ __('messages.finance.interest_rate') }}</span>
            <input type="range" min="{{ (int) ($settings['min_rate_pct'] ?? 2) }}" max="{{ (int) ($settings['max_rate_pct'] ?? 13) }}" step="0.1" value="{{ (float) ($settings['default_rate_pct'] ?? 4.9) }}" class="w-full" data-finance-rate />
        </label>
    </div>

    <p class="text-xs text-muted-foreground mt-4">{{ $settings['disclaimer'] ?? '' }}</p>

    @if(!empty($financePartnerUrl))
    <a href="{{ $financePartnerUrl }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex text-sm font-medium text-primary hover:underline">
        {{ __('messages.finance.apply_with_partner') }}
    </a>
    @endif
</div>

<script>
(function () {
    const root = document.querySelector('[data-calculator-id="{{ $calculatorId ?? 'finance-calculator' }}"]');
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';
    const price = {{ json_encode($price) }};
    const monthlyEl = root.querySelector('[data-finance-monthly]');
    const metaEl = root.querySelector('[data-finance-meta]');
    const downEl = root.querySelector('[data-finance-down]');
    const termEl = root.querySelector('[data-finance-term]');
    const rateEl = root.querySelector('[data-finance-rate]');

    async function recalc() {
        const body = {
            price,
            down_payment: Number(downEl?.value || 0),
            term_months: Number(termEl?.value || 60),
            annual_rate_pct: Number(rateEl?.value || 4.9),
            website: '',
        };
        const res = await fetch('/api/v1/finance/calculate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body),
        });
        const json = await res.json();
        const data = json.data || json;
        if (monthlyEl && data.monthly_payment != null) {
            monthlyEl.innerHTML = new Intl.NumberFormat('da-DK', { style: 'currency', currency: 'DKK', maximumFractionDigits: 0 }).format(data.monthly_payment)
                + ' <span class="text-sm font-normal text-muted-foreground">/ {{ __('messages.finance.per_month') }}</span>';
        }
        if (metaEl) {
            metaEl.textContent = '{{ __('messages.finance.estimate_meta', ['rate' => ':rate', 'months' => ':months']) }}'
                .replace(':rate', data.annual_rate_pct).replace(':months', data.term_months);
        }
    }

    [downEl, termEl, rateEl].forEach((el) => el?.addEventListener('input', recalc));
})();
</script>
