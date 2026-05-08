<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Service de comparaison normalisée d'outils IA (DRY).
 * Fournit la structure de critères, l'extraction de valeurs, et le calcul de différences.
 */

namespace Modules\Directory\Services;

use Illuminate\Support\Collection;
use Modules\Directory\Models\Tool;

class ToolComparisonService
{
    public const MAX_TOOLS = 4;

    /**
     * Schéma des critères groupés en sections (DRY : utilisé par vue + tests + futur API).
     *
     * @return array<string, array{label: string, icon: string, criteria: array<string, array{label: string, accessor: string, type: string, better: string}>}>
     */
    public function getCriteriaSchema(): array
    {
        return [
            'identite' => [
                'label' => __('Identité'),
                'icon' => '🆔',
                'criteria' => [
                    'launch_year' => ['label' => __('Année de lancement'), 'accessor' => 'launch_year', 'type' => 'year', 'better' => 'newer'],
                    'website_type' => ['label' => __('Type'), 'accessor' => 'website_type', 'type' => 'text', 'better' => 'neutral'],
                    'lifecycle_status' => ['label' => __('Statut'), 'accessor' => 'lifecycle_status', 'type' => 'lifecycle', 'better' => 'active'],
                ],
            ],
            'tarification' => [
                'label' => __('Tarification'),
                'icon' => '💰',
                'criteria' => [
                    'pricing' => ['label' => __('Modèle'), 'accessor' => 'pricing', 'type' => 'pricing', 'better' => 'free'],
                    'has_education_pricing' => ['label' => __('Tarif éducation'), 'accessor' => 'has_education_pricing', 'type' => 'bool', 'better' => 'true'],
                ],
            ],
            'capacites' => [
                'label' => __('Capacités IA'),
                'icon' => '🤖',
                'criteria' => [
                    'underlying_model' => ['label' => __('Modèle sous-jacent'), 'accessor' => 'underlying_model', 'type' => 'text', 'better' => 'neutral'],
                    'is_multimodal' => ['label' => __('Multimodal'), 'accessor' => 'is_multimodal', 'type' => 'bool', 'better' => 'true'],
                    'output_types' => ['label' => __('Types de sortie'), 'accessor' => 'output_types', 'type' => 'list', 'better' => 'more'],
                    'learning_curve' => ['label' => __('Courbe apprentissage'), 'accessor' => 'learning_curve', 'type' => 'curve', 'better' => 'lower'],
                ],
            ],
            'integrations' => [
                'label' => __('Intégrations'),
                'icon' => '🔌',
                'criteria' => [
                    'has_api_access' => ['label' => __('API publique'), 'accessor' => 'has_api_access', 'type' => 'bool', 'better' => 'true'],
                ],
            ],
            'confidentialite' => [
                'label' => __('Confidentialité'),
                'icon' => '🔒',
                'criteria' => [
                    'opt_out_training' => ['label' => __('Opt-out entraînement'), 'accessor' => 'opt_out_training', 'type' => 'opt_out', 'better' => 'yes'],
                    'privacy_compliance' => ['label' => __('Conformité'), 'accessor' => 'privacy_compliance', 'type' => 'text', 'better' => 'neutral'],
                ],
            ],
            'editorial' => [
                'label' => __('Éditorial laveille'),
                'icon' => '✍️',
                'criteria' => [
                    'unique_value' => ['label' => __('Différenciateur'), 'accessor' => 'unique_value', 'type' => 'text', 'better' => 'neutral'],
                ],
            ],
        ];
    }

    /**
     * Valide et nettoie une liste d'IDs (max MAX_TOOLS, dédup, entiers positifs).
     *
     * @param  array<int|string>|string  $ids
     * @return array<int>
     */
    public function validateIds(array|string $ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        return collect($ids)
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->take(self::MAX_TOOLS)
            ->all();
    }

    /**
     * Charge les outils correspondant aux IDs (publié, ordre conservé).
     *
     * @param  array<int>  $ids
     */
    public function loadTools(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $tools = Tool::whereIn('id', $ids)
            ->where('status', 'published')
            ->with('categories')
            ->get();

        return collect($ids)
            ->map(fn (int $id) => $tools->firstWhere('id', $id))
            ->filter()
            ->values();
    }

    /**
     * Extrait la valeur d'un critère pour un outil (centralise les accès).
     */
    public function getValue(Tool $tool, string $accessor): mixed
    {
        return $tool->{$accessor} ?? null;
    }

    /**
     * Calcule pour chaque critère quels outils sont "best/same/worst" — pour color-coding.
     * Retourne ['tool_id' => 'best'|'same'|'worst'|'neutral'] pour 1 critère donné.
     *
     * @param  array{label: string, accessor: string, type: string, better: string}  $criterion
     */
    public function computeDiff(Collection $tools, array $criterion): array
    {
        $values = $tools->mapWithKeys(fn (Tool $t) => [$t->id => $this->getValue($t, $criterion['accessor'])]);

        // Si aucune donnée comparable, neutral pour tous
        $nonNull = $values->filter(fn ($v) => $v !== null && $v !== '' && $v !== []);
        if ($nonNull->count() <= 1) {
            return $values->map(fn () => 'neutral')->all();
        }

        return match ($criterion['type']) {
            'bool' => $this->diffBool($values, $criterion['better']),
            'pricing' => $this->diffPricing($values, $criterion['better']),
            'opt_out' => $this->diffOptOut($values, $criterion['better']),
            'lifecycle' => $this->diffLifecycle($values),
            'year' => $this->diffYear($values, $criterion['better']),
            'curve' => $this->diffCurve($values),
            'list' => $this->diffList($values),
            default => $values->map(fn () => 'neutral')->all(),
        };
    }

