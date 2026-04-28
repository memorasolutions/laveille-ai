@props([
    'name',
    'title',
    'maxWidth' => '520px',
    'titleIcon' => '',
])

@once
<style>
.ct-modal-overlay { position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.5); display: grid; place-items: center; padding: 20px; }
.ct-modal-content { background: #fff; border-radius: 16px; width: 100%; max-height: 80vh; overflow-y: auto; padding: 28px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.ct-modal-close { position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 20px; cursor: pointer; color: #6B7280; padding: 4px; line-height: 1; }
.ct-modal-close:hover { color: #1F2937; }
.ct-modal-title { font-family: var(--f-heading); font-weight: 700; font-size: 1.2rem; margin: 0 0 16px; color: var(--c-dark); }
.ct-modal-body { color: #4B5563; line-height: 1.7; }
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
