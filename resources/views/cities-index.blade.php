@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.pages.cities.index_heading'))

@section('content')
<div class="container mx-auto px-4 md:px-6 py-8 md:py-12">
    <nav class="mb-6 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="/" class="hover:text-foreground">{{ __('messages.pages.cities.breadcrumb_home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-foreground font-medium">{{ __('messages.pages.cities.index_heading') }}</li>
        </ol>
    </nav>

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-foreground">
            {{ __('messages.pages.cities.index_heading') }}
        </h1>
        <p class="mt-3 text-base text-muted-foreground leading-relaxed">
            {{ __('messages.pages.cities.index_intro') }}
        </p>
    </header>

    @if($cities->isEmpty())
        <p class="text-muted-foreground">{{ __('messages.pages.cities.no_cars', ['city' => 'Danmark']) }}</p>
        <a href="/vehicles" class="mt-4 inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">{{ __('messages.pages.footer.browse_vehicles') }}</a>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($cities as $city)
                <article class="rounded-xl border border-border bg-card p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h2 class="text-lg font-semibold text-foreground">{{ $city->name }}</h2>
                    @if($city->region)
                        <p class="text-xs text-muted-foreground mt-0.5">{{ $city->region }}</p>
                    @endif
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{ __('messages.pages.cities.vehicles_count', ['count' => $city->published_vehicle_count]) }}
                        ·
                        {{ __('messages.pages.cities.dealers_count', ['count' => $city->dealer_count]) }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($city->published_vehicle_count > 0)
                            <a href="{{ route('cities.cars', $city->slug) }}" class="inline-flex h-9 items-center rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground hover:bg-primary/90">
                                {{ __('messages.pages.cities.see_cars', ['city' => $city->name]) }}
                            </a>
                        @endif
                        @if($city->dealer_count > 0)
                            <a href="{{ route('cities.dealers', $city->slug) }}" class="inline-flex h-9 items-center rounded-md border border-input px-3 text-xs font-medium hover:bg-muted">
                                {{ __('messages.pages.cities.see_dealers', ['city' => $city->name]) }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
