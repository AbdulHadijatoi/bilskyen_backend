@extends('layouts.app')

@php
    $honeypotField = config('security.honeypot.field', 'website');
@endphp

@section('content')
<style>
    .advisor-page {
        --advisor-navy: #03418b;
        --advisor-navy-soft: rgba(3, 65, 139, 0.08);
        --advisor-border: #e4e4e7;
        --advisor-muted: #71717a;
        --advisor-surface: #fafafa;
    }
    .advisor-page .advisor-hero {
        border-bottom: 1px solid var(--advisor-border);
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    }
    .advisor-page .advisor-input-wrap {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        border: 1px solid var(--advisor-border);
        border-radius: 0.75rem;
        background: #fff;
        padding: 0.75rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-input-wrap {
            flex-direction: row;
            align-items: flex-end;
            padding: 0.5rem 0.5rem 0.5rem 1rem;
        }
    }
    .advisor-page .advisor-input-wrap textarea {
        flex: 1;
        min-height: 5.5rem;
        border: 0;
        background: transparent;
        resize: vertical;
        padding: 0.5rem 0;
        font-size: 0.9375rem;
        line-height: 1.5;
        color: #18181b;
        outline: none;
        box-shadow: none;
    }
    .advisor-page .advisor-input-wrap textarea:focus {
        outline: none;
        box-shadow: none;
    }
    .advisor-page .advisor-submit {
        flex-shrink: 0;
        height: 2.75rem;
        padding: 0 1.25rem;
        border-radius: 0.5rem;
        background: var(--advisor-navy);
        color: #fff;
        font-size: 0.875rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .advisor-page .advisor-submit:hover { background: #023a7a; }
    .advisor-page .advisor-submit:disabled { opacity: 0.65; cursor: not-allowed; }
    .advisor-page .advisor-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--advisor-border);
        border-radius: 999px;
        background: #fff;
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        color: #3f3f46;
        transition: background 0.15s, border-color 0.15s;
    }
    .advisor-page .advisor-chip:hover {
        background: var(--advisor-surface);
        border-color: #d4d4d8;
    }
    .advisor-page .advisor-understood {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 1rem;
    }
    .advisor-page .advisor-understood-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: var(--advisor-navy-soft);
        color: var(--advisor-navy);
        padding: 0.3rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .advisor-page .advisor-summary-bar {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin: 0 -1rem 1.25rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--advisor-border);
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(8px);
    }
    @media (min-width: 768px) {
        .advisor-page .advisor-summary-bar {
            margin-left: 0;
            margin-right: 0;
            border: 1px solid var(--advisor-border);
            border-radius: 0.75rem;
        }
    }
    .advisor-page .advisor-row {
        display: grid;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid var(--advisor-border);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-row {
            grid-template-columns: 140px 1fr auto;
            align-items: start;
            gap: 1.25rem;
            padding: 1.25rem 0;
        }
    }
    .advisor-page .advisor-row.is-best {
        background: linear-gradient(90deg, var(--advisor-navy-soft), transparent 60%);
        margin: 0 -0.75rem;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        border-radius: 0.5rem;
    }
    .advisor-page .advisor-row-photo {
        display: block;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        border-radius: 0.5rem;
        background: var(--advisor-surface);
        border: 1px solid var(--advisor-border);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-row-photo {
            width: 140px;
            aspect-ratio: 4 / 3;
        }
    }
    .advisor-page .advisor-row-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .advisor-page .advisor-match {
        min-width: 5.5rem;
        text-align: right;
    }
    @media (max-width: 639px) {
        .advisor-page .advisor-match {
            text-align: left;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
    }
    .advisor-page .advisor-match-bar {
        width: 100%;
        max-width: 5.5rem;
        height: 0.35rem;
        border-radius: 999px;
        background: #e4e4e7;
        overflow: hidden;
        margin-top: 0.35rem;
    }
    @media (max-width: 639px) {
        .advisor-page .advisor-match-bar { max-width: 6rem; margin-top: 0; }
    }
    .advisor-page .advisor-match-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--advisor-navy);
    }
    .advisor-page .advisor-best-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: var(--advisor-navy);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        padding: 0.2rem 0.5rem;
        margin-bottom: 0.35rem;
    }
    .advisor-page .advisor-meta-line {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0.85rem;
        margin-top: 0.35rem;
        font-size: 0.8125rem;
        color: var(--advisor-muted);
    }
    .advisor-page .advisor-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.85rem;
    }
    .advisor-page .advisor-btn-primary {
        display: inline-flex;
        align-items: center;
        height: 2.25rem;
        padding: 0 0.9rem;
        border-radius: 0.45rem;
        background: var(--advisor-navy);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .advisor-page .advisor-btn-primary:hover { background: #023a7a; }
    .advisor-page .advisor-btn-ghost {
        display: inline-flex;
        align-items: center;
        height: 2.25rem;
        padding: 0 0.85rem;
        border-radius: 0.45rem;
        border: 1px solid var(--advisor-border);
        background: #fff;
        font-size: 0.75rem;
        font-weight: 500;
        color: #3f3f46;
    }
    .advisor-page .advisor-btn-ghost:hover { background: var(--advisor-surface); }
    .advisor-page .advisor-details {
        margin-top: 0.75rem;
        border-top: 1px solid #f4f4f5;
        padding-top: 0.65rem;
    }
    .advisor-page .advisor-details summary {
        cursor: pointer;
        list-style: none;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--advisor-navy);
        user-select: none;
    }
    .advisor-page .advisor-details summary::-webkit-details-marker { display: none; }
    .advisor-page .advisor-details[open] summary { margin-bottom: 0.5rem; }
    .advisor-page .advisor-details-body {
        font-size: 0.8125rem;
        line-height: 1.55;
        color: #3f3f46;
    }
    .advisor-page .advisor-details-body ul {
        margin: 0.4rem 0 0;
        padding-left: 1.1rem;
        color: var(--advisor-muted);
    }
    .advisor-page .advisor-skeleton {
        display: grid;
        gap: 1rem;
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--advisor-border);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-skeleton {
            grid-template-columns: 140px 1fr 5rem;
        }
    }
    .advisor-page .advisor-skel {
        border-radius: 0.4rem;
        background: linear-gradient(90deg, #f4f4f5 25%, #e4e4e7 50%, #f4f4f5 75%);
        background-size: 200% 100%;
        animation: advisor-shimmer 1.2s ease-in-out infinite;
    }
    @keyframes advisor-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .advisor-page .advisor-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        border: 1px dashed var(--advisor-border);
        border-radius: 0.75rem;
        background: var(--advisor-surface);
    }
    .advisor-page .advisor-examples-panel[hidden],
    .advisor-page .advisor-refine[hidden],
    .advisor-page .advisor-results[hidden] {
        display: none !important;
    }
