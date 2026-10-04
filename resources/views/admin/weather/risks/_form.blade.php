<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-group">
        <label>Prévision liée</label>
        <select name="weather_forecast_id" class="form-control @error('weather_forecast_id') is-invalid @enderror">
            <option value="">-- Choisir --</option>
            @foreach ($forecasts as $f)
                <option value="{{ $f->id }}" @selected(old('weather_forecast_id', $risk->weather_forecast_id) == $f->id)>
                    {{ $f->date->format('d/m/Y') }} — {{ $f->neighborhood->name }} ({{ $f->temp_max }}°C)
                </option>
            @endforeach
        </select>
        @error('weather_forecast_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Niveau de risque</label>
        <select name="risk_level" class="form-control @error('risk_level') is-invalid @enderror">
            @foreach (['faible', 'moyen', 'eleve'] as $lvl)
                <option value="{{ $lvl }}" @selected(old('risk_level', $risk->risk_level ?? 'faible') === $lvl)>{{ $lvl }}</option>
            @endforeach
        </select>
        @error('risk_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $risk->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('admin.weather.risks.index') }}" class="btn btn-light">Annuler</a>
</form>