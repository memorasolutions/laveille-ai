/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: Mixin Alpine.js partagé "sélecteur d'actualités" (recherche, filtre langue, filtre
 *         couleur, 3 modes de tri, regroupement par acteur/cluster, pastille couleur manuelle,
 *         sélection/retrait). Extrait fidèlement de concentreBuilder() (concentre-builder.blade.php)
 *         pour être réutilisé aussi par videoGoalBuilder() (video-goal-builder.blade.php) - DRY,
 *         un seul bloc de logique au lieu de deux implémentations parallèles.
 * RAISON: v1.117.0 - la page "Générateur d'objectif vidéo" avait une liste basique (checkboxes,
 *         pas de recherche/filtre/tri) alors que le "Concentré IA" a un système riche. On extrait
 *         ce système en composant partagé plutôt que de le dupliquer une 2e fois.
 *
 * Usage dans un composant Alpine (x-data) :
 *
 *   function monBuilder(opts) {
 *       const state = {
 *           // ... état propre à la page ...
 *           loading: { news: false },           // objet PRÉ-EXISTANT - le mixin lit/écrit
 *                                                // seulement loading.news, jamais l'objet entier.
 *           init() {
 *               // ... init existant (le mixin est déjà fusionné à ce stade, voir plus bas) ...
 *           },
 *       };
 *
 *       // Fusionne le mixin AVANT le `return` - donc AVANT qu'Alpine ne rende l'objet réactif
 *       // (Alpine.reactive() enveloppe la valeur RETOURNÉE par cette factory x-data).
 *       // Object.defineProperties (et non Object.assign / spread) préserve les getters réactifs
 *       // (availableItems, filteredAvailable, groupedAvailable) - un spread les évaluerait une
 *       // seule fois comme valeurs figées et casserait la réactivité Alpine.
 *       Object.defineProperties(state, Object.getOwnPropertyDescriptors(NewsArticlePicker({
 *           fetchStrategy: (ctx) => ({ method: 'GET', url: ctx.endpoints.news + '?...' }),
 *       })));
 *
 *       return state;
 *   }
 *
 * PIÈGE ÉVITÉ #1 : ne JAMAIS fusionner via {...NewsArticlePicker(opts), ...autreChose} ni
 * Object.assign(state, NewsArticlePicker(opts)) - les `get xxx() {...}` seraient évalués
 * immédiatement et copiés comme valeurs figées, pas comme des getters. Toujours
 * Object.defineProperties(state, Object.getOwnPropertyDescriptors(...)).
 *
 * PIÈGE ÉVITÉ #2 (vérifié empiriquement, Playwright local) : ne PAS faire cette fusion à
 * l'intérieur de init() sur `this` - avec l'Alpine embarqué par Livewire dans ce projet, `this`
 * dans init() est déjà le proxy réactif, et les propriétés ajoutées via defineProperties dessus
 * ne sont PAS visibles par le reste du template (erreurs "x is not defined" malgré defineProperties
 * qui ne lève aucune exception). La fusion doit se faire sur l'objet BRUT, avant que la factory
 * ne le `return` à Alpine - voir l'exemple ci-dessus.
 */
