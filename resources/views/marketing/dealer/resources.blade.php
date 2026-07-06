@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.dealer_marketing.resources.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $content['resources_header_title'] ?? __('messages.dealer_marketing.resources.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $content['resources_header_description'] ?? __('messages.dealer_marketing.resources.subtitle') }}
            </p>
        </div>
    </section>

    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6 max-w-4xl">
            <div class="grid gap-6">
                @foreach([
                    ['key' => 'getting_started', 'route' => route('for-dealers.pricing')],
                    ['key' => 'inventory', 'route' => route('blog.index')],
                    ['key' => 'leads', 'route' => route('for-dealers.contact')],
                    ['key' => 'support', 'route' => route('for-dealers.contact')],
                ] as $resource)
                    <a href="{{ $resource['route'] }}" class="group flex items-start gap-4 rounded-xl border border-border bg-card p-6 transition-colors hover:border-primary/50 hover:bg-muted/30">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold group-hover:text-primary transition-colors">
                                {{ $content["resource_{$resource['key']}_title"] ?? __("messages.dealer_marketing.resources.{$resource['key']}_title") }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ $content["resource_{$resource['key']}_description"] ?? __("messages.dealer_marketing.resources.{$resource['key']}_description") }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
