{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Composant réutilisable : upload drag & drop avec preview + compression --}}
@props([
    'name',
    'accept' => 'image/*',
    'maxSize' => 2,
    'maxWidth' => 1920,
    'multiple' => false,
    'label' => null,
    'helpText' => null,
    'currentImage' => null,
    'compact' => false,
])

<div x-data="{
    files: [],
    isDragging: false,
    currentImage: '{{ $currentImage }}',
    maxSizeBytes: {{ $maxSize }} * 1024 * 1024,
    error: '',

    triggerInput() { this.$refs.fileInput.click(); },

    async handleFiles(fileList) {
        this.error = '';
        const accepted = '{{ $accept }}'.split(',').map(t => t.trim());

        for (const file of Array.from(fileList)) {
            const typeOk = accepted.some(a => {
                if (a.endsWith('/*')) return file.type.startsWith(a.replace('/*', '/'));
                return file.type === a;
            });
            if (!typeOk) { this.error = '{{ __('Type de fichier non autorisé.') }}'; continue; }
            if (file.size > this.maxSizeBytes) { this.error = '{{ __('Fichier trop volumineux (max :size Mo).', ['size' => $maxSize]) }}'; continue; }

            let processed = file;
            if (file.type.startsWith('image/')) {
                processed = await this.compress(file);
            }

            const entry = {
                name: file.name,
                size: processed.size,
                url: URL.createObjectURL(processed),
                blob: processed,
                isImage: file.type.startsWith('image/')
            };

            if ({{ $multiple ? 'false' : 'true' }}) {
                this.files = [entry];
                this.currentImage = '';
            } else {
                this.files.push(entry);
            }
        }
        this.syncInput();
    },

    compress(file) {
        return new Promise(resolve => {
            const img = new Image();
            img.onload = () => {
                const maxW = {{ $maxWidth }};
                let w = img.width, h = img.height;
                if (w > maxW) { h = Math.round(h * maxW / w); w = maxW; }
                const c = document.createElement('canvas');
                c.width = w; c.height = h;
                c.getContext('2d').drawImage(img, 0, 0, w, h);
                c.toBlob(blob => resolve(blob || file), 'image/jpeg', 0.8);
            };
            img.onerror = () => resolve(file);
            img.src = URL.createObjectURL(file);
        });
    },

    removeFile(i) {
        URL.revokeObjectURL(this.files[i].url);
        this.files.splice(i, 1);
        this.syncInput();
    },

    removeCurrent() { this.currentImage = ''; },

    syncInput() {
        const dt = new DataTransfer();
        this.files.forEach(f => {
            dt.items.add(new File([f.blob], f.name, { type: f.blob.type }));
        });
        this.$refs.fileInput.files = dt.files;
    },

    handleDrop(e) {
        this.isDragging = false;
        this.handleFiles(e.dataTransfer.files);
    }
}">
    @if($label)
        <label style="font-family: var(--f-heading, 'Plus Jakarta Sans', system-ui, sans-serif); font-weight: 600; font-size: 13px; color: var(--c-text-muted, #6E7687); display: block; margin-bottom: 6px;">{{ $label }}</label>
    @endif

    <div @click="triggerInput()"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)"
         @keydown.enter.prevent="triggerInput()"
         @keydown.space.prevent="triggerInput()"
         role="button"
         tabindex="0"
         aria-label="{{ __('Zone de téléchargement') }}"
         class="core-upload-zone {{ $compact ? 'core-upload-zone--compact' : '' }}"
         :class="{ 'core-upload-zone--dragging': isDragging }">
        @if(!$compact)
        <div class="core-upload-zone__icon">
            <i class="fa fa-cloud-upload"></i>
        </div>
        @endif
        <div class="core-upload-zone__body">
            @if($compact)
                <i class="fa fa-cloud-upload core-upload-zone__icon-inline"></i>
            @endif
            <p class="core-upload-zone__title">
                {{ $compact ? __('Glissez ou') : __('Glissez votre fichier ici') }}
            </p>
            @if(!$compact)
            <p class="core-upload-zone__or">{{ __('ou') }}</p>
            @endif
            <span class="core-upload-zone__btn">{{ __('Parcourir') }}</span>
        </div>
    </div>

    <input type="file" name="{{ $name }}" x-ref="fileInput" accept="{{ $accept }}" {{ $multiple ? 'multiple' : '' }} @change="handleFiles($event.target.files)" style="display: none;">

    <p x-show="error" x-text="error" x-cloak style="color: #DC2626; font-size: 13px; margin: 8px 0 0;"></p>

    <div x-show="currentImage && files.length === 0" x-cloak style="margin-top: 10px; display: flex !important; align-items: center !important; gap: 10px; background: #F9FAFB; padding: 8px 12px; border-radius: var(--r-base, 0.75rem);">
        <img :src="currentImage" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
        <span style="font-size: 13px; color: var(--c-text-muted, #6E7687);">{{ __('Image actuelle') }}</span>
        <button type="button" @click="removeCurrent()" style="margin-left: auto; background: none; border: none; color: #DC2626; cursor: pointer; font-size: 18px;" title="{{ __('Supprimer') }}">&times;</button>
    </div>

    <template x-for="(file, i) in files" :key="i">
        <div style="margin-top: 10px; display: flex !important; align-items: center !important; gap: 10px; background: #F9FAFB; padding: 8px 12px; border-radius: var(--r-base, 0.75rem); border: 1px solid #E5E7EB;">
            <img x-show="file.isImage" :src="file.url" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
            <div x-show="!file.isImage" style="width: 60px; height: 60px; border-radius: 8px; background: #E5E7EB; display: flex !important; align-items: center !important; justify-content: center !important;">
                <i class="fa fa-file" style="font-size: 20px; color: var(--c-text-muted, #6E7687);"></i>
            </div>
            <div style="flex: 1 !important; min-width: 0;">
                <p x-text="file.name" style="margin: 0; font-size: 13px; font-weight: 600; color: var(--c-dark, #1A1D23); word-break: break-all;"></p>
                <small x-text="(file.size / 1024).toFixed(0) + ' Ko'" style="color: var(--c-text-muted, #6E7687);"></small>
            </div>
            <button type="button" @click="removeFile(i)" style="background: none; border: none; color: #DC2626; cursor: pointer; font-size: 18px; padding: 4px 8px;" title="{{ __('Supprimer') }}">&times;</button>
        </div>
    </template>

    @if($helpText)
        <p style="font-size: 12px; color: var(--c-text-muted, #6E7687); margin: 8px 0 0;">{{ $helpText }}</p>
    @endif
