<div class="flex flex-col min-h-screen bg-white">
    <nav class="sticky top-0 z-10 bg-white border-b border-gray-200">
        <div class="flex overflow-x-auto" role="tablist">
            @foreach([
                'composer' => '✍️ Composer',
                'articles' => '📝 Articles',
                'curation' => '📚 Curation',
                'builders' => '🤖 Builders',
                'parametres' => '⚙️ Paramètres',
                'stats' => '📊 Stats',
            ] as $key => $label)
                <button type="button" wire:click="switchTab('{{ $key }}')" role="tab"
                        class="px-4 py-3 font-medium text-sm md:text-base whitespace-nowrap min-h-[44px]
                               {{ $activeTab === $key ? 'bg-[#064E5A] text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    {!! $label !!}
                </button>
            @endforeach
        </div>
    </nav>

    <div class="flex-grow p-4 md:p-6 max-w-7xl mx-auto w-full">
        @if($activeTab === 'composer')
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Composer</h2>
                <div class="space-y-4">
                    <button type="button" class="w-full py-3 px-4 bg-[#064E5A] text-white rounded-md hover:bg-[#085f6b] min-h-[44px]">
                        Nouveau statut court (≤ 280 caractères)
                    </button>
                    <button type="button" class="w-full py-3 px-4 bg-[#C2410C] text-white rounded-md hover:bg-orange-700 min-h-[44px]">
                        Nouvel article long
                    </button>
                </div>
            </div>
        @elseif($activeTab === 'articles')
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Tes articles</h2>
                <p class="text-gray-500 italic">Liste des articles à brancher sur Modules\Blog\Models\Article::where('user_id', $author->user_id).</p>
            </div>
        @elseif($activeTab === 'curation')
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Curation Inbox</h2>
                <p class="text-gray-500 italic">Composant CurationInbox Livewire à brancher (Phase 2).</p>
            </div>
        @elseif($activeTab === 'builders')
            <div class="bg-gray-50 rounded-lg p-6 space-y-6">
                <h2 class="text-xl font-bold">Builders IA</h2>
                <div>
                    <h3 class="font-medium mb-2">📝 Article Builder</h3>
                    @livewire('authors.article-builder')
                </div>
                <div>
                    <h3 class="font-medium mb-2">🎨 Image Builder</h3>
                    @livewire('authors.image-builder')
                </div>
            </div>
        @elseif($activeTab === 'parametres')
            @if($author)
                @livewire('authors.author-settings', ['authorProfileId' => $author->id], key('author-settings-'.$author->id))
            @else
                <div class="bg-gray-50 rounded-lg p-6">
                    <p class="text-gray-500 italic">Profil auteur non trouvé.</p>
                </div>
            @endif
        @elseif($activeTab === 'stats')
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-6">Statistiques & Recommandations IA</h2>
                @if($author)
                    @php
                        $insights = app(\Modules\Authors\Services\AnalyticsRecommendationService::class)->getCachedInsights($author->id);
                    @endphp
                    <div class="space-y-4">
                        <div class="bg-white p-5 rounded-lg border">
                            <h3 class="font-semibold mb-2">Résumé</h3>
                            <p class="text-gray-700">{{ $insights['summary'] ?? '' }}</p>
                        </div>
                        <div class="bg-white p-5 rounded-lg border">
                            <h3 class="font-semibold mb-2">Recommandations</h3>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($insights['recommendations'] ?? [] as $rec)
                                    <li class="text-gray-700">{{ $rec }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="bg-white p-5 rounded-lg border">
                            <h3 class="font-semibold mb-2">Meilleur moment de publication</h3>
                            <p class="text-[#064E5A] font-bold">{{ $insights['best_publish_time'] ?? '' }}</p>
                        </div>
                        <div class="bg-white p-5 rounded-lg border">
                            <h3 class="font-semibold mb-2">Sujets tendance</h3>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($insights['trending_topics'] ?? [] as $topic)
                                    <li class="text-gray-700">{{ $topic }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <p class="text-gray-600 italic">Profil non trouvé.</p>
                @endif
            </div>
        @endif
    </div>
</div>
