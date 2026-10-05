<x-app-layout>
    <x-slot name="header">Seuils d'alerte</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <p class="text-muted">
                Une prévision atteint un niveau d'alerte quand sa température maximale dépasse le seuil correspondant.
            </p>

            <form method="POST" action="{{ route('admin.weather.thresholds.update') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Seuil « Forte chaleur » (°C)</label>
                    <input type="number" step="0.1" name="warning"
                           class="form-control @error('warning') is-invalid @enderror"
                           value="{{ old('warning', $thresholds['warning']) }}">
                    @error('warning') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Seuil « Canicule » (°C)</label>
                    <input type="number" step="0.1" name="canicule"
                           class="form-control @error('canicule') is-invalid @enderror"
                           value="{{ old('canicule', $thresholds['canicule']) }}">
                    @error('canicule') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('weather.index') }}" class="btn btn-light">Retour</a>
            </form>
        </div>
    </div>
</x-app-layout>