@props([
    'uploadUrl',
    'label' => '🎬 Capture assistée (Screen Capture API)',
    'helpText' => 'Ouvre le site cible dans un autre onglet, accepte les cookies et cadre. Reviens ici puis clique Capturer. Le navigateur demandera quel onglet partager.',
    'enabled' => true,
    {{-- Design doc 2026-08-10 (recadrage frontend), volet C - opt-in STRICT. Defaut false =
         comportement inchange (News, Directory admin edit). setFocalUrl requis uniquement quand
         framingMode=true (seule instance : FAB de Modules/Directory/resources/views/public/show.blade.php). --}}
    'framingMode' => false,
    'setFocalUrl' => null,
    {{-- Selecteur du cadre ou la page affichera la vignette. Transmis tel quel au
         focal-cropper, qui y mesure la bande reellement visible. Null = aucun repere. --}}
    'apercuSelector' => null,
])

@if($enabled)
<div
    x-data="screenshotCaptureComponent({ uploadUrl: @js($uploadUrl), framingMode: @js($framingMode), setFocalUrl: @js($setFocalUrl), apercuSelector: @js($apercuSelector) })"
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
            framingMode: config.framingMode || false,
            setFocalUrl: config.setFocalUrl || null,
            apercuSelector: config.apercuSelector || null,
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
                console.log('[ScreenshotCapture] Opening getDisplayMedia prompt...');

                let stream;
                try {
                    stream = await navigator.mediaDevices.getDisplayMedia({
                        video: { displaySurface: 'browser' },
                        audio: false,
                        preferCurrentTab: false,
                        selfBrowserSurface: 'exclude',
                    });
                    console.log('[ScreenshotCapture] Stream obtained', stream);
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
                    console.log('[ScreenshotCapture] Video track settings', track.getSettings());
                    await new Promise(r => setTimeout(r, 300));
                    const imageCapture = new ImageCapture(track);
                    const bitmap = await imageCapture.grabFrame();
                    console.log('[ScreenshotCapture] Frame grabbed', bitmap.width, 'x', bitmap.height);

                    const canvas = this.$refs.canvas;
                    const ctx = canvas.getContext('2d');

                    // ACTION: branche opt-in "mode cadrage" (Porte 1, design doc 2026-08-10) - AVANT
                    // le crop centre existant, avec retour anticipe si assez de hauteur. Quand
                    // framingMode est false (defaut, toutes les autres pages : News, Directory admin
                    // edit), ce bloc est totalement inerte et le code original ci-dessous s'execute
                    // exactement comme avant, octet pour octet.
                    // MCP: SELF (orchestration UI locale, aucune logique metier > 5 lignes)
                    // RAISON: design doc 2026-08-10 (recadrage frontend), volet C.
                    if (this.framingMode) {
                        const scale = 1200 / bitmap.width;
                        const scaledH = Math.round(bitmap.height * scale);
                        const normalizedH = Math.min(scaledH, 1400);

                        if (normalizedH > 630) {
                            canvas.width = 1200;
                            canvas.height = normalizedH;
                            ctx.drawImage(bitmap, 0, 0, bitmap.width, bitmap.height, 0, 0, 1200, scaledH);
                            bitmap.close();
                            stream.getTracks().forEach(t => t.stop());

                            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                            this.status = 'idle';
                            this.openFramingCropper(dataUrl, normalizedH);
                            return;
                        }

                        this.message = 'Fenêtre trop courte pour le cadrage - vignette centrée appliquée.';
                    }

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
                        stream.getTracks().forEach(t => t.stop());
                        if (blob) {
                            console.log('[ScreenshotCapture] Blob created', blob.size, 'bytes');
                            this.upload(blob);
                        } else {
                            this.status = 'error';
                            this.message = 'Impossible de générer l\'image depuis le canvas.';
                            console.error('[ScreenshotCapture] canvas.toBlob returned null');
                        }
                    }, 'image/jpeg', 0.9);
                } catch (err) {
                    stream.getTracks().forEach(t => t.stop());
                    this.status = 'error';
                    this.message = err.message || 'Erreur lors du traitement de la capture.';
                    console.error('[ScreenshotCapture] capture exception', err);
                }
            },

            finishAndReload() {
                try {
                    const dlg = this.$root.closest('dialog');
                    if (dlg && typeof dlg.close === 'function' && dlg.open) dlg.close();
                } catch (_) {}
                try { window.focus(); } catch (_) {}
                setTimeout(() => { window.location.reload(); }, 1500);
            },

            // Design doc 2026-08-10 (recadrage frontend), volet C - ouvre le focal-cropper partage
            // (x-core::focal-cropper) sur l'image normalisee (jamais un crop centre impose). Le
            // cropper ne fait aucune requete lui-meme : onSave() declenche l'unique enchainement
            // reseau (upload puis set-focal), chaine ici.
            openFramingCropper(dataUrl, normalizedH) {
                if (!window.FocalCropper) {
                    this.status = 'error';
                    this.message = 'Composant de recadrage indisponible.';
                    return;
                }

                window.FocalCropper.open({
                    imageSrc: dataUrl,
                    initialFocal: 0,
                    maxHauteurMaster: normalizedH,
                    apercuSelector: this.apercuSelector,
                    onSave: (focalY) => this.uploadFramedAndSetFocal(dataUrl, focalY),
                });
            },

            async uploadFramedAndSetFocal(dataUrl, focalY) {
                this.status = 'uploading';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                let uploadPayload = null;
                try {
                    const blob = await (await fetch(dataUrl)).blob();
                    const formData = new FormData();
                    formData.append('screenshot', new File([blob], 'capture.jpg', { type: 'image/jpeg' }));
                    if (csrfToken) formData.append('_token', csrfToken);

                    const uploadResponse = await fetch(this.uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json, text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                    });
                    try { uploadPayload = await uploadResponse.json(); } catch (_) {}
                } catch (err) {
                    this.status = 'error';
                    this.message = err.message || 'Erreur réseau lors de l\'upload.';
                    return { ok: false, message: this.message };
                }

                if (!uploadPayload || uploadPayload.ok !== true) {
                    this.status = 'error';
                    this.message = (uploadPayload && (uploadPayload.message || uploadPayload.error)) || 'Erreur serveur lors de l\'upload.';
                    return { ok: false, message: this.message };
                }

                if (!this.setFocalUrl) {
                    this.status = 'success';
                    this.message = 'Capture enregistrée. Rechargement dans 2 s…';
                    this.finishAndReload();
                    return { ok: true };
                }

                try {
                    const focalResponse = await fetch(this.setFocalUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                        body: JSON.stringify({ focal_y: focalY }),
                    });
                    const focalPayload = await focalResponse.json();

                    if (focalPayload && focalPayload.ok) {
                        this.status = 'success';
                        this.message = (focalPayload.message || 'Cadrage appliqué.') + ' Rechargement dans 2 s…';
                        this.finishAndReload();
                        return { ok: true };
                    }
                } catch (_) {}

                this.status = 'error';
                this.message = 'Cadrage non enregistré - réessayez via le bouton Recadrer sur la fiche.';
                return { ok: false, message: this.message };
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

                    console.log('[ScreenshotCapture] Upload response', response.status, 'redirected=', response.redirected);
                    let payload = null;
                    try { payload = await response.json(); } catch (_) {}
                    console.log('[ScreenshotCapture] Response payload', payload);

                    if (payload && payload.ok === true) {
                        this.status = 'success';
                        this.message = (payload.message || 'Succès !') + ' Rechargement dans 2 s…';
                        this.finishAndReload();
                    } else if (!payload && (response.ok || response.redirected)) {
                        this.status = 'success';
                        this.message = 'Succès. Rechargement dans 2 s…';
                        this.finishAndReload();
                    } else {
                        this.status = 'error';
                        this.message = (payload && (payload.message || payload.error))
                            || ('Erreur serveur (HTTP ' + response.status + ')');
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
