{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Sondage de dates Décido · ' . config('app.name'))
@section('page_noindex', true)
@section('meta_description', "Crée un sondage de dates Décido pour trouver le meilleur moment avec ton équipe, ta famille ou ta communauté.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Sondage de dates'])
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
                @endphp
                <form x-data="{
                    candidateDates: [''],
                    candidateDateRanges: [[]],
                    rangeStartTime: '{{ old('range_start_time', '09:00') }}',
                    rangeEndTime: '{{ old('range_end_time', '17:00') }}',
                    durationChoice: '{{ $decidoDurationChoice }}',
                    customDuration: {{ $decidoDurationOld }}
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
                                    ⚙ Personnaliser l'horaire pour cette date
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
                                                <button type="button" class="ct-btn ct-btn-outline-danger ct-btn-sm"
                                                        x-on:click="candidateDateRanges[index].splice(rangeIndex, 1)"
                                                        x-show="candidateDateRanges[index].length > 0"
                                                        aria-label="Supprimer cette plage horaire">×</button>
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
                                {{-- Libellé clarifié (demande utilisateur 2026-07-17, veille pp_search
                                     validée Perplexity+Codex+Gemini, 96/100) : "Pas entre les créneaux"
                                     était ambigu (lu comme la durée de la rencontre, pas l'espacement
                                     entre les heures de début). Formulation en mini-phrase auto-explicative
                                     alignée sur le pattern "Show available times every: [X]" identifié par
                                     la recherche - le select devient grammaticalement partie du libellé. --}}
                                <label for="step_minutes" class="form-label d-block">
                                    Proposer une nouvelle heure de début toutes les
                                    <select id="step_minutes" name="step_minutes" class="form-select d-inline-block w-auto mx-1">
                                        <option value="15" {{ old('step_minutes') == 15 ? 'selected' : '' }}>15</option>
                                        <option value="30" {{ old('step_minutes', 30) == 30 ? 'selected' : '' }}>30</option>
                                        <option value="60" {{ old('step_minutes') == 60 ? 'selected' : '' }}>60</option>
                                    </select>
                                    minutes
                                </label>
                                @error('step_minutes')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </details>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Créer le sondage</x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
