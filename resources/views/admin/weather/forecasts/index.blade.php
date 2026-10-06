<x-app-layout>
    <x-slot name="header">Gestion des prévisions</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.weather.forecasts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle prévision
        </a>
        <a href="{{ route('admin.weather.risks.index') }}" class="btn btn-secondary">
            <i class="fas fa-plug"></i> Risques de coupure
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quartier</th>
                        <th>Max</th>
                        <th>Min</th>
                        <th>Niveau</th>
                        <th>Risques</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forecasts as $f)
                        <tr>
                            <td>{{ $f->date->format('d/m/Y') }}</td>
                            <td>{{ $f->neighborhood->name }}</td>
                            <td>{{ $f->temp_max }}°C</td>
                            <td>{{ $f->temp_min }}°C</td>
                            <td>
                                @if ($f->level === 'canicule')
                                    <span class="badge badge-danger">Canicule</span>
                                @elseif ($f->level === 'warning')
                                    <span class="badge badge-warning">Forte chaleur</span>
                                @else
                                    <span class="badge badge-success">Normal</span>
                                @endif
                            </td>
                            <td>{{ $f->outage_risks_count }}</td>
                            <td class="d-flex">
                                <a href="{{ route('admin.weather.forecasts.edit', $f) }}" class="btn btn-sm btn-info mr-2">Modifier</a>
                                <form method="POST" action="{{ route('admin.weather.forecasts.destroy', $f) }}"
                                      onsubmit="return confirm('Supprimer cette prévision ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Aucune prévision.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $forecasts->links() }}
        </div>
    </div>
</x-app-layout>