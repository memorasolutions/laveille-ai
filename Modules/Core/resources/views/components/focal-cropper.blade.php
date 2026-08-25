{{--
    Design doc 2026-08-10 (recadrage frontend) - surcouche plein ecran style Canva pour choisir le
    point focal vertical d'une image. Autonome (markup + script inline), garde de ré-enregistrement
    comme Modules/Core/resources/views/components/screenshot-capture.blade.php. Aucun `src` dans le
    markup statique : l'image n'est injectee que par window.FocalCropper.open() (CA-5).

    API JS : window.FocalCropper.open({ imageSrc, initialFocal, maxHauteurMaster, onSave(focalY),
                                        apercuSelector })
    apercuSelector (optionnel) : selecteur du cadre ou la page affichera la vignette. Le
    composant y mesure la largeur REELLE et lit sa variable CSS --fc-apercu-hauteur-max pour
    dessiner le repere de la bande visible. Absent ou introuvable : aucun repere.
    onSave doit retourner une Promise résolvant { ok: bool, message?: string }. Le composant ne fait
    lui-même AUCUNE requête réseau - un seul enchaînement réseau, décidé par l'appelant, à Enregistrer.

    @author MEMORA solutions <info@memora.ca>
--}}
<dialog id="core-focal-cropper-dialog" class="fc-dialog" aria-label="{{ __('Recadrer la vignette') }}">
    <div class="fc-dialog__inner">
        <div class="fc-dialog__header">
            <h5 class="fc-dialog__title">{{ __('Recadrer la vignette') }}</h5>
            <button type="button" class="fc-btn fc-btn--icon" data-fc-cancel aria-label="{{ __('Annuler et fermer') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <p class="fc-dialog__help">{{ __('Glissez l\'image verticalement (ou utilisez les flèches du clavier). Le cadre blanc est la vignette complète 1200×630, celle des partages et des listes.') }}<span data-fc-help-apercu hidden> {{ __('Le cadre jaune est la bande qui restera visible sur la page, où l\'image est rognée en haut et en bas : gardez l\'essentiel à l\'intérieur.') }}</span></p>

        <div
            class="fc-track"
            data-fc-track
            role="group"
            aria-label="{{ __('Cadre de recadrage - glissez verticalement, ou utilisez les flèches du clavier (Maj pour un ajustement fin)') }}"
            tabindex="0"
        >
            <img data-fc-img-dim class="fc-img fc-img--dim" alt="" aria-hidden="true">
            <img data-fc-img-sharp class="fc-img fc-img--sharp" alt="{{ __('Aperçu du cadrage') }}">
            <div class="fc-frame" data-fc-frame aria-hidden="true">
                <span class="fc-frame__line fc-frame__line--h1"></span>
                <span class="fc-frame__line fc-frame__line--h2"></span>
                <span class="fc-frame__line fc-frame__line--v1"></span>
                <span class="fc-frame__line fc-frame__line--v2"></span>

                {{--
                    Second repere : une page n'affiche pas forcement TOUTE la vignette. Elle la pose
                    en `width: 100%` sous une hauteur plafonnee avec `object-fit: cover`, et le
                    navigateur rogne l'exces, moitie en haut, moitie en bas.

                    Mesures du 2026-08-25, qui expliquent pourquoi ce repere ne peut PAS etre une
                    constante : la fiche d'un outil (plafond 400 px, cadre 1146 px) n'en montre que
                    66,5 %, alors qu'une fiche d'actualite (plafond 420 px, cadre 740 px) la montre
                    EN ENTIER, tout comme un affichage mobile etroit. Ecrire 16,75 % en dur ici
                    dessinerait donc une coupe imaginaire partout ailleurs que sur une fiche d'outil.

                    --fc-cut est calcule a l'ouverture par applyApercu(), a partir de la largeur
                    MESUREE du cadre vise et de sa variable --fc-apercu-hauteur-max. Sans cadre
                    exploitable, ces trois elements restent caches : aucun repere vaut mieux qu'un
                    repere faux.
                --}}
                <span class="fc-cut fc-cut--top" data-fc-cut hidden></span>
                <span class="fc-cut fc-cut--bottom" data-fc-cut hidden></span>
                <span class="fc-safe" data-fc-safe hidden>
                    <span class="fc-safe__tag">{{ __('Visible sur la fiche') }}</span>
                </span>
            </div>
        </div>

        <p class="fc-dialog__error" data-fc-error role="alert" hidden></p>

        <div class="fc-dialog__actions">
            <button type="button" class="fc-btn fc-btn--ghost" data-fc-cancel>{{ __('Annuler') }}</button>
            <button type="button" class="fc-btn fc-btn--primary" data-fc-save>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <span data-fc-save-label>{{ __('Enregistrer le cadrage') }}</span>
            </button>
        </div>
    </div>
