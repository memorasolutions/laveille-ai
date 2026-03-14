<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Date *</label>
        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', isset($override) ? $override->date->format('Y-m-d') : '') }}" required>
        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Type *</label>
        <select name="override_type" class="form-select" required>
            <option value="blocked" {{ old('override_type', $override->override_type ?? '') === 'blocked' ? 'selected' : '' }}>Bloqué</option>
            <option value="available" {{ old('override_type', $override->override_type ?? '') === 'available' ? 'selected' : '' }}>Disponible</option>
        </select>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="all_day" value="0">
            <input type="checkbox" name="all_day" class="form-check-input" id="allDayCheck" value="1" {{ old('all_day', $override->all_day ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="allDayCheck">Journée complète</label>
        </div>
    </div>
    <div class="col-md-6 time-fields" style="{{ old('all_day', $override->all_day ?? false) ? 'display:none' : '' }}">
        <label class="form-label">Heure de début</label>
        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $override->start_time ?? '') }}">
    </div>
    <div class="col-md-6 time-fields" style="{{ old('all_day', $override->all_day ?? false) ? 'display:none' : '' }}">
        <label class="form-label">Heure de fin</label>
        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $override->end_time ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Raison</label>
        <textarea name="reason" class="form-control" rows="2">{{ old('reason', $override->reason ?? '') }}</textarea>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('allDayCheck').addEventListener('change', function() {
    document.querySelectorAll('.time-fields').forEach(el => el.style.display = this.checked ? 'none' : 'block');
});
</script>
@endpush
