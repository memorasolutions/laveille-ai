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

    <div class="mb-3">
        <div class="input-icon" style="max-width:400px;">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par route ou titre...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th wire:click="sort('route_name')" style="cursor:pointer">
                        Route <i class="ti ti-arrows-sort {{ $sortBy === 'route_name' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th wire:click="sort('title')" style="cursor:pointer">
                        Titre SEO <i class="ti ti-arrows-sort {{ $sortBy === 'title' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Description</th>
                    <th>OG Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($metaTags as $meta)
                <tr>
                    <td>
                        <code class="small text-primary">{{ $meta->route_name }}</code>
                        @if($meta->locale)
                        <span class="badge bg-secondary-lt ms-1">{{ strtoupper($meta->locale) }}</span>
                        @endif
                    </td>
                    <td style="max-width: 220px;">
                        <div class="text-truncate small fw-medium" title="{{ $meta->title }}">
                            {{ $meta->title ?: '—' }}
                        </div>
                        @if($meta->title)
                        @php $len = strlen($meta->title); @endphp
                        <small class="{{ $len < 50 ? 'text-warning' : ($len > 60 ? 'text-danger' : 'text-success') }}">
                            {{ $len }}/60 car.
                        </small>
                        @endif
                    </td>
                    <td style="max-width: 240px;">
                        <span class="text-muted small text-truncate d-block" title="{{ $meta->description }}">
                            {{ $meta->description ? Str::limit($meta->description, 80) : '—' }}
                        </span>
                        @if($meta->description)
                        @php $len = strlen($meta->description); @endphp
                        <small class="{{ $len < 120 ? 'text-warning' : ($len > 160 ? 'text-danger' : 'text-success') }}">
                            {{ $len }}/160 car.
                        </small>
                        @endif
                    </td>
                    <td>
                        @if($meta->og_image)
                        <img src="{{ $meta->og_image }}" alt="OG" class="rounded" style="width:48px; height:28px; object-fit:cover;">
                        @else
                        <span class="text-muted small"><i class="ti ti-photo-off me-1"></i>Aucune</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.seo.edit', $meta) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button wire:click="deleteMeta({{ $meta->id }})"
                                wire:confirm="Supprimer cette méta-balise ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-tag-off fs-2 d-block mb-2"></i>
                        Aucune méta-balise trouvée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $metaTags->total() }} méta-balise(s) au total</div>
        <div>{{ $metaTags->links() }}</div>
    </div>
</div>
