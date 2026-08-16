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
</style>
@endpush

@section('content')
<div
    x-data="compositionBuilder({
        candidatesEndpoint: @js($candidatesEndpoint),
        showEndpointTemplate: @js($showEndpointTemplate),
        updateEndpointTemplate: @js($updateEndpointTemplate),
        deleteSourceTextEndpointTemplate: @js($deleteSourceTextEndpointTemplate),
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
            articlesIndexUrl: opts.articlesIndexUrl,
        },
        loading: { news: false, article: false, saving: false, deleting: false },
        selectedArticle: null,
        formSeoTitle: '',
        formSummary: '',
        formSourceText: '',
        saveOk: false,
        saveError: '',

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
            } catch (e) {
                this.saveError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.article = false;
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
