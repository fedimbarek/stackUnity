<x-app-layout>
    <x-slot name="header">Alertes météo</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="mb-3">
        @role('admin')
            <a href="{{ route('admin.weather.alerts.create') }}" class="btn btn-danger">
                <i class="fas fa-bell"></i> Déclencher une alerte
            </a>
            <a href="{{ route('admin.weather.thresholds.edit') }}" class="btn btn-secondary">
                <i class="fas fa-sliders-h"></i> Seuils
            </a>
        @endrole
    </div>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quartier</th>
                        <th>Niveau</th>
                        <th>Source</th>
                        <th>Message</th>
                        <th>Créée par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $a)
                        <tr>
                            <td>{{ $a->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $a->neighborhood->name }}</td>
                            <td>
                                @if ($a->level === 'canicule')
                                    <span class="badge badge-danger">Canicule</span>
                                @else
                                    <span class="badge badge-warning">Forte chaleur</span>
                                @endif
                            </td>
                            <td>{{ $a->source === 'auto' ? 'Automatique' : 'Manuelle' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($a->message, 60) }}</td>
                            <td>{{ $a->creator?->name ?? '—' }}</td>
                            <td class="d-flex">
                                <a href="{{ route('admin.weather.alerts.show', $a) }}" class="btn btn-sm btn-primary mr-2">Voir</a>
                                @role('admin')
                                    <a href="{{ route('admin.weather.alerts.edit', $a) }}" class="btn btn-sm btn-info mr-2">Modifier</a>
                                    <form method="POST" action="{{ route('admin.weather.alerts.destroy', $a) }}"
                                          onsubmit="return confirm('Supprimer cette alerte ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Supprimer</button>
                                    </form>
                                @endrole
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Aucune alerte.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $alerts->links() }}
        </div>
    </div>
</x-app-layout>