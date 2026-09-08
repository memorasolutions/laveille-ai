{{--
    Nom d'auteur, cliquable vers son mini-site (route authors.mini-site.show) UNIQUEMENT si un
    profil d'auteur existe pour l'utilisateur donné et n'est pas archivé (table author_profiles,
    colonne archived_at). Si aucun profil n'existe, le nom reste du texte brut, sans lien.

    Composant Blade PARTAGÉ (DRY) - point UNIQUE qui décide de ce lien, à réutiliser partout où
    le nom d'un auteur est affiché en dehors de son propre mini-site (fiche d'actualité, article
    de blogue, etc.), plutôt que de dupliquer cette logique à chaque endroit.

    Props :
        user (\App\Models\User|null) - l'utilisateur dont on affiche le nom.
        name (string|null)           - libellé à afficher ; par défaut $user->name.

    Attributs supplémentaires (class, etc.) passés au span/lien portant itemprop="name" -
    itemprop reste hors de la balise <a> volontairement : pour un <a>, le microdata HTML lit la
    valeur de la propriété dans l'attribut href, pas dans le texte - le poser sur le lien
    aurait remplacé le nom affiché par l'URL dans le schéma Person.

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@props(['user' => null, 'name' => null])

@php
    $lvAuthorNameLinkLabel = $name ?? $user?->name ?? __('Auteur');
    $lvAuthorNameLinkProfile = $user?->id
        ? \Modules\Authors\Models\AuthorProfile::where('user_id', $user->id)->whereNull('archived_at')->first()
        : null;
@endphp

@if($lvAuthorNameLinkProfile)
    <a href="{{ route('authors.mini-site.show', $lvAuthorNameLinkProfile->slug) }}" rel="author" style="text-decoration:none;color:inherit;">
        <span {{ $attributes->merge(['itemprop' => 'name']) }}>{{ $lvAuthorNameLinkLabel }}</span>
    </a>
@else
    <span {{ $attributes->merge(['itemprop' => 'name']) }}>{{ $lvAuthorNameLinkLabel }}</span>
@endif
