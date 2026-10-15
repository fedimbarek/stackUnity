<x-app-layout>
    <x-slot name="header">Détail de l'alerte</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <dl>
                <dt>Niveau</dt>
                <dd>
                    @if ($alert->level === 'canicule')
                        <span class="badge badge-danger">Canicule</span>
                    @else
                        <span class="badge badge-warning">Forte chaleur</span>
                    @endif
                </dd>

                <dt>Quartier</dt>
                <dd>{{ $alert->neighborhood->name }} ({{ $alert->neighborhood->city }})</dd>

                <dt>Message</dt>
                <dd>{{ $alert->message }}</dd>

                <dt>Source</dt>
                <dd>{{ $alert->source === 'auto' ? 'Automatique' : 'Manuelle' }}</dd>

                <dt>Créée par</dt>
                <dd>{{ $alert->creator?->name ?? '—' }}</dd>

                <dt>Prévision liée</dt>
                <dd>
                    @if ($alert->forecast)
                        <a href="{{ route('admin.weather.forecasts.show', $alert->forecast) }}">
                            {{ $alert->forecast->date->format('d/m/Y') }} — {{ $alert->forecast->temp_max }}°C
                        </a>
                    @else
                        Aucune (alerte manuelle)
                    @endif
                </dd>

                <dt>Créée le</dt>
                <dd>{{ $alert->created_at->format('d/m/Y H:i') }}</dd>
            </dl>

            @role('admin')
                <a href="{{ route('admin.weather.alerts.edit', $alert) }}" class="btn btn-info">Modifier</a>
            @endrole
            <a href="{{ route('admin.weather.alerts.index') }}" class="btn btn-light">Retour à la liste</a>

            @role('admin')
                <form method="POST" action="{{ route('admin.weather.alerts.destroy', $alert) }}"
                      class="d-inline" onsubmit="return confirm('Supprimer cette alerte ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger">Supprimer</button>
                </form>
            @endrole
        </div>
    </div>
</x-app-layout>