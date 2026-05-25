<div class="flex flex-col min-h-screen bg-white">
    <nav class="sticky top-0 z-10 bg-white border-b border-gray-200">
        <div class="flex overflow-x-auto" role="tablist">
            @foreach([
                'composer' => '✍️ Composer',
                'articles' => '📝 Articles',
                'curation' => '📚 Curation',
                'builders' => '🤖 Builders',
                'subscribers' => '📬 Abonnés',
                'affiliates' => '🔗 Affiliés',
                'historique' => '📜 Historique',
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
        @elseif($activeTab === 'affiliates')
            @if($author)
                @livewire('authors.affiliate-link-manager', ['authorProfileId' => $author->id], key('affiliate-mgr-'.$author->id))
            @else
                <p class="text-gray-500 italic bg-gray-50 p-6 rounded">Profil auteur non trouvé.</p>
            @endif
        @elseif($activeTab === 'historique')
            @if($author)
                @livewire('authors.author-activity-log-viewer', ['authorProfileId' => $author->id], key('activity-log-'.$author->id))
            @else
                <p class="text-gray-500 italic bg-gray-50 p-6 rounded">Profil auteur non trouvé.</p>
            @endif
        @elseif($activeTab === 'parametres')
            @if($author)
                @livewire('authors.author-settings', ['authorProfileId' => $author->id], key('author-settings-'.$author->id))
            @else
                <div class="bg-gray-50 rounded-lg p-6">
                    <p class="text-gray-500 italic">Profil auteur non trouvé.</p>
                </div>
            @endif
        @elseif($activeTab === 'subscribers')
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-[#0B7285]">📬 Abonnés newsletter</h2>
                @if(!$author)
                    <p class="text-gray-500 italic">Profil auteur non trouvé.</p>
                @elseif(($subscribersStats['total'] ?? 0) === 0)
                    <div aria-live="polite" class="text-center py-12 px-6 bg-[#F8FAFB] rounded-lg border border-gray-200">
                        <p class="text-lg font-medium text-[#0B7285]">Pas encore d'abonnés.</p>
                        <p class="mt-2 text-gray-600">
                            Partage ton lien
                            <code class="bg-white px-2 py-1 rounded text-[#C2410C] border">{{ '/@'.$author->slug.'#newsletter' }}</code>
                            pour commencer à grandir ta liste.
                        </p>
                    </div>
                @else
                    <div aria-live="polite" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="p-4 rounded-lg border bg-white border-[#0B7285]" style="min-height: 88px;">
                            <p class="text-sm font-medium text-[#0B7285]">Total</p>
                            <p class="text-2xl font-bold mt-1 text-[#0B7285]">{{ $subscribersStats['total'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg border bg-white border-green-700" style="min-height: 88px;">
                            <p class="text-sm font-medium text-green-700">Confirmés</p>
                            <p class="text-2xl font-bold mt-1 text-green-700">{{ $subscribersStats['confirmed'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg border bg-white border-[#C2410C]" style="min-height: 88px;">
                            <p class="text-sm font-medium text-[#C2410C]">En attente</p>
                            <p class="text-2xl font-bold mt-1 text-[#C2410C]">{{ $subscribersStats['pending'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg border bg-white border-gray-500" style="min-height: 88px;">
                            <p class="text-sm font-medium text-gray-700">Désabonnés</p>
                            <p class="text-2xl font-bold mt-1 text-gray-700">{{ $subscribersStats['unsubscribed'] }}</p>
                        </div>
                    </div>

                    <p class="mb-6 text-gray-700">
                        Nouveaux abonnés sur 7 jours :
                        <strong class="text-[#0B7285]">{{ $subscribersStats['last_7_days'] }}</strong>
                    </p>

                    <button type="button" wire:click="exportSubscribersCsv"
                            class="inline-flex items-center gap-2 px-4 py-3 bg-white text-[#C2410C] border-2 border-[#C2410C] rounded-md font-semibold hover:bg-[#C2410C] hover:text-white focus-visible:outline-3 focus-visible:outline-offset-2 min-h-[44px]">
                        📥 Exporter CSV
                    </button>
                @endif
            </div>
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
