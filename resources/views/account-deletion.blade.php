@extends('layouts.app')

@section('title', __('messages.pages.account_deletion.page_title') . ' | ' . __('messages.common.site_name'))

@section('content')
@php
    $supportEmail = __('messages.pages.account_deletion.support_email');
    $mailSubject = __('messages.pages.account_deletion.mail_subject');
    $siteName = __('messages.common.site_name');
    $emailHref = 'mailto:' . $supportEmail . '?subject=' . rawurlencode($mailSubject);
    $emailLink = '<a href="' . e($emailHref) . '" class="font-semibold text-white underline decoration-white/40 underline-offset-2 hover:decoration-white">' . e($supportEmail) . '</a>';
@endphp
<div class="min-h-[70vh] bg-[#0a0d12] py-12 px-4 md:py-16">
    <div class="mx-auto w-full max-w-xl">
        <article class="overflow-hidden rounded-2xl shadow-xl shadow-black/40 ring-1 ring-white/5">
            <header class="bg-[#789961] px-6 py-5 text-center">
                <h1 class="text-xl font-bold tracking-tight text-[#1a1f2e] md:text-2xl">
                    {{ __('messages.pages.account_deletion.card_title') }}
                </h1>
            </header>
            <div class="space-y-6 bg-[#121621] px-6 py-8 text-[15px] leading-relaxed text-white/95 md:px-10 md:py-10">
                <div class="space-y-3">
                    <p>
                        {!! __('messages.pages.account_deletion.intro', ['email' => $emailLink]) !!}
                    </p>
                    <p>
                        <span class="font-semibold text-white">{{ __('messages.pages.account_deletion.subject_label') }}</span>
                        {{ __('messages.pages.account_deletion.subject_value') }}
                    </p>
                    <p class="text-white/90">
                        {{ __('messages.pages.account_deletion.include_identifier', ['site' => $siteName]) }}
                    </p>
                </div>

                <section>
                    <h2 class="mb-3 font-semibold text-white">
                        {{ __('messages.pages.account_deletion.section_deleted_title') }}
                    </h2>
                    <ul class="list-disc space-y-2 pl-5 text-white/90 marker:text-white/50">
                        <li>{{ __('messages.pages.account_deletion.deleted_item_1') }}</li>
                        <li>{{ __('messages.pages.account_deletion.deleted_item_2') }}</li>
                        <li>{{ __('messages.pages.account_deletion.deleted_item_3') }}</li>
                        <li>{{ __('messages.pages.account_deletion.deleted_item_4') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 font-semibold text-white">
                        {{ __('messages.pages.account_deletion.section_retained_title') }}
                    </h2>
                    <ul class="list-disc space-y-2 pl-5 text-white/90 marker:text-white/50">
                        <li>{{ __('messages.pages.account_deletion.retained_item_1') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 font-semibold text-white">
                        {{ __('messages.pages.account_deletion.section_processing_title') }}
                    </h2>
                    <p class="text-white/90">
                        {{ __('messages.pages.account_deletion.processing_text') }}
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 font-semibold text-white">
                        {{ __('messages.pages.account_deletion.section_contact_title') }}
                    </h2>
                    <p class="text-white/90">
                        {!! __('messages.pages.account_deletion.contact_text', ['email' => $emailLink]) !!}
                    </p>
                </section>
            </div>
        </article>
    </div>
</div>
@endsection
