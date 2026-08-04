@extends('layouts.app')

@php
    $honeypotField = config('security.honeypot.field', 'website');
@endphp

@section('content')
<style>
    .advisor-page {
        --advisor-navy: #03418b;
        --advisor-navy-soft: rgba(3, 65, 139, 0.08);
        --advisor-border: #e4e4e7;
        --advisor-muted: #71717a;
        --advisor-surface: #fafafa;
    }
    .advisor-page .advisor-hero {
        border-bottom: 1px solid var(--advisor-border);
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    }
    .advisor-page .advisor-input-wrap {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        border: 1px solid var(--advisor-border);
        border-radius: 0.75rem;
        background: #fff;
        padding: 0.75rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-input-wrap {
            flex-direction: row;
            align-items: flex-end;
            padding: 0.5rem 0.5rem 0.5rem 1rem;
        }
    }
    .advisor-page .advisor-input-wrap textarea {
        flex: 1;
        min-height: 5.5rem;
        border: 0;
        background: transparent;
        resize: vertical;
        padding: 0.5rem 0;
        font-size: 0.9375rem;
        line-height: 1.5;
        color: #18181b;
        outline: none;
        box-shadow: none;
    }
    .advisor-page .advisor-input-wrap textarea:focus {
        outline: none;
        box-shadow: none;
    }
    .advisor-page .advisor-submit {
        flex-shrink: 0;
        height: 2.75rem;
        padding: 0 1.25rem;
        border-radius: 0.5rem;
        background: var(--advisor-navy);
        color: #fff;
        font-size: 0.875rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .advisor-page .advisor-submit:hover { background: #023a7a; }
    .advisor-page .advisor-submit:disabled { opacity: 0.65; cursor: not-allowed; }
    .advisor-page .advisor-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--advisor-border);
        border-radius: 999px;
        background: #fff;
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        color: #3f3f46;
        transition: background 0.15s, border-color 0.15s;
    }
    .advisor-page .advisor-chip:hover {
        background: var(--advisor-surface);
        border-color: #d4d4d8;
    }
    .advisor-page .advisor-understood {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 1rem;
    }
    .advisor-page .advisor-understood-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: var(--advisor-navy-soft);
        color: var(--advisor-navy);
        padding: 0.3rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .advisor-page .advisor-summary-bar {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin: 0 -1rem 1.25rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--advisor-border);
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(8px);
    }
    @media (min-width: 768px) {
        .advisor-page .advisor-summary-bar {
            margin-left: 0;
            margin-right: 0;
            border: 1px solid var(--advisor-border);
            border-radius: 0.75rem;
        }
    }
    .advisor-page .advisor-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .advisor-page .advisor-row {
        display: grid;
        gap: 0.85rem;
        padding: 0.9rem;
        border: 1px solid var(--advisor-border);
        border-radius: 0.85rem;
        background: #fff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        animation: advisor-row-in 0.45s ease both;
    }
    .advisor-page .advisor-row:hover {
        border-color: #cbd5e1;
        box-shadow: 0 8px 24px rgba(3, 65, 139, 0.06);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-row {
            grid-template-columns: 152px 1fr auto;
            align-items: stretch;
            gap: 1.15rem;
            padding: 1rem;
        }
    }
    .advisor-page .advisor-row.is-best {
        border-color: rgba(3, 65, 139, 0.28);
        background: linear-gradient(135deg, rgba(3, 65, 139, 0.05), #fff 55%);
        box-shadow: 0 1px 0 rgba(3, 65, 139, 0.04);
    }
    .advisor-page .advisor-row-photo {
        position: relative;
        display: block;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        border-radius: 0.6rem;
        background: var(--advisor-surface);
        border: 1px solid var(--advisor-border);
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-row-photo {
            width: 152px;
            height: 100%;
            min-height: 114px;
            aspect-ratio: auto;
        }
    }
    .advisor-page .advisor-row-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .advisor-page .advisor-row:hover .advisor-row-photo img {
        transform: scale(1.04);
    }
    .advisor-page .advisor-rank {
        position: absolute;
        top: 0.45rem;
        left: 0.45rem;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.6rem;
        height: 1.6rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        backdrop-filter: blur(4px);
    }
    .advisor-page .advisor-row.is-best .advisor-rank {
        background: var(--advisor-navy);
    }
    .advisor-page .advisor-match {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.35rem;
        min-width: 4.75rem;
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-match {
            align-items: center;
            justify-content: center;
            padding-left: 0.25rem;
        }
    }
    .advisor-page .advisor-ring {
        --pct: 0;
        position: relative;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 999px;
        background: conic-gradient(var(--advisor-navy) calc(var(--pct) * 1%), #e4e4e7 0);
        display: grid;
        place-items: center;
    }
    .advisor-page .advisor-ring::before {
        content: '';
        position: absolute;
        inset: 0.35rem;
        border-radius: 999px;
        background: #fff;
    }
    .advisor-page .advisor-ring-value {
        position: relative;
        z-index: 1;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--advisor-navy);
        line-height: 1;
    }
    .advisor-page .advisor-ring-label {
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--advisor-muted);
    }
    .advisor-page .advisor-best-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: var(--advisor-navy);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        padding: 0.2rem 0.5rem;
        margin-bottom: 0.4rem;
    }
    .advisor-page .advisor-teaser {
        margin-top: 0.55rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: #3f3f46;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .advisor-page .advisor-meta-line {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.25rem 0;
        margin-top: 0.4rem;
        font-size: 0.8125rem;
        color: var(--advisor-muted);
    }
    .advisor-page .advisor-meta-line > * + *::before {
        content: '·';
        margin: 0 0.45rem;
        color: #a1a1aa;
    }
    .advisor-page .advisor-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.85rem;
    }
    @keyframes advisor-row-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .advisor-page .advisor-btn-primary {
        display: inline-flex;
        align-items: center;
        height: 2.25rem;
        padding: 0 0.9rem;
        border-radius: 0.45rem;
        background: var(--advisor-navy);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .advisor-page .advisor-btn-primary:hover { background: #023a7a; }
    .advisor-page .advisor-btn-ghost {
        display: inline-flex;
        align-items: center;
        height: 2.25rem;
        padding: 0 0.85rem;
        border-radius: 0.45rem;
        border: 1px solid var(--advisor-border);
        background: #fff;
        font-size: 0.75rem;
        font-weight: 500;
        color: #3f3f46;
    }
    .advisor-page .advisor-btn-ghost:hover { background: var(--advisor-surface); }
    .advisor-page .advisor-why-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.75rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--advisor-navy);
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .advisor-page .advisor-why-btn:hover { color: #023a7a; }
    .advisor-why-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: 0;
    }
    @media (min-width: 640px) {
        .advisor-why-modal {
            align-items: center;
            padding: 1.25rem;
        }
    }
    .advisor-why-modal[hidden] { display: none !important; }
    .advisor-why-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(3px);
    }
    .advisor-why-modal__dialog {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 34rem;
        max-height: min(92vh, 44rem);
        background: #fff;
        border-radius: 1rem 1rem 0 0;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        animation: advisor-modal-in 0.28s ease both;
    }
    @media (min-width: 640px) {
        .advisor-why-modal__dialog {
            border-radius: 1rem;
        }
    }
    @keyframes advisor-modal-in {
        from { opacity: 0; transform: translateY(18px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .advisor-why-modal__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1rem 0.75rem;
        border-bottom: 1px solid #f4f4f5;
        flex-shrink: 0;
    }
    .advisor-why-modal__kicker {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--advisor-navy, #03418b);
    }
    .advisor-why-modal__title {
        margin-top: 0.15rem;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.3;
        color: #18181b;
    }
    .advisor-why-modal__close {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 999px;
        border: 1px solid #e4e4e7;
        background: #fff;
        color: #52525b;
        font-size: 1.25rem;
        line-height: 1;
        cursor: pointer;
    }
    .advisor-why-modal__close:hover { background: #fafafa; }
    .advisor-why-modal__body {
        overflow-y: auto;
        padding: 1rem;
        flex: 1;
        -webkit-overflow-scrolling: touch;
    }
    .advisor-why-modal__hero {
        display: grid;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }
    @media (min-width: 480px) {
        .advisor-why-modal__hero {
            grid-template-columns: 7.5rem 1fr auto;
            align-items: center;
        }
    }
    .advisor-why-modal__photo {
        aspect-ratio: 4 / 3;
        border-radius: 0.65rem;
        overflow: hidden;
        border: 1px solid #e4e4e7;
        background: #fafafa;
    }
    .advisor-why-modal__photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .advisor-why-modal__meta {
        font-size: 0.8125rem;
        color: #71717a;
        margin-top: 0.35rem;
        line-height: 1.45;
    }
    .advisor-why-modal__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.55rem;
    }
    .advisor-why-modal__chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: rgba(3, 65, 139, 0.08);
        color: #03418b;
        font-size: 0.7rem;
        font-weight: 500;
        padding: 0.25rem 0.6rem;
    }
    .advisor-why-modal__match {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    @media (min-width: 480px) {
        .advisor-why-modal__match { align-items: center; }
    }
    .advisor-why-modal__section {
        margin-top: 0.85rem;
        padding: 0.85rem 0.9rem;
        border-radius: 0.75rem;
        border: 1px solid #f4f4f5;
        background: #fafafa;
    }
    .advisor-why-modal__section--warn {
        border-color: #fde68a;
        background: #fffbeb;
    }
    .advisor-why-modal__section-title {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #71717a;
        margin-bottom: 0.45rem;
    }
    .advisor-why-modal__section--warn .advisor-why-modal__section-title {
        color: #a16207;
    }
    .advisor-why-modal__section p {
        font-size: 0.875rem;
        line-height: 1.55;
        color: #3f3f46;
        margin: 0;
    }
    .advisor-why-modal__section ul {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .advisor-why-modal__section li {
        position: relative;
        padding-left: 1.15rem;
        font-size: 0.875rem;
        line-height: 1.45;
        color: #3f3f46;
    }
    .advisor-why-modal__section li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.45rem;
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 999px;
        background: #03418b;
    }
    .advisor-why-modal__section--warn li::before {
        background: #d97706;
    }
    .advisor-why-modal__footer {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.85rem 1rem 1rem;
        border-top: 1px solid #f4f4f5;
        background: #fff;
        flex-shrink: 0;
    }
    .advisor-why-modal__footer .advisor-btn-primary,
    .advisor-why-modal__footer .advisor-btn-ghost {
        flex: 1 1 auto;
        justify-content: center;
        min-width: 8rem;
        height: 2.5rem;
        font-size: 0.8125rem;
    }
    .advisor-page .advisor-loading {
        margin-top: 1.75rem;
        border: 1px solid var(--advisor-border);
        border-radius: 1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 42%);
        padding: 1.15rem 1rem 1rem;
        overflow: hidden;
    }
    .advisor-page .advisor-loading-head {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }
    .advisor-page .advisor-spinner {
        position: relative;
        width: 2.35rem;
        height: 2.35rem;
        flex-shrink: 0;
    }
    .advisor-page .advisor-spinner::before,
    .advisor-page .advisor-spinner::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 999px;
        border: 2px solid transparent;
        border-top-color: var(--advisor-navy);
        animation: advisor-spin 0.9s linear infinite;
    }
    .advisor-page .advisor-spinner::after {
        inset: 0.35rem;
        border-top-color: rgba(3, 65, 139, 0.35);
        animation-duration: 1.35s;
        animation-direction: reverse;
    }
    .advisor-page .advisor-loading-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #18181b;
        line-height: 1.3;
    }
    .advisor-page .advisor-loading-step {
        margin-top: 0.2rem;
        font-size: 0.8125rem;
        color: var(--advisor-muted);
        min-height: 1.25rem;
        transition: opacity 0.25s ease;
    }
    .advisor-page .advisor-loading-step.is-swap {
        opacity: 0;
    }
    .advisor-page .advisor-progress {
        height: 0.35rem;
        border-radius: 999px;
        background: #e4e4e7;
        overflow: hidden;
        margin-bottom: 0.85rem;
    }
    .advisor-page .advisor-progress-fill {
        height: 100%;
        width: 8%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--advisor-navy), #2563eb);
        transition: width 0.7s cubic-bezier(0.22, 1, 0.36, 1);
        position: relative;
    }
    .advisor-page .advisor-progress-fill::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.45), transparent);
        animation: advisor-sheen 1.4s ease-in-out infinite;
    }
    .advisor-page .advisor-loading-stages {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.35rem;
        margin-bottom: 1.1rem;
    }
    .advisor-page .advisor-stage {
        height: 0.28rem;
        border-radius: 999px;
        background: #e4e4e7;
        overflow: hidden;
    }
    .advisor-page .advisor-stage > span {
        display: block;
        height: 100%;
        width: 0;
        background: var(--advisor-navy);
        border-radius: inherit;
        transition: width 0.55s ease;
    }
    .advisor-page .advisor-stage.is-done > span { width: 100%; }
    .advisor-page .advisor-stage.is-active > span {
        width: 100%;
        animation: advisor-stage-pulse 1.1s ease-in-out infinite;
    }
    .advisor-page .advisor-loading-tip {
        margin-top: 0.85rem;
        padding: 0.7rem 0.8rem;
        border-radius: 0.65rem;
        background: var(--advisor-navy-soft);
        color: var(--advisor-navy);
        font-size: 0.75rem;
        line-height: 1.45;
    }
    .advisor-page .advisor-skeleton {
        display: grid;
        gap: 0.85rem;
        padding: 0.9rem;
        border: 1px solid var(--advisor-border);
        border-radius: 0.85rem;
        background: #fff;
        margin-bottom: 0.75rem;
    }
    @media (min-width: 640px) {
        .advisor-page .advisor-skeleton {
            grid-template-columns: 152px 1fr 4.75rem;
            align-items: center;
            gap: 1.15rem;
            padding: 1rem;
        }
    }
    .advisor-page .advisor-skel {
        border-radius: 0.45rem;
        background: linear-gradient(90deg, #f4f4f5 15%, #e8e8ec 45%, #f4f4f5 75%);
        background-size: 220% 100%;
        animation: advisor-shimmer 1.35s ease-in-out infinite;
    }
    .advisor-page .advisor-skel-ring {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 999px;
        margin-inline: auto;
    }
    .advisor-page .advisor-skeleton:nth-child(2) .advisor-skel { animation-delay: 0.12s; }
    .advisor-page .advisor-skeleton:nth-child(3) .advisor-skel { animation-delay: 0.24s; }
    .advisor-page .advisor-skeleton:nth-child(4) .advisor-skel { animation-delay: 0.36s; }
    @keyframes advisor-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    @keyframes advisor-spin {
        to { transform: rotate(360deg); }
    }
    @keyframes advisor-sheen {
        0% { transform: translateX(-120%); }
        100% { transform: translateX(120%); }
    }
    @keyframes advisor-stage-pulse {
        0%, 100% { opacity: 0.55; }
        50% { opacity: 1; }
    }
    .advisor-page .advisor-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        border: 1px dashed var(--advisor-border);
        border-radius: 0.75rem;
        background: var(--advisor-surface);
    }
    .advisor-page .advisor-examples-panel[hidden],
    .advisor-page .advisor-refine[hidden],
    .advisor-page .advisor-results[hidden],
    .advisor-page .advisor-loading[hidden] {
        display: none !important;
    }
