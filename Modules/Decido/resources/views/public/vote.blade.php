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
         titre. CORRIGÉ le 16 août : la bannière n'est plus réduite qu'en dessous de 768px (voir
         le média query dans le <style> plus bas) - à 768px et plus, elle retrouve la hauteur
         standard du site (mesurée à 250px, IDENTIQUE à toutes les autres pages ; la valeur
         400px documentée dans _page-title.scss n'est PAS celle réellement compilée/servie par
         public/themes/bloggar/sass/style.css, vérifié par mesure Playwright sur /outils). --}}
    <div class="decido-vote-hero">
        @include('fronttheme::partials.breadcrumb', ['breadcrumbItems' => [__('Outils'), $poll->title]])
    </div>
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
              {{-- Point 2 (rapport 2026-08-16, reproche propriétaire) : la page de vote n'avait
                   AUCUNE enveloppe de contenu, alors que les autres pages du site enveloppent tout
                   leur contenu dans une carte unique (motif de référence vérifié :
                   Modules/Tools/resources/views/public/show.blade.php, `.card.shadow-sm` +
                   `.card-body.p-4.p-md-5`) - le formulaire flottait nu sur le fond de page.
                   Anti carte-dans-carte : les créneaux (.decido-slot) sont eux-mêmes des mini-cartes
                   Bootstrap .card ; plutôt que les extraire de la carte englobante (ce qui aurait
                   cassé le regroupement par journée/container queries), leur habillage carte
                   (bordure + ombre + coins arrondis) est neutralisé au profit d'un simple séparateur
                   horizontal - voir la règle .decido-slot plus bas dans ce même <style>. --}}
              <div class="card shadow-sm" style="border-radius: var(--r-base);">
                <div class="card-body p-4 p-md-5">
                <h1 class="h2 mb-2">{{ $poll->title }}</h1>

                @php
                    // LOT 1 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 1) : le sondage
                    // clôturé n'est plus un formulaire fermé sans réponse visible - la décision
                    // s'affiche CLAIREMENT et EN PREMIER (avant même la description), le formulaire
                    // n'est plus soumissible ($isOpenForVoting pilote le reste de cette vue), mais
                    // les créneaux et leurs totaux restent consultables juste en dessous.
                    $isOpenForVoting = $poll->status->value === 'open';
                    $isClosedWithFinal = $poll->status->value === 'closed' && $poll->final_option_id !== null;
                    $finalOption = $isClosedWithFinal ? $options->firstWhere('id', $poll->final_option_id) : null;
                    $finalOptionHasDates = $finalOption && $finalOption->starts_at && $finalOption->ends_at;
                @endphp

                @if($isClosedWithFinal)
                    <div class="alert mb-4" style="border-color: var(--c-primary); background-color: rgba(6, 78, 90, 0.08);">
                        <h2 class="h5 mb-2" style="color: var(--c-primary);">Créneau retenu</h2>
                        <p class="mb-2">{{ $finalOption->label }}</p>
                        @if($finalOptionHasDates)
                            {{-- Réutilise PollExportService::exportIcs() (même service que
                                 l'organisateur) via une route publique dédiée - voir
                                 PublicPollController::exportIcs(). --}}
                            <a href="{{ route('decido.vote.ics', ['slug' => $poll->share_slug]) }}" class="ct-btn ct-btn-outline ct-btn-sm">
                                Ajouter à mon calendrier (.ics)
                            </a>
                        @endif
                    </div>
                @elseif($poll->status->value === 'closed')
                    <div class="alert alert-light border mb-4">
                        Ce sondage est clôturé. L'organisateur n'a pas retenu de créneau final.
                    </div>
                @endif

                @if($poll->description)
                    <p class="text-muted mb-4">{{ $poll->description }}</p>
                @endif

                @if($isOpenForVoting && $poll->response_deadline_at)
                    @php
                        $deadlineLocal = $poll->responseDeadlineInPollTimezone()->locale('fr');
                        $deadlinePassed = $poll->isResponseDeadlinePassed();
                    @endphp
                    {{-- LOT 1, point 2 : affichée avant ET après l'envoi (donc ici, hors du bloc
                         succès plus bas) - JAMAIS bloquante : une échéance dépassée avertit
                         seulement, aucun champ n'est désactivé ni rendu required en conséquence. --}}
                    <div class="alert {{ $deadlinePassed ? 'alert-warning' : 'alert-light border' }} mb-4">
                        @if($deadlinePassed)
                            La date limite de réponse ({{ $deadlineLocal->isoFormat('dddd D MMMM [à] H [h] mm') }}) est passée. Tu peux tout de même répondre - l'organisateur en sera informé.
                        @else
                            Réponds avant le {{ $deadlineLocal->isoFormat('dddd D MMMM [à] H [h] mm') }}.
                        @endif
                    </div>
                @endif

                {{-- La bannière se déclenche sur l'existence RÉELLE de votes, jamais sur la simple
                     présence du témoin : celui-ci est posé aussi bien par vote() que par decline(),
                     et quelqu'un qui a SEULEMENT décliné voyait deux messages contradictoires
                     (« tu as déjà voté » + « aucune date ne te convenait »). Défaut trouvé en
                     validation visuelle le 2026-08-16 ; les tests ne l'attrapaient pas, la donnée
                     étant juste et seul l'affichage étant faux. --}}
                @if($voterToken && (! empty($existingVotes) || ($existingDecline ?? false)))
                    <div class="alert alert-info mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>@if(! empty($existingVotes))Tu as déjà voté sous ce lien - modifie ton choix ci-dessous si besoin.@else Tu as déjà indiqué qu'aucune date ne te convenait. Tu peux voter ci-dessous si un créneau finit par te convenir.@endif</span>
                        {{-- LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 4) : geste
                             EXPLICITE et IRRÉVERSIBLE, distinct du simple "revoter" ci-dessus -
                             confirmation obligatoire via la modale du thème (x-core::confirm-modal,
                             instance globale montée par FrontTheme/layouts/master.blade.php), câblée
                             par le seul attribut data-confirm (délégué global déjà présent dans ce
                             layout - AUCUN JS ajouté ici). JAMAIS confirm()/alert() natifs du
                             navigateur (interdit au projet). PublicPollController::clearVote()
                             n'efface QUE les données du voter_token du cookie chiffré du demandeur,
                             jamais celles d'un autre votant. --}}
                        <form method="POST" action="{{ route('decido.vote.clear', ['slug' => $poll->share_slug]) }}"
                              data-confirm="Effacer TOUTE ta participation à ce sondage (votes, réponse et commentaire) ? Cette action est irréversible.">
                            @csrf
                            <button type="submit" class="ct-btn ct-btn-outline-danger ct-btn-sm">Effacer ma participation</button>
                        </form>
                    </div>
                @endif

                {{-- Le message de refus est désormais porté par le bandeau unique ci-dessus, qui
                     conserve le bouton d'effacement : un seul bandeau, jamais deux contradictoires. --}}

                @if(session('success'))
                    <div class="alert alert-success mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if($isOpenForVoting)
                <form method="POST" action="{{ route('decido.vote.store', ['slug' => $poll->share_slug]) }}">
                    @csrf
                @else
                <div>
                @endif

                    @if($isOpenForVoting)
                    <div class="mb-4">
                        <label for="voter_pseudonym" class="form-label">Ton nom ou pseudonyme <span class="text-danger">*</span></label>
                        <input type="text" id="voter_pseudonym" name="voter_pseudonym" class="form-control"
                               value="{{ old('voter_pseudonym') }}" required>
                        @error('voter_pseudonym')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 5) : UN
                         commentaire facultatif par participant (pas un par créneau - 14 champs à
                         remplir serait du bruit). Même <form> que le vote ET le déclin (bouton
                         "Aucune de ces dates ne me convient" plus bas) : le champ voyage avec les
                         deux soumissions sans dupliquer de HTML. maxlength="280" = même limite que
                         la validation serveur (PublicPollController::vote()/decline()) - repli
                         client, la vraie limite reste imposée côté serveur. --}}
                    <div class="mb-4">
                        <label for="comment" class="form-label">Un commentaire ? (facultatif)</label>
                        <textarea id="comment" name="comment" class="form-control" rows="2" maxlength="280"
                                  placeholder="Ex. : je peux seulement après 18 h, je participe à distance...">{{ old('comment', $existingComment ?? '') }}</textarea>
                        @error('comment')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

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
                                        class="ct-btn ct-btn-outline decido-tz-toggle-btn"
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
                                // Correction microcopie (2026-08-16) : état 1 du résumé par créneau
                                // ("Réponses déjà reçues" annoncé puis "Aucune réponse reçue" juste en
                                // dessous - absurde et redondant, répété 14 fois sur un sondage neuf).
                                // Il faut savoir si le SONDAGE ENTIER (pas seulement le créneau courant)
                                // a reçu au moins un vote - calculé ICI, UNE SEULE FOIS avant la boucle
                                // des créneaux plus bas, jamais à chaque itération. $options->votes est
                                // déjà chargé en mémoire par $poll->load('options.votes')
                                // (PublicPollController::show()) : contains() ne déclenche aucune requête
                                // SQL supplémentaire et court-circuite au premier vote trouvé.
                                $pollHasAnyVotes = $options->contains(fn ($option) => $option->votes->isNotEmpty());
                            @endphp

                            @if($pollHasAnyVotes)
                                {{-- Explique UNE SEULE FOIS le sens des totaux affichés dans chaque carte
                                     (zone de droite), jamais répété par créneau. N'a de sens que s'il existe
                                     déjà des totaux à expliquer (sinon la phrase annonce des totaux
                                     inexistants) - masquée à l'état 1 (sondage sans aucun vote). Formulation
                                     vérifiée contre PublicPollController::show() : $poll->load('options.votes')
                                     charge TOUS les votes du créneau sans filtrer par voter_token, donc ces
                                     totaux incluent le propre vote du votant s'il a déjà répondu - jamais
                                     "autres participants", qui serait faux. --}}
                                <p class="decido-slot-summary-explainer text-muted small mb-3">
                                    Ces totaux montrent les choix déjà enregistrés par les participants, y compris le tien si tu as déjà voté.
                                </p>
                            @endif

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
                                            //
                                            // Round « trois zones » (2026-08-16, constat propriétaire sur sondage
                                            // réel) : $option->votes N'EST PAS filtré par voter_token dans
                                            // PublicPollController::show() ($poll->load('options.votes') charge
                                            // TOUS les votes du créneau) - ces totaux incluent donc le propre vote
                                            // du votant qui regarde la page, s'il a déjà répondu. Le libellé et la
                                            // phrase d'explication plus haut sont formulés en conséquence (jamais
                                            // "autres participants", qui serait faux).
                                            $voteCounts = $option->votes->countBy('value');
                                            $totalOptionVotes = $option->votes->count();
                                        @endphp
                                        <div class="card mb-3 decido-slot"
                                             data-starts-at-utc="{{ $slotStartUtc }}"
                                             data-ends-at-utc="{{ $slotEndUtc }}"
                                             data-full-label="{{ $option->label }}">
                                            {{-- Trois zones, DANS CET ORDRE dans le DOM (heure, boutons,
                                                 résumé) - l'ordre visuel (desktop en ligne, mobile en colonne
                                                 via .decido-slot-layout ci-dessous) SUIT STRICTEMENT cet ordre
                                                 du document : aucune propriété CSS `order` ni `grid-column`
                                                 explicite n'est utilisée nulle part dans ce bloc, pour qu'un
                                                 parcours au clavier ou au lecteur d'écran corresponde toujours
                                                 à ce qui est affiché. --}}
                                            <div class="card-body decido-slot-layout">
                                                <div class="decido-slot-zone decido-slot-zone-time">
                                                    <h4 class="h5 mb-1 decido-slot-label">{{ $displayLabel }}</h4>
                                                    <p class="decido-slot-secondary text-muted small mb-0" style="display:none"></p>
                                                </div>
                                                <div class="decido-slot-zone decido-slot-zone-buttons">
                                                    <div class="d-flex flex-wrap gap-2 decido-vote-pills">
                                                        @foreach(['yes' => 'Oui', 'maybe' => 'Peut-être', 'no' => 'Non'] as $value => $label)
                                                            <label class="decido-vote-pill" for="vote_{{ $option->id }}_{{ $value }}">
                                                                <input class="visually-hidden" type="radio"
                                                                       name="votes[{{ $option->id }}]"
                                                                       id="vote_{{ $option->id }}_{{ $value }}"
                                                                       value="{{ $value }}"
                                                                       {{ (($existingVotes[$option->id] ?? null) === $value) ? 'checked' : '' }}
                                                                       {{ $isOpenForVoting ? '' : 'disabled' }}>
                                                                {{ $label }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    @error("votes.{$option->id}")
                                                        <div class="text-danger mt-2">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                {{-- Correction microcopie (2026-08-16) : trois états, jamais un
                                                     titre suivi d'un "aucune réponse" en dessous (absurde et
                                                     redondant, répété 14 fois sur un sondage neuf).
                                                     - État 1 (sondage entier sans AUCUN vote, $pollHasAnyVotes
                                                       faux) : zone vide, ni titre ni message - mais l'élément
                                                       reste dans le DOM (grid-template-columns ne dépend pas du
                                                       contenu de cette zone) pour ne jamais faire sauter la
                                                       grille 2↔3 colonnes au 1er vote du sondage. aria-hidden
                                                       explicite (en plus d'un div sans texte, déjà ignoré par
                                                       les lecteurs d'écran) pour garantir qu'aucun titre
                                                       orphelin n'est jamais annoncé.
                                                     - État 2 (le sondage a des votes, mais pas CE créneau) :
                                                       une seule ligne, sans titre.
                                                     - État 3 (ce créneau a des réponses) : titre court +
                                                       pastilles existantes, inchangées. --}}
                                                <div class="decido-slot-zone decido-slot-zone-summary"@if(!$pollHasAnyVotes) aria-hidden="true" @endif>
                                                    @if($pollHasAnyVotes)
                                                        @if($totalOptionVotes === 0)
                                                            <p class="decido-slot-summary-empty text-muted small mb-0">Aucune réponse</p>
                                                        @else
                                                            <p class="decido-slot-summary-title mb-1">Réponses</p>
                                                            {{-- Totaux par créneau (jamais les noms) : classe .ct-badge-status
                                                                 de public/css/charte.css (Point 3, rapport 2026-08-16) - remplace
                                                                 les 3 badges Bootstrap .badge en style="" en ligne dupliqués ici
                                                                 ET dans results-content.blade.php (dette DRY partagée, migrée
                                                                 aux deux endroits). Classes utilitaires Bootstrap d-flex/flex-wrap/
                                                                 gap-2 retirées (2026-08-16, défaut visuel constaté par le
                                                                 propriétaire) au profit de .decido-slot-totals seule, qui pilote
                                                                 maintenant elle-même sa mise en page (ligne ou pile propre selon
                                                                 la place réellement disponible - voir le <style> plus bas). --}}
                                                            <div class="decido-slot-totals">
                                                                <span class="ct-badge-status ct-badge-status-success">
                                                                    ✓ {{ $voteCounts['yes'] ?? 0 }} oui
                                                                </span>
                                                                <span class="ct-badge-status ct-badge-status-warning">
                                                                    ? {{ $voteCounts['maybe'] ?? 0 }} peut-être
                                                                </span>
                                                                <span class="ct-badge-status ct-badge-status-danger">
                                                                    ✕ {{ $voteCounts['no'] ?? 0 }} non
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
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
                                               {{ array_key_exists($option->id, $existingVotes) ? 'checked' : '' }}
                                               {{ $isOpenForVoting ? '' : 'disabled' }}>
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
                                               {{ array_key_exists($option->id, $existingVotes) ? 'checked' : '' }}
                                               {{ $isOpenForVoting ? '' : 'disabled' }}>
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
                        /* Point 3 (rapport 2026-08-16) : le visuel (bordure, couleur, fond, survol,
                           focus) de ce bouton reimplementait presque trait pour trait .ct-btn-outline
                           de public/css/charte.css - remplacé par cette classe existante (voir
                           l'attribut class="ct-btn ct-btn-outline decido-tz-toggle-btn" plus haut).
                           .decido-tz-toggle-btn ne sert plus qu'à garantir la cible tactile AAA 44px
                           (2.5.5), que .ct-btn/.ct-btn-outline n'imposent pas par défaut (seuls
                           .ct-btn-accent et .ct-btn-icon le font dans charte.css) - la seule règle non
                           déjà couverte par la classe réutilisée. */
                        .decido-tz-toggle-btn {
                            min-height: 44px;
                            min-width: 44px;
                            font-size: 0.9rem;
                        }
                        .decido-slot-secondary { margin-top: -0.5rem; }

                        /* Priorité 1 (docs/specs/2026-08-15-decido-page-vote-design.md), CORRIGÉ le
                           16 août (reproche propriétaire : la page ne respecte pas la mise en page
                           du site) : réduire la bannière à TOUS les paliers était une SUR-CORRECTION.
                           Le problème mesuré le 15 août (zéro créneau visible au 1er écran, popup
                           infolettre compris) n'existait que sur téléphone - sur grand écran, deux
                           créneaux étaient déjà visibles avec la bannière standard. Seul le palier
                           mobile (max-width: 767px, MÊME seuil que le thème utilise pour sa propre
                           media query .wpo-breadcumb-area, voir _page-title.scss) reste réduit ; à
                           768px et plus, plus AUCUNE règle de ce fichier ne touche min-height/padding
                           - la bannière retombe simplement sur le style standard du site (vérifié
                           IDENTIQUE, 250px mesurés par Playwright à 1440px, sur /decido/{slug} ET sur
                           /outils - la valeur 400px documentée dans _page-title.scss ne correspond
                           PAS à ce que public/themes/bloggar/sass/style.css sert réellement, ce
                           fichier compilé n'a d'ailleurs aucune media query sur cette règle). Le h2
                           (titre dupliqué avec le h1 juste en dessous) reste masqué à TOUS les
                           paliers - il est vide dans tous les cas ($breadcrumbTitle non transmis, voir
                           breadcrumb.blade.php), orthogonal au problème de hauteur ci-dessus. */
                        @media (max-width: 767px) {
                            .decido-vote-hero .wpo-breadcumb-area {
                                min-height: auto;
                                padding: 0.85rem 0;
                            }
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

                        /* Priorité 3 : totaux par créneau (jamais les noms). Styles du badge
                           lui-même migrés vers .ct-badge-status (public/css/charte.css, Point 3
                           du rapport 2026-08-16) - plus rien à redéfinir ici. */

                        /* Complément responsive (2026-08-16, constat propriétaire + point de rupture
                           basé sur le CONTENU, pas un gabarit d'appareil) : chaque carte .decido-slot
                           mesure SA PROPRE largeur rendue via une container query CSS plutôt qu'un
                           media query lié au viewport. Raison : à cause de col-lg-9 (Bootstrap), la
                           largeur réelle de la carte n'est PAS monotone avec la largeur d'écran - elle
                           vaut ~720px de 768px à 1199px de viewport (le conteneur .container passe à
                           720px fixe à 768px, puis col-lg-9 la ramène au même ~720px à 992px), donc un
                           media query calé sur une largeur d'écran aurait pu mal cibler la vraie zone
                           à l'étroit. La container query s'ajuste à la largeur RÉELLE de la carte,
                           qu'elle soit dans cette page (col-lg-9) ou réutilisée ailleurs plus tard.

                           3 paliers, choisis par mesure visuelle réelle (Playwright, cette carte) :
                           - < 480px (conteneur de carte) : une seule colonne (heure, boutons, résumé
                             empilés) - en dessous, boutons ("Peut-être" ~110-130px à lui seul) et
                             résumé (titre "Réponses déjà reçues" ~150-170px) ne tiennent plus côte à
                             côte sans se resserrer excessivement.
                           - 480px à 759px (le vrai "entre-deux" signalé, ex. carte ~540-720px comme au
                             palier Bootstrap 576-767px où le conteneur est fixé à 540px) : heure sur sa
                             propre ligne pleine largeur (elle est le texte le plus imprévisible en
                             longueur - créneaux "sans-date" ou libellé complet avec suffixe DST), puis
                             boutons + résumé se partagent la ligne suivante (boutons à gauche, résumé à
                             droite) - c'est la largeur où les 3 zones AU COMPLET sur une seule ligne se
                             seraient serrées, mais où 2 zones tiennent confortablement.
                           - >= 760px : les 3 zones sur une seule ligne (heure | boutons au centre |
                             résumé à droite), le layout "bureau" du brief.

                           RÈGLE D'ORDRE (non négociable, prime sur l'esthétique) : à AUCUN palier une
                           propriété `order` n'est utilisée, et le seul `grid-column` explicite sert à
                           faire OCCUPER PLUS DE PLACE à la zone heure (span pleine largeur au palier
                           intermédiaire), jamais à intervertir la position de deux zones. Le
                           placement des zones boutons/résumé reste TOUJOURS le placement automatique de
                           grille en ordre de document (1ère zone libre suivante), donc l'ordre visuel de
                           lecture (haut→bas puis gauche→droite) reste heure, boutons, résumé à CHAQUE
                           palier - identique à l'ordre HTML, y compris à 320px et à 200% de zoom (qui
                           revient à un conteneur de carte plus étroit, retombant simplement dans le
                           palier "une colonne" déjà validé). */
                        .decido-slot {
                            container-type: inline-size;
                            container-name: decido-slot;
                            /* Point 2 (rapport 2026-08-16) : anti carte-dans-carte - la page est
                               maintenant enveloppée dans une carte unique (voir plus haut dans cette
                               vue). Les mini-cartes Bootstrap .card par créneau perdent donc leur
                               bordure/ombre/coins arrondis au profit d'un simple séparateur
                               horizontal entre créneaux, plus léger visuellement dans ce contexte
                               imbriqué. Le regroupement par journée (.decido-day-group) garde son
                               propre séparateur (border-top) : les deux niveaux restent visuellement
                               distincts (trait fort entre journées, trait fin entre créneaux). */
                            border: none;
                            border-radius: 0;
                            box-shadow: none;
                            background-color: transparent;
                            border-bottom: 1px solid var(--sys-border-default, #E5E7EB);
                        }
                        .decido-slot:last-child {
                            border-bottom: none;
                        }
                        .decido-slot-layout {
                            display: flex;
                            flex-direction: column;
                            gap: 0.75rem;
                        }
                        /* Correction visuelle (2026-08-16, constat propriétaire sur capture prod) :
                           2 défauts corrigés ensemble ici, même cause racine que le fix historique des
                           pills de vote (qui avait dû garantir une largeur minimale à SA colonne pour
                           empêcher "Non" de retomber seul) - le problème s'était simplement déplacé
                           vers la colonne du résumé, jamais traité côté résumé :
                           1) Le titre ("Réponses") était text-align:right alors que les pastilles
                              (flex, alignées à gauche dans leur propre boîte) commençaient à gauche -
                              les deux n'avaient pas le même bord de référence, d'où le décalage visuel
                              constaté. text-align:left partout (aucune exception dans les container
                              queries plus bas) aligne désormais les deux sur le même bord.
                           2) À width=763px (mesuré Playwright, poll cyO3pTAxaAlT), la boîte .decido-
                              slot-zone-summary ne recevait que ~236.5px (justify-self:end la réduisait
                              au fit-content de son contenu, capé par l'espace de piste disponible),
                              alors que les 3 pastilles sur une seule ligne réclament ~237.6px (mesuré) -
                              1px d'écart suffisait à faire retomber "✕ 0 non" seul sur une 2e ligne.
                              Plutôt que chasser ce chiffre à chaque palier de container query (fragile
                              si le libellé "peut-être" change un jour, ou si les compteurs passent à 2
                              chiffres - mesuré ~262px dans ce cas), la boîte résumé devient elle-même un
                              conteneur (container-type: inline-size) : les pastilles s'affichent sur une
                              seule ligne UNIQUEMENT si leur propre boîte a réellement la place (>= 280px,
                              marge de sécurité au-delà des ~262px mesurés pour des compteurs à 2
                              chiffres), sinon elles s'empilent PROPREMENT en colonne (jamais 2 pastilles
                              sur une ligne puis 1 orpheline en dessous - le pire des deux mondes,
                              signalé par le propriétaire). Ce mécanisme s'applique à N'IMPORTE QUELLE
                              largeur de carte, pas seulement aux 3 paliers de .decido-slot ci-dessous -
                              condition nécessaire pour que la largeur de cette boîte reste déterministe
                              (justify-self reste au défaut "stretch" au palier 760px plus bas, jamais
                              "end" : une boîte en fit-content dont la taille dépendrait de son propre
                              contenu conditionné par une container query créerait une dépendance
                              circulaire que les navigateurs refusent de résoudre). */
                        .decido-slot-zone-summary {
                            text-align: left;
                            container-type: inline-size;
                            container-name: decido-slot-summary;
                        }
                        .decido-slot-summary-title {
                            font-size: 0.85rem;
                            font-weight: 700;
                            color: var(--c-primary, #064E5A);
                        }
                        .decido-slot-summary-empty {
                            font-style: italic;
                        }
                        .decido-slot-totals {
                            display: flex;
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 0.35rem;
                        }
                        @container decido-slot-summary (min-width: 280px) {
                            .decido-slot-totals {
                                flex-direction: row;
                                flex-wrap: nowrap;
                                align-items: center;
                                gap: 0.5rem;
                            }
                        }
                        @container decido-slot (min-width: 480px) {
                            .decido-slot-layout {
                                display: grid;
                                /* 235px = mesure réelle (Playwright) des 3 pills "Oui"/"Peut-être"/"Non"
                                   sur une seule ligne (~231px) + marge - une colonne 1fr/1fr stricte les
                                   compressait sous ce seuil et forçait "Non" seul sur une 2e ligne au bas
                                   de ce palier (~218px de colonne à 480px de carte, vérifié visuellement).
                                   max-content borne la colonne à SA largeur naturelle si plus de place est
                                   disponible, jamais au-delà. */
                                grid-template-columns: minmax(235px, max-content) 1fr;
                                align-items: center;
                                gap: 0.5rem 1rem;
                            }
                            /* Span pleine largeur = agrandissement de la zone, pas un réordonnancement :
                               la zone heure reste la 1ère affichée (ligne 1), boutons et résumé suivent
                               en ligne 2 par placement automatique dans l'ordre du document. */
                            .decido-slot-zone-time {
                                grid-column: 1 / -1;
                            }
                        }
                        @container decido-slot (min-width: 760px) {
                            .decido-slot-layout {
                                /* 1fr/1fr → max-content/1fr (2026-08-16) : la largeur RÉELLE de la carte
                                   reste plafonnée à ~763px sur cette page quelle que soit la largeur
                                   d'écran (col-lg-9 Bootstrap, voir le commentaire du complément
                                   responsive plus haut). Avec deux pistes 1fr (heure ET résumé), l'espace
                                   restant se partage à ÉGALITÉ entre elles peu importe leur minimum -
                                   relever le seul minimum du résumé (190px → 230px) n'y changeait donc
                                   RIEN, le résumé restait bloqué à ~236.5px, sous le seuil de 280px de la
                                   container query decido-slot-summary ci-dessus (pastilles TOUJOURS
                                   empilées, même quand 3 sur une ligne auraient largement tenu). La piste
                                   heure n'a besoin que de son propre contenu (~140px mesuré pour un
                                   libellé "H h MM - H h MM") - max-content la sort de la compétition 1fr,
                                   laissant le résumé (SEULE piste flexible restante) absorber tout le
                                   reste (333px mesuré sur cette carte), largement au-dessus du seuil. */
                                grid-template-columns: minmax(140px, max-content) auto minmax(230px, 1fr);
                            }
                            .decido-slot-zone-time {
                                grid-column: auto;
                            }
                            .decido-slot-zone-buttons {
                                justify-self: center;
                            }
                            /* justify-self reste au défaut (stretch), jamais "end" - voir le
                               commentaire plus haut sur .decido-slot-zone-summary : "end" créait une
                               boîte en fit-content dont la largeur dépendait de son propre contenu, ce
                               qui cassait le calcul de la container query decido-slot-summary
                               ci-dessus (dépendance circulaire) et provoquait le décalage/retour à la
                               ligne constaté par le propriétaire. */
                        }
                    </style>

                    @if($isOpenForVoting)
                    {{-- LOT 1, point 3 : "aucune date ne me convient", en un geste - même <form>,
                         même champ pseudonyme, formaction/formnovalidate détourne SEULEMENT cette
                         soumission vers la route dédiée (PublicPollController::decline()) sans JS
                         ni exiger que les créneaux ci-dessus soient remplis. Représentation choisie
                         : table decido_poll_declines dédiée (état distinct), pas un "no" forcé sur
                         chaque créneau - voir le commentaire de sa migration pour la justification. --}}
                    <div class="mb-3">
                        <button type="submit"
                                formaction="{{ route('decido.vote.decline', ['slug' => $poll->share_slug]) }}"
                                formnovalidate
                                class="ct-btn ct-btn-outline">
                            Aucune de ces dates ne me convient
                        </button>
                    </div>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Envoyer mon vote</x-core::button>
                    </div>
                </form>
                    @else
                </div>
                    @endif

                    {{-- LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 5) : les
                         commentaires sont VISIBLES DE TOUS LES PARTICIPANTS, pas seulement de
                         l'organisateur - "un commentaire que personne ne lit ne sert à rien".
                         Affiché hors du bloc @if($isOpenForVoting) ci-dessus : reste visible même
                         un sondage clôturé. {{ }} échappe tout HTML (le texte est de toute façon
                         déjà nettoyé de ses balises à l'écriture, voir PublicPollController) et
                         AUCUN linkifier n'est appliqué - une URL collée par un participant reste du
                         texte brut, jamais un lien cliquable. --}}
                    @if(($comments ?? collect())->isNotEmpty())
                        <div class="mt-4 pt-4 border-top">
                            <h2 class="h5 mb-3">Commentaires des participants</h2>
                            <div class="d-flex flex-column gap-2">
                                @foreach($comments as $c)
                                    <p class="small mb-0"><strong>{{ $c->voter_pseudonym }}</strong> <span class="text-muted">- {{ $c->comment }}</span></p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
              </div>
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
