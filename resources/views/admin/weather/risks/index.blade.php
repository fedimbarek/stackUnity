<x-app-layout>
    <x-slot name="header">Risques de coupure</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.weather.risks.create') }}" class="btn btn-primary">Nouveau risque</a>
        <a href="{{ route('admin.weather.forecasts.index') }}" class="btn btn-secondary">Prévisions</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>Prévision</th>
                        <th>Quartier</th>
                        <th>Niveau</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($risks as $r)
                        <tr>
                            <td>{{ $r->forecast->date->format('d/m/Y') }} ({{ $r->forecast->temp_max }}°C)</td>
                            <td>{{ $r->forecast->neighborhood->name }}</td>
                            <td>{{ $r->risk_level }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($r->description, 70) }}</td>
                            <td class="d-flex">
                                <a href="{{ route('admin.weather.risks.show', $r) }}" class="btn btn-sm btn-primary mr-2">Voir</a>
                                <a href="{{ route('admin.weather.risks.edit', $r) }}" class="btn btn-sm btn-info mr-2">Modifier</a>
                                <form method="POST" action="{{ route('admin.weather.risks.destroy', $r) }}"
                                      onsubmit="return confirm('Supprimer ce risque ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Aucun risque enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $risks->links() }}
        </div>
    </div>
</x-app-layout>