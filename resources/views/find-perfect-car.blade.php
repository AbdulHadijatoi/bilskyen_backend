@extends('layouts.app')

@php
    $honeypotField = config('security.honeypot.field', 'website');
@endphp

@section('content')
<div class="flex min-h-screen flex-col" id="car-advisor-page"
     data-locale="{{ app()->getLocale() }}"
     data-api-url="{{ url('/api/v1/ai/car-advisor') }}"
     data-save-url="{{ url('/saved-searches') }}"
     data-honeypot="{{ $honeypotField }}"
     data-ai-enabled="{{ !empty($publicAiEnabled) ? '1' : '0' }}"
     data-vehicles-url="{{ route('vehicles') }}"
     data-login-url="{{ route('login') }}">
    <section class="bg-muted py-12 text-center md:py-16" aria-labelledby="advisor-heading">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            <p class="text-sm font-semibold tracking-wide text-primary uppercase mb-3">Bilskyen</p>
            <h1 id="advisor-heading" class="text-3xl font-bold tracking-tight md:text-5xl">
                {{ __('messages.pages.find_perfect_car.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-base leading-relaxed md:text-lg">
                {{ __('messages.pages.find_perfect_car.subtitle') }}
            </p>
        </div>
    </section>

    <section class="py-10 md:py-14">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            @if(empty($publicAiEnabled))
                <div class="rounded-lg border border-border bg-card p-6 text-center">
                    <p class="text-muted-foreground">{{ __('messages.pages.find_perfect_car.disabled') }}</p>
                    <a href="{{ route('vehicles') }}" class="mt-4 inline-flex h-10 items-center justify-center rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground">
                        {{ __('messages.pages.home.browse_vehicles') }}
                    </a>
                </div>
            @else
                <form id="advisor-form" class="space-y-4" autocomplete="off">
                    <input type="text" name="{{ $honeypotField }}" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                    <label for="advisor-input" class="sr-only">{{ __('messages.pages.find_perfect_car.placeholder') }}</label>
                    <textarea
                        id="advisor-input"
                        rows="4"
                        maxlength="2000"
                        required
                        class="w-full rounded-xl border border-border bg-card px-4 py-3 text-sm md:text-base text-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('messages.pages.find_perfect_car.placeholder') }}"
                    ></textarea>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <button type="submit" id="advisor-submit" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground shadow hover:bg-primary/90">
                            {{ __('messages.pages.find_perfect_car.submit') }}
                        </button>
                        <p id="advisor-status" class="text-sm text-muted-foreground hidden" aria-live="polite"></p>
                    </div>
                    <p id="advisor-error" class="text-sm text-destructive hidden" role="alert"></p>
                </form>

                @if(!empty($advisorExamples))
                <div class="mt-6">
                    <p class="text-sm font-medium text-muted-foreground mb-2">{{ __('messages.pages.find_perfect_car.examples_label') }}</p>
                    <div class="flex flex-wrap gap-2" id="advisor-examples">
                        @foreach($advisorExamples as $example)
                            <button type="button" class="advisor-example-chip rounded-full border border-border bg-background px-3 py-1.5 text-left text-xs text-foreground hover:bg-muted transition-colors" data-example="{{ $example }}">
                                {{ \Illuminate\Support\Str::limit($example, 72) }}
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif

                <div id="advisor-refine" class="mt-8 hidden">
                    <p class="text-sm font-medium text-muted-foreground mb-2">{{ __('messages.pages.find_perfect_car.refine_label') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="advisor-refine-chip rounded-full border border-border px-3 py-1.5 text-xs hover:bg-muted" data-refine="{{ app()->getLocale() === 'en' ? 'Prefer cheaper options within my budget' : 'Foretræk billigere valg inden for mit budget' }}">{{ __('messages.pages.find_perfect_car.refine_cheaper') }}</button>
                        <button type="button" class="advisor-refine-chip rounded-full border border-border px-3 py-1.5 text-xs hover:bg-muted" data-refine="{{ app()->getLocale() === 'en' ? 'I need more cabin and cargo space' : 'Jeg har brug for mere kabine- og bagagerumsplads' }}">{{ __('messages.pages.find_perfect_car.refine_space') }}</button>
                        <button type="button" class="advisor-refine-chip rounded-full border border-border px-3 py-1.5 text-xs hover:bg-muted" data-refine="{{ app()->getLocale() === 'en' ? 'Prioritise city driving fitness' : 'Prioritér bykørsel' }}">{{ __('messages.pages.find_perfect_car.refine_city') }}</button>
                        <button type="button" class="advisor-refine-chip rounded-full border border-border px-3 py-1.5 text-xs hover:bg-muted" data-refine="{{ app()->getLocale() === 'en' ? 'Lower ownership tax is more important' : 'Lavere ejerafgift er vigtigere' }}">{{ __('messages.pages.find_perfect_car.refine_tax') }}</button>
                    </div>
                </div>

                <div id="advisor-results" class="mt-10 hidden">
                    <div class="mb-6 space-y-2">
                        <h2 class="text-2xl font-bold tracking-tight">{{ __('messages.pages.find_perfect_car.results_heading') }}</h2>
                        <p id="advisor-summary" class="text-muted-foreground"></p>
                        <p id="advisor-meta" class="text-xs text-muted-foreground"></p>
                        <p id="advisor-relaxed" class="text-xs text-amber-700 dark:text-amber-400 hidden">{{ __('messages.pages.find_perfect_car.relaxed_note') }}</p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <a id="advisor-browse-link" href="{{ route('vehicles') }}" class="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-accent">
                                {{ __('messages.pages.find_perfect_car.browse_all') }}
                            </a>
                            <button type="button" id="advisor-save-search" class="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-accent">
                                {{ __('messages.pages.find_perfect_car.save_search') }}
                            </button>
                        </div>
                        <p id="advisor-save-msg" class="text-xs text-muted-foreground hidden" role="status"></p>
                    </div>
                    <div id="advisor-cards" class="space-y-5"></div>
                </div>

                <p class="mt-10 text-xs text-muted-foreground leading-relaxed">
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
        match: @json(__('messages.pages.find_perfect_car.match')),
        why: @json(__('messages.pages.find_perfect_car.why')),
        tradeoffs: @json(__('messages.pages.find_perfect_car.tradeoffs')),
        ownership: @json(__('messages.pages.find_perfect_car.ownership')),
        ownershipTax: @json(__('messages.pages.find_perfect_car.ownership_tax')),
        fairPrice: @json(__('messages.pages.find_perfect_car.fair_price')),
        viewListing: @json(__('messages.pages.find_perfect_car.view_listing')),
        enquire: @json(__('messages.pages.find_perfect_car.enquire')),
        candidateCount: @json(__('messages.pages.find_perfect_car.candidate_count')),
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
    const honeypotName = page.dataset.honeypot || 'website';

    let history = [];
    let lastFilters = {};
    let lastSummary = '';

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setLoading(on) {
        submitBtn.disabled = on;
        statusEl.classList.toggle('hidden', !on);
        statusEl.textContent = on ? I18N.thinking : '';
        if (on) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
        }
    }

    function showError(msg) {
        errorEl.textContent = msg || I18N.error;
        errorEl.classList.remove('hidden');
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderCard(rec) {
        const reasons = (rec.match_reasons || []).map(r => `<li>${escapeHtml(r)}</li>`).join('');
        const tradeoffs = (rec.tradeoffs || []).map(t => `<li>${escapeHtml(t)}</li>`).join('');
        const fair = rec.fair_price && rec.fair_price.label_text
            ? `<span class="text-xs rounded-full bg-muted px-2 py-0.5">${escapeHtml(I18N.fairPrice)}: ${escapeHtml(rec.fair_price.label_text)}</span>`
            : '';
        const tax = rec.ownership_tax_formatted
            ? `<span class="text-xs rounded-full bg-muted px-2 py-0.5">${escapeHtml(I18N.ownershipTax)}: ${escapeHtml(rec.ownership_tax_formatted)}</span>`
            : '';
        const img = rec.image_url
            ? `<img src="${escapeHtml(rec.image_url)}" alt="" class="h-full w-full object-cover" loading="lazy">`
            : `<div class="flex h-full items-center justify-center text-xs text-muted-foreground">Bilskyen</div>`;

        return `
        <article class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="grid gap-0 md:grid-cols-[200px_1fr]">
                <a href="${escapeHtml(rec.detail_url)}" class="block aspect-[4/3] md:aspect-auto md:min-h-[180px] bg-muted overflow-hidden">${img}</a>
                <div class="p-4 md:p-5 space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold leading-snug">
                                <a href="${escapeHtml(rec.detail_url)}" class="hover:underline">${escapeHtml(rec.title)}</a>
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                ${escapeHtml(rec.price_formatted || '')}
                                ${rec.year ? ' · ' + escapeHtml(String(rec.year)) : ''}
                                ${rec.km_driven != null ? ' · ' + escapeHtml(Number(rec.km_driven).toLocaleString()) + ' km' : ''}
                                ${rec.fuel ? ' · ' + escapeHtml(rec.fuel) : ''}
                            </p>
                        </div>
                        <div class="rounded-lg bg-primary/10 px-3 py-2 text-center">
                            <div class="text-xs text-muted-foreground">${escapeHtml(I18N.match)}</div>
                            <div class="text-xl font-bold text-primary">${escapeHtml(String(rec.match_percent))}%</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">${tax}${fair}</div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">${escapeHtml(I18N.why)}</p>
                        <p class="text-sm leading-relaxed">${escapeHtml(rec.explanation || '')}</p>
                        ${reasons ? `<ul class="mt-2 list-disc pl-5 text-sm text-muted-foreground space-y-0.5">${reasons}</ul>` : ''}
                    </div>
                    ${tradeoffs ? `<div><p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">${escapeHtml(I18N.tradeoffs)}</p><ul class="list-disc pl-5 text-sm text-muted-foreground space-y-0.5">${tradeoffs}</ul></div>` : ''}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">${escapeHtml(I18N.ownership)}</p>
                        <p class="text-sm text-muted-foreground">${escapeHtml(rec.ownership_outlook || '')}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <a href="${escapeHtml(rec.detail_url)}" class="inline-flex h-9 items-center rounded-md bg-primary px-3 text-xs font-semibold text-primary-foreground">${escapeHtml(I18N.viewListing)}</a>
                        <a href="${escapeHtml(rec.enquire_url)}" class="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-accent">${escapeHtml(I18N.enquire)}</a>
                    </div>
                </div>
            </div>
        </article>`;
    }

    function renderResults(data) {
        lastFilters = data.filters || {};
        lastSummary = data.summary || '';
        summaryEl.textContent = data.summary || '';
        const countTpl = I18N.candidateCount;
        metaEl.textContent = countTpl.replace(':count', String(data.candidate_count || 0));
        relaxedEl.classList.toggle('hidden', !data.relaxed_filters);
        if (data.browse_url) {
            browseLink.href = data.browse_url;
        }
        const recs = data.recommendations || [];
        if (!recs.length) {
            cardsEl.innerHTML = `<p class="text-muted-foreground">${escapeHtml(I18N.empty)}</p>`;
        } else {
            cardsEl.innerHTML = recs.map(renderCard).join('');
        }
        resultsEl.classList.remove('hidden');
        refineEl.classList.remove('hidden');
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
            const json = await res.json().catch(() => ({}));
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

    // Prefill from ?q=
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    if (q) {
        input.value = q;
    }
})();
</script>
@endpush
