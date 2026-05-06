<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Sudoku quotidien' . (isset($date) ? ' du ' . $date : ''))

@push('meta')
    <meta property="og:title" content="Sudoku quotidien - La veille">
    <meta property="og:description" content="5 grilles fraiches chaque jour, 5 niveaux, classements live.">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Game",
      "name": "Sudoku quotidien",
      "description": "Sudoku 9x9 avec 5 niveaux de difficulte, classements et streak.",
      "gamePlatform": "Web",
      "url": "{{ route('sudoku.play') }}"
    }
    </script>
@endpush

@section('content')
<div class="container py-4" x-data="sudokuApp({{ json_encode(collect($puzzles)->map(fn($p, $k) => ['id' => $p->id, 'difficulty' => $p->difficulty, 'grid_init' => $p->grid_init, 'clues_count' => $p->clues_count, 'label' => $p->getDifficultyLabel(), 'color' => $p->getDifficultyColor()])->values()) }})" x-init="init()">

    <header class="text-center mb-4">
        <h1 class="display-6 fw-bold" style="color:#0B7285">Sudoku quotidien</h1>
        <p class="text-muted">5 grilles fraiches chaque jour, 5 niveaux, classements live.</p>
        @isset($date)
            <p class="badge bg-secondary">Grilles du {{ \Carbon\Carbon::parse($date)->isoFormat('LL') }}</p>
        @endisset
        <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
            <a href="{{ route('sudoku.leaderboards') }}" class="btn btn-outline-primary">Classements</a>
            <a href="{{ route('sudoku.archive') }}" class="btn btn-outline-secondary">Archive</a>
        </div>
    </header>

    <ul class="nav nav-pills justify-content-center mb-4 flex-wrap" role="tablist">
        @foreach(['easy','medium','hard','expert','diabolical'] as $idx => $level)
            @php($puzzle = $puzzles[$level])
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link mx-1 d-flex align-items-center gap-2"
                    :class="{ 'active': activeIdx === {{ $idx }} }"
                    @click="switchTo({{ $idx }})"
                    role="tab"
                    aria-controls="sudoku-pane"
                    :aria-selected="activeIdx === {{ $idx }} ? 'true' : 'false'"
                >
                    <span class="d-inline-block rounded-circle" style="width:12px;height:12px;background:{{ $puzzle->getDifficultyColor() }}"></span>
                    <strong>{{ $puzzle->getDifficultyLabel() }}</strong>
                    <span class="badge bg-light text-dark">{{ $puzzle->clues_count }} indices</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div id="sudoku-pane" role="tabpanel" class="row g-4">
        <div class="col-lg-8">
            <div class="sudoku-grid mx-auto" role="grid" :aria-label="'Grille Sudoku ' + currentDifficulty">
                <template x-for="(row, r) in grid" :key="'r'+r">
                    <template x-for="(value, c) in row" :key="'c'+r+'-'+c">
                        <div class="sudoku-cell"
                             :class="cellClass(r, c)"
                             :data-row="r" :data-col="c"
                             role="gridcell"
                             :aria-label="cellAriaLabel(r, c)"
                             :tabindex="originalGrid[r][c] === 0 ? 0 : -1"
                             @click="selectCell(r, c)"
                             @keydown="handleKey($event, r, c)"
                        >
                            <span x-text="value === 0 ? '' : value"></span>
                        </div>
                    </template>
                </template>
            </div>

            <div id="numeric-keypad" class="mt-3 d-flex flex-wrap justify-content-center gap-2" aria-label="Clavier numerique">
                <template x-for="n in 9" :key="'k'+n">
                    <button type="button" class="btn btn-outline-secondary sudoku-key"
                            @click="inputValue(n)"
                            :disabled="completed || paused"
                            :aria-label="'Saisir le chiffre ' + n"
                            x-text="n"></button>
                </template>
                <button type="button" class="btn btn-outline-danger sudoku-key"
                        @click="clearCell()" :disabled="completed || paused"
                        aria-label="Effacer la cellule">&times;</button>
            </div>
        </div>

        <aside class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Temps</strong>
                        <span x-text="formatTime(timer)" class="font-monospace fs-5"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Erreurs</strong>
                        <span x-text="errorsCount" class="badge bg-danger"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Indices</strong>
                        <span x-text="hintsUsed" class="badge bg-warning text-dark"></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" @click="verifyComplete()"
                                :disabled="completed || paused">
                            Verifier la grille
                        </button>
                        <button type="button" class="btn btn-info text-white" @click="useHint()"
                                :disabled="completed || paused">
                            Indice
                        </button>
                        <button type="button" class="btn btn-warning" @click="togglePause()"
                                :disabled="completed">
                            <span x-text="paused ? 'Reprendre' : 'Pause'"></span>
                        </button>
                        <button type="button" class="btn btn-outline-primary" @click="saveState()">
                            Sauvegarder
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="askRestart()">
                            Recommencer
                        </button>
                    </div>

                    <div id="sudoku-status" class="mt-3 text-center small" aria-live="polite" x-text="statusMessage"></div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Modal victoire (pas de confirm/alert natif) -->
    <div class="modal fade" id="winModal" tabindex="-1" aria-labelledby="winModalLabel" aria-hidden="true" x-ref="winModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#0B7285;color:white">
                    <h5 class="modal-title" id="winModalLabel">Bravo !</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="lead text-center">Grille resolue en <strong x-text="formatTime(timer)"></strong>.</p>
                    <p class="text-center">
                        Erreurs : <strong x-text="errorsCount"></strong> &middot;
                        Indices : <strong x-text="hintsUsed"></strong>
                    </p>
                    <div class="mb-3">
                        <label for="pseudoInput" class="form-label">Pseudo (pour le classement)</label>
                        <input type="text" id="pseudoInput" class="form-control" maxlength="30"
                               x-model="pseudo" placeholder="Anonyme">
                    </div>
                    <div x-show="resultMessage" class="alert" :class="resultIsSuccess ? 'alert-success' : 'alert-danger'">
                        <span x-text="resultMessage"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Plus tard</button>
                    <button type="button" class="btn btn-primary" @click="submitScore()" :disabled="submitting">
                        <span x-show="!submitting">Soumettre score</span>
                        <span x-show="submitting">Envoi...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal restart (remplace confirm() natif) -->
    <div class="modal fade" id="restartModal" tabindex="-1" aria-hidden="true" x-ref="restartModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Recommencer ?</h5></div>
                <div class="modal-body">
                    <p>Toute progression sera perdue. Confirmer ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" @click="restartGrid()">Recommencer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --sudoku-cell: 44px;
        --memora-teal: #0B7285;
        --memora-orange: #C2410C;
    }
    .sudoku-grid {
        display: grid;
        grid-template-columns: repeat(9, var(--sudoku-cell));
        grid-template-rows: repeat(9, var(--sudoku-cell));
        background: #1f2937;
        border: 3px solid #1f2937;
        gap: 1px;
        max-width: calc(9 * var(--sudoku-cell) + 6px);
        user-select: none;
    }
    .sudoku-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        font-size: 1.25rem;
        cursor: pointer;
        position: relative;
        color: #000000;
    }
    .sudoku-cell.is-given {
        background: #f1f5f9;
        font-weight: 700;
        color: #0f172a;
        cursor: default;
    }
    .sudoku-cell.is-selected {
        outline: 3px solid var(--memora-orange);
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
    /* Bordures epaisses 3x3 */
    .sudoku-grid > :nth-child(3n):not(:nth-child(9n)) { border-right: 2px solid #1f2937; }
    .sudoku-grid > :nth-child(n+19):nth-child(-n+27),
    .sudoku-grid > :nth-child(n+46):nth-child(-n+54) { border-bottom: 2px solid #1f2937; }

    .sudoku-cell:focus { outline: 3px solid var(--memora-orange); outline-offset: -3px; z-index: 3; }

    .sudoku-key {
        min-width: 44px;
        min-height: 44px;
        font-size: 1.1rem;
        font-weight: 600;
    }
    @@media (max-width: 576px) {
        :root { --sudoku-cell: 36px; }
        .sudoku-key { min-width: 40px; min-height: 40px; }
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
            this.restorePreset();
            this.startTimer();
            this.startAutosave();
        },

        switchTo(idx) {
            this.saveStateSilent();
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
            this.restorePreset();
        },

        cellClass(r, c) {
            const classes = [];
            if (this.originalGrid[r][c] !== 0) classes.push('is-given');
            if (this.selectedCell.row === r && this.selectedCell.col === c) classes.push('is-selected');
            if (this.errorsCells.has(`${r}-${c}`)) classes.push('is-error');
            const sel = this.selectedCell;
            if (sel.row >= 0 && (sel.row === r || sel.col === c ||
                (Math.floor(sel.row/3) === Math.floor(r/3) && Math.floor(sel.col/3) === Math.floor(c/3)))) {
                if (!(sel.row === r && sel.col === c)) classes.push('peer-highlight');
            }
            return classes.join(' ');
        },

        cellAriaLabel(r, c) {
            const v = this.grid[r][c];
            return `Ligne ${r+1}, colonne ${c+1}, ${v === 0 ? 'vide' : 'valeur ' + v}`;
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
                this.errorsCells.add(`${row}-${col}`);
                this.statusMessage = `Conflit detecte ligne ${row+1}, colonne ${col+1}.`;
                setTimeout(() => { this.statusMessage = ''; }, 2500);
            } else {
                this.errorsCells.delete(`${row}-${col}`);
            }
        },

        clearCell() {
            if (this.completed || this.paused) return;
            const { row, col } = this.selectedCell;
            if (row === -1 || this.originalGrid[row][col] !== 0) return;
            this.grid[row][col] = 0;
            this.errorsCells.delete(`${row}-${col}`);
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
            // Premiere cellule vide, insere une valeur valide naive (server donnerait la solution mais coute revele)
            for (let r = 0; r < 9; r++) {
                for (let c = 0; c < 9; c++) {
                    if (this.originalGrid[r][c] === 0 && this.grid[r][c] === 0) {
                        for (let n = 1; n <= 9; n++) {
                            if (this.checkLocal(r, c, n)) {
                                this.grid[r][c] = n;
                                this.hintsUsed++;
                                this.selectCell(r, c);
                                this.statusMessage = `Indice : ligne ${r+1}, colonne ${c+1} = ${n}.`;
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
            this.timerId = setInterval(() => { if (!this.paused && !this.completed) this.timer++; }, 1000);
        },

        startAutosave() {
            clearInterval(this.autoSaveId);
            this.autoSaveId = setInterval(() => { if (!this.completed) this.saveStateSilent(); }, 30000);
        },

        async saveState() {
            const ok = await this.saveStateSilent();
            this.statusMessage = ok ? 'Partie sauvegardee.' : 'Echec sauvegarde.';
            setTimeout(() => { this.statusMessage = ''; }, 2500);
        },

        async saveStateSilent() {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('/api/sudoku/preset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        puzzle_id: this.currentPuzzleId,
                        pseudo: this.pseudo || ('anon-' + Math.random().toString(36).slice(2, 8)),
                        grid_state: this.grid,
                        time_elapsed: this.timer,
                        hints_used: this.hintsUsed,
                        errors_count: this.errorsCount,
                    }),
                });
                return res.ok;
            } catch (e) { return false; }
        },

        async restorePreset() {
            try {
                const params = this.pseudo ? `?pseudo=${encodeURIComponent(this.pseudo)}` : '';
                const res = await fetch(`/api/sudoku/preset/${this.currentPuzzleId}${params}`);
                if (!res.ok) return;
                const data = await res.json();
                if (data.grid_state) {
                    this.grid = data.grid_state;
                    this.timer = data.time_elapsed || 0;
                    this.hintsUsed = data.hints_used || 0;
                    this.errorsCount = data.errors_count || 0;
                    this.statusMessage = 'Partie restauree.';
                    setTimeout(() => { this.statusMessage = ''; }, 2500);
                }
            } catch (e) { /* silence */ }
        },

        askRestart() { this.restartModalEl?.show(); },

        restartGrid() {
            this.grid = JSON.parse(JSON.stringify(this.originalGrid));
            this.timer = 0;
            this.hintsUsed = 0;
            this.errorsCount = 0;
            this.errorsCells.clear();
            this.completed = false;
            this.paused = false;
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
                    this.statusMessage = `Erreur ligne ${r+1}, colonne ${c+1}.`;
                    setTimeout(() => { this.statusMessage = ''; }, 2500);
                    return;
                }
            }
            this.completed = true;
            clearInterval(this.timerId);
            this.winModalEl?.show();
        },

        async submitScore() {
            this.submitting = true;
            this.resultMessage = '';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                localStorage.setItem('sudoku_pseudo', this.pseudo);
                const res = await fetch('/api/sudoku/score', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'Accept': 'application/json' },
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
                    this.resultMessage = `Score ${data.score} ! Rang du jour : ${data.rank_today}.` + (data.is_published ? '' : ' (non classe : temps trop court)');
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
            return `${m.toString().padStart(2,'0')}:${x.toString().padStart(2,'0')}`;
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
