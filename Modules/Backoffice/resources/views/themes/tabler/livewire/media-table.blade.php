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
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un fichier...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterType" class="form-select">
                <option value="">Tous les types</option>
                <option value="image">Images</option>
                <option value="video">Vidéos</option>
                <option value="document">Documents</option>
                <option value="audio">Audio</option>
                <option value="other">Autres</option>
            </select>
        </div>
        <div class="col-md-2">
            <select wire:model.live="viewMode" class="form-select">
                <option value="list">Liste</option>
                <option value="grid">Grille</option>
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
                    <th style="width:60px;">Aperçu</th>
                    <th wire:click="sort('file_name')" style="cursor:pointer">
                        Nom du fichier <i class="ti ti-arrows-sort {{ $sortBy === 'file_name' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th wire:click="sort('size')" style="cursor:pointer">
                        Taille <i class="ti ti-arrows-sort {{ $sortBy === 'size' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Type</th>
                    <th wire:click="sort('created_at')" style="cursor:pointer">
                        Ajouté le <i class="ti ti-arrows-sort {{ $sortBy === 'created_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($media as $file)
                <tr>
                    <td>
                        @if(str_starts_with($file->mime_type ?? '', 'image/'))
                        <img src="{{ $file->getUrl('thumb') ?? $file->getUrl() }}"
                            alt="{{ $file->file_name }}"
                            class="rounded"
                            style="width:48px; height:36px; object-fit:cover;">
                        @else
                        @php
                            $iconMap = [
                                'application/pdf'  => ['ti-file-type-pdf', 'text-danger'],
                                'video/'           => ['ti-video', 'text-purple'],
                                'audio/'           => ['ti-music', 'text-green'],
                                'text/'            => ['ti-file-text', 'text-azure'],
                            ];
                            $icon = 'ti-file'; $color = 'text-muted';
                            foreach($iconMap as $mime => [$i, $c]) {
                                if(str_contains($file->mime_type ?? '', $mime)) { $icon = $i; $color = $c; break; }
                            }
                        @endphp
                        <div class="avatar avatar-sm rounded bg-secondary-lt" style="width:48px; height:36px; display:flex; align-items:center; justify-content:center;">
                            <i class="ti {{ $icon }} {{ $color }}"></i>
                        </div>
                        @endif
                    </td>
                    <td style="max-width: 240px;">
                        <div class="text-truncate small fw-medium" title="{{ $file->file_name }}">
                            {{ $file->file_name }}
                        </div>
                        @if($file->custom_properties['alt'] ?? null)
                        <small class="text-muted">{{ $file->custom_properties['alt'] }}</small>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $file->size ? number_format($file->size / 1024, 1) . ' Ko' : '—' }}
                    </td>
                    <td>
                        <span class="badge bg-secondary-lt small">
                            {{ strtoupper(pathinfo($file->file_name, PATHINFO_EXTENSION)) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $file->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ $file->getUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Voir">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="{{ $file->getUrl() }}" download class="btn btn-sm btn-outline-secondary" title="Télécharger">
                                <i class="ti ti-download"></i>
                            </a>
                            <button wire:click="deleteMedia({{ $file->id }})"
                                wire:confirm="Supprimer ce fichier ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-photo-off fs-2 d-block mb-2"></i>
                        Aucun fichier trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $media->total() }} fichier(s) au total</div>
        <div>{{ $media->links() }}</div>
    </div>
</div>