</style>

<div class="advisor-page flex min-h-screen flex-col" id="car-advisor-page"
     data-locale="{{ app()->getLocale() }}"
     data-api-url="{{ url('/api/v1/ai/car-advisor') }}"
     data-save-url="{{ url('/saved-searches') }}"
     data-honeypot="{{ $honeypotField }}"
     data-ai-enabled="{{ !empty($publicAiEnabled) ? '1' : '0' }}"
     data-vehicles-url="{{ route('vehicles') }}"
     data-login-url="{{ route('login') }}">

    <section class="advisor-hero py-8 md:py-10" aria-labelledby="advisor-heading">
        <div class="container mx-auto max-w-3xl px-4 md:px-6">
            <h1 id="advisor-heading" class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                {{ __('messages.pages.find_perfect_car.title') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground md:text-base">
                {{ __('messages.pages.find_perfect_car.subtitle') }}
            </p>
        </div>
    </section>

    <section class="flex-1 pb-12 pt-6 md:pb-16 md:pt-8">
        <div class="container mx-auto max-w-3xl px-4 md:px-6">
            @if(empty($publicAiEnabled))
                <div class="rounded-lg border border-border bg-card p-6 text-center">
                    <p class="text-muted-foreground">{{ __('messages.pages.find_perfect_car.disabled') }}</p>
                    <a href="{{ route('vehicles') }}" class="advisor-btn-primary mt-4">
                        {{ __('messages.pages.home.browse_vehicles') }}
                    </a>
                </div>
            @else
                <form id="advisor-form" autocomplete="off">
                    <input type="text" name="{{ $honeypotField }}" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                    <label for="advisor-input" class="sr-only">{{ __('messages.pages.find_perfect_car.placeholder') }}</label>
                    <div class="advisor-input-wrap">
                        <textarea
                            id="advisor-input"
                            rows="3"
                            maxlength="2000"
                            required
                            placeholder="{{ __('messages.pages.find_perfect_car.placeholder') }}"
                        >{{ $advisorPrefill ?? '' }}</textarea>
                        <button type="submit" id="advisor-submit" class="advisor-submit">
                            {{ __('messages.pages.find_perfect_car.submit') }}
                        </button>
                    </div>
                    <p id="advisor-status" class="mt-2 hidden text-sm text-muted-foreground" aria-live="polite"></p>
                    <p id="advisor-error" class="mt-2 hidden text-sm text-destructive" role="alert"></p>
                </form>

                <div id="advisor-understood" class="advisor-understood hidden" aria-live="polite"></div>

                @if(!empty($advisorExamples))
                <div class="mt-4">
                    <button type="button" id="advisor-examples-toggle" class="text-xs font-medium text-primary hover:underline" aria-expanded="false" aria-controls="advisor-examples-panel">
                        {{ __('messages.pages.find_perfect_car.examples_label') }}
                    </button>
                    <div id="advisor-examples-panel" class="advisor-examples-panel mt-2 hidden" hidden>
                        <div class="flex flex-wrap gap-2" id="advisor-examples">
                            @foreach($advisorExamples as $example)
                                <button type="button" class="advisor-example-chip advisor-chip" data-example="{{ $example }}">
                                    {{ \Illuminate\Support\Str::limit($example, 64) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div id="advisor-loading" class="mt-8 hidden" aria-hidden="true">
                    <div class="advisor-skeleton">
                        <div class="advisor-skel" style="aspect-ratio:4/3"></div>
                        <div class="space-y-2 py-1">
                            <div class="advisor-skel" style="height:1rem;width:75%"></div>
                            <div class="advisor-skel" style="height:0.75rem;width:50%"></div>
                            <div class="advisor-skel" style="height:0.75rem;width:66%"></div>
                        </div>
                        <div class="advisor-skel justify-self-end" style="height:2rem;width:3.5rem"></div>
                    </div>
                    <div class="advisor-skeleton">
                        <div class="advisor-skel" style="aspect-ratio:4/3"></div>
                        <div class="space-y-2 py-1">
                            <div class="advisor-skel" style="height:1rem;width:66%"></div>
                            <div class="advisor-skel" style="height:0.75rem;width:50%"></div>
                            <div class="advisor-skel" style="height:0.75rem;width:40%"></div>
                        </div>
                        <div class="advisor-skel justify-self-end" style="height:2rem;width:3.5rem"></div>
                    </div>
                    <div class="advisor-skeleton">
                        <div class="advisor-skel" style="aspect-ratio:4/3"></div>
                        <div class="space-y-2 py-1">
                            <div class="advisor-skel" style="height:1rem;width:80%"></div>
                            <div class="advisor-skel" style="height:0.75rem;width:45%"></div>
                        </div>
                        <div class="advisor-skel justify-self-end" style="height:2rem;width:3.5rem"></div>
                    </div>
                </div>

                <div id="advisor-results" class="advisor-results mt-8 hidden" hidden>
                    <div class="advisor-summary-bar">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold tracking-tight md:text-lg">{{ __('messages.pages.find_perfect_car.results_heading') }}</h2>
                            <p id="advisor-summary" class="mt-0.5 text-sm text-muted-foreground"></p>
                            <p id="advisor-meta" class="mt-0.5 text-xs text-muted-foreground"></p>
                            <p id="advisor-relaxed" class="mt-1 hidden text-xs text-amber-700">{{ __('messages.pages.find_perfect_car.relaxed_note') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a id="advisor-browse-link" href="{{ route('vehicles') }}" class="advisor-btn-ghost">
                                {{ __('messages.pages.find_perfect_car.browse_all') }}
                            </a>
                            <button type="button" id="advisor-save-search" class="advisor-btn-ghost">
                                {{ __('messages.pages.find_perfect_car.save_search') }}
                            </button>
                        </div>
                    </div>
                    <p id="advisor-save-msg" class="mb-3 hidden text-xs text-muted-foreground" role="status"></p>
                    <div id="advisor-cards"></div>
                </div>

                <div id="advisor-refine" class="advisor-refine mt-6 hidden" hidden>
                    <p class="mb-2 text-xs font-medium text-muted-foreground">{{ __('messages.pages.find_perfect_car.refine_label') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'Prefer cheaper options within my budget' : 'Foretræk billigere valg inden for mit budget' }}">{{ __('messages.pages.find_perfect_car.refine_cheaper') }}</button>
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'I need more cabin and cargo space' : 'Jeg har brug for mere kabine- og bagagerumsplads' }}">{{ __('messages.pages.find_perfect_car.refine_space') }}</button>
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'Prioritise city driving fitness' : 'Prioritér bykørsel' }}">{{ __('messages.pages.find_perfect_car.refine_city') }}</button>
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'Lower ownership tax is more important' : 'Lavere ejerafgift er vigtigere' }}">{{ __('messages.pages.find_perfect_car.refine_tax') }}</button>
                    </div>
                </div>

                <p class="mt-10 text-xs leading-relaxed text-muted-foreground">
                    {{ __('messages.pages.find_perfect_car.disclaimer') }}
                </p>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.getElementById('car-advisor-page');
    if (!page || page.dataset.aiEnabled !== '1') return;

    const I18N = {
        thinking: @json(__('messages.pages.find_perfect_car.thinking')),
        error: @json(__('messages.pages.find_perfect_car.error')),
        empty: @json(__('messages.pages.find_perfect_car.empty')),
        emptyBrowse: @json(__('messages.pages.find_perfect_car.empty_browse')),
        match: @json(__('messages.pages.find_perfect_car.match')),
        bestMatch: @json(__('messages.pages.find_perfect_car.best_match')),
        why: @json(__('messages.pages.find_perfect_car.why')),
        whyToggle: @json(__('messages.pages.find_perfect_car.why_toggle')),
        tradeoffs: @json(__('messages.pages.find_perfect_car.tradeoffs')),
        ownership: @json(__('messages.pages.find_perfect_car.ownership')),
        ownershipTax: @json(__('messages.pages.find_perfect_car.ownership_tax')),
        fairPrice: @json(__('messages.pages.find_perfect_car.fair_price')),
        viewListing: @json(__('messages.pages.find_perfect_car.view_listing')),
        enquire: @json(__('messages.pages.find_perfect_car.enquire')),
        candidateCount: @json(__('messages.pages.find_perfect_car.candidate_count')),
        understood: @json(__('messages.pages.find_perfect_car.understood')),
        saveOk: @json(__('messages.pages.find_perfect_car.save_search_ok')),
        saveFail: @json(__('messages.pages.find_perfect_car.save_search_fail')),
        saveLogin: @json(__('messages.pages.find_perfect_car.save_search_login')),
    };

    const form = document.getElementById('advisor-form');
    const input = document.getElementById('advisor-input');
    const submitBtn = document.getElementById('advisor-submit');
    const statusEl = document.getElementById('advisor-status');
    const errorEl = document.getElementById('advisor-error');
    const resultsEl = document.getElementById('advisor-results');
    const cardsEl = document.getElementById('advisor-cards');
    const summaryEl = document.getElementById('advisor-summary');
    const metaEl = document.getElementById('advisor-meta');
    const relaxedEl = document.getElementById('advisor-relaxed');
    const browseLink = document.getElementById('advisor-browse-link');
    const refineEl = document.getElementById('advisor-refine');
    const saveBtn = document.getElementById('advisor-save-search');
    const saveMsg = document.getElementById('advisor-save-msg');
    const loadingEl = document.getElementById('advisor-loading');
    const understoodEl = document.getElementById('advisor-understood');
    const examplesToggle = document.getElementById('advisor-examples-toggle');
    const examplesPanel = document.getElementById('advisor-examples-panel');
    const honeypotName = page.dataset.honeypot || 'website';
    const vehiclesUrl = page.dataset.vehiclesUrl || '/vehicles';

    let history = [];
    let lastFilters = {};
    let lastSummary = '';

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function show(el) {
        if (!el) return;
        el.classList.remove('hidden');
        el.removeAttribute('hidden');
    }

    function hide(el) {
        if (!el) return;
        el.classList.add('hidden');
        el.setAttribute('hidden', '');
    }

    function setLoading(on) {
        submitBtn.disabled = on;
        statusEl.classList.toggle('hidden', !on);
        statusEl.textContent = on ? I18N.thinking : '';
        if (on) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
            hide(resultsEl);
            hide(refineEl);
            show(loadingEl);
        } else {
            hide(loadingEl);
        }
    }

    function showError(msg) {
        errorEl.textContent = msg || I18N.error;
        errorEl.classList.remove('hidden');
        hide(loadingEl);
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatKm(km) {
        if (km == null || km === '') return '';
        try {
            return Number(km).toLocaleString() + ' km';
        } catch (e) {
            return String(km) + ' km';
        }
    }

    function renderUnderstood(labels) {
        if (!understoodEl) return;
        const chips = Array.isArray(labels) ? labels.filter(Boolean) : [];
        if (!chips.length) {
            hide(understoodEl);
            understoodEl.innerHTML = '';
            return;
        }
        const items = chips.map(function (item) {
            const text = typeof item === 'string' ? item : (item.label || item.key || '');
            if (!text) return '';
            return `<span class="advisor-understood-chip">${escapeHtml(text)}</span>`;
        }).filter(Boolean).join('');
        understoodEl.innerHTML = items
            ? `<span class="w-full text-xs font-medium text-muted-foreground mb-0.5">${escapeHtml(I18N.understood)}</span>${items}`
            : '';
        if (items) show(understoodEl);
        else hide(understoodEl);
    }

    function renderCard(rec, index) {
        const isBest = index === 0;
        const pct = Math.max(0, Math.min(100, Number(rec.match_percent) || 0));
        const reasons = (rec.match_reasons || []).map(function (r) {
            return `<li>${escapeHtml(r)}</li>`;
        }).join('');
        const tradeoffs = (rec.tradeoffs || []).map(function (t) {
            return `<li>${escapeHtml(t)}</li>`;
        }).join('');
        const fair = rec.fair_price && rec.fair_price.label_text
            ? `<span>${escapeHtml(I18N.fairPrice)}: ${escapeHtml(rec.fair_price.label_text)}</span>`
            : '';
        const tax = rec.ownership_tax_formatted
            ? `<span>${escapeHtml(I18N.ownershipTax)}: ${escapeHtml(rec.ownership_tax_formatted)}</span>`
            : '';
        const img = rec.image_url
            ? `<img src="${escapeHtml(rec.image_url)}" alt="" loading="lazy">`
            : `<div class="flex h-full items-center justify-center text-xs text-muted-foreground">Bilskyen</div>`;

        const metaParts = [];
        if (rec.price_formatted) metaParts.push(`<strong class="font-semibold text-foreground">${escapeHtml(rec.price_formatted)}</strong>`);
        if (rec.year) metaParts.push(`<span>${escapeHtml(String(rec.year))}</span>`);
        if (rec.km_driven != null) metaParts.push(`<span>${escapeHtml(formatKm(rec.km_driven))}</span>`);
        if (rec.fuel) metaParts.push(`<span>${escapeHtml(rec.fuel)}</span>`);

        const detailBits = [];
        if (rec.explanation) {
            detailBits.push(`<p>${escapeHtml(rec.explanation)}</p>`);
        }
        if (reasons) {
            detailBits.push(`<p class="mt-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">${escapeHtml(I18N.why)}</p><ul>${reasons}</ul>`);
        }
        if (tradeoffs) {
            detailBits.push(`<p class="mt-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">${escapeHtml(I18N.tradeoffs)}</p><ul>${tradeoffs}</ul>`);
        }
        if (rec.ownership_outlook) {
            detailBits.push(`<p class="mt-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">${escapeHtml(I18N.ownership)}</p><p class="mt-1 text-muted-foreground">${escapeHtml(rec.ownership_outlook)}</p>`);
        }

        return `
        <article class="advisor-row${isBest ? ' is-best' : ''}">
            <a href="${escapeHtml(rec.detail_url)}" class="advisor-row-photo">${img}</a>
            <div class="min-w-0">
                ${isBest ? `<span class="advisor-best-badge">${escapeHtml(I18N.bestMatch)}</span>` : ''}
                <h3 class="text-base font-semibold leading-snug text-foreground md:text-lg">
                    <a href="${escapeHtml(rec.detail_url)}" class="hover:underline">${escapeHtml(rec.title)}</a>
                </h3>
                <div class="advisor-meta-line">${metaParts.join('')}</div>
                ${(tax || fair) ? `<div class="advisor-meta-line">${tax}${fair}</div>` : ''}
                <div class="advisor-actions">
                    <a href="${escapeHtml(rec.detail_url)}" class="advisor-btn-primary">${escapeHtml(I18N.viewListing)}</a>
                    <a href="${escapeHtml(rec.enquire_url)}" class="advisor-btn-ghost">${escapeHtml(I18N.enquire)}</a>
                </div>
                ${detailBits.length ? `
                <details class="advisor-details">
                    <summary>${escapeHtml(I18N.whyToggle)}</summary>
                    <div class="advisor-details-body">${detailBits.join('')}</div>
                </details>` : ''}
            </div>
            <div class="advisor-match">
                <div>
                    <div class="text-[0.65rem] uppercase tracking-wide text-muted-foreground">${escapeHtml(I18N.match)}</div>
                    <div class="text-lg font-bold text-primary">${escapeHtml(String(rec.match_percent))}%</div>
                </div>
                <div class="advisor-match-bar" aria-hidden="true">
                    <div class="advisor-match-fill" style="width:${pct}%"></div>
                </div>
            </div>
        </article>`;
    }

    function renderEmpty() {
        return `
        <div class="advisor-empty">
            <p class="text-sm text-muted-foreground">${escapeHtml(I18N.empty)}</p>
            <a href="${escapeHtml(vehiclesUrl)}" class="advisor-btn-primary mt-4 inline-flex">${escapeHtml(I18N.emptyBrowse)}</a>
        </div>`;
    }

    function renderResults(data) {
        lastFilters = data.filters || {};
        lastSummary = data.summary || '';
        summaryEl.textContent = data.summary || '';
        metaEl.textContent = I18N.candidateCount.replace(':count', String(data.candidate_count || 0));
        relaxedEl.classList.toggle('hidden', !data.relaxed_filters);
        if (data.browse_url) {
            browseLink.href = data.browse_url;
        }
        renderUnderstood(data.labels || []);

        const recs = data.recommendations || [];
        if (!recs.length) {
            cardsEl.innerHTML = renderEmpty();
        } else {
            cardsEl.innerHTML = recs.map(renderCard).join('');
        }
        show(resultsEl);
        show(refineEl);
        if (examplesPanel) {
            hide(examplesPanel);
            if (examplesToggle) examplesToggle.setAttribute('aria-expanded', 'false');
        }
    }

    async function advise(message, asRefine) {
        const text = (message || '').trim();
        if (!text) return;
        setLoading(true);
        try {
            const body = {
                message: text,
                locale: page.dataset.locale || 'da',
                history: history.slice(-6),
            };
            body[honeypotName] = '';

            const res = await fetch(page.dataset.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            const json = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                showError((json && json.message) || I18N.error);
                return;
            }
            const data = json.data || json;
            history.push({ role: 'user', content: text });
            history.push({ role: 'assistant', content: data.summary || '' });
            if (!asRefine) {
                input.value = text;
            }
            renderResults(data);
            resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e) {
            showError(I18N.error);
        } finally {
            setLoading(false);
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        advise(input.value, false);
    });

    if (examplesToggle && examplesPanel) {
        examplesToggle.addEventListener('click', function () {
            const open = examplesPanel.hasAttribute('hidden');
            if (open) {
                show(examplesPanel);
                examplesToggle.setAttribute('aria-expanded', 'true');
            } else {
                hide(examplesPanel);
                examplesToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('.advisor-example-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const ex = btn.getAttribute('data-example') || '';
            input.value = ex;
            history = [];
            advise(ex, false);
        });
    });

    document.querySelectorAll('.advisor-refine-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const refine = btn.getAttribute('data-refine') || '';
            advise(refine, true);
        });
    });

    saveBtn.addEventListener('click', async function () {
        saveMsg.classList.add('hidden');
        if (!lastFilters || !Object.keys(lastFilters).length) {
            saveMsg.textContent = I18N.saveFail;
            saveMsg.classList.remove('hidden');
            return;
        }
        try {
            const res = await fetch(page.dataset.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name: (lastSummary || 'Find My Perfect Car').slice(0, 80),
                    filters: lastFilters,
                }),
            });
            if (res.status === 401) {
                saveMsg.textContent = I18N.saveLogin;
                saveMsg.classList.remove('hidden');
                window.location.href = page.dataset.loginUrl || '/login';
                return;
            }
            if (!res.ok) {
                saveMsg.textContent = I18N.saveFail;
                saveMsg.classList.remove('hidden');
                return;
            }
            saveMsg.textContent = I18N.saveOk;
            saveMsg.classList.remove('hidden');
        } catch (e) {
            saveMsg.textContent = I18N.saveFail;
            saveMsg.classList.remove('hidden');
        }
    });

    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    if (q && !input.value) {
        input.value = q;
    }
})();
</script>
@endpush
