@extends('layouts.app')

@section('title', __('messages.pages.about.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $aboutPageContent['about_header_title'] ?? __('messages.pages.about.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $aboutPageContent['about_header_description'] ?? __('messages.pages.about.description') }}
            </p>
        </div>
    </section>

    <!-- Mission and Story Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid items-center gap-12 md:grid-cols-2">
                <div class="space-y-6">
                    <div class="text-primary flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="M4.5 16.5c-1.5 1.5-3 3-2 5s3.5-.5 5-2c1.5-1.5 2.5-3.5 2-5s-3.5-1.5-5 2z"></path>
                            <path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                            <path d="M20 8v6"></path>
                            <path d="M17 11h6"></path>
                        </svg>
                        <h2 class="text-sm font-semibold tracking-wider uppercase">
                            {{ $aboutPageContent['about_mission_label'] ?? __('messages.pages.about.mission_label') }}
                        </h2>
                    </div>
                    <h3 class="text-3xl font-bold tracking-tight">
                        {{ $aboutPageContent['about_mission_title'] ?? __('messages.pages.about.mission_title') }}
                    </h3>
                    <p class="text-muted-foreground">
                        {{ $aboutPageContent['about_mission_description_1'] ?? __('messages.pages.about.mission_description_1') }}
                    </p>
                    <p class="text-muted-foreground">
                        {{ $aboutPageContent['about_mission_description_2'] ?? __('messages.pages.about.mission_description_2') }}
                    </p>
                </div>
                <div class="relative h-80 w-full overflow-hidden rounded-lg shadow-lg">
                    @php
                        $missionImage = isset($aboutPageImages['about_mission_image']) && count($aboutPageImages['about_mission_image']) > 0 
                            ? $aboutPageImages['about_mission_image'][0] 
                            : null;
                    @endphp
                    <img
                        src="{{ $missionImage && isset($missionImage['image_url']) ? $missionImage['image_url'] : '/images/showroom.jpg' }}"
                        alt="{{ $missionImage && isset($missionImage['alt_text']) ? $missionImage['alt_text'] : __('messages.pages.about.modern_car_dealership_interior') }}"
                        class="h-full w-full object-cover"
                        onerror="this.src='https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'"
                    />
                </div>
            </div>
        </div>
    </section>

    <div class="border-t border-border"></div>

    <!-- Our Values Section -->
    <section class="bg-muted py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold tracking-tight">{{ $aboutPageContent['about_values_title'] ?? __('messages.pages.about.values_title') }}</h2>
                <p class="text-muted-foreground mx-auto mt-2 max-w-2xl">
                    {{ $aboutPageContent['about_values_description'] ?? __('messages.pages.about.values_description') }}
                </p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M9 2v6l6-3-6-3z"></path>
                            <path d="M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
                            <path d="M20 13a8 8 0 1 1-16 0 8 8 0 0 1 16 0z"></path>
                            <path d="M12 23a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_value_1_title'] ?? __('messages.pages.about.value_1_title') }}</h3>
                    <p class="text-muted-foreground mt-1">
                        {{ $aboutPageContent['about_value_1_description'] ?? __('messages.pages.about.value_1_description') }}
                    </p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_value_2_title'] ?? __('messages.pages.about.value_2_title') }}</h3>
                    <p class="text-muted-foreground mt-1">
                        {{ $aboutPageContent['about_value_2_description'] ?? __('messages.pages.about.value_2_description') }}
                    </p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_value_3_title'] ?? __('messages.pages.about.value_3_title') }}</h3>
                    <p class="text-muted-foreground mt-1">
                        {{ $aboutPageContent['about_value_3_description'] ?? __('messages.pages.about.value_3_description') }}
                    </p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M7 10v12"></path>
                            <path d="m15 5-2-2-2 2"></path>
                            <path d="M17 14v-3a2 2 0 0 0-2-2h-2"></path>
                            <path d="M17 14H7"></path>
                            <path d="M7 14v-3a2 2 0 0 1 2-2h2"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_value_4_title'] ?? __('messages.pages.about.value_4_title') }}</h3>
                    <p class="text-muted-foreground mt-1">
                        {{ $aboutPageContent['about_value_4_description'] ?? __('messages.pages.about.value_4_description') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Meet the Team Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold tracking-tight">{{ $aboutPageContent['about_team_title'] ?? __('messages.pages.about.team_title') }}</h2>
                <p class="text-muted-foreground mx-auto mt-2 max-w-2xl">
                    {{ $aboutPageContent['about_team_description'] ?? __('messages.pages.about.team_description') }}
                </p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $teamMember1Image = isset($aboutPageImages['about_team_member_1_image']) && count($aboutPageImages['about_team_member_1_image']) > 0 
                        ? $aboutPageImages['about_team_member_1_image'][0] 
                        : null;
                    $teamMember2Image = isset($aboutPageImages['about_team_member_2_image']) && count($aboutPageImages['about_team_member_2_image']) > 0 
                        ? $aboutPageImages['about_team_member_2_image'][0] 
                        : null;
                    $teamMember3Image = isset($aboutPageImages['about_team_member_3_image']) && count($aboutPageImages['about_team_member_3_image']) > 0 
                        ? $aboutPageImages['about_team_member_3_image'][0] 
                        : null;
                @endphp
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <div class="bg-muted/50 p-5 text-center">
                        <div class="border-background mx-auto h-24 w-24 rounded-full border-4 bg-muted flex items-center justify-center">
                            @if($teamMember1Image && isset($teamMember1Image['image_url']))
                                <img src="{{ $teamMember1Image['image_url'] }}" alt="{{ $teamMember1Image['alt_text'] ?? ($aboutPageContent['about_team_member_1_name'] ?? __('messages.pages.about.team_member_1')) }}" class="h-full w-full rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-lg font-semibold\'>{{ substr($aboutPageContent['about_team_member_1_name'] ?? __('messages.pages.about.tm1'), 0, 2) }}</span>'">
                            @else
                                <span class="text-lg font-semibold">{{ substr($aboutPageContent['about_team_member_1_name'] ?? __('messages.pages.about.tm1'), 0, 2) }}</span>
                            @endif
                        </div>
                        <div class="pt-4">
                            <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_team_member_1_name'] ?? __('messages.pages.about.team_member_1_name') }}</h3>
                            <p class="text-primary text-sm">{{ $aboutPageContent['about_team_member_1_role'] ?? __('messages.pages.about.team_member_1_role') }}</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-muted-foreground">
                            {{ $aboutPageContent['about_team_member_1_description'] ?? __('messages.pages.about.team_member_1_description') }}
                        </p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <div class="bg-muted/50 p-5 text-center">
                        <div class="border-background mx-auto h-24 w-24 rounded-full border-4 bg-muted flex items-center justify-center">
                            @if($teamMember2Image && isset($teamMember2Image['image_url']))
                                <img src="{{ $teamMember2Image['image_url'] }}" alt="{{ $teamMember2Image['alt_text'] ?? ($aboutPageContent['about_team_member_2_name'] ?? __('messages.pages.about.team_member_2')) }}" class="h-full w-full rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-lg font-semibold\'>{{ substr($aboutPageContent['about_team_member_2_name'] ?? __('messages.pages.about.tm2'), 0, 2) }}</span>'">
                            @else
                                <span class="text-lg font-semibold">{{ substr($aboutPageContent['about_team_member_2_name'] ?? __('messages.pages.about.tm2'), 0, 2) }}</span>
                            @endif
                        </div>
                        <div class="pt-4">
                            <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_team_member_2_name'] ?? __('messages.pages.about.team_member_2_name') }}</h3>
                            <p class="text-primary text-sm">{{ $aboutPageContent['about_team_member_2_role'] ?? __('messages.pages.about.team_member_2_role') }}</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-muted-foreground">
                            {{ $aboutPageContent['about_team_member_2_description'] ?? __('messages.pages.about.team_member_2_description') }}
                        </p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <div class="bg-muted/50 p-5 text-center">
                        <div class="border-background mx-auto h-24 w-24 rounded-full border-4 bg-muted flex items-center justify-center">
                            @if($teamMember3Image && isset($teamMember3Image['image_url']))
                                <img src="{{ $teamMember3Image['image_url'] }}" alt="{{ $teamMember3Image['alt_text'] ?? ($aboutPageContent['about_team_member_3_name'] ?? __('messages.pages.about.team_member_3')) }}" class="h-full w-full rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-lg font-semibold\'>{{ substr($aboutPageContent['about_team_member_3_name'] ?? __('messages.pages.about.tm3'), 0, 2) }}</span>'">
                            @else
                                <span class="text-lg font-semibold">{{ substr($aboutPageContent['about_team_member_3_name'] ?? __('messages.pages.about.tm3'), 0, 2) }}</span>
                            @endif
                        </div>
                        <div class="pt-4">
                            <h3 class="text-xl font-semibold">{{ $aboutPageContent['about_team_member_3_name'] ?? __('messages.pages.about.team_member_3_name') }}</h3>
                            <p class="text-primary text-sm">{{ $aboutPageContent['about_team_member_3_role'] ?? __('messages.pages.about.team_member_3_role') }}</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-muted-foreground">
                            {{ $aboutPageContent['about_team_member_3_description'] ?? __('messages.pages.about.team_member_3_description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-muted py-16">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <h2 class="text-3xl font-bold">{{ $aboutPageContent['about_cta_title'] ?? __('messages.pages.about.cta_title') }}</h2>
            <p class="text-primary-foreground/90 mx-auto mt-4 max-w-2xl text-lg">
                {{ $aboutPageContent['about_cta_description'] ?? __('messages.pages.about.cta_description') }}
            </p>
            <div class="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                <a href="/vehicles"
                   class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    {{ __('messages.pages.about.explore_vehicles') }}
                </a>
                <a href="/contact"
                   class="inline-flex h-10 items-center justify-center rounded-md border border-primary px-8 text-sm font-medium bg-muted text-primary hover:bg-primary hover:text-primary-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    {{ __('messages.pages.about.contact_us') }}
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

