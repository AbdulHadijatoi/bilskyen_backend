@extends('layouts.app')

@section('title', __('messages.pages.vehicles.title') . ' | Bilskyen')

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8 px-4 sm:px-6">
    <!-- Search Bar -->
    <div id="search-bar-container" class="rounded-lg bg-card p-2 sm:p-3 shadow-sm w-full">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-none  focus:bg-none">
            <!-- Search Input -->
            <form class="flex w-full sm:flex-1 focus:bg-none bg-none min-w-0" id="search-form">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="search-input"
                        value="{{ $currentFilters['search'] ?? '' }}"
                        placeholder="{{ __('messages.forms.search_placeholder') }}"
                        class="flex h-10 w-full rounded-md pl-9 pr-2.5 py-1.5 text-sm placeholder:text-muted-foreground focus-visible:outline-none"
                        autocomplete="off"
                    />
                </div>
            </form>
            
        </div>
    </div>

    <!-- Mobile Filter Toggle Button -->
            <button 
        id="mobile-filter-toggle"
                type="button" 
        class="lg:hidden fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-black text-white shadow-xl border-2 border-white transition-all hover:bg-black/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
        aria-label="{{ __('messages.pages.vehicles.toggle_filters') }}"
            >
        <!-- Filter Icon (shown when sidebar is closed) — SlidersHorizontal -->
        <svg id="mobile-filter-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-[22px] w-[22px]">
            <line x1="21" x2="14" y1="4" y2="4"/>
            <line x1="10" x2="3" y1="4" y2="4"/>
            <line x1="21" x2="12" y1="12" y2="12"/>
            <line x1="8" x2="3" y1="12" y2="12"/>
            <line x1="21" x2="16" y1="20" y2="20"/>
            <line x1="12" x2="3" y1="20" y2="20"/>
            <line x1="14" x2="14" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="10" y2="14"/>
            <line x1="16" x2="16" y1="18" y2="22"/>
        </svg>
        <!-- Close Icon (shown when sidebar is open) -->
        <svg id="mobile-close-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-[22px] w-[22px] hidden">
            <path d="M18 6 6 18"/>
            <path d="m6 6 12 12"/>
        </svg>
            </button>

    <!-- Filters + Sort/View/Layout -->
    <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 items-start w-full">

        <style>
            #filter-sidebar details > summary { list-style: none; }
            #filter-sidebar details > summary::-webkit-details-marker { display: none; }
            #filter-sidebar details > summary::marker { display: none; }
            #filter-sidebar details[open] .filter-chevron { transform: rotate(180deg); }
            #filter-sidebar .filter-chevron { transition: transform 0.2s ease; }
            /* Expanded section: make heading stand out so content below is clearly tied to it */
            #filter-sidebar details[open] > summary {
                background: hsl(var(--muted) / 0.5);
                border-left: 3px solid hsl(var(--primary));
                font-weight: 700;
            }
            #filter-sidebar details[open] > summary span {
                color: hsl(var(--foreground));
                font-weight: 700;
            }
            #filter-sidebar details[open] > summary .filter-chevron {
                color: hsl(var(--foreground) / 0.8);
            }
        </style>

        <!-- Filter Sidebar -->
        <aside
            id="filter-sidebar"
            class="hidden lg:flex lg:flex-col fixed lg:relative inset-0 lg:inset-auto lg:sticky lg:top-4 z-40 lg:z-auto overflow-y-auto bg-background lg:bg-card shadow-lg lg:shadow-sm lg:rounded-lg w-full lg:w-72 xl:w-80 shrink-0 border-t border-border lg:border-t-0 lg:max-h-[calc(100vh-5rem)]"
        >
            @php $cf = $currentFilters ?? []; @endphp

            <!-- Sticky Header -->
            <div class="sticky top-0 z-10 bg-card flex flex-col shrink-0 border-b border-border">
                <!-- Pull bar (mobile): drag down or tap to close -->
                <div id="sidebar-pullbar" class="lg:hidden flex justify-center items-center py-3 touch-none cursor-grab active:cursor-grabbing select-none min-h-[44px]" aria-label="{{ __('messages.pages.vehicles.close_filters') }}">
                    <span class="w-10 h-1 rounded-full bg-muted-foreground/50"></span>
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-foreground">{{ __('messages.forms.filters') }}</span>
                </div>
                <button id="sidebar-reset-btn" type="button" class="hidden text-xs text-muted-foreground hover:text-destructive transition-colors focus-visible:outline-none">
                    {{ __('messages.pages.vehicles.reset_filters') }}
                </button>
            </div>

            <!-- Condition + Listing Type + Sales Type (always visible) -->
            <div class="px-4 py-3 space-y-3 border-b border-border shrink-0">
                <!-- Condition -->
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.condition') }}</p>
                    <div class="inline-flex items-center gap-0.5 p-1 rounded-full bg-muted border border-input flex-wrap">
                        <label class="condition-radio-label filter-pill inline-flex items-center px-3 py-1 rounded-full text-xs font-medium cursor-pointer transition-all border border-transparent @if(!isset($currentFilters['condition_id']) || $currentFilters['condition_id'] == '') bg-primary text-primary-foreground font-semibold @else bg-card text-muted-foreground hover:text-foreground border-input @endif">
                            <input type="radio" name="condition_id" value="" class="sr-only peer condition-radio" @if(!isset($currentFilters['condition_id']) || $currentFilters['condition_id'] == '') checked @endif>
                            <span>{{ __('messages.common.all') }}</span>
                        </label>
                        @foreach($constants['conditions'] as $condition)
                            <label class="condition-radio-label filter-pill inline-flex items-center px-3 py-1 rounded-full text-xs font-medium cursor-pointer transition-all border border-transparent @if(isset($currentFilters['condition_id']) && (string)($currentFilters['condition_id']) === (string)($condition['id'] ?? '')) bg-primary text-primary-foreground font-semibold @else bg-card text-muted-foreground hover:text-foreground border-input @endif">
                                <input type="radio" name="condition_id" value="{{ $condition['id'] }}" class="sr-only peer condition-radio" @if(isset($currentFilters['condition_id']) && $currentFilters['condition_id'] == $condition['id']) checked @endif>
                                <span>{{ $condition['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <!-- Listing Type -->
                @php
                    $listingTypesRaw = $constants['listing_types'] ?? [];
                    $listingTypesList = collect($listingTypesRaw)->map(function ($lt) {
                        return is_array($lt) ? $lt : ['id' => $lt->id, 'name' => $lt->name];
                    })->all();
                    $selectedListingTypes = isset($currentFilters['listing_type_id']) ? (is_array($currentFilters['listing_type_id']) ? $currentFilters['listing_type_id'] : [$currentFilters['listing_type_id']]) : [];
                    $selectedListingTypeStrings = collect($selectedListingTypes)->map(fn ($v) => (string) $v)->all();
                @endphp
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.listing_type') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($listingTypesList as $lt)
                            @php
                                $ltId = $lt['id'] ?? null;
                                $ltName = $lt['name'] ?? '';
                                $isListingTypeActive = $ltId !== null && in_array((string) $ltId, $selectedListingTypeStrings, true);
                            @endphp
                            @if($ltId !== null)
                                <label class="listing-type-checkbox-label filter-pill inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-colors border @if($isListingTypeActive) border-transparent bg-primary text-primary-foreground font-semibold shadow-sm @else border-border bg-muted/40 text-foreground/90 hover:bg-muted/70 hover:border-muted-foreground/30 @endif">
                                    <input type="checkbox" name="listing_type_id[]" value="{{ $ltId }}" class="sr-only peer listing-type-checkbox" @if($isListingTypeActive) checked @endif>
                                    <span class="listing-type-check-icon hidden peer-checked:inline-flex flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                                    <span>{{ $ltName }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
                <!-- Sales Type -->
                <div class="space-y-2">
                    <label class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.sales_type') }}</label>
                    <select name="sales_type_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <option value="">{{ __('messages.common.all') }}</option>
                        @foreach($constants['sales_types'] ?? [] as $st)
                        <option value="{{ is_array($st) ? $st['id'] : $st->id }}" @if(isset($cf['sales_type_id']) && (is_array($cf['sales_type_id']) ? in_array(is_array($st) ? $st['id'] : $st->id, $cf['sales_type_id']) : (is_array($st) ? $st['id'] : $st->id) == $cf['sales_type_id'])) selected @endif>{{ is_array($st) ? $st['name'] : $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Brand & Model (open by default) -->
            @php
                $selectedBrandIds = isset($cf['brand_id']) ? (is_array($cf['brand_id']) ? $cf['brand_id'] : [$cf['brand_id']]) : [];
                $selectedModelIds = isset($cf['model_id']) ? (is_array($cf['model_id']) ? $cf['model_id'] : [$cf['model_id']]) : [];
                $brandsList = $selectedBrands ?? [];
                $modelsList = $selectedModels ?? [];
                $selectedBrandNames = collect($brandsList)->filter(fn($b) => in_array(is_array($b) ? $b['id'] : $b->id, $selectedBrandIds))->map(fn($b) => is_array($b) ? $b['name'] : $b->name)->values()->all();
                $selectedModelNames = collect($modelsList)->filter(fn($m) => in_array(is_array($m) ? $m['id'] : $m->id, $selectedModelIds))->map(fn($m) => is_array($m) ? $m['name'] : $m->name)->values()->all();
                $brandDropdownSummary = count($selectedBrandNames) === 0
                    ? __('messages.common.all')
                    : (count($selectedBrandNames) === 1
                        ? $selectedBrandNames[0]
                        : implode(', ', array_slice($selectedBrandNames, 0, 3)) . (count($selectedBrandNames) > 3 ? '…' : ''));
            @endphp
            <details open class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.type_brand_model') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-2">
                    <div class="relative" data-multiselect-dropdown>
                        <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.brand') }}</label>
                        <button type="button" id="brand-dropdown-trigger" class="brand-dropdown-trigger w-full h-9 rounded-md border border-input bg-background px-3 py-1.5 text-sm text-left flex items-center justify-between gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="brand-dropdown-label">
                            <span id="brand-dropdown-label" class="truncate" title="{{ $brandDropdownSummary }}">
                                {{ $brandDropdownSummary }}
                            </span>
                            <svg class="flex-shrink-0 w-4 h-4 text-muted-foreground transition-transform dropdown-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                        </button>
                        <div id="brand-dropdown-panel" class="brand-dropdown-panel absolute left-0 right-0 top-full mt-1 z-50 hidden rounded-md border border-input bg-background shadow-lg max-h-56 overflow-y-auto">
                            <div class="p-2 border-b border-border">
                                <input
                                    type="text"
                                    id="brand-search-input"
                                    placeholder="{{ __('messages.forms.filter_brand_list') }}"
                                    class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    autocomplete="off"
                                >
                            </div>
                            <div id="brand-checkbox-list" class="p-2 space-y-0.5">
                                @foreach($selectedBrands ?? [] as $b)
                                    @php $bid = is_array($b) ? $b['id'] : $b->id; $bname = is_array($b) ? $b['name'] : $b->name; @endphp
                                    <label class="brand-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm">
                                        <input type="checkbox" name="brand_id[]" value="{{ $bid }}" class="brand-checkbox rounded border-input" checked>
                                        <span>{{ $bname }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="relative" data-multiselect-dropdown>
                        <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.model') }}</label>
                        <button type="button" id="model-dropdown-trigger" class="model-dropdown-trigger w-full h-9 rounded-md border border-input bg-background px-3 py-1.5 text-sm text-left flex items-center justify-between gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50 disabled:cursor-not-allowed" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="model-dropdown-label" @if(count($selectedBrandIds) === 0) disabled @endif>
                            <span id="model-dropdown-label" class="truncate">
                                @if(count($selectedModelNames) === 0){{ __('messages.common.all') }}@elseif(count($selectedModelNames) === 1){{ $selectedModelNames[0] }}@else{{ count($selectedModelNames) }} {{ __('messages.forms.selected') }}@endif
                            </span>
                            <svg class="flex-shrink-0 w-4 h-4 text-muted-foreground transition-transform dropdown-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                        </button>
                        <div id="model-dropdown-panel" class="model-dropdown-panel absolute left-0 right-0 top-full mt-1 z-50 hidden rounded-md border border-input bg-background shadow-lg max-h-56 overflow-y-auto">
                            <div class="p-2 border-b border-border">
                                <input
                                    type="text"
                                    id="model-search-input"
                                    placeholder="{{ __('messages.forms.filter_model_list') }}"
                                    class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    autocomplete="off"
                                >
                            </div>
                            <div id="model-checkbox-list" class="p-2 space-y-0.5">
                                @foreach($selectedModels ?? [] as $m)
                                    @php $mid = is_array($m) ? $m['id'] : $m->id; $mname = is_array($m) ? $m['name'] : $m->name; $mBrandId = is_array($m) ? ($m['brand_id'] ?? '') : $m->brand_id; @endphp
                                    <label class="model-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm" data-brand-id="{{ $mBrandId }}">
                                        <input type="checkbox" name="model_id[]" value="{{ $mid }}" class="model-checkbox rounded border-input" checked>
                                        <span class="model-checkbox-name">{{ $mname }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.gear_type') }}</label>
                        <select name="gear_type_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <option value="">{{ __('messages.common.all') }}</option>
                            @foreach($constants['gear_types'] ?? [] as $gt)
                            <option value="{{ is_array($gt) ? $gt['id'] : $gt->id }}" @if(isset($cf['gear_type_id']) && (is_array($cf['gear_type_id']) ? in_array(is_array($gt) ? $gt['id'] : $gt->id, $cf['gear_type_id']) : (is_array($gt) ? $gt['id'] : $gt->id) == $cf['gear_type_id'])) selected @endif>{{ is_array($gt) ? $gt['name'] : $gt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.model_year') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="year-from" name="model_year_from" placeholder="{{ __('messages.forms.from') }}" min="1950" max="2027" value="{{ $cf['model_year_from'] ?? $cf['year_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="year-to" name="model_year_to" placeholder="{{ __('messages.forms.to') }}" min="1950" max="2027" value="{{ $cf['model_year_to'] ?? $cf['year_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="year-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary transition-opacity"></div>
                            <input type="range" id="year-slider-min" min="1950" max="2027" step="1" value="1950" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="year-slider-max" min="1950" max="2027" step="1" value="2027" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="year-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="year-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                        <p id="year-slider-label" class="text-[10px] text-muted-foreground text-center tabular-nums"></p>
                    </div>
                </div>
            </details>

            <!-- Price & KM (open by default) -->
            <details open class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.price_range') }} / {{ __('messages.forms.km_driven') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.price_range') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="price-from" name="price_from" placeholder="{{ __('messages.forms.minimum') }}" min="0" max="{{ $filterPriceMax }}" value="{{ $cf['price_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="price-to" name="price_to" placeholder="{{ __('messages.forms.max') }}" min="0" max="{{ $filterPriceMax }}" value="{{ $cf['price_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="price-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary transition-opacity"></div>
                            <input type="range" id="price-slider-min" min="0" max="{{ $filterPriceMax }}" step="1000" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="price-slider-max" min="0" max="{{ $filterPriceMax }}" step="1000" value="{{ $filterPriceMax }}" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="price-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="price-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                        <p id="price-slider-label" class="text-[10px] text-muted-foreground text-center tabular-nums"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.km_driven') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="mileage-from" name="km_driven_from" placeholder="{{ __('messages.forms.min') }}" min="0" max="{{ $filterKmMax }}" value="{{ $cf['km_driven_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="mileage-to" name="km_driven_to" placeholder="{{ __('messages.forms.max') }}" min="0" max="{{ $filterKmMax }}" value="{{ $cf['km_driven_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="mileage-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary transition-opacity"></div>
                            <input type="range" id="mileage-slider-min" min="0" max="{{ $filterKmMax }}" step="1000" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="mileage-slider-max" min="0" max="{{ $filterKmMax }}" step="1000" value="{{ $filterKmMax }}" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="mileage-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="mileage-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                        <p id="mileage-slider-label" class="text-[10px] text-muted-foreground text-center tabular-nums"></p>
                    </div>
                </div>
            </details>

            <!-- Fuel & Body (collapsed by default) -->
            @php
                $selectedFuelTypeIds = isset($cf['fuel_type_id']) ? (is_array($cf['fuel_type_id']) ? $cf['fuel_type_id'] : [$cf['fuel_type_id']]) : [];
                $fuelTypesList = $constants['fuel_types'] ?? [];
                $selectedFuelTypeNames = collect($fuelTypesList)->filter(fn($ft) => in_array(is_array($ft) ? $ft['id'] : $ft->id, $selectedFuelTypeIds))->map(fn($ft) => is_array($ft) ? $ft['name'] : $ft->name)->values()->all();
            @endphp
            <details @if(isset($cf['fuel_type_id']) || isset($cf['body_type_id'])) open @endif class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.fuel_type') }} / {{ __('messages.forms.body_type') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="relative" data-multiselect-dropdown>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.fuel_type') }}</label>
                            <button type="button" id="fuel-type-dropdown-trigger" class="fuel-type-dropdown-trigger w-full h-9 rounded-md border border-input bg-background px-3 py-1.5 text-sm text-left flex items-center justify-between gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="fuel-type-dropdown-label">
                                <span id="fuel-type-dropdown-label" class="truncate">
                                    @if(count($selectedFuelTypeNames) === 0){{ __('messages.common.all') }}@elseif(count($selectedFuelTypeNames) === 1){{ $selectedFuelTypeNames[0] }}@else{{ count($selectedFuelTypeNames) }} {{ __('messages.forms.selected') }}@endif
                                </span>
                                <svg class="flex-shrink-0 w-4 h-4 text-muted-foreground transition-transform dropdown-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                            </button>
                            <div id="fuel-type-dropdown-panel" class="fuel-type-dropdown-panel absolute left-0 right-0 top-full mt-1 z-50 hidden rounded-md border border-input bg-background shadow-lg max-h-56 overflow-y-auto">
                                <div id="fuel-type-checkbox-list" class="p-2 space-y-0.5">
                                    @foreach($constants['fuel_types'] ?? [] as $ft)
                                    @php $ftid = is_array($ft) ? $ft['id'] : $ft->id; $ftname = is_array($ft) ? $ft['name'] : $ft->name; @endphp
                                    <label class="fuel-type-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm">
                                        <input type="checkbox" name="fuel_type_id[]" value="{{ $ftid }}" class="fuel-type-checkbox rounded border-input" @if(in_array($ftid, $selectedFuelTypeIds)) checked @endif>
                                        <span class="fuel-type-checkbox-name">{{ $ftname }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.body_type') }}</label>
                            <select name="body_type_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.common.all') }}</option>
                                @foreach($constants['body_types'] ?? [] as $bt)
                                <option value="{{ is_array($bt) ? $bt['id'] : $bt->id }}" @if(isset($cf['body_type_id']) && (is_array($cf['body_type_id']) ? in_array(is_array($bt) ? $bt['id'] : $bt->id, $cf['body_type_id']) : (is_array($bt) ? $bt['id'] : $bt->id) == $cf['body_type_id'])) selected @endif>{{ is_array($bt) ? $bt['name'] : $bt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </details>

            <!-- More Details: Color, Variant, Type, Price type, Euronom, Use (collapsed, 2-col grid) -->
            @php
                $moreDetailsOpen = isset($cf['color_id']) || isset($cf['price_type_id']) || isset($cf['emission_norm_id']) || isset($cf['use_id']);
            @endphp
            <details @if($moreDetailsOpen) open @endif class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.color') }} / {{ __('messages.forms.type') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3">
                    <div class="grid grid-cols-2 gap-x-2 gap-y-2">
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.color') }}</label>
                            <select name="color_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.common.all') }}</option>
                                @foreach($constants['colors'] ?? [] as $cl)
                                <option value="{{ is_array($cl) ? $cl['id'] : $cl->id }}" @if(isset($cf['color_id']) && (is_array($cl) ? $cl['id'] : $cl->id) == $cf['color_id']) selected @endif>{{ is_array($cl) ? $cl['name'] : $cl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.price_type') }}</label>
                            <select name="price_type_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.common.all') }}</option>
                                @foreach($constants['price_types'] ?? [] as $pt)
                                <option value="{{ is_array($pt) ? $pt['id'] : $pt->id }}" @if(isset($cf['price_type_id']) && (is_array($cf['price_type_id']) ? in_array(is_array($pt) ? $pt['id'] : $pt->id, $cf['price_type_id']) : (is_array($pt) ? $pt['id'] : $pt->id) == $cf['price_type_id'])) selected @endif>{{ is_array($pt) ? $pt['name'] : $pt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.euro_norm') }}</label>
                            <select name="emission_norm_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.common.all') }}</option>
                                @foreach($constants['euronorms'] ?? [] as $en)
                                <option value="{{ is_array($en) ? $en['id'] : $en->id }}" @if(isset($cf['emission_norm_id']) && (is_array($en) ? $en['id'] : $en->id) == $cf['emission_norm_id']) selected @endif>{{ is_array($en) ? $en['name'] : $en->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.use') }}</label>
                            <select name="use_id" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.common.all') }}</option>
                                @foreach($constants['vehicle_uses'] ?? [] as $uu)
                                <option value="{{ is_array($uu) ? $uu['id'] : $uu->id }}" @if(isset($cf['use_id']) && (is_array($uu) ? $uu['id'] : $uu->id) == $cf['use_id']) selected @endif>{{ is_array($uu) ? $uu['name'] : $uu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </details>

            <!-- Year & Specs: first reg, owner tax, HP, battery, range, fuel efficiency (collapsed) -->
            @php
                $yearSpecsOpen = isset($cf['first_registration_year_from']) || isset($cf['first_registration_year_to']) || isset($cf['ownership_tax_from']) || isset($cf['ownership_tax_to']) || isset($cf['engine_power_kw_from']) || isset($cf['engine_power_kw_to']) || isset($cf['electrical_consumption_from']) || isset($cf['electrical_consumption_to']) || isset($cf['km_per_liter_from']) || isset($cf['km_per_liter_to']);
            @endphp
            <details @if($yearSpecsOpen) open @endif class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.first_registration_year') }} / {{ __('messages.forms.horsepower_hp') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.first_registration_year') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="first-reg-year-from" name="first_registration_year_from" placeholder="{{ __('messages.forms.from') }}" min="1950" max="2027" value="{{ $cf['first_registration_year_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="first-reg-year-to" name="first_registration_year_to" placeholder="{{ __('messages.forms.to') }}" min="1950" max="2027" value="{{ $cf['first_registration_year_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="first-reg-year-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="first-reg-year-slider-min" min="1950" max="2027" step="1" value="1950" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="first-reg-year-slider-max" min="1950" max="2027" step="1" value="2027" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="first-reg-year-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="first-reg-year-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.owner_tax') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="owner-tax-from" name="ownership_tax_from" placeholder="{{ __('messages.forms.min') }}" min="0" max="20000" value="{{ $cf['ownership_tax_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="owner-tax-to" name="ownership_tax_to" placeholder="{{ __('messages.forms.max') }}" min="0" max="20000" value="{{ $cf['ownership_tax_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="owner-tax-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="owner-tax-slider-min" min="0" max="20000" step="100" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="owner-tax-slider-max" min="0" max="20000" step="100" value="20000" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="owner-tax-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="owner-tax-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.horsepower_hp') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="horsepower-min" name="engine_power_kw_from" placeholder="{{ __('messages.forms.min') }}" min="0" value="{{ $cf['engine_power_kw_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="horsepower-max" name="engine_power_kw_to" placeholder="{{ __('messages.forms.max') }}" min="0" value="{{ $cf['engine_power_kw_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="horsepower-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="horsepower-slider-min" min="0" max="1000" step="10" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="horsepower-slider-max" min="0" max="1000" step="10" value="1000" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="horsepower-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="horsepower-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.battery_capacity_kwh') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="battery-capacity-min" name="electrical_consumption_from" placeholder="{{ __('messages.forms.min') }}" min="0" value="{{ $cf['electrical_consumption_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="battery-capacity-max" name="electrical_consumption_to" placeholder="{{ __('messages.forms.max') }}" min="0" value="{{ $cf['electrical_consumption_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="battery-capacity-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="battery-capacity-slider-min" min="0" max="500" step="5" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="battery-capacity-slider-max" min="0" max="500" step="5" value="500" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="battery-capacity-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="battery-capacity-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.fuel_efficiency') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="fuel-efficiency-from" name="km_per_liter_from" placeholder="{{ __('messages.forms.min') }}" min="0" value="{{ $cf['km_per_liter_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="fuel-efficiency-to" name="km_per_liter_to" placeholder="{{ __('messages.forms.max') }}" min="0" value="{{ $cf['km_per_liter_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="fuel-efficiency-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="fuel-efficiency-slider-min" min="0" max="100" step="0.5" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="fuel-efficiency-slider-max" min="0" max="100" step="0.5" value="100" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="fuel-efficiency-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="fuel-efficiency-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                </div>
            </details>

            <!-- Physical Details: top speed, weight, single inputs, drive wheels (collapsed) -->
            @php
                $physicalOpen = isset($cf['max_speed_from']) || isset($cf['max_speed_to']) || isset($cf['maximum_weight_kg_from']) || isset($cf['maximum_weight_kg_to']) || isset($cf['door_count']) || isset($cf['seats_min']) || isset($cf['seats_max']) || isset($cf['axle_count']) || isset($cf['specifications_airbags']) || isset($cf['towing_weight']);
            @endphp
            <details @if($physicalOpen) open @endif class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.physical_details') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.top_speed') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="top-speed-from" name="max_speed_from" placeholder="{{ __('messages.forms.from') }}" min="0" value="{{ $cf['max_speed_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="top-speed-to" name="max_speed_to" placeholder="{{ __('messages.forms.to') }}" min="0" value="{{ $cf['max_speed_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="top-speed-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="top-speed-slider-min" min="0" max="400" step="5" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="top-speed-slider-max" min="0" max="400" step="5" value="400" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="top-speed-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="top-speed-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-medium text-muted-foreground">{{ __('messages.forms.weight') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" id="weight-from" name="maximum_weight_kg_from" placeholder="{{ __('messages.forms.from') }}" min="0" value="{{ $cf['maximum_weight_kg_from'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                            <input type="number" id="weight-to" name="maximum_weight_kg_to" placeholder="{{ __('messages.forms.to') }}" min="0" value="{{ $cf['maximum_weight_kg_to'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div class="relative h-6"><div class="slider-track-area relative h-full mx-3">
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-muted"></div>
                            <div id="weight-range-track" class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"></div>
                            <input type="range" id="weight-slider-min" min="0" max="5000" step="50" value="0" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <input type="range" id="weight-slider-max" min="0" max="5000" step="50" value="5000" class="absolute left-0 right-0 top-1/2 h-4 w-full -translate-y-1/2 opacity-0 cursor-pointer z-10">
                            <div id="weight-handle-min" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                            <div id="weight-handle-max" class="absolute w-5 h-5 rounded-full border-2 border-primary bg-background shadow pointer-events-auto z-20 cursor-grab active:cursor-grabbing" style="top:50%;transform:translateY(-50%);"></div>
                        </div></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.doors') }}</label>
                            <input type="number" name="door_count" placeholder="{{ __('messages.forms.minimum') }}" min="0" value="{{ $cf['door_count'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.seats_min') }}</label>
                            <input type="number" name="seats_min" placeholder="{{ __('messages.forms.min') }}" min="0" value="{{ $cf['seats_min'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.seats_max') }}</label>
                            <input type="number" name="seats_max" placeholder="{{ __('messages.forms.max') }}" min="0" value="{{ $cf['seats_max'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.axles') }}</label>
                            <input type="number" name="axle_count" placeholder="{{ __('messages.forms.minimum') }}" min="0" value="{{ $cf['axle_count'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.airbags') }}</label>
                            <input type="number" name="specifications_airbags" placeholder="{{ __('messages.forms.minimum') }}" min="0" value="{{ $cf['specifications_airbags'] ?? '' }}" class="h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-primary">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-medium text-muted-foreground block mb-1">{{ __('messages.forms.towing_weight_min') }}</label>
                        <input type="number" name="towing_weight" placeholder="{{ __('messages.forms.minimum') }}" min="0" value="{{ $cf['towing_weight'] ?? '' }}" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    </div>
                </div>
            </details>

            <!-- Charging, NCAP, Import, Factory New (collapsed) -->
            @php
                $extrasOpen = isset($cf['charging_type']) || !empty($cf['ncap_test']) || !empty($cf['is_import']) || !empty($cf['is_factory_new']);
            @endphp
            <details @if($extrasOpen) open @endif class="border-b border-border">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.charging_type') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-2">
                    <select name="charging_type" class="w-full h-9 rounded-md border border-input bg-background px-3 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <option value="">{{ __('messages.common.all') }}</option>
                        <option value="AC" @if(isset($cf['charging_type']) && $cf['charging_type'] == 'AC') selected @endif>{{ __('messages.forms.charging_ac') }}</option>
                        <option value="DC" @if(isset($cf['charging_type']) && $cf['charging_type'] == 'DC') selected @endif>{{ __('messages.forms.charging_dc') }}</option>
                        <option value="AC/DC" @if(isset($cf['charging_type']) && $cf['charging_type'] == 'AC/DC') selected @endif>{{ __('messages.forms.charging_ac_dc') }}</option>
                    </select>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <label class="filter-pill-checkbox inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all border border-input bg-card text-muted-foreground hover:text-foreground has-[:checked]:bg-primary has-[:checked]:text-primary-foreground has-[:checked]:border-primary">
                            <input type="checkbox" name="ncap_test" value="1" class="sr-only peer" @if(isset($cf['ncap_test']) && $cf['ncap_test']) checked @endif>
                            <span class="hidden peer-checked:inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                            <span>{{ __('messages.forms.ncap_test') }}</span>
                        </label>
                        <label class="filter-pill-checkbox inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all border border-input bg-card text-muted-foreground hover:text-foreground has-[:checked]:bg-primary has-[:checked]:text-primary-foreground has-[:checked]:border-primary">
                            <input type="checkbox" name="is_import" value="1" class="sr-only peer" @if(isset($cf['is_import']) && $cf['is_import']) checked @endif>
                            <span class="hidden peer-checked:inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                            <span>{{ __('messages.forms.is_import') }}</span>
                        </label>
                        <label class="filter-pill-checkbox inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all border border-input bg-card text-muted-foreground hover:text-foreground has-[:checked]:bg-primary has-[:checked]:text-primary-foreground has-[:checked]:border-primary">
                            <input type="checkbox" name="is_factory_new" value="1" class="sr-only peer" @if(isset($cf['is_factory_new']) && $cf['is_factory_new']) checked @endif>
                            <span class="hidden peer-checked:inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                            <span>{{ __('messages.forms.is_factory_new') }}</span>
                        </label>
                    </div>
                </div>
            </details>

            <!-- Equipment (collapsed, with nested sub-accordion) -->
            @php
                $equipmentActive = !empty($cf['equipment_ids']);
                $equipmentTypes = $constants['equipment_types'] ?? [];
                $equipmentsList = $constants['equipments'] ?? [];
                $equipmentsByType = [];
                foreach ($equipmentsList as $eq) {
                    $eid = is_array($eq) ? $eq['id'] : $eq->id;
                    $ename = is_array($eq) ? $eq['name'] : $eq->name;
                    $typeId = is_array($eq) ? ($eq['equipment_type_id'] ?? null) : ($eq->equipment_type_id ?? null);
                    if (!isset($equipmentsByType[$typeId])) $equipmentsByType[$typeId] = [];
                    $equipmentsByType[$typeId][] = ['id' => $eid, 'name' => $ename];
                }
            @endphp
            <details @if($equipmentActive) open @endif class="last:border-0">
                <summary class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-muted/40 transition-colors select-none">
                    <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{{ __('messages.forms.equipment') }}</span>
                    <svg class="filter-chevron w-4 h-4 text-muted-foreground flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-3 space-y-1">
                    @foreach($equipmentTypes as $et)
                        @php
                            $typeId = is_array($et) ? $et['id'] : $et->id;
                            $typeName = is_array($et) ? $et['name'] : $et->name;
                            $items = $equipmentsByType[$typeId] ?? [];
                        @endphp
                        @if(count($items) > 0)
                            <div class="equipment-type-group border-b border-border pb-2 last:border-0">
                                <button type="button" class="equipment-type-toggle w-full flex items-center justify-between gap-2 py-1.5 text-left text-[10px] font-semibold text-muted-foreground uppercase tracking-wide hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded">
                                    <span>{{ $typeName }}</span>
                                    <svg class="equipment-type-icon w-3.5 h-3.5 flex-shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div class="equipment-type-content hidden flex flex-wrap gap-1.5 mt-1.5 pl-4">
                                    @foreach($items as $eq)
                                        @php $checked = isset($cf['equipment_ids']) && is_array($cf['equipment_ids']) && in_array($eq['id'], $cf['equipment_ids']); @endphp
                                        <label class="equipment-btn inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium cursor-pointer transition-all border border-input bg-card text-muted-foreground hover:text-foreground has-[:checked]:bg-primary has-[:checked]:text-primary-foreground has-[:checked]:border-primary">
                                            <input type="checkbox" name="equipment_ids[]" value="{{ $eq['id'] }}" class="sr-only peer" @if($checked) checked @endif>
                                            <span class="equipment-check-icon hidden peer-checked:inline-flex flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                                            <span>{{ $eq['name'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @php $otherItems = array_merge($equipmentsByType[null] ?? [], $equipmentsByType[''] ?? []); @endphp
                    @if(count($otherItems) > 0)
                        <div class="equipment-type-group border-b border-border pb-2 last:border-0">
                            <button type="button" class="equipment-type-toggle w-full flex items-center justify-between gap-2 py-1.5 text-left text-[10px] font-semibold text-muted-foreground uppercase tracking-wide hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded">
                                <span>{{ __('messages.pages.sell_your_car.equipment_other') }}</span>
                                <svg class="equipment-type-icon w-3.5 h-3.5 flex-shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                            </button>
                            <div class="equipment-type-content hidden flex flex-wrap gap-1.5 mt-1.5 pl-4">
                                @foreach($otherItems as $eq)
                                    @php $checked = isset($cf['equipment_ids']) && is_array($cf['equipment_ids']) && in_array($eq['id'], $cf['equipment_ids']); @endphp
                                    <label class="equipment-btn inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium cursor-pointer transition-all border border-input bg-card text-muted-foreground hover:text-foreground has-[:checked]:bg-primary has-[:checked]:text-primary-foreground has-[:checked]:border-primary">
                                        <input type="checkbox" name="equipment_ids[]" value="{{ $eq['id'] }}" class="sr-only peer" @if($checked) checked @endif>
                                        <span class="equipment-check-icon hidden peer-checked:inline-flex flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                                        <span>{{ $eq['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </details>
        </aside>

        <!-- Sort, view toggle and results -->
        <div class="flex-1 flex flex-col gap-4 w-full">
            <!-- Results count, filter chips, and reset button -->
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3 flex-wrap w-full">
                        <!-- Applied Filters Chips -->
                        <div id="applied-filters-container" class="flex flex-wrap gap-2 w-full min-w-0">
                            <!-- Filter chips will be rendered here via JavaScript -->
                    <!-- Reset Button (only visible when filters are applied) -->
                    <button
                        id="filter-reset-button-main"
                        type="button"
                        class="hidden inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1.5 text-xs text-foreground transition-colors hover:bg-muted/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        {{ __('messages.pages.vehicles.reset_filters') }}
                    </button>
                        </div>
                    </div>
            </div>
        </div>

            <div id="sort-and-condition-controls" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 w-full">
            <div class="text-xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2 min-w-0 w-full sm:w-auto">
                <div class="flex items-center gap-2 flex-shrink-0">
                    <p id="results-count" class="text-xs text-foreground whitespace-nowrap">
                        <strong>{{ number_format($vehicles->total()) }}</strong> 
                        {{ __('messages.forms.results') }}
                    </p>
                </div>
                <!-- Sort Dropdown Container -->
                <div class="relative text-xs font-medium">
                    <select
                        id="sort-select"
                        name="sort"
                        class="appearance-none bg-transparent border border-input rounded-md text-xs text-foreground font-medium px-3 py-1.5 pr-8 cursor-pointer focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full sm:w-auto min-w-[180px] sm:min-w-[200px]"
                    >
                        @foreach($vehicleSortLabels as $value => $label)
                            <option value="{{ $value }}" @if(\App\Services\VehicleService::listingSortOptionIsSelected($value, $rawSortQuery ?? null)) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-foreground pointer-events-none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>
            
            <!-- Sort and View Toggle -->
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Sort Dropdown -->
            <div class="relative text-xs font-medium">
                
                
                <!-- View Toggle Buttons -->
                <div class="hidden sm:inline-flex items-center gap-1 p-1 rounded-full bg-muted border border-input">
                    <label class="view-toggle-label inline-flex items-center px-3 py-1 rounded-full text-xs cursor-pointer transition-all view-card-label bg-primary text-primary-foreground font-semibold">
                        <input 
                            type="radio" 
                            name="view-toggle" 
                            value="card"
                            class="sr-only peer view-toggle-radio"
                            checked
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                    <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                    <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                    <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                </svg>
                    </label>
                    <label class="view-toggle-label inline-flex items-center px-3 py-1 rounded-full text-xs cursor-pointer transition-all view-list-label bg-card text-muted-foreground hover:text-foreground border border-transparent border-input">
                        <input 
                            type="radio" 
                            name="view-toggle" 
                            value="list"
                            class="sr-only peer view-toggle-radio"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <line x1="8" x2="21" y1="6" y2="6"></line>
                    <line x1="8" x2="21" y1="12" y2="12"></line>
                    <line x1="8" x2="21" y1="18" y2="18"></line>
                    <line x1="3" x2="3.01" y1="6" y2="6"></line>
                    <line x1="3" x2="3.01" y1="12" y2="12"></line>
                    <line x1="3" x2="3.01" y1="18" y2="18"></line>
                </svg>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Vehicle Grid/List -->
    <div id="no-results-message" class="hidden col-span-full py-6 text-center rounded-lg bg-muted/50 border border-border space-y-3">
        <h3 class="text-lg font-semibold">{{ __('messages.forms.no_vehicles_found') }}</h3>
        <p class="text-muted-foreground text-sm">{{ __('messages.forms.try_adjusting_filters') }}</p>
        <button type="button" id="no-results-reset-filters" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            {{ __('messages.pages.vehicles.reset_filters') }}
        </button>
    </div>
    <div id="vehicle-container" class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3" data-view="card">
        @forelse($vehicles as $row)
            <x-vehicle-listing-item
                :vehicle="$row['vehicle']"
                :img-url="$row['imgUrl']"
                :img-alt="$row['imgAlt']"
                :sales-type-name="$row['salesTypeName']"
                :trust-badge="$row['trustBadge'] ?? false"
                :price-dropped-recently="$row['priceDroppedRecently'] ?? false"
                :fair-price-label="$row['fairPriceLabel'] ?? null"
                :premium-dealer-badge="$row['premiumDealerBadge'] ?? false"
                :is-boosted="$row['isBoosted'] ?? false"
            />
        @empty
        <div class="col-span-full space-y-6">
            <div class="flex flex-col items-center justify-center text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-6 w-6 text-muted-foreground">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <h3 class="text-lg font-semibold">{{ __('messages.forms.no_vehicles_found') }}</h3>
                <p class="text-muted-foreground mt-1">
                    {{ __('messages.forms.try_adjusting_filters') }}
                </p>
                <button type="button" onclick="document.getElementById('filter-reset-button-main')?.click()" class="mt-4 inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    {{ __('messages.pages.vehicles.reset_filters') }}
                </button>
            </div>
            @if(isset($showNoResultsMessage) && $showNoResultsMessage && isset($fallbackVehicles) && $fallbackVehicles->count() > 0)
            <div class="pt-4 border-t border-border">
                <h4 class="text-sm font-semibold text-foreground mb-4">{{ __('messages.pages.vehicles.showing_all_vehicles') }}</h4>
                <div class="vehicle-fallback-grid grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3">
                @foreach($fallbackVehicles as $row)
                    <x-vehicle-listing-item
                        :vehicle="$row['vehicle']"
                        :img-url="$row['imgUrl']"
                        :img-alt="$row['imgAlt']"
                        :sales-type-name="$row['salesTypeName']"
                        :trust-badge="$row['trustBadge'] ?? false"
                        :price-dropped-recently="$row['priceDroppedRecently'] ?? false"
                        :fair-price-label="$row['fairPriceLabel'] ?? null"
                        :premium-dealer-badge="$row['premiumDealerBadge'] ?? false"
                        :is-boosted="$row['isBoosted'] ?? false"
                    />
                @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforelse
    </div>

    <!-- Enquiry Dialogs for Vehicles -->
    @php $vehiclesForDialogs = (isset($showNoResultsMessage) && $showNoResultsMessage && isset($fallbackVehicles) && $fallbackVehicles->count() > 0) ? $fallbackVehicles : $vehicles; @endphp
    @foreach($vehiclesForDialogs as $row)
        <x-enquiry-dialog type="enquiry" :vehicle="$row['vehicle']" />
    @endforeach

    <!-- Login Dialog -->
    <x-login-dialog />

    <!-- Pagination -->
    <div id="pagination-container" class="mt-8 flex items-center justify-center gap-2">
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            {{ __('messages.common.previous') }}
        </button>
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            1
        </button>
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            {{ __('messages.common.next') }}
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </button>
    </div>
</div>


@push('styles')
<style>
    /* List view styles - Compact design matching card view styles */
    #vehicle-container[data-view="list"] {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        grid-template-columns: 1fr;
    }
    
    #vehicle-container[data-view="list"] .vehicle-item {
        display: flex;
        flex-direction: row;
        width: 100%;
        border: 1px solid hsl(var(--border));
        overflow: hidden;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    #vehicle-container[data-view="list"] .vehicle-fallback-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        width: 100%;
    }

    #vehicle-container[data-view="card"] .vehicle-fallback-grid {
        display: grid;
    }
    
    
    #vehicle-container[data-view="list"] .vehicle-dealer-label {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        z-index: 20;
        width: fit-content;
        pointer-events: none;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container {
        flex-shrink: 0;
        width: 200px;
        min-width: 200px;
        height: 150px;
        padding: 0;
        overflow: hidden;
        background-color: hsl(var(--muted) / 0.3);
        display: block;
        position: relative;
        aspect-ratio: auto;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        border-radius: 0.375rem;
    }
    
    #vehicle-container[data-view="list"] .vehicle-item > a {
        display: flex;
        flex-direction: row;
        flex: 1;
        min-width: 0;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1rem;
        gap: 1rem;
        position: relative;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper h3 {
        font-size: 1.125rem;
        font-weight: 700;
        line-height: 1.3;
        margin: 0;
        color: hsl(var(--foreground));
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper .text-muted-foreground {
        font-size: 0.75rem;
        color: hsl(var(--muted-foreground));
        margin-top: -0.375rem;
        font-weight: 400;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper .vehicle-listing-price {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
        color: hsl(var(--primary));
    }
    
    #vehicle-container[data-view="list"] .vehicle-item-footer {
        margin-top: auto;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 1rem;
        padding-top: 0.5rem;
        min-width: 0;
    }
    
    #vehicle-container[data-view="list"] .vehicle-actions-section {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    #vehicle-container[data-view="list"] .vehicle-actions-section button,
    #vehicle-container[data-view="list"] .vehicle-actions-section a button {
        height: 2.25rem;
        padding: 0 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container .absolute {
        top: 0.5rem;
        right: 0.5rem;
        z-index: 10;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container .absolute.top-2.left-2 {
        top: 0.5rem;
        left: 0.5rem;
    }
    
    /* Tablet and up */
    @media (min-width: 768px) {
        #vehicle-container[data-view="list"] .vehicle-image-container {
            width: 240px;
            min-width: 240px;
            height: 180px;
        }
    }
    
    /* Large screens */
    @media (min-width: 1024px) {
        #vehicle-container[data-view="list"] .vehicle-image-container {
            width: 280px;
            min-width: 280px;
            height: 200px;
        }
    }
    
    /* Mobile optimizations */
    @media (max-width: 640px) {
        #vehicle-container[data-view="list"] {
            gap: 0.75rem;
        }
        
        #vehicle-container[data-view="list"] .vehicle-item {
            flex-direction: column;
        }
        
        #vehicle-container[data-view="list"] .vehicle-item > a {
            flex-direction: column;
        }
        
        #vehicle-container[data-view="list"] .vehicle-image-container {
            width: 100%;
            min-width: 100%;
            height: 200px;
        }
        
        #vehicle-container[data-view="list"] .vehicle-content-wrapper {
            padding: 1rem;
        }
        
        #vehicle-container[data-view="list"] .vehicle-item-footer {
            padding: 1rem;
            padding-top: 0.5rem;
        }
        
        #vehicle-container[data-view="list"] .vehicle-actions-section {
            flex-direction: column;
            width: 100%;
        }
        
        #vehicle-container[data-view="list"] .vehicle-actions-section button,
        #vehicle-container[data-view="list"] .vehicle-actions-section a button {
            width: 100%;
        }
    }
    
    /* Filter pill-style tabs (condition, listing type, view) - primary + neutral */
    .condition-radio-label,
    .listing-type-checkbox-label,
    .view-toggle-label {
        transition: background-color 0.15s, color 0.15s;
    }
    
    .condition-radio-label.bg-primary,
    .listing-type-checkbox-label.bg-primary,
    .view-toggle-label.bg-primary {
        background-color: var(--primary) !important;
        color: var(--primary-foreground) !important;
        font-weight: 600 !important;
    }
    
    .condition-radio-label.bg-card,
    .listing-type-checkbox-label.bg-card,
    .view-toggle-label.bg-card {
        background-color: var(--card) !important;
        color: hsl(var(--muted-foreground)) !important;
    }
    
    .condition-radio-label.bg-card:hover,
    .listing-type-checkbox-label.bg-card:hover,
    .view-toggle-label.bg-card:hover {
        color: hsl(var(--foreground)) !important;
    }
    
    /* Condition filter responsive styles */
    @media (max-width: 768px) {
        #sort-and-condition-controls {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
        }
        
        #sort-and-condition-controls > div:first-child {
            width: 100%;
            flex-wrap: wrap;
        }
        
        #sort-and-condition-controls > div:first-child > div {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        
        #sort-and-condition-controls > div:first-child > label {
            margin-bottom: 0.5rem;
        }
    }
    
    @media (max-width: 640px) {
        #sort-and-condition-controls {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
        }
        
        #sort-and-condition-controls > div:first-child {
            width: 100%;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        #sort-and-condition-controls > div:last-child {
            width: 100%;
            justify-content: flex-end;
        }
        
        /* Results count mobile styles */
        #results-count {
            flex-shrink: 0;
        }
        
        /* Mobile layout alignment fixes */
        #search-bar-container {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }
        
        #vehicle-container {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }
        
        /* Ensure consistent padding in mobile */
        .container.mx-auto {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function() {
        // Constants
        const vehicleContainer = document.getElementById('vehicle-container');
        const vehicleGrid = vehicleContainer; // Keep for backward compatibility
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        const sortSelect = document.getElementById('sort-select');
        const DEFAULT_LISTING_SORT = 'standard';
        const filterSidebar = document.getElementById('filter-sidebar');
        const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
        const filterResetButtonMain = document.getElementById('filter-reset-button-main');
        const viewToggleRadios = document.querySelectorAll('input[name="view-toggle"]');
        
        // View state
        let currentView = localStorage.getItem('vehicleView') || 'card';
        
        // Check if device is mobile (screen width <= 640px)
        function isMobile() {
            return window.innerWidth <= 640;
        }
        
        // Force card view on mobile
        if (isMobile()) {
            currentView = 'card';
            localStorage.setItem('vehicleView', 'card');
        }
        
        const sortLabels = @json($vehicleSortLabels);
        const I18N_BMV = {
            selectBrandForModels: @json(__('messages.forms.select_brand_for_models')),
            all: @json(__('messages.common.all')),
            from: @json(__('messages.forms.from')),
            to: @json(__('messages.forms.to')),
            chipsMore: @json(__('messages.forms.chips_more')),
            chipsShowLess: @json(__('messages.forms.chips_show_less')),
            resetFilters: @json(__('messages.pages.vehicles.reset_filters')),
        };
        const CHIP_COLLAPSE_LIMIT = 5;
        
        let searchDebounceTimer = null;
        let isLoading = false;
        
        // Format currency helper (matches PHP FormatHelper)
        function formatCurrency(amount) {
            if (amount === null || amount === undefined) {
                return 'N/A';
            }
            return new Intl.NumberFormat('da-DK', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(amount) + ' kr.';
        }

        const listingLocale = document.documentElement.lang || '{{ app()->getLocale() }}' || 'da';

        function formatListingTitle(title) {
            if (!title) return '';
            return String(title).toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
        }

        function formatMonthYear(dateStr) {
            if (!dateStr) return '';
            try {
                return new Date(dateStr).toLocaleDateString(listingLocale, { month: 'short', year: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        }

        function formatListingLocation(vehicle) {
            const parts = [];
            if (vehicle.seller_postcode) parts.push(String(vehicle.seller_postcode).trim());
            if (vehicle.seller_city) {
                parts.push(String(vehicle.seller_city).trim());
            } else if (vehicle.city) {
                parts.push(String(vehicle.city).trim());
            } else if (vehicle.seller_address) {
                parts.push(String(vehicle.seller_address).trim());
            }
            return parts.filter(Boolean).join(' ');
        }
        
        // Single listing tile (card + list layouts differ only via #vehicle-container[data-view] CSS)
        function renderVehicleItem(vehicle) {
            const imageUrl = vehicle.thumbnail_url || vehicle.image_url || '/placeholder-vehicle.jpg';
            const details = vehicle.details || {};
            const slug = vehicle.slug || vehicle.id;
            const salesTypeLabel = (details.sales_type_name || details.salesTypeName || vehicle.sales_type_name || vehicle.salesTypeName || '').trim();
            const titleText = formatListingTitle(vehicle.title || '');
            const locationText = formatListingLocation(vehicle);

            return `
                <div class="vehicle-item flex flex-col rounded-2xl bg-card overflow-hidden p-0 cursor-pointer h-full w-full min-w-0">
                    <a href="/vehicles/${slug}" class="vehicle-item-main-link block flex-1 min-w-0">
                        <div class="vehicle-image-container relative aspect-[2/1.5] overflow-hidden p-3 pb-0">
                            <img
                                src="${imageUrl}"
                                alt="${titleText}"
                                class="h-full w-full object-cover rounded-md vehicle-listing-thumb"
                            />
                            <div class="absolute top-4 left-4 z-10 flex flex-row flex-wrap items-center gap-1.5">
                            ${vehicle.dealer_id ? `
                            <span class="inline-flex items-center rounded-md bg-blue-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                {{ __('messages.pages.vehicles.dealer') }}
                            </span>
                            ` : `
                            <span class="inline-flex items-center rounded-md bg-orange-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                {{ __('messages.pages.vehicles.private') }}
                            </span>
                            `}
                            ${salesTypeLabel ? `
                            <span class="inline-flex items-center rounded-md bg-green-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                ${salesTypeLabel}
                            </span>
                            ` : ''}
                            ${vehicle.premium_dealer_badge ? `
                            <span class="inline-flex items-center rounded-md bg-violet-600/80 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                {{ __('messages.pages.vehicles.premium_badge') }}
                            </span>
                            ` : ''}
                            ${vehicle.is_boosted ? `
                            <span class="inline-flex items-center rounded-md bg-amber-500/90 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                {{ __('messages.pages.vehicles.boosted_badge') }}
                            </span>
                            ` : ''}
                            ${vehicle.trust_badge ? `
                            <span class="inline-flex items-center rounded-md bg-emerald-600/80 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                {{ __('messages.pages.vehicles.detail.trust_verified_badge') }}
                            </span>
                            ` : ''}
                            ${vehicle.price_dropped_recently ? `
                            <span class="inline-flex items-center rounded-md bg-rose-600/80 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                {{ __('messages.pages.vehicles.detail.price_dropped_badge') }}
                            </span>
                            ` : ''}
                            ${vehicle.fair_price_label === 'below_market' ? `
                            <span class="inline-flex items-center rounded-md bg-sky-600/80 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                {{ __('messages.pages.vehicles.detail.fair_price_below_market') }}
                            </span>
                            ` : ''}
                            </div>
                            <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring dark:bg-card/90" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(${vehicle.id}, event); return false;" aria-label="{{ __('messages.forms.add_to_favorites') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 ${vehicle.dealer_id ? 'text-primary' : 'text-foreground'} hover:opacity-80 transition-colors heart-icon" data-vehicle-id="${vehicle.id}" data-dealer-id="${vehicle.dealer_id || ''}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </button>
                        </div>
                        <div class="vehicle-content-wrapper flex flex-1 flex-col p-3 space-y-1 min-h-[7.5rem]">
                            <div class="flex flex-col gap-1">
                                <h3 class="flex items-center gap-2 text-xs font-semibold leading-snug line-clamp-2 min-h-[2rem]">
                                    ${titleText}
                                </h3>
                                ${vehicle.variant_name ? `
                                <p class="text-muted-foreground text-xs font-normal line-clamp-1">
                                    ${vehicle.variant_name}
                                </p>
                                ` : `<p class="text-xs font-normal invisible select-none" aria-hidden="true">&nbsp;</p>`}
                                <p class="vehicle-listing-price text-lg font-bold">
                                    ${formatCurrency(vehicle.price)}
                                </p>
                            </div>
                            <div class="vehicle-listing-badges mt-auto flex min-h-[2rem] flex-wrap content-start gap-1 text-xs font-light">
                                ${vehicle.mileage || vehicle.km_driven ? `
                                <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${new Intl.NumberFormat('da-DK').format(vehicle.mileage || vehicle.km_driven || 0)} km</span>
                                ` : ''}
                                ${vehicle.engine_power_hp ? `
                                <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${Math.round(vehicle.engine_power_hp)} HP</span>
                                ` : ''}
                                ${vehicle.first_registration_date ? `
                                <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${formatMonthYear(vehicle.first_registration_date)}</span>
                                ` : ''}
                                ${vehicle.fuel_type_name ? `
                                <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${vehicle.fuel_type_name}</span>
                                ` : ''}
                                ${vehicle.gear_type_name ? `
                                <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${vehicle.gear_type_name}</span>
                                ` : ''}
                            </div>
                        </div>
                    </a>
                    <div class="vehicle-item-footer mt-auto" onclick="event.stopPropagation()">
                        <div class="px-3 pt-3 pb-2 min-h-[2.25rem]">
                            ${locationText ? `
                            <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="truncate text-right" title="${locationText}">${locationText}</span>
                            </div>
                            ` : ''}
                        </div>
                        <div class="p-3 pt-0">
                            <div class="vehicle-actions-section flex w-full flex-col gap-2 sm:flex-row">
                                <a href="/vehicles/${slug}" class="flex-1" onclick="event.stopPropagation()">
                                    <button type="button" class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 hover:shadow-md disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                        {{ __('messages.pages.vehicles.view_details') }}
                                    </button>
                                </a>
                                <button type="button" class="flex-1 inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground hover:shadow-sm dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border" onclick="event.stopPropagation(); openEnquiryDialog('enquiry', '${slug}');">
                                    {{ __('messages.pages.vehicles.enquire') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderVehicleGrid(vehicles) {
            if (!vehicleContainer) return;

            if (vehicles.length === 0) {
                vehicleContainer.innerHTML = `
                    <div class="col-span-full flex items-center justify-center py-12">
                        <div class="flex flex-col items-center justify-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-6 w-6 text-muted-foreground">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                <h3 class="text-lg font-semibold">{{ __('messages.forms.no_vehicles_found') }}</h3>
                <p class="text-muted-foreground mt-1">
                    {{ __('messages.forms.try_adjusting_filters') }}
                </p>
                <button type="button" class="empty-state-reset-btn mt-4 inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1.5 text-xs text-foreground transition-colors hover:bg-muted/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    {{ __('messages.pages.vehicles.reset_filters') }}
                </button>
            </div>
                    </div>
                `;
                vehicleContainer.querySelector('.empty-state-reset-btn')?.addEventListener('click', resetAllFilters);
                return;
            }

            vehicleContainer.innerHTML = vehicles.map(vehicle => renderVehicleItem(vehicle)).join('');
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
                const response = await fetch('/favorites/check-batch', {
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
        
        // Render pagination
        function renderPagination(pagination) {
            console.log('renderPagination called with:', pagination);
            
            // Find pagination container by ID (more reliable)
            let paginationContainer = document.getElementById('pagination-container');
            
            // If container doesn't exist, create it after the vehicle container
            if (!paginationContainer && vehicleContainer) {
                paginationContainer = document.createElement('div');
                paginationContainer.id = 'pagination-container';
                paginationContainer.className = 'mt-8 flex items-center justify-center gap-2';
                vehicleContainer.parentNode.insertBefore(paginationContainer, vehicleContainer.nextSibling);
                console.log('Created pagination container');
            }
            
            if (!paginationContainer) {
                console.warn('Pagination container not found and could not be created');
                return;
            }
            
            // Handle missing or invalid pagination data
            if (!pagination || typeof pagination !== 'object') {
                console.warn('Invalid pagination data:', pagination);
                paginationContainer.innerHTML = '';
                return;
            }
            
            const { current_page, last_page, total } = pagination;
            console.log('Pagination values:', { current_page, last_page, total });
            
            // Convert to numbers and validate pagination values
            const currentPageNum = parseInt(current_page);
            const lastPageNum = parseInt(last_page);
            
            if (isNaN(currentPageNum) || isNaN(lastPageNum) || currentPageNum < 1 || lastPageNum < 1) {
                console.warn('Invalid pagination values:', { current_page, last_page, currentPageNum, lastPageNum });
                paginationContainer.innerHTML = '';
                return;
            }
            
            // Only clear if there's truly only 1 page
            if (lastPageNum <= 1) {
                console.log('Only 1 page, clearing pagination');
                paginationContainer.innerHTML = '';
                return;
            }
            
            console.log('Rendering pagination HTML');
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <button 
                    ${currentPageNum === 1 ? 'disabled' : ''}
                    data-page="${currentPageNum - 1}"
                    class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    {{ __('messages.common.previous') }}
                </button>
            `;
            
            // Page numbers
            const maxPagesToShow = 7;
            let startPage = Math.max(1, currentPageNum - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(lastPageNum, startPage + maxPagesToShow - 1);
            
            if (endPage - startPage < maxPagesToShow - 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            if (startPage > 1) {
                paginationHTML += `
                    <button data-page="1" class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        1
                    </button>
                `;
                if (startPage > 2) {
                    paginationHTML += `<span class="px-2 text-muted-foreground">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button 
                        data-page="${i}"
                        class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring ${i === currentPageNum ? 'bg-accent' : ''}"
                    >
                        ${i}
                    </button>
                `;
            }
            
            if (endPage < lastPageNum) {
                if (endPage < lastPageNum - 1) {
                    paginationHTML += `<span class="px-2 text-muted-foreground">...</span>`;
                }
                paginationHTML += `
                    <button data-page="${lastPageNum}" class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        ${lastPageNum}
                    </button>
                `;
            }
            
            // Next button
            paginationHTML += `
                <button 
                    ${currentPageNum === lastPageNum ? 'disabled' : ''}
                    data-page="${currentPageNum + 1}"
                    class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    {{ __('messages.common.next') }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
            
            paginationContainer.innerHTML = paginationHTML;
            
            // Add click handlers to pagination buttons
            paginationContainer.querySelectorAll('.pagination-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(btn.getAttribute('data-page'));
                    if (page && !btn.disabled) {
                        fetchVehicles({ page });
                    }
                });
            });
        }
        
        // Show loading overlay without clearing filter sidebar or chips
        function showLoading() {
            if (!vehicleGrid) return;
            isLoading = true;
            vehicleGrid.classList.add('relative', 'opacity-60', 'pointer-events-none');
            let overlay = document.getElementById('vehicle-loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'vehicle-loading-overlay';
                overlay.className = 'absolute inset-0 z-20 flex items-center justify-center bg-background/40';
                overlay.innerHTML = `
                    <div class="flex flex-col items-center justify-center text-center rounded-lg bg-card/90 px-6 py-4 shadow-sm">
                        <svg class="animate-spin h-8 w-8 text-primary mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12 h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-muted-foreground">{{ __('messages.forms.loading_vehicles') }}</p>
                    </div>
                `;
                vehicleGrid.appendChild(overlay);
            }
            overlay.classList.remove('hidden');
        }

        function hideLoading() {
            if (!vehicleGrid) return;
            vehicleGrid.classList.remove('opacity-60', 'pointer-events-none');
            const overlay = document.getElementById('vehicle-loading-overlay');
            if (overlay) overlay.classList.add('hidden');
        }
        
        // Show error state
        function showError(message) {
            if (!vehicleGrid) return;
            isLoading = false;
            vehicleGrid.innerHTML = `
                <div class="col-span-full flex items-center justify-center py-12">
                    <div class="flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-6 w-6 text-destructive">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y2="12" y1="8"></line>
                            <line x1="12" y1="16" x2="12" y2="16"></line>
                        </svg>
                        <h3 class="text-lg font-semibold">{{ __('messages.forms.error_loading_vehicles') }}</h3>
                        <p class="text-muted-foreground mt-1">${message || '{{ __('messages.forms.please_try_again') }}'}</p>
                    </div>
                </div>
            `;
        }
        
        function formatMultiSelectSummary(names, maxVisible = 3) {
            if (!names.length) return I18N_BMV.all;
            if (names.length === 1) return names[0];
            const visible = names.slice(0, maxVisible).join(', ');
            return names.length > maxVisible ? visible + '…' : visible;
        }

        function isResolvableChipLabel(label, rawValue) {
            if (!label || !String(label).trim()) return false;
            const trimmed = String(label).trim();
            const valueStr = rawValue === undefined || rawValue === null ? '' : String(rawValue);
            if (valueStr && trimmed === valueStr) return false;
            if (/^\d+$/.test(trimmed) && valueStr && trimmed === valueStr) return false;
            return true;
        }

        // Render filter chips
        function renderFilterChips() {
            const container = document.getElementById('applied-filters-container');
            if (!container) return;
            
            const chips = [];
            const filters = collectFilters();
            
            // Helper to get option text by value
            function getOptionText(selectIdOrName, value) {
                let select = document.getElementById(selectIdOrName);
                if (!select) {
                    select = document.querySelector(`select[name="${selectIdOrName}"]`);
                }
                if (!select) return null;
                const option = Array.from(select.options).find(opt => opt.value == value);
                const text = option ? option.textContent.trim() : null;
                return isResolvableChipLabel(text, value) ? text : null;
            }
            
            // Helper to get label text for checkbox/radio
            // Skips icon spans (those containing SVG) and returns the first text-only span
            function getLabelText(name, value) {
                const input = document.querySelector(`[name="${name}"][value="${value}"]`);
                if (!input) return null;
                const label = input.closest('label');
                if (!label) return null;
                const spans = Array.from(label.querySelectorAll('span'));
                const textSpan = spans.find(s => s.textContent.trim() && !s.querySelector('svg'));
                const text = textSpan ? textSpan.textContent.trim() : label.textContent.trim();
                return isResolvableChipLabel(text, value) ? text : null;
            }
            
            // Search
            if (filters.search) {
                chips.push({
                    key: 'search',
                    label: `"${filters.search}"`,
                    value: filters.search
                });
            }
            
            // Listing Type (checkboxes - can have multiple)
            if (filters.listing_type_id && Array.isArray(filters.listing_type_id)) {
                filters.listing_type_id.forEach(id => {
                    const name = getLabelText('listing_type_id[]', id);
                    if (name) {
                    chips.push({
                        key: 'listing_type_id',
                            label: name,
                            value: id,
                            isArray: true
                    });
                }
                });
            }
            
            // Brand (multi-select: one chip per selected brand)
            if (filters.brand_id) {
                const ids = Array.isArray(filters.brand_id) ? filters.brand_id : [filters.brand_id];
                ids.forEach(id => {
                    const brandName = getLabelText('brand_id[]', id);
                    if (brandName) {
                        chips.push({
                            key: 'brand_id',
                            label: brandName,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Model (multi-select: one chip per selected model)
            if (filters.model_id) {
                const ids = Array.isArray(filters.model_id) ? filters.model_id : [filters.model_id];
                ids.forEach(id => {
                    const modelName = getLabelText('model_id[]', id);
                    if (modelName) {
                        chips.push({
                            key: 'model_id',
                            label: modelName,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }

            // Fuel type (multi-select: one chip per selected fuel type)
            if (filters.fuel_type_id) {
                const ids = Array.isArray(filters.fuel_type_id) ? filters.fuel_type_id : [filters.fuel_type_id];
                ids.forEach(id => {
                    const fuelTypeName = getLabelText('fuel_type_id[]', id);
                    if (fuelTypeName) {
                        chips.push({
                            key: 'fuel_type_id',
                            label: fuelTypeName,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            
            // Price range
            if (filters.price_from || filters.price_to) {
                const from = filters.price_from ? formatCurrency(filters.price_from).replace(' kr.', '') : '';
                const to = filters.price_to ? formatCurrency(filters.price_to).replace(' kr.', '') : '';
                if (from && to) {
                    chips.push({
                        key: 'price_range',
                        label: `${from} - ${to} kr.`,
                        value: { from: filters.price_from, to: filters.price_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'price_from',
                        label: `{{ __('messages.forms.price_from') }} ${from} kr.`,
                        value: filters.price_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'price_to',
                        label: `{{ __('messages.forms.price_up_to') }} ${to} kr.`,
                        value: filters.price_to
                    });
                }
            }
            
            // Model year range
            if (filters.model_year_from || filters.model_year_to) {
                const from = filters.model_year_from || '';
                const to = filters.model_year_to || '';
                if (from && to) {
                    chips.push({
                        key: 'model_year_range',
                        label: `${from} - ${to}`,
                        value: { from: filters.model_year_from, to: filters.model_year_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'model_year_from',
                        label: `{{ __('messages.forms.model_year') }} {{ __('messages.forms.from') }} ${from}`,
                        value: filters.model_year_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'model_year_to',
                        label: `{{ __('messages.forms.model_year') }} {{ __('messages.forms.to') }} ${to}`,
                        value: filters.model_year_to
                    });
                }
            }

            // First registration year range
            if (filters.first_registration_year_from || filters.first_registration_year_to) {
                const from = filters.first_registration_year_from || '';
                const to = filters.first_registration_year_to || '';
                if (from && to) {
                    chips.push({
                        key: 'first_registration_year_range',
                        label: `{{ __('messages.forms.first_registration_year') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'first_registration_year_from', label: `{{ __('messages.forms.first_registration_year') }} {{ __('messages.forms.from') }} ${from}`, value: filters.first_registration_year_from });
                } else if (to) {
                    chips.push({ key: 'first_registration_year_to', label: `{{ __('messages.forms.first_registration_year') }} {{ __('messages.forms.to') }} ${to}`, value: filters.first_registration_year_to });
                }
            }

            // Ownership tax range
            if (filters.ownership_tax_from || filters.ownership_tax_to) {
                const from = filters.ownership_tax_from || '';
                const to = filters.ownership_tax_to || '';
                if (from && to) {
                    chips.push({
                        key: 'ownership_tax_range',
                        label: `{{ __('messages.forms.owner_tax') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'ownership_tax_from', label: `{{ __('messages.forms.owner_tax') }} {{ __('messages.forms.from') }} ${from}`, value: filters.ownership_tax_from });
                } else if (to) {
                    chips.push({ key: 'ownership_tax_to', label: `{{ __('messages.forms.owner_tax') }} {{ __('messages.forms.to') }} ${to}`, value: filters.ownership_tax_to });
                }
            }

            // Engine power range
            if (filters.engine_power_kw_from || filters.engine_power_kw_to) {
                const from = filters.engine_power_kw_from || '';
                const to = filters.engine_power_kw_to || '';
                if (from && to) {
                    chips.push({
                        key: 'engine_power_range',
                        label: `{{ __('messages.forms.horsepower_hp') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'engine_power_kw_from', label: `{{ __('messages.forms.horsepower_hp') }} {{ __('messages.forms.from') }} ${from}`, value: filters.engine_power_kw_from });
                } else if (to) {
                    chips.push({ key: 'engine_power_kw_to', label: `{{ __('messages.forms.horsepower_hp') }} {{ __('messages.forms.to') }} ${to}`, value: filters.engine_power_kw_to });
                }
            }

            // Battery capacity range
            if (filters.electrical_consumption_from || filters.electrical_consumption_to) {
                const from = filters.electrical_consumption_from || '';
                const to = filters.electrical_consumption_to || '';
                if (from && to) {
                    chips.push({
                        key: 'battery_capacity_range',
                        label: `{{ __('messages.forms.battery_capacity_kwh') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'electrical_consumption_from', label: `{{ __('messages.forms.battery') }} {{ __('messages.forms.from') }} ${from}`, value: filters.electrical_consumption_from });
                } else if (to) {
                    chips.push({ key: 'electrical_consumption_to', label: `{{ __('messages.forms.battery') }} {{ __('messages.forms.to') }} ${to}`, value: filters.electrical_consumption_to });
                }
            }

            // Fuel efficiency range
            if (filters.km_per_liter_from || filters.km_per_liter_to) {
                const from = filters.km_per_liter_from || '';
                const to = filters.km_per_liter_to || '';
                if (from && to) {
                    chips.push({
                        key: 'fuel_efficiency_range',
                        label: `{{ __('messages.forms.fuel_efficiency') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'km_per_liter_from', label: `{{ __('messages.forms.fuel_efficiency') }} {{ __('messages.forms.from') }} ${from}`, value: filters.km_per_liter_from });
                } else if (to) {
                    chips.push({ key: 'km_per_liter_to', label: `{{ __('messages.forms.fuel_efficiency') }} {{ __('messages.forms.to') }} ${to}`, value: filters.km_per_liter_to });
                }
            }

            // Top speed range
            if (filters.max_speed_from || filters.max_speed_to) {
                const from = filters.max_speed_from || '';
                const to = filters.max_speed_to || '';
                if (from && to) {
                    chips.push({
                        key: 'top_speed_range',
                        label: `{{ __('messages.forms.top_speed') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'max_speed_from', label: `{{ __('messages.forms.top_speed') }} {{ __('messages.forms.from') }} ${from}`, value: filters.max_speed_from });
                } else if (to) {
                    chips.push({ key: 'max_speed_to', label: `{{ __('messages.forms.top_speed') }} {{ __('messages.forms.to') }} ${to}`, value: filters.max_speed_to });
                }
            }

            // Weight range
            if (filters.maximum_weight_kg_from || filters.maximum_weight_kg_to) {
                const from = filters.maximum_weight_kg_from || '';
                const to = filters.maximum_weight_kg_to || '';
                if (from && to) {
                    chips.push({
                        key: 'weight_range',
                        label: `{{ __('messages.forms.weight') }}: ${from} - ${to}`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'maximum_weight_kg_from', label: `{{ __('messages.forms.weight') }} {{ __('messages.forms.from') }} ${from}`, value: filters.maximum_weight_kg_from });
                } else if (to) {
                    chips.push({ key: 'maximum_weight_kg_to', label: `{{ __('messages.forms.weight') }} {{ __('messages.forms.to') }} ${to}`, value: filters.maximum_weight_kg_to });
                }
            }

            // Single numeric filters (door_count, seats_min, seats_max, axle_count, specifications_airbags, towing_weight)
            const singleNumChips = [
                ['door_count', '{{ __('messages.forms.doors') }}'],
                ['seats_min', '{{ __('messages.forms.seats_min') }}'],
                ['seats_max', '{{ __('messages.forms.seats_max') }}'],
                ['axle_count', '{{ __('messages.forms.axles') }}'],
                ['specifications_airbags', '{{ __('messages.forms.airbags') }}'],
                ['towing_weight', '{{ __('messages.forms.towing_weight_min') }}']
            ];
            singleNumChips.forEach(([key, labelPrefix]) => {
                if (filters[key]) {
                    chips.push({ key, label: `${labelPrefix}: ${filters[key]}`, value: filters[key] });
                }
            });

            // Charging type
            if (filters.charging_type) {
                const name = getOptionText('charging_type', filters.charging_type);
                if (name) {
                    chips.push({ key: 'charging_type', label: name, value: filters.charging_type });
                }
            }

            // Checkboxes: ncap_test, is_import, is_factory_new
            if (filters.ncap_test) {
                chips.push({ key: 'ncap_test', label: '{{ __('messages.forms.ncap_test') }}', value: '1' });
            }
            if (filters.is_import) {
                chips.push({ key: 'is_import', label: '{{ __('messages.forms.is_import') }}', value: '1' });
            }
            if (filters.is_factory_new) {
                chips.push({ key: 'is_factory_new', label: '{{ __('messages.forms.is_factory_new') }}', value: '1' });
            }

            // Drive axle_count (checkboxes)
            if (filters.drive_axle_count && Array.isArray(filters.drive_axle_count)) {
                filters.drive_axle_count.forEach(axle => {
                    const name = getLabelText('drive_axle_count[]', axle);
                    if (name) {
                        chips.push({ key: 'drive_axle_count', label: name, value: axle, isArray: true });
                    }
                });
            }
            
            // KM driven range
            if (filters.km_driven_from || filters.km_driven_to) {
                const from = filters.km_driven_from ? new Intl.NumberFormat('da-DK').format(filters.km_driven_from) : '';
                const to = filters.km_driven_to ? new Intl.NumberFormat('da-DK').format(filters.km_driven_to) : '';
                if (from && to) {
                    chips.push({
                        key: 'km_driven_range',
                        label: `${from} - ${to} km`,
                        value: 'range'
                    });
                } else if (from) {
                    chips.push({ key: 'km_driven_from', label: `{{ __('messages.forms.min') }} ${from} km`, value: filters.km_driven_from });
                } else if (to) {
                    chips.push({ key: 'km_driven_to', label: `{{ __('messages.forms.max') }} ${to} km`, value: filters.km_driven_to });
                }
            }

            // Condition
            if (filters.condition_id) {
                const conditionName = getLabelText('condition_id', filters.condition_id);
                if (conditionName) {
                    chips.push({
                        key: 'condition_id',
                        label: conditionName,
                        value: filters.condition_id
                    });
                }
            }
            
            // Single-value selects (body_type, gear_type, color, type, sales_type, price_type, euronom, use, transmission)
            const selectChips = [
                ['body_type_id', '{{ __('messages.forms.body_type') }}'],
                ['gear_type_id', '{{ __('messages.forms.gear_type') }}'],
                ['color_id', '{{ __('messages.forms.color') }}'],
                ['sales_type_id', '{{ __('messages.forms.sales_type') }}'],
                ['price_type_id', '{{ __('messages.forms.price_type') }}'],
                ['emission_norm_id', '{{ __('messages.forms.euro_norm') }}'],
                ['use_id', '{{ __('messages.forms.use') }}']
            ];
            selectChips.forEach(([key, labelPrefix]) => {
                const val = filters[key];
                if (!val) return;
                const id = Array.isArray(val) ? val[0] : val;
                const name = getOptionText(key, id);
                if (name) chips.push({ key, label: name, value: id });
            });

            // Equipment
            if (filters.equipment_ids && Array.isArray(filters.equipment_ids)) {
                filters.equipment_ids.forEach(id => {
                    const name = getLabelText('equipment_ids[]', id);
                    if (name) {
                        chips.push({
                            key: 'equipment_ids',
                            label: name,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Render chips (always keep reset button in DOM for updateResetButtonVisibility)
            const chipsExpanded = container.dataset.chipsExpanded === '1';
            const visibleChips = (!chipsExpanded && chips.length > CHIP_COLLAPSE_LIMIT)
                ? chips.slice(0, CHIP_COLLAPSE_LIMIT)
                : chips;
            const hiddenCount = chips.length - visibleChips.length;
            const chipsHTML = visibleChips.map(chip => `
                <div class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1.5 text-xs text-foreground">
                    <span>${chip.label}</span>
                    <button 
                        type="button"
                        class="filter-chip-remove ml-0.5 rounded-full hover:bg-muted-foreground/20 transition-colors border border-foreground"
                        data-filter-key="${chip.key}"
                        data-filter-value="${typeof chip.value === 'object' ? JSON.stringify(chip.value) : chip.value}"
                        data-is-array="${chip.isArray || false}"
                        aria-label="{{ __('messages.forms.remove_filter') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
            const moreBtnHtml = hiddenCount > 0
                ? `<button type="button" id="filter-chips-more" class="inline-flex items-center rounded-full border border-input bg-background px-2.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">${I18N_BMV.chipsMore.replace(':count', hiddenCount)}</button>`
                : (chipsExpanded && chips.length > CHIP_COLLAPSE_LIMIT
                    ? `<button type="button" id="filter-chips-less" class="inline-flex items-center rounded-full border border-input bg-background px-2.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">${I18N_BMV.chipsShowLess}</button>`
                    : '');
            const resetBtnHtml = `<button id="filter-reset-button-main" type="button" class="hidden inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1.5 text-xs text-foreground transition-colors hover:bg-muted/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">{{ __('messages.pages.vehicles.reset_filters') }}</button>`;
            container.innerHTML = chipsHTML + moreBtnHtml + resetBtnHtml;
            updateResetButtonVisibility();
            container.querySelector('#filter-chips-more')?.addEventListener('click', () => {
                container.dataset.chipsExpanded = '1';
                renderFilterChips();
            });
            container.querySelector('#filter-chips-less')?.addEventListener('click', () => {
                container.dataset.chipsExpanded = '0';
                renderFilterChips();
            });
            container.querySelectorAll('.filter-chip-remove').forEach(btn => {
                btn.addEventListener('click', () => {
                    const key = btn.getAttribute('data-filter-key');
                    const value = btn.getAttribute('data-filter-value');
                    const isArray = btn.getAttribute('data-is-array') === 'true';
                    
                    // Remove filter from DOM
                    if (isArray) {
                        const inputs = document.getElementsByName(key + '[]');
                        const checkbox = Array.from(inputs).find(inp => inp.value == value);
                        if (checkbox) checkbox.checked = false;
                        if (key === 'brand_id' && typeof updateBrandDropdownLabel === 'function') {
                            updateBrandDropdownLabel();
                            if (typeof refreshModelsFromApi === 'function') refreshModelsFromApi();
                        }
                        if (key === 'model_id' && typeof updateModelDropdownLabel === 'function') {
                            updateModelDropdownLabel();
                        }
                        if (key === 'fuel_type_id' && typeof updateFuelTypeDropdownLabel === 'function') updateFuelTypeDropdownLabel();
                    } else if (key === 'search') {
                        if (searchInput) searchInput.value = '';
                    } else if (key === 'listing_type_id') {
                        // Uncheck the specific listing type checkbox
                        const checkbox = document.querySelector(`[name="listing_type_id[]"][value="${value}"]`);
                        if (checkbox) {
                            checkbox.checked = false;
                            if (typeof updateListingTypeStyles === 'function') {
                                updateListingTypeStyles();
                            }
                        }
                    } else if (key === 'price_range') {
                        const priceFrom = document.querySelector('[name="price_from"]');
                        const priceTo = document.querySelector('[name="price_to"]');
                        if (priceFrom) priceFrom.value = '';
                        if (priceTo) priceTo.value = '';
                    } else if (key === 'model_year_range') {
                        const yearFrom = document.querySelector('[name="model_year_from"]');
                        const yearTo = document.querySelector('[name="model_year_to"]');
                        if (yearFrom) yearFrom.value = '';
                        if (yearTo) yearTo.value = '';
                    } else if (key === 'model_year_from' || key === 'model_year_to') {
                        const inp = document.querySelector(`[name="${key}"]`);
                        if (inp) inp.value = '';
                    } else if (key === 'mileage_range' || key === 'km_driven_range') {
                        const from = document.querySelector('[name="km_driven_from"]');
                        const to = document.querySelector('[name="km_driven_to"]');
                        if (from) from.value = '';
                        if (to) to.value = '';
                    } else if (key === 'first_registration_year_range') {
                        const a = document.querySelector('[name="first_registration_year_from"]');
                        const b = document.querySelector('[name="first_registration_year_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'ownership_tax_range') {
                        const a = document.querySelector('[name="ownership_tax_from"]');
                        const b = document.querySelector('[name="ownership_tax_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'engine_power_range') {
                        const a = document.querySelector('[name="engine_power_kw_from"]');
                        const b = document.querySelector('[name="engine_power_kw_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'battery_capacity_range') {
                        const a = document.querySelector('[name="electrical_consumption_from"]');
                        const b = document.querySelector('[name="electrical_consumption_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'fuel_efficiency_range') {
                        const a = document.querySelector('[name="km_per_liter_from"]');
                        const b = document.querySelector('[name="km_per_liter_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'top_speed_range') {
                        const a = document.querySelector('[name="max_speed_from"]');
                        const b = document.querySelector('[name="max_speed_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'weight_range') {
                        const a = document.querySelector('[name="maximum_weight_kg_from"]');
                        const b = document.querySelector('[name="maximum_weight_kg_to"]');
                        if (a) a.value = ''; if (b) b.value = '';
                    } else if (key === 'drive_axle_count' && isArray) {
                        const checkbox = document.querySelector(`[name="drive_axle_count[]"][value="${value}"]`);
                        if (checkbox) checkbox.checked = false;
                    } else if (key === 'condition_id') {
                        const selectedRadio = document.querySelector(`[name="condition_id"][value="${value}"]`);
                        const allRadio = document.querySelector('[name="condition_id"][value=""]');
                        if (selectedRadio) selectedRadio.checked = false;
                        if (allRadio) allRadio.checked = true;
                        if (typeof updateConditionStyles === 'function') updateConditionStyles();
                    } else {
                        const input = document.querySelector(`[name="${key}"]`);
                        if (input) {
                            if (input.type === 'radio' || input.type === 'checkbox') {
                                input.checked = false;
                            } else {
                                input.value = '';
                            }
                        }
                    }
                    
                    // Re-apply filters
                    autoApplyFilters();
                });
            });
        }
        
        // Update reset button visibility based on active filters
        function updateResetButtonVisibility() {
            const resetButton = document.getElementById('filter-reset-button-main');
            if (!resetButton) return;
            
            const filters = collectFilters();
            const hasFilters = Object.keys(filters).some(key => {
                if (key === 'sort' || key === 'limit') return false;
                const value = filters[key];
                if (Array.isArray(value)) return value.length > 0;
                return value !== null && value !== '' && value !== undefined;
            });
            
            if (hasFilters) {
                resetButton.classList.remove('hidden');
            } else {
                resetButton.classList.add('hidden');
            }
        }
        
        // POST search-vehicles: single source of truth is sidebar state (collectFilters).
        async function fetchVehicles(params = {}) {
            if (isLoading) return;
            const payload = { ...collectFilters(), ...params };
            if (sortSelect?.value) {
                payload.sort = sortSelect.value;
            }
            // Prune empty (keep sort so API can apply default normalization)
            Object.keys(payload).forEach(k => {
                if (k === 'sort') return;
                if (payload[k] === '' || payload[k] === null || payload[k] === undefined) delete payload[k];
                if (Array.isArray(payload[k]) && payload[k].length === 0) delete payload[k];
            });
            showLoading();
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('{{ url("/api/v1/search-vehicles") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
                    },
                    body: JSON.stringify(payload),
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const json = await response.json();
                const data = json.data || {};
                let vehicles = data.docs || [];
                let totalDocs = data.totalDocs ?? 0;
                let page = data.page ?? 1;
                let totalPages = data.totalPages ?? 1;
                const noResults = data.no_results === true && Array.isArray(data.fallback_docs);
                if (noResults && totalDocs === 0 && data.fallback_docs.length > 0) {
                    vehicles = data.fallback_docs;
                    totalDocs = data.fallback_totalDocs ?? vehicles.length;
                    page = data.fallback_page ?? 1;
                    totalPages = data.fallback_totalPages ?? 1;
                }
                const noResultsMessageEl = document.getElementById('no-results-message');
                if (noResultsMessageEl) {
                    if (noResults && data.totalDocs === 0) {
                        noResultsMessageEl.classList.remove('hidden');
                        noResultsMessageEl.innerHTML = `
                            <h3 class="text-lg font-semibold">{{ __("messages.forms.no_vehicles_found") }}</h3>
                            <p class="text-muted-foreground text-sm mt-1">{{ __("messages.forms.try_adjusting_filters") }} {{ __("messages.pages.vehicles.showing_all_vehicles") }}</p>
                            <button type="button" class="empty-state-reset-btn mt-4 inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1.5 text-xs text-foreground transition-colors hover:bg-muted/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">${I18N_BMV.resetFilters}</button>
                        `;
                        noResultsMessageEl.querySelector('.empty-state-reset-btn')?.addEventListener('click', resetAllFilters);
                    } else {
                        noResultsMessageEl.classList.add('hidden');
                        noResultsMessageEl.innerHTML = '';
                    }
                }
                hideLoading();
                renderVehicleGrid(vehicles);
                setView(currentView);
                await checkFavoritesBatch();
                renderPagination({ current_page: page, last_page: totalPages, total: totalDocs });
                const resultsCount = document.getElementById('results-count');
                if (resultsCount) {
                    const filteredTotal = data.totalDocs ?? 0;
                    if (noResults && filteredTotal === 0 && vehicles.length > 0) {
                        resultsCount.innerHTML = `<strong>0</strong> {{ __('messages.forms.results') }} <span class="text-muted-foreground">({{ __('messages.pages.vehicles.showing_all_vehicles') }})</span>`;
                    } else {
                        resultsCount.innerHTML = `<strong>${new Intl.NumberFormat('da-DK').format(totalDocs)}</strong> {{ __('messages.forms.results') }}`;
                    }
                }
                renderFilterChips();
                updateResetButtonVisibility();
                isLoading = false;
            } catch (error) {
                console.error('Error fetching vehicles:', error);
                hideLoading();
                showError('{{ __('messages.pages.vehicles.failed_to_load_vehicles') }}');
                isLoading = false;
            }
        }
        
        // Search form handler with debounce
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    const searchValue = e.target.value.trim();
                    fetchVehicles({ search: searchValue || null, page: 1 });
                }, 300);
            });
            
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    const searchValue = e.target.value.trim();
                    fetchVehicles({ search: searchValue || null, page: 1 });
                }
            });
        }
        
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                const searchValue = searchInput?.value.trim();
                fetchVehicles({ search: searchValue || null, page: 1 });
            });
        }
        
        // Sort select functionality
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                fetchVehicles({ sort: e.target.value, page: 1 });
            });
        }
        
        // Sticky search bar on scroll
        const searchBarContainer = document.getElementById('search-bar-container');
        let lastScrollY = window.scrollY;
        let originalOffsetTop = null;
        let isSticky = false;
        
        function handleStickySearchBar() {
            if (!searchBarContainer) return;
            
            const currentScrollY = window.scrollY;
            const scrollDirection = currentScrollY > lastScrollY ? 'down' : 'up';
            
            // Get original position on first load (before any sticky behavior)
            if (originalOffsetTop === null) {
                originalOffsetTop = searchBarContainer.offsetTop;
            }
            
            const rect = searchBarContainer.getBoundingClientRect();
            const isAtTop = rect.top <= 0;
            
            // When scrolling down and search bar reaches top - make it sticky
            if (scrollDirection === 'down' && isAtTop && !isSticky) {
                searchBarContainer.classList.add('sticky', 'top-0', 'z-30');
                isSticky = true;
            }
            // When scrolling back up past original position - remove sticky
            else if (scrollDirection === 'up' && currentScrollY < originalOffsetTop && isSticky) {
                searchBarContainer.classList.remove('sticky', 'top-0', 'z-30');
                isSticky = false;
            }
            // When scrolling up while sticky and element naturally comes back into view
            else if (scrollDirection === 'up' && isSticky && rect.top > 0) {
                searchBarContainer.classList.remove('sticky', 'top-0', 'z-30');
                isSticky = false;
            }
            
            lastScrollY = currentScrollY;
        }
        
        // Throttle scroll events for better performance
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) {
                cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = requestAnimationFrame(handleStickySearchBar);
        });
        
        // Initialize on load
        handleStickySearchBar();
        
        // Recalculate original position on resize (only if not sticky)
        window.addEventListener('resize', () => {
            if (!isSticky && originalOffsetTop !== null) {
                originalOffsetTop = searchBarContainer.offsetTop;
            }
        });
        
        // Mobile sidebar toggle
        if (mobileFilterToggle && filterSidebar) {
            const filterIcon = document.getElementById('mobile-filter-icon');
            const closeIcon = document.getElementById('mobile-close-icon');
            
            function updateToggleIcon() {
                const isOpen = !filterSidebar.classList.contains('hidden');
                if (filterIcon && closeIcon) {
                    if (isOpen) {
                        filterIcon.classList.add('hidden');
                        closeIcon.classList.remove('hidden');
                        mobileFilterToggle.setAttribute('aria-label', '{{ __("messages.pages.vehicles.close_filters") }}');
                    } else {
                        filterIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                        mobileFilterToggle.setAttribute('aria-label', '{{ __("messages.pages.vehicles.open_filters") }}');
                    }
                }
            }
            
            mobileFilterToggle.addEventListener('click', () => {
                filterSidebar.classList.toggle('hidden');
                updateToggleIcon();
            });

            // Pullbar: tap or drag down to close sidebar on mobile (touch-friendly)
            const sidebarPullbar = document.getElementById('sidebar-pullbar');
            if (sidebarPullbar && filterSidebar) {
                let pullbarStartY = 0;
                function closeSidebar() {
                    filterSidebar.classList.add('hidden');
                    updateToggleIcon();
                }
                sidebarPullbar.addEventListener('click', (e) => {
                    if (window.innerWidth < 1024) {
                        e.preventDefault();
                        closeSidebar();
                    }
                });
                sidebarPullbar.addEventListener('touchend', (e) => {
                    if (window.innerWidth >= 1024) return;
                    const touch = e.changedTouches && e.changedTouches[0];
                    if (!touch) return;
                    const deltaY = touch.clientY - pullbarStartY;
                    if (deltaY > 30) closeSidebar();
                }, { passive: true });
                sidebarPullbar.addEventListener('touchstart', (e) => {
                    if (e.touches && e.touches[0]) pullbarStartY = e.touches[0].clientY;
                }, { passive: true });
                sidebarPullbar.addEventListener('mousedown', (e) => { pullbarStartY = e.clientY; });
                sidebarPullbar.addEventListener('mouseup', (e) => {
                    if (window.innerWidth >= 1024) return;
                    if (e.clientY - pullbarStartY > 30) closeSidebar();
                });
            }

            // Update icon on initial load
            updateToggleIcon();
        }

        // Listing Type functionality for Purchase/Leasing (checkboxes)
        const listingTypeCheckboxes = document.querySelectorAll('input[name="listing_type_id[]"]');
        
        function updateListingTypeStyles() {
            listingTypeCheckboxes.forEach(checkbox => {
                const label = checkbox.closest('.listing-type-checkbox-label');
                if (label) {
                    if (checkbox.checked) {
                        label.classList.add('bg-primary', 'text-primary-foreground', 'font-semibold');
                        label.classList.remove('bg-card', 'text-muted-foreground', 'border-input');
                    } else {
                        label.classList.remove('bg-primary', 'text-primary-foreground', 'font-semibold');
                        label.classList.add('bg-card', 'text-muted-foreground', 'border-input');
                    }
                }
            });
        }
        
        listingTypeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateListingTypeStyles();
            });
        });

        // Initialize styles on page load
        updateListingTypeStyles();

        // Brand-Model dependency: Filter models based on selected brand
        const brandSelect = document.getElementById('brand-select');
        const modelSelect = document.getElementById('model-select');
        
        function updateModelOptions() {
            if (!brandSelect || !modelSelect) return;
            
            const selectedBrandId = brandSelect.value;
            const modelOptions = modelSelect.querySelectorAll('option[data-brand-id]');
            const defaultOption = modelSelect.querySelector('option[value=""]');
            
            if (!selectedBrandId || selectedBrandId === '') {
                // No brand selected - disable model dropdown
                modelSelect.disabled = true;
                if (defaultOption) {
                    defaultOption.textContent = '{{ __('messages.forms.model') }}';
                }
                modelSelect.value = '';
                // Hide all model options
                modelOptions.forEach(option => {
                    option.style.display = 'none';
                });
            } else {
                // Brand selected - enable model dropdown
                modelSelect.disabled = false;
                if (defaultOption) {
                    defaultOption.textContent = '{{ __('messages.common.all') }}';
                }
                
                // Show/hide model options based on brand
                modelOptions.forEach(option => {
                    const optionBrandId = option.getAttribute('data-brand-id');
                    if (optionBrandId === selectedBrandId) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Reset model selection if current model is not available for selected brand
                if (modelSelect.value) {
                    const selectedModelOption = modelSelect.options[modelSelect.selectedIndex];
                    if (selectedModelOption && selectedModelOption.getAttribute('data-brand-id') !== selectedBrandId) {
                        modelSelect.value = '';
                    }
                }
            }
        }
        
        if (brandSelect) {
            brandSelect.addEventListener('change', updateModelOptions);
        }
        
        // Initialize model options on page load
        updateModelOptions();
        
        // Condition radio button styling
        function updateConditionStyles() {
            const conditionRadios = document.querySelectorAll('input[name="condition_id"]');
            conditionRadios.forEach(radio => {
                const label = radio.closest('.condition-radio-label');
                if (label) {
                    if (radio.checked) {
                        label.classList.add('bg-primary', 'text-primary-foreground', 'font-semibold');
                        label.classList.remove('bg-card', 'text-muted-foreground', 'border-input');
                    } else {
                        label.classList.remove('bg-primary', 'text-primary-foreground', 'font-semibold');
                        label.classList.add('bg-card', 'text-muted-foreground', 'border-input');
                    }
                }
            });
        }
        
        // Set up condition radio listeners - only update UI; filter apply is handled by setupAutoApplyFilters
        document.querySelectorAll('input[name="condition_id"]').forEach(radio => {
            radio.addEventListener('change', () => {
                updateConditionStyles();
            });
        });
        
        // Initialize condition styles
        updateConditionStyles();
        
        // Equipment collapsible functionality
        function setupEquipmentCollapsible() {
            if (!filterSidebar) return;
            
            const equipmentToggles = filterSidebar.querySelectorAll('.equipment-type-toggle');
            equipmentToggles.forEach(toggle => {
                // Remove existing listeners by cloning
                const newToggle = toggle.cloneNode(true);
                toggle.parentNode.replaceChild(newToggle, toggle);
                
                newToggle.addEventListener('click', () => {
                    const content = newToggle.nextElementSibling;
                    const icon = newToggle.querySelector('.equipment-type-icon');
                    
                    if (content) {
                        content.classList.toggle('hidden');
                        if (icon) {
                            icon.classList.toggle('rotate-180');
                        }
                    }
                });
            });
        }
        

        // Dual-handle range slider functionality
        function initRangeSlider(config) {
            const { minSlider, maxSlider, minInput, maxInput, minHandle, maxHandle, track, min, max, valueLabel, formatValue } = config;
            
            if (!minSlider || !maxSlider || !minInput || !maxInput || !minHandle || !maxHandle || !track) return;

            const formatDisplay = typeof formatValue === 'function'
                ? formatValue
                : (v) => new Intl.NumberFormat('da-DK').format(v);

            function isRangeFilterActive() {
                return minInput.value !== '' || maxInput.value !== '';
            }

            function updateValueLabel(finalMin, finalMax, active) {
                if (!valueLabel) return;
                if (!active) {
                    valueLabel.textContent = '';
                    return;
                }
                const fromText = minInput.value !== '' ? formatDisplay(finalMin) : '—';
                const toText = maxInput.value !== '' ? formatDisplay(finalMax) : '—';
                valueLabel.textContent = `${fromText} – ${toText}`;
            }

            function setTrackActive(active) {
                if (active) {
                    track.classList.remove('opacity-0');
                    minHandle.classList.remove('opacity-40');
                    maxHandle.classList.remove('opacity-40');
                } else {
                    track.classList.add('opacity-0');
                    minHandle.classList.add('opacity-40');
                    maxHandle.classList.add('opacity-40');
                }
            }
            
            // Sync from server-rendered number inputs to sliders on init
            const minVal = parseFloat(minInput.value);
            const maxVal = parseFloat(maxInput.value);
            if (!isNaN(minVal) && minVal > min) minSlider.value = Math.min(max, minVal);
            if (!isNaN(maxVal) && maxVal < max) maxSlider.value = Math.max(min, maxVal);
            
            function updateSlider() {
                let minVal = parseFloat(minSlider.value);
                let maxVal = parseFloat(maxSlider.value);
                if (isNaN(minVal)) minVal = min;
                if (isNaN(maxVal)) maxVal = max;
                
                if (minVal > maxVal) {
                    if (document.activeElement === minSlider) {
                        minSlider.value = maxVal;
                        minVal = maxVal;
                    } else {
                        maxSlider.value = minVal;
                        maxVal = minVal;
                    }
                }
                
                const finalMin = Math.min(minVal, maxVal);
                const finalMax = Math.max(minVal, maxVal);
                const active = isRangeFilterActive();
                const sliderDriving = document.activeElement === minSlider || document.activeElement === maxSlider;

                if (sliderDriving) {
                    if (finalMin === min || finalMin === 0) minInput.value = '';
                    else minInput.value = finalMin;
                    if (finalMax === max) maxInput.value = '';
                    else maxInput.value = finalMax;
                }
                
                const minPercent = ((finalMin - min) / (max - min)) * 100;
                const maxPercent = ((finalMax - min) / (max - min)) * 100;
                
                minHandle.style.left = `calc(${minPercent}% - 10px)`;
                maxHandle.style.left = `calc(${maxPercent}% - 10px)`;
                
                if (active) {
                    track.style.left = `${minPercent}%`;
                    track.style.width = `${Math.max(0, maxPercent - minPercent)}%`;
                } else {
                    track.style.left = '0%';
                    track.style.width = '0%';
                }
                setTrackActive(active);
                updateValueLabel(finalMin, finalMax, active);
            }
            
            // Update only the visual track and handles without touching input values
            function updateTrackOnly() {
                let minVal = parseFloat(minSlider.value);
                let maxVal = parseFloat(maxSlider.value);
                if (isNaN(minVal)) minVal = min;
                if (isNaN(maxVal)) maxVal = max;
                if (minVal > maxVal) {
                    if (document.activeElement === minSlider) {
                        minSlider.value = maxVal;
                        minVal = maxVal;
                    } else {
                        maxSlider.value = minVal;
                        maxVal = minVal;
                    }
                }
                const finalMin = Math.min(minVal, maxVal);
                const finalMax = Math.max(minVal, maxVal);
                const active = isRangeFilterActive();
                const minPercent = ((finalMin - min) / (max - min)) * 100;
                const maxPercent = ((finalMax - min) / (max - min)) * 100;
                minHandle.style.left = `calc(${minPercent}% - 10px)`;
                maxHandle.style.left = `calc(${maxPercent}% - 10px)`;
                if (active) {
                    track.style.left = `${minPercent}%`;
                    track.style.width = `${Math.max(0, maxPercent - minPercent)}%`;
                } else {
                    track.style.left = '0%';
                    track.style.width = '0%';
                }
                setTrackActive(active);
                updateValueLabel(finalMin, finalMax, active);
            }

            function updateFromInput(input, slider) {
                const value = parseFloat(input.value);
                if (!isNaN(value) && value >= 0) {
                    const clampedValue = Math.max(min, Math.min(max, value));
                    slider.value = clampedValue;
                    if (slider === minSlider && parseFloat(maxSlider.value) < clampedValue) {
                        maxSlider.value = clampedValue;
                    }
                    if (slider === maxSlider && parseFloat(minSlider.value) > clampedValue) {
                        minSlider.value = clampedValue;
                    }
                    updateTrackOnly();
                } else if (input.value === '') {
                    slider.value = (slider === minSlider) ? min : max;
                    updateTrackOnly();
                }
            }
            
            // Initialize
            updateSlider();
            
            // Slider events
            minSlider.addEventListener('input', updateSlider);
            maxSlider.addEventListener('input', updateSlider);
            
            // Input events — update slider position while typing but never overwrite the input
            minInput.addEventListener('input', () => updateFromInput(minInput, minSlider));
            maxInput.addEventListener('input', () => updateFromInput(maxInput, maxSlider));

            // On blur, clamp and sync the input value with the slider state
            minInput.addEventListener('blur', () => updateSlider());
            maxInput.addEventListener('blur', () => updateSlider());
            
            // Handle drag events for visual handles
            let isDragging = false;
            let activeHandle = null;
            
            [minHandle, maxHandle].forEach((handle, index) => {
                handle.addEventListener('mousedown', (e) => {
                    isDragging = true;
                    activeHandle = index === 0 ? minSlider : maxSlider;
                    e.preventDefault();
                });
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging || !activeHandle) return;
                
                const sliderContainer = activeHandle.closest('.slider-track-area') || activeHandle.closest('.relative');
                const rect = sliderContainer.getBoundingClientRect();
                const percent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                const value = min + (percent / 100) * (max - min);
                const step = parseFloat(activeHandle.step) || 1;
                const steppedValue = Math.round(value / step) * step;
                const clampedValue = Math.max(min, Math.min(max, steppedValue));
                
                activeHandle.value = clampedValue;
                updateSlider();
            });
            
            document.addEventListener('mouseup', () => {
                const wasDragging = isDragging;
                isDragging = false;
                activeHandle = null;
                if (wasDragging && typeof autoApplyFilters === 'function') {
                    autoApplyFilters();
                }
            });
        }
        
        // Initialize all range sliders
        const sliderConfigs = [
            {
                minSlider: document.getElementById('price-slider-min'),
                maxSlider: document.getElementById('price-slider-max'),
                minInput: document.getElementById('price-from'),
                maxInput: document.getElementById('price-to'),
                minHandle: document.getElementById('price-handle-min'),
                maxHandle: document.getElementById('price-handle-max'),
                track: document.getElementById('price-range-track'),
                valueLabel: document.getElementById('price-slider-label'),
                formatValue: (v) => formatCurrency(v).replace(' kr.', ''),
                min: 0,
                max: {{ $filterPriceMax }}
            },
            {
                minSlider: document.getElementById('year-slider-min'),
                maxSlider: document.getElementById('year-slider-max'),
                minInput: document.getElementById('year-from'),
                maxInput: document.getElementById('year-to'),
                minHandle: document.getElementById('year-handle-min'),
                maxHandle: document.getElementById('year-handle-max'),
                track: document.getElementById('year-range-track'),
                valueLabel: document.getElementById('year-slider-label'),
                formatValue: (v) => String(Math.round(v)),
                min: 1950,
                max: 2027
            },
            {
                minSlider: document.getElementById('mileage-slider-min'),
                maxSlider: document.getElementById('mileage-slider-max'),
                minInput: document.getElementById('mileage-from'),
                maxInput: document.getElementById('mileage-to'),
                minHandle: document.getElementById('mileage-handle-min'),
                maxHandle: document.getElementById('mileage-handle-max'),
                track: document.getElementById('mileage-range-track'),
                valueLabel: document.getElementById('mileage-slider-label'),
                formatValue: (v) => new Intl.NumberFormat('da-DK').format(v) + ' km',
                min: 0,
                max: {{ $filterKmMax }}
            },
            {
                minSlider: document.getElementById('first-reg-year-slider-min'),
                maxSlider: document.getElementById('first-reg-year-slider-max'),
                minInput: document.getElementById('first-reg-year-from'),
                maxInput: document.getElementById('first-reg-year-to'),
                minHandle: document.getElementById('first-reg-year-handle-min'),
                maxHandle: document.getElementById('first-reg-year-handle-max'),
                track: document.getElementById('first-reg-year-range-track'),
                min: 1950,
                max: 2027
            },
            {
                minSlider: document.getElementById('horsepower-slider-min'),
                maxSlider: document.getElementById('horsepower-slider-max'),
                minInput: document.getElementById('horsepower-min'),
                maxInput: document.getElementById('horsepower-max'),
                minHandle: document.getElementById('horsepower-handle-min'),
                maxHandle: document.getElementById('horsepower-handle-max'),
                track: document.getElementById('horsepower-range-track'),
                min: 0,
                max: 1000
            },
            {
                minSlider: document.getElementById('battery-capacity-slider-min'),
                maxSlider: document.getElementById('battery-capacity-slider-max'),
                minInput: document.getElementById('battery-capacity-min'),
                maxInput: document.getElementById('battery-capacity-max'),
                minHandle: document.getElementById('battery-capacity-handle-min'),
                maxHandle: document.getElementById('battery-capacity-handle-max'),
                track: document.getElementById('battery-capacity-range-track'),
                min: 0,
                max: 500
            },
            {
                minSlider: document.getElementById('owner-tax-slider-min'),
                maxSlider: document.getElementById('owner-tax-slider-max'),
                minInput: document.getElementById('owner-tax-from'),
                maxInput: document.getElementById('owner-tax-to'),
                minHandle: document.getElementById('owner-tax-handle-min'),
                maxHandle: document.getElementById('owner-tax-handle-max'),
                track: document.getElementById('owner-tax-range-track'),
                min: 0,
                max: 20000
            },
            {
                minSlider: document.getElementById('range-km-slider-min'),
                maxSlider: document.getElementById('range-km-slider-max'),
                minInput: document.getElementById('range-km-from'),
                maxInput: document.getElementById('range-km-to'),
                minHandle: document.getElementById('range-km-handle-min'),
                maxHandle: document.getElementById('range-km-handle-max'),
                track: document.getElementById('range-km-range-track'),
                min: 0,
                max: 1500
            },
            {
                minSlider: document.getElementById('fuel-efficiency-slider-min'),
                maxSlider: document.getElementById('fuel-efficiency-slider-max'),
                minInput: document.getElementById('fuel-efficiency-from'),
                maxInput: document.getElementById('fuel-efficiency-to'),
                minHandle: document.getElementById('fuel-efficiency-handle-min'),
                maxHandle: document.getElementById('fuel-efficiency-handle-max'),
                track: document.getElementById('fuel-efficiency-range-track'),
                min: 0,
                max: 100
            },
            {
                minSlider: document.getElementById('top-speed-slider-min'),
                maxSlider: document.getElementById('top-speed-slider-max'),
                minInput: document.getElementById('top-speed-from'),
                maxInput: document.getElementById('top-speed-to'),
                minHandle: document.getElementById('top-speed-handle-min'),
                maxHandle: document.getElementById('top-speed-handle-max'),
                track: document.getElementById('top-speed-range-track'),
                min: 0,
                max: 400
            },
            {
                minSlider: document.getElementById('weight-slider-min'),
                maxSlider: document.getElementById('weight-slider-max'),
                minInput: document.getElementById('weight-from'),
                maxInput: document.getElementById('weight-to'),
                minHandle: document.getElementById('weight-handle-min'),
                maxHandle: document.getElementById('weight-handle-max'),
                track: document.getElementById('weight-range-track'),
                min: 0,
                max: 5000
            }
        ];

        // Reset filters function
        function resetAllFilters() {
            const chipsContainer = document.getElementById('applied-filters-container');
            if (chipsContainer) chipsContainer.dataset.chipsExpanded = '0';
            document.querySelectorAll('input.js-carry-model-year').forEach((el) => el.remove());
            // Reset search input
            if (searchInput) {
                searchInput.value = '';
            }
            if (sortSelect) {
                sortSelect.value = DEFAULT_LISTING_SORT;
            }
            
            // Reset all inputs in filter sidebar
            const inputs = filterSidebar.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else if (input.type === 'range') {
                    // Reset range sliders to their min/max defaults
                    const config = sliderConfigs.find(c => 
                        c.minSlider === input || c.maxSlider === input
                    );
if (config) {
                    if (input === config.minSlider) {
                            input.value = config.min;
                            if (config.minInput) config.minInput.value = '';
                        } else {
                            input.value = config.max;
                            if (config.maxInput) config.maxInput.value = '';
                        }
                        if (config.minSlider) config.minSlider.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                } else if (input.tagName === 'SELECT' && input.multiple) {
                    // Reset multi-select dropdowns
                    Array.from(input.options).forEach(option => {
                        option.selected = false;
                    });
                } else if (input.tagName === 'SELECT') {
                    // Reset single select dropdowns to first option (usually "All")
                    if (input.options.length > 0) {
                        input.selectedIndex = 0;
                    }
                } else {
                    input.value = '';
                }
            });
            
            // Reset listing type checkboxes (uncheck all)
            const listingTypeCheckboxes = document.querySelectorAll('input[name="listing_type_id[]"]');
            listingTypeCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Reset condition to "All" (empty value)
            const conditionAllRadio = document.querySelector('input[name="condition_id"][value=""]');
            if (conditionAllRadio) {
                conditionAllRadio.checked = true;
            }
            
            // Update listing type styles
            if (typeof updateListingTypeStyles === 'function') {
                updateListingTypeStyles();
            }
            
            // Update condition styles
            if (typeof updateConditionStyles === 'function') {
                updateConditionStyles();
            }
            
            // Reset model options
            if (typeof updateModelOptions === 'function') {
                updateModelOptions();
            }
            updateBrandDropdownLabel();
            setModelDropdownEnabled(false);
            if (typeof updateModelDropdownLabel === 'function') updateModelDropdownLabel();
            if (typeof updateFuelTypeDropdownLabel === 'function') updateFuelTypeDropdownLabel();
            
            // Reinitialize sliders after reset
            setTimeout(() => {
                sliderConfigs.forEach(config => initRangeSlider(config));
            }, 50);
            
            // Clear URL parameters and fetch vehicles with empty filters
            const cleanUrl = window.location.pathname;
            window.history.pushState({}, '', cleanUrl);
            
            // Apply the reset by fetching vehicles with no filters (only page 1)
            // This will clear all filter parameters from the URL
            fetchVehicles({ page: 1 });
            
            // Update filter chips (should be empty now) - delay slightly to ensure filters are cleared
            setTimeout(() => {
                renderFilterChips();
                updateResetButtonVisibility();
            }, 100);
        }
        
        // Wire empty-state reset buttons (SSR + dynamically injected)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.empty-state-reset-btn, #no-results-reset-filters');
            if (btn) {
                e.preventDefault();
                resetAllFilters();
            }
        });

        // Attach reset handler to initial button
        if (filterResetButtonMain) {
            filterResetButtonMain.addEventListener('click', resetAllFilters);
        }
        
        // Also use event delegation on container for dynamically created buttons
        const appliedFiltersContainer = document.getElementById('applied-filters-container');
        if (appliedFiltersContainer) {
            appliedFiltersContainer.addEventListener('click', (e) => {
                const resetButton = e.target.closest('#filter-reset-button-main');
                if (resetButton) {
                    e.preventDefault();
                    resetAllFilters();
                }
        });
        }

        // Helper function to check if a value should be included in filters
        // Excludes empty strings, 0, and max/default values
        function shouldIncludeFilterValue(value, maxValue = null) {
            if (!value || value === '' || value === '0') return false;
            const numValue = parseFloat(value);
            if (isNaN(numValue)) return false;
            if (numValue === 0) return false;
            // Exclude max values (these are defaults and shouldn't be applied)
            if (maxValue !== null && numValue >= maxValue) return false;
            return true;
        }
        
        // Single source of truth: read all filter values from sidebar (and search/sort).
        function collectFilters() {
            const filters = {};
            const currentYear = new Date().getFullYear();
            const v = (name) => document.querySelector(`[name="${name}"]`)?.value?.trim();
            const vNum = (name, max) => { const val = v(name); if (!val) return null; const n = parseFloat(val); if (isNaN(n)) return null; if (max != null && n >= max) return null; return val; };

            if (searchInput?.value?.trim()) filters.search = searchInput.value.trim();
            if (sortSelect?.value) filters.sort = sortSelect.value;

            const listingTypeIds = Array.from(document.querySelectorAll('[name="listing_type_id[]"]:checked')).map(cb => cb.value);
            if (listingTypeIds.length > 0) filters.listing_type_id = listingTypeIds;

            const conditionId = document.querySelector('[name="condition_id"]:checked')?.value;
            if (conditionId) filters.condition_id = parseInt(conditionId, 10);

            const brandIds = Array.from(document.getElementsByName('brand_id[]')).filter(cb => cb.checked).map(cb => cb.value);
            if (brandIds.length > 0) filters.brand_id = brandIds;
            const modelIds = Array.from(document.getElementsByName('model_id[]')).filter(cb => cb.checked).map(cb => cb.value);
            if (modelIds.length > 0) filters.model_id = modelIds;
            const fuelTypeIds = Array.from(document.getElementsByName('fuel_type_id[]')).filter(cb => cb.checked).map(cb => cb.value);
            if (fuelTypeIds.length > 0) filters.fuel_type_id = fuelTypeIds;
            if (vNum('price_from')) filters.price_from = v('price_from');
            if (vNum('price_to', {{ $filterPriceMax + 1 }})) filters.price_to = v('price_to');
            if (vNum('km_driven_from')) filters.km_driven_from = v('km_driven_from');
            if (vNum('km_driven_to', {{ $filterKmMax + 1 }})) filters.km_driven_to = v('km_driven_to');
            if (v('gear_type_id')) filters.gear_type_id = v('gear_type_id');
            if (v('body_type_id')) filters.body_type_id = v('body_type_id');
            if (v('color_id')) filters.color_id = v('color_id');
            if (v('sales_type_id')) filters.sales_type_id = v('sales_type_id');
            if (v('price_type_id')) filters.price_type_id = v('price_type_id');
            if (v('emission_norm_id')) filters.emission_norm_id = v('emission_norm_id');
            if (v('use_id')) filters.use_id = v('use_id');
            if (v('model_year_from') && parseInt(v('model_year_from')) > 1950) filters.model_year_from = v('model_year_from');
            if (vNum('model_year_to', currentYear + 2)) filters.model_year_to = v('model_year_to');
            if (v('first_registration_year_from') && parseInt(v('first_registration_year_from')) > 1950) filters.first_registration_year_from = v('first_registration_year_from');
            if (vNum('first_registration_year_to', currentYear + 1)) filters.first_registration_year_to = v('first_registration_year_to');
            if (vNum('ownership_tax_from')) filters.ownership_tax_from = v('ownership_tax_from');
            if (vNum('ownership_tax_to', 20001)) filters.ownership_tax_to = v('ownership_tax_to');
            if (vNum('engine_power_kw_from')) filters.engine_power_kw_from = v('engine_power_kw_from');
            if (vNum('engine_power_kw_to', 1001)) filters.engine_power_kw_to = v('engine_power_kw_to');
            if (vNum('electrical_consumption_from')) filters.electrical_consumption_from = v('electrical_consumption_from');
            if (vNum('electrical_consumption_to', 501)) filters.electrical_consumption_to = v('electrical_consumption_to');
            if (vNum('km_per_liter_from')) filters.km_per_liter_from = v('km_per_liter_from');
            if (vNum('km_per_liter_to', 101)) filters.km_per_liter_to = v('km_per_liter_to');
            if (v('charging_type')) filters.charging_type = v('charging_type');
            if (v('max_speed_from')) filters.max_speed_from = v('max_speed_from');
            if (v('max_speed_to')) filters.max_speed_to = v('max_speed_to');
            if (v('maximum_weight_kg_from')) filters.maximum_weight_kg_from = v('maximum_weight_kg_from');
            if (v('maximum_weight_kg_to')) filters.maximum_weight_kg_to = v('maximum_weight_kg_to');
            if (v('door_count')) filters.door_count = v('door_count');
            if (v('seats_min')) filters.seats_min = v('seats_min');
            if (v('seats_max')) filters.seats_max = v('seats_max');
            if (v('axle_count')) filters.axle_count = v('axle_count');
            if (v('specifications_airbags')) filters.specifications_airbags = v('specifications_airbags');
            if (v('towing_weight')) filters.towing_weight = v('towing_weight');
            const driveAxles = Array.from(document.querySelectorAll('[name="drive_axle_count[]"]:checked')).map(cb => cb.value);
            if (driveAxles.length > 0) filters.drive_axle_count = driveAxles;
            if (document.querySelector('[name="ncap_test"]:checked')) filters.ncap_test = 1;
            if (document.querySelector('[name="is_import"]:checked')) filters.is_import = 1;
            if (document.querySelector('[name="is_factory_new"]:checked')) filters.is_factory_new = 1;
            const equipmentIds = Array.from(document.querySelectorAll('[name="equipment_ids[]"]:checked')).map(cb => cb.value);
            if (equipmentIds.length > 0) filters.equipment_ids = equipmentIds;
            if (typeof window.__viewerGeo === 'object' && window.__viewerGeo
                && typeof window.__viewerGeo.latitude === 'number'
                && typeof window.__viewerGeo.longitude === 'number') {
                filters.viewer_latitude = window.__viewerGeo.latitude;
                filters.viewer_longitude = window.__viewerGeo.longitude;
            }
            filters.limit = 15;
            return filters;
        }
        
        // Auto-apply filters when any filter changes
        let filterDebounceTimer = null;
        
        function autoApplyFilters() {
            clearTimeout(filterDebounceTimer);
            filterDebounceTimer = setTimeout(() => fetchVehicles({ page: 1 }), 500);
        }

        // Types dropdown still uses paged search; brands/models load full lists (or scoped lists) from API.
        const LOOKUP_LIMIT = 25;
        let brandLookupToken = 0;
        let modelLookupToken = 0;
        let typeLookupToken = 0;

        function applyBrandClientFilter() {
            const input = document.getElementById('brand-search-input');
            const q = (input && input.value || '').trim().toLowerCase();
            document.querySelectorAll('#brand-checkbox-list .brand-checkbox-label').forEach(label => {
                const span = label.querySelector('span');
                const t = (span && span.textContent || '').toLowerCase();
                label.style.display = !q || t.includes(q) ? '' : 'none';
            });
        }

        function applyModelClientFilter() {
            const input = document.getElementById('model-search-input');
            const q = (input && input.value || '').trim().toLowerCase();
            document.querySelectorAll('#model-checkbox-list .model-checkbox-label').forEach(label => {
                const span = label.querySelector('.model-checkbox-name');
                const t = (span && span.textContent || '').toLowerCase();
                label.style.display = !q || t.includes(q) ? '' : 'none';
            });
        }

        function getSelectedBrandMeta() {
            const meta = {};
            Array.from(document.querySelectorAll('input[name="brand_id[]"]:checked')).forEach(cb => {
                const label = cb.closest('label');
                const span = label ? (label.querySelector('span:last-child') || label.querySelector('span')) : null;
                meta[String(cb.value)] = span ? span.textContent.trim() : String(cb.value);
            });
            return meta;
        }

        function getSelectedModelMeta() {
            const meta = {};
            Array.from(document.querySelectorAll('input[name="model_id[]"]:checked')).forEach(cb => {
                const label = cb.closest('label');
                const span = label ? (label.querySelector('.model-checkbox-name') || label.querySelector('span')) : null;
                const brandId = label ? String(label.getAttribute('data-brand-id') || '') : '';
                meta[String(cb.value)] = {
                    text: span ? span.textContent.trim() : String(cb.value),
                    brandId
                };
            });
            return meta;
        }

        async function refreshBrandsFromApi() {
            const list = document.getElementById('brand-checkbox-list');
            if (!list) return;

            const selectedMeta = getSelectedBrandMeta();
            const selectedIds = new Set(Object.keys(selectedMeta));

            const token = ++brandLookupToken;
            const url = new URL('/api/v1/brands', window.location.origin);

            try {
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!response.ok) return;
                const json = await response.json().catch(() => ({}));
                if (token !== brandLookupToken) return;

                const items = json?.data?.items || [];
                list.innerHTML = '';

                const resultsIds = new Set();
                items.forEach(item => {
                    const id = String(item.id);
                    resultsIds.add(id);

                    const label = document.createElement('label');
                    label.className = 'brand-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm';

                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.name = 'brand_id[]';
                    input.value = id;
                    input.className = 'brand-checkbox rounded border-input';
                    input.checked = selectedIds.has(id);

                    const span = document.createElement('span');
                    span.textContent = item.name;

                    label.appendChild(input);
                    label.appendChild(span);
                    list.appendChild(label);
                });

                Object.keys(selectedMeta).forEach(id => {
                    if (resultsIds.has(id)) return;
                    const label = document.createElement('label');
                    label.className = 'brand-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm';

                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.name = 'brand_id[]';
                    input.value = id;
                    input.className = 'brand-checkbox rounded border-input';
                    input.checked = true;

                    const span = document.createElement('span');
                    span.textContent = selectedMeta[id];

                    label.appendChild(input);
                    label.appendChild(span);
                    list.appendChild(label);
                });

                updateBrandDropdownLabel();
                applyBrandClientFilter();
            } catch (e) {
                console.debug('Brand load failed:', e);
            }
        }

        function appendModelHintRow(list, text) {
            const p = document.createElement('p');
            p.className = 'model-list-hint text-muted-foreground text-xs px-2 py-2';
            p.textContent = text;
            list.appendChild(p);
        }

        async function refreshModelsFromApi() {
            const list = document.getElementById('model-checkbox-list');
            if (!list) return;

            const selectedMeta = getSelectedModelMeta();
            const selectedIds = new Set(Object.keys(selectedMeta));
            const selectedBrandIds = Array.from(document.querySelectorAll('input[name="brand_id[]"]:checked')).map(cb => String(cb.value).trim()).filter(Boolean);

            const token = ++modelLookupToken;

            if (selectedBrandIds.length === 0) {
                list.innerHTML = '';
                appendModelHintRow(list, I18N_BMV.selectBrandForModels);
                Object.keys(selectedMeta).forEach(id => {
                    const meta = selectedMeta[id];
                    const label = document.createElement('label');
                    label.className = 'model-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm';
                    label.setAttribute('data-brand-id', String(meta.brandId || ''));
                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.name = 'model_id[]';
                    input.value = id;
                    input.className = 'model-checkbox rounded border-input';
                    input.checked = true;
                    const span = document.createElement('span');
                    span.className = 'model-checkbox-name';
                    span.textContent = meta.text;
                    label.appendChild(input);
                    label.appendChild(span);
                    list.appendChild(label);
                });
                updateModelDropdownLabel();
                applyModelClientFilter();
                return;
            }

            const url = new URL('/api/v1/listing-models', window.location.origin);
            url.searchParams.set('brand_ids', selectedBrandIds.join(','));

            try {
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!response.ok) return;
                const json = await response.json().catch(() => ({}));
                if (token !== modelLookupToken) return;

                const items = json?.data?.items || [];
                list.innerHTML = '';

                const resultsIds = new Set();
                items.forEach(item => {
                    const id = String(item.id);
                    resultsIds.add(id);

                    const label = document.createElement('label');
                    label.className = 'model-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm';
                    label.setAttribute('data-brand-id', String(item.brand_id || ''));

                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.name = 'model_id[]';
                    input.value = id;
                    input.className = 'model-checkbox rounded border-input';
                    input.checked = selectedIds.has(id);

                    const span = document.createElement('span');
                    span.className = 'model-checkbox-name';
                    span.textContent = item.name;

                    label.appendChild(input);
                    label.appendChild(span);
                    list.appendChild(label);
                });

                Object.keys(selectedMeta).forEach(id => {
                    if (resultsIds.has(id)) return;
                    const meta = selectedMeta[id];

                    const label = document.createElement('label');
                    label.className = 'model-checkbox-label flex items-center gap-2 cursor-pointer hover:bg-muted/50 rounded px-2 py-1.5 text-sm';
                    label.setAttribute('data-brand-id', String(meta.brandId || ''));

                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.name = 'model_id[]';
                    input.value = id;
                    input.className = 'model-checkbox rounded border-input';
                    input.checked = true;

                    const span = document.createElement('span');
                    span.className = 'model-checkbox-name';
                    span.textContent = meta.text;

                    label.appendChild(input);
                    label.appendChild(span);
                    list.appendChild(label);
                });

                updateModelDropdownLabel();
                applyModelClientFilter();
            } catch (e) {
                console.debug('Model load failed:', e);
            }
        }

        async function refreshTypesFromApi(searchTerm) {
            const typeSelect = document.getElementById('type-select');
            if (!typeSelect) return;

            const currentValue = typeSelect.value || '';
            const currentText = typeSelect.querySelector(`option[value="${currentValue}"]`)?.textContent?.trim() || '';

            const token = ++typeLookupToken;
            const term = (searchTerm || '').trim();

            // If empty search, just keep current selection (plus "All").
            if (term === '') {
                typeSelect.innerHTML = '';
                const allOpt = document.createElement('option');
                allOpt.value = '';
                allOpt.textContent = '{{ __("messages.common.all") }}';
                typeSelect.appendChild(allOpt);

                if (currentValue !== '' && currentText) {
                    const opt = document.createElement('option');
                    opt.value = currentValue;
                    opt.textContent = currentText;
                    opt.selected = true;
                    typeSelect.appendChild(opt);
                }
                return;
            }

            const url = new URL('/api/v1/types', window.location.origin);
            url.searchParams.set('limit', String(LOOKUP_LIMIT));
            url.searchParams.set('search', term);

            try {
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!response.ok) return;
                const json = await response.json().catch(() => ({}));
                if (token !== typeLookupToken) return;

                const items = json?.data?.items || [];

                typeSelect.innerHTML = '';
                const allOpt = document.createElement('option');
                allOpt.value = '';
                allOpt.textContent = '{{ __("messages.common.all") }}';
                typeSelect.appendChild(allOpt);

                const resultsIds = new Set();
                items.forEach(item => {
                    const id = String(item.id);
                    resultsIds.add(id);

                    const opt = document.createElement('option');
                    opt.value = id;
                    opt.textContent = item.name;
                    if (String(currentValue) === id) opt.selected = true;
                    typeSelect.appendChild(opt);
                });

                // Ensure the current selection stays in the dropdown even if it doesn't match results.
                if (currentValue !== '' && !resultsIds.has(String(currentValue))) {
                    const opt = document.createElement('option');
                    opt.value = String(currentValue);
                    opt.textContent = currentText || String(currentValue);
                    opt.selected = true;
                    typeSelect.appendChild(opt);
                }
            } catch (e) {
                console.debug('Type lookup failed:', e);
            }
        }

        // Update brand dropdown trigger label from checked checkboxes
        function updateBrandDropdownLabel() {
            const labelEl = document.getElementById('brand-dropdown-label');
            if (!labelEl) return;
            const checked = Array.from(document.getElementsByName('brand_id[]')).filter(cb => cb.checked);
            const names = checked.map(cb => (cb.closest('label') && cb.closest('label').querySelector('span:last-child')) ? cb.closest('label').querySelector('span:last-child').textContent.trim() : '').filter(Boolean);
            const summary = formatMultiSelectSummary(names);
            labelEl.textContent = summary;
            labelEl.title = names.join(', ');
            setModelDropdownEnabled(names.length > 0);
        }

        function setModelDropdownEnabled(enabled) {
            const modelTrigger = document.getElementById('model-dropdown-trigger');
            const modelPanel = document.getElementById('model-dropdown-panel');
            if (modelTrigger) {
                modelTrigger.disabled = !enabled;
                modelTrigger.classList.toggle('opacity-50', !enabled);
                modelTrigger.classList.toggle('cursor-not-allowed', !enabled);
            }
            if (!enabled && modelPanel) {
                modelPanel.classList.add('hidden');
                if (modelTrigger) modelTrigger.setAttribute('aria-expanded', 'false');
            }
            if (!enabled) {
                document.querySelectorAll('input[name="model_id[]"]:checked').forEach(cb => {
                    const label = cb.closest('.model-checkbox-label');
                    const brandId = label ? String(label.getAttribute('data-brand-id') || '') : '';
                    const selectedBrandIds = new Set(Array.from(document.querySelectorAll('input[name="brand_id[]"]:checked')).map(b => String(b.value)));
                    if (!selectedBrandIds.has(brandId)) cb.checked = false;
                });
                updateModelDropdownLabel();
            }
        }

        // Update model dropdown trigger label from checked checkboxes
        function updateModelDropdownLabel() {
            const labelEl = document.getElementById('model-dropdown-label');
            if (!labelEl) return;
            const checked = Array.from(document.getElementsByName('model_id[]')).filter(cb => cb.checked);
            const names = checked.map(cb => (cb.closest('label') && cb.closest('label').querySelector('.model-checkbox-name')) ? cb.closest('label').querySelector('.model-checkbox-name').textContent.trim() : '').filter(Boolean);
            if (names.length === 0) labelEl.textContent = '{{ __("messages.common.all") }}';
            else if (names.length === 1) labelEl.textContent = names[0];
            else labelEl.textContent = names.length + ' {{ __("messages.forms.selected") }}';
        }

        // Update fuel type dropdown trigger label from checked checkboxes
        function updateFuelTypeDropdownLabel() {
            const labelEl = document.getElementById('fuel-type-dropdown-label');
            if (!labelEl) return;
            const checked = Array.from(document.getElementsByName('fuel_type_id[]')).filter(cb => cb.checked);
            const names = checked.map(cb => (cb.closest('label') && cb.closest('label').querySelector('.fuel-type-checkbox-name')) ? cb.closest('label').querySelector('.fuel-type-checkbox-name').textContent.trim() : '').filter(Boolean);
            if (names.length === 0) labelEl.textContent = '{{ __("messages.common.all") }}';
            else if (names.length === 1) labelEl.textContent = names[0];
            else labelEl.textContent = names.length + ' {{ __("messages.forms.selected") }}';
        }

        // Initialize dropdown toggle and outside-click close for brand/model/fuel-type multiselects
        function initBrandModelDropdowns() {
            const brandTrigger = document.getElementById('brand-dropdown-trigger');
            const brandPanel = document.getElementById('brand-dropdown-panel');
            const modelTrigger = document.getElementById('model-dropdown-trigger');
            const modelPanel = document.getElementById('model-dropdown-panel');
            const fuelTypeTrigger = document.getElementById('fuel-type-dropdown-trigger');
            const fuelTypePanel = document.getElementById('fuel-type-dropdown-panel');
            const brandSearchInput = document.getElementById('brand-search-input');
            const modelSearchInput = document.getElementById('model-search-input');

            function closeAll() {
                if (brandPanel) { brandPanel.classList.add('hidden'); if (brandTrigger) brandTrigger.setAttribute('aria-expanded', 'false'); }
                if (modelPanel) { modelPanel.classList.add('hidden'); if (modelTrigger) modelTrigger.setAttribute('aria-expanded', 'false'); }
                if (fuelTypePanel) { fuelTypePanel.classList.add('hidden'); if (fuelTypeTrigger) fuelTypeTrigger.setAttribute('aria-expanded', 'false'); }
                document.querySelectorAll('.brand-dropdown-trigger .dropdown-chevron, .model-dropdown-trigger .dropdown-chevron, .fuel-type-dropdown-trigger .dropdown-chevron').forEach(el => { el.style.transform = ''; });
            }

            if (brandTrigger && brandPanel) {
                brandTrigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !brandPanel.classList.contains('hidden');
                    closeAll();
                    if (!isOpen) {
                        brandPanel.classList.remove('hidden');
                        brandTrigger.setAttribute('aria-expanded', 'true');
                        const chev = brandTrigger.querySelector('.dropdown-chevron');
                        if (chev) chev.style.transform = 'rotate(180deg)';
                        refreshBrandsFromApi();
                    }
                });
            }
            if (modelTrigger && modelPanel) {
                modelTrigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !modelPanel.classList.contains('hidden');
                    closeAll();
                    if (!isOpen) {
                        modelPanel.classList.remove('hidden');
                        modelTrigger.setAttribute('aria-expanded', 'true');
                        const chev = modelTrigger.querySelector('.dropdown-chevron');
                        if (chev) chev.style.transform = 'rotate(180deg)';
                        refreshModelsFromApi();
                    }
                });
            }
            if (fuelTypeTrigger && fuelTypePanel) {
                fuelTypeTrigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !fuelTypePanel.classList.contains('hidden');
                    closeAll();
                    if (!isOpen) {
                        fuelTypePanel.classList.remove('hidden');
                        fuelTypeTrigger.setAttribute('aria-expanded', 'true');
                        const chev = fuelTypeTrigger.querySelector('.dropdown-chevron');
                        if (chev) chev.style.transform = 'rotate(180deg)';
                    }
                });
            }
            document.addEventListener('click', () => closeAll());

            document.querySelectorAll('.brand-dropdown-panel, .model-dropdown-panel, .fuel-type-dropdown-panel').forEach(panel => {
                panel.addEventListener('click', (e) => e.stopPropagation());
            });

            // Use delegation for dynamically injected brand/model checkboxes.
            if (brandPanel) {
                const brandList = brandPanel.querySelector('#brand-checkbox-list');
                if (brandList) {
                    brandList.addEventListener('change', (e) => {
                        if (e.target && e.target.classList && e.target.classList.contains('brand-checkbox')) {
                            updateBrandDropdownLabel();
                            if (typeof refreshModelsFromApi === 'function') refreshModelsFromApi();
                        }
                    });
                }
            }
            if (modelPanel) {
                const modelList = modelPanel.querySelector('#model-checkbox-list');
                if (modelList) {
                    modelList.addEventListener('change', (e) => {
                        if (e.target && e.target.classList && e.target.classList.contains('model-checkbox')) updateModelDropdownLabel();
                    });
                }
            }
            document.querySelectorAll('.fuel-type-checkbox').forEach(cb => {
                cb.addEventListener('change', updateFuelTypeDropdownLabel);
            });

            // Brand/model: client-side filter only (full lists loaded from API).
            if (brandSearchInput) {
                brandSearchInput.addEventListener('input', () => applyBrandClientFilter());
            }
            if (modelSearchInput) {
                modelSearchInput.addEventListener('input', () => applyModelClientFilter());
            }

            // Type dropdown search.
            const typeSearchInput = document.getElementById('type-search-input');
            if (typeSearchInput) {
                let t = null;
                typeSearchInput.addEventListener('input', () => {
                    clearTimeout(t);
                    t = setTimeout(() => refreshTypesFromApi(typeSearchInput.value), 300);
                });
            }
        }

        // Set up auto-apply listeners for all filter inputs
        function setupAutoApplyFilters() {
            if (!filterSidebar) return;
            
            // Radio buttons (listing type, condition)
            filterSidebar.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', autoApplyFilters);
            });
            
            // Checkboxes (use event delegation to support dynamically injected items)
            filterSidebar.addEventListener('change', (e) => {
                const target = e.target;
                if (!(target instanceof Element)) return;

                if (target.matches('input[type="checkbox"]')) {
                    if (target.classList.contains('brand-checkbox')) {
                        updateBrandDropdownLabel();
                        if (typeof refreshModelsFromApi === 'function') refreshModelsFromApi();
                    }
                    autoApplyFilters();
                }
            });
            
            // Select dropdowns
            filterSidebar.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', autoApplyFilters);
            });
            
            // Number inputs (with debounce)
            filterSidebar.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(filterDebounceTimer);
                    filterDebounceTimer = setTimeout(() => {
                        autoApplyFilters();
                    }, 800); // Longer debounce for number inputs
                });
            });
            
            // Range sliders: no input listener here; filter is applied on handle release in initRangeSlider
        }
        
        
        // Update view toggle button styles
        function updateViewToggleStyles() {
            viewToggleRadios.forEach(radio => {
                const label = radio.closest('.view-toggle-label');
                if (label) {
                    if (radio.checked) {
                        label.classList.add('bg-primary', 'text-primary-foreground', 'font-semibold');
                        label.classList.remove('bg-card', 'text-muted-foreground', 'border-input');
                    } else {
                        label.classList.remove('bg-primary', 'text-primary-foreground', 'font-semibold');
                        label.classList.add('bg-card', 'text-muted-foreground', 'border-input');
                    }
                }
            });
        }
        
        // View toggle functionality
        function setView(view) {
            if (!vehicleContainer || (view !== 'card' && view !== 'list')) return;
            
            // Force card view on mobile devices
            if (isMobile() && view === 'list') {
                view = 'card';
            }
            
            currentView = view;
            localStorage.setItem('vehicleView', view);
            
            // Update radio button selection
            viewToggleRadios.forEach(radio => {
                radio.checked = radio.value === view;
            });
            
            // Update container data attribute and classes
            vehicleContainer.setAttribute('data-view', view);
            if (view === 'list') {
                vehicleContainer.classList.remove('grid', 'grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-3');
                vehicleContainer.classList.add('flex', 'flex-col');
            } else {
                vehicleContainer.classList.add('grid', 'grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-3');
                vehicleContainer.classList.remove('flex', 'flex-col');
            }
            
            // Update view toggle button styles
            updateViewToggleStyles();
        }
        
        // View toggle radio button change handlers
        viewToggleRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                // Prevent switching to list view on mobile
                if (isMobile() && radio.value === 'list') {
                    radio.checked = false;
                    const cardRadio = document.querySelector('input[name="view-toggle"][value="card"]');
                    if (cardRadio) cardRadio.checked = true;
                    updateViewToggleStyles();
                    return;
                }
                
                if (radio.checked) {
                    setView(radio.value);
                }
            });
        });
        
        // Handle window resize to force card view if switching to mobile
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (isMobile() && currentView === 'list') {
                    setView('card');
                }
            }, 250);
        });
        
        // Initialize view on page load
        if (currentView) {
            // Force card view on mobile
            if (isMobile()) {
                currentView = 'card';
                localStorage.setItem('vehicleView', 'card');
            }
            setView(currentView);
        }
        
        // Initialize filter chips and reset button visibility on page load
        renderFilterChips();
        updateResetButtonVisibility();
        updateBrandDropdownLabel();
        setModelDropdownEnabled(document.querySelectorAll('input[name="brand_id[]"]:checked').length > 0);

        // Clear filter params from URL (state lives in sidebar + POST only) then run first search
        if (window.location.search) {
            history.replaceState({}, '', window.location.pathname || '/vehicles');
        }
        window.__viewerGeo = window.__viewerGeo || null;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    window.__viewerGeo = { latitude: pos.coords.latitude, longitude: pos.coords.longitude };
                    const ss = document.getElementById('sort-select');
                    if (ss && (ss.value === 'distance_asc' || ss.value === 'distance_desc')) {
                        fetchVehicles({ page: 1 });
                    }
                },
                function() {},
                { maximumAge: 600000, timeout: 8000 }
            );
        }
        fetchVehicles({ page: 1 });
        
        // Initialize auto-apply filters for sidebar
        setupAutoApplyFilters();
        
        // Initialize brand/model dropdown toggles and label updates
        initBrandModelDropdowns();
        // Prefetch full brand list for faster first open
        if (typeof refreshBrandsFromApi === 'function') refreshBrandsFromApi();
        // If URL/state already has brands selected, load models
        if (document.querySelector('input[name="brand_id[]"]:checked') && typeof refreshModelsFromApi === 'function') {
            refreshModelsFromApi();
        }
        
        // Initialize all range sliders
        setTimeout(() => {
            sliderConfigs.forEach(config => initRangeSlider(config));
            setupEquipmentCollapsible();
            updateConditionStyles();
        }, 100);
        
        // Get access token from cookie helper
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        
        // Check if user is authenticated
        function isUserAuthenticated() {
            return getCookie('access_token') !== null;
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
                    const response = await fetch(`/favorites/${vehicleId}`, {
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
                            heartIcon.classList.add('text-primary');
                            heartIcon.classList.remove('text-foreground');
                        } else {
                            heartIcon.classList.add('text-foreground');
                            heartIcon.classList.remove('text-primary');
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
                    const response = await fetch('/favorites', {
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
                        heartIcon.classList.remove('text-primary', 'text-foreground');
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

    })();
</script>
@endpush
@endsection

