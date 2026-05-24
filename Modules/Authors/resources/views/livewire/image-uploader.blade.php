<div class="max-w-2xl mx-auto p-6 border-2 border-dashed border-gray-300 rounded-lg" style="border-color: #064E5A;">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">Uploader une image</h2>

    <div class="mb-4">
        <label for="image-upload" class="block text-sm font-medium text-gray-700 mb-2">Image (max 10 Mo)</label>
        <input type="file" id="image-upload" wire:model="image" accept="image/*"
               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-[#064E5A] file:text-white hover:file:bg-[#085f6b]">
        @error('image') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-6">
        <label for="alt-text" class="block text-sm font-medium text-gray-700 mb-2">Texte alternatif (accessibilité)</label>
        <textarea id="alt-text" wire:model="altText" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Décrivez l'image pour les lecteurs d'écran"></textarea>
    </div>

    @if($image)
        <div class="mb-6">
            <p class="text-sm font-medium text-gray-700 mb-2">Aperçu :</p>
            <img src="{{ $image->temporaryUrl() }}" alt="{{ $altText ?: 'Aperçu' }}" class="max-h-64 rounded-md border">
        </div>
    @endif

    <div class="flex items-center justify-between">
        <button type="button" wire:click="process" wire:loading.attr="disabled"
                class="px-4 py-2 bg-[#064E5A] text-white font-medium rounded-md shadow-sm hover:bg-[#085f6b] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:opacity-50"
                style="min-width: 44px; min-height: 44px;">
            <span wire:loading.remove>Traiter l'image</span>
            <span wire:loading>Traitement…</span>
        </button>
    </div>

    @if($result && !empty($result['variants']))
        <div class="mt-6 p-4 bg-gray-50 rounded-md border border-gray-200">
            <h3 class="font-medium text-gray-900 mb-2">Résultat</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-700">Open Graph (1200×630)</p>
                    <p class="text-sm break-all" style="color: #064E5A;">{{ $result['og_image'] }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Twitter Card (1200×600)</p>
                    <p class="text-sm break-all" style="color: #064E5A;">{{ $result['twitter_card'] }}</p>
                </div>
            </div>
            <p class="text-sm text-gray-600">{{ count($result['variants']) }} variantes générées (5 tailles × 3 formats AVIF/WebP/JPG)</p>
        </div>
    @endif
</div>
