<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
window.BilskyenListingMap = window.BilskyenListingMap || (function () {
    const DETAIL_URL = (slug) => @json(rtrim(route('vehicle.detail', ['vehicle' => '__SLUG__']), '/')).replace('__SLUG__', encodeURIComponent(slug));
    let map = null;
    let pinLayer = null;
    let viewerMarker = null;
    let radiusCircle = null;
    let lastVehicles = [];

    function pinFromVehicle(vehicle) {
        const lat = Number(vehicle.map_latitude ?? vehicle.latitude);
        const lng = Number(vehicle.map_longitude ?? vehicle.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return null;
        }
        return {
            lat,
            lng,
            slug: vehicle.slug || vehicle.id,
            title: vehicle.title || '',
        };
    }

    function ensureMap() {
        const el = document.querySelector('[data-listing-map]');
        if (!el || typeof L === 'undefined') {
            return null;
        }
        if (map) {
            return map;
        }
        map = L.map(el, { scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 18,
        }).addTo(map);
        pinLayer = L.layerGroup().addTo(map);
        return map;
    }

    function currentRadiusKm() {
        const select = document.querySelector('[name="radius_km"]');
        const value = parseInt(select && select.value, 10);
        return [25, 50, 100, 200].includes(value) ? value : null;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function update(vehicles) {
        if (Array.isArray(vehicles)) {
            lastVehicles = vehicles;
        }
        const wrap = document.querySelector('[data-listing-map-wrap]');
        const pins = lastVehicles.map(pinFromVehicle).filter(Boolean);
        const geo = window.__viewerGeo;
        const hasViewer = geo && typeof geo.latitude === 'number' && typeof geo.longitude === 'number';
        if (!wrap) {
            return;
        }
        if (!pins.length && !hasViewer) {
            wrap.classList.add('hidden');
            return;
        }
        const instance = ensureMap();
        if (!instance) {
            wrap.classList.add('hidden');
            return;
        }
        wrap.classList.remove('hidden');
        pinLayer.clearLayers();
        const bounds = [];
        pins.forEach((pin) => {
            const marker = L.marker([pin.lat, pin.lng]);
            marker.bindPopup(
                '<a href="' + DETAIL_URL(pin.slug) + '">' + escapeHtml(pin.title) + '</a>'
            );
            marker.addTo(pinLayer);
            bounds.push([pin.lat, pin.lng]);
        });
        if (viewerMarker) {
            instance.removeLayer(viewerMarker);
            viewerMarker = null;
        }
        if (radiusCircle) {
            instance.removeLayer(radiusCircle);
            radiusCircle = null;
        }
        if (hasViewer) {
            viewerMarker = L.circleMarker([geo.latitude, geo.longitude], {
                radius: 8,
                color: '#1d4ed8',
                fillColor: '#3b82f6',
                fillOpacity: 1,
            }).addTo(instance);
            bounds.push([geo.latitude, geo.longitude]);
            const radiusKm = currentRadiusKm();
            if (radiusKm) {
                radiusCircle = L.circle([geo.latitude, geo.longitude], {
                    radius: radiusKm * 1000,
                    color: '#1d4ed8',
                    weight: 1,
                    fillOpacity: 0.08,
                }).addTo(instance);
            }
        }
        if (bounds.length === 1) {
            instance.setView(bounds[0], 11);
        } else if (bounds.length > 1) {
            instance.fitBounds(bounds, { padding: [24, 24], maxZoom: 12 });
        }
        setTimeout(() => instance.invalidateSize(), 50);
    }

    function refresh() {
        update(lastVehicles);
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.getAttribute('name') === 'radius_km') {
            refresh();
        }
    });

    return { update, refresh, pinFromVehicle };
})();
</script>
