<div x-data="{ open: false }" @click.outside="open = false" class="position-relative">
    <div class="position-relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            placeholder="Rechercher..."
            class="form-control ps-40 pe-12 py-8 bg-base border-0"
            style="width: 220px;"
        >
        <iconify-icon icon="ion:search-outline" class="position-absolute top-50 start-0 translate-middle-y ms-12 text-secondary-light"></iconify-icon>
    </div>

    @if(strlen($query) >= 2)
    <div x-show="open" x-cloak
         class="position-absolute top-100 start-0 mt-1 bg-base border border-neutral-200 radius-8 shadow-lg"
         style="z-index: 1050; width: 320px;">

        @if($users->isNotEmpty() || $articles->isNotEmpty() || $settings->isNotEmpty())
            <div class="py-8">

                @if($users->isNotEmpty())
                <div class="px-16 py-8">
                    <p class="text-xs fw-semibold text-secondary-light text-uppercase mb-8">Utilisateurs</p>
                    @foreach($users as $user)
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="d-flex align-items-center gap-12 px-8 py-8 radius-6 hover-bg-transparent hover-text-primary text-black text-decoration-none mb-4">
                        <div class="w-32-px h-32-px bg-primary-100 text-primary-600 rounded-circle d-flex justify-content-center align-items-center fw-semibold text-sm flex-shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold text-sm">{{ $user->name }}</div>
                            <div class="text-xs text-secondary-light">{{ $user->email }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($articles->isNotEmpty())
                <div class="border-top border-neutral-200 px-16 py-8">
                    <p class="text-xs fw-semibold text-secondary-light text-uppercase mb-8">Articles</p>
                    @foreach($articles as $article)
                    <a href="{{ route('blog.show', $article->slug) }}"
                       class="d-flex align-items-center gap-12 px-8 py-8 radius-6 hover-bg-transparent hover-text-primary text-black text-decoration-none mb-4">
                        <iconify-icon icon="solar:document-text-outline" class="text-secondary-light text-lg flex-shrink-0"></iconify-icon>
                        <span class="text-sm fw-medium">{{ $article->title }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($settings->isNotEmpty())
                <div class="border-top border-neutral-200 px-16 py-8">
                    <p class="text-xs fw-semibold text-secondary-light text-uppercase mb-8">Paramètres</p>
                    @foreach($settings as $setting)
                    <a href="{{ route('admin.settings.index') }}"
                       class="d-flex align-items-center gap-12 px-8 py-8 radius-6 hover-bg-transparent hover-text-primary text-black text-decoration-none mb-4">
                        <iconify-icon icon="icon-park-outline:setting-two" class="text-secondary-light text-lg flex-shrink-0"></iconify-icon>
                        <span class="text-sm fw-medium">{{ $setting->key }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

            </div>
        @else
            <div class="px-16 py-24 text-center">
                <p class="text-sm text-secondary-light mb-0">Aucun résultat pour "{{ $query }}"</p>
            </div>
        @endif
    </div>
    @endif
</div>
