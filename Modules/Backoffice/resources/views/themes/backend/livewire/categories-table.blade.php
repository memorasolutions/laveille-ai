<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="check-circle" class="icon-sm flex-shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="alert-triangle" class="icon-sm flex-shrink-0"></i>
            {{ session('error') }}
        </div>
    @endif

    @if(count($selected) > 0)
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3 px-3 py-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded">
            <span class="fw-medium text-body">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="Action groupée">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="activate">{{ __('Activer') }}</option>
                <option value="deactivate">{{ __('Désactiver') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" class="icon-sm"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="border-bottom pb-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="input-group input-group-sm w-auto">
                <span class="input-group-text bg-white border-end-0">
                    <i data-lucide="search" class="icon-sm text-muted"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control form-control-sm border-start-0"
                       placeholder="{{ __('Rechercher une catégorie...') }}"
                       aria-label="Rechercher">
            </div>
            <select wire:model.live="filterActive" class="form-select form-select-sm w-auto" aria-label="Filtrer par statut">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="1">{{ __('Actif') }}</option>
                <option value="0">{{ __('Inactif') }}</option>
            </select>
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th style="width:40px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="width:16px;height:16px;" aria-label="Tout sélectionner">
                    </th>
                    <th class="fw-medium">{{ __('Nom') }}</th>
                    <th class="fw-medium">{{ __('Couleur') }}</th>
                    <th class="fw-medium">{{ __('Articles') }}</th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td>
                        <input type="checkbox" wire:model.live="selected" value="{{ $category->id }}" class="form-check-input" style="width:16px;height:16px;" aria-label="Sélectionner">
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle flex-shrink-0 d-inline-block"
                                  style="width:12px;height:12px;background:{{ $category->color }}"></span>
                            <span class="fw-medium text-body">{{ $category->name }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge small fw-medium d-inline-block text-white"
                              style="background-color:{{ $category->color }}">
                            {{ $category->color }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-medium">
                            {{ $category->articles_count }}
                        </span>
                    </td>
                    <td>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   wire:click="toggleActive({{ $category->id }})"
                                   @checked($category->is_active)
                                   title="{{ $category->is_active ? __('Désactiver') : __('Activer') }}">
                        </div>
                    </td>
                    <td>
                        <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width:32px;height:32px;">
                                <i data-lucide="more-vertical" class="icon-sm"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="dropdown-menu show position-absolute end-0 mt-1 shadow"
                                 style="z-index:50;min-width:140px;">
                                <a href="{{ route('admin.blog.categories.edit', $category) }}"
                                   class="dropdown-item d-flex align-items-center gap-2 text-body">
                                    <i data-lucide="pencil" class="icon-sm text-success"></i> {{ __('Modifier') }}
                                </a>
                                <form action="{{ route('admin.blog.categories.destroy', $category) }}" method="POST"
                                      onsubmit="return confirm('{{ __('Supprimer cette catégorie ?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                        <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-5 text-center text-muted">
                        <i data-lucide="tag" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                        {{ __('Aucune catégorie') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top">
        <span class="text-muted small">{{ $categories->total() }} {{ __('catégorie(s)') }}</span>
        {{ $categories->links() }}
    </div>
</div>
