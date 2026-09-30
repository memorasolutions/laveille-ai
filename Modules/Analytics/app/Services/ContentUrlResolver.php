<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Associe une URL publique à un contenu (content_id + content_type).
 *
 * Service PUR : url -> contenu, aucune écriture, aucun état. La résolution est faite une fois à
 * l'ingestion puis STOCKÉE ; le passé n'est jamais re-résolu contre les URLs courantes.
 *
 * Le registre vit dans config('analytics.content_resolvers'), liste ORDONNÉE d'entrées
 * {prefix, type, resolver}. Le premier préfixe qui correspond gagne, sans repli sur les suivants :
 * '/actualites/dossier/' doit donc précéder '/actualites/'. Config vide : toujours [null, null].
 *
 * `resolver` accepte :
 *  - null : le type est connu mais n'a pas d'enregistrement (ex. dossier, content_id null) ;
 *  - un callable fn(string $slug): ?int ;
 *  - un tableau ['model' => classe, 'translatable' => bool, 'scope' => 'published'|null,
 *    'where' => [colonne => valeur]] qui réutilise la résolution de slug propre au modèle
 *    (scope published, colonne slug, clé JSON par locale) comme le fait sa route publique.
 */
class ContentUrlResolver
{
    /** @return array{content_id: ?int, content_type: ?string} */
    public function resolve(string $url): array
    {
        $path = $this->normalize($url);

        foreach ((array) config('analytics.content_resolvers', []) as $entry) {
            $prefix = (string) ($entry['prefix'] ?? '');
            $type = $entry['type'] ?? null;

            if ($prefix === '' || $type === null || ! str_starts_with($path, $prefix)) {
                continue;
            }

            $slug = rawurldecode(substr($path, strlen($prefix)));
            if ($slug === '') {
                return $this->none();
            }

            $resolver = $entry['resolver'] ?? null;
            if ($resolver === null) {
                return ['content_id' => null, 'content_type' => (string) $type];
            }

            $id = $this->lookup($resolver, $slug);

            return $id === null ? $this->none() : ['content_id' => $id, 'content_type' => (string) $type];
        }

        return $this->none();
    }

    /** Chemin seul : sans schéma, hôte, paramètres ni fragment ; slash initial garanti, final retiré. */
    public function normalize(string $url): string
    {
        $path = parse_url(trim($url), PHP_URL_PATH);
        $path = is_string($path) ? $path : '';
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function lookup(mixed $resolver, string $slug): ?int
    {
        if (is_callable($resolver)) {
            $id = $resolver($slug);

            return $id === null ? null : (int) $id;
        }

        if (! is_array($resolver)) {
            return null;
        }

        $class = $resolver['model'] ?? null;
        // Portabilité : un module absent ou désactivé ne casse pas la résolution.
        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        try {
            $query = $class::query();

            if (($resolver['scope'] ?? null) === 'published') {
                $query->published();
            }

            foreach ((array) ($resolver['where'] ?? []) as $column => $value) {
                $query->where($column, $value);
            }

            if (! empty($resolver['translatable'])) {
                // Même clé que la route publique (locale courante), avec les replis fr_CA / fr :
                // config/translatable.php n'est pas publié, aucun repli automatique n'existe.
                $locales = array_unique([app()->getLocale(), 'fr_CA', 'fr']);
                $query->where(function ($q) use ($locales, $slug) {
                    foreach ($locales as $locale) {
                        $q->orWhere('slug->'.$locale, $slug);
                    }
                });
            } else {
                $query->where('slug', $slug);
            }

            $id = $query->value('id');
        } catch (\Throwable) {
            // Table absente ou scope indisponible : le résolveur reste pur et répond « introuvable ».
            return null;
        }

        return $id === null ? null : (int) $id;
    }

    /** @return array{content_id: null, content_type: null} */
    private function none(): array
    {
        return ['content_id' => null, 'content_type' => null];
    }
}
