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

                    @if($poll->vote_mode->value === 'yes_no_maybe')
                        <div class="mb-4">
                            <h2 class="h4 mb-3">Tes disponibilités</h2>
                            @foreach($options as $option)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h3 class="h5 mb-3">{{ $option->label }}</h3>
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach(['yes' => 'Oui', 'maybe' => 'Peut-être', 'no' => 'Non'] as $value => $label)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                           name="votes[{{ $option->id }}]"
                                                           id="vote_{{ $option->id }}_{{ $value }}"
                                                           value="{{ $value }}"
                                                           {{ (($existingVotes[$option->id] ?? null) === $value) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="vote_{{ $option->id }}_{{ $value }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
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
                            @foreach($options as $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                           name="votes"
                                           id="vote_{{ $option->id }}"
                                           value="{{ $option->id }}"
                                           {{ array_key_exists($option->id, $existingVotes) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vote_{{ $option->id }}">
                                        {{ $option->label }}
                                    </label>
                                </div>
                            @endforeach
                            @error('votes')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @elseif($poll->vote_mode->value === 'approval')
                        <div class="mb-4">
                            <h2 class="h4 mb-3">Sélectionne toutes les options qui te conviennent</h2>
                            @foreach($options as $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox"
                                           name="votes[]"
                                           id="vote_{{ $option->id }}"
                                           value="{{ $option->id }}"
                                           {{ array_key_exists($option->id, $existingVotes) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vote_{{ $option->id }}">
                                        {{ $option->label }}
                                    </label>
                                </div>
                            @endforeach
                            @error('votes')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Envoyer mon vote</x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
