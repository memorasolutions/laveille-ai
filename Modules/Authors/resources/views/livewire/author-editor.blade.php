<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    AuthorEditor view — éditeur d'article public-facing (mini-site auteur /@slug/post-slug).
    Migration markdown-stub → Tiptap (réutilise Alpine.data('tiptapEditor', ...) de
    resources/js/tiptap-frontend.js, DÉJÀ utilisé côté front par editor::components.tiptap-light).
    Le champ Livewire "body_markdown" reçoit désormais le HTML produit par Tiptap : le backend
    (GithubFlavoredMarkdownConverter html_input=allow) le traite déjà tel quel, sans casse.
--}}
@once
    @push('head')
        @vite('resources/js/tiptap-frontend.js')
    @endpush
@endonce

<div class="lv-author-editor">
    {{-- Statut d'auto-sauvegarde --}}
    @if($autoSaveStatus !== 'idle')
        <p class="lv-ae-autosave lv-ae-autosave--{{ $autoSaveStatus }}" role="status" aria-live="polite">
            @if($autoSaveStatus === 'saving') Enregistrement du brouillon…
            @elseif($autoSaveStatus === 'saved') ✓ Brouillon enregistré
            @elseif($autoSaveStatus === 'error') ⚠️ Erreur d'enregistrement
            @endif
        </p>
    @endif

    {{-- Titre --}}
    <div class="lv-ae-field">
        <label for="ae-title" class="lv-ae-label">Titre de l'article</label>
        <input type="text" id="ae-title" wire:model.live.blur="title" maxlength="255"
               class="lv-ae-input lv-ae-input--title" placeholder="Entrez un titre percutant…">
        @error('title') <p class="lv-ae-error" role="alert">{{ $message }}</p> @enderror
    </div>

    {{-- Extrait --}}
    <div class="lv-ae-field">
        <label for="ae-excerpt" class="lv-ae-label">Extrait (optionnel, max 280 caractères)</label>
        <textarea id="ae-excerpt" wire:model.live.blur="excerpt" rows="2" maxlength="280"
                  class="lv-ae-input lv-ae-input--excerpt"
                  placeholder="Résumez votre article en quelques mots…"></textarea>
        <p class="lv-ae-hint">{{ 280 - mb_strlen($excerpt) }} caractères restants</p>
    </div>

    {{-- Statut + visibilité --}}
    <div class="lv-ae-row">
        <div class="lv-ae-field lv-ae-field--inline">
            <span class="lv-ae-label">Statut</span>
            <span class="lv-ae-badge lv-ae-badge--{{ $status }}">
                @switch($status)
                    @case('draft') Brouillon @break
                    @case('scheduled') Programmé @break
                    @case('published') Publié @break
                    @case('archived') Archivé @break
                    @default {{ ucfirst($status) }}
                @endswitch
            </span>
        </div>

        <div class="lv-ae-field lv-ae-field--inline">
            <label for="ae-visibility" class="lv-ae-label">Visibilité</label>
            <select id="ae-visibility" wire:model.live="visibility" class="lv-ae-select">
                <option value="public">Public</option>
                <option value="subscribers">Abonnés</option>
                <option value="premium">Premium</option>
            </select>
        </div>
    </div>

    {{-- Tags --}}
    <div class="lv-ae-field">
        <span class="lv-ae-label" id="ae-tags-label">Tags</span>
        @if(count($tags) > 0)
            <ul class="lv-ae-tags" aria-labelledby="ae-tags-label">
                @foreach($tags as $tag)
                    <li class="lv-ae-tag">
                        {{ $tag }}
                        <button type="button" wire:click="removeTag('{{ $tag }}')"
                                class="lv-ae-tag-remove" aria-label="Retirer le tag {{ $tag }}">×</button>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="lv-ae-tag-add">
            <label for="ae-tags-input" class="sr-only">Ajouter un tag</label>
            <input type="text" id="ae-tags-input" wire:model.live.blur="tagsInput"
                   class="lv-ae-input" placeholder="Ajouter un tag…">
            <button type="button" wire:click="addTag" class="lv-ae-btn lv-ae-btn--ghost">Ajouter</button>
        </div>
    </div>

    {{-- Éditeur Tiptap (contenu de l'article) --}}
    <div class="lv-ae-field">
        <span class="lv-ae-label" id="ae-body-label">Contenu de l'article</span>
        <div wire:ignore wire:key="ae-tiptap-editor" class="lv-ae-tiptap" x-data="tiptapEditor({ content: {{ json_encode($body_markdown ?: '') }}, placeholder: 'Racontez votre histoire…' })">
            <div class="lv-ae-tiptap-toolbar" role="toolbar" aria-label="Mise en forme du texte" aria-labelledby="ae-body-label">
                <button type="button" class="lv-ae-tt-btn" :class="isActive('bold') && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleBold().run())" aria-label="Gras">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6V4zm0 8h9a4 4 0 014 4 4 4 0 01-4 4H6v-8z"/></svg>
                </button>
                <button type="button" class="lv-ae-tt-btn" :class="isActive('italic') && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleItalic().run())" aria-label="Italique">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
                </button>
                <span class="lv-ae-tt-sep" aria-hidden="true"></span>
                <button type="button" class="lv-ae-tt-btn lv-ae-tt-btn--text" :class="isActive('heading', {level:1}) && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:1}).run())" aria-label="Titre niveau 1">H1</button>
                <button type="button" class="lv-ae-tt-btn lv-ae-tt-btn--text" :class="isActive('heading', {level:2}) && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:2}).run())" aria-label="Titre niveau 2">H2</button>
                <button type="button" class="lv-ae-tt-btn lv-ae-tt-btn--text" :class="isActive('heading', {level:3}) && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:3}).run())" aria-label="Titre niveau 3">H3</button>
                <span class="lv-ae-tt-sep" aria-hidden="true"></span>
                <button type="button" class="lv-ae-tt-btn" :class="isActive('blockquote') && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleBlockquote().run())" aria-label="Citation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C9.591 11.69 11 13.166 11 15a3 3 0 01-3 3c-1.305 0-2.497-.637-3.417-1.679zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C19.591 11.69 21 13.166 21 15a3 3 0 01-3 3c-1.305 0-2.497-.637-3.417-1.679z"/></svg>
                </button>
                <button type="button" class="lv-ae-tt-btn" :class="isActive('bulletList') && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleBulletList().run())" aria-label="Liste à puces">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="4" cy="6" r="1.5" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1.5" fill="currentColor" stroke="none"/><line x1="9" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="9" y1="18" x2="21" y2="18"/></svg>
                </button>
                <button type="button" class="lv-ae-tt-btn" :class="isActive('orderedList') && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleOrderedList().run())" aria-label="Liste numérotée">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="2" y="8" fill="currentColor" stroke="none" font-size="8" font-weight="700">1</text><text x="2" y="14" fill="currentColor" stroke="none" font-size="8" font-weight="700">2</text><text x="2" y="20" fill="currentColor" stroke="none" font-size="8" font-weight="700">3</text></svg>
                </button>
                <span class="lv-ae-tt-sep" aria-hidden="true"></span>
                <button type="button" class="lv-ae-tt-btn" :class="isActive('link') && 'is-active'"
                        @mousedown.prevent="setLink()" aria-label="Insérer un lien">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                </button>
                <button type="button" class="lv-ae-tt-btn" :class="isActive('code') && 'is-active'"
                        @mousedown.prevent="cmd(() => editor.chain().focus().toggleCode().run())" aria-label="Code en ligne">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </button>
                <button type="button" class="lv-ae-tt-btn"
                        @mousedown.prevent="cmd(() => editor.chain().focus().setHorizontalRule().run())" aria-label="Ligne horizontale">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
            </div>

            <div x-ref="editorContent" class="lv-ae-tiptap-content" aria-labelledby="ae-body-label"></div>
            <input type="hidden" x-ref="hiddenInput" wire:model.live="body_markdown">
        </div>
        @error('body_markdown') <p class="lv-ae-error" role="alert">{{ $message }}</p> @enderror
    </div>

    {{-- Programmation --}}
    @if($status !== 'published')
        <div class="lv-ae-schedule">
            <label for="ae-scheduled-at" class="lv-ae-label">Programmer la publication</label>
            <div class="lv-ae-schedule-row">
                <input type="datetime-local" id="ae-scheduled-at" wire:model.live="scheduledAt" class="lv-ae-input">
                <button type="button" wire:click="schedule" wire:loading.attr="disabled" wire:target="schedule"
                        class="lv-ae-btn lv-ae-btn--ghost">Programmer</button>
            </div>
            @error('scheduledAt') <p class="lv-ae-error" role="alert">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- Actions --}}
    <div class="lv-ae-actions">
        <button type="button" wire:click="autoSave" wire:loading.attr="disabled" wire:target="autoSave"
                class="lv-ae-btn lv-ae-btn--ghost">Enregistrer le brouillon</button>

        <button type="button" wire:click="publish" wire:loading.attr="disabled" wire:target="publish"
                class="lv-ae-btn lv-ae-btn--primary">
            <span wire:loading.remove wire:target="publish">Publier</span>
            <span wire:loading wire:target="publish">Publication…</span>
        </button>

        @if($post)
            <a href="{{ $this->previewUrl() }}" target="_blank" rel="noopener" class="lv-ae-btn lv-ae-btn--link">Prévisualiser</a>
        @endif
    </div>
</div>

@once
    @push('styles')
    <style>
    .lv-author-editor { font-family:'DM Sans',sans-serif; color:#1A1D23; max-width:820px; }
    .lv-ae-autosave { font-size:13px; margin:0 0 16px; padding:6px 12px; border-radius:8px; display:inline-block; }
    .lv-ae-autosave--saving { background:#FEF5ED; color:#7C2D12; }
    .lv-ae-autosave--saved { background:rgba(6,78,90,0.08); color:var(--c-primary,#064E5A); }
    .lv-ae-autosave--error { background:#FEF2F2; color:#9B1C1C; }
    .lv-ae-field { margin-bottom:20px; }
    .lv-ae-row { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:20px; }
    .lv-ae-field--inline { margin-bottom:0; }
    .lv-ae-label { display:block; font-weight:600; font-size:13px; color:#52586a; margin-bottom:6px; font-family:'Plus Jakarta Sans',sans-serif; }
    .lv-ae-input, .lv-ae-select { width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:15px; color:#1A1D23; background:#fff; }
    .lv-ae-input--title { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.3rem; font-weight:700; }
    .lv-ae-input--excerpt { resize:vertical; }
    .lv-ae-input:focus-visible, .lv-ae-select:focus-visible { outline:3px solid var(--c-primary,#064E5A); outline-offset:1px; border-color:var(--c-primary,#064E5A); }
    .lv-ae-hint { margin:4px 0 0; font-size:12px; color:#52586a; text-align:right; }
    .lv-ae-error { margin:6px 0 0; font-size:13px; color:#9B1C1C; font-weight:600; }
    .lv-ae-badge { display:inline-block; padding:5px 14px; border-radius:999px; font-size:13px; font-weight:700; }
    .lv-ae-badge--draft { background:#F3F4F6; color:#374151; }
    .lv-ae-badge--scheduled { background:#FEF5ED; color:#7C2D12; }
    .lv-ae-badge--published { background:rgba(6,78,90,0.1); color:var(--c-primary,#064E5A); }
    .lv-ae-badge--archived { background:#F3F4F6; color:#52586a; }
    .lv-ae-tags { list-style:none; margin:0 0 10px; padding:0; display:flex; flex-wrap:wrap; gap:8px; }
    .lv-ae-tag { display:inline-flex; align-items:center; gap:6px; background:var(--c-primary,#064E5A); color:#fff; padding:6px 12px; border-radius:999px; font-size:13px; font-weight:600; }
    .lv-ae-tag-remove { background:none; border:none; color:#fff; cursor:pointer; font-size:16px; line-height:1; min-width:24px; min-height:24px; padding:0; }
    .lv-ae-tag-remove:focus-visible { outline:2px solid #fff; outline-offset:2px; border-radius:4px; }
    .lv-ae-tag-add { display:flex; gap:8px; }
    .lv-ae-tag-add .lv-ae-input { flex:1; }
    .lv-ae-tiptap { border:2px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    .lv-ae-tiptap-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:2px; padding:8px 10px; background:#f8fafc; border-bottom:2px solid #e5e7eb; }
    .lv-ae-tt-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; min-width:24px; min-height:24px; border:none; border-radius:8px; background:transparent; color:#374151; cursor:pointer; transition:all .15s ease; }
    .lv-ae-tt-btn:hover { background:rgba(6,78,90,0.08); color:var(--c-primary,#064E5A); }
    .lv-ae-tt-btn.is-active { background:var(--c-primary,#064E5A); color:#fff; }
    .lv-ae-tt-btn:focus-visible { outline:3px solid var(--c-primary,#064E5A); outline-offset:2px; }
    .lv-ae-tt-btn--text { font-size:13px; font-weight:700; font-family:'DM Sans',sans-serif; width:auto; padding:0 10px; }
    .lv-ae-tt-sep { width:1px; height:22px; background:#e2e8f0; margin:0 4px; flex-shrink:0; }
    .lv-ae-tiptap-content { min-height:360px; padding:16px 18px; outline:none; font-size:16px; line-height:1.7; }
    .lv-ae-tiptap-content .tiptap p.is-editor-empty:first-child::before { content:attr(data-placeholder); color:#9ca3af; float:left; height:0; pointer-events:none; }
    .lv-ae-tiptap-content h1 { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.7em; font-weight:700; margin:0.8em 0 0.4em; }
    .lv-ae-tiptap-content h2 { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.35em; font-weight:700; margin:0.7em 0 0.35em; }
    .lv-ae-tiptap-content h3 { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.15em; font-weight:700; margin:0.6em 0 0.3em; }
    .lv-ae-tiptap-content p { margin:0 0 0.8em; }
    .lv-ae-tiptap-content ul, .lv-ae-tiptap-content ol { padding-left:1.5em; margin:0 0 0.8em; }
    .lv-ae-tiptap-content blockquote { border-left:4px solid var(--c-primary,#064E5A); padding:0.5em 1em; margin:1em 0; background:rgba(6,78,90,0.04); border-radius:0 8px 8px 0; color:#4b5563; }
    .lv-ae-tiptap-content code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.9em; font-family:ui-monospace,Menlo,monospace; }
    .lv-ae-tiptap-content a { color:var(--c-primary,#064E5A); text-decoration:underline; }
    .lv-ae-tiptap-content hr { border:none; border-top:2px solid #e5e7eb; margin:1.5em 0; }
    .lv-ae-schedule { background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:20px; }
    .lv-ae-schedule-row { display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; }
    .lv-ae-schedule-row .lv-ae-input { max-width:280px; }
    .lv-ae-actions { display:flex; flex-wrap:wrap; gap:12px; padding-top:8px; }
    .lv-ae-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 22px; border-radius:999px; font-weight:700; font-size:14px; font-family:'DM Sans',sans-serif; cursor:pointer; border:2px solid transparent; text-decoration:none; }
    .lv-ae-btn--primary { background:var(--c-primary,#064E5A); color:#fff; }
    .lv-ae-btn--primary:hover { background:#053e47; }
    .lv-ae-btn--ghost { background:#fff; color:var(--c-primary,#064E5A); border-color:var(--c-primary,#064E5A); }
    .lv-ae-btn--ghost:hover { background:rgba(6,78,90,0.06); }
    .lv-ae-btn--link { background:transparent; color:var(--c-primary,#064E5A); text-decoration:underline; min-height:44px; }
    .lv-ae-btn:focus-visible { outline:3px solid var(--c-primary,#064E5A); outline-offset:2px; }
    .lv-ae-btn:disabled { opacity:0.6; cursor:not-allowed; }
    </style>
    @endpush
@endonce
