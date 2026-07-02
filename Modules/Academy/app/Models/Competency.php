<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - COMPÉTENCE / RÉSULTAT (« competency / outcome », parité Moodle). Référentiel
 * OWNER-SCOPÉ : un formateur ne voit/gère QUE ses propres compétences (owner_id) ;
 * l'admin (academy.manage) voit tout (autorisation SERVEUR côté composant). Le slug
 * est unique PAR propriétaire. Aucune logique UI ici (relations + scopes purs).
 *
 * NIVEAU d'acquisition : réutilise une ÉCHELLE F14 (scale_id) si fournie, sinon barème
 * binaire par défaut. L'état d'acquisition par étudiant est DÉRIVÉ (jamais stocké) par
 * Modules\Academy\Services\CompetencyService à partir de l'achèvement (V2-c) et/ou des
 * notes (carnet), donc toujours cohérent avec la réalité de l'apprenant.
 *
 * F22-b - GRAPHE DE COMPÉTENCES : une compétence peut REQUÉRIR d'autres compétences
 * (prérequis pondérés, table académy_competency_relations) — voir requiresCompetencies()
 * / requiredByCompetencies() et Modules\Academy\Services\CompetencyGraphService pour le
 * calcul de maîtrise et le déverrouillage. Rétrocompat stricte : sans relation créée,
 * toute compétence reste déverrouillée (comportement actuel inchangé).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int                          $id
 * @property int|null                     $owner_id
 * @property string                       $name
 * @property string|null                  $slug
 * @property string|null                  $description
 * @property int|null                     $scale_id
 * @property int|null                     $pass_threshold
 * @property bool                         $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Competency extends Model
{
    protected $table = 'academy_competencies';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'scale_id',
        'pass_threshold',
        'is_active',
    ];

    protected $casts = [
        'owner_id'       => 'integer',
        'scale_id'       => 'integer',
        'pass_threshold' => 'integer',
        'is_active'      => 'boolean',
    ];

    /** Formateur propriétaire (null = compétence système partagée). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Échelle F14 du niveau d'acquisition (null = barème binaire par défaut). */
    public function scale(): BelongsTo
    {
        return $this->belongsTo(Scale::class, 'scale_id');
    }

    /** Liens vers les cours et/ou items de leçon associés à cette compétence. */
    public function links(): HasMany
    {
        return $this->hasMany(CompetencyLink::class, 'competency_id');
    }

    /**
     * F22-b - Prérequis de CETTE compétence (arêtes sortantes du graphe) : les
     * compétences qu'il faut maîtriser AVANT celle-ci, avec leur seuil/pondération.
     */
    public function requiresCompetencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'academy_competency_relations',
            'competency_id',
            'requires_competency_id',
        )->withPivot(['mastery_threshold', 'weight'])->withTimestamps();
    }

    /**
     * F22-b - Compétences qui REQUIÈRENT celle-ci (arêtes entrantes du graphe) :
     * utile pour afficher « débloque » dans une vue de graphe.
     */
    public function requiredByCompetencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'academy_competency_relations',
            'requires_competency_id',
            'competency_id',
        )->withPivot(['mastery_threshold', 'weight'])->withTimestamps();
    }

    /**
     * Scope : compétences appartenant à un propriétaire donné (anti-IDOR / anti-fuite
     * inter-formateurs). N'inclut PAS les compétences système (owner_id null).
     *
     * @param  \App\Models\User|int  $user
     */
    public function scopeOwned(Builder $query, $user): Builder
    {
        $ownerId = $user instanceof User ? $user->getKey() : (int) $user;

        return $query->where('owner_id', $ownerId);
    }

    /**
     * Normalise un libellé en slug stable (clé d'unicité owner-scope). Vide si le
     * libellé ne produit rien d'exploitable.
     */
    public static function slugify(string $name): string
    {
        return Str::slug(trim($name));
    }
}
