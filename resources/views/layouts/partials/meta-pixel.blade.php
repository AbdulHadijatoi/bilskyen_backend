@php
    $metaPixelService = app(\App\Services\Marketing\MetaConversionsApiService::class);
    $metaPixelId = $metaPixelService->isBrowserEnabled() ? $metaPixelService->pixelId() : '';
@endphp
@if($metaPixelId !== '')
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
    fbq('track', 'PageView');
    window.bilskyenTrackMetaLead = function (eventId, vehicleId, extra) {
        if (typeof fbq !== 'function' || !vehicleId) return;
        var payload = {
            content_ids: [String(vehicleId)],
            content_type: 'vehicle',
            currency: 'DKK'
        };
        if (extra && extra.content_name) payload.content_name = extra.content_name;
        if (extra && extra.value != null) payload.value = extra.value;
        var options = eventId ? { eventID: eventId } : {};
        fbq('track', 'Lead', payload, options);
    };
    window.bilskyenTrackMetaSearch = function (searchString) {
        if (typeof fbq !== 'function') return;
        fbq('track', 'Search', {
            search_string: searchString ? String(searchString) : '',
            content_type: 'vehicle',
            currency: 'DKK'
        });
    };
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"
/></noscript>
@endif
