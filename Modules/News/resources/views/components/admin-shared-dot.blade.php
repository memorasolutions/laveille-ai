{{--
    Point rouge "déjà publié" (superadmin only) — composant partagé.

    Utilisé sur 3 emplacements : fiche article publique (public/show.blade.php,
    dans le <h1>), carte d'actualité (public/partials/article-card.blade.php,
    dans le <h3>) et liste admin (admin/articles/index.blade.php).

    Même gate STRICT que le menu de partage admin plus bas (isSuperAdmin()).
    AUCUNE trace (dot ni *_shared_at) dans le HTML pour un visiteur non-admin :
    le bloc entier est omis côté serveur, pas juste caché en CSS.

    Réactif sans reload via l'événement 'admin-share-tracked' émis par
    <x-core::admin-copy-menu> au clic sur "Post LinkedIn"/"Post Facebook".

    Props :
        article  (\Modules\News\Models\NewsArticle) requis

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@props(['article'])

@once
@push('styles')
<style>
    .nw-shared-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: var(--c-danger, #DC2626); margin-right: 0.5rem; vertical-align: middle; flex-shrink: 0; }
</style>
@endpush
@endonce

@if(auth()->user()?->isSuperAdmin())
    @php
        $nwLinkedinTrackUrl = route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'linkedin']);
        $nwFacebookTrackUrl = route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'facebook']);
    @endphp
    <span class="nw-shared-dot"
          x-cloak
          x-data="{
              linkedin: {{ $article->linkedin_shared_at ? 'true' : 'false' }},
              facebook: {{ $article->facebook_shared_at ? 'true' : 'false' }},
          }"
          x-on:admin-share-tracked.window="
              if ($event.detail.trackUrl === @js($nwLinkedinTrackUrl)) linkedin = true;
              if ($event.detail.trackUrl === @js($nwFacebookTrackUrl)) facebook = true;
          "
          x-show="linkedin || facebook"
          role="img"
          :aria-label="linkedin && facebook ? '{{ __('Déjà publié sur LinkedIn et Facebook') }}' : (linkedin ? '{{ __('Déjà publié sur LinkedIn') }}' : '{{ __('Déjà publié sur Facebook') }}')"
          :title="linkedin && facebook ? '{{ __('Déjà publié sur LinkedIn et Facebook') }}' : (linkedin ? '{{ __('Déjà publié sur LinkedIn') }}' : '{{ __('Déjà publié sur Facebook') }}')"
    ></span>
@endif
