<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if($articles->isEmpty())
        <div class="text-center py-20 text-gray-400">
            <p class="text-lg">Aucun article pour le moment.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="blog-articles">
            @foreach($articles as $article)
            <article class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
                @if($article->featured_image)
                <a href="{{ route('blog.show', $article) }}">
                    <img src="{{ asset($article->featured_image) }}"
                         alt="{{ $article->title }}"
                         class="w-full h-48 object-cover group-hover:opacity-90 transition">
                </a>
                @endif
                <div class="p-5">
                    @if($article->category)
                    <span class="text-xs font-semibold text-blue-600 uppercase tracking-wide">{{ $article->category }}</span>
                    @endif
                    <h2 class="text-lg font-bold text-gray-900 mt-1 mb-2 leading-tight">
                        <a href="{{ route('blog.show', $article) }}" class="hover:text-blue-600 transition">
                            {{ $article->title }}
                        </a>
                    </h2>
                    @if($article->excerpt)
                    <p class="text-gray-500 text-sm mb-4 line-clamp-3">{{ Str::limit($article->excerpt, 120) }}</p>
                    @endif
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>{{ $article->published_at?->translatedFormat('d M Y') }}</span>
                        <a href="{{ route('blog.show', $article) }}" class="text-blue-600 font-medium hover:underline">
                            Lire →
                        </a>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        @if($hasMore)
        <div class="mt-10 text-center">
            <button wire:click="loadMore"
                    wire:loading.attr="disabled"
                    class="px-8 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-60">
                <span wire:loading.remove>Charger plus d'articles</span>
                <span wire:loading>Chargement...</span>
            </button>
        </div>
        @else
        <div class="mt-10 text-center text-sm text-gray-400">
            Tous les articles sont affichés.
        </div>
        @endif
    @endif
</div>
