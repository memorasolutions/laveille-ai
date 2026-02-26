<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-alert-circle me-2"></i>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-3 g-2">
        <div class="col-md-5">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par clé ou valeur...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterGroup" class="form-select">
                <option value="">Tous les groupes</option>
                @foreach($availableGroups ?? [] as $group)
                <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select wire:model.live="filterType" class="form-select">
                <option value="">Tous les types</option>
                <option value="string">String</option>
                <option value="boolean">Boolean</option>
                <option value="integer">Integer</option>
                <option value="text">Text</option>
                <option value="json">JSON</option>
                <option value="color">Color</option>
            </select>
        </div>
        <div class="col-md-2">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th wire:click="sort('key')" style="cursor:pointer">
                        Clé <i class="ti ti-arrows-sort {{ $sortBy === 'key' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Valeur</th>
                    <th wire:click="sort('group')" style="cursor:pointer">
                        Groupe <i class="ti ti-arrows-sort {{ $sortBy === 'group' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                <tr>
                    <td>
                        <code class="small text-primary">{{ $setting->key }}</code>
                        @if($setting->label)
                        <div class="small text-muted">{{ $setting->label }}</div>
                        @endif
                    </td>
                    <td style="max-width: 260px;">
                        @if($setting->type === 'boolean')
                        <span class="badge {{ $setting->value ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">
                            <i class="ti {{ $setting->value ? 'ti-check' : 'ti-x' }} me-1"></i>
                            {{ $setting->value ? 'Vrai' : 'Faux' }}
                        </span>
                        @elseif($setting->type === 'color' && $setting->value)
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-block rounded border"
                                style="width:20px; height:14px; background:{{ $setting->value }};"></span>
                            <code class="small">{{ $setting->value }}</code>
                        </div>
                        @else
                        <span class="small text-truncate d-block" title="{{ $setting->value }}" style="max-width:240px;">
                            {{ $setting->value ? Str::limit($setting->value, 60) : '—' }}
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($setting->group)
                        <span class="badge bg-azure-lt text-azure">{{ $setting->group }}</span>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary-lt small">{{ $setting->type ?? 'string' }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.settings.edit', $setting) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button wire:click="deleteSetting({{ $setting->id }})"
                                wire:confirm="Supprimer le paramètre « {{ $setting->key }} » ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-settings-off fs-2 d-block mb-2"></i>
                        Aucun paramètre trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $settings->total() }} paramètre(s) au total</div>
        <div>{{ $settings->links() }}</div>
    </div>
</div>
