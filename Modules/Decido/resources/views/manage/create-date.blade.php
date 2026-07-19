{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Sondage de dates Décido · ' . config('app.name'))
@section('page_noindex', true)
@section('meta_description', "Crée un sondage de dates Décido pour trouver le meilleur moment avec ton équipe, ta famille ou ta communauté.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Sondage de dates', 'breadcrumbItems' => [__('Outils'), 'Sondage de dates']])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="h2 mb-2">Sondage de dates</h1>
                <p class="text-muted mb-4">Trouve la meilleure date parmi plusieurs propositions.</p>

                {{-- Option E (skill /100 hors gate, veille pp_search juillet 2026 validée
                     Perplexity+Codex+Gemini, 95/100) : formulaire dédié au type "date" - plus de
                     rendu conditionnel x-show partagé avec le type "classique". Essentiel visible
                     d'emblée (titre, durée, plage par défaut, dates) ; description/fuseau/pas
                     entre créneaux regroupés sous "Plus d'options" (<details>, replié par défaut,
                     natif donc accessible sans JS ni ARIA supplémentaire). --}}
                @php
                    // Demande utilisateur 2026-07-17 : durée de la rencontre personnalisable, pas
                    // seulement les 6 valeurs présélectionnées - le backend (PollManageController)
                    // valide déjà n'importe quel entier 5-480, seule l'UI manquait l'option libre.
                    $decidoDurationOld = (int) old('duration_minutes', 60);
                    $decidoDurationPresets = [15, 30, 45, 60, 90, 120];
                    $decidoDurationChoice = in_array($decidoDurationOld, $decidoDurationPresets, true) ? (string) $decidoDurationOld : 'custom';

                    // Demande utilisateur 2026-07-17 (round 2) : "step_minutes" jugé confus pour un
                    // public non-technique - veille pp_search juillet 2026 validée Perplexity +
                    // Codex + Gemini (91-95/100, hybride retenu) : select brut remplacé par 2 choix
                    // NOMMÉS par intention ("Flexible" vs "Sans chevauchement"), valeur calculée
                    // dynamiquement depuis la durée (Doodle recommande step = durée/2) tant que non
                    // personnalisée manuellement (pattern GOV.UK dependent-field), avec un lien
                    // "Valeur personnalisée" en secours (même pattern reveal-on-demand que la durée).
                    $decidoFlexibleStepGuess = max(5, (int) round(($decidoDurationOld / 2) / 5) * 5);
                    $decidoStepOld = old('step_minutes');
                    if ($decidoStepOld !== null) {
                        $decidoStepOld = (int) $decidoStepOld;
                        if ($decidoStepOld === $decidoFlexibleStepGuess) {
                            $decidoStepMode = 'flexible';
                        } elseif ($decidoStepOld === $decidoDurationOld) {
                            $decidoStepMode = 'nooverlap';
                        } else {
                            $decidoStepMode = 'custom';
                        }
                    } else {
                        $decidoStepMode = 'flexible';
                    }
                    $decidoStepCustomOld = $decidoStepOld ?? $decidoFlexibleStepGuess;

                    // Round 27 (revue adversariale) : candidateDates/candidateDateRanges
                    // initialisaient x-data en dur sans jamais relire old(), contrairement à tous
                    // les autres champs du formulaire - un échec de validation (chevauchement,
                    // doublon, DST) faisait perdre toutes les dates/plages saisies au réaffichage.
                    $decidoOldCandidateDates = old('candidate_dates');
                    if (!is_array($decidoOldCandidateDates) || empty($decidoOldCandidateDates)) {
                        $decidoOldCandidateDates = [''];
                    }
                    $decidoOldCandidateDates = array_values($decidoOldCandidateDates);

                    $decidoOldCandidateDateRangesRaw = old('candidate_date_ranges');
                    $decidoOldCandidateDateRanges = [];
                    foreach ($decidoOldCandidateDates as $decidoIdx => $decidoDate) {
                        $decidoRangesForIdx = (is_array($decidoOldCandidateDateRangesRaw) && isset($decidoOldCandidateDateRangesRaw[$decidoIdx]) && is_array($decidoOldCandidateDateRangesRaw[$decidoIdx]))
                            ? array_values($decidoOldCandidateDateRangesRaw[$decidoIdx])
                            : [];
                        $decidoOldCandidateDateRanges[] = $decidoRangesForIdx;
                    }
                @endphp
                <form x-data="{
                    candidateDates: {{ json_encode($decidoOldCandidateDates) }},
                    candidateDateRanges: {{ json_encode($decidoOldCandidateDateRanges) }},
                    rangeStartTime: '{{ old('range_start_time', '09:00') }}',
                    rangeEndTime: '{{ old('range_end_time', '17:00') }}',
                    durationChoice: '{{ $decidoDurationChoice }}',
                    customDuration: {{ $decidoDurationOld }},
                    stepMode: '{{ $decidoStepMode }}',
                    customStep: {{ $decidoStepCustomOld }},
                    effectiveDuration() {
                        return this.durationChoice === 'custom' ? this.customDuration : parseInt(this.durationChoice, 10);
                    },
                    computedStep() {
                        if (this.stepMode === 'custom') return this.customStep;
                        if (this.stepMode === 'nooverlap') return this.effectiveDuration();
                        const step = Math.round((this.effectiveDuration() / 2) / 5) * 5;
                        return step < 5 ? 5 : step;
                    }
                }" method="POST" action="{{ route('decido.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="date">

                    @include('decido::manage.partials.title-field')

                    <div class="mb-3">
                        <label for="duration_minutes" class="form-label">Durée de la rencontre (minutes)</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <select id="duration_minutes" class="form-select" style="max-width:220px;" x-model="durationChoice">
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                                <option value="90">90 minutes</option>
                                <option value="120">120 minutes</option>
                                <option value="custom">Personnalisée...</option>
                            </select>
                            <div class="input-group" style="max-width:200px;" x-show="durationChoice === 'custom'" x-cloak>
                                <input type="number" class="form-control"
                                       min="5" max="480" x-model.number="customDuration"
                                       aria-label="Nombre de minutes personnalisé, de 5 à 480">
                                <span class="input-group-text">minutes</span>
                            </div>
                        </div>
                        <input type="hidden" name="duration_minutes" :value="durationChoice === 'custom' ? customDuration : durationChoice">
                        @error('duration_minutes')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="range_start_time" class="form-label">Début de la plage horaire par défaut</label>
                            <input type="time" id="range_start_time" name="range_start_time" class="form-control"
                                   x-model="rangeStartTime" value="{{ old('range_start_time', '09:00') }}">
                            @error('range_start_time')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="range_end_time" class="form-label">Fin de la plage horaire par défaut</label>
                            <input type="time" id="range_end_time" name="range_end_time" class="form-control"
                                   x-model="rangeEndTime" value="{{ old('range_end_time', '17:00') }}">
                            @error('range_end_time')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Dates proposées</label>
                        <template x-for="(date, index) in candidateDates" :key="index">
                            <div class="border rounded p-3 mb-2">
                                <div class="input-group mb-2">
                                    <input type="date" name="candidate_dates[]" class="form-control" x-model="candidateDates[index]">
                                    <button type="button" class="ct-btn ct-btn-outline-danger ct-btn-sm"
                                            x-on:click="candidateDates.splice(index, 1); candidateDateRanges.splice(index, 1)"
                                            x-show="candidateDates.length > 1">
                                        Retirer
                                    </button>
                                </div>
                                {{-- Nouvelle fonctionnalité (demande utilisateur 2026-07-17, veille pp_search
                                     validée Perplexity+Codex+Gemini, 95/100) : une date peut désormais avoir
                                     PLUSIEURS plages horaires (ex. 9h-12h ET 14h-17h, pour sauter le dîner),
                                     pas une seule surcharge. Le lien/icône discret (raffinement Gemini) révèle
                                     une LISTE de plages "Début/Fin" répétable au lieu de 2 champs fixes. --}}
                                <button type="button" class="ct-btn ct-btn-ghost ct-btn-sm"
                                        x-show="candidateDateRanges[index].length === 0"
                                        x-on:click="candidateDateRanges[index].push({ start: rangeStartTime, end: rangeEndTime })">
                                    {{-- Fix 2026-07-17 : le caractère unicode brut "⚙" rendait minuscule/cassé
                                         (glyphe absent des polices de charte DM Sans/Plus Jakarta Sans, repli sur
                                         une police système à taille incohérente) - remplacé par un SVG inline à
                                         dimensions explicites (cf. historique #592, icônes sans dimension). --}}
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;margin-right:2px;">
                                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                                    </svg>Personnaliser l'horaire pour cette date
                                </button>
                                <template x-if="candidateDateRanges[index].length > 0">
                                    <div class="mt-1">
                                        <template x-for="(range, rangeIndex) in candidateDateRanges[index]" :key="rangeIndex">
                                            <div class="d-flex gap-2 align-items-end mb-2">
                                                <div class="flex-grow-1">
                                                    <label class="form-label small" :for="'decido-range-start-' + index + '-' + rangeIndex">Début</label>
                                                    <input type="time" class="form-control form-control-sm" :id="'decido-range-start-' + index + '-' + rangeIndex"
                                                           :name="'candidate_date_ranges[' + index + '][' + rangeIndex + '][start]'" x-model="range.start">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label class="form-label small" :for="'decido-range-end-' + index + '-' + rangeIndex">Fin</label>
                                                    <input type="time" class="form-control form-control-sm" :id="'decido-range-end-' + index + '-' + rangeIndex"
                                                           :name="'candidate_date_ranges[' + index + '][' + rangeIndex + '][end]'" x-model="range.end">
                                                </div>
                                                {{-- Demande utilisateur 2026-07-17 : remplace le bouton "×" bordé rouge
                                                     (jugé "laid") par une icône corbeille rouge sans contour - style
                                                     .ct-btn-ghost (transparent, pas de bordure) + couleur var(--c-danger). --}}
                                                <button type="button" class="ct-btn ct-btn-ghost"
                                                        style="color: var(--c-danger); padding: 6px; width: 36px; height: 36px; min-width: 36px; min-height: 36px; flex: 0 0 auto;"
                                                        x-on:click="candidateDateRanges[index].splice(rangeIndex, 1)"
                                                        x-show="candidateDateRanges[index].length > 0"
                                                        aria-label="Supprimer cette plage horaire">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                        <path d="M10 11v6"/>
                                                        <path d="M14 11v6"/>
                                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <button type="button" class="ct-btn ct-btn-ghost ct-btn-sm"
                                                x-on:click="candidateDateRanges[index].push({ start: '', end: '' })">
                                            + Ajouter une plage pour cette date
                                        </button>
                                        <button type="button" class="ct-btn ct-btn-ghost ct-btn-sm ms-3"
                                                x-on:click="candidateDateRanges[index] = []">
                                            ✕ Revenir à l'horaire par défaut
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <button type="button" class="ct-btn ct-btn-outline ct-btn-sm"
                                x-on:click="candidateDates.push(''); candidateDateRanges.push([])">
                            + Ajouter une date
                        </button>
                        @error('candidate_dates')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <details class="mb-4 decido-advanced-options">
                        <summary class="fw-semibold">Plus d'options</summary>
                        <div class="mt-3">
                            @include('decido::manage.partials.description-timezone-fields')

                            <div class="mb-3">
                                {{-- Refonte 2026-07-17 (round 2, demande utilisateur : "j'ai l'impression
                                     que l'option est importante, mais comment rendre ça simple ?") - veille
                                     pp_search juillet 2026 validée Perplexity + Codex + Gemini (91-95/100,
                                     hybride retenu) : select de minutes brutes remplacé par 2 CHOIX NOMMÉS
                                     par intention plutôt que par la valeur technique. La valeur réelle
                                     (step_minutes) est calculée dynamiquement depuis la durée choisie
                                     (Doodle recommande step = durée/2 pour doubler les options sans
                                     complexité) et se recalcule tant que l'utilisateur n'a pas personnalisé
                                     manuellement (pattern GOV.UK dependent-field). Lien "Valeur
                                     personnalisée" en secours = même pattern reveal-on-demand que la durée
                                     personnalisée (DRY, cohérent). --}}
                                <label class="form-label d-flex align-items-center gap-2">
                                    Créneaux proposés aux votants
                                    <button type="button" class="ct-btn ct-btn-primary ct-btn-icon" @click="jQuery('#stepHelpModal').modal('show')" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;font-size:0.7rem;" title="Aide">?</button>
                                </label>
                                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Créneaux proposés aux votants" x-show="stepMode !== 'custom'">
                                    <button type="button" class="ct-btn ct-btn-sm" :class="stepMode === 'flexible' ? 'ct-btn-primary' : 'ct-btn-outline'"
                                            @click="stepMode = 'flexible'" :aria-pressed="(stepMode === 'flexible').toString()">
                                        Flexible (recommandé)
                                    </button>
                                    <button type="button" class="ct-btn ct-btn-sm" :class="stepMode === 'nooverlap' ? 'ct-btn-primary' : 'ct-btn-outline'"
                                            @click="stepMode = 'nooverlap'" :aria-pressed="(stepMode === 'nooverlap').toString()">
                                        Sans chevauchement
                                    </button>
                                </div>
                                <p class="text-muted small mt-2 mb-0" x-show="stepMode !== 'custom'">
                                    <span x-text="'Un nouveau créneau proposé toutes les ' + computedStep() + ' minutes.'"></span>
                                </p>
                                <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs mt-1" x-show="stepMode !== 'custom'"
                                        @click="customStep = computedStep(); stepMode = 'custom'">
                                    Valeur personnalisée...
                                </button>
                                <div class="mt-2" x-show="stepMode === 'custom'" x-cloak>
                                    <div class="input-group" style="max-width:200px;">
                                        <input type="number" class="form-control" min="5" max="480" x-model.number="customStep"
                                               aria-label="Pas personnalisé entre les créneaux, en minutes, de 5 à 480">
                                        <span class="input-group-text">minutes</span>
                                    </div>
                                    <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs mt-1" @click="stepMode = 'flexible'">
                                        ← Revenir aux préréglages
                                    </button>
                                </div>
                                <input type="hidden" name="step_minutes" :value="computedStep()">
                                @error('step_minutes')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </details>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Créer le sondage</x-core::button>
                    </div>
                    {{-- Politique de rétention (2026-07-19) : mention discrète, non intrusive
                         (pas d'alerte/encadré) - le calcul exact (dernière date candidate + 2 mois)
                         se fait côté serveur à la création, pas affiché ici avant saisie des dates. --}}
                    <p class="text-muted small text-center mt-2 mb-0">Ce sondage sera automatiquement supprimé quelque temps après la dernière date proposée, pour protéger tes données.</p>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- Popup d'aide "Créneaux proposés aux votants" - patron identique aux autres outils du site
     (ex. code-qr.blade.php #qrHelpModal) : bouton "?" circulaire ct-btn-icon + modale Bootstrap. --}}
<div class="modal fade" id="stepHelpModal" tabindex="-1" role="dialog" aria-labelledby="stepHelpModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" id="stepHelpModalLabel" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">Créneaux proposés aux votants</h4>
                <button type="button" onclick="jQuery('#stepHelpModal').modal('hide')" aria-label="Fermer" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">Comment ça fonctionne</h4>
                <p>À partir de ta plage horaire (ex. 9h-17h) et de la durée de la rencontre (ex. 60 minutes), Décido génère automatiquement une série de créneaux candidats en avançant l'heure de début à intervalles réguliers. Ce réglage contrôle la taille de ces intervalles - pas la durée de la rencontre elle-même.</p>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">Flexible (recommandé)</h4>
                <p>Les créneaux se chevauchent, ce qui double le nombre d'options proposées aux votants sans surcharger le sondage. Exemple pour une rencontre de 60 minutes entre 9h et 11h :</p>
                <ul>
                    <li>9h00 - 10h00</li>
                    <li>9h30 - 10h30</li>
                    <li>10h00 - 11h00</li>
                </ul>
                <p>Les votants trouvent plus facilement un moment qui leur convient vraiment.</p>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">Sans chevauchement</h4>
                <p>Les créneaux sont collés bout à bout, sans se chevaucher. Même exemple (60 minutes, 9h-11h) :</p>
                <ul>
                    <li>9h00 - 10h00</li>
                    <li>10h00 - 11h00</li>
                </ul>
                <p>Sondage plus court à remplir, utile quand chaque créneau correspond à un vrai bloc distinct (ex. plusieurs entrevues consécutives).</p>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">Valeur personnalisée</h4>
                <p>Pour un contrôle précis (ex. un nouveau départ toutes les 10 minutes), utilise le lien « Valeur personnalisée » et saisis directement l'intervalle en minutes.</p>
            </div>
        </div>
    </div>
</div>
@endsection
