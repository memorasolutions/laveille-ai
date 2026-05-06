<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Classements Sudoku') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Classements Sudoku'), 'breadcrumbItems' => [__('Outils'), __('Sudoku quotidien'), __('Classements')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-12">
        <div class="card shadow-sm" style="border-radius: var(--r-base);">
          <div class="card-body p-4 p-md-5">
            <h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ __('Classements Sudoku') }}</h1>
            <p class="text-muted mb-4">{{ __('Top des joueurs par difficulté, semaine, mois et all-time. Mise à jour toutes les 5 minutes.') }}</p>

            <ul class="nav nav-tabs mb-4" role="tablist" style="border-bottom:2px solid #053d4a;flex-wrap:wrap;">
              <li class="nav-item"><a class="nav-link" href="{{ route('sudoku.play') }}" style="color:#053d4a;font-weight:600;">{{ __('Jouer') }}</a></li>
              <li class="nav-item"><a class="nav-link active" href="{{ route('sudoku.leaderboards') }}" style="color:#053d4a;font-weight:600;border-bottom:3px solid #053d4a;background:rgba(11,114,133,.08);">{{ __('Classements') }}</a></li>
              <li class="nav-item"><a class="nav-link" href="{{ route('sudoku.archive') }}" style="color:#053d4a;font-weight:600;">{{ __('Archive') }}</a></li>
            </ul>

            <ul class="nav nav-pills mb-3 flex-wrap gap-2" role="tablist">
              @foreach(['easy'=>['Facile','#10B981'],'medium'=>['Moyen','#0B7285'],'hard'=>['Difficile','#7C3AED'],'expert'=>['Expert','#C2410C'],'diabolical'=>['Diabolique','#1f2937']] as $diff => $info)
                <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $diff }}" type="button" style="font-weight:600;{{ $loop->first ? 'background:'.$info[1].';color:#fff;' : 'color:'.$info[1].';' }}">
                  <span class="d-inline-block rounded-circle me-2" style="width:8px;height:8px;background:{{ $info[1] }}"></span>
                  {{ __($info[0]) }}
                </button></li>
              @endforeach
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-week" type="button" style="color:#053d4a;font-weight:600;">{{ __('Semaine') }}</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-month" type="button" style="color:#053d4a;font-weight:600;">{{ __('Mois') }}</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alltime" type="button" style="color:#053d4a;font-weight:600;">{{ __('All-time') }}</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-streaks" type="button" style="color:#053d4a;font-weight:600;">{{ __('Streaks') }}</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-countries" type="button" style="color:#053d4a;font-weight:600;">{{ __('Pays') }}</button></li>
            </ul>

            <div class="tab-content">
              @foreach(['easy','medium','hard','expert','diabolical'] as $diff)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $diff }}" role="tabpanel">
                  @include('sudoku::partials.score-table', ['rows' => $todayByDifficulty[$diff] ?? collect(), 'showDifficulty' => false])
                </div>
              @endforeach
              <div class="tab-pane fade" id="tab-week" role="tabpanel">
                @include('sudoku::partials.score-table', ['rows' => $week ?? collect(), 'showDifficulty' => true])
              </div>
              <div class="tab-pane fade" id="tab-month" role="tabpanel">
                @include('sudoku::partials.score-table', ['rows' => $month ?? collect(), 'showDifficulty' => true])
              </div>
              <div class="tab-pane fade" id="tab-alltime" role="tabpanel">
                @include('sudoku::partials.score-table', ['rows' => $alltime ?? collect(), 'showDifficulty' => true])
              </div>
              <div class="tab-pane fade" id="tab-streaks" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead>
                      <tr style="color:#053d4a;">
                        <th>#</th>
                        <th>{{ __('Joueur') }}</th>
                        <th>{{ __('Streak actuel') }}</th>
                        <th>{{ __('Plus long streak') }}</th>
                        <th>{{ __('Total') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($streaks as $i => $s)
                        <tr>
                          <td>{{ $i+1 }}</td>
                          <td>{{ $s->user->name ?? __('Anonyme') }}</td>
                          <td><span class="badge bg-warning text-dark">{{ $s->current_streak }}</span></td>
                          <td><span class="badge" style="background:#053d4a;color:#fff;">{{ $s->longest_streak }}</span></td>
                          <td>{{ $s->total_completed }}</td>
                        </tr>
                      @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('Aucun streak enregistré.') }}</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tab-pane fade" id="tab-countries" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead>
                      <tr style="color:#053d4a;"><th>{{ __('Pays') }}</th><th>{{ __('Parties classées') }}</th></tr>
                    </thead>
                    <tbody>
                      @forelse($countries as $c)
                        <tr><td>{{ $c->country }}</td><td>{{ $c->count }}</td></tr>
                      @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">{{ __('Aucune donnée par pays.') }}</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
