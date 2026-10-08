<div class="form-group">
    <label for="title">Titre</label>
    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $report->title ?? '') }}" required>
    @error('title') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="period_start">Début de période</label>
        <input type="date" name="period_start" id="period_start" class="form-control @error('period_start') is-invalid @enderror"
               value="{{ old('period_start', isset($report) ? $report->period_start->format('Y-m-d') : '') }}" required>
        @error('period_start') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="period_end">Fin de période</label>
        <input type="date" name="period_end" id="period_end" class="form-control @error('period_end') is-invalid @enderror"
               value="{{ old('period_end', isset($report) ? $report->period_end->format('Y-m-d') : '') }}" required>
        @error('period_end') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    </div>
</div>

<div class="form-group">
    <label for="neighborhood_id">Quartier (optionnel — laisser vide pour tous)</label>
    <select name="neighborhood_id" id="neighborhood_id" class="form-control @error('neighborhood_id') is-invalid @enderror">
        <option value="">-- Tous les quartiers --</option>
        @foreach ($neighborhoods as $n)
            <option value="{{ $n->id }}" @selected(old('neighborhood_id', $report->neighborhood_id ?? '') == $n->id)>{{ $n->name }}</option>
        @endforeach
    </select>
    @error('neighborhood_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
</div>