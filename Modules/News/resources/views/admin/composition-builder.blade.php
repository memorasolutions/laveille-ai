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
    /* Avertissement non bloquant sur l'acquisition automatique (design doc composition manuelle,
       2026-08-17) - mêmes tokens que les badges internes, pour rester un avertissement, pas une
       erreur. */
    .nc-status-warning { color:#92400e; font-size:12.5px; font-weight:600; background:#fef3c7; padding:6px 8px; border-radius:6px; }
    .nc-internal-badge { background:#fef3c7; color:#92400e; padding:2px 10px; border-radius:10px; font-size:11px; font-weight:700; }
    .nc-draft-badge { color:#92400e; font-weight:700; font-size:12px; }
    /* Écran minimal (panel "club des sages", 2026-08-15) - filet de secours replié par défaut :
       titre/résumé publiés, preuve éditoriale, image et bouton Enregistrer restent accessibles
       sans être imposés au parcours principal (source + génération du prompt). */
    .nc-manual-details { margin-top:22px; border-top:1px solid #e2e8f0; padding-top:14px; }
    .nc-manual-details > summary { cursor:pointer; font-weight:700; color:#064E5A; font-size:14px; list-style:none; display:flex; align-items:center; gap:8px; min-height:44px; }
    .nc-manual-details > summary::-webkit-details-marker { display:none; }
    .nc-manual-details > summary::before { content:'▶'; font-size:11px; color:#0B7285; }
    .nc-manual-details[open] > summary::before { content:'▼'; }
    .nc-manual-details > summary:focus-visible { outline:3px solid #0B7285; outline-offset:2px; }
    .nc-manual-details[open] > summary { margin-bottom:10px; }
    /* Fiche de preuve éditoriale (Phase B, design doc 2026-08-15 section 7) - carte par paire
       phrase/extrait, badge de décision fait/analyse. */
    .nc-proof-card { border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:10px; background:#fff; }
    .nc-proof-type { display:inline-block; font-size:10.5px; font-weight:800; letter-spacing:.04em; padding:2px 8px; border-radius:10px; margin-bottom:6px; }
    .nc-proof-type-fact { background:#dcfce7; color:#166534; }
    .nc-proof-type-analysis { background:#e0e7ff; color:#3730a3; }
    /* Lot 4a (design doc 2026-09-03, section 2.7) - 3e type de paire, « fait primaire » (citation
       exacte de l'original, distincte du texte source collé pour l'agent). */
    .nc-proof-type-primary { background:#fef3c7; color:#92400e; }
    /* Lot 4a - nouveau panneau toujours visible (title/preuves/crédit/nature/niveau), positionné
       hors du <details> "filet de secours" (design doc section 2.7). Même séparation visuelle
       (bordure supérieure) que .nc-manual-details, sans dupliquer sa mécanique de repli. */
    .nc-composition-panel { margin-top:18px; border-top:1px solid #e2e8f0; padding-top:14px; }
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
        createDraftEndpoint: @js($createDraftEndpoint),
        showEndpointTemplate: @js($showEndpointTemplate),
        updateEndpointTemplate: @js($updateEndpointTemplate),
        deleteSourceTextEndpointTemplate: @js($deleteSourceTextEndpointTemplate),
        fetchSourceEndpointTemplate: @js($fetchSourceEndpointTemplate),
        generatePromptEndpointTemplate: @js($generatePromptEndpointTemplate),
        proofPairsStoreEndpointTemplate: @js($proofPairsStoreEndpointTemplate),
        proofPairsDestroyEndpointTemplate: @js($proofPairsDestroyEndpointTemplate),
        generateImagePromptEndpointTemplate: @js($generateImagePromptEndpointTemplate),
        uploadImageEndpointTemplate: @js($uploadImageEndpointTemplate),
        publishEndpointTemplate: @js($publishEndpointTemplate),
        markReviewedEndpointTemplate: @js($markReviewedEndpointTemplate),
        articlesIndexUrl: @js($articlesIndexUrl),
        {{-- Lot 4b (design doc 2026-09-03, section 2.5/2.7) - outils liés, action immédiate. --}}
        relatedToolsStoreEndpointTemplate: @js($relatedToolsStoreEndpointTemplate),
        relatedToolsDestroyEndpointTemplate: @js($relatedToolsDestroyEndpointTemplate),
        availableTools: @js($availableTools),
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

    {{-- Améliorations en attente (2026-08-17), point 1 - « Créer une fiche depuis un lien » : le
         premier cycle /actu2 réel a prouvé qu'un post X ou une annonce hors collecte RSS n'a
         aucune fiche à composer tant qu'elle n'est pas créée ici. Même implémentation serveur
         que `php artisan news:create-draft` (NewsArticle::createManualDraft(), DRY strict). --}}
    <div class="cb-card">
        <div class="cb-section-title">➕ Créer une fiche depuis un lien</div>
        <p class="nc-hint" style="display:block; margin:0 0 10px;">
            Pour un lien qui n'a pas été collecté automatiquement (post X, communiqué...) - crée un
            brouillon vide, déjà sélectionné, prêt pour /actu2.
        </p>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="url"
                   class="form-control"
                   x-model="createDraftUrl"
                   :disabled="createDraftLoading"
                   placeholder="https://…"
                   style="max-width:420px;"
                   @keydown.enter="createDraft()">
            <button type="button" class="cb-btn" @click="createDraft()" :disabled="createDraftLoading || !createDraftUrl">
                <span x-show="!createDraftLoading">➕ Créer la fiche</span>
                <span x-show="createDraftLoading" x-cloak>⏳ Création…</span>
            </button>
        </div>
        <span class="nc-status-error" x-show="createDraftError" x-cloak x-text="createDraftError" style="display:block; margin-top:6px;"></span>
        <template x-if="createDraftPrompt">
            <div style="margin-top:10px;">
                <span class="nc-status-ok" x-text="createDraftCreatedNew ? '✓ Fiche créée et sélectionnée.' : '✓ Fiche déjà existante, sélectionnée.'"></span>
                <div class="d-flex flex-wrap gap-2 align-items-center" style="margin-top:6px;">
                    <code x-text="createDraftPrompt" style="background:#f8fafc; padding:6px 8px; border-radius:6px; font-size:12.5px;"></code>
                    <button type="button" class="cb-btn cb-btn-secondary" @click="copyCreateDraftPrompt()">
                        <span x-show="!createDraftPromptCopied">📋 Copier</span>
                        <span x-show="createDraftPromptCopied" x-cloak>✓ Copié</span>
                    </button>
                </div>
            </div>
        </template>
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
                                <a :href="selectedArticle.source_url" target="_blank" rel="noopener" x-show="selectedArticle.source_url" x-cloak>📰 Lire l'article original</a>
                                <template x-if="selectedArticle.source_url"><span>&nbsp;·&nbsp;</span></template>
                                <a :href="selectedArticle.site_url" target="_blank" rel="noopener" x-show="selectedArticle.is_published" x-cloak>🔗 Voir la fiche publique</a>
                                <span class="nc-draft-badge" x-show="!selectedArticle.is_published" x-cloak>📝 Brouillon - pas encore publié</span>
                                &nbsp;·&nbsp;
                                <button type="button" @click="removeItem()" style="background:none; border:none; color:#0B7285; text-decoration:underline; cursor:pointer; padding:0; font-size:12px;">Changer d'actualité</button>
                            </div>
                        </div>

                        {{-- Écran minimal (panel "club des sages", 2026-08-15) : le texte source et le
                             bouton de génération du prompt sont la seule action attendue de ce parcours -
                             tout le reste (édition manuelle des champs publiés, preuve éditoriale, image)
                             est un filet de secours replié, jamais supprimé. --}}
                        <div class="nc-field">
                            <label for="nc-source">Texte complet de la source <span class="nc-hint">(interne, jamais publié)</span></label>

                            <div x-show="fetchLoading" x-cloak style="display:block; color:#0B7285; font-size:13px; font-weight:600; margin-bottom:8px;">⏳ Récupération de l'article chez l'éditeur…</div>
                            <div class="nc-status-ok" x-show="!fetchLoading && fetchSuccessMessage" x-cloak x-text="fetchSuccessMessage" style="display:block; margin-bottom:8px;"></div>
                            <div class="nc-status-warning" x-show="fetchWarning" x-cloak style="display:block; margin-bottom:8px;"><span x-text="'⚠️ ' + fetchWarning"></span></div>
                            <div class="nc-status-error" x-show="fetchError" x-cloak style="display:block; margin-bottom:8px;">⚠️ Récupération impossible : <span x-text="fetchError"></span>. Colle le texte manuellement ou réessaie.</div>

                            <textarea id="nc-source" class="form-control nc-source-textarea" rows="10" x-model="formSourceText" :disabled="fetchLoading" placeholder="Colle ici le texte intégral de l'article source, pour ton propre usage éditorial."></textarea>
                            <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                <button type="button"
                                        class="cb-btn cb-btn-secondary"
                                        :disabled="fetchLoading"
                                        @click="formSourceText
                                            ? $dispatch('confirm-action', {
                                                title: 'Confirmer',
                                                message: 'Remplacer le texte source actuel par une nouvelle récupération ? Le texte actuel sera perdu.',
                                                action: () => fetchSource(true)
                                              })
                                            : fetchSource(false)">
                                    <span x-show="!fetchLoading">🔁 Récupérer à nouveau</span>
                                    <span x-show="fetchLoading" x-cloak>⏳ Récupération…</span>
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
                            </div>
                        </div>

                        {{-- Implémentation /actu2 - volet serveur (design doc "Actus - composition
                             manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet
                             serveur (2026-08-17)") - le mini-prompt /actu2 est construit CÔTÉ
                             CLIENT (aucun appel serveur requis) : source_url et id sont déjà dans
                             selectedArticle (endpoint show()). Fonctionne même sans texte source
                             collé - c'est le skill qui récoltera l'original via news:source. --}}
                        <div class="nc-field">
                            <button type="button" class="cb-btn" @click="copyQuickPrompt()">
                                <span x-show="!quickPromptCopied">📋 Copier le prompt /actu2</span>
                                <span x-show="quickPromptCopied" x-cloak>✓ Copié</span>
                            </button>
                            <span class="nc-status-error" x-show="quickPromptError" x-cloak x-text="quickPromptError" style="display:block; margin-top:6px;"></span>
                            <span class="nc-hint" style="display:block; margin-top:6px;">Colle /actu2 dans Claude Code : il retrouve l'original, rédige, prouve, révise, choisit la photo et publie - puis te donne le lien.</span>
                        </div>

                        {{-- Lot 4a puis 4b (design doc "extension de l'écran de composition des
                             actualités", 2026-09-03, section 2.7) : panneau TOUJOURS VISIBLE (pas de
                             <details> replié) - la façon normale de composer une fiche depuis
                             l'admin, plus un filet de secours. Résumé structuré, sources primaires
                             et outils liés (Lot 4b) vivent dans ce même panneau, plus bas. --}}
                        <div class="nc-composition-panel">
                            <div class="cb-section-title" style="font-size:14px; margin-top:0;">🗂️ Détails de la fiche</div>

                            <div class="nc-field">
                                <label for="nc-title-publie">Titre publié</label>
                                <input id="nc-title-publie" type="text" class="form-control" x-model="formTitle" maxlength="200" placeholder="Titre affiché sur la fiche publique">
                                <span class="nc-hint" x-show="selectedArticle && selectedArticle.is_published" x-cloak style="display:block; margin-top:4px;">Fiche déjà publiée : l'adresse (URL) ne change jamais, même si tu corriges ce titre.</span>
                            </div>

                            {{-- Lot 4b (design doc "extension de l'écran de composition des actualités",
                                 2026-09-03, section 2.5/2.7) : résumé structuré, huit sous-clés de
                                 composed_summary. Le formulaire renvoie l'état complet à save() (voir
                                 plus bas) UNIQUEMENT si l'admin y a touché ou si la fiche porte déjà
                                 un résumé composé (hasTouchedComposedSummary()) - sans cette garde,
                                 un enregistrement qui ne touche QUE image_credit enverrait quand même
                                 un résumé reconstruit et poserait composed:true sur un résumé encore
                                 MACHINE (voir le commentaire de show(), NewsCompositionController.php). --}}
                            <div class="nc-summary-section" id="nc-composed-summary">
                                <div class="cb-section-title" style="font-size:14px; margin-top:14px;">📝 Résumé structuré</div>

                                <div class="nc-field">
                                    <label for="nc-summary-hook">Accroche</label>
                                    <textarea id="nc-summary-hook" class="form-control" rows="2" x-model="formHook" maxlength="600" placeholder="La phrase d'ouverture qui donne envie de lire"></textarea>
                                </div>

                                <div class="nc-field">
                                    <label id="nc-summary-key-points-label">Points clés <span class="nc-hint">(5 maximum)</span></label>
                                    <template x-for="(point, index) in formKeyPoints" :key="index">
                                        <div class="d-flex gap-2 align-items-start" style="margin-bottom:8px;">
                                            <input type="text" class="form-control" x-model="formKeyPoints[index]" maxlength="300" placeholder="Un point clé, tiré du texte">
                                            <button type="button" class="nc-proof-remove" @click="removeKeyPoint(index)" aria-label="Retirer ce point clé">🗑</button>
                                        </div>
                                    </template>
                                    <button type="button" class="cb-btn cb-btn-secondary" @click="addKeyPoint()" :disabled="formKeyPoints.length >= 5">➕ Ajouter un point clé</button>
                                </div>

                                <div class="nc-field">
                                    <label for="nc-summary-why-important">Pourquoi c'est important</label>
                                    <textarea id="nc-summary-why-important" class="form-control" rows="2" x-model="formWhyImportant" maxlength="600" placeholder="L'enjeu concret, en une ou deux phrases"></textarea>
                                </div>

                                <div class="nc-field">
                                    <label for="nc-summary-key-number">Chiffre clé</label>
                                    <input id="nc-summary-key-number" type="text" class="form-control" x-model="formKeyNumber" maxlength="600" placeholder="Ex. 5 millions de dollars">
                                </div>

                                <div class="nc-field">
                                    <label for="nc-summary-quote-text">Citation</label>
                                    <textarea id="nc-summary-quote-text" class="form-control" rows="2" x-model="formQuoteText" maxlength="400" placeholder="Citation exacte, courte et attribuée"></textarea>
                                    <label for="nc-summary-quote-author" class="visually-hidden">Auteur de la citation</label>
                                    <input id="nc-summary-quote-author" type="text" class="form-control" style="margin-top:6px; max-width:340px;" x-model="formQuoteAuthor" maxlength="120" placeholder="Auteur de la citation">
                                </div>

                                <div class="nc-field">
                                    <label for="nc-summary-angle-qc-ca">Angle Québec/Canada</label>
                                    <textarea id="nc-summary-angle-qc-ca" class="form-control" rows="2" x-model="formAngleQcCa" maxlength="600" placeholder="Ce que ça change ici - jamais inventé, laisse vide si aucun angle vérifiable"></textarea>
                                </div>

                                <div class="nc-field">
                                    <label for="nc-summary-action-concrete">Action concrète</label>
                                    <textarea id="nc-summary-action-concrete" class="form-control" rows="2" x-model="formActionConcrete" maxlength="600" placeholder="Ce que le lecteur peut faire de cette information"></textarea>
                                </div>

                                <div class="nc-field">
                                    <label id="nc-summary-reperes-dates-label">Repères dans le temps <span class="nc-hint">(4 maximum, juxtaposés jamais causaux)</span></label>
                                    <template x-for="(repere, index) in formReperesDates" :key="index">
                                        <div class="nc-repere-row" style="border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:8px;">
                                            <div class="d-flex gap-2" style="margin-bottom:6px;">
                                                <input type="text" class="form-control" x-model="repere.date" maxlength="40" placeholder="Date (ex. 2024)" style="max-width:160px;">
                                                <button type="button" class="nc-proof-remove" @click="removeRepereDate(index)" aria-label="Retirer ce repère">🗑</button>
                                            </div>
                                            <input type="text" class="form-control" x-model="repere.texte" maxlength="200" placeholder="Ce qui s'est passé" style="margin-bottom:6px;">
                                            <input type="url" class="form-control" x-model="repere.url" maxlength="2000" placeholder="Lien (optionnel)">
                                        </div>
                                    </template>
                                    <button type="button" class="cb-btn cb-btn-secondary" @click="addRepereDate()" :disabled="formReperesDates.length >= 4">➕ Ajouter un repère</button>
                                </div>
                            </div>

                            <div class="nc-proof-section">
                                <div class="cb-section-title" style="font-size:14px; margin-top:14px;">🔍 Passages à vérifier</div>
                                <p class="nc-hint" style="display:block; margin:0 0 10px;">
                                    Pour chaque passage risqué du résumé publié : colle la phrase, colle l'extrait exact
                                    qui l'appuie, et déclare-le « fait » (sous-chaîne exacte du texte source collé),
                                    « fait primaire » (citation exacte de l'original, avec son lien - jamais vérifiée
                                    contre le texte source collé) ou « analyse » (ton propre liant éditorial, non vérifié).
                                </p>

                                <template x-if="!proofPairs.length">
                                    <p class="nc-hint" style="display:block; font-style:italic;">Aucun passage vérifié pour l'instant.</p>
                                </template>

                                <div class="nc-proof-pair" x-show="proofPairs.length" x-cloak>
                                    <template x-for="pair in proofPairs" :key="pair.id">
                                        <div class="nc-proof-card">
                                            <div class="nc-proof-type" :class="proofTypeClass(pair.type)" x-text="proofTypeLabel(pair.type)"></div>
                                            <div class="nc-proof-statement" x-text="pair.statement"></div>
                                            <div class="nc-proof-excerpt" x-text="'« ' + pair.excerpt + ' »'"></div>
                                            <div x-show="pair.type === 'primary_fact' && pair.source_url" x-cloak style="margin-top:4px;">
                                                <a :href="pair.source_url" target="_blank" rel="noopener nofollow" style="font-size:12px; color:#0B7285; font-weight:600;">🔗 Voir la source primaire</a>
                                            </div>
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
                                        <textarea id="nc-pair-excerpt" class="form-control nc-source-textarea" rows="3" x-model="newPairExcerpt" maxlength="2000" placeholder="Copie-colle l'extrait exact du texte source (ou de l'original, pour un fait primaire)"></textarea>
                                        <span class="nc-hint" x-show="newPairType === 'fact' && newPairExcerpt" x-cloak x-text="excerptFoundInSource() ? '✓ trouvé tel quel dans le texte source' : '⚠ introuvable tel quel dans le texte source'" :style="excerptFoundInSource() ? 'color:#059669' : 'color:#dc2626'"></span>
                                    </div>
                                    <div class="nc-field">
                                        <label for="nc-pair-type">Décision</label>
                                        <select id="nc-pair-type" class="form-control" x-model="newPairType" style="max-width:260px;">
                                            <option value="fact">Fait (sous-chaîne exacte exigée)</option>
                                            <option value="analysis">Analyse (liant éditorial)</option>
                                            <option value="primary_fact">Fait primaire (cite l'original, lien exigé)</option>
                                        </select>
                                    </div>
                                    <div class="nc-field" x-show="newPairType === 'primary_fact'" x-cloak>
                                        <label for="nc-pair-source-url">Lien de la source primaire</label>
                                        <input id="nc-pair-source-url" type="url" class="form-control" x-model="newPairSourceUrl" maxlength="2000" placeholder="https://…">
                                    </div>
                                    <button type="button" class="cb-btn cb-btn-secondary" @click="addProofPair()" :disabled="pairSaving || !newPairStatement || !newPairExcerpt || (newPairType === 'fact' && !excerptFoundInSource()) || (newPairType === 'primary_fact' && !newPairSourceUrl)">
                                        <span x-show="!pairSaving">➕ Ajouter le passage</span>
                                        <span x-show="pairSaving" x-cloak>⏳ Ajout…</span>
                                    </button>
                                    <span class="nc-status-error" x-show="pairError" x-cloak x-text="pairError" style="display:block; margin-top:6px;"></span>
                                </div>
                            </div>

                            {{-- Lot 4b (section 2.5/2.7) - sources primaires : REMPLACEMENT complet à
                                 l'enregistrement (pas une accumulation, contrairement aux preuves et
                                 aux outils ci-dessus/dessous), plafonnées à 10 côté client, miroir du
                                 plafond serveur (pas une nouvelle règle). --}}
                            <div class="nc-field" id="nc-primary-sources">
                                <div class="cb-section-title" style="font-size:14px; margin-top:0;">🔗 Sources primaires</div>
                                <template x-for="(source, index) in formPrimarySources" :key="index">
                                    <div class="nc-repere-row" style="border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:8px;">
                                        <div class="d-flex gap-2" style="margin-bottom:6px;">
                                            <input type="text" class="form-control" x-model="source.label" maxlength="255" placeholder="Nom de la source">
                                            <button type="button" class="nc-proof-remove" @click="removePrimarySource(index)" aria-label="Retirer cette source primaire">🗑</button>
                                        </div>
                                        <input type="url" class="form-control" x-model="source.url" maxlength="2000" placeholder="https://…" style="margin-bottom:6px;">
                                        <input type="text" class="form-control" x-model="source.note" maxlength="255" placeholder="Note (optionnelle)">
                                    </div>
                                </template>
                                <button type="button" class="cb-btn cb-btn-secondary" @click="addPrimarySource()" :disabled="formPrimarySources.length >= 10">➕ Ajouter une source primaire</button>
                                <span class="nc-hint" style="display:block; margin-top:6px;">Remplace intégralement les sources primaires à l'enregistrement - une ligne laissée incomplète (nom ou lien manquant) n'est pas envoyée.</span>
                            </div>

                            <div class="nc-field">
                                <label for="nc-image-credit">Crédit photo</label>
                                <input id="nc-image-credit" type="text" class="form-control" x-model="formImageCredit" maxlength="255" placeholder="Ex. Photo : Untel, Agence Untelle">
                            </div>

                            <div class="nc-field">
                                <label for="nc-nature-original">Nature de la source <span class="nc-hint">(interne, jamais affichée telle quelle)</span></label>
                                <select id="nc-nature-original" class="form-control" x-model="formNatureOriginal" style="max-width:340px;">
                                    <option value="">(non classée)</option>
                                    <template x-for="opt in natureOriginalOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="nc-field">
                                <label for="nc-niveau-preuve">Niveau de preuve <span class="nc-hint">(affiché sur la fiche publique)</span></label>
                                <select id="nc-niveau-preuve" class="form-control" x-model="formNiveauPreuve" style="max-width:340px;">
                                    <option value="">(non classé)</option>
                                    <template x-for="opt in niveauPreuveOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Lot 4b (section 2.5/2.7) - outils liés : action IMMÉDIATE (une
                                 sélection appelle related-tools.store, un retrait de puce appelle
                                 related-tools.destroy), même patron que les preuves éditoriales
                                 ci-dessus - pas un troisième patron d'interaction sur cet écran.
                                 TomSelect (déjà une dépendance du projet, déjà utilisée pour
                                 tool_ids[] sur l'écran classique) sert ici de simple sélecteur "un
                                 outil à la fois", vidé après chaque ajout - les outils déjà liés
                                 s'affichent en puces retirables juste en dessous, jamais dans
                                 TomSelect lui-même. --}}
                            <div class="nc-field">
                                <label for="nc-related-tool-select">Outils liés</label>
                                {{-- Pas d'attribut "placeholder" ici : un <select> natif ne le supporte pas,
                                     le vrai placeholder vit dans la config TomSelect (initRelatedToolPicker()). --}}
                                <select id="nc-related-tool-select" x-init="$nextTick(() => initRelatedToolPicker($el))"></select>
                                <div id="nc-related-tools-list" style="margin-top:8px;">
                                    <template x-if="!relatedTools.length">
                                        <p class="nc-hint" style="display:block; font-style:italic;">Aucun outil lié pour l'instant.</p>
                                    </template>
                                    <template x-for="tool in relatedTools" :key="tool.slug">
                                        <span class="nc-internal-badge" style="display:inline-flex; align-items:center; gap:6px; margin:0 6px 6px 0;">
                                            <span x-text="tool.label"></span>
                                            <button type="button" @click="removeRelatedTool(tool.slug)" style="background:none; border:none; cursor:pointer; color:#92400e; font-weight:700; min-height:24px;" :aria-label="'Retirer ' + tool.label">×</button>
                                        </span>
                                    </template>
                                </div>
                                <span class="nc-status-error" x-show="relatedToolError" x-cloak x-text="relatedToolError" style="display:block; margin-top:6px;"></span>
                            </div>

                            {{-- Le bouton d'enregistrement vit DANS le panneau toujours visible, et non
                                 dans le <details> « filet de secours » plus bas. Un panneau permanent dont
                                 le bouton de sauvegarde est caché derrière un accordéon oblige à ouvrir une
                                 section « secours » pour valider une saisie ordinaire : le geste normal
                                 passerait par le chemin d'exception. Même fonction save() appelée des deux
                                 endroits (aucune logique dupliquée), même état de chargement. --}}
                            <div class="d-flex flex-wrap gap-2 align-items-center" style="margin-top:10px;">
                                <button type="button" class="cb-btn" @click="save()" :disabled="loading.saving">
                                    <span x-show="!loading.saving">💾 Enregistrer</span>
                                    <span x-show="loading.saving" x-cloak>⏳ Enregistrement…</span>
                                </button>
                                <span class="nc-hint">Titre publié, résumé structuré, sources primaires, crédit photo, nature de la source et niveau de preuve.</span>
                            </div>
                            <span class="nc-status-ok" x-show="saveOk" x-cloak x-transition style="display:block; margin-top:6px;">✓ Enregistré</span>
                            <span class="nc-status-error" x-show="saveError" x-cloak x-text="saveError" style="display:block; margin-top:6px;"></span>
                            <button type="button" class="cb-btn cb-btn-secondary" x-show="saveConflict" x-cloak @click="reloadAfterConflict()" style="margin-top:6px;">🔄 Recharger la fiche</button>
                        </div>

                        <div class="nc-field">
                            <button type="button"
                                    class="cb-btn"
                                    :disabled="publishing || publishMissingList().length > 0"
                                    @click="$dispatch('confirm-action', {
                                        title: 'Confirmer',
                                        message: 'Publier cette actualité ET supprimer définitivement le texte source original ? La suppression est irréversible (l\'empreinte et la preuve sont conservées).',
                                        action: () => publishArticle()
                                    })">
                                <span x-show="!publishing">✅ Publier et purger le texte source</span>
                                <span x-show="publishing" x-cloak>⏳ Publication…</span>
                            </button>
                            <div class="nc-hint" x-show="publishMissingList().length > 0" x-cloak x-text="'Il manque : ' + publishMissingList().join(', ')" style="display:block; margin-top:6px;"></div>
                            <span class="nc-status-ok" x-show="publishOk" x-cloak x-transition x-text="'✓ Actualité publiée.'" style="display:block; margin-top:6px;"></span>
                            <span class="nc-status-error" x-show="publishError" x-cloak x-text="publishError" style="display:block; margin-top:6px;"></span>
                        </div>

                        {{-- Signature éditoriale (2026-08-21) : le geste HUMAIN qui date la
                             relecture affichée sous la fiche publique. Volontairement séparé de
                             la publication, car une fiche composée puis publiée par l'agent
                             n'a, par construction, jamais été relue par quelqu'un. --}}
                        <div class="nc-field" x-show="selectedArticle && selectedArticle.is_published" x-cloak>
                            <button type="button"
                                    class="cb-btn"
                                    x-show="!selectedArticle.reviewed_at"
                                    :disabled="reviewing"
                                    @click="markReviewed()">
                                <span x-show="!reviewing">✍️ J'ai relu cette fiche</span>
                                <span x-show="reviewing" x-cloak>⏳ Enregistrement…</span>
                            </button>
                            <span class="nc-hint" x-show="!selectedArticle.reviewed_at" style="display:block; margin-top:6px;">
                                Date ta relecture : la fiche publique affiche alors « Relu et validé par… », avec cette date. À ne cliquer qu'après avoir vraiment lu la fiche.
                            </span>
                            <span class="nc-hint" x-show="selectedArticle.reviewed_at" x-cloak style="display:block; margin-top:6px;">
                                ✍️ Fiche déjà signée <span x-text="selectedArticle.reviewed_by ? 'par ' + selectedArticle.reviewed_by : ''"></span>.
                            </span>
                            <span class="nc-status-ok" x-show="reviewOk" x-cloak x-transition style="display:block; margin-top:6px;">✓ Relecture enregistrée.</span>
                            <span class="nc-status-error" x-show="reviewError" x-cloak x-text="reviewError" style="display:block; margin-top:6px;"></span>
                        </div>

                        <details class="nc-manual-details">
                            <summary>Édition manuelle (filet de secours)</summary>

                            <div class="nc-field">
                                <label for="nc-angle">Angle éditorial <span class="nc-hint">(optionnel, transmis au prompt de rédaction)</span></label>
                                <input id="nc-angle" type="text" class="form-control" x-model="formAngle" maxlength="500" placeholder="Ex. impact pour les PME québécoises">
                            </div>

                            {{-- Ancien flux de génération (gros gabarit) - DÉPLACÉ ici depuis le
                                 parcours principal (implémentation /actu2, 2026-08-17) : le bouton
                                 principal copie désormais le mini-prompt /actu2, qui remplace ce
                                 flux pour l'usage courant. Conservé accessible en filet de secours,
                                 jamais supprimé. --}}
                            <div class="nc-field">
                                <button type="button" class="cb-btn cb-btn-secondary" @click="generatePrompt()" :disabled="promptLoading || !formSourceText">
                                    <span x-show="!promptLoading">🧠 Enregistrer et générer le prompt Claude Code (déprécié - l'ancien gros prompt)</span>
                                    <span x-show="promptLoading" x-cloak>⏳ Enregistrement et génération…</span>
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
                                            <span class="nc-hint">Colle-le dans Claude Code (CLI) : il rédige, remplit la preuve, crée l'image et met à jour la fiche - qui reste en brouillon.</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Lot 4a (design doc 2026-09-03, section 2.7) - libellé corrigé : ce
                                 champ pointe vers seo_title, pas vers le titre publié (qui vit
                                 maintenant dans le panneau toujours visible ci-dessus, formTitle).
                                 Les deux coexistent : ce sont deux champs distincts du modèle. --}}
                            <div class="nc-field">
                                <label for="nc-seo-title">Titre SEO (balise &lt;title&gt;)</label>
                                <input id="nc-seo-title" type="text" class="form-control" x-model="formSeoTitle" maxlength="255" placeholder="Titre affiché dans l'onglet du navigateur et les résultats de recherche">
                            </div>

                            <div class="nc-field">
                                <label for="nc-summary">Résumé publié <span class="nc-hint">(court, affiché sur la fiche et les cartes)</span></label>
                                <textarea id="nc-summary" class="form-control" rows="4" x-model="formSummary" maxlength="2000" placeholder="Résumé court destiné à l'affichage public"></textarea>
                            </div>

                            {{-- Bonification panel 2026-08-17 (soir) - bloc LECTURE SEULE : les
                                 sources primaires et le crédit photo sont fournis par l'agent
                                 (NewsApplyCommand --payload), jamais édités depuis cet écran.
                                 Masqué si aucune source primaire n'a encore été fournie. --}}
                            <div class="nc-field" x-show="selectedArticle?.primary_sources?.length" x-cloak>
                                <label>Sources primaires <span class="nc-hint">(fournies par l'agent, lecture seule)</span></label>
                                <ul style="margin:0; padding-left:18px; font-size:13px; color:#374151; line-height:1.6;">
                                    <template x-for="src in (selectedArticle?.primary_sources || [])" :key="src.url">
                                        <li>
                                            <a :href="src.url" target="_blank" rel="noopener nofollow" x-text="src.label || src.url" style="color:#0B7285; font-weight:600;"></a>
                                            <template x-if="src.note"><span x-text="' - ' + src.note" style="color:#6b7280;"></span></template>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="cb-btn" @click="save()" :disabled="loading.saving">
                                    <span x-show="!loading.saving">💾 Enregistrer</span>
                                    <span x-show="loading.saving" x-cloak>⏳ Enregistrement…</span>
                                </button>
                                <span class="nc-status-ok" x-show="saveOk" x-cloak x-transition>✓ Enregistré</span>
                                <span class="nc-status-error" x-show="saveError" x-cloak x-text="saveError"></span>
                            </div>

                            {{-- Lot 4a (design doc 2026-09-03, section 2.7) : la fiche de preuve
                                 éditoriale a déménagé dans le panneau toujours visible ci-dessus
                                 (même endpoints storeProofPair/destroyProofPair, action immédiate
                                 - jamais deux UI pour la même donnée). --}}

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
                        </details>
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
            createDraft: opts.createDraftEndpoint,
            showTemplate: opts.showEndpointTemplate,
            updateTemplate: opts.updateEndpointTemplate,
            deleteSourceTextTemplate: opts.deleteSourceTextEndpointTemplate,
            fetchSourceTemplate: opts.fetchSourceEndpointTemplate,
            generatePromptTemplate: opts.generatePromptEndpointTemplate,
            proofPairsStoreTemplate: opts.proofPairsStoreEndpointTemplate,
            proofPairsDestroyTemplate: opts.proofPairsDestroyEndpointTemplate,
            generateImagePromptTemplate: opts.generateImagePromptEndpointTemplate,
            uploadImageTemplate: opts.uploadImageEndpointTemplate,
            publishTemplate: opts.publishEndpointTemplate,
            markReviewedTemplate: opts.markReviewedEndpointTemplate,
            articlesIndexUrl: opts.articlesIndexUrl,
            // Lot 4b (design doc 2026-09-03, section 2.5/2.7) - outils liés, action immédiate.
            relatedToolsStoreTemplate: opts.relatedToolsStoreEndpointTemplate,
            relatedToolsDestroyTemplate: opts.relatedToolsDestroyEndpointTemplate,
        },
        loading: { news: false, article: false, saving: false, deleting: false },
        selectedArticle: null,
        formSeoTitle: '',
        formSummary: '',
        formSourceText: '',
        // Lot 4a (design doc "extension de l'écran de composition des actualités", 2026-09-03,
        // section 2.5/2.7) : champs riches du panneau toujours visible. natureOriginalOptions/
        // niveauPreuveOptions sont des tableaux {value, label} dérivés de show(), jamais recopiés
        // en dur ici (source unique : NewsArticle::NATURE_ORIGINAL_VALUES/NIVEAU_PREUVE_VALUES).
        formTitle: '',
        formImageCredit: '',
        formNatureOriginal: '',
        formNiveauPreuve: '',
        natureOriginalOptions: [],
        niveauPreuveOptions: [],
        // Lot 4b (design doc 2026-09-03, section 2.5/2.7) - huit sous-clés de composed_summary.
        // formReperesDates/formKeyPoints sont des tableaux de longueur variable (5/4 max, addXxx()/
        // removeXxx() plus bas) - jamais un nombre fixe de champs vides pré-remplis.
        formHook: '',
        formKeyPoints: [],
        formWhyImportant: '',
        formKeyNumber: '',
        formQuoteText: '',
        formQuoteAuthor: '',
        formAngleQcCa: '',
        formActionConcrete: '',
        formReperesDates: [],
        // Garde anti-écrasement silencieux (voir le commentaire de composed_summary_active dans
        // NewsCompositionController::show()) : reflète l'état SERVEUR au chargement, mis à jour
        // localement après un save() qui a effectivement touché le résumé structuré.
        composedSummaryActive: false,
        // Sérialisation des 8 champs telle qu'ils ont été CHARGÉS. hasTouchedComposedSummary()
        // s'y compare pour distinguer « pré-rempli » de « modifié par un humain ».
        composedSummarySnapshot: 'null',
        // Sources primaires (remplacement complet, plafond 10 - miroir du plafond serveur).
        formPrimarySources: [],
        // Outils liés (action immédiate, jamais dans save()). availableTools est CHARGÉ UNE FOIS
        // depuis opts (catalogue indépendant de la fiche sélectionnée, voir index()) ; relatedTools
        // est propre à la fiche COURANTE, rechargé à chaque loadArticle().
        availableTools: opts.availableTools || [],
        relatedTools: [],
        newRelatedToolSlug: '',
        relatedToolSaving: false,
        relatedToolError: '',
        // Verrou optimiste (section 2.6) : true après un 409 - affiche le bouton "Recharger".
        saveConflict: false,
        saveOk: false,
        saveError: '',

        // Récupération automatique du texte source chez l'éditeur (design doc composition
        // manuelle, révision 2026-08-17). fetchAbortController n'est jamais lu dans le template -
        // stocker un AbortController sur l'état réactif Alpine ne pose pas de problème (aucune
        // lecture ne le rend réactif, seule sa présence/absence compte).
        fetchLoading: false,
        fetchError: '',
        fetchWarning: '',
        fetchSuccessMessage: '',
        fetchAbortController: null,

        // Publication + purge du texte source (design doc composition manuelle, révision
        // 2026-08-17).
        publishing: false,
        publishOk: false,
        publishError: '',
        reviewing: false,
        reviewOk: false,
        reviewError: '',

        // Phase B (design doc 2026-08-15, sections 5.1 et 7) : génération du prompt de
        // rédaction et fiche de preuve éditoriale. Flux DÉPRECIÉ (implémentation /actu2,
        // 2026-08-17) - déplacé dans le volet replié, conservé en filet de secours.
        formAngle: '',
        generatedPrompt: '',
        promptLoading: false,
        promptError: '',
        promptCopied: false,

        // Implémentation /actu2 - volet serveur (design doc "Actus - composition manuelle
        // assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur (2026-08-17)") :
        // mini-prompt /actu2 construit CÔTÉ CLIENT (aucun appel serveur), copié au presse-papier.
        quickPromptCopied: false,
        quickPromptError: '',

        // Améliorations en attente (2026-08-17), point 1 - « Créer une fiche depuis un lien »
        // (formulaire au-dessus de la colonne gauche).
        createDraftUrl: '',
        createDraftLoading: false,
        createDraftError: '',
        createDraftPrompt: '',
        createDraftCreatedNew: true,
        createDraftPromptCopied: false,
        proofPairs: [],
        newPairStatement: '',
        newPairExcerpt: '',
        newPairType: 'fact',
        // Lot 4a (section 2.7) - 3e type de paire « fait primaire » : lien exigé, affiché
        // seulement quand newPairType === 'primary_fact' (même patron que le formulaire).
        newPairSourceUrl: '',
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
            // Piège nommé par le panel (design doc composition manuelle, révision 2026-08-17) :
            // une récupération encore en vol pour l'actualité précédente ne doit jamais remplir
            // la fiche qu'on est en train d'ouvrir - on l'annule systématiquement.
            if (this.fetchAbortController) {
                this.fetchAbortController.abort();
                this.fetchAbortController = null;
            }
            this.fetchLoading = false;
            this.fetchError = '';
            this.fetchWarning = '';
            this.fetchSuccessMessage = '';
            this.publishError = '';
            this.publishOk = false;
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
                // Lot 4a (design doc 2026-09-03, section 2.5/2.7) - champs riches, mêmes
                // conventions que les trois champs historiques ci-dessus (chaîne vide si absent,
                // jamais null dans un x-model). natureOriginalOptions/niveauPreuveOptions : objet
                // {clé: libellé} renvoyé par show() converti en tableau {value, label} pour
                // x-for, source unique NewsArticle::NATURE_ORIGINAL_VALUES/NIVEAU_PREUVE_VALUES.
                this.formTitle = data.title || '';
                this.formImageCredit = data.image_credit || '';
                this.formNatureOriginal = data.nature_original || '';
                this.formNiveauPreuve = data.niveau_preuve || '';
                this.natureOriginalOptions = Object.entries(data.nature_original_options || {}).map(([value, label]) => ({ value, label }));
                this.niveauPreuveOptions = Object.entries(data.niveau_preuve_options || {}).map(([value, label]) => ({ value, label }));
                // Lot 4b (design doc 2026-09-03, section 2.5/2.7) - résumé structuré : pré-rempli
                // depuis composed_summary QUE la fiche soit déjà composée ou encore machine (les
                // deux partagent les mêmes noms de sous-clés, voir le commentaire de show()) -
                // composedSummaryActive distingue les deux pour hasTouchedComposedSummary() plus
                // bas, jamais le CONTENU affiché ici.
                const cs = data.composed_summary || {};
                this.formHook = cs.hook || '';
                this.formKeyPoints = Array.isArray(cs.key_points) ? cs.key_points.slice(0, 5) : [];
                this.formWhyImportant = cs.why_important || '';
                this.formKeyNumber = cs.key_number || '';
                this.formQuoteText = (cs.quote && cs.quote.text) || '';
                this.formQuoteAuthor = (cs.quote && cs.quote.author) || '';
                this.formAngleQcCa = cs.angle_qc_ca || '';
                this.formActionConcrete = cs.action_concrete || '';
                this.formReperesDates = Array.isArray(cs.reperes_dates)
                    ? cs.reperes_dates.slice(0, 4).map(r => ({ date: r.date || '', texte: r.texte || '', url: r.url || '' }))
                    : [];
                this.composedSummaryActive = Boolean(data.composed_summary_active);
                // Instantané de l'état CHARGÉ des 8 champs. C'est la référence à laquelle
                // hasTouchedComposedSummary() compare : sans lui, la garde testait un champ NON
                // VIDE et non un champ MODIFIÉ - or les 8 champs sont pré-remplis ci-dessus, y
                // compris pour un résumé encore machine. Défaut mesuré en QC visuelle le
                // 2026-09-03 : enregistrer le seul crédit photo faisait basculer composed à true
                // dans le dos de l'administrateur.
                this.composedSummarySnapshot = JSON.stringify(this.buildComposedSummaryPayload());
                // Sources primaires (remplacement complet à save(), voir plus bas).
                this.formPrimarySources = Array.isArray(data.primary_sources)
                    ? data.primary_sources.slice(0, 10).map(s => ({ label: s.label || '', url: s.url || '', note: s.note || '' }))
                    : [];
                // Outils déjà liés (action immédiate, jamais dans save()).
                this.relatedTools = Array.isArray(data.related_tools) ? data.related_tools.slice() : [];
                this.relatedToolError = '';
                this.saveConflict = false;
                this.formAngle = '';
                this.generatedPrompt = '';
                this.promptError = '';
                this.newPairStatement = '';
                this.newPairExcerpt = '';
                this.newPairType = 'fact';
                this.newPairSourceUrl = '';
                this.pairError = '';
                this.imagePromptError = '';
                this.imagePromptCopied = false;
                this.imageUploadOk = false;
                this.imageUploadError = '';
                // Récupération automatique (design doc composition manuelle, révision
                // 2026-08-17) : seulement si aucun texte source n'existe déjà - jamais
                // d'écrasement silencieux d'un texte collé manuellement.
                if (!this.formSourceText) {
                    this.fetchSource(false);
                }
            } catch (e) {
                this.saveError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.article = false;
            }
        },

        // Récupération automatique/manuelle du texte source chez l'éditeur (design doc
        // composition manuelle, révision 2026-08-17). AbortController pour ne jamais remplir la
        // mauvaise fiche si l'admin change d'actualité pendant l'attente (jusqu'à 35 s côté
        // serveur).
        async fetchSource(replace = false) {
            if (!this.selectedArticle) return;
            if (this.fetchAbortController) {
                this.fetchAbortController.abort();
            }
            const controller = new AbortController();
            this.fetchAbortController = controller;
            const slugAtStart = this.selectedArticle.slug;

            this.fetchLoading = true;
            this.fetchError = '';
            this.fetchWarning = '';
            this.fetchSuccessMessage = '';

            try {
                const url = this.endpoints.fetchSourceTemplate.replace('__SLUG__', slugAtStart);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ replace: !!replace }),
                    signal: controller.signal,
                });
                // Réponse tardive après changement de sélection : on l'ignore silencieusement,
                // elle ne doit jamais remplir la fiche désormais affichée.
                if (!this.selectedArticle || this.selectedArticle.slug !== slugAtStart) return;
                const data = await res.json();
                if (!res.ok) {
                    this.fetchError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.formSourceText = data.markdown || '';
                const words = this.formSourceText.trim() ? this.formSourceText.trim().split(/\s+/).length : 0;
                const method = data.acquisition?.method || 'inconnue';
                this.fetchSuccessMessage = '✓ Article récupéré (' + words + ' mots, ' + method + ')';
                if (data.acquisition?.warning) {
                    this.fetchWarning = data.acquisition.warning;
                }
                // Lot 4a (design doc 2026-09-03, section 2.6) - report obligatoire : cette
                // récupération se déclenche AUTOMATIQUEMENT à l'ouverture de toute fiche sans
                // texte source (loadArticle() ci-dessus). Sans ce report, le verrou optimiste
                // refuserait la toute première sauvegarde de champs riches avec un 409
                // auto-infligé, alors que personne d'autre n'a touché la fiche.
                if (data.updated_at && this.selectedArticle) {
                    this.selectedArticle.updated_at = data.updated_at;
                }
            } catch (e) {
                if (e.name === 'AbortError') return;
                this.fetchError = 'erreur réseau (' + e.message + ')';
            } finally {
                if (this.fetchAbortController === controller) {
                    this.fetchAbortController = null;
                }
                if (this.selectedArticle && this.selectedArticle.slug === slugAtStart) {
                    this.fetchLoading = false;
                }
            }
        },

        // Champs manquants avant publication (design doc composition manuelle, révision
        // 2026-08-17) : lu par le template pour désactiver le bouton et lister ce qui manque.
        publishMissingList() {
            const missing = [];
            // Lot 4a (design doc 2026-09-03, section 2.7) - libellé corrigé : ce contrôle porte
            // sur formSeoTitle (colonne seo_title, exigée par NewsArticle::publishReadinessCheck())
            // et non sur le nouveau champ "titre publié" (formTitle) - même confusion que celle
            // corrigée sur le libellé du champ lui-même, fermée ici aussi.
            if (!this.formSeoTitle || !this.formSeoTitle.trim()) missing.push('titre SEO');
            if (!this.formSummary || !this.formSummary.trim()) missing.push('résumé');
            if (!this.proofPairs.length) missing.push('au moins une paire de preuve');
            // ACTION : garde-fou #2244 (2026-09-04) - le serveur refuse depuis aujourd'hui une
            // fiche sans crédit d'image ; sans cette ligne le bouton restait ACTIF et le rédacteur
            // ne découvrait le refus qu'après l'appel HTTP. Trouvé par la revue de Codex, vérifié
            // dans le code : publishMissingList() listait encore les 3 champs d'avant le correctif.
            // MCP: SELF (1 ligne utile)
            if (!this.formImageCredit || !this.formImageCredit.trim()) missing.push("le crédit de l'image (une vraie photo, pas la carte de repli)");
            return missing;
        },

        // Signature éditoriale : seul chemin front qui pose reviewed_at sur une fiche déjà
        // publiée. Le serveur refuse (409) une fiche déjà signée - on n'écrase jamais une date.
        async markReviewed() {
            if (!this.selectedArticle) return;
            this.reviewing = true;
            this.reviewError = '';
            this.reviewOk = false;
            try {
                const url = this.endpoints.markReviewedTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok) {
                    this.reviewError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.selectedArticle.reviewed_at = data.reviewed_at;
                this.selectedArticle.reviewed_by = data.reviewed_by || this.selectedArticle.reviewed_by;
                this.reviewOk = true;
                setTimeout(() => { this.reviewOk = false; }, 2500);
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('toast', { type: 'success', message: 'Relecture enregistrée.' });
                }
            } catch (e) {
                this.reviewError = 'Erreur réseau : ' + e.message;
            } finally {
                this.reviewing = false;
            }
        },

        async publishArticle() {
            if (!this.selectedArticle) return;
            this.publishing = true;
            this.publishError = '';
            this.publishOk = false;
            try {
                const url = this.endpoints.publishTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok) {
                    if (data.missing && data.missing.length) {
                        this.publishError = (data.error || 'Publication impossible.') + ' (' + data.missing.join(', ') + ')';
                    } else {
                        this.publishError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    }
                    return;
                }
                this.selectedArticle.is_published = true;
                if (data.site_url) { this.selectedArticle.site_url = data.site_url; }
                if (data.reviewed_at) { this.selectedArticle.reviewed_at = data.reviewed_at; }
                if (data.reviewed_by) { this.selectedArticle.reviewed_by = data.reviewed_by; }
                this.selectedArticle.internal_source_text = null;
                this.formSourceText = '';
                this.publishOk = true;
                setTimeout(() => { this.publishOk = false; }, 2500);
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('toast', { type: 'success', message: 'Actualité publiée.' });
                }
            } catch (e) {
                this.publishError = 'Erreur réseau : ' + e.message;
            } finally {
                this.publishing = false;
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

        // Lot 4a (design doc 2026-09-03, section 2.7) - 3e type de paire, « fait primaire »,
        // ajouté au badge existant (fait/analyse) sans dupliquer le gabarit de carte.
        proofTypeClass(type) {
            if (type === 'fact') return 'nc-proof-type-fact';
            if (type === 'primary_fact') return 'nc-proof-type-primary';

            return 'nc-proof-type-analysis';
        },
        proofTypeLabel(type) {
            if (type === 'fact') return 'FAIT';
            if (type === 'primary_fact') return 'FAIT PRIMAIRE';

            return 'ANALYSE';
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
                    body: JSON.stringify({ angle: this.formAngle, source_text: this.formSourceText }),
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

        // Implémentation /actu2 - volet serveur (design doc composition manuelle, section
        // "Implémentation /actu2 - volet serveur (2026-08-17)") : mini-prompt construit CÔTÉ
        // CLIENT, aucun appel serveur - source_url et id sont déjà dans selectedArticle (endpoint
        // show(), champ source_url = resolved_url ?: url calculé côté serveur). Fonctionne même
        // sans texte source collé : c'est le skill qui récoltera l'original via news:source.
        async copyQuickPrompt() {
            if (!this.selectedArticle) return;
            this.quickPromptError = '';
            const sourceUrl = this.selectedArticle.source_url || '';
            const prompt = '/actu2 ' + (sourceUrl ? sourceUrl + ' ' : '') + 'fiche:' + this.selectedArticle.id;
            try {
                await navigator.clipboard.writeText(prompt);
                this.quickPromptCopied = true;
                setTimeout(() => { this.quickPromptCopied = false; }, 2500);
            } catch (e) {
                this.quickPromptError = 'Copie impossible, copie-le manuellement : ' + prompt;
            }
        },

        // Améliorations en attente (2026-08-17), point 1 - « Créer une fiche depuis un lien » :
        // appelle la même implémentation serveur que `php artisan news:create-draft`
        // (NewsArticle::createManualDraft(), DRY strict). Idempotent par URL - créer deux fois
        // le même lien sélectionne simplement la fiche déjà créée, sans erreur. Une fois créée,
        // rafraîchit la liste des actualités disponibles (fetchNews()) PUIS sélectionne
        // directement la fiche (selectItem()), pour ne jamais laisser l'admin la rechercher.
        async createDraft() {
            if (!this.createDraftUrl) return;
            this.createDraftLoading = true;
            this.createDraftError = '';
            this.createDraftPrompt = '';
            try {
                const res = await fetch(this.endpoints.createDraft, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ url: this.createDraftUrl }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.createDraftError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.createDraftPrompt = data.mini_prompt || '';
                this.createDraftCreatedNew = !!data.created;
                this.createDraftUrl = '';
                await this.fetchNews();
                this.selectItem(data.id);
            } catch (e) {
                this.createDraftError = 'Erreur réseau : ' + e.message;
            } finally {
                this.createDraftLoading = false;
            }
        },

        async copyCreateDraftPrompt() {
            if (!this.createDraftPrompt) return;
            try {
                await navigator.clipboard.writeText(this.createDraftPrompt);
                this.createDraftPromptCopied = true;
                setTimeout(() => { this.createDraftPromptCopied = false; }, 2500);
            } catch (e) {
                this.createDraftError = 'Copie impossible, copie-le manuellement : ' + this.createDraftPrompt;
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
            // Lot 4a (design doc 2026-09-03, section 2.7) - 'source_url' exigé pour le 3e type,
            // miroir client du 'required_if:type,primary_fact' du contrôleur (défense en
            // profondeur, jamais la seule garde).
            if (this.newPairType === 'primary_fact' && !this.newPairSourceUrl) return;
            this.pairSaving = true;
            this.pairError = '';
            try {
                const url = this.endpoints.proofPairsStoreTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const body = {
                    statement: this.newPairStatement,
                    excerpt: this.newPairExcerpt,
                    type: this.newPairType,
                };
                if (this.newPairType === 'primary_fact') {
                    body.source_url = this.newPairSourceUrl;
                }
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pairError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.proofPairs = data.pairs;
                // Report du verrou optimiste (section 2.6) - voir le commentaire de fetchSource()
                // plus haut, même raison : cette écriture ne doit jamais auto-infliger un 409 à
                // la prochaine sauvegarde des champs riches dans le même onglet.
                if (data.updated_at) { this.selectedArticle.updated_at = data.updated_at; }
                this.newPairStatement = '';
                this.newPairExcerpt = '';
                this.newPairType = 'fact';
                this.newPairSourceUrl = '';
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
                // Report du verrou optimiste (section 2.6) - même raison qu'addProofPair() ci-dessus.
                if (data.updated_at) { this.selectedArticle.updated_at = data.updated_at; }
            } catch (e) {
                this.pairError = 'Erreur réseau : ' + e.message;
            }
        },

        // ── Résumé structuré - lignes répétables (Lot 4b, section 2.7) ───────────────────
        addKeyPoint() {
            if (this.formKeyPoints.length >= 5) return;
            this.formKeyPoints.push('');
        },
        removeKeyPoint(index) {
            this.formKeyPoints.splice(index, 1);
        },
        addRepereDate() {
            if (this.formReperesDates.length >= 4) return;
            this.formReperesDates.push({ date: '', texte: '', url: '' });
        },
        removeRepereDate(index) {
            this.formReperesDates.splice(index, 1);
        },

        // Construit le payload complet des 8 sous-clés de composed_summary à partir de l'état du
        // formulaire (Lot 4b, section 2.5) - un champ vide part comme `null` explicite (retrait
        // volontaire d'une sous-clé, jamais un silence), exactement la sémantique attendue par
        // CompositionPayloadNormalizer::overlayComposedSummary() côté serveur. `author`/`url`
        // absents (plutôt que vides) quand non renseignés : ce sont des sous-clés FACULTATIVES du
        // service (voir normalizeComposedQuote()/normalizeComposedReperesDates()), une chaîne
        // vide n'y est pas une valeur valide pour `url`.
        buildComposedSummaryPayload() {
            const keyPoints = this.formKeyPoints.map(p => (p || '').trim()).filter(p => p !== '');
            const reperes = this.formReperesDates
                .map(r => ({ date: (r.date || '').trim(), texte: (r.texte || '').trim(), url: (r.url || '').trim() }))
                .filter(r => r.date !== '' && r.texte !== '')
                .map(r => (r.url !== '' ? r : { date: r.date, texte: r.texte }));
            const quoteText = (this.formQuoteText || '').trim();
            const quoteAuthor = (this.formQuoteAuthor || '').trim();

            return {
                hook: this.formHook.trim() ? this.formHook.trim() : null,
                key_points: keyPoints.length ? keyPoints : null,
                why_important: this.formWhyImportant.trim() ? this.formWhyImportant.trim() : null,
                key_number: this.formKeyNumber.trim() ? this.formKeyNumber.trim() : null,
                quote: quoteText ? (quoteAuthor ? { text: quoteText, author: quoteAuthor } : { text: quoteText }) : null,
                angle_qc_ca: this.formAngleQcCa.trim() ? this.formAngleQcCa.trim() : null,
                action_concrete: this.formActionConcrete.trim() ? this.formActionConcrete.trim() : null,
                reperes_dates: reperes.length ? reperes : null,
            };
        },

        // Garde anti-écrasement silencieux (Lot 4b, section 2.5-2.6) - vrai si l'admin a
        // effectivement rempli au moins un des 8 champs OU si la fiche portait déjà un résumé
        // composé au chargement (composedSummaryActive, reflet de NewsArticle::hasComposedSummary()
        // côté serveur). Faux pour une fiche encore MACHINE que l'admin n'a jamais touchée : dans
        // ce cas, save() n'envoie PAS composed_summary du tout, pour ne jamais poser composed:true
        // dans le dos de l'admin sur un résumé qu'il n'a pas composé (voir le commentaire de
        // composed_summary_active dans NewsCompositionController::show()).
        hasTouchedComposedSummary() {
            if (this.composedSummaryActive) return true;

            // Comparaison à l'INSTANTANÉ du chargement, jamais au vide : les 8 champs arrivent
            // pré-remplis quand la fiche porte un résumé machine, donc « non vide » ne veut pas
            // dire « touché ». Tant que l'administrateur n'a rien changé, la sérialisation est
            // identique et composed_summary n'est pas envoyé du tout - le résumé machine garde
            // son statut. Voir l'instantané posé dans loadArticle().
            return JSON.stringify(this.buildComposedSummaryPayload()) !== this.composedSummarySnapshot;
        },

        // ── Sources primaires - lignes répétables (Lot 4b, section 2.7) ──────────────────
        addPrimarySource() {
            if (this.formPrimarySources.length >= 10) return;
            this.formPrimarySources.push({ label: '', url: '', note: '' });
        },
        removePrimarySource(index) {
            this.formPrimarySources.splice(index, 1);
        },

        // ── Outils liés - action immédiate (Lot 4b, section 2.5/2.7) ─────────────────────
        // TomSelect sert de simple sélecteur "un outil à la fois" : le catalogue complet
        // (availableTools, chargé une seule fois, indépendant de la fiche) alimente ses options ;
        // aucun item n'y est jamais pré-sélectionné (les outils DÉJÀ liés s'affichent en puces
        // retirables dans le template, jamais dans TomSelect lui-même). Se réinitialise après
        // chaque ajout réussi - prêt pour le suivant.
        initRelatedToolPicker(el) {
            if (!el || el.tomselect || typeof TomSelect === 'undefined') return;

            const instance = new TomSelect(el, {
                valueField: 'slug',
                labelField: 'label',
                searchField: ['label'],
                options: this.availableTools,
                maxOptions: null,
                sortField: { field: 'label', direction: 'asc' },
                placeholder: 'Rechercher un outil de l\'annuaire…',
            });

            instance.on('item_add', (slug) => {
                this.addRelatedTool(slug);
                instance.clear(true);
            });
        },
        async addRelatedTool(slug) {
            if (!this.selectedArticle || !slug) return;
            this.relatedToolSaving = true;
            this.relatedToolError = '';
            try {
                const url = this.endpoints.relatedToolsStoreTemplate.replace('__SLUG__', this.selectedArticle.slug);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ tool_slug: slug }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.relatedToolError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.relatedTools = data.related_tools;
                if (data.updated_at) { this.selectedArticle.updated_at = data.updated_at; }
            } catch (e) {
                this.relatedToolError = 'Erreur réseau : ' + e.message;
            } finally {
                this.relatedToolSaving = false;
            }
        },
        async removeRelatedTool(slug) {
            if (!this.selectedArticle || !slug) return;
            this.relatedToolError = '';
            try {
                const url = this.endpoints.relatedToolsDestroyTemplate.replace('__SLUG__', this.selectedArticle.slug).replace('__TOOL_SLUG__', encodeURIComponent(slug));
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
                    this.relatedToolError = data.error || data.message || ('Impossible de retirer cet outil (HTTP ' + res.status + ').');
                    return;
                }
                this.relatedTools = data.related_tools;
                if (data.updated_at) { this.selectedArticle.updated_at = data.updated_at; }
            } catch (e) {
                this.relatedToolError = 'Erreur réseau : ' + e.message;
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
            this.saveConflict = false;
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
                        // Lot 4a (design doc 2026-09-03, section 2.5/2.7) - champs riches du
                        // panneau toujours visible, rejoignent ce même appel PUT (jamais un
                        // second bouton/endpoint). Chaîne vide -> null : le <select>/l'input vidé
                        // exprime un retrait volontaire, jamais une chaîne vide en base.
                        title: this.formTitle,
                        image_credit: this.formImageCredit || null,
                        nature_original: this.formNatureOriginal || null,
                        niveau_preuve: this.formNiveauPreuve || null,
                        // Lot 4b (section 2.5) - primary_sources REMPLACE intégralement à chaque
                        // save() (jamais une accumulation) : une ligne label/url incomplète n'est
                        // jamais envoyée (mirroir client du "chaque source exige label et url").
                        primary_sources: this.formPrimarySources
                            .map(s => ({ label: (s.label || '').trim(), url: (s.url || '').trim(), note: (s.note || '').trim() }))
                            .filter(s => s.label !== '' && s.url !== ''),
                        // Lot 4b (section 2.5) - composed_summary n'est envoyé QUE si l'admin y a
                        // touché ou si la fiche porte déjà un résumé composé
                        // (hasTouchedComposedSummary() plus bas) : sinon, un save() qui ne touche
                        // QUE image_credit par exemple enverrait quand même un résumé reconstruit
                        // depuis les 8 champs pré-remplis, et poserait composed:true sur un résumé
                        // encore MACHINE - voir le commentaire de composed_summary_active dans
                        // NewsCompositionController::show().
                        ...(this.hasTouchedComposedSummary() ? { composed_summary: this.buildComposedSummaryPayload() } : {}),
                        // Verrou optimiste (section 2.6) - comparé tel quel, côté serveur, à
                        // l'updated_at COURANT de la fiche.
                        expected_updated_at: this.selectedArticle.updated_at,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    // 409 = verrou optimiste (section 2.6) : la fiche a bougé depuis l'ouverture
                    // de cet écran (agent /actu2 ou un autre onglet) - on ne réessaie jamais tout
                    // seul, on propose de recharger (bouton lié à saveConflict, plus haut dans le
                    // template).
                    if (res.status === 409) {
                        this.saveConflict = true;
                    }
                    this.saveError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.saveOk = true;
                setTimeout(() => { this.saveOk = false; }, 2500);
                // Report du verrou optimiste + reflet local des champs riches tout juste
                // enregistrés - même patron léger que publishArticle() plus haut (qui reflète
                // déjà internal_source_text côté client sans réponse serveur dédiée).
                if (data.updated_at) { this.selectedArticle.updated_at = data.updated_at; }
                // Le slug d'un brouillon suit son titre côté serveur : sans ce report, le PROCHAIN
                // enregistrement viserait une URL périmée (404, travail perdu). Défaut mesuré en
                // QC visuelle le 2026-09-03.
                if (data.slug) { this.selectedArticle.slug = data.slug; }
                this.selectedArticle.title = this.formTitle;
                this.selectedArticle.image_credit = this.formImageCredit || null;
                this.selectedArticle.nature_original = this.formNatureOriginal || null;
                this.selectedArticle.niveau_preuve = this.formNiveauPreuve || null;
                // Lot 4b (section 2.6) - reflet local du même garde-fou que
                // hasTouchedComposedSummary() plus bas : si ce save() a envoyé composed_summary,
                // la fiche est désormais composée pour de bon (même si elle ne l'était pas avant).
                if (this.hasTouchedComposedSummary()) { this.composedSummaryActive = true; }
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

        // Lot 4a (design doc 2026-09-03, section 2.6) - "un 409 propose de recharger la fiche" :
        // réutilise loadArticle() tel quel (même endpoint show(), même remplissage des
        // formulaires), plutôt qu'un second chemin de rechargement.
        async reloadAfterConflict() {
            if (!this.selectedArticle) return;
            this.saveConflict = false;
            this.saveError = '';
            await this.loadArticle(this.selectedArticle.id);
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
                const data = await res.json();
                this.formSourceText = '';
                this.selectedArticle.internal_source_text = null;
                // Report du verrou optimiste (section 2.6) - même raison que fetchSource()/
                // addProofPair() plus haut : cette suppression ne doit jamais auto-infliger un
                // 409 à la prochaine sauvegarde des champs riches dans le même onglet.
                if (data.updated_at) { this.selectedArticle.updated_at = data.updated_at; }
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
        if (this.fetchAbortController) {
            this.fetchAbortController.abort();
            this.fetchAbortController = null;
        }
        this.selectedIds = [];
        this.selectedArticle = null;
        this.formSeoTitle = '';
        this.formSummary = '';
        this.formSourceText = '';
        // Lot 4a (design doc 2026-09-03, section 2.5/2.7) - même remise à zéro que les champs
        // historiques ci-dessus, pour ne jamais laisser de valeur de la fiche précédente visible
        // pendant l'état "aucune fiche sélectionnée".
        this.formTitle = '';
        this.formImageCredit = '';
        this.formNatureOriginal = '';
        this.formNiveauPreuve = '';
        this.natureOriginalOptions = [];
        this.niveauPreuveOptions = [];
        // Lot 4b (design doc 2026-09-03, section 2.5/2.7) - même remise à zéro que les champs
        // riches ci-dessus, pour ne jamais laisser le résumé/les sources/les outils de la fiche
        // précédente visibles pendant l'état "aucune fiche sélectionnée".
        this.formHook = '';
        this.formKeyPoints = [];
        this.formWhyImportant = '';
        this.formKeyNumber = '';
        this.formQuoteText = '';
        this.formQuoteAuthor = '';
        this.formAngleQcCa = '';
        this.formActionConcrete = '';
        this.formReperesDates = [];
        this.composedSummaryActive = false;
        this.formPrimarySources = [];
        this.relatedTools = [];
        this.relatedToolError = '';
        this.saveConflict = false;
        this.saveError = '';
        this.saveOk = false;
        this.fetchLoading = false;
        this.fetchError = '';
        this.fetchWarning = '';
        this.fetchSuccessMessage = '';
        this.publishError = '';
        this.publishOk = false;
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

{{-- Lot 4b (design doc 2026-09-03, section 2.5/2.7) - TomSelect pour le sélecteur "Outils liés",
     même paquet déjà chargé par Modules/News/resources/views/admin/articles/edit.blade.php pour
     tool_ids[] (DRY : aucune nouvelle dépendance, réutilisation stricte de l'existant). --}}
@push('plugin-styles')
<link href="{{ asset('build/nobleui/plugins/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('custom-scripts')
<script src="{{ asset('build/nobleui/plugins/tom-select/tom-select.complete.min.js') }}"></script>
@endpush
