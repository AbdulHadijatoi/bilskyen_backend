<script>
window.BilskyenCompare = window.BilskyenCompare || (function () {
    const KEY = 'bilskyen.compare';
    const MAX = 3;
    const I18N = {
        add: @json(__('messages.pages.vehicles.compare_add')),
        remove: @json(__('messages.pages.vehicles.compare_remove')),
        limit: @json(__('messages.pages.vehicles.compare_limit')),
        needTwo: @json(__('messages.pages.vehicles.compare_need_two')),
        pageUrl: @json(route('vehicles.compare')),
    };

    function readIds() {
        try {
            const raw = JSON.parse(localStorage.getItem(KEY) || '[]');
            if (!Array.isArray(raw)) {
                return [];
            }
            return raw.map(Number).filter((n) => n > 0).slice(0, MAX);
        } catch (e) {
            return [];
        }
    }

    function writeIds(ids) {
        try {
            localStorage.setItem(KEY, JSON.stringify(ids.slice(0, MAX)));
        } catch (e) {}
    }

    function has(id) {
        return readIds().includes(Number(id));
    }

    function toggle(id) {
        const n = Number(id);
        if (!n) {
            return readIds();
        }
        let ids = readIds();
        if (ids.includes(n)) {
            ids = ids.filter((x) => x !== n);
        } else if (ids.length >= MAX) {
            window.showSnackbar?.(I18N.limit, 'error');
            return ids;
        } else {
            ids = [...ids, n];
        }
        writeIds(ids);
        sync();
        return ids;
    }

    function clear() {
        writeIds([]);
        sync();
    }

    function compareUrl(ids) {
        const list = (ids || readIds()).slice(0, MAX);
        if (!list.length) {
            return I18N.pageUrl;
        }
        return I18N.pageUrl + '?ids=' + list.join(',');
    }

    function syncButtons() {
        const ids = readIds();
        document.querySelectorAll('[data-compare-toggle]').forEach((btn) => {
            const id = Number(btn.getAttribute('data-vehicle-id'));
            const on = ids.includes(id);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.classList.toggle('is-active', on);
            const label = on ? I18N.remove : I18N.add;
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        });
    }

    function syncTray() {
        const tray = document.querySelector('[data-compare-tray]');
        if (!tray) {
            return;
        }
        const ids = readIds();
        const countEl = tray.querySelector('[data-compare-count]');
        const openEl = tray.querySelector('[data-compare-open]');
        if (countEl) {
            countEl.textContent = ids.length ? `(${ids.length}/${MAX})` : '';
        }
        if (openEl) {
            openEl.setAttribute('href', compareUrl(ids));
        }
        tray.hidden = ids.length === 0;
    }

    function sync() {
        syncButtons();
        syncTray();
    }

    function bind() {
        document.addEventListener('click', (event) => {
            const toggleBtn = event.target.closest('[data-compare-toggle]');
            if (toggleBtn) {
                event.preventDefault();
                event.stopPropagation();
                toggle(toggleBtn.getAttribute('data-vehicle-id'));
                return;
            }
            const clearBtn = event.target.closest('[data-compare-clear]');
            if (clearBtn) {
                event.preventDefault();
                clear();
            }
        });
        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }

    return { KEY, MAX, readIds, writeIds, has, toggle, clear, compareUrl, sync };
})();
</script>
