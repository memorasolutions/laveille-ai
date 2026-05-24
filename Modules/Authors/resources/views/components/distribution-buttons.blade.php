@props(['url', 'title', 'excerpt' => '', 'imageUrl' => ''])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-3']) }}>
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-[#064E5A] text-white hover:bg-[#085f6b] focus:outline-none focus:ring-2 focus:ring-[#C2410C] focus:ring-offset-2 transition-colors"
       aria-label="Partager sur LinkedIn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
    </a>

    <a href="https://bsky.app/intent/compose?text={{ urlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-[#064E5A] text-white hover:bg-[#085f6b] focus:outline-none focus:ring-2 focus:ring-[#C2410C] focus:ring-offset-2 transition-colors"
       aria-label="Partager sur Bluesky">
        <span class="font-bold text-sm">B</span>
    </a>

    <a href="https://www.threads.net/intent/post?text={{ urlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-[#064E5A] text-white hover:bg-[#085f6b] focus:outline-none focus:ring-2 focus:ring-[#C2410C] focus:ring-offset-2 transition-colors"
       aria-label="Partager sur Threads">
        <span class="font-bold text-sm">T</span>
    </a>

    <button onclick="(function(){const i=prompt('Ton instance Mastodon (ex: mastodon.social)');if(i){window.open('https://'+i+'/share?text='+encodeURIComponent('{{ $title }}')+'+'+encodeURIComponent('{{ $url }}'),'_blank');}})()"
            type="button"
            class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-[#064E5A] text-white hover:bg-[#085f6b] focus:outline-none focus:ring-2 focus:ring-[#C2410C] focus:ring-offset-2 transition-colors"
            aria-label="Partager sur Mastodon">
        <span class="font-bold text-sm">M</span>
    </button>

    <a href="mailto:?subject={{ urlencode($title) }}&body={{ urlencode($excerpt.' '.$url) }}"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-[#064E5A] text-white hover:bg-[#085f6b] focus:outline-none focus:ring-2 focus:ring-[#C2410C] focus:ring-offset-2 transition-colors"
       aria-label="Partager par e-mail">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
    </a>
</div>
