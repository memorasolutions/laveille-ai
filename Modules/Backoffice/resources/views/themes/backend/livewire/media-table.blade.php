<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
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
            <h6 class="fw-medium mb-0">{{ __('Uploader un fichier') }}</h6>
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
                <p class="fw-medium mb-1">{{ __('Glisser-déposer un fichier ici') }}</p>
                <p class="small text-muted mb-0">{{ __('ou cliquer pour parcourir') }}</p>
                <p class="small text-muted mt-1 mb-0">{{ __('Images, PDF, Word, Excel, CSV, vidéo. Max 10 Mo.') }}</p>

                <div wire:loading wire:target="file" class="mt-3">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        {{ __('Chargement du fichier...') }}
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
                    {{ __('Uploader') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <select wire:model.live="filterType" class="form-select form-select-sm w-auto" aria-label="Filtrer par type">
            <option value="">{{ __('Tous les types') }}</option>
            <option value="image">{{ __('Images') }}</option>
            <option value="document">{{ __('Documents') }}</option>
            <option value="video">{{ __('Vidéos') }}</option>
        </select>
        <select wire:model.live="filterFolder" class="form-select form-select-sm w-auto" aria-label="Filtrer par dossier">
            <option value="">{{ __('Tous les dossiers') }}</option>
            @foreach($folders as $folder)
                <option value="{{ $folder }}">{{ $folder }}</option>
            @endforeach
        </select>
        <div class="input-group input-group-sm w-auto">
            <span class="input-group-text bg-white border-end-0">
                <i data-lucide="search" class="icon-sm text-muted"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm border-start-0"
                   placeholder="{{ __('Rechercher un fichier...') }}"
                   aria-label="Rechercher">
        </div>
        @if($search !== '' || $filterType !== '' || $filterFolder !== '')
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th class="fw-medium">{{ __('Aperçu') }}</th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('file_name')">
                        {{ __('Nom du fichier') }}
                        @if($sortBy === 'file_name')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium">{{ __('Collection') }}</th>
                    <th class="fw-medium">{{ __('Type MIME') }}</th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('size')">
                        {{ __('Taille') }}
                        @if($sortBy === 'size')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('created_at')">
                        {{ __('Date') }}
                        @if($sortBy === 'created_at')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium text-center">{{ __('Actions') }}</th>
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
                            @if($item->getCustomProperty('alt_text'))
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" title="Alt: {{ $item->getCustomProperty('alt_text') }}">ALT</span>
                            @endif
                            @if($item->getCustomProperty('folder'))
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1">
                                    <i data-lucide="folder" class="icon-sm me-1" style="width:12px;height:12px;"></i>{{ $item->getCustomProperty('folder') }}
                                </span>
                            @endif
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
                                    <button wire:click="editMedia({{ $item->id }})"
                                            class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="pencil" class="icon-sm"></i> {{ __('Métadonnées') }}
                                    </button>
                                    @if(str_starts_with($item->mime_type, 'image/'))
                                        <button @click="open=false; window.dispatchEvent(new CustomEvent('open-image-editor', {detail:{id:{{ $item->id }},url:'{{ $item->getUrl() }}',cropUrl:'{{ route('admin.media-api.crop', $item->id) }}'}}))"
                                                class="dropdown-item d-flex align-items-center gap-2">
                                            <i data-lucide="crop" class="icon-sm"></i> {{ __('Recadrer') }}
                                        </button>
                                    @endif
                                    <button wire:click="deleteMedia({{ $item->id }})"
                                            wire:confirm="{{ __('Supprimer ce fichier définitivement ?') }}"
                                            class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                        <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-center text-muted">
                            <i data-lucide="image" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                            {{ __('Aucun média dans la bibliothèque') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($media->hasPages())
        <div class="mt-3">{{ $media->links() }}</div>
    @endif

    {{-- Modal édition métadonnées --}}
    @if($editingMediaId)
        <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="z-index:1050;background:rgba(0,0,0,.5);">
            <div class="card shadow-lg" style="width:100%;max-width:520px;">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 fw-semibold">{{ __('Modifier les métadonnées') }}</h6>
                    <button type="button" wire:click="cancelEdit" class="btn-close btn-close-sm" aria-label="Fermer"></button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="editTitle">{{ __('Titre') }}</label>
                        <input type="text" id="editTitle" class="form-control" wire:model="editTitle" placeholder="{{ __('Titre du média') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editAltText">{{ __('Texte alternatif') }}</label>
                        <input type="text" id="editAltText" class="form-control" wire:model="editAltText" placeholder="{{ __('Description de l\'image') }}">
                        <div class="form-text">{{ __('Décrit l\'image pour l\'accessibilité et le SEO.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editCaption">{{ __('Légende') }}</label>
                        <input type="text" id="editCaption" class="form-control" wire:model="editCaption" placeholder="{{ __('Légende visible sous l\'image') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editFolder">{{ __('Dossier') }}</label>
                        <input type="text" id="editFolder" class="form-control" wire:model="editFolder" placeholder="{{ __('Ex: Photos, Logos, Documents...') }}" list="folderSuggestions">
                        <datalist id="folderSuggestions">
                            @foreach($folders as $folder)
                                <option value="{{ $folder }}">
                            @endforeach
                        </datalist>
                        <div class="form-text">{{ __('Classez vos médias par dossier pour mieux les organiser.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editDescription">{{ __('Description') }}</label>
                        <textarea id="editDescription" class="form-control" rows="3" wire:model="editDescription" placeholder="{{ __('Description détaillée (usage interne)') }}"></textarea>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2 py-3">
                    <button type="button" class="btn btn-sm btn-light" wire:click="cancelEdit" wire:loading.attr="disabled">{{ __('Annuler') }}</button>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2" wire:click="updateMedia" wire:loading.attr="disabled">
                        <span wire:loading wire:target="updateMedia"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                        <span wire:loading.remove wire:target="updateMedia"><i data-lucide="check" class="icon-sm"></i></span>
                        {{ __('Enregistrer') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Image Editor (Cropper.js) --}}
    @if(class_exists(\Modules\Media\Providers\MediaServiceProvider::class))
        @include('media::components.image-editor')
    @endif
</div>