window.NewsArticlePicker = function (opts) {
    opts = opts || {};

    return {
        // ── État ──────────────────────────────────────────────────────────────
        newsItems: [],
        selectedIds: [],
        fetchError: '',

        // ── Avis de liste (2026-08-23) ───────────────────────────────────────
        // Renseignes par les reponses qui les fournissent (ecran de composition) ; restent nuls
        // ailleurs, ce qui rend l'ajout inoffensif pour les autres pages hotes du composant.
        // Raison d'etre : une liste vide ou des titres restes en anglais doivent DIRE pourquoi.
        // Un ecran muet fait reposer la meme question un mois plus tard.
        jourAffiche: null,
        estRepli: false,
        traductionStatut: null,
        traductionMotif: null,
        searchQuery: '',
        languageFilter: '',
        colorFilter: '',
        // Filtre par compagnie d'IA (2026-08-29) - chaîne vide = toutes compagnies. Alimenté par
        // item.source_company, présent seulement quand la source d'origine est taggée
        // (Modules\News\Database\Seeders\OfficialCompanySourcesSeeder) ; sans quoi
        // availableCompanies reste vide et le sélecteur hôte peut choisir de ne pas l'afficher -
        // inoffensif pour les pages qui n'ont jamais ce champ (concentre-builder, objectif vidéo).
        companyFilter: '',
        sortMode: opts.defaultSortMode || 'cluster', // 'cluster' (défaut, groupage acteur) | 'date' | 'color'
        manualColors: {}, // { [itemId]: '#hexcolor' }
        colorPalette: [
            { label: 'Effacer', value: '' },
            { label: 'Rouge', value: '#ef4444' },
            { label: 'Orange', value: '#f97316' },
            { label: 'Jaune', value: '#eab308' },
            { label: 'Vert', value: '#22c55e' },
            { label: 'Bleu', value: '#3b82f6' },
            { label: 'Violet', value: '#a855f7' },
        ],

        // Message d'avis, calcule a UN seul endroit plutot que recompose dans chaque vue.
        // Rend une chaine vide quand tout est normal : la vue n'affiche alors rien.
        get avisListe() {
            const avis = [];
            if (this.estRepli && this.jourAffiche) {
                avis.push('Aucune actualite collectee aujourd\'hui : affichage du ' + this.jourAffiche + '.');
            }
            if (this.traductionStatut === 'indisponible') {
                avis.push('Traduction des titres indisponible' + (this.traductionMotif ? ' (' + this.traductionMotif + ')' : '') + ' : les titres anglais restent tels quels.');
            }
            return avis.join(' ');
        },

        // ── Fetch des actualités ─────────────────────────────────────────────
        // Mécanique de fetch (loading/erreurs/parsing JSON) commune aux deux pages ; seule la
        // requête elle-même (méthode, URL, body) diffère et est fournie par la page hôte via
        // opts.fetchStrategy(ctx) → { method, url, body?, headers? }.
        // opts.shouldFetch(ctx)     : garde optionnelle avant de déclencher le fetch (ex. dates
        //                             vides côté Objectif Vidéo - le Concentré n'en a pas besoin).
        // opts.onFetchSuccess(ctx, data) : hook optionnel après assignation de newsItems (succès).
        // opts.onFetchSettled(ctx) : hook optionnel exécuté après CHAQUE tentative (succès, HTTP
        //                             en erreur, ou erreur réseau) - ex. Objectif Vidéo y bascule
        //                             son indicateur "hasFetched".
        async fetchNews() {
            if (typeof opts.shouldFetch === 'function' && !opts.shouldFetch(this)) {
                return;
            }
            this.loading.news = true;
            this.fetchError = '';
            try {
                const strategy = typeof opts.fetchStrategy === 'function' ? opts.fetchStrategy(this) : opts.fetchStrategy;
                if (!strategy || !strategy.url) {
                    throw new Error('fetchStrategy invalide (url manquante).');
                }
                const fetchOpts = {
                    method: strategy.method || 'GET',
                    headers: Object.assign({ 'Accept': 'application/json' }, strategy.headers || {}),
                    credentials: 'same-origin',
                };
                if (strategy.body !== undefined) {
                    fetchOpts.headers['Content-Type'] = 'application/json';
                    fetchOpts.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    fetchOpts.headers['X-Requested-With'] = 'XMLHttpRequest';
                    fetchOpts.body = JSON.stringify(strategy.body);
                }
                const res = await fetch(strategy.url, fetchOpts);
                if (!res.ok) {
                    let detail = '';
                    try { const e = await res.json(); detail = e.error || e.message || ''; } catch (_) {}
                    this.fetchError = 'HTTP ' + res.status + (detail ? ' – ' + detail : '');
                    this.newsItems = [];
                    return;
                }
                const data = await res.json();
                this.newsItems = data.items || [];
                this.jourAffiche = data.jour_affiche || null;
                this.estRepli = data.est_repli === true;
                this.traductionStatut = data.traduction_statut || null;
                this.traductionMotif = data.traduction_motif || null;
                if (typeof opts.onFetchSuccess === 'function') opts.onFetchSuccess(this, data);
            } catch (e) {
                this.fetchError = 'Erreur réseau : ' + e.message;
                this.newsItems = [];
            } finally {
                this.loading.news = false;
                if (typeof opts.onFetchSettled === 'function') opts.onFetchSettled(this);
            }
        },

        // ── Getters ──────────────────────────────────────────────────────────
        get availableItems() {
            return this.newsItems.filter(n => !this.selectedIds.includes(n.id));
        },

        // Compagnies distinctes présentes dans le lot COMPLET (newsItems, pas availableItems) :
        // la liste du sélecteur reste stable pendant qu'on sélectionne des actualités, plutôt que
        // de perdre une option au fur et à mesure que ses articles sont ajoutés.
        get availableCompanies() {
            const vues = new Set();
            for (const n of this.newsItems) {
                if (n.source_company) vues.add(n.source_company);
            }
            return [...vues].sort((a, b) => a.localeCompare(b));
        },

        get filteredAvailable() {
            const q = this.searchQuery?.toLowerCase().trim() || '';
            const lang = this.languageFilter || '';
            const colorF = this.colorFilter || '';
            const companyF = this.companyFilter || '';
            const filtered = this.availableItems.filter(n => {
                if (lang && n.source_language !== lang) return false;
                if (companyF && n.source_company !== companyF) return false;
                if (colorF && this.colorForItem(n) !== colorF) return false;
                if (!q) return true;
                return (n.title || '').toLowerCase().includes(q)
                    || (n.title_original || '').toLowerCase().includes(q)
                    || (n.summary || '').toLowerCase().includes(q);
            });

            if (this.sortMode === 'color') {
                // Trie : par couleur (manuel d'abord puis cluster), date desc dans chaque
                return [...filtered].sort((a, b) => {
                    const ca = this.colorForItem(a);
                    const cb = this.colorForItem(b);
                    if (ca !== cb) return ca.localeCompare(cb);
                    return (b.pub_date || '').localeCompare(a.pub_date || '');
                });
            }
            if (this.sortMode === 'cluster') {
                return [...filtered].sort((a, b) => {
                    const ca = a.actor_cluster || '￿';
                    const cb = b.actor_cluster || '￿';
                    if (ca !== cb) return ca.localeCompare(cb);
                    return (b.pub_date || '').localeCompare(a.pub_date || '');
                });
            }
            return filtered;
        },

        // Renvoie une map [cluster|null → items[]] selon l'ordre du tri actuel
        get groupedAvailable() {
            const items = this.filteredAvailable;
            if (this.sortMode !== 'cluster') return [{ cluster: null, items }];
            const groups = [];
            let last = null;
            for (const it of items) {
                const c = it.actor_cluster || 'Autres';
                if (!last || last.cluster !== c) {
                    last = { cluster: c, items: [] };
                    groups.push(last);
                }
                last.items.push(it);
            }
            return groups;
        },

        // ── Méthodes ─────────────────────────────────────────────────────────
        colorForItem(item) {
            if (!item) return '#94a3b8';
            const manual = this.manualColors[item.id];
            if (manual) return manual;
            return item.cluster_color || '#94a3b8';
        },

        setColor(itemId, color) {
            if (color === '' || color === null) {
                delete this.manualColors[itemId];
            } else {
                this.manualColors[itemId] = color;
            }
            this.manualColors = { ...this.manualColors }; // force reactivity
            // saveLocal() = brouillon localStorage, présent seulement côté Concentré : garde
            // optionnelle pour rester réutilisable sur une page qui ne sauvegarde pas de brouillon.
            if (typeof this.saveLocal === 'function') this.saveLocal();
        },

        itemById(id) { return this.newsItems.find(n => n.id === id); },

        selectItem(id) {
            if (!this.selectedIds.includes(id)) {
                this.selectedIds = [...this.selectedIds, id];
                // initSortable() = ré-init du drag-drop SortableJS, présent seulement côté
                // Concentré : garde optionnelle pour rester réutilisable sans SortableJS.
                if (typeof this.initSortable === 'function') {
                    this.$nextTick(() => this.initSortable());
                }
            }
        },

        removeItem(id) {
            this.selectedIds = this.selectedIds.filter(i => i !== id);
        },

        selectAllVisible() {
            const toAdd = this.filteredAvailable.map(n => n.id).filter(id => !this.selectedIds.includes(id));
            this.selectedIds = [...this.selectedIds, ...toAdd];
            if (typeof this.initSortable === 'function') {
                this.$nextTick(() => this.initSortable());
            }
        },
    };
};
