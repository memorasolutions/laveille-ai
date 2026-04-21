<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Contracts\Container\Container;
use Modules\Core\Contracts\Searchable;

/**
 * Registry central des modèles searchable cross-module.
 *
 * Collecte les modèles taggués 'searchable.models' via le container Laravel.
 * Idempotent : cache interne par request pour éviter re-résolution.
 *
 * Usage côté module métier (ServiceProvider::register()) :
 *   $this->app->tag([MonModel::class], 'searchable.models');
 *
 * Usage côté consommateur (module Search) :
 *   $registry = app(SearchRegistry::class);
 *   foreach ($registry->all() as $modelClass) { ... }
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class SearchRegistry
{
    /**
     * @var array<int, class-string<Searchable>>|null
     */
    private ?array $cachedModels = null;

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Tous les modèles Searchable enregistrés, triés par priority ASC puis label alpha.
     *
     * @return array<int, class-string<Searchable>>
     */
    public function all(): array
    {
        if ($this->cachedModels !== null) {
            return $this->cachedModels;
        }

        $models = [];
        foreach ($this->container->tagged('searchable.models') as $instance) {
            if (! $instance instanceof Searchable) {
                continue;
            }
            $class = get_class($instance);
            if (! in_array($class, $models, true)) {
                $models[] = $class;
            }
        }

        usort($models, function (string $a, string $b): int {
            $pa = $a::searchPriority();
            $pb = $b::searchPriority();
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return $a::searchSectionLabel() <=> $b::searchSectionLabel();
        });

        $this->cachedModels = $models;

        return $this->cachedModels;
    }

    /**
     * Métadonnées sections indexées par clé.
     *
     * @return array<string, array{key: string, label: string, icon: string, priority: int, model: class-string<Searchable>}>
     */
    public function sections(): array
    {
        $sections = [];
        foreach ($this->all() as $model) {
            $key = $model::searchSectionKey();
            $sections[$key] = [
                'key' => $key,
                'label' => $model::searchSectionLabel(),
                'icon' => $model::searchSectionIcon(),
                'priority' => $model::searchPriority(),
                'model' => $model,
            ];
        }

        return $sections;
    }

    /**
     * Modèle associé à une section, ou null si absent (module désactivé).
     *
     * @return class-string<Searchable>|null
     */
    public function get(string $sectionKey): ?string
    {
        return $this->sections()[$sectionKey]['model'] ?? null;
    }

    /**
     * Reset cache interne (utile dans tests).
     */
    public function forgetCache(): void
    {
        $this->cachedModels = null;
    }
}
