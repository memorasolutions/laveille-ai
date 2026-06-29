<div class="bg-gray-50 rounded-lg p-6">
    <h2 class="text-xl font-bold mb-4 text-[#064E5A]">🔗 Liens affiliés</h2>

    @if(session('success'))
        <div role="status" aria-live="polite" class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="bg-white rounded-lg p-4 mb-6 border border-gray-200">
        <h3 class="font-bold mb-3 text-[#064E5A]">
            {{ $editingId ? '✏️ Modifier le lien' : '➕ Nouveau lien affilié' }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label for="aff-slug" class="block text-sm font-medium text-[#064E5A] mb-1">Slug</label>
                <input id="aff-slug" type="text" wire:model="slug" placeholder="amazon-livre"
                    class="w-full px-3 py-2 border border-gray-300 rounded min-h-[44px] focus:outline-3 focus:outline-[#9A2A06]"
                    aria-describedby="aff-slug-help">
                <small id="aff-slug-help" class="text-xs text-gray-600">URL : /go/{slug} · lowercase + tirets</small>
                @error('slug') <span role="alert" class="text-[#9A2A06] text-sm">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="aff-dest" class="block text-sm font-medium text-[#064E5A] mb-1">URL destination</label>
                <input id="aff-dest" type="url" wire:model="destinationUrl" placeholder="https://amazon.com/dp/..."
                    class="w-full px-3 py-2 border border-gray-300 rounded min-h-[44px] focus:outline-3 focus:outline-[#9A2A06]">
                @error('destinationUrl') <span role="alert" class="text-[#9A2A06] text-sm">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="aff-label" class="block text-sm font-medium text-[#064E5A] mb-1">Label (optionnel)</label>
                <input id="aff-label" type="text" wire:model="label" placeholder="Mon livre"
                    class="w-full px-3 py-2 border border-gray-300 rounded min-h-[44px] focus:outline-3 focus:outline-[#9A2A06]">
            </div>
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" class="px-4 py-2 min-h-[44px] bg-[#064E5A] text-white font-semibold rounded hover:bg-[#043C45] focus-visible:outline-3 focus-visible:outline-[#9A2A06]">
                {{ $editingId ? 'Enregistrer' : 'Créer' }}
            </button>
            @if($editingId)
                <button type="button" wire:click="resetForm" class="px-4 py-2 min-h-[44px] border border-[#064E5A] text-[#064E5A] rounded">
                    Annuler
                </button>
            @endif
        </div>
    </form>

    @if($links->isEmpty())
        <p class="text-center py-8 text-gray-600 bg-white rounded">
            Aucun lien affilié. Crée le premier ci-dessus.
        </p>
    @else
        <div class="overflow-x-auto bg-white rounded-lg">
            <table class="min-w-full text-sm" role="table" aria-label="Liens affiliés">
                <thead class="bg-[#F8FAFB]">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Slug</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Destination</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Label</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Clicks</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($links as $link)
                        <tr class="border-t border-gray-200" wire:key="link-{{ $link->id }}">
                            <td class="px-4 py-3 font-mono text-[#9A2A06]">/go/{{ $link->slug }}</td>
                            <td class="px-4 py-3 text-gray-700 max-w-xs truncate"><a href="{{ $link->destination_url }}" target="_blank" rel="noopener" class="underline">{{ $link->destination_url }}</a></td>
                            <td class="px-4 py-3 text-gray-600">{{ $link->label ?: '—' }}</td>
                            <td class="px-4 py-3 font-bold text-[#064E5A]">{{ $link->clicks_count }}</td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="startEdit({{ $link->id }})" class="text-[#064E5A] hover:underline mr-3 min-h-[44px]" aria-label="Modifier {{ $link->slug }}">✏️ Modifier</button>
                                <button type="button" wire:click="delete({{ $link->id }})" wire:confirm="Supprimer ce lien définitivement ?" class="text-[#9A2A06] hover:underline min-h-[44px]" aria-label="Supprimer {{ $link->slug }}">🗑️ Supprimer</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('backoffice::partials.infinite-scroll', ['paginator' => \$links])
    @endif
</div>
