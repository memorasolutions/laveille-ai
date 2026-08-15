{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', "{$poll->title} · " . config('app.name'))
{{-- Round 10 (skill /100) : page privée partagée par lien, jamais destinée à l'indexation - un
     sondage devenu public (DECIDO_UNDER_CONSTRUCTION=false) exposerait sinon les pseudonymes et
     choix de vote dans les résultats de recherche. --}}
@section('page_noindex', true)
@section('meta_description', "Participe au sondage Décido « {$poll->title} » pour aider à trouver le bon moment ou le bon choix.")
@section('breadcrumb')
    {{-- Priorité 1 (docs/specs/2026-08-15-decido-page-vote-design.md) : le titre du sondage
         apparaissait deux fois au premier écran mobile (h2 de la bannière ICI + h1 juste en
         dessous). breadcrumbTitle n'est plus transmis (le fil d'Ariane garde son rôle de
         navigation via breadcrumbItems, le h2 - désormais vide - est masqué en CSS scopé
         .decido-vote-hero ci-dessous) ; le h1 plus bas reste la SEULE occurrence visuelle du
         titre. La bannière (normalement min-height 400px/250px, voir _page-title.scss) est aussi
         réduite, mais SEULEMENT sur cette page (wrapper .decido-vote-hero, style scopé à cette vue). --}}
    <div class="decido-vote-hero">
        @include('fronttheme::partials.breadcrumb', ['breadcrumbItems' => [__('Outils'), $poll->title]])
    </div>
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="h2 mb-2">{{ $poll->title }}</h1>
                @if($poll->description)
                    <p class="text-muted mb-4">{{ $poll->description }}</p>
                @endif

                @if($voterToken)
                    <div class="alert alert-info mb-4">
                        Tu as déjà voté sous ce lien - modifie ton choix ci-dessous si besoin.
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('decido.vote.store', ['slug' => $poll->share_slug]) }}">
                    @csrf

                    <div class="mb-4">
                        <label for="voter_pseudonym" class="form-label">Ton nom ou pseudonyme <span class="text-danger">*</span></label>
                        <input type="text" id="voter_pseudonym" name="voter_pseudonym" class="form-control"
                               value="{{ old('voter_pseudonym') }}" required>
                        @error('voter_pseudonym')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Round 8 (skill /100) : les cases à cocher/radios natives (.form-check) mesuraient
                         14×14px avec un libellé cliquable ~22×21px - bien sous la cible tactile WCAG 2.5.5
                         AAA de 44×44px, particulièrement problématique pour un sondage de dates multi-jours
                         (48+ cartes × 3 boutons = 144+ cibles trop petites pour voter au pouce sur mobile).
                         Remplacé par des libellés pleine taille (input visually-hidden dans le label,
                         :has(input:checked)/:has(input:focus-visible) en CSS pur, sans JS) - même pattern
                         que le sélecteur "Type de sondage" de create.blade.php. --}}
                    @if($poll->vote_mode->value === 'yes_no_maybe')
                        {{-- Volet B (adaptation de fuseau à l'affichage) : x-data porte l'état du
                             composant Alpine ; starts_at/ends_at sont DÉJÀ en UTC en base (voir
                             SlotGenerationService), donc toIso8601String() suffit côté client sans
                             conversion serveur additionnelle. Purement cosmétique - le formulaire
                             soumis reste inchangé (vote toujours par option_id). --}}
                        <div class="mb-4"
                             x-data="decidoSlotTimezone(@js($poll->timezone))"
                             x-init="init()">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <h2 class="h4 mb-0">Tes disponibilités</h2>
                                <button type="button"
                                        class="decido-tz-toggle-btn"
                                        x-show="showToggle"
                                        x-cloak
                                        @click="toggleDisplay()"
                                        :aria-pressed="displayMode === 'local' ? 'true' : 'false'">
                                    <span x-text="displayMode === 'local' ? 'Afficher l’heure du sondage' : 'Afficher mon heure locale'"></span>
                                </button>
                            </div>

                            {{-- Repli explicite si Intl.DateTimeFormat ne peut pas détecter le
                                 fuseau du navigateur (vieux navigateur, mode restreint...). --}}
                            <template x-if="showManualSelector">
                                <div class="mb-3 decido-tz-manual">
                                    <label for="decido_manual_tz" class="form-label">
                                        Ton fuseau horaire n’a pas pu être détecté automatiquement - choisis-le pour voir tes heures locales :
                                    </label>
                                    <select id="decido_manual_tz" class="form-select" x-model="manualTimezone" @change="applyManualTimezone()">
                                        <option value="">Choisir un fuseau...</option>
                                        <template x-for="tz in commonTimezones" :key="tz.value">
                                            <option :value="tz.value" x-text="tz.label"></option>
                                        </template>
                                    </select>
                                    <a href="https://www.iana.org/time-zones" target="_blank" rel="noopener" class="small d-inline-block mt-1">Voir la liste complète des fuseaux</a>
                                </div>
                            </template>

                            {{-- Annonce le changement de bascule une seule fois (pas à chaque
                                 frappe) aux lecteurs d'écran - jamais peuplée au chargement pour
                                 ne pas spammer. --}}
                            <div aria-live="polite" class="visually-hidden" x-text="announcement"></div>

                            @php
                                // Priorité 2 (docs/specs/2026-08-15-decido-page-vote-design.md) : regroupement
                                // PAR JOURNÉE, calculé ici en vue - pure présentation sur une collection déjà
                                // chargée ($poll->load('options.votes') dans PublicPollController::show()),
                                // aucune requête SQL additionnelle. Toujours dans le fuseau du SONDAGE
                                // ($poll->timezone), jamais celui du navigateur : le sélecteur de fuseau JS
                                // (decidoSlotTimezone plus bas) reste seul responsable de la conversion pour
                                // le fuseau du VOTANT, et réécrit alors le libellé COMPLET (le jour civil peut
                                // changer sous conversion, ce qui invaliderait un regroupement par journée du
                                // sondage) - comportement JS pré-existant, non touché ici.
                                $groupedByDay = $options->groupBy(function ($option) use ($poll) {
                                    return $option->starts_at
                                        ? \Carbon\Carbon::parse($option->starts_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->format('Y-m-d')
                                        : 'sans-date';
                                });
                            @endphp
                            @foreach($groupedByDay as $dayKey => $dayOptions)
                                @php
                                    $dayHeading = null;
                                    if ($dayKey !== 'sans-date') {
                                        $dayHeading = ucfirst(
                                            \Carbon\Carbon::parse($dayOptions->first()->starts_at->format('Y-m-d H:i:s'), 'UTC')
                                                ->timezone($poll->timezone)
                                                ->locale('fr')
                                                ->isoFormat('dddd D MMMM')
                                        );
                                    }
                                @endphp
                                <div class="decido-day-group">
                                    @if($dayHeading)
                                        <h3 class="decido-day-heading">{{ $dayHeading }}</h3>
                                    @endif
                                    @foreach($dayOptions as $option)
                                        @php
                                            // starts_at/ends_at sont stockés en UTC brut, mais config('app.timezone')
                                            // = America/Toronto fait que le cast Eloquent datetime réinterprète à tort
                                            // la valeur comme déjà en heure de Québec sans conversion : reparser
                                            // explicitement la valeur brute comme UTC est requis (même cause racine
                                            // que le fix PollExportService::exportIcs() v1.107.1 - bug trouvé ici par
                                            // vérification visuelle Playwright, écart constant de 4h confirmé avant fix).
                                            $slotStartUtc = $option->starts_at ? \Carbon\Carbon::parse($option->starts_at->format('Y-m-d H:i:s'), 'UTC')->toIso8601String() : null;
                                            $slotEndUtc = $option->ends_at ? \Carbon\Carbon::parse($option->ends_at->format('Y-m-d H:i:s'), 'UTC')->toIso8601String() : null;

                                            // La date n'est plus répétée à chaque ligne (mesure du 15 août : 5
                                            // répétitions identiques pour samedi/dimanche) - l'en-tête de journée
                                            // ci-dessus l'annonce une seule fois. $option->label (généré par
                                            // SlotGenerationService, complet, avec suffixe UTC de désambiguïsation
                                            // DST le cas échéant) reste posé en attribut data-full-label pour le
                                            // JS de bascule de fuseau (decidoSlotTimezone), qui en a besoin comme
                                            // point de retour "heure du sondage".
                                            $displayLabel = $option->label;
                                            if ($dayHeading && $option->starts_at && $option->ends_at) {
                                                $hoursStart = \Carbon\Carbon::parse($option->starts_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->locale('fr');
                                                $hoursEnd = \Carbon\Carbon::parse($option->ends_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->locale('fr');
                                                $displayLabel = $hoursStart->isoFormat('H [h] mm').' - '.$hoursEnd->isoFormat('H [h] mm');
                                                // Conserve le suffixe de désambiguïsation DST éventuel du libellé
                                                // complet (ex. " (UTC-04:00)") - rare, mais l'omettre rendrait deux
                                                // créneaux distincts strictement identiques à l'affichage un jour
                                                // de changement d'heure.
                                                $suffixPos = strpos($option->label, ' (UTC');
                                                if ($suffixPos !== false) {
                                                    $displayLabel .= substr($option->label, $suffixPos);
                                                }
                                            }

                                            // Priorité 3 : totaux par créneau. $option->votes est déjà chargé en
                                            // mémoire par $poll->load('options.votes') (contrôleur) - countBy() sur
                                            // une collection déjà hydratée ne déclenche AUCUNE requête SQL
                                            // supplémentaire (0 requête par créneau, pas de N+1).
                                            $voteCounts = $option->votes->countBy('value');
                                        @endphp
                                        <div class="card mb-3 decido-slot"
                                             data-starts-at-utc="{{ $slotStartUtc }}"
                                             data-ends-at-utc="{{ $slotEndUtc }}"
                                             data-full-label="{{ $option->label }}">
                                            <div class="card-body">
                                                <h4 class="h5 mb-1 decido-slot-label">{{ $displayLabel }}</h4>
                                                <p class="decido-slot-secondary text-muted small mb-3" style="display:none"></p>
                                                {{-- Totaux par créneau (jamais les noms) : même langage visuel que
                                                     Modules/Decido/resources/views/manage/partials/results-content.blade.php
                                                     (badges succès/avertissement/danger), réutilisé pour rester DRY. --}}
                                                <div class="d-flex flex-wrap gap-2 mb-2 decido-slot-totals">
                                                    <span class="badge" style="background-color: var(--sys-success-bg); color: var(--sys-success); border: 1px solid var(--sys-success); display: inline-flex; align-items: center; gap: 4px;">
                                                        ✓ {{ $voteCounts['yes'] ?? 0 }} oui
                                                    </span>
                                                    <span class="badge" style="background-color: var(--sys-warning-bg); color: var(--sys-warning); border: 1px solid var(--sys-warning); display: inline-flex; align-items: center; gap: 4px;">
                                                        ? {{ $voteCounts['maybe'] ?? 0 }} peut-être
                                                    </span>
                                                    <span class="badge" style="background-color: #fff; color: var(--sys-danger); border: 1px solid var(--sys-danger); display: inline-flex; align-items: center; gap: 4px;">
                                                        ✕ {{ $voteCounts['no'] ?? 0 }} non
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2 decido-vote-pills">
                                                    @foreach(['yes' => 'Oui', 'maybe' => 'Peut-être', 'no' => 'Non'] as $value => $label)
                                                        <label class="decido-vote-pill" for="vote_{{ $option->id }}_{{ $value }}">
                                                            <input class="visually-hidden" type="radio"
                                                                   name="votes[{{ $option->id }}]"
                                                                   id="vote_{{ $option->id }}_{{ $value }}"
                                                                   value="{{ $value }}"
                                                                   {{ (($existingVotes[$option->id] ?? null) === $value) ? 'checked' : '' }}>
                                                            {{ $label }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error("votes.{$option->id}")
                                                    <div class="text-danger mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            {{-- Round 27 (revue adversariale) : aucun bloc n'affichait l'erreur de
                                 validation sur la clé racine 'votes' (required/min:1) - un votant
                                 qui ne cochait rien voyait la page se recharger sans le moindre
                                 feedback (violation WCAG 3.3.1). --}}
                            @error('votes')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @elseif($poll->vote_mode->value === 'single_choice')
                        <div class="mb-4">
                            <h2 class="h4 mb-3">Choisis une option</h2>
                            <div class="decido-vote-options">
                                @foreach($options as $option)
                                    <label class="decido-vote-option" for="vote_{{ $option->id }}">
                                        <input class="visually-hidden" type="radio"
                                               name="votes"
                                               id="vote_{{ $option->id }}"
                                               value="{{ $option->id }}"
                                               {{ array_key_exists($option->id, $existingVotes) ? 'checked' : '' }}>
                                        {{ $option->label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('votes')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @elseif($poll->vote_mode->value === 'approval')
                        <div class="mb-4">
                            <h2 class="h4 mb-3">Sélectionne toutes les options qui te conviennent</h2>
                            <div class="decido-vote-options">
                                @foreach($options as $option)
                                    <label class="decido-vote-option" for="vote_{{ $option->id }}">
                                        <input class="visually-hidden" type="checkbox"
                                               name="votes[]"
                                               id="vote_{{ $option->id }}"
                                               value="{{ $option->id }}"
                                               {{ array_key_exists($option->id, $existingVotes) ? 'checked' : '' }}>
                                        {{ $option->label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('votes')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                    <style>
                        .decido-vote-pill {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 44px;
                            min-width: 44px;
                            padding: 0.4rem 1rem;
                            border: 1px solid #ced4da;
                            border-radius: 999px;
                            cursor: pointer;
                        }
                        .decido-vote-pill:has(input:checked) {
                            border-color: var(--c-primary, #064E5A);
                            background-color: var(--c-primary-light, #F0FAFB);
                            font-weight: 600;
                        }
                        .decido-vote-options { display: flex; flex-direction: column; gap: 0.5rem; }
                        .decido-vote-option {
                            display: flex;
                            align-items: center;
                            min-height: 44px;
                            padding: 0.5rem 1rem;
                            border: 1px solid #ced4da;
                            border-radius: 0.375rem;
                            cursor: pointer;
                        }
                        .decido-vote-option:has(input:checked) {
                            border-color: var(--c-primary, #064E5A);
                            border-width: 2px;
                            background-color: var(--c-primary-light, #F0FAFB);
                        }
                        .decido-vote-pill:has(input:focus-visible),
                        .decido-vote-option:has(input:focus-visible) {
                            outline: 3px solid var(--c-primary, #064E5A);
                            outline-offset: 2px;
                        }
                        /* Volet B : bascule d'affichage du fuseau horaire (heure locale du
                           votant vs heure du sondage). */
                        .decido-tz-toggle-btn {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 44px;
                            min-width: 44px;
                            padding: 0.5rem 1rem;
                            border: 1px solid var(--c-primary, #064E5A);
                            border-radius: 0.375rem;
                            background-color: #fff;
                            color: var(--c-primary, #064E5A);
                            font-size: 0.9rem;
                            cursor: pointer;
                        }
                        .decido-tz-toggle-btn:hover {
                            background-color: var(--c-primary-light, #F0FAFB);
                        }
                        .decido-tz-toggle-btn:focus-visible {
                            outline: 3px solid var(--c-primary, #064E5A);
                            outline-offset: 2px;
                        }
                        .decido-slot-secondary { margin-top: -0.5rem; }

                        /* Priorité 1 (docs/specs/2026-08-15-decido-page-vote-design.md) : bannière de
                           titre écrasée SUR CETTE PAGE SEULEMENT (scopée par .decido-vote-hero, qui
                           n'entoure l'include du fil d'Ariane que dans cette vue) - normalement
                           min-height 400px desktop / 250px mobile (voir _page-title.scss), le popup
                           infolettre en plus recouvrait le formulaire : zéro créneau visible au 1er
                           écran mobile (mesure réelle du 15 août sur /decido/LDitANr2dPmJ). Le h2 (titre
                           dupliqué avec le h1 juste en dessous) est masqué plutôt que simplement vidé de
                           contenu, pour ne laisser aucun espace vide résiduel. */
                        .decido-vote-hero .wpo-breadcumb-area {
                            min-height: auto;
                            padding: 0.85rem 0;
                        }
                        .decido-vote-hero .wpo-breadcumb-wrap h2 {
                            display: none;
                        }
                        .decido-vote-hero .wpo-breadcumb-wrap ul {
                            margin: 0;
                        }

                        /* Priorité 2 : regroupement visuel par journée - en-tête fort + séparateur net
                           entre journées. Reste sobre même quand une journée n'a qu'un seul créneau
                           (4 journées sur 6 dans le relevé du 15 août) : pas de bouton d'action par
                           journée (tué par le panel, voir section 2 du plan - ils ne groupent rien
                           quand il n'y a qu'une ligne à grouper). */
                        .decido-day-heading {
                            font-size: 1.05rem;
                            font-weight: 700;
                            color: var(--c-primary, #064E5A);
                            margin: 0 0 0.75rem;
                        }
                        .decido-day-group + .decido-day-group {
                            margin-top: 1.5rem;
                            padding-top: 1.25rem;
                            border-top: 2px solid #E5E7EB;
                        }

                        /* Priorité 3 : totaux par créneau (jamais les noms). */
                        .decido-slot-totals .badge {
                            font-size: 0.8rem;
                            font-weight: 600;
                            padding: 0.3rem 0.6rem;
                            border-radius: 999px;
                        }
                    </style>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Envoyer mon vote</x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@if($poll->vote_mode->value === 'yes_no_maybe')
    {{-- Volet B (skill /100 hors gate) : composant Alpine dédié à l'adaptation de fuseau horaire
         à l'affichage des créneaux. Pattern DRY du projet (voir promptBuilder() dans
         constructeur-prompts.blade.php) : une fonction factory déclarée inline dans la vue plutôt
         qu'un fichier JS séparé, car le module Décido n'a ni entrée Vite ni dossier resources/js
         (vérifié avant d'écrire ce bloc) - créer un pipeline de build juste pour ~100 lignes de JS
         aurait été disproportionné et aurait cassé la cohérence avec le reste du module. --}}
    <script>
        function decidoSlotTimezone(pollTimezone) {
            return {
                pollTimezone: pollTimezone,
                pollCityLabel: '',
                detectedTimezone: null,
                manualTimezone: '',
                // 'local' = heure du votant en primaire, 'poll' = heure du sondage en primaire.
                displayMode: 'local',
                showToggle: false,
                showManualSelector: false,
                announcement: '',
                // Repli manuel : petit set de fuseaux courants (Canada + Europe francophone),
                // suffisant en secours - le Volet A (formulaire de création) n'expose qu'un
                // <select> à 3 options, pas de liste JSON réutilisable accessible ici.
                commonTimezones: [
                    { value: 'America/Toronto', label: 'Toronto / Montréal (HNE/HAE)' },
                    { value: 'America/Halifax', label: 'Halifax (HNA/HAA)' },
                    { value: 'America/Winnipeg', label: 'Winnipeg (HNC/HAC)' },
                    { value: 'America/Edmonton', label: 'Edmonton / Calgary (HNR/HAR)' },
                    { value: 'America/Vancouver', label: 'Vancouver (HNP/HAP)' },
                    { value: 'Europe/Paris', label: 'Paris / Bruxelles (HNEC/HAEC)' },
                    { value: 'Europe/London', label: 'Londres (GMT/BST)' }
                ],

                init() {
                    this.pollCityLabel = this.cityLabelFrom(this.pollTimezone);

                    try {
                        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        this.detectedTimezone = tz || null;
                    } catch (e) {
                        this.detectedTimezone = null;
                    }

                    if (!this.detectedTimezone) {
                        this.showManualSelector = true;
                        return;
                    }

                    if (this.detectedTimezone === this.pollTimezone) {
                        // Même fuseau que le sondage : aucun changement visuel (évite le clutter
                        // inutile pour l'immense majorité des votants - raffinement demandé par
                        // la revue Codex de cette fonctionnalité).
                        return;
                    }

                    this.displayMode = this.loadPref();
                    this.showToggle = true;
                    this.renderSlots(this.detectedTimezone);
                },

                cityLabelFrom(tz) {
                    if (!tz) return '';
                    var parts = tz.split('/');
                    return (parts[parts.length - 1] || tz).replace(/_/g, ' ');
                },

                loadPref() {
                    try {
                        var pref = localStorage.getItem('decido_vote_tz_display_pref');
                        return (pref === 'local' || pref === 'poll') ? pref : 'local';
                    } catch (e) {
                        return 'local';
                    }
                },

                savePref() {
                    try {
                        localStorage.setItem('decido_vote_tz_display_pref', this.displayMode);
                    } catch (e) {
                        // localStorage indisponible (navigation privée stricte...) : la préférence
                        // ne survit simplement pas à la session, sans bloquer la bascule.
                    }
                },

                applyManualTimezone() {
                    if (!this.manualTimezone) return;
                    this.detectedTimezone = this.manualTimezone;
                    this.displayMode = this.loadPref();
                    this.showToggle = true;
                    this.renderSlots(this.detectedTimezone);
                    this.announcement = 'Heures locales affichées selon le fuseau choisi.';
                },

                toggleDisplay() {
                    this.displayMode = this.displayMode === 'local' ? 'poll' : 'local';
                    this.savePref();
                    this.renderSlots(this.detectedTimezone);
                    this.announcement = this.displayMode === 'local'
                        ? 'Ton heure locale est maintenant affichée en premier.'
                        : 'L’heure du sondage est maintenant affichée en premier.';
                },

                // formatToParts plutôt que toLocaleString + parsing : évite toute dépendance
                // fragile au format de sortie exact de la locale fr-CA du navigateur.
                formatParts(iso, tz) {
                    var date = new Date(iso);
                    var fmt = new Intl.DateTimeFormat('fr-CA', {
                        timeZone: tz,
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        hour: '2-digit',
                        minute: '2-digit',
                        hourCycle: 'h23'
                    });
                    var parts = {};
                    fmt.formatToParts(date).forEach(function (p) { parts[p.type] = p.value; });
                    return parts;
                },

                renderSlots(tz) {
                    if (!tz) return;
                    var self = this;
                    // $root (pas $el) : cette méthode est aussi appelée depuis des directives
                    // portées par des éléments ENFANTS (bouton de bascule, <select> de repli) où
                    // $el se résout à l'élément déclencheur (bouton/select), pas à la racine du
                    // composant - un querySelectorAll scopé au bouton ne trouve alors aucune
                    // carte de créneau (BUG constaté en test réel : le clic ne mettait rien à
                    // jour alors qu'un appel direct depuis init() fonctionnait). $root résout
                    // toujours à la racine x-data, peu importe l'élément qui a déclenché l'appel.
                    var cards = this.$root.querySelectorAll('.decido-slot');

                    cards.forEach(function (card) {
                        var startIso = card.getAttribute('data-starts-at-utc');
                        var endIso = card.getAttribute('data-ends-at-utc');
                        if (!startIso || !endIso) return;

                        var labelEl = card.querySelector('.decido-slot-label');
                        var secondaryEl = card.querySelector('.decido-slot-secondary');
                        if (!labelEl || !secondaryEl) return;

                        // Le libellé serveur d'origine (fuseau du sondage) est capturé une seule
                        // fois, avant la première mutation, pour rester réutilisable à chaque
                        // bascule sans perte d'information. Depuis le regroupement par journée
                        // (docs/specs/2026-08-15-decido-page-vote-design.md), le texte affiché par
                        // défaut n'est plus le libellé complet mais les heures seules (la date est
                        // annoncée par l'en-tête de journée) - card.dataset.fullLabel (posé côté
                        // serveur, voir data-full-label) reste la seule source fiable du libellé
                        // complet à restaurer quand displayMode revient sur 'poll'.
                        if (!labelEl.dataset.pollLabel) {
                            labelEl.dataset.pollLabel = card.dataset.fullLabel || labelEl.textContent.trim();
                        }

                        var startLocal = self.formatParts(startIso, tz);
                        var endLocal = self.formatParts(endIso, tz);
                        var localFull = startLocal.weekday + ' ' + startLocal.day + ' ' + startLocal.month
                            + ', ' + startLocal.hour + ' h ' + startLocal.minute
                            + ' - ' + endLocal.hour + ' h ' + endLocal.minute;

                        var startPoll = self.formatParts(startIso, self.pollTimezone);
                        var localHourOnly = startLocal.hour + 'h' + startLocal.minute;
                        var pollHourOnly = startPoll.hour + 'h' + startPoll.minute;

                        if (self.displayMode === 'local') {
                            labelEl.textContent = localFull;
                            secondaryEl.textContent = '(' + pollHourOnly + ' heure de ' + self.pollCityLabel + ')';
                        } else {
                            labelEl.textContent = labelEl.dataset.pollLabel;
                            secondaryEl.textContent = '(' + localHourOnly + ' heure locale)';
                        }
                        secondaryEl.style.display = '';
                    });
                }
            };
        }
    </script>
@endif
@endsection
