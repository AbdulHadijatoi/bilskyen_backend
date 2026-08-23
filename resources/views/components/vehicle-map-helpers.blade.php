<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    [data-vehicle-map-wrap] {
        position: relative;
        z-index: 0;
        isolation: isolate;
        overflow: hidden;
    }
    #vehicle-detail-map[data-vehicle-map],
    #vehicle-detail-map[data-vehicle-map].leaflet-container {
        height: 18rem;
        width: 100%;
        z-index: 0;
    }
</style>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.querySelector('[data-vehicle-map-wrap]');
    const el = document.querySelector('[data-vehicle-map]');
    if (!wrap || !el || typeof L === 'undefined') {
        return;
    }
    const lat = Number(el.getAttribute('data-lat'));
    const lng = Number(el.getAttribute('data-lng'));
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }
    wrap.hidden = false;
    const map = L.map(el, { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);
    const title = el.getAttribute('data-title') || '';
    const address = el.getAttribute('data-address') || '';
    const popup = [title, address].filter(Boolean).map(function (value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }).join('<br>');
    L.marker([lat, lng]).addTo(map).bindPopup(popup);
    map.setView([lat, lng], 13);
    setTimeout(function () { map.invalidateSize(); }, 80);
});
</script>