</dialog>

<style>
    .fc-dialog { position: fixed; inset: 0; margin: 0; padding: 0; width: 100vw; height: 100vh; max-width: 100vw; max-height: 100vh; border: none; background: #0B0D12; color: #fff; }
    .fc-dialog::backdrop { background: rgba(0,0,0,0.85); }
    .fc-dialog[open] { display: flex; align-items: center; justify-content: center; }
    .fc-dialog__inner { width: min(94vw, 900px); max-height: 92vh; display: flex; flex-direction: column; gap: 14px; padding: 20px; }
    .fc-dialog__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .fc-dialog__title { margin: 0; font-size: 1.2rem; font-weight: 700; color: #fff; }
    .fc-dialog__help { margin: 0; font-size: 0.9rem; color: #E5E7EB; }
    .fc-dialog__error { margin: 0; padding: 10px 12px; border-radius: 8px; background: #7A2004; color: #fff; font-weight: 600; font-size: 0.9rem; }

    .fc-track { position: relative; width: 100%; overflow: hidden; border-radius: 10px; background: #000; cursor: grab; touch-action: none; outline-offset: 3px; }
    .fc-track:active { cursor: grabbing; }
    .fc-track:focus-visible { outline: 3px solid #7DD3FC; }
    .fc-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; user-select: none; pointer-events: none; display: block; }
    .fc-img--dim { opacity: 0.35; }
    .fc-img--sharp { opacity: 1; }
    .fc-frame { position: absolute; left: 0; right: 0; border: 2px solid #fff; box-shadow: 0 0 0 9999px rgba(0,0,0,0.001); pointer-events: none; }
    .fc-frame__line { position: absolute; background: rgba(255,255,255,0.35); }
    .fc-frame__line--h1 { left: 0; right: 0; top: 33.333%; height: 1px; }
    .fc-frame__line--h2 { left: 0; right: 0; top: 66.666%; height: 1px; }
    .fc-frame__line--v1 { top: 0; bottom: 0; left: 33.333%; width: 1px; }
    .fc-frame__line--v2 { top: 0; bottom: 0; left: 66.666%; width: 1px; }

    /* Bande reellement visible sur la page qui affichera la vignette. La valeur de --fc-cut
       est CALCULEE a l'ouverture (visibleHeightFraction), jamais ecrite ici : elle depend de la
       page visee ET de la largeur de l'ecran. Voir le commentaire du markup. */
    .fc-frame { --fc-cut: 0%; }
    .fc-cut[hidden], .fc-safe[hidden] { display: none; }
    .fc-cut { position: absolute; left: 0; right: 0; height: var(--fc-cut); background: rgba(0,0,0,0.55); pointer-events: none; }
    .fc-cut--top { top: 0; }
    .fc-cut--bottom { bottom: 0; }
    .fc-safe { position: absolute; left: 0; right: 0; top: var(--fc-cut); bottom: var(--fc-cut); border: 2px dashed #FBBF24; pointer-events: none; }
    .fc-safe__tag { position: absolute; top: 4px; left: 4px; background: #0B1E27; color: #fff; font-size: 0.7rem; font-weight: 700; line-height: 1; padding: 4px 7px; border-radius: 4px; white-space: nowrap; }

    .fc-dialog__actions { display: flex; justify-content: flex-end; gap: 10px; }
    .fc-btn { min-height: 44px; min-width: 44px; padding: 0 18px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; border: 2px solid transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
    .fc-btn--icon { padding: 0; background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.18); }
    .fc-btn--icon:hover, .fc-btn--icon:focus-visible { background: rgba(255,255,255,0.18); }
    .fc-btn--ghost { background: transparent; color: #fff; border-color: rgba(255,255,255,0.35); }
    .fc-btn--ghost:hover, .fc-btn--ghost:focus-visible { background: rgba(255,255,255,0.1); }
    .fc-btn--primary { background: #064E5A; color: #fff; }
    .fc-btn--primary:hover, .fc-btn--primary:focus-visible { background: #053C46; }
    .fc-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .fc-btn:focus-visible { outline: 3px solid #7DD3FC; outline-offset: 2px; }

    @media (max-width: 640px) { .fc-dialog__inner { width: 100vw; padding: 14px; } }
</style>

<script src="{{ asset('assets/directory/focal-cropper-math.js') }}?v={{ config('version.semver') }}" defer></script>
<script>
if (!window.__focalCropperRegistered) {
    window.__focalCropperRegistered = true;

    document.addEventListener('DOMContentLoaded', function () {
        var dlg = document.getElementById('core-focal-cropper-dialog');
        if (!dlg) return;

        var track = dlg.querySelector('[data-fc-track]');
        var imgDim = dlg.querySelector('[data-fc-img-dim]');
        var imgSharp = dlg.querySelector('[data-fc-img-sharp]');
        var frame = dlg.querySelector('[data-fc-frame]');
        var errorEl = dlg.querySelector('[data-fc-error]');
        var saveBtn = dlg.querySelector('[data-fc-save]');
        var saveLabel = dlg.querySelector('[data-fc-save-label]');
        var cancelBtns = dlg.querySelectorAll('[data-fc-cancel]');
        var cutBands = dlg.querySelectorAll('[data-fc-cut]');
        var safeZone = dlg.querySelector('[data-fc-safe]');
        var helpApercu = dlg.querySelector('[data-fc-help-apercu]');

        var state = { masterHeight: 630, focalY: 0, onSave: null, dragging: false, startClientY: 0, startFocal: 0, saving: false };

        function math() { return window.FocalCropperMath; }

        function render() {
            var m = math();
            if (!m) return;
            var top = m.focalTopPercent(state.focalY, state.masterHeight);
            var bottom = m.focalBottomPercent(state.focalY, state.masterHeight);
            imgSharp.style.clipPath = 'inset(' + top + '% 0 ' + bottom + '% 0)';
            frame.style.top = top + '%';
            frame.style.height = (100 - top - bottom) + '%';
        }

        // Repere de la bande visible sur la page de destination. L'appelant fournit les
        // dimensions REELLES de son cadre d'affichage (largeur mesuree dans le DOM, plafond de
        // hauteur issu de son CSS) ; sans elles, aucun repere n'est dessine. Un repere approximatif
        // serait pire que pas de repere : il ferait deplacer l'image pour une coupe imaginaire.
        function applyApercu(selector) {
            var m = math();
            var cadre = selector ? document.querySelector(selector) : null;
            // La largeur est MESUREE (elle depend de l'ecran) et le plafond de hauteur est LU
            // sur le cadre lui-meme, ou il sert deja de max-height : une seule declaration,
            // donc le repere ne peut pas se desynchroniser du CSS reel de la page.
            var largeur = cadre ? cadre.getBoundingClientRect().width : 0;
            var hauteurMax = cadre
                ? parseFloat(getComputedStyle(cadre).getPropertyValue('--fc-apercu-hauteur-max'))
                : 0;
            var fraction = m ? m.visibleHeightFraction(largeur, hauteurMax) : null;
            var pct = fraction === null ? 0 : m.croppedSidePercent(fraction);
            // Sous un demi pour cent, la coupe est invisible a l'oeil : afficher un lisere
            // dans ce cas ferait croire a une perte qui n'existe pas.
            var montrer = pct > 0.5;

            frame.style.setProperty('--fc-cut', pct + '%');
            cutBands.forEach(function (el) { el.hidden = !montrer; });
            if (safeZone) safeZone.hidden = !montrer;
            if (helpApercu) helpApercu.hidden = !montrer;
        }

        function nudge(deltaPx) {
            var m = math();
            state.focalY = m.clampFocal(state.focalY + deltaPx, state.masterHeight);
            render();
        }

        function showError(message) {
            errorEl.textContent = message;
            errorEl.hidden = false;
        }

        function hideError() {
            errorEl.hidden = true;
            errorEl.textContent = '';
        }

        function close() {
            hideError();
            if (dlg.open) dlg.close();
            state.onSave = null;
        }

        track.addEventListener('pointerdown', function (e) {
            state.dragging = true;
            state.startClientY = e.clientY;
            state.startFocal = state.focalY;
            track.setPointerCapture(e.pointerId);
        });
        track.addEventListener('pointermove', function (e) {
            if (!state.dragging) return;
            var m = math();
            var rect = track.getBoundingClientRect();
            var displayScale = rect.height / m.normalizedMasterHeight(state.masterHeight);
            var deltaScreenPx = e.clientY - state.startClientY;
            var deltaFocal = m.pointerDeltaToFocalDelta(deltaScreenPx, displayScale);
            state.focalY = m.clampFocal(state.startFocal + deltaFocal, state.masterHeight);
            render();
        });
        function endDrag() { state.dragging = false; }
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);

        // Correctif revue adversariale 2026-08-10 (Codex) #4 : Echap et Annuler ne doivent JAMAIS
        // fermer le dialog pendant que l'enregistrement (upload + set-focal) est en cours - sinon
        // la mutation continue en silence, invisible pour l'admin, sans rien pour l'arreter ni la
        // suivre. Garde en tete de close() + etat visuel (boutons Annuler desactives) le temps de
        // l'enregistrement.
        dlg.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp') { e.preventDefault(); nudge(e.shiftKey ? -1 : -10); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); nudge(e.shiftKey ? 1 : 10); }
            else if (e.key === 'Escape') {
                e.preventDefault();
                if (state.saving) return;
                close();
            }
        });

        cancelBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (state.saving) return;
                close();
            });
        });

        saveBtn.addEventListener('click', function () {
            if (!state.onSave || state.saving) return;
            hideError();
            state.saving = true;
            saveBtn.disabled = true;
            cancelBtns.forEach(function (btn) { btn.disabled = true; });
            var previousLabel = saveLabel.textContent;
            saveLabel.textContent = @js(__('Enregistrement…'));

            Promise.resolve()
                .then(function () { return state.onSave(state.focalY); })
                .then(function (result) {
                    if (result && result.ok) {
                        close();
                    } else {
                        showError((result && result.message) || @js(__('Erreur lors de l\'enregistrement du cadrage.')));
                    }
                })
                .catch(function (err) {
                    showError((err && err.message) || @js(__('Erreur réseau lors de l\'enregistrement du cadrage.')));
                })
                .finally(function () {
                    state.saving = false;
                    saveBtn.disabled = false;
                    cancelBtns.forEach(function (btn) { btn.disabled = false; });
                    saveLabel.textContent = previousLabel;
                });
        });

        window.FocalCropper = {
            open: function (config) {
                var m = math();
                if (!m || !config || !config.imageSrc) return;

                state.masterHeight = m.normalizedMasterHeight(config.maxHauteurMaster);
                state.focalY = m.clampFocal(config.initialFocal || 0, state.masterHeight);
                state.onSave = typeof config.onSave === 'function' ? config.onSave : null;

                imgDim.src = config.imageSrc;
                imgSharp.src = config.imageSrc;
                track.style.aspectRatio = '1200 / ' + state.masterHeight;
                applyApercu(config.apercuSelector);
                hideError();
                render();

                if (typeof dlg.showModal === 'function') {
                    dlg.showModal();
                    track.focus();
                }
            },
        };
    });
}
</script>
