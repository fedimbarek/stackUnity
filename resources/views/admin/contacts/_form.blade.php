@php $c = $contact ?? null; @endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-group">
    <label>Catégorie *</label>
    <select name="contact_category_id" class="form-control @error('contact_category_id') is-invalid @enderror">
        <option value="">-- Choisir --</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                @selected(old('contact_category_id', $c->contact_category_id ?? '') == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('contact_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label>Nom *</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $c->name ?? '') }}">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label>Téléphone *</label>
    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
           value="{{ old('phone', $c->phone ?? '') }}">
    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label>Adresse</label>
        <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $c->address ?? '') }}">
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="form-group col-md-6">
        <label>Ville</label>
        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
               value="{{ old('city', $c->city ?? '') }}">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $c->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    @foreach (['is_24h' => 'Disponible 24h/24', 'is_priority' => 'Contact prioritaire', 'is_active' => 'Actif'] as $field => $label)
        <input type="hidden" name="{{ $field }}" value="0">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="{{ $field }}" name="{{ $field }}" value="1"
                   @checked(old($field, $c->$field ?? ($field === 'is_active')))>
            <label class="custom-control-label" for="{{ $field }}">{{ $label }}</label>
        </div>
    @endforeach
</div>