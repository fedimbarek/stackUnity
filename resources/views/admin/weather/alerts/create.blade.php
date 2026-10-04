<x-app-layout>
    <x-slot name="header">Déclencher une alerte</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.weather.alerts.store') }}">
                @csrf

                <div class="form-group">
                    <label>Quartier</label>
                    <select name="neighborhood_id" class="form-control @error('neighborhood_id') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @foreach ($neighborhoods as $n)
                            <option value="{{ $n->id }}" @selected(old('neighborhood_id') == $n->id)>
                                {{ $n->name }} ({{ $n->city }})
                            </option>
                        @endforeach
                    </select>
                    @error('neighborhood_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Niveau</label>
                    <select name="level" class="form-control @error('level') is-invalid @enderror">
                        <option value="warning" @selected(old('level', 'warning') === 'warning')>Forte chaleur</option>
                        <option value="canicule" @selected(old('level') === 'canicule')>Canicule</option>
                    </select>
                    @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="4"
                              class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-danger">Déclencher</button>
                <a href="{{ route('admin.weather.alerts.index') }}" class="btn btn-light">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>