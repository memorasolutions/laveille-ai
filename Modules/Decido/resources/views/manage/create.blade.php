{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Créer un sondage Décido · ' . config('app.name'))
{{-- Round 10 (skill /100) : page privée (auth requis), jamais destinée à l'indexation - même
     politique noindex que le reste des vues privées Décido. --}}
@section('page_noindex', true)
@section('meta_description', "Crée un sondage Décido pour trouver le bon moment ou le bon choix avec ton équipe, ta famille ou ta communauté.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Créer un sondage Décido'])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="h2 mb-4">Créer un sondage Décido</h1>

                <form x-data="{
                    type: 'date',
                    candidateDates: [''],
                    candidateDateOverrides: [false],
                    candidateDateStartTimes: [''],
                    candidateDateEndTimes: [''],
                    rangeStartTime: '{{ old('range_start_time', '09:00') }}',
                    rangeEndTime: '{{ old('range_end_time', '17:00') }}',
                    options: ['', ''],
                    voteMode: 'single_choice'
                }" method="POST" action="{{ route('decido.store') }}">
                    @csrf

                    <div class="mb-5">
                        <h2 class="h4 mb-3">1. Type de sondage</h2>
                        {{-- Décido round 6 (skill /100) : les radios utilisaient class="d-none" (display:none),
                             ce qui les retire de l'ordre de tabulation - un utilisateur clavier seul ne pouvait
                             jamais choisir le type de sondage (WCAG 2.1.1, niveau A). visually-hidden garde le
                             radio natif focalisable/actionnable au clavier (Espace/flèches) tout en le masquant
                             visuellement ; :has(:focus-visible) donne un anneau de focus visible sur la carte. --}}
                        {{-- Signalé par l'utilisateur (capture 2026-07-16) : aucune des 2 cartes n'indiquait
                             visuellement le type actuellement sélectionné (bordure/fond identiques dans les
                             deux états), alors que "date" est sélectionné par défaut. Ajout d'une classe
                             .decido-poll-type-selected pilotée par x-bind:class + un badge "Sélectionné"
                             (icône + texte, jamais la couleur seule - WCAG 1.4.1) visible uniquement sur la
                             carte active. --}}
                        <div class="row g-3 decido-poll-type-choices">
                            <div class="col-md-6">
                                <label class="d-flex flex-column h-100 border rounded p-3 position-relative"
                                       style="cursor: pointer;"
                                       x-on:click="type = 'date'"
                                       x-bind:class="{ 'decido-poll-type-selected': type === 'date' }">
                                    <input type="radio" name="type" value="date" class="visually-hidden" x-model="type" required>
                                    <span class="decido-poll-type-badge" x-show="type === 'date'" x-cloak>✓ Sélectionné</span>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <strong>Sondage de dates</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Trouve la meilleure date parmi plusieurs propositions.</p>
                                </label>
                            </div>

                            <div class="col-md-6">
                                <label class="d-flex flex-column h-100 border rounded p-3 position-relative"
                                       style="cursor: pointer;"
                                       x-on:click="type = 'classic'"
                                       x-bind:class="{ 'decido-poll-type-selected': type === 'classic' }">
                                    <input type="radio" name="type" value="classic" class="visually-hidden" x-model="type" required>
                                    <span class="decido-poll-type-badge" x-show="type === 'classic'" x-cloak>✓ Sélectionné</span>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <strong>Sondage classique</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Choisis entre plusieurs options libres.</p>
                                </label>
                            </div>
                        </div>
                        <style>
                            .decido-poll-type-choices label:has(input:focus-visible) {
                                outline: 3px solid var(--c-primary, #064E5A);
                                outline-offset: 2px;
                            }
                            .decido-poll-type-choices .decido-poll-type-selected {
                                border-color: var(--c-primary, #064E5A);
                                border-width: 2px;
                                background-color: var(--c-primary-light, #F0FAFB);
                            }
                            .decido-poll-type-badge {
                                position: absolute;
                                top: 0.6rem;
                                right: 0.6rem;
                                font-size: 0.75rem;
                                font-weight: 600;
                                color: var(--c-primary, #064E5A);
                                background-color: var(--c-primary-badge, #DDF4F8);
                                border-radius: 999px;
                                padding: 0.15rem 0.6rem;
                            }
                        </style>
                        @error('type')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="title" class="form-label">Titre du sondage <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Description (optionnel)</label>
                        <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="timezone" class="form-label">Fuseau horaire</label>
                        <select id="timezone" name="timezone" class="form-select">
                            <option value="America/Toronto" {{ old('timezone', 'America/Toronto') === 'America/Toronto' ? 'selected' : '' }}>Toronto (HNE/HAE)</option>
                            <option value="America/Montreal" {{ old('timezone') === 'America/Montreal' ? 'selected' : '' }}>Montréal (HNE/HAE)</option>
                            <option value="Europe/Paris" {{ old('timezone') === 'Europe/Paris' ? 'selected' : '' }}>Paris (HNEC/HAEC)</option>
                        </select>
                        @error('timezone')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div x-show="type === 'date'" class="mb-5">
                        <h2 class="h4 mb-3">2. Paramètres des dates</h2>

                        <div class="mb-3">
                            <label for="duration_minutes" class="form-label">Durée de la rencontre (minutes)</label>
                            {{-- Nouvelle fonctionnalité (demande utilisateur 2026-07-17) : la durée et le pas
                                 restent globaux (même durée de rencontre pour toutes les dates proposées),
                                 seule la plage horaire (début/fin) peut être personnalisée par date
                                 ci-dessous - c'est le seul axe demandé, éviter d'ajouter une complexité
                                 non demandée sur la durée/le pas. --}}
                            <select id="duration_minutes" name="duration_minutes" class="form-select">
                                <option value="15" {{ old('duration_minutes') == 15 ? 'selected' : '' }}>15</option>
                                <option value="30" {{ old('duration_minutes') == 30 ? 'selected' : '' }}>30</option>
                                <option value="45" {{ old('duration_minutes') == 45 ? 'selected' : '' }}>45</option>
                                <option value="60" {{ old('duration_minutes', 60) == 60 ? 'selected' : '' }}>60</option>
                                <option value="90" {{ old('duration_minutes') == 90 ? 'selected' : '' }}>90</option>
                                <option value="120" {{ old('duration_minutes') == 120 ? 'selected' : '' }}>120</option>
                            </select>
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
                        <p class="text-muted small mb-3">Cette plage s'applique à toutes les dates proposées, sauf si tu personnalises l'horaire d'une date précise ci-dessous.</p>

                        <div class="mb-3">
                            <label for="step_minutes" class="form-label">Pas entre les créneaux (minutes)</label>
                            <select id="step_minutes" name="step_minutes" class="form-select">
                                <option value="15" {{ old('step_minutes') == 15 ? 'selected' : '' }}>15</option>
                                <option value="30" {{ old('step_minutes', 30) == 30 ? 'selected' : '' }}>30</option>
                                <option value="60" {{ old('step_minutes') == 60 ? 'selected' : '' }}>60</option>
                            </select>
                            @error('step_minutes')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Dates proposées</label>
                            {{-- Nouvelle fonctionnalité (demande utilisateur 2026-07-17) : chaque date
                                 peut personnaliser sa propre plage horaire (ex. lundi seulement l'après-midi,
                                 mercredi seulement le matin) au lieu d'être forcée d'utiliser la même plage
                                 par défaut que toutes les autres dates - décrit dans PollManageController::store()
                                 (regroupement par plage effective avant génération des créneaux). --}}
                            <template x-for="(date, index) in candidateDates" :key="index">
                                <div class="border rounded p-3 mb-2">
                                    <div class="input-group mb-2">
                                        <input type="date" name="candidate_dates[]" class="form-control" x-model="candidateDates[index]">
                                        <button type="button" class="btn btn-outline-secondary decido-touch-target"
                                                x-on:click="candidateDates.splice(index, 1); candidateDateOverrides.splice(index, 1); candidateDateStartTimes.splice(index, 1); candidateDateEndTimes.splice(index, 1)"
                                                x-show="candidateDates.length > 1">
                                            Retirer
                                        </button>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" :id="'decido-override-' + index"
                                               x-model="candidateDateOverrides[index]"
                                               x-on:change="if (candidateDateOverrides[index]) { candidateDateStartTimes[index] = candidateDateStartTimes[index] || rangeStartTime; candidateDateEndTimes[index] = candidateDateEndTimes[index] || rangeEndTime; } else { candidateDateStartTimes[index] = ''; candidateDateEndTimes[index] = ''; }">
                                        <label class="form-check-label small" :for="'decido-override-' + index">
                                            Horaire différent pour cette date
                                        </label>
                                    </div>
                                    <div class="row g-2 mt-1" x-show="candidateDateOverrides[index]" x-cloak>
                                        <div class="col-6">
                                            <label class="form-label small" :for="'decido-override-start-' + index">Début</label>
                                            <input type="time" class="form-control form-control-sm" :id="'decido-override-start-' + index"
                                                   name="candidate_date_start_times[]" x-model="candidateDateStartTimes[index]">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small" :for="'decido-override-end-' + index">Fin</label>
                                            <input type="time" class="form-control form-control-sm" :id="'decido-override-end-' + index"
                                                   name="candidate_date_end_times[]" x-model="candidateDateEndTimes[index]">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-primary decido-touch-target"
                                    x-on:click="candidateDates.push(''); candidateDateOverrides.push(false); candidateDateStartTimes.push(''); candidateDateEndTimes.push('')">
                                + Ajouter une date
                            </button>
                            @error('candidate_dates')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div x-show="type === 'classic'" class="mb-5">
                        <h2 class="h4 mb-3">2. Options du sondage</h2>

                        <div class="mb-3">
                            <label class="form-label d-block">Mode de vote</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vote_mode" id="vote_mode_single" value="single_choice"
                                       x-model="voteMode">
                                <label class="form-check-label" for="vote_mode_single">Choix unique</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vote_mode" id="vote_mode_approval" value="approval"
                                       x-model="voteMode">
                                <label class="form-check-label" for="vote_mode_approval">Approbation, plusieurs choix possibles</label>
                            </div>
                            @error('vote_mode')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Options</label>
                            <template x-for="(option, index) in options" :key="index">
                                <div class="input-group mb-2">
                                    <input type="text" name="options[]" class="form-control" x-model="options[index]">
                                    <button type="button" class="btn btn-outline-secondary decido-touch-target"
                                            x-on:click="options.splice(index, 1)"
                                            x-show="options.length > 2">
                                        Retirer
                                    </button>
                                </div>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-primary decido-touch-target" x-on:click="options.push('')">
                                + Ajouter une option
                            </button>
                            @error('options')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Créer le sondage</x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
