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
        <div class="col-md-8">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher une catégorie...">
            </div>
        </div>
        <div class="col-md-4">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th wire:click="sort('name')" style="cursor:pointer">
                        Nom <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'name' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Slug</th>
                    <th>Couleur</th>
                    <th>Articles</th>
                    <th>Actif</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($category->color)
                            <span class="d-inline-block rounded-circle flex-shrink-0"
                                style="width:12px; height:12px; background-color:{{ $category->color }};"></span>
                            @endif
                            <span class="fw-medium">{{ $category->name }}</span>
                        </div>
                    </td>
                    <td><code class="small">{{ $category->slug }}</code></td>
                    <td>
                        @if($category->color)
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-block rounded border"
                                style="width:28px; height:18px; background-color:{{ $category->color }};"></span>
                            <small class="text-muted font-monospace">{{ $category->color }}</small>
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-primary-lt">
                            <i class="ti ti-article me-1"></i>{{ $category->articles_count ?? $category->articles()->count() }}
                        </span>
                    </td>
                    <td>
                        <label class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input"
                                wire:click="toggleActive({{ $category->id }})"
                                {{ $category->is_active ? 'checked' : '' }}>
                        </label>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="Supprimer la catégorie « {{ $category->name }} » ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-folder-off fs-2 d-block mb-2"></i>
                        Aucune catégorie trouvée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $categories->total() }} catégorie(s) au total</div>
        <div>{{ $categories->links() }}</div>
    </div>
</div>
