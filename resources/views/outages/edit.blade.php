<x-app-layout>
    <x-slot name="header">Modifier la coupure #{{ $outage->id }}</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('outages.update', $outage) }}">
                @csrf @method('put')

                <div class="form-group">
                    <label>Quartier</label>
                    <select name="neighborhood_id" class="form-control @error('neighborhood_id') is-invalid @enderror">
                        @foreach ($neighborhoods as $n)
                            <option value="{{ $n->id }}" @selected(old('neighborhood_id', $outage->neighborhood_id) == $n->id)>{{ $n->name }}</option>
                        @endforeach
                    </select>
                    @error('neighborhood_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Statut</label>
                    <select name="status" class="form-control">
                        @foreach (['reported' => 'Signalée', 'confirmed' => 'Confirmée', 'resolved' => 'Résolue'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $outage->status) == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label>Position sur la carte (clique pour corriger)</label>
                <div id="editMap" style="height: 300px; border-radius: 8px;" class="mb-3"></div>
                <input type="hidden" name="latitude" id="editLatitude" value="{{ old('latitude', $outage->latitude) }}">
                <input type="hidden" name="longitude" id="editLongitude" value="{{ old('longitude', $outage->longitude) }}">

                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('outages.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const startLat = {{ $outage->latitude ?? 36.8 }};
        const startLng = {{ $outage->longitude ?? 10.18 }};

        const editMap = L.map('editMap').setView([startLat, startLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(editMap);

        let marker = L.marker([startLat, startLng], { draggable: true }).addTo(editMap);

        function setPoint(lat, lng) {
            marker.setLatLng([lat, lng]);
            document.getElementById('editLatitude').value = lat;
            document.getElementById('editLongitude').value = lng;
        }

        editMap.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng));
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setPoint(pos.lat, pos.lng);
        });
    </script>
    @endpush
</x-app-layout>