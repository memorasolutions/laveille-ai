<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php($course = $this->course)

{{-- ─────────────────────────── Glisser-déposer (Alpine « sort ») ───────────────────────────
     Plugin officiel @alpinejs/sort chargé en CDN, comme le plugin « intersect » du thème : il
     s'attache à l'instance Alpine fournie par Livewire 4 via l'évènement alpine:init (pas de
     double-chargement d'Alpine, pas de rebuild Vite). Le helper academyDomOrder lit l'ordre
     COURANT du DOM (attributs data-sort-id) au moment du drop et renvoie la liste d'ids ordonnée
     à la méthode Livewire de réordonnancement. La SÉCURITÉ reste serveur : l'ordre client n'est
     qu'une suggestion ; reorder*() ré-autorise et valide l'appartenance des ids (anti-IDOR). --}}
@once
    @push('styles')
        <style>
            [x-cloak]{display:none !important;}
            /* Aperçu markdown dans l'éditeur (mêmes bases que le lecteur public). */
            .academy-richtext { line-height: 1.7; color: #374151; }
            .academy-richtext > :first-child { margin-top: 0; }
            .academy-richtext h1, .academy-richtext h2, .academy-richtext h3, .academy-richtext h4 {
                font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); line-height: 1.3; margin: 1.2em 0 0.5em;
            }
            .academy-richtext h1 { font-size: 1.4rem; }
            .academy-richtext h2 { font-size: 1.2rem; }
            .academy-richtext h3 { font-size: 1.05rem; }
            .academy-richtext p { margin: 0 0 0.9em; }
            .academy-richtext ul, .academy-richtext ol { margin: 0 0 0.9em; padding-left: 1.4rem; }
            .academy-richtext li { margin-bottom: 0.3em; }
            .academy-richtext a { color: var(--sys-action-primary, #064E5A); text-decoration: underline; }
            .academy-richtext strong { font-weight: 700; color: var(--sys-text-default, #1A1D23); }
            .academy-richtext em { font-style: italic; }
            .academy-richtext code { font-family: ui-monospace, Menlo, monospace; font-size: 0.9em; background: #F3F4F6; padding: 0.1em 0.35em; border-radius: 4px; }
        </style>
    @endpush
@endonce

{{-- Scripts via @assets (Livewire v4) : injectés tôt (avant le boot d'Alpine par Livewire),
     ce qui garantit que le listener alpine:init est enregistré À TEMPS - contrairement à
     @push('scripts') rendu en bas (alpine:init déjà émis → plugin sort + Alpine.data jamais
     enregistrés). @assets est dédupliqué automatiquement par Livewire (une seule injection). --}}
@assets
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3.x.x/dist/cdn.min.js"></script>
        <script>
            // Lit l'ordre courant des éléments triables d'un conteneur (par leur data-sort-id)
            // et renvoie un tableau d'identifiants entiers, dans l'ordre d'affichage du DOM.
            window.academyDomOrder = function (container) {
                return Array.from(container.querySelectorAll(':scope > [data-sort-id]'))
                    .map(function (el) { return parseInt(el.getAttribute('data-sort-id'), 10); })
                    .filter(function (id) { return Number.isInteger(id) && id > 0; });
            };

            // Mini-éditeur markdown (aide de saisie). La <textarea> ciblée par son id
            // reste la SOURCE DE VÉRITÉ : on n'insère que de la syntaxe markdown et on
            // émet un évènement « input » pour que Livewire (wire:model) capte la valeur.
            // L'aperçu est rendu CÔTÉ SERVEUR (previewRichText) → jamais de divergence.
            document.addEventListener('alpine:init', function () {
                window.Alpine.data('academyMarkdownEditor', function (textareaId) {
                    return {
                        showPreview: false,
                        loading: false,
                        previewHtml: '',
                        ta() { return document.getElementById(textareaId); },
                        // Notifie Livewire de la nouvelle valeur (déclenche wire:model).
                        notify(el) { el.dispatchEvent(new Event('input', { bubbles: true })); },
                        wrap(before, after, placeholder) {
                            const el = this.ta(); if (!el) return;
                            const s = el.selectionStart, e = el.selectionEnd;
                            const sel = el.value.slice(s, e) || placeholder;
                            el.value = el.value.slice(0, s) + before + sel + after + el.value.slice(e);
                            const pos = s + before.length;
                            el.focus(); el.setSelectionRange(pos, pos + sel.length);
                            this.notify(el);
                        },
                        prefixLine(prefix) {
                            const el = this.ta(); if (!el) return;
                            const s = el.selectionStart;
                            const lineStart = el.value.lastIndexOf('\n', s - 1) + 1;
                            el.value = el.value.slice(0, lineStart) + prefix + el.value.slice(lineStart);
                            const pos = s + prefix.length;
                            el.focus(); el.setSelectionRange(pos, pos);
                            this.notify(el);
                        },
                        insertLink() {
                            const el = this.ta(); if (!el) return;
                            const s = el.selectionStart, e = el.selectionEnd;
                            const sel = el.value.slice(s, e) || 'texte du lien';
                            const md = '[' + sel + '](https://)';
                            el.value = el.value.slice(0, s) + md + el.value.slice(e);
                            // Place le curseur dans l'URL (entre les parenthèses).
                            const pos = s + sel.length + 3;
                            el.focus(); el.setSelectionRange(pos + 8, pos + 8);
                            this.notify(el);
                        },
                        async togglePreview() {
                            if (this.showPreview) { this.showPreview = false; return; }
                            const el = this.ta();
                            this.showPreview = true; this.loading = true;
                            try { this.previewHtml = await this.$wire.previewRichText(el ? el.value : ''); }
                            catch (err) { this.previewHtml = ''; }
                            this.loading = false;
                        },
                    };
                });
            });
        </script>
@endassets

<div style="display: flex; flex-direction: column; gap: 28px;">

    {{-- ───────────────────────── Indicateur « enregistré » (signal global discret) ─────────────────────────
         Piloté par Alpine : « Enregistrement… » pendant une requête Livewire en cours
         (wire:loading), puis « ✓ Enregistré » ~2,5 s à la réception de l'évènement
         Livewire academy-saved (émis par flashSaved côté serveur), puis fondu de sortie.
         a11y : role=status + aria-live=polite (annonce non intrusive). --}}
    <div
        x-data="{ saved: false, timer: null, show(){ this.saved = true; clearTimeout(this.timer); this.timer = setTimeout(() => this.saved = false, 2500); } }"
        x-on:academy-saved.window="show()"
        role="status" aria-live="polite"
        style="position: sticky; top: 8px; z-index: 5; display: flex; justify-content: flex-end; min-height: 0; pointer-events: none;">
        {{-- Pendant la requête Livewire (autosave / clic Enregistrer) --}}
        <span wire:loading
              style="font-size: 0.78rem; font-weight: 600; padding: 4px 12px; border-radius: 999px; background: #F1F5F9; color: var(--sys-text-muted, #475569); box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            Enregistrement…
        </span>
        {{-- Une fois la requête terminée et l'évènement reçu --}}
        <span wire:loading.remove x-show="saved" x-transition.opacity.duration.400ms x-cloak
              style="font-size: 0.78rem; font-weight: 600; padding: 4px 12px; border-radius: 999px; background: #E0F2F1; color: var(--sys-action-primary, #064E5A); box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            ✓ Enregistré
        </span>
    </div>

    {{-- ───────────────────────── Message de succès ───────────────────────── --}}
    @if (session('academy_editor_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; font-weight: 600;">
            {{ session('academy_editor_status') }}
        </div>
    @endif

    {{-- ───────────────────────── Métadonnées + publication ───────────────────────── --}}
    <section aria-labelledby="editor-meta"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="margin-bottom: 18px;">
            <h2 id="editor-meta" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0; font-size: 1.25rem;">
                Métadonnées du cours
            </h2>
            @php($isPublished = $course->status === 'published')
            <span role="status"
                  style="font-size: 0.78rem; font-weight: 700; padding: 4px 14px; border-radius: 999px;
                         background: {{ $isPublished ? '#DCFCE7' : '#FEF3C7' }};
                         color: {{ $isPublished ? '#166534' : '#92400E' }};">
                <span aria-hidden="true">●</span> {{ $isPublished ? 'Publié' : 'Brouillon' }}
            </span>
        </div>

        <form wire:submit="save" style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label for="meta-title" style="display: block; font-weight: 600; margin-bottom: 6px;">Titre</label>
                <input id="meta-title" type="text" wire:model.live.blur="title"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="meta-subtitle" style="display: block; font-weight: 600; margin-bottom: 6px;">Sous-titre</label>
                <input id="meta-subtitle" type="text" wire:model.live.blur="subtitle"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('subtitle') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="meta-summary" style="display: block; font-weight: 600; margin-bottom: 6px;">Résumé</label>
                <textarea id="meta-summary" wire:model.live.blur="summary" rows="3"
                          style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);"></textarea>
                @error('summary') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            {{-- ── Image de couverture (téléversement Spatie, collection « cover ») ── --}}
            @can('update', $course)
                <div style="border: 1px dashed #CBD5E1; border-radius: var(--sys-radius-md, 0.75rem); padding: 14px 16px;">
                    <span style="display: block; font-weight: 600; margin-bottom: 8px;">Image de couverture</span>

                    @php($coverUrl = $course->coverUrl())
                    @if ($coverUrl)
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $coverUrl }}" alt="Aperçu de l'image de couverture du cours « {{ $course->title }} »"
                                 style="max-width: 240px; width: 100%; height: auto; border-radius: var(--sys-radius-md, 0.5rem); border: 1px solid #E5E7EB;">
                        </div>
                    @endif

                    {{-- Aperçu instantané du fichier choisi (avant enregistrement). isPreviewable() évite
                         l'exception sur un fichier non image (sécurité : on ne prévisualise que des images). --}}
                    @if ($cover && method_exists($cover, 'isPreviewable') && $cover->isPreviewable())
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $cover->temporaryUrl() }}" alt="Aperçu de la nouvelle image de couverture"
                                 style="max-width: 240px; width: 100%; height: auto; border-radius: var(--sys-radius-md, 0.5rem); border: 1px solid var(--sys-action-primary, #064E5A);">
                        </div>
                    @endif

                    <label for="meta-cover" style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px;">
                        Choisir une image (JPG, PNG ou WebP, 4 Mo max)
                    </label>
                    <input id="meta-cover" type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp"
                           aria-describedby="meta-cover-help"
                           style="width: 100%; padding: 8px 0;">
                    <p id="meta-cover-help" style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                        L'image s'affiche sur la fiche du cours et dans le catalogue.
                    </p>
                    @error('cover') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror

                    <div wire:loading wire:target="cover" role="status" aria-live="polite"
                         style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin-top: 6px;">
                        Téléversement de l'image en cours…
                    </div>

                    <div class="d-flex flex-wrap gap-2" style="margin-top: 10px;">
                        <x-core::button type="button" wire:click="saveCover" wire:loading.attr="disabled" wire:target="saveCover,cover" variant="secondary" size="sm">
                            <span wire:loading.remove wire:target="saveCover">Enregistrer l'image</span>
                            <span wire:loading wire:target="saveCover">Enregistrement…</span>
                        </x-core::button>
                        @if ($coverUrl)
                            <x-core::button type="button" wire:click="removeCover" wire:loading.attr="disabled" wire:target="removeCover" variant="ghost" size="sm">
                                Retirer l'image
                            </x-core::button>
                        @endif
                    </div>
                </div>
            @endcan

            <div class="d-flex flex-wrap gap-3">
                <div style="flex: 1 1 200px;">
                    <label for="meta-level" style="display: block; font-weight: 600; margin-bottom: 6px;">Niveau</label>
                    <select id="meta-level" wire:model.live.blur="level"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="intro">Débutant</option>
                        <option value="inter">Intermédiaire</option>
                        <option value="avance">Avancé</option>
                    </select>
                    @error('level') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div style="flex: 1 1 200px;">
                    <label for="meta-language" style="display: block; font-weight: 600; margin-bottom: 6px;">Langue</label>
                    <input id="meta-language" type="text" wire:model.live.blur="language" maxlength="10"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('language') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                @if(config('academy.course_categories_enabled', false))
                    <div style="flex: 1 1 200px;">
                        <label for="meta-category" style="display: block; font-weight: 600; margin-bottom: 6px;">Catégorie</label>
                        <select id="meta-category" wire:model.live.blur="category_id"
                                style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            <option value="">Aucune catégorie</option>
                            @foreach($this->courseCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-3">
                <div style="flex: 1 1 200px;">
                    <label for="meta-visibility" style="display: block; font-weight: 600; margin-bottom: 6px;">Visibilité</label>
                    <select id="meta-visibility" wire:model.live.blur="visibility"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="public">Publique</option>
                        <option value="unlisted">Non répertoriée</option>
                        <option value="private">Privée</option>
                    </select>
                    @error('visibility') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div style="flex: 1 1 200px;">
                    <label for="meta-access" style="display: block; font-weight: 600; margin-bottom: 6px;">Accès</label>
                    <select id="meta-access" wire:model.live="access_type"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="free">Gratuit</option>
                        <option value="paid_one_time">Payant (achat unique)</option>
                        <option value="paid_subscription">Payant (abonnement)</option>
                    </select>
                    @error('access_type') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            @if (in_array($access_type, ['paid_one_time', 'paid_subscription'], true))
                <div style="flex: 1 1 200px;">
                    <label for="meta-price" style="display: block; font-weight: 600; margin-bottom: 6px;">Prix (en cents)</label>
                    <input id="meta-price" type="number" min="0" wire:model.live.blur="price_cents"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('price_cents') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            @endif

            {{-- Modèle réutilisable (C3) : gâté update. Une fois coché, le cours apparaît
                 dans la section « Modèles » du tableau de bord et peut servir de base à
                 une duplication (« Utiliser ce modèle »). --}}
            @can('update', $course)
                <div style="flex: 1 1 100%;">
                    <label for="meta-template" class="d-flex align-items-center gap-2" style="font-weight: 600; cursor: pointer;">
                        <input id="meta-template" type="checkbox" wire:model.live="is_template" style="width: 18px; height: 18px;">
                        Utiliser ce cours comme modèle réutilisable
                    </label>
                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                        Les formateurs pourront créer une copie de ce cours d'un seul clic depuis leur espace.
                    </p>
                    @error('is_template') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            @endcan

            {{-- Prérequis du cours (C4) : gâté manageStructure. L'apprenant devra avoir
                 complété ces cours (100 %) avant de pouvoir s'inscrire / y accéder.
                 Le cours courant est exclu de la liste (pas d'auto-référence possible). --}}
            @can('manageStructure', $course)
                <div style="flex: 1 1 100%; border-top: 1px solid #F1F5F9; padding-top: 12px;">
                    <p style="font-weight: 600; margin: 0 0 4px;">Prérequis (cours à compléter d'abord)</p>
                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                        Les apprenants devront avoir terminé les cours cochés avant de pouvoir s'inscrire à celui-ci.
                    </p>
                    @if ($this->availableCourses->isEmpty())
                        <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                            Aucun autre cours disponible comme prérequis pour le moment.
                        </p>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 220px; overflow-y: auto;">
                            @foreach ($this->availableCourses as $candidate)
                                <label class="d-flex align-items-center gap-2"
                                       style="font-size: 0.88rem; cursor: pointer;">
                                    <input type="checkbox"
                                           wire:model.live="prerequisiteIds"
                                           wire:change="savePrerequisites"
                                           value="{{ $candidate->id }}"
                                           style="width: 18px; height: 18px;">
                                    {{ $candidate->title }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('prerequisiteIds') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            @endcan

            {{-- Certificat du cours (E3) : gâté manageStructure. Personnalise le certificat
                 remis à l'apprenant qui termine le cours (titre, message, signature, couleur
                 d'accent). Tout champ vide → on retombe sur les défauts (aucune régression).
                 La sécurité reste 100 % serveur (saveCertificate : resolveCourse + authorize). --}}
            @can('manageStructure', $course)
                <div style="flex: 1 1 100%; border-top: 1px solid #F1F5F9; padding-top: 12px;">
                    <p style="font-weight: 600; margin: 0 0 4px;">Certificat de réussite</p>
                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 10px;">
                        Personnalisez le certificat remis aux apprenants qui terminent ce cours. Laissez un champ vide pour utiliser la valeur par défaut.
                    </p>

                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <div style="flex: 1 1 240px;">
                            <label for="cert-title" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Titre du certificat</label>
                            <input id="cert-title" type="text" wire:model.live.blur="certificate_title" maxlength="120"
                                   placeholder="Certificat de complétion"
                                   style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                            @error('certificate_title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div style="flex: 0 1 200px;">
                            <label for="cert-accent" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Couleur d'accent</label>
                            <div class="d-flex align-items-center gap-2">
                                <input id="cert-accent" type="text" wire:model.live.blur="certificate_accent_color" maxlength="7"
                                       placeholder="#064E5A" aria-describedby="cert-accent-help"
                                       style="flex: 1; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px; font-family: ui-monospace, monospace;">
                            </div>
                            <span id="cert-accent-help" style="display: block; font-size: 0.74rem; color: var(--sys-text-muted, #6B7280); margin-top: 4px;">Code hexadécimal (ex. #064E5A).</span>
                            @error('certificate_accent_color') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div style="flex: 1 1 100%;">
                            <label for="cert-signature" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Signature (nom affiché)</label>
                            <input id="cert-signature" type="text" wire:model.live.blur="certificate_signature_name" maxlength="120"
                                   placeholder="Stéphane Lapointe, La veille"
                                   style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                            @error('certificate_signature_name') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div style="flex: 1 1 100%;">
                            <label for="cert-message" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Message / mention (sous le nom du cours)</label>
                            <textarea id="cert-message" wire:model.live.blur="certificate_message" rows="3" maxlength="2000"
                                      placeholder="Décerné en reconnaissance de la réussite complète du parcours. (markdown simple accepté)"
                                      style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;"></textarea>
                            @error('certificate_message') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Phase 2 câblage - sélecteur owner-scopé, gâté par le drapeau, `null` = comportement actuel inchangé --}}
                        @if(config('academy.diploma_editor_enabled', false))
                            <div style="flex: 1 1 100%;">
                                <label for="cert-diploma-template" style="font-weight: 600; margin-bottom: 6px; font-size: 0.85rem; display: block;">
                                    Gabarit de diplôme (Phase 2 - optionnel)
                                </label>
                                <select id="cert-diploma-template"
                                        wire:model.live="diploma_template_id"
                                        style="width: 100%; border: 1px solid #D1D5DB; border-radius: 8px; padding: 8px 10px; font-size: 0.875rem;">
                                    <option value="">Rendu par défaut (aucun gabarit personnalisé)</option>
                                    @foreach($this->myDiplomaTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                                @if(\Illuminate\Support\Facades\Route::has('academy.diplomas.templates.editor'))
                                    <a href="{{ route('academy.diplomas.templates.editor') }}"
                                       target="_blank"
                                       rel="noopener"
                                       style="font-size: 0.74rem; color: var(--sys-text-muted, #6B7280); text-decoration: none; margin-top: 4px; display: inline-block;">
                                        Gérer mes gabarits de diplôme →
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 10px;">
                        <x-core::button type="button" wire:click="saveCertificate" wire:loading.attr="disabled" wire:target="saveCertificate" variant="secondary" size="sm">
                            <span wire:loading.remove wire:target="saveCertificate">Enregistrer le certificat</span>
                            <span wire:loading wire:target="saveCertificate">Enregistrement…</span>
                        </x-core::button>

                        @if($this->sampleCertificateSlug)
                            <x-core::button :href="route('academy.certificates.show', $this->sampleCertificateSlug)" target="_blank" rel="noopener" variant="ghost" size="sm">
                                👁️ Prévisualiser un certificat d'exemple
                            </x-core::button>
                        @endif
                    </div>
                </div>
            @endcan

            {{-- Tuteur IA - fenêtre d'accès + quota (recommandation veille juillet 2026).
                 Gâté manageStructure ET drapeau academy.ai_tutor_access_control_enabled
                 (absent en OFF : aucune régression du Tuteur IA existant). Modifier ces
                 réglages n'affecte JAMAIS un apprenant déjà inscrit (grant déjà figé) -
                 uniquement les NOUVELLES inscriptions. Sécurité 100 % serveur
                 (saveAiTutorAccess : resolveCourse + authorize). --}}
            @if(config('academy.ai_tutor_access_control_enabled'))
                @can('manageStructure', $course)
                    <div style="flex: 1 1 100%; border-top: 1px solid #F1F5F9; padding-top: 12px;">
                        <p style="font-weight: 600; margin: 0 0 4px;">🤖 Accès au tuteur IA</p>
                        <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 10px;">
                            Limitez (optionnel) la durée pendant laquelle un apprenant peut poser des questions au tuteur IA et/ou le nombre de questions par mois. Le contenu du cours reste TOUJOURS accessible, même si le tuteur se termine. Modifier ces réglages n'affecte que les <strong>nouvelles</strong> inscriptions, jamais celles déjà en cours.
                        </p>

                        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                            <div style="flex: 1 1 240px;">
                                <label for="tutor-window-type" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Type de fenêtre</label>
                                <select id="tutor-window-type" wire:model.live="ai_tutor_window_type"
                                        style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                                    <option value="none">Aucune (accès illimité)</option>
                                    <option value="relative_to_enrollment">X jours après l'inscription</option>
                                    <option value="relative_to_course_launch">X jours après le lancement du cours</option>
                                    <option value="fixed_date">Date fixe</option>
                                </select>
                                @error('ai_tutor_window_type') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                            </div>

                            @if(in_array($ai_tutor_window_type, ['relative_to_enrollment', 'relative_to_course_launch']))
                                <div style="flex: 0 1 160px;">
                                    <label for="tutor-window-days" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Nombre de jours</label>
                                    <input id="tutor-window-days" type="number" min="1" max="3650" wire:model.live.blur="ai_tutor_window_days"
                                           style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                                    @error('ai_tutor_window_days') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if($ai_tutor_window_type === 'fixed_date')
                                <div style="flex: 0 1 200px;">
                                    <label for="tutor-fixed-expiry" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Date d'expiration</label>
                                    <input id="tutor-fixed-expiry" type="date" wire:model.live.blur="ai_tutor_fixed_expiry_at"
                                           style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                                    @error('ai_tutor_fixed_expiry_at') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div style="flex: 0 1 200px;">
                                <label for="tutor-monthly-quota" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">Quota mensuel de questions</label>
                                <input id="tutor-monthly-quota" type="number" min="1" max="100000" wire:model.live.blur="ai_tutor_monthly_quota"
                                       placeholder="Illimité"
                                       style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                                @error('ai_tutor_monthly_quota') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                            </div>

                            <div style="flex: 0 1 200px;">
                                <label for="tutor-reminder-days" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">1er rappel avant expiration (jours)</label>
                                <input id="tutor-reminder-days" type="number" min="1" max="60" wire:model.live.blur="ai_tutor_reminder_days_before"
                                       style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                                <span style="display: block; font-size: 0.74rem; color: var(--sys-text-muted, #6B7280); margin-top: 4px;">Un second rappel est toujours envoyé à J-1.</span>
                                @error('ai_tutor_reminder_days_before') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 10px;">
                            <x-core::button type="button" wire:click="saveAiTutorAccess" wire:loading.attr="disabled" wire:target="saveAiTutorAccess" variant="secondary" size="sm">
                                <span wire:loading.remove wire:target="saveAiTutorAccess">Enregistrer l'accès au tuteur IA</span>
                                <span wire:loading wire:target="saveAiTutorAccess">Enregistrement…</span>
                            </x-core::button>
                        </div>
                    </div>
                @endcan
            @endif

            {{-- Achèvement du cours (course completion configurable) : gâté manageStructure.
                 Choisit QUAND le cours est considéré complété (déblocage certificat + badges).
                 Défaut « all_required » = comportement actuel. Sécurité 100 % serveur
                 (saveCompletion : resolveCourse + authorize + re-filtrage anti-IDOR). --}}
            @can('manageStructure', $course)
                <div style="flex: 1 1 100%; border-top: 1px solid #F1F5F9; padding-top: 12px;">
                    <p style="font-weight: 600; margin: 0 0 4px;">Achèvement du cours</p>
                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 10px;">
                        Définissez la condition qui déclenche la complétion du cours (certificat et badges). Par défaut, l'apprenant doit compléter toutes les leçons requises.
                    </p>

                    <fieldset style="border: 0; margin: 0; padding: 0;">
                        <legend class="visually-hidden">Critère d'achèvement</legend>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem;">
                                <input type="radio" wire:model.live="completion_type" value="all_required" style="margin-top: 3px; min-width: 18px; min-height: 18px;">
                                <span>Toutes les leçons requises (par défaut)</span>
                            </label>

                            <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem;">
                                <input type="radio" wire:model.live="completion_type" value="percent" style="margin-top: 3px; min-width: 18px; min-height: 18px;">
                                <span>Un pourcentage des leçons requises</span>
                            </label>

                            <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem;">
                                <input type="radio" wire:model.live="completion_type" value="min_grade" style="margin-top: 3px; min-width: 18px; min-height: 18px;">
                                <span>Une note finale minimale au carnet</span>
                            </label>

                            <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem;">
                                <input type="radio" wire:model.live="completion_type" value="selected_activities" style="margin-top: 3px; min-width: 18px; min-height: 18px;">
                                <span>Des activités précises à compléter</span>
                            </label>
                        </div>

                        @error('completion_type') <span style="display:block; color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror

                        {{-- Seuil X pour percent / min_grade --}}
                        @if(in_array($completion_type, ['percent', 'min_grade'], true))
                            <div style="margin-top: 10px; max-width: 260px;">
                                <label for="completion-value" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.85rem;">
                                    {{ $completion_type === 'min_grade' ? 'Note finale minimale (%)' : 'Pourcentage requis (%)' }}
                                </label>
                                <input id="completion-value" type="number" min="1" max="100" step="1"
                                       wire:model.live.blur="completion_value"
                                       style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 8px;">
                                @error('completion_value') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        {{-- Activités désignées pour selected_activities --}}
                        @if($completion_type === 'selected_activities')
                            <div style="margin-top: 10px;">
                                <p style="font-weight: 600; margin: 0 0 6px; font-size: 0.85rem;">Activités à compléter</p>
                                @if(count($this->completionItems) === 0)
                                    <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                        Ajoutez d'abord des leçons et des activités ci-dessous, puis revenez désigner celles qui valident le cours.
                                    </p>
                                @else
                                    <div style="display: flex; flex-direction: column; gap: 6px; max-height: 220px; overflow: auto; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px;">
                                        @foreach($this->completionItems as $ci)
                                            <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.86rem;">
                                                <input type="checkbox" value="{{ $ci['id'] }}" wire:model.live="completion_selected"
                                                       style="margin-top: 3px; min-width: 18px; min-height: 18px;">
                                                <span>{{ $ci['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                @error('completion_selected') <span style="display:block; color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </fieldset>

                    <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 10px;">
                        <x-core::button type="button" wire:click="saveCompletion" wire:loading.attr="disabled" wire:target="saveCompletion" variant="secondary" size="sm">
                            <span wire:loading.remove wire:target="saveCompletion">Enregistrer l'achèvement</span>
                            <span wire:loading wire:target="saveCompletion">Enregistrement…</span>
                        </x-core::button>
                    </div>
                </div>
            @endcan

            <p style="font-size: 0.74rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                Les modifications sont enregistrées automatiquement dès que vous quittez un champ.
            </p>

            <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 6px;">
                @can('update', $course)
                    <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                @endcan

                {{-- Prévisualiser « en tant qu'étudiant » : ouvre la 1re leçon (si elle existe)
                     sinon la fiche du cours, en mode preview, dans un NOUVEL onglet. La gate
                     serveur (CourseController/LessonController) exige can('update') : l'URL
                     ?preview=1 seule ne donne jamais accès à un cours brouillon. --}}
                @can('update', $course)
                    @php($previewUrl = $this->firstLessonId
                        ? route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $this->firstLessonId]) . '?preview=1'
                        : route('academy.courses.show', $course->slug) . '?preview=1')
                    <x-core::button :href="$previewUrl" target="_blank" rel="noopener" variant="ghost" size="sm">
                        👁️ Prévisualiser en tant qu'étudiant
                    </x-core::button>
                @endcan

                @can('publish', $course)
                    <x-core::button type="button" wire:click="togglePublish" variant="secondary" size="sm">
                        {{ $isPublished ? 'Dépublier le cours' : 'Publier le cours' }}
                    </x-core::button>
                @endcan

                {{-- Import Moodle (.mbz) : crée un NOUVEAU cours à part (pas une fusion dans
                     CE cours) - lien vers l'écran d'import dédié, gâté academy.moodle_import_enabled
                     ET create(). Voir CourseMoodleImport. --}}
                @if(config('academy.moodle_import_enabled'))
                    @can('create', \Modules\Academy\Models\Course::class)
                        <x-core::button :href="route('academy.courses.moodle-import')" variant="ghost" size="sm">
                            Importer un cours Moodle (.mbz)
                        </x-core::button>
                    @endcan
                @endif
            </div>

            @can('publish', $course)
                <p style="font-size: 0.74rem; color: var(--sys-text-muted, #6B7280); margin: 6px 0 0;">
                    @if ($isPublished)
                        Ce cours est publié et visible du public. Dépubliez-le pour le repasser en brouillon.
                    @else
                        Un cours en brouillon n'est pas visible du public. Publiez-le quand il est prêt.
                    @endif
                </p>
            @endcan
        </form>
    </section>

    {{-- ───────────────────────── Chapitres + leçons ───────────────────────── --}}
    <section aria-labelledby="editor-structure"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <h2 id="editor-structure" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 18px; font-size: 1.25rem;">
            Contenu de la formation
        </h2>

        {{-- ── Onboarding 1er cours (cours sans aucun chapitre) ──
             Encart d'accueil RÉDUCTIBLE, mémorisé par cours via localStorage : une fois
             fermé, il ne réapparaît pas pour ce cours. Ne bloque jamais l'usage (le
             formulaire d'ajout reste juste en dessous). a11y : role=region + aria-label. --}}
        @if ($course->chapters->isEmpty())
            <div
                x-data="{
                    key: 'academy_onboarding_{{ $course->id }}',
                    open: true,
                    init() { this.open = localStorage.getItem(this.key) !== 'dismissed'; },
                    dismiss() { this.open = false; localStorage.setItem(this.key, 'dismissed'); }
                }"
                x-show="open" x-cloak
                role="region" aria-label="Premiers pas pour bâtir votre formation"
                style="border: 1px solid #BAE6FD; background: #F0F9FF; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px; margin-bottom: 20px;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <p style="font-weight: 700; color: #0C4A6E; margin: 0 0 8px; font-size: 0.95rem;">
                            🚀 Bienvenue ! Voici comment bâtir votre formation en 3 étapes.
                        </p>
                        <ol style="margin: 0; padding-left: 1.25rem; font-size: 0.86rem; color: #0C4A6E; line-height: 1.7;">
                            <li><strong>Ajoutez un chapitre</strong> pour regrouper vos leçons.</li>
                            <li><strong>Ajoutez des leçons</strong> dans chaque chapitre.</li>
                            <li><strong>Ajoutez du contenu</strong> (vidéo, document ou quiz), puis publiez.</li>
                        </ol>
                    </div>
                    <button type="button" @click="dismiss()" aria-label="Fermer l'aide de démarrage" title="Fermer"
                            style="flex-shrink: 0; border: none; background: none; cursor: pointer; color: #0C4A6E; font-size: 1.1rem; line-height: 1; min-width: 28px; min-height: 28px; padding: 4px;">✕</button>
                </div>
            </div>
        @endif

        {{-- Ajouter un chapitre --}}
        @can('manageStructure', $course)
            <form wire:submit="addChapter"
                  style="border: 1px dashed #CBD5E1; border-radius: var(--sys-radius-md, 0.75rem); padding: 14px 16px; margin-bottom: 22px; display: flex; flex-direction: column; gap: 10px;">
                <label for="new-chapter-title" style="font-weight: 600;">Nouveau chapitre</label>
                <input id="new-chapter-title" type="text" wire:model="newChapterTitle" placeholder="Titre du chapitre"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('newChapterTitle') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                <input type="text" wire:model="newChapterSummary" placeholder="Résumé (facultatif)" aria-label="Résumé du chapitre"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                <div>
                    <x-core::button type="submit" variant="primary" size="sm">Ajouter le chapitre</x-core::button>
                </div>
            </form>
        @endcan

        {{-- Conteneur glisser-déposer des CHAPITRES. Le drag est un PLUS : les boutons
             ↑/↓ restent le mécanisme accessible au clavier. Au drop, on lit l'ordre du
             DOM et on le persiste instantanément côté serveur (reorderChapters), qui
             RÉ-AUTORISE et VALIDE l'appartenance des ids (anti-IDOR). --}}
        @can('manageStructure', $course)
            <div
                x-data
                x-sort="$wire.reorderChapters(window.academyDomOrder($el))"
                x-sort:config="{ handle: '[data-sort-handle]', animation: 150 }"
            >
        @endcan
        @forelse ($course->chapters as $chapter)
            <article
                @can('manageStructure', $course) x-sort:item="{{ $chapter->id }}" data-sort-id="{{ $chapter->id }}" @endcan
                wire:key="chapter-{{ $chapter->id }}"
                style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px; margin-bottom: 16px;">
                {{-- En-tête de chapitre --}}
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2" style="margin-bottom: 12px;">
                    @can('manageStructure', $course)
                        <span data-sort-handle role="button" tabindex="-1" aria-hidden="true"
                              title="Glisser pour réordonner le chapitre"
                              style="cursor: grab; user-select: none; color: var(--sys-text-muted, #9CA3AF); font-size: 1.1rem; line-height: 1; padding: 4px 2px; align-self: center;">⠿</span>
                    @endcan
                    {{-- Titre du chapitre : clic-pour-éditer (Alpine). Enregistre sur blur ET sur Entrée
                         (appelle la méthode Livewire updateChapter, gardes serveur inchangées) ; Échap annule.
                         Le résumé reste éditable via le panneau « Modifier » plus bas. --}}
                    @can('manageStructure', $course)
                        <div style="flex: 1 1 240px;"
                             x-data="{ editing: false, value: @js($chapter->title), original: @js($chapter->title),
                                       commit() { this.editing = false; const v = this.value.trim();
                                                  if (v !== '' && v !== this.original) { $wire.updateChapter({{ $chapter->id }}, v, @js($chapter->summary)); this.original = v; }
                                                  else { this.value = this.original; } },
                                       cancel() { this.value = this.original; this.editing = false; } }">
                            <div x-show="!editing" class="d-flex align-items-center gap-2">
                                <h3 @click="editing = true" style="font-family: var(--f-heading); font-size: 1.05rem; color: var(--sys-text-default, #1A1D23); margin: 0; cursor: text;">
                                    <span x-text="value"></span>
                                </h3>
                                <button type="button" @click="editing = true" aria-label="Renommer le chapitre « {{ $chapter->title }} »" title="Renommer le chapitre"
                                        style="border: none; background: none; cursor: pointer; color: var(--sys-action-primary, #064E5A); font-size: 0.85rem; min-width: 24px; min-height: 24px; line-height: 1; padding: 4px;">✏️</button>
                            </div>
                            <input x-show="editing" x-cloak type="text" x-model="value"
                                   x-ref="chapterTitleInput"
                                   x-init="$watch('editing', v => { if (v) $nextTick(() => $refs.chapterTitleInput.focus()) })"
                                   @blur="commit()" @keydown.enter.prevent="commit()" @keydown.escape.prevent="cancel()"
                                   aria-label="Titre du chapitre"
                                   style="width: 100%; padding: 8px 12px; min-height: 24px; border: 1px solid var(--sys-action-primary, #064E5A); border-radius: var(--sys-radius-md, 0.5rem); font-family: var(--f-heading); font-size: 1.05rem;">
                            @if ($chapter->summary)
                                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">{{ $chapter->summary }}</p>
                            @endif
                        </div>
                    @else
                        <div style="flex: 1 1 240px;">
                            <h3 style="font-family: var(--f-heading); font-size: 1.05rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 4px;">
                                {{ $chapter->title }}
                            </h3>
                            @if ($chapter->summary)
                                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">{{ $chapter->summary }}</p>
                            @endif
                        </div>
                    @endcan

                    @can('manageStructure', $course)
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            {{-- Monter/Descendre : action PRINCIPALE (réorganisation fréquente pendant l'édition),
                                 reste un bouton flèche TOUJOURS VISIBLE, jamais caché dans le menu kebab. --}}
                            <x-core::button type="button" wire:click="moveChapterUp({{ $chapter->id }})" variant="ghost" size="sm" title="Monter le chapitre" aria-label="Monter le chapitre">↑</x-core::button>
                            <x-core::button type="button" wire:click="moveChapterDown({{ $chapter->id }})" variant="ghost" size="sm" title="Descendre le chapitre" aria-label="Descendre le chapitre">↓</x-core::button>
                            @if ($confirmingChapterDeletion === $chapter->id)
                                <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Supprimer ?</span>
                                <x-core::button type="button" wire:click="deleteChapter({{ $chapter->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                <x-core::button type="button" wire:click="cancelChapterDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                            @else
                                {{-- Action secondaire/rare : dans le menu kebab. La méthode Livewire
                                     confirmChapterDeletion gère elle-même son état de confirmation 2-temps
                                     (bloc @if ci-dessus), donc wireClick seul, sans wireConfirm. --}}
                                @include('core::components.admin-action-menu', ['actions' => [
                                    ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmChapterDeletion({$chapter->id})", 'danger' => true],
                                ]])
                            @endif
                        </div>
                    @endcan
                </div>

                {{-- Édition inline du titre/résumé du chapitre --}}
                @can('manageStructure', $course)
                    <details style="margin-bottom: 12px;">
                        <summary style="cursor: pointer; font-size: 0.85rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Modifier le titre ou le résumé</summary>
                        <form wire:submit="updateChapter({{ $chapter->id }}, $event.target.title.value, $event.target.summary.value)"
                              style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                            <input type="text" name="title" value="{{ $chapter->title }}" aria-label="Titre du chapitre"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            <input type="text" name="summary" value="{{ $chapter->summary }}" aria-label="Résumé du chapitre"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            <div><x-core::button type="submit" variant="secondary" size="sm">Mettre à jour</x-core::button></div>
                        </form>
                    </details>
                @endcan

                {{-- Leçons du chapitre --}}
                @if ($chapter->lessons->isNotEmpty())
                    <ul class="list-unstyled"
                        @can('manageStructure', $course)
                            x-data
                            x-sort="$wire.reorderLessons({{ $chapter->id }}, window.academyDomOrder($el))"
                            x-sort:config="{ handle: '[data-sort-handle]', animation: 150 }"
                        @endcan
                        style="margin: 0 0 12px; display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($chapter->lessons as $lesson)
                            <li
                                @can('manageStructure', $course) x-sort:item="{{ $lesson->id }}" data-sort-id="{{ $lesson->id }}" @endcan
                                wire:key="lesson-{{ $lesson->id }}"
                                style="border: 1px solid #F1F5F9; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px 14px;">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    @can('manageStructure', $course)
                                        <span data-sort-handle role="button" tabindex="-1" aria-hidden="true"
                                              title="Glisser pour réordonner la leçon"
                                              style="cursor: grab; user-select: none; color: var(--sys-text-muted, #9CA3AF); font-size: 1rem; line-height: 1; padding: 2px; align-self: center;">⠿</span>
                                    @endcan
                                    {{-- Titre de la leçon : clic-pour-éditer (Alpine). Enregistre sur blur ET
                                         sur Entrée (méthode Livewire updateLesson, gardes serveur inchangées) ; Échap annule. --}}
                                    @can('manageStructure', $course)
                                        <div style="flex: 1 1 220px;"
                                             x-data="{ editing: false, value: @js($lesson->title), original: @js($lesson->title),
                                                       commit() { this.editing = false; const v = this.value.trim();
                                                                  if (v !== '' && v !== this.original) { $wire.updateLesson({{ $lesson->id }}, v, @js($lesson->summary), @js($lesson->estimated_minutes)); this.original = v; }
                                                                  else { this.value = this.original; } },
                                                       cancel() { this.value = this.original; this.editing = false; } }">
                                            <div x-show="!editing" class="d-flex align-items-center gap-2">
                                                <strong @click="editing = true" style="color: var(--sys-text-default, #1A1D23); cursor: text;" x-text="value"></strong>
                                                <button type="button" @click="editing = true" aria-label="Renommer la leçon « {{ $lesson->title }} »" title="Renommer la leçon"
                                                        style="border: none; background: none; cursor: pointer; color: var(--sys-action-primary, #064E5A); font-size: 0.8rem; min-width: 24px; min-height: 24px; line-height: 1; padding: 4px;">✏️</button>
                                            </div>
                                            <input x-show="editing" x-cloak type="text" x-model="value"
                                                   x-ref="lessonTitleInput"
                                                   x-init="$watch('editing', v => { if (v) $nextTick(() => $refs.lessonTitleInput.focus()) })"
                                                   @blur="commit()" @keydown.enter.prevent="commit()" @keydown.escape.prevent="cancel()"
                                                   aria-label="Titre de la leçon"
                                                   style="width: 100%; padding: 7px 12px; min-height: 24px; border: 1px solid var(--sys-action-primary, #064E5A); border-radius: var(--sys-radius-md, 0.5rem); font-weight: 700;">
                                            <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                                                {{ $lesson->lesson_items_count }} {{ $lesson->lesson_items_count > 1 ? 'éléments' : 'élément' }}
                                                @if ($lesson->estimated_minutes) · {{ $lesson->estimated_minutes }} min @endif
                                            </p>
                                        </div>
                                    @else
                                        <div style="flex: 1 1 220px;">
                                            <strong style="color: var(--sys-text-default, #1A1D23);">{{ $lesson->title }}</strong>
                                            <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                                                {{ $lesson->lesson_items_count }} {{ $lesson->lesson_items_count > 1 ? 'éléments' : 'élément' }}
                                                @if ($lesson->estimated_minutes) · {{ $lesson->estimated_minutes }} min @endif
                                            </p>
                                        </div>
                                    @endcan

                                    @can('manageStructure', $course)
                                        <div class="d-flex flex-wrap align-items-center gap-1">
                                            {{-- Monter/Descendre : action PRINCIPALE (réorganisation fréquente pendant l'édition),
                                                 reste un bouton flèche TOUJOURS VISIBLE, jamais caché dans le menu kebab. --}}
                                            <x-core::button type="button" wire:click="moveLessonUp({{ $lesson->id }})" variant="ghost" size="sm" title="Monter la leçon" aria-label="Monter la leçon">↑</x-core::button>
                                            <x-core::button type="button" wire:click="moveLessonDown({{ $lesson->id }})" variant="ghost" size="sm" title="Descendre la leçon" aria-label="Descendre la leçon">↓</x-core::button>
                                            @if ($confirmingLessonDeletion === $lesson->id)
                                                <span style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">Supprimer ?</span>
                                                <x-core::button type="button" wire:click="deleteLesson({{ $lesson->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelLessonDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                                            @else
                                                {{-- Action secondaire/rare : dans le menu kebab. Le crayon inline (renommer)
                                                     reste tel quel juste au-dessus, ce n'est pas ce mécanisme qu'on consolide. --}}
                                                @include('core::components.admin-action-menu', ['actions' => [
                                                    ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmLessonDeletion({$lesson->id})", 'danger' => true],
                                                ]])
                                            @endif
                                        </div>
                                    @endcan
                                </div>

                                @can('manageStructure', $course)
                                    <details style="margin-top: 8px;">
                                        <summary style="cursor: pointer; font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Modifier cette leçon</summary>
                                        <form wire:submit="updateLesson({{ $lesson->id }}, $event.target.title.value, $event.target.summary.value, $event.target.estimated_minutes.value, $event.target.drip_days.value)"
                                              style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                            <input type="text" name="title" value="{{ $lesson->title }}" aria-label="Titre de la leçon"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <input type="text" name="summary" value="{{ $lesson->summary }}" aria-label="Résumé de la leçon"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <input type="number" min="1" name="estimated_minutes" value="{{ $lesson->estimated_minutes }}" aria-label="Durée estimée en minutes"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            {{-- C4 : libération progressive. 0 ou vide = disponible dès l'inscription. --}}
                                            <label for="drip-{{ $lesson->id }}" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 2px 0 -4px;">
                                                Disponible N jours après l'inscription (0 ou vide = immédiat)
                                            </label>
                                            <input id="drip-{{ $lesson->id }}" type="number" min="0" max="365" name="drip_days" value="{{ $lesson->drip_days }}" aria-label="Jours avant disponibilité de la leçon"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <div><x-core::button type="submit" variant="secondary" size="sm">Enregistrer la leçon</x-core::button></div>
                                        </form>
                                    </details>
                                @endcan

                                {{-- ───────── Contenu de la leçon : éléments (vidéo / document / quiz) ───────── --}}
                                @can('manageStructure', $course)
                                    <div style="margin-top: 12px; border-top: 1px solid #F1F5F9; padding-top: 12px;">
                                        <p style="font-weight: 600; font-size: 0.82rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                            Contenu de la leçon
                                        </p>

                                        @if ($lesson->lessonItems->isNotEmpty())
                                            <ul class="list-unstyled"
                                                x-data
                                                x-sort="$wire.reorderItems({{ $lesson->id }}, window.academyDomOrder($el))"
                                                x-sort:config="{ handle: '[data-sort-handle]', animation: 150 }"
                                                style="margin: 0 0 10px; display: flex; flex-direction: column; gap: 8px;">
                                                @foreach ($lesson->lessonItems as $item)
                                                    @php($typeLabel = ['video' => 'Vidéo', 'document' => 'Document', 'quiz' => 'Quiz', 'choice' => 'Sondage', 'feedback' => 'Rétroaction', 'forum' => 'Forum', 'wiki' => 'Wiki', 'database' => 'Base de données', 'workshop' => 'Atelier', 'h5p' => 'H5P', 'scorm' => 'SCORM'][$item->type] ?? $item->type)
                                                    <li x-sort:item="{{ $item->id }}" data-sort-id="{{ $item->id }}"
                                                        wire:key="item-{{ $item->id }}"
                                                        style="border: 1px solid #F1F5F9; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px 12px;">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                            <span data-sort-handle role="button" tabindex="-1" aria-hidden="true"
                                                                  title="Glisser pour réordonner l'élément"
                                                                  style="cursor: grab; user-select: none; color: var(--sys-text-muted, #9CA3AF); font-size: 0.95rem; line-height: 1; padding: 2px; align-self: center;">⠿</span>
                                                            <div style="flex: 1 1 200px;">
                                                                <span style="display: inline-block; font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #E0F2F1; color: #064E5A; margin-bottom: 4px;">
                                                                    {{ $typeLabel }}
                                                                </span>
                                                                @if ($item->is_required)
                                                                    <span style="display: inline-block; font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #FEF3C7; color: #92400E; margin-bottom: 4px;">Obligatoire</span>
                                                                @endif
                                                                <strong style="display: block; color: var(--sys-text-default, #1A1D23); font-size: 0.9rem;">{{ $item->title }}</strong>
                                                                @if ($item->estimated_minutes)
                                                                    <span style="font-size: 0.75rem; color: var(--sys-text-muted, #6B7280);">{{ $item->estimated_minutes }} min</span>
                                                                @endif
                                                            </div>

                                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                                {{-- Monter/Descendre : action PRINCIPALE (réorganisation fréquente pendant l'édition),
                                                                     reste un bouton flèche TOUJOURS VISIBLE, jamais caché dans le menu kebab. --}}
                                                                <x-core::button type="button" wire:click="moveItemUp({{ $item->id }})" variant="ghost" size="sm" title="Monter l'élément" aria-label="Monter l'élément">↑</x-core::button>
                                                                <x-core::button type="button" wire:click="moveItemDown({{ $item->id }})" variant="ghost" size="sm" title="Descendre l'élément" aria-label="Descendre l'élément">↓</x-core::button>
                                                                @if ($confirmingItemDeletion === $item->id)
                                                                    <span style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">Supprimer ?</span>
                                                                    <x-core::button type="button" wire:click="deleteItem({{ $item->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                                    <x-core::button type="button" wire:click="cancelItemDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                                                                @else
                                                                    {{-- Actions secondaires/rares : toggle Obligatoire/Facultatif + Supprimer, dans le menu kebab. --}}
                                                                    @include('core::components.admin-action-menu', ['actions' => [
                                                                        ['label' => $item->is_required ? 'Rendre facultatif' : 'Rendre obligatoire', 'icon' => $item->is_required ? 'circle' : 'check-circle', 'wireClick' => "toggleRequired({$item->id})"],
                                                                        ['divider' => true],
                                                                        ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmItemDeletion({$item->id})", 'danger' => true],
                                                                    ]])
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Modifier cet élément (formulaire par type).
                                                             wire:ignore.self : Livewire ne re-morphe PAS l'attribut natif `open` du
                                                             <details> à chaque action (ajout d'un champ de schéma / critère), il
                                                             met seulement à jour les enfants -> le panneau reste ouvert et le champ
                                                             ajouté reste visible (correctif SIM BUG-3). wire:key stabilise le morph. --}}
                                                        <details style="margin-top: 8px;" wire:key="item-edit-{{ $item->id }}" wire:ignore.self>
                                                            <summary style="cursor: pointer; font-size: 0.78rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Modifier cet élément</summary>
                                                            {{-- Valeur affichée pour l'URL vidéo : champ canonique player_url, repli sur l'ancien payload['embed']. --}}
                                                            @php($videoUrlValue = $item->payload['player_url'] ?? ($item->payload['embed'] ?? ''))
                                                            @php($posterValue = $item->payload['poster'] ?? '')
                                                            @php($durationMin = isset($item->payload['duration_seconds']) ? (int) ceil(((int) $item->payload['duration_seconds']) / 60) : '')
                                                            {{-- ── FEEDBACK : éditeur dédié (répéteur de questions). Le formulaire
                                                                 générique ($event.target) ne sait pas sérialiser un répéteur dynamique ;
                                                                 on passe par un tampon Livewire (editFeedback.{item}) + actions dédiées. --}}
                                                            @if ($item->type === 'feedback')
                                                                @if (! isset($editFeedback[$item->id]))
                                                                    <div style="margin-top: 10px;">
                                                                        <x-core::button type="button" wire:click="loadFeedbackEditor({{ $item->id }})" variant="secondary" size="sm">Modifier la rétroaction</x-core::button>
                                                                    </div>
                                                                @else
                                                                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="fbedit-title-{{ $item->id }}">Titre</label>
                                                                        <input id="fbedit-title-{{ $item->id }}" type="text" wire:model="editFeedback.{{ $item->id }}.title" aria-label="Titre du sondage"
                                                                               style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="fbedit-intro-{{ $item->id }}">Introduction (facultative)</label>
                                                                        <textarea id="fbedit-intro-{{ $item->id }}" wire:model="editFeedback.{{ $item->id }}.intro" rows="2" aria-label="Introduction du sondage"
                                                                                  style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                                                        <span style="font-size: 0.78rem; font-weight: 700;">Questions</span>
                                                                        @error('feedback_questions') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                                        @forelse ($editFeedback[$item->id]['questions'] ?? [] as $qi => $q)
                                                                            <div wire:key="fbedit-{{ $item->id }}-{{ $qi }}" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="fbedit-type-{{ $item->id }}-{{ $qi }}">Type</label>
                                                                                <select id="fbedit-type-{{ $item->id }}-{{ $qi }}" wire:model="editFeedback.{{ $item->id }}.questions.{{ $qi }}.type" aria-label="Type de question"
                                                                                        style="width: 100%; padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                    <option value="rating">Échelle (note)</option>
                                                                                    <option value="choice">Choix</option>
                                                                                    <option value="text">Texte libre</option>
                                                                                </select>
                                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="fbedit-label-{{ $item->id }}-{{ $qi }}">Énoncé</label>
                                                                                <input id="fbedit-label-{{ $item->id }}-{{ $qi }}" type="text" wire:model="editFeedback.{{ $item->id }}.questions.{{ $qi }}.label" placeholder="Votre question"
                                                                                       style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                @if (($q['type'] ?? '') === 'rating')
                                                                                    <label style="font-size: 0.74rem; font-weight: 600;" for="fbedit-scale-{{ $item->id }}-{{ $qi }}">Échelle 1 à</label>
                                                                                    <input id="fbedit-scale-{{ $item->id }}-{{ $qi }}" type="number" min="2" max="10" wire:model="editFeedback.{{ $item->id }}.questions.{{ $qi }}.scale"
                                                                                           style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                @elseif (($q['type'] ?? '') === 'choice')
                                                                                    <label style="font-size: 0.74rem; font-weight: 600;" for="fbedit-opts-{{ $item->id }}-{{ $qi }}">Options (une par ligne, au moins 2)</label>
                                                                                    <textarea id="fbedit-opts-{{ $item->id }}-{{ $qi }}" wire:model="editFeedback.{{ $item->id }}.questions.{{ $qi }}.options" rows="3"
                                                                                              style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                                                @endif
                                                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 600;">
                                                                                    <input type="checkbox" wire:model="editFeedback.{{ $item->id }}.questions.{{ $qi }}.required" style="width: 22px; height: 22px; flex: 0 0 auto;">
                                                                                    <span>Réponse obligatoire</span>
                                                                                </label>
                                                                                <div><x-core::button type="button" wire:click="removeFeedbackQuestion({{ $item->id }}, {{ $qi }})" variant="ghost" size="sm">Retirer cette question</x-core::button></div>
                                                                            </div>
                                                                        @empty
                                                                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune question. Ajoutez-en au moins une.</p>
                                                                        @endforelse
                                                                        <div><x-core::button type="button" wire:click="addFeedbackQuestion({{ $item->id }})" variant="ghost" size="sm">+ Ajouter une question</x-core::button></div>

                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;">
                                                                            <input type="checkbox" wire:model="editFeedback.{{ $item->id }}.anonymous" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Réponses anonymes (ne pas lier la réponse à l'étudiant)</span>
                                                                        </label>

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="fbedit-completion-{{ $item->id }}">Critère d'achèvement</label>
                                                                        <select id="fbedit-completion-{{ $item->id }}" wire:model="editFeedback.{{ $item->id }}.completion" aria-label="Critère d'achèvement de l'élément"
                                                                                style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                            <option value="submit">En répondant au sondage</option>
                                                                            <option value="view">Automatique à la consultation</option>
                                                                            <option value="manual">Manuel (l'étudiant le marque terminé)</option>
                                                                        </select>

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="fbedit-min-{{ $item->id }}">Durée estimée (min, facultatif)</label>
                                                                        <input id="fbedit-min-{{ $item->id }}" type="number" min="1" wire:model="editFeedback.{{ $item->id }}.estimated_minutes" aria-label="Durée estimée en minutes"
                                                                               style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <x-core::button type="button" wire:click="saveFeedback({{ $item->id }})" variant="secondary" size="sm">Enregistrer le sondage</x-core::button>
                                                                            <x-core::button type="button" wire:click="cancelFeedbackEditor({{ $item->id }})" variant="ghost" size="sm">Annuler</x-core::button>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @elseif ($item->type === 'database')
                                                                {{-- ── F20 - BASE DE DONNÉES : éditeur dédié du SCHÉMA (répéteur de champs).
                                                                     Comme la rétroaction, on passe par un tampon Livewire (editDatabase.{item})
                                                                     + actions dédiées : le schéma ne se sérialise pas via $event.target. --}}
                                                                @if (! isset($editDatabase[$item->id]))
                                                                    <div style="margin-top: 10px;">
                                                                        <x-core::button type="button" wire:click="loadDatabaseEditor({{ $item->id }})" variant="secondary" size="sm">Modifier la base de données (schéma)</x-core::button>
                                                                    </div>
                                                                @else
                                                                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="dbedit-title-{{ $item->id }}">Titre</label>
                                                                        <input id="dbedit-title-{{ $item->id }}" type="text" wire:model="editDatabase.{{ $item->id }}.title" aria-label="Titre de la base de données"
                                                                               style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="dbedit-intro-{{ $item->id }}">Introduction (facultative)</label>
                                                                        <textarea id="dbedit-intro-{{ $item->id }}" wire:model="editDatabase.{{ $item->id }}.intro" rows="2" aria-label="Introduction de la base de données"
                                                                                  style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                                                        <span style="font-size: 0.78rem; font-weight: 700;">Champs du schéma</span>
                                                                        @forelse ($editDatabase[$item->id]['fields'] ?? [] as $fi => $f)
                                                                            <div wire:key="dbedit-{{ $item->id }}-{{ $fi }}" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="dbedit-label-{{ $item->id }}-{{ $fi }}">Libellé du champ</label>
                                                                                <input id="dbedit-label-{{ $item->id }}-{{ $fi }}" type="text" wire:model="editDatabase.{{ $item->id }}.fields.{{ $fi }}.label" placeholder="Ex. : Titre de l'outil"
                                                                                       style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="dbedit-type-{{ $item->id }}-{{ $fi }}">Type</label>
                                                                                <select id="dbedit-type-{{ $item->id }}-{{ $fi }}" wire:model.live="editDatabase.{{ $item->id }}.fields.{{ $fi }}.type" aria-label="Type du champ"
                                                                                        style="width: 100%; padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                    <option value="text">Texte court</option>
                                                                                    <option value="textarea">Texte long</option>
                                                                                    <option value="number">Nombre</option>
                                                                                    <option value="url">Lien (URL)</option>
                                                                                    <option value="select">Liste de choix</option>
                                                                                </select>
                                                                                @if (($f['type'] ?? '') === 'select')
                                                                                    <label style="font-size: 0.74rem; font-weight: 600;" for="dbedit-opts-{{ $item->id }}-{{ $fi }}">Options (une par ligne)</label>
                                                                                    <textarea id="dbedit-opts-{{ $item->id }}-{{ $fi }}" wire:model="editDatabase.{{ $item->id }}.fields.{{ $fi }}.options" rows="3"
                                                                                              style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                                                @endif
                                                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 600;">
                                                                                    <input type="checkbox" wire:model="editDatabase.{{ $item->id }}.fields.{{ $fi }}.required" style="width: 22px; height: 22px; flex: 0 0 auto;">
                                                                                    <span>Champ obligatoire</span>
                                                                                </label>
                                                                                <div><x-core::button type="button" wire:click="removeDatabaseField({{ $item->id }}, {{ $fi }})" variant="ghost" size="sm">Retirer ce champ</x-core::button></div>
                                                                            </div>
                                                                        @empty
                                                                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucun champ. Ajoutez-en pour définir la fiche que les inscrits rempliront.</p>
                                                                        @endforelse
                                                                        <div><x-core::button type="button" wire:click="addDatabaseField({{ $item->id }})" variant="ghost" size="sm">+ Ajouter un champ</x-core::button></div>

                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;">
                                                                            <input type="checkbox" wire:model="editDatabase.{{ $item->id }}.allow_student_add" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Autoriser les inscrits à ajouter une fiche (par défaut : oui)</span>
                                                                        </label>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;">
                                                                            <input type="checkbox" wire:model="editDatabase.{{ $item->id }}.require_approval" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Exiger l'approbation d'un gérant avant de publier une fiche</span>
                                                                        </label>

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="dbedit-min-{{ $item->id }}">Durée estimée (min, facultatif)</label>
                                                                        <input id="dbedit-min-{{ $item->id }}" type="number" min="1" wire:model="editDatabase.{{ $item->id }}.estimated_minutes" aria-label="Durée estimée en minutes"
                                                                               style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <x-core::button type="button" wire:click="saveDatabase({{ $item->id }})" variant="secondary" size="sm">Enregistrer la base de données</x-core::button>
                                                                            <x-core::button type="button" wire:click="cancelDatabaseEditor({{ $item->id }})" variant="ghost" size="sm">Annuler</x-core::button>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @elseif ($item->type === 'workshop')
                                                                {{-- ── F21 - ATELIER : éditeur dédié de la GRILLE (répéteur de critères) + réglages.
                                                                     Comme la base de données, on passe par un tampon Livewire (editWorkshop.{item})
                                                                     + actions dédiées : la grille ne se sérialise pas via $event.target.
                                                                     La PHASE se pilote depuis le lecteur (tableau de bord gérant), pas ici. --}}
                                                                @if (! isset($editWorkshop[$item->id]))
                                                                    <div style="margin-top: 10px;">
                                                                        <x-core::button type="button" wire:click="loadWorkshopEditor({{ $item->id }})" variant="secondary" size="sm">Modifier l'atelier (grille)</x-core::button>
                                                                    </div>
                                                                @else
                                                                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="wsedit-title-{{ $item->id }}">Titre</label>
                                                                        <input id="wsedit-title-{{ $item->id }}" type="text" wire:model="editWorkshop.{{ $item->id }}.title" aria-label="Titre de l'atelier"
                                                                               style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="wsedit-intro-{{ $item->id }}">Consigne / introduction (facultative)</label>
                                                                        <textarea id="wsedit-intro-{{ $item->id }}" wire:model="editWorkshop.{{ $item->id }}.intro" rows="2" aria-label="Introduction de l'atelier"
                                                                                  style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                                                        <span style="font-size: 0.78rem; font-weight: 700;">Grille d'évaluation (critères)</span>
                                                                        @forelse ($editWorkshop[$item->id]['criteria'] ?? [] as $ci => $c)
                                                                            <div wire:key="wsedit-{{ $item->id }}-{{ $ci }}" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="wsedit-label-{{ $item->id }}-{{ $ci }}">Libellé du critère</label>
                                                                                <input id="wsedit-label-{{ $item->id }}-{{ $ci }}" type="text" wire:model="editWorkshop.{{ $item->id }}.criteria.{{ $ci }}.label" placeholder="Ex. : Clarté de l'argumentation"
                                                                                       style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="wsedit-desc-{{ $item->id }}-{{ $ci }}">Description (facultative)</label>
                                                                                <textarea id="wsedit-desc-{{ $item->id }}-{{ $ci }}" wire:model="editWorkshop.{{ $item->id }}.criteria.{{ $ci }}.description" rows="2"
                                                                                          style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                                                    <span style="display: flex; flex-direction: column; gap: 4px;">
                                                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="wsedit-max-{{ $item->id }}-{{ $ci }}">Note max</label>
                                                                                        <input id="wsedit-max-{{ $item->id }}-{{ $ci }}" type="number" min="1" max="100" wire:model="editWorkshop.{{ $item->id }}.criteria.{{ $ci }}.max_score"
                                                                                               style="width: 90px; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                    </span>
                                                                                    <span style="display: flex; flex-direction: column; gap: 4px;">
                                                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="wsedit-weight-{{ $item->id }}-{{ $ci }}">Poids</label>
                                                                                        <input id="wsedit-weight-{{ $item->id }}-{{ $ci }}" type="number" min="0" step="0.5" wire:model="editWorkshop.{{ $item->id }}.criteria.{{ $ci }}.weight"
                                                                                               style="width: 90px; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                    </span>
                                                                                </div>
                                                                                <div><x-core::button type="button" wire:click="removeWorkshopCriterion({{ $item->id }}, {{ $ci }})" variant="ghost" size="sm">Retirer ce critère</x-core::button></div>
                                                                            </div>
                                                                        @empty
                                                                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucun critère. Ajoutez-en pour définir la grille d'évaluation par les pairs.</p>
                                                                        @endforelse
                                                                        <div><x-core::button type="button" wire:click="addWorkshopCriterion({{ $item->id }})" variant="ghost" size="sm">+ Ajouter un critère</x-core::button></div>

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="wsedit-reviews-{{ $item->id }}">Évaluations par étudiant</label>
                                                                        <input id="wsedit-reviews-{{ $item->id }}" type="number" min="1" max="10" wire:model="editWorkshop.{{ $item->id }}.reviews_per_student" aria-label="Nombre de travaux à évaluer par étudiant"
                                                                               style="width: 120px; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;">
                                                                            <input type="checkbox" wire:model="editWorkshop.{{ $item->id }}.anonymous" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Évaluation anonyme (masquer l'auteur des travaux ; par défaut : oui)</span>
                                                                        </label>

                                                                        <label style="font-size: 0.78rem; font-weight: 600;" for="wsedit-min-{{ $item->id }}">Durée estimée (min, facultatif)</label>
                                                                        <input id="wsedit-min-{{ $item->id }}" type="number" min="1" wire:model="editWorkshop.{{ $item->id }}.estimated_minutes" aria-label="Durée estimée en minutes"
                                                                               style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <x-core::button type="button" wire:click="saveWorkshop({{ $item->id }})" variant="secondary" size="sm">Enregistrer l'atelier</x-core::button>
                                                                            <x-core::button type="button" wire:click="cancelWorkshopEditor({{ $item->id }})" variant="ghost" size="sm">Annuler</x-core::button>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @else
                                                            <form wire:submit="updateItem({{ $item->id }}, '{{ $item->type }}', $event.target.title.value, $event.target.estimated_minutes.value, { player_url: $event.target.player_url ? $event.target.player_url.value : null, poster_url: $event.target.poster_url ? $event.target.poster_url.value : null, duration_minutes: $event.target.duration_minutes ? $event.target.duration_minutes.value : null, rich_text: $event.target.rich_text ? $event.target.rich_text.value : null, qt_bank_key: $event.target.qt_bank_key ? $event.target.qt_bank_key.value : null, passing_score: $event.target.passing_score ? $event.target.passing_score.value : null, attempts_allowed: $event.target.attempts_allowed ? $event.target.attempts_allowed.value : null, bank_category_id: $event.target.bank_category_id ? $event.target.bank_category_id.value : null, bank_draw_count: $event.target.bank_draw_count ? $event.target.bank_draw_count.value : null, bank_include_subcategories: $event.target.bank_include_subcategories ? $event.target.bank_include_subcategories.checked : null, grading_method: $event.target.grading_method ? $event.target.grading_method.value : null, shuffle_questions: $event.target.shuffle_questions ? $event.target.shuffle_questions.checked : null, shuffle_answers: $event.target.shuffle_answers ? $event.target.shuffle_answers.checked : null, time_limit_minutes: $event.target.time_limit_minutes ? $event.target.time_limit_minutes.value : null, kiosk_mode: $event.target.kiosk_mode ? $event.target.kiosk_mode.checked : null, review_options: $event.target.review_show_correctness ? { show_correctness: $event.target.review_show_correctness.checked, show_marks: $event.target.review_show_marks.checked, show_specific_feedback: $event.target.review_show_specific_feedback.checked, show_general_feedback: $event.target.review_show_general_feedback.checked, show_overall_feedback: $event.target.review_show_overall_feedback.checked, show_right_answer: $event.target.review_show_right_answer.checked } : null, question_behaviour: $event.target.question_behaviour ? $event.target.question_behaviour.value : null, adaptive_penalty: $event.target.adaptive_penalty ? $event.target.adaptive_penalty.value : null, adaptive_max_tries: $event.target.adaptive_max_tries ? $event.target.adaptive_max_tries.value : null, completion: $event.target.completion ? $event.target.completion.value : null, choice_question: $event.target.choice_question ? $event.target.choice_question.value : null, choice_options: $event.target.choice_options ? $event.target.choice_options.value : null, allow_multiple: $event.target.allow_multiple ? $event.target.allow_multiple.checked : null, anonymous: $event.target.anonymous ? $event.target.anonymous.checked : null, results_visibility: $event.target.results_visibility ? $event.target.results_visibility.value : null, forum_intro: $event.target.forum_intro ? $event.target.forum_intro.value : null, allow_student_topics: $event.target.allow_student_topics ? $event.target.allow_student_topics.checked : null, locked: $event.target.locked ? $event.target.locked.checked : null, wiki_intro: $event.target.wiki_intro ? $event.target.wiki_intro.value : null, allow_student_edit: $event.target.allow_student_edit ? $event.target.allow_student_edit.checked : null })"
                                                                  style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                                <label style="font-size: 0.78rem; font-weight: 600;" for="item-title-{{ $item->id }}">Titre</label>
                                                                <input id="item-title-{{ $item->id }}" type="text" name="title" value="{{ $item->title }}" aria-label="Titre de l'élément"
                                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                @if ($item->type === 'video')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-url-{{ $item->id }}">URL d'intégration ScreenPal</label>
                                                                    <input id="item-url-{{ $item->id }}" type="url" name="player_url" value="{{ $videoUrlValue }}" placeholder="https://share.screenpal.com/player/…" aria-label="URL d'intégration ScreenPal"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        La vidéo doit être non répertoriée, avec verrou de domaine activé sur ScreenPal. Utilisez l'URL d'intégration (player/…), pas le lien de partage (watch/…).
                                                                    </p>

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-poster-{{ $item->id }}">Affiche / vignette (URL externe, facultatif)</label>
                                                                    <input id="item-poster-{{ $item->id }}" type="url" name="poster_url" value="{{ $posterValue }}" placeholder="https://…" aria-label="URL de l'affiche de la vidéo"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-dur-{{ $item->id }}">Durée de la vidéo (min, facultatif)</label>
                                                                    <input id="item-dur-{{ $item->id }}" type="number" min="1" max="1440" name="duration_minutes" value="{{ $durationMin }}" placeholder="Durée de la vidéo en minutes" aria-label="Durée de la vidéo en minutes"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                @elseif ($item->type === 'document')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-doc-{{ $item->id }}">Contenu (markdown)</label>
                                                                    <x-academy::markdown-editor :textareaId="'item-doc-'.$item->id" :uid="$item->id" wire:key="md-edit-{{ $item->id }}">
                                                                        <textarea id="item-doc-{{ $item->id }}" name="rich_text" rows="5" aria-label="Contenu du document"
                                                                                  style="width: 100%; padding: 8px 12px; border: none; border-radius: 0; outline: none; resize: vertical;">{{ $item->payload['rich_text'] ?? '' }}</textarea>
                                                                    </x-academy::markdown-editor>
                                                                @elseif ($item->type === 'quiz')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-qt-{{ $item->id }}">Clé de banque QT</label>
                                                                    <input id="item-qt-{{ $item->id }}" type="text" name="qt_bank_key" value="{{ $item->payload['qt_bank_key'] ?? '' }}" placeholder="Clé de banque QT" aria-label="Clé de banque QT"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        La clé identifie la banque générale (repli). Pour tirer dans VOTRE banque de questions, choisissez plutôt une catégorie ci-dessous.
                                                                    </p>

                                                                    {{-- QB2 : tirage depuis MA banque de questions (prioritaire sur la clé QT) --}}
                                                                    @php($qb = $item->payload['question_bank'] ?? null)
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-bankcat-{{ $item->id }}">Catégorie de ma banque (facultatif)</label>
                                                                    <select id="item-bankcat-{{ $item->id }}" name="bank_category_id" aria-label="Catégorie de la banque de questions"
                                                                            style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        <option value="">- Aucune (utiliser la clé QT ci-dessus) -</option>
                                                                        @foreach ($this->bankCategories as $cat)
                                                                            @php($deepCount = (int) ($cat->deep_active_count ?? 0))
                                                                            @php($catLabel = $cat->name.' ('.$deepCount.' question'.($deepCount > 1 ? 's' : '').((int) ($cat->children_count ?? 0) > 0 ? ', sous-catégories incluses' : '').')')
                                                                            <option value="{{ $cat->id }}" @selected(($qb['category_id'] ?? null) === $cat->id)>{{ $catLabel }}</option>
                                                                        @endforeach
                                                                    </select>

                                                                    {{-- QB3 : toggle « inclure les sous-catégories » (parité Moodle, coché par défaut). --}}
                                                                    @php($includeSubs = $qb['include_subcategories'] ?? true)
                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-banksub-{{ $item->id }}">
                                                                        <input id="item-banksub-{{ $item->id }}" type="checkbox" name="bank_include_subcategories" value="1" @checked($includeSubs)
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Inclure les sous-catégories</span>
                                                                    </label>
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        Les questions des sous-catégories sont aussi tirées (comme Moodle). Décochez pour limiter à cette seule catégorie.
                                                                    </p>

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-bankdraw-{{ $item->id }}">Nombre de questions à tirer (1 à 50)</label>
                                                                    <input id="item-bankdraw-{{ $item->id }}" type="number" min="1" max="50" name="bank_draw_count" value="{{ $qb['draw_count'] ?? 5 }}" aria-label="Nombre de questions à tirer de la banque"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-pass-{{ $item->id }}">Score de réussite (%)</label>
                                                                    <input id="item-pass-{{ $item->id }}" type="number" min="0" max="100" name="passing_score" value="{{ $item->payload['passing_score'] ?? 60 }}" aria-label="Score de réussite en pourcentage"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-att-{{ $item->id }}">Tentatives autorisées (laisser vide = illimité)</label>
                                                                    <input id="item-att-{{ $item->id }}" type="number" min="1" max="99" name="attempts_allowed" value="{{ $item->payload['attempts_allowed'] ?? '' }}" placeholder="Illimité" aria-label="Nombre de tentatives autorisées"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                    {{-- V1-c : méthode de notation sur tentatives (parité Moodle, défaut « meilleure »). --}}
                                                                    @php($gradingMethod = $item->payload['grading_method'] ?? 'highest')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-grade-{{ $item->id }}">Note retenue (plusieurs tentatives)</label>
                                                                    <select id="item-grade-{{ $item->id }}" name="grading_method" aria-label="Méthode de notation sur les tentatives"
                                                                            style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        <option value="highest" @selected($gradingMethod === 'highest')>Meilleure note</option>
                                                                        <option value="average" @selected($gradingMethod === 'average')>Moyenne des tentatives</option>
                                                                        <option value="first" @selected($gradingMethod === 'first')>Première tentative</option>
                                                                        <option value="last" @selected($gradingMethod === 'last')>Dernière tentative</option>
                                                                    </select>

                                                                    {{-- V1-f : COMPORTEMENT DE QUESTION (parité Moodle). Différé = défaut
                                                                         (1 soumission, révision à la fin). Immédiat = rétroaction par
                                                                         question avant de continuer. --}}
                                                                    @php($questionBehaviour = \Modules\Academy\Services\QuizBehaviour::for($item->payload))
                                                                    {{-- C3 (audit F6) : scope Alpine partagé select↔champs adaptatifs.
                                                                         display:contents = ne perturbe pas le flex/gap du parent ; le
                                                                         pré-remplissage (option @selected) et le wire:submit (name=)
                                                                         restent intacts (x-model lit la même valeur initiale). --}}
                                                                    <div x-data="{ behaviour: @js($questionBehaviour) }" style="display: contents;">
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-behaviour-{{ $item->id }}">Rétroaction des questions</label>
                                                                    <select id="item-behaviour-{{ $item->id }}" name="question_behaviour" x-model="behaviour" aria-label="Comportement de rétroaction des questions"
                                                                            style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        <option value="deferred" @selected($questionBehaviour === 'deferred')>Différée (révision à la fin)</option>
                                                                        <option value="immediate" @selected($questionBehaviour === 'immediate')>Immédiate (par question)</option>
                                                                        <option value="adaptive" @selected($questionBehaviour === 'adaptive')>Adaptative (réessai avec pénalité)</option>
                                                                    </select>

                                                                    {{-- ADAPTATIF : pénalité par essai raté (%) + nombre d'essais maximal.
                                                                         N'a d'effet que si le mode « Adaptative » est choisi (les valeurs
                                                                         ne sont écrites au payload que dans ce cas). Pénalité saisie en %,
                                                                         convertie en fraction au build (défaut 33 % = 1/3, défaut 3 essais).
                                                                         C3 : masqués hors mode adaptatif (clarté UX ; inoffensif si envoyés). --}}
                                                                    @php($adaptivePenaltyPct = isset($item->payload['adaptive_penalty']) && is_numeric($item->payload['adaptive_penalty']) ? (int) round(((float) $item->payload['adaptive_penalty']) * 100) : 33)
                                                                    @php($adaptiveMaxTries = isset($item->payload['adaptive_max_tries']) ? max(1, min(10, (int) $item->payload['adaptive_max_tries'])) : 3)
                                                                    <div x-show="behaviour === 'adaptive'" x-cloak style="display: flex; flex-direction: column; gap: 8px;">
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-adpen-{{ $item->id }}">Pénalité adaptative par essai raté (%)</label>
                                                                    <input id="item-adpen-{{ $item->id }}" type="number" min="0" max="100" name="adaptive_penalty" value="{{ $adaptivePenaltyPct }}" aria-label="Pénalité par essai raté en pourcentage (mode adaptatif)"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        Mode adaptatif seulement : chaque essai raté retranche ce pourcentage des points de la question (défaut 33 %).
                                                                    </p>
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-adtries-{{ $item->id }}">Essais maximaux (mode adaptatif, 1 à 10)</label>
                                                                    <input id="item-adtries-{{ $item->id }}" type="number" min="1" max="10" name="adaptive_max_tries" value="{{ $adaptiveMaxTries }}" aria-label="Nombre d'essais maximal (mode adaptatif)"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    </div>
                                                                    </div>

                                                                    {{-- V1-d : MÉLANGE (questions / réponses). --}}
                                                                    @php($shuffleQ = (bool) ($item->payload['shuffle_questions'] ?? false))
                                                                    @php($shuffleA = (bool) ($item->payload['shuffle_answers'] ?? false))
                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-shufq-{{ $item->id }}">
                                                                        <input id="item-shufq-{{ $item->id }}" type="checkbox" name="shuffle_questions" value="1" @checked($shuffleQ)
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Mélanger les questions</span>
                                                                    </label>
                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-shufa-{{ $item->id }}">
                                                                        <input id="item-shufa-{{ $item->id }}" type="checkbox" name="shuffle_answers" value="1" @checked($shuffleA)
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Mélanger les réponses (choix multiples)</span>
                                                                    </label>

                                                                    {{-- V1-d : LIMITE DE TEMPS (minutes, vide = aucune). --}}
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-timelimit-{{ $item->id }}">Limite de temps (minutes, vide = aucune)</label>
                                                                    <input id="item-timelimit-{{ $item->id }}" type="number" min="1" max="240" name="time_limit_minutes" value="{{ $item->payload['time_limit_minutes'] ?? '' }}" placeholder="Aucune" aria-label="Limite de temps en minutes"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                    {{-- MODE KIOSQUE : verrouillage anti-triche des évaluations surveillées.
                                                                         Case affichée UNIQUEMENT si le drapeau global est actif (défaut off). --}}
                                                                    @if(config('academy.kiosk_mode_enabled'))
                                                                        <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-kiosk-{{ $item->id }}">
                                                                            <input id="item-kiosk-{{ $item->id }}" type="checkbox" name="kiosk_mode" value="1" @checked((bool) ($item->kiosk_mode ?? false))
                                                                                   style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>
                                                                                Mode kiosque (évaluation surveillée)
                                                                                <br>
                                                                                <small style="font-weight: 400; color: #6B7280;">
                                                                                    Plein écran forcé au démarrage, incidents consignés (sortie plein écran, changement d'onglet, outils de développement suspectés). Dissuasion et traçabilité, pas une garantie de sécurité absolue.
                                                                                </small>
                                                                            </span>
                                                                        </label>
                                                                    @endif

                                                                    {{-- V1-d : OPTIONS DE RÉVISION (ce que l'étudiant voit après soumission). --}}
                                                                    @php($ro = \Modules\Academy\Services\QuizReviewOptions::normalize($item->payload['review_options'] ?? null))
                                                                    <fieldset style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px 12px; margin: 0;">
                                                                        <legend style="font-size: 0.78rem; font-weight: 700; padding: 0 4px; width: auto;">Options de révision</legend>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;" for="item-ro-correct-{{ $item->id }}">
                                                                            <input id="item-ro-correct-{{ $item->id }}" type="checkbox" name="review_show_correctness" value="1" @checked($ro['show_correctness']) style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Afficher la justesse</span>
                                                                        </label>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;" for="item-ro-marks-{{ $item->id }}">
                                                                            <input id="item-ro-marks-{{ $item->id }}" type="checkbox" name="review_show_marks" value="1" @checked($ro['show_marks']) style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Afficher les points</span>
                                                                        </label>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;" for="item-ro-spec-{{ $item->id }}">
                                                                            <input id="item-ro-spec-{{ $item->id }}" type="checkbox" name="review_show_specific_feedback" value="1" @checked($ro['show_specific_feedback']) style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Afficher la rétroaction du choix</span>
                                                                        </label>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;" for="item-ro-gen-{{ $item->id }}">
                                                                            <input id="item-ro-gen-{{ $item->id }}" type="checkbox" name="review_show_general_feedback" value="1" @checked($ro['show_general_feedback']) style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Afficher l'explication générale</span>
                                                                        </label>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;" for="item-ro-overall-{{ $item->id }}">
                                                                            <input id="item-ro-overall-{{ $item->id }}" type="checkbox" name="review_show_overall_feedback" value="1" @checked($ro['show_overall_feedback']) style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Afficher la rétroaction globale</span>
                                                                        </label>
                                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;" for="item-ro-right-{{ $item->id }}">
                                                                            <input id="item-ro-right-{{ $item->id }}" type="checkbox" name="review_show_right_answer" value="1" @checked($ro['show_right_answer']) style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                            <span>Afficher la bonne réponse</span>
                                                                        </label>
                                                                    </fieldset>

                                                                {{-- ── CHOICE : sondage / vote simple (non noté) ── --}}
                                                                @elseif ($item->type === 'choice')
                                                                    @php($choiceVisibility = \Modules\Academy\Services\ChoiceService::visibility($item))
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-choiceq-{{ $item->id }}">Énoncé du sondage</label>
                                                                    <input id="item-choiceq-{{ $item->id }}" type="text" name="choice_question" value="{{ \Modules\Academy\Services\ChoiceService::question($item) }}" placeholder="Quelle est votre préférence ?" aria-label="Énoncé du sondage"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-choiceopts-{{ $item->id }}">Options (une par ligne, au moins 2)</label>
                                                                    <textarea id="item-choiceopts-{{ $item->id }}" name="choice_options" rows="4" placeholder="Option A&#10;Option B&#10;Option C" aria-label="Options du sondage, une par ligne"
                                                                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ implode("\n", \Modules\Academy\Services\ChoiceService::options($item)) }}</textarea>

                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-choicemulti-{{ $item->id }}">
                                                                        <input id="item-choicemulti-{{ $item->id }}" type="checkbox" name="allow_multiple" value="1" @checked(\Modules\Academy\Services\ChoiceService::allowsMultiple($item))
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Autoriser plusieurs réponses</span>
                                                                    </label>
                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-choiceanon-{{ $item->id }}">
                                                                        <input id="item-choiceanon-{{ $item->id }}" type="checkbox" name="anonymous" value="1" @checked(\Modules\Academy\Services\ChoiceService::isAnonymous($item))
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Vote anonyme (ne pas révéler qui a voté)</span>
                                                                    </label>

                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-choicevis-{{ $item->id }}">Visibilité des résultats</label>
                                                                    <select id="item-choicevis-{{ $item->id }}" name="results_visibility" aria-label="Visibilité des résultats du sondage"
                                                                            style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        <option value="after_vote" @selected($choiceVisibility === 'after_vote')>Après avoir voté</option>
                                                                        <option value="always" @selected($choiceVisibility === 'always')>Toujours visibles</option>
                                                                        <option value="never" @selected($choiceVisibility === 'never')>Jamais (formateur seulement)</option>
                                                                    </select>
                                                                @elseif ($item->type === 'forum')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-forumintro-{{ $item->id }}">Introduction du forum (facultative)</label>
                                                                    <textarea id="item-forumintro-{{ $item->id }}" name="forum_intro" rows="2" placeholder="Présentez le sujet de discussion…" aria-label="Introduction du forum"
                                                                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ \Modules\Academy\Services\ForumService::intro($item) }}</textarea>

                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-forumallow-{{ $item->id }}">
                                                                        <input id="item-forumallow-{{ $item->id }}" type="checkbox" name="allow_student_topics" value="1" @checked(\Modules\Academy\Services\ForumService::allowsStudentTopics($item))
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Autoriser les étudiants à ouvrir des sujets</span>
                                                                    </label>
                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-forumlock-{{ $item->id }}">
                                                                        <input id="item-forumlock-{{ $item->id }}" type="checkbox" name="locked" value="1" @checked(\Modules\Academy\Services\ForumService::isLocked($item))
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Verrouiller le forum (lecture seule, aucune nouvelle contribution)</span>
                                                                    </label>
                                                                @elseif ($item->type === 'wiki')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-wikiintro-{{ $item->id }}">Introduction du wiki (facultative)</label>
                                                                    <textarea id="item-wikiintro-{{ $item->id }}" name="wiki_intro" rows="2" placeholder="Expliquez le but de ce wiki collaboratif…" aria-label="Introduction du wiki"
                                                                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ \Modules\Academy\Services\WikiService::intro($item) }}</textarea>

                                                                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="item-wikiedit-{{ $item->id }}">
                                                                        <input id="item-wikiedit-{{ $item->id }}" type="checkbox" name="allow_student_edit" value="1" @checked(\Modules\Academy\Services\WikiService::allowsStudentEdit($item))
                                                                               style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                                        <span>Autoriser les étudiants à modifier les pages (sinon : lecture seule, seul le formateur édite)</span>
                                                                    </label>
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                                                                        Les pages, leur contenu et l'historique se gèrent dans le lecteur de la leçon.
                                                                    </p>
                                                                @elseif ($item->type === 'h5p')
                                                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        Modifiez le titre et le critère d'achèvement ici. Le contenu interactif se remplace via « Remplacer le paquet H5P » plus bas.
                                                                    </p>
                                                                @elseif ($item->type === 'scorm')
                                                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        Modifiez le titre ici. Le paquet SCORM se remplace via « Remplacer le paquet SCORM » plus bas.
                                                                    </p>
                                                                @endif

                                                                {{--
                                                                    V2-c : critère d'achèvement (parité Moodle « activity completion »).
                                                                    Import SCORM : critère IMPOSÉ (piloté par le runtime), pas de sélecteur -
                                                                    voir ActivityCompletionService::allowedForType('scorm') = ['scorm'] seul.
                                                                --}}
                                                                @php($itemCriterion = \Modules\Academy\Services\ActivityCompletionService::criterionFor($item))
                                                                @if ($item->type === 'scorm')
                                                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        Achèvement : {{ \Modules\Academy\Services\ActivityCompletionService::modeLabel($itemCriterion) }} (non modifiable).
                                                                    </p>
                                                                @else
                                                                <label style="font-size: 0.78rem; font-weight: 600;" for="item-completion-{{ $item->id }}">Critère d'achèvement</label>
                                                                <select id="item-completion-{{ $item->id }}" name="completion" aria-label="Critère d'achèvement de l'élément"
                                                                        style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    <option value="manual" @selected($itemCriterion === 'manual')>Manuel (l'étudiant le marque terminé)</option>
                                                                    <option value="view" @selected($itemCriterion === 'view')>Automatique à la consultation</option>
                                                                    @if ($item->type === 'quiz')
                                                                        <option value="min_grade" @selected($itemCriterion === 'min_grade')>En réussissant le quiz (note de passage)</option>
                                                                    @endif
                                                                    @if ($item->type === 'choice')
                                                                        <option value="vote" @selected($itemCriterion === 'vote')>En votant au sondage</option>
                                                                    @endif
                                                                    @if ($item->type === 'forum')
                                                                        <option value="post" @selected($itemCriterion === 'post')>En participant au forum (sujet ou réponse)</option>
                                                                    @endif
                                                                </select>
                                                                @endif

                                                                <label style="font-size: 0.78rem; font-weight: 600;" for="item-min-{{ $item->id }}">Durée estimée de l'élément (min, facultatif)</label>
                                                                <input id="item-min-{{ $item->id }}" type="number" min="1" name="estimated_minutes" value="{{ $item->estimated_minutes }}" aria-label="Durée estimée en minutes"
                                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                <div><x-core::button type="submit" variant="secondary" size="sm">Enregistrer l'élément</x-core::button></div>
                                                            </form>
                                                            @endif

                                                            {{-- ── V1-a : Rétroaction globale par tranche de score (item quiz) - hors du formulaire principal ── --}}
                                                            @if ($item->type === 'quiz')
                                                                <div style="margin-top: 12px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                                    <span style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 4px;">Rétroaction globale par tranche de score</span>
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                                                                        Message affiché selon le pourcentage obtenu (ex. à partir de 80 %, à partir de 50 %…). Le message de la borne la plus haute atteinte est retenu.
                                                                    </p>

                                                                    @php($rows = $overallFeedback[$item->id] ?? null)
                                                                    @if ($rows === null)
                                                                        <x-core::button type="button" wire:click="loadOverallFeedback({{ $item->id }})" variant="secondary" size="sm">
                                                                            @if (!empty($item->payload['overall_feedback'])) Modifier la rétroaction globale @else Ajouter une rétroaction globale @endif
                                                                        </x-core::button>
                                                                    @else
                                                                        @foreach ($rows as $ri => $row)
                                                                            <div wire:key="ofb-{{ $item->id }}-{{ $ri }}" style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                                                                <div style="flex: 0 0 auto;">
                                                                                    <label class="visually-hidden" for="ofb-min-{{ $item->id }}-{{ $ri }}">Seuil minimum en pourcentage</label>
                                                                                    <input id="ofb-min-{{ $item->id }}-{{ $ri }}" type="number" min="0" max="100"
                                                                                           wire:model="overallFeedback.{{ $item->id }}.{{ $ri }}.min_percent"
                                                                                           aria-label="Seuil minimum (%)"
                                                                                           style="width: 84px; padding: 6px 8px; min-height: 34px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                                </div>
                                                                                <span aria-hidden="true" style="line-height: 34px; font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">% →</span>
                                                                                <label class="visually-hidden" for="ofb-msg-{{ $item->id }}-{{ $ri }}">Message de rétroaction</label>
                                                                                <input id="ofb-msg-{{ $item->id }}-{{ $ri }}" type="text" maxlength="2000"
                                                                                       wire:model="overallFeedback.{{ $item->id }}.{{ $ri }}.message"
                                                                                       placeholder="Message affiché à ce score (ex. Excellent !)"
                                                                                       style="flex: 1; padding: 6px 10px; min-height: 34px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.85rem;">
                                                                                <x-core::button type="button" wire:click="removeOverallBoundary({{ $item->id }}, {{ $ri }})" variant="ghost" size="sm" aria-label="Retirer cette tranche">✕</x-core::button>
                                                                            </div>
                                                                        @endforeach
                                                                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                                                                            <x-core::button type="button" wire:click="addOverallBoundary({{ $item->id }})" variant="secondary" size="sm">+ Ajouter une tranche</x-core::button>
                                                                            <x-core::button type="button" wire:click="saveOverallFeedback({{ $item->id }})" variant="primary" size="sm">Enregistrer la rétroaction globale</x-core::button>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            {{-- ── Téléversement de l'affiche (item vidéo) - hors du formulaire principal ── --}}
                                                            @if ($item->type === 'video')
                                                                @php($posterUploaded = $item->posterUrl())
                                                                <div style="margin-top: 12px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                                    <span style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 6px;">Téléverser une affiche (JPG, PNG ou WebP, 4 Mo max)</span>
                                                                    @if ($posterUploaded)
                                                                        <img src="{{ $posterUploaded }}" alt="Aperçu de l'affiche de la vidéo « {{ $item->title }} »"
                                                                             style="max-width: 200px; width: 100%; height: auto; border-radius: var(--sys-radius-md, 0.5rem); border: 1px solid #E5E7EB; margin-bottom: 8px;">
                                                                    @endif
                                                                    @if (isset($itemPoster[$item->id]) && $itemPoster[$item->id] && method_exists($itemPoster[$item->id], 'isPreviewable') && $itemPoster[$item->id]->isPreviewable())
                                                                        <img src="{{ $itemPoster[$item->id]->temporaryUrl() }}" alt="Aperçu de la nouvelle affiche"
                                                                             style="max-width: 200px; width: 100%; height: auto; border-radius: var(--sys-radius-md, 0.5rem); border: 1px solid var(--sys-action-primary, #064E5A); margin-bottom: 8px;">
                                                                    @endif
                                                                    <label class="visually-hidden" for="item-poster-file-{{ $item->id }}">Fichier de l'affiche de la vidéo</label>
                                                                    <input id="item-poster-file-{{ $item->id }}" type="file" wire:model="itemPoster.{{ $item->id }}" accept="image/jpeg,image/png,image/webp" style="width: 100%;">
                                                                    @error("itemPoster.{$item->id}") <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                                    <div wire:loading wire:target="itemPoster.{{ $item->id }}" role="status" aria-live="polite" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin-top: 4px;">Téléversement…</div>
                                                                    <div class="d-flex flex-wrap gap-2" style="margin-top: 8px;">
                                                                        <x-core::button type="button" wire:click="uploadItemPoster({{ $item->id }})" wire:loading.attr="disabled" wire:target="uploadItemPoster({{ $item->id }}),itemPoster.{{ $item->id }}" variant="secondary" size="sm">Téléverser l'affiche</x-core::button>
                                                                        @if ($posterUploaded)
                                                                            <x-core::button type="button" wire:click="removeItemPoster({{ $item->id }})" wire:loading.attr="disabled" wire:target="removeItemPoster({{ $item->id }})" variant="ghost" size="sm">Retirer l'affiche</x-core::button>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            {{-- ── Pièces jointes (item document) - hors du formulaire principal ── --}}
                                                            @if ($item->type === 'document')
                                                                <div style="margin-top: 12px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                                    <span style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 6px;">Pièces jointes</span>
                                                                    @if (!empty($item->payload['attachments']))
                                                                        <ul style="margin: 0 0 10px; padding-left: 18px; font-size: 0.82rem;">
                                                                            @foreach ($item->payload['attachments'] as $attachment)
                                                                                <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                                                                    <a href="{{ $attachment['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $attachment['name'] ?? 'Document' }}</a>
                                                                                    @if (isset($attachment['media_id']))
                                                                                        <x-core::button type="button" wire:click="removeItemAttachment({{ $item->id }}, {{ (int) $attachment['media_id'] }})" wire:loading.attr="disabled" wire:target="removeItemAttachment({{ $item->id }}, {{ (int) $attachment['media_id'] }})" variant="ghost" size="sm" title="Retirer la pièce jointe" aria-label="Retirer la pièce jointe « {{ $attachment['name'] ?? 'document' }} »">Retirer</x-core::button>
                                                                                    @endif
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @endif
                                                                    <label class="visually-hidden" for="item-attach-file-{{ $item->id }}">Fichier de la pièce jointe</label>
                                                                    <input id="item-attach-file-{{ $item->id }}" type="file" wire:model="itemAttachment.{{ $item->id }}" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp" style="width: 100%;">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">PDF, Word ou image, 10 Mo max.</p>
                                                                    @error("itemAttachment.{$item->id}") <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                                    <div wire:loading wire:target="itemAttachment.{{ $item->id }}" role="status" aria-live="polite" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin-top: 4px;">Téléversement…</div>
                                                                    <div style="margin-top: 8px;">
                                                                        <x-core::button type="button" wire:click="uploadItemAttachment({{ $item->id }})" wire:loading.attr="disabled" wire:target="uploadItemAttachment({{ $item->id }}),itemAttachment.{{ $item->id }}" variant="secondary" size="sm">Ajouter la pièce jointe</x-core::button>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            {{-- ── F16 : Paquet H5P (item h5p) - remplacement hors du formulaire principal ── --}}
                                                            @if ($item->type === 'h5p')
                                                                <div style="margin-top: 12px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                                    <span style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 6px;">Contenu interactif H5P</span>
                                                                    @if (!empty($item->payload['h5p_path']))
                                                                        <p style="font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); margin: 0 0 6px;">
                                                                            🧩 Paquet en place : {{ $item->payload['title'] ?? 'contenu H5P' }}
                                                                        </p>
                                                                    @else
                                                                        <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Aucun paquet pour l'instant.</p>
                                                                    @endif
                                                                    <label class="visually-hidden" for="item-h5p-file-{{ $item->id }}">Nouveau paquet H5P</label>
                                                                    <input id="item-h5p-file-{{ $item->id }}" type="file" accept=".h5p,application/zip" wire:model="itemH5p.{{ $item->id }}" style="width: 100%;">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">Fichier .h5p, 30 Mo max. Remplace le contenu actuel.</p>
                                                                    @error("itemH5p.{$item->id}") <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                                    <div wire:loading wire:target="itemH5p.{{ $item->id }}" role="status" aria-live="polite" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin-top: 4px;">Téléversement…</div>
                                                                    <div style="margin-top: 8px;">
                                                                        <x-core::button type="button" wire:click="replaceH5pPackage({{ $item->id }})" wire:loading.attr="disabled" wire:target="replaceH5pPackage({{ $item->id }}),itemH5p.{{ $item->id }}" variant="secondary" size="sm">Remplacer le paquet H5P</x-core::button>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            {{-- ── Import SCORM (item scorm) - remplacement hors du formulaire principal ── --}}
                                                            @if ($item->type === 'scorm')
                                                                <div style="margin-top: 12px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                                    <span style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 6px;">Paquet SCORM</span>
                                                                    @if (!empty($item->payload['scorm_path']))
                                                                        <p style="font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); margin: 0 0 6px;">
                                                                            📦 Paquet en place : {{ $item->payload['scorm_title'] ?? 'contenu SCORM' }}
                                                                            (SCORM {{ $item->payload['scorm_version'] ?? 'inconnu' }})
                                                                        </p>
                                                                    @else
                                                                        <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Aucun paquet pour l'instant.</p>
                                                                    @endif
                                                                    <label class="visually-hidden" for="item-scorm-file-{{ $item->id }}">Nouveau paquet SCORM</label>
                                                                    <input id="item-scorm-file-{{ $item->id }}" type="file" accept=".zip,application/zip" wire:model="itemScorm.{{ $item->id }}" style="width: 100%;">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">Fichier .zip SCORM 1.2 ou 2004 (single-SCO), 200 Mo max. Remplace le contenu actuel.</p>
                                                                    @error("itemScorm.{$item->id}") <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                                    <div wire:loading wire:target="itemScorm.{{ $item->id }}" role="status" aria-live="polite" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin-top: 4px;">Téléversement…</div>
                                                                    <div style="margin-top: 8px;">
                                                                        <x-core::button type="button" wire:click="replaceScormPackage({{ $item->id }})" wire:loading.attr="disabled" wire:target="replaceScormPackage({{ $item->id }}),itemScorm.{{ $item->id }}" variant="secondary" size="sm">Remplacer le paquet SCORM</x-core::button>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            {{-- ── V5-d : Restrictions d'accès (parité Moodle « Restrict access ») ── --}}
                                                            <div style="margin-top: 12px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                                <span style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 4px;">Restrictions d'accès</span>
                                                                <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                                                                    Conditionne la visibilité de cet élément (date, note, achèvement d'un autre élément, groupe).
                                                                </p>

                                                                @php($__rBuf = $editRestrictions[$item->id] ?? null)

                                                                @if($__rBuf === null)
                                                                    {{-- Panneau fermé --}}
                                                                    @if(!empty($item->payload['access_restrictions']['conditions']))
                                                                        <p style="font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); margin: 0 0 6px;">
                                                                            🔒 {{ count($item->payload['access_restrictions']['conditions']) }} restriction(s) active(s)
                                                                        </p>
                                                                    @endif
                                                                    <x-core::button type="button"
                                                                        wire:click="loadItemRestrictions({{ $item->id }})"
                                                                        variant="secondary" size="sm">
                                                                        @if(!empty($item->payload['access_restrictions']['conditions']))
                                                                            Modifier les restrictions
                                                                        @else
                                                                            Ajouter une restriction
                                                                        @endif
                                                                    </x-core::button>
                                                                @else
                                                                    {{-- Panneau ouvert --}}
                                                                    <fieldset style="border: 0; padding: 0; margin: 0 0 8px;">
                                                                        <legend class="visually-hidden">Opérateur logique des restrictions</legend>
                                                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 0.82rem;">
                                                                            <label for="restrict-match-{{ $item->id }}" style="font-weight: 600;">L'étudiant doit remplir :</label>
                                                                            <select id="restrict-match-{{ $item->id }}"
                                                                                    wire:model="editRestrictions.{{ $item->id }}.match"
                                                                                    style="padding: 4px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                                                                                <option value="all">Toutes les conditions (ET)</option>
                                                                                <option value="any">Au moins une condition (OU)</option>
                                                                            </select>
                                                                        </div>
                                                                    </fieldset>

                                                                    @php($__refItems = $this->restrictionRefItems($item->id))

                                                                    @forelse($__rBuf['conditions'] as $__ci => $__cond)
                                                                        <div wire:key="rcond-{{ $item->id }}-{{ $__ci }}"
                                                                             style="border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; background: #FAFAFA;">
                                                                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                                                                <div style="flex: 1;">
                                                                                    <label for="rcond-type-{{ $item->id }}-{{ $__ci }}" class="visually-hidden">Type de condition</label>
                                                                                    <select id="rcond-type-{{ $item->id }}-{{ $__ci }}"
                                                                                            wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.type"
                                                                                            style="width: 100%; padding: 5px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem; margin-bottom: 6px;">
                                                                                        <option value="completion">Achèvement requis</option>
                                                                                        <option value="grade">Note minimale</option>
                                                                                        <option value="date">Date</option>
                                                                                        <option value="group">Groupe</option>
                                                                                    </select>

                                                                                    {{-- Champs dynamiques selon le type --}}
                                                                                    @if(($__cond['type'] ?? '') === 'completion')
                                                                                        <label for="rcond-item-{{ $item->id }}-{{ $__ci }}" class="visually-hidden">Élément à compléter</label>
                                                                                        <select id="rcond-item-{{ $item->id }}-{{ $__ci }}"
                                                                                                wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.item_id"
                                                                                                style="width: 100%; padding: 5px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                                                                                            <option value="0">-- Choisir un élément --</option>
                                                                                            @foreach($__refItems as $__ref)
                                                                                                <option value="{{ $__ref['id'] }}">{{ $__ref['title'] }} ({{ $__ref['type'] }})</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    @elseif(($__cond['type'] ?? '') === 'grade')
                                                                                        <label for="rcond-gitem-{{ $item->id }}-{{ $__ci }}" class="visually-hidden">Quiz cible</label>
                                                                                        <select id="rcond-gitem-{{ $item->id }}-{{ $__ci }}"
                                                                                                wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.item_id"
                                                                                                style="width: 100%; padding: 5px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem; margin-bottom: 4px;">
                                                                                            <option value="0">-- Choisir un quiz --</option>
                                                                                            @foreach($__refItems as $__ref)
                                                                                                @if($__ref['type'] === 'quiz')
                                                                                                    <option value="{{ $__ref['id'] }}">{{ $__ref['title'] }}</option>
                                                                                                @endif
                                                                                            @endforeach
                                                                                        </select>
                                                                                        <div style="display: flex; align-items: center; gap: 6px; font-size: 0.82rem;">
                                                                                            <label for="rcond-gpct-{{ $item->id }}-{{ $__ci }}" style="white-space: nowrap;">Min. :</label>
                                                                                            <input id="rcond-gpct-{{ $item->id }}-{{ $__ci }}" type="number" min="0" max="100"
                                                                                                   wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.min_percent"
                                                                                                   style="width: 72px; padding: 4px 6px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                                                                                            <span>%</span>
                                                                                        </div>
                                                                                    @elseif(($__cond['type'] ?? '') === 'date')
                                                                                        <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.82rem;">
                                                                                            <label for="rcond-from-{{ $item->id }}-{{ $__ci }}" style="font-size: 0.78rem;">Disponible à partir du :</label>
                                                                                            <input id="rcond-from-{{ $item->id }}-{{ $__ci }}" type="datetime-local"
                                                                                                   wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.from"
                                                                                                   style="padding: 4px 6px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.8rem;">
                                                                                            <label for="rcond-until-{{ $item->id }}-{{ $__ci }}" style="font-size: 0.78rem; margin-top: 4px;">Disponible jusqu'au :</label>
                                                                                            <input id="rcond-until-{{ $item->id }}-{{ $__ci }}" type="datetime-local"
                                                                                                   wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.until"
                                                                                                   style="padding: 4px 6px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.8rem;">
                                                                                        </div>
                                                                                    @elseif(($__cond['type'] ?? '') === 'group')
                                                                                        <label for="rcond-group-{{ $item->id }}-{{ $__ci }}" class="visually-hidden">Groupe (cohorte)</label>
                                                                                        <input id="rcond-group-{{ $item->id }}-{{ $__ci }}" type="number" min="1"
                                                                                               wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.group_id"
                                                                                               placeholder="ID de la cohorte"
                                                                                               style="width: 100%; padding: 5px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                                                                                        <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 3px 0 0;">Identifiant de la cohorte (visible dans la liste de gestion du cours).</p>
                                                                                    @endif
                                                                                </div>
                                                                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0;">
                                                                                    <x-core::button type="button"
                                                                                        wire:click="removeRestrictionCondition({{ $item->id }}, {{ $__ci }})"
                                                                                        variant="ghost" size="sm"
                                                                                        aria-label="Retirer cette condition">✕</x-core::button>
                                                                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.72rem; cursor: pointer; white-space: nowrap;"
                                                                                           for="rcond-hide-{{ $item->id }}-{{ $__ci }}">
                                                                                        <input id="rcond-hide-{{ $item->id }}-{{ $__ci }}" type="checkbox"
                                                                                               wire:model="editRestrictions.{{ $item->id }}.conditions.{{ $__ci }}.hide"
                                                                                               style="width: 16px; height: 16px; flex-shrink: 0;">
                                                                                        Masquer si non remplie
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @empty
                                                                        <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">Aucune restriction pour l'instant.</p>
                                                                    @endforelse

                                                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                                                                        <x-core::button type="button"
                                                                            wire:click="addRestrictionCondition({{ $item->id }}, 'completion')"
                                                                            variant="ghost" size="sm">+ Achèvement</x-core::button>
                                                                        <x-core::button type="button"
                                                                            wire:click="addRestrictionCondition({{ $item->id }}, 'grade')"
                                                                            variant="ghost" size="sm">+ Note</x-core::button>
                                                                        <x-core::button type="button"
                                                                            wire:click="addRestrictionCondition({{ $item->id }}, 'date')"
                                                                            variant="ghost" size="sm">+ Date</x-core::button>
                                                                        <x-core::button type="button"
                                                                            wire:click="addRestrictionCondition({{ $item->id }}, 'group')"
                                                                            variant="ghost" size="sm">+ Groupe</x-core::button>
                                                                    </div>
                                                                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                                                                        <x-core::button type="button"
                                                                            wire:click="saveItemRestrictions({{ $item->id }})"
                                                                            variant="primary" size="sm">Enregistrer les restrictions</x-core::button>
                                                                        <x-core::button type="button"
                                                                            wire:click="cancelItemRestrictions({{ $item->id }})"
                                                                            variant="ghost" size="sm">Annuler</x-core::button>
                                                                    </div>
                                                                @endif
                                                                {{-- variables temporaires ($__rBuf, $__refItems) réinitialisées à chaque itération --}}
                                                            </div>
                                                        </details>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <x-academy::empty-state icon="🎬" :compact="true"
                                                message="Cette leçon est vide. Ajoutez une vidéo, un document ou un quiz." />
                                        @endif

                                        {{-- Ajouter un élément (formulaire par type) --}}
                                        <details style="border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                            <summary style="cursor: pointer; font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Ajouter un élément</summary>

                                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-title-{{ $lesson->id }}">Titre de l'élément</label>
                                                <input id="newitem-title-{{ $lesson->id }}" type="text" wire:model="newItem.{{ $lesson->id }}.title" placeholder="Titre de l'élément"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 4px 0 0;">Champs pour une vidéo</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-url-{{ $lesson->id }}">URL d'intégration ScreenPal</label>
                                                <input id="newitem-url-{{ $lesson->id }}" type="url" wire:model="newItem.{{ $lesson->id }}.player_url" placeholder="https://share.screenpal.com/player/…"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                    Utilisez une vidéo non répertoriée avec verrou de domaine sur ScreenPal, et l'URL d'intégration (player/…), pas le lien de partage (watch/…).
                                                </p>

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-poster-{{ $lesson->id }}">Affiche / vignette (URL, facultatif)</label>
                                                <input id="newitem-poster-{{ $lesson->id }}" type="url" wire:model="newItem.{{ $lesson->id }}.poster_url" placeholder="https://… (téléversement à venir)"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-dur-{{ $lesson->id }}">Durée de la vidéo (min, facultatif)</label>
                                                <input id="newitem-dur-{{ $lesson->id }}" type="number" min="1" max="1440" wire:model="newItem.{{ $lesson->id }}.duration_minutes" placeholder="Durée de la vidéo en minutes"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un document</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-doc-{{ $lesson->id }}">Contenu (markdown)</label>
                                                <x-academy::markdown-editor :textareaId="'newitem-doc-'.$lesson->id" :uid="'new-'.$lesson->id" wire:key="md-new-{{ $lesson->id }}">
                                                    <textarea id="newitem-doc-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.rich_text" rows="4" placeholder="Rédigez le contenu en markdown…"
                                                              style="width: 100%; padding: 8px 12px; border: none; border-radius: 0; outline: none; resize: vertical;"></textarea>
                                                </x-academy::markdown-editor>

                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un quiz</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-qt-{{ $lesson->id }}">Clé de banque QT</label>
                                                <input id="newitem-qt-{{ $lesson->id }}" type="text" wire:model="newItem.{{ $lesson->id }}.qt_bank_key" placeholder="Clé de banque QT (réutilise QtService)"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-pass-{{ $lesson->id }}">Score de réussite (%)</label>
                                                <input id="newitem-pass-{{ $lesson->id }}" type="number" min="0" max="100" wire:model="newItem.{{ $lesson->id }}.passing_score" placeholder="60"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-att-{{ $lesson->id }}">Tentatives autorisées (laisser vide = illimité)</label>
                                                <input id="newitem-att-{{ $lesson->id }}" type="number" min="1" max="99" wire:model="newItem.{{ $lesson->id }}.attempts_allowed" placeholder="Illimité"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                {{-- V1-c : méthode de notation sur tentatives (défaut « meilleure », parité Moodle). --}}
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-grade-{{ $lesson->id }}">Note retenue (plusieurs tentatives)</label>
                                                <select id="newitem-grade-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.grading_method" aria-label="Méthode de notation sur les tentatives"
                                                        style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                    <option value="highest">Meilleure note</option>
                                                    <option value="average">Moyenne des tentatives</option>
                                                    <option value="first">Première tentative</option>
                                                    <option value="last">Dernière tentative</option>
                                                </select>

                                                {{-- V1-d : mélange + limite de temps (options de révision réglables après création, défaut = révision complète). --}}
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-shufq-{{ $lesson->id }}">
                                                    <input id="newitem-shufq-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.shuffle_questions" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Mélanger les questions</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-shufa-{{ $lesson->id }}">
                                                    <input id="newitem-shufa-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.shuffle_answers" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Mélanger les réponses (choix multiples)</span>
                                                </label>

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-timelimit-{{ $lesson->id }}">Limite de temps (minutes, vide = aucune)</label>
                                                <input id="newitem-timelimit-{{ $lesson->id }}" type="number" min="1" max="240" wire:model="newItem.{{ $lesson->id }}.time_limit_minutes" placeholder="Aucune"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un sondage</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-choiceq-{{ $lesson->id }}">Énoncé du sondage</label>
                                                <input id="newitem-choiceq-{{ $lesson->id }}" type="text" wire:model="newItem.{{ $lesson->id }}.choice_question" placeholder="Quelle est votre préférence ?"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @error('choice_question') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-choiceopts-{{ $lesson->id }}">Options (une par ligne, au moins 2)</label>
                                                <textarea id="newitem-choiceopts-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.choice_options" rows="4" placeholder="Option A&#10;Option B&#10;Option C"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                @error('choice_options') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-choicemulti-{{ $lesson->id }}">
                                                    <input id="newitem-choicemulti-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.allow_multiple" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Autoriser plusieurs réponses</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-choiceanon-{{ $lesson->id }}">
                                                    <input id="newitem-choiceanon-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.anonymous" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Vote anonyme (ne pas révéler qui a voté)</span>
                                                </label>

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-choicevis-{{ $lesson->id }}">Visibilité des résultats</label>
                                                <select id="newitem-choicevis-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.results_visibility" aria-label="Visibilité des résultats du sondage"
                                                        style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                    <option value="after_vote">Après avoir voté</option>
                                                    <option value="always">Toujours visibles</option>
                                                    <option value="never">Jamais (formateur seulement)</option>
                                                </select>

                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour une rétroaction (questionnaire)</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-fbintro-{{ $lesson->id }}">Introduction (facultative)</label>
                                                <textarea id="newitem-fbintro-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.feedback_intro" rows="2" placeholder="Quelques mots pour présenter le sondage…"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                                <span style="font-size: 0.78rem; font-weight: 700;">Questions</span>
                                                @error('feedback_questions') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                @forelse ($newItem[$lesson->id]['feedback_questions'] ?? [] as $qi => $q)
                                                    <div wire:key="newfb-{{ $lesson->id }}-{{ $qi }}" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="newfb-type-{{ $lesson->id }}-{{ $qi }}">Type</label>
                                                        <select id="newfb-type-{{ $lesson->id }}-{{ $qi }}" wire:model="newItem.{{ $lesson->id }}.feedback_questions.{{ $qi }}.type" aria-label="Type de question"
                                                                style="width: 100%; padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            <option value="rating">Échelle (note)</option>
                                                            <option value="choice">Choix</option>
                                                            <option value="text">Texte libre</option>
                                                        </select>
                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="newfb-label-{{ $lesson->id }}-{{ $qi }}">Énoncé</label>
                                                        <input id="newfb-label-{{ $lesson->id }}-{{ $qi }}" type="text" wire:model="newItem.{{ $lesson->id }}.feedback_questions.{{ $qi }}.label" placeholder="Votre question"
                                                               style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        @if (($q['type'] ?? '') === 'rating')
                                                            <label style="font-size: 0.74rem; font-weight: 600;" for="newfb-scale-{{ $lesson->id }}-{{ $qi }}">Échelle 1 à</label>
                                                            <input id="newfb-scale-{{ $lesson->id }}-{{ $qi }}" type="number" min="2" max="10" wire:model="newItem.{{ $lesson->id }}.feedback_questions.{{ $qi }}.scale"
                                                                   style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        @elseif (($q['type'] ?? '') === 'choice')
                                                            <label style="font-size: 0.74rem; font-weight: 600;" for="newfb-opts-{{ $lesson->id }}-{{ $qi }}">Options (une par ligne, au moins 2)</label>
                                                            <textarea id="newfb-opts-{{ $lesson->id }}-{{ $qi }}" wire:model="newItem.{{ $lesson->id }}.feedback_questions.{{ $qi }}.options" rows="3"
                                                                      style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                        @endif
                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 600;">
                                                            <input type="checkbox" wire:model="newItem.{{ $lesson->id }}.feedback_questions.{{ $qi }}.required" style="width: 22px; height: 22px; flex: 0 0 auto;">
                                                            <span>Réponse obligatoire</span>
                                                        </label>
                                                        <div><x-core::button type="button" wire:click="removeNewFeedbackQuestion({{ $lesson->id }}, {{ $qi }})" variant="ghost" size="sm">Retirer cette question</x-core::button></div>
                                                    </div>
                                                @empty
                                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune question. Ajoutez-en au moins une pour un sondage de rétroaction.</p>
                                                @endforelse
                                                <div><x-core::button type="button" wire:click="addNewFeedbackQuestion({{ $lesson->id }})" variant="ghost" size="sm">+ Ajouter une question</x-core::button></div>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-fbanon-{{ $lesson->id }}">
                                                    <input id="newitem-fbanon-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.anonymous" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Réponses anonymes (ne pas lier la réponse à l'étudiant)</span>
                                                </label>

                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un forum (discussion)</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-forumintro-{{ $lesson->id }}">Introduction (facultative)</label>
                                                <textarea id="newitem-forumintro-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.forum_intro" rows="2" placeholder="Présentez le sujet de discussion…"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-forumallow-{{ $lesson->id }}">
                                                    <input id="newitem-forumallow-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.allow_student_topics" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Autoriser les étudiants à ouvrir des sujets (par défaut : oui)</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-forumlock-{{ $lesson->id }}">
                                                    <input id="newitem-forumlock-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.locked" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Verrouiller le forum (lecture seule)</span>
                                                </label>

                                                {{-- F19 : wiki collaboratif (pages + historique). L'intro et l'édition
                                                     collaborative se règlent ici ; les pages se créent dans le lecteur. --}}
                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un wiki (pages collaboratives)</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-wikiintro-{{ $lesson->id }}">Introduction (facultative)</label>
                                                <textarea id="newitem-wikiintro-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.wiki_intro" rows="2" placeholder="Expliquez le but de ce wiki collaboratif…"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-wikiedit-{{ $lesson->id }}">
                                                    <input id="newitem-wikiedit-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.allow_student_edit" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Autoriser les étudiants à modifier les pages (par défaut : oui)</span>
                                                </label>

                                                {{-- F20 : base de données collaborative. Le gérant définit un SCHÉMA de
                                                     champs ; les inscrits soumettent ensuite des fiches selon ce schéma. --}}
                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour une base de données (fiches collaboratives)</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-dbintro-{{ $lesson->id }}">Introduction (facultative)</label>
                                                <textarea id="newitem-dbintro-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.database_intro" rows="2" placeholder="Expliquez ce que les inscrits doivent saisir…"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                                <span style="font-size: 0.78rem; font-weight: 700;">Schéma (champs de la fiche)</span>
                                                @forelse ($newItem[$lesson->id]['database_fields'] ?? [] as $fi => $f)
                                                    <div wire:key="newdb-{{ $lesson->id }}-{{ $fi }}" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="newdb-label-{{ $lesson->id }}-{{ $fi }}">Libellé du champ</label>
                                                        <input id="newdb-label-{{ $lesson->id }}-{{ $fi }}" type="text" wire:model="newItem.{{ $lesson->id }}.database_fields.{{ $fi }}.label" placeholder="Ex. : Nom de l'outil"
                                                               style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="newdb-type-{{ $lesson->id }}-{{ $fi }}">Type</label>
                                                        <select id="newdb-type-{{ $lesson->id }}-{{ $fi }}" wire:model.live="newItem.{{ $lesson->id }}.database_fields.{{ $fi }}.type" aria-label="Type du champ"
                                                                style="width: 100%; padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            <option value="text">Texte court</option>
                                                            <option value="textarea">Texte long</option>
                                                            <option value="number">Nombre</option>
                                                            <option value="url">Lien (URL)</option>
                                                            <option value="select">Liste de choix</option>
                                                        </select>
                                                        @if (($f['type'] ?? '') === 'select')
                                                            <label style="font-size: 0.74rem; font-weight: 600;" for="newdb-opts-{{ $lesson->id }}-{{ $fi }}">Options (une par ligne)</label>
                                                            <textarea id="newdb-opts-{{ $lesson->id }}-{{ $fi }}" wire:model="newItem.{{ $lesson->id }}.database_fields.{{ $fi }}.options" rows="3"
                                                                      style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                        @endif
                                                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 600;">
                                                            <input type="checkbox" wire:model="newItem.{{ $lesson->id }}.database_fields.{{ $fi }}.required" style="width: 22px; height: 22px; flex: 0 0 auto;">
                                                            <span>Champ obligatoire</span>
                                                        </label>
                                                        <div><x-core::button type="button" wire:click="removeNewDatabaseField({{ $lesson->id }}, {{ $fi }})" variant="ghost" size="sm">Retirer ce champ</x-core::button></div>
                                                    </div>
                                                @empty
                                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucun champ. Ajoutez-en pour définir la fiche que les inscrits rempliront.</p>
                                                @endforelse
                                                <div><x-core::button type="button" wire:click="addNewDatabaseField({{ $lesson->id }})" variant="ghost" size="sm">+ Ajouter un champ</x-core::button></div>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-dballow-{{ $lesson->id }}">
                                                    <input id="newitem-dballow-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.allow_student_add" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Autoriser les inscrits à ajouter une fiche (par défaut : oui)</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-dbapprove-{{ $lesson->id }}">
                                                    <input id="newitem-dbapprove-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.require_approval" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Exiger l'approbation d'un gérant avant de publier une fiche</span>
                                                </label>

                                                {{-- F21 : atelier d'évaluation par les pairs. Le gérant définit une GRILLE de
                                                     critères ; les inscrits remettent un travail puis évaluent ceux de leurs pairs. --}}
                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un atelier (évaluation par les pairs)</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-wsintro-{{ $lesson->id }}">Consigne / introduction (facultative)</label>
                                                <textarea id="newitem-wsintro-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.workshop_intro" rows="2" placeholder="Décrivez le travail à remettre et l'objectif de l'évaluation…"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                                <span style="font-size: 0.78rem; font-weight: 700;">Grille d'évaluation (critères)</span>
                                                @forelse ($newItem[$lesson->id]['workshop_criteria'] ?? [] as $ci => $c)
                                                    <div wire:key="newws-{{ $lesson->id }}-{{ $ci }}" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="newws-label-{{ $lesson->id }}-{{ $ci }}">Libellé du critère</label>
                                                        <input id="newws-label-{{ $lesson->id }}-{{ $ci }}" type="text" wire:model="newItem.{{ $lesson->id }}.workshop_criteria.{{ $ci }}.label" placeholder="Ex. : Clarté de l'argumentation"
                                                               style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        <label style="font-size: 0.74rem; font-weight: 600;" for="newws-desc-{{ $lesson->id }}-{{ $ci }}">Description (facultative)</label>
                                                        <textarea id="newws-desc-{{ $lesson->id }}-{{ $ci }}" wire:model="newItem.{{ $lesson->id }}.workshop_criteria.{{ $ci }}.description" rows="2"
                                                                  style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                            <span style="display: flex; flex-direction: column; gap: 4px;">
                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="newws-max-{{ $lesson->id }}-{{ $ci }}">Note max</label>
                                                                <input id="newws-max-{{ $lesson->id }}-{{ $ci }}" type="number" min="1" max="100" wire:model="newItem.{{ $lesson->id }}.workshop_criteria.{{ $ci }}.max_score"
                                                                       style="width: 90px; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            </span>
                                                            <span style="display: flex; flex-direction: column; gap: 4px;">
                                                                <label style="font-size: 0.74rem; font-weight: 600;" for="newws-weight-{{ $lesson->id }}-{{ $ci }}">Poids</label>
                                                                <input id="newws-weight-{{ $lesson->id }}-{{ $ci }}" type="number" min="0" step="0.5" wire:model="newItem.{{ $lesson->id }}.workshop_criteria.{{ $ci }}.weight"
                                                                       style="width: 90px; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            </span>
                                                        </div>
                                                        <div><x-core::button type="button" wire:click="removeNewWorkshopCriterion({{ $lesson->id }}, {{ $ci }})" variant="ghost" size="sm">Retirer ce critère</x-core::button></div>
                                                    </div>
                                                @empty
                                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucun critère. Ajoutez-en pour définir la grille d'évaluation par les pairs.</p>
                                                @endforelse
                                                <div><x-core::button type="button" wire:click="addNewWorkshopCriterion({{ $lesson->id }})" variant="ghost" size="sm">+ Ajouter un critère</x-core::button></div>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-wsreviews-{{ $lesson->id }}">Évaluations par étudiant (par défaut : 2)</label>
                                                <input id="newitem-wsreviews-{{ $lesson->id }}" type="number" min="1" max="10" wire:model="newItem.{{ $lesson->id }}.reviews_per_student" placeholder="2"
                                                       style="width: 120px; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;" for="newitem-wsanon-{{ $lesson->id }}">
                                                    <input id="newitem-wsanon-{{ $lesson->id }}" type="checkbox" wire:model="newItem.{{ $lesson->id }}.workshop_anonymous" style="width: 24px; height: 24px; flex: 0 0 auto;">
                                                    <span>Évaluation anonyme (masquer l'auteur des travaux ; par défaut : oui)</span>
                                                </label>

                                                {{-- F16 : contenu interactif H5P. Le paquet .h5p (zip) est validé + extrait
                                                     côté serveur (H5pPackageService), puis rendu dans un iframe sandbox. --}}
                                                <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un contenu interactif H5P</p>
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-h5p-{{ $lesson->id }}">Paquet .h5p (max 30 Mo)</label>
                                                <input id="newitem-h5p-{{ $lesson->id }}" type="file" accept=".h5p,application/zip" wire:model="newH5p.{{ $lesson->id }}"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                    Exportez votre activité depuis H5P (.h5p). Le titre ci-dessus est repris ; sinon le titre du paquet est utilisé.
                                                </p>
                                                @error('newH5p.'.$lesson->id) <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                <div wire:loading wire:target="newH5p.{{ $lesson->id }}" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">Téléversement du paquet…</div>
                                                <div>
                                                    <x-core::button type="button" wire:click="addH5pItem({{ $lesson->id }})" wire:loading.attr="disabled"
                                                        wire:target="addH5pItem({{ $lesson->id }}),newH5p.{{ $lesson->id }}" variant="primary" size="sm">
                                                        Ajouter un contenu interactif H5P
                                                    </x-core::button>
                                                </div>

                                                @if (config('academy.scorm_enabled', false))
                                                    {{-- Import SCORM : le paquet .zip (imsmanifest.xml + SCO) est validé + extrait
                                                         côté serveur (ScormPackageService, disque privé), puis rendu dans un iframe
                                                         sandbox via un pont API SCORM 1.2/2004 basique. Single-SCO uniquement. --}}
                                                    <p style="font-size: 0.74rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 8px 0 0;">Champs pour un import SCORM</p>
                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-scorm-{{ $lesson->id }}">Paquet SCORM .zip (max 200 Mo)</label>
                                                    <input id="newitem-scorm-{{ $lesson->id }}" type="file" accept=".zip,application/zip" wire:model="newScorm.{{ $lesson->id }}"
                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                        SCORM 1.2 (prioritaire) ou 2004 (basique), UN SEUL SCO par paquet. Le titre ci-dessus est repris ; sinon le titre du manifeste est utilisé.
                                                    </p>
                                                    @error('newScorm.'.$lesson->id) <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                    <div wire:loading wire:target="newScorm.{{ $lesson->id }}" style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">Téléversement du paquet…</div>
                                                    <div>
                                                        <x-core::button type="button" wire:click="addScormItem({{ $lesson->id }})" wire:loading.attr="disabled"
                                                            wire:target="addScormItem({{ $lesson->id }}),newScorm.{{ $lesson->id }}" variant="primary" size="sm">
                                                            Ajouter un import SCORM
                                                        </x-core::button>
                                                    </div>
                                                @endif

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-min-{{ $lesson->id }}">Durée estimée (min, facultatif)</label>
                                                <input id="newitem-min-{{ $lesson->id }}" type="number" min="1" wire:model="newItem.{{ $lesson->id }}.estimated_minutes" placeholder="Durée estimée"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                {{-- V2-c : critère d'achèvement (« min_grade » ne s'applique qu'aux quiz ;
                                                     posé sur un autre type, il est ignoré côté serveur → défaut du type). --}}
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-completion-{{ $lesson->id }}">Critère d'achèvement</label>
                                                <select id="newitem-completion-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.completion" aria-label="Critère d'achèvement de l'élément"
                                                        style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                    <option value="">Défaut (manuel ; quiz : réussir ; sondage : voter ; rétroaction : répondre)</option>
                                                    <option value="manual">Manuel (l'étudiant le marque terminé)</option>
                                                    <option value="view">Automatique à la consultation</option>
                                                    <option value="min_grade">En réussissant le quiz (quiz seulement)</option>
                                                    <option value="vote">En votant au sondage (sondage seulement)</option>
                                                    <option value="submit">En répondant à la rétroaction (rétroaction seulement)</option>
                                                    <option value="post">En participant au forum (forum seulement)</option>
                                                </select>

                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;">
                                                    <input type="checkbox" wire:model="newItem.{{ $lesson->id }}.is_required" style="width: 18px; height: 18px;">
                                                    Élément obligatoire
                                                </label>

                                                <div class="d-flex flex-wrap gap-2" style="margin-top: 4px;">
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'video')" variant="primary" size="sm">Ajouter une vidéo</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'document')" variant="primary" size="sm">Ajouter un document</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'quiz')" variant="primary" size="sm">Ajouter un quiz</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'choice')" variant="primary" size="sm">Ajouter un sondage</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'feedback')" variant="primary" size="sm">Ajouter une rétroaction</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'forum')" variant="primary" size="sm">Ajouter un forum</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'wiki')" variant="primary" size="sm">Ajouter un wiki</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'database')" variant="primary" size="sm">Ajouter une base de données</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'workshop')" variant="primary" size="sm">Ajouter un atelier</x-core::button>
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @else
                    @can('manageStructure', $course)
                        <x-academy::empty-state icon="📝" :compact="true"
                            message="Ce chapitre n'a pas encore de leçon. Ajoutez-en une pour structurer le contenu." />
                    @else
                        <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px;">Aucune leçon dans ce chapitre.</p>
                    @endcan
                @endif

                {{-- Ajouter une leçon à CE chapitre --}}
                @can('manageStructure', $course)
                    <form wire:submit="addLesson({{ $chapter->id }})"
                          style="border-top: 1px dashed #E5E7EB; padding-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                        <label for="new-lesson-{{ $chapter->id }}" style="font-weight: 600; font-size: 0.85rem;">Nouvelle leçon</label>
                        <input id="new-lesson-{{ $chapter->id }}" type="text" wire:model="newLesson.{{ $chapter->id }}.title" placeholder="Titre de la leçon"
                               style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        @error('newLesson.' . $chapter->id . '.title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        <input type="number" min="1" wire:model="newLesson.{{ $chapter->id }}.estimated_minutes" placeholder="Durée estimée (min, facultatif)" aria-label="Durée estimée de la nouvelle leçon"
                               style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <div><x-core::button type="submit" variant="primary" size="sm">Ajouter la leçon</x-core::button></div>
                    </form>
                @endcan
            </article>
        @empty
            <x-academy::empty-state icon="📚"
                message="Votre formation est vide. Commencez par ajouter un premier chapitre ci-dessus." />
        @endforelse
        @can('manageStructure', $course)
            </div>
        @endcan
    </section>

    {{-- ───────────────────────── Authoring IA ─────────────────────────────────────────────── --}}
    @if (config('academy.ai_authoring_enabled', false))
        @can('manageStructure', $course)
            <section aria-labelledby="editor-ai-authoring"
                     style="border: 1px solid #A7F3D0; background: #F0FDF4; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
                <h2 id="editor-ai-authoring" style="font-family: var(--f-heading); color: var(--sys-action-primary, #064E5A); margin: 0 0 6px; font-size: 1.1rem;">
                    ✨ Authoring IA
                </h2>
                <p style="font-size: 0.85rem; color: #374151; margin: 0 0 16px;">
                    Génère un plan de cours ou des questions de quiz depuis un prompt - en brouillon éditable, jamais publié automatiquement.
                </p>
                <livewire:academy.ai-authoring-modal :course="$course" />
            </section>
        @endcan
    @endif

    {{-- ───────────────────────── Traduction IA (brouillon, aucune sauvegarde auto) ──────────── --}}
    @if (config('academy.ai_translation_enabled', false))
        @can('manageStructure', $course)
            <section aria-labelledby="editor-ai-translation"
                     style="border: 1px solid #A7F3D0; background: #F0FDF4; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
                <h2 id="editor-ai-translation" style="font-family: var(--f-heading); color: var(--sys-action-primary, #064E5A); margin: 0 0 6px; font-size: 1.1rem;">
                    🌐 Traduction IA
                </h2>
                <p style="font-size: 0.85rem; color: #374151; margin: 0 0 16px;">
                    Traduis un texte du cours (description, résumé…) en aperçu éditable. Aucune sauvegarde automatique : copie le résultat où tu en as besoin.
                </p>
                <livewire:academy.translate-field-modal :course="$course" />
            </section>
        @endcan
    @endif

    {{-- ───────────────────────── Zone sensible : suppression du cours ───────────────────────── --}}
    @can('delete', $course)
        <section aria-labelledby="editor-danger"
                 style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: var(--sys-radius-md, 0.75rem); padding: 20px 24px;">
            <h2 id="editor-danger" style="font-family: var(--f-heading); color: #991B1B; margin: 0 0 8px; font-size: 1.05rem;">
                Supprimer la formation
            </h2>
            <p style="font-size: 0.85rem; color: #7F1D1D; margin: 0 0 14px;">
                Cette action supprime le cours et tout son contenu. Elle est réservée au propriétaire et à l'administration.
            </p>
            @if ($confirmingCourseDeletion)
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span style="font-size: 0.85rem; color: #7F1D1D; font-weight: 600;">Confirmer la suppression définitive ?</span>
                    <x-core::button type="button" wire:click="deleteCourse" variant="danger" size="sm">Oui, supprimer</x-core::button>
                    <x-core::button type="button" wire:click="cancelCourseDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                </div>
            @else
                <x-core::button type="button" wire:click="confirmCourseDeletion" variant="danger" size="sm">Supprimer ce cours</x-core::button>
            @endif
        </section>
    @endcan

</div>
