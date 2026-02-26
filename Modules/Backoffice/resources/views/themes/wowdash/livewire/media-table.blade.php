<div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-20" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Upload drag & drop --}}
    <div class="card border mb-20">
        <div class="card-header py-12 px-16">
            <h6 class="mb-0 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:upload-outline" class="text-xl"></iconify-icon>
                Uploader un fichier
            </h6>
        </div>
        <div class="card-body py-16 px-16">
            <div x-data="{ dragover: false }"
                 x-on:dragover.prevent="dragover = true"
                 x-on:dragleave.prevent="dragover = false"
                 x-on:drop.prevent="dragover = false; const files = $event.dataTransfer.files; if (files.length) { $refs.fileInput.files = files; $refs.fileInput.dispatchEvent(new Event('change')); }"
                 x-on:click="$refs.fileInput.click()"
                 x-bind:class="{ 'border-primary-600 bg-primary-50': dragover }"
                 class="d-flex flex-column align-items-center justify-content-center text-center"
                 style="min-height: 200px; border: 2px dashed #ddd; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">

                <input type="file"
                       wire:model="file"
                       x-ref="fileInput"
                       accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,video/*"
                       class="d-none">

                <iconify-icon icon="solar:cloud-upload-outline" class="text-5xl text-secondary-light mb-8"></iconify-icon>
                <p class="fw-medium mb-4">Glisser-déposer un fichier ici</p>
                <p class="text-sm text-secondary-light mb-0">ou cliquer pour parcourir</p>
                <p class="text-xs text-neutral-400 mt-8 mb-0">Images, PDF, Word, Excel, CSV, vidéo. Max 10 Mo.</p>

                <div wire:loading wire:target="file" class="mt-12">
                    <span class="spinner-border spinner-border-sm text-primary-600"></span>
                    <span class="ms-2 text-sm">Chargement du fichier...</span>
                </div>

                @error('file')
                    <p class="text-danger-main text-sm mt-8 mb-0">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-12 text-end">
                <button type="button"
                        wire:click="upload"
                        wire:loading.attr="disabled"
                        class="btn btn-primary-600 d-flex align-items-center gap-2 ms-auto">
                    <span wire:loading wire:target="upload" class="spinner-border spinner-border-sm"></span>
                    <iconify-icon icon="solar:upload-outline" wire:loading.remove wire:target="upload"></iconify-icon>
                    Uploader
                </button>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-20">
        <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px" wire:model.live="filterType">
            <option value="">Tous les types</option>
            <option value="image">Images</option>
            <option value="document">Documents</option>
            <option value="video">Vidéos</option>
        </select>

        <form class="navbar-search">
            <input type="text" wire:model.live.debounce.300ms="search" class="bg-base h-40-px w-auto" placeholder="Rechercher un fichier...">
            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
        </form>

        @if($search !== '' || $filterType !== '')
            <button type="button" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1" wire:click="resetFilters">
                <iconify-icon icon="solar:restart-outline"></iconify-icon>Réinitialiser
            </button>
        @endif
    </div>

    {{-- Tableau --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th>Aperçu</th>
                    <th wire:click="sort('file_name')" style="cursor:pointer;">
                        Nom du fichier
                        @if($sortBy === 'file_name')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:sort-from-top-to-bottom-outline' : 'solar:sort-from-bottom-to-top-outline' }}"></iconify-icon>
                        @endif
                    </th>
                    <th>Collection</th>
                    <th>Type MIME</th>
                    <th wire:click="sort('size')" style="cursor:pointer;">
                        Taille
                        @if($sortBy === 'size')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:sort-from-top-to-bottom-outline' : 'solar:sort-from-bottom-to-top-outline' }}"></iconify-icon>
                        @endif
                    </th>
                    <th wire:click="sort('created_at')" style="cursor:pointer;">
                        Date
                        @if($sortBy === 'created_at')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:sort-from-top-to-bottom-outline' : 'solar:sort-from-bottom-to-top-outline' }}"></iconify-icon>
                        @endif
                    </th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($media as $item)
                    <tr>
                        <td>
                            @if(str_starts_with($item->mime_type, 'image/'))
                                <img src="{{ $item->hasGeneratedConversion('thumbnail') ? $item->getUrl('thumbnail') : $item->getUrl() }}"
                                     alt="{{ $item->file_name }}"
                                     style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-neutral-100 rounded"
                                     style="width:56px;height:56px;">
                                    <iconify-icon icon="solar:document-outline" style="font-size:24px;" class="text-secondary-light"></iconify-icon>
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ $item->getUrl() }}" target="_blank" class="text-primary-600 fw-medium">
                                {{ $item->file_name }}
                            </a>
                        </td>
                        <td>
                            <span class="bg-neutral-200 text-neutral-600 border border-neutral-400 px-24 py-4 radius-4 fw-medium text-sm">{{ $item->collection_name }}</span>
                        </td>
                        <td>
                            <span class="text-sm text-secondary-light">{{ $item->mime_type }}</span>
                        </td>
                        <td class="text-sm">
                            @if($item->size >= 1048576)
                                {{ number_format($item->size / 1048576, 2) }} MB
                            @else
                                {{ number_format($item->size / 1024, 1) }} KB
                            @endif
                        </td>
                        <td class="text-sm text-secondary-light">{{ $item->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <button wire:click="deleteMedia({{ $item->id }})" wire:confirm="Supprimer ce fichier définitivement ?" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                        <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary-light py-20">
                            <iconify-icon icon="solar:gallery-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                            Aucun média dans la bibliothèque
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($media->hasPages())
        <div class="mt-20">
            {{ $media->links() }}
        </div>
    @endif

</div>
