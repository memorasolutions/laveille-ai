<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Image Editor Modal (Cropper.js + Alpine.js) --}}
{{-- Usage: dispatch 'open-image-editor' event with {id, url, cropUrl} --}}

<div x-data="imageEditor()" x-init="init()">
    <div class="modal fade" id="imageEditorModal" tabindex="-1" aria-labelledby="imageEditorLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageEditorLabel">Modifier l'image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0 bg-dark d-flex justify-content-center align-items-center" style="min-height:400px;max-height:70vh;overflow:hidden;">
                    <img id="imageToCrop" :src="imageUrl" style="max-width:100%;display:block;" alt="Image source">
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary" @click="doRotate(-90)" title="Rotation gauche" aria-label="Rotation gauche">
                            <i data-lucide="rotate-ccw" style="width:18px;height:18px;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="doRotate(90)" title="Rotation droite" aria-label="Rotation droite">
                            <i data-lucide="rotate-cw" style="width:18px;height:18px;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="doReset()" title="Reinitialiser" aria-label="Reinitialiser">
                            <i data-lucide="refresh-cw" style="width:18px;height:18px;"></i>
                        </button>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="doSave()" :disabled="processing">
                            <template x-if="!processing"><span><i data-lucide="crop" style="width:16px;height:16px;" class="me-1"></i> Recadrer</span></template>
                            <template x-if="processing"><span class="spinner-border spinner-border-sm"></span></template>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function imageEditor() {
    return {
        imageUrl: '',
        cropUrl: '',
        cropper: null,
        processing: false,
        modal: null,

        init() {
            var el = document.getElementById('imageEditorModal');
            window.addEventListener('open-image-editor', (e) => {
                this.imageUrl = e.detail.url;
                this.cropUrl = e.detail.cropUrl;
                this.modal = new bootstrap.Modal(el);
                this.modal.show();
            });
            el.addEventListener('shown.bs.modal', () => {
                var img = document.getElementById('imageToCrop');
                if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                var initCrop = () => {
                    this.cropper = new Cropper(img, {
                        viewMode: 1, dragMode: 'move', autoCropArea: 0.8,
                        guides: true, center: true, cropBoxMovable: true, cropBoxResizable: true
                    });
                };
                img.complete ? initCrop() : (img.onload = initCrop);
                if (typeof lucide !== 'undefined') lucide.createIcons({nodes: el.querySelectorAll('[data-lucide]')});
            });
            el.addEventListener('hidden.bs.modal', () => {
                if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
            });
        },

        doRotate(deg) { if (this.cropper) this.cropper.rotate(deg); },
        doReset() { if (this.cropper) this.cropper.reset(); },

        async doSave() {
            if (!this.cropper || this.processing) return;
            this.processing = true;
            try {
                var canvas = this.cropper.getCroppedCanvas({ maxWidth: 4096, maxHeight: 4096 });
                var imageData = canvas.toDataURL('image/jpeg', 0.9);
                var token = document.querySelector('meta[name="csrf-token"]')?.content;
                var res = await fetch(this.cropUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ image_data: imageData })
                });
                if (!res.ok) throw new Error('Server error');
                var result = await res.json();
                window.dispatchEvent(new CustomEvent('image-cropped', { detail: result }));
                this.modal.hide();
            } catch (e) {
                console.error('Crop failed:', e);
                alert('Erreur lors du recadrage.');
            } finally {
                this.processing = false;
            }
        }
    };
}
</script>
