@php
    $panelUrl = $panelUrl ?? config('payments.panel_url');
    $variant = $variant ?? 'dealer';
@endphp
<footer class="bg-muted border-t border-border py-12 md:py-16">
    <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-4 lg:col-span-2">
                <a href="{{ $variant === 'staff' ? route('for-staff.landing') : route('for-dealers.landing') }}" class="flex items-center space-x-2">
                    <img src="/images/logo.png" alt="{{ __('messages.common.site_name') }}" class="h-8">
                </a>
                <p class="text-sm text-muted-foreground max-w-md">
                    {{ $variant === 'staff'
                        ? __('messages.staff_marketing.footer.description')
                        : __('messages.dealer_marketing.footer.description') }}
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-4">{{ __('messages.dealer_marketing.footer.product') }}</h3>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    @if($variant === 'dealer')
                        <li><a href="{{ route('for-dealers.pricing') }}" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.nav.pricing') }}</a></li>
                        <li><a href="{{ route('for-dealers.resources') }}" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.nav.resources') }}</a></li>
                        <li><a href="{{ $panelUrl }}/auth/register" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.nav.create_account') }}</a></li>
                    @else
                        <li><a href="{{ route('for-staff.resources') }}" class="hover:text-foreground transition-colors">{{ __('messages.staff_marketing.nav.resources') }}</a></li>
                        <li><a href="{{ $panelUrl }}/auth/staff-login" class="hover:text-foreground transition-colors">{{ __('messages.staff_marketing.nav.staff_login') }}</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-4">{{ __('messages.dealer_marketing.footer.legal') }}</h3>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    <li><a href="{{ route('privacy-policy') }}" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.footer.privacy') }}</a></li>
                    <li><a href="{{ route('terms-of-service') }}" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.footer.terms') }}</a></li>
                    <li><a href="{{ route('for-dealers.contact') }}" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.nav.contact') }}</a></li>
                    <li><a href="/" class="hover:text-foreground transition-colors">{{ __('messages.dealer_marketing.footer.marketplace') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-10 border-t border-border pt-6 text-center text-sm text-muted-foreground">
            &copy; {{ date('Y') }} {{ __('messages.common.site_name') }}. {{ __('messages.dealer_marketing.footer.rights') }}
        </div>
    </div>
</footer>
