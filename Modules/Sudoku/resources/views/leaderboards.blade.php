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
              @auth<li class="nav-item"><a class="nav-link" href="{{ route('sudoku.my-games') }}" style="color:#053d4a;font-weight:600;">{{ __('Mes parties') }}</a></li>@endauth
            </ul>

            {{-- #206 : refactor CSS-driven, Bootstrap gere active via class .active.
                 Couleurs fonçees WCAG 2.2 AAA (>=7:1 sur blanc).
                 Mode inactif : text colore fonce / Mode actif : bg colore + text blanc. --}}
            <ul class="nav nav-pills lb-pills mb-3 flex-wrap gap-2" role="tablist">
              @foreach(['easy'=>'Facile','medium'=>'Moyen','hard'=>'Difficile','expert'=>'Expert','diabolical'=>'Diabolique'] as $diff => $label)
                <li class="nav-item">
                  <button class="nav-link lb-pill lb-{{ $diff }} {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $diff }}" type="button">
                    <span class="lb-dot" aria-hidden="true"></span>
                    {{ __($label) }}
                  </button>
                </li>
              @endforeach
              <li class="nav-item"><button class="nav-link lb-pill lb-period" data-bs-toggle="tab" data-bs-target="#tab-week" type="button">{{ __('Semaine') }}</button></li>
              <li class="nav-item"><button class="nav-link lb-pill lb-period" data-bs-toggle="tab" data-bs-target="#tab-month" type="button">{{ __('Mois') }}</button></li>
              <li class="nav-item"><button class="nav-link lb-pill lb-period" data-bs-toggle="tab" data-bs-target="#tab-alltime" type="button">{{ __('All-time') }}</button></li>
              <li class="nav-item"><button class="nav-link lb-pill lb-period" data-bs-toggle="tab" data-bs-target="#tab-streaks" type="button">{{ __('Séries') }}</button></li>
            </ul>
            <style>
              .lb-pills .lb-pill {
                padding: 6px 14px;
                font-size: 0.85rem;
                font-weight: 700;
                border-radius: 999px;
                background: transparent;
                border: 1px solid transparent;
                transition: all 150ms ease;
              }
              .lb-pill .lb-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }

              /* WCAG 2.2 AAA : ratio >=7:1 sur fond blanc (mode inactif) ET sur bg colore (mode actif) */
              .lb-pill.lb-easy { color: #065F46; border-color: #065F46; } /* emerald-800, 8.5:1 */
              .lb-pill.lb-easy .lb-dot { background: #065F46; }
              .lb-pill.lb-easy.active { background: #065F46; color: #fff; border-color: #065F46; }
              .lb-pill.lb-easy.active .lb-dot { background: #fff; }

              .lb-pill.lb-medium { color: #053D4A; border-color: #053D4A; } /* teal-deep Memora, 9.5:1 */
              .lb-pill.lb-medium .lb-dot { background: #053D4A; }
              .lb-pill.lb-medium.active { background: #053D4A; color: #fff; border-color: #053D4A; }
              .lb-pill.lb-medium.active .lb-dot { background: #fff; }

              .lb-pill.lb-hard { color: #4C1D95; border-color: #4C1D95; } /* violet-900, 11:1 */
              .lb-pill.lb-hard .lb-dot { background: #4C1D95; }
              .lb-pill.lb-hard.active { background: #4C1D95; color: #fff; border-color: #4C1D95; }
              .lb-pill.lb-hard.active .lb-dot { background: #fff; }

              .lb-pill.lb-expert { color: #7C2D12; border-color: #7C2D12; } /* orange-900, 9.2:1 */
              .lb-pill.lb-expert .lb-dot { background: #7C2D12; }
              .lb-pill.lb-expert.active { background: #7C2D12; color: #fff; border-color: #7C2D12; }
              .lb-pill.lb-expert.active .lb-dot { background: #fff; }

              .lb-pill.lb-diabolical { color: #1f2937; border-color: #1f2937; } /* slate-800, 14:1 */
              .lb-pill.lb-diabolical .lb-dot { background: #1f2937; }
              .lb-pill.lb-diabolical.active { background: #1f2937; color: #fff; border-color: #1f2937; }
              .lb-pill.lb-diabolical.active .lb-dot { background: #fff; }

              .lb-pill.lb-period { color: #053D4A; border-color: #053D4A; }
              .lb-pill.lb-period.active { background: #053D4A; color: #fff; border-color: #053D4A; }

              .lb-pill:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; }

              @@media (max-width: 767px) { .lb-pills .lb-pill { padding: 5px 11px; font-size: 0.8rem; } }
            </style>

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
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