</style>

<div class="advisor-page flex min-h-screen flex-col" id="car-advisor-page"
     data-locale="{{ app()->getLocale() }}"
     data-api-url="{{ url('/api/v1/ai/car-advisor') }}"
     data-save-url="{{ url('/saved-searches') }}"
     data-honeypot="{{ $honeypotField }}"
     data-ai-enabled="{{ !empty($publicAiEnabled) ? '1' : '0' }}"
     data-vehicles-url="{{ route('vehicles') }}"
     data-login-url="{{ route('login') }}">

    <section class="advisor-hero py-8 md:py-10" aria-labelledby="advisor-heading">
        <div class="container mx-auto max-w-3xl px-4 md:px-6">
            <h1 id="advisor-heading" class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                {{ __('messages.pages.find_perfect_car.title') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground md:text-base">
                {{ __('messages.pages.find_perfect_car.subtitle') }}
            </p>
        </div>
    </section>

    <section class="flex-1 pb-12 pt-6 md:pb-16 md:pt-8">
        <div class="container mx-auto max-w-3xl px-4 md:px-6">
            @if(empty($publicAiEnabled))
                <div class="rounded-lg border border-border bg-card p-6 text-center">
                    <p class="text-muted-foreground">{{ __('messages.pages.find_perfect_car.disabled') }}</p>
                    <a href="{{ route('vehicles') }}" class="advisor-btn-primary mt-4">
                        {{ __('messages.pages.home.browse_vehicles') }}
                    </a>
                </div>
            @else
                <form id="advisor-form" autocomplete="off">
                    <input type="text" name="{{ $honeypotField }}" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                    <label for="advisor-input" class="sr-only">{{ __('messages.pages.find_perfect_car.placeholder') }}</label>
                    <div class="advisor-input-wrap">
                        <textarea
                            id="advisor-input"
                            rows="3"
                            maxlength="2000"
                            required
                            placeholder="{{ __('messages.pages.find_perfect_car.placeholder') }}"
                        >{{ $advisorPrefill ?? '' }}</textarea>
                        <button type="submit" id="advisor-submit" class="advisor-submit">
                            {{ __('messages.pages.find_perfect_car.submit') }}
                        </button>
                    </div>
                    <p id="advisor-status" class="mt-2 hidden text-sm text-muted-foreground" aria-live="polite"></p>
                    <p id="advisor-error" class="mt-2 hidden text-sm text-destructive" role="alert"></p>
                </form>

                <div id="advisor-understood" class="advisor-understood hidden" aria-live="polite"></div>

                @if(!empty($advisorExamples))
                <div class="mt-4">
                    <button type="button" id="advisor-examples-toggle" class="text-xs font-medium text-primary hover:underline" aria-expanded="false" aria-controls="advisor-examples-panel">
                        {{ __('messages.pages.find_perfect_car.examples_label') }}
                    </button>
                    <div id="advisor-examples-panel" class="advisor-examples-panel mt-2 hidden" hidden>
                        <div class="flex flex-wrap gap-2" id="advisor-examples">
                            @foreach($advisorExamples as $example)
                                <button type="button" class="advisor-example-chip advisor-chip" data-example="{{ $example }}">
                                    {{ \Illuminate\Support\Str::limit($example, 64) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div id="advisor-loading" class="advisor-loading mt-8 hidden" hidden aria-live="polite" aria-busy="false">
                    <div class="advisor-loading-head">
                        <div class="advisor-spinner" aria-hidden="true"></div>
                        <div class="min-w-0 flex-1">
                            <p class="advisor-loading-title">{{ __('messages.pages.find_perfect_car.loading_title') }}</p>
                            <p id="advisor-loading-step" class="advisor-loading-step">{{ __('messages.pages.find_perfect_car.loading_step_1') }}</p>
                        </div>
                    </div>
                    <div class="advisor-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="8" id="advisor-progress">
                        <div class="advisor-progress-fill" id="advisor-progress-fill" style="width:8%"></div>
                    </div>
                    <div class="advisor-loading-stages" aria-hidden="true">
                        <div class="advisor-stage is-active" data-stage="0"><span></span></div>
                        <div class="advisor-stage" data-stage="1"><span></span></div>
                        <div class="advisor-stage" data-stage="2"><span></span></div>
                        <div class="advisor-stage" data-stage="3"><span></span></div>
                    </div>
                    <div id="advisor-skeleton-list">
                        <div class="advisor-skeleton">
                            <div class="advisor-skel" style="aspect-ratio:4/3;border-radius:0.6rem"></div>
                            <div class="space-y-2 py-1">
                                <div class="advisor-skel" style="height:0.7rem;width:4.5rem;border-radius:999px"></div>
                                <div class="advisor-skel" style="height:1rem;width:78%"></div>
                                <div class="advisor-skel" style="height:0.75rem;width:58%"></div>
                                <div class="advisor-skel" style="height:0.75rem;width:88%"></div>
                                <div class="flex gap-2 pt-1">
                                    <div class="advisor-skel" style="height:2.1rem;width:5.5rem"></div>
                                    <div class="advisor-skel" style="height:2.1rem;width:5.5rem"></div>
                                </div>
                            </div>
                            <div class="advisor-skel advisor-skel-ring"></div>
                        </div>
                        <div class="advisor-skeleton">
                            <div class="advisor-skel" style="aspect-ratio:4/3;border-radius:0.6rem"></div>
                            <div class="space-y-2 py-1">
                                <div class="advisor-skel" style="height:1rem;width:70%"></div>
                                <div class="advisor-skel" style="height:0.75rem;width:52%"></div>
                                <div class="advisor-skel" style="height:0.75rem;width:80%"></div>
                            </div>
                            <div class="advisor-skel advisor-skel-ring"></div>
                        </div>
                        <div class="advisor-skeleton">
                            <div class="advisor-skel" style="aspect-ratio:4/3;border-radius:0.6rem"></div>
                            <div class="space-y-2 py-1">
                                <div class="advisor-skel" style="height:1rem;width:64%"></div>
                                <div class="advisor-skel" style="height:0.75rem;width:48%"></div>
                                <div class="advisor-skel" style="height:0.75rem;width:72%"></div>
                            </div>
                            <div class="advisor-skel advisor-skel-ring"></div>
                        </div>
                    </div>
                    <p class="advisor-loading-tip">{{ __('messages.pages.find_perfect_car.loading_tip') }}</p>
                </div>

                <div id="advisor-results" class="advisor-results mt-8 hidden" hidden>
                    <div class="advisor-summary-bar">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold tracking-tight md:text-lg">{{ __('messages.pages.find_perfect_car.results_heading') }}</h2>
                            <p id="advisor-summary" class="mt-0.5 text-sm text-muted-foreground"></p>
                            <p id="advisor-meta" class="mt-0.5 text-xs text-muted-foreground"></p>
                            <p id="advisor-relaxed" class="mt-1 hidden text-xs text-amber-700">{{ __('messages.pages.find_perfect_car.relaxed_note') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a id="advisor-browse-link" href="{{ route('vehicles') }}" class="advisor-btn-ghost">
                                {{ __('messages.pages.find_perfect_car.browse_all') }}
                            </a>
                            <button type="button" id="advisor-save-search" class="advisor-btn-ghost">
                                {{ __('messages.pages.find_perfect_car.save_search') }}
                            </button>
                        </div>
                    </div>
                    <p id="advisor-save-msg" class="mb-3 hidden text-xs text-muted-foreground" role="status"></p>
                    <div id="advisor-cards"></div>
                </div>

                <div id="advisor-refine" class="advisor-refine mt-6 hidden" hidden>
                    <p class="mb-2 text-xs font-medium text-muted-foreground">{{ __('messages.pages.find_perfect_car.refine_label') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'Prefer cheaper options within my budget' : 'Foretræk billigere valg inden for mit budget' }}">{{ __('messages.pages.find_perfect_car.refine_cheaper') }}</button>
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'I need more cabin and cargo space' : 'Jeg har brug for mere kabine- og bagagerumsplads' }}">{{ __('messages.pages.find_perfect_car.refine_space') }}</button>
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'Prioritise city driving fitness' : 'Prioritér bykørsel' }}">{{ __('messages.pages.find_perfect_car.refine_city') }}</button>
                        <button type="button" class="advisor-refine-chip advisor-chip" data-refine="{{ app()->getLocale() === 'en' ? 'Lower ownership tax is more important' : 'Lavere ejerafgift er vigtigere' }}">{{ __('messages.pages.find_perfect_car.refine_tax') }}</button>
                    </div>
                </div>

                <p class="mt-10 text-xs leading-relaxed text-muted-foreground">
                    {{ __('messages.pages.find_perfect_car.disclaimer') }}
                </p>
            @endif
        </div>
    </section>

    <div id="advisor-why-modal" class="advisor-why-modal" hidden aria-hidden="true">
        <div class="advisor-why-modal__backdrop" data-advisor-why-close tabindex="-1"></div>
        <div class="advisor-why-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="advisor-why-modal-title">
            <div class="advisor-why-modal__header">
                <div class="min-w-0">
                    <p class="advisor-why-modal__kicker">{{ __('messages.pages.find_perfect_car.why_toggle') }}</p>
                    <h2 id="advisor-why-modal-title" class="advisor-why-modal__title"></h2>
                </div>
                <button type="button" class="advisor-why-modal__close" data-advisor-why-close aria-label="{{ __('messages.pages.find_perfect_car.close') }}">&times;</button>
            </div>
            <div class="advisor-why-modal__body" id="advisor-why-modal-body"></div>
            <div class="advisor-why-modal__footer" id="advisor-why-modal-footer"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.getElementById('car-advisor-page');
    if (!page || page.dataset.aiEnabled !== '1') return;

    const I18N = {
        thinking: @json(__('messages.pages.find_perfect_car.thinking')),
        loadingSteps: [
            @json(__('messages.pages.find_perfect_car.loading_step_1')),
            @json(__('messages.pages.find_perfect_car.loading_step_2')),
            @json(__('messages.pages.find_perfect_car.loading_step_3')),
            @json(__('messages.pages.find_perfect_car.loading_step_4')),
        ],
        error: @json(__('messages.pages.find_perfect_car.error')),
        empty: @json(__('messages.pages.find_perfect_car.empty')),
        emptyBrowse: @json(__('messages.pages.find_perfect_car.empty_browse')),
        match: @json(__('messages.pages.find_perfect_car.match')),
        bestMatch: @json(__('messages.pages.find_perfect_car.best_match')),
        why: @json(__('messages.pages.find_perfect_car.why')),
        whyToggle: @json(__('messages.pages.find_perfect_car.why_toggle')),
        close: @json(__('messages.pages.find_perfect_car.close')),
        tradeoffs: @json(__('messages.pages.find_perfect_car.tradeoffs')),
        ownership: @json(__('messages.pages.find_perfect_car.ownership')),
        ownershipTax: @json(__('messages.pages.find_perfect_car.ownership_tax')),
        fairPrice: @json(__('messages.pages.find_perfect_car.fair_price')),
        viewListing: @json(__('messages.pages.find_perfect_car.view_listing')),
        enquire: @json(__('messages.pages.find_perfect_car.enquire')),
        candidateCount: @json(__('messages.pages.find_perfect_car.candidate_count')),
        understood: @json(__('messages.pages.find_perfect_car.understood')),
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
    const loadingEl = document.getElementById('advisor-loading');
    const loadingStepEl = document.getElementById('advisor-loading-step');
    const progressEl = document.getElementById('advisor-progress');
    const progressFill = document.getElementById('advisor-progress-fill');
    const stageEls = loadingEl ? loadingEl.querySelectorAll('.advisor-stage') : [];
    const understoodEl = document.getElementById('advisor-understood');
    const examplesToggle = document.getElementById('advisor-examples-toggle');
    const examplesPanel = document.getElementById('advisor-examples-panel');
    const honeypotName = page.dataset.honeypot || 'website';
    const vehiclesUrl = page.dataset.vehiclesUrl || '/vehicles';
    const whyModal = document.getElementById('advisor-why-modal');
    const whyModalTitle = document.getElementById('advisor-why-modal-title');
    const whyModalBody = document.getElementById('advisor-why-modal-body');
    const whyModalFooter = document.getElementById('advisor-why-modal-footer');

    let history = [];
    let lastFilters = {};
    let lastSummary = '';
    let lastRecommendations = [];
    let loadingTimer = null;
    let loadingStepIndex = 0;
    let whyModalLastFocus = null;

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function show(el) {
        if (!el) return;
        el.classList.remove('hidden');
        el.removeAttribute('hidden');
    }

    function hide(el) {
        if (!el) return;
        el.classList.add('hidden');
        el.setAttribute('hidden', '');
    }

    function setProgress(pct, stageIndex) {
        const clamped = Math.max(0, Math.min(96, pct));
        if (progressFill) progressFill.style.width = clamped + '%';
        if (progressEl) progressEl.setAttribute('aria-valuenow', String(Math.round(clamped)));
        stageEls.forEach(function (el, i) {
            el.classList.remove('is-active', 'is-done');
            if (i < stageIndex) el.classList.add('is-done');
            else if (i === stageIndex) el.classList.add('is-active');
        });
    }

    function setLoadingStep(index) {
        if (!loadingStepEl) return;
        const next = I18N.loadingSteps[index] || I18N.thinking;
        loadingStepEl.classList.add('is-swap');
        window.setTimeout(function () {
            loadingStepEl.textContent = next;
            loadingStepEl.classList.remove('is-swap');
        }, 160);
    }

    function startLoadingProgress() {
        stopLoadingProgress();
        loadingStepIndex = 0;
        setProgress(12, 0);
        setLoadingStep(0);
        const targets = [28, 52, 74, 90];
        loadingTimer = window.setInterval(function () {
            loadingStepIndex = Math.min(loadingStepIndex + 1, I18N.loadingSteps.length - 1);
            setLoadingStep(loadingStepIndex);
            setProgress(targets[loadingStepIndex] || 90, loadingStepIndex);
            if (loadingStepIndex >= I18N.loadingSteps.length - 1) {
                window.clearInterval(loadingTimer);
                loadingTimer = null;
            }
        }, 1400);
    }

    function stopLoadingProgress() {
        if (loadingTimer) {
            window.clearInterval(loadingTimer);
            loadingTimer = null;
        }
    }

    function setLoading(on) {
        submitBtn.disabled = on;
        statusEl.classList.add('hidden');
        statusEl.textContent = '';
        if (on) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
            hide(resultsEl);
            hide(refineEl);
            if (loadingEl) {
                loadingEl.setAttribute('aria-busy', 'true');
                show(loadingEl);
                loadingEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            startLoadingProgress();
        } else {
            stopLoadingProgress();
            if (loadingEl) {
                loadingEl.setAttribute('aria-busy', 'false');
                hide(loadingEl);
            }
        }
    }

    function showError(msg) {
        stopLoadingProgress();
        errorEl.textContent = msg || I18N.error;
        errorEl.classList.remove('hidden');
        hide(loadingEl);
        if (loadingEl) loadingEl.setAttribute('aria-busy', 'false');
        submitBtn.disabled = false;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatKm(km) {
        if (km == null || km === '') return '';
        try {
            return Number(km).toLocaleString() + ' km';
        } catch (e) {
            return String(km) + ' km';
        }
    }

    function truncateText(text, max) {
        const value = String(text || '').trim();
        if (value.length <= max) return value;
        return value.slice(0, max - 1).trimEnd() + '…';
    }

    function renderUnderstood(labels) {
        if (!understoodEl) return;
        const chips = Array.isArray(labels) ? labels.filter(Boolean) : [];
        if (!chips.length) {
            hide(understoodEl);
            understoodEl.innerHTML = '';
            return;
        }
        const items = chips.map(function (item) {
            const text = typeof item === 'string' ? item : (item.label || item.key || '');
            if (!text) return '';
            return `<span class="advisor-understood-chip">${escapeHtml(text)}</span>`;
        }).filter(Boolean).join('');
        understoodEl.innerHTML = items
            ? `<span class="w-full text-xs font-medium text-muted-foreground mb-0.5">${escapeHtml(I18N.understood)}</span>${items}`
            : '';
        if (items) show(understoodEl);
        else hide(understoodEl);
    }

    function hasWhyContent(rec) {
        return !!(rec.explanation
            || (rec.match_reasons && rec.match_reasons.length)
            || (rec.tradeoffs && rec.tradeoffs.length)
            || rec.ownership_outlook);
    }

    function closeWhyModal() {
        if (!whyModal || whyModal.hasAttribute('hidden')) return;
        whyModal.setAttribute('hidden', '');
        whyModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (whyModalLastFocus && typeof whyModalLastFocus.focus === 'function') {
            whyModalLastFocus.focus();
        }
        whyModalLastFocus = null;
    }

    function openWhyModal(index) {
        const rec = lastRecommendations[index];
        if (!rec || !whyModal || !whyModalBody || !whyModalFooter) return;

        const pct = Math.max(0, Math.min(100, Number(rec.match_percent) || 0));
        const img = rec.image_url
            ? `<img src="${escapeHtml(rec.image_url)}" alt="">`
            : `<div class="flex h-full items-center justify-center text-xs text-muted-foreground">Bilskyen</div>`;

        const metaBits = [];
        if (rec.price_formatted) metaBits.push(escapeHtml(rec.price_formatted));
        if (rec.year) metaBits.push(escapeHtml(String(rec.year)));
        if (rec.km_driven != null) metaBits.push(escapeHtml(formatKm(rec.km_driven)));
        if (rec.fuel) metaBits.push(escapeHtml(rec.fuel));

        const chips = [];
        if (rec.ownership_tax_formatted) {
            chips.push(`<span class="advisor-why-modal__chip">${escapeHtml(I18N.ownershipTax)}: ${escapeHtml(rec.ownership_tax_formatted)}</span>`);
        }
        if (rec.fair_price && rec.fair_price.label_text) {
            chips.push(`<span class="advisor-why-modal__chip">${escapeHtml(I18N.fairPrice)}: ${escapeHtml(rec.fair_price.label_text)}</span>`);
        }

        const sections = [];
        const hasExplanation = !!rec.explanation;
        const hasReasons = !!(rec.match_reasons && rec.match_reasons.length);

        if (hasExplanation || hasReasons) {
            const reasonItems = hasReasons
                ? `<ul${hasExplanation ? ' style="margin-top:0.65rem"' : ''}>${rec.match_reasons.map(function (r) {
                    return `<li>${escapeHtml(r)}</li>`;
                }).join('')}</ul>`
                : '';
            sections.push(`
            <section class="advisor-why-modal__section">
                <h3 class="advisor-why-modal__section-title">${escapeHtml(I18N.why)}</h3>
                ${hasExplanation ? `<p>${escapeHtml(rec.explanation)}</p>` : ''}
                ${reasonItems}
            </section>`);
        }
        if (rec.tradeoffs && rec.tradeoffs.length) {
            sections.push(`
            <section class="advisor-why-modal__section advisor-why-modal__section--warn">
                <h3 class="advisor-why-modal__section-title">${escapeHtml(I18N.tradeoffs)}</h3>
                <ul>${rec.tradeoffs.map(function (t) { return `<li>${escapeHtml(t)}</li>`; }).join('')}</ul>
            </section>`);
        }
        if (rec.ownership_outlook) {
            sections.push(`
            <section class="advisor-why-modal__section">
                <h3 class="advisor-why-modal__section-title">${escapeHtml(I18N.ownership)}</h3>
                <p>${escapeHtml(rec.ownership_outlook)}</p>
            </section>`);
        }

        whyModalTitle.textContent = rec.title || I18N.whyToggle;
        whyModalBody.innerHTML = `
            <div class="advisor-why-modal__hero">
                <div class="advisor-why-modal__photo">${img}</div>
                <div class="min-w-0">
                    ${index === 0 ? `<span class="advisor-best-badge">${escapeHtml(I18N.bestMatch)}</span>` : ''}
                    <div class="advisor-why-modal__meta">${metaBits.join(' · ')}</div>
                    ${chips.length ? `<div class="advisor-why-modal__chips">${chips.join('')}</div>` : ''}
                </div>
                <div class="advisor-why-modal__match">
                    <div class="advisor-ring" style="--pct:${pct}">
                        <span class="advisor-ring-value">${escapeHtml(String(rec.match_percent))}%</span>
                    </div>
                    <span class="advisor-ring-label">${escapeHtml(I18N.match)}</span>
                </div>
            </div>
            ${sections.join('')}`;
        whyModalFooter.innerHTML = `
            <a href="${escapeHtml(rec.detail_url)}" class="advisor-btn-primary">${escapeHtml(I18N.viewListing)}</a>
            <a href="${escapeHtml(rec.enquire_url)}" class="advisor-btn-ghost">${escapeHtml(I18N.enquire)}</a>
        `;

        whyModalLastFocus = document.activeElement;
        whyModal.removeAttribute('hidden');
        whyModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const closeBtn = whyModal.querySelector('.advisor-why-modal__close');
        if (closeBtn) closeBtn.focus();
    }

    function renderCard(rec, index) {
        const isBest = index === 0;
        const pct = Math.max(0, Math.min(100, Number(rec.match_percent) || 0));
        const fair = rec.fair_price && rec.fair_price.label_text
            ? `<span>${escapeHtml(I18N.fairPrice)}: ${escapeHtml(rec.fair_price.label_text)}</span>`
            : '';
        const tax = rec.ownership_tax_formatted
            ? `<span>${escapeHtml(I18N.ownershipTax)}: ${escapeHtml(rec.ownership_tax_formatted)}</span>`
            : '';
        const img = rec.image_url
            ? `<img src="${escapeHtml(rec.image_url)}" alt="" loading="lazy">`
            : `<div class="flex h-full items-center justify-center text-xs text-muted-foreground">Bilskyen</div>`;

        const metaParts = [];
        if (rec.price_formatted) metaParts.push(`<strong class="font-semibold text-foreground">${escapeHtml(rec.price_formatted)}</strong>`);
        if (rec.year) metaParts.push(`<span>${escapeHtml(String(rec.year))}</span>`);
        if (rec.km_driven != null) metaParts.push(`<span>${escapeHtml(formatKm(rec.km_driven))}</span>`);
        if (rec.fuel) metaParts.push(`<span>${escapeHtml(rec.fuel)}</span>`);

        const teaserSource = rec.explanation
            || ((rec.match_reasons && rec.match_reasons[0]) ? rec.match_reasons[0] : '');
        const teaser = teaserSource ? truncateText(teaserSource, 140) : '';
        const delay = Math.min(index * 70, 280);

        return `
        <article class="advisor-row${isBest ? ' is-best' : ''}" style="animation-delay:${delay}ms">
            <a href="${escapeHtml(rec.detail_url)}" class="advisor-row-photo">
                <span class="advisor-rank">${escapeHtml(String(index + 1))}</span>
                ${img}
            </a>
            <div class="min-w-0">
                ${isBest ? `<span class="advisor-best-badge">${escapeHtml(I18N.bestMatch)}</span>` : ''}
                <h3 class="text-base font-semibold leading-snug text-foreground md:text-[1.05rem]">
                    <a href="${escapeHtml(rec.detail_url)}" class="hover:underline">${escapeHtml(rec.title)}</a>
                </h3>
                <div class="advisor-meta-line">${metaParts.join('')}</div>
                ${(tax || fair) ? `<div class="advisor-meta-line">${tax}${fair}</div>` : ''}
                ${teaser ? `<p class="advisor-teaser">${escapeHtml(teaser)}</p>` : ''}
                <div class="advisor-actions">
                    <a href="${escapeHtml(rec.detail_url)}" class="advisor-btn-primary">${escapeHtml(I18N.viewListing)}</a>
                    <a href="${escapeHtml(rec.enquire_url)}" class="advisor-btn-ghost">${escapeHtml(I18N.enquire)}</a>
                </div>
                ${hasWhyContent(rec) ? `
                <button type="button" class="advisor-why-btn" data-advisor-why="${index}">
                    ${escapeHtml(I18N.whyToggle)}
                </button>` : ''}
            </div>
            <div class="advisor-match">
                <div class="advisor-ring" style="--pct:${pct}" aria-label="${escapeHtml(I18N.match)} ${escapeHtml(String(rec.match_percent))}%">
                    <span class="advisor-ring-value">${escapeHtml(String(rec.match_percent))}%</span>
                </div>
                <span class="advisor-ring-label">${escapeHtml(I18N.match)}</span>
            </div>
        </article>`;
    }

    function renderEmpty() {
        return `
        <div class="advisor-empty">
            <p class="text-sm text-muted-foreground">${escapeHtml(I18N.empty)}</p>
            <a href="${escapeHtml(vehiclesUrl)}" class="advisor-btn-primary mt-4 inline-flex">${escapeHtml(I18N.emptyBrowse)}</a>
        </div>`;
    }

    function renderResults(data) {
        lastFilters = data.filters || {};
        lastSummary = data.summary || '';
        summaryEl.textContent = data.summary || '';
        metaEl.textContent = I18N.candidateCount.replace(':count', String(data.candidate_count || 0));
        relaxedEl.classList.toggle('hidden', !data.relaxed_filters);
        if (data.browse_url) {
            browseLink.href = data.browse_url;
        }
        renderUnderstood(data.labels || []);

        const recs = data.recommendations || [];
        lastRecommendations = recs;
        if (!recs.length) {
            cardsEl.innerHTML = renderEmpty();
        } else {
            cardsEl.innerHTML = `<div class="advisor-list">${recs.map(renderCard).join('')}</div>`;
        }
        show(resultsEl);
        show(refineEl);
        if (examplesPanel) {
            hide(examplesPanel);
            if (examplesToggle) examplesToggle.setAttribute('aria-expanded', 'false');
        }
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
            const json = await res.json().catch(function () { return {}; });
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
            setProgress(100, 3);
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

    if (examplesToggle && examplesPanel) {
        examplesToggle.addEventListener('click', function () {
            const open = examplesPanel.hasAttribute('hidden');
            if (open) {
                show(examplesPanel);
                examplesToggle.setAttribute('aria-expanded', 'true');
            } else {
                hide(examplesPanel);
                examplesToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

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
                    name: (lastSummary || @json(__('messages.navigation.find_perfect_car'))).slice(0, 80),
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

    if (cardsEl) {
        cardsEl.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-advisor-why]');
            if (!btn) return;
            e.preventDefault();
            openWhyModal(Number(btn.getAttribute('data-advisor-why')));
        });
    }

    if (whyModal) {
        whyModal.addEventListener('click', function (e) {
            if (e.target.closest('[data-advisor-why-close]')) {
                closeWhyModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeWhyModal();
    });

    const params = new URLSearchParams(window.location.search);
    const q = (params.get('q') || '').trim();
    if (q) {
        if (!input.value.trim()) {
            input.value = q;
        }
        advise(input.value.trim() || q, false);
    }
})();
</script>
@endpush
