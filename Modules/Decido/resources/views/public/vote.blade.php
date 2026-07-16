{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', "{$poll->title} · " . config('app.name'))
@section('meta_description', "Participe au sondage Décido « {$poll->title} » pour aider à trouver le bon moment ou le bon choix.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $poll->title])
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
                        <div class="mb-4">
                            <h2 class="h4 mb-3">Tes disponibilités</h2>
                            @foreach($options as $option)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h3 class="h5 mb-3">{{ $option->label }}</h3>
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
                    </style>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Envoyer mon vote</x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
