<div x-data="{ open: false }" @click.outside="open = false" class="position-relative">
    <div class="input-icon">
        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
        <input type="text"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            @input="open = true"
            class="form-control"
            placeholder="Rechercher..."
            style="width: 220px;"
            autocomplete="off">
    </div>

    @if(strlen($query ?? '') >= 2)
    <div x-show="open" x-cloak x-transition
        class="position-absolute top-100 start-0 mt-1 bg-white border rounded shadow-lg"
        style="z-index: 1050; width: 320px; max-height: 400px; overflow-y: auto;">

        @if(count($users ?? []) > 0)
        <div class="px-3 py-2 border-bottom bg-light">
            <small class="text-uppercase text-muted fw-bold"><i class="ti ti-users me-1"></i>Utilisateurs</small>
        </div>
        @foreach($users as $user)
        <a href="{{ route('admin.users.show', $user) }}"
            class="d-flex align-items-center px-3 py-2 text-decoration-none text-body border-bottom hover-bg"
            @click="open = false">
            <span class="avatar avatar-xs rounded-circle me-2"
                style="background-color: var(--tblr-primary); color: white; font-size: .65rem; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center;">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div>
                <div class="fw-bold small">{{ $user->name }}</div>
                <small class="text-muted">{{ $user->email }}</small>
            </div>
        </a>
        @endforeach
        @endif

        @if(count($articles ?? []) > 0)
        <div class="px-3 py-2 border-bottom bg-light">
            <small class="text-uppercase text-muted fw-bold"><i class="ti ti-article me-1"></i>Articles</small>
        </div>
        @foreach($articles as $article)
        <a href="{{ route('admin.articles.edit', $article) }}"
            class="d-flex align-items-center px-3 py-2 text-decoration-none text-body border-bottom"
            @click="open = false">
            <i class="ti ti-article me-2 text-muted"></i>
            <div class="small">{{ $article->getTranslation('title', app()->getLocale()) }}</div>
        </a>
        @endforeach
        @endif

        @if(count($settings ?? []) > 0)
        <div class="px-3 py-2 border-bottom bg-light">
            <small class="text-uppercase text-muted fw-bold"><i class="ti ti-settings me-1"></i>Paramètres</small>
        </div>
        @foreach($settings as $setting)
        <a href="{{ route('admin.settings.edit', $setting) }}"
            class="d-flex align-items-center px-3 py-2 text-decoration-none text-body border-bottom"
            @click="open = false">
            <i class="ti ti-settings me-2 text-muted"></i>
            <div class="small">{{ $setting->key }}</div>
        </a>
        @endforeach
        @endif

        @if(count($users ?? []) === 0 && count($articles ?? []) === 0 && count($settings ?? []) === 0)
        <div class="px-3 py-4 text-center text-muted">
            <i class="ti ti-search-off fs-2 d-block mb-1"></i>
            Aucun résultat pour "{{ $query }}"
        </div>
        @endif
    </div>
    @endif
</div>
