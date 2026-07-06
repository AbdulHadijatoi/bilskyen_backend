@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.staff_marketing.resources.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $content['resources_header_title'] ?? __('messages.staff_marketing.resources.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $content['resources_header_description'] ?? __('messages.staff_marketing.resources.subtitle') }}
            </p>
        </div>
    </section>

    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl space-y-8">
            @foreach(range(1, 4) as $i)
                <div class="rounded-xl border border-border bg-card p-6">
                    <h2 class="text-lg font-semibold">{{ $content["resource_{$i}_title"] ?? __("messages.staff_marketing.resources.item_{$i}_title") }}</h2>
                    <p class="mt-2 text-sm text-muted-foreground">{{ $content["resource_{$i}_description"] ?? __("messages.staff_marketing.resources.item_{$i}_description") }}</p>
                </div>
            @endforeach

            <div class="rounded-xl border border-primary/30 bg-primary/5 p-6 text-center">
                <p class="text-sm text-muted-foreground mb-4">{{ __('messages.staff_marketing.resources.need_access') }}</p>
                <p class="font-medium">{{ __('messages.staff_marketing.resources.contact_admin') }}</p>
            </div>
        </div>
    </section>
</div>
@endsection
