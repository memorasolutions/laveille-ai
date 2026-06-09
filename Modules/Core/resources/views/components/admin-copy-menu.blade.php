@props(['items' => [], 'label' => 'Admin'])

{{-- Composant GÉNÉRIQUE réutilisable : bouton + menu déroulant ; chaque entrée copie un
     texte (multi-lignes) dans le presse-papier. Base déléguée Hermes, corrigée à l'intégration
     (lecture du texte via <textarea> ref au lieu de JSON inline, @once style, fallback execCommand). --}}

@once
<style>
.ct-acm-wrap { position: relative; display: inline-block; }
.ct-acm-trigger { display:inline-flex; align-items:center; gap:.375rem; padding:.375rem .75rem; min-height:44px; border:1px solid #e2e8f0; background:rgba(255,255,255,.7); border-radius:.5rem; font-size:.875rem; line-height:1.2; color:#064E5A; cursor:pointer; transition:background-color .2s, border-color .2s; }
.ct-acm-trigger:hover { background:rgba(241,245,249,.95); border-color:#cbd5e1; }
.ct-acm-trigger:focus-visible { outline:2px solid #0B7285; outline-offset:2px; }
.ct-acm-menu { position:absolute; top:100%; right:0; z-index:50; display:flex; flex-direction:column; width:max-content; min-width:240px; max-width:330px; margin-top:.375rem; padding:.375rem 0; background:#fff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.14); }
.ct-acm-item { display:flex; align-items:center; gap:.5rem; width:100%; padding:.625rem 1rem; background:none; border:none; text-align:left; font-size:.875rem; line-height:1.3; color:#064E5A; cursor:pointer; transition:background-color .15s; }
.ct-acm-item:hover { background:#f8fafc; }
.ct-acm-item:focus-visible { outline:2px solid #0B7285; outline-offset:-2px; }
.ct-acm-item.is-copied { color:#0B7285; font-weight:600; }
.ct-acm-src { position:absolute; left:-9999px; top:0; width:1px; height:1px; opacity:0; }
</style>
@endonce

@if (!empty($items))
<div class="ct-acm-wrap"
     x-data="{
        open: false,
        copied: null,
        async copy(i, ref) {
            const el = this.$refs[ref];
            const text = el.value;
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                el.removeAttribute('hidden');
                el.select();
                document.execCommand('copy');
                el.setAttribute('hidden', '');
            }
            this.copied = i;
            setTimeout(() => { this.copied = null; }, 1500);
        }
     }"
     x-on:click.outside="open = false"
     x-on:keydown.escape.window="open = false">

    <button type="button" class="ct-acm-trigger"
            x-on:click="open = !open"
            aria-haspopup="menu" :aria-expanded="open"
            aria-label="Actions admin (copier dans le presse-papier)">
        <span aria-hidden="true">⚙️</span> {{ $label }}
    </button>

    <div class="ct-acm-menu" role="menu" x-show="open" x-transition x-cloak>
        @foreach ($items as $i => $item)
            <button type="button" role="menuitem" class="ct-acm-item"
                    :class="{ 'is-copied': copied === {{ $i }} }"
                    x-on:click="copy({{ $i }}, 'acmsrc{{ $i }}')">
                <span aria-hidden="true">{{ $item['icon'] ?? '📋' }}</span>
                <span x-text="copied === {{ $i }} ? 'Copié ✓' : @js($item['label'])"></span>
            </button>
            <textarea x-ref="acmsrc{{ $i }}" class="ct-acm-src" hidden readonly>{{ $item['text'] }}</textarea>
        @endforeach
    </div>
</div>
@endif
