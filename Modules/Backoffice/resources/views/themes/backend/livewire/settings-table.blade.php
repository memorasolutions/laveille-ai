<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filtres --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <select wire:model.live="filterGroup" class="form-select form-select-sm w-auto" aria-label="Filtrer par groupe">
            <option value="">Tous les groupes</option>
            @foreach($groups as $group)
                <option value="{{ $group }}">{{ $group }}</option>
            @endforeach
        </select>
        @if($filterGroup || $search)
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="x-circle" style="width:14px;height:14px;"></i> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Recherche --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm ps-4"
                   style="width:260px;"
                   placeholder="Rechercher une clé ou valeur..."
                   aria-label="Rechercher">
            <i data-lucide="search" class="position-absolute" style="left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#9ca3af;"></i>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr class="border-bottom">
                    <th class="fw-medium user-select-none" style="cursor:pointer;"
                        wire:click="sort('key')">
                        Clé
                        @if($sortBy === 'key')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="ms-1 text-primary" style="width:13px;height:13px;"></i>
                        @else
                            <i data-lucide="arrow-up-down" class="ms-1 text-muted" style="width:13px;height:13px;opacity:.4;"></i>
                        @endif
                    </th>
                    <th class="fw-medium">Valeur</th>
                    <th class="fw-medium">Groupe</th>
                    <th class="fw-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                    <tr>
                        <td>
                            <code class="small text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $setting->key }}</code>
                        </td>
                        <td class="small text-muted">{{ Str::limit($setting->value, 60) }}</td>
                        <td>
                            @if($setting->group)
                                <span class="badge rounded py-1 px-2 fw-medium small bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $setting->group }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light d-flex align-items-center justify-content-center rounded-circle"
                                        style="width:32px;height:32px;padding:0;">
                                    <i data-lucide="more-horizontal" style="width:16px;height:16px;"></i>
                                </button>
                                <div x-show="open" x-cloak
                                     class="position-absolute end-0 bg-white border rounded shadow py-1"
                                     style="top:100%;margin-top:4px;min-width:140px;z-index:50;">
                                    <a href="{{ route('admin.settings.edit', $setting) }}"
                                       class="d-flex align-items-center gap-2 px-3 py-2 small text-body text-decoration-none">
                                        <i data-lucide="pencil" class="text-success" style="width:14px;height:14px;"></i> Modifier
                                    </a>
                                    <form action="{{ route('admin.settings.destroy', $setting) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-link w-100 d-flex align-items-center gap-2 px-3 py-2 small text-danger text-decoration-none text-start"
                                                onclick="return confirm('Confirmer la suppression ?')">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-5 text-center text-muted small">
                            <i data-lucide="settings" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                            Aucun paramètre trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($settings->hasPages())
        <div class="mt-3">{{ $settings->links() }}</div>
    @endif
</div>
