@php
    if (! ($languageSwitcherEnabled ?? false)) {
        return;
    }

    $currentLocale = app()->getLocale();
    $variant = $variant ?? 'light';
    $buttonClass = $variant === 'dark'
        ? 'inline-flex h-8 items-center gap-1.5 rounded-md border border-primary-foreground/25 bg-primary-foreground/10 px-2 text-xs font-medium text-primary-foreground shadow-sm hover:bg-primary-foreground/20 transition-colors'
        : 'inline-flex h-8 items-center gap-1.5 rounded-md border border-border bg-background px-2 text-xs font-medium text-foreground shadow-sm hover:bg-muted transition-colors';
@endphp
<details class="relative language-switcher" data-language-switcher>
    <summary
        class="{{ $buttonClass }} list-none cursor-pointer [&::-webkit-details-marker]:hidden"
        aria-label="{{ __('messages.common.language_menu') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m5 8 6 6"></path>
            <path d="m4 14 6-6 2-3"></path>
            <path d="M2 5h12"></path>
            <path d="M7 2h1"></path>
            <path d="m22 22-5-10-5 10"></path>
            <path d="M14 18h6"></path>
        </svg>
        <span class="sr-only">{{ __('messages.navigation.language') }}:</span>
        <span aria-hidden="true">{{ strtoupper($currentLocale) }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6"></path>
        </svg>
    </summary>
    <div
        class="absolute right-0 mt-2 w-40 rounded-[var(--radius)] border border-border bg-popover p-1 text-popover-foreground shadow-md z-[100]"
        role="menu"
        aria-label="{{ __('messages.navigation.language') }}"
    >
        <a
            href="{{ route('locale.switch', ['locale' => 'da']) }}"
            class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground {{ $currentLocale === 'da' ? 'bg-accent/50 font-medium' : '' }}"
            role="menuitem"
        >
            {{ __('messages.common.danish') }}
        </a>
        <a
            href="{{ route('locale.switch', ['locale' => 'en']) }}"
            class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground {{ $currentLocale === 'en' ? 'bg-accent/50 font-medium' : '' }}"
            role="menuitem"
        >
            {{ __('messages.common.english') }}
        </a>
    </div>
</details>
<style>
    .language-switcher > summary:focus {
        outline: none;
    }
    .language-switcher[open] > summary {
        background-color: var(--muted, rgba(0, 0, 0, 0.05));
    }
</style>
