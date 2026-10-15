<x-app-layout>
    <x-slot name="header">Coupures de courant</x-slot>

    @if (session('status') === 'outage-reported')
        <div class="alert alert-success">Merci, ta coupure a été signalée.</div>
    @elseif (session('status') === 'outage-confirmed')
        <div class="alert alert-success">Coupure confirmée.</div>
    @elseif (session('status') === 'outage-resolved')
        <div class="alert alert-success">Coupure marquée comme résolue.</div>
    @elseif (session('status') === 'outage-updated')
        <div class="alert alert-success">Coupure mise à jour.</div>
    @elseif (session('status') === 'outage-deleted')
        <div class="alert alert-success">Coupure supprimée.</div>
    @endif
    @error('neighborhood') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#reportOutageModal">
            <i class="fas fa-bolt"></i> Signaler une coupure
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Carte des coupures en cours</h6></div>
        <div class="card-body"><div id="outagesMap" style="height: 350px; border-radius: 8px;"></div></div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Historique</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Quartier</th><th>Statut</th><th>Signalements</th><th>Démarrée</th>
                            @role('admin|gestionnaire')<th></th>@endrole
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($outages as $outage)
                            <tr>
                                <td>{{ $outage->neighborhood->name }}</td>
                                <td>
                                    @if ($outage->status === 'reported') <span class="badge badge-warning">Signalée</span>
                                    @elseif ($outage->status === 'confirmed') <span class="badge badge-danger">Confirmée</span>
                                    @else <span class="badge badge-success">Résolue</span>
                                    @endif
                                </td>
                                <td>{{ $outage->reports->count() }}</td>
                                <td>{{ $outage->started_at->diffForHumans() }}</td>
                                @role('admin|gestionnaire')
                                <td class="text-nowrap">
                                    @if ($outage->status !== 'resolved')
                                        @if ($outage->status === 'reported')
                                            <form method="POST" action="{{ route('outages.confirm', $outage) }}" class="d-inline">
                                                @csrf @method('put')
                                                <button class="btn btn-sm btn-dark">Confirmer</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('outages.resolve', $outage) }}" class="d-inline">
                                            @csrf @method('put')
                                            <button class="btn btn-sm btn-success">Résolue</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('outages.edit', $outage) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form method="POST" action="{{ route('outages.destroy', $outage) }}" class="d-inline"
                                          onsubmit="return confirm('Supprimer définitivement cette coupure ?');">
                                        @csrf @method('delete')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                                @endrole
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Aucune coupure pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $outages->links() }}
        </div>
    </div>

    <div class="modal fade" id="reportOutageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('outages.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Signaler une coupure</h5>
                        <button class="close" type="button" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-gray-600">
                            Quartier concerné : <strong>{{ auth()->user()->neighborhood?->name ?? 'non renseigné' }}</strong>
                        </p>

                        <label class="small font-weight-bold">Clique sur la carte pour indiquer l'endroit exact</label>
                        <div id="pickerMap" style="height: 250px; border-radius: 8px;" class="mb-2"></div>

                        <button type="button" id="useMyLocationBtn" class="btn btn-sm btn-outline-primary mb-2">
                            <i class="fas fa-location-arrow"></i> Utiliser ma position actuelle
                        </button>

                        <input type="hidden" name="latitude" id="reportLatitude">
                        <input type="hidden" name="longitude" id="reportLongitude">
                        <p class="small text-muted" id="geoStatus">Aucun point sélectionné pour l'instant.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Confirmer le signalement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // ---- Carte historique (affichage seul) ----
        const map = L.map('outagesMap').setView([36.8, 10.18], 11); // Grand Tunis
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        fetch("{{ route('outages.map') }}")
            .then(r => r.json())
            .then(outages => outages.forEach(o => {
                const color = o.status === 'confirmed' ? 'red' : 'orange';
                L.circleMarker([o.latitude, o.longitude], { radius: 8, color, fillColor: color, fillOpacity: 0.7 })
                    .addTo(map)
                    .bindPopup(`<strong>${o.neighborhood.name}</strong><br>Statut : ${o.status}`);
            }));

        // ---- Carte "picker" dans le modal (cliquable) ----
        let pickerMap, pickerMarker;

        $('#reportOutageModal').on('shown.bs.modal', function () {
            if (pickerMap) return; // init une seule fois, à l'ouverture du modal (conteneur alors visible)

            pickerMap = L.map('pickerMap').setView([36.8, 10.18], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(pickerMap);

            pickerMap.on('click', (e) => setReportPoint(e.latlng.lat, e.latlng.lng));

            setTimeout(() => pickerMap.invalidateSize(), 200); // fix affichage dans modal Bootstrap
        });

        function setReportPoint(lat, lng) {
            document.getElementById('reportLatitude').value = lat;
            document.getElementById('reportLongitude').value = lng;
            document.getElementById('geoStatus').textContent = `Point sélectionné : ${lat.toFixed(5)}, ${lng.toFixed(5)}`;

            if (pickerMarker) pickerMap.removeLayer(pickerMarker);
            pickerMarker = L.marker([lat, lng]).addTo(pickerMap);
        }

        document.getElementById('useMyLocationBtn').addEventListener('click', () => {
            if (!navigator.geolocation) {
                document.getElementById('geoStatus').textContent = "Géolocalisation non supportée par ce navigateur.";
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    setReportPoint(pos.coords.latitude, pos.coords.longitude);
                    pickerMap.setView([pos.coords.latitude, pos.coords.longitude], 15);
                },
                () => document.getElementById('geoStatus').textContent = "Position refusée — clique sur la carte à la place."
            );
        });
    </script>
    @endpush
</x-app-layout>