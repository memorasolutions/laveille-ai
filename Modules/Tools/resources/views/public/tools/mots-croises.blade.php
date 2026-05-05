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
{{-- 2026-05-05 #106 : helper WCAG contrast global (window.WcagContrast.ratio) - DRY pour QR personnalisé + futurs outils --}}
@include('core::partials.wcag-contrast-helper')
<section class="wpo-blog-single-section section-padding">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-12">
        <div class="card shadow-sm" style="border-radius: var(--r-base);">
          <div class="card-body p-4 p-md-5" x-data="crosswordGenerator()" x-init="init()">
            <h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ $tool->name }}</h1>
            <p class="text-muted mb-2">{{ $tool->description }}</p>
            {{-- 2026-05-05 #94 : lien vers index publique --}}
            <p class="mb-4">
              <a href="{{ url('/jeumc') }}" class="d-inline-flex align-items-center gap-2" style="color:#053d4a;font-weight:600;text-decoration:none;padding:.4rem .75rem;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:6px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                {{ __('Voir toutes les grilles publiques') }}
              </a>
            </p>

            <div class="d-flex flex-wrap gap-2 mb-4 no-print">
              {{-- Bouton 'Imprimer' retire S79 #43 - remplace par export PDF dedie (vierge + corrige) dans le menu Plus d'options apres generation. --}}
              @auth
                <button type="button" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2" @click="save()" :disabled="saving" :class="!grid ? 'opacity-75' : ''" :title="!grid ? @js(__('Générez d\'abord la grille avant de sauvegarder')) : @js(__('Sauvegarder dans mon compte'))" aria-label="{{ __('Sauvegarder dans mon compte') }}">
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

            {{-- S80 #47 Onglets Bootstrap : reduit longueur percue (1176 lignes -> 2 onglets). FIRST-USE: extract to <x-core::tabs> if reused in 1 other tool (regle DRY user 2026-05-02 21:10). --}}
            <ul class="nav nav-tabs mb-3" role="tablist" style="border-bottom:2px solid #053d4a;flex-wrap:wrap">
              <li class="nav-item" role="presentation">
                <button type="button" role="tab" class="nav-link" :class="activeTab === 'config' ? 'active' : ''" @click="activeTab = 'config'; localStorage.setItem('cw_active_tab', 'config')" :aria-selected="activeTab === 'config'" style="font-weight:600;color:#053d4a;border-color:transparent;border-bottom:3px solid transparent" :style="activeTab === 'config' ? 'border-bottom-color:#053d4a !important;background:rgba(11,114,133,.08)' : ''">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>{{ __('Configuration & mots') }}</span>
                  </span>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button type="button" role="tab" class="nav-link" :class="activeTab === 'grille' ? 'active' : ''" @click="if(grid) { activeTab = 'grille'; localStorage.setItem('cw_active_tab', 'grille'); }" :disabled="!grid" :aria-selected="activeTab === 'grille'" :title="!grid ? @js(__('Générez d\'abord la grille pour activer cet onglet')) : ''" style="font-weight:600;color:#053d4a;border-color:transparent;border-bottom:3px solid transparent" :style="activeTab === 'grille' ? 'border-bottom-color:#053d4a !important;background:rgba(11,114,133,.08)' : (!grid ? 'opacity:.5;cursor:not-allowed' : '')">
                  <span class="d-inline-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
                    <span>{{ __('Grille générée') }}</span>
                    <template x-if="grid"><span class="badge" style="background:#053d4a;color:#fff;font-size:.65rem;padding:2px 6px;border-radius:999px" x-text="(words?.length || 0) + '/' + ((words?.length || 0) + (unplaced?.length || 0))"></span></template>
                  </span>
                </button>
              </li>
            </ul>

            <div class="tab-content">
            <div class="tab-pane fade" :class="activeTab === 'config' ? 'show active' : ''" role="tabpanel" x-show="activeTab === 'config'">

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
                        class="ct-btn d-inline-flex align-items-start gap-3 text-start w-100"
                        style="min-height:44px;padding:.75rem 1.25rem;max-width:100%;white-space:normal"
                        :class="metadata.is_public ? 'ct-btn-primary' : 'ct-btn-outline'"
                        @click="togglePublic()"
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
                    @php
                        $msgPublic = __('Lien partageable /jeumc/... actif - cliquez pour rendre privée.');
                        $msgPrivate = __('Génère un lien partageable /jeumc/... que les autres pourront jouer en ligne.');
                    @endphp
                    <span class="d-block small" style="color:inherit;opacity:.85;font-weight:400" x-data="{ msgPublic: {{ \Illuminate\Support\Js::from($msgPublic) }}, msgPrivate: {{ \Illuminate\Support\Js::from($msgPrivate) }} }" x-text="metadata.is_public ? msgPublic : msgPrivate"></span>
                  </span>
                </button>

                {{-- 2026-05-05 #101 : alerte doublon publique --}}
                <div x-show="duplicateInfo" x-cloak x-transition class="mt-3 p-3" role="alert" aria-live="polite" style="background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;color:#78350f">
                  <div class="d-flex align-items-start gap-2 mb-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:2px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div style="flex:1;min-width:0">
                      <strong>{{ __('Grille publique identique détectée') }}</strong>
                      <p class="small mb-2 mt-1" x-text="duplicateInfo?.message"></p>
                      <div class="d-flex flex-wrap gap-2">
                        <a x-show="duplicateInfo?.url" :href="duplicateInfo?.url" target="_blank" rel="noopener" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2" style="min-height:44px;font-size:.875rem">
                          <span>{{ __('Voir la grille existante') }}</span>
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                        <button type="button" @click="duplicateInfo = null" class="ct-btn ct-btn-outline" style="min-height:44px;font-size:.875rem" aria-label="{{ __('Fermer l\'alerte') }}">
                          {{ __('Modifier ma grille') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- A1+A2 (2026-05-05) : panneau lien public visible après save quand grille publique --}}
                <div x-show="publicShareUrl" x-cloak x-transition class="mt-3 p-3" style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <strong style="color:#047857">{{ __('Lien partageable de la grille') }}</strong>
                  </div>
                  <div class="d-flex flex-wrap gap-2 align-items-stretch mb-2">
                    <input type="text" readonly x-model="publicShareUrl" @click="$event.target.select()" class="form-control" style="flex:1;min-width:240px;background:#fff;font-family:monospace;font-size:.875rem" aria-label="{{ __('URL de partage du jeu') }}">
                    <button type="button" class="ct-btn ct-btn-primary d-inline-flex align-items-center justify-content-center gap-2" style="min-height:44px;min-width:44px" @click="copyShareLink()" :aria-label="shareLinkCopied ? '{{ __('Lien copié') }}' : '{{ __('Copier le lien') }}'">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                      <span x-text="shareLinkCopied ? '{{ __('Copié ✓') }}' : '{{ __('Copier') }}'"></span>
                    </button>
                    <a :href="publicShareUrl" target="_blank" rel="noopener" class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2" style="min-height:44px;min-width:44px" :aria-label="'{{ __('Ouvrir la grille dans un nouvel onglet') }}'">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      <span>{{ __('Ouvrir') }}</span>
                    </a>
                    <button type="button" x-show="canNativeShare" class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2" style="min-height:44px;min-width:44px" @click="nativeShare()" :aria-label="'{{ __('Partager la grille') }}'">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                      <span>{{ __('Partager') }}</span>
                    </button>
                  </div>
                  <details class="mt-2">
                    <summary style="cursor:pointer;color:#047857;font-weight:600;font-size:.875rem">{{ __('Afficher le QR code') }}</summary>
                    <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
                      <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=10&data=' + encodeURIComponent(publicShareUrl)" alt="{{ __('QR code de la grille') }}" loading="lazy" width="180" height="180" style="border:1px solid #d1fae5;border-radius:8px;background:#fff">
                      <p class="small mb-0" style="color:#047857;flex:1;min-width:200px">{{ __('Scannez ce QR code avec un téléphone pour ouvrir directement la grille en ligne. Idéal pour vos affiches en classe ou présentations.') }}</p>
                    </div>
                  </details>

                  {{-- 2026-05-05 #97 Phase 1 : édition lien personnalisé (custom_slug) --}}
                  <details class="mt-2" x-show="currentPresetPublicId">
                    <summary style="cursor:pointer;color:#047857;font-weight:600;font-size:.875rem">{{ __('Personnaliser le lien (slug)') }}</summary>
                    <div class="mt-2">
                      <p class="small mb-2" style="color:#047857">{{ __('Remplacez l\'identifiant aléatoire par un mot lisible (ex: ia-quebec). 3-50 caractères, minuscules, chiffres et tirets.') }}</p>
                      <div class="d-flex flex-wrap gap-2 align-items-stretch mb-2">
                        <span class="d-inline-flex align-items-center" style="color:#047857;font-family:monospace;font-size:.875rem">https://laveille.ai/jeumc/</span>
                        <input type="text"
                               x-model="customSlugInput"
                               @input="customSlugInput = $event.target.value.toLowerCase().replace(/[^a-z0-9-]/g,'').replace(/-{2,}/g,'-').slice(0,50); customSlugError = ''"
                               x-init="customSlugInput = currentCustomSlug"
                               x-effect="customSlugInput = currentCustomSlug"
                               class="form-control"
                               style="flex:1;min-width:180px;background:#fff;font-family:monospace;font-size:.875rem;min-height:44px"
                               maxlength="50"
                               placeholder="ex: ia-quebec"
                               aria-label="{{ __('Identifiant personnalisé du lien') }}">
                        <button type="button"
                                class="ct-btn ct-btn-primary d-inline-flex align-items-center justify-content-center gap-2"
                                style="min-height:44px"
                                :disabled="customSlugSaving"
                                @click="saveCustomSlug()"
                                :aria-label="customSlugSaving ? '{{ __('Enregistrement...') }}' : '{{ __('Enregistrer le lien personnalisé') }}'">
                          <span x-text="customSlugSaving ? '{{ __('Enregistrement...') }}' : '{{ __('Enregistrer') }}'"></span>
                        </button>
                        <button type="button"
                                x-show="currentCustomSlug"
                                class="ct-btn ct-btn-outline"
                                style="min-height:44px"
                                @click="customSlugInput = ''; saveCustomSlug()"
                                :aria-label="'{{ __('Retirer le lien personnalisé') }}'">
                          <span>{{ __('Retirer') }}</span>
                        </button>
                      </div>
                      <p x-show="customSlugError" x-cloak class="small mb-0" style="color:#b91c1c" x-text="customSlugError" role="alert"></p>
                      <p class="small mb-0" style="color:#047857;opacity:.8">
                        {{ __('Mots réservés interdits : index, admin, api, nouveau, creer, mes-grilles, populaires, recents, themes, share, qr, edit.') }}
                      </p>
                    </div>
                  </details>

                  {{-- 2026-05-05 #97 Phase 2 : personnalisation QR code (couleurs, logo, ECC, dot style) --}}
                  <details class="mt-2" x-show="currentPresetPublicId">
                    <summary style="cursor:pointer;color:#047857;font-weight:600;font-size:.875rem">{{ __('Personnaliser le QR code') }}</summary>
                    <div class="mt-3 d-flex flex-wrap gap-3 align-items-start">
                      <div class="d-flex flex-column align-items-center" style="flex:0 0 auto">
                        <img :src="'/jeumc/' + (currentCustomSlug || currentPresetPublicId) + '/qr.png?fg=' + qrFg.replace('#','') + '&bg=' + qrBg.replace('#','') + '&ecc=' + qrEcc + '&style=' + qrDotStyle + '&logo=' + (qrIncludeLogo ? '1' : '0') + '&size=240&_b=' + qrPreviewBust"
                             alt="{{ __('Aperçu QR code personnalisé') }}" width="240" height="240"
                             style="border:1px solid #d1fae5;border-radius:8px;background:#fff;min-width:240px;min-height:240px"
                             loading="lazy">
                        <div class="d-flex gap-2 mt-2 flex-wrap" style="max-width:240px">
                          <a :href="'/jeumc/' + (currentCustomSlug || currentPresetPublicId) + '/qr.png?fg=' + qrFg.replace('#','') + '&bg=' + qrBg.replace('#','') + '&ecc=' + qrEcc + '&style=' + qrDotStyle + '&logo=' + (qrIncludeLogo ? '1' : '0') + '&size=600&download=1'"
                             class="ct-btn ct-btn-primary d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1"
                             style="min-height:44px"
                             download
                             aria-label="{{ __('Télécharger le QR code en PNG haute résolution') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <span>{{ __('Télécharger') }}</span>
                          </a>
                          <button type="button" :disabled="qrSaving" @click="saveQrOptions()" class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" style="min-height:44px" :aria-label="qrSaving ? '{{ __('Enregistrement...') }}' : '{{ __('Enregistrer ces options QR comme défaut') }}'">
                            <span x-text="qrSaving ? '{{ __('Enreg...') }}' : '{{ __('Enregistrer') }}'"></span>
                          </button>
                        </div>
                      </div>

                      <div style="flex:1;min-width:240px">
                        <label class="form-label small fw-bold" style="color:#047857;text-transform:uppercase;letter-spacing:.4px;font-size:.7rem">{{ __('Style prédéfini') }}</label>
                        <div class="d-flex gap-2 flex-wrap mb-3">
                          <button type="button" @click="qrFg='#0B7285'; qrBg='#FFFFFF'" :class="{'ct-btn-primary': qrFg==='#0B7285' && qrBg==='#FFFFFF', 'ct-btn-outline': !(qrFg==='#0B7285' && qrBg==='#FFFFFF')}" class="ct-btn d-inline-flex align-items-center gap-2" style="min-height:36px;padding:.4rem .75rem;font-size:.8rem" aria-label="{{ __('Preset Teal') }}"><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:#0B7285;border:1px solid #fff"></span>Teal</button>
                          <button type="button" @click="qrFg='#1A1D23'; qrBg='#FFFFFF'" :class="{'ct-btn-primary': qrFg==='#1A1D23' && qrBg==='#FFFFFF', 'ct-btn-outline': !(qrFg==='#1A1D23' && qrBg==='#FFFFFF')}" class="ct-btn d-inline-flex align-items-center gap-2" style="min-height:36px;padding:.4rem .75rem;font-size:.8rem" aria-label="{{ __('Preset Noir') }}"><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:#1A1D23;border:1px solid #fff"></span>Noir</button>
                          <button type="button" @click="qrFg='#C2410C'; qrBg='#FFFFFF'" :class="{'ct-btn-primary': qrFg==='#C2410C' && qrBg==='#FFFFFF', 'ct-btn-outline': !(qrFg==='#C2410C' && qrBg==='#FFFFFF')}" class="ct-btn d-inline-flex align-items-center gap-2" style="min-height:36px;padding:.4rem .75rem;font-size:.8rem" aria-label="{{ __('Preset Orange') }}"><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:#C2410C;border:1px solid #fff"></span>Orange</button>
                          <button type="button" @click="qrFg='#FFFFFF'; qrBg='#064E5C'" :class="{'ct-btn-primary': qrFg==='#FFFFFF' && qrBg==='#064E5C', 'ct-btn-outline': !(qrFg==='#FFFFFF' && qrBg==='#064E5C')}" class="ct-btn d-inline-flex align-items-center gap-2" style="min-height:36px;padding:.4rem .75rem;font-size:.8rem" aria-label="{{ __('Preset Inversé') }}"><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:#064E5C;border:1px solid #fff"></span>Inversé</button>
                        </div>

                        <div class="row g-2 mb-3">
                          <div class="col-6">
                            <label for="qr-fg" class="form-label small fw-bold" style="color:#047857;text-transform:uppercase;letter-spacing:.4px;font-size:.7rem">{{ __('Couleur QR') }}</label>
                            <input type="color" id="qr-fg" x-model="qrFg" class="form-control form-control-color" style="min-height:44px;width:100%" aria-label="{{ __('Couleur du QR code') }}">
                          </div>
                          <div class="col-6">
                            <label for="qr-bg" class="form-label small fw-bold" style="color:#047857;text-transform:uppercase;letter-spacing:.4px;font-size:.7rem">{{ __('Fond') }}</label>
                            <input type="color" id="qr-bg" x-model="qrBg" class="form-control form-control-color" style="min-height:44px;width:100%" aria-label="{{ __('Couleur de fond du QR code') }}">
                          </div>
                        </div>

                        {{-- 2026-05-05 #106 : utilise window.WcagContrast.ratio() depuis core::partials.wcag-contrast-helper (DRY) --}}
                        <div class="mb-3">
                          <span class="small fw-bold" :style="(window.WcagContrast?.ratio(qrFg, qrBg) ?? 1) >= 7 ? 'color:#047857' : ((window.WcagContrast?.ratio(qrFg, qrBg) ?? 1) >= 4.5 ? 'color:#92400e' : 'color:#b91c1c')">
                            {{ __('Contraste') }} : <span x-text="(window.WcagContrast?.ratio(qrFg, qrBg) ?? 1)"></span>:1
                            <span x-show="(window.WcagContrast?.ratio(qrFg, qrBg) ?? 1) >= 7" x-cloak>{{ __('AAA ✓') }}</span>
                            <span x-show="(window.WcagContrast?.ratio(qrFg, qrBg) ?? 1) >= 4.5 && (window.WcagContrast?.ratio(qrFg, qrBg) ?? 1) < 7" x-cloak>{{ __('AA ⚠') }}</span>
                            <span x-show="(window.WcagContrast?.ratio(qrFg, qrBg) ?? 1) < 4.5" x-cloak>{{ __('FAIL ✗ — QR illisible') }}</span>
                          </span>
                        </div>

                        <div class="mb-3">
                          <label for="qr-style" class="form-label small fw-bold" style="color:#047857;text-transform:uppercase;letter-spacing:.4px;font-size:.7rem">{{ __('Forme des modules') }}</label>
                          <select id="qr-style" x-model="qrDotStyle" class="form-select" style="min-height:44px" aria-label="{{ __('Forme des modules QR') }}">
                            <option value="square">{{ __('Carré (classique)') }}</option>
                            <option value="rounded">{{ __('Arrondi') }}</option>
                            <option value="dots">{{ __('Points') }}</option>
                          </select>
                        </div>

                        <div class="mb-3">
                          <label for="qr-ecc" class="form-label small fw-bold" style="color:#047857;text-transform:uppercase;letter-spacing:.4px;font-size:.7rem">{{ __('Niveau de correction') }}</label>
                          <select id="qr-ecc" x-model="qrEcc" class="form-select" style="min-height:44px" aria-label="{{ __('Niveau de correction d\'erreur ECC') }}">
                            <option value="L">{{ __('Bas (L) - 7%') }}</option>
                            <option value="M">{{ __('Moyen (M) - 15%') }}</option>
                            <option value="Q">{{ __('Élevé (Q) - 25%') }}</option>
                            <option value="H">{{ __('Maximum (H) - 30%') }}</option>
                          </select>
                          <p class="form-text small mb-0" style="color:#047857;opacity:.8">{{ __('Plus élevé = QR plus dense mais plus tolérant aux dommages.') }}</p>
                        </div>

                        <div class="form-check mb-2" style="min-height:44px;display:flex;align-items:center">
                          <input type="checkbox" id="qr-logo" x-model="qrIncludeLogo" @change="qrIncludeLogo && (qrEcc='Q')" class="form-check-input" style="margin-right:.5rem">
                          <label for="qr-logo" class="form-check-label small" style="color:#047857;font-weight:500">{{ __('Inclure logo laveille.ai au centre') }}</label>
                        </div>
                        <p x-show="qrIncludeLogo" x-cloak class="form-text small mb-0" style="color:#047857;opacity:.8">{{ __('Niveau de correction ECC Q (25%) auto-activé pour compenser l\'espace du logo.') }}</p>
                      </div>
                    </div>
                  </details>
                </div>
              </div>
              @endauth
            </div>

            {{-- S81 #65 Form-to-Prompt BYO-AI : générer prompt zero-shot CoT pour copier dans son IA → user récupère CSV → import via menu Données CSV --}}
            <div class="mb-4">
              <button type="button" class="ct-btn ct-btn-outline w-100 d-flex align-items-center justify-content-between" style="min-height:44px" @click="aiBuilderOpen = !aiBuilderOpen" :aria-expanded="aiBuilderOpen" aria-controls="ai-builder-panel">
                <span class="d-inline-flex align-items-center gap-2">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2L9.5 9 2 12l7.5 3 2.5 7 2.5-7 7.5-3-7.5-3z"/></svg>
                  <span><strong>{{ __('Générer mes mots avec mon IA') }}</strong> <span class="d-block d-sm-inline small" style="opacity:.75;font-weight:400">— {{ __('100 % gratuit, utilise votre ChatGPT, Claude ou Gemini') }}</span></span>
                </span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="aiBuilderOpen ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div x-show="aiBuilderOpen" x-cloak x-transition id="ai-builder-panel" class="card mt-2" style="border-radius:var(--r-base);border:1px solid #e2e8f0">
                <div class="card-body p-3 p-md-4">
                  <p class="small mb-3" style="color:#475569">
                    {{ __('Remplissez le formulaire ci-dessous pour générer un prompt sur mesure. Copiez-le, collez-le dans votre IA préférée (ChatGPT, Claude, Gemini, etc.). Votre IA vous renverra un fichier CSV. Collez-le ensuite dans le bouton ') }}<strong>{{ __('Données CSV → Importer CSV') }}</strong>{{ __(' plus bas.') }}
                  </p>
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label for="aiTheme" class="form-label fw-medium">{{ __('Thème') }} <span style="color:#DC2626">*</span></label>
                      <input type="text" id="aiTheme" class="form-control" x-model="aiTheme" placeholder="{{ __('Ex: Capitales du monde, Vocabulaire IA, Histoire du Québec') }}" maxlength="120">
                    </div>
                    <div class="col-md-3 col-6">
                      <label for="aiNbWords" class="form-label fw-medium">{{ __('Nombre de mots') }}</label>
                      <input type="number" id="aiNbWords" class="form-control" x-model.number="aiNbWords" min="5" max="50" step="1">
                    </div>
                    <div class="col-md-3 col-6">
                      <label for="aiLevel" class="form-label fw-medium">{{ __('Niveau') }}</label>
                      <select id="aiLevel" class="form-control" x-model="aiLevel">
                        <option value="primaire">{{ __('Primaire') }}</option>
                        <option value="secondaire">{{ __('Secondaire') }}</option>
                        <option value="adulte" selected>{{ __('Adulte') }}</option>
                        <option value="expert">{{ __('Expert / spécialiste') }}</option>
                      </select>
                    </div>
                    <div class="col-md-4 col-6">
                      <label for="aiLang" class="form-label fw-medium">{{ __('Langue') }}</label>
                      <select id="aiLang" class="form-control" x-model="aiLang">
                        <option value="fr" selected>{{ __('Français') }}</option>
                        <option value="en">{{ __('Anglais') }}</option>
                        <option value="es">{{ __('Espagnol') }}</option>
                      </select>
                    </div>
                    <div class="col-md-4 col-6">
                      <label for="aiClueStyle" class="form-label fw-medium">{{ __('Style des indices') }}</label>
                      <select id="aiClueStyle" class="form-control" x-model="aiClueStyle">
                        <option value="court">{{ __('Court (1-5 mots)') }}</option>
                        <option value="definition" selected>{{ __('Définition (~10 mots)') }}</option>
                        <option value="long">{{ __('Long et descriptif') }}</option>
                        <option value="devinette">{{ __('Devinette créative') }}</option>
                      </select>
                    </div>
                    <div class="col-md-4 col-12">
                      <label for="aiTarget" class="form-label fw-medium">{{ __('Cible (optionnel)') }}</label>
                      <input type="text" id="aiTarget" class="form-control" x-model="aiTarget" placeholder="{{ __('Ex: élèves Quebec, profs, grand public') }}" maxlength="100">
                    </div>
                  </div>
                  <label for="aiPromptOutput" class="form-label fw-medium d-flex justify-content-between align-items-center">
                    <span>{{ __('Prompt généré pour votre IA') }}</span>
                    <small style="color:#475569;font-weight:400" x-text="aiPromptText().length + ' {{ __('caractères') }}'"></small>
                  </label>
                  <textarea id="aiPromptOutput" class="form-control" rows="10" readonly x-text="aiPromptText()" style="font-family:monospace;font-size:.82rem;background:#F8FAFB;line-height:1.5"></textarea>
                  <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                    <button type="button" class="ct-btn ct-btn-accent flex-fill d-inline-flex align-items-center justify-content-center gap-2" @click="copyAiPrompt()" style="min-height:44px" :disabled="!aiTheme.trim()">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                      <span x-text="aiPromptCopied ? '{{ __('Copié ✓') }}' : '{{ __('Copier le prompt') }}'"></span>
                    </button>
                    <a class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2" :href="aiChatGPTUrl()" target="_blank" rel="noopener" style="min-height:44px" :class="!aiTheme.trim() ? 'opacity-50' : ''">
                      <span aria-hidden="true">↗</span>
                      <span>{{ __('Ouvrir ChatGPT') }}</span>
                    </a>
                    <a class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2" :href="aiClaudeUrl()" target="_blank" rel="noopener" style="min-height:44px" :class="!aiTheme.trim() ? 'opacity-50' : ''">
                      <span aria-hidden="true">↗</span>
                      <span>{{ __('Ouvrir Claude') }}</span>
                    </a>
                    <a class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2" :href="aiPerplexityUrl()" target="_blank" rel="noopener" style="min-height:44px" :class="!aiTheme.trim() ? 'opacity-50' : ''">
                      <span aria-hidden="true">↗</span>
                      <span>{{ __('Ouvrir Perplexity') }}</span>
                    </a>
                    {{-- S81 #67 (re-confirmé 2026-05-05) : bouton 'Ouvrir Gemini' retiré — deeplink ?q= toujours non supporté par Google (test live : textbox reste vide, aucune recherche déclenchée). User Gemini : copier le prompt puis coller manuellement. --}}
                  </div>
                  <p class="small mt-3 mb-0" style="color:#475569">
                    <strong>{{ __('Étape suivante') }}</strong> : {{ __('une fois votre IA vous a renvoyé le CSV, copiez-le, allez dans le bouton ') }}<strong>{{ __('Données CSV') }}</strong>{{ __(' plus bas, cliquez ') }}<strong>{{ __('Importer CSV') }}</strong>{{ __(' et collez le contenu dans la zone de texte.') }}
                  </p>
                  <p class="small mt-2 mb-0" style="color:#475569;font-style:italic">
                    {{ __('Vous préférez Gemini ?') }} {{ __('Copiez le prompt avec le bouton ci-dessus, puis collez-le dans') }} <a href="https://gemini.google.com/app" target="_blank" rel="noopener" style="color:#053d4a;font-weight:600">gemini.google.com</a>.
                  </p>
                </div>
              </div>
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
                    <button type="button" class="crossword-pair-delete" @click="removePair(index)" x-show="pairs.length > 2" :aria-label="'{{ __('Supprimer le mot') }} ' + (index + 1)" :title="'{{ __('Supprimer le mot') }} ' + (index + 1)">
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
                {{-- S81 #68 : bouton reset paires + confirm modal Memora (règle anti-popup native #41) --}}
                <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" @click="resetPairs()" aria-label="{{ __('Tout effacer les mots') }}" :disabled="!hasAnyPairContent()" :class="!hasAnyPairContent() ? 'opacity-50' : ''">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  <span>{{ __('Tout effacer') }}</span>
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

            {{-- Bouton générer (S81 #69 : texte adaptatif + cliquable même si <2 mots → toast clair) --}}
            <div class="d-grid gap-2 mb-4">
              {{-- S81 #70 : style adaptatif outline+cadenas si !canGenerate (sonar-pro 95/100) — pleine couleur si OK --}}
              <button type="button" class="ct-btn ct-btn-lg d-inline-flex align-items-center justify-content-center gap-2" :class="canGenerate() ? 'ct-btn-primary' : 'ct-btn-locked'" @click="generate()" :disabled="generating" :aria-label="generateBtnLabel()" :aria-disabled="!canGenerate()" :style="!canGenerate() ? 'cursor:not-allowed' : ''">
                <template x-if="!generating">
                  <span class="d-inline-flex align-items-center gap-2">
                    <template x-if="canGenerate()">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3l1.9 4.6L18.5 9l-4.6 1.9L12 15.5l-1.9-4.6L5.5 9l4.6-1.4z"/><path d="M19 14l.7 2.3L22 17l-2.3.7L19 20l-.7-2.3L16 17l2.3-.7z"/><path d="M5 17l.5 1.5L7 19l-1.5.5L5 21l-.5-1.5L3 19l1.5-.5z"/></svg>
                    </template>
                    <template x-if="!canGenerate()">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </template>
                    <span x-text="generateBtnLabel()"></span>
                  </span>
                </template>
                <template x-if="generating">
                  <span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>{{ __('Génération en cours...') }}</span></span>
                </template>
              </button>
            </div>

            {{-- Erreur génération --}}
            <div x-show="generationError" x-cloak class="alert alert-danger mb-4" role="alert">
              <strong>{{ __('Erreur') }}:</strong> <span x-text="generationError"></span>
            </div>

            </div>{{-- /tab-pane config --}}

            {{-- Modal Import CSV (HORS template x-if grid : doit exister DOM avant 1re génération) - S80 #46 utilise .popup-overlay global (display:grid place-items:center robuste vs flex inline cassé par x-show) --}}
            <div x-show="csvImportOpen" x-cloak x-transition.opacity @click.self="csvImportOpen=false" @keydown.escape.window="csvImportOpen=false" role="dialog" aria-modal="true" aria-labelledby="csv-import-title" class="popup-overlay">
              <div class="popup-content">
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

            <div class="tab-pane fade" :class="activeTab === 'grille' ? 'show active' : ''" role="tabpanel" x-show="activeTab === 'grille'">

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

                {{-- S81 #65 WCAG 2.2 AAA : role=grid + aria-rowindex/colindex + nav clavier flèches + focus visible --}}
                <div class="table-responsive d-flex justify-content-center cw-grid-wrap-editor" :style="`--cols: ${grid.cols};`">
                  <table class="crossword-grid" role="grid" :style="`--cols: ${grid.cols}; --rows: ${grid.rows};`" :aria-label="'{{ __('Grille de mots croisés') }} ' + grid.rows + ' {{ __('lignes par') }} ' + grid.cols + ' {{ __('colonnes') }}'" :aria-rowcount="grid.rows" :aria-colcount="grid.cols">
                    <caption class="visually-hidden">{{ __('Grille interactive de mots croisés. Utilisez les touches fléchées pour naviguer entre les cases.') }}</caption>
                    <tbody>
                      <template x-for="(row, rowIndex) in grid.cells" :key="rowIndex">
                        <tr role="row" :aria-rowindex="rowIndex + 1">
                          <template x-for="(cell, colIndex) in row" :key="colIndex">
                            <td role="gridcell"
                                :data-cell-row="rowIndex"
                                :data-cell-col="colIndex"
                                :tabindex="(rowIndex === gridFocusRow && colIndex === gridFocusCol) ? 0 : -1"
                                :aria-colindex="colIndex + 1"
                                :class="cell !== null ? 'cell-active' : ('cell-inactive cell-inactive-' + inactiveStyle)"
                                :aria-label="cell !== null ? ('{{ __('Case active ligne') }} ' + (rowIndex + 1) + ' {{ __('colonne') }} ' + (colIndex + 1) + (cell.number ? ', {{ __('départ du mot numéro') }} ' + cell.number : '') + (showSolutions && cell.letter ? ', {{ __('lettre') }} ' + cell.letter : '')) : ('{{ __('Case inactive ligne') }} ' + (rowIndex + 1) + ' {{ __('colonne') }} ' + (colIndex + 1))"
                                @keydown="gridKeyNav(rowIndex, colIndex, $event)"
                                @focus="gridFocusRow = rowIndex; gridFocusCol = colIndex">
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

            </div>{{-- /tab-pane grille --}}
            </div>{{-- /tab-content --}}

            {{-- Brouillon --}}
            <div class="mt-4 pt-3 border-top no-print">
              <small class="text-muted d-inline-flex align-items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                {{ __('Brouillon sauvegardé dans votre navigateur.') }}
                <button type="button" class="btn btn-sm btn-link p-0 ms-2" @click="$dispatch('open-confirm-global', { message: @js(__('Effacer définitivement le brouillon ?')), callback: () => clearDraft() })">{{ __('Effacer le brouillon') }}</button>
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
    padding: .35rem .75rem;
    border-radius: 999px;
    text-transform: uppercase;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
  /* S80 #55 — Option #3 Hybride : bordure externe grille + bordures cases actives uniquement (sauf mode BLACK statu quo). POTENTIAL-EXTRACT: dupliqué dans pdf-blank.blade.php + pdf-solution.blade.php — extraire en partial _grid_styles.blade.php S81 si stable. */
  /* 2026-05-05 #100 : grille éditeur - clamp largeur uniquement (hauteur = scroll OK pour création) */
  .cw-grid-wrap-editor { --cols: 10; max-width: 100%; overflow-x: auto; }
  .cw-grid-wrap-editor .crossword-grid { --cell: clamp(20px, calc((100vw - 80px) / var(--cols)), 36px); }
  .crossword-grid {
    table-layout: fixed;
    border-collapse: collapse;
    margin: 1rem auto;
  }
  .crossword-grid td {
    width: var(--cell, 36px);
    height: var(--cell, 36px);
    min-width: 18px;
    min-height: 18px;
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
  /* Cases inactives : pas de bordure par défaut (mode gray + border = îlots), exception mode BLACK statu quo */
  .cell-inactive.cell-inactive-black { background-color: #1A1D23 !important; border: 1.5px solid #1A1D23 !important; }
  .cell-inactive.cell-inactive-gray { background-color: #9ca3af !important; border: 0 !important; }
  .cell-inactive.cell-inactive-border { background-color: #ffffff !important; border: 0 !important; }
  /* S81 #65 WCAG 2.2 AAA focus visible : outline 3px contraste élevé */
  .crossword-grid td:focus { outline: 3px solid #C2410C !important; outline-offset: 2px !important; z-index: 5; position: relative; }
  .crossword-grid td:focus-visible { outline: 3px solid #C2410C !important; outline-offset: 2px !important; }
  .visually-hidden { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0,0,0,0) !important; white-space: nowrap !important; border: 0 !important; }
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
    activeTab: 'config', // S80 #47 onglets : 'config' ou 'grille' (grille auto-switch apres generate)

    // S81 #65 Form-to-Prompt BYO-AI : générer prompt zero-shot CoT pour IA externe (zéro coût backend)
    aiBuilderOpen: false,
    aiTheme: '',
    aiNbWords: 12,
    aiLevel: 'adulte',
    aiLang: 'fr',
    aiClueStyle: 'definition',
    aiTarget: '',
    aiPromptCopied: false,

    // 2026-05-05 A1+A2 : panneau lien public après save
    publicShareUrl: '',
    currentPresetPublicId: '', // 2026-05-05 #97 Phase 1 : id pour POST update slug
    currentCustomSlug: '',     // 2026-05-05 #97 Phase 1 : valeur actuelle slug custom
    customSlugInput: '',       // 2026-05-05 #97 Phase 1 : modèle pour formulaire édition slug
    customSlugSaving: false,
    customSlugError: '',
    // 2026-05-05 #97 Phase 2 : QR personnalisation
    qrFg: '#0B7285',
    qrBg: '#FFFFFF',
    qrEcc: 'M',
    qrDotStyle: 'square',
    qrIncludeLogo: false,
    qrSaving: false,
    qrPreviewBust: 0,
    // 2026-05-05 #101 : anti-doublons grille publique
    duplicateInfo: null,
    shareLinkCopied: false,
    canNativeShare: typeof navigator !== 'undefined' && typeof navigator.share === 'function',

    // S81 #65 WCAG AAA grille : focus position pour navigation clavier flèches
    gridFocusRow: 0,
    gridFocusCol: 0,

    init() {
      // S80 #47 onglets : restaurer dernier onglet, mais forcer 'config' si pas de grille (sera basculer auto post-generate)
      this.activeTab = localStorage.getItem('cw_active_tab') || 'config';
      if (!this.grid) this.activeTab = 'config';
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
            // Auto-restore silencieux du brouillon (regle anti-popup native S79 #41).
            // L'utilisateur peut effacer via le bouton 'Effacer le brouillon' visible.
            this.pairs = data.pairs && data.pairs.length >= 2 ? data.pairs : [{clue: '', answer: ''}, {clue: '', answer: ''}];
            this.metadata = Object.assign({title: '', difficulty: 'Moyen', is_public: false, theme: ''}, data.metadata || {});
            this.saveName = data.saveName || '';
            // Guard contre double dispatch (S80 #59 : init() peut être appelé 2× par Alpine si x-show réinitialise le composant)
            if (!window._cwDraftToastShown) {
              window._cwDraftToastShown = true;
              setTimeout(() => {
                window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: @json(__('Brouillon de votre dernier essai restauré.')), variant: 'info', duration: 4000 }}));
              }, 600);
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

    // S81 #68 : true si au moins une paire a contenu (pour activer bouton "Tout effacer")
    hasAnyPairContent() {
      return (this.pairs || []).some(p => (p.clue || '').trim() || (p.answer || '').trim());
    },

    // S81 #68 : reset toutes les paires avec confirmation modale Memora (règle anti-popup native #41)
    resetPairs() {
      if (!this.hasAnyPairContent()) return;
      this.$dispatch('open-confirm-global', {
        message: @js(__('Effacer toutes les paires saisies ? Cette action est irréversible.')),
        callback: () => {
          this.pairs = [{clue: '', answer: ''}, {clue: '', answer: ''}];
          this.errors = {};
          this.saveDraft();
          this.dispatchToast(@js(__('Toutes les paires ont été effacées.')), 'info');
        }
      });
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
        // 2026-05-05 #100 fix : si grille publique chargée, set publicShareUrl + currentPresetPublicId pour afficher panneau Lien+QR+slug+QR-custom
        if (preset.is_public && preset.public_id) {
          const identifier = preset.custom_slug || preset.public_id;
          this.publicShareUrl = window.location.origin + '/jeumc/' + identifier;
          this.currentPresetPublicId = preset.public_id;
          this.currentCustomSlug = preset.custom_slug || '';
          // Pré-remplir options QR depuis qr_options DB
          if (preset.qr_options && typeof preset.qr_options === 'object') {
            this.qrFg = preset.qr_options.foreground || this.qrFg;
            this.qrBg = preset.qr_options.background || this.qrBg;
            this.qrEcc = preset.qr_options.ecc || this.qrEcc;
            this.qrDotStyle = preset.qr_options.dot_style || this.qrDotStyle;
            this.qrIncludeLogo = preset.qr_options.logo === '1' || preset.qr_options.logo === true;
          }
        } else {
          this.publicShareUrl = '';
          this.currentPresetPublicId = '';
          this.currentCustomSlug = '';
        }
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

    // S81 #69 : compte les paires valides (clue + answer 2+ lettres alpha)
    validPairsCount() {
      return this.pairs.filter(p => p.clue && p.clue.trim() && p.answer && p.answer.trim().length >= 2 && /^[a-zA-ZàâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ]+$/.test(p.answer.trim())).length;
    },

    canGenerate() {
      return this.validPairsCount() >= 2 && Object.keys(this.errors).length === 0;
    },

    // S81 #69 : label adaptatif sur bouton Générer (UX 2026 inclusive — explique pourquoi disabled directement sur le bouton)
    generateBtnLabel() {
      const valid = this.validPairsCount();
      if (valid >= 2 && Object.keys(this.errors).length === 0) {
        return @js(__('Générer la grille'));
      }
      if (Object.keys(this.errors).length > 0) {
        return @js(__('Corrigez les erreurs avant de générer'));
      }
      const missing = 2 - valid;
      return missing === 1
        ? @js(__('Encore 1 mot valide à saisir'))
        : @js(__('Saisissez 2 mots valides minimum'));
    },

    // S80 cleanup : suggestPairs() retirée (bouton UI retiré S79, dead code orphelin)

    async generate(seed = null, isRegenerate = false) {
      // S81 #69 : si pas valide, toast clair au lieu de silent return
      if (!this.canGenerate()) {
        const valid = this.validPairsCount();
        if (Object.keys(this.errors).length > 0) {
          this.dispatchToast(@js(__('Corrigez les erreurs (champs en rouge) avant de générer la grille.')), 'warning', 5000);
        } else {
          const missing = 2 - valid;
          const msg = missing === 1
            ? @js(__('Il manque 1 mot valide pour générer la grille (au moins 2 requis).'))
            : @js(__('Il faut au moins 2 mots valides pour générer la grille. Saisissez vos mots dans les champs ci-dessus.'));
          this.dispatchToast(msg, 'warning', 5500);
        }
        return;
      }
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
          // S80 #47 onglets : auto-switch vers 'grille' apres generation reussie
          this.activeTab = 'grille';
          try { localStorage.setItem('cw_active_tab', 'grille'); } catch (_) {}
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
          // X-Requested-With force Laravel expectsJson() = true → ValidationException retourne JSON 422 au lieu de redirect 302 back (#51 root cause)
          headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/pdf, application/json', 'X-Requested-With': 'XMLHttpRequest'},
          credentials: 'same-origin',
          body: JSON.stringify({pairs: this._validPairs(), seed: seed, title: this.metadata.title || '', inactive_style: this.inactiveStyle})
        });
        if (!res.ok) {
          let msg = "{{ __('Erreur lors de la génération du PDF.') }}";
          try { const j = await res.json(); if (j.error) msg = j.error; } catch(_){}
          this.dispatchToast(msg, 'danger'); return;
        }
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/pdf')) {
          this.dispatchToast("{{ __('Réponse serveur invalide (pas un PDF). Rechargez la page (Ctrl+Shift+R).') }}", 'danger');
          console.error('PDF download: unexpected content-type', ct);
          return;
        }
        // Filename depuis Content-Disposition serveur (#52), fallback sur filename param
        const cd = res.headers.get('content-disposition') || '';
        const m = cd.match(/filename="?([^";]+)"?/i);
        const finalName = m ? m[1] : filename;
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = finalName;
        document.body.appendChild(a); a.click();
        setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 200);
      } catch (e) { console.error(e); this.dispatchToast("{{ __('Erreur réseau PDF.') }}", 'danger'); }
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

    // 2026-05-05 #105 : toggle is_public avec auto-save si conditions remplies (sinon message clair).
    async togglePublic() {
      const wasPublic = !!this.metadata.is_public;
      this.metadata.is_public = !wasPublic;
      this.saveDraft();
      // Si l'utilisateur tente de rendre publique...
      if (this.metadata.is_public) {
        if (!this.isAuthenticated) {
          this.metadata.is_public = false; // revert
          this.dispatchToast("{{ __('Connectez-vous pour publier votre grille.') }}", 'warning');
          this.$dispatch('open-auth-modal');
          return;
        }
        if (!this.grid) {
          this.metadata.is_public = false; // revert
          this.dispatchToast("{{ __('Générez d\'abord la grille (cliquez « Générer la grille ») puis activez le partage public.') }}", 'warning', 6000);
          return;
        }
        const name = (this.saveName || '').trim() || (this.metadata.title || '').trim();
        if (!name) {
          this.metadata.is_public = false; // revert
          this.dispatchToast("{{ __('Donnez un nom à votre grille avant de la publier.') }}", 'warning', 6000);
          this.$nextTick(() => {
            const el = document.querySelector('input[aria-label="{{ __('Titre du prompt') }}"], input[x-model="saveName"]');
            if (el) { el.focus(); el.scrollIntoView({behavior:'smooth', block:'center'}); }
          });
          return;
        }
        // Conditions remplies : auto-save pour persister + récupérer publicShareUrl + afficher panneau.
        await this.save();
      } else if (wasPublic && this.currentPresetPublicId) {
        // Passage à privée d'une grille déjà publique : auto-save pour persister.
        await this.save();
        this.dispatchToast("{{ __('Grille passée en privée. Le lien public est désactivé.') }}", 'info');
      }
    },

    async save() {
      // Validation explicite avec messages clairs (#54)
      if (!this.isAuthenticated) {
        this.dispatchToast("{{ __('Connectez-vous pour sauvegarder votre grille.') }}", 'warning');
        return;
      }
      if (!this.grid) {
        this.dispatchToast("{{ __('Générez d\'abord la grille avant de sauvegarder. Saisissez vos mots puis cliquez sur « Générer la grille ».') }}", 'warning', 6000);
        return;
      }
      // Avertissement non-bloquant si grille incomplète (mots non placés)
      const placedCount = (this.words || []).length;
      const totalCount = placedCount + (this.unplaced || []).length;
      if (totalCount > 0 && placedCount < totalCount) {
        const missing = totalCount - placedCount;
        this.dispatchToast("{{ __('Attention') }} : " + missing + " {{ __('mot(s) sur') }} " + totalCount + " {{ __('non placé(s). La sauvegarde se poursuit, mais pensez à ajouter des lettres communes pour une grille complète.') }}", 'info', 7000);
      }
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
            // 2026-05-05 #97 Phase 1 : utilise custom_slug si défini, sinon public_id
            const identifier = preset.custom_slug || preset.public_id;
            this.publicShareUrl = window.location.origin + '/jeumc/' + identifier;
            this.currentPresetPublicId = preset.public_id;
            this.currentCustomSlug = preset.custom_slug || '';
            // Scroll vers le panneau lien (visible après transition Alpine)
            this.$nextTick(() => {
              const el = document.querySelector('input[aria-label="{{ __('URL de partage du jeu') }}"]');
              if (el) el.scrollIntoView({behavior: 'smooth', block: 'center'});
            });
          } else {
            this.publicShareUrl = '';
            this.currentPresetPublicId = '';
            this.currentCustomSlug = '';
          }
        } else if (response.status === 409) {
          // 2026-05-05 #101 anti-doublons : grille publique identique existe déjà.
          const dup = await response.json().catch(() => ({}));
          this.duplicateInfo = {
            url: dup.duplicate_url || '',
            name: dup.duplicate_name || '',
            message: dup.message || '',
          };
          // Force grille en privée tant que conflit pas résolu (sécurité user)
          this.metadata.is_public = false;
          this.dispatchToast(dup.message || "{{ __('Grille publique identique existe déjà.') }}", 'warning', 8000);
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

    // S81 #65 Form-to-Prompt : génère le prompt zero-shot CoT à partir du form
    aiPromptText() {
      const theme = (this.aiTheme || '').trim() || '__VOTRE THÈME ICI__';
      const n = Math.max(5, Math.min(50, parseInt(this.aiNbWords) || 12));
      const langMap = { fr: 'français', en: 'anglais', es: 'espagnol' };
      const lang = langMap[this.aiLang] || 'français';
      const levelMap = {
        primaire: 'élèves du primaire (8-12 ans), vocabulaire simple et courant',
        secondaire: 'élèves du secondaire (12-17 ans), vocabulaire de niveau scolaire',
        adulte: 'adultes éduqués, vocabulaire général',
        expert: 'experts du domaine, vocabulaire technique et précis',
      };
      const level = levelMap[this.aiLevel] || levelMap.adulte;
      const styleMap = {
        court: 'Très court (1 à 5 mots), comme un mot-clé ou synonyme',
        definition: 'Définition concise (8 à 15 mots) qui décrit clairement le mot',
        long: 'Description détaillée (15 à 25 mots) avec contexte',
        devinette: 'Devinette créative et amusante avec jeu sur les sens',
      };
      const style = styleMap[this.aiClueStyle] || styleMap.definition;
      const target = (this.aiTarget || '').trim();
      const targetLine = target ? `Cible spécifique : ${target}.\n` : '';

      return `Tu es expert créateur de mots croisés en ${lang}, niveau ${level}.
${targetLine}
THÈME : "${theme}"
NOMBRE DE PAIRES À GÉNÉRER : ${n}
STYLE DES INDICES : ${style}

RAISONNE étape par étape (ne montre pas le raisonnement, seulement le résultat final) :
1. Liste mentalement 20 mots forts du thème (3 à 12 lettres, en ${lang}, sans accents ni espaces).
2. Sélectionne les ${n} meilleurs en privilégiant la variété de longueurs ET les lettres communes (E, A, S, N, R, T, I, L, O, U, C, D, P, M) qui faciliteront les croisements.
3. Pour chaque mot, rédige un indice cohérent avec le style choisi.

CONTRAINTES ABSOLUES :
- Réponses en MAJUSCULES, sans accents, sans espaces (ex: QUEBEC, pas Québec ; LAVEILLE, pas "La Veille")
- Longueur réponse : entre 3 et 12 lettres
- Indice : maximum 100 caractères, ${this.aiLang === 'fr' ? 'en français correct (accents permis)' : 'in proper '+lang}
- Pas de réponses identiques ou variantes proches (ex: pas CHAT et CHATS ensemble)

FORMAT DE SORTIE (CRITIQUE) :
Réponds UNIQUEMENT avec un bloc CSV strict, sans commentaires avant ou après. Première ligne = en-tête. Séparateur = point-virgule (;). Pas de guillemets autour des valeurs.

Exemple exact à reproduire :

Indice;Mot
Capitale du Québec;QUEBEC
Langage de programmation web;PHP
Animal qui aboie;CHIEN

Maintenant, génère ${n} paires sur le thème "${theme}".`;
    },

    aiChatGPTUrl() {
      return 'https://chat.openai.com/?q=' + encodeURIComponent(this.aiPromptText());
    },
    aiClaudeUrl() {
      return 'https://claude.ai/new?q=' + encodeURIComponent(this.aiPromptText());
    },
    aiPerplexityUrl() {
      // 2026-05-05 : Perplexity supporte ?q= et lance la recherche directement (test live OK)
      return 'https://www.perplexity.ai/?q=' + encodeURIComponent(this.aiPromptText());
    },
    aiGeminiUrl() {
      // 2026-05-05 : Gemini ?q= toujours non supporté (textbox reste vide). Conserve la fonction au cas où Google active plus tard ; pas exposée comme bouton public.
      return 'https://gemini.google.com/app?q=' + encodeURIComponent(this.aiPromptText());
    },

    async copyAiPrompt() {
      if (!this.aiTheme.trim()) {
        this.dispatchToast("{{ __('Saisissez d\'abord un thème.') }}", 'warning');
        return;
      }
      try {
        await navigator.clipboard.writeText(this.aiPromptText());
        this.aiPromptCopied = true;
        setTimeout(() => { this.aiPromptCopied = false; }, 2500);
        this.dispatchToast("{{ __('Prompt copié ! Collez-le dans votre IA.') }}", 'success');
      } catch (e) {
        // Fallback : sélection textarea
        const ta = document.getElementById('aiPromptOutput');
        if (ta) { ta.select(); document.execCommand('copy'); }
        this.dispatchToast("{{ __('Prompt copié (fallback).') }}", 'info');
      }
    },

    // 2026-05-05 A1+A2 : copie + partage natif du lien public
    async copyShareLink() {
      if (!this.publicShareUrl) return;
      try {
        await navigator.clipboard.writeText(this.publicShareUrl);
        this.shareLinkCopied = true;
        setTimeout(() => { this.shareLinkCopied = false; }, 2500);
        this.dispatchToast("{{ __('Lien copié dans le presse-papier.') }}", 'success');
      } catch (e) {
        this.dispatchToast("{{ __('Impossible de copier — sélectionnez et copiez manuellement.') }}", 'warning');
      }
    },
    async nativeShare() {
      if (!this.publicShareUrl || !navigator.share) return;
      try {
        await navigator.share({
          title: this.metadata.title || this.saveName || "{{ __('Mots croisés') }}",
          text: "{{ __('Essaie cette grille de mots croisés sur laveille.ai') }}",
          url: this.publicShareUrl,
        });
      } catch (e) {
        // user cancelled
      }
    },

    // 2026-05-05 #97 Phase 1 : POST custom_slug (ou retrait si vide).
    async saveCustomSlug() {
      if (!this.currentPresetPublicId) return;
      this.customSlugSaving = true;
      this.customSlugError = '';
      try {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch('/user/mots-croises/' + this.currentPresetPublicId + '/slug', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
          },
          body: JSON.stringify({ custom_slug: this.customSlugInput || null }),
        });
        if (response.ok || response.redirected) {
          this.currentCustomSlug = this.customSlugInput || '';
          const identifier = this.currentCustomSlug || this.currentPresetPublicId;
          this.publicShareUrl = window.location.origin + '/jeumc/' + identifier;
          this.dispatchToast(this.currentCustomSlug ? "{{ __('Lien personnalisé enregistré.') }}" : "{{ __('Lien personnalisé retiré.') }}", 'success');
        } else if (response.status === 422) {
          const data = await response.json().catch(() => ({}));
          const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
          this.customSlugError = firstError || data.message || "{{ __('Lien invalide ou déjà pris.') }}";
        } else {
          this.customSlugError = "{{ __('Erreur serveur. Réessayez plus tard.') }}";
        }
      } catch (err) {
        this.customSlugError = "{{ __('Erreur réseau lors de l\'enregistrement.') }}";
      } finally {
        this.customSlugSaving = false;
      }
    },

    // 2026-05-05 #97 Phase 2 : POST qr_options.
    async saveQrOptions() {
      if (!this.currentPresetPublicId) return;
      this.qrSaving = true;
      try {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch('/user/mots-croises/' + this.currentPresetPublicId + '/qr-options', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({ foreground: this.qrFg, background: this.qrBg, ecc: this.qrEcc, dot_style: this.qrDotStyle, logo: this.qrIncludeLogo }),
        });
        if (response.ok) {
          this.qrPreviewBust = Date.now();
          this.dispatchToast("{{ __('Options QR enregistrées.') }}", 'success');
        } else if (response.status === 422) {
          const data = await response.json().catch(() => ({}));
          const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
          this.dispatchToast(firstError || "{{ __('Options QR invalides.') }}", 'danger');
        } else {
          this.dispatchToast("{{ __('Erreur serveur QR.') }}", 'danger');
        }
      } catch (err) {
        this.dispatchToast("{{ __('Erreur réseau QR.') }}", 'danger');
      } finally {
        this.qrSaving = false;
      }
    },

    // S81 #65 WCAG AAA : navigation clavier flèches sur la grille générée (focus de cell en cell)
    gridKeyNav(rowIndex, colIndex, event) {
      if (!this.grid || !this.grid.cells) return;
      const rows = this.grid.rows;
      const cols = this.grid.cols;
      let r = rowIndex, c = colIndex;
      switch (event.key) {
        case 'ArrowUp': r = Math.max(0, r - 1); event.preventDefault(); break;
        case 'ArrowDown': r = Math.min(rows - 1, r + 1); event.preventDefault(); break;
        case 'ArrowLeft': c = Math.max(0, c - 1); event.preventDefault(); break;
        case 'ArrowRight': c = Math.min(cols - 1, c + 1); event.preventDefault(); break;
        case 'Home': c = 0; event.preventDefault(); break;
        case 'End': c = cols - 1; event.preventDefault(); break;
        default: return;
      }
      this.gridFocusRow = r;
      this.gridFocusCol = c;
      this.$nextTick(() => {
        const target = document.querySelector('[data-cell-row="' + r + '"][data-cell-col="' + c + '"]');
        if (target) target.focus();
      });
    },

    dispatchToast(message, variant, duration) {
      const detail = { message: message, variant: variant || 'info' };
      if (duration) detail.duration = duration;
      window.dispatchEvent(new CustomEvent('toast-show', { detail: detail }));
    }
  };
}
</script>
@endpush
