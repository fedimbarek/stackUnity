<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-group">
        <label>Quartier</label>
        <select name="neighborhood_id" class="form-control @error('neighborhood_id') is-invalid @enderror">
            <option value="">-- Choisir --</option>
            @foreach ($neighborhoods as $n)
                <option value="{{ $n->id }}" @selected(old('neighborhood_id', $forecast->neighborhood_id) == $n->id)>
                    {{ $n->name }} ({{ $n->city }})
                </option>
            @endforeach
        </select>
        @error('neighborhood_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Date</label>
        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
               value="{{ old('date', $forecast->date?->format('Y-m-d')) }}">
        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Température max (°C)</label>
            <input type="number" step="0.1" name="temp_max" class="form-control @error('temp_max') is-invalid @enderror"
                   value="{{ old('temp_max', $forecast->temp_max) }}">
            @error('temp_max') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group col-md-6">
            <label>Température min (°C)</label>
            <input type="number" step="0.1" name="temp_min" class="form-control @error('temp_min') is-invalid @enderror"
                   value="{{ old('temp_min', $forecast->temp_min) }}">
            @error('temp_min') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-group">
        <label>Niveau</label>
        <select name="level" class="form-control @error('level') is-invalid @enderror">
            @foreach (['normal', 'warning', 'canicule'] as $lvl)
                <option value="{{ $lvl }}" @selected(old('level', $forecast->level ?? 'normal') === $lvl)>{{ $lvl }}</option>
            @endforeach
        </select>
        @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('admin.weather.forecasts.index') }}" class="btn btn-light">Annuler</a>
</form>