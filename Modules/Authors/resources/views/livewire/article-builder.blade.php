<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between mb-8 border-b pb-4">
        <div class="flex space-x-6">
            @foreach([1 => 'Brief', 2 => 'Sources', 3 => 'Plan', 4 => 'Prompt'] as $step => $label)
                <div class="{{ $currentStep === $step ? 'font-bold text-[#064E5A] border-b-2 border-[#064E5A]' : 'text-gray-500' }}">
                    {{ $step }}. {{ $label }}
                </div>
            @endforeach
        </div>
    </div>

    @if($currentStep === 1)
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Sujet</label>
                <input wire:model="subject" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ex: La Loi 25 et l'IA générative pour PME">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Audience</label>
                <input wire:model="audience" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ex: dirigeants PME québécoises non techniques">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ton</label>
                    <select wire:model="tone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="pedagogue">Pédagogue</option>
                        <option value="direct">Direct</option>
                        <option value="conversationnel">Conversationnel</option>
                        <option value="formel">Formel</option>
                        <option value="opinion">Opinion</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Angle</label>
                    <select wire:model="angle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="guide_pratique">Guide pratique</option>
                        <option value="etude_de_cas">Étude de cas</option>
                        <option value="comparatif">Comparatif</option>
                        <option value="opinion">Opinion</option>
                        <option value="actualite_commentee">Actualité commentée</option>
                        <option value="tutoriel">Tutoriel</option>
                        <option value="faq">FAQ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Longueur</label>
                    <select wire:model="length" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="court_800">Court (800 mots)</option>
                        <option value="moyen_1500">Moyen (1500 mots)</option>
                        <option value="long_2500">Long (2500 mots)</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Niveau technique</label>
                    <select wire:model="techLevel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">IA cible</label>
                    <select wire:model="targetAi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="claude">Claude</option>
                        <option value="chatgpt">ChatGPT</option>
                        <option value="gemini">Gemini</option>
                        <option value="perplexity">Perplexity</option>
                        <option value="mistral">Mistral</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mots-clés (séparés par des virgules)</label>
                <input wire:model="keywordsInput" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Loi 25, IA générative, PME québécoise">
            </div>
        </div>
    @elseif($currentStep === 2)
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Sources Curation Inbox</h3>
            <p class="text-sm text-gray-600 mb-4">Sélectionne 2-5 sources de ta Curation Inbox à utiliser comme matière première.</p>
            <p class="text-sm italic text-gray-500">(Liste de sources à brancher sur CurationInboxService->search() — Phase 2)</p>
        </div>
    @elseif($currentStep === 3)
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Plan d'article (preview)</h3>
            <p class="text-sm text-gray-600">Le prompt structuré sera généré à l'étape 4. Vérifie les paramètres ci-dessus.</p>
        </div>
    @elseif($currentStep === 4)
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Prompt final</h3>
            @if($generatedResult)
                <div x-data="{ copied: false }" class="relative">
                    <pre class="bg-gray-50 p-4 rounded-md overflow-x-auto text-sm whitespace-pre-wrap">{{ $generatedResult['prompt'] }}</pre>
                    <button type="button"
                            x-on:click="navigator.clipboard.writeText({{ json_encode($generatedResult['prompt']) }}); if (window.toast) { window.toast('Prompt copié', 'success', 2000) }; copied = true; setTimeout(() => copied = false, 2000)"
                            class="absolute top-2 right-2 bg-[#064E5A] text-white px-3 py-1 rounded text-xs">
                        <span x-text="copied ? 'Copié !' : 'Copier'"></span>
                    </button>
                </div>
                <div class="mt-4 flex space-x-3 flex-wrap gap-2">
                    @foreach($generatedResult['open_urls'] as $ai => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="inline-flex items-center px-4 py-2 bg-[#064E5A] text-white font-semibold text-sm rounded hover:bg-[#085f6b]">
                            Ouvrir dans {{ ucfirst($ai) }}
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 italic">Clique sur « Générer » ci-dessous pour produire ton prompt.</p>
            @endif
        </div>
    @endif

    <div class="mt-8 flex justify-between">
        <button type="button" wire:click="previousStep" @disabled($currentStep === 1)
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 disabled:opacity-50">
            ← Précédent
        </button>

        @if($currentStep < 4)
            <button type="button" wire:click="nextStep"
                    class="px-4 py-2 bg-[#064E5A] text-white rounded-md hover:bg-[#085f6b]">
                Suivant →
            </button>
        @else
            <button type="button" wire:click="generate" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-[#C2410C] text-white rounded-md hover:bg-orange-700 disabled:opacity-50">
                <span wire:loading.remove>✨ Générer le prompt</span>
                <span wire:loading>Génération…</span>
            </button>
        @endif
    </div>

    <div class="mt-12 p-4 bg-amber-50 border-l-4 border-amber-400">
        <p class="font-bold text-amber-900">⚠️ Tu restes responsable.</p>
        <p class="text-sm mt-1 text-amber-800">L'IA peut inventer des chiffres ou des sources. Vérifie chaque affirmation factuelle, ajoute ton expérience personnelle, ajuste le ton. Un article 100% IA non vérifié = mauvaise qualité + risque Google "scaled content abuse".</p>
    </div>
</div>
