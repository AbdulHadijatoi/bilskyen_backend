{{-- Shared AI search helpers: parse → filters URL, suggest autocomplete, example chips --}}
@php
    $honeypotField = config('security.honeypot.field', 'website');
    $aiSearchLocale = app()->getLocale();
    $aiSearchExamples = app(\App\Services\VehicleSearchSynonymService::class)->exampleQueries($aiSearchLocale);
@endphp
<script>
window.BilskyenAiSearch = (function () {
    const LOCALE = @json($aiSearchLocale);
    const PARSE_URL = @json(url('/api/v1/ai/search-parse'));
    const SUGGEST_URL = @json(url('/api/v1/search/suggest'));
    const SAVE_URL = @json(url('/saved-searches'));
    const HONEYPOT = @json($honeypotField);
    const EXAMPLES = @json($aiSearchExamples);
    const I18N = {
        understood: @json(__('messages.pages.home.ai_understood')),
        parsing: @json(__('messages.pages.home.ai_parsing')),
        brand: @json(__('messages.forms.brand')),
        model: @json(__('messages.forms.model')),
        examplesLabel: @json(__('messages.pages.home.ai_examples_label')),
        saveSearch: @json(__('messages.pages.vehicles.save_search')),
        saveSearchOk: @json(__('messages.pages.vehicles.save_search_ok')),
        saveSearchFail: @json(__('messages.pages.vehicles.save_search_fail')),
        loginToSave: @json(__('messages.pages.vehicles.login_to_save_search')),
        suggestBrands: @json(__('messages.pages.home.ai_suggest_brands')),
        suggestModels: @json(__('messages.pages.home.ai_suggest_models')),
        suggestQueries: @json(__('messages.pages.home.ai_suggest_queries')),
    };
    const PARSE_TIMEOUT_MS = 2800;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function getCookie(name) {
        const value = '; ' + document.cookie;
        const parts = value.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    function isLoggedIn() {
        return getCookie('bilskyen_auth') !== null;
    }

    async function botFields() {
        if (typeof window.bilskyenBotFields === 'function') {
            return window.bilskyenBotFields();
        }
        const fields = {};
        fields[HONEYPOT] = '';
        return fields;
    }

    /**
     * Build /vehicles query string from parse API filters + optional extras.
     */
    function filtersToParams(filters, extras) {
        const params = new URLSearchParams();
        const source = Object.assign({}, filters || {}, extras || {});
        const multi = ['brand_id', 'model_id', 'fuel_type_id', 'body_type_id', 'gear_type_id', 'listing_type_id'];
        Object.keys(source).forEach(function (key) {
            const value = source[key];
            if (value === null || value === undefined || value === '') return;
            if (Array.isArray(value)) {
                value.forEach(function (v) {
                    if (v !== null && v !== undefined && v !== '') {
                        params.append(multi.includes(key) ? key + '[]' : key, v);
                    }
                });
            } else if (multi.includes(key)) {
                params.append(key + '[]', value);
            } else {
                params.set(key, value);
            }
        });
        return params;
    }

    function buildVehiclesUrl(filters, extras) {
        const params = filtersToParams(filters, extras);
        const qs = params.toString();
        return '/vehicles' + (qs ? '?' + qs : '');
    }

    /**
     * Call AI search-parse with timeout; on failure return keyword fallback.
     */
    async function parseQuery(query) {
        const q = (query || '').trim();
        if (!q) {
            return { filters: {}, labels: [], fallback: true, query: '' };
        }

        const controller = new AbortController();
        const timer = setTimeout(function () { controller.abort(); }, PARSE_TIMEOUT_MS);

        try {
            const bot = await botFields();
            const body = Object.assign({ query: q, locale: LOCALE }, bot);
            const response = await fetch(PARSE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(body),
                signal: controller.signal,
            });
            clearTimeout(timer);
            if (!response.ok) {
                return { filters: { search: q }, labels: [{ key: 'search', label: q }], fallback: true, query: q };
            }
            const json = await response.json();
            const data = json.data || json;
            return {
                filters: data.filters || { search: q },
                labels: data.labels || [],
                fallback: !!data.fallback,
                query: data.query || q,
                ai_search: 1,
            };
        } catch (e) {
            clearTimeout(timer);
            return { filters: { search: q }, labels: [{ key: 'search', label: q }], fallback: true, query: q };
        }
    }

    async function suggest(term) {
        const url = new URL(SUGGEST_URL, window.location.origin);
        url.searchParams.set('q', term || '');
        url.searchParams.set('locale', LOCALE);
        url.searchParams.set('limit', '6');
        const response = await fetch(url.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return { brands: [], models: [], examples: EXAMPLES };
        const json = await response.json();
        return json.data || json;
    }

    async function navigateWithAiSearch(query, extraParams) {
        const result = await parseQuery(query);
        const extras = Object.assign({ ai_search: '1' }, extraParams || {});
        if (result.query) {
            extras.q = result.query;
        }
        // Keep original NL in search only when fallback or residual search filter
        window.location.href = buildVehiclesUrl(result.filters, extras);
    }

    function renderExampleChips(container) {
        if (!container) return;
        container.innerHTML = '';
        const label = document.createElement('span');
        label.className = 'ai-search-examples-label';
        label.textContent = I18N.examplesLabel;
        container.appendChild(label);
        EXAMPLES.forEach(function (ex) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ai-search-example-chip';
            btn.textContent = ex;
            btn.addEventListener('click', function () {
                const input = container.closest('form, section, .home-filter-card, #search-bar-container, .navbar-ai-search')
                    ?.querySelector('input[type="search"], input[name="search"], #home-search-input, #search-input, #navbar-search-input');
                if (input) {
                    input.value = ex;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.focus();
                }
                container.dispatchEvent(new CustomEvent('ai-example-selected', { detail: { query: ex }, bubbles: true }));
            });
            container.appendChild(btn);
        });
    }

    function renderAutocomplete(dropdown, data, onPick) {
        if (!dropdown) return;
        dropdown.innerHTML = '';
        dropdown.classList.remove('hidden');
        let hasAny = false;

        function addGroup(title, items, type) {
            if (!items || !items.length) return;
            hasAny = true;
            const heading = document.createElement('div');
            heading.className = 'ai-suggest-heading';
            heading.textContent = title;
            dropdown.appendChild(heading);
            items.forEach(function (item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ai-suggest-item';
                const name = typeof item === 'string' ? item : item.name;
                btn.textContent = name;
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    onPick({ type: type, item: item, label: name });
                    dropdown.classList.add('hidden');
                });
                dropdown.appendChild(btn);
            });
        }

        addGroup(I18N.suggestQueries, data.examples || [], 'example');
        addGroup(I18N.suggestBrands, data.brands || [], 'brand');
        addGroup(I18N.suggestModels, data.models || [], 'model');

        if (!hasAny) {
            dropdown.classList.add('hidden');
        }
    }

    function bindAutocomplete(input, dropdown, options) {
        if (!input || !dropdown) return;
        let timer = null;
        const opts = options || {};

        input.addEventListener('input', function () {
            clearTimeout(timer);
            const term = input.value.trim();
            if (term.length < 1) {
                dropdown.classList.add('hidden');
                return;
            }
            timer = setTimeout(async function () {
                try {
                    const data = await suggest(term);
                    renderAutocomplete(dropdown, data, function (pick) {
                        if (pick.type === 'brand' && opts.onBrand) {
                            opts.onBrand(pick.item);
                        } else if (pick.type === 'model' && opts.onModel) {
                            opts.onModel(pick.item);
                        } else {
                            input.value = pick.label;
                            if (opts.onExample) opts.onExample(pick.label);
                        }
                    });
                } catch (e) {
                    dropdown.classList.add('hidden');
                }
            }, 180);
        });

        input.addEventListener('blur', function () {
            setTimeout(function () { dropdown.classList.add('hidden'); }, 150);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 1) {
                input.dispatchEvent(new Event('input'));
            }
        });
    }

    function renderAiBanner(container, labels, query) {
        if (!container) return;
        const chips = (labels || []).map(function (l) {
            return typeof l === 'string' ? l : (l.label || '');
        }).filter(Boolean);
        if (!chips.length && !query) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }
        container.classList.remove('hidden');
        const chipHtml = chips.map(function (c) {
            return '<span class="ai-understood-chip">' + escapeHtml(c) + '</span>';
        }).join('');
        container.innerHTML =
            '<div class="ai-understood-inner">' +
            '<span class="ai-understood-title">' + escapeHtml(I18N.understood) +
            (query ? ' “' + escapeHtml(query) + '”' : '') + '</span>' +
            '<div class="ai-understood-chips">' + chipHtml + '</div>' +
            '</div>';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    async function saveCurrentSearch(name, filters) {
        if (!isLoggedIn()) {
            return { ok: false, message: I18N.loginToSave };
        }
        try {
            const response = await fetch(SAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name: name || 'Saved search', filters: filters || {} }),
            });
            if (response.status === 401) {
                return { ok: false, message: I18N.loginToSave };
            }
            if (!response.ok) {
                return { ok: false, message: I18N.saveSearchFail };
            }
            return { ok: true, message: I18N.saveSearchOk };
        } catch (e) {
            return { ok: false, message: I18N.saveSearchFail };
        }
    }

    return {
        LOCALE,
        EXAMPLES,
        I18N,
        parseQuery,
        suggest,
        navigateWithAiSearch,
        buildVehiclesUrl,
        filtersToParams,
        renderExampleChips,
        bindAutocomplete,
        renderAiBanner,
        saveCurrentSearch,
    };
})();
</script>
<style>
.ai-search-examples {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.65rem;
}
.ai-search-examples-label {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-right: 0.15rem;
}
.ai-search-example-chip {
    display: inline-flex;
    align-items: center;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    border-radius: 9999px;
    padding: 0.25rem 0.7rem;
    font-size: 0.75rem;
    line-height: 1.2;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}
