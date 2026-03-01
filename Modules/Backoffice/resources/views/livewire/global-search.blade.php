<div x-data="{ open: false }" @click.outside="open = false" class="position-relative w-100">
    <div class="input-group">
        <div class="input-group-text"><i data-lucide="search"></i></div>
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            placeholder="{{ __('Rechercher...') }}"
            class="form-control"
        />
    </div>

    @if(strlen($query) >= 2)
    <div x-show="open" x-cloak
         class="position-absolute start-0 top-100 mt-1 bg-white border rounded shadow-lg"
         style="width: 320px; z-index: 1050;">

        @if($users->isNotEmpty() || $articles->isNotEmpty() || $settings->isNotEmpty())
            <div class="py-2">

                @if($users->isNotEmpty())
                <div class="px-3 py-1">
                    <p class="mb-1 text-uppercase text-muted small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Utilisateurs') }}</p>
                    @foreach($users as $user)
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="d-flex align-items-center gap-2 rounded px-2 py-1 text-decoration-none text-body search-result-item">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; min-width: 28px; font-size: 0.7rem;">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="fw-medium small">{{ $user->name }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $user->email }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($articles->isNotEmpty())
                <div class="border-top px-3 py-1">
                    <p class="mb-1 text-uppercase text-muted small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Articles') }}</p>
                    @foreach($articles as $article)
                    <a href="{{ route('blog.show', $article->slug) }}"
                       class="d-flex align-items-center gap-2 rounded px-2 py-1 text-decoration-none text-body search-result-item">
                        <i data-lucide="file-text" class="text-muted icon-sm flex-shrink-0"></i>
                        <span class="small">{{ $article->title }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

                @if($settings->isNotEmpty())
                <div class="border-top px-3 py-1">
                    <p class="mb-1 text-uppercase text-muted small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Paramètres') }}</p>
                    @foreach($settings as $setting)
                    <a href="{{ route('admin.settings.index') }}"
                       class="d-flex align-items-center gap-2 rounded px-2 py-1 text-decoration-none text-body search-result-item">
                        <i data-lucide="settings" class="text-muted icon-sm flex-shrink-0"></i>
                        <span class="small">{{ $setting->key }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

            </div>
        @else
            <div class="px-4 py-4 text-center text-muted small">
                {{ __('Aucun résultat pour') }} "{{ $query }}"
            </div>
        @endif
    </div>
    @endif
</div>
