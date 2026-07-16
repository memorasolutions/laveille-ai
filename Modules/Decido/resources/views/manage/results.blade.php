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
                        <button type="button" class="btn btn-sm btn-outline-dark flex-shrink-0 decido-touch-target"
                                x-on:click="navigator.clipboard.writeText('{{ $adminUrl }}')">
                            Copier
                        </button>
                    </div>
                @endif

                @php
                    // Décido round 6 (skill /100) : reclé par voter_token (identifiant réellement unique
                    // d'un votant) plutôt que par voter_pseudonym (texte libre) - deux votants distincts
                    // au même pseudonyme (homonymes) voyaient auparavant un de leurs deux votes
                    // silencieusement écrasé/disparu du résumé et du tableau croisé.
                    $isDateType = $poll->type->value === 'date';
                    $isYesNoMaybe = $poll->vote_mode->value === 'yes_no_maybe';
                    $totalVoters = $options->flatMap(fn ($opt) => $opt->votes)->unique('voter_token')->count();

                    $optionStats = $options->map(function ($option) use ($isYesNoMaybe, $totalVoters) {
                        $votes = $option->votes;
                        $yes = $isYesNoMaybe ? $votes->where('value', 'yes')->count() : $votes->where('value', 'selected')->count();
                        $maybe = $isYesNoMaybe ? $votes->where('value', 'maybe')->count() : 0;
                        $no = $isYesNoMaybe ? $votes->where('value', 'no')->count() : 0;
                        $noResponse = $isYesNoMaybe ? max(0, $totalVoters - $yes - $maybe - $no) : 0;

                        return (object) [
                            'option' => $option,
                            'yes' => $yes,
                            'maybe' => $maybe,
                            'no' => $no,
                            'noResponse' => $noResponse,
                        ];
                    });

                    $bestCount = $optionStats->pluck('yes')->max() ?? 0;
                    $bestOptions = $bestCount > 0 ? $optionStats->where('yes', $bestCount)->values() : collect();
                    $otherOptionsCount = $options->count() - $bestOptions->count();

                    $groupedByDay = $isDateType
                        ? $options->groupBy(fn ($opt) => $opt->starts_at ? \Carbon\Carbon::parse($opt->starts_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->locale('fr')->isoFormat('dddd D MMMM') : null)
                        : null;

                    $voters = $options->flatMap(fn ($opt) => $opt->votes)
                        ->unique('voter_token')
                        ->map(fn ($vote) => (object) ['token' => $vote->voter_token, 'name' => $vote->voter_pseudonym])
                        ->values();

                    $matrix = [];
                    foreach ($options as $option) {
                        foreach ($option->votes as $vote) {
                            $matrix[$option->id][$vote->voter_token] = $vote->value;
                        }
                    }
                @endphp

                <div x-data="{ openDrill: null }">
                    <h2 class="h4 mb-3">{{ $isDateType ? 'Meilleurs créneaux' : 'Options les mieux classées' }}</h2>

                    @if($totalVoters === 0)
                        <div class="alert alert-light border">
                            Aucun vote pour l'instant.
                        </div>
                    @else
                        @foreach($bestOptions as $stat)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h3 class="h5 mb-2">{{ $stat->option->label }}</h3>
                                    @if($isYesNoMaybe)
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="badge" style="background-color: var(--sys-success-bg); color: var(--sys-success); border: 1px solid var(--sys-success);">
                                                ✓ {{ $stat->yes }} oui
                                            </span>
                                            <span class="badge" style="background-color: var(--sys-warning-bg); color: var(--sys-warning); border: 1px solid var(--sys-warning);">
                                                ? {{ $stat->maybe }} peut-être
                                            </span>
                                            <span class="badge" style="background-color: #fff; color: var(--sys-danger); border: 1px solid var(--sys-danger);">
                                                ✕ {{ $stat->no }} non
                                            </span>
                                        </div>
                                        <p class="text-muted small">{{ $stat->noResponse }} sans réponse</p>
                                    @else
                                        <div class="mb-2">
                                            <span class="badge" style="background-color: var(--sys-success-bg); color: var(--sys-success); border: 1px solid var(--sys-success);">
                                                ✓ {{ $stat->yes }} sur {{ $totalVoters }} participants
                                            </span>
                                        </div>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2 decido-touch-target"
                                            x-on:click="openDrill === {{ $stat->option->id }} ? openDrill = null : openDrill = {{ $stat->option->id }}"
                                            x-text="openDrill === {{ $stat->option->id }} ? 'Masquer les réponses' : 'Voir qui a répondu'"
                                            x-bind:aria-expanded="(openDrill === {{ $stat->option->id }}).toString()"
                                            aria-controls="decido-drill-{{ $stat->option->id }}">
                                        Voir qui a répondu
                                    </button>
                                    <div id="decido-drill-{{ $stat->option->id }}" x-show="openDrill === {{ $stat->option->id }}" x-cloak class="mt-3 pt-3 border-top">
                                        @if($stat->option->votes->isEmpty())
                                            <p class="text-muted small">Aucun vote pour cette option.</p>
                                        @else
                                            @foreach($stat->option->votes as $vote)
                                                <div class="d-flex justify-content-between small py-1">
                                                    <span>{{ $vote->voter_pseudonym }}</span>
                                                    <span>
                                                        @php
                                                            $label = match($vote->value) {
                                                                'yes' => 'Oui',
                                                                'maybe' => 'Peut-être',
                                                                'no' => 'Non',
                                                                'selected' => 'Sélectionné',
                                                                default => $vote->value,
                                                            };
                                                        @endphp
                                                        {{ $label }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($otherOptionsCount > 0)
                            <p class="text-muted small">+ {{ $otherOptionsCount }} autre(s) option(s) disponible(s) - <a href="#decido-comparaison-complete">voir le détail complet ci-dessous</a>.</p>
                        @endif
                    @endif
                </div>

                <details id="decido-comparaison-complete" class="mt-4 border rounded p-3">
                    <summary class="h5" style="cursor: pointer;">Comparer toutes les réponses ({{ $voters->count() }} participant(s) × {{ $options->count() }} option(s))</summary>
                    @if($voters->isEmpty())
                        <p class="text-muted">Aucun vote pour l'instant.</p>
                    @else
                        <div class="table-responsive mt-3" style="max-height: 70vh; overflow-y: auto;">
                            <table class="table table-bordered table-sm align-middle">
                                <caption class="visually-hidden">Tableau croisé des réponses par participant et par option</caption>
                                @if($isDateType)
                                    <thead>
                                        <tr>
                                            <th></th>
                                            @foreach($groupedByDay as $dayName => $dayOptions)
                                                <th colspan="{{ $dayOptions->count() }}" scope="colgroup" class="text-center" style="background-color: var(--c-primary); color: #fff;">
                                                    {{ $dayName }}
                                                </th>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            <th scope="col" style="position: sticky; left: 0; background: #fff; z-index: 2;">Participant</th>
                                            @foreach($groupedByDay as $dayOptions)
                                                @foreach($dayOptions as $option)
                                                    <th scope="col" class="text-center small">{{ \Carbon\Carbon::parse($option->starts_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->isoFormat('H[h]mm') }}</th>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </thead>
                                @else
                                    <thead>
                                        <tr>
                                            <th scope="col" style="position: sticky; left: 0; background: #fff; z-index: 2;">Participant</th>
                                            @foreach($options as $option)
                                                <th scope="col" class="text-center">{{ $option->label }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                @endif
                                <tbody>
                                    @foreach($voters as $voter)
                                        <tr>
                                            <th scope="row" style="position: sticky; left: 0; background: #fff; z-index: 1;">{{ $voter->name }}</th>
                                            @if($isDateType)
                                                @foreach($groupedByDay as $dayOptions)
                                                    @foreach($dayOptions as $option)
                                                        @php
                                                            $value = $matrix[$option->id][$voter->token] ?? null;
                                                        @endphp
                                                        <td class="text-center">
                                                            @if($value === 'yes' || $value === 'selected')
                                                                <span aria-label="{{ $voter->name }} : oui" style="color: var(--sys-success);">✓</span>
                                                            @elseif($value === 'maybe')
                                                                <span aria-label="{{ $voter->name }} : peut-être" style="color: var(--sys-warning);">?</span>
                                                            @elseif($value === 'no')
                                                                <span aria-label="{{ $voter->name }} : non" style="color: var(--sys-danger);">✕</span>
                                                            @else
                                                                <span aria-label="{{ $voter->name }} : sans réponse" class="text-muted">–</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                @endforeach
                                            @else
                                                @foreach($options as $option)
                                                    @php
                                                        $value = $matrix[$option->id][$voter->token] ?? null;
                                                    @endphp
                                                    <td class="text-center">
                                                        @if($value === 'yes' || $value === 'selected')
                                                            <span aria-label="{{ $voter->name }} : oui" style="color: var(--sys-success);">✓</span>
                                                        @elseif($value === 'maybe')
                                                            <span aria-label="{{ $voter->name }} : peut-être" style="color: var(--sys-warning);">?</span>
                                                        @elseif($value === 'no')
                                                            <span aria-label="{{ $voter->name }} : non" style="color: var(--sys-danger);">✕</span>
                                                        @else
                                                            <span aria-label="{{ $voter->name }} : sans réponse" class="text-muted">–</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th scope="row" style="position: sticky; left: 0; background: #fff;">Total</th>
                                        @if($isDateType)
                                            @foreach($groupedByDay as $dayOptions)
                                                @foreach($dayOptions as $option)
                                                    @php
                                                        $stat = $optionStats->firstWhere('option.id', $option->id);
                                                    @endphp
                                                    <td class="text-center small">{{ $stat->yes }}✓</td>
                                                @endforeach
                                            @endforeach
                                        @else
                                            @foreach($options as $option)
                                                @php
                                                    $stat = $optionStats->firstWhere('option.id', $option->id);
                                                @endphp
                                                <td class="text-center small">{{ $stat->yes }}✓</td>
                                            @endforeach
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </details>

                <div class="mt-5 p-3 border rounded">
                    <h3 class="h5 mb-3">Partage et export</h3>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <span class="text-muted">Lien public :</span>
                        <code>{{ $poll->share_url }}</code>
                        <button type="button" class="btn btn-sm btn-outline-secondary decido-touch-target"
                                x-on:click="navigator.clipboard.writeText('{{ $poll->share_url }}')">
                            Copier
                        </button>
                    </div>

                    @if($poll->getShortUrlString())
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <span class="text-muted">Lien court :</span>
                            <code>{{ $poll->getShortUrlString() }}</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary decido-touch-target"
                                    x-on:click="navigator.clipboard.writeText('{{ $poll->getShortUrlString() }}')">
                                Copier
                            </button>
                        </div>
                    @else
                        <form method="POST" action="{{ route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary decido-touch-target">
                                Créer un lien court
                            </button>
                        </form>
                    @endif

                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <img src="{{ route('decido.qr', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}"
                             alt="QR code du sondage {{ $poll->title }}" width="140" height="140" loading="lazy"
                             class="border rounded">
                        <a href="{{ route('decido.qr', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}"
                           download="qr-decido-{{ $poll->public_id }}.png"
                           class="btn btn-sm btn-outline-secondary decido-touch-target">
                            Télécharger le QR code
                        </a>
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
{{-- .decido-touch-target : voir public/css/charte.css (round 8, déplacé hors de cette vue pour
     rester DRY - réutilisé aussi par create.blade.php et public/vote.blade.php). --}}
@endsection
