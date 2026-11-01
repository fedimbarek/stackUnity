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
                            <strong class="text-primary" id="radiusLabel">5 km</strong>
                        </label>
                        <input type="range" class="custom-range" id="radiusSlider" min="500" max="10000" step="500" value="5000">
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

                {{-- Barre d'outils au-dessus de la carte --}}
                <div class="card shadow mb-3">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center flex-wrap">

                            @role('admin')
                            <button id="btnAddPoint" class="btn btn-success btn-sm mr-2 mb-1">
                                <i class="fas fa-plus"></i> Ajouter un point ici
                            </button>
                            <span class="border-left pl-3 ml-1 mb-1"></span>
                            @endrole

                            <div class="btn-group btn-group-sm mb-1 flex-wrap" role="group" id="filterGroupMap">
                                <button type="button" class="btn btn-primary filter-btn" data-filter="all">Tous</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Carte --}}
                <div class="card shadow">
                    <div class="card-body p-0 position-relative">
                        <div id="cooling-map" style="height:650px;border-radius:.35rem"></div>
                        <div id="mapLoader" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.85);align-items:center;justify-content:center;border-radius:.35rem">
                            <div class="text-center">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 small text-muted mb-0">Chargement des points...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @role('admin')
    {{-- Modal : ajouter un point depuis la carte --}}
    <div class="modal fade" id="addPointModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle text-success"></i> Ajouter un point</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select id="addType" class="form-control">
                            @foreach(\App\Models\CoolingPointType::orderBy('name')->get() as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nom <span class="text-danger">*</span></label>
                        <input type="text" id="addName" class="form-control" value="Point de fraîcheur">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Latitude</label>
                            <input type="text" id="addLatDisplay" class="form-control" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Longitude</label>
                            <input type="text" id="addLngDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Adresse</label>
                        <input type="text" id="addAddress" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Horaires</label>
                        <input type="text" id="addHours" class="form-control" placeholder="08:00-20:00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="button" id="addPointSave" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endrole

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

        {{-- ✅ Évite l'erreur VSCode "Decorators are not valid here" --}}
        <script>
            window.IS_ADMIN = @json(auth()->check() && auth()->user()->hasRole('admin'));
        </script>

        <script>
            (function () {
                'use strict';

                // ── État global ────────────────────────────
                let ICONS  = {};
                let COLORS = {};
                let TYPES  = [];

                let map = null;
                let clusterGroup = null;
                let userMarker = null;
                let userPos = null;
                let allPoints = [];
                let filteredPoints = [];
                let currentFilter = 'all';
                let currentSearch = '';

                // ✅ Marker reference par ID (O(1) au clic)
                const markerById = new Map();

                // ── Init carte ─────────────────────────────
                function initMap() {
                    const el = document.getElementById('cooling-map');
                    if (!el || typeof L === 'undefined' || map) return;

                    map = L.map(el).setView([36.8065, 10.1815], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(map);

                    clusterGroup = L.markerClusterGroup({
                        maxClusterRadius: 60,
                        showCoverageOnHover: false,
                        spiderfyOnMaxZoom: true,
                        disableClusteringAtZoom: 16,
                        chunkedLoading: true,
                        chunkInterval: 100,
                        chunkDelay: 30
                    });
                    map.addLayer(clusterGroup);
                }

                // ── Icône ──────────────────────────────────
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

                // ── Popup ──────────────────────────────────
                function buildPopup(p) {
                    const color = p.type_color || COLORS[p.type] || '#858796';
                    const label = p.type_name  || p.type;

                    const addr = p.address
                        ? `<div style="margin-top:4px"><i class="fas fa-map-marker-alt" style="color:#999"></i> ${escapeHtml(p.address)}</div>`
                        : '';
                    const hours = p.opening_hours
                        ? `<div style="margin-top:2px"><i class="fas fa-clock" style="color:#999"></i> ${escapeHtml(p.opening_hours)}</div>`
                        : '';
                    const routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`;

                    const aiBadge = (p.ai_score !== null && p.ai_score !== undefined)
                        ? `<span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:10px;background:${p.ai_rank <= 3 ? '#1cc88a' : '#858796'};color:#fff;font-size:10px;font-weight:700" title="Score IA">
                             ${p.ai_rank <= 3 ? '⭐' : 'IA'} ${(p.ai_score * 100).toFixed(0)}%
                           </span>`
                        : '';

                    return `
                    <div style="min-width:200px;font-family:inherit">
                        <div style="font-weight:700;font-size:14px;margin-bottom:6px;color:#333">
                            ${escapeHtml(p.name)} ${aiBadge}
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

                // ── Haversine ──────────────────────────────
                function haversine(lat1, lon1, lat2, lon2) {
                    const R = 6371;
                    const dLat = (lat2 - lat1) * Math.PI / 180;
                    const dLon = (lon2 - lon1) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) ** 2 +
                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                        Math.sin(dLon / 2) ** 2;
                    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                }

                // ── Marqueur "vous êtes ici" ───────────────
                function addUserMarker() {
                    if (!map || !userPos) return;
                    if (userMarker) map.removeLayer(userMarker);
                    userMarker = L.circleMarker([userPos.lat, userPos.lng], {
                        radius: 8, color: '#4e73df', fillColor: '#4e73df',
                        fillOpacity: 1, weight: 3
                    }).addTo(map).bindPopup('📍 Vous êtes ici');
                    map.setView([userPos.lat, userPos.lng], 14);
                }

                // ── Géoloc ─────────────────────────────────
                function locate() {
                    if (!navigator.geolocation) return;
                    showLoader(true);
                    navigator.geolocation.getCurrentPosition(
                        pos => {
                            userPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                            localStorage.setItem('heatalert_userPos', JSON.stringify(userPos));
                            initMap();
                            addUserMarker();
                            fetchPoints();
                        },
                        () => {
                            showLoader(false);
                            console.warn('Géoloc refusée — on garde la position par défaut');
                        },
                        { enableHighAccuracy: true, timeout: 8000 }
                    );
                }

                // ── Fetch ──────────────────────────────────
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

                        allPoints = list.map(p => {
                            const lat = parseFloat(p.latitude);
                            const lng = parseFloat(p.longitude);
                            return {
                                ...p,
                                latitude: lat,
                                longitude: lng,
                                distance: haversine(userPos.lat, userPos.lng, lat, lng)
                            };
                        })
                        .filter(p => p.distance <= radius / 1000)
                        .sort((a, b) => a.distance - b.distance);

                        console.log('Points reçus :', allPoints.length);
                        applyFilters();
                        updateStats();
                    } catch (e) {
                        console.error('Erreur fetch:', e);
                    } finally {
                        showLoader(false);
                    }
                }

                // ── Filtres ────────────────────────────────
                function buildFilterButtons() {
                    const groups = [
                        document.getElementById('filterGroup'),
                        document.getElementById('filterGroupMap'),
                    ].filter(Boolean);

                    groups.forEach(group => {
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

                function applyFilters() {
                    const s = currentSearch.toLowerCase();
                    filteredPoints = allPoints.filter(p =>
                        (currentFilter === 'all' || p.type === currentFilter) &&
                        (!s || (p.name || '').toLowerCase().includes(s))
                    );
                    renderMarkers();
                    renderList();
                }

                // ── Rendu marqueurs ────────────────────────
                function renderMarkers() {
                    if (!map || !clusterGroup) return;

                    clusterGroup.clearLayers();
                    markerById.clear();

                    const layers = filteredPoints.map(p => {
                        const m = L.marker([p.latitude, p.longitude], { icon: iconFor(p.type) });
                        m.bindPopup(buildPopup(p), { maxWidth: 260, minWidth: 220 });
                        markerById.set(p.id, m);
                        return m;
                    });

                    clusterGroup.addLayers(layers);
                }

                // ── Rendu liste (1 seul listener via délégation) ──
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

                    const slice = filteredPoints.slice(0, 100);

                    list.innerHTML = slice.map((p, i) => {
                        const color = p.type_color || COLORS[p.type] || '#858796';
                        const icon  = p.type_icon  || ICONS[p.type]  || 'fa-map-marker-alt';
                        const name  = p.type_name  || p.type;

                        const isRec = p.ai_rank && p.ai_rank <= 3;
                        const aiBadge = (p.ai_score !== null && p.ai_score !== undefined)
                            ? `<span class="badge badge-${isRec ? 'success' : 'secondary'} ml-1" style="font-size:9px" title="Score IA: ${(p.ai_score * 100).toFixed(0)}%">
                                 ${isRec ? '⭐' : 'IA'} ${(p.ai_score * 100).toFixed(0)}%
                               </span>`
                            : '';

                        return `<a href="#" class="d-block px-3 py-2 border-bottom text-decoration-none text-dark point-item" data-id="${p.id}">
                            <div class="d-flex align-items-center">
                                <span class="mr-2" style="width:26px;height:26px;border-radius:50%;background:${color};display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas ${icon}" style="color:#fff;font-size:11px"></i>
                                </span>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="font-weight-bold small text-truncate">${escapeHtml(p.name)} ${aiBadge}</div>
                                    <div class="text-muted" style="font-size:11px">
                                        ${p.distance.toFixed(2)} km · ${escapeHtml(name)}
                                    </div>
                                </div>
                            </div>
                        </a>`;
                    }).join('');

                    if (filteredPoints.length > 100) {
                        list.innerHTML += `<div class="text-center text-muted small p-2">... ${filteredPoints.length - 100} autres points</div>`;
                    }
                }

                // ✅ 1 seul listener pour TOUS les items (délégation)
                function bindListClick() {
                    const list = document.getElementById('pointsList');
                    if (!list || list.dataset.bound === '1') return;
                    list.dataset.bound = '1';

                    list.addEventListener('click', e => {
                        const item = e.target.closest('.point-item');
                        if (!item) return;
                        e.preventDefault();

                        const id = parseInt(item.dataset.id, 10);
                        const marker = markerById.get(id);
                        if (!marker) return;

                        // ✅ API officielle MarkerCluster : gère les clusters + zoom auto
                        if (clusterGroup.zoomToShowLayer) {
                            clusterGroup.zoomToShowLayer(marker, () => marker.openPopup());
                        } else {
                            map.setView(marker.getLatLng(), 16);
                            marker.openPopup();
                        }
                    });
                }

                // ── Stats ──────────────────────────────────
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
                    if (el) el.style.display = on ? 'flex' : 'none';
                }

                // ── Admin : ajout depuis la carte ──────────
                let addMode = false;
                let pendingLat = 0, pendingLng = 0;
                let adminBound = false;

                function toggleAddMode() {
                    addMode = !addMode;
                    const btn = document.getElementById('btnAddPoint');
                    const mapEl = document.getElementById('cooling-map');
                    if (!btn) return;

                    if (addMode) {
                        btn.classList.replace('btn-success', 'btn-warning');
                        btn.innerHTML = '<i class="fas fa-times"></i> Annuler l\'ajout';
                        mapEl.style.cursor = 'crosshair';
                    } else {
                        btn.classList.replace('btn-warning', 'btn-success');
                        btn.innerHTML = '<i class="fas fa-plus"></i> Ajouter un point ici';
                        mapEl.style.cursor = '';
                    }
                }

                function bindAdminAdd() {
                    if (!window.IS_ADMIN || adminBound || !map) return;
                    adminBound = true;

                    const btn = document.getElementById('btnAddPoint');
                    if (btn) btn.addEventListener('click', toggleAddMode);

                    map.on('click', (e) => {
                        if (!addMode) return;
                        pendingLat = e.latlng.lat;
                        pendingLng = e.latlng.lng;
                        document.getElementById('addLatDisplay').value = pendingLat.toFixed(7);
                        document.getElementById('addLngDisplay').value = pendingLng.toFixed(7);
                        $('#addPointModal').modal('show');
                    });

                    document.getElementById('addPointSave')?.addEventListener('click', saveNewPoint);
                }

                async function saveNewPoint() {
                    const payload = {
                        cooling_point_type_id: parseInt(document.getElementById('addType').value, 10),
                        name: document.getElementById('addName').value.trim() || 'Point de fraîcheur',
                        latitude: pendingLat,
                        longitude: pendingLng,
                        address: document.getElementById('addAddress').value.trim() || null,
                        opening_hours: document.getElementById('addHours').value.trim() || null,
                    };

                    const saveBtn = document.getElementById('addPointSave');
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

                    try {
                        const res = await fetch('{{ route("admin.cooling-points.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const json = await res.json();
                        if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));

                        $('#addPointModal').modal('hide');
                        document.getElementById('addName').value = 'Point de fraîcheur';
                        document.getElementById('addAddress').value = '';
                        document.getElementById('addHours').value = '';

                        if (addMode) toggleAddMode();

                        // ✅ AJOUT LOCAL — pas de re-fetch, instantané
                        appendNewPoint(json.point);

                        showToast('✅ Point ajouté !');

                    } catch (e) {
                        alert('Erreur : ' + e.message);
                    } finally {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                    }
                }

                // ✅ Ajoute le point localement sans re-fetch
                function appendNewPoint(raw) {
                    const type = raw.cooling_point_type || {};
                    const lat = parseFloat(raw.latitude);
                    const lng = parseFloat(raw.longitude);

                    const p = {
                        id: raw.id,
                        name: raw.name,
                        latitude: lat,
                        longitude: lng,
                        address: raw.address || null,
                        opening_hours: raw.opening_hours || null,
                        type: type.slug || 'other',
                        type_name: type.name || 'Autre',
                        type_icon: type.icon || 'fa-map-marker-alt',
                        type_color: type.color || '#858796',
                        ai_score: null,
                        ai_rank: null,
                        distance: haversine(userPos.lat, userPos.lng, lat, lng),
                    };

                    allPoints.push(p);
                    allPoints.sort((a, b) => a.distance - b.distance);
                    applyFilters();
                    updateStats();
                }

                function showToast(msg) {
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bg-success text-white px-3 py-2 rounded shadow';
                    toast.style.cssText = 'bottom:20px;right:20px;z-index:9999';
                    toast.textContent = msg;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2500);
                }

                // ── Utils ──────────────────────────────────
                function escapeHtml(s) {
                    return String(s == null ? '' : s).replace(/[&<>"']/g, c => (
                        { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]
                    ));
                }

                // ── Events globaux ─────────────────────────
                function bindEvents() {
                    document.getElementById('btnLocate')?.addEventListener('click', locate);

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

                // ── BOOT ───────────────────────────────────
                function boot() {
                    console.log('🎬 Cooling points boot — admin:', window.IS_ADMIN);
                    initMap();
                    bindEvents();
                    bindListClick();
                    bindAdminAdd();

                    // 1. Position sauvegardée ?
                    const saved = localStorage.getItem('heatalert_userPos');
                    if (saved) {
                        try {
                            userPos = JSON.parse(saved);
                            if (userPos && userPos.lat && userPos.lng) {
                                console.log('📍 Position restaurée:', userPos);
                                addUserMarker();
                                fetchPoints();
                                return;
                            }
                        } catch (e) {}
                    }

                    // 2. Position par défaut
                    userPos = { lat: 36.8065, lng: 10.1815 };
                    console.log('📍 Position par défaut : Tunis centre');
                    addUserMarker();
                    fetchPoints();

                    // 3. Géoloc en arrière-plan
                    setTimeout(locate, 300);
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