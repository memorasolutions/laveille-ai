{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai

    Écran de composition manuelle d'une actualité (Phase A - design doc "Actus - composition
    manuelle assistée", 2026-08-15). Structure seulement : réutilise le composant partagé
    "sélecteur d'actualités" (public/assets/admin/news-article-picker.js +
    news::admin.partials.news-article-picker) pour choisir UNE actualité déjà collectée -
    selectItem()/removeItem() sont volontairement RÉÉCRITS ci-dessous (après la fusion du mixin)
    pour n'autoriser qu'un seul élément sélectionné à la fois : "une actualité = une fiche"
    (design doc, périmètre Phase A). PAS de génération de prompt, PAS de dépôt d'image - phases
    suivantes.
--}}
@extends('backoffice::layouts.admin', ['title' => 'Composition d\'une actualité', 'subtitle' => 'Sélectionne une actualité collectée, compose sa fiche'])

@push('styles')
{{-- Composant partagé "sélecteur d'actualités" (recherche/filtres/tri/couleur), le même que
     celui utilisé par admin/concentre-builder et admin/objectif-video. --}}
<link rel="stylesheet" href="{{ asset('assets/admin/news-article-picker.css') }}?v={{ config('version.semver') }}">
{{-- Coquille "carte builder" (carte/bouton/titre de section), extraite du <style> inline commun
     à concentre-builder.blade.php et video-goal-builder.blade.php - voir le fichier pour le
     détail de cette extraction DRY (3e occurrence). --}}