    private function diffBool(Collection $values, string $better): array
    {
        $target = $better === 'true';

        return $values->map(function ($v) use ($target) {
            if ($v === null) {
                return 'neutral';
            }

            return ((bool) $v) === $target ? 'best' : 'worst';
        })->all();
    }

    private function diffPricing(Collection $values, string $better): array
    {
        // Hiérarchie : open_source/free > freemium > education > paid > enterprise
        $rank = ['open_source' => 0, 'free' => 0, 'freemium' => 1, 'education' => 2, 'paid' => 3, 'enterprise' => 4];

        return $this->diffByRank($values, $rank, lowerIsBetter: true);
    }

    private function diffOptOut(Collection $values, string $better): array
    {
        return $values->map(function ($v) use ($better) {
            if ($v === null || $v === 'unknown') {
                return 'neutral';
            }

            return $v === $better ? 'best' : 'worst';
        })->all();
    }

    private function diffLifecycle(Collection $values): array
    {
        return $values->map(function ($v) {
            if ($v === null) {
                return 'neutral';
            }

            return match ((string) $v) {
                'active' => 'best',
                'beta' => 'same',
                'deprecated', 'down' => 'worst',
                default => 'neutral',
            };
        })->all();
    }

    private function diffYear(Collection $values, string $better): array
    {
        $numeric = $values->filter(fn ($v) => is_numeric($v));
        if ($numeric->count() <= 1) {
            return $values->map(fn () => 'neutral')->all();
        }

        $best = $better === 'newer' ? $numeric->max() : $numeric->min();
        $worst = $better === 'newer' ? $numeric->min() : $numeric->max();

        return $values->map(function ($v) use ($best, $worst) {
            if (! is_numeric($v)) {
                return 'neutral';
            }
            if ((int) $v === (int) $best) {
                return 'best';
            }
            if ((int) $v === (int) $worst && $best !== $worst) {
                return 'worst';
            }

            return 'same';
        })->all();
    }

    private function diffCurve(Collection $values): array
    {
        $numeric = $values->filter(fn ($v) => is_numeric($v));
        if ($numeric->count() <= 1) {
            return $values->map(fn () => 'neutral')->all();
        }

        $best = $numeric->min();
        $worst = $numeric->max();

        return $values->map(function ($v) use ($best, $worst) {
            if (! is_numeric($v)) {
                return 'neutral';
            }
            if ((int) $v === (int) $best) {
                return 'best';
            }
            if ((int) $v === (int) $worst && $best !== $worst) {
                return 'worst';
            }

            return 'same';
        })->all();
    }

    private function diffList(Collection $values): array
    {
        $counts = $values->map(fn ($v) => is_array($v) ? count($v) : 0);
        $max = $counts->max();
        $min = $counts->min();
        if ($max === $min) {
            return $values->map(fn () => 'same')->all();
        }

        return $values->map(function ($v) use ($max, $min) {
            $c = is_array($v) ? count($v) : 0;
            if ($c === $max) {
                return 'best';
            }
            if ($c === $min) {
                return 'worst';
            }

            return 'same';
        })->all();
    }

    /**
     * Helper rank-based diff (smaller rank = better when lowerIsBetter).
     */
    private function diffByRank(Collection $values, array $rank, bool $lowerIsBetter): array
    {
        $ranks = $values->map(fn ($v) => $rank[$v] ?? null)->filter(fn ($r) => $r !== null);
        if ($ranks->count() <= 1) {
            return $values->map(fn () => 'neutral')->all();
        }

        $bestRank = $lowerIsBetter ? $ranks->min() : $ranks->max();
        $worstRank = $lowerIsBetter ? $ranks->max() : $ranks->min();

        return $values->map(function ($v) use ($rank, $bestRank, $worstRank) {
            if (! isset($rank[$v])) {
                return 'neutral';
            }
            if ($rank[$v] === $bestRank) {
                return 'best';
            }
            if ($rank[$v] === $worstRank && $bestRank !== $worstRank) {
                return 'worst';
            }

            return 'same';
        })->all();
    }

    /**
     * Format human-readable d'une valeur selon le type de critère (DRY pour vue + futur PDF).
     */
    public function formatValue(mixed $value, string $type): string
    {
        if ($value === null || $value === '' || $value === []) {
            return __('Non renseigné');
        }

        return match ($type) {
            'bool' => $value ? __('Oui') : __('Non'),
            'list' => is_array($value) ? implode(', ', $value) : (string) $value,
            'pricing' => match ((string) $value) {
                'free' => __('🆓 Gratuit'),
                'freemium' => __('💎 Freemium'),
                'paid' => __('💰 Payant'),
                'open_source' => __('🔓 Open source'),
                'enterprise' => __('🏢 Enterprise'),
                'education' => __('🎓 Éducation'),
                default => ucfirst((string) $value),
            },
            'lifecycle' => match ((string) $value) {
                'active' => __('✅ Actif'),
                'beta' => __('🧪 Bêta'),
                'deprecated' => __('⚠️ Obsolète'),
                'down' => __('🚫 Plus en ligne'),
                default => ucfirst((string) $value),
            },
            'opt_out' => match ((string) $value) {
                'yes' => __('✅ Oui'),
                'no' => __('❌ Non'),
                'unknown' => __('❔ Inconnu'),
                default => (string) $value,
            },
            'curve' => match ((int) $value) {
                1 => __('🌱 Très facile'),
                2 => __('🌿 Facile'),
                3 => __('🌳 Modérée'),
                4 => __('⛰️ Élevée'),
                5 => __('🏔️ Avancée'),
                default => (string) $value,
            },
            default => is_scalar($value) ? (string) $value : json_encode($value),
        };
    }
}
