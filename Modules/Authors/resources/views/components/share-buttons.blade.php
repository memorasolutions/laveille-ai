@props(['title' => '', 'url' => '', 'description' => ''])

@php
    $title = $title ?: 'Article';
    $url = $url ?: '';
    $description = $description ?: '';
@endphp

<section class="lv-share-buttons" aria-labelledby="lv-share-h2">
    <h2 id="lv-share-h2" style="font-size:18px; font-weight:700; color:var(--c-primary,#064E5A); margin:0 0 12px;">
        📢 Partager cet article
    </h2>
    <div class="lv-share-row" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
        <a href="https://twitter.com/intent/tweet?text={{ urlencode($title) }}&url={{ urlencode($url) }}"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Partager sur X (Twitter)"
           class="lv-share-btn lv-share-btn-x">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
            <span>X</span>
        </a>

        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($url) }}"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Partager sur LinkedIn"
           class="lv-share-btn lv-share-btn-linkedin">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zm1.782 13.019H3.555V9h3.564v11.452z"/>
            </svg>
            <span>LinkedIn</span>
        </a>

        <button type="button"
                x-data="{ shareMastodon() { const instance = prompt('Entre ton instance Mastodon (ex: https://mastodon.social)', 'https://mastodon.social'); if (instance) { window.open(instance.replace(/\/$/, '') + '/share?text=' + encodeURIComponent('{{ addslashes($title) }} {{ $url }}'), '_blank', 'noopener,noreferrer'); } } }"
                @click="shareMastodon()"
                aria-label="Partager sur Mastodon"
                class="lv-share-btn lv-share-btn-mastodon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21.327 8.566c0-4.339-2.843-5.61-2.843-5.61-1.433-.658-3.894-.935-6.451-.956h-.063c-2.557.021-5.016.298-6.45.956 0 0-2.843 1.272-2.843 5.611 0 .993-.019 2.181.012 3.441.103 4.243.778 8.425 4.701 9.463 1.808.479 3.36.579 4.611.51 2.265-.126 3.536-.809 3.536-.809l-.075-1.643s-1.618.51-3.435.449c-1.8-.061-3.699-.193-3.99-2.404a4.564 4.564 0 0 1-.041-.628s1.766.432 4.005.534c1.37.063 2.654-.08 3.957-.236 2.497-.298 4.671-1.836 4.945-3.241.432-2.211.397-5.398.397-5.437zm-3.353 5.589h-2.081V9.057c0-1.075-.452-1.62-1.357-1.62-1 0-1.501.647-1.501 1.927v2.791h-2.069V9.364c0-1.28-.501-1.927-1.502-1.927-.905 0-1.357.546-1.357 1.62v5.099H6.026V8.903c0-1.074.273-1.927.823-2.558.566-.631 1.307-.955 2.228-.955 1.065 0 1.872.41 2.405 1.229l.518.869.519-.869c.533-.819 1.34-1.229 2.405-1.229.92 0 1.662.324 2.227.955.549.631.823 1.484.823 2.558v5.252z"/>
            </svg>
            <span>Mastodon</span>
        </button>

        <a href="mailto:?subject={{ urlencode($title) }}&body={{ urlencode(($description ? $description.' – ' : '').$url) }}"
           aria-label="Partager par courriel"
           class="lv-share-btn lv-share-btn-email">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
            </svg>
            <span>Courriel</span>
        </a>

        <button type="button"
                x-data="{ copied: false, copy() { if (navigator.clipboard) { navigator.clipboard.writeText('{{ $url }}').then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } } }"
                @click="copy()"
                :aria-pressed="copied"
                aria-label="Copier le lien de l'article"
                class="lv-share-btn lv-share-btn-copy">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
            <span x-text="copied ? '✓ Copié' : 'Copier'"></span>
        </button>

        <button type="button"
                x-data="{ canShare: false }"
                x-init="canShare = !!(navigator.share)"
                x-show="canShare"
                x-cloak
                @click="navigator.share({ title: '{{ addslashes($title) }}', url: '{{ $url }}' }).catch(() => {})"
                aria-label="Partager via le menu natif du système"
                class="lv-share-btn lv-share-btn-native">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
            </svg>
            <span>Partager</span>
        </button>
    </div>
</section>

<style>
    .lv-share-buttons { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(6,78,90,0.12); }
    .lv-share-btn {
        display: inline-flex; align-items: center; gap: 6px;
        min-height: 44px; min-width: 44px; padding: 8px 14px;
        background: transparent; color: var(--c-primary,#064E5A);
        border: 2px solid var(--c-primary,#064E5A); border-radius: 999px;
        text-decoration: none; cursor: pointer; font-size: 14px; font-weight: 600;
        transition: background 200ms, color 200ms, transform 100ms;
    }
    @media (prefers-reduced-motion: reduce) { .lv-share-btn { transition: none; } }
    .lv-share-btn:hover { background: var(--c-primary,#064E5A); color: white; }
    .lv-share-btn:focus-visible { outline: 3px solid var(--c-accent,#9A2A06); outline-offset: 2px; }
    .lv-share-btn:active { transform: scale(0.97); }
</style>
