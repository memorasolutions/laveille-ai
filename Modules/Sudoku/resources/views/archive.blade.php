<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Archive Sudoku')

@section('content')
<div class="container py-4">
    <header class="text-center mb-4">
        <h1 class="display-6 fw-bold" style="color:#0B7285">Archive Sudoku</h1>
        <p class="text-muted">Rejouez les grilles des 30 derniers jours.</p>
        <a href="{{ route('sudoku.play') }}" class="btn btn-outline-primary">Retour au jeu du jour</a>
    </header>

    @if($days->isEmpty())
        <div class="alert alert-info text-center">Aucune grille archivee pour l'instant.</div>
    @else
        <div class="row g-3">
            @foreach($days as $day)
                <div class="col-md-4 col-lg-3">
                    <a href="{{ route('sudoku.date', ['date' => \Carbon\Carbon::parse($day->date)->toDateString()]) }}"
                       class="card text-decoration-none h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="text-muted small">{{ \Carbon\Carbon::parse($day->date)->isoFormat('dddd') }}</div>
                            <div class="h4 mb-0" style="color:#0B7285">{{ \Carbon\Carbon::parse($day->date)->isoFormat('LL') }}</div>
                            <span class="badge bg-light text-dark mt-2">{{ $day->puzzle_count }} grilles</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
