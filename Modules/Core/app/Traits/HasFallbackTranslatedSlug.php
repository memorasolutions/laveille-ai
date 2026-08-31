<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * Résout un champ Spatie Translatable (typiquement 'slug') avec repli explicite.
 *
 * Incident du 18 juillet 2026 : config/translatable.php n'est pas publié dans ce projet, donc le
 * troisième paramètre $useFallbackLocale de getTranslation() n'a aucun effet réel (aucun
 * fallback_locale configuré) - un accès direct à un attribut traduisible, ou un getTranslation() à
 * 2 arguments, renvoie null dès que la locale courante (fr_CA en production) n'a pas de traduction
 * pour ce champ. Passer null à route() lève une UrlGenerationException, capable de faire tomber une
 * boucle entière (sitemap, page d'accueil, recherche, flux RSS, infolettre) pour UNE seule fiche
 * sans traduction. Le 18 juillet 2026, exactement ce défaut sur le plan de site a fait chuter le
 * trafic de recherche de 95 % pendant un mois.
 *
 * Repli : locale courante -> locale secondaire (par défaut 'fr') -> première traduction disponible.
 * Même patron que Modules\Directory\Models\Tool::getPublicUrl() (correctif de juillet 2026) et
 * Modules\Blog\Models\Article::getPublicUrl() (27 juillet 2026), qui restent tels quels : ce trait
 * sert les modèles qui n'avaient encore aucune protection (Term, Acronym, StaticPage).
 */
trait HasFallbackTranslatedSlug
{
    public function resolveTranslatedSlug(string $field = 'slug', string $secondaryFallbackLocale = 'fr'): string
    {
        return $this->getTranslation($field, app()->getLocale(), false)
            ?: $this->getTranslation($field, $secondaryFallbackLocale, false)
            ?: (string) collect($this->getTranslations($field))->first();
    }
}
