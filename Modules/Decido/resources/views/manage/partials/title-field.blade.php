{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Option E (skill /100 hors gate) : champ titre partagé entre create-date.blade.php et
     create-classic.blade.php (DRY - même champ requis dans les deux formulaires dédiés). --}}
<div class="mb-4">
    <label for="title" class="form-label">Titre du sondage <span class="text-danger">*</span></label>
    <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
    @error('title')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
