@extends('backoffice::layouts.admin', ['title' => 'Générateur d\'objectif vidéo', 'subtitle' => 'Sélectionne des actualités et génère un objectif de vidéo pour le Prompteur'])

@push('styles')
{{-- Composant partagé "sélecteur d'actualités" (recherche/filtres/tri/cluster/couleur),
     le même que celui utilisé par admin/concentre-builder.blade.php — voir public/assets/admin/. --}}
<link rel="stylesheet" href="{{ asset('assets/admin/news-article-picker.css') }}?v={{ config('version.semver') }}">
<style>
    .cb-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px; margin-bottom:18px; }
    .cb-section-title { font-weight:700; color:#0B7285; font-size:15px; margin-bottom:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .cb-counter { background:#0B7285; color:#fff; padding:2px 10px; border-radius:10px; font-size:12px; font-weight:700; }
    .cb-empty { text-align:center; color:#57606f; padding:24px; font-style:italic; }
    .cb-btn { background:#0B7285; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; min-height:44px; min-width:44px; display:inline-flex; align-items:center; gap:6px; justify-content:center; }
    .cb-btn:hover:not(:disabled) { background:#075f6f; }
    /* WCAG 2.2 AAA : #6b7280 précédent = 4.83:1 (échec AAA, seuil 7:1). #475569 + texte blanc =
       7.58:1. Même correctif que concentre-builder.blade.php (composant partagé) - règle explicite
       dupliquée pour .cb-btn-secondary:disabled, sinon le texte teal hérité de .cb-btn-secondary
       retombe à un contraste insuffisant sur fond gris. */
    .cb-btn:disabled { background:#475569; color:#fff; cursor:not-allowed; }
    .cb-btn-secondary { background:#fff; color:#0B7285; border:1.5px solid #0B7285; }
    .cb-btn-secondary:hover:not(:disabled) { background:#0B7285; color:#fff; }
    .cb-btn-secondary:disabled { background:#475569; color:#fff; border-color:#475569; cursor:not-allowed; }
    .cb-btn:focus-visible, .cb-btn-secondary:focus-visible, input:focus-visible, textarea:focus-visible {
        outline: 3px solid #064E5A; outline-offset: 2px;
    }
    [x-cloak] { display:none !important; }

    /* Colonne "sélectionnées" simplifiée : réutilise .cb-news-item.is-selected du composant
       partagé (public/assets/admin/news-article-picker.css) mais sans handle/position-badge —
       l'ordre n'a pas d'importance pour la synthèse IA, donc pas de glisser-déposer ici. */
    .vgb-selected-item .cb-actions { margin-top: 0; }

    .vgb-pre {
        background:#0f172a; color:#e2e8f0; padding:14px; border-radius:8px; max-height:340px; overflow:auto;
        font-family:'SF Mono','Monaco','Consolas',monospace; font-size:13px; line-height:1.55; white-space:pre-wrap;
        word-break:break-word; width:100%; border:none; resize:vertical;
    }
    .vgb-error {
        color:#8f1d1d; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:10px 14px;
        font-weight:600; font-size:13px; margin-top:10px;
    }
</style>
@endpush

@section('content')
<div
    x-data="videoGoalBuilder({
        defaultStart: @js($defaultStart),
        defaultEnd: @js($defaultEnd),
        newsEndpoint: @js(route('admin.news.video-goal.news')),
        generateEndpoint: @js(route('admin.news.video-goal.generate')),
    })"
    x-init="init()"
>
    {{-- En-tête --}}
    <div class="cb-card">
        <h1 style="font-size:20px; font-weight:800; color:#064E5A; margin-bottom:6px;">🎬 Générateur d'objectif vidéo</h1>
        <p style="color:#374151; font-size:14px; margin:0;">
            Sélectionnez des actualités pour générer automatiquement un objectif de vidéo à coller dans le Prompteur.
        </p>
    </div>

    {{-- Plage de dates libre --}}
    <div class="cb-card">
        <div class="cb-section-title">📅 Plage de dates</div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold" for="vgb-date-start">Du</label>
                <input type="date" id="vgb-date-start" class="form-control" x-model="dateStart" :max="dateEnd">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold" for="vgb-date-end">Au</label>
                <input type="date" id="vgb-date-end" class="form-control" x-model="dateEnd" :min="dateStart" :max="todayDate()">
            </div>
            <div class="col-md-4">
                <button type="button" class="cb-btn" @click="fetchNews()" :disabled="loading.news || !dateStart || !dateEnd">
                    <span x-show="!loading.news">📰 Charger les actualités</span>
                    <span x-show="loading.news" x-cloak>⏳ Chargement…</span>
                </button>
            </div>
        </div>
        <div class="vgb-error" role="alert" aria-live="polite" x-show="fetchError" x-cloak x-text="'⚠ ' + fetchError"></div>
    </div>

    {{-- Sélection d'actualités : même système que /admin/concentre-builder (recherche, filtre
         langue, filtre couleur, tri, regroupement par acteur) — colonne gauche partagée,
         colonne droite "sélection" simplifiée (pas de glisser-déposer, l'ordre n'a pas
         d'importance pour la synthèse IA contrairement au Concentré). --}}
    <div class="row">
        <div class="col-md-6">
            @include('news::admin.partials.news-article-picker')
        </div>

        <div class="col-md-6">
            <div class="cb-card">
                <div class="cb-section-title">
                    🎯 Actualités sélectionnées
                    <span class="cb-counter" style="background:#064E5A;" x-text="selectedIds.length + ' actualité' + (selectedIds.length > 1 ? 's' : '') + ' sélectionnée' + (selectedIds.length > 1 ? 's' : '')"></span>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <template x-for="id in selectedIds" :key="'sel-' + id">
                        <div class="cb-news-item is-selected vgb-selected-item" :style="'border-left-color:' + colorForItem(itemById(id))">
                            <img :src="itemById(id)?.favicon" loading="lazy" class="cb-fav" alt="" onerror="this.style.display='none'">
                            <div style="flex:1; min-width:0;">
                                <div class="cb-title" x-text="itemById(id)?.title || '(introuvable)'"></div>
                                <div class="cb-meta">
                                    <span x-text="itemById(id)?.source_name"></span> · <span x-text="itemById(id)?.pub_date_short"></span>
                                </div>
                            </div>
                            <a :href="itemById(id)?.site_url" target="_blank" rel="noopener" title="Ouvrir dans un nouvel onglet" style="color:#0B7285; font-size:14px; padding:4px 8px; text-decoration:none;">🔗</a>
                            <button type="button" @click="removeItem(id)" title="Retirer de la sélection" aria-label="Retirer cette actualité de la sélection" style="background:none; border:none; color:#dc2626; font-size:18px; cursor:pointer; padding:2px 8px; line-height:1; min-height:32px; min-width:32px;">×</button>
                        </div>
                    </template>
                </div>
                <div class="cb-empty" x-show="selectedIds.length === 0" x-cloak>
                    Aucune actualité sélectionnée. Clique « + Ajouter » dans la colonne de gauche.
                </div>
            </div>
        </div>
    </div>

    {{-- Génération de l'objectif --}}
    <div class="cb-card">
        <div class="cb-section-title">✨ Objectif de vidéo généré</div>
        <div class="mb-2 d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="cb-btn" @click="generateGoal()" :disabled="selectedIds.length === 0 || loading.generate">
                <span x-show="!loading.generate">⚡ Générer l'objectif</span>
                <span x-show="loading.generate" x-cloak>⏳ Génération en cours (peut prendre quelques secondes)…</span>
            </button>
            <button type="button" class="cb-btn cb-btn-secondary" @click="copyGoal()" :disabled="!generatedGoal" x-show="generatedGoal" x-cloak>📋 Copier</button>
            <a href="/outils/prompteur" target="_blank" rel="noopener noreferrer" class="cb-btn cb-btn-secondary">Ouvrir le Prompteur →</a>
        </div>

        <div class="vgb-error" role="alert" aria-live="polite" x-show="generateError" x-cloak x-text="'⚠ ' + generateError"></div>

        <div aria-live="polite">
            <label for="vgb-result" class="form-label small fw-bold" x-show="generatedGoal || loading.generate" x-cloak>Texte à coller dans le champ « Objectif de la vidéo »</label>
            <textarea id="vgb-result" class="vgb-pre" rows="6" readonly x-show="generatedGoal" x-cloak x-text="generatedGoal"></textarea>
            <div class="cb-empty" x-show="!generatedGoal && !loading.generate" x-cloak>
                L'objectif généré apparaîtra ici. Sélectionne au moins une actualité puis clique « Générer l'objectif ».
            </div>
        </div>
    </div>
</div>

{{-- Ticket #2210 (2026-09-05) : le <script defer> de public/assets/admin/news-article-picker.js
     a déménagé dans news::admin.partials.news-article-picker (inclus plus haut, ligne 90), sous
     la directive @assets - injecté par Livewire AVANT le boot d'Alpine, contrairement à un
     <script> posé ici qui se charge APRÈS (même classe de défaut que l'historique 1.65.302). --}}

<script>
function videoGoalBuilder(opts) {
    const state = {
        dateStart: opts.defaultStart,
        dateEnd: opts.defaultEnd,
        hasFetched: false,
        generateError: '',
        generatedGoal: '',
        loading: { news: false, generate: false },
        endpoints: { news: opts.newsEndpoint, generate: opts.generateEndpoint },

        // Consomme un éventuel import poussé depuis /admin/concentre-builder (bouton "Envoyer vers
        // Objectif vidéo", mécanisme 100% client-side via sessionStorage — voir pushToVideoGoal()
        // dans concentre-builder.blade.php). Clé lue une seule fois puis retirée immédiatement
        // (removeItem) : un rafraîchissement de cette page ne redéclenche pas l'import.
        // Garde d'idempotence (_initDone) : x-init="init()" est appelé DEUX FOIS sur ce layout
        // (morph Alpine/Livewire au chargement, vérifié en local) — sans cette garde, le 2e appel
        // retombait sur fetchNews() et écrasait silencieusement l'import qui venait de réussir.
        init() {
            if (this._initDone) return;
            this._initDone = true;

            const importData = sessionStorage.getItem('lv_vgb_import');
            if (importData) {
                // removeItem AVANT le parse (consommation unique garantie même si le JSON est
                // corrompu) - sinon une clé corrompue reste bloquée indéfiniment et re-échoue à
                // chaque chargement de page jusqu'au prochain pushToVideoGoal().
                sessionStorage.removeItem('lv_vgb_import');
                try {
                    const payload = JSON.parse(importData);

                    this.newsItems = payload.items || [];
                    this.selectedIds = payload.selectedIds || [];
                    this.hasFetched = true;

                    const count = this.selectedIds.length;
                    const plural = count > 1 ? 's' : '';
                    const message = `${count} actualité${plural} importée${plural} depuis le Concentré.`;
                    Livewire.dispatch('toast', { type: 'success', message });
                    return;
                } catch (e) {
                    // JSON corrompu : on ignore silencieusement et on continue normalement.
                }
            }
            this.fetchNews();
        },

        todayDate() { return new Date().toISOString().split('T')[0]; },

        // fetchNews(), newsItems/selectedIds (état), availableItems/filteredAvailable/
        // groupedAvailable (getters), recherche/filtres/tri/couleurs, colorForItem()/setColor()/
        // itemById()/selectItem()/removeItem()/selectAllVisible() proviennent maintenant du mixin
        // partagé NewsArticlePicker() fusionné ci-dessous (avant le `return`) — DRY avec
        // admin/concentre-builder.blade.php, voir public/assets/admin/news-article-picker.js.

        async generateGoal() {
            if (this.selectedIds.length === 0) return;
            this.loading.generate = true;
            this.generateError = '';
            this.generatedGoal = '';
            try {
                const res = await fetch(this.endpoints.generate, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ article_ids: this.selectedIds }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.generateError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.generatedGoal = data.goal || '';
            } catch (e) {
                this.generateError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.generate = false;
            }
        },

        // NOTE : window.copyToClipboard (Modules/FrontTheme/.../master.blade.php) n'est PAS chargé
        // dans le layout backoffice (thème réellement rendu : themes/backend/layouts/admin.blade.php).
        // Toast géré par ce thème via Livewire.dispatch('toast', {...}) - écouté par
        // Livewire.on('toast', ...) dans backoffice::themes.backend.partials.toast (PAS un
        // CustomEvent DOM 'notification-toast' : ce mécanisme, utilisé avant, ne fait rien - aucun
        // listener DOM n'existe, trouvé lors de la passe adversariale /100 du 2026-07-21).
        async copyGoal() {
            if (!this.generatedGoal) return;
            try {
                await navigator.clipboard.writeText(this.generatedGoal);
                Livewire.dispatch('toast', { type: 'success', message: "Objectif copié dans le presse-papiers !" });
            } catch (e) {
                Livewire.dispatch('toast', { type: 'error', message: 'Copie impossible - copie le texte manuellement.' });
            }
        },
    };

    // Fusionne le mixin partagé (état + getters + méthodes du sélecteur d'actualités) sur l'objet
    // AVANT qu'Alpine ne le rende réactif (Alpine.reactive() enveloppe la valeur RETOURNÉE par
    // cette factory x-data, pas l'objet manipulé après coup). Object.defineProperties (PAS
    // Object.assign ni spread) préserve les `get xxx() {...}` comme de vrais getters — un spread
    // les figerait en valeurs statiques et casserait la réactivité.
    // NOTE IMPORTANTE : fusionner APRÈS coup dans init() (sur `this`, déjà réactif à ce stade) ne
    // fonctionne PAS de façon fiable avec l'Alpine embarqué par Livewire dans ce projet — vérifié
    // en local (Playwright) : les propriétés ajoutées via defineProperties sur `this` à l'intérieur
    // d'init() n'étaient PAS visibles par le reste du template (erreurs "x is not defined"). D'où
    // la fusion ICI, avant le `return`, sur l'objet encore brut.
    //
    // Garde de disponibilité (ticket #2210, 2026-09-05) - même forme que initRelatedToolPicker()
    // (typeof TomSelect === 'undefined' dans composition-builder.blade.php) : si
    // window.NewsArticlePicker manque malgré le chargement via la directive Livewire "assets"
    // (voir news-article-picker.blade.php), on NE LAISSE PAS le ReferenceError remonter - il ferait
    // échouer videoGoalBuilder() au complet et Alpine cascaderait en dizaines d'erreurs sur
    // CHAQUE directive du composant (x-data jamais retourné). Sans plafond de réessai : un script
    // absent le reste, retenter ne change rien. Mode dégradé assumé : le sélecteur d'actualités
    // reste inopérant, le reste du panneau demeure utilisable.
    if (typeof NewsArticlePicker === 'undefined') {
        console.error('[video-goal-builder] window.NewsArticlePicker introuvable (script assets/admin/news-article-picker.js non chargé) - sélecteur d\'actualités inopérant, reste du panneau conservé.');
    } else {
        Object.defineProperties(state, Object.getOwnPropertyDescriptors(NewsArticlePicker({
            // Objectif Vidéo n'a pas de plage par défaut garantie côté UI (l'utilisateur peut vider
            // les champs) : on ne fetch que si les deux dates sont renseignées, comme le faisait
            // l'ancien fetchNews() de cette page (`if (!dateStart || !dateEnd) return;`).
            shouldFetch: (ctx) => !!(ctx.dateStart && ctx.dateEnd),
            fetchStrategy: (ctx) => ({
                method: 'POST',
                url: ctx.endpoints.news,
                body: { date_start: ctx.dateStart, date_end: ctx.dateEnd },
            }),
            // Purge la sélection des actualités qui ne sont plus dans la nouvelle plage chargée
            // (même comportement que l'ancien fetchNews() de cette page).
            onFetchSuccess: (ctx) => {
                ctx.selectedIds = ctx.selectedIds.filter(id => ctx.newsItems.some(n => n.id === id));
            },
            onFetchSettled: (ctx) => { ctx.hasFetched = true; },
        })));
    }

    return state;
}
</script>
@endsection
