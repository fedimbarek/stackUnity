<x-app-layout>

    <div class="container-fluid">

        {{-- En-tête --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-snowflake text-primary"></i> Points de Fraîcheur
            </h1>
            <button id="btnLocate" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-location-arrow fa-sm"></i> Me localiser
            </button>
        </div>

        {{-- Statistiques dynamiques --}}
        <div class="row" id="statsRow">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="statTotal">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-map-marked-alt fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Filtres + liste --}}
            <div class="col-lg-4 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtres</h6>
                    </div>
                    <div class="card-body">
                        <input type="text" class="form-control mb-3" id="searchInput" placeholder="Rechercher un lieu...">
                        <div class="btn-group btn-group-sm btn-block mb-3 flex-wrap" role="group" id="filterGroup">
                            <button type="button" class="btn btn-primary filter-btn" data-filter="all">Tous</button>
                        </div>
                        <label class="small text-muted d-flex justify-content-between mb-1">
                            <span>Rayon de recherche</span>
                            <strong class="text-primary" id="radiusLabel">2 km</strong>
                        </label>
                        <input type="range" class="custom-range" id="radiusSlider" min="500" max="10000" step="500" value="2000">
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Résultats</h6>
                        <span class="badge badge-primary badge-pill" id="resultCount">0</span>
                    </div>
                    <div class="card-body p-0" style="max-height:380px;overflow-y:auto" id="pointsList">
                        <div class="text-center text-muted small p-4">
                            <i class="fas fa-map-marker-alt fa-2x d-block mb-2 text-gray-300"></i>
                            Aucun point trouvé
                        </div>
                    </div>
                </div>
            </div>

            {{-- Carte --}}
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-body p-0 position-relative">
                        <div id="cooling-map" style="height:650px;border-radius:.35rem"></div>
                        <div id="mapLoader" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.85);align-items:center;justify-content:center;border-radius:.35rem">
                            <div class="text-center">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 small text-muted mb-0">Recherche des points...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- Leaflet --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        {{-- ✅ MarkerCluster : regroupement automatique des marqueurs --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

        <script>
            (function () {
                'use strict';

                let ICONS  = {};
                let COLORS = {};
                let TYPES  = [];

                let map = null;
                let clusterGroup = null;    // ✅ remplace markers[]
                let userMarker = null;
                let userPos = null;
                let allPoints = [];
                let filteredPoints = [];
                let currentFilter = 'all';
                let currentSearch = '';

                // ---- Init carte ----
                function initMap() {
                    const el = document.getElementById('cooling-map');
                    if (!el || typeof L === 'undefined') return;
                    if (map) return;

                    map = L.map(el).setView([36.8065, 10.1815], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(map);

                    // ✅ Cluster group
                    clusterGroup = L.markerClusterGroup({
                        maxClusterRadius: 60,
                        showCoverageOnHover: false,
                        spiderfyOnMaxZoom: true,
                        disableClusteringAtZoom: 16,
                        chunkedLoading: true,       // ✅ rendu progressif = pas de freeze
                        chunkInterval: 100,
                        chunkDelay: 30
                    });
                    map.addLayer(clusterGroup);

                    console.log('✅ Carte + cluster initialisés');
                }

                // ---- Icône (petite = plus rapide) ----
                function iconFor(type) {
                    const c = COLORS[type] || '#858796';
                    const i = ICONS[type]  || 'fa-map-marker-alt';
                    return L.divIcon({
                        className: '',
                        html: `<div style="background:${c};width:24px;height:24px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center">
                                 <i class="fas ${i}" style="transform:rotate(45deg);font-size:10px;color:#fff"></i>
                               </div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 24],
                        popupAnchor: [0, -22]
                    });
                }

                // ---- Popup détaillé ----
                function buildPopup(p) {
                    const color = p.type_color || COLORS[p.type] || '#858796';
                    const label = p.type_name  || p.type;
                    const addr  = p.address
                        ? `<div style="margin-top:4px"><i class="fas fa-map-marker-alt" style="color:#999"></i> ${escapeHtml(p.address)}</div>`
                        : '';
                    const hours = p.opening_hours
                        ? `<div style="margin-top:2px"><i class="fas fa-clock" style="color:#999"></i> ${escapeHtml(p.opening_hours)}</div>`
                        : '';
                    const routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`;

                    return `
                    <div style="min-width:200px;font-family:inherit">
                        <div style="font-weight:700;font-size:14px;margin-bottom:6px;color:#333">
                            ${escapeHtml(p.name)}
                        </div>
                        <span style="display:inline-block;padding:2px 10px;border-radius:12px;background:${color};color:#fff;font-size:11px;font-weight:600">
                            <i class="fas ${p.type_icon || ''}"></i> ${escapeHtml(label)}
                        </span>
                        <div style="font-size:12px;color:#555;margin-top:8px;line-height:1.5">
                            <div><i class="fas fa-route" style="color:#999"></i> ${p.distance.toFixed(2)} km de vous</div>
                            ${addr}${hours}
                        </div>
                        <a href="${routeUrl}" target="_blank" rel="noopener"
                           style="display:block;margin-top:10px;padding:6px 10px;background:#4e73df;color:#fff;text-decoration:none;border-radius:4px;text-align:center;font-size:12px;font-weight:600">
                            <i class="fas fa-directions"></i> Itinéraire
                        </a>
                    </div>`;
                }

                // ---- Haversine ----
                function haversine(lat1, lon1, lat2, lon2) {
                    const R = 6371;
                    const dLat = (lat2 - lat1) * Math.PI / 180;
                    const dLon = (lon2 - lon1) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) ** 2 +
                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                        Math.sin(dLon / 2) ** 2;
                    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                }

                // ---- Localiser ----
                function locate() {
                    if (!navigator.geolocation) {
                        alert('Géolocalisation non supportée par votre navigateur');
                        return;
                    }
                    showLoader(true);
                    navigator.geolocation.getCurrentPosition(
                        pos => {
                            userPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                            initMap();
                            if (!map) { showLoader(false); return; }

                            if (userMarker) map.removeLayer(userMarker);
                            userMarker = L.circleMarker([userPos.lat, userPos.lng], {
                                radius: 8, color: '#4e73df', fillColor: '#4e73df',
                                fillOpacity: 1, weight: 3
                            }).addTo(map).bindPopup('📍 Vous êtes ici');
                            map.setView([userPos.lat, userPos.lng], 14);

                            fetchPoints();
                        },
                        err => {
                            console.error(err);
                            showLoader(false);
                            alert('Localisation refusée ou indisponible');
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                }

                // ---- Charger les points ----
                async function fetchPoints() {
                    if (!userPos) return;
                    showLoader(true);
                    const radius = parseInt(document.getElementById('radiusSlider').value, 10);
                    const t0 = performance.now();

                    try {
                        const res = await fetch('{{ route("cooling-points.fetch") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ lat: userPos.lat, lng: userPos.lng, radius })
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const data = await res.json();
                        console.log(`⏱️ Fetch en ${(performance.now() - t0).toFixed(0)} ms`);

                        if (Array.isArray(data.types) && data.types.length) {
                            TYPES = data.types;
                            ICONS  = {};
                            COLORS = {};
                            TYPES.forEach(t => {
                                ICONS[t.slug]  = t.icon  || 'fa-map-marker-alt';
                                COLORS[t.slug] = t.color || '#858796';
                            });
                            buildFilterButtons();
                        }

                        const list = Array.isArray(data) ? data : (data.points ?? []);

                        allPoints = list
                            .map(p => {
                                const latitude  = parseFloat(p.latitude);
                                const longitude = parseFloat(p.longitude);
                                return {
                                    ...p,
                                    latitude,
                                    longitude,
                                    distance: haversine(userPos.lat, userPos.lng, latitude, longitude)
                                };
                            })
                            .filter(p => p.distance <= radius / 1000)
                            .sort((a, b) => a.distance - b.distance);

                        console.log('Points reçus :', allPoints.length);
                        applyFilters();
                        updateStats();
                    } catch (e) {
                        console.error('Erreur fetch:', e);
                        alert('Impossible de charger les points. Vérifiez la connexion.');
                    } finally {
                        showLoader(false);
                    }
                }

                // ---- Filtres dynamiques ----
                function buildFilterButtons() {
                    const group = document.getElementById('filterGroup');
                    if (!group) return;

                    Array.from(group.querySelectorAll('.filter-btn')).forEach(b => {
                        if (b.dataset.filter !== 'all') b.remove();
                    });

                    TYPES.forEach(t => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-outline-primary filter-btn';
                        btn.dataset.filter = t.slug;
                        btn.innerHTML = `<i class="fas ${t.icon || 'fa-map-marker-alt'} fa-sm mr-1"></i>${escapeHtml(t.name)}`;
                        group.appendChild(btn);
                    });

                    attachFilterEvents();
                }

                function attachFilterEvents() {
                    document.querySelectorAll('.filter-btn').forEach(b => {
                        if (b.dataset.bound === '1') return;
                        b.dataset.bound = '1';
                        b.addEventListener('click', () => {
                            document.querySelectorAll('.filter-btn').forEach(x => {
                                x.classList.remove('btn-primary');
                                x.classList.add('btn-outline-primary');
                            });
                            b.classList.remove('btn-outline-primary');
                            b.classList.add('btn-primary');
                            currentFilter = b.dataset.filter;
                            applyFilters();
                        });
                    });
                }

                // ---- Filtres ----
                function applyFilters() {
                    const s = currentSearch.toLowerCase();
                    filteredPoints = allPoints.filter(p =>
                        (currentFilter === 'all' || p.type === currentFilter) &&
                        (!s || (p.name || '').toLowerCase().includes(s))
                    );
                    renderMarkers();
                    renderList();
                }

                // ✅ Rendu via cluster (rapide + pas de freeze)
                function renderMarkers() {
                    if (!map || !clusterGroup) return;

                    clusterGroup.clearLayers();

                    const layers = filteredPoints.map(p => {
                        const m = L.marker([p.latitude, p.longitude], { icon: iconFor(p.type) });
                        m.bindPopup(buildPopup(p), { maxWidth: 260, minWidth: 220 });
                        return m;
                    });

                    clusterGroup.addLayers(layers);   // addLayers = optimisé
                }

                function renderList() {
                    const list = document.getElementById('pointsList');
                    document.getElementById('resultCount').textContent = filteredPoints.length;

                    if (!filteredPoints.length) {
                        list.innerHTML = `<div class="text-center text-muted small p-4">
                            <i class="fas fa-map-marker-alt fa-2x d-block mb-2 text-gray-300"></i>
                            Aucun point trouvé
                        </div>`;
                        return;
                    }

                    // Limiter à 100 items dans la liste (évite le lag DOM)
                    const slice = filteredPoints.slice(0, 100);

                    list.innerHTML = slice.map((p, i) => {
                        const color = p.type_color || COLORS[p.type] || '#858796';
                        const icon  = p.type_icon  || ICONS[p.type]  || 'fa-map-marker-alt';
                        const name  = p.type_name  || p.type;

                        return `<a href="#" class="d-block px-3 py-2 border-bottom text-decoration-none text-dark point-item" data-idx="${i}">
                            <div class="d-flex align-items-center">
                                <span class="mr-2" style="width:26px;height:26px;border-radius:50%;background:${color};display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas ${icon}" style="color:#fff;font-size:11px"></i>
                                </span>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="font-weight-bold small text-truncate">${escapeHtml(p.name)}</div>
                                    <div class="text-muted" style="font-size:11px">
                                        ${p.distance.toFixed(2)} km · ${escapeHtml(name)}
                                    </div>
                                </div>
                            </div>
                        </a>`;
                    }).join('');

                    if (filteredPoints.length > 100) {
                        list.innerHTML += `<div class="text-center text-muted small p-2">
                            ... ${filteredPoints.length - 100} autres points
                        </div>`;
                    }

                    list.querySelectorAll('.point-item').forEach(el => {
                        el.addEventListener('click', ev => {
                            ev.preventDefault();
                            const p = filteredPoints[el.dataset.idx];
                            map.setView([p.latitude, p.longitude], 16);
                            // Ouvrir le popup du marqueur via le cluster
                            clusterGroup.eachLayer(layer => {
                                const ll = layer.getLatLng();
                                if (ll.lat === p.latitude && ll.lng === p.longitude) {
                                    layer.openPopup();
                                }
                            });
                        });
                    });
                }

                function escapeHtml(s) {
                    return String(s == null ? '' : s).replace(/[&<>"']/g, c => (
                        { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]
                    ));
                }

                function updateStats() {
                    document.getElementById('statTotal').textContent = allPoints.length;

                    const row = document.getElementById('statsRow');
                    if (!row) return;
                    row.querySelectorAll('[data-dynamic-stat]').forEach(el => el.remove());

                    const palette = ['success', 'info', 'warning', 'danger', 'dark'];
                    TYPES.forEach((t, i) => {
                        const count = allPoints.filter(p => p.type === t.slug).length;
                        const colorClass = palette[i % palette.length];
                        const color = t.color || '#858796';
                        const icon  = t.icon  || 'fa-map-marker-alt';

                        const col = document.createElement('div');
                        col.className = 'col-xl-3 col-md-6 mb-4';
                        col.setAttribute('data-dynamic-stat', t.slug);
                        col.innerHTML = `
                            <div class="card border-left-${colorClass} shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-${colorClass} text-uppercase mb-1">${escapeHtml(t.name)}</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">${count}</div>
                                        </div>
                                        <div class="col-auto"><i class="fas ${icon} fa-2x" style="color:${color};opacity:.35"></i></div>
                                    </div>
                                </div>
                            </div>`;
                        row.appendChild(col);
                    });
                }

                function showLoader(on) {
                    const el = document.getElementById('mapLoader');
                    if (!el) return;
                    el.style.display = on ? 'flex' : 'none';
                }

                // ---- Événements ----
                function bindEvents() {
                    const btn = document.getElementById('btnLocate');
                    if (btn) btn.addEventListener('click', locate);

                    // ✅ Debounce sur la recherche (évite 1 recalcul par frappe)
                    const search = document.getElementById('searchInput');
                    if (search) {
                        let t = null;
                        search.addEventListener('input', e => {
                            clearTimeout(t);
                            t = setTimeout(() => {
                                currentSearch = e.target.value;
                                applyFilters();
                            }, 250);
                        });
                    }

                    attachFilterEvents();

                    const slider = document.getElementById('radiusSlider');
                    if (slider) {
                        slider.addEventListener('input', e => {
                            document.getElementById('radiusLabel').textContent = (e.target.value / 1000) + ' km';
                        });
                        slider.addEventListener('change', () => {
                            if (userPos) fetchPoints();
                        });
                    }
                }

                function boot() {
                    console.log('🎬 Cooling points boot');
                    initMap();
                    bindEvents();
                    setTimeout(locate, 600);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot);
                } else {
                    boot();
                }
            })();
        </script>
    @endpush

</x-app-layout>