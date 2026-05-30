@props([
    'name',
    'title',
    'maxWidth' => '520px',
    'titleIcon' => '',
])

@once
<style>
.ct-modal-overlay { position: fixed; inset: 0; z-index: 99999; background: var(--cmp-overlay-backdrop, rgba(17,20,23,0.5)); display: grid; place-items: center; padding: 20px; }
.ct-modal-content { background: var(--cmp-overlay-bg, #fff); border-radius: var(--cmp-overlay-radius, 1rem); width: 100%; max-height: 80vh; overflow-y: auto; padding: 28px; position: relative; box-shadow: var(--cmp-overlay-shadow, 0 20px 60px rgba(0,0,0,0.2)); }
.ct-modal-close { position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 20px; cursor: pointer; color: var(--sys-text-muted, #52586a); padding: 4px; line-height: 1; border-radius: var(--sys-radius-sm, 0.375rem); }
.ct-modal-close:hover { color: var(--sys-text-default, #1A1D23); }
.ct-modal-close:focus-visible { outline: var(--cmp-overlay-focus-ring-width, 3px) solid var(--cmp-overlay-focus-ring, #9A2A06); outline-offset: 2px; }
.ct-modal-title { font-family: var(--f-heading); font-weight: 700; font-size: 1.2rem; margin: 0 0 16px; color: var(--sys-text-default, #1A1D23); }
.ct-modal-body { color: var(--sys-text-secondary, #4a4f5c); line-height: 1.7; }
</style>
@endonce

<div x-data="{ open: false }"
     x-on:open-{{ $name }}.window="open = true"
     @keydown.escape.window="open = false">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             class="ct-modal-overlay"
             role="dialog" aria-modal="true"
             :aria-labelledby="open ? 'modal-{{ $name }}-title' : null"
             @click.self="open = false">
            <div class="ct-modal-content" style="max-width: {{ $maxWidth }};">
                <button @click="open = false" class="ct-modal-close" aria-label="{{ __('Fermer') }}">✕</button>
                <h3 id="modal-{{ $name }}-title" class="ct-modal-title">
                    @if($titleIcon){{ $titleIcon }} @endif{{ $title }}
                </h3>
                <div class="ct-modal-body">{{ $slot }}</div>
            </div>
        </div>
    </template>
</div>
