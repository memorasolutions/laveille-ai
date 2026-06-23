<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F14 - ÉCHELLE personnalisée (scale, parité Moodle). Appartient à UN propriétaire
 * (owner_id, formateur) ; l'admin gère tout (autorisation SERVEUR côté composant).
 * « items » = liste ORDONNÉE [{label, value}] du plus faible au plus fort. Un
 * devoir peut référencer une échelle (Assignment::scale_id) au lieu d'une note
 * numérique ; à la correction le formateur choisit un NIVEAU et GradebookService
 * convertit la valeur du niveau en points sur max_points (formule documentée là-bas).
 *
 * SÉCURITÉ : ce modèle ne porte QUE relations + scopes purs ; l'enforcement
 * d'autorisation (owner-scope, anti-IDOR) est fait SERVEUR par le composant Livewire.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                          $id
 * @property int|null                     $owner_id
 * @property string                       $name
 * @property string|null                  $slug
 * @property array<int, array{label: string, value: float}>|null $items
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Scale extends Model
{
    protected $table = 'academy_scales';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'items',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'items'    => 'array',
    ];

    /** Formateur propriétaire (null = échelle système partagée). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Devoirs qui utilisent cette échelle. */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'scale_id');
    }

    /**
     * Scope : échelles appartenant à un propriétaire donné (anti-IDOR/anti-fuite
     * inter-formateurs). N'inclut PAS les échelles système (owner_id null).
     *
     * @param  \App\Models\User|int  $user
     */
    public function scopeOwned(Builder $query, $user): Builder
    {
        $ownerId = $user instanceof User ? $user->getKey() : (int) $user;

        return $query->where('owner_id', $ownerId);
    }

    /**
     * Niveaux normalisés de l'échelle : ne garde que les entrées {label, value}
     * exploitables (libellé non vide, value numérique), dans l'ordre fourni.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function levels(): array
    {
        return self::sanitizeItems(is_array($this->items) ? $this->items : null);
    }

    /**
     * Normalise une liste d'items venue du stockage/formulaire en [{label, value}]
     * valides (libellé non vide, value bornée 0..1e9 et >= 0). Renvoie [] si rien.
     *
     * @param  array<int, array<string, mixed>>|null  $raw
     * @return array<int, array{label: string, value: float}>
     */
    public static function sanitizeItems(?array $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $items = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            if (! isset($item['value']) || ! is_numeric($item['value'])) {
                continue;
            }
            $value   = max(0.0, min(1000000000.0, (float) $item['value']));
            $items[] = ['label' => $label, 'value' => $value];
        }

        return $items;
    }

    /** Valeur maximale parmi les niveaux (0.0 si aucun) ; sert de dénominateur de conversion. */
    public function maxValue(): float
    {
        $values = array_map(static fn (array $l): float => $l['value'], $this->levels());

        return $values === [] ? 0.0 : max($values);
    }
}
