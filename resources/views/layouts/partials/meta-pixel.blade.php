{{-- Meta Pixel base code. Enabled via platform marketing settings (production only). --}}
@php
    $metaPixelEnabled = app()->environment('production') && filter_var(
        app(\App\Services\PlatformSettingService::class)->get('marketing', 'meta_pixel_enabled', false),
        FILTER_VALIDATE_BOOLEAN
    );
    $metaPixelId = trim((string) app(\App\Services\PlatformSettingService::class)->get('marketing', 'meta_pixel_id', ''));
@endphp
@if($metaPixelEnabled && $metaPixelId !== '')
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', @json($metaPixelId));
fbq('track', 'PageView'@if(!empty($metaPageViewEventId)), {eventID: @json($metaPageViewEventId)}@endif);
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1"/></noscript>
@endif
