<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@php $shareData = $tool->getShareData(); @endphp
@section('meta_description', $shareData['meta_description'])
@section('og_type', $shareData['og_type'])
@section('og_image', $shareData['og_image'])
@section('share_text', $shareData['share_text'])
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-12">
        <div class="card shadow-sm" style="border-radius: var(--r-base);">
          <div class="card-body p-4 p-md-5" x-data="crosswordGenerator()" x-init="init()">
            <h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ $tool->name }}</h1>
            <p class="text-muted mb-4">{{ $tool->description }}</p>

            <div class="d-flex flex-wrap gap-2 mb-4 no-print">
              <button type="button" class="ct-btn ct-btn-outline" @click="print()" :disabled="!grid" aria-label="{{ __('Imprimer la grille') }}">
                <i class="fas fa-print me-1"></i> {{ __('Imprimer') }}
              </button>
              @auth
                <button type="button" class="ct-btn ct-btn-primary" @click="save()" :disabled="saving || !grid" aria-label="{{ __('Sauvegarder dans mon compte') }}">
                  <span x-show="!saving"><i class="fas fa-save me-1"></i> {{ __('Sauvegarder') }}</span>
                  <span x-show="saving"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> {{ __('Sauvegarde...') }}</span>
                </button>
              @endauth
            </div>

            {{-- Banner sauvegarde --}}
            @auth
              <div class="alert mb-4" style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px;">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <label for="saveName" class="form-label mb-0 me-2">{{ __('Nom de la grille') }}:</label>
                  <input type="text" id="saveName" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Ex: Ma grille du lundi') }}" aria-label="{{ __('Nom de la grille pour sauvegarde') }}" maxlength="255">
                </div>
                <div class="small mt-2" style="font-size: 0.8rem; color: var(--c-text-muted);">
                  {{ __('Sauvegardez vos grilles dans votre compte pour les retrouver plus tard.') }}
                </div>
              </div>
            @else
              <div class="alert mb-4" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 14px; font-size: 0.85rem; color: #0369a1;">
                <a href="{{ route('login') }}" style="color: #0369a1; font-weight: 600; text-decoration: underline;">{{ __('Connectez-vous') }}</a> {{ __('pour sauvegarder vos grilles dans votre compte.') }}
              </div>
            @endauth

            {{-- Thème (Bonification #2 - Phase 2) --}}
            <div class="mb-4">
              <label for="theme" class="form-label fw-medium">{{ __('Thème de la grille (optionnel)') }}</label>
              <div class="d-flex gap-2">
                <input type="text" id="theme" class="form-control" x-model="metadata.theme" placeholder="{{ __('Ex: Marketing B2B, Histoire du Québec...') }}" aria-label="{{ __('Thème de la grille') }}" maxlength="100">
                <button type="button" class="ct-btn ct-btn-outline" disabled aria-disabled="true" title="{{ __('Bientôt disponible Phase 2') }}">
                  <i class="fas fa-magic me-1"></i> {{ __('Pré-remplir IA') }}
                </button>
              </div>
              <small class="text-muted">{{ __('Suggestions IA bientôt disponibles (Phase 2).') }}</small>
            </div>

            {{-- Métadonnées --}}
            <div class="row g-2 mb-4">
              <div class="col-md-6">
                <label for="gridTitle" class="form-label fw-medium">{{ __('Titre de la grille') }}</label>
                <input type="text" id="gridTitle" class="form-control" x-model="metadata.title" placeholder="{{ __('Ex: Capitales du monde') }}" aria-label="{{ __('Titre de la grille') }}" maxlength="100">
              </div>
              <div class="col-md-3">
                <label for="difficulty" class="form-label fw-medium">{{ __('Difficulté') }}</label>
                <select id="difficulty" class="form-select" x-model="metadata.difficulty" aria-label="{{ __('Niveau de difficulté') }}">
                  <option value="Facile">{{ __('Facile') }}</option>
                  <option value="Moyen">{{ __('Moyen') }}</option>
                  <option value="Difficile">{{ __('Difficile') }}</option>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="isPublic" x-model="metadata.is_public">
                  <label class="form-check-label" for="isPublic">{{ __('Grille publique') }}</label>
                </div>
              </div>
            </div>

            {{-- Paires --}}
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">{{ __('Paires indice/réponse') }}</h2>
                <span class="badge bg-secondary" x-text="pairs.length + ' ' + (pairs.length === 1 ? '{{ __('paire') }}' : '{{ __('paires') }}')"></span>
              </div>

              <template x-for="(pair, index) in pairs" :key="index">
                <div class="row g-2 align-items-start mb-2">
                  <div class="col-12 col-md-6">
                    <input :id="'clue-' + index" type="text" class="form-control" :class="{'is-invalid': errors['clue-' + index]}" x-model="pairs[index].clue" @input="validatePair(index); saveDraft()" maxlength="250" :placeholder="'{{ __('Indice') }} #' + (index + 1)" :aria-label="'{{ __('Indice paire') }} ' + (index + 1)">
                    <div class="invalid-feedback" x-show="errors['clue-' + index]" x-text="errors['clue-' + index]"></div>
                  </div>
                  <div class="col-12 col-md-5">
                    <input :id="'answer-' + index" type="text" class="form-control text-uppercase" :class="{'is-invalid': errors['answer-' + index]}" :value="pairs[index].answer" @input="pairs[index].answer = $event.target.value.toUpperCase(); validatePair(index); saveDraft()" maxlength="30" :placeholder="'{{ __('Réponse') }} #' + (index + 1)" :aria-label="'{{ __('Réponse paire') }} ' + (index + 1)">
                    <div class="invalid-feedback" x-show="errors['answer-' + index]" x-text="errors['answer-' + index]"></div>
                  </div>
                  <div class="col-12 col-md-1 d-flex">
                    <button type="button" class="btn btn-outline-danger w-100" @click="removePair(index)" :disabled="pairs.length <= 1" :aria-label="'{{ __('Supprimer paire') }} ' + (index + 1)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </template>

              <button type="button" class="ct-btn ct-btn-outline mt-2" @click="addPair()" aria-label="{{ __('Ajouter une nouvelle paire') }}">
                <i class="fas fa-plus me-1"></i> {{ __('Ajouter une paire') }}
              </button>
            </div>

            {{-- Bouton générer --}}
            <div class="d-grid gap-2 mb-4">
              <button type="button" class="ct-btn ct-btn-primary ct-btn-lg" @click="generate()" :disabled="generating || !canGenerate()" aria-label="{{ __('Générer la grille') }}">
                <span x-show="!generating"><i class="fas fa-cogs me-2"></i> {{ __('Générer la grille') }}</span>
                <span x-show="generating"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> {{ __('Génération en cours...') }}</span>
              </button>
              <small class="text-muted text-center" x-show="!canGenerate() && !generating">{{ __('Saisissez au moins 2 paires valides pour générer la grille.') }}</small>
            </div>

            {{-- Erreur génération --}}
            <div x-show="generationError" x-cloak class="alert alert-danger mb-4" role="alert">
              <strong>{{ __('Erreur') }}:</strong> <span x-text="generationError"></span>
            </div>

            {{-- Grille générée --}}
            <template x-if="grid && grid.cells && grid.cells.length > 0">
              <div class="mb-5">
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                  <h2 class="h5 mb-0">
                    {{ __('Grille générée') }}
                    <span class="badge bg-success ms-2" x-text="words.length + ' / ' + (words.length + unplaced.length) + ' {{ __('mots placés') }}'"></span>
                  </h2>
                  <div class="form-check form-switch no-print">
                    <input class="form-check-input" type="checkbox" id="showSolutions" x-model="showSolutions">
                    <label class="form-check-label" for="showSolutions">{{ __('Afficher les solutions') }}</label>
                  </div>
                </div>

                <div class="table-responsive d-flex justify-content-center">
                  <table class="crossword-grid" :aria-label="'{{ __('Grille de mots croisés') }} ' + grid.rows + 'x' + grid.cols">
                    <tbody>
                      <template x-for="(row, rowIndex) in grid.cells" :key="rowIndex">
                        <tr>
                          <template x-for="(cell, colIndex) in row" :key="colIndex">
                            <td :class="cell !== null ? 'cell-active' : 'cell-inactive'" :aria-label="cell !== null ? ('{{ __('Case') }} ' + (rowIndex + 1) + '-' + (colIndex + 1) + (cell.number ? ', {{ __('numéro') }} ' + cell.number : '')) : '{{ __('Case noire') }}'">
                              <template x-if="cell !== null">
                                <span>
                                  <span class="number" x-show="cell.number" x-text="cell.number"></span>
                                  <span class="letter" x-show="showSolutions" x-text="cell.letter"></span>
                                </span>
                              </template>
                            </td>
                          </template>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>

                {{-- Liste indices --}}
                <div class="row mt-4">
                  <div class="col-md-6">
                    <h3 class="h6 fw-bold">{{ __('Horizontaux') }} →</h3>
                    <ul class="list-unstyled">
                      <template x-for="word in words.filter(w => w.orientation === 'horizontal')" :key="word.number + 'h'">
                        <li class="mb-1"><strong x-text="word.number + '.'"></strong> <span x-text="word.clue"></span> <small class="text-muted" x-show="showSolutions" x-text="' (' + word.answer + ')'"></small></li>
                      </template>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <h3 class="h6 fw-bold">{{ __('Verticaux') }} ↓</h3>
                    <ul class="list-unstyled">
                      <template x-for="word in words.filter(w => w.orientation === 'vertical')" :key="word.number + 'v'">
                        <li class="mb-1"><strong x-text="word.number + '.'"></strong> <span x-text="word.clue"></span> <small class="text-muted" x-show="showSolutions" x-text="' (' + word.answer + ')'"></small></li>
                      </template>
                    </ul>
                  </div>
                </div>

                {{-- Mots non placés --}}
                <template x-if="unplaced.length > 0">
                  <div class="alert alert-warning mt-4" role="alert">
                    <strong>{{ __('Mots non placés') }}:</strong>
                    <span x-text="unplaced.map(u => u.answer).join(', ')"></span>
                    <div class="small">{{ __('Aucune intersection possible avec les autres mots. Modifiez les indices/réponses ou ajoutez des mots partageant des lettres.') }}</div>
                  </div>
                </template>

                {{-- Footer sobre pour impression --}}
                <div class="crossword-print-footer mt-5 pt-3" style="display: none; border-top: 1px solid #ccc; font-size: 9pt; color: #6E7687;">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <strong>laveille.ai/outils</strong>
                      <div class="mt-1" x-show="metadata.title" x-text="metadata.title"></div>
                    </div>
                    <div class="text-end">{{ __('Généré le') }} {{ now()->format('Y-m-d') }}</div>
                  </div>
                </div>
              </div>
            </template>

            {{-- Brouillon --}}
            <div class="mt-4 pt-3 border-top no-print">
              <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i> {{ __('Brouillon sauvegardé dans votre navigateur.') }}
                <button type="button" class="btn btn-sm btn-link p-0 ms-2" @click="if(confirm('{{ __('Effacer le brouillon ?') }}')) clearDraft()">{{ __('Effacer le brouillon') }}</button>
              </small>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .crossword-grid {
    table-layout: fixed;
    border-collapse: collapse;
    margin: 1rem auto;
  }
  .crossword-grid td {
    width: 36px;
    height: 36px;
    min-width: 36px;
    min-height: 36px;
    padding: 0;
    text-align: center;
    vertical-align: middle;
    box-sizing: border-box;
  }
  .cell-active {
    background-color: #ffffff;
    border: 1px solid var(--c-dark, #1A1D23);
    position: relative;
  }
  .cell-active .number {
    position: absolute;
    top: 1px;
    left: 3px;
    font-size: 9px;
    line-height: 1;
    color: var(--c-dark, #1A1D23);
    font-weight: 600;
  }
  .cell-active .letter {
    font-size: 18px;
    font-weight: 700;
    text-align: center;
    line-height: 36px;
    color: var(--c-dark, #1A1D23);
    text-transform: uppercase;
  }
  .cell-inactive {
    background-color: var(--c-dark, #1A1D23);
    border: 1px solid var(--c-dark, #1A1D23);
  }
  @media print {
    .no-print, header, footer, nav, .breadcrumb-container, .navbar, .modal { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .crossword-grid td {
      width: 28px;
      height: 28px;
      min-width: 28px;
      min-height: 28px;
    }
    .cell-active .letter { font-size: 14px; line-height: 28px; }
    .cell-active .number { font-size: 8px; }
    .crossword-print-footer { display: block !important; }
  }
</style>
@endsection

@push('scripts')
<script>
function crosswordGenerator() {
  return {
    isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
    pairs: [{clue: '', answer: ''}, {clue: '', answer: ''}],
    metadata: {title: '', difficulty: 'Moyen', is_public: false, theme: ''},
    grid: null,
    words: [],
    unplaced: [],
    showSolutions: false,
    generating: false,
    saving: false,
    saveName: '',
    errors: {},
    generationError: null,

    init() {
      const draft = localStorage.getItem('crossword_draft');
      if (draft) {
        try {
          const data = JSON.parse(draft);
          if ((data.pairs && data.pairs.length > 0 && (data.pairs[0].clue || data.pairs[0].answer)) || data.metadata?.title) {
            if (confirm("{{ __('Brouillon trouvé. Reprendre ?') }}")) {
              this.pairs = data.pairs && data.pairs.length >= 2 ? data.pairs : [{clue: '', answer: ''}, {clue: '', answer: ''}];
              this.metadata = Object.assign({title: '', difficulty: 'Moyen', is_public: false, theme: ''}, data.metadata || {});
              this.saveName = data.saveName || '';
            } else {
              localStorage.removeItem('crossword_draft');
            }
          }
        } catch (e) {
          console.error('Invalid draft', e);
          localStorage.removeItem('crossword_draft');
        }
      }
    },

    addPair() {
      this.pairs.push({clue: '', answer: ''});
      this.saveDraft();
    },

    removePair(i) {
      if (this.pairs.length <= 1) return;
      this.pairs.splice(i, 1);
      delete this.errors['clue-' + i];
      delete this.errors['answer-' + i];
      this.saveDraft();
    },

    validatePair(i) {
      const pair = this.pairs[i];
      const clueKey = 'clue-' + i;
      const answerKey = 'answer-' + i;
      delete this.errors[clueKey];
      delete this.errors[answerKey];

      if (!pair.clue || !pair.clue.trim()) {
        this.errors[clueKey] = "{{ __('L\'indice est requis.') }}";
      } else if (pair.clue.length > 250) {
        this.errors[clueKey] = "{{ __('Maximum 250 caractères.') }}";
      }

      const answer = (pair.answer || '').trim();
      if (!answer) {
        this.errors[answerKey] = "{{ __('La réponse est requise.') }}";
      } else if (answer.length < 2) {
        this.errors[answerKey] = "{{ __('Au moins 2 caractères.') }}";
      } else if (answer.length > 30) {
        this.errors[answerKey] = "{{ __('Maximum 30 caractères.') }}";
      } else if (!/^[a-zA-ZàâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ]+$/.test(answer)) {
        this.errors[answerKey] = "{{ __('Lettres uniquement (accents permis).') }}";
      }
    },

    canGenerate() {
      const validPairs = this.pairs.filter(p => p.clue && p.clue.trim() && p.answer && p.answer.trim().length >= 2 && /^[a-zA-ZàâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ]+$/.test(p.answer.trim()));
      return validPairs.length >= 2 && Object.keys(this.errors).length === 0;
    },

    async generate() {
      if (!this.canGenerate()) return;
      this.generating = true;
      this.generationError = null;
      this.grid = null;
      this.words = [];
      this.unplaced = [];

      try {
        const response = await fetch('{{ url("/outils/mots-croises/generate") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            pairs: this.pairs.filter(p => p.clue.trim() && p.answer.trim()).map(p => ({clue: p.clue.trim(), answer: p.answer.trim().toUpperCase()}))
          })
        });

        const data = await response.json();
        if (response.ok && data.success) {
          this.grid = data.grid;
          this.words = data.words;
          this.unplaced = data.unplaced || [];
          this.showSolutions = false;
          this.dispatchToast("{{ __('Grille générée avec succès.') }}", 'success');
        } else {
          this.generationError = data.error || data.message || "{{ __('Impossible de générer la grille avec ces mots. Vérifiez qu\'ils partagent des lettres communes.') }}";
        }
      } catch (error) {
        console.error('Fetch error', error);
        this.generationError = "{{ __('Erreur réseau. Réessayez.') }}";
      } finally {
        this.generating = false;
      }
    },

    async save() {
      if (!this.isAuthenticated || !this.grid) return;
      const name = this.saveName.trim() || this.metadata.title.trim() || ('{{ __('Grille') }} ' + new Date().toLocaleDateString('fr-CA'));
      this.saving = true;
      try {
        const response = await fetch('/api/crossword-presets', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            name: name,
            config_text: JSON.stringify({
              pairs: this.pairs,
              metadata: this.metadata,
              grid: this.grid,
              words: this.words,
              unplaced: this.unplaced
            }),
            params: this.metadata,
            is_public: !!this.metadata.is_public
          })
        });

        if (response.ok) {
          const preset = await response.json();
          this.dispatchToast("{{ __('Grille sauvegardée avec succès.') }}", 'success');
          if (this.metadata.is_public && preset.public_id) {
            const playUrl = window.location.origin + '/jeu/' + preset.public_id;
            console.log('Lien public', playUrl);
          }
        } else {
          const errorData = await response.json().catch(() => ({}));
          this.dispatchToast(errorData.message || "{{ __('Erreur lors de la sauvegarde.') }}", 'danger');
        }
      } catch (error) {
        console.error('Save error', error);
        this.dispatchToast("{{ __('Erreur réseau lors de la sauvegarde.') }}", 'danger');
      } finally {
        this.saving = false;
      }
    },

    print() {
      if (!this.grid) return;
      this.showSolutions = false;
      setTimeout(() => window.print(), 50);
    },

    saveDraft() {
      try {
        localStorage.setItem('crossword_draft', JSON.stringify({
          pairs: this.pairs,
          metadata: this.metadata,
          saveName: this.saveName
        }));
      } catch (e) { /* localStorage full or disabled */ }
    },

    clearDraft() {
      localStorage.removeItem('crossword_draft');
      this.pairs = [{clue: '', answer: ''}, {clue: '', answer: ''}];
      this.metadata = {title: '', difficulty: 'Moyen', is_public: false, theme: ''};
      this.saveName = '';
      this.errors = {};
      this.grid = null;
      this.words = [];
      this.unplaced = [];
      this.generationError = null;
    },

    dispatchToast(message, variant) {
      window.dispatchEvent(new CustomEvent('toast-show', {
        detail: { message: message, variant: variant || 'info' }
      }));
    }
  };
}
</script>
@endpush
