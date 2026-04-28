<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-gray-800 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    @if(count($selected) > 0)
        <div class="mb-4 flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ count($selected) }} sélectionné(s)</span>
            <select wire:model.live="bulkAction" class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                <option value="">Choisir</option>
                <option value="activate">Activer</option>
                <option value="deactivate">Désactiver</option>
                <option value="delete">Supprimer</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="Confirmer l'action en masse ?" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                Exécuter
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            <option value="">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
        </select>
        <select wire:model.live="filterRole" class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            <option value="">Tous les rôles</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ $role->name }}</option>
            @endforeach
        </select>
        @if($filterStatus || $filterRole || $search)
            <button wire:click="resetFilters" class="rounded-lg border border-gray-300 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Réinitialiser
            </button>
        @endif
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher..."
               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm">
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-gray-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-3">
                        <input type="checkbox" wire:model.live="selectAll">
                    </th>
                    <th class="cursor-pointer px-6 py-3" wire:click="sort('name')">
                        Nom
                        @if($sortBy === 'name')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="cursor-pointer px-6 py-3" wire:click="sort('email')">
                        Email
                        @if($sortBy === 'email')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3">Statut</th>
                    <th class="px-6 py-3">Rôles</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($users as $user)
                    <tr class="text-gray-900 dark:text-white">
                        <td class="px-6 py-4">
                            <input type="checkbox" wire:model.live="selected" value="{{ $user->id }}">
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Actif</span>
                            @else
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="flex gap-2 px-6 py-4">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-gray-600 hover:underline dark:text-gray-400">Voir</a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Modifier</a>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Supprimer cet utilisateur ?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline dark:text-red-400">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Aucun utilisateur trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-200 px-6 py-3 dark:border-gray-700">
            {{ $users->links() }}
        </div>
    </div>
</div>
