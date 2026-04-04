@extends('layouts.app')

@section('title', __('messages.pages.account_deletion.page_title') . ' | ' . __('messages.common.site_name'))

@section('content')
@php
    $supportEmail = __('messages.pages.account_deletion.support_email');
    $mailSubject = __('messages.pages.account_deletion.mail_subject');
    $siteName = __('messages.common.site_name');
    $emailHref = 'mailto:' . $supportEmail . '?subject=' . rawurlencode($mailSubject);
    $emailLink = '<a href="' . e($emailHref) . '" class="font-medium text-primary underline-offset-2 hover:underline">' . e($supportEmail) . '</a>';
@endphp
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-16 text-center md:py-20" aria-labelledby="account-deletion-heading">
        <div class="container mx-auto px-4 md:px-6">
            <h1 id="account-deletion-heading" class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ __('messages.pages.account_deletion.header_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg leading-relaxed">
                {{ __('messages.pages.account_deletion.header_description') }}
            </p>
        </div>
    </section>

    <section class="py-12 md:py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto max-w-3xl space-y-10 md:space-y-12">
                <div class="rounded-lg border border-border bg-card shadow-sm">
                    <div class="border-b border-border p-6 md:p-8 md:pb-6">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            {{ __('messages.pages.account_deletion.how_to_title') }}
                        </h2>
                        <p class="text-muted-foreground mt-3 text-base leading-relaxed">
                            {!! __('messages.pages.account_deletion.intro', ['email' => $emailLink]) !!}
                        </p>
                        <p class="text-muted-foreground mt-3 text-sm leading-relaxed">
                            {{ __('messages.pages.account_deletion.include_identifier', ['site' => $siteName]) }}
                        </p>
                    </div>
                    <div class="space-y-6 p-6 pt-2 md:px-8 md:pb-8">
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none" for="deletion-subject-preview">
                                {{ __('messages.pages.account_deletion.subject_label') }}
                            </label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                                <input
                                    type="text"
                                    id="deletion-subject-preview"
                                    readonly
                                    value="{{ e($mailSubject) }}"
                                    tabindex="0"
                                    class="flex min-h-10 w-full min-w-0 flex-1 cursor-default rounded-md border border-input bg-muted/60 px-3 py-2 font-mono text-sm text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    aria-readonly="true"
                                />
                                <button
                                    type="button"
                                    class="inline-flex h-10 shrink-0 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium ring-offset-background transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    data-copy-text="{{ e($mailSubject) }}"
                                    data-label-default="{{ __('messages.pages.account_deletion.copy_subject') }}"
                                    data-label-copied="{{ __('messages.pages.account_deletion.copied') }}"
                                    aria-label="{{ __('messages.pages.account_deletion.copy_subject') }}"
                                >
                                    {{ __('messages.pages.account_deletion.copy_subject') }}
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <a
                                href="{{ e($emailHref) }}"
                                class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 shrink-0" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                {{ __('messages.pages.account_deletion.send_email_cta') }}
                            </a>
                            <button
                                type="button"
                                class="inline-flex h-11 items-center justify-center rounded-md border border-input bg-background px-6 text-sm font-medium ring-offset-background transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                data-copy-text="{{ e($supportEmail) }}"
                                data-label-default="{{ __('messages.pages.account_deletion.copy_email') }}"
                                data-label-copied="{{ __('messages.pages.account_deletion.copied') }}"
                                aria-label="{{ __('messages.pages.account_deletion.copy_email') }}"
                            >
                                {{ __('messages.pages.account_deletion.copy_email') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 lg:grid-cols-2 lg:gap-10">
                    <section class="rounded-lg border border-border bg-card p-6 shadow-sm md:p-8" aria-labelledby="deleted-data-heading">
                        <h2 id="deleted-data-heading" class="text-lg font-semibold tracking-tight">
                            {{ __('messages.pages.account_deletion.section_deleted_title') }}
                        </h2>
                        <ul class="text-muted-foreground mt-4 list-disc space-y-2.5 pl-5 text-sm leading-relaxed marker:text-primary">
                            <li>{{ __('messages.pages.account_deletion.deleted_item_1') }}</li>
                            <li>{{ __('messages.pages.account_deletion.deleted_item_2') }}</li>
                            <li>{{ __('messages.pages.account_deletion.deleted_item_3') }}</li>
                            <li>{{ __('messages.pages.account_deletion.deleted_item_4') }}</li>
                        </ul>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-6 shadow-sm md:p-8" aria-labelledby="retained-data-heading">
                        <h2 id="retained-data-heading" class="text-lg font-semibold tracking-tight">
                            {{ __('messages.pages.account_deletion.section_retained_title') }}
                        </h2>
                        <ul class="text-muted-foreground mt-4 list-disc space-y-2.5 pl-5 text-sm leading-relaxed marker:text-primary">
                            <li>{{ __('messages.pages.account_deletion.retained_item_1') }}</li>
                        </ul>
                    </section>
                </div>

                <aside class="rounded-lg border-l-4 border-primary bg-primary/5 p-5 md:p-6" aria-labelledby="processing-heading">
                    <h2 id="processing-heading" class="text-base font-semibold tracking-tight">
                        {{ __('messages.pages.account_deletion.section_processing_title') }}
                    </h2>
                    <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                        {{ __('messages.pages.account_deletion.processing_text') }}
                    </p>
                </aside>

                <section class="rounded-lg border border-border bg-muted/40 p-6 md:p-8" aria-labelledby="contact-heading">
                    <h2 id="contact-heading" class="text-lg font-semibold tracking-tight">
                        {{ __('messages.pages.account_deletion.section_contact_title') }}
                    </h2>
                    <p class="text-muted-foreground mt-3 text-sm leading-relaxed">
                        {!! __('messages.pages.account_deletion.contact_text', ['email' => $emailLink]) !!}
                    </p>
                </section>
            </div>
        </div>
    </section>
</div>

<span id="account-deletion-copy-announcer" class="sr-only" role="status" aria-live="polite"></span>

@push('scripts')
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var announcer = document.getElementById('account-deletion-copy-announcer');
        document.querySelectorAll('[data-copy-text]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy-text') || '';
                var defaultLabel = btn.getAttribute('data-label-default') || '';
                var copiedLabel = btn.getAttribute('data-label-copied') || '';
                function finishCopy() {
                    btn.textContent = copiedLabel;
                    if (announcer) announcer.textContent = copiedLabel;
                    setTimeout(function () {
                        btn.textContent = defaultLabel;
                        if (announcer) announcer.textContent = '';
                    }, 2000);
                }
                function fallbackCopy() {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    try {
                        if (document.execCommand('copy')) finishCopy();
                    } finally {
                        document.body.removeChild(ta);
                    }
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(finishCopy).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }
            });
        });
    });
})();
</script>
@endpush
@endsection
