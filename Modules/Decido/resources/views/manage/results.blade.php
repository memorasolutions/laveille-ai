{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', "Résultats - {$poll->title} · " . config('app.name'))
@section('meta_description', "Résultats du sondage Décido « {$poll->title} » : visualise les votes, clôture le sondage et exporte les données.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Résultats du sondage'])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding" x-data="{}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="h2 mb-4">Résultats - {{ $poll->title }}</h1>

                @if(session('admin_token_plain'))
                    @php
                        $adminUrl = route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => session('admin_token_plain')]);
                    @endphp
                    <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                        <div>
                            <strong>Lien d'administration à conserver précieusement, il ne sera plus jamais réaffiché :</strong><br>
                            <code class="d-block mt-1" style="word-break: break-all;">{{ $adminUrl }}</code>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-dark flex-shrink-0"
                                x-on:click="navigator.clipboard.writeText('{{ $adminUrl }}')">
                            Copier
                        </button>
                    </div>
                @endif

                @php
                    $totalVoters = $options->flatMap(fn ($opt) => $opt->votes)->unique('voter_pseudonym')->count();
                @endphp

                @foreach($options as $option)
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="h5 mb-3">{{ $option->label }}</h3>

                            @if($totalVoters > 0)
                                @php
                                    $positiveVotes = $option->votes->whereIn('value', ['yes', 'selected'])->count();
                                    $percentage = round(($positiveVotes / $totalVoters) * 100);
                                @endphp
                                <div class="mb-3">
                                    <div class="progress" style="height: 12px; border-radius: 6px;">
                                        <div class="progress-bar"
                                             style="width: {{ $percentage }}%; background-color: var(--c-primary);"
                                             role="progressbar"
                                             aria-valuenow="{{ $percentage }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $positiveVotes }}/{{ $totalVoters }} participants</small>
                                </div>
                            @endif

                            @if($option->votes->isNotEmpty())
                                <div class="mt-2">
                                    @foreach($option->votes as $vote)
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $vote->voter_pseudonym }}</span>
                                            <span>{{ match ($vote->value) {
                                                'yes' => 'Oui',
                                                'maybe' => 'Peut-être',
                                                'no' => 'Non',
                                                'selected' => 'Sélectionné',
                                                default => $vote->value,
                                            } }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted small mb-0">Aucun vote pour cette option.</p>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="mt-5 p-3 border rounded">
                    <h3 class="h5 mb-3">Partage et export</h3>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <span class="text-muted">Lien public :</span>
                        <code>{{ $poll->share_url }}</code>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                x-on:click="navigator.clipboard.writeText('{{ $poll->share_url }}')">
                            Copier
                        </button>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <x-core::button :href="route('decido.export.csv', ['poll' => $poll->public_id, 'adminToken' => $adminToken])" variant="secondary">
                            Télécharger en CSV
                        </x-core::button>

                        @if($poll->status->value === 'closed' && $poll->final_option_id)
                            <x-core::button :href="route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => $adminToken])" variant="secondary">
                                Télécharger en ICS
                            </x-core::button>
                        @endif
                    </div>
                </div>

                @if($poll->status->value === 'open')
                    <div class="mt-5 p-3 border rounded bg-light">
                        <h3 class="h5 mb-3">Clôturer le sondage</h3>
                        <form method="POST" action="{{ route('decido.close', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}">
                            @csrf
                            <p class="mb-3">Sélectionne l'option finale :</p>
                            @foreach($options as $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="final_option_id" id="opt_{{ $option->id }}" value="{{ $option->id }}" required>
                                    <label class="form-check-label" for="opt_{{ $option->id }}">{{ $option->label }}</label>
                                </div>
                            @endforeach
                            <x-core::button type="submit" variant="primary">Clôturer le sondage</x-core::button>
                        </form>
                    </div>
                @elseif($poll->status->value === 'closed' && $poll->final_option_id)
                    <div class="mt-5 p-3 border rounded" style="border-color: var(--c-primary); background-color: rgba(6, 78, 90, 0.05);">
                        <h3 class="h5 mb-2" style="color: var(--c-primary);">Option finale choisie</h3>
                        <p class="mb-0">{{ $options->firstWhere('id', $poll->final_option_id)?->label }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
