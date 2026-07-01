<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gabarit de diplôme personnalisable (Phase 1 — système de diplomation moderne).
 *
 * `layout_config` stocke les éléments positionnés sur le canevas de l'éditeur
 * (Konva.js) sous forme d'un tableau d'objets avec positions en POURCENTAGE
 * (x/y/width/height), jamais en pixels absolus — portable entre tailles
 * d'écran et formats d'impression. Voir DiplomaRenderService pour le rendu
 * HTML/PDF final (interpolation des variables SYSTÈME / PÉDAGOGIQUES /
 * ORGANISATIONNELLES / CUSTOM) et DiplomaTemplateEditor pour l'édition.
 *
 * @property int                             $id
 * @property string                          $name
 * @property array<string, mixed>|null       $layout_config
 * @property bool                            $is_default
 * @property int|null                        $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DiplomaTemplate extends Model
{
    protected $table = 'academy_diploma_templates';

    protected $fillable = [
        'name',
        'layout_config',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_default'    => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Gabarits marqués par défaut par leur créateur. */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Éléments du canevas (accès sûr : jamais d'erreur si layout_config est
     * absent ou mal formé).
     *
     * @return array<int, mixed>
     */
    public function elements(): array
    {
        $config = $this->layout_config;

        return is_array($config) ? ($config['elements'] ?? []) : [];
    }
}
