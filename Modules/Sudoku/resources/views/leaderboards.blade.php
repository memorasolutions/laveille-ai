<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Classements Sudoku')

@section('content')
<div class="container py-4">
    <header class="text-center mb-4">
        <h1 class="display-6 fw-bold" style="color:#0B7285">Classements Sudoku</h1>
        <p class="text-muted">Top des joueurs par difficulte, semaine, mois et all-time.</p>
        <a href="{{ route('sudoku.play') }}" class="btn btn-outline-primary">Jouer maintenant</a>
    </header>

    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach(['easy'=>'Facile','medium'=>'Moyen','hard'=>'Difficile','expert'=>'Expert','diabolical'=>'Diabolique'] as $diff => $label)
            <li class="nav-item">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $diff }}" type="button">{{ $label }}</button>
            </li>
        @endforeach
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-week">Semaine</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-month">Mois</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alltime">All-time</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-streaks">Streaks</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-countries">Pays</button></li>
    </ul>

    <div class="tab-content">
        @foreach(['easy','medium','hard','expert','diabolical'] as $diff)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $diff }}">
                @include('sudoku::partials.score-table', ['rows' => $todayByDifficulty[$diff] ?? collect(), 'showDifficulty' => false])
            </div>
        @endforeach
        <div class="tab-pane fade" id="tab-week">@include('sudoku::partials.score-table', ['rows' => $week, 'showDifficulty' => true])</div>
        <div class="tab-pane fade" id="tab-month">@include('sudoku::partials.score-table', ['rows' => $month, 'showDifficulty' => true])</div>
        <div class="tab-pane fade" id="tab-alltime">@include('sudoku::partials.score-table', ['rows' => $alltime, 'showDifficulty' => true])</div>
        <div class="tab-pane fade" id="tab-streaks">
            <table class="table table-striped">
                <thead><tr><th>#</th><th>Joueur</th><th>Streak actuel</th><th>Plus long streak</th><th>Total</th></tr></thead>
                <tbody>
                @forelse($streaks as $i => $s)
                    <tr><td>{{ $i+1 }}</td><td>#{{ $s->user_id ?? 'anon' }}</td><td>{{ $s->current_streak }}</td><td>{{ $s->longest_streak }}</td><td>{{ $s->total_completed }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Aucun streak enregistre.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade" id="tab-countries">
            <table class="table table-striped">
                <thead><tr><th>Pays</th><th>Parties classees</th></tr></thead>
                <tbody>
                @forelse($countries as $c)
                    <tr><td>{{ $c->country }}</td><td>{{ $c->count }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-3">Aucune donnee pays.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
