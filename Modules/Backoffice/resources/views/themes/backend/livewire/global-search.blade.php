<div x-data="{ open: false }" @click.outside="open = false" class="position-relative">
    <div class="position-relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            placeholder="Rechercher..."
            aria-label="Rechercher"
            class="form-control border rounded ps-5 pe-3 py-1 focus-ring"
            style="width:220px;padding-left:2.25rem;"
        >
        <i data-lucide="search" class="position-absolute text-muted" style="width:16px;height:16px;left:10px;top:50%;transform:translateY(-50%);"></i>
    </div>

    @if(strlen($query) >= 2)
    <div x-show="open" x-cloak
         class="position-absolute bg-white border rounded-3 shadow mt-1"
         style="top:100%;left:0;width:320px;z-index:50;">

        @if($users->isNotEmpty() || $articles->isNotEmpty() || $settings->isNotEmpty())
            <div class="py-2">

                @if($users->isNotEmpty())
                <div class="px-3 py-2">
                    <p class="small fw-semibold text-muted text-uppercase mb-2">Utilisateurs</p>
                    @foreach($users as $user)
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="d-flex align-items-center gap-3 px-2 py-2 rounded text-body text-decoration-none mb-1">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-semibold small flex-shrink-0"
                             style="width:32px;height:32px;">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-medium text-body">{{ $user->name }}</div>
                            <div class="small text-muted">{{ $user->email }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($articles->isNotEmpty())
                <div class="border-top px-3 py-2">
                    <p class="small fw-semibold text-muted text-uppercase mb-2">Articles</p>
                    @foreach($articles as $article)
                    <a href="{{ route('blog.show', $article->slug) }}"
                       class="d-flex align-items-center gap-3 px-2 py-2 rounded text-body text-decoration-none mb-1">
                        <i data-lucide="file-text" class="text-muted flex-shrink-0" style="width:16px;height:16px;"></i>
                        <span class="fw-medium text-body">{{ $article->title }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($settings->isNotEmpty())
                <div class="border-top px-3 py-2">
                    <p class="small fw-semibold text-muted text-uppercase mb-2">Paramètres</p>
                    @foreach($settings as $setting)
                    <a href="{{ route('admin.settings.index') }}"
                       class="d-flex align-items-center gap-3 px-2 py-2 rounded text-body text-decoration-none mb-1">
                        <i data-lucide="settings" class="text-muted flex-shrink-0" style="width:16px;height:16px;"></i>
                        <span class="fw-medium text-body">{{ $setting->key }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

            </div>
        @else
            <div class="px-3 py-4 text-center">
                <p class="small text-muted">Aucun résultat pour "{{ $query }}"</p>
            </div>
        @endif
    </div>
    @endif
</div>
