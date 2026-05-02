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
              <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" @click="print()" :disabled="!grid" aria-label="{{ __('Imprimer la grille') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>{{ __('Imprimer') }}</span>
              </button>
              @auth
                <button type="button" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2" @click="save()" :disabled="saving || !grid" aria-label="{{ __('Sauvegarder dans mon compte') }}">
                  <template x-if="!saving">
                    <span class="d-inline-flex align-items-center gap-2">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                      <span>{{ __('Sauvegarder') }}</span>
                    </span>
                  </template>
                  <template x-if="saving">
                    <span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>{{ __('Sauvegarde...') }}</span></span>
                  </template>
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

            {{-- Bloc explicatif "Comment ça fonctionne" --}}
            <section class="cw-howto mb-4" aria-labelledby="cw-howto-title">
              <h2 id="cw-howto-title" class="h5 mb-2 d-flex align-items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                {{ __('Comment ça fonctionne') }}
              </h2>
              <p class="mb-2">{{ __('Pour chaque mot caché à placer dans la grille, écrivez :') }}</p>
              <ul class="mb-2">
                <li><strong>{{ __('Le mot') }}</strong> {{ __('(la réponse cachée, ex : LARAVEL)') }}</li>
                <li><strong>{{ __('Son indice') }}</strong> {{ __('(la définition que verront les joueurs, ex : Framework PHP majeur)') }}</li>
              </ul>
              <p class="mb-0 small" style="color:#1A1D23"><strong>{{ __('Astuce') }} :</strong> {{ __('plus vos mots partagent de lettres communes, mieux ils s\'entrecroiseront dans la grille.') }}</p>
            </section>

            {{-- Métadonnées --}}
            <div class="row g-3 mb-4">
              <div class="col-md-7">
                <label for="gridTitle" class="form-label fw-medium">{{ __('Titre de la grille') }}</label>
                <input type="text" id="gridTitle" class="form-control" x-model="metadata.title" placeholder="{{ __('Ex: Capitales du monde') }}" aria-label="{{ __('Titre de la grille') }}" maxlength="100">
              </div>
              <div class="col-md-5">
                <label for="difficulty" class="form-label fw-medium">{{ __('Difficulté') }}</label>
                <select id="difficulty" class="form-select" x-model="metadata.difficulty" aria-label="{{ __('Niveau de difficulté') }}">
                  <option value="Facile">{{ __('Facile') }}</option>
                  <option value="Moyen">{{ __('Moyen') }}</option>
                  <option value="Difficile">{{ __('Difficile') }}</option>
                </select>
              </div>
              @auth
              <div class="col-12">
                <div class="form-check form-switch d-inline-flex align-items-center gap-2 m-0" style="padding-left:2.5em">
                  <input class="form-check-input" type="checkbox" role="switch" id="isPublic" x-model="metadata.is_public" style="width:2.5em;height:1.4em;cursor:pointer">
                  <label class="form-check-label" for="isPublic" style="cursor:pointer;color:#1A1D23">
                    <strong>{{ __('Rendre la grille publique') }}</strong>
                    <span class="d-block small" style="color:#475569">{{ __('Génère un lien partageable /jeu/... que d\'autres pourront jouer en ligne.') }}</span>
                  </label>
                </div>
              </div>
              @endauth
            </div>

            {{-- Mots à placer --}}
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">{{ __('Mots à placer') }}</h2>
                <span class="badge bg-secondary" x-text="pairs.length + ' ' + (pairs.length === 1 ? '{{ __('mot') }}' : '{{ __('mots') }}')"></span>
              </div>
              <p class="small mb-3 d-flex align-items-start gap-2" style="color:#1A1D23">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>{{ __('Au moins 2 mots sont nécessaires. Les mots doivent partager des lettres pour s\'entrecroiser dans la grille.') }}</span>
              </p>

              <template x-for="(pair, index) in pairs" :key="index">
                <div class="row g-2 align-items-start mb-2">
                  <div class="col-12 col-md-6">
                    <input :id="'clue-' + index" type="text" class="form-control" :class="{'is-invalid': errors['clue-' + index]}" x-model="pairs[index].clue" @input="validatePair(index); saveDraft()" maxlength="250" :placeholder="'{{ __('Indice') }} #' + (index + 1)" :aria-label="'{{ __('Indice du mot') }} ' + (index + 1)">
                    <div class="invalid-feedback" x-show="errors['clue-' + index]" x-text="errors['clue-' + index]"></div>
                  </div>
                  <div class="col-12 col-md-5">
                    <input :id="'answer-' + index" type="text" class="form-control text-uppercase" :class="{'is-invalid': errors['answer-' + index]}" :value="pairs[index].answer" @input="pairs[index].answer = $event.target.value.toUpperCase(); validatePair(index); saveDraft()" maxlength="30" :placeholder="'{{ __('Mot') }} #' + (index + 1)" :aria-label="'{{ __('Mot') }} ' + (index + 1)">
                    <div class="invalid-feedback" x-show="errors['answer-' + index]" x-text="errors['answer-' + index]"></div>
                  </div>
                  <div class="col-12 col-md-1 d-flex">
                    <button type="button" class="crossword-pair-delete" @click="removePair(index)" :disabled="pairs.length <= 1" :aria-label="'{{ __('Supprimer le mot') }} ' + (index + 1)" :title="'{{ __('Supprimer le mot') }} ' + (index + 1)">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </template>

              <button type="button" class="ct-btn ct-btn-outline mt-2 d-inline-flex align-items-center gap-2" @click="addPair()" aria-label="{{ __('Ajouter un nouveau mot') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>{{ __('Ajouter un mot') }}</span>
              </button>
            </div>

            {{-- Bouton générer --}}
            <div class="d-grid gap-2 mb-4">
              <button type="button" class="ct-btn ct-btn-primary ct-btn-lg d-inline-flex align-items-center justify-content-center gap-2" @click="generate()" :disabled="generating || !canGenerate()" aria-label="{{ __('Générer la grille') }}">
                <template x-if="!generating">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>
                    <span>{{ __('Générer la grille') }}</span>
                  </span>
                </template>
                <template x-if="generating">
                  <span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>{{ __('Génération en cours...') }}</span></span>
                </template>
              </button>
              <small class="text-muted text-center" x-show="!canGenerate() && !generating">{{ __('Saisissez au moins 2 mots valides pour générer la grille.') }}</small>
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
                  <h2 class="h5 mb-0 d-flex align-items-center flex-wrap gap-2">
                    {{ __('Grille générée') }}
                    <span class="badge" style="background:#053d4a;color:#fff" x-text="words.length + ' / ' + (words.length + unplaced.length) + ' {{ __('mots placés') }}'"></span>
                    <span x-show="gridStats" x-cloak class="badge" style="background:#e0f2f1;color:#053d4a;border:1px solid #053d4a" :title="'{{ __('Densité de la grille (cases utilisées vs surface totale)') }}'" x-text="gridStats ? '{{ __('Densité') }} ' + Math.round(gridStats.compactness * 100) + ' %' : ''"></span>
                  </h2>
                  <div class="d-flex align-items-center flex-wrap gap-2 no-print">
                    <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" style="min-height:44px" @click="regenerate()" :disabled="regenerating || generating" :aria-label="'{{ __('Régénérer une autre disposition de la grille') }}'">
                      <template x-if="!regenerating">
                        <span class="d-inline-flex align-items-center gap-2">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                          <span>{{ __('Autre disposition') }}</span>
                        </span>
                      </template>
                      <template x-if="regenerating">
                        <span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>{{ __('Recalcul...') }}</span></span>
                      </template>
                    </button>
                    <div class="form-check form-switch m-0">
                      <input class="form-check-input" type="checkbox" id="showSolutions" x-model="showSolutions">
                      <label class="form-check-label" for="showSolutions">{{ __('Afficher les solutions') }}</label>
                    </div>
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
              <small class="text-muted d-inline-flex align-items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                {{ __('Brouillon sauvegardé dans votre navigateur.') }}
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
  /* Card Démarrage IA - workflow 3 étapes */
  .ai-quickstart {
    background: linear-gradient(135deg, rgba(11,114,133,0.04) 0%, rgba(255,140,66,0.04) 100%);
    border: 1px solid rgba(11,114,133,0.18);
    border-radius: 16px;
    padding: 24px;
  }
  .ai-quickstart-header { margin-bottom: 20px; }
  .ai-quickstart-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 6px 0;
    font-family: var(--f-heading, system-ui);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--c-dark, #1A1D23);
    flex-wrap: wrap;
  }
  .ai-quickstart-icon { color: #FF8C42; flex-shrink: 0; }
  .ai-quickstart-badge {
    display: inline-block;
    background: #16A34A;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .ai-quickstart-subtitle {
    margin: 0;
    color: var(--c-text-muted, #6E7687);
    font-size: 0.95rem;
  }
  .ai-quickstart-steps {
    list-style: none;
    counter-reset: ai-step;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 16px;
  }
  .ai-quickstart-step {
    display: flex;
    gap: 16px;
    align-items: flex-start;
  }
  .ai-quickstart-step-num {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #0B7285;
    color: #fff;
    font-weight: 700;
    border-radius: 50%;
    font-size: 0.95rem;
  }
  .ai-quickstart-step-body { flex: 1; min-width: 0; }
  .ai-quickstart-step-body strong {
    display: block;
    color: var(--c-dark, #1A1D23);
    font-size: 1rem;
  }
  .ai-quickstart-step-desc {
    margin: 4px 0 8px 0;
    color: var(--c-text-muted, #6E7687);
    font-size: 0.9rem;
  }
  .ai-quickstart-cta {
    background: #0B7285;
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
    min-height: 44px;
  }
  .ai-quickstart-cta:hover:not(:disabled) {
    background: #095462;
  }
  .ai-quickstart-cta:focus-visible {
    outline: 2px solid #0B7285;
    outline-offset: 2px;
  }
  .ai-quickstart-cta:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  .ai-quickstart-warning {
    color: #92400E;
    font-size: 0.85rem;
  }
  .ai-quickstart-error {
    margin-top: 16px;
    padding: 10px 14px;
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    border-radius: 8px;
    color: #991B1B;
    font-size: 0.9rem;
  }
  @media (max-width: 640px) {
    .ai-quickstart { padding: 16px; }
    .ai-quickstart-step { gap: 12px; }
    .ai-quickstart-step-num { width: 28px; height: 28px; font-size: 0.85rem; }
  }
  .crossword-pair-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border: 1px solid #E5E7EB;
    background: transparent;
    color: #6E7687;
    border-radius: 8px;
    cursor: pointer;
    transition: color 0.15s, background-color 0.15s, border-color 0.15s;
  }
  .crossword-pair-delete:hover:not(:disabled),
  .crossword-pair-delete:focus-visible:not(:disabled) {
    color: #DC2626;
    border-color: #DC2626;
    background-color: rgba(220, 38, 38, 0.06);
  }
  .crossword-pair-delete:focus-visible {
    outline: 2px solid #DC2626;
    outline-offset: 2px;
  }
  .crossword-pair-delete:disabled {
    opacity: 0.35;
    cursor: not-allowed;
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
    gridStats: null,
    showSolutions: false,
    generating: false,
    regenerating: false,
    saving: false,
    saveName: '',
    errors: {},
    generationError: null,
    suggestingPairs: false,
    suggestError: null,

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

    async suggestPairs() {
      const theme = (this.metadata.theme || '').trim();
      if (!theme || this.suggestingPairs) return;
      this.suggestingPairs = true;
      this.suggestError = null;

      try {
        const response = await fetch('{{ url("/outils/mots-croises/ai-suggest-pairs") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({theme: theme, count: 10})
        });

        const data = await response.json();
        if (response.ok && data.success && Array.isArray(data.pairs) && data.pairs.length > 0) {
          this.pairs = data.pairs.map(p => ({clue: p.clue, answer: (p.answer || '').toUpperCase()}));
          this.errors = {};
          this.saveDraft();
          this.dispatchToast('{{ __('Paires générées :') }} ' + data.pairs.length, 'success');
        } else {
          this.suggestError = (data && data.error) || '{{ __('Aucune suggestion. Essayez un thème plus précis.') }}';
        }
      } catch (error) {
        console.error('Suggest error', error);
        this.suggestError = '{{ __('Erreur réseau. Réessayez dans quelques secondes.') }}';
      } finally {
        this.suggestingPairs = false;
      }
    },

    async generate(seed = null, isRegenerate = false) {
      if (!this.canGenerate()) return;
      if (isRegenerate) {
        this.regenerating = true;
      } else {
        this.generating = true;
        this.grid = null;
        this.words = [];
        this.unplaced = [];
        this.gridStats = null;
      }
      this.generationError = null;

      try {
        const body = {
          pairs: this.pairs.filter(p => p.clue.trim() && p.answer.trim()).map(p => ({clue: p.clue.trim(), answer: p.answer.trim().toUpperCase()}))
        };
        if (seed !== null) body.seed = seed;

        const response = await fetch('{{ url("/outils/mots-croises/generate") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify(body)
        });

        const data = await response.json();
        if (response.ok && data.success) {
          this.grid = data.grid;
          this.words = data.words;
          this.unplaced = data.unplaced || [];
          this.gridStats = data.stats || null;
          this.showSolutions = false;
          if (!isRegenerate) {
            this.dispatchToast("{{ __('Grille générée avec succès.') }}", 'success');
          }
        } else {
          this.generationError = data.error || data.message || "{{ __('Impossible de générer la grille avec ces mots. Vérifiez qu\'ils partagent des lettres communes.') }}";
        }
      } catch (error) {
        console.error('Fetch error', error);
        this.generationError = "{{ __('Erreur réseau. Réessayez.') }}";
      } finally {
        this.generating = false;
        this.regenerating = false;
      }
    },

    regenerate() {
      if (typeof window.gtag === 'function') {
        window.gtag('event', 'crossword_reroll', {
          event_category: 'tools',
          event_label: 'mots-croises',
          words_count: this.pairs.filter(p => p.clue.trim() && p.answer.trim()).length
        });
      }
      this.generate(null, true);
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
