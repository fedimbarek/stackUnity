@php
    $initialLat = $neighborhood->latitude ?? 36.8;
    $initialLng = $neighborhood->longitude ?? 10.18;
@endphp

<div class="form-group">
    <label for="name">Nom du quartier</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $neighborhood->name ?? '') }}" required>
    @error('name') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label for="city">Ville</label>
    <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror"
           value="{{ old('city', $neighborhood->city ?? '') }}" required>
    @error('city') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Position du quartier (clique sur la carte — le nom et la ville se remplissent automatiquement)</label>
    <div id="neighborhoodMap" style="height: 320px; border-radius: 8px;"></div>
    <p class="small text-muted mt-1" id="coordsStatus">
        @if ($neighborhood->latitude ?? false)
            Point actuel : {{ $initialLat }}, {{ $initialLng }}
        @else
            Aucun point sélectionné pour l'instant.
        @endif
    </p>

    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $neighborhood->latitude ?? '') }}">
    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $neighborhood->longitude ?? '') }}">

    @error('latitude') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    @error('longitude') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const nMap = L.map('neighborhoodMap').setView([{{ $initialLat }}, {{ $initialLng }}], {{ ($neighborhood->latitude ?? false) ? 14 : 8 }});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(nMap);

    let nMarker = null;
    @if ($neighborhood->latitude ?? false)
        nMarker = L.marker([{{ $initialLat }}, {{ $initialLng }}], { draggable: true }).addTo(nMap);
        nMarker.on('dragend', () => {
            const pos = nMarker.getLatLng();
            setNeighborhoodPoint(pos.lat, pos.lng);
        });
    @endif

    function setNeighborhoodPoint(lat, lng) {
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('coordsStatus').textContent = 'Recherche du nom du lieu...';

        if (nMarker) {
            nMarker.setLatLng([lat, lng]);
        } else {
            nMarker = L.marker([lat, lng], { draggable: true }).addTo(nMap);
            nMarker.on('dragend', () => {
                const pos = nMarker.getLatLng();
                setNeighborhoodPoint(pos.lat, pos.lng);
            });
        }

        reverseGeocode(lat, lng);
    }

    async function reverseGeocode(lat, lng) {
        try {
            const res = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`,
                { headers: { 'Accept-Language': 'fr' } }
            );
            const data = await res.json();
            const addr = data.address || {};

            const suggestedName = addr.suburb || addr.neighbourhood || addr.quarter
                || addr.city_district || addr.town || addr.village || '';
            const suggestedCity = addr.city || addr.town || addr.municipality || addr.county || '';

            if (suggestedName) document.getElementById('name').value = suggestedName;
            if (suggestedCity) document.getElementById('city').value = suggestedCity;

            document.getElementById('coordsStatus').textContent =
                `Point sélectionné : ${lat.toFixed(5)}, ${lng.toFixed(5)}` +
                (suggestedName ? ` — ${suggestedName}` : '');
        } catch (e) {
            document.getElementById('coordsStatus').textContent =
                `Point sélectionné : ${lat.toFixed(5)}, ${lng.toFixed(5)} (nom non trouvé, saisis-le manuellement)`;
        }
    }

    nMap.on('click', (e) => setNeighborhoodPoint(e.latlng.lat, e.latlng.lng));

    setTimeout(() => nMap.invalidateSize(), 150);
</script>
@endpush