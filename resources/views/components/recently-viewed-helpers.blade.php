<script>
window.BilskyenRecentlyViewed = window.BilskyenRecentlyViewed || (function () {
    const KEY = 'bilskyen.recentlyViewed';
    const MAX = 8;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function readIds() {
        try {
            const raw = JSON.parse(localStorage.getItem(KEY) || '[]');
            if (!Array.isArray(raw)) {
                return [];
            }
            return raw.map(Number).filter((n) => n > 0);
        } catch (e) {
            return [];
        }
    }

    function remember(id) {
        const n = Number(id);
        if (!n) {
            return;
        }
        const next = [n, ...readIds().filter((x) => x !== n)].slice(0, MAX);
        try {
            localStorage.setItem(KEY, JSON.stringify(next));
        } catch (e) {}
    }

    function defaultCard(vehicle) {
        const slug = vehicle.slug || vehicle.id;
        const title = vehicle.title || '';
        const img = vehicle.thumbnail_url || '/placeholder-vehicle.jpg';
        const price = vehicle.price != null
            ? new Intl.NumberFormat('da-DK').format(vehicle.price) + ' kr'
            : '';
        return `<div class="vehicle-item site-card flex flex-col overflow-hidden p-0 h-full w-full min-w-0">
            <a href="/biler/${encodeURIComponent(slug)}" class="flex flex-1 flex-col min-w-0">
                <div class="relative aspect-[2/1.5] overflow-hidden bg-muted">
                    <img src="${escapeHtml(img)}" alt="${escapeHtml(title)}" class="absolute inset-0 block h-full w-full object-cover">
                </div>
                <div class="space-y-1 px-3 py-3">
                    <h3 class="vehicle-listing-title">${escapeHtml(title)}</h3>
                    <p class="vehicle-listing-price">${escapeHtml(price)}</p>
                </div>
            </a>
        </div>`;
    }

    async function hydrate(options = {}) {
        const section = document.querySelector('[data-recently-viewed]');
        const grid = document.querySelector('[data-recently-viewed-grid]');
        if (!section || !grid) {
            return;
        }
        const excludeId = Number(options.excludeId || section.getAttribute('data-exclude-id') || 0) || null;
        let ids = readIds();
        if (excludeId) {
            ids = ids.filter((id) => id !== excludeId);
        }
        if (!ids.length) {
            section.hidden = !grid.children.length;
            return;
        }
        const params = new URLSearchParams();
        params.set('ids', ids.join(','));
        if (excludeId) {
            params.set('exclude', String(excludeId));
        }
        try {
            const response = await fetch('/api/v1/vehicles/recently-viewed?' + params.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const json = await response.json();
            const docs = json?.data?.docs || [];
            if (!docs.length) {
                section.hidden = !grid.children.length;
                return;
            }
            const hasCustomRenderer = typeof options.renderItem === 'function';
            const render = hasCustomRenderer ? options.renderItem : defaultCard;
            // Keep Blade listing cards on PDP; only fill an empty grid (guests) or
            // replace when the listing page supplies a matching renderer.
            if (hasCustomRenderer || !grid.children.length) {
                grid.innerHTML = docs.map(render).join('');
            }
            section.hidden = false;
        } catch (e) {
            section.hidden = !grid.children.length;
        }
    }

    return { remember, readIds, hydrate };
})();
</script>
