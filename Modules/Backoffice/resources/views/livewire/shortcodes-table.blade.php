<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-gray-800 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    @if(count($selected) > 0)
        <div class="mb-4 flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    <div class="flex items-center gap-3 mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher..." class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm bg-white dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        @if($search)
            <button wire:click="resetFilters" class="text-sm text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400">Réinitialiser</button>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-200">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3" style="width:40px">
                        <input type="checkbox" wire:model.live="selectAll">
                    </th>
                    <th wire:click="sort('tag')" class="px-6 py-3 cursor-pointer">
                        <div class="flex items-center gap-1">
                            Tag
                            @if($sortField === 'tag')
                                <span>{{ $sortDirection === 'asc' ? "\u2191" : "\u2193" }}</span>
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('name')" class="px-6 py-3 cursor-pointer">
                        <div class="flex items-center gap-1">
                            Nom
                            @if($sortField === 'name')
                                <span>{{ $sortDirection === 'asc' ? "\u2191" : "\u2193" }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-6 py-3">Template</th>
                    <th class="px-6 py-3">Statut</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shortcodes as $shortcode)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4">
                            <input type="checkbox" wire:model.live="selected" value="{{ $shortcode->id }}">
                        </td>
                        <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400">{{ $shortcode->tag }}</td>
                        <td class="px-6 py-4">{{ $shortcode->name }}</td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($shortcode->html_template, 50) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($shortcode->is_active)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Actif</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.shortcodes.edit', $shortcode) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Aucun shortcode trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $shortcodes->links() }}
    </div>
</div>
