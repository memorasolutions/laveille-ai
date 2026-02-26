<div>
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="check-circle" class="icon-sm flex-shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Upload drag & drop --}}
    <div class="border rounded-3 mb-4">
        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
            <i data-lucide="upload-cloud" class="icon-sm text-muted"></i>
            <h6 class="fw-medium mb-0">Uploader un fichier</h6>
        </div>
        <div class="p-3">
            <div x-data="{ dragover: false }"
                 x-on:dragover.prevent="dragover = true"
                 x-on:dragleave.prevent="dragover = false"
                 x-on:drop.prevent="dragover = false; const files = $event.dataTransfer.files; if (files.length) { $refs.fileInput.files = files; $refs.fileInput.dispatchEvent(new Event('change')); }"
                 x-on:click="$refs.fileInput.click()"
                 x-bind:class="{ 'border-primary bg-primary bg-opacity-10': dragover, 'border-secondary': !dragover }"
                 class="d-flex flex-column align-items-center justify-content-center text-center rounded-3 border border-2 border-dashed"
                 style="min-height:180px;cursor:pointer;">

                <input type="file"
                       wire:model="file"
                       x-ref="fileInput"
                       accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,video/*"
                       class="d-none">

                <i data-lucide="upload-cloud" class="mb-2 text-muted" style="width:40px;height:40px;opacity:.4;"></i>
                <p class="fw-medium mb-1">Glisser-déposer un fichier ici</p>
                <p class="small text-muted mb-0">ou cliquer pour parcourir</p>
                <p class="small text-muted mt-1 mb-0">Images, PDF, Word, Excel, CSV, vidéo. Max 10 Mo.</p>

                <div wire:loading wire:target="file" class="mt-3">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        Chargement du fichier...
                    </div>
                </div>

                @error('file')
                    <p class="text-danger small mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-3 d-flex justify-content-end">
                <button type="button"
                        wire:click="upload"
                        wire:loading.attr="disabled"
                        class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                    <span wire:loading wire:target="upload">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </span>
                    <i data-lucide="upload-cloud" class="icon-sm" wire:loading.remove wire:target="upload"></i>
                    Uploader
                </button>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <select wire:model.live="filterType" class="form-select form-select-sm w-auto" aria-label="Filtrer par type">
            <option value="">Tous les types</option>
            <option value="image">Images</option>
            <option value="document">Documents</option>
            <option value="video">Vidéos</option>
        </select>
        <div class="input-group input-group-sm w-auto">
            <span class="input-group-text bg-white border-end-0">
                <i data-lucide="search" class="icon-sm text-muted"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm border-start-0"
                   placeholder="Rechercher un fichier..."
                   aria-label="Rechercher">
        </div>
        @if($search !== '' || $filterType !== '')
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" class="icon-sm"></i> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th class="fw-medium">Aperçu</th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('file_name')">
                        Nom du fichier
                        @if($sortBy === 'file_name')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium">Collection</th>
                    <th class="fw-medium">Type MIME</th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('size')">
                        Taille
                        @if($sortBy === 'size')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('created_at')">
                        Date
                        @if($sortBy === 'created_at')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($media as $item)
                    <tr>
                        <td>
                            @if(str_starts_with($item->mime_type, 'image/'))
                                <img src="{{ $item->hasGeneratedConversion('thumbnail') ? $item->getUrl('thumbnail') : $item->getUrl() }}"
                                     alt="{{ $item->file_name }}"
                                     class="rounded object-fit-cover"
                                     style="width:56px;height:56px;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                     style="width:56px;height:56px;">
                                    <i data-lucide="file-text" class="text-muted" style="width:24px;height:24px;"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ $item->getUrl() }}" target="_blank"
                               class="text-primary fw-medium small">
                                {{ $item->file_name }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-muted border fw-medium">
                                {{ $item->collection_name }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $item->mime_type }}</td>
                        <td class="text-muted">
                            @if($item->size >= 1048576)
                                {{ number_format($item->size / 1048576, 2) }} MB
                            @else
                                {{ number_format($item->size / 1024, 1) }} KB
                            @endif
                        </td>
                        <td class="text-muted small">{{ $item->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px;">
                                    <i data-lucide="more-vertical" class="icon-sm"></i>
                                </button>
                                <div x-show="open" x-cloak
                                     class="dropdown-menu show position-absolute end-0 mt-1 shadow"
                                     style="z-index:50;min-width:140px;">
                                    <button wire:click="deleteMedia({{ $item->id }})"
                                            wire:confirm="Supprimer ce fichier définitivement ?"
                                            class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                        <i data-lucide="trash-2" class="icon-sm"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-center text-muted">
                            <i data-lucide="image" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                            Aucun média dans la bibliothèque
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($media->hasPages())
        <div class="mt-3">{{ $media->links() }}</div>
    @endif
</div>
