<div x-data="{ copied: false }" class="max-w-4xl mx-auto p-6">
    <form wire:submit.prevent="generate" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700">Sujet image</label>
                <input type="text" id="subject" wire:model="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" placeholder="Ex: PME québécoise utilisant IA pour service client">
            </div>

            <div>
                <label for="style" class="block text-sm font-medium text-gray-700">Style</label>
                <select id="style" wire:model="style" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    <option value="realiste">Réaliste</option>
                    <option value="illustration">Illustration</option>
                    <option value="minimaliste">Minimaliste</option>
                    <option value="corporate">Corporate</option>
                    <option value="3d">3D</option>
                    <option value="isometric">Isométrique</option>
                    <option value="watercolor">Aquarelle</option>
                    <option value="photographie">Photographie</option>
                    <option value="bd">BD</option>
                    <option value="anime">Anime</option>
                </select>
            </div>

            <div>
                <label for="composition" class="block text-sm font-medium text-gray-700">Composition</label>
                <select id="composition" wire:model="composition" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    <option value="paysage_16_9">Paysage 16:9</option>
                    <option value="portrait_9_16">Portrait 9:16</option>
                    <option value="square_1_1">Carré 1:1</option>
                    <option value="panoramic_21_9">Panoramique 21:9</option>
                </select>
            </div>

            <div>
                <label for="mood" class="block text-sm font-medium text-gray-700">Ambiance</label>
                <select id="mood" wire:model="mood" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    <option value="lumineux">Lumineux</option>
                    <option value="sombre">Sombre</option>
                    <option value="neutre">Neutre</option>
                    <option value="dramatique">Dramatique</option>
                    <option value="joyeux">Joyeux</option>
                    <option value="professionnel">Professionnel</option>
                </select>
            </div>

            <div>
                <label for="targetAi" class="block text-sm font-medium text-gray-700">IA cible</label>
                <select id="targetAi" wire:model="targetAi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    <option value="dalle3">DALL-E 3</option>
                    <option value="midjourney">Midjourney</option>
                    <option value="imagen3">Imagen 3</option>
                    <option value="flux_pro">Flux Pro</option>
                    <option value="stable_diffusion">Stable Diffusion</option>
                    <option value="gemini_imagen">Gemini Imagen</option>
                </select>
            </div>

            <div>
                <label for="aspectRatio" class="block text-sm font-medium text-gray-700">Ratio</label>
                <select id="aspectRatio" wire:model="aspectRatio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    <option value="16:9">16:9</option>
                    <option value="9:16">9:16</option>
                    <option value="1:1">1:1</option>
                    <option value="4:3">4:3</option>
                    <option value="3:2">3:2</option>
                    <option value="21:9">21:9</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-[#064E5A] text-white font-semibold rounded-md hover:bg-[#085f6b]">
                ✨ Générer le prompt
            </button>
        </div>
    </form>

    @if($generatedResult)
        <div class="mt-8 space-y-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium text-gray-900 mb-2">Prompt généré</h3>
                <p class="text-sm text-gray-700 break-words" x-ref="prompt">{{ $generatedResult['prompt'] }}</p>
            </div>

            @if(!empty($generatedResult['negative_prompt']))
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium text-gray-900 mb-2">Negative prompt (Stable Diffusion)</h3>
                    <p class="text-sm text-gray-700 break-words">{{ $generatedResult['negative_prompt'] }}</p>
                </div>
            @endif

            <div class="flex space-x-3 flex-wrap gap-2">
                <button type="button"
                        x-on:click="navigator.clipboard.writeText($refs.prompt.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                    <span x-text="copied ? 'Copié !' : 'Copier le prompt'"></span>
                </button>

                @foreach($generatedResult['open_urls'] as $ai => $url)
                    @if($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="px-3 py-2 text-sm font-medium rounded shadow-sm text-white bg-[#064E5A] hover:bg-[#085f6b]">
                            Ouvrir dans {{ ucfirst(str_replace('_', ' ', $ai)) }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