.ai-search-example-chip:hover {
    background: hsl(var(--muted));
    border-color: hsl(var(--primary) / 0.35);
}
.ai-suggest-dropdown {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    z-index: 40;
    max-height: 16rem;
    overflow-y: auto;
    border-radius: 0.75rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--card));
    box-shadow: 0 10px 30px rgba(0,0,0,.12);
    padding: 0.35rem 0;
}
.ai-suggest-dropdown.hidden { display: none; }
.ai-suggest-heading {
    padding: 0.35rem 0.85rem 0.15rem;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: hsl(var(--muted-foreground));
}
.ai-suggest-item {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: transparent;
    padding: 0.45rem 0.85rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    cursor: pointer;
}
.ai-suggest-item:hover { background: hsl(var(--muted)); }
.ai-understood-banner {
    margin-bottom: 1rem;
    border-radius: 0.75rem;
    border: 1px solid hsl(var(--primary) / 0.25);
    background: hsl(var(--primary) / 0.06);
    padding: 0.85rem 1rem;
}
.ai-understood-banner.hidden { display: none; }
.ai-understood-title {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.45rem;
}
.ai-understood-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.ai-understood-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    padding: 0.2rem 0.65rem;
    font-size: 0.75rem;
}
.home-filter-cta.is-loading,
.navbar-ai-search-btn.is-loading {
    opacity: 0.75;
    pointer-events: none;
}
</style>
