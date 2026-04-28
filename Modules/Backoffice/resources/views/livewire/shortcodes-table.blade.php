<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
            <i data-lucide="check-circle" class="icon-sm"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i data-lucide="alert-triangle" class="icon-sm"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bulk actions bar --}}
    @if(count($selected) > 0)
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3 px-3 py-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded">
            <span class="fw-medium text-body">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="Action groupée">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" class="icon-sm"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Search bar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="input-group" style="width:220px">
            <span class="input-group-text">
                <i data-lucide="search" class="icon-sm"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm"
                   placeholder="{{ __('Rechercher...') }}"
                   aria-label="{{ __('Rechercher des shortcodes') }}">
        </div>
        @if($search)
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="x-circle" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th style="width:40px">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="cursor:pointer" aria-label="{{ __('Tout sélectionner') }}">
                    </th>
                    <th class="fw-medium" style="cursor:pointer" wire:click="sort('tag')">
                        Tag
                        @if($sortBy === 'tag')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium" style="cursor:pointer" wire:click="sort('name')">
                        {{ __('Nom') }}
                        @if($sortBy === 'name')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium">Template</th>
                    <th class="fw-medium">{{ __('Paramètres') }}</th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium text-end" style="width:80px">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shortcodes as $shortcode)
                    <tr>
                        <td>
                            <input type="checkbox" wire:model.live="selected" value="{{ $shortcode->id }}" class="form-check-input" style="cursor:pointer" aria-label="{{ __('Sélectionner') }}">
                        </td>
                        <td>
                            <code class="bg-primary bg-opacity-10 text-primary px-2 py-1 rounded small">[{{ $shortcode->tag }}]</code>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                {{ $shortcode->name }}
                                @if($shortcode->has_content)
                                    <i data-lucide="file-text" class="icon-sm text-info" title="{{ __('Accepte du contenu') }}" aria-label="{{ __('Accepte du contenu') }}"></i>
                                @endif
                            </div>
                            @if($shortcode->description)
                                <div class="small text-muted mt-1">{{ Str::limit($shortcode->description, 60) }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="small text-muted">{{ Str::limit($shortcode->html_template, 50) }}</code>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($shortcode->parameters ?? [] as $param)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $param }}</span>
                                @empty
                                    <span class="text-muted small">{{ __('Aucun') }}</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            @if($shortcode->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">{{ __('Actif') }}</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">{{ __('Inactif') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px"
                                        aria-label="{{ __('Actions pour') }} {{ $shortcode->name }}">
                                    <i data-lucide="more-vertical" class="icon-sm"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" :class="{ show: open }" x-show="open" x-cloak style="min-width:140px">
                                    <a href="{{ route('admin.shortcodes.edit', $shortcode) }}"
                                       class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="pencil" class="icon-sm text-success"></i> {{ __('Modifier') }}
                                    </a>
                                    <hr class="dropdown-divider">
                                    <form action="{{ route('admin.shortcodes.destroy', $shortcode) }}" method="POST"
                                          data-confirm="{{ __('Supprimer ce shortcode ?') }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                            <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-center text-muted">
                            <i data-lucide="code" class="d-block mx-auto mb-2" style="width:32px;height:32px"></i>
                            {{ __('Aucun shortcode trouvé.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($shortcodes->hasPages())
        <div class="mt-3">{{ $shortcodes->links() }}</div>
    @endif
</div>
