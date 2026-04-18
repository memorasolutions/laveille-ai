@props([
    'uploadUrl',
    'label' => '🎬 Capture assistée (Screen Capture API)',
    'helpText' => 'Ouvre le site cible dans un autre onglet, accepte les cookies et cadre. Reviens ici puis clique Capturer. Le navigateur demandera quel onglet partager.',
    'enabled' => true,
])

@if($enabled)
<div
    x-data="screenshotCaptureComponent({ uploadUrl: @js($uploadUrl) })"
    style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px;margin-bottom:16px;"
>
    <h6 class="mb-2">{{ $label }}</h6>
    <p class="text-muted small mb-2">{{ $helpText }}</p>

    <template x-if="!supported">
        <p class="text-warning small fw-semibold mb-0">
            Votre navigateur ne supporte pas la capture d'écran. Utilisez l'upload fichier manuel ci-dessous.
        </p>
    </template>

    <template x-if="supported">
        <div>
            <button
                type="button"
                class="btn btn-sm btn-outline-primary"
                :disabled="status === 'capturing' || status === 'uploading'"
                @click="capture()"
            >
                <i data-lucide="camera" class="icon-sm"></i>
                <span x-text="status === 'capturing' || status === 'uploading' ? 'Travail en cours…' : 'Capturer l\'onglet'"></span>
            </button>

            <div x-show="status !== 'idle'" x-cloak class="mt-2 small">
                <span x-show="status === 'capturing'" class="text-info">Capture en cours…</span>
                <span x-show="status === 'uploading'" class="text-info">Upload en cours…</span>
                <span x-show="status === 'success'" class="text-success fw-semibold" x-text="message"></span>
                <span x-show="status === 'error'" class="text-danger fw-semibold" x-text="'Erreur : ' + message"></span>
            </div>
        </div>
    </template>

    <canvas x-ref="canvas" style="display:none;" width="1200" height="630"></canvas>
</div>

<script>
if (!window.__screenshotCaptureComponentRegistered) {
    window.__screenshotCaptureComponentRegistered = true;

    window.screenshotCaptureComponent = function(config) {
        return {
            uploadUrl: config.uploadUrl,
            status: 'idle',
            message: '',
            supported: false,

            init() {
                this.supported = 'mediaDevices' in navigator
                    && 'getDisplayMedia' in navigator.mediaDevices
                    && typeof ImageCapture !== 'undefined';
            },

            async capture() {
                this.status = 'capturing';
                this.message = '';

                let stream;
                try {
                    stream = await navigator.mediaDevices.getDisplayMedia({
                        video: { displaySurface: 'browser' },
                        audio: false,
                    });
                } catch (err) {
                    this.status = 'error';
                    if (err.name === 'NotAllowedError') {
                        this.message = 'Permission refusée. Vous devez autoriser le partage d\'onglet pour la capture.';
                    } else if (err.name === 'NotSupportedError') {
                        this.message = 'La capture d\'écran n\'est pas supportée par votre navigateur.';
                    } else if (err.name === 'NotFoundError') {
                        this.message = 'Aucune source de capture trouvée.';
                    } else if (err.name === 'AbortError') {
                        this.message = 'La capture a été annulée.';
                    } else if (err.name === 'NotReadableError') {
                        this.message = 'Impossible de lire le flux. Une autre app utilise peut-être la ressource.';
                    } else {
                        this.message = err.message || 'Erreur inconnue lors de la capture.';
                    }
                    return;
                }

                try {
                    const track = stream.getVideoTracks()[0];
                    const imageCapture = new ImageCapture(track);
                    const bitmap = await imageCapture.grabFrame();

                    stream.getTracks().forEach(t => t.stop());

                    const canvas = this.$refs.canvas;
                    const ctx = canvas.getContext('2d');
                    const targetW = 1200;
                    const targetH = 630;
                    canvas.width = targetW;
                    canvas.height = targetH;

                    const srcW = bitmap.width;
                    const srcH = bitmap.height;
                    const targetRatio = targetW / targetH;
                    const srcRatio = srcW / srcH;

                    let cropW, cropH, cropX, cropY;
                    if (srcRatio > targetRatio) {
                        cropH = srcH;
                        cropW = srcH * targetRatio;
                        cropX = (srcW - cropW) / 2;
                        cropY = 0;
                    } else {
                        cropW = srcW;
                        cropH = srcW / targetRatio;
                        cropX = 0;
                        cropY = (srcH - cropH) / 2;
                    }

                    ctx.drawImage(bitmap, cropX, cropY, cropW, cropH, 0, 0, targetW, targetH);
                    bitmap.close();

                    canvas.toBlob((blob) => {
                        if (blob) {
                            this.upload(blob);
                        } else {
                            this.status = 'error';
                            this.message = 'Impossible de générer l\'image depuis le canvas.';
                        }
                    }, 'image/jpeg', 0.9);
                } catch (err) {
                    stream.getTracks().forEach(t => t.stop());
                    this.status = 'error';
                    this.message = err.message || 'Erreur lors du traitement de la capture.';
                }
            },

            async upload(blob) {
                this.status = 'uploading';

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const formData = new FormData();
                formData.append('screenshot', new File([blob], 'capture.jpg', { type: 'image/jpeg' }));
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }

                try {
                    const response = await fetch(this.uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json, text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                    });

                    if (response.ok || response.redirected) {
                        this.status = 'success';
                        this.message = 'Succès ! Rechargez la page pour voir le nouveau screenshot.';
                    } else {
                        this.status = 'error';
                        let errMsg = 'Erreur serveur (HTTP ' + response.status + ')';
                        try {
                            const data = await response.json();
                            errMsg = data.message || data.error || errMsg;
                        } catch (_) {}
                        this.message = errMsg;
                    }
                } catch (err) {
                    this.status = 'error';
                    this.message = err.message || 'Erreur réseau lors de l\'upload.';
                }
            },
        };
    };
}
</script>
@endif
