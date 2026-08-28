@extends('layouts.app')

@section('title', __('messages.navigation.home') . ' | Bilskyen')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
<style>
    .featured-vehicles-scroll-container {
        scrollbar-width: thin;
        scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
        scroll-behavior: smooth;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        min-width: 0;
        max-width: 100%;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        container-type: inline-size;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar {
        height: 8px;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar-thumb {
        background-color: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar-thumb:hover {
        background-color: hsl(var(--muted-foreground) / 0.5);
    }
    
    /* Hide scrollbar on large screens */
    @media (min-width: 768px) {
        .featured-vehicles-scroll-container {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .featured-vehicles-scroll-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
    }
    
    /* Multi-select dropdown: show checkmark when option is selected */
    .dropdown-option.selected .dropdown-option-check {
        opacity: 1;
    }
    
    /* Navigation buttons */
    .featured-vehicles-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: hsl(var(--background));
        border: 1px solid hsl(var(--border));
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: hsl(var(--foreground));
    }
    
    .featured-vehicles-nav-btn:hover {
        background-color: hsl(var(--accent));
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .featured-vehicles-nav-btn:active {
        transform: translateY(-50%) scale(0.95);
    }
    
    .featured-vehicles-nav-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .featured-vehicles-nav-btn-left {
        left: -64px;
    }
    
    .featured-vehicles-nav-btn-right {
        right: -64px;
    }
    
    @media (min-width: 1024px) {
        .featured-vehicles-nav-btn-left {
            left: -80px;
        }
        
        .featured-vehicles-nav-btn-right {
            right: -80px;
        }
    }
    
    @media (min-width: 1280px) {
        .featured-vehicles-nav-btn-left {
            left: -96px;
        }
        
        .featured-vehicles-nav-btn-right {
            right: -96px;
        }
    }
    
    .featured-vehicles-carousel {
        min-width: 0;
        max-width: 100%;
    }

    .featured-vehicles-scroll-container > div {
        display: flex;
        align-items: stretch;
        width: max-content;
        max-width: none;
        box-sizing: border-box;
    }

    .featured-vehicles-scroll-container .featured-vehicle-card {
        display: flex;
        flex-direction: column;
        align-self: stretch;
        height: auto;
        scroll-snap-align: start;
        scroll-snap-stop: always;
        box-sizing: border-box;
        /* Mobile: one primary card with the next card peeking */
        flex: 0 0 85cqi;
        width: 85cqi;
        min-width: 85cqi;
        max-width: 85cqi;
    }

    @media (min-width: 640px) {
        .featured-vehicles-scroll-container .featured-vehicle-card {
            /* Tablet: two full cards (one gap of 0.75rem) */
            flex-basis: calc((100cqi - 0.75rem) / 2);
            width: calc((100cqi - 0.75rem) / 2);
            min-width: calc((100cqi - 0.75rem) / 2);
            max-width: calc((100cqi - 0.75rem) / 2);
        }
    }

    @media (min-width: 768px) {
        .featured-vehicles-scroll-container .featured-vehicle-card {
            /* Desktop: three full cards (two gaps of 0.75rem) */
            flex-basis: calc((100cqi - 1.5rem) / 3);
            width: calc((100cqi - 1.5rem) / 3);
            min-width: calc((100cqi - 1.5rem) / 3);
            max-width: calc((100cqi - 1.5rem) / 3);
        }
    }

    @media (max-width: 767px) {
        .featured-vehicles-section {
            overflow-x: clip;
        }
    }

    /* Home filter form — redesign (compact, site fonts) */
    .home-search-section {
        position: relative;
        isolation: isolate;
        background-color: var(--background, #f8fafc);
        background-image:
            radial-gradient(ellipse at 12% 0%, color-mix(in srgb, var(--primary, #03418b) 18%, transparent) 0%, transparent 52%),
            linear-gradient(color-mix(in srgb, var(--border, #e2e8f0) 55%, transparent) 1px, transparent 1px),
            linear-gradient(90deg, color-mix(in srgb, var(--border, #e2e8f0) 55%, transparent) 1px, transparent 1px);
        background-size: auto, 28px 28px, 28px 28px;
    }

    .home-search-section--has-image {
        background-image:
            radial-gradient(ellipse at 12% 0%, color-mix(in srgb, var(--primary, #03418b) 22%, transparent) 0%, transparent 55%),
            linear-gradient(
                color-mix(in srgb, var(--background, #f8fafc) 78%, transparent),
                color-mix(in srgb, var(--background, #f8fafc) 86%, transparent)
            ),
            var(--home-hero-image);
        background-size: auto, cover, cover;
        background-position: 0 0, center, center;
        background-repeat: no-repeat;
    }

    .home-filter-card {
        --hf-paper: #f0f1f3;
        --hf-panel: #ffffff;
        --hf-ink: var(--foreground, #0f172a);
        --hf-ink-soft: var(--muted-foreground, #64748b);
        --hf-slate: var(--muted-foreground, #64748b);
        --hf-line: var(--border, #e2e8f0);
        --hf-route: var(--primary, #03418b);
        --hf-route-soft: var(--primary-light, #e8f0fa);
        --hf-accent: var(--primary, #03418b);
        --hf-accent-soft: var(--primary-light, #e8f0fa);
        --hf-success: var(--success, #059669);

        background: var(--hf-panel);
        border: 1px solid var(--hf-line);
        border-radius: 1rem;
        padding: 1.25rem 1.125rem 1rem;
        box-shadow: var(--shadow-card, 0 1px 3px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04));
        color: var(--hf-ink);
    }

    @media (min-width: 768px) {
        .home-filter-card {
            padding: 1.5rem 1.75rem 1.25rem;
        }
    }

    .home-filter-intro {
        margin-bottom: 1rem;
    }

    @media (min-width: 768px) {
        .home-filter-intro {
            margin-bottom: 1.125rem;
        }
    }

    .home-filter-intro h1 {
        font-weight: 700;
        font-size: 1.5rem;
        line-height: 1.2;
        letter-spacing: -0.02em;
        color: var(--hf-ink);
        margin: 0;
    }

    @media (min-width: 768px) {
        .home-filter-intro h1 {
            font-size: 1.75rem;
        }
    }

    .home-filter-intro-sub {
        margin: 0.375rem 0 0;
        max-width: 36rem;
        font-size: 0.875rem;
        line-height: 1.4;
        color: var(--hf-ink-soft);
    }

    .home-filter-field {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .home-filter-search-row {
        display: none;
    }

    @media (min-width: 768px) {
        .home-filter-search-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
        }
    }

    .home-filter-search-wrap {
        position: relative;
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 0.625rem;
        background: var(--hf-paper);
        border: 1px solid var(--hf-line);
        border-radius: 0.625rem;
        padding: 0 1rem;
        height: 3.25rem;
        min-height: 3.25rem;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .home-filter-search-wrap:focus-within {
        border-color: var(--hf-route);
    }

    .home-filter-search-wrap.is-ai {
        border-color: color-mix(in oklch, var(--hf-route) 35%, var(--hf-line));
        background: linear-gradient(
            135deg,
            color-mix(in oklch, var(--hf-route-soft) 55%, #fff) 0%,
            var(--hf-paper) 55%
        );
    }

    .home-filter-search-wrap.is-ai:focus-within {
        border-color: var(--hf-route);
        box-shadow: 0 0 0 3px color-mix(in oklch, var(--hf-route) 16%, transparent);
    }

    .home-filter-search-wrap > svg {
        flex-shrink: 0;
        width: 18px;
        height: 18px;
        color: var(--hf-slate);
    }

    .home-filter-search-wrap.is-ai > svg {
        color: var(--hf-route);
    }

    .home-filter-ai-badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        height: 1.5rem;
        padding: 0 0.5rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--hf-route);
        background: color-mix(in oklch, var(--hf-route-soft) 80%, #fff);
        border: 1px solid color-mix(in oklch, var(--hf-route) 22%, transparent);
    }

    .home-filter-search-input {
        height: 100%;
        min-height: 0;
        width: 100%;
        border: 0;
        border-radius: 0;
        background: transparent;
        padding: 0;
        font-size: 1rem;
        line-height: 1.4;
        color: var(--hf-ink);
        outline: none;
        appearance: none;
        -webkit-appearance: none;
    }

    .home-filter-search-input::-webkit-search-decoration,
    .home-filter-search-input::-webkit-search-cancel-button,
    .home-filter-search-input::-webkit-search-results-button,
    .home-filter-search-input::-webkit-search-results-decoration {
        -webkit-appearance: none;
    }

    .home-filter-search-input::placeholder {
        color: var(--hf-slate);
    }

    .home-filter-search-input:hover,
    .home-filter-search-input:focus {
        background: transparent;
    }

    .home-filter-cta {
        display: inline-flex;
        height: 3.25rem;
        min-height: 3.25rem;
        width: 100%;
        align-items: center;
        justify-content: center;
        border-radius: 0.625rem;
        border: 0;
        background-color: var(--hf-route);
        padding: 0 1.25rem;
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        flex-shrink: 0;
        transition: background-color 0.15s ease;
        cursor: pointer;
    }

    @media (min-width: 640px) {
        .home-filter-search-wrap {
            height: 2.75rem;
            min-height: 2.75rem;
            padding: 0 0.875rem;
        }

        .home-filter-search-wrap > svg {
            width: 16px;
            height: 16px;
        }

        .home-filter-ai-badge {
            height: 1.375rem;
            padding: 0 0.45rem;
            font-size: 0.625rem;
        }

        .home-filter-search-input {
            font-size: 0.875rem;
        }

        .home-filter-cta {
            height: 2.75rem;
            min-height: 2.75rem;
            width: auto;
            font-size: 0.875rem;
        }
    }

    .home-filter-cta:hover {
        background-color: var(--primary-hover, #022a5c);
    }

    .home-filter-submit-cta {
        height: 2.5rem;
        min-height: 0;
        border-radius: 0.5rem;
        padding: 0 1.25rem;
        font-size: 0.875rem;
    }

    .home-filter-search-hint {
        display: none;
        margin: 0.5rem 0 0;
        padding-left: 0.125rem;
        font-size: 0.75rem;
        color: var(--hf-slate);
    }

    @media (min-width: 768px) {
        .home-filter-search-hint {
            display: block;
        }
    }

    .home-filter-suggestion-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.875rem;
        margin-bottom: 0;
    }

    .home-filter-card .ai-search-examples {
        display: contents;
        margin-top: 0;
        gap: 0.4rem;
    }

    .home-filter-card .ai-search-examples-label {
        display: none;
    }

    .home-filter-card .ai-search-example-chip {
        font-size: 0.75rem;
        color: var(--hf-ink-soft);
        background: var(--hf-paper);
        border: 1px solid var(--hf-line);
        border-radius: 9999px;
        padding: 0.3rem 0.75rem;
    }

    .home-filter-card .ai-search-example-chip:hover {
        border-color: var(--hf-route);
        color: var(--hf-route);
        background: var(--hf-paper);
    }

    .home-filter-card .ai-search-example-chip.is-active {
        background: var(--hf-route-soft);
        border-color: var(--hf-route);
        color: var(--hf-route);
        font-weight: 600;
    }

    .home-filter-card .ai-search-example-chip.lifestyle {
        background: var(--hf-accent-soft);
        border-color: color-mix(in oklch, var(--hf-route) 28%, #fff);
        color: var(--hf-route);
        text-decoration: none;
    }

    .home-filter-card .ai-search-example-chip.lifestyle:hover {
        border-color: var(--hf-accent);
        color: var(--hf-accent);
        background: var(--hf-accent-soft);
    }

    .home-filter-ai-understood {
        margin-top: 0.75rem;
    }

    .home-filter-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem 1rem;
        margin-top: 1.125rem;
        padding-bottom: 0;
        border-bottom: 1px solid var(--hf-line);
    }

    .home-filter-tabs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1.125rem;
    }

    .home-filter-toolbar-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        padding-bottom: 0.625rem;
    }

    .home-filter-showing {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
        color: var(--hf-ink-soft);
        white-space: nowrap;
    }

    .home-filter-showing b,
    .home-filter-showing #home-results-badge {
        color: var(--hf-ink);
        font-weight: 700;
    }

    .home-filter-showing-dot {
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 9999px;
        background: var(--hf-success);
        flex-shrink: 0;
    }

    .home-filter-hide-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border: 0;
        background: transparent;
        padding: 0;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--hf-ink-soft);
        cursor: pointer;
        transition: color 0.15s ease;
    }

    .home-filter-hide-btn:hover {
        color: var(--hf-ink);
    }

    .home-filter-tab {
        border: 0;
        background: transparent;
        padding: 0 0 0.625rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--hf-slate);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        transition: color 0.15s ease, border-color 0.15s ease;
    }

    .home-filter-tab:hover {
        color: var(--hf-ink);
    }

    .home-filter-tab.active {
        color: var(--hf-ink);
        font-weight: 600;
        border-bottom-color: var(--hf-route);
    }

    .home-filter-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
        margin-top: 0.75rem;
        min-height: 0;
    }

    .home-filter-chips:empty {
        display: none;
    }

    .home-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border: 1px solid var(--hf-line);
        border-radius: 9999px;
        background-color: var(--hf-paper);
        padding: 0.25rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--hf-ink);
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .home-filter-chip:hover {
        background-color: var(--hf-route-soft);
        border-color: var(--hf-route);
    }

    .home-filter-core-grid,
    .home-filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem 1.25rem;
        margin-top: 1.125rem;
    }

    @media (min-width: 768px) {
        .home-filter-core-grid,
        .home-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .home-filter-pill-btn {
        display: inline-flex;
        height: 2.5rem;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        border-radius: 0.5rem;
        border: 1px solid var(--hf-line);
        background-color: var(--hf-paper);
        padding: 0 0.75rem;
        font-size: 0.875rem;
        font-weight: 400;
        color: var(--hf-ink);
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .home-filter-pill-btn:hover:not(:disabled) {
        border-color: var(--hf-route);
        background-color: var(--hf-paper);
    }

    .home-filter-pill-btn:focus-visible {
        outline: none;
        border-color: var(--hf-route);
    }

    .home-filter-pill-btn.is-open {
        border-color: var(--hf-route);
        background-color: #fff;
    }

    .home-filter-pill-btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        background-color: var(--hf-paper);
        color: var(--hf-slate);
    }

    .home-filter-field-label {
        display: block;
        margin-bottom: 0.4rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: var(--letter-spacing-label, 0.04em);
        text-transform: uppercase;
        color: var(--hf-slate);
    }

    .home-filter-field[data-filter-range] .home-filter-field-label {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
        text-transform: none;
        letter-spacing: 0;
        font-size: 0.75rem;
        color: var(--hf-ink);
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .home-filter-field[data-filter-range] .home-filter-field-label-text {
        text-transform: none;
        letter-spacing: 0;
    }

    .home-filter-range-value {
        color: var(--hf-route);
        font-weight: 500;
        white-space: nowrap;
        font-size: 0.75rem;
    }

    .home-filter-range-box {
        border: 0;
        border-radius: 0;
        background-color: transparent;
        padding: 0;
    }

    .home-filter-dropdown-menu {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 0.25rem);
        z-index: 60;
        width: 100%;
        border: 1px solid var(--hf-line);
        border-radius: 0.625rem;
        background-color: #fff;
        box-shadow: 0 8px 24px rgba(19, 35, 63, 0.12);
        max-height: 300px;
        overflow: hidden;
    }

    .home-filter-dropdown-menu.is-open {
        display: block;
    }

    .home-filter-dropdown-menu .dropdown-search {
        border: 0;
        background-color: var(--hf-paper);
        border-radius: 0.5rem;
    }

    .home-filter-dropdown-menu .dropdown-search:focus {
        background-color: #fff;
        outline: none;
    }

    .home-filter-dropdown-menu .dropdown-option:hover {
        background-color: var(--hf-paper);
    }

    .home-filter-range-track-wrap {
        position: relative;
        padding: 0.875rem 0.125rem 0.375rem;
    }

    .home-filter-range-rail {
        position: relative;
        height: 3px;
        margin: 0;
        border-radius: 3px;
        background-color: var(--hf-line);
    }

    .home-filter-range-fill {
        position: absolute;
        height: 3px;
        border-radius: 3px;
        background-color: var(--hf-route);
    }

    .home-filter-range-input {
        position: absolute;
        top: 50%;
        left: 0;
        width: 100%;
        height: 1.5rem;
        margin: 0;
        transform: translateY(-50%);
        opacity: 0;
        cursor: pointer;
        z-index: 10;
    }

    .home-filter-range-input-max {
        z-index: 20;
    }

    .home-filter-range-handle {
        position: absolute;
        top: 50%;
        width: 0.875rem;
        height: 0.875rem;
        border-radius: 9999px;
        border: 2px solid var(--hf-route);
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(19, 35, 63, 0.2);
        cursor: pointer;
        z-index: 30;
        transform: translate(-50%, -50%);
    }

    .home-filter-range-tooltip {
        position: absolute;
        bottom: calc(100% + 0.375rem);
        left: 50%;
        transform: translateX(-50%);
        border-radius: 0.375rem;
        background-color: var(--hf-ink);
        padding: 0.2rem 0.4rem;
        font-size: 0.6875rem;
        font-weight: 600;
        line-height: 1.2;
        color: #fff;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.12s ease, visibility 0.12s ease;
    }

    .home-filter-range-handle.is-active .home-filter-range-tooltip {
        opacity: 1;
        visibility: visible;
    }

    .home-filter-footer {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        column-gap: 1rem;
        row-gap: 0.75rem;
        margin-top: 1.125rem;
        padding-top: 1rem;
        border-top: 1px solid var(--hf-line);
    }

    .home-filter-footer-reset {
        display: inline-flex;
        align-items: center;
        justify-self: start;
        min-height: 2.75rem;
        font-size: 0.8125rem;
        color: var(--hf-slate);
        text-decoration: underline;
        background: transparent;
        border: 0;
        padding: 0;
        cursor: pointer;
    }

    .home-filter-footer-reset:hover {
        color: var(--hf-ink);
    }

    .home-filter-advanced-link {
        display: inline-flex;
        align-items: center;
        justify-self: end;
        min-height: 2.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--hf-slate);
        text-align: end;
        text-decoration: none;
        background: transparent;
        border: 0;
        box-shadow: none;
    }

    .home-filter-advanced-link:hover {
        color: var(--hf-ink);
        text-decoration: underline;
    }

    .home-filter-footer .home-filter-submit-cta {
        grid-column: 1 / -1;
        width: 100%;
    }

    @media (min-width: 640px) {
        .home-filter-footer {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            gap: 0.75rem 1rem;
        }

        .home-filter-footer-reset {
            min-height: 0;
            margin-inline-end: auto;
        }

        .home-filter-advanced-link {
            justify-self: unset;
            min-height: 0;
        }

        .home-filter-footer .home-filter-submit-cta {
            grid-column: auto;
            width: auto;
        }
    }

    .vehicle-card-enquire-btn {
        border-color: #e2e8f0;
        color: #475569;
        box-shadow: none;
    }

    #home-filters-panel.is-collapsed {
        display: none;
    }

    [data-dropdown] {
        position: relative;
        z-index: 1;
    }

    [data-dropdown]:has(.home-filter-dropdown-menu.is-open) {
        z-index: 70;
    }

    .testimonial-quote {
        position: relative;
        padding-left: 1.25rem;
        font-weight: 400;
    }

    .testimonial-quote::before {
        content: '\201C';
        position: absolute;
        left: 0;
        top: -0.125rem;
        font-size: 1.5rem;
        line-height: 1;
        color: hsl(var(--primary));
        font-weight: 400;
    }

    .testimonial-card {
        border-bottom: 1px solid var(--border, #e2e8f0);
    }

    @media (min-width: 768px) {
        .testimonial-card {
            border-bottom: 0;
        }
    }
</style>
@endpush

@section('content')
@php
    $vehicleCountFormatted = number_format($publishedVehicleCount ?? 0, 0, ',', '.');
    $homeYearMin = 1975;
    $homeYearMax = ($currentYear ?? (int) date('Y')) + 1;
    $homeHeroTitle = \App\Support\HomeHeroCopy::title($homePageContent['search_title'] ?? null);
    $searchDescription = \App\Support\HomeHeroCopy::description($homePageContent['search_description'] ?? null);
    $filterPriceMax = $filterPriceMax ?? 1_000_000;
    $filterKmMax = $filterKmMax ?? 500_000;
    $homePageImages = $homePageImages ?? [];
    $heroBackgroundImage = $homePageImages['hero_background'][0]['image_url'] ?? null;
@endphp
<div class="flex min-h-screen flex-col pt-0">
    <!-- Search Section -->
    <section
        class="home-search-section relative py-6 md:py-8{{ $heroBackgroundImage ? ' home-search-section--has-image' : '' }}"
        @if($heroBackgroundImage) style="--home-hero-image: url('{{ $heroBackgroundImage }}')" @endif
    >
        <div class="container mx-auto px-4 md:px-6">
            <div class="home-filter-card">
                <div class="home-filter-intro">
                    <h1>
                        {{ $homeHeroTitle }}
                    </h1>
                    @if(filled($searchDescription))
                        <p class="home-filter-intro-sub">
                            {{ $searchDescription }}
                        </p>
                    @endif
                </div>

                <form method="GET" action="{{ route('vehicles') }}" id="filter-form">
                <div class="home-filter-search-row">
                    <div class="home-filter-search-wrap{{ !empty($publicAiEnabled) ? ' is-ai' : '' }}">
                        @if(!empty($publicAiEnabled))
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"></path>
                            <path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14z"></path>
                            <path d="M5 15l.6 1.6L7 17l-1.4.6L5 19l-.6-1.4L3 17l1.4-.6L5 15z"></path>
                        </svg>
                        <span class="home-filter-ai-badge">{{ __('messages.pages.home.search_ai_badge') }}</span>
                        @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                        @endif
                        <input
                            type="search"
                            id="home-search-input"
                            @if(empty($publicAiEnabled)) name="search" @endif
                            class="home-filter-search-input"
                            placeholder="{{ !empty($publicAiEnabled) ? __('messages.pages.home.search_placeholder_ai') : __('messages.pages.home.search_placeholder') }}"
                            autocomplete="off"
                            aria-label="{{ !empty($publicAiEnabled) ? __('messages.pages.home.search_aria_ai') : __('messages.pages.home.search_aria') }}"
                            @if(!empty($publicAiEnabled))
                            aria-autocomplete="list"
                            aria-controls="home-ai-suggest"
                            @endif
                        >
                        @if(!empty($publicAiEnabled))
                        <div id="home-ai-suggest" class="ai-suggest-dropdown hidden" role="listbox"></div>
                        @endif
                    </div>
                    <button type="submit" id="home-search-submit" class="home-filter-cta">
                        {{ !empty($publicAiEnabled) ? __('messages.pages.home.search_cta_ai') : __('messages.common.search') }}
                    </button>
                </div>

                @if(!empty($publicAiEnabled))
                <p class="home-filter-search-hint">{{ __('messages.pages.home.search_hint_ai') }}</p>
                @endif

                    @if(!empty($publicAiEnabled))
                    <div class="home-filter-suggestion-row">
                    <div id="home-ai-examples" class="ai-search-examples" aria-label="{{ __('messages.pages.home.ai_examples_label') }}"></div>
                    @foreach($lifestyleChips ?? [] as $chip)
                    <a href="{{ $chip['href'] }}" class="ai-search-example-chip lifestyle">
                        ✦ {{ $chip['label'] }}
                    </a>
                    @endforeach
                    </div>
                    @endif

                @if(!empty($publicAiEnabled))
                <div id="home-ai-understood" class="ai-understood-banner home-filter-ai-understood hidden" aria-live="polite"></div>
                @endif

                <div class="home-filter-toolbar">
                    <div class="home-filter-tabs">
                        <button type="button" class="home-filter-tab active" data-listing-type="">
                            {{ __('messages.pages.home.all_cars') }}
                        </button>
                        @foreach($listingTypes ?? [] as $listingType)
                            <button type="button" class="home-filter-tab" data-listing-type="{{ $listingType->id }}">
                                {{ $listingType->name }}
                            </button>
                        @endforeach
                    </div>
                    <div class="home-filter-toolbar-meta">
                        <div class="home-filter-showing" aria-live="polite">
                            <span class="home-filter-showing-dot" aria-hidden="true"></span>
                            <span>{!! __('messages.pages.home.showing_cars', ['count' => '<b id="home-results-badge">' . e($vehicleCountFormatted) . '</b>']) !!}</span>
                        </div>
                        <button
                            type="button"
                            id="toggle-filters-button"
                            class="home-filter-hide-btn"
                            data-hide-label="{{ __('messages.pages.home.hide_filters') }}"
                            data-show-label="{{ __('messages.pages.home.show_filters') }}"
                            aria-expanded="false"
                            aria-controls="home-filters-panel"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            <span class="home-filter-hide-btn-label">{{ __('messages.pages.home.show_filters') }}</span>
                        </button>
                    </div>
                </div>
                <div id="home-listing-type-inputs" data-name="listing_type_id[]"></div>

                <div id="home-filter-chips" class="home-filter-chips" aria-live="polite"></div>

                <div class="home-filter-core-grid">
                    <div class="home-filter-field">
                        <label class="home-filter-field-label" title="{{ __('messages.pages.home.filter_help_brand') }}">{{ __('messages.forms.brand') }}</label>
                        <div class="relative" data-dropdown="brand" data-multiselect="true">
                            <button type="button" class="home-filter-pill-btn" data-dropdown-trigger aria-expanded="false" aria-haspopup="listbox">
                                <span class="dropdown-selected truncate">{{ __('messages.forms.all_brands') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 flex-shrink-0 opacity-50">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="dropdown-menu home-filter-dropdown-menu" role="listbox">
                                <div class="p-2 border-b border-border">
                                    <input type="text" placeholder="{{ __('messages.forms.search_brand') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                                </div>
                                <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_brands') }}</button>
                                </div>
                            </div>
                            <div class="dropdown-values-container" data-name="brand_id[]"></div>
                        </div>
                    </div>

                    <div class="home-filter-field">
                        <label class="home-filter-field-label" title="{{ __('messages.pages.home.filter_help_model') }}">{{ __('messages.forms.model') }}</label>
                        <div class="relative" data-dropdown="model" data-multiselect="true">
                            <button type="button" id="model-dropdown-button" class="home-filter-pill-btn" data-dropdown-trigger aria-expanded="false" aria-haspopup="listbox">
                                <span class="dropdown-selected truncate">{{ __('messages.forms.all_models') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 flex-shrink-0 opacity-50">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="dropdown-menu home-filter-dropdown-menu" role="listbox">
                                <div class="p-2 border-b border-border">
                                    <input type="text" placeholder="{{ __('messages.forms.search_model') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                                </div>
                                <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_models') }}</button>
                                </div>
                            </div>
                            <div class="dropdown-values-container" data-name="model_id[]"></div>
                        </div>
                    </div>

                    <div class="home-filter-field">
                        <label class="home-filter-field-label" title="{{ __('messages.pages.home.filter_help_fuel') }}">{{ __('messages.forms.fuel_type') }}</label>
                        <div class="relative" data-dropdown="fuel_type" data-multiselect="true">
                            <button type="button" class="home-filter-pill-btn" data-dropdown-trigger aria-expanded="false" aria-haspopup="listbox">
                                <span class="dropdown-selected truncate">{{ __('messages.forms.all_fuel_types') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 flex-shrink-0 opacity-50">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="dropdown-menu home-filter-dropdown-menu" role="listbox">
                                <div class="p-2 border-b border-border">
                                    <input type="text" placeholder="{{ __('messages.forms.search_fuel_type') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                                </div>
                                <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_fuel_types') }}</button>
                                </div>
                            </div>
                            <div class="dropdown-values-container" data-name="fuel_type_id[]"></div>
                        </div>
                    </div>
                </div>

                <div id="home-filters-panel" class="mt-0 is-collapsed">
                    <div class="home-filter-grid">
                        <div class="home-filter-field" data-filter-range="price">
                            <label class="home-filter-field-label" title="{{ __('messages.pages.home.filter_help_price') }}">
                                <span class="home-filter-field-label-text">{{ __('messages.forms.price') }}</span>
                                <span class="home-filter-range-value" id="price-range-label"></span>
                            </label>
                            <input type="hidden" id="price-from-dropdown" name="price_from" value="">
                            <input type="hidden" id="price-to-dropdown" name="price_to" value="">
                            <div class="home-filter-range-box">
                                <div class="home-filter-range-track-wrap">
                                    <div class="home-filter-range-rail">
                                        <div id="price-range-track-dropdown" class="home-filter-range-fill"></div>
                                        <input type="range" id="price-slider-min-dropdown" min="0" max="{{ $filterPriceMax }}" step="1000" value="0" class="home-filter-range-input">
                                        <input type="range" id="price-slider-max-dropdown" min="0" max="{{ $filterPriceMax }}" step="1000" value="{{ $filterPriceMax }}" class="home-filter-range-input home-filter-range-input-max">
                                        <div id="price-handle-min-dropdown" class="home-filter-range-handle">
                                            <span class="home-filter-range-tooltip" aria-hidden="true"></span>
                                        </div>
                                        <div id="price-handle-max-dropdown" class="home-filter-range-handle">
                                            <span class="home-filter-range-tooltip" aria-hidden="true"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="home-filter-field" data-filter-range="km_driven">
                            <label class="home-filter-field-label" title="{{ __('messages.pages.home.filter_help_km') }}">
                                <span class="home-filter-field-label-text">{{ __('messages.forms.km_driven') }}</span>
                                <span class="home-filter-range-value" id="km-driven-range-label"></span>
                            </label>
                            <input type="hidden" id="km-driven-from" name="km_driven_from" value="">
                            <input type="hidden" id="km-driven-to" name="km_driven_to" value="">
                            <div class="home-filter-range-box">
                                <div class="home-filter-range-track-wrap">
                                    <div class="home-filter-range-rail">
                                        <div id="km-driven-range-track" class="home-filter-range-fill"></div>
                                        <input type="range" id="km-driven-slider-min" min="0" max="{{ $filterKmMax }}" step="1000" value="0" class="home-filter-range-input">
                                        <input type="range" id="km-driven-slider-max" min="0" max="{{ $filterKmMax }}" step="1000" value="{{ $filterKmMax }}" class="home-filter-range-input home-filter-range-input-max">
                                        <div id="km-driven-handle-min" class="home-filter-range-handle">
                                            <span class="home-filter-range-tooltip" aria-hidden="true"></span>
                                        </div>
                                        <div id="km-driven-handle-max" class="home-filter-range-handle">
                                            <span class="home-filter-range-tooltip" aria-hidden="true"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="home-filter-field" data-filter-range="model_year">
                            <label class="home-filter-field-label" title="{{ __('messages.pages.home.filter_help_year') }}">
                                <span class="home-filter-field-label-text">{{ __('messages.forms.model_year') }}</span>
                                <span class="home-filter-range-value" id="model-year-range-label"></span>
                            </label>
                            <input type="hidden" id="model-year-from" value="">
                            <input type="hidden" id="model-year-to" value="">
                            <div class="home-filter-range-box">
                                <div class="home-filter-range-track-wrap">
                                    <div class="home-filter-range-rail">
                                        <div id="model-year-range-track" class="home-filter-range-fill"></div>
                                        <input type="range" id="model-year-slider-min" min="{{ $homeYearMin }}" max="{{ $homeYearMax }}" step="1" value="{{ $homeYearMin }}" class="home-filter-range-input">
                                        <input type="range" id="model-year-slider-max" min="{{ $homeYearMin }}" max="{{ $homeYearMax }}" step="1" value="{{ $homeYearMax }}" class="home-filter-range-input home-filter-range-input-max">
                                        <div id="model-year-handle-min" class="home-filter-range-handle">
                                            <span class="home-filter-range-tooltip" aria-hidden="true"></span>
                                        </div>
                                        <div id="model-year-handle-max" class="home-filter-range-handle">
                                            <span class="home-filter-range-tooltip" aria-hidden="true"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <div class="home-filter-footer">
                        <button type="button" id="reset-filters-button" class="home-filter-footer-reset">
                            {{ __('messages.pages.vehicles.reset_filters') }}
                        </button>
                        <a href="{{ route('vehicles') }}" class="home-filter-advanced-link">
                            {{ __('messages.pages.vehicles.advanced_filters') }}
                        </a>
                        <button type="submit" id="home-show-results-btn" class="home-filter-cta home-filter-submit-cta">
                            {{ __('messages.pages.home.see_results', ['count' => $vehicleCountFormatted]) }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Hero Section -->
    <section class="relative bg-background py-16 md:py-20">
        <div class="container mx-auto px-4 md:px-6">
            <div class="max-w-4xl space-y-8">
                <p class="text-xl text-muted-foreground">
                    {{ $homePageContent['hero_description'] ?? __('messages.pages.home.hero_description') }}
                </p>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="{{ route('vehicles') }}" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-all hover:bg-primary/90 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        {{ __('messages.pages.home.browse_vehicles') }}
                    </a>
                    @if(!empty($publicAiEnabled))
                    <a href="{{ route('find-perfect-car') }}" class="inline-flex h-11 items-center justify-center rounded-md border border-input bg-background px-8 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ __('messages.pages.home.find_perfect_car_cta') }}
                    </a>
                    @endif
                    <a href="{{ route('contact') }}" class="inline-flex h-11 items-center justify-center rounded-md border border-input bg-background px-8 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        {{ __('messages.pages.footer.contact_us') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Vehicles Section -->
    <section class="featured-vehicles-section py-16 bg-muted">
        <div class="container mx-auto min-w-0 px-4 md:px-6">
            <div class="flex min-w-0 flex-col gap-8">
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        {{ $homePageContent['featured_vehicles_title'] ?? __('messages.pages.home.featured_vehicles_title') }}
                    </h2>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['featured_vehicles_description'] ?? __('messages.pages.home.featured_vehicles_description') }}
                    </p>
                </div>
                
                <!-- Featured Vehicles Horizontal Scroll -->
                <div class="featured-vehicles-carousel relative">
                    @if(isset($featuredVehicles) && $featuredVehicles->count() > 0)
                    <!-- Navigation Arrows (Desktop Only) -->
                    <button 
                        id="featured-vehicles-prev" 
                        class="featured-vehicles-nav-btn featured-vehicles-nav-btn-left hidden md:flex"
                        aria-label="{{ __('messages.pages.vehicles.detail.previous_vehicles') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button 
                        id="featured-vehicles-next" 
                        class="featured-vehicles-nav-btn featured-vehicles-nav-btn-right hidden md:flex"
                        aria-label="{{ __('messages.pages.vehicles.detail.next_vehicles') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </button>
                    
                    <div class="featured-vehicles-scroll-container overflow-x-auto pb-4 scroll-smooth snap-x snap-mandatory" id="featured-vehicles-scroll">
                        <div class="flex items-stretch gap-3">
                            @foreach($featuredVehicles as $vehicle)
                                @php
                                    $firstImage = $vehicle->images->first();
                                    $imgUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
                                    $badges = $listingPresentation->badgeFields($vehicle);
                                @endphp
                                <x-vehicle-listing-item
                                    class="featured-vehicle-card"
                                    :vehicle="$vehicle"
                                    :img-url="$imgUrl"
                                    :img-alt="$vehicle->title"
                                    :sales-type-name="$vehicle->salesType?->name"
                                    :trust-badge="$badges['trust_badge'] ?? false"
                                    :price-dropped-recently="$badges['price_dropped_recently'] ?? false"
                                    :premium-dealer-badge="$badges['premium_dealer_badge'] ?? false"
                                    :is-boosted="$badges['is_boosted'] ?? false"
                                />
                            @endforeach
                        </div>
                    </div>
                    <!-- Enquiry Dialogs for Featured Vehicles -->
                    @foreach($featuredVehicles as $vehicle)
                        <x-enquiry-dialog type="enquiry" :vehicle="$vehicle" />
                    @endforeach

                    <!-- Login Dialog -->
                    <x-login-dialog />
                    <div class="mt-4 flex justify-center">
                        <a href="{{ route('vehicles') }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            {{ __('messages.pages.home.view_all_vehicles') }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                    @else
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <p class="text-muted-foreground">{{ __('messages.pages.home.no_featured_vehicles') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="py-8 md:py-10 bg-background">
        <div class="container mx-auto px-4 md:px-6">
            <x-popular-cities />
        </div>
    </section>

    <!-- Why choose + services -->
    <section class="py-10 md:py-12 bg-muted" aria-labelledby="home-trust-heading">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-8 text-center">
                <h2 id="home-trust-heading" class="mb-2 text-3xl font-bold tracking-tight">
                    {{ $homePageContent['stats_title'] ?? __('messages.pages.home.why_choose_title') }}
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    {{ $homePageContent['stats_description'] ?? __('messages.pages.home.why_choose_description') }}
                </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-5 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.7L18.7 4c-.3-.8-1-1.3-1.7-1.3H8.7c-.7 0-1.4.5-1.7 1.3L5.5 11.3C4.7 11.3 4 12.1 4 13v3c0 .6.4 1 1 1h2"></path>
                                <circle cx="7" cy="18" r="2"></circle>
                                <circle cx="17" cy="18" r="2"></circle>
                                <path d="M12 8v6M9 11h6"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">{{ $vehicleCountFormatted }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_1_title'] ?? __('messages.pages.home.stat_1_title') }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_1_description'] ?? __('messages.pages.home.stat_1_description') }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-5 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_2_value'] ?? '30+' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_2_title'] ?? __('messages.pages.home.stat_2_title') }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_2_description'] ?? __('messages.pages.home.stat_2_description') }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-5 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                <line x1="16" x2="16" y1="2" y2="6"></line>
                                <line x1="8" x2="8" y1="2" y2="6"></line>
                                <line x1="3" x2="21" y1="10" y2="10"></line>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_3_value'] ?? '24/7' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_3_title'] ?? __('messages.pages.home.stat_3_title') }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_3_description'] ?? __('messages.pages.home.stat_3_description') }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-5 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M7 10v12M17 10v12M3 10h2a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1a2 2 0 0 1 2-2h2"></path>
                                <path d="M12 2v6"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_4_value'] ?? 'DK' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_4_title'] ?? __('messages.pages.home.stat_4_title') }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_4_description'] ?? __('messages.pages.home.stat_4_description') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-12">
            <div class="mb-8 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    {{ $homePageContent['features_title'] ?? __('messages.pages.home.our_services_title') }}
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    {{ $homePageContent['features_description'] ?? __('messages.pages.home.our_services_description') }}
                </p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                @php
                    $feature1Title = $homePageContent['feature_1_title'] ?? __('messages.pages.home.feature_1_title');
                    $feature2Title = $homePageContent['feature_2_title'] ?? __('messages.pages.home.feature_2_title');
                    $feature3Title = $homePageContent['feature_3_title'] ?? __('messages.pages.home.feature_3_title');
                @endphp
                <a href="{{ route('vehicles') }}" class="group bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center transition-all hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <div class="mb-4 rounded-full bg-primary/10 p-3" role="img" aria-label="{{ $feature1Title }}" title="{{ $feature1Title }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary" aria-hidden="true">
                            <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                            <line x1="2" x2="22" y1="10" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">{{ $feature1Title }}</h3>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['feature_1_description'] ?? __('messages.pages.home.feature_1_description') }}
                    </p>
                    <span class="mt-4 text-sm font-medium text-primary">{{ __('messages.pages.home.learn_more') }} &rarr;</span>
                </a>
                <a href="{{ route('contact') }}" class="group bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center transition-all hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <div class="mb-4 rounded-full bg-primary/10 p-3" role="img" aria-label="{{ $feature2Title }}" title="{{ $feature2Title }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">{{ $feature2Title }}</h3>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['feature_2_description'] ?? __('messages.pages.home.feature_2_description') }}
                    </p>
                    <span class="mt-4 text-sm font-medium text-primary">{{ __('messages.pages.home.learn_more') }} &rarr;</span>
                </a>
                <a href="{{ route('about') }}" class="group bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center transition-all hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <div class="mb-4 rounded-full bg-primary/10 p-3" role="img" aria-label="{{ $feature3Title }}" title="{{ $feature3Title }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                            <line x1="16" x2="16" y1="2" y2="6"></line>
                            <line x1="8" x2="8" y1="2" y2="6"></line>
                            <line x1="3" x2="21" y1="10" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">{{ $feature3Title }}</h3>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['feature_3_description'] ?? __('messages.pages.home.feature_3_description') }}
                    </p>
                    <span class="mt-4 text-sm font-medium text-primary">{{ __('messages.pages.home.learn_more') }} &rarr;</span>
                </a>
            </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-10 md:py-12 bg-background">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-8 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    {{ $homePageContent['testimonials_title'] ?? __('messages.pages.home.testimonials_title') }}
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    {{ $homePageContent['testimonials_description'] ?? __('messages.pages.home.testimonials_description') }}
                </p>
            </div>
            @php
                $cleanTestimonialQuote = function (string $quote): string {
                    $quote = trim($quote);
                    return preg_replace('/^[\s"\'\x{201C}\x{201D}\x{2018}\x{2019}\x{00AB}\x{00BB}]+|[\s"\'\x{201C}\x{201D}\x{2018}\x{2019}\x{00AB}\x{00BB}]+$/u', '', $quote);
                };

                $testimonials = [
                    [
                        'name' => \App\Support\TestimonialAttribution::name($homePageContent['testimonial_1_name'] ?? __('messages.pages.home.testimonial_1_name')),
                        'location' => \App\Support\TestimonialAttribution::location($homePageContent['testimonial_1_location'] ?? __('messages.pages.home.testimonial_1_location')),
                        'quote' => $cleanTestimonialQuote($homePageContent['testimonial_1_quote'] ?? __('messages.pages.home.testimonial_1_quote')),
                        'rating' => (int)($homePageContent['testimonial_1_rating'] ?? 5),
                        'date' => $homePageContent['testimonial_1_date'] ?? null,
                    ],
                    [
                        'name' => \App\Support\TestimonialAttribution::name($homePageContent['testimonial_2_name'] ?? __('messages.pages.home.testimonial_2_name')),
                        'location' => \App\Support\TestimonialAttribution::location($homePageContent['testimonial_2_location'] ?? __('messages.pages.home.testimonial_2_location')),
                        'quote' => $cleanTestimonialQuote($homePageContent['testimonial_2_quote'] ?? __('messages.pages.home.testimonial_2_quote')),
                        'rating' => (int)($homePageContent['testimonial_2_rating'] ?? 5),
                        'date' => $homePageContent['testimonial_2_date'] ?? null,
                    ],
                    [
                        'name' => \App\Support\TestimonialAttribution::name($homePageContent['testimonial_3_name'] ?? __('messages.pages.home.testimonial_3_name')),
                        'location' => \App\Support\TestimonialAttribution::location($homePageContent['testimonial_3_location'] ?? __('messages.pages.home.testimonial_3_location')),
                        'quote' => $cleanTestimonialQuote($homePageContent['testimonial_3_quote'] ?? __('messages.pages.home.testimonial_3_quote')),
                        'rating' => (int)($homePageContent['testimonial_3_rating'] ?? 4),
                        'date' => $homePageContent['testimonial_3_date'] ?? null,
                    ],
                ];
            @endphp
            <div class="relative">
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3" id="testimonials-grid">
                @foreach($testimonials as $index => $testimonial)
                <div class="testimonial-card overflow-hidden rounded-lg border border-border bg-card {{ $index > 0 ? 'hidden md:block' : '' }}" data-testimonial-index="{{ $index }}">
                    <div class="p-6">
                        <div class="flex flex-col gap-4">
                            <div class="flex gap-1">
                                @for($i = 0; $i < 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="{{ $i < $testimonial['rating'] ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 {{ $i < $testimonial['rating'] ? 'fill-yellow-500 text-yellow-500' : 'text-muted-foreground' }}">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                @endfor
                            </div>

                            <blockquote class="testimonial-quote text-muted-foreground">
                                {{ $testimonial['quote'] }}
                            </blockquote>

                            <div class="mt-2 flex items-center gap-3">
                                <div class="bg-muted flex h-10 w-10 items-center justify-center overflow-hidden rounded-full">
                                    <span class="text-muted-foreground">{{ mb_strtoupper(mb_substr($testimonial['name'], 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium">{{ $testimonial['name'] }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $testimonial['location'] }}</p>
                                    @if(!empty($testimonial['date']))
                                    <p class="text-xs text-muted-foreground/80">{{ $testimonial['date'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
                @if(count($testimonials) >= 3)
                <div class="mt-6 flex items-center justify-center gap-4 md:hidden">
                    <button
                        type="button"
                        id="testimonial-prev"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border bg-background shadow-sm transition-all hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label="{{ __('messages.common.previous') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button
                        type="button"
                        id="testimonial-next"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border bg-background shadow-sm transition-all hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label="{{ __('messages.common.next') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </section>

</div>

<script type="module">
    import EmblaCarousel from 'https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/+esm';
    
    document.addEventListener('DOMContentLoaded', function() {
        const emblaNode = document.getElementById('featured-vehicles-embla');
        const prevButton = document.querySelector('.embla__prev');
        const nextButton = document.querySelector('.embla__next');

        if (!emblaNode) return;

        const embla = EmblaCarousel(emblaNode, {
            align: 'start',
            loop: true,
        });

        const updateButtonStates = () => {
            if (prevButton) {
                const canScrollPrev = embla.canScrollPrev();
                prevButton.disabled = !canScrollPrev;
                if (!canScrollPrev) {
                    prevButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            if (nextButton) {
                const canScrollNext = embla.canScrollNext();
                nextButton.disabled = !canScrollNext;
                if (!canScrollNext) {
                    nextButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        };

        if (prevButton) {
            prevButton.addEventListener('click', () => {
                embla.scrollPrev();
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                embla.scrollNext();
            });
        }

        embla.on('select', updateButtonStates);
        embla.on('reInit', updateButtonStates);
        updateButtonStates();
    });

    // Searchable Dropdowns Functionality
    const DEFAULT_DROPDOWN_LABELS = {
        'brand': '{{ __('messages.forms.all_brands') }}',
        'model': '{{ __('messages.forms.all_models') }}',
        'fuel_type': '{{ __('messages.forms.all_fuel_types') }}'
    };

    const HOME_YEAR_MIN = {{ $homeYearMin }};
    const HOME_YEAR_MAX = {{ $homeYearMax }};
    const HOME_PRICE_MAX = {{ $filterPriceMax }};
    const HOME_KM_MAX = {{ $filterKmMax }};
    const HOME_BEFORE_YEAR_LABEL = @json(__('messages.pages.home.before_year', ['year' => 1980]));
    const HOME_RESULTS_COUNT_LABEL = @json(__('messages.pages.home.results_count', ['count' => ':count']));
    const HOME_SEE_RESULTS_LABEL = @json(__('messages.pages.home.see_results', ['count' => ':count']));

    function formatHomeNumber(value) {
        return new Intl.NumberFormat('da-DK').format(Math.round(value));
    }

    function formatPriceRangeChip(min, max) {
        const minLabel = min <= 0 ? '0 kr.' : `${formatHomeNumber(min)} kr.`;
        const maxLabel = max >= HOME_PRICE_MAX ? '1.000.000+ kr.' : `${formatHomeNumber(max)} kr.`;
        return `${minLabel} – ${maxLabel}`;
    }

    function formatKmRangeChip(min, max) {
        const minLabel = min <= 0 ? '0 km.' : `${formatHomeNumber(min)} km.`;
        const maxLabel = max >= HOME_KM_MAX ? '500.000+ km.' : `${formatHomeNumber(max)} km.`;
        return `${minLabel} – ${maxLabel}`;
    }

    function formatYearRangeChip(min, max) {
        const minLabel = min <= HOME_YEAR_MIN ? HOME_BEFORE_YEAR_LABEL : String(Math.round(min));
        const maxLabel = max >= HOME_YEAR_MAX ? String(HOME_YEAR_MAX) : String(Math.round(max));
        return `${minLabel} – ${maxLabel}`;
    }

    function formatPriceHandleValue(value, isMin) {
        if (isMin && value <= 0) return '0 kr.';
        if (!isMin && value >= HOME_PRICE_MAX) return '1.000.000+ kr.';
        return `${formatHomeNumber(value)} kr.`;
    }

    function formatKmHandleValue(value, isMin) {
        if (isMin && value <= 0) return '0 km.';
        if (!isMin && value >= HOME_KM_MAX) return '500.000+ km.';
        return `${formatHomeNumber(value)} km.`;
    }

    function formatYearHandleValue(value, isMin) {
        if (isMin && value <= HOME_YEAR_MIN) return HOME_BEFORE_YEAR_LABEL;
        if (!isMin && value >= HOME_YEAR_MAX) return String(HOME_YEAR_MAX);
        return String(Math.round(value));
    }

    function updateHomeFilterChips() {
        const container = document.getElementById('home-filter-chips');
        if (!container) return;

        const chips = [];
        const priceFrom = document.getElementById('price-from-dropdown')?.value;
        const priceTo = document.getElementById('price-to-dropdown')?.value;
        if (priceFrom || priceTo) {
            const minSlider = document.getElementById('price-slider-min-dropdown');
            const maxSlider = document.getElementById('price-slider-max-dropdown');
            const finalMin = parseFloat(minSlider?.value) || 0;
            const finalMax = parseFloat(maxSlider?.value) || HOME_PRICE_MAX;
            chips.push({
                key: 'price',
                label: formatPriceRangeChip(finalMin, finalMax),
                onRemove: () => {
                    const minSlider = document.getElementById('price-slider-min-dropdown');
                    const maxSlider = document.getElementById('price-slider-max-dropdown');
                    if (minSlider) minSlider.value = '0';
                    if (maxSlider) maxSlider.value = String(HOME_PRICE_MAX);
                    minSlider?.dispatchEvent(new Event('input'));
                },
            });
        }

        const kmFrom = document.getElementById('km-driven-from')?.value;
        const kmTo = document.getElementById('km-driven-to')?.value;
        if (kmFrom || kmTo) {
            const minSlider = document.getElementById('km-driven-slider-min');
            const maxSlider = document.getElementById('km-driven-slider-max');
            const finalMin = parseFloat(minSlider?.value) || 0;
            const finalMax = parseFloat(maxSlider?.value) || HOME_KM_MAX;
            chips.push({
                key: 'km',
                label: formatKmRangeChip(finalMin, finalMax),
                onRemove: () => {
                    const minSlider = document.getElementById('km-driven-slider-min');
                    const maxSlider = document.getElementById('km-driven-slider-max');
                    if (minSlider) minSlider.value = '0';
                    if (maxSlider) maxSlider.value = String(HOME_KM_MAX);
                    minSlider?.dispatchEvent(new Event('input'));
                },
            });
        }

        const yearFrom = document.getElementById('model-year-from')?.value;
        const yearTo = document.getElementById('model-year-to')?.value;
        if (yearFrom || yearTo) {
            const minSlider = document.getElementById('model-year-slider-min');
            const maxSlider = document.getElementById('model-year-slider-max');
            const finalMin = parseFloat(minSlider?.value) || HOME_YEAR_MIN;
            const finalMax = parseFloat(maxSlider?.value) || HOME_YEAR_MAX;
            chips.push({
                key: 'year',
                label: formatYearRangeChip(finalMin, finalMax),
                onRemove: () => {
                    const minSlider = document.getElementById('model-year-slider-min');
                    const maxSlider = document.getElementById('model-year-slider-max');
                    if (minSlider) minSlider.value = String(HOME_YEAR_MIN);
                    if (maxSlider) maxSlider.value = String(HOME_YEAR_MAX);
                    minSlider?.dispatchEvent(new Event('input'));
                },
            });
        }

        const brandDropdown = document.querySelector('[data-dropdown="brand"]');
        if (brandDropdown) {
            getMultiSelectValues(brandDropdown).forEach(id => {
                const opt = brandDropdown.querySelector(`.dropdown-option.selected[data-value="${id}"]`);
                const label = opt?.getAttribute('data-text') || id;
                chips.push({
                    key: `brand-${id}`,
                    label,
                    onRemove: () => {
                        opt?.classList.remove('selected');
                        syncMultiSelectInputs(brandDropdown);
                        updateMultiSelectButtonText(brandDropdown);
                        filterModelsByBrand(getMultiSelectValues(brandDropdown));
                        updateHomeFilterChips();
                    },
                });
            });
        }

        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (modelDropdown) {
            getMultiSelectValues(modelDropdown).forEach(id => {
                const opt = modelDropdown.querySelector(`.dropdown-option.selected[data-value="${id}"]`);
                const label = opt?.getAttribute('data-text') || id;
                chips.push({
                    key: `model-${id}`,
                    label,
                    onRemove: () => {
                        opt?.classList.remove('selected');
                        syncMultiSelectInputs(modelDropdown);
                        updateMultiSelectButtonText(modelDropdown);
                        updateHomeFilterChips();
                    },
                });
            });
        }

        const fuelDropdown = document.querySelector('[data-dropdown="fuel_type"]');
        if (fuelDropdown) {
            getMultiSelectValues(fuelDropdown).forEach(id => {
                const opt = fuelDropdown.querySelector(`.dropdown-option.selected[data-value="${id}"]`);
                const label = opt?.getAttribute('data-text') || id;
                chips.push({
                    key: `fuel-${id}`,
                    label,
                    onRemove: () => {
                        opt?.classList.remove('selected');
                        syncMultiSelectInputs(fuelDropdown);
                        updateMultiSelectButtonText(fuelDropdown);
                        updateHomeFilterChips();
                    },
                });
            });
        }

        const activeListingTab = document.querySelector('.home-filter-tab.active[data-listing-type]:not([data-listing-type=""])');
        if (activeListingTab) {
            chips.push({
                key: 'listing-type',
                label: activeListingTab.textContent.trim(),
                onRemove: () => {
                    document.querySelectorAll('.home-filter-tab').forEach(tab => {
                        tab.classList.toggle('active', tab.getAttribute('data-listing-type') === '');
                    });
                    syncListingTypeInput('');
                    updateHomeFilterChips();
                },
            });
        }

        container.innerHTML = '';
        chips.forEach(chip => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'home-filter-chip';
            btn.innerHTML = `<span>${chip.label}</span><span aria-hidden="true">×</span>`;
            btn.addEventListener('click', chip.onRemove);
            container.appendChild(btn);
        });

        scheduleHomeResultsCountUpdate();
    }

    function syncListingTypeInput(listingTypeId) {
        const container = document.getElementById('home-listing-type-inputs');
        if (!container) return;
        container.innerHTML = '';
        if (!listingTypeId) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = container.getAttribute('data-name');
        input.value = listingTypeId;
        container.appendChild(input);
    }

    function initListingTypeTabs() {
        const tabs = document.querySelectorAll('.home-filter-tab[data-listing-type]');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                syncListingTypeInput(tab.getAttribute('data-listing-type') || '');
                updateHomeFilterChips();
            });
        });
    }

    function initFilterPanelToggle() {
        const toggleButton = document.getElementById('toggle-filters-button');
        const panel = document.getElementById('home-filters-panel');
        if (!toggleButton || !panel) return;

        toggleButton.addEventListener('click', () => {
            const isCollapsed = panel.classList.toggle('is-collapsed');
            const label = toggleButton.querySelector('.home-filter-hide-btn-label');
            if (label) {
                label.textContent = isCollapsed
                    ? toggleButton.getAttribute('data-show-label')
                    : toggleButton.getAttribute('data-hide-label');
            }
            toggleButton.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        });
    }

    function getMultiSelectValues(dropdown) {
        const options = dropdown.querySelectorAll('.dropdown-option[data-value]:not([data-value=""])');
        return Array.from(options).filter(opt => opt.classList.contains('selected')).map(opt => opt.getAttribute('data-value'));
    }

    function syncMultiSelectInputs(dropdown) {
        const container = dropdown.querySelector('.dropdown-values-container');
        if (!container) return;
        if (dropdown.getAttribute('data-dropdown') === 'model_year') {
            container.innerHTML = '';

            return;
        }
        const name = container.getAttribute('data-name');
        const values = getMultiSelectValues(dropdown);
        container.innerHTML = '';
        values.forEach(v => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = v;
            container.appendChild(input);
        });
    }

    function updateMultiSelectButtonText(dropdown) {
        const selectedText = dropdown.querySelector('.dropdown-selected');
        const dropdownType = dropdown.getAttribute('data-dropdown');
        const options = dropdown.querySelectorAll('.dropdown-option[data-value]:not([data-value=""])');
        const selected = Array.from(options).filter(opt => opt.classList.contains('selected'));
        const labels = selected.map(opt => opt.getAttribute('data-text') || opt.textContent.trim());
        const defaultLabel = DEFAULT_DROPDOWN_LABELS[dropdownType] || '{{ __('messages.common.select') }}';
        if (labels.length === 0) {
            selectedText.textContent = defaultLabel;
        } else if (labels.length <= 2) {
            selectedText.textContent = labels.join(', ');
        } else {
            selectedText.textContent = defaultLabel + ' (' + labels.length + ')';
        }
    }

    function closeAllDropdownMenus(exceptDropdown = null) {
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            if (exceptDropdown && dropdown === exceptDropdown) return;
            const menu = dropdown.querySelector('.dropdown-menu');
            const trigger = dropdown.querySelector('[data-dropdown-trigger]');
            if (menu) menu.classList.remove('is-open');
            if (trigger) trigger.classList.remove('is-open');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function openDropdownMenu(dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        if (!menu || !trigger || trigger.disabled) return;

        closeAllDropdownMenus(dropdown);
        menu.classList.add('is-open');
        trigger.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
    }

    function closeDropdownMenu(dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        if (menu) menu.classList.remove('is-open');
        if (trigger) trigger.classList.remove('is-open');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    function initSearchableDropdowns() {
        const dropdowns = document.querySelectorAll('[data-dropdown]');

        dropdowns.forEach(dropdown => {
            const dropdownType = dropdown.getAttribute('data-dropdown');
            const trigger = dropdown.querySelector('[data-dropdown-trigger]');
            const menu = dropdown.querySelector('.dropdown-menu');
            if (!trigger || !menu) return;

            const searchInput = dropdown.querySelector('.dropdown-search');
            const valuesContainer = dropdown.querySelector('.dropdown-values-container');
            const selectedText = dropdown.querySelector('.dropdown-selected');
            const isMultiSelect = dropdown.getAttribute('data-multiselect') === 'true';

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (trigger.disabled) return;

                const isOpen = menu.classList.contains('is-open');
                if (isOpen) {
                    closeDropdownMenu(dropdown);
                    return;
                }

                openDropdownMenu(dropdown);

                if (dropdownType === 'brand') {
                    loadBrandOptions(searchInput?.value || '');
                } else if (dropdownType === 'model') {
                    const brandDropdown = document.querySelector('[data-dropdown="brand"]');
                    const brandIds = brandDropdown ? getMultiSelectValues(brandDropdown) : [];
                    filterModelsByBrand(brandIds);
                } else if (dropdownType === 'fuel_type') {
                    loadFuelTypeOptions(searchInput?.value || '');
                }

                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 50);
                }
            });

            if (searchInput) {
                searchInput.addEventListener('click', (e) => e.stopPropagation());
                searchInput.addEventListener('input', (e) => {
                    const rawTerm = e.target.value || '';
                    if (dropdownType === 'brand') {
                        loadBrandOptions(rawTerm);
                    } else if (dropdownType === 'model') {
                        const brandDropdown = document.querySelector('[data-dropdown="brand"]');
                        const brandIds = brandDropdown ? getMultiSelectValues(brandDropdown) : [];
                        filterModelsByBrand(brandIds);
                    } else if (dropdownType === 'fuel_type') {
                        loadFuelTypeOptions(rawTerm);
                    } else {
                        const searchTerm = rawTerm.toLowerCase();
                        dropdown.querySelectorAll('.dropdown-option').forEach(option => {
                            const text = (option.getAttribute('data-text') || option.textContent).toLowerCase();
                            option.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    }
                });
            }

            const optionsContainer = dropdown.querySelector('.dropdown-options');
            if (optionsContainer) {
                optionsContainer.addEventListener('click', (evt) => {
                    const option = evt.target.closest('.dropdown-option');
                    if (!option) return;
                    evt.preventDefault();
                    evt.stopPropagation();

                    const value = option.getAttribute('data-value');
                    const text = option.getAttribute('data-text') || option.textContent.trim();

                    if (isMultiSelect) {
                        if (value === '') {
                            dropdown.querySelectorAll('.dropdown-option.selected').forEach(opt => opt.classList.remove('selected'));
                            syncMultiSelectInputs(dropdown);
                            updateMultiSelectButtonText(dropdown);
                                if (dropdownType === 'brand') filterModelsByBrand([]);
                                closeDropdownMenu(dropdown);
                                updateHomeFilterChips();
                                return;
                        }

                        option.classList.toggle('selected');
                        syncMultiSelectInputs(dropdown);
                        updateMultiSelectButtonText(dropdown);

                            if (dropdownType === 'brand') {
                                filterModelsByBrand(getMultiSelectValues(dropdown));
                            }
                            updateHomeFilterChips();
                            return;
                        }

                    if (valuesContainer) {
                        valuesContainer.innerHTML = '';
                        if (value) {
                            const name = valuesContainer.getAttribute('data-name');
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = value;
                            valuesContainer.appendChild(input);
                        }
                    }

                    if (value === '') {
                        selectedText.textContent = DEFAULT_DROPDOWN_LABELS[dropdownType] || '{{ __('messages.common.select') }}';
                    } else {
                        selectedText.textContent = text;
                    }
                    if (dropdownType === 'brand') filterModelsByBrand(value);
                    closeDropdownMenu(dropdown);
                });
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-dropdown]')) return;
            closeAllDropdownMenus();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllDropdownMenus();
        });
    }
    
    let brandsFetchToken = 0;
    let modelsFetchToken = 0;
    let fuelTypesFetchToken = 0;

    async function loadBrandOptions(searchTerm) {
        const brandDropdown = document.querySelector('[data-dropdown="brand"]');
        if (!brandDropdown) return;

        const optionsContainer = brandDropdown.querySelector('.dropdown-options');
        if (!optionsContainer) return;

        const defaultOption = brandDropdown.querySelector('.dropdown-option[data-value=""]');

        // Preserve current selections across re-renders.
        const selectedMeta = {};
        brandDropdown.querySelectorAll('.dropdown-option.selected[data-value]:not([data-value=""])').forEach(opt => {
            const id = String(opt.getAttribute('data-value'));
            selectedMeta[id] = {
                text: opt.getAttribute('data-text') || opt.textContent.trim()
            };
        });

        brandsFetchToken++;
        const token = brandsFetchToken;

        const term = (searchTerm || '').trim();
        const url = new URL('/api/v1/brands', window.location.origin);
        url.searchParams.set('listing', '1');
        if (term !== '') url.searchParams.set('search', term);

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!response.ok) return;
            const json = await response.json().catch(() => ({}));
            if (token !== brandsFetchToken) return;

            const items = json?.data?.items || [];

            // Remove previous non-default options.
            optionsContainer.querySelectorAll('.dropdown-option').forEach(opt => {
                if (defaultOption && opt === defaultOption) return;
                if (opt.getAttribute('data-value') === '') return;
                opt.remove();
            });

            const resultsIds = new Set();

            items.forEach(item => {
                const id = String(item.id);
                resultsIds.add(id);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2';
                btn.setAttribute('data-value', id);
                btn.setAttribute('data-text', item.name);

                const check = document.createElement('span');
                check.className = 'dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0';
                check.textContent = '✓';

                btn.appendChild(check);
                btn.appendChild(document.createTextNode(item.name));

                if (selectedMeta[id]) btn.classList.add('selected');
                optionsContainer.appendChild(btn);
            });

            // Ensure selected items remain visible even if they don't match current search term.
            Object.keys(selectedMeta).forEach(id => {
                if (resultsIds.has(id)) return;
                const meta = selectedMeta[id];
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2';
                btn.setAttribute('data-value', id);
                btn.setAttribute('data-text', meta.text);

                const check = document.createElement('span');
                check.className = 'dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0';
                check.textContent = '✓';

                btn.appendChild(check);
                btn.appendChild(document.createTextNode(meta.text));

                btn.classList.add('selected');
                optionsContainer.appendChild(btn);
            });

            syncMultiSelectInputs(brandDropdown);
            updateMultiSelectButtonText(brandDropdown);
        } catch (e) {
            // Silently fail - dropdown can still function without brand suggestions.
            console.debug('Brand lookup failed:', e);
        }
    }

    async function loadFuelTypeOptions(searchTerm) {
        const fuelDropdown = document.querySelector('[data-dropdown="fuel_type"]');
        if (!fuelDropdown) return;

        const optionsContainer = fuelDropdown.querySelector('.dropdown-options');
        if (!optionsContainer) return;

        const defaultOption = fuelDropdown.querySelector('.dropdown-option[data-value=""]');
        const selectedMeta = {};
        fuelDropdown.querySelectorAll('.dropdown-option.selected[data-value]:not([data-value=""])').forEach(opt => {
            const id = String(opt.getAttribute('data-value'));
            selectedMeta[id] = opt.getAttribute('data-text') || opt.textContent.trim();
        });

        fuelTypesFetchToken++;
        const token = fuelTypesFetchToken;
        const term = (searchTerm || '').trim().toLowerCase();

        try {
            const response = await fetch('/api/v1/constants', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!response.ok) return;
            const json = await response.json().catch(() => ({}));
            if (token !== fuelTypesFetchToken) return;

            let items = json?.data?.fuel_types || [];
            if (term !== '') {
                items = items.filter(item => String(item.name || '').toLowerCase().includes(term));
            }

            optionsContainer.querySelectorAll('.dropdown-option').forEach(opt => {
                if (defaultOption && opt === defaultOption) return;
                if (opt.getAttribute('data-value') === '') return;
                opt.remove();
            });

            const resultsIds = new Set();
            items.forEach(item => {
                const id = String(item.id);
                resultsIds.add(id);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2';
                btn.setAttribute('data-value', id);
                btn.setAttribute('data-text', item.name);

                const check = document.createElement('span');
                check.className = 'dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0';
                check.textContent = '✓';

                btn.appendChild(check);
                btn.appendChild(document.createTextNode(item.name));

                if (selectedMeta[id]) btn.classList.add('selected');
                optionsContainer.appendChild(btn);
            });

            Object.keys(selectedMeta).forEach(id => {
                if (resultsIds.has(id)) return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2';
                btn.setAttribute('data-value', id);
                btn.setAttribute('data-text', selectedMeta[id]);

                const check = document.createElement('span');
                check.className = 'dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0';
                check.textContent = '✓';

                btn.appendChild(check);
                btn.appendChild(document.createTextNode(selectedMeta[id]));
                btn.classList.add('selected');
                optionsContainer.appendChild(btn);
            });

            syncMultiSelectInputs(fuelDropdown);
            updateMultiSelectButtonText(fuelDropdown);
        } catch (e) {
            console.debug('Fuel type lookup failed:', e);
        }
    }

    // Filter models by brand(s) + search (remote, partial dataset)
    function filterModelsByBrand(brandIds) {
        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (!modelDropdown) return;

        const modelButton = document.getElementById('model-dropdown-button');
        const optionsContainer = modelDropdown.querySelector('.dropdown-options');
        const defaultOption = modelDropdown.querySelector('.dropdown-option[data-value=""]');
        const searchInput = modelDropdown.querySelector('.dropdown-search');

        const searchTerm = (searchInput?.value || '').trim();
        const ids = Array.isArray(brandIds) ? brandIds : (brandIds === '' || !brandIds ? [] : [String(brandIds)]);
        const brandIdSet = new Set(ids.map(String));

        if (modelButton) modelButton.disabled = false;

        // Preserve currently selected models that match the allowed brands (if any).
        const selectedMeta = {};
        modelDropdown.querySelectorAll('.dropdown-option.selected[data-value]:not([data-value=""])').forEach(opt => {
            const id = String(opt.getAttribute('data-value'));
            const bId = String(opt.getAttribute('data-brand-id') || '');
            if (brandIdSet.size > 0 && !brandIdSet.has(bId)) return;
            selectedMeta[id] = {
                text: opt.getAttribute('data-text') || opt.textContent.trim(),
                brandId: bId
            };
        });

        modelsFetchToken++;
        const token = modelsFetchToken;

        const url = new URL('/api/v1/listing-models', window.location.origin);
        if (searchTerm !== '') url.searchParams.set('search', searchTerm);
        if (ids.length > 0) url.searchParams.set('brand_ids', ids.join(','));

        fetch(url.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(r => r.ok ? r.json() : null)
            .then(json => {
                if (!json || token !== modelsFetchToken) return;
                const items = json?.data?.items || [];

                // Remove previous non-default options.
                if (optionsContainer) {
                    optionsContainer.querySelectorAll('.dropdown-option').forEach(opt => {
                        if (defaultOption && opt === defaultOption) return;
                        if (opt.getAttribute('data-value') === '') return;
                        opt.remove();
                    });
                }

                const resultsIds = new Set();

                items.forEach(item => {
                    const id = String(item.id);
                    resultsIds.add(id);

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2';
                    btn.setAttribute('data-value', id);
                    btn.setAttribute('data-text', item.name);
                    btn.setAttribute('data-brand-id', String(item.brand_id));

                    const check = document.createElement('span');
                    check.className = 'dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0';
                    check.textContent = '✓';

                    btn.appendChild(check);
                    btn.appendChild(document.createTextNode(item.name));

                    if (selectedMeta[id]) btn.classList.add('selected');
                    if (optionsContainer) optionsContainer.appendChild(btn);
                });

                // Ensure selected models remain visible even if they don't match search term.
                Object.keys(selectedMeta).forEach(id => {
                    if (resultsIds.has(id)) return;
                    const meta = selectedMeta[id];

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2';
                    btn.setAttribute('data-value', id);
                    btn.setAttribute('data-text', meta.text);
                    btn.setAttribute('data-brand-id', meta.brandId);

                    const check = document.createElement('span');
                    check.className = 'dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0';
                    check.textContent = '✓';

                    btn.appendChild(check);
                    btn.appendChild(document.createTextNode(meta.text));

                    btn.classList.add('selected');
                    if (optionsContainer) optionsContainer.appendChild(btn);
                });

                syncMultiSelectInputs(modelDropdown);
                updateMultiSelectButtonText(modelDropdown);
            })
            .catch(err => {
                console.debug('Model lookup failed:', err);
            });
    }
    
    // Range sliders for Price, KM Driven, and Model Year
    function initDropdownRangeSliders() {
        const rangeConfigs = [];

        const priceConfig = {
            minSlider: document.getElementById('price-slider-min-dropdown'),
            maxSlider: document.getElementById('price-slider-max-dropdown'),
            minInput: document.getElementById('price-from-dropdown'),
            maxInput: document.getElementById('price-to-dropdown'),
            minHandle: document.getElementById('price-handle-min-dropdown'),
            maxHandle: document.getElementById('price-handle-max-dropdown'),
            track: document.getElementById('price-range-track-dropdown'),
            valueLabel: document.getElementById('price-range-label'),
            min: 0,
            max: HOME_PRICE_MAX,
            formatValue: formatPriceHandleValue,
            formatRange: formatPriceRangeChip,
        };
        
        const kmDrivenConfig = {
            minSlider: document.getElementById('km-driven-slider-min'),
            maxSlider: document.getElementById('km-driven-slider-max'),
            minInput: document.getElementById('km-driven-from'),
            maxInput: document.getElementById('km-driven-to'),
            minHandle: document.getElementById('km-driven-handle-min'),
            maxHandle: document.getElementById('km-driven-handle-max'),
            track: document.getElementById('km-driven-range-track'),
            valueLabel: document.getElementById('km-driven-range-label'),
            min: 0,
            max: HOME_KM_MAX,
            formatValue: formatKmHandleValue,
            formatRange: formatKmRangeChip,
        };

        const yearConfig = {
            minSlider: document.getElementById('model-year-slider-min'),
            maxSlider: document.getElementById('model-year-slider-max'),
            minInput: document.getElementById('model-year-from'),
            maxInput: document.getElementById('model-year-to'),
            minHandle: document.getElementById('model-year-handle-min'),
            maxHandle: document.getElementById('model-year-handle-max'),
            track: document.getElementById('model-year-range-track'),
            valueLabel: document.getElementById('model-year-range-label'),
            min: HOME_YEAR_MIN,
            max: HOME_YEAR_MAX,
            formatValue: formatYearHandleValue,
            formatRange: formatYearRangeChip,
        };

        function setActiveRangeHandle(config, isMin) {
            config.minHandle?.classList.remove('is-active');
            config.maxHandle?.classList.remove('is-active');
            (isMin ? config.minHandle : config.maxHandle)?.classList.add('is-active');
        }

        function clearActiveRangeHandles(config) {
            config.minHandle?.classList.remove('is-active');
            config.maxHandle?.classList.remove('is-active');
        }

        function updateHandleTooltips(config, finalMin, finalMax) {
            const minTooltip = config.minHandle?.querySelector('.home-filter-range-tooltip');
            const maxTooltip = config.maxHandle?.querySelector('.home-filter-range-tooltip');
            if (minTooltip && config.formatValue) {
                minTooltip.textContent = config.formatValue(finalMin, true);
            }
            if (maxTooltip && config.formatValue) {
                maxTooltip.textContent = config.formatValue(finalMax, false);
            }
        }

        function endAllRangeDrags() {
            rangeConfigs.forEach(clearActiveRangeHandles);
        }
        
        [priceConfig, kmDrivenConfig, yearConfig].forEach(config => {
            if (!config.minSlider || !config.maxSlider) return;
            rangeConfigs.push(config);
            
            function updateSlider() {
                const minVal = parseFloat(config.minSlider.value) || config.min;
                const maxVal = parseFloat(config.maxSlider.value) || config.max;
                
                if (minVal > maxVal) {
                    config.minSlider.value = maxVal;
                    config.maxSlider.value = minVal;
                }
                
                const finalMin = Math.min(parseFloat(config.minSlider.value), parseFloat(config.maxSlider.value));
                const finalMax = Math.max(parseFloat(config.minSlider.value), parseFloat(config.maxSlider.value));
                
                config.minInput.value = finalMin <= config.min ? '' : String(finalMin);
                config.maxInput.value = finalMax >= config.max ? '' : String(finalMax);
                
                const minPercent = ((finalMin - config.min) / (config.max - config.min)) * 100;
                const maxPercent = ((finalMax - config.min) / (config.max - config.min)) * 100;
                
                if (config.minHandle) config.minHandle.style.left = `${minPercent}%`;
                if (config.maxHandle) config.maxHandle.style.left = `${maxPercent}%`;
                if (config.track) {
                    config.track.style.left = `${minPercent}%`;
                    config.track.style.width = `${maxPercent - minPercent}%`;
                }

                if (config.valueLabel && config.formatRange) {
                    let label = config.formatRange(finalMin, finalMax);
                    if (config.valueLabel.id === 'price-range-label') {
                        label = label.replace(/\s*kr\./g, ' DKK');
                    } else if (config.valueLabel.id === 'km-driven-range-label') {
                        label = label.replace(/\s*km\./g, ' km');
                    }
                    config.valueLabel.textContent = label;
                }

                updateHandleTooltips(config, finalMin, finalMax);
                updateHomeFilterChips();
            }
            
            updateSlider();
            
            config.minSlider.addEventListener('input', updateSlider);
            config.maxSlider.addEventListener('input', updateSlider);

            [config.minSlider, config.maxSlider].forEach((slider, index) => {
                slider.addEventListener('pointerdown', () => setActiveRangeHandle(config, index === 0));
            });
            
            let isDragging = false;
            let activeHandle = null;
            
            [config.minHandle, config.maxHandle].forEach((handle, index) => {
                handle.addEventListener('mousedown', (e) => {
                    isDragging = true;
                    activeHandle = index === 0 ? config.minSlider : config.maxSlider;
                    setActiveRangeHandle(config, index === 0);
                    e.preventDefault();
                    e.stopPropagation();
                });
                handle.addEventListener('touchstart', () => {
                    isDragging = true;
                    activeHandle = index === 0 ? config.minSlider : config.maxSlider;
                    setActiveRangeHandle(config, index === 0);
                }, { passive: true });
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging || !activeHandle) return;
                
                const sliderContainer = activeHandle.closest('.home-filter-range-rail');
                if (!sliderContainer) return;
                
                const rect = sliderContainer.getBoundingClientRect();
                const percent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                const value = config.min + (percent / 100) * (config.max - config.min);
                const step = parseFloat(activeHandle.step) || 1;
                const steppedValue = Math.round(value / step) * step;
                const clampedValue = Math.max(config.min, Math.min(config.max, steppedValue));
                
                activeHandle.value = clampedValue;
                updateSlider();
            });

            document.addEventListener('touchmove', (e) => {
                if (!isDragging || !activeHandle || !e.touches[0]) return;

                const sliderContainer = activeHandle.closest('.home-filter-range-rail');
                if (!sliderContainer) return;

                const rect = sliderContainer.getBoundingClientRect();
                const percent = Math.max(0, Math.min(100, ((e.touches[0].clientX - rect.left) / rect.width) * 100));
                const value = config.min + (percent / 100) * (config.max - config.min);
                const step = parseFloat(activeHandle.step) || 1;
                const steppedValue = Math.round(value / step) * step;
                const clampedValue = Math.max(config.min, Math.min(config.max, steppedValue));

                activeHandle.value = clampedValue;
                updateSlider();
            }, { passive: true });
            
            const endDrag = () => {
                isDragging = false;
                activeHandle = null;
                clearActiveRangeHandles(config);
            };

            document.addEventListener('mouseup', endDrag);
            document.addEventListener('touchend', endDrag);
        });

        document.addEventListener('pointerup', endAllRangeDrags);
    }

    function buildHomeFilterParams() {
        const params = new URLSearchParams();

        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            const valuesContainer = dropdown.querySelector('.dropdown-values-container');
            if (valuesContainer) {
                valuesContainer.querySelectorAll('input[type="hidden"]').forEach(input => {
                    if (input.name && input.value) params.append(input.name, input.value);
                });
            }
        });

        const priceFrom = document.getElementById('price-from-dropdown')?.value;
        const priceTo = document.getElementById('price-to-dropdown')?.value;
        const kmFrom = document.getElementById('km-driven-from')?.value;
        const kmTo = document.getElementById('km-driven-to')?.value;
        const yearFrom = document.getElementById('model-year-from')?.value;
        const yearTo = document.getElementById('model-year-to')?.value;
        const searchQuery = document.getElementById('home-search-input')?.value?.trim();

        if (priceFrom) params.append('price_from', priceFrom);
        if (priceTo) params.append('price_to', priceTo);
        if (kmFrom) params.append('km_driven_from', kmFrom);
        if (kmTo) params.append('km_driven_to', kmTo);
        if (yearFrom) params.append('model_year_from', yearFrom);
        if (yearTo) params.append('model_year_to', yearTo);
        if (searchQuery) params.append('search', searchQuery);

        const listingTypeContainer = document.getElementById('home-listing-type-inputs');
        if (listingTypeContainer) {
            listingTypeContainer.querySelectorAll('input[type="hidden"]').forEach(input => {
                if (input.name && input.value) params.append(input.name, input.value);
            });
        }

        return params;
    }

    let homeCountDebounceTimer = null;
    let homeCountRequestId = 0;

    function updateHomeResultsCountDisplay(count) {
        const formatted = formatHomeNumber(count);
        const badge = document.getElementById('home-results-badge');
        if (badge) {
            badge.textContent = formatted;
        }
        const button = document.getElementById('home-show-results-btn');
        if (button) {
            button.textContent = HOME_SEE_RESULTS_LABEL.replace(':count', formatted);
        }
    }

    function scheduleHomeResultsCountUpdate() {
        clearTimeout(homeCountDebounceTimer);
        homeCountDebounceTimer = setTimeout(fetchHomeResultsCount, 300);
    }

    async function fetchHomeResultsCount() {
        const params = buildHomeFilterParams();
        const requestId = ++homeCountRequestId;

        try {
            const response = await fetch('/api/v1/vehicles/count?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) return;

            const payload = await response.json();
            if (requestId !== homeCountRequestId) return;

            const count = Number(payload?.data?.count ?? 0);
            updateHomeResultsCountDisplay(Number.isFinite(count) ? count : 0);
        } catch (error) {
            console.debug('Home results count failed:', error);
        }
    }
    
    // Form submission: AI text → Find My Perfect Car; otherwise vehicle listing filters
    const filterForm = document.getElementById('filter-form');
    const publicAiEnabled = @json(!empty($publicAiEnabled));
    const vehiclesUrl = @json(route('vehicles'));
    const findPerfectCarUrl = @json(route('find-perfect-car'));
    if (filterForm) {
        filterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const searchQuery = document.getElementById('home-search-input')?.value?.trim() || '';
            const facetParams = buildHomeFilterParams();

            if (typeof window.bilskyenTrackMetaSearch === 'function') {
                window.bilskyenTrackMetaSearch(searchQuery || facetParams.toString());
            }

            if (publicAiEnabled && searchQuery) {
                const advisorParams = new URLSearchParams({ q: searchQuery });
                window.location.href = findPerfectCarUrl + '?' + advisorParams.toString();
                return;
            }

            window.location.href = vehiclesUrl + (facetParams.toString() ? '?' + facetParams.toString() : '');
        });
    }

    // AI examples + autocomplete (only when helpers are loaded)
    if (publicAiEnabled && window.BilskyenAiSearch) {
        window.BilskyenAiSearch.renderExampleChips(document.getElementById('home-ai-examples'));
        window.BilskyenAiSearch.bindAutocomplete(
            document.getElementById('home-search-input'),
            document.getElementById('home-ai-suggest'),
            {
                onExample: function (label) {
                    document.getElementById('home-search-input').value = label;
                },
                onBrand: function (item) {
                    document.getElementById('home-search-input').value = item.name || '';
                },
                onModel: function (item) {
                    document.getElementById('home-search-input').value = item.name || '';
                },
            }
        );
        document.getElementById('home-ai-examples')?.addEventListener('ai-example-selected', function (ev) {
            const q = ev.detail?.query;
            if (q) document.getElementById('home-search-input').value = q;
        });
    }
    
    // Reset filters function
    function resetAllFilters() {
        const defaultTexts = DEFAULT_DROPDOWN_LABELS;
        
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            const dropdownType = dropdown.getAttribute('data-dropdown');
            const selectedText = dropdown.querySelector('.dropdown-selected');
            const valuesContainer = dropdown.querySelector('.dropdown-values-container');
            const options = dropdown.querySelectorAll('.dropdown-option');
            
            if (valuesContainer) {
                valuesContainer.innerHTML = '';
            }
            options.forEach(opt => opt.classList.remove('selected'));
            if (selectedText && defaultTexts[dropdownType]) {
                selectedText.textContent = defaultTexts[dropdownType];
            }
        });

        closeAllDropdownMenus();
        
        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (modelDropdown) {
            const modelSearchInput = modelDropdown.querySelector('.dropdown-search');
            if (modelSearchInput) modelSearchInput.value = '';
            filterModelsByBrand([]);
        }

        const searchInput = document.getElementById('home-search-input');
        if (searchInput) searchInput.value = '';

        document.querySelectorAll('.home-filter-tab').forEach(tab => {
            tab.classList.toggle('active', tab.getAttribute('data-listing-type') === '');
        });
        syncListingTypeInput('');

        const priceFromInput = document.getElementById('price-from-dropdown');
        const priceToInput = document.getElementById('price-to-dropdown');
        const priceMinSlider = document.getElementById('price-slider-min-dropdown');
        const priceMaxSlider = document.getElementById('price-slider-max-dropdown');
        
        if (priceFromInput) priceFromInput.value = '';
        if (priceToInput) priceToInput.value = '';
        if (priceMinSlider) priceMinSlider.value = '0';
        if (priceMaxSlider) priceMaxSlider.value = String(HOME_PRICE_MAX);
        
        const kmFromInput = document.getElementById('km-driven-from');
        const kmToInput = document.getElementById('km-driven-to');
        const kmMinSlider = document.getElementById('km-driven-slider-min');
        const kmMaxSlider = document.getElementById('km-driven-slider-max');
        
        if (kmFromInput) kmFromInput.value = '';
        if (kmToInput) kmToInput.value = '';
        if (kmMinSlider) kmMinSlider.value = '0';
        if (kmMaxSlider) kmMaxSlider.value = String(HOME_KM_MAX);

        const yearFromInput = document.getElementById('model-year-from');
        const yearToInput = document.getElementById('model-year-to');
        const yearMinSlider = document.getElementById('model-year-slider-min');
        const yearMaxSlider = document.getElementById('model-year-slider-max');
        if (yearFromInput) yearFromInput.value = '';
        if (yearToInput) yearToInput.value = '';
        if (yearMinSlider) yearMinSlider.value = String(HOME_YEAR_MIN);
        if (yearMaxSlider) yearMaxSlider.value = String(HOME_YEAR_MAX);

        [priceMinSlider, priceMaxSlider, kmMinSlider, kmMaxSlider, yearMinSlider, yearMaxSlider].forEach(slider => {
            if (slider) slider.dispatchEvent(new Event('input'));
        });
        updateHomeFilterChips();
    }
    
    // Reset filters button handler
    const resetFiltersButton = document.getElementById('reset-filters-button');
    if (resetFiltersButton) {
        resetFiltersButton.addEventListener('click', resetAllFilters);
    }
    
    // Initialize everything
    initSearchableDropdowns();
    initDropdownRangeSliders();
    initListingTypeTabs();
    initFilterPanelToggle();
    updateHomeFilterChips();

    const homeSearchInput = document.getElementById('home-search-input');
    if (homeSearchInput) {
        homeSearchInput.addEventListener('input', scheduleHomeResultsCountUpdate);
    }
    
    const brandDropdown = document.querySelector('[data-dropdown="brand"]');
    const initialBrandIds = brandDropdown ? getMultiSelectValues(brandDropdown) : [];
    filterModelsByBrand(initialBrandIds);
    </script>
    
    <!-- Featured Vehicles Navigation Script -->
    <script>
        (function() {
            const scrollContainer = document.getElementById('featured-vehicles-scroll');
            const prevBtn = document.getElementById('featured-vehicles-prev');
            const nextBtn = document.getElementById('featured-vehicles-next');
            
            if (!scrollContainer || !prevBtn || !nextBtn) return;
            
            // Calculate scroll amount (width of one card + gap)
            const getScrollAmount = () => {
                const firstCard = scrollContainer.querySelector('.featured-vehicle-card');
                if (!firstCard) return scrollContainer.clientWidth;
                const cardWidth = firstCard.offsetWidth;
                const track = firstCard.parentElement;
                const gap = track ? (parseFloat(getComputedStyle(track).columnGap) || 12) : 12;
                return cardWidth + gap;
            };
            
            // Update button states based on scroll position
            const updateButtonStates = () => {
                const { scrollLeft, scrollWidth, clientWidth } = scrollContainer;
                const isAtStart = scrollLeft <= 1;
                const isAtEnd = scrollLeft >= scrollWidth - clientWidth - 1;
                
                prevBtn.disabled = isAtStart;
                nextBtn.disabled = isAtEnd;
            };
            
            // Scroll functions
            const scrollLeft = () => {
                const scrollAmount = getScrollAmount();
                scrollContainer.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            };
            
            const scrollRight = () => {
                const scrollAmount = getScrollAmount();
                scrollContainer.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            };
            
            // Event listeners
            prevBtn.addEventListener('click', scrollLeft);
            nextBtn.addEventListener('click', scrollRight);
            
            // Update button states on scroll
            scrollContainer.addEventListener('scroll', updateButtonStates);
            
            // Update button states on resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(updateButtonStates, 100);
            });
            
            // Initial state update
            updateButtonStates();
            
            // Update after images load (in case cards resize)
            const images = scrollContainer.querySelectorAll('img');
            let loadedImages = 0;
            images.forEach(img => {
                if (img.complete) {
                    loadedImages++;
                } else {
                    img.addEventListener('load', () => {
                        loadedImages++;
                        if (loadedImages === images.length) {
                            setTimeout(updateButtonStates, 100);
                        }
                    });
                }
            });
            if (loadedImages === images.length) {
                setTimeout(updateButtonStates, 100);
            }
        })();
    </script>

@push('scripts')
<script>
    (function() {
        const favoritesUrl = @json(route('favorites'));
        const favoritesCheckBatchUrl = @json(route('favorites.check.batch'));
        const favoritesDestroyUrl = (vehicleId) => @json(rtrim(route('favorites.destroy', ['vehicleId' => '__ID__']), '/')).replace('__ID__', encodeURIComponent(vehicleId));

        // Get access token from cookie helper
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        
        // Check if user is authenticated
        function isUserAuthenticated() {
            return getCookie('bilskyen_auth') !== null;
        }
        
        // Load favorite status for all vehicles in batch
        async function checkFavoritesBatch() {
            const heartIcons = document.querySelectorAll('.heart-icon');
            if (heartIcons.length === 0) return;

            // Collect all vehicle IDs
            const vehicleIds = [];
            const iconMap = new Map(); // Map vehicle ID to icon element
            
            heartIcons.forEach(icon => {
                const vehicleId = icon.getAttribute('data-vehicle-id');
                if (vehicleId) {
                    vehicleIds.push(parseInt(vehicleId));
                    iconMap.set(parseInt(vehicleId), icon);
                }
            });

            if (vehicleIds.length === 0) return;

            try {
                // Make single batch API call
                const response = await fetch(favoritesCheckBatchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ vehicle_ids: vehicleIds })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success' && data.data) {
                        // Update icons based on batch response
                        Object.keys(data.data).forEach(vehicleIdStr => {
                            const vehicleId = parseInt(vehicleIdStr);
                            const isFavorited = data.data[vehicleId];
                            const icon = iconMap.get(vehicleId);
                            
                            if (icon && isFavorited) {
                                icon.classList.add('filled');
                                icon.classList.remove('text-gray-700');
                                icon.classList.add('text-red-500');
                                const path = icon.querySelector('path');
                                if (path) {
                                    path.setAttribute('fill', 'currentColor');
                                }
                            }
                        });
                    }
                }
            } catch (error) {
                // Silently fail if auth check fails or user is not authenticated
                console.debug('Favorite check failed (user may not be authenticated):', error);
            }
        }
        
        // Toggle favorite function
        window.toggleFavorite = async function(vehicleId, event) {
            // Prevent any default behavior and stop propagation
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
            }
            
            // Check if user is authenticated
            if (!isUserAuthenticated()) {
                // Open login dialog with callback to favorite after login
                if (window.openLoginDialog) {
                    window.openLoginDialog(() => {
                        // After successful login, automatically favorite the vehicle
                        window.toggleFavorite(vehicleId, event);
                    });
                }
                return false;
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            try {
                const heartIcon = document.querySelector(`.heart-icon[data-vehicle-id="${vehicleId}"]`);
                if (!heartIcon) {
                    console.error('Heart icon not found for vehicle:', vehicleId);
                    return false;
                }
                
                const path = heartIcon.querySelector('path');
                const isFavorited = heartIcon.classList.contains('filled') || path?.getAttribute('fill') === 'currentColor';

                if (isFavorited) {
                    // Remove from favorites
                    const response = await fetch(favoritesDestroyUrl(vehicleId), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (response.ok) {
                        const data = await response.json().catch(() => ({}));
                        heartIcon.classList.remove('filled');
                        heartIcon.classList.remove('text-red-500');
                        // Restore original color based on dealer status
                        const dealerId = heartIcon.getAttribute('data-dealer-id');
                        if (dealerId && dealerId !== '') {
                            heartIcon.classList.add('text-blue-600');
                            heartIcon.classList.remove('text-orange-600');
                        } else {
                            heartIcon.classList.add('text-orange-600');
                            heartIcon.classList.remove('text-blue-600');
                        }
                        if (path) path.setAttribute('fill', 'none');
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.messages.removed_from_favorites') }}', 'success');
                        }
                    } else {
                        if (response.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('{{ __('messages.errors.please_login') }}', 'error');
                            }
                            // Open login dialog instead of redirecting
                            if (window.openLoginDialog) {
                                window.openLoginDialog(() => {
                                    window.toggleFavorite(vehicleId, event);
                                });
                            } else {
                                setTimeout(() => {
                                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                                }, 1500);
                            }
                            return false;
                        }
                        const data = await response.json().catch(() => ({}));
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.errors.failed_to_remove_favorites') }}', 'error');
                        }
                    }
                } else {
                    // Add to favorites
                    const response = await fetch(favoritesUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ vehicle_id: vehicleId }),
                        credentials: 'same-origin'
                    });

                    if (response.ok) {
                        const data = await response.json().catch(() => ({}));
                        heartIcon.classList.add('filled');
                        heartIcon.classList.remove('text-blue-600', 'text-orange-600');
                        heartIcon.classList.add('text-red-500');
                        if (path) path.setAttribute('fill', 'currentColor');
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.messages.saved_to_favorites') }}', 'success');
                        }
                    } else {
                        if (response.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('{{ __('messages.errors.please_login_to_save') }}', 'error');
                            }
                            // Open login dialog instead of redirecting
                            if (window.openLoginDialog) {
                                window.openLoginDialog(() => {
                                    window.toggleFavorite(vehicleId, event);
                                });
                            } else {
                                setTimeout(() => {
                                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                                }, 1500);
                            }
                            return false;
                        }
                        const data = await response.json().catch(() => ({}));
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.errors.failed_to_save_favorites') }}', 'error');
                        }
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                if (window.showSnackbar) {
                    window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
                }
            }
            
            return false;
        };
        
        // openEnquiryDialog is available globally from layouts/app.blade.php
        // No authentication check needed - guests can submit enquiries
        
        // Load favorite status on page load
        (async function() {
            await checkFavoritesBatch();
        })();

        // Mobile testimonials carousel
        const testimonialCards = document.querySelectorAll('.testimonial-card');
        const testimonialPrev = document.getElementById('testimonial-prev');
        const testimonialNext = document.getElementById('testimonial-next');
        let activeTestimonialIndex = 0;

        const showTestimonial = (index) => {
            if (!testimonialCards.length) return;
            activeTestimonialIndex = (index + testimonialCards.length) % testimonialCards.length;
            testimonialCards.forEach((card, cardIndex) => {
                if (window.matchMedia('(max-width: 767px)').matches) {
                    card.classList.toggle('hidden', cardIndex !== activeTestimonialIndex);
                } else {
                    card.classList.remove('hidden');
                    card.classList.add('md:block');
                }
            });
        };

        if (testimonialPrev) {
            testimonialPrev.addEventListener('click', () => showTestimonial(activeTestimonialIndex - 1));
        }

        if (testimonialNext) {
            testimonialNext.addEventListener('click', () => showTestimonial(activeTestimonialIndex + 1));
        }

        window.addEventListener('resize', () => showTestimonial(activeTestimonialIndex));
        showTestimonial(0);
    })();
</script>
@endpush
@endsection