</div>

@once
@push('styles')
<style>
.core-upload-zone {
    border: 2px dashed #D1D5DB;
    background: linear-gradient(180deg, #FAFBFC 0%, #F3F4F6 100%);
    border-radius: 16px;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0;
}
.core-upload-zone--compact {
    border-radius: 10px;
    padding: 20px 20px;
    min-height: 90px;
    flex-direction: row;
    gap: 12px;
}
.core-upload-zone--dragging {
    border-color: var(--c-primary, #064E5A);
    background: var(--c-primary-light, #F0FAFB);
    transform: scale(1.02);
    box-shadow: 0 0 0 4px rgba(11,114,133,0.1);
}
.core-upload-zone__icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: var(--c-primary-light, #F0FAFB);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
}
.core-upload-zone__icon i { font-size: 28px; color: var(--c-primary, #064E5A); }
.core-upload-zone__icon-inline { font-size: 20px; color: var(--c-primary, #064E5A); margin-right: 6px; }
.core-upload-zone--compact .core-upload-zone__body { text-align: left; }
.core-upload-zone__title {
    margin: 0;
    font-family: var(--f-heading, 'Plus Jakarta Sans', system-ui, sans-serif);
    font-size: 15px; font-weight: 700;
    color: var(--c-dark, #1A1D23);
}
.core-upload-zone--compact .core-upload-zone__title { font-size: 13px; display: inline; }
.core-upload-zone__or {
    margin: 6px 0 12px; font-size: 13px;
    color: var(--c-text-muted, #6E7687);
}
.core-upload-zone__btn {
    background: var(--c-primary, #064E5A); color: #fff;
    padding: 8px 20px;
    border-radius: var(--r-btn, 0.5rem);
    font-family: var(--f-heading, 'Plus Jakarta Sans', system-ui, sans-serif);
    font-weight: 600; font-size: 13px;
    display: inline-block;
}
.core-upload-zone--compact .core-upload-zone__btn {
    padding: 5px 14px; font-size: 12px; margin-left: 4px;
}
</style>
@endpush
@endonce
