<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Archive Sudoku') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Archive Sudoku'), 'breadcrumbItems' => [__('Outils'), __('Sudoku quotidien'), __('Archive')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-12">
        <div class="card shadow-sm" style="border-radius: var(--r-base);">
          <div class="card-body p-4 p-md-5">
            <h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ __('Archive Sudoku') }}</h1>
            <p class="text-muted mb-4">{{ __('Rejouez les grilles déjà générées. Les nouvelles sont créées à la demande, instantanément.') }}</p>

            <ul class="nav nav-tabs mb-4" role="tablist" style="border-bottom:2px solid #053d4a;flex-wrap:wrap;">
              <li class="nav-item"><a class="nav-link" href="{{ route('sudoku.play') }}" style="color:#053d4a;font-weight:600;">{{ __('Jouer') }}</a></li>
              <li class="nav-item"><a class="nav-link" href="{{ route('sudoku.leaderboards') }}" style="color:#053d4a;font-weight:600;">{{ __('Classements') }}</a></li>
              <li class="nav-item"><a class="nav-link active" href="{{ route('sudoku.archive') }}" style="color:#053d4a;font-weight:600;border-bottom:3px solid #053d4a;background:rgba(11,114,133,.08);">{{ __('Archive') }}</a></li>
            </ul>

            @if($days->isEmpty())
              <div class="alert alert-info" role="alert">
                {{ __('Aucune grille archivée pour l\'instant. Lancez une partie pour commencer !') }}
              </div>
            @else
              <div class="row g-3">
                @foreach($days as $day)
                  <div class="col-md-4 col-lg-3">
                    <a href="{{ route('sudoku.date', ['date' => \Carbon\Carbon::parse($day->date)->toDateString()]) }}"
                       class="card text-decoration-none h-100 border-0 shadow-sm hover-lift"
                       style="transition: transform 150ms ease, box-shadow 150ms ease;"
                       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(11,114,133,0.15)'"
                       onmouseout="this.style.transform='';this.style.boxShadow=''">
                      <div class="card-body text-center">
                        <div class="text-muted small text-uppercase" style="letter-spacing:.05em;">{{ \Carbon\Carbon::parse($day->date)->isoFormat('dddd') }}</div>
                        <div class="h5 mb-2 mt-1" style="color:#053d4a;font-weight:700;">{{ \Carbon\Carbon::parse($day->date)->isoFormat('LL') }}</div>
                        <span class="badge" style="background:#0B7285;color:#fff;">{{ $day->puzzle_count }} {{ __('grilles') }}</span>
                      </div>
                    </a>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
