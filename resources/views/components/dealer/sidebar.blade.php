@php
    $currentRoute = request()->path();
    $isActive = function($route) use ($currentRoute) {
        return str_starts_with($currentRoute, $route);
    };
    
    // User data (placeholder)
    $userName = 'Abdul Hadi';
    $userEmail = 'abdulhadijatoi@gmail.com';
    $initials = 'AH';
    if ($userName) {
        $names = explode(' ', trim($userName));
        if (count($names) >= 2) {
            $initials = strtoupper(substr($names[0], 0, 1) . substr($names[count($names) - 1], 0, 1));
        } else {
            $initials = strtoupper(substr($userName, 0, 2));
        }
    }
    
    $isMobile = isset($isMobile) && $isMobile;
    $sidebarId = $isMobile ? 'mobile-sidebar-content' : 'sidebar';
    $userMenuToggleId = $isMobile ? 'mobile-user-profile-menu-toggle' : 'user-profile-menu-toggle';
    $userMenuId = $isMobile ? 'mobile-user-profile-menu' : 'user-profile-menu';
@endphp

<aside id="{{ $sidebarId }}" class="bg-sidebar text-sidebar-foreground {{ $isMobile ? 'flex flex-col h-full w-full' : 'fixed inset-y-0 left-0 z-10 hidden h-screen w-64 flex-col border-r border-sidebar-border transition-all duration-300 md:flex' }}">
    <!-- Sidebar Header -->
    <div class="flex flex-col gap-2 p-2 border-b border-sidebar-border sidebar-header">
        <ul class="flex w-full min-w-0 flex-col gap-1 sidebar-menu">
            <li class="group/menu-item relative sidebar-menu-item">
                <a href="/dealer" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 h-12 sidebar-header-button">
                    <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg shrink-0 sidebar-logo">
                        <span class="text-sm font-bold">R</span>
                    </div>
                    <div class="grid flex-1 text-left text-sm leading-tight sidebar-header-text">
                        <span class="truncate font-medium text-sidebar-foreground">Bilskyen</span>
                        <span class="truncate text-xs text-sidebar-foreground">Dealer Panel</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Scrollable Sidebar Content -->
    <div class="flex min-h-0 flex-1 flex-col gap-2 overflow-auto sidebar-content">
        <nav class="flex flex-col gap-2 p-2 sidebar-nav">
            <!-- Dashboard -->
            <div class="relative flex w-full min-w-0 flex-col p-2 sidebar-section sidebar-group">
                <ul class="flex w-full min-w-0 flex-col gap-1 mt-0 sidebar-menu">
                    <li class="group/menu-item relative sidebar-menu-item">
                        <a href="/dealer" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer') && !$isActive('dealer/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                                <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                                <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                                <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                            </svg>
                            <span class="truncate sidebar-nav-text">Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Core Operations -->
            <div class="relative flex w-full min-w-0 flex-col p-2 sidebar-section sidebar-group">
                <p class="ring-sidebar-ring flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium outline-hidden transition-[margin,opacity] duration-200 ease-linear focus-visible:ring-2 sidebar-section-title">Core Operations</p>
                <ul class="flex w-full min-w-0 flex-col gap-1 mt-0 sidebar-menu">
                    <!-- Vehicles (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="vehicles">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/vehicles') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="vehicles">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.7 5c-.3-.9-1.2-1.5-2.2-1.5h-3c-.6 0-1 .4-1 1s.4 1 1 1h3c.2 0 .4.2.4.4l1.2 3.6c-.1 0-.2-.1-.3-.1H5c-.2 0-.4.1-.5.2L3.4 5c-.1-.2-.3-.4-.5-.4H2"></path>
                                <path d="M3 17h14"></path>
                                <path d="M5 17V9h14v8"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Vehicles</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="vehicles">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="vehicles">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/vehicles/overview" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/vehicles/overview') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Overview</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/vehicles/add-vehicle" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/vehicles/add-vehicle') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Vehicle</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Purchases (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="purchases">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/purchases') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="purchases">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <circle cx="8" cy="21" r="1"></circle>
                                <circle cx="19" cy="21" r="1"></circle>
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Purchases</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="purchases">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="purchases">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/purchases" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/purchases') && !$isActive('dealer/purchases/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Overview</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/purchases/add-purchase" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/purchases/add-purchase') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Purchase</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Sales (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="sales">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/sales') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="sales">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                <path d="M4 22h16"></path>
                                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                                <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Sales</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="sales">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="sales">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/sales" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/sales') && !$isActive('dealer/sales/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Overview</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/sales/add-sale" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/sales/add-sale') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Sale</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Expenses (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="expenses">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/expenses') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="expenses">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <path d="M22 6l-10 7L2 6"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Expenses</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="expenses">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="expenses">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/expenses" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/expenses') && !$isActive('dealer/expenses/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Overview</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/expenses/add-expense" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/expenses/add-expense') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Expense</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <!-- Contact Management -->
            <div class="relative flex w-full min-w-0 flex-col p-2 sidebar-section sidebar-group">
                <p class="ring-sidebar-ring flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium outline-hidden transition-[margin,opacity] duration-200 ease-linear focus-visible:ring-2 sidebar-section-title">Contact Management</p>
                <ul class="flex w-full min-w-0 flex-col gap-1 mt-0 sidebar-menu">
                    <!-- Contacts (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="contacts">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/contacts') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="contacts">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Contacts</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="contacts">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="contacts">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/contacts" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/contacts') && !$isActive('dealer/contacts/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Directory</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/contacts/add-contact" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/contacts/add-contact') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Contact</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Enquiries (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="enquiries">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/enquiries') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="enquiries">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Enquiries</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="enquiries">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="enquiries">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/enquiries" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/enquiries') && !$isActive('dealer/enquiries/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Overview</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/enquiries/add-enquiry" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/enquiries/add-enquiry') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Enquiry</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <!-- Accounting & Finance -->
            <div class="relative flex w-full min-w-0 flex-col p-2 sidebar-section sidebar-group">
                <p class="ring-sidebar-ring flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium outline-hidden transition-[margin,opacity] duration-200 ease-linear focus-visible:ring-2 sidebar-section-title">Accounting & Finance</p>
                <ul class="flex w-full min-w-0 flex-col gap-1 mt-0 sidebar-menu">
                    <!-- Transactions (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="transactions">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/accounting/transactions') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="transactions">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <line x1="12" x2="12" y1="2" y2="22"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Transactions</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="transactions">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="transactions">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/accounting/transactions" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/accounting/transactions') && !$isActive('dealer/accounting/add-transaction') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">General Ledger Entries</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/accounting/add-transaction" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/accounting/add-transaction') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Transaction</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Financial Accounts (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="financial-accounts">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/accounting/financial-accounts') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="financial-accounts">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Financial Accounts</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="financial-accounts">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="financial-accounts">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/accounting/financial-accounts" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/accounting/financial-accounts') && !$isActive('dealer/accounting/financial-accounts/add-financial-account') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Chart of Accounts</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/accounting/financial-accounts/add-financial-account" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/accounting/financial-accounts/add-financial-account') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Add Financial Account</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Financial Reports (no children) -->
                    <li class="group/menu-item relative sidebar-menu-item">
                        <a href="/dealer/accounting/financial-reports" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/accounting/financial-reports') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                            </svg>
                            <span class="truncate sidebar-nav-text">Financial Reports</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Settings -->
            <div class="relative flex w-full min-w-0 flex-col p-2 sidebar-section sidebar-group">
                <p class="ring-sidebar-ring flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium outline-hidden transition-[margin,opacity] duration-200 ease-linear focus-visible:ring-2 sidebar-section-title">Settings</p>
                <ul class="flex w-full min-w-0 flex-col gap-1 mt-0 sidebar-menu">
                    <!-- Settings (with children) -->
                    <li class="group/menu-item relative sidebar-menu-item group/collapsible" data-collapsible="settings">
                        <button type="button" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-nav-item {{ $isActive('dealer/settings') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}" data-collapsible-trigger="settings">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 sidebar-icon">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span class="truncate sidebar-nav-text">Settings</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 sidebar-chevron" data-chevron="settings">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                        <ul class="border-sidebar-border mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l px-2.5 py-0.5 sidebar-menu-sub hidden overflow-hidden" data-collapsible-content="settings">
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/settings" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/settings') && !$isActive('dealer/settings/') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">General</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/settings/profile" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/settings/profile') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Profile</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/settings/sessions" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/settings/sessions') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Sessions</span>
                                </a>
                            </li>
                            <li class="group/menu-sub-item relative">
                                <a href="/dealer/settings/change-password" class="text-sm text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 {{ $isActive('dealer/settings/change-password') ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : '' }}">
                                    <span class="truncate">Change Password</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    
    <!-- Sidebar Footer -->
    <div class="shrink-0 border-t border-sidebar-border p-2 sidebar-footer">
        <div class="relative">
            <button id="{{ $userMenuToggleId }}" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm text-sidebar-foreground outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 sidebar-footer-button {{ !$isMobile ? 'w-full rounded-md hover:bg-accent/50 duration-300' : '' }}">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted shrink-0 sidebar-footer-avatar">
                    <span class="text-xs font-medium text-sidebar-foreground">{{ $initials }}</span>
                </div>
                <div class="grid flex-1 text-left text-sm leading-tight sidebar-footer-text">
                    <span class="truncate font-medium text-sidebar-foreground">{{ $userName }}</span>
                    <span class="truncate text-xs text-sidebar-foreground/70">{{ $userEmail }}</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0 sidebar-footer-chevron">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </button>
            
            <!-- User Profile Dropdown -->
            <div id="{{ $userMenuId }}" class="hidden absolute bottom-full left-0 mb-2 w-full rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md z-50">
                <a href="/profile" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Profile
                </a>
                <div class="my-1 h-px bg-border"></div>
                <a href="/dealer/settings" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    Settings
                </a>
                <div class="my-1 h-px bg-border"></div>
                <button class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground text-destructive">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" x2="9" y1="12" y2="12"></line>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </div>
    
    <script>
        (function() {
            function initializeSidebar() {
                const sidebarId = '{{ $sidebarId }}';
                const sidebar = document.getElementById(sidebarId);
                
                if (!sidebar) {
                    // Retry after a short delay if sidebar not found
                    setTimeout(initializeSidebar, 100);
                    return;
                }
                
                // Collapsible menu functionality
                const currentPath = window.location.pathname;
                const collapsibleItems = sidebar.querySelectorAll('[data-collapsible]');
                
                collapsibleItems.forEach(item => {
                    const collapsibleId = item.getAttribute('data-collapsible');
                    const trigger = item.querySelector(`[data-collapsible-trigger="${collapsibleId}"]`);
                    const content = item.querySelector(`[data-collapsible-content="${collapsibleId}"]`);
                    const chevron = item.querySelector(`[data-chevron="${collapsibleId}"]`);
                    
                    if (!trigger || !content) {
                        return;
                    }
                    
                    // Check if current path matches to auto-expand
                    const shouldBeOpen = currentPath.includes(`/dealer/${collapsibleId}`) || 
                                       currentPath.includes(`/dealer/accounting/${collapsibleId}`) ||
                                       (collapsibleId === 'settings' && currentPath.includes(`/dealer/settings`));
                    
                if (shouldBeOpen) {
                    content.classList.remove('hidden');
                    content.setAttribute('data-state', 'open');
                    item.setAttribute('data-state', 'open');
                    if (chevron) {
                        chevron.style.transform = 'rotate(90deg)';
                    }
                } else {
                    content.setAttribute('data-state', 'closed');
                }
                
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isOpen = content.getAttribute('data-state') === 'open';
                    
                    if (isOpen) {
                        content.setAttribute('data-state', 'closed');
                        item.setAttribute('data-state', 'closed');
                        // Use setTimeout to allow animation to complete before hiding
                        setTimeout(() => {
                            content.classList.add('hidden');
                        }, 200);
                        if (chevron) {
                            chevron.style.transform = 'rotate(0deg)';
                        }
                    } else {
                        content.classList.remove('hidden');
                        // Use requestAnimationFrame to ensure the element is visible before animation
                        requestAnimationFrame(() => {
                            content.setAttribute('data-state', 'open');
                            item.setAttribute('data-state', 'open');
                        });
                        if (chevron) {
                            chevron.style.transform = 'rotate(90deg)';
                        }
                    }
                });
                });
                
                // User menu toggle (desktop)
                @if(!$isMobile)
                const userMenuToggle = document.getElementById('user-profile-menu-toggle');
                const userMenu = document.getElementById('user-profile-menu');
                
                if (userMenuToggle && userMenu) {
                    userMenuToggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        userMenu.classList.toggle('hidden');
                    });
                    
                    document.addEventListener('click', (e) => {
                        if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
                            userMenu.classList.add('hidden');
                        }
                    });
                }
                @else
                // User menu toggle (mobile)
                const userMenuToggle = document.getElementById('mobile-user-profile-menu-toggle');
                const userMenu = document.getElementById('mobile-user-profile-menu');
                
                if (userMenuToggle && userMenu) {
                    userMenuToggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        userMenu.classList.toggle('hidden');
                    });
                    
                    document.addEventListener('click', (e) => {
                        if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
                            userMenu.classList.add('hidden');
                        }
                    });
                }
                @endif
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeSidebar);
            } else {
                // DOM is already ready
                initializeSidebar();
            }
        })();
    </script>
