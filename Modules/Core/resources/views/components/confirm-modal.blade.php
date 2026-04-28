@props(['name', 'title' => 'Confirmer', 'message' => '', 'confirmLabel' => 'Confirmer', 'cancelLabel' => 'Annuler', 'variant' => 'danger', 'icon' => '⚠️'])

@once
<style>
.ct-confirm-overlay { position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.5); display: grid; place-items: center; padding: 20px; }
.ct-confirm-content { background: #fff; border-radius: 16px; max-width: 440px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
.ct-confirm-title { font-size: 1.05rem; font-weight: 700; padding: 20px 24px 8px; display: flex; align-items: center; gap: 10px; color: var(--c-dark, #1a1a2e); margin: 0; }
.ct-confirm-message { padding: 0 24px 20px; color: #374151; line-height: 1.5; font-size: 0.95rem; }
.ct-confirm-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 0 24px 24px; }
.ct-confirm-btn-confirm-danger { background-color: #991B1B; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-confirm-warning { background-color: #92400E; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-confirm-info { background-color: #1E3A8A; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-cancel { background-color: #fff; border: 1px solid #e2e8f0; color: #374151; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
.ct-confirm-btn-confirm-danger:focus-visible, .ct-confirm-btn-confirm-warning:focus-visible, .ct-confirm-btn-confirm-info:focus-visible, .ct-confirm-btn-cancel:focus-visible { outline: 3px solid #1E3A8A; outline-offset: 2px; }
</style>
@endonce

<div x-data="{ open: false, message: @js($message), callback: null }"
     x-on:open-confirm-{{ $name }}.window="open = true; message = $event.detail.message || @js($message); callback = $event.detail.callback;"
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
            <div class="ct-confirm-content">
                <h3 class="ct-confirm-title" id="confirm-{{ $name }}-title">
                    <span aria-hidden="true">{!! $icon !!}</span>
                    <span>{{ $title }}</span>
                </h3>
                <p class="ct-confirm-message" x-text="message"></p>
                <div class="ct-confirm-actions">
                    <button type="button" class="ct-confirm-btn-cancel" @click="open = false">{{ $cancelLabel }}</button>
                    <button type="button" class="ct-confirm-btn-confirm-{{ $variant }}" @click="if(typeof callback === 'function'){ callback(); } open = false;">{{ $confirmLabel }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
