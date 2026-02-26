<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            placeholder="Rechercher..."
            class="w-48 lg:w-64 rounded-lg border border-gray-300 bg-white px-4 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
        />
        <svg class="absolute right-3 top-2 h-4 w-4 text-gray-400" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>

    @if(strlen($query) >= 2)
    <div x-show="open" x-cloak
         class="absolute left-0 top-full z-50 mt-1 w-80 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">

        @if($users->isNotEmpty() || $articles->isNotEmpty() || $settings->isNotEmpty())
            <div class="py-2">

                @if($users->isNotEmpty())
                <div class="px-3 py-1">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Utilisateurs</p>
                    @foreach($users as $user)
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-gray-400">{{ $user->email }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($articles->isNotEmpty())
                <div class="border-t border-gray-100 px-3 py-1 dark:border-gray-700">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Articles</p>
                    @foreach($articles as $article)
                    <a href="{{ route('blog.show', $article->slug) }}"
                       class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>{{ $article->title }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($settings->isNotEmpty())
                <div class="border-t border-gray-100 px-3 py-1 dark:border-gray-700">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Paramètres</p>
                    @foreach($settings as $setting)
                    <a href="{{ route('admin.settings.index') }}"
                       class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $setting->key }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

            </div>
        @else
            <div class="px-4 py-6 text-center text-sm text-gray-400">
                Aucun résultat pour "{{ $query }}"
            </div>
        @endif
    </div>
    @endif
</div>