</aside>

<style>
    #sidebar.collapsed {
        width: 3rem;
    }
    
    /* Section title color - match Next.js darker appearance (70% opacity) */
    .sidebar-section-title {
        color: var(--sidebar-foreground);
        opacity: 0.7;
    }
    
    /* Hide section titles when collapsed */
    #sidebar.collapsed .sidebar-section-title {
        margin-top: -2rem;
        opacity: 0;
    }
    
    /* Hide text labels in nav links when collapsed */
    #sidebar.collapsed .sidebar-nav-text {
        display: none;
    }
    
    /* Center nav links when collapsed - match Next.js size-8! and p-2! */
    #sidebar.collapsed .sidebar-nav-item {
        justify-content: center;
        width: 2rem !important;
        height: 2rem !important;
        padding: 0.5rem !important;
        gap: 0;
    }
    
    /* Ensure icons are visible when collapsed */
    #sidebar.collapsed .sidebar-icon {
        display: block !important;
    }
    
    /* Hide header text when collapsed, keep logo centered */
    #sidebar.collapsed .sidebar-header-text {
        display: none;
    }
    
    #sidebar.collapsed .sidebar-header-button {
        justify-content: center;
        width: 2rem !important;
        height: 2rem !important;
        padding: 0.5rem !important;
        gap: 0;
    }
    
    #sidebar.collapsed .sidebar-header-button .sidebar-logo {
        margin: 0 auto;
    }
    
    /* Footer: hide text and chevron when collapsed, keep avatar centered */
    #sidebar.collapsed .sidebar-footer-text,
    #sidebar.collapsed .sidebar-footer-chevron {
        display: none;
    }
    
    #sidebar.collapsed .sidebar-footer-button {
        justify-content: center;
        width: 2rem !important;
        height: 2rem !important;
        padding: 0.5rem !important;
        gap: 0;
    }
    
    #sidebar.collapsed .sidebar-footer-avatar {
        margin: 0;
    }
    
    /* Mobile sidebar styling */
    #mobile-sidebar-content {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
    }
    
    /* Collapsible menu styles */
    .sidebar-chevron {
        transition: transform 0.2s ease;
    }
    
    [data-state="open"] .sidebar-chevron {
        transform: rotate(90deg);
    }
    
    /* Collapsible animation */
    .sidebar-menu-sub {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.2s ease-out, opacity 0.2s ease-out;
    }
    
    .sidebar-menu-sub[data-state="open"] {
        max-height: 500px;
        opacity: 1;
    }
    
    .sidebar-menu-sub[data-state="closed"],
    .sidebar-menu-sub.hidden {
        max-height: 0;
        opacity: 0;
    }
    
    /* Hide submenu when sidebar is collapsed */
    #sidebar.collapsed .sidebar-menu-sub {
        display: none !important;
    }
    
    /* Hide chevron when sidebar is collapsed */
    #sidebar.collapsed .sidebar-chevron {
        display: none !important;
    }
    
    /* Mobile responsive */
    @media (max-width: 767px) {
        #sidebar {
            display: none !important;
        }
    }
    
    @media (min-width: 768px) {
        #mobile-sidebar {
            display: none !important;
        }
        #mobile-sidebar-overlay {
            display: none !important;
        }
    }
</style>

