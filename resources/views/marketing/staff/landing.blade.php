@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.staff_marketing.landing.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="py-20 md:py-28 text-center bg-muted/40">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $content['landing_hero_title'] ?? __('messages.staff_marketing.landing.hero_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-6 max-w-2xl text-lg">
                {{ $content['landing_hero_description'] ?? __('messages.staff_marketing.landing.hero_description') }}
            </p>
            <a href="{{ $panelUrl }}/auth/staff-login" class="mt-10 inline-flex h-12 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                {{ __('messages.staff_marketing.nav.staff_login') }}
            </a>
            <p class="mt-4 text-sm text-muted-foreground">{{ __('messages.staff_marketing.landing.invite_note') }}</p>
        </div>
    </section>

    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight">{{ $content['landing_features_title'] ?? __('messages.staff_marketing.landing.features_title') }}</h2>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3 max-w-5xl mx-auto">
                @foreach(range(1, 3) as $i)
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <h3 class="text-lg font-semibold">{{ $content["landing_feature_{$i}_title"] ?? __("messages.staff_marketing.landing.feature_{$i}_title") }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">{{ $content["landing_feature_{$i}_description"] ?? __("messages.staff_marketing.landing.feature_{$i}_description") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
