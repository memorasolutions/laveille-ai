<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Contrat pour tout modèle Eloquent participant à la recherche globale cross-module.
 *
 * Chaque module métier (Blog, News, Directory, Dictionary, Acronyms, ...) fait
 * implémenter cette interface sur son modèle principal et l'enregistre via
 * $this->app->tag([MonModel::class], 'searchable.models') dans son ServiceProvider.
 * Le module Search (consommateur) utilise SearchRegistry pour itérer sur tous
 * les modèles taggués, de manière entièrement agnostique aux modules installés.
 *
 * Si un module est désactivé, il ne tag plus son modèle — le Registry ne le voit plus,
 * aucune régression sur le reste du site.
 *
 * @author MEMORA solutions <info@memora.ca>
 */
interface Searchable
{
    /**
     * Champs SQL à inclure dans la requête LIKE/FULLTEXT (ex: ['title', 'content']).
     *
     * @return array<int, string>
     */
    public static function searchableFields(): array;

    /**
     * Identifiant snake_case unique de la section (ex: 'blog', 'annuaire', 'glossaire').
     */
    public static function searchSectionKey(): string;

    /**
     * Libellé traduit de la section pour UI (ex: __('Blog')).
     */
    public static function searchSectionLabel(): string;

    /**
     * Icône de la section : classe CSS (ex: 'fa-blog') ou emoji (ex: '📝').
     */
    public static function searchSectionIcon(): string;

    /**
     * Ordre d'affichage des tabs : valeur basse = gauche. Default 100.
     */
    public static function searchPriority(): int;

    /**
     * Titre de l'item pour affichage dans les résultats.
     */
    public function searchableResultTitle(): string;

    /**
     * Extrait ≤ 200 caractères de l'item pour preview résultat.
     */
    public function searchableResultExcerpt(): string;

    /**
     * URL canonique de l'item (route publique).
     */
    public function searchableResultUrl(): string;
}
