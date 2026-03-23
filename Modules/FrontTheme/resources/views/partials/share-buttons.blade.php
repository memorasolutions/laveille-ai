{{-- Composant réutilisable — boutons de partage social
     Usage: @include('fronttheme::partials.share-buttons', ['title' => 'Mon titre', 'url' => request()->url()])
--}}
<div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;"
     x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ $url }}'); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">

    {{-- Facebook --}}
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}"
       target="_blank" rel="noopener noreferrer"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; background: linear-gradient(135deg, #1877F2, #166FD5); color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(24,119,242,0.3); transition: all 0.2s;"
       aria-label="{{ __('Partager sur Facebook') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Facebook
    </a>

    {{-- X / Twitter --}}
    <a href="https://twitter.com/intent/tweet?text={{ urlencode($title ?? '') }}&url={{ urlencode($url) }}"
       target="_blank" rel="noopener noreferrer"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; background: linear-gradient(135deg, #000, #1a1a1a); color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.3); transition: all 0.2s;"
       aria-label="{{ __('Partager sur X') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        X
    </a>

    {{-- LinkedIn --}}
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($url) }}"
       target="_blank" rel="noopener noreferrer"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; background: linear-gradient(135deg, #0A66C2, #074d9c); color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(10,102,194,0.3); transition: all 0.2s;"
       aria-label="{{ __('Partager sur LinkedIn') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        LinkedIn
    </a>

    {{-- Copier le lien --}}
    <a href="javascript:void(0)" @click="copy()"
       :style="'display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.2s; ' + (copied ? 'background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 2px 8px rgba(5,150,105,0.4);' : 'background: linear-gradient(135deg, #374151, #1F2937); box-shadow: 0 2px 8px rgba(55,65,81,0.3);')"
       role="button"
       aria-label="{{ __('Copier le lien') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        <span x-text="copied ? '{{ __('Copié !') }}' : '{{ __('Copier') }}'"></span>
    </a>
</div>
