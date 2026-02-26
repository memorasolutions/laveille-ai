<div class="relative mb-6">
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Rechercher des articles..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
        />
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    @if($search !== '' && $results->isNotEmpty())
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto">
            <div class="py-2">
                @foreach($results as $article)
                    <a
                        href="{{ route('blog.show', $article->slug) }}"
                        class="block px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
                    >
                        <div class="font-semibold text-gray-900 line-clamp-1">{{ $article->title }}</div>
                        <div class="text-sm text-gray-500 mt-1 line-clamp-2">
                            {{ \Illuminate\Support\Str::limit(strip_tags($article->excerpt ?? $article->content), 80) }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($search !== '' && $results->isEmpty())
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg">
            <div class="px-4 py-3 text-gray-500 text-sm">
                Aucun résultat pour '<span class="font-medium">{{ $search }}</span>'
            </div>
        </div>
    @endif
</div>
