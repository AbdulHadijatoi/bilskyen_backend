@php
    $showNotifications = $showNotifications ?? false;
@endphp
<div
    id="marketplace-notifications"
    class="relative {{ $showNotifications ? '' : 'hidden' }}"
    data-authenticated="{{ $showNotifications ? '1' : '0' }}"
>
    <button type="button" id="notification-bell" class="relative inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary-foreground/10 text-primary-foreground hover:bg-primary-foreground/20" aria-label="{{ __('messages.notifications.title') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
        </svg>
        <span id="notification-count" class="absolute -right-1 -top-1 hidden min-w-[18px] rounded-full bg-rose-500 px-1 text-center text-[10px] font-bold text-white">0</span>
    </button>
    <div id="notification-panel" class="hidden absolute right-0 z-50 mt-2 w-80 max-h-96 overflow-y-auto rounded-lg border border-border bg-popover p-2 shadow-lg text-popover-foreground">
        <div class="mb-2 flex items-center justify-between px-2">
            <span class="text-sm font-semibold text-foreground">{{ __('messages.notifications.title') }}</span>
            <button type="button" id="notification-mark-read" class="text-xs text-primary hover:underline">{{ __('messages.notifications.mark_all_read') }}</button>
        </div>
        <div id="notification-list" class="space-y-1"></div>
        <p id="notification-empty" class="hidden px-2 py-4 text-center text-sm text-muted-foreground">{{ __('messages.notifications.empty') }}</p>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('marketplace-notifications');
    if (!root) return;

    const serverAuthenticated = root.dataset.authenticated === '1';
    if (!serverAuthenticated) return;

    const bell = document.getElementById('notification-bell');
    const panel = document.getElementById('notification-panel');
    const list = document.getElementById('notification-list');
    const empty = document.getElementById('notification-empty');
    const countEl = document.getElementById('notification-count');
    const markReadBtn = document.getElementById('notification-mark-read');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    async function api(path, options = {}) {
        const res = await fetch(path, {
            ...options,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(options.headers || {}),
            },
        });

        if (!res.ok) {
            throw new Error('Notification request failed: ' + res.status);
        }

        return res.json();
    }

    async function loadCount() {
        try {
            const data = await api('/marketplace-notifications/count');
            const count = data?.data?.count ?? 0;
            if (count > 0) {
                countEl.textContent = count > 9 ? '9+' : String(count);
                countEl.classList.remove('hidden');
            } else {
                countEl.classList.add('hidden');
            }
        } catch (e) {
            console.warn('Failed to load notification count', e);
        }
    }

    async function loadList() {
        try {
            const data = await api('/marketplace-notifications');
            const items = data?.data?.notifications ?? [];
            list.innerHTML = '';
            if (!items.length) {
                empty.classList.remove('hidden');
                return;
            }
            empty.classList.add('hidden');
            items.forEach((item) => {
                const el = document.createElement('a');
                el.href = item.action_url || '#';
                el.className = 'block rounded-md px-2 py-2 text-sm hover:bg-accent' + (item.read_at ? '' : ' bg-primary/5');
                el.innerHTML = `<div class="font-medium text-foreground">${item.title || ''}</div><div class="text-xs text-muted-foreground">${item.message || ''}</div>`;
                list.appendChild(el);
            });
        } catch (e) {
            console.warn('Failed to load notifications', e);
            empty.classList.remove('hidden');
        }
    }

    bell?.addEventListener('click', async (e) => {
        e.stopPropagation();
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            await loadList();
        }
    });

    markReadBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        await api('/marketplace-notifications/mark-read', { method: 'POST', body: '{}' });
        await loadCount();
        await loadList();
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) panel.classList.add('hidden');
    });

    window.refreshMarketplaceNotifications = async function () {
        root.classList.remove('hidden');
        await loadCount();
    };

    loadCount();
})();
</script>
