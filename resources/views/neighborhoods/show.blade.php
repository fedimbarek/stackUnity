<x-app-layout>
    <x-slot name="header">Quartier : {{ $neighborhood->name }}</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><strong>Ville :</strong> {{ $neighborhood->city }}</div>
                <div class="col-md-6"><strong>Coordonnées :</strong>
                    @if ($neighborhood->latitude) {{ $neighborhood->latitude }}, {{ $neighborhood->longitude }} @else — @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Résidents ({{ $neighborhood->users->count() }})</h6></div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th></tr></thead>
                <tbody>
                    @forelse ($neighborhood->users as $user)
                        <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('name')->join(', ') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Aucun résident.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Dernières coupures</h6></div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Statut</th><th>Démarrée</th></tr></thead>
                <tbody>
                    @forelse ($neighborhood->powerOutages as $outage)
                        <tr><td>{{ ucfirst($outage->status) }}</td><td>{{ $outage->started_at->diffForHumans() }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">Aucune coupure.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('neighborhoods.index') }}" class="btn btn-secondary">← Retour</a>
</x-app-layout>