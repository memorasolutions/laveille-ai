{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Option E (skill /100 hors gate) : description + fuseau horaire, partagés entre les 2
     formulaires dédiés (DRY). Destiné à être inclus DANS le <details> "Plus d'options" de chaque
     formulaire (jamais visible par défaut - la recherche pp_search juillet 2026 confirme que la
     réduction du nombre de champs VISIBLES au départ compte plus que le nombre total de champs).

     Combobox fuseau horaire (remplace l'ancien <select> à 3 valeurs fixes) : $timezones est
     fourni par PollManageController::createDate()/createClassic() via TimezoneListService::list()
     - liste complète des fuseaux IANA + America/Montreal (alias legacy, voir le service),
     triée alphabétiquement par label. Partagé automatiquement par @include (mêmes données que
     la vue parente), aucun paramètre supplémentaire à passer ici. --}}
<div class="mb-4">
    <label for="description" class="form-label">Description (optionnel)</label>
    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
</div>

{{-- LOT 1 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 2) : échéance de réponse
     FACULTATIVE, partagée entre les 2 formulaires dédiés (même emplacement DRY que la
     description/le fuseau ci-dessus). Un sondage sans échéance continue de fonctionner
     exactement comme avant - jamais bloquante par défaut (aucune option de verrouillage ici :
     une échéance passée n'est affichée que comme un avertissement côté vote, le vote reste
     accepté). Heure interprétée dans le FUSEAU du sondage choisi ci-dessus, pas celui du
     navigateur ni du serveur (voir PollManageController::store()). --}}
<div class="mb-4">
    <label for="response_deadline_at" class="form-label">Date limite de réponse (optionnel)</label>
    <input type="datetime-local" id="response_deadline_at" name="response_deadline_at" class="form-control"
           style="max-width:320px;" value="{{ old('response_deadline_at') }}">
    <p class="text-muted small mt-1 mb-0">Une fois passée, les votants sont avertis mais peuvent toujours répondre - rien n'est verrouillé.</p>
    @error('response_deadline_at')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
    <label for="timezone_search" class="form-label">Fuseau horaire</label>

    <div
        class="decido-tz-combobox"
        x-data="decidoTimezoneCombobox({
            items: {{ \Illuminate\Support\Js::from($timezones ?? []) }},
            oldValue: {{ \Illuminate\Support\Js::from(old('timezone')) }},
            defaultValue: 'America/Toronto',
        })"
        x-init="init()"
        @click.outside="closeList()"
    >
        <input
            type="text"
            id="timezone_search"
            class="form-control"
            role="combobox"
            aria-controls="timezone_listbox"
            aria-autocomplete="list"
            aria-haspopup="listbox"
            autocomplete="off"
            spellcheck="false"
            x-model="query"
            x-ref="tzSearchInput"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-activedescendant="open ? activeDomId : null"
            @focus="openList()"
            @keydown.down.prevent="moveActive(1)"
            @keydown.up.prevent="moveActive(-1)"
            @keydown.enter="onEnter($event)"
            @keydown.escape.prevent="closeList()"
            @keydown.tab="closeList()"
            placeholder="Rechercher (ville, région, ex. « Toronto »)…"
        >

        {{-- Chevron purement décoratif (aria-hidden) : le role="combobox" porte déjà la
             sémantique de déroulement, ce caret ne fait qu'indiquer visuellement qu'on peut
             dérouler des choix - motif repris de .bd-chip__caret (brain-dump.blade.php).
             Tourne selon `open` (état déjà présent dans le composant Alpine, pas de nouvelle
             variable) ; @mousedown.prevent évite de faire perdre le focus au champ avant que
             le clic ne s'exécute. --}}
        <span
            class="decido-tz-caret"
            :class="{ 'is-open': open }"
            aria-hidden="true"
            @mousedown.prevent="if (open) { closeList(); } else { openList(); $refs.tzSearchInput.focus(); }"
        >▾</span>

        {{-- Valeur réellement soumise : l'identifiant IANA canonique. Le champ texte ci-dessus
             n'affiche que le libellé lisible et sert de filtre - jamais soumis directement. --}}
        <input type="hidden" name="timezone" x-model="selectedId">

        <ul
            id="timezone_listbox"
            role="listbox"
            aria-label="Fuseau horaire"
            class="decido-tz-listbox"
            x-show="open"
            x-cloak
            x-transition.opacity.duration.120ms
        >
            <template x-if="visualOrder.length === 0">
                <li class="decido-tz-empty" role="presentation">Aucun fuseau ne correspond à cette recherche.</li>
            </template>
            {{-- role="presentation" sur le <li> de groupe : retire sa sémantique de liste native
                 tout en laissant les descendants role="option" remonter comme s'ils étaient des
                 enfants directs du role="listbox" (pattern ARIA de groupement sans role="group").
                 L'en-tête de région est aria-hidden : chaque option porte déjà sa région dans son
                 texte visible (ex. "Toronto — Amérique · UTC-04:00"), donc l'information reste
                 accessible sans dupliquer un role="group" + aria-label. --}}
            <template x-for="group in renderGroups" :key="group.region ?? 'flat'">
                <li role="presentation" class="decido-tz-group">
                    <div class="decido-tz-group__label" x-show="group.region" x-text="group.region" aria-hidden="true"></div>
                    <template x-for="item in group.items" :key="item.id">
                        <div
                            role="option"
                            :id="item.domId"
                            :aria-selected="(item.id === selectedId).toString()"
                            :class="{ 'is-active': item.id === activeItemId }"
                            class="decido-tz-option"
                            @mousedown.prevent="select(item)"
                            @mousemove="activeIndex = visualOrder.indexOf(item)"
                        >
                            <span class="decido-tz-option__label" x-text="item.label"></span>
                            <span class="decido-tz-option__meta" x-text="item.region + ' · UTC' + item.offset"></span>
                        </div>
                    </template>
                </li>
            </template>
        </ul>
    </div>
    @error('timezone')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<script>
    // Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
    function decidoTimezoneCombobox({ items, oldValue, defaultValue }) {
        return {
            items: items.map((it) => ({
                ...it,
                // id-safe pour attribut HTML id (les identifiants IANA contiennent des "/").
                domId: 'timezone-opt-' + it.id.replace(/[^a-zA-Z0-9]/g, '-'),
            })),
            open: false,
            query: '',
            selectedId: '',
            activeIndex: -1,
            typing: false,
            _suppressWatch: false,

            init() {
                // old('timezone') a TOUJOURS priorité sur la détection navigateur (FEAT_010,
                // préservation de la resoumission après échec de validation round 27) - ne
                // jamais écraser une valeur déjà choisie par l'utilisateur.
                let initialId = oldValue;
                if (!initialId) {
                    try {
                        const detected = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        if (detected && this.items.some((it) => it.id === detected)) {
                            initialId = detected;
                        }
                    } catch (e) {
                        // Intl indisponible : on retombe sur defaultValue plus bas.
                    }
                }
                if (!initialId || !this.items.some((it) => it.id === initialId)) {
                    initialId = defaultValue;
                }
                this.select(this.findItem(initialId) || this.items[0], false);
                // Le select() ci-dessus passe par setQuery() (donc _suppressWatch = true) avant
                // même que le $watch ne soit enregistré - remis à false explicitement pour ne pas
                // gober silencieusement la toute première frappe réelle de l'utilisateur.
                this._suppressWatch = false;

                this.$watch('query', () => {
                    if (this._suppressWatch) {
                        this._suppressWatch = false;
                        return;
                    }
                    // Frappe réelle de l'utilisateur (pas une réécriture programmatique après
                    // select()/closeList()) : on repasse en mode filtrage plat.
                    this.typing = true;
                    this.open = true;
                    this.activeIndex = this.visualOrder.length ? 0 : -1;
                });
            },

            findItem(id) {
                return this.items.find((it) => it.id === id) || null;
            },

            normalize(s) {
                return (s || '')
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase();
            },

            get filtered() {
                // Tant que l'utilisateur n'a pas RÉELLEMENT tapé (this.typing), `query` ne fait
                // qu'afficher le libellé de la sélection courante (ex. focus sans frappe après
                // une sélection précédente) - filtrer sur ce texte masquerait à tort la liste
                // complète au simple focus. Le filtre ne s'applique qu'en mode frappe.
                if (!this.typing) return this.items;

                const q = this.normalize(this.query);
                if (!q) return this.items;

                return this.items.filter(
                    (it) =>
                        this.normalize(it.label).includes(q) ||
                        this.normalize(it.region).includes(q) ||
                        this.normalize(it.id).includes(q)
                );
            },

            // Groupement visuel par région uniquement en mode "parcours" (query vide affichée
            // telle quelle, pas de frappe active) - pendant la frappe, liste plate non groupée
            // (spec confirmée : le filtre reste plat pendant la recherche).
            get renderGroups() {
                if (this.typing) {
                    return [{ region: null, items: this.filtered }];
                }
                const out = [];
                const byRegion = {};
                this.filtered.forEach((it) => {
                    if (!byRegion[it.region]) {
                        byRegion[it.region] = { region: it.region, items: [] };
                        out.push(byRegion[it.region]);
                    }
                    byRegion[it.region].items.push(it);
                });
                out.sort((a, b) => a.region.localeCompare(b.region, 'fr'));
                return out;
            },

            // Ordre de navigation clavier = ordre visuel réel (groupé par région en mode
            // parcours, plat en mode frappe) - jamais l'ordre alphabétique brut de `filtered`
            // quand celui-ci diffère de l'ordre affiché, sinon les flèches haut/bas feraient
            // sauter la surbrillance vers une option d'un autre groupe visuellement éloignée.
            get visualOrder() {
                const out = [];
                this.renderGroups.forEach((g) => g.items.forEach((it) => out.push(it)));
                return out;
            },

            get activeItemId() {
                const list = this.visualOrder;
                return this.activeIndex >= 0 && this.activeIndex < list.length ? list[this.activeIndex].id : null;
            },

            get activeDomId() {
                const list = this.visualOrder;
                return this.activeIndex >= 0 && this.activeIndex < list.length ? list[this.activeIndex].domId : null;
            },

            setQuery(value) {
                this._suppressWatch = true;
                this.query = value;
            },

            openList() {
                this.open = true;
                if (this.activeIndex === -1 || this.activeIndex >= this.visualOrder.length) {
                    const idx = this.visualOrder.findIndex((it) => it.id === this.selectedId);
                    this.activeIndex = idx !== -1 ? idx : this.visualOrder.length ? 0 : -1;
                }
            },

            closeList() {
                this.open = false;
                this.typing = false;
                // Referme sans changer la sélection (Échap, clic ailleurs, Tab) : le champ texte
                // revient au libellé de la sélection réellement soumise.
                const current = this.findItem(this.selectedId);
                this.setQuery(current ? current.label : '');
            },

            moveActive(delta) {
                if (!this.open) {
                    this.openList();
                    return;
                }
                const list = this.visualOrder;
                if (!list.length) return;
                this.activeIndex = (this.activeIndex + delta + list.length) % list.length;
                this.$nextTick(() => {
                    const el = document.getElementById(list[this.activeIndex].domId);
                    if (el) el.scrollIntoView({ block: 'nearest' });
                });
            },

            onEnter(e) {
                // .prevent conditionnel : Entrée ne doit intercepter la soumission du formulaire
                // QUE si la liste est ouverte (sélection en cours) - fermée, Entrée doit garder
                // son comportement natif (soumission du formulaire comme n'importe quel autre
                // champ texte).
                if (!this.open) return;
                e.preventDefault();
                const list = this.visualOrder;
                if (this.activeIndex >= 0 && list[this.activeIndex]) {
                    this.select(list[this.activeIndex]);
                } else {
                    this.closeList();
                }
            },

            select(item, closeAfter = true) {
                if (!item) return;
                this.selectedId = item.id;
                this.setQuery(item.label);
                if (closeAfter) this.closeList();
            },
        };
    }
</script>

<style>
    .decido-tz-combobox { position: relative; }
    /* Chevron de déroulement (jamais un <select> natif ici, cf. commentaire du champ plus
       haut) : padding-right sur le champ pour que le texte long (placeholder ou saisie) ne
       passe jamais sous le caret. Un seul combobox non natif dans tout le projet : ces règles
       restent ici plutôt que dans charte.css (sur-ingénierie évitée, cf. instructions). */
    #timezone_search { padding-right: 34px; }
    .decido-tz-caret {
        position: absolute;
        top: 50%;
        right: 12px;
        margin-top: -6px;
        font-size: 12px;
        line-height: 1;
        color: #374151;
        cursor: pointer;
        transition: transform .15s ease;
        user-select: none;
    }
    .decido-tz-caret.is-open { transform: rotate(180deg); }
    @media (prefers-reduced-motion: reduce) {
        .decido-tz-caret { transition-duration: 0s; }
    }
    .decido-tz-listbox {
        position: absolute;
        z-index: 20;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        max-height: 320px;
        overflow-y: auto;
        margin: 0;
        padding: 6px;
        list-style: none;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: var(--r-base, 8px);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.16);
    }
    .decido-tz-group { padding: 2px 0; }
    .decido-tz-group__label {
        padding: 6px 10px 2px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        /* #374151 (pas #6b7280) : WCAG AAA 7:1 obligatoire sur ce projet - #6b7280/blanc ne fait
           que ~4.8:1 (passe AA, échoue AAA), #374151/blanc ~10.3:1. */
        color: #374151;
    }
    .decido-tz-option {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        min-height: 44px;
        border-radius: 6px;
        cursor: pointer;
        color: #0b1220;
    }
    .decido-tz-option__label { font-weight: 600; font-size: 14px; }
    .decido-tz-option__meta { font-size: 12px; color: #374151; white-space: nowrap; }
    .decido-tz-option.is-active,
    .decido-tz-option:hover {
        background: var(--c-primary, #064E5A);
    }
    .decido-tz-option.is-active .decido-tz-option__label,
    .decido-tz-option:hover .decido-tz-option__label,
    .decido-tz-option.is-active .decido-tz-option__meta,
    .decido-tz-option:hover .decido-tz-option__meta {
        color: #ffffff;
    }
    .decido-tz-option[aria-selected="true"] {
        font-weight: 700;
    }
    .decido-tz-empty {
        padding: 12px 10px;
        color: #374151;
        font-size: 13px;
    }
    #timezone_search:focus-visible {
        outline: 2px solid var(--c-danger, #ea580c);
        outline-offset: 2px;
    }
    @media (prefers-reduced-motion: reduce) {
        .decido-tz-listbox { transition-duration: 0s !important; }
    }
</style>
