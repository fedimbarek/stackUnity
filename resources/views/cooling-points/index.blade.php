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

        {{-- Statistiques --}}
        <div class="row">
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
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Parcs</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="statParks">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-tree fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Fontaines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="statFountains">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-tint fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Climatisés</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="statMalls">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-snowflake fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Colonne filtres + liste --}}
            <div class="col-lg-4 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtres</h6>
                    </div>
                    <div class="card-body">
                        <input type="text" class="form-control mb-3" id="searchInput" placeholder="Rechercher un lieu...">
                        <div class="btn-group btn-group-sm btn-block mb-3" role="group">
                            <button type="button" class="btn btn-primary filter-btn" data-filter="all">Tous</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="park">Parcs</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="fountain">Fontaines</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="mall">Climatisés</button>
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
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                'use strict';

                const ICONS  = { park:'🌳', fountain:'💧', mall:'❄️' };
                const COLORS = { park:'#1cc88a', fountain:'#36b9cc', mall:'#f6c23e' };

                let map = null;
                let markers = [];
                let userMarker = null;
                let userPos = null;
                let allPoints = [];
                let filteredPoints = [];
                let currentFilter = 'all';
                let currentSearch = '';

                // ---- Init carte ----
                function initMap() {
                    const el = document.getElementById('cooling-map');
                    if (!el || typeof L === 'undefined') {
                        console.warn('Carte : élément ou Leaflet absent');
                        return;
                    }
                    if (map) return;

                    map = L.map(el).setView([36.8065, 10.1815], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(map);
                    console.log('✅ Carte initialisée');
                }

                // ---- Icône ----
                function iconFor(type) {
                    const c = COLORS[type] || '#858796';
                    const i = ICONS[type] || '📍';
                    return L.divIcon({
                        className: '',
                        html: `<div style="background:${c};width:26px;height:26px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center">
                     <span style="transform:rotate(45deg);font-size:12px">${i}</span>
                   </div>`,
                        iconSize: [26, 26],
                        iconAnchor: [13, 26],
                        popupAnchor: [0, -26]
                    });
                }

                // ---- Distance Haversine ----
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
                        allPoints = data.map(p => ({
                            ...p,
                            distance: haversine(userPos.lat, userPos.lng, p.latitude, p.longitude)
                        })).sort((a, b) => a.distance - b.distance);

                        applyFilters();
                        updateStats();
                    } catch (e) {
                        console.error('Erreur fetch:', e);
                        alert('Impossible de charger les points. Vérifiez la connexion.');
                    } finally {
                        showLoader(false);
                    }
                }

                // ---- Filtres ----
                function applyFilters() {
                    const s = currentSearch.toLowerCase();
                    filteredPoints = allPoints.filter(p =>
                        (currentFilter === 'all' || p.type === currentFilter) &&
                        (!s || p.name.toLowerCase().includes(s))
                    );
                    renderMarkers();
                    renderList();
                }

                function renderMarkers() {
                    if (!map) return;
                    markers.forEach(m => map.removeLayer(m));
                    markers = filteredPoints.map(p =>
                        L.marker([p.latitude, p.longitude], { icon: iconFor(p.type) })
                            .addTo(map)
                            .bindPopup(`<b>${p.name}</b><br><small>${p.type} · ${p.distance.toFixed(2)} km</small>`)
                    );
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

                    list.innerHTML = filteredPoints.map((p, i) => `
            <a href="#" class="d-block px-3 py-2 border-bottom text-decoration-none text-dark point-item" data-idx="${i}">
                <div class="d-flex align-items-center">
                    <span class="mr-2" style="font-size:18px">${ICONS[p.type] || '📍'}</span>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="font-weight-bold small text-truncate">${escapeHtml(p.name)}</div>
                        <div class="text-muted" style="font-size:11px">
                            ${p.distance.toFixed(2)} km · ${p.type}
                        </div>
                    </div>
                </div>
            </a>
        `).join('');

                    list.querySelectorAll('.point-item').forEach(el => {
                        el.addEventListener('click', ev => {
                            ev.preventDefault();
                            const p = filteredPoints[el.dataset.idx];
                            map.setView([p.latitude, p.longitude], 16);
                            if (markers[el.dataset.idx]) markers[el.dataset.idx].openPopup();
                        });
                    });
                }

                function escapeHtml(s) {
                    return String(s).replace(/[&<>"']/g, c => (
                        { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]
                    ));
                }

                function updateStats() {
                    document.getElementById('statTotal').textContent     = allPoints.length;
                    document.getElementById('statParks').textContent     = allPoints.filter(p => p.type === 'park').length;
                    document.getElementById('statFountains').textContent = allPoints.filter(p => p.type === 'fountain').length;
                    document.getElementById('statMalls').textContent     = allPoints.filter(p => p.type === 'mall').length;
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

                    const search = document.getElementById('searchInput');
                    if (search) search.addEventListener('input', e => {
                        currentSearch = e.target.value;
                        applyFilters();
                    });

                    document.querySelectorAll('.filter-btn').forEach(b => {
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

                // ---- Boot ----
                function boot() {
                    console.log('🎬 Cooling points boot');
                    initMap();
                    bindEvents();
                    // Auto-localisation au chargement
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
