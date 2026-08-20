{{-- Google Ads (gtag.js). Production only. Deferred so it does not block LCP/INP. --}}
@if(app()->environment('production'))
<script>
(function () {
  var conversionId = 'AW-18364502271';
  function loadGtag() {
    if (window.__bilskyenGtagLoaded) return;
    window.__bilskyenGtagLoaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { dataLayer.push(arguments); };
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + conversionId;
    s.onload = function () {
      window.gtag('js', new Date());
      window.gtag('config', conversionId);
    };
    document.head.appendChild(s);
  }
  if ('requestIdleCallback' in window) {
    requestIdleCallback(loadGtag, { timeout: 4000 });
  } else {
    window.addEventListener('load', loadGtag);
  }
})();
</script>
@endif
