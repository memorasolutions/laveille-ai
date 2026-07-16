{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Créer un sondage Décido · ' . config('app.name'))
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
                    options: ['', ''],
                    voteMode: 'single_choice'
                }" method="POST" action="{{ route('decido.store') }}">
                    @csrf

                    <div class="mb-5">
                        <h2 class="h4 mb-3">1. Type de sondage</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="d-flex flex-column h-100 border rounded p-3" style="cursor: pointer;"
                                       x-on:click="type = 'date'">
                                    <input type="radio" name="type" value="date" class="d-none" x-model="type" required>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <strong>Sondage de dates</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Trouve la meilleure date parmi plusieurs propositions.</p>
                                </label>
                            </div>

                            <div class="col-md-6">
                                <label class="d-flex flex-column h-100 border rounded p-3" style="cursor: pointer;"
                                       x-on:click="type = 'classic'">
                                    <input type="radio" name="type" value="classic" class="d-none" x-model="type" required>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <strong>Sondage classique</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Choisis entre plusieurs options libres.</p>
                                </label>
                            </div>
                        </div>
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
                                <label for="range_start_time" class="form-label">Début de la plage horaire</label>
                                <input type="time" id="range_start_time" name="range_start_time" class="form-control" value="{{ old('range_start_time', '09:00') }}">
                                @error('range_start_time')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="range_end_time" class="form-label">Fin de la plage horaire</label>
                                <input type="time" id="range_end_time" name="range_end_time" class="form-control" value="{{ old('range_end_time', '17:00') }}">
                                @error('range_end_time')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

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
                            <template x-for="(date, index) in candidateDates" :key="index">
                                <div class="input-group mb-2">
                                    <input type="date" name="candidate_dates[]" class="form-control" x-model="candidateDates[index]">
                                    <button type="button" class="btn btn-outline-secondary"
                                            x-on:click="candidateDates.splice(index, 1)"
                                            x-show="candidateDates.length > 1">
                                        Retirer
                                    </button>
                                </div>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-primary" x-on:click="candidateDates.push('')">
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
                                    <button type="button" class="btn btn-outline-secondary"
                                            x-on:click="options.splice(index, 1)"
                                            x-show="options.length > 2">
                                        Retirer
                                    </button>
                                </div>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-primary" x-on:click="options.push('')">
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
