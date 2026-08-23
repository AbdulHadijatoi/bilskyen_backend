@props([])
<section
    id="listing-compare-tray"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card/95 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur-sm"
    data-compare-tray
    hidden
    aria-labelledby="listing-compare-tray-title"
>
    <div class="container mx-auto flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 id="listing-compare-tray-title" class="text-sm font-semibold text-foreground">
                {{ __('messages.pages.vehicles.compare_tray_title') }}
                <span data-compare-count class="text-muted-foreground font-normal">0</span>
            </h2>
            <div class="mt-1 flex flex-wrap gap-2" data-compare-previews></div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button
                type="button"
                class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent"
                data-compare-clear
            >
                {{ __('messages.pages.vehicles.compare_clear') }}
            </button>
            <a
                href="{{ route('vehicles.compare') }}"
                class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                data-compare-open
            >
                {{ __('messages.pages.vehicles.compare_open') }}
            </a>
        </div>
    </div>
</section>
