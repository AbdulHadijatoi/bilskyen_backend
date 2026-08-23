@props([
    'url',
    'title' => '',
])

@php
    $shareUrl = (string) $url;
    $shareTitle = trim((string) $title);
    $encodedUrl = rawurlencode($shareUrl);
    $encodedTitle = rawurlencode($shareTitle);
@endphp

<div class="relative inline-flex shrink-0">
    <button
        type="button"
        id="vehicle-share-open"
        class="vehicle-share-trigger inline-flex items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        popovertarget="vehicle-share-dialog"
        aria-label="{{ __('messages.pages.vehicles.detail.share') }}"
        aria-controls="vehicle-share-dialog"
        aria-expanded="false"
        aria-haspopup="dialog"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
            <circle cx="18" cy="5" r="3"></circle>
            <circle cx="6" cy="12" r="3"></circle>
            <circle cx="18" cy="19" r="3"></circle>
            <line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line>
            <line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line>
        </svg>
    </button>

    <div
        id="vehicle-share-dialog"
        popover="auto"
        role="dialog"
        class="vehicle-share-popover w-[min(20rem,calc(100vw-1rem))] max-h-[min(28rem,calc(100dvh-1rem))] overflow-auto rounded-xl border border-border bg-card p-0 text-foreground shadow-lg"
        aria-labelledby="vehicle-share-title"
    >
        <div class="flex items-start justify-between gap-3 border-b border-border px-4 py-3">
            <h2 id="vehicle-share-title" class="text-base font-semibold">{{ __('messages.pages.vehicles.detail.share_title') }}</h2>
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                popovertarget="vehicle-share-dialog"
                popovertargetaction="hide"
                aria-label="{{ __('messages.common.close') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-3 p-4">
            <p class="break-all rounded-md bg-muted/60 px-3 py-2 text-xs text-muted-foreground" data-share-url>{{ $shareUrl }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <button
                    type="button"
                    data-share-native
                    class="hidden col-span-full inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >
                    {{ __('messages.pages.vehicles.detail.share_native') }}
                </button>
                <button
                    type="button"
                    data-share-copy
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >
                    {{ __('messages.pages.vehicles.detail.share_copy') }}
                </button>
                <a
                    href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >Facebook</a>
                <a
                    href="https://wa.me/?text={{ $encodedTitle }}%20{{ $encodedUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >WhatsApp</a>
                <a
                    href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}&text={{ $encodedTitle }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >X</a>
                <a
                    href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encodedUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >LinkedIn</a>
                <a
                    href="mailto:?subject={{ $encodedTitle }}&body={{ $encodedUrl }}"
                    class="inline-flex items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >{{ __('messages.pages.vehicles.detail.send_email') }}</a>
            </div>
            <p class="hidden text-xs font-medium text-green-700 dark:text-green-400" data-share-copied>{{ __('messages.pages.vehicles.detail.share_copied') }}</p>
        </div>
    </div>
</div>
<style>
    .vehicle-share-trigger {
        width: 2.75rem;
        height: 2.75rem;
        flex-shrink: 0;
    }
    @media (min-width: 640px) {
        .vehicle-share-trigger {
            width: 2.5rem;
            height: 2.5rem;
        }
    }
    .vehicle-share-popover {
        position: fixed;
        inset: unset;
        margin: 0;
    }
    .vehicle-share-popover:is(:popover-open, .\:popover-open) {
        display: block;
    }
</style>
<script>
(function () {
    const panel = document.getElementById('vehicle-share-dialog');
    const openBtn = document.getElementById('vehicle-share-open');
    if (!panel || !openBtn) {
        return;
    }
    const url = @json($shareUrl);
    const title = @json($shareTitle);
    const supportsPopover = 'popover' in HTMLElement.prototype;
    const PAD = 8;
    const GAP = 8;

    function isOpen() {
        if (supportsPopover) {
            return panel.matches(':popover-open') || panel.classList.contains(':popover-open');
        }
        return !panel.hasAttribute('hidden');
    }

    function positionPanel() {
        if (!isOpen()) {
            return;
        }
        const btn = openBtn.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const maxWidth = Math.min(320, vw - PAD * 2);
        panel.style.width = maxWidth + 'px';
        const height = panel.offsetHeight;
        let top = btn.bottom + GAP;
        if (top + height > vh - PAD) {
            const above = btn.top - GAP - height;
            top = above >= PAD ? above : Math.max(PAD, vh - PAD - height);
        }
        let left = btn.right - maxWidth;
        left = Math.min(Math.max(PAD, left), vw - PAD - maxWidth);
        panel.style.top = Math.round(top) + 'px';
        panel.style.left = Math.round(left) + 'px';
    }

    function setExpanded(open) {
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            requestAnimationFrame(positionPanel);
            window.addEventListener('resize', positionPanel);
            window.addEventListener('scroll', positionPanel, true);
        } else {
            window.removeEventListener('resize', positionPanel);
            window.removeEventListener('scroll', positionPanel, true);
        }
    }

    if (supportsPopover) {
        panel.addEventListener('toggle', function (event) {
            setExpanded(event.newState === 'open');
        });
    } else {
        panel.removeAttribute('popover');
        panel.setAttribute('hidden', '');
        openBtn.addEventListener('click', function () {
            const willOpen = panel.hasAttribute('hidden');
            if (willOpen) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', '');
            }
            setExpanded(willOpen);
        });
        panel.querySelector('[popovertargetaction="hide"]')?.addEventListener('click', function () {
            panel.setAttribute('hidden', '');
            setExpanded(false);
        });
        document.addEventListener('click', function (event) {
            if (!isOpen()) {
                return;
            }
            if (openBtn.contains(event.target) || panel.contains(event.target)) {
                return;
            }
            panel.setAttribute('hidden', '');
            setExpanded(false);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) {
                panel.setAttribute('hidden', '');
                setExpanded(false);
            }
        });
    }

    const nativeBtn = panel.querySelector('[data-share-native]');
    if (nativeBtn && navigator.share) {
        nativeBtn.classList.remove('hidden');
        nativeBtn.addEventListener('click', async function () {
            try {
                await navigator.share({ title: title, url: url, text: title });
                if (supportsPopover && typeof panel.hidePopover === 'function') {
                    panel.hidePopover();
                } else {
                    panel.setAttribute('hidden', '');
                    setExpanded(false);
                }
            } catch (err) {
                if (err && err.name !== 'AbortError') {
                    /* keep the panel open so the user can copy the link instead */
                }
            }
        });
    }
    const copyBtn = panel.querySelector('[data-share-copy]');
    const copied = panel.querySelector('[data-share-copied]');
    copyBtn?.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(url);
        } catch (e) {
            const input = document.createElement('textarea');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }
        if (copied) {
            copied.classList.remove('hidden');
        }
    });
})();
</script>
