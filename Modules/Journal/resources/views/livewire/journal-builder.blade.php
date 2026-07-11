<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Glisser-déposer via @alpinejs/sort (même pattern que Modules/Academy/course-editor) :
     la SÉCURITÉ reste serveur, reorderBlocks() ré-autorise et vérifie que l'ensemble d'IDs
     reçu du client est une permutation exacte de l'ensemble attendu avant d'écrire. --}}
@assets
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3.x.x/dist/cdn.min.js"></script>
    <script>
        window.journalDomOrder = function (container) {
            return Array.from(container.querySelectorAll(':scope > [data-sort-id]'))
                .map(function (el) { return parseInt(el.getAttribute('data-sort-id'), 10); })
                .filter(function (id) { return Number.isInteger(id) && id > 0; });
        };
    </script>
@endassets

{{-- Éditeur riche du panneau "+ Texte" (x-editor::tiptap-light) : son script (resources/js/
     tiptap-frontend.js) est chargé ICI, au niveau racine du composant Livewire, plutôt que dans
     le composant Blade imbriqué lui-même, pour être présent dès le rendu INITIAL de la page
     (comme le plugin @alpinejs/sort ci-dessus) — AVANT que le bouton "+ Texte" ne soit jamais
     cliqué. Le panneau texte n'apparaît que plus tard via un morph Livewire déclenché par ce
     clic ; si le script n'était chargé qu'à ce moment-là (cas du @assets interne au composant
     imbriqué, qui suffit pour Directory/Authors où il est présent dès le rendu initial), Alpine
     scanne et évalue le x-data="tiptapEditor(...)" du panneau AVANT que le module (chargé en
     <script type="module">, donc asynchrone) n'ait fini de s'exécuter et d'enregistrer
     Alpine.data('tiptapEditor', ...) — Alpine ne réévalue jamais un x-data en échec après coup.
     En le chargeant ici dès l'ouverture de l'éditeur, l'enregistrement est garanti terminé bien
     avant le premier clic possible sur "+ Texte". --}}
@assets
    @vite('resources/js/tiptap-frontend.js')
@endassets

