{{-- Composant aide réutilisable site-wide #16 S84 (Memora pattern <x-core::*>) --}}
{{-- Usage : <x-core::help-modal /> placé 1× dans master.blade.php --}}
{{-- Trigger : <button @click="$dispatch('open-help-modal', { title, body })">ⓘ</button> --}}
{{-- body accepte HTML (rendu via x-html) --}}
@once
<style>
/* Fallback styles si <x-core::modal> pas chargé sur la page */
.ct-modal-overlay { position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.5); display: grid; place-items: center; padding: 20px; }
.ct-modal-content { background: #fff; border-radius: 16px; width: 100%; max-height: 80vh; overflow-y: auto; padding: 28px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.ct-modal-close { position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 20px; cursor: pointer; color: var(--c-text-muted, #52586a); padding: 4px; line-height: 1; }
.ct-modal-close:hover, .ct-modal-close:focus-visible { color: var(--c-dark, #1F2937); outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }
.ct-modal-title { font-family: var(--f-heading); font-weight: 700; font-size: 1.2rem; margin: 0 0 16px; color: var(--c-dark, #1F2937); }
.ct-modal-body { color: var(--c-text-secondary, #4a4f5c); line-height: 1.7; }
.ct-modal-body p { margin: 0 0 0.75rem; }
.ct-modal-body p:last-child { margin-bottom: 0; }
.ct-help-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    margin-left: 6px;
    padding: 0;
    border: 1.5px solid var(--c-primary, #064E5A);
    border-radius: 50%;
    background: transparent;
    color: var(--c-primary, #064E5A);
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    line-height: 1;
    vertical-align: middle;
}
.ct-help-btn:hover, .ct-help-btn:focus-visible {
    background: var(--c-primary, #064E5A);
    color: #fff;
    outline: 2px solid var(--c-primary, #064E5A);
    outline-offset: 2px;
}
</style>
@endonce

<div x-data="{ open: false, modalTitle: '', modalBody: '' }"
     x-on:open-help-modal.window="
        open = true;
        modalTitle = $event.detail?.title || 'ⓘ {{ __('Aide') }}';
        modalBody = $event.detail?.body || '';
        $nextTick(() => { document.querySelector('.ct-help-modal-content button[aria-label]')?.focus(); });
     "
     @keydown.escape.window="open = false">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             class="ct-modal-overlay"
             role="dialog" aria-modal="true"
             aria-labelledby="ct-help-modal-title"
             @click.self="open = false">
            <div class="ct-modal-content ct-help-modal-content" style="max-width: 520px;">
                <button @click="open = false" class="ct-modal-close" aria-label="{{ __('Fermer') }}">✕</button>
                <h3 id="ct-help-modal-title" class="ct-modal-title" x-text="modalTitle"></h3>
                <div class="ct-modal-body" x-html="modalBody"></div>
            </div>
        </div>
    </template>
</div>
