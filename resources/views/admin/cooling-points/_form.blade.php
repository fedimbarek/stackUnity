<div class="form-group">
    <label>Type <span class="text-danger">*</span></label>
    <select name="cooling_point_type_id" class="form-control @error('cooling_point_type_id') is-invalid @enderror">
        <option value="">-- Choisir --</option>
        @foreach($types as $type)
            <option value="{{ $type->id }}"
                {{ old('cooling_point_type_id', $coolingPoint->cooling_point_type_id ?? '') == $type->id ? 'selected' : '' }}>
                {{ $type->name }}
            </option>
        @endforeach
    </select>
    @error('cooling_point_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label>Nom <span class="text-danger">*</span></label>
    <input type="text" name="name" value="{{ old('name', $coolingPoint->name ?? '') }}"
           class="form-control @error('name') is-invalid @enderror">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label>Latitude <span class="text-danger">*</span></label>
        <input type="text" name="latitude" value="{{ old('latitude', $coolingPoint->latitude ?? '36.8065') }}"
               class="form-control @error('latitude') is-invalid @enderror">
        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="form-group col-md-6">
        <label>Longitude <span class="text-danger">*</span></label>
        <input type="text" name="longitude" value="{{ old('longitude', $coolingPoint->longitude ?? '10.1815') }}"
               class="form-control @error('longitude') is-invalid @enderror">
        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-group">
    <label>Adresse</label>
    <input type="text" name="address" value="{{ old('address', $coolingPoint->address ?? '') }}" class="form-control">
</div>

<div class="form-group">
    <label>Horaires</label>
    <input type="text" name="opening_hours" value="{{ old('opening_hours', $coolingPoint->opening_hours ?? '') }}"
           class="form-control" placeholder="08:00-20:00">
</div>
