<x-app-layout>
    <x-slot name="header">Détail du risque de coupure</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <dl>
                <dt>Niveau de risque</dt>
                <dd>
                    @if ($risk->risk_level === 'eleve')
                        <span class="badge badge-danger">Élevé</span>
                    @elseif ($risk->risk_level === 'moyen')
                        <span class="badge badge-warning">Moyen</span>
                    @else
                        <span class="badge badge-success">Faible</span>
                    @endif
                </dd>

                <dt>Description</dt>
                <dd>{{ $risk->description }}</dd>

                <dt>Prévision liée</dt>
                <dd>
                    <a href="{{ route('admin.weather.forecasts.show', $risk->forecast) }}">
                        {{ $risk->forecast->date->format('d/m/Y') }} — {{ $risk->forecast->temp_max }}°C
                    </a>
                </dd>

                <dt>Quartier</dt>
                <dd>{{ $risk->forecast->neighborhood->name }} ({{ $risk->forecast->neighborhood->city }})</dd>

                <dt>Créé le</dt>
                <dd>{{ $risk->created_at->format('d/m/Y H:i') }}</dd>
            </dl>

            <a href="{{ route('admin.weather.risks.edit', $risk) }}" class="btn btn-info">Modifier</a>
            <a href="{{ route('admin.weather.risks.index') }}" class="btn btn-light">Retour à la liste</a>

            <form method="POST" action="{{ route('admin.weather.risks.destroy', $risk) }}"
                  class="d-inline" onsubmit="return confirm('Supprimer ce risque ?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </div>
</x-app-layout>