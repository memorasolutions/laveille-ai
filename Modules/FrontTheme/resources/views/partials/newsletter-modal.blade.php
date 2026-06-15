<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@if(Route::has('newsletter.subscribe'))
<div class="modal fade" id="newsletterModal" tabindex="-1" role="dialog" aria-labelledby="newsletterModalLabel" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none;">
            <div style="background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-hover) 100%); padding: 20px 30px 20px 30px; position: relative;">
                <button type="button" id="newsletterModalClose" data-bs-dismiss="modal" aria-label="Fermer" style="position: absolute; top: 8px; right: 8px; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 28px; line-height: 1; cursor: pointer; opacity: 0.85; transition: opacity 0.2s, background-color 0.2s; background: rgba(0,0,0,0.15); border: none; border-radius: 50%; padding: 0; font-weight: 300; outline: none; z-index: 5;">&times;</button>
                <div style="text-align: center; font-size: 40px; line-height: 1;">✉️</div>
                <h3 class="text-center" id="newsletterModalLabel" style="font-family: var(--f-heading); color: #fff; font-weight: 800; margin: 10px 0 8px;">
                    {{ __('Restez informé') }}
                </h3>
                <p class="text-center" style="color: #fff; margin: 0; font-size: 14px;">
                    {{ __('Recevez nos sélections d\'outils et articles directement dans votre boîte courriel.') }}
                </p>
            </div>
            <div style="padding: 20px 30px;">
                <form id="newsletterModalForm">
                    @csrf
                    <x-honeypot />
                    @if(config('services.turnstile.site_key'))<div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-size="invisible" data-action="newsletter"></div>@once @push('scripts')<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endpush @endonce @endif
                    <input type="email" name="email" placeholder="{{ __('Votre courriel *') }}" required aria-label="{{ __('Courriel') }}" autocomplete="email"
                           style="width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px 16px; font-size: 16px; font-family: var(--f-body); margin-bottom: 12px; outline: none; transition: border-color 0.2s; box-sizing: border-box;">
                    <input type="text" name="name" placeholder="{{ __('Votre prénom (optionnel)') }}" aria-label="{{ __('Prénom') }}" autocomplete="given-name"
                           style="width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px 16px; font-size: 16px; font-family: var(--f-body); margin-bottom: 16px; outline: none; transition: border-color 0.2s; box-sizing: border-box;">

                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #374151; margin-bottom: 14px; cursor: pointer; line-height: 1.4;">
                        <input type="checkbox" name="consent" required style="margin-top: 2px; flex-shrink: 0; width: 18px; height: 18px;">
                        <span style="flex: 1 1 auto; min-width: 0;">
                            {!! __('J\'accepte de recevoir l\'infolettre conformément à la <a href=":url" target="_blank" rel="noopener" onclick="event.stopPropagation();" style="color: var(--c-primary); text-decoration: underline;">politique de confidentialité</a>.', ['url' => route('legal.privacy')]) !!}
                        </span>
                    </label>

                    <div id="newsletterModalMessage" class="alert d-none" style="border-radius: 8px; font-size: 14px;"></div>

                    <button type="submit" id="newsletterModalSubmit"
                            style="width: 100%; min-height: 48px; background: var(--c-dark); color: #fff; border: none; border-radius: 8px; padding: 14px; font-family: var(--f-heading); font-weight: 700; font-size: 16px; cursor: pointer; transition: background 0.2s;">
                        <span class="submit-text">{{ __('S\'inscrire') }}</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
#newsletterModal { pointer-events: none; }
#newsletterModal.show { pointer-events: auto; }
#newsletterModal .modal-content { max-height: calc(100vh - 40px); overflow-y: auto !important; overflow-x: hidden; -webkit-overflow-scrolling: touch; }
#newsletterModal .modal-dialog { margin: 20px auto; max-height: calc(100vh - 40px); }
#newsletterModal .modal-dialog.modal-dialog-scrollable { display: flex; max-height: calc(100vh - 40px); }
#newsletterModal .modal-dialog.modal-dialog-scrollable .modal-content { max-height: 100%; overflow: hidden; }
#newsletterModal .modal-dialog.modal-dialog-scrollable .modal-body, #newsletterModal form { overflow-y: auto; }
#newsletterModal input[type="checkbox"] { display: inline-block !important; width: 18px !important; height: 18px !important; appearance: auto !important; -webkit-appearance: checkbox !important; opacity: 1 !important; position: static !important; }
#newsletterModal input:focus { border-color: var(--c-primary) !important; box-shadow: 0 0 0 3px rgba(6,78,90,0.18); }
#newsletterModalSubmit:hover, #newsletterModalSubmit:focus-visible { background: var(--c-primary) !important; }
#newsletterModalClose:hover, #newsletterModalClose:focus-visible { opacity: 1 !important; background: rgba(0,0,0,0.30) !important; outline: 2px solid #fff; outline-offset: 2px; }
@media (max-width: 480px) {
    #newsletterModal .modal-dialog { margin: 10px; max-width: calc(100vw - 20px); }
    #newsletterModal .modal-content { max-height: calc(100vh - 20px); }
}
</style>

@push('scripts')
<script>
(function () {
    var el = document.getElementById('newsletterModal');
    if (! el) return;

    // Ouverture robuste (indépendante de l'API jQuery .modal, instable en BS5)
    function openNewsletterModal() {
        var st = document.getElementById('newsletterScrollTrigger');
        if (st) st.style.display = 'none';
        el.removeAttribute('inert');
        try {
            if (window.bootstrap && window.bootstrap.Modal) {
                (new window.bootstrap.Modal(el)).show();
                return;
            }
        } catch (e) {}
        // Fallback pur si bootstrap indisponible
        el.classList.add('show');
        el.style.display = 'block';
        el.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        if (! document.querySelector('.modal-backdrop')) {
            var bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show';
            document.body.appendChild(bd);
        }
    }

    // Fermeture robuste (le bug venait de $(...).modal('hide') inopérant + inert jamais retiré)
    function closeNewsletterModal() {
        try {
            if (window.bootstrap && window.bootstrap.Modal) {
                var inst = (window.bootstrap.Modal.getInstance && window.bootstrap.Modal.getInstance(el))
                    || (window.bootstrap.Modal.getOrCreateInstance && window.bootstrap.Modal.getOrCreateInstance(el));
                if (inst) { inst.hide(); return; }
            }
        } catch (e) {}
        // Fallback pur
        el.classList.remove('show');
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
        document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    // Quand la modale s'ouvre (data-bs-toggle natif OU openNewsletterModal) : retirer inert
    el.addEventListener('show.bs.modal', function () {
        var st = document.getElementById('newsletterScrollTrigger');
        if (st) st.style.display = 'none';
        el.removeAttribute('inert');
    });
    el.addEventListener('shown.bs.modal', function () { el.removeAttribute('inert'); });

    // Bouton X : fermeture explicite vanilla (en plus du data-bs-dismiss natif)
    var closeBtn = document.getElementById('newsletterModalClose');
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeNewsletterModal();
        });
    }

    // ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && el.classList.contains('show')) {
            closeNewsletterModal();
        }
    });

    // Clic sur le fond (backdrop dans la modale)
    el.addEventListener('click', function (e) {
        if (e.target === el) { closeNewsletterModal(); }
    });

    // Liens « infolettre / newsletter » dans le contenu ouvrent la modale
    document.addEventListener('click', function (e) {
        var a = e.target.closest('.entry-details a, .entry-media a, .wpo-blog-content a, .post a');
        if (! a) return;
        var text = (a.textContent || '').toLowerCase();
        var href = (a.getAttribute('href') || '').toLowerCase();
        if (text.indexOf('infolettre') !== -1 || text.indexOf('newsletter') !== -1 ||
            href.indexOf('infolettre') !== -1 || (href.indexOf('newsletter') !== -1 && href.indexOf('/blog') === -1)) {
            e.preventDefault();
            openNewsletterModal();
        }
    });

    // Soumission du formulaire (fetch natif, indépendant de jQuery)
    var form = document.getElementById('newsletterModalForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('newsletterModalSubmit');
            var msg = document.getElementById('newsletterModalMessage');
            var txt = btn.querySelector('.submit-text');
            var spin = btn.querySelector('.spinner-border');
            msg.classList.add('d-none'); msg.classList.remove('alert-success', 'alert-danger');
            if (txt) txt.classList.add('d-none');
            if (spin) spin.classList.remove('d-none');
            btn.disabled = true;

            var token = (document.querySelector('meta[name="csrf-token"]') || {}).content
                || (form.querySelector('input[name="_token"]') || {}).value || '';
            fetch('{{ route("newsletter.subscribe") }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                body: new FormData(form)
            }).then(function (resp) {
                return resp.json().then(function (data) { return { ok: resp.ok, data: data }; });
            }).then(function (res) {
                if (res.ok) {
                    msg.classList.remove('d-none'); msg.classList.add('alert-success');
                    msg.textContent = (res.data && res.data.message) || '{{ __("Inscription réussie !") }}';
                    form.reset();
                    setTimeout(closeNewsletterModal, 2500);
                } else {
                    var err = '{{ __("Une erreur est survenue.") }}';
                    if (res.data) { err = res.data.message || (res.data.errors ? Object.values(res.data.errors)[0] : err); }
                    msg.classList.remove('d-none'); msg.classList.add('alert-danger');
                    msg.textContent = err;
                }
            }).catch(function () {
                msg.classList.remove('d-none'); msg.classList.add('alert-danger');
                msg.textContent = '{{ __("Une erreur est survenue.") }}';
            }).finally(function () {
                if (spin) spin.classList.add('d-none');
                if (txt) txt.classList.remove('d-none');
                btn.disabled = false;
            });
        });
    }

    // Réinitialisation à la fermeture
    el.addEventListener('hidden.bs.modal', function () {
        if (form) form.reset();
        var msg = document.getElementById('newsletterModalMessage');
        if (msg) { msg.classList.add('d-none'); msg.classList.remove('alert-success', 'alert-danger'); }
    });

    // Exposé global au cas où d'autres déclencheurs en aient besoin
    window.openNewsletterModal = openNewsletterModal;
    window.closeNewsletterModal = closeNewsletterModal;
})();
</script>
@endpush
@endif