<div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">

    {{-- Réglages du journal --}}
    <div class="border rounded p-3 mb-4" style="background:var(--sys-surface-subtle,#F9FAFB);">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="journal-title" class="form-label fw-semibold">Titre du journal</label>
                <input type="text" id="journal-title" class="form-control" wire:model="title" maxlength="255">
                @error('title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label for="journal-template" class="form-label fw-semibold">Gabarit de mise en page</label>
                <select id="journal-template" class="form-select" wire:model="template">
                    @foreach ($templates as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-primary" wire:click="updateSettings">Enregistrer les réglages</button>
            <button type="button" class="btn btn-sm {{ $isPublished ? 'btn-outline-secondary' : 'btn-success' }}" wire:click="togglePublished">
                {{ $isPublished ? 'Repasser en brouillon privé' : 'Publier ce journal' }}
            </button>
            <span class="badge {{ $isPublished ? 'text-bg-success' : 'text-bg-secondary' }}">
                {{ $isPublished ? 'Publié' : 'Brouillon privé' }}
            </span>
        </div>
    </div>

    {{-- Liste des blocs (réordonnable) --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="h5 mb-0" style="font-family: var(--f-heading);">Contenu du journal</h2>
    </div>

    <div x-sort="$wire.reorderBlocks(window.journalDomOrder($el))" class="d-flex flex-column gap-2 mb-3">
        @forelse ($blocks as $block)
            <div x-sort:item="{{ $block->id }}" data-sort-id="{{ $block->id }}" class="border rounded p-3 d-flex align-items-start gap-3" style="background:#fff;">
                <div class="d-flex flex-column gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="moveBlockUp({{ $block->id }})" aria-label="Monter ce bloc">↑</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="moveBlockDown({{ $block->id }})" aria-label="Descendre ce bloc">↓</button>
                </div>

                <div class="flex-grow-1" style="min-width:0;">
                    @switch($block->type)
                        @case('text')
                            <span class="badge text-bg-light mb-1">Texte</span>
                            <div class="small text-truncate" style="max-height:3em;overflow:hidden;">{!! \Illuminate\Support\Str::limit(strip_tags($block->payload['html'] ?? ''), 160) !!}</div>
                            @break
                        @case('image')
                            <span class="badge text-bg-light mb-1">Image</span>
                            <img src="{{ $block->payload['url'] ?? '' }}" alt="" style="max-height:90px;border-radius:6px;">
                            @break
                        @case('video')
                            <span class="badge text-bg-light mb-1">Vidéo YouTube</span>
                            <div class="small text-muted">{{ $block->payload['embed_url'] ?? '' }}</div>
                            @break
                        @default
                            <span class="badge text-bg-light mb-1">{{ ucfirst($block->type) }}</span>
                            <div class="fw-semibold">{{ $block->payload['title'] ?? '' }}</div>
                            <div class="small text-muted">{{ $block->payload['excerpt'] ?? '' }}</div>
                    @endswitch
                </div>

                <div class="d-flex flex-column gap-1">
                    @if ($block->type === 'text')
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="editTextBlock({{ $block->id }})">Éditer</button>
                    @endif
                    {{-- Confirmation inline à 2 temps (jamais de popup native confirm()), même pattern que Modules/Academy/CourseCategoryManager --}}
                    @if ($confirmingRemoveBlockId === $block->id)
                        <span class="small text-danger">Retirer ?</span>
                        <button type="button" class="btn btn-sm btn-danger" wire:click="removeBlock({{ $block->id }})">Oui, retirer</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelRemoveBlock">Annuler</button>
                    @else
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="confirmRemoveBlock({{ $block->id }})">Retirer</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted small mb-0">Ce journal est vide pour l'instant. Ajoutez un bloc ci-dessous.</p>
        @endforelse
    </div>

    {{-- Barre d'ajout de blocs --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openPanel('text')">+ Texte</button>
        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openPanel('image')">+ Image</button>
        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openPanel('video')">+ Vidéo YouTube</button>
    </div>

    @if ($activePanel === 'text')
        {{-- x-editor::tiptap-light : version front-end légère (icônes SVG intégrées, aucune
             dépendance Lucide), déjà utilisée sur les pages publiques (Directory, Authors) —
             contrairement à x-editor::tiptap (lourd) qui suppose le layout admin backend.
             Le pont vers Livewire écoute le vrai évènement natif "input" déjà émis par
             resources/js/tiptap-frontend.js sur le hidden input (pas de wire:model direct
             possible : le nom du champ est fixé par le composant partagé). --}}
        <div class="border rounded p-3 mb-3" wire:key="panel-text" x-on:input="if ($event.target.name === 'journalBlockHtml') $wire.receiveTiptapContent($event.target.value)">
            <label class="form-label fw-semibold">Texte du bloc</label>
            <x-editor::tiptap-light name="journalBlockHtml" :value="$textBlockHtml" placeholder="Écrivez le contenu de ce bloc..." />
            @error('textBlockHtml') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-primary" wire:click="saveTextBlock">Enregistrer le bloc</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closePanel">Annuler</button>
            </div>
        </div>
    @endif

    @if ($activePanel === 'image')
        <div class="border rounded p-3 mb-3" wire:key="panel-image">
            <label for="journal-image-file" class="form-label fw-semibold">Image (max 8 Mo)</label>
            <input type="file" id="journal-image-file" class="form-control" wire:model="imageFile" accept="image/*">
            @error('imageFile') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <div class="form-check mt-2">
                <input type="checkbox" id="journal-image-rights" class="form-check-input" wire:model="imageRightsConfirmed">
                <label for="journal-image-rights" class="form-check-label small">Je confirme détenir les droits sur cette image ou qu'elle est libre de droits.</label>
            </div>
            @error('imageRightsConfirmed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-primary" wire:click="saveImageBlock" wire:loading.attr="disabled">Ajouter l'image</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closePanel">Annuler</button>
            </div>
        </div>
    @endif

    @if ($activePanel === 'video')
        <div class="border rounded p-3 mb-3" wire:key="panel-video">
            <label for="journal-video-url" class="form-label fw-semibold">Lien YouTube</label>
            <input type="url" id="journal-video-url" class="form-control" wire:model="videoUrl" placeholder="https://www.youtube.com/watch?v=...">
            @error('videoUrl') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-primary" wire:click="saveVideoBlock">Ajouter la vidéo</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closePanel">Annuler</button>
            </div>
        </div>
    @endif
</div>
