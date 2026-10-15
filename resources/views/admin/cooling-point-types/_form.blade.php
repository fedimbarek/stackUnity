<div class="form-group">
    <label>Nom <span class="text-danger">*</span></label>
    <input type="text" name="name" value="{{ old('name', $coolingPointType->name ?? '') }}"
           class="form-control @error('name') is-invalid @enderror">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label>Icône FontAwesome <span class="text-danger">*</span></label>
    <input type="text" name="icon" value="{{ old('icon', $coolingPointType->icon ?? 'fa-map-marker-alt') }}"
           class="form-control @error('icon') is-invalid @enderror" placeholder="fa-tree">
    @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">Ex : fa-tree, fa-tint, fa-snowflake</small>
</div>

<div class="form-group">
    <label>Couleur <span class="text-danger">*</span></label>
    <input type="color" name="color" value="{{ old('color', $coolingPointType->color ?? '#4e73df') }}"
           class="form-control @error('color') is-invalid @enderror" style="max-width:100px">
    @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $coolingPointType->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
