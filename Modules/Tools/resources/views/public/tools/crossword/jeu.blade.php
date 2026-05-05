@extends('fronttheme::layouts.master')

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@section('og_type', 'article')

@section('content')
@php $playUrl = $preset->share_url; @endphp

<style>
/* 2026-05-05 #122 v3 : grille TOUJOURS 100% largeur container + ratio var(--cols)/var(--rows) préservé OBLIGATOIRE. Cells carrées garanties. */
.cw-grid-wrap{--cols:10;--rows:10;width:100%;max-width:100%}
.cw-grid-wrap .table-responsive{width:100%;aspect-ratio:calc(var(--cols)) / calc(var(--rows));display:block !important;margin:1rem 0;overflow:visible}
.crossword-grid{table-layout:fixed;border-collapse:collapse;background:#fff;width:100%;height:100%}
.crossword-grid td{padding:0;text-align:center;vertical-align:middle;box-sizing:border-box;width:auto;height:auto}
.cell-active{background-color:#ffffff;border:2px solid #1A1D23;position:relative}
.cell-inactive{background-color:#1A1D23;border:2px solid #1A1D23}
.cw-grid-wrap .crossword-grid td{min-width:22px;min-height:22px}
.cell-wrapper{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.cell-active input{width:100%;height:100%;min-width:22px;min-height:22px;border:none;text-align:center;font-size:clamp(0.9rem, 2vw, 1.4rem);font-weight:700;text-transform:uppercase;background:transparent;color:#1A1D23;padding:0;display:block}
.cell-active input:focus-visible{outline:3px solid #053d4a;outline-offset:-3px;background:#fff7ed}
.cell-correct{background-color:#bbf7d0!important;color:#064e3b!important}
.cell-wrong{background-color:#fecaca!important;color:#5b0c0c!important}
.cell-pulse{animation:cellPulse 0.6s ease-out 2}
@keyframes cellPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08);box-shadow:0 0 0 4px rgba(220,38,38,0.4)}}
.cw-toggle-autocheck{display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .75rem;background:#fff;border:1px solid #cbd5e1;border-radius:6px;cursor:pointer;font-weight:500;font-size:.875rem;min-height:36px}
.cw-toggle-autocheck input{margin:0}
.cw-action-secondary{padding:.5rem .8rem;background:#fef3c7;border:1px solid #fbbf24;color:#78350f;border-radius:6px;cursor:pointer;font-weight:500;min-height:40px;font-size:.875rem;display:inline-flex;align-items:center;gap:.4rem}
.cw-action-secondary:hover,.cw-action-secondary:focus-visible{background:#fde68a;outline:2px solid #d97706;outline-offset:2px}
.cw-action-danger{padding:.5rem .8rem;background:#fee2e2;border:1px solid #f87171;color:#7f1d1d;border-radius:6px;cursor:pointer;font-weight:500;min-height:40px;font-size:.875rem;display:inline-flex;align-items:center;gap:.4rem}
.cw-action-danger:hover,.cw-action-danger:focus-visible{background:#fecaca;outline:2px solid #dc2626;outline-offset:2px}
.cell-active .number{position:absolute;top:1px;left:2px;font-size:.6rem;font-weight:700;color:#053d4a;line-height:1;z-index:1;pointer-events:none}
.timer-display{font-size:1.75rem;font-weight:800;font-variant-numeric:tabular-nums;color:#053d4a}
.cw-status-text{color:#1A1D23;font-weight:600}
.cw-status-text .num{color:#053d4a;font-weight:800}
.completion-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem}
.completion-modal-card{background:#fff;padding:2rem;border-radius:16px;max-width:480px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.completion-modal-card .completion-msg{color:#1A1D23;font-weight:500}
.clue-link{background:none;border:none;padding:.6rem .75rem;text-align:left;cursor:pointer;color:#1A1D23;min-height:44px;display:flex;align-items:flex-start;gap:.6rem;width:100%;border-radius:6px;font-weight:500}
.clue-link:hover,.clue-link:focus-visible{background-color:#e0f2f1;outline:2px solid #053d4a;outline-offset:2px}
.clue-number{font-weight:800;color:#053d4a;flex-shrink:0;min-width:1.6rem}
.clue-text{flex:1;line-height:1.4}
.cw-status-bar{display:flex;flex-wrap:wrap;align-items:center;gap:1.25rem;padding:1.25rem 1.5rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;margin-bottom:1.5rem}
.cw-action-btn{min-height:44px;min-width:44px;font-weight:600}
.cw-cta-create{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;background:#053d4a;color:#fff!important;border-radius:8px;text-decoration:none!important;font-weight:600;min-height:44px;min-width:44px}
.cw-cta-create:hover,.cw-cta-create:focus-visible{background:#032327;outline:3px solid #1A1D23;outline-offset:2px}
.cw-subtitle{color:#1A1D23;font-weight:500}
.cw-loader{padding:3rem;text-align:center;color:#1A1D23}
.cw-clues-section h2{color:#053d4a}
@media print{.no-print{display:none!important}.cw-status-bar{display:none}}
/* 2026-05-05 #100 : la formule clamp() gère déjà le mobile - règle simplifiée pour tablette éventuelle */
</style>

<section class="page-section py-5">
  <div class="container">
    <div class="card shadow-sm">
      <div class="card-body p-4 p-md-5" x-data="crosswordPlayer()" x-init="init()" @keydown.escape.window="completed && (completed = false)">

        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2 no-print">
          <div>
            <h1 class="h3 mb-1" style="color:#1A1D23">{{ $preset->name }}</h1>
            <p class="cw-subtitle mb-0 small">{{ __('Mode joueur — résolvez la grille en ligne') }}</p>
          </div>
          {{-- Bouton 'Imprimer' retire S79 #43 - le createur peut telecharger le PDF (vierge ou corrige) depuis /outils/mots-croises menu Plus d'options. --}}
        </div>

        <template x-if="loading">
          <div class="cw-loader">
            <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
            <p>{{ __('Chargement de la grille...') }}</p>
          </div>
        </template>

        <template x-if="loadError">
          <div class="alert alert-warning" role="alert">
            <strong>{{ __('Impossible de charger la grille.') }}</strong>
            <span x-text="loadError"></span>
            <a href="{{ url('/outils/mots-croises') }}" class="alert-link">{{ __('Retour aux mots croisés') }}</a>
          </div>
        </template>

        <template x-if="grid && !loading">
          <div>
            <div class="cw-status-bar no-print" role="region" aria-label="{{ __('Statut de la partie') }}">
              <div class="d-flex align-items-center gap-2">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="timer-display" aria-live="polite" :aria-label="'{{ __('Temps écoulé') }} ' + formatTime(timer)" x-text="formatTime(timer)"></span>
              </div>
              <div class="d-flex align-items-center gap-2 cw-status-text flex-wrap" aria-live="polite">
                <span><strong class="num" x-text="correctCount"></strong> / <strong class="num" x-text="totalActiveCells"></strong> {{ __('cases') }}</span>
                <span aria-hidden="true" style="color:#1A1D23">·</span>
                <span><strong class="num" x-text="hintsUsed"></strong> {{ __('indice(s)') }}</span>
                <span aria-hidden="true" style="color:#1A1D23" x-show="errorsCount > 0">·</span>
                <span x-show="errorsCount > 0" :title="'{{ __('Erreurs cumulées sur la partie') }}'"><strong class="num" style="color:#b91c1c" x-text="errorsCount"></strong> {{ __('erreur(s)') }}</span>
              </div>
              <label class="cw-toggle-autocheck" :title="autoCheck ? '{{ __('Validation immédiate activée — case rouge si erreur') }}' : '{{ __('Validation manuelle — clic Vérifier requis') }}'">
                <input type="checkbox" x-model="autoCheck" aria-label="{{ __('Activer la validation automatique des cases') }}">
                <span>{{ __('Auto-check') }}</span>
              </label>
              <div class="d-flex gap-2 ms-auto flex-wrap">
                <button type="button" class="cw-action-secondary" @click="checkGrid()" :disabled="completed" aria-label="{{ __('Vérifier la grille — anime les cases erronées') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>{{ __('Vérifier') }}</span>
                </button>
                <button type="button" class="ct-btn ct-btn-outline cw-action-btn d-inline-flex align-items-center gap-2" @click="useHint()" :disabled="completed || emptyCellCount === 0" :aria-label="'{{ __('Révéler une lettre') }} (' + hintsUsed + ' utilisés)'">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-7 7c0 3 2 5 2 7h10c0-2 2-4 2-7a7 7 0 0 0-7-7z"/></svg>
                  <span>{{ __('Indice') }}</span>
                </button>
                <button type="button" class="ct-btn ct-btn-outline cw-action-btn d-inline-flex align-items-center gap-2" @click="revealCurrentWord()" :disabled="completed || !currentWord" :aria-label="'{{ __('Révéler le mot complet en cours') }}'">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                  <span>{{ __('Révéler le mot') }}</span>
                </button>
                <button type="button" class="cw-action-danger" @click="confirmReveal()" :disabled="completed" aria-label="{{ __('Abandonner et révéler la solution complète') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
                  <span>{{ __('Abandonner') }}</span>
                </button>
                <button type="button" class="ct-btn ct-btn-outline cw-action-btn d-inline-flex align-items-center gap-2" @click="resetGame()" aria-label="{{ __('Effacer toutes les réponses et recommencer') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                  <span>{{ __('Recommencer') }}</span>
                </button>
              </div>
            </div>

            <div class="row">
              {{-- 2026-05-05 #125 : grille col-12 pleine largeur + indices 50/50 sous (pas latéral) --}}
              <div class="col-12 mb-4 cw-grid-wrap" :style="`--cols: ${grid.cols}; --rows: ${grid.rows};`">
                <div class="table-responsive d-flex justify-content-center">
                  <table class="crossword-grid" :aria-label="'{{ __('Grille de mots croisés') }} ' + grid.rows + 'x' + grid.cols">
                    <tbody>
                      <template x-for="(row, rowIndex) in grid.cells" :key="rowIndex">
                        <tr>
                          <template x-for="(cell, colIndex) in row" :key="colIndex">
                            <td :class="cell !== null ? 'cell-active' : 'cell-inactive'">
                              <template x-if="cell !== null">
                                <div class="cell-wrapper">
                                  <span class="number" x-show="cell.number" x-text="cell.number"></span>
                                  <input type="text"
                                         maxlength="1"
                                         autocomplete="off"
                                         autocapitalize="characters"
                                         spellcheck="false"
                                         :data-row="rowIndex"
                                         :data-col="colIndex"
                                         :value="userInput[rowIndex+'-'+colIndex] || ''"
                                         @input="setCell(rowIndex, colIndex, $event.target.value)"
                                         @focus="startTimer(); setDirectionFromCell(rowIndex, colIndex)"
                                         @keydown="handleKey($event, rowIndex, colIndex)"
                                         :class="{'cell-correct': isCorrect(rowIndex, colIndex), 'cell-wrong': isWrong(rowIndex, colIndex)}"
                                         :aria-label="'{{ __('Case ligne') }} ' + (rowIndex+1) + ' {{ __('colonne') }} ' + (colIndex+1) + (cell.number ? ', {{ __('numéro') }} ' + cell.number : '') + (isCorrect(rowIndex, colIndex) ? ', {{ __('correcte') }}' : '') + (isWrong(rowIndex, colIndex) ? ', {{ __('incorrecte') }}' : '')">
                                </div>
                              </template>
                            </td>
                          </template>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="col-12 cw-clues-section">
                <div class="row">
                  {{-- 2026-05-05 #123 : 2 colonnes Horizontaux | Verticaux côte à côte sur tablette+ --}}
                  <div class="col-sm-6 mb-4">
                    <h2 class="h6 fw-bold mb-2">
                      <span aria-hidden="true">→</span> {{ __('Horizontaux') }}
                    </h2>
                    <ul class="list-unstyled mb-0">
                      <template x-for="word in horizontalWords" :key="word.number + 'h'">
                        <li>
                          <button type="button" class="clue-link" @click="focusWord(word)" :aria-label="'{{ __('Indice horizontal') }} ' + word.number + ': ' + word.clue">
                            <span class="clue-number" x-text="word.number + '.'"></span>
                            <span class="clue-text" x-text="word.clue"></span>
                            <template x-if="isWordComplete(word)">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0CA678" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                            </template>
                          </button>
                        </li>
                      </template>
                    </ul>
                  </div>
                  <div class="col-sm-6">
                    <h2 class="h6 fw-bold mb-2">
                      <span aria-hidden="true">↓</span> {{ __('Verticaux') }}
                    </h2>
                    <ul class="list-unstyled mb-0">
                      <template x-for="word in verticalWords" :key="word.number + 'v'">
                        <li>
                          <button type="button" class="clue-link" @click="focusWord(word)" :aria-label="'{{ __('Indice vertical') }} ' + word.number + ': ' + word.clue">
                            <span class="clue-number" x-text="word.number + '.'"></span>
                            <span class="clue-text" x-text="word.clue"></span>
                            <template x-if="isWordComplete(word)">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0CA678" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                            </template>
                          </button>
                        </li>
                      </template>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <div class="completion-modal-backdrop"
             x-show="completed"
             x-cloak
             role="dialog"
             aria-modal="true"
             aria-labelledby="completion-title"
             aria-live="assertive"
             @click.self="completed = false">
          <div class="completion-modal-card">
            <div class="d-flex align-items-center gap-2 mb-3">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0CA678" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
              <h2 id="completion-title" class="h4 mb-0">{{ __('Grille complétée !') }}</h2>
            </div>
            <p class="mb-1 completion-msg">{{ __('Temps') }} : <strong style="color:#053d4a" x-text="formatTime(finalTime)"></strong></p>
            <p class="mb-1 completion-msg">{{ __('Indices utilisés') }} : <strong style="color:#053d4a" x-text="hintsUsed"></strong></p>
            <p class="mb-1 completion-msg" x-show="errorsCount > 0">{{ __('Erreurs cumulées') }} : <strong style="color:#b91c1c" x-text="errorsCount"></strong></p>
            <p class="mb-3 completion-msg" style="font-style:italic" x-text="completionMessage"></p>
            <div class="d-flex flex-column flex-sm-row gap-2 flex-wrap">
              <button type="button" class="ct-btn ct-btn-primary cw-action-btn" @click="resetGame(); completed = false" aria-label="{{ __('Recommencer la partie') }}">
                {{ __('Recommencer') }}
              </button>
              <button type="button" class="ct-btn ct-btn-outline cw-action-btn d-inline-flex align-items-center gap-2" @click="copyWordleShare()" aria-label="{{ __('Copier mon résultat au format partageable') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <span x-show="!wordleShareCopied">{{ __('Partager mon résultat') }}</span>
                <span x-show="wordleShareCopied" x-cloak>{{ __('Copié ✓') }}</span>
              </button>
              <button type="button" class="ct-btn ct-btn-outline cw-action-btn" @click="copyShareUrl()" aria-label="{{ __('Copier le lien de la grille') }}">
                <span x-show="!copyDone">{{ __('Lien grille') }}</span>
                <span x-show="copyDone" x-cloak>{{ __('Lien copié !') }}</span>
              </button>
              <button type="button" class="ct-btn ct-btn-outline cw-action-btn ms-sm-auto" @click="completed = false" aria-label="{{ __('Fermer') }}">
                {{ __('Fermer') }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="text-center mt-4 no-print">
      <a href="{{ url('/outils/mots-croises') }}" class="cw-cta-create">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>{{ __('Créer ma propre grille de mots croisés') }}</span>
      </a>
    </div>

  </div>
</section>

{{-- C1 : canvas-confetti CDN pour animation completion (5 KB, 0$ coût, async no-block) --}}
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" async></script>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('crosswordPlayer', () => ({
    presetId: @json($preset->public_id),
    configText: @json($preset->config_text),
    draftKey: 'crossword-game-' + @json($preset->public_id),
    grid: null,
    words: [],
    horizontalWords: [],
    verticalWords: [],
    userInput: {},
    timer: 0,
    timerInterval: null,
    startTimeStamp: null,
    hintsUsed: 0,
    completed: false,
    finalTime: 0,
    loading: true,
    loadError: '',
    saveTimer: null,
    copyDone: false,
    completionMessage: '',

    // 2026-05-05 Tier S+A : auto-check toggle, erreurs, mot courant, revealed cells (sans erreur counter), Wordle share
    autoCheck: localStorage.getItem('cw_autocheck') !== '0',
    errorsCount: 0,
    revealedCells: {},      // {key: true} pour cases revelees via hint/word/reveal-puzzle
    currentWord: null,
    currentDirection: 'horizontal', // 2026-05-05 #95 : respecter orientation initiale (pas de swap auto h↔v)
    abandoned: false,
    wordleShareCopied: false,

    init() {
      this.loadDraft();
      this.parseAndGenerate();
      // 2026-05-05 : persister autoCheck dans localStorage
      this.$watch('autoCheck', () => this.persistAutoCheck());
    },

    loadDraft() {
      try {
        const raw = localStorage.getItem(this.draftKey);
        if (!raw) return;
        const parsed = JSON.parse(raw);
        this.userInput = parsed.cells || {};
        this.timer = parsed.elapsed || 0;
        this.hintsUsed = parsed.hintsUsed || 0;
      } catch (e) {}
    },

    saveDraft() {
      try {
        localStorage.setItem(this.draftKey, JSON.stringify({
          cells: this.userInput,
          elapsed: this.timer,
          hintsUsed: this.hintsUsed
        }));
      } catch (e) {}
    },

    debounceSave() {
      if (this.saveTimer) clearTimeout(this.saveTimer);
      this.saveTimer = setTimeout(() => this.saveDraft(), 1000);
    },

    parseAndGenerate() {
      const cfg = (this.configText || '').trim();
      let pairs = [];
      let preGrid = null;
      let preWords = null;

      // 2026-05-05 #119 : détecter format JSON moderne S80+ {pairs:[],grid,words,unplaced}
      if (cfg.startsWith('{')) {
        try {
          const data = JSON.parse(cfg);
          if (data && Array.isArray(data.pairs)) {
            pairs = data.pairs.filter(p => p && p.clue && p.answer).map(p => ({ clue: String(p.clue), answer: String(p.answer) }));
          }
          // Si grille déjà générée stockée, l'utiliser directement (évite appel API)
          if (data && data.grid && Array.isArray(data.words)) {
            preGrid = data.grid;
            preWords = data.words;
          }
        } catch (e) { /* fallback sur format legacy */ }
      }

      // Fallback format legacy "indice / mot" ligne par ligne
      if (pairs.length === 0) {
        const lines = cfg.split('\n').map(l => l.trim()).filter(Boolean);
        for (const line of lines) {
          const idx = line.indexOf(' / ');
          if (idx === -1) continue;
          const clue = line.substring(0, idx).trim();
          const answer = line.substring(idx + 3).trim();
          if (clue && answer) pairs.push({ clue, answer });
        }
      }

      if (pairs.length < 2) {
        this.loadError = @json(__('Cette grille ne contient pas assez de paires valides.'));
        this.loading = false;
        return;
      }

      // 2026-05-05 #119 : si grille pré-calculée disponible, l'utiliser directement (skip API)
      if (preGrid && preWords) {
        this.grid = preGrid;
        this.words = preWords;
        this.horizontalWords = this.words.filter(w => w.orientation === 'horizontal');
        this.verticalWords = this.words.filter(w => w.orientation === 'vertical');
        this.cleanUserInput();
        this.loading = false;
        this.$nextTick(() => this.checkCompletion());
        return;
      }
      const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      fetch(@json(route('tools.crossword.generate')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ pairs })
      })
      .then(r => r.json())
      .then(data => {
        if (!data || !data.success || !data.grid) {
          this.loadError = @json(__('Échec de la génération de la grille.'));
          return;
        }
        this.grid = data.grid;
        this.words = data.words || [];
        this.horizontalWords = this.words.filter(w => w.orientation === 'horizontal');
        this.verticalWords = this.words.filter(w => w.orientation === 'vertical');
        this.cleanUserInput();
        this.$nextTick(() => this.checkCompletion());
      })
      .catch(err => {
        this.loadError = @json(__('Erreur réseau lors du chargement de la grille.'));
      })
      .finally(() => {
        this.loading = false;
      });
    },

    cleanUserInput() {
      const clean = {};
      if (!this.grid || !this.grid.cells) return;
      for (const key in this.userInput) {
        const [r, c] = key.split('-').map(Number);
        if (this.grid.cells[r] && this.grid.cells[r][c]) {
          clean[key] = this.userInput[key];
        }
      }
      this.userInput = clean;
    },

    get totalActiveCells() {
      if (!this.grid || !this.grid.cells) return 0;
      let n = 0;
      for (const row of this.grid.cells) for (const cell of row) if (cell !== null) n++;
      return n;
    },

    get correctCount() {
      if (!this.grid || !this.grid.cells) return 0;
      let n = 0;
      for (let r = 0; r < this.grid.cells.length; r++) {
        for (let c = 0; c < this.grid.cells[r].length; c++) {
          if (this.grid.cells[r][c] && this.userInput[r+'-'+c] === this.grid.cells[r][c].letter) n++;
        }
      }
      return n;
    },

    get emptyCellCount() {
      if (!this.grid || !this.grid.cells) return 0;
      let n = 0;
      for (let r = 0; r < this.grid.cells.length; r++) {
        for (let c = 0; c < this.grid.cells[r].length; c++) {
          if (this.grid.cells[r][c] && !this.userInput[r+'-'+c]) n++;
        }
      }
      return n;
    },

    setCell(row, col, value) {
      const key = row + '-' + col;
      const v = (value || '').toUpperCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^A-Z]/g, '').slice(0, 1);
      // B4 : compter les erreurs au moment de la saisie (uniquement nouvelles entrées différentes de la lettre attendue)
      const cell = this.grid && this.grid.cells[row] && this.grid.cells[row][col];
      const previous = this.userInput[key];
      if (cell && v && v !== previous && v !== cell.letter && this.autoCheck) {
        this.errorsCount++;
      }
      this.userInput = { ...this.userInput, [key]: v };
      this.debounceSave();
      this.$nextTick(() => this.checkCompletion());
      if (v) this.advanceCursor(row, col);
    },

    advanceCursor(row, col) {
      // 2026-05-05 #95 : avance dans la direction courante UNIQUEMENT — pas de swap auto h↔v
      if (this.currentDirection === 'vertical') {
        const below = document.querySelector('input[data-row="'+(row+1)+'"][data-col="'+col+'"]');
        if (below) below.focus();
        return;
      }
      const next = document.querySelector('input[data-row="'+row+'"][data-col="'+(col+1)+'"]');
      if (next) next.focus();
    },

    handleKey(e, row, col) {
      if (e.key === 'Backspace' && !e.target.value) {
        // 2026-05-05 #95 : recule dans la direction courante UNIQUEMENT
        if (this.currentDirection === 'vertical') {
          const above = document.querySelector('input[data-row="'+(row-1)+'"][data-col="'+col+'"]');
          if (above) above.focus();
          return;
        }
        const prev = document.querySelector('input[data-row="'+row+'"][data-col="'+(col-1)+'"]');
        if (prev) prev.focus();
      } else if (e.key === 'ArrowRight') {
        this.currentDirection = 'horizontal';
        const n = document.querySelector('input[data-row="'+row+'"][data-col="'+(col+1)+'"]');
        if (n) { e.preventDefault(); n.focus(); }
      } else if (e.key === 'ArrowLeft') {
        this.currentDirection = 'horizontal';
        const n = document.querySelector('input[data-row="'+row+'"][data-col="'+(col-1)+'"]');
        if (n) { e.preventDefault(); n.focus(); }
      } else if (e.key === 'ArrowDown') {
        this.currentDirection = 'vertical';
        const n = document.querySelector('input[data-row="'+(row+1)+'"][data-col="'+col+'"]');
        if (n) { e.preventDefault(); n.focus(); }
      } else if (e.key === 'ArrowUp') {
        this.currentDirection = 'vertical';
        const n = document.querySelector('input[data-row="'+(row-1)+'"][data-col="'+col+'"]');
        if (n) { e.preventDefault(); n.focus(); }
      }
    },

    isCorrect(row, col) {
      if (!this.grid || !this.grid.cells) return false;
      const cell = this.grid.cells[row] && this.grid.cells[row][col];
      if (!cell) return false;
      const v = this.userInput[row+'-'+col];
      return !!v && v === cell.letter;
    },

    isWrong(row, col) {
      if (!this.grid || !this.grid.cells) return false;
      // Si auto-check désactivé, ne rien afficher en rouge (mode honor system)
      if (!this.autoCheck) return false;
      const cell = this.grid.cells[row] && this.grid.cells[row][col];
      if (!cell) return false;
      const v = this.userInput[row+'-'+col];
      return !!v && v !== cell.letter;
    },

    isWordComplete(word) {
      for (let i = 0; i < word.length; i++) {
        const r = word.orientation === 'horizontal' ? word.row : word.row + i;
        const c = word.orientation === 'horizontal' ? word.col + i : word.col;
        if (this.userInput[r+'-'+c] !== word.answer[i]) return false;
      }
      return true;
    },

    startTimer() {
      if (this.timerInterval || this.completed) return;
      this.startTimeStamp = Date.now() - (this.timer * 1000);
      this.timerInterval = setInterval(() => {
        this.timer = Math.floor((Date.now() - this.startTimeStamp) / 1000);
        this.debounceSave();
      }, 1000);
    },

    stopTimer() {
      if (this.timerInterval) {
        clearInterval(this.timerInterval);
        this.timerInterval = null;
      }
    },

    formatTime(s) {
      const m = Math.floor(s / 60).toString().padStart(2, '0');
      const r = (s % 60).toString().padStart(2, '0');
      return m + ':' + r;
    },

    useHint() {
      if (!this.grid || !this.grid.cells) return;
      const empties = [];
      for (let r = 0; r < this.grid.cells.length; r++) {
        for (let c = 0; c < this.grid.cells[r].length; c++) {
          if (this.grid.cells[r][c] && !this.userInput[r+'-'+c]) empties.push([r, c]);
        }
      }
      if (empties.length === 0) return;
      const [r, c] = empties[Math.floor(Math.random() * empties.length)];
      const key = r + '-' + c;
      this.userInput = { ...this.userInput, [key]: this.grid.cells[r][c].letter };
      this.hintsUsed++;
      this.debounceSave();
      this.$nextTick(() => this.checkCompletion());
    },

    checkCompletion() {
      if (this.completed || !this.grid || !this.grid.cells) return;
      if (this.totalActiveCells === 0) return;
      if (this.correctCount === this.totalActiveCells) {
        this.completed = true;
        this.finalTime = this.timer;
        this.stopTimer();
        const noHints = this.hintsUsed === 0 && !this.abandoned;
        if (this.abandoned) {
          this.completionMessage = @json(__('Solution révélée. Recommencez pour vous mesurer à la grille !'));
        } else if (noHints && this.finalTime < 180) {
          this.completionMessage = @json(__('Excellent ! Sans aucune aide et en moins de 3 minutes.'));
        } else if (noHints) {
          this.completionMessage = @json(__('Bravo, sans aucune aide.'));
        } else if (this.hintsUsed <= 2) {
          this.completionMessage = @json(__('Bien joué, peu d\'indices utilisés.'));
        } else {
          this.completionMessage = @json(__('Grille complétée. Réessayez avec moins d\'indices !'));
        }
        this.saveDraft();
        // C1 : confetti animation (skip si abandon)
        if (!this.abandoned) this.triggerConfetti();
      }
    },

    // 2026-05-05 #124 : auto-détecter direction quand user clique directement une case (sans passer par indice)
    setDirectionFromCell(r, c) {
      const candidates = (this.words || []).filter(w => {
        if (w.orientation === 'horizontal') return w.row === r && c >= w.col && c < w.col + w.length;
        return w.col === c && r >= w.row && r < w.row + w.length;
      });
      if (candidates.length === 1) {
        this.currentDirection = candidates[0].orientation;
        this.currentWord = candidates[0];
      } else if (candidates.length > 1) {
        // Intersection : si direction actuelle valide pour cette case, garder ; sinon prendre 1er
        const validCurrent = candidates.find(w => w.orientation === this.currentDirection);
        if (!validCurrent) {
          this.currentDirection = candidates[0].orientation;
          this.currentWord = candidates[0];
        } else {
          this.currentWord = validCurrent;
        }
      }
    },

    focusWord(word) {
      // B2 : track current word pour révéler-mot
      this.currentWord = word;
      // 2026-05-05 #95 : verrouille la direction sur l'orientation du mot choisi
      this.currentDirection = word.orientation;
      const r = word.row;
      const c = word.col;
      const input = document.querySelector('input[data-row="'+r+'"][data-col="'+c+'"]');
      if (input) {
        input.focus();
        input.select();
        this.startTimer();
      }
    },

    resetGame() {
      this.userInput = {};
      this.timer = 0;
      this.hintsUsed = 0;
      this.finalTime = 0;
      this.errorsCount = 0;
      this.revealedCells = {};
      this.abandoned = false;
      this.stopTimer();
      this.startTimeStamp = null;
      this.saveDraft();
    },

    copyShareUrl() {
      const url = @json($playUrl);
      const fallback = () => {
        const ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).catch(fallback);
      } else {
        fallback();
      }
      this.copyDone = true;
      setTimeout(() => { this.copyDone = false; }, 2500);
    },

    // 2026-05-05 Tier S+A : nouvelles fonctions UX
    persistAutoCheck() {
      try { localStorage.setItem('cw_autocheck', this.autoCheck ? '1' : '0'); } catch (e) {}
    },

    // A3 : vérifier la grille — flash pulse rouge sur cases erronées (force auto-check temporairement)
    checkGrid() {
      if (!this.grid || !this.grid.cells) return;
      const wrongCells = [];
      for (let r = 0; r < this.grid.cells.length; r++) {
        for (let c = 0; c < this.grid.cells[r].length; c++) {
          const cell = this.grid.cells[r][c];
          if (!cell) continue;
          const v = this.userInput[r+'-'+c];
          if (v && v !== cell.letter) wrongCells.push({r, c});
        }
      }
      if (wrongCells.length === 0) {
        const empty = this.emptyCellCount;
        if (empty === 0) {
          this.checkCompletion();
        } else {
          alert(@json(__('Aucune erreur détectée. Continue, il reste des cases vides.')) + ' (' + empty + ' ' + @json(__('cases vides')) + ')');
        }
        return;
      }
      // Animer les cases wrong
      wrongCells.forEach(({r, c}) => {
        const inp = document.querySelector('input[data-row="'+r+'"][data-col="'+c+'"]');
        if (inp) {
          const wrap = inp.closest('.cell-active');
          if (wrap) {
            wrap.classList.remove('cell-pulse');
            void wrap.offsetWidth; // force reflow
            wrap.classList.add('cell-pulse');
            setTimeout(() => wrap.classList.remove('cell-pulse'), 1600);
          }
        }
      });
    },

    // B2 : révéler le mot complet en cours
    revealCurrentWord() {
      if (!this.currentWord || this.completed) return;
      const w = this.currentWord;
      const dr = w.orientation === 'vertical' ? 1 : 0;
      const dc = w.orientation === 'horizontal' ? 1 : 0;
      let revealed = 0;
      for (let i = 0; i < w.answer.length; i++) {
        const r = w.row + dr * i;
        const c = w.col + dc * i;
        const key = r + '-' + c;
        if (this.userInput[key] !== w.answer[i]) {
          this.userInput[key] = w.answer[i];
          this.revealedCells[key] = true;
          revealed++;
        }
      }
      this.userInput = {...this.userInput};
      this.hintsUsed += revealed;
      this.debounceSave();
      this.$nextTick(() => this.checkCompletion());
    },

    // B3 : abandonner / révéler la solution complète
    confirmReveal() {
      if (this.completed) return;
      if (!confirm(@json(__('Révéler la solution complète et terminer la partie ?')))) return;
      if (!this.grid || !this.grid.cells) return;
      for (let r = 0; r < this.grid.cells.length; r++) {
        for (let c = 0; c < this.grid.cells[r].length; c++) {
          const cell = this.grid.cells[r][c];
          if (!cell) continue;
          const key = r + '-' + c;
          if (this.userInput[key] !== cell.letter) {
            this.userInput[key] = cell.letter;
            this.revealedCells[key] = true;
          }
        }
      }
      this.userInput = {...this.userInput};
      this.abandoned = true;
      this.$nextTick(() => this.checkCompletion());
    },

    // B4 + watcher input : compter erreurs cumulatives au moment de la saisie
    onCellInput(r, c, value) {
      const cell = this.grid && this.grid.cells[r] && this.grid.cells[r][c];
      if (!cell || !value) return;
      if (this.autoCheck && value.toUpperCase() !== cell.letter) {
        this.errorsCount++;
      }
    },

    // C1 : confetti animation au completed
    triggerConfetti() {
      if (typeof window.confetti !== 'function') return;
      const duration = 2500;
      const end = Date.now() + duration;
      (function frame() {
        window.confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#0F766E', '#FFF7ED', '#C2410C'] });
        window.confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#0F766E', '#FFF7ED', '#C2410C'] });
        if (Date.now() < end) requestAnimationFrame(frame);
      })();
    },

    // C2 : texte partage Wordle-style
    wordleShareText() {
      const t = this.formatTime(this.finalTime || this.timer);
      const total = this.totalActiveCells;
      const hints = this.hintsUsed;
      const errors = this.errorsCount;
      const url = @json($playUrl);
      const grid = [];
      // Build mini emoji grid : ⬜ pour case correcte, 🟨 pour case revealed (hint), 🟧 pour erreur cumulée (subtil)
      const correctNoReveal = total - Object.keys(this.revealedCells).length;
      const blocks = [];
      for (let i = 0; i < correctNoReveal; i++) blocks.push('🟩');
      for (let i = 0; i < Object.keys(this.revealedCells).length; i++) blocks.push('🟨');
      // Wrap each line at 10 blocks
      const lines = [];
      for (let i = 0; i < blocks.length; i += 10) lines.push(blocks.slice(i, i+10).join(''));
      const gridStr = lines.join('\n');

      const titleName = @json($preset->name);
      let text = '🧩 Mots-croisés — ' + titleName + '\n';
      text += '⏱ ' + t + ' · 💡 ' + hints + ' indices';
      if (errors > 0) text += ' · ❌ ' + errors + ' erreurs';
      text += (this.abandoned ? ' · 🏳️ abandon' : '') + '\n\n';
      text += gridStr + '\n\n';
      text += '🔗 ' + url + '\n#MotsCroises #LaVeilleAI';
      return text;
    },
    async copyWordleShare() {
      const text = this.wordleShareText();
      try {
        await navigator.clipboard.writeText(text);
        this.wordleShareCopied = true;
        setTimeout(() => { this.wordleShareCopied = false; }, 2500);
      } catch (e) {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e2) {}
        document.body.removeChild(ta);
        this.wordleShareCopied = true;
        setTimeout(() => { this.wordleShareCopied = false; }, 2500);
      }
    },
    async nativeShareWordle() {
      if (!navigator.share) { return this.copyWordleShare(); }
      const text = this.wordleShareText();
      try {
        await navigator.share({ title: 'Mots-croisés laveille.ai', text, url: @json($playUrl) });
      } catch (e) {}
    }
  }));
});
</script>
@endsection
