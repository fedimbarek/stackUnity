<x-app-layout>
    <x-slot name="header">Détail de la prévision</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Quartier</dt>
                        <dd>{{ $forecast->neighborhood->name }} ({{ $forecast->neighborhood->city }})</dd>

                        <dt>Date</dt>
                        <dd>{{ $forecast->date->format('d/m/Y') }}</dd>

                        <dt>Niveau</dt>
                        <dd>
                            @if ($forecast->level === 'canicule')
                                <span class="badge badge-danger">Canicule</span>
                            @elseif ($forecast->level === 'warning')
                                <span class="badge badge-warning">Forte chaleur</span>
                            @else
                                <span class="badge badge-success">Normal</span>
                            @endif
                        </dd>
                    </dl>
                </div>

                <div class="col-md-6">
                    <dl>
                        <dt>Température maximale</dt>
                        <dd class="h4">{{ $forecast->temp_max }}°C</dd>

                        <dt>Température minimale</dt>
                        <dd>{{ $forecast->temp_min }}°C</dd>

                        <dt>Dernière mise à jour</dt>
                        <dd>{{ $forecast->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <a href="{{ route('admin.weather.forecasts.edit', $forecast) }}" class="btn btn-info">Modifier</a>
            <a href="{{ route('admin.weather.forecasts.index') }}" class="btn btn-light">Retour à la liste</a>

            <form method="POST" action="{{ route('admin.weather.forecasts.destroy', $forecast) }}"
                  class="d-inline" onsubmit="return confirm('Supprimer cette prévision et ses risques ?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header font-weight-bold">
            Risques de coupure liés ({{ $forecast->outageRisks->count() }})
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>Niveau</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forecast->outageRisks as $risk)
                        <tr>
                            <td>{{ $risk->risk_level }}</td>
                            <td>{{ $risk->description }}</td>
                            <td>
                                <a href="{{ route('admin.weather.risks.show', $risk) }}" class="btn btn-sm btn-primary">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Aucun risque pour cette prévision.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>