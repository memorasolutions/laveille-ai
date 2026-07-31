{{-- Round 108 (2026-07-27, passe adversariale) : Correction WCAG 2.2 -
    (1) Focus déplacé dans la modale à l'ouverture (vers Annuler),
    (2) Piège de focus (Tab/Shift+Tab) entre les 2 boutons,
    (3) Focus restauré vers l'élément réellement focalisé au moment du déclenchement
        (document.activeElement, plus fiable que $event.target qui, pour $dispatch,
        pointe vers le $el du composant Alpine dispatcheur - souvent un <div> wrapper
        non focalisable, pas le bouton réellement cliqué par l'utilisateur).
    Conforme à WCAG 2.4.3 (Ordre du focus) et 2.1.2 (No Keyboard Trap - ici un trap
    contrôlé est volontairement ajouté, ce qui est la pratique WAI-ARIA recommandée
    pour une VRAIE boîte de dialogue modale). --}}

@props(['name', 'title' => 'Confirmer', 'message' => '', 'confirmLabel' => 'Confirmer', 'cancelLabel' => 'Annuler', 'variant' => 'danger', 'icon' => '⚠️'])

@once
<style>
.ct-confirm-overlay { position: fixed; inset: 0; z-index: 99999; background: var(--cmp-overlay-backdrop, rgba(17,20,23,0.5)); display: grid; place-items: center; padding: 20px; }
.ct-confirm-content { background: var(--cmp-overlay-bg, #fff); border-radius: var(--cmp-overlay-radius, 1rem); max-width: 440px; width: 100%; box-shadow: var(--cmp-overlay-shadow, 0 20px 60px rgba(0,0,0,0.2)); overflow: hidden; }
.ct-confirm-title { font-size: 1.05rem; font-weight: 700; padding: 20px 24px 8px; display: flex; align-items: center; gap: 10px; color: var(--sys-text-default, #1A1D23); margin: 0; }
.ct-confirm-message { padding: 0 24px 20px; color: var(--sys-text-secondary, #4a4f5c); line-height: 1.5; font-size: 0.95rem; }
.ct-confirm-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 0 24px 24px; }
.ct-confirm-btn-confirm-danger { background-color: #991B1B; color: #fff; border: none; padding: 10px 20px; border-radius: var(--sys-radius-sm, 0.5rem); cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-confirm-warning { background-color: #92400E; color: #fff; border: none; padding: 10px 20px; border-radius: var(--sys-radius-sm, 0.5rem); cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-confirm-info { background-color: #1E3A8A; color: #fff; border: none; padding: 10px 20px; border-radius: var(--sys-radius-sm, 0.5rem); cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-cancel { background-color: #fff; border: 1px solid var(--sys-border-subtle, #e2e8f0); color: var(--sys-text-secondary, #374151); padding: 10px 20px; border-radius: var(--sys-radius-sm, 0.5rem); cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-confirm-danger:focus-visible, .ct-confirm-btn-confirm-warning:focus-visible, .ct-confirm-btn-confirm-info:focus-visible, .ct-confirm-btn-cancel:focus-visible { outline: var(--sys-focus-ring-width, 3px) solid var(--sys-focus-ring, #9A2A06); outline-offset: 2px; }
</style>
@endonce

<div x-data="{
    open: false,
    message: @js($message),
    callback: null,
    triggerEl: null,
    init: function() {
        var self = this;
        this.$watch('open', function(isOpen) {
            if (isOpen) {
                self.$nextTick(function() {
                    self.$refs.cancelBtn.focus();
                });
            } else {
                // Round 122 (2026-07-30, passe adversariale) : triggerEl est capturé au moment du
                // $dispatch, mais quand le déclencheur est un item du menu ⋮ (composant action-menu),
                // l'action exécute `open = false` juste avant : le menu passe en display:none pendant
                // que l'utilisateur lit la boîte de dialogue. Or focus() sur un élément display:none
                // est un NO-OP SILENCIEUX - aucune exception, aucun log - et le focus reste sur
                // <body>. Repli : le bouton ⋮ lui-même (x-ref=trigger), qui reste toujours visible.
                self.$nextTick(function() {
                    if (self.triggerEl && typeof self.triggerEl.focus === 'function') {
                        self.triggerEl.focus();
                    }

                    if (document.activeElement === document.body && self.triggerEl) {
                        var menu = self.triggerEl.closest('[x-ref=menu]');

                        if (menu && menu.parentElement) {
                            var menuTrigger = menu.parentElement.querySelector('[x-ref=trigger]');

                            if (menuTrigger && typeof menuTrigger.focus === 'function') {
                                menuTrigger.focus();
                            }
                        }
                    }

                    self.triggerEl = null;
                });
            }
        });
    },
    trapFocus: function(event) {
        var first = this.$refs.cancelBtn;
        var last = this.$refs.confirmBtn;
        if (event.shiftKey) {
            if (document.activeElement === first) {
                event.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }
}"
     x-on:open-confirm-{{ $name }}.window="triggerEl = document.activeElement; open = true; message = $event.detail.message || @js($message); callback = $event.detail.callback;"
     @keydown.escape.window="open && (open = false)">
    <template x-teleport="body">
        <div x-show="open"
             x-cloak
             x-transition.opacity
             class="ct-confirm-overlay"
             role="dialog"
             aria-modal="true"
             aria-labelledby="confirm-{{ $name }}-title"
             @click.self="open = false">
            <div class="ct-confirm-content" @keydown.tab="trapFocus">
                <h3 class="ct-confirm-title" id="confirm-{{ $name }}-title">
                    <span aria-hidden="true">{!! $icon !!}</span>
                    <span>{{ $title }}</span>
                </h3>
                <p class="ct-confirm-message" x-text="message"></p>
                <div class="ct-confirm-actions">
                    <button type="button" class="ct-confirm-btn-cancel" @click="open = false" x-ref="cancelBtn">{{ $cancelLabel }}</button>
                    <button type="button" class="ct-confirm-btn-confirm-{{ $variant }}" @click="if(typeof callback === 'function'){ callback(); } open = false;" x-ref="confirmBtn">{{ $confirmLabel }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
