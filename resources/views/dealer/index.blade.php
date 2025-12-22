@extends('layouts.dealer')

@section('title', 'Dashboard - Dealer Panel')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/recharts@2.12.7/dist/Recharts.css" />
@endpush

@section('content')
<div class="flex flex-col gap-4">
    <!-- Dashboard Header -->
    <div>
        <h1 class="text-xl font-bold">Home of Dealer</h1>
        <p class="text-lg">
            @php
                $hour = (int)date('H');
                $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
            @endphp
            {{ $greeting }} <span class="font-semibold">Abdul Hadi</span>!
        </p>
    </div>
    
    <!-- Financial Overview Chart -->
    <div class="border-none bg-transparent p-0">
        <div class="space-y-2 border-b border-border p-0">
            <div class="flex items-center gap-2 space-y-0 sm:flex-row">
                <div class="grid flex-1 gap-1">
                    <h3 class="text-lg font-semibold leading-normal">Financial Overview</h3>
                    <p class="text-muted-foreground text-sm">
                        Showing financial metrics for the last year.
                    </p>
                </div>
                
                <select id="year-select" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="year">Year</option>
                    <option value="month">Month</option>
                    <option value="week">Week</option>
                </select>
            </div>
            
            <!-- Financial Overview Cards -->
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <!-- Revenue Card -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm">Revenue</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="12" x2="12" y1="2" y2="22"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold">600.000 kr.</h3>
                    <p class="text-muted-foreground flex items-center gap-1.5 text-xs text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                        +5.2% from last year
                    </p>
                </div>
                
                <!-- Expense Card -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm">Expense</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <path d="M22 6l-10 7L2 6"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold">3.060 kr.</h3>
                    <p class="text-muted-foreground flex items-center gap-1.5 text-xs text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"></polyline>
                            <polyline points="16 17 22 17 22 11"></polyline>
                        </svg>
                        -2.1% from last year
                    </p>
                </div>
                
                <!-- Net Profit Card -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm">Net Profit</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold">596.940 kr.</h3>
                    <p class="text-muted-foreground flex items-center gap-1.5 text-xs text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                        +5.5% from last year
                    </p>
                </div>
                
                <!-- Profit Margin Card -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm">Profit Margin</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="12" x2="12" y1="2" y2="22"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold">99.5%</h3>
                    <p class="text-muted-foreground flex items-center gap-1.5 text-xs text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                        +0.3% from last year
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Chart Area (Placeholder) -->
        <div class="pb-8 pt-4">
            <div class="bg-muted/30 h-[250px] w-full rounded-lg flex items-center justify-center">
                <p class="text-muted-foreground text-sm">Financial Trend Chart - Chart library integration pending</p>
            </div>
        </div>
    </div>
    
    <!-- Overview Cards Grid -->
    <div class="grid grid-cols-1 gap-8 py-4 lg:grid-cols-2">
        <!-- Vehicles Overview Card -->
        <div class="w-full border-none bg-transparent p-0">
            <div class="p-0">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Vehicles Overview</h3>
                        <p class="text-muted-foreground text-sm">Current stock and status</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-md border border-border px-2.5 py-0.5 text-xs font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.7 5c-.3-.9-1.2-1.5-2.2-1.5h-3c-.6 0-1 .4-1 1s.4 1 1 1h3c.2 0 .4.2.4.4l1.2 3.6c-.1 0-.2-.1-.3-.1H5c-.2 0-.4.1-.5.2L3.4 5c-.1-.2-.3-.4-.5-.4H2"></path>
                            <path d="M3 17h14"></path>
                            <path d="M5 17V9h14v8"></path>
                        </svg>
                        7 Total Vehicles
                    </span>
                </div>
            </div>
            
            <div class="space-y-6 p-0">
                <!-- Vehicles Overview Stats -->
                <div class="space-y-3">
                    <h4 class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Vehicles Overview</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="bg-card flex items-center justify-center gap-3 rounded-lg border border-border p-4">
                            <div class="bg-muted text-muted-foreground rounded-md p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" x2="12" y1="22.08" y2="12"></line>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-muted-foreground text-sm font-medium">Available</p>
                                <p class="text-lg font-semibold">5</p>
                            </div>
                        </div>
                        <div class="bg-card flex items-center justify-center gap-3 rounded-lg border border-border p-4">
                            <div class="bg-muted text-muted-foreground rounded-md p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-muted-foreground text-sm font-medium">Pending</p>
                                <p class="text-lg font-semibold">1</p>
                            </div>
                        </div>
                        <div class="bg-card flex items-center justify-center gap-3 rounded-lg border border-border p-4">
                            <div class="bg-muted text-muted-foreground rounded-md p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-muted-foreground text-sm font-medium">Need Work</p>
                                <p class="text-lg font-semibold">1</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sales Overview Card -->
        <div class="w-full border-none bg-transparent p-0">
            <div class="p-0">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Sales Overview</h3>
                        <p class="text-muted-foreground text-sm">Sales performance and revenue insights</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-md border border-border px-2.5 py-0.5 text-xs font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        4 Total Sales
                    </span>
                </div>
            </div>
            
            <div class="space-y-6 p-0">
                <!-- Revenue Overview -->
                <div class="space-y-3">
                    <h4 class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Revenue Overview</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="bg-card flex items-center justify-center gap-3 rounded-lg border border-border p-4">
                            <div class="bg-muted text-muted-foreground rounded-md p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <line x1="12" x2="12" y1="2" y2="22"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-muted-foreground text-sm font-medium">Total Revenue</p>
                                <p class="text-lg font-semibold">600.000 kr.</p>
                            </div>
                        </div>
                        <div class="bg-card flex items-center justify-center gap-3 rounded-lg border border-border p-4">
                            <div class="bg-muted text-muted-foreground rounded-md p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-muted-foreground text-sm font-medium">Average Sale Value</p>
                                <p class="text-lg font-semibold">150.000 kr.</p>
                            </div>
                        </div>
                        <div class="bg-card flex items-center justify-center gap-3 rounded-lg border border-border p-4">
                            <div class="bg-muted text-muted-foreground rounded-md p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-muted-foreground text-sm font-medium">Avg Days to Sell</p>
                                <p class="text-lg font-semibold">45</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

