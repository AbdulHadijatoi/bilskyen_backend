@php
    $clarity = $microsoftClarity ?? ['enabled' => true, 'projectId' => 'y8l8s0praw', 'requireConsent' => false];
    $clarityId = is_array($clarity) ? trim((string) ($clarity['projectId'] ?? '')) : '';
    if ($clarityId === '') {
        $clarityId = 'y8l8s0praw';
    }
    $clarityEnabled = $clarityId !== '';
    $clarityNeedsConsent = is_array($clarity) && ! empty($clarity['requireConsent']);
    $touch = $trafficAttribution ?? [];
@endphp
@if($clarityEnabled)
<script>
(function () {
    var projectId = @json($clarityId);
    var needsConsent = @json((bool) $clarityNeedsConsent);
    var tags = {
        traffic_source: @json($touch['traffic_source'] ?? 'other'),
        campaign: @json($touch['utm_campaign'] ?? ''),
        vehicle_id: @json(isset($vehicle) ? (string) $vehicle->id : '')
    };

    function applyTags() {
        if (typeof window.clarity !== 'function') return;
        Object.keys(tags).forEach(function (key) {
            if (tags[key]) window.clarity('set', key, String(tags[key]));
        });
    }

    function maskInputs() {
        document.querySelectorAll('input, textarea, select').forEach(function (el) {
            el.setAttribute('data-clarity-mask', 'true');
        });
    }

    function loadClarity() {
        if (window.__bilskyenClarityLoaded) return;
        window.__bilskyenClarityLoaded = true;
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", projectId);
        maskInputs();
        applyTags();
    }

    function maybeLoad() {
        if (needsConsent && !localStorage.getItem('cookie_consent_accepted')) return;
        if ('requestIdleCallback' in window) {
            requestIdleCallback(loadClarity, { timeout: 4000 });
        } else {
            window.addEventListener('load', loadClarity);
        }
    }

    maybeLoad();
    document.addEventListener('click', function (event) {
        if (event.target && event.target.id === 'cookie-consent-accept') {
            setTimeout(loadClarity, 0);
        }
    });
})();
</script>
@endif