<link rel="stylesheet" href="{{ asset('assets/admin/news-builder-shell.css') }}?v={{ config('version.semver') }}">
<style>
    /* Styles propres à cet écran (1re occurrence, pas de duplication à extraire) : panneau de
       composition (titre/résumé/texte source) et bandeau de l'actualité sélectionnée. */
    .nc-empty-panel { text-align:center; color:#57606f; padding:48px 24px; font-style:italic; }
    .nc-selected-banner { background:#ecfeff; border:1px solid #0B7285; border-radius:8px; padding:12px 14px; margin-bottom:14px; }
    .nc-selected-banner .nc-orig-title { font-weight:600; color:#064E5A; font-size:14px; line-height:1.4; }
    .nc-selected-banner .nc-meta { font-size:12px; color:#374151; margin-top:4px; }
    .nc-field { margin-bottom:16px; }
    .nc-field label { display:block; font-weight:700; font-size:13px; color:#064E5A; margin-bottom:6px; }
    .nc-field .nc-hint { font-weight:400; color:#6b7280; font-size:12px; margin-left:4px; }
    .nc-source-textarea { font-family:'SF Mono','Monaco','Consolas',monospace; font-size:12.5px; line-height:1.5; }
    .nc-status-ok { color:#059669; font-size:13px; font-weight:600; }
    .nc-status-error { color:#dc2626; font-size:13px; font-weight:600; }
    .nc-internal-badge { background:#fef3c7; color:#92400e; padding:2px 10px; border-radius:10px; font-size:11px; font-weight:700; }
    /* Fiche de preuve éditoriale (Phase B, design doc 2026-08-15 section 7) - carte par paire
       phrase/extrait, badge de décision fait/analyse. */
    .nc-proof-card { border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:10px; background:#fff; }
    .nc-proof-type { display:inline-block; font-size:10.5px; font-weight:800; letter-spacing:.04em; padding:2px 8px; border-radius:10px; margin-bottom:6px; }
    .nc-proof-type-fact { background:#dcfce7; color:#166534; }
    .nc-proof-type-analysis { background:#e0e7ff; color:#3730a3; }
    .nc-proof-statement { font-weight:600; color:#064E5A; font-size:13px; margin-bottom:4px; }
    .nc-proof-excerpt { font-style:italic; color:#374151; font-size:12.5px; background:#f8fafc; padding:6px 8px; border-radius:6px; }
    .nc-proof-remove { background:none; border:none; color:#dc2626; text-decoration:underline; cursor:pointer; padding:10px 4px; font-size:12px; min-height:44px; display:inline-flex; align-items:center; }
    .nc-proof-form { border-top:1px dashed #cbd5e1; padding-top:12px; margin-top:4px; }
    /* Standard d'images (Phase D, design doc 2026-08-15 section 5.3/5.4) - flux manuel, aucun
       indicateur de progression fictif. */
    .nc-image-preview { max-width:220px; border-radius:8px; border:1px solid #e2e8f0; display:block; margin-top:8px; }
    .nc-image-dropzone { border:2px dashed #cbd5e1; border-radius:8px; padding:14px; text-align:center; color:#6b7280; font-size:12.5px; }
</style>
@endpush

@section('content')
<div
    x-data="compositionBuilder({
        candidatesEndpoint: @js($candidatesEndpoint),
        showEndpointTemplate: @js($showEndpointTemplate),
        updateEndpointTemplate: @js($updateEndpointTemplate),
        deleteSourceTextEndpointTemplate: @js($deleteSourceTextEndpointTemplate),
        generatePromptEndpointTemplate: @js($generatePromptEndpointTemplate),
        proofPairsStoreEndpointTemplate: @js($proofPairsStoreEndpointTemplate),
        proofPairsDestroyEndpointTemplate: @js($proofPairsDestroyEndpointTemplate),
        generateImagePromptEndpointTemplate: @js($generateImagePromptEndpointTemplate),
        uploadImageEndpointTemplate: @js($uploadImageEndpointTemplate),
        articlesIndexUrl: @js($articlesIndexUrl),
    })"
    x-init="init()"
>
    <div class="cb-card">
        <h1 style="font-size:20px; font-weight:800; color:#064E5A; margin-bottom:6px;">🧩 Composition d'une actualité</h1>
        <p style="color:#374151; font-size:14px; margin:0 0 8px;">
            Choisis une actualité déjà collectée, ajuste son titre et son résumé publiés, et colle
            au besoin le texte complet de la source pour ton propre usage éditorial.
        </p>
        <p style="color:#92400e; font-size:12.5px; margin:0; display:flex; align-items:center; gap:6px;">
            <span class="nc-internal-badge">🔒 interne</span>
            Le texte source collé n'est jamais publié ni affiché publiquement - il reste réservé à l'administration et peut être supprimé à tout moment.
        </p>
        <div class="mt-2">
            <a :href="endpoints.articlesIndexUrl" class="cb-btn cb-btn-secondary" style="font-size:12px; padding:6px 12px; min-height:32px; text-decoration:none;">← Liste des articles (publication)</a>
        </div>
    </div>

    <div class="row">
        {{-- Colonne gauche : actualités disponibles (composant partagé, sélection forcée à 1 seul élément) --}}
        <div class="col-md-6">
            @include('news::admin.partials.news-article-picker', ['emptyStateText' => 'Aucune actualité collectée pour l\'instant.'])
        </div>

        {{-- Colonne droite : panneau de composition --}}
        <div class="col-md-6">
            <div class="cb-card">
                <div class="cb-section-title">
                    ✏️ Composition
                    <span class="cb-counter" x-show="selectedArticle" x-cloak x-text="selectedArticle?.is_published ? 'publiée' : 'brouillon'"></span>
                </div>

                <div class="nc-empty-panel" x-show="!selectedArticle && !loading.article" x-cloak>
                    Sélectionne une actualité à gauche (bouton « + Ajouter ») pour commencer la composition.
                </div>
                <div class="nc-empty-panel" x-show="loading.article" x-cloak>⏳ Chargement de l'actualité…</div>

                <template x-if="selectedArticle">
                    <div>
                        <div class="nc-selected-banner">
                            <div class="nc-orig-title" x-text="selectedArticle.title"></div>
                            <div class="nc-meta">
                                <a :href="selectedArticle.site_url" target="_blank" rel="noopener">🔗 Voir la fiche publique</a>
                                &nbsp;·&nbsp;
                                <button type="button" @click="removeItem()" style="background:none; border:none; color:#0B7285; text-decoration:underline; cursor:pointer; padding:0; font-size:12px;">Changer d'actualité</button>
                            </div>
                        </div>

                        <div class="nc-field">
                            <label for="nc-title">Titre publié</label>
                            <input id="nc-title" type="text" class="form-control" x-model="formSeoTitle" maxlength="255" placeholder="Titre affiché sur la fiche publique">
                        </div>

                        <div class="nc-field">
                            <label for="nc-summary">Résumé publié <span class="nc-hint">(court, affiché sur la fiche et les cartes)</span></label>
                            <textarea id="nc-summary" class="form-control" rows="4" x-model="formSummary" maxlength="2000" placeholder="Résumé court destiné à l'affichage public"></textarea>
                        </div>

                        <div class="nc-field">
                            <label for="nc-source">Texte complet de la source <span class="nc-hint">(interne, jamais publié)</span></label>
                            <textarea id="nc-source" class="form-control nc-source-textarea" rows="10" x-model="formSourceText" placeholder="Colle ici le texte intégral de l'article source, pour ton propre usage éditorial."></textarea>
                        </div>

                        <div class="nc-field">
                            <label for="nc-angle">Angle éditorial <span class="nc-hint">(optionnel, transmis au prompt de rédaction)</span></label>
                            <input id="nc-angle" type="text" class="form-control" x-model="formAngle" maxlength="500" placeholder="Ex. impact pour les PME québécoises">
                        </div>

                        <div class="nc-field">
                            <button type="button" class="cb-btn cb-btn-secondary" @click="generatePrompt()" :disabled="promptLoading || !formSourceText">
                                <span x-show="!promptLoading">🧠 Générer le prompt de rédaction</span>
                                <span x-show="promptLoading" x-cloak>⏳ Génération…</span>
                            </button>
                            <span class="nc-status-error" x-show="promptError" x-cloak x-text="promptError" style="display:block; margin-top:6px;"></span>
                            <template x-if="generatedPrompt">
                                <div style="margin-top:10px;">
                                    <textarea class="form-control nc-source-textarea" rows="12" readonly x-text="generatedPrompt" style="background:#f8fafc;"></textarea>
                                    <div class="mt-2">
                                        <button type="button" class="cb-btn cb-btn-secondary" @click="copyPrompt()">
                                            <span x-show="!promptCopied">📋 Copier le prompt</span>
                                            <span x-show="promptCopied" x-cloak>✓ Copié</span>
                                        </button>
                                        <span class="nc-hint">Colle-le ensuite dans ton outil d'IA externe, puis recolle son résultat dans le titre et le résumé ci-dessus.</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="cb-btn" @click="save()" :disabled="loading.saving">
                                <span x-show="!loading.saving">💾 Enregistrer</span>
                                <span x-show="loading.saving" x-cloak>⏳ Enregistrement…</span>
                            </button>
                            <button type="button"
                                    class="cb-btn cb-btn-secondary"
                                    style="color:#dc2626; border-color:#dc2626;"
                                    x-show="formSourceText"
                                    x-cloak
                                    :disabled="loading.deleting"
                                    @click="$dispatch('confirm-action', {
                                        title: 'Confirmer',
                                        message: 'Supprimer définitivement le texte source collé pour cette actualité ? Cette action est irréversible.',
                                        action: () => deleteSourceText()
                                    })">
                                <span x-show="!loading.deleting">🗑 Supprimer le texte source</span>
                                <span x-show="loading.deleting" x-cloak>⏳ Suppression…</span>
                            </button>
                            <span class="nc-status-ok" x-show="saveOk" x-cloak x-transition>✓ Enregistré</span>
                            <span class="nc-status-error" x-show="saveError" x-cloak x-text="saveError"></span>
                        </div>

                        <div class="nc-proof-section">
                            <div class="cb-section-title" style="font-size:14px; margin-top:20px;">🔍 Passages à vérifier</div>
                            <p class="nc-hint" style="display:block; margin:0 0 10px;">
                                Pour chaque passage risqué du résumé publié : colle la phrase, colle l'extrait exact de la
                                source qui l'appuie, et déclare-le « fait » (l'extrait doit être une sous-chaîne exacte
                                du texte source) ou « analyse » (ton propre liant éditorial, non vérifié).
                            </p>

                            <template x-if="!proofPairs.length">
                                <p class="nc-hint" style="display:block; font-style:italic;">Aucun passage vérifié pour l'instant.</p>
                            </template>

                            <div class="nc-proof-pair" x-show="proofPairs.length" x-cloak>
                                <template x-for="pair in proofPairs" :key="pair.id">
                                    <div class="nc-proof-card">
                                        <div class="nc-proof-type" :class="pair.type === 'fact' ? 'nc-proof-type-fact' : 'nc-proof-type-analysis'" x-text="pair.type === 'fact' ? 'FAIT' : 'ANALYSE'"></div>
                                        <div class="nc-proof-statement" x-text="pair.statement"></div>
                                        <div class="nc-proof-excerpt" x-text="'« ' + pair.excerpt + ' »'"></div>
                                        <button type="button" class="nc-proof-remove" @click="$dispatch('confirm-action', {
                                            title: 'Confirmer',
                                            message: 'Retirer ce passage de la fiche de preuve éditoriale ?',
                                            action: () => removeProofPair(pair.id)
                                        })">🗑 Retirer</button>
                                    </div>
                                </template>
                            </div>

                            <div class="nc-proof-form">
                                <div class="nc-field">
                                    <label for="nc-pair-statement">Phrase du résumé</label>
                                    <input id="nc-pair-statement" type="text" class="form-control" x-model="newPairStatement" maxlength="1000" placeholder="La phrase publiée qui doit être appuyée">
                                </div>
                                <div class="nc-field">
                                    <label for="nc-pair-excerpt">Extrait de la source</label>
                                    <textarea id="nc-pair-excerpt" class="form-control nc-source-textarea" rows="3" x-model="newPairExcerpt" maxlength="2000" placeholder="Copie-colle l'extrait exact du texte source"></textarea>
                                    <span class="nc-hint" x-show="newPairType === 'fact' && newPairExcerpt" x-cloak x-text="excerptFoundInSource() ? '✓ trouvé tel quel dans le texte source' : '⚠ introuvable tel quel dans le texte source'" :style="excerptFoundInSource() ? 'color:#059669' : 'color:#dc2626'"></span>
                                </div>
                                <div class="nc-field">
                                    <label for="nc-pair-type">Décision</label>
                                    <select id="nc-pair-type" class="form-control" x-model="newPairType" style="max-width:220px;">
                                        <option value="fact">Fait (sous-chaîne exacte exigée)</option>
                                        <option value="analysis">Analyse (liant éditorial)</option>
                                    </select>
                                </div>
                                <button type="button" class="cb-btn cb-btn-secondary" @click="addProofPair()" :disabled="pairSaving || !newPairStatement || !newPairExcerpt || (newPairType === 'fact' && !excerptFoundInSource())">
                                    <span x-show="!pairSaving">➕ Ajouter le passage</span>
                                    <span x-show="pairSaving" x-cloak>⏳ Ajout…</span>
                                </button>
                                <span class="nc-status-error" x-show="pairError" x-cloak x-text="pairError" style="display:block; margin-top:6px;"></span>
                            </div>
                        </div>

                        <div class="nc-image-section">
                            <div class="cb-section-title" style="font-size:14px; margin-top:20px;">🖼️ Image</div>
                            <p class="nc-hint" style="display:block; margin:0 0 10px;">
                                La génération d'image par IA n'est pas automatisée sur ce site : copie le prompt,
                                colle-le dans Gemini, puis dépose ici le fichier obtenu. La fiche s'enregistre et
                                se publie sans image - le dépôt remplace simplement l'illustration générée par défaut.
                            </p>

                            <template x-if="selectedArticle?.image_url">
                                <img :src="selectedArticle.image_url" alt="" class="nc-image-preview">
                            </template>

                            <div class="nc-field" style="margin-top:10px;">
                                <button type="button" class="cb-btn cb-btn-secondary" @click="copyImagePromptAndOpenGemini()" :disabled="imagePromptLoading">
                                    <span x-show="!imagePromptLoading">📋 Copier le prompt d'image et ouvrir Gemini</span>
                                    <span x-show="imagePromptLoading" x-cloak>⏳ Génération du prompt…</span>
                                </button>
                                <span class="nc-status-ok" x-show="imagePromptCopied" x-cloak x-transition>✓ Prompt copié, Gemini ouvert dans un nouvel onglet</span>
                                <span class="nc-status-error" x-show="imagePromptError" x-cloak x-text="imagePromptError" style="display:block; margin-top:6px;"></span>
                            </div>

                            <div class="nc-field">
                                <label for="nc-image-file">Déposer le fichier reçu de Gemini <span class="nc-hint">(jpeg, png ou webp)</span></label>
                                <div class="nc-image-dropzone">
                                    <input id="nc-image-file" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadImage($event.target.files[0])" :disabled="imageUploading">
                                    <div x-show="imageUploading" x-cloak>⏳ Traitement de l'image…</div>
                                </div>
                                <span class="nc-status-ok" x-show="imageUploadOk" x-cloak x-transition>✓ Image déposée et traitée (JPEG social 1200×630 + WebP)</span>
                                <span class="nc-status-error" x-show="imageUploadError" x-cloak x-text="imageUploadError" style="display:block; margin-top:6px;"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- Mixin partagé "sélecteur d'actualités" - voir public/assets/admin/. Chargé AVANT le script
     inline ci-dessous : window.NewsArticlePicker doit exister avant init(). --}}
<script src="{{ asset('assets/admin/news-article-picker.js') }}?v={{ config('version.semver') }}" defer></script>

<script>
function compositionBuilder(opts) {
    const state = {
        endpoints: {
            candidates: opts.candidatesEndpoint,
            showTemplate: opts.showEndpointTemplate,
            updateTemplate: opts.updateEndpointTemplate,
            deleteSourceTextTemplate: opts.deleteSourceTextEndpointTemplate,
            generatePromptTemplate: opts.generatePromptEndpointTemplate,
            proofPairsStoreTemplate: opts.proofPairsStoreEndpointTemplate,
            proofPairsDestroyTemplate: opts.proofPairsDestroyEndpointTemplate,
            generateImagePromptTemplate: opts.generateImagePromptEndpointTemplate,
            uploadImageTemplate: opts.uploadImageEndpointTemplate,
            articlesIndexUrl: opts.articlesIndexUrl,
        },
        loading: { news: false, article: false, saving: false, deleting: false },
        selectedArticle: null,
        formSeoTitle: '',
        formSummary: '',
        formSourceText: '',
        saveOk: false,
        saveError: '',

        // Phase B (design doc 2026-08-15, sections 5.1 et 7) : génération du prompt de
        // rédaction et fiche de preuve éditoriale.
        formAngle: '',
        generatedPrompt: '',
        promptLoading: false,
        promptError: '',
        promptCopied: false,
        proofPairs: [],
        newPairStatement: '',
        newPairExcerpt: '',
        newPairType: 'fact',
        pairError: '',
        pairSaving: false,

        // Phase D (design doc 2026-08-15, sections 5.3 et 5.4) : prompt d'image (jamais de
        // bouton "générer", flux manuel Gemini) et dépôt/validation du fichier rapporté.
        imagePromptLoading: false,
        imagePromptCopied: false,
        imagePromptError: '',
        imageUploading: false,
        imageUploadOk: false,
        imageUploadError: '',

        init() {
            this.fetchNews();
        },

        // fetchNews(), availableItems/filteredAvailable/groupedAvailable (getters), état
        // recherche/filtres/tri/couleurs et colorForItem()/setColor() proviennent du mixin
        // partagé NewsArticlePicker() fusionné plus bas (DRY avec concentre-builder et
        // video-goal-builder). selectItem()/removeItem()/selectAllVisible() sont RÉÉCRITS
        // juste après la fusion, ci-dessous, pour imposer la règle "une actualité = une fiche".

        async loadArticle(id) {
            const item = this.itemById(id);
            if (!item) return;
            this.loading.article = true;
            this.saveError = '';
            this.saveOk = false;
            try {
                const url = this.endpoints.showTemplate.replace('__SLUG__', item.slug);
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.saveError = 'Impossible de charger cette actualité (HTTP ' + res.status + ').';
                    return;
                }
                const data = await res.json();
                this.selectedArticle = data;
                this.formSeoTitle = data.seo_title || data.title || '';
                this.formSummary = data.summary || '';
                this.formSourceText = data.internal_source_text || '';
                this.proofPairs = data.editorial_proof_pairs || [];
                this.formAngle = '';
                this.generatedPrompt = '';
                this.promptError = '';
                this.newPairStatement = '';
                this.newPairExcerpt = '';
                this.newPairType = 'fact';
                this.pairError = '';
                this.imagePromptError = '';
                this.imagePromptCopied = false;
                this.imageUploadOk = false;
                this.imageUploadError = '';
            } catch (e) {
                this.saveError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.article = false;
            }
        },

        // Normalisation MIROIR de EditorialProofNormalizer::normalize() (PHP, source de vérité
        // - cette copie client n'est qu'un confort d'interface, la validation qui compte est
        // celle du serveur dans addProofPair()) : espaces (dont insécables) réduits à un seul,
        // apostrophes typographiques ramenées à l'apostrophe droite, extrémités nettoyées.
        normalizeForCompare(text) {
            return String(text || '')
                .replace(/[’‘]/g, "'")
                .replace(/[  ]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        excerptFoundInSource() {
            const needle = this.normalizeForCompare(this.newPairExcerpt);
            if (!needle) return false;
            return this.normalizeForCompare(this.formSourceText).includes(needle);
        },

        async generatePrompt() {
            if (!this.selectedArticle || !this.formSourceText) return;
            this.promptLoading = true;
            this.promptError = '';
            this.generatedPrompt = '';
            try {
                const url = this.endpoints.generatePromptTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ angle: this.formAngle }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.promptError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.generatedPrompt = data.prompt;
            } catch (e) {
                this.promptError = 'Erreur réseau : ' + e.message;
            } finally {
                this.promptLoading = false;
            }
        },

        async copyPrompt() {
            if (!this.generatedPrompt) return;
            try {
                await navigator.clipboard.writeText(this.generatedPrompt);
                this.promptCopied = true;
                setTimeout(() => { this.promptCopied = false; }, 2500);
            } catch (e) {
                this.promptError = 'Copie impossible, sélectionne et copie manuellement.';
            }
        },

        async addProofPair() {
            if (!this.selectedArticle || !this.newPairStatement || !this.newPairExcerpt) return;
            this.pairSaving = true;
            this.pairError = '';
            try {
                const url = this.endpoints.proofPairsStoreTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        statement: this.newPairStatement,
                        excerpt: this.newPairExcerpt,
                        type: this.newPairType,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pairError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.proofPairs = data.pairs;
                this.newPairStatement = '';
                this.newPairExcerpt = '';
                this.newPairType = 'fact';
            } catch (e) {
                this.pairError = 'Erreur réseau : ' + e.message;
            } finally {
                this.pairSaving = false;
            }
        },

        async removeProofPair(pairId) {
            if (!this.selectedArticle) return;
            this.pairError = '';
            try {
                const url = this.endpoints.proofPairsDestroyTemplate.replace('__SLUG__', this.selectedArticle.slug).replace('__PAIR_ID__', pairId);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pairError = 'Impossible de retirer ce passage (HTTP ' + res.status + ').';
                    return;
                }
                this.proofPairs = data.pairs;
            } catch (e) {
                this.pairError = 'Erreur réseau : ' + e.message;
            }
        },

        // Phase D (design doc 2026-08-15, section 5.3) : génère le prompt d'image côté serveur
        // (style fixe + titre + angle), le copie dans le presse-papiers, PUIS ouvre Gemini dans
        // un nouvel onglet - libellé assumé "copier le prompt et ouvrir Gemini", aucun bouton
        // "générer l'image" et aucun indicateur de progression fictif (l'appli ne sait rien de
        // l'autre onglet une fois ouvert).
        async copyImagePromptAndOpenGemini() {
            if (!this.selectedArticle) return;
            this.imagePromptLoading = true;
            this.imagePromptError = '';
            this.imagePromptCopied = false;
            try {
                const url = this.endpoints.generateImagePromptTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ angle: this.formAngle }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.imagePromptError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                await navigator.clipboard.writeText(data.prompt);
                window.open('https://gemini.google.com/app', '_blank', 'noopener');
                this.imagePromptCopied = true;
                setTimeout(() => { this.imagePromptCopied = false; }, 4000);
            } catch (e) {
                this.imagePromptError = 'Erreur : ' + e.message;
            } finally {
                this.imagePromptLoading = false;
            }
        },

        // Dépôt manuel du fichier rapporté de Gemini (design doc section 5.3/5.4). Le flux ne
        // bloque JAMAIS la composition : cette action est indépendante de save(), la fiche reste
        // enregistrable/publiable sans image (l'image de repli existante fait foi en attendant).
        async uploadImage(file) {
            if (!this.selectedArticle || !file) return;
            this.imageUploading = true;
            this.imageUploadOk = false;
            this.imageUploadError = '';
            try {
                const url = this.endpoints.uploadImageTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const formData = new FormData();
                formData.append('image', file);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) {
                    this.imageUploadError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.selectedArticle.image_url = data.image_url;
                this.imageUploadOk = true;
                setTimeout(() => { this.imageUploadOk = false; }, 4000);
            } catch (e) {
                this.imageUploadError = 'Erreur réseau : ' + e.message;
            } finally {
                this.imageUploading = false;
            }
        },

        async save() {
            if (!this.selectedArticle) return;
            this.loading.saving = true;
            this.saveError = '';
            this.saveOk = false;
            try {
                const url = this.endpoints.updateTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        seo_title: this.formSeoTitle,
                        summary: this.formSummary,
                        internal_source_text: this.formSourceText,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.saveError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.saveOk = true;
                setTimeout(() => { this.saveOk = false; }, 2500);
                const item = this.itemById(this.selectedIds[0]);
                if (item) { item.already_used = this.formSourceText.trim() !== ''; }
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('toast', { type: 'success', message: 'Composition enregistrée.' });
                }
            } catch (e) {
                this.saveError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.saving = false;
            }
        },

        async deleteSourceText() {
            if (!this.selectedArticle) return;
            this.loading.deleting = true;
            this.saveError = '';
            try {
                const url = this.endpoints.deleteSourceTextTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.saveError = 'Impossible de supprimer le texte source (HTTP ' + res.status + ').';
                    return;
                }
                this.formSourceText = '';
                this.selectedArticle.internal_source_text = null;
                const item = this.itemById(this.selectedIds[0]);
                if (item) { item.already_used = false; }
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('toast', { type: 'success', message: 'Texte source supprimé.' });
                }
            } catch (e) {
                this.saveError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.deleting = false;
            }
        },
    };

    // Fusionne le mixin partagé AVANT le `return` (Alpine.reactive() enveloppe la valeur
    // RETOURNÉE par cette factory x-data) - voir le commentaire détaillé en tête de
    // public/assets/admin/news-article-picker.js pour les 2 pièges évités (defineProperties
    // plutôt que spread, fusion hors de init()).
    // defaultSortMode: 'date' - le regroupement par acteur (mixin par défaut) n'apporte rien ici
    // : contrairement au Concentré, une seule actualité est composée à la fois.
    Object.defineProperties(state, Object.getOwnPropertyDescriptors(NewsArticlePicker({
        defaultSortMode: 'date',
        fetchStrategy: (ctx) => ({ method: 'GET', url: ctx.endpoints.candidates }),
    })));

    // RÉÉCRITURE volontaire, APRÈS la fusion du mixin (pour primer sur ses versions) : cet écran
    // impose "une actualité = une fiche" (design doc "Actus - composition manuelle assistée"
    // 2026-08-15, périmètre Phase A) - le mixin partagé autorise nativement une sélection
    // multiple (Concentré, Objectif vidéo), ce qui serait un contresens ici.
    state.selectItem = function (id) {
        this.selectedIds = [id];
        this.loadArticle(id);
    };
    state.removeItem = function () {
        this.selectedIds = [];
        this.selectedArticle = null;
        this.formSeoTitle = '';
        this.formSummary = '';
        this.formSourceText = '';
        this.saveError = '';
        this.saveOk = false;
    };
    // "Tout cocher" n'a pas de sens sur un écran à sélection unique - remplacé par un simple
    // rappel plutôt que de laisser le bouton partagé sélectionner plusieurs actualités.
    state.selectAllVisible = function () {
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('toast', { type: 'info', message: 'Une seule actualité à la fois : clique « + Ajouter » sur celle que tu veux composer.' });
        }
    };

    return state;
}
</script>
@endsection
