<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="position-relative">
            <input type="text" wire:model.live="search"
                   class="form-control form-control-sm ps-4"
                   placeholder="{{ __('Rechercher URL ou titre...') }}"
                   aria-label="{{ __('Rechercher') }}">
            <i data-lucide="search" class="position-absolute" style="left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#6b7280;"></i>
        </div>
        <select wire:model.live="filterActive" class="form-select form-select-sm w-auto" aria-label="{{ __('Filtrer par statut') }}">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="1">{{ __('Actif') }}</option>
            <option value="0">{{ __('Inactif') }}</option>
        </select>
        @if($search || $filterActive !== '')
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> {{ __('Réinitialiser') }}
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr class="border-bottom">
                    <th class="fw-medium user-select-none" style="cursor:pointer;"
                        wire:click="sort('url_pattern')">
                        {{ __('URL Pattern') }}
                        @if($sortBy === 'url_pattern')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="ms-1 text-primary" style="width:13px;height:13px;"></i>
                        @else
                            <i data-lucide="arrow-up-down" class="ms-1 text-muted" style="width:13px;height:13px;opacity:.4;"></i>
                        @endif
                    </th>
                    <th class="fw-medium user-select-none" style="cursor:pointer;"
                        wire:click="sort('title')">
                        {{ __('Titre') }}
                        @if($sortBy === 'title')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="ms-1 text-primary" style="width:13px;height:13px;"></i>
                        @else
                            <i data-lucide="arrow-up-down" class="ms-1 text-muted" style="width:13px;height:13px;opacity:.4;"></i>
                        @endif
                    </th>
                    <th class="fw-medium">{{ __('Robots') }}</th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($metaTags as $tag)
                    <tr>
                        <td>
                            <code class="small text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $tag->url_pattern }}</code>
                        </td>
                        <td class="small text-body">
                            {{ $tag->title ? \Illuminate\Support\Str::limit($tag->title, 50) : '—' }}
                        </td>
                        <td>
                            <span class="badge rounded py-1 px-2 fw-medium small bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                {{ $tag->robots ?? 'index, follow' }}
                            </span>
                        </td>
                        <td>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       wire:click="toggleActive({{ $tag->id }})"
                                       @checked($tag->is_active)
                                       title="{{ $tag->is_active ? __('Désactiver') : __('Activer') }}">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light d-flex align-items-center justify-content-center rounded-circle"
                                        style="width:32px;height:32px;padding:0;">
                                    <i data-lucide="more-horizontal" style="width:16px;height:16px;"></i>
                                </button>
                                <div x-show="open" x-cloak
                                     class="position-absolute end-0 bg-white border rounded shadow py-1"
                                     style="top:100%;margin-top:4px;min-width:140px;z-index:50;">
                                    <a href="{{ route('admin.seo.edit', $tag) }}"
                                       class="d-flex align-items-center gap-2 px-3 py-2 small text-body text-decoration-none">
                                        <i data-lucide="pencil" class="text-success" style="width:14px;height:14px;"></i> {{ __('Modifier') }}
                                    </a>
                                    <form action="{{ route('admin.seo.destroy', $tag) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Supprimer ce tag SEO ?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-link w-100 d-flex align-items-center gap-2 px-3 py-2 small text-danger text-decoration-none text-start">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i> {{ __('Supprimer') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-5 text-center text-muted small">
                            <i data-lucide="tag" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                            {{ __('Aucun tag SEO configuré') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $metaTags->links() }}
</div>
