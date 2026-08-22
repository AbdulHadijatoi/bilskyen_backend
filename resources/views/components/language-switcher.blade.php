@php
    if (! ($languageSwitcherEnabled ?? false)) {
        return;
    }

    $currentLocale = app()->getLocale();
    $localeLabels = ['en' => 'EN', 'da' => 'DA'];
    $currentLocaleLabel = $localeLabels[$currentLocale] ?? 'DA';
    $variant = $variant ?? 'light';
    $buttonClass = $variant === 'dark'
        ? 'inline-flex h-9 items-center gap-1.5 rounded-md border border-primary-foreground/25 bg-primary-foreground/10 px-2 font-[inherit] text-xs font-medium text-primary-foreground shadow-sm hover:bg-primary-foreground/20 transition-colors'
        : 'inline-flex h-9 items-center gap-1.5 rounded-md border border-border bg-background px-2 font-[inherit] text-xs font-medium text-foreground shadow-sm hover:bg-muted transition-colors';
@endphp
<details class="relative language-switcher {{ $variant === 'dark' ? 'language-switcher--dark' : 'language-switcher--light' }}" data-language-switcher>
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
        <span aria-hidden="true">{{ $currentLocaleLabel }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6"></path>
        </svg>
    </summary>
    <div
        class="absolute right-0 z-[200] mt-2 w-40 rounded-[var(--radius)] border border-slate-200 bg-white p-1 text-slate-900 shadow-lg language-switcher__menu"
        role="menu"
        aria-label="{{ __('messages.navigation.language') }}"
    >
        <a
            href="{{ route('locale.switch', ['locale' => 'da']) }}"
            class="language-switcher__item flex w-full items-center rounded-sm px-2 py-1.5 text-sm text-slate-900 transition-colors hover:bg-slate-100 {{ $currentLocale === 'da' ? 'bg-slate-100 font-medium' : '' }}"
            role="menuitem"
        >
            {{ __('messages.common.danish') }}
        </a>
        <a
            href="{{ route('locale.switch', ['locale' => 'en']) }}"
            class="language-switcher__item flex w-full items-center rounded-sm px-2 py-1.5 text-sm text-slate-900 transition-colors hover:bg-slate-100 {{ $currentLocale === 'en' ? 'bg-slate-100 font-medium' : '' }}"
            role="menuitem"
        >
            {{ __('messages.common.english') }}
        </a>
    </div>
</details>
<style>
    .language-switcher {
        font-family: inherit;
    }
    .language-switcher > summary:focus {
        outline: none;
    }
    /* Open state uses a light muted background — keep text dark for contrast */
    .language-switcher[open] > summary {
        background-color: var(--muted, #f1f5f9);
        color: #0f172a;
    }
    .language-switcher--dark[open] > summary {
        background-color: rgba(255, 255, 255, 0.92);
        color: #0f172a;
        border-color: rgba(15, 23, 42, 0.15);
    }
    .language-switcher__menu {
        background-color: #fff;
        color: #0f172a;
    }
    .language-switcher__item,
    .language-switcher__item:visited {
        color: #0f172a;
    }
    .language-switcher__item:hover,
    .language-switcher__item:focus {
        background-color: #f1f5f9;
        color: #0f172a;
    }
</style>
