@php $locked = $alert->exists && $alert->source === 'auto'; @endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-group">
        <label>Quartier</label>
        <select name="{{ $locked ? '' : 'neighborhood_id' }}"
                class="form-control @error('neighborhood_id') is-invalid @enderror"
                @disabled($locked)>
            <option value="">-- Choisir --</option>
            @foreach ($neighborhoods as $n)
                <option value="{{ $n->id }}" @selected(old('neighborhood_id', $alert->neighborhood_id) == $n->id)>
                    {{ $n->name }} ({{ $n->city }})
                </option>
            @endforeach
        </select>

        @if ($locked)
            <input type="hidden" name="neighborhood_id" value="{{ $alert->neighborhood_id }}">
            <small class="text-muted">Alerte automatique : le quartier est lié à sa prévision et ne peut pas changer.</small>
        @endif

        @error('neighborhood_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Niveau</label>
        <select name="level" class="form-control @error('level') is-invalid @enderror">
            <option value="warning" @selected(old('level', $alert->level ?? 'warning') === 'warning')>Forte chaleur</option>
            <option value="canicule" @selected(old('level', $alert->level) === 'canicule')>Canicule</option>
        </select>
        @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Message</label>
        <textarea name="message" rows="4"
                  class="form-control @error('message') is-invalid @enderror">{{ old('message', $alert->message) }}</textarea>
        @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button class="btn btn-danger">{{ $method === 'POST' ? 'Déclencher' : 'Enregistrer' }}</button>
    <a href="{{ route('admin.weather.alerts.index') }}" class="btn btn-light">Annuler</a>
</form>