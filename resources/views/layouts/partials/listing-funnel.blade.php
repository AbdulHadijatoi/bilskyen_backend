@php
    $funnelVehicleId = isset($vehicle) ? (int) $vehicle->id : 0;
    $funnelUrl = $listingFunnelTrackUrl ?? url('/api/v1/marketing/funnel/track');
@endphp
<script>
(function () {
    var vehicleId = @json($funnelVehicleId);
    var trackUrl = @json($funnelUrl);
    var sent = {};

    function payload(eventName, meta) {
        var body = { event_name: eventName, website: '' };
        if (vehicleId) body.vehicle_id = vehicleId;
        if (meta && typeof meta === 'object') body.meta = meta;
        return JSON.stringify(body);
    }

    function send(eventName, meta) {
        var key = eventName + ':' + (meta && meta.cta ? meta.cta : '') + ':' + (meta && meta.form ? meta.form : '');
        if (eventName !== 'form_error' && sent[key]) return;
        sent[key] = true;
        try {
            var blob = new Blob([payload(eventName, meta)], { type: 'application/json' });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(trackUrl, blob);
                return;
            }
            fetch(trackUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: payload(eventName, meta),
                credentials: 'same-origin',
                keepalive: true
            }).catch(function () {});
        } catch (e) {}
    }

    window.bilskyenTrackFunnel = send;
    window.bilskyenTrackMetaCustom = window.bilskyenTrackMetaCustom || function () {};

    function engaged() {
        send('engaged');
    }

    var engagedTimer = setTimeout(engaged, 8000);
    var onScroll = function () {
        var doc = document.documentElement;
        var height = doc.scrollHeight - doc.clientHeight;
        if (height <= 0 || (window.scrollY || doc.scrollTop) / height >= 0.5) {
            window.removeEventListener('scroll', onScroll);
            clearTimeout(engagedTimer);
            engaged();
        }
    };
    window.addEventListener('scroll', onScroll, { passive: true });

    document.addEventListener('click', function (event) {
        var target = event.target && event.target.closest ? event.target.closest('a.glightbox, .glightbox') : null;
        if (target) send('gallery');
    });
})();
</script>
