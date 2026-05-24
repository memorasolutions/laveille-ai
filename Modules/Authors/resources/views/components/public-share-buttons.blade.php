@props(['url', 'title', 'excerpt' => '', 'imageUrl' => ''])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2 md:gap-3']) }}>
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
       aria-label="Partager sur Facebook">FB</a>

    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-blue-800 text-white hover:bg-blue-900 focus:ring-2 focus:ring-blue-700 focus:ring-offset-2 transition-colors"
       aria-label="Partager sur LinkedIn">LI</a>

    <a href="https://twitter.com/intent/tweet?text={{ urlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-black text-white hover:bg-gray-800 focus:ring-2 focus:ring-gray-700 focus:ring-offset-2 transition-colors"
       aria-label="Partager sur X (Twitter)">X</a>

    <a href="https://bsky.app/intent/compose?text={{ urlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-sky-500 text-white hover:bg-sky-600 focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 transition-colors"
       aria-label="Partager sur Bluesky">B</a>

    <a href="https://www.threads.net/intent/post?text={{ urlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-purple-600 text-white hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors"
       aria-label="Partager sur Threads">T</a>

    <a href="https://wa.me/?text={{ urlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-green-500 text-white hover:bg-green-600 focus:ring-2 focus:ring-green-400 focus:ring-offset-2 transition-colors"
       aria-label="Partager sur WhatsApp">W</a>

    <a href="mailto:?subject={{ urlencode($title) }}&body={{ urlencode($excerpt.' '.$url) }}"
       class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-gray-600 text-white hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
       aria-label="Partager par e-mail">✉</a>

    <button onclick="navigator.clipboard.writeText('{{ $url }}').then(()=>{this.innerText='✓';setTimeout(()=>this.innerText='⎘',2000);})"
            type="button"
            class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors"
            aria-label="Copier le lien">⎘</button>
</div>
