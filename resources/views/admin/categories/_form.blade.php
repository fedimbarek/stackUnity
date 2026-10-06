@php $cat = $category ?? null; @endphp

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
    <label>Nom *</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $cat->name ?? '') }}">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<!-- <div class="form-group"> -->
    <!-- <label>Icône *</label> -->
    <!-- <select name="icon" class="form-control @error('icon') is-invalid @enderror"> -->
        <!-- <option value="">-- Choisir --</option> -->
        <!-- @foreach (\App\Http\Requests\ContactCategoryRequest::icons() as $icon) -->
            <!-- <option value="{{ $icon }}" @selected(old('icon', $cat->icon ?? '') === $icon)>{{ $icon }}</option> -->
        <!-- @endforeach -->
    <!-- </select> -->
    <!-- @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror -->
<!-- </div> -->

<div class="form-group">
    <label>Couleur *</label>
    <input type="color" name="color" class="form-control @error('color') is-invalid @enderror"
           value="{{ old('color', $cat->color ?? '#4e73df') }}" style="height: 45px; max-width: 120px;">
    @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>