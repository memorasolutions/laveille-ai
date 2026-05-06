<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Sudoku quotidien') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Sudoku quotidien'), 'breadcrumbItems' => [__('Outils'), __('Sudoku quotidien')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-12">
        <div class="card shadow-sm" style="border-radius: var(--r-base);">
          <div class="card-body p-4 p-md-5"
               x-data="sudokuApp({{ json_encode(collect($puzzles)->map(fn($p) => ['id' => $p->id, 'difficulty' => $p->difficulty, 'grid_init' => $p->grid_init, 'clues_count' => $p->clues_count, 'label' => $p->getDifficultyLabel(), 'color' => $p->getDifficultyColor()])->values()) }})"
               x-init="init()">

            <h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ __('Sudoku quotidien') }}</h1>
            <p class="text-muted mb-3">{{ __('5 niveaux générés à la demande, gratuitement, en quelques millisecondes. Classements live.') }}</p>

            @isset($date)
              <span class="badge bg-secondary mb-3">{{ __('Grilles du') }} {{ \Carbon\Carbon::parse($date)->isoFormat('LL') }}</span>
            @endisset

            <ul class="nav nav-tabs mb-4" role="tablist" style="border-bottom:2px solid #053d4a;flex-wrap:wrap;">
              <li class="nav-item" role="presentation">
                <a class="nav-link active" href="{{ route('sudoku.play') }}" style="color:#053d4a;font-weight:600;border-bottom:3px solid #053d4a;background:rgba(11,114,133,.08);">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 3l14 9-14 9V3z"/></svg>
                    {{ __('Jouer') }}
                  </span>
                </a>
              </li>
              <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ route('sudoku.leaderboards') }}" style="color:#053d4a;font-weight:600;">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                    {{ __('Classements') }}
                  </span>
                </a>
              </li>
              @auth
              <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ route('sudoku.my-games') }}" style="color:#053d4a;font-weight:600;">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ __('Mes parties') }}
                  </span>
                </a>
              </li>
              @endauth
            </ul>

            {{-- Pills 5 difficultes (compact pour tenir sur 1 ligne md+) --}}
            <div class="sudoku-pills d-flex flex-wrap gap-2 mb-4" role="tablist" aria-label="{{ __('Niveau de difficulté') }}">
              <template x-for="(puzzle, idx) in puzzles" :key="'pill-'+idx">
                <button type="button"
                        class="sudoku-pill rounded-pill d-inline-flex align-items-center gap-2"
                        :class="{'shadow-sm': activeIdx===idx}"
                        @click="switchTo(idx)"
                        :aria-pressed="activeIdx===idx ? 'true' : 'false'"
                        :style="activeIdx===idx ? `border:2px solid ${puzzle.color}; background:${puzzle.color}; color:#fff` : `border:1px solid ${puzzle.color}; color:${puzzle.color}; background:transparent`">
                  <span :style="activeIdx===idx ? `background:#fff; width:6px; height:6px; display:inline-block; border-radius:50%;` : `background:${puzzle.color}; width:6px; height:6px; display:inline-block; border-radius:50%;`"></span>
                  <strong x-text="puzzle.label"></strong>
                  <span class="sudoku-pill-badge" :style="activeIdx===idx ? `background:rgba(255,255,255,0.25); color:#fff` : `background:#f1f5f9; color:#475569`" x-text="puzzle.clues_count"></span>
                </button>
              </template>
            </div>

            {{-- Layout 2 colonnes : grille + sidebar --}}
            <div class="row g-4">
              <div class="col-lg-8 col-12">
                <div class="sudoku-grid mx-auto" role="grid" :aria-label="'Grille Sudoku ' + currentDifficulty">
                  <template x-for="(row, r) in grid" :key="'r'+r">
                    <template x-for="(value, c) in row" :key="'c'+r+'-'+c">
                      <div class="sudoku-cell"
                           :class="cellClass(r, c)"
                           :data-row="r"
                           :data-col="c"
                           role="gridcell"
                           :tabindex="originalGrid[r][c] === 0 ? 0 : -1"
                           :aria-label="cellAriaLabel(r, c)"
                           @click="selectCell(r, c)"
                           @keydown="handleKey($event, r, c)">
                        <span x-text="value === 0 ? '' : value"></span>
                      </div>
                    </template>
                  </template>
                </div>

                {{-- Keypad --}}
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2" id="numeric-keypad" aria-label="{{ __('Clavier numérique') }}">
                  <template x-for="n in 9" :key="'k'+n">
                    <button type="button" class="btn btn-outline-secondary sudoku-key" @click="inputValue(n)" :disabled="completed||paused" :aria-label="'Saisir le chiffre ' + n" x-text="n"></button>
                  </template>
                  <button type="button" class="btn btn-outline-danger sudoku-key" @click="clearCell()" :disabled="completed||paused" aria-label="{{ __('Effacer la cellule') }}">&times;</button>
                </div>
              </div>

              <aside class="col-lg-4 col-12">
                <div class="card border-0" style="background:rgba(11,114,133,0.04); border:1px solid rgba(11,114,133,0.15)!important; border-radius:10px;">
                  <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>{{ __('Temps') }}</strong>
                      <span x-text="formatTime(timer)" class="font-monospace fs-5" style="color:#053d4a;"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>{{ __('Erreurs') }}</strong>
                      <span x-text="errorsCount" class="badge" :class="errorsCount > 0 ? 'bg-danger' : 'bg-success'"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <strong>{{ __('Indices') }}</strong>
                      <span x-text="hintsUsed" class="badge bg-warning text-dark"></span>
                    </div>

                    <div class="d-grid gap-2">
                      <button type="button" class="btn" @click="verifyComplete()" :disabled="completed||paused" style="background:#053d4a;color:#fff;font-weight:600;">
                        <span class="d-inline-flex align-items-center gap-2 justify-content-center">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                          {{ __('Vérifier la grille') }}
                        </span>
                      </button>
                      <button type="button" class="btn" @click="useHint()" :disabled="completed||paused" style="background:#0B7285;color:#fff;font-weight:600;">
                        {{ __('Indice') }}
                      </button>
                      <button type="button" class="btn btn-outline-secondary" @click="togglePause()" :disabled="completed">
                        <span x-text="paused ? '{{ __('Reprendre') }}' : '{{ __('Pause') }}'"></span>
                      </button>
                      <button type="button" class="btn btn-outline-secondary" @click="askRestart()">{{ __('Recommencer') }}</button>
                    </div>

                    <div id="sudoku-status" class="mt-3 text-center small" style="min-height:1.5em;color:#053d4a;" aria-live="polite" x-text="statusMessage"></div>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal victoire (anti-popup natif - pattern Memora) --}}
  <div class="modal fade" id="winModal" tabindex="-1" aria-labelledby="winModalLabel" aria-hidden="true" x-ref="winModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:12px;border:none;">
        <div class="modal-header" style="background:linear-gradient(135deg, #0B7285 0%, #053d4a 100%);color:#fff;border-bottom:none;">
          <h5 class="modal-title" id="winModalLabel">🎉 {{ __('Bravo !') }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body p-4">
          <p class="lead text-center">{{ __('Grille résolue en') }} <strong x-text="formatTime(timer)" style="color:#053d4a;"></strong></p>
          <p class="text-center text-muted">{{ __('Erreurs') }} : <strong x-text="errorsCount"></strong> &middot; {{ __('Indices') }} : <strong x-text="hintsUsed"></strong></p>
          <div class="mb-3">
            <label for="pseudoInput" class="form-label">{{ __('Pseudo (pour le classement)') }}</label>
            <input type="text" class="form-control" id="pseudoInput" maxlength="30" x-model="pseudo" placeholder="{{ __('Anonyme') }}">
          </div>
          <div x-show="resultMessage" :class="resultIsSuccess ? 'alert alert-success' : 'alert alert-danger'" x-text="resultMessage"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Plus tard') }}</button>
          <button type="button" class="btn" @click="submitScore()" :disabled="submitting" style="background:#053d4a;color:#fff;font-weight:600;">
            <span x-show="!submitting">{{ __('Soumettre score') }}</span>
            <span x-show="submitting">{{ __('Envoi...') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal restart (anti-popup natif) --}}
  <div class="modal fade" id="restartModal" tabindex="-1" aria-hidden="true" x-ref="restartModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:12px;border:none;">
        <div class="modal-header"><h5 class="modal-title">{{ __('Recommencer ?') }}</h5></div>
        <div class="modal-body"><p>{{ __('Toute progression sur cette grille sera perdue. Confirmer ?') }}</p></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
          <button type="button" class="btn btn-danger" @click="restartGrid()">{{ __('Recommencer') }}</button>
        </div>
      </div>
    </div>
  </div>
</section>

<script type="application/ld+json">
@verbatim
{
  "@context": "https://schema.org",
  "@type": "Game",
  "name": "Sudoku quotidien",
  "description": "Sudoku 9x9, 5 niveaux genere a la demande, classements live.",
  "gamePlatform": "Web",
@endverbatim
  "url": "{{ route('sudoku.play') }}"
@verbatim
}
@endverbatim
</script>

<style>
.sudoku-grid {
  --sudoku-cell: 44px;
  display: grid;
  grid-template-columns: repeat(9, var(--sudoku-cell));
  grid-template-rows: repeat(9, var(--sudoku-cell));
  background: #1f2937;
  border: 3px solid #1f2937;
  gap: 1px;
  /* fit-content : largeur strictement = 9 cellules + 8 gaps + 6px border.
     Evite l'erreur de calc() qui oubliait les gaps internes -> bordure
     droite coupee. */
  width: fit-content;
  max-width: 100%;
  user-select: none;
}
.sudoku-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  font-size: 1.25rem;
  font-weight: 600;
  color: #0f172a;
  cursor: pointer;
  position: relative;
}
.sudoku-cell.is-given {
  background: #f1f5f9;
  font-weight: 700;
  color: #053d4a;
  cursor: default;
}
.sudoku-cell.is-selected {
  outline: 3px solid #C2410C;
  outline-offset: -3px;
  z-index: 2;
}
.sudoku-cell.is-error {
  background: #fee2e2 !important;
  color: #991b1b;
}
.sudoku-cell.peer-highlight {
  background: #e0f2fe;
}
/* #172 fix : Alpine <template x-for> insere des markers commentaires qui cassent
   :nth-child. Utiliser data-col/data-row attribute selectors (positions explicites). */
.sudoku-cell[data-col="2"],
.sudoku-cell[data-col="5"] { border-right: 2px solid #1f2937; }
.sudoku-cell[data-row="2"],
.sudoku-cell[data-row="5"] { border-bottom: 2px solid #1f2937; }
.sudoku-cell:focus {
  outline: 3px solid #C2410C;
  outline-offset: -3px;
  z-index: 3;
}
.sudoku-key {
  /* #174 v2 fix : !important pour battre Bootstrap .btn padding override. */
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  min-width: 44px;
  min-height: 44px;
  padding: 0 !important;
  font-size: 1.1rem !important;
  font-weight: 600;
  line-height: 1 !important;
}

/* #175 : pills compactes pour tenir sur 1 ligne md+ */
.sudoku-pill {
  background: transparent;
  padding: 6px 12px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 150ms ease;
}
.sudoku-pill:hover { transform: translateY(-1px); }
.sudoku-pill-badge {
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: 999px;
  font-weight: 700;
}
@@media (max-width: 767px) {
  .sudoku-pill { padding: 4px 10px; font-size: 0.8rem; }
  .sudoku-pill strong { font-size: 0.85rem; }
}
@@media (max-width: 576px) {
  .sudoku-grid { --sudoku-cell: 34px; }
  .sudoku-key { min-width: 40px; min-height: 40px; }
}
@@media (prefers-reduced-motion: reduce) {
  .sudoku-cell, .sudoku-key { transition: none !important; }
}
</style>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('sudokuApp', (puzzles) => ({
    puzzles,
    activeIdx: 0,
    currentPuzzleId: puzzles[0].id,
    currentDifficulty: puzzles[0].difficulty,
    originalGrid: JSON.parse(JSON.stringify(puzzles[0].grid_init)),
    grid: JSON.parse(JSON.stringify(puzzles[0].grid_init)),
    selectedCell: { row: -1, col: -1 },
    timer: 0,
    timerId: null,
    autoSaveId: null,
    paused: false,
    hintsUsed: 0,
    errorsCount: 0,
    completed: false,
    statusMessage: '',
    pseudo: '',
    submitting: false,
    resultMessage: '',
    resultIsSuccess: false,
    winModalEl: null,
    restartModalEl: null,
    errorsCells: new Set(),

    init() {
      this.pseudo = localStorage.getItem('sudoku_pseudo') || '';
      this.winModalEl = new bootstrap.Modal(this.$refs.winModal);
      this.restartModalEl = new bootstrap.Modal(this.$refs.restartModal);
      this.restoreLocalState();
      this.startTimer();
      this.startAutosave();
    },

    switchTo(idx) {
      if (this.activeIdx === idx) return;
      this.saveLocalState();
      this.activeIdx = idx;
      this.currentPuzzleId = this.puzzles[idx].id;
      this.currentDifficulty = this.puzzles[idx].difficulty;
      this.originalGrid = JSON.parse(JSON.stringify(this.puzzles[idx].grid_init));
      this.grid = JSON.parse(JSON.stringify(this.puzzles[idx].grid_init));
      this.timer = 0;
      this.hintsUsed = 0;
      this.errorsCount = 0;
      this.completed = false;
      this.paused = false;
      this.errorsCells.clear();
      this.statusMessage = '';
      this.selectedCell = { row: -1, col: -1 };
      this.restoreLocalState();
    },

    cellClass(r, c) {
      const classes = [];
      if (this.originalGrid[r][c] !== 0) classes.push('is-given');
      if (this.selectedCell.row === r && this.selectedCell.col === c) classes.push('is-selected');
      if (this.errorsCells.has(r + '-' + c)) classes.push('is-error');
      const sel = this.selectedCell;
      if (sel.row >= 0 && (sel.row === r || sel.col === c ||
          (Math.floor(sel.row/3) === Math.floor(r/3) && Math.floor(sel.col/3) === Math.floor(c/3)))) {
        if (!(sel.row === r && sel.col === c)) classes.push('peer-highlight');
      }
      return classes.join(' ');
    },

    cellAriaLabel(r, c) {
      const v = this.grid[r][c];
      return 'Ligne ' + (r+1) + ', colonne ' + (c+1) + ', ' + (v === 0 ? 'vide' : 'valeur ' + v);
    },

    selectCell(r, c) {
      if (this.completed || this.paused) return;
      this.selectedCell = { row: r, col: c };
    },

    inputValue(n) {
      if (this.completed || this.paused) return;
      const { row, col } = this.selectedCell;
      if (row === -1 || this.originalGrid[row][col] !== 0) return;
      const num = parseInt(n, 10);
      if (isNaN(num) || num < 1 || num > 9) return;
      this.grid[row][col] = num;
      const valid = this.checkLocal(row, col, num);
      if (!valid) {
        this.errorsCount++;
        this.errorsCells.add(row + '-' + col);
        this.statusMessage = 'Conflit ligne ' + (row+1) + ', colonne ' + (col+1) + '.';
        setTimeout(() => { this.statusMessage = ''; }, 2500);
      } else {
        this.errorsCells.delete(row + '-' + col);
      }
    },

    clearCell() {
      if (this.completed || this.paused) return;
      const { row, col } = this.selectedCell;
      if (row === -1 || this.originalGrid[row][col] !== 0) return;
      this.grid[row][col] = 0;
      this.errorsCells.delete(row + '-' + col);
    },

    checkLocal(row, col, value) {
      for (let c = 0; c < 9; c++) if (c !== col && this.grid[row][c] === value) return false;
      for (let r = 0; r < 9; r++) if (r !== row && this.grid[r][col] === value) return false;
      const br = Math.floor(row/3)*3, bc = Math.floor(col/3)*3;
      for (let r = br; r < br+3; r++) for (let c = bc; c < bc+3; c++) {
        if ((r !== row || c !== col) && this.grid[r][c] === value) return false;
      }
      return true;
    },

    useHint() {
      if (this.completed || this.paused) return;
      for (let r = 0; r < 9; r++) {
        for (let c = 0; c < 9; c++) {
          if (this.originalGrid[r][c] === 0 && this.grid[r][c] === 0) {
            for (let n = 1; n <= 9; n++) {
              if (this.checkLocal(r, c, n)) {
                this.grid[r][c] = n;
                this.hintsUsed++;
                this.selectedCell = { row: r, col: c };
                this.statusMessage = 'Indice : (' + (r+1) + ',' + (c+1) + ') = ' + n + '.';
                setTimeout(() => { this.statusMessage = ''; }, 3000);
                return;
              }
            }
          }
        }
      }
    },

    togglePause() {
      this.paused = !this.paused;
      if (this.paused) clearInterval(this.timerId);
      else this.startTimer();
    },

    startTimer() {
      clearInterval(this.timerId);
      this.timerId = setInterval(() => {
        if (!this.paused && !this.completed) this.timer++;
      }, 1000);
    },

    startAutosave() {
      clearInterval(this.autoSaveId);
      this.autoSaveId = setInterval(() => {
        if (!this.completed) this.saveLocalState();
      }, 30000);
    },

    saveState() {
      this.saveLocalState();
      this.statusMessage = 'Partie sauvegardee localement.';
      setTimeout(() => { this.statusMessage = ''; }, 2500);
    },

    saveLocalState() {
      if (!this.currentPuzzleId) return;
      try {
        localStorage.setItem('sudoku_state_' + this.currentPuzzleId, JSON.stringify({
          grid: this.grid,
          timer: this.timer,
          hintsUsed: this.hintsUsed,
          errorsCount: this.errorsCount,
          paused: this.paused,
          completed: this.completed,
        }));
      } catch (e) { /* ignore */ }
    },

    restoreLocalState() {
      try {
        const raw = localStorage.getItem('sudoku_state_' + this.currentPuzzleId);
        if (!raw) return;
        const data = JSON.parse(raw);
        if (data.grid) this.grid = data.grid;
        this.timer = data.timer || 0;
        this.hintsUsed = data.hintsUsed || 0;
        this.errorsCount = data.errorsCount || 0;
        this.completed = !!data.completed;
        this.paused = !!data.paused;
        if (data.grid) {
          this.statusMessage = 'Partie restauree.';
          setTimeout(() => { this.statusMessage = ''; }, 2500);
        }
      } catch (e) { /* ignore */ }
    },

    askRestart() {
      this.restartModalEl?.show();
    },

    restartGrid() {
      this.grid = JSON.parse(JSON.stringify(this.originalGrid));
      this.timer = 0;
      this.hintsUsed = 0;
      this.errorsCount = 0;
      this.errorsCells.clear();
      this.completed = false;
      this.paused = false;
      this.selectedCell = { row: -1, col: -1 };
      try { localStorage.removeItem('sudoku_state_' + this.currentPuzzleId); } catch(e) {}
      this.startTimer();
      this.restartModalEl?.hide();
    },

    verifyComplete() {
      for (let r = 0; r < 9; r++) for (let c = 0; c < 9; c++) {
        if (this.grid[r][c] === 0) {
          this.statusMessage = 'Grille incomplete.';
          setTimeout(() => { this.statusMessage = ''; }, 2500);
          return;
        }
      }
      for (let r = 0; r < 9; r++) for (let c = 0; c < 9; c++) {
        if (!this.checkLocal(r, c, this.grid[r][c])) {
          this.statusMessage = 'Erreur ligne ' + (r+1) + ', colonne ' + (c+1) + '.';
          setTimeout(() => { this.statusMessage = ''; }, 2500);
          return;
        }
      }
      this.completed = true;
      clearInterval(this.timerId);
      this.saveLocalState();
      this.winModalEl?.show();
    },

    async submitScore() {
      if (this.submitting) return;
      this.submitting = true;
      this.resultMessage = '';
      try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        localStorage.setItem('sudoku_pseudo', this.pseudo);
        const res = await fetch('/api/sudoku/score', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf || '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            puzzle_id: this.currentPuzzleId,
            pseudo: this.pseudo || 'Anonyme',
            time_seconds: this.timer,
            hints_used: this.hintsUsed,
            errors_count: this.errorsCount,
            grid_complete: this.grid,
          }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
          this.resultIsSuccess = true;
          this.resultMessage = 'Score ' + data.score + ' ! Rang du jour : ' + (data.rank_today || '-') + (data.is_published === false ? ' (non classe : temps trop court)' : '');
        } else {
          this.resultIsSuccess = false;
          this.resultMessage = data.error || 'Erreur de soumission.';
        }
      } catch (e) {
        this.resultIsSuccess = false;
        this.resultMessage = 'Erreur reseau.';
      } finally {
        this.submitting = false;
      }
    },

    formatTime(s) {
      const m = Math.floor(s / 60), x = s % 60;
      return m.toString().padStart(2, '0') + ':' + x.toString().padStart(2, '0');
    },

    handleKey(e, r, c) {
      if (e.key >= '1' && e.key <= '9') { e.preventDefault(); this.selectCell(r, c); this.inputValue(parseInt(e.key, 10)); }
      else if (e.key === 'Backspace' || e.key === 'Delete' || e.key === '0') { e.preventDefault(); this.selectCell(r, c); this.clearCell(); }
      else if (e.key === 'ArrowUp' && r > 0) { e.preventDefault(); this.selectCell(r-1, c); }
      else if (e.key === 'ArrowDown' && r < 8) { e.preventDefault(); this.selectCell(r+1, c); }
      else if (e.key === 'ArrowLeft' && c > 0) { e.preventDefault(); this.selectCell(r, c-1); }
      else if (e.key === 'ArrowRight' && c < 8) { e.preventDefault(); this.selectCell(r, c+1); }
    },
  }));
});
</script>
@endsection
