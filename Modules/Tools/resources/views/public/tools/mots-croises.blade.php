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
              <section class="cw-guest-card mb-4" aria-labelledby="cw-guest-title">
                <div class="cw-guest-header">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <h2 id="cw-guest-title" class="h5 mb-0">{{ __('Créez un compte gratuit pour aller plus loin') }}</h2>
                  <span class="cw-guest-pill">{{ __('Gratuit, 30 sec') }}</span>
                </div>
                <p class="cw-guest-intro">{{ __('Pas de mot de passe, pas d\'inscription compliquée. Un courriel suffit pour débloquer :') }}</p>
                <ul class="cw-guest-list">
                  <li>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <div><strong>{{ __('Sauvegardez vos grilles') }}</strong> {{ __('et retrouvez-les sur n\'importe quel appareil.') }}</div>
                  </li>
                  <li>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    <div><strong>{{ __('Partagez vos grilles publiques') }}</strong> {{ __('via un lien /jeu/... que vos collègues, élèves ou amis peuvent jouer en ligne.') }}</div>
                  </li>
                  <li>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                    <div><strong>{{ __('Mettez vos articles en favoris') }}</strong> {{ __('pour les retrouver dans votre tableau de bord.') }}</div>
                  </li>
                  <li>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <div><strong>{{ __('Recevez les alertes IA hebdomadaires') }}</strong> {{ __('sur les sujets qui vous intéressent (sans spam).') }}</div>
                  </li>
                  <li>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="7 11 12 6 17 11"/><polyline points="7 17 12 12 17 17"/></svg>
                    <div><strong>{{ __('Votez pour vos outils IA préférés') }}</strong> {{ __('dans l\'annuaire et influencez le classement de la communauté.') }}</div>
                  </li>
                  <li>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <div><strong>{{ __('Suggérez des modifications') }}</strong> {{ __('et proposez de nouvelles idées dans la roadmap publique.') }}</div>
                  </li>
                </ul>
                <div class="cw-guest-actions">
                  <button type="button" class="cw-guest-cta" @click="$dispatch('open-auth-modal', { message: 'Connectez-vous pour sauvegarder vos grilles et profiter de tous les avantages.' })" aria-label="{{ __('Créer un compte gratuit pour sauvegarder mes grilles') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <span>{{ __('Créer mon compte gratuit') }}</span>
                  </button>
                  <small class="cw-guest-note">{{ __('Aucune carte de crédit. Désabonnement en 1 clic.') }}</small>
                </div>
              </section>
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
                <button type="button"
                        class="ct-btn d-inline-flex align-items-start gap-3 text-start"
                        style="min-height:44px;padding:.75rem 1.25rem;max-width:100%;white-space:normal"
                        :class="metadata.is_public ? 'ct-btn-primary' : 'ct-btn-outline'"
                        @click="metadata.is_public = !metadata.is_public; saveDraft()"
                        :aria-pressed="metadata.is_public"
                        :aria-label="metadata.is_public ? '{{ __('Rendre la grille privée') }}' : '{{ __('Rendre la grille publique') }}'">
                  <template x-if="!metadata.is_public">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0;margin-top:2px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </template>
                  <template x-if="metadata.is_public">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                  </template>
                  <span style="flex:1;min-width:0">
                    <strong x-text="metadata.is_public ? '{{ __('Grille publique') }} ✓' : '{{ __('Rendre la grille publique') }}'"></strong>
                    <span class="d-block small" style="color:inherit;opacity:.85;font-weight:400" x-text="metadata.is_public ? '{{ __('Lien partageable /jeumc/... actif - cliquez pour rendre privée.') }}' : '{{ __('Génère un lien partageable /jeumc/... que d\'autres pourront jouer en ligne.') }}'"></span>
                  </span>
                </button>
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
                    <input :id="'answer-' + index" type="text" class="form-control text-uppercase" :class="{'is-invalid': errors['answer-' + index]}" :value="pairs[index].answer" @input="pairs[index].answer = $event.target.value.toUpperCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^A-Z]/g,''); validatePair(index); saveDraft()" maxlength="30" :placeholder="'{{ __('Mot') }} #' + (index + 1)" :aria-label="'{{ __('Mot sans accent ni espace') }} ' + (index + 1)" autocapitalize="characters" autocorrect="off" spellcheck="false">
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

              <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" @click="addPair()" aria-label="{{ __('Ajouter un nouveau mot') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>{{ __('Ajouter un mot') }}</span>
                </button>
                <div class="position-relative" x-data="{ dataMenuOpen: false }">
                  <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" style="min-height:44px" @click="dataMenuOpen = !dataMenuOpen" :aria-expanded="dataMenuOpen" aria-haspopup="menu" aria-label="{{ __('Données : importer, exporter, modèle CSV') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/></svg>
                    <span>{{ __('Données CSV') }}</span>
                  </button>
                  <div x-show="dataMenuOpen" x-cloak @click.outside="dataMenuOpen=false" @keydown.escape.window="dataMenuOpen=false" role="menu"
                       style="position:absolute;top:calc(100% + 6px);left:0;z-index:200;background:#fff;border:1px solid #053d4a;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.15);min-width:280px;padding:.5rem;display:flex;flex-direction:column;gap:.15rem">
                    <button type="button" role="menuitem" class="cw-menu-item" @click="openImportCsv(); dataMenuOpen=false">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                      <span><strong>{{ __('Importer CSV') }}</strong><span class="d-block small" style="color:#475569">{{ __('Remplir tous les mots à partir d\'un fichier') }}</span></span>
                    </button>
                    <a role="menuitem" class="cw-menu-item" href="{{ route('tools.crossword.csv-template') }}" download @click="dataMenuOpen=false" style="text-decoration:none;color:inherit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                      <span><strong>{{ __('Télécharger modèle CSV') }}</strong><span class="d-block small" style="color:#475569">{{ __('Démo avec en-têtes Indice;Mot') }}</span></span>
                    </a>
                    <button type="button" role="menuitem" class="cw-menu-item" @click="exportCsv(); dataMenuOpen=false" :disabled="!pairs.some(p => p.clue && p.answer)">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                      <span><strong>{{ __('Exporter mes mots en CSV') }}</strong><span class="d-block small" style="color:#475569">{{ __('Sauvegarder les paires saisies') }}</span></span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            {{-- Bouton générer --}}
            <div class="d-grid gap-2 mb-4">
              <button type="button" class="ct-btn ct-btn-primary ct-btn-lg d-inline-flex align-items-center justify-content-center gap-2" @click="generate()" :disabled="generating || !canGenerate()" aria-label="{{ __('Générer la grille') }}">
                <template x-if="!generating">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3l1.9 4.6L18.5 9l-4.6 1.9L12 15.5l-1.9-4.6L5.5 9l4.6-1.4z"/><path d="M19 14l.7 2.3L22 17l-2.3.7L19 20l-.7-2.3L16 17l2.3-.7z"/><path d="M5 17l.5 1.5L7 19l-1.5.5L5 21l-.5-1.5L3 19l1.5-.5z"/></svg>
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
                  <div class="d-flex align-items-center flex-wrap gap-2 no-print" x-data="{ menuOpen: false }">
                    <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" style="min-height:44px" @click="regenerate()" :disabled="regenerating || generating">
                      <template x-if="!regenerating">
                        <span class="d-inline-flex align-items-center gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg><span>{{ __('Autre disposition') }}</span></span>
                      </template>
                      <template x-if="regenerating">
                        <span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>{{ __('Recalcul...') }}</span></span>
                      </template>
                    </button>
                    <div class="position-relative">
                      <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" style="min-height:44px" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-haspopup="menu" aria-label="{{ __('Plus d\'options : PDF, CSV, solutions') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>{{ __('Plus d\'options') }}</span>
                      </button>
                      <div x-show="menuOpen" x-cloak @click.outside="menuOpen=false" @keydown.escape.window="menuOpen=false" role="menu"
                           style="position:absolute;top:calc(100% + 6px);right:0;z-index:200;background:#fff;border:1px solid #053d4a;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.15);min-width:280px;padding:.5rem;display:flex;flex-direction:column;gap:.15rem">
                        <button type="button" role="menuitem" class="cw-menu-item" @click="downloadPdfBlank(); menuOpen=false" :disabled="!grid">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                          <span><strong>{{ __('PDF vierge') }}</strong><span class="d-block small" style="color:#475569">{{ __('Pour impression élève') }}</span></span>
                        </button>
                        <button type="button" role="menuitem" class="cw-menu-item" @click="downloadPdfSolution(); menuOpen=false" :disabled="!grid">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><circle cx="12" cy="15" r="2"/></svg>
                          <span><strong>{{ __('PDF corrigé') }}</strong><span class="d-block small" style="color:#475569">{{ __('Avec lettres révélées (prof)') }}</span></span>
                        </button>
                        <hr class="my-1" style="border-color:#e2e8f0">
                        <button type="button" role="menuitem" class="cw-menu-item" @click="exportCsv(); menuOpen=false" :disabled="!pairs.some(p => p.clue && p.answer)">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                          <span><strong>{{ __('Exporter CSV') }}</strong><span class="d-block small" style="color:#475569">{{ __('Sauvegarder vos paires') }}</span></span>
                        </button>
                        <hr class="my-1" style="border-color:#e2e8f0">
                        <button type="button" role="menuitem" class="cw-menu-item" @click="showSolutions = !showSolutions; menuOpen=false">
                          <template x-if="!showSolutions"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></template>
                          <template x-if="showSolutions"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></template>
                          <span><strong x-text="showSolutions ? '{{ __('Masquer solutions') }}' : '{{ __('Afficher solutions') }}'"></strong><span class="d-block small" style="color:#475569">{{ __('Bascule lettres dans la grille') }}</span></span>
                        </button>
                        <hr class="my-1" style="border-color:#e2e8f0">
                        <div style="padding:.4rem .75rem">
                          <div class="small fw-bold mb-2" style="color:#053d4a;text-transform:uppercase;letter-spacing:.04em;font-size:.7rem">{{ __('Cases noires') }}</div>
                          <div role="radiogroup" :aria-label="'{{ __('Apparence des cases noires') }}'" class="d-flex gap-2 flex-wrap">
                            <button type="button" role="radio" :aria-checked="inactiveStyle === 'black'" :class="inactiveStyle === 'black' ? 'cw-style-active' : ''" class="cw-style-opt" @click="inactiveStyle='black'; localStorage.setItem('cw_inactive_style','black')" :title="'{{ __('Noir plein (par défaut)') }}'">
                              <span class="cw-style-swatch" style="background:#1A1D23"></span>
                              <span>{{ __('Noir') }}</span>
                            </button>
                            <button type="button" role="radio" :aria-checked="inactiveStyle === 'gray'" :class="inactiveStyle === 'gray' ? 'cw-style-active' : ''" class="cw-style-opt" @click="inactiveStyle='gray'; localStorage.setItem('cw_inactive_style','gray')" :title="'{{ __('Gris (économie encre partielle)') }}'">
                              <span class="cw-style-swatch" style="background:#9ca3af"></span>
                              <span>{{ __('Gris') }}</span>
                            </button>
                            <button type="button" role="radio" :aria-checked="inactiveStyle === 'border'" :class="inactiveStyle === 'border' ? 'cw-style-active' : ''" class="cw-style-opt" @click="inactiveStyle='border'; localStorage.setItem('cw_inactive_style','border')" :title="'{{ __('Bordure seule (économie encre maximale)') }}'">
                              <span class="cw-style-swatch" style="background:#fff;border:2px solid #1A1D23"></span>
                              <span>{{ __('Bordure') }}</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- Modal Import CSV --}}
                <div x-show="csvImportOpen" x-cloak @click.self="csvImportOpen=false" @keydown.escape.window="csvImportOpen=false" role="dialog" aria-modal="true" aria-labelledby="csv-import-title" style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem">
                  <div style="background:#fff;border-radius:12px;padding:1.5rem;max-width:600px;width:100%;max-height:90vh;overflow:auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h2 id="csv-import-title" class="h5 mb-0" style="color:#053d4a">📤 {{ __('Importer un fichier CSV') }}</h2>
                      <button type="button" class="btn btn-link p-0" @click="csvImportOpen=false" aria-label="{{ __('Fermer') }}" style="font-size:1.5rem;color:#475569;text-decoration:none">&times;</button>
                    </div>
                    <p class="small mb-3" style="color:#475569">{{ __('Format attendu : 2 colonnes Indice;Mot (séparateur ; ou ,). En-tête optionnelle. Max 50 lignes. Téléchargez le') }} <a href="{{ route('tools.crossword.csv-template') }}" download style="color:#053d4a;font-weight:600">{{ __('modèle CSV') }}</a> {{ __('si besoin.') }}</p>
                    <div class="mb-3">
                      <label class="form-label" style="font-weight:600;color:#1A1D23">{{ __('Coller le contenu CSV') }}</label>
                      <textarea x-model="csvImportText" rows="8" class="form-control" placeholder="Indice;Mot&#10;Capitale du Quebec;QUEBEC&#10;Framework PHP majeur;LARAVEL" style="font-family:monospace;font-size:.85rem"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label" style="font-weight:600;color:#1A1D23">{{ __('OU choisir un fichier') }}</label>
                      <input type="file" accept=".csv,text/csv" @change="csvImportFile = $event.target.files[0]" class="form-control">
                    </div>
                    <div x-show="csvImportError" x-cloak class="alert alert-danger small mb-3" role="alert" x-text="csvImportError"></div>
                    <div class="d-flex gap-2 justify-content-end">
                      <button type="button" class="ct-btn ct-btn-outline" @click="csvImportOpen=false">{{ __('Annuler') }}</button>
                      <button type="button" class="ct-btn ct-btn-primary" @click="doImportCsv()" :disabled="csvImporting || (!csvImportText && !csvImportFile)">
                        <span x-show="!csvImporting">{{ __('Importer et remplacer') }}</span>
                        <span x-show="csvImporting" x-cloak><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Import...') }}</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="table-responsive d-flex justify-content-center">
                  <table class="crossword-grid" :aria-label="'{{ __('Grille de mots croisés') }} ' + grid.rows + 'x' + grid.cols">
                    <tbody>
                      <template x-for="(row, rowIndex) in grid.cells" :key="rowIndex">
                        <tr>
                          <template x-for="(cell, colIndex) in row" :key="colIndex">
                            <td :class="cell !== null ? 'cell-active' : ('cell-inactive cell-inactive-' + inactiveStyle)" :aria-label="cell !== null ? ('{{ __('Case') }} ' + (rowIndex + 1) + '-' + (colIndex + 1) + (cell.number ? ', {{ __('numéro') }} ' + cell.number : '')) : '{{ __('Case noire') }}'">
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

                {{-- Suggestion regenerer si <100% mots places --}}
                <template x-if="unplaced.length > 0">
                  <div class="alert mt-4 d-flex align-items-start gap-3" role="status" style="background:#e0f2f1;border:1px solid #053d4a;color:#1A1D23">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#053d4a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <div style="flex:1;min-width:0">
                      <strong style="color:#053d4a" x-text="words.length + ' / ' + (words.length + unplaced.length) + ' {{ __('mots placés') }}'"></strong>
                      <div class="small mt-1">
                        <strong>{{ __('Mots non placés') }} :</strong> <span x-text="unplaced.map(u => u.answer).join(', ')"></span>
                      </div>
                      <div class="small mt-2">
                        💡 {{ __('Chaque génération est différente. Cliquez sur') }} <strong>« {{ __('Autre disposition') }} »</strong> {{ __('pour essayer un autre agencement et potentiellement placer plus de mots. Si certains restent toujours non placés, modifiez les mots/indices pour qu\'ils partagent des lettres communes.') }}
                      </div>
                      <button type="button" class="ct-btn ct-btn-primary mt-3 d-inline-flex align-items-center gap-2" style="min-height:44px" @click="regenerate()" :disabled="regenerating || generating">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                        <span x-text="regenerating ? '{{ __('Recalcul...') }}' : '{{ __('Essayer une autre disposition') }}'"></span>
                      </button>
                    </div>
                  </div>
                </template>
                <template x-if="unplaced.length === 0 && words.length > 0">
                  <div class="alert mt-4 d-flex align-items-center gap-3" role="status" style="background:#d1fae5;border:1px solid #065f46;color:#065f46">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#065f46" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                      <strong x-text="'{{ __('Parfait, tous les mots placés !') }} (' + words.length + '/' + words.length + ')'"></strong>
                    </div>
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
  .cw-guest-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2f1 100%);
    border: 1px solid #053d4a;
    border-radius: 12px;
    padding: 1.5rem 1.75rem;
    color: #1A1D23;
  }
  .cw-guest-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
  }
  .cw-guest-header h2 {
    color: #053d4a;
    font-weight: 700;
    flex: 1;
    min-width: 0;
  }
  .cw-guest-pill {
    background: #053d4a;
    color: #ffffff;
    font-size: .7rem;
    font-weight: 700;
    padding: .25rem .65rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .04em;
  }
  .cw-guest-intro {
    color: #1A1D23;
    margin-bottom: 1rem;
    font-size: .95rem;
  }
  .cw-guest-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1.25rem;
    display: grid;
    gap: .65rem;
  }
  .cw-guest-list li {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    color: #1A1D23;
    line-height: 1.45;
    font-size: .92rem;
  }
  .cw-guest-list li svg {
    flex-shrink: 0;
    margin-top: 2px;
  }
  .cw-guest-list li strong {
    color: #053d4a;
    font-weight: 700;
  }
  .cw-guest-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding-top: .75rem;
    border-top: 1px solid rgba(5, 61, 74, .15);
  }
  .cw-guest-cta {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    background: #053d4a;
    color: #ffffff;
    border: none;
    padding: .8rem 1.4rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: .95rem;
    cursor: pointer;
    min-height: 44px;
    transition: background .15s, transform .1s;
  }
  .cw-guest-cta:hover {
    background: #032327;
  }
  .cw-guest-cta:focus-visible {
    outline: 3px solid #1A1D23;
    outline-offset: 3px;
  }
  .cw-guest-cta:active {
    transform: scale(.98);
  }
  .cw-guest-note {
    color: #475569;
    font-size: .8rem;
  }
  @media (max-width: 576px) {
    .cw-guest-card { padding: 1.25rem 1rem; }
    .cw-guest-actions { flex-direction: column; align-items: stretch; }
    .cw-guest-cta { justify-content: center; }
  }
  .cw-menu-item {
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    padding: .6rem .75rem;
    background: none;
    border: none;
    text-align: left;
    color: #1A1D23;
    border-radius: 6px;
    cursor: pointer;
    min-height: 44px;
    width: 100%;
    font-size: .92rem;
  }
  .cw-menu-item svg { flex-shrink: 0; margin-top: 2px; color: #053d4a; }
  .cw-menu-item:hover, .cw-menu-item:focus-visible { background: #e0f2f1; outline: 2px solid #053d4a; outline-offset: -2px; }
  .cw-menu-item:disabled { opacity: .5; cursor: not-allowed; }
  .cw-menu-item:disabled:hover { background: none; outline: none; }
  .cw-menu-item strong { font-weight: 700; color: #053d4a; }
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
  .cell-inactive { border: 1.5px solid #1A1D23 !important; }
  .cell-inactive.cell-inactive-black { background-color: #1A1D23 !important; }
  .cell-inactive.cell-inactive-gray { background-color: #9ca3af !important; }
  .cell-inactive.cell-inactive-border { background-color: #ffffff !important; }
  /* Style options selecteur dans menu */
  .cw-style-opt { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .4rem .5rem; background: #f8fafc; border: 2px solid transparent; border-radius: 6px; cursor: pointer; min-width: 64px; min-height: 44px; font-size: .75rem; color: #1A1D23; font-weight: 600; }
  .cw-style-opt:hover { background: #e0f2f1; }
  .cw-style-opt.cw-style-active { border-color: #053d4a; background: #e0f2f1; color: #053d4a; }
  .cw-style-opt:focus-visible { outline: 2px solid #053d4a; outline-offset: 2px; }
  .cw-style-swatch { display: inline-block; width: 22px; height: 22px; border-radius: 4px; border: 1px solid #1A1D23; }
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
    csvImportOpen: false,
    csvImportText: '',
    csvImportFile: null,
    csvImporting: false,
    csvImportError: '',
    inactiveStyle: localStorage.getItem('cw_inactive_style') || 'black',
    generating: false,
    regenerating: false,
    saving: false,
    saveName: '',
    errors: {},
    generationError: null,
    suggestingPairs: false,
    suggestError: null,

    init() {
      // Preload depuis preset (?preset=publicId) si user authentifie redirige depuis /user/mots-croises
      const params = new URLSearchParams(window.location.search);
      const presetId = params.get('preset');
      if (presetId) {
        this.loadPreset(presetId);
        return;
      }
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

    async loadPreset(publicId) {
      try {
        const res = await fetch('/api/crossword-presets', {headers: {'Accept':'application/json'}});
        if (!res.ok) return;
        const data = await res.json();
        const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
        const preset = list.find(p => p.public_id === publicId);
        if (!preset) {
          this.dispatchToast("{{ __('Grille introuvable ou non autorisée.') }}", 'danger');
          return;
        }
        const lines = (preset.config_text || '').split(/\r\n|\n|\r/).map(l => l.trim()).filter(Boolean);
        const pairs = [];
        for (const line of lines) {
          const idx = line.indexOf(' / ');
          if (idx === -1) continue;
          pairs.push({clue: line.substring(0, idx).trim(), answer: line.substring(idx+3).trim().toUpperCase()});
        }
        if (pairs.length < 2) pairs.push({clue:'', answer:''}, {clue:'', answer:''});
        this.pairs = pairs;
        const params = preset.params || {};
        this.metadata = Object.assign({title: preset.name || '', difficulty: 'Moyen', is_public: !!preset.is_public, theme: ''}, params);
        this.saveName = preset.name || '';
        this.dispatchToast("{{ __('Grille chargée :') }} " + (preset.name || ''), 'success');
      } catch (e) {
        console.error('loadPreset', e);
      }
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

    _validPairs() {
      return this.pairs.filter(p => p.clue.trim() && p.answer.trim()).map(p => ({clue: p.clue.trim(), answer: p.answer.trim().toUpperCase()}));
    },

    async downloadPdfBlank() { return this._downloadPdf('pdf-blank', 'mots-croises-vierge.pdf'); },
    async downloadPdfSolution() { return this._downloadPdf('pdf-solution', 'mots-croises-corrige.pdf'); },

    async _downloadPdf(endpoint, filename) {
      if (!this.grid) return;
      const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
      const seed = this.gridStats?.seed || null;
      try {
        const res = await fetch('{{ url("/outils/mots-croises") }}/' + endpoint, {
          method: 'POST',
          headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/pdf'},
          body: JSON.stringify({pairs: this._validPairs(), seed: seed, title: this.metadata.title || 'Mots croises', inactive_style: this.inactiveStyle})
        });
        if (!res.ok) { this.dispatchToast("{{ __('Erreur lors de la generation du PDF.') }}", 'danger'); return; }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 200);
      } catch (e) { console.error(e); this.dispatchToast("{{ __('Erreur reseau PDF.') }}", 'danger'); }
    },

    async exportCsv() {
      const valid = this._validPairs();
      if (valid.length === 0) return;
      const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
      try {
        const res = await fetch('{{ route('tools.crossword.csv-export') }}', {
          method:'POST',
          headers:{'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'text/csv'},
          body: JSON.stringify({pairs: valid})
        });
        if (!res.ok) { this.dispatchToast("{{ __('Erreur export CSV.') }}", 'danger'); return; }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'mots-croises.csv';
        document.body.appendChild(a); a.click();
        setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 200);
      } catch (e) { console.error(e); }
    },

    openImportCsv() {
      this.csvImportText = '';
      this.csvImportFile = null;
      this.csvImportError = '';
      this.csvImportOpen = true;
    },

    async doImportCsv() {
      this.csvImporting = true;
      this.csvImportError = '';
      const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
      try {
        const fd = new FormData();
        if (this.csvImportFile) fd.append('file', this.csvImportFile);
        if (this.csvImportText.trim()) fd.append('csv', this.csvImportText);
        const res = await fetch('{{ route('tools.crossword.csv-import') }}', {
          method:'POST',
          headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json'},
          body: fd
        });
        const data = await res.json();
        if (res.ok && data.success && Array.isArray(data.pairs) && data.pairs.length >= 2) {
          this.pairs = data.pairs.map(p => ({clue: p.clue, answer: p.answer.toUpperCase()}));
          this.csvImportOpen = false;
          this.saveDraft();
          this.dispatchToast(data.count + " {{ __('paires importees avec succes.') }}", 'success');
        } else {
          this.csvImportError = data.error || "{{ __('Import echoue. Verifiez le format Indice;Mot.') }}";
        }
      } catch (e) {
        this.csvImportError = "{{ __('Erreur reseau.') }}";
      } finally {
        this.csvImporting = false;
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
            const playUrl = window.location.origin + '/jeumc/' + preset.public_id;
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
