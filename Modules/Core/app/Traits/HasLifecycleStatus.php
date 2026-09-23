<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasLifecycleStatus
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BETA = 'beta';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ACQUIRED = 'acquired';
    public const STATUS_RENAMED = 'renamed';
    public const STATUS_PIVOTED = 'pivoted';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_SCAM = 'scam';

    /**
     * Statut ajouté pour corriger un cas réel mesuré sur la fiche Headroom : elle affichait
     * « Cette plateforme a fermé ses portes. » alors que le service n'a jamais fermé - il répond
     * 401 avec le message « walls.sh is private », donc il est devenu PRIVÉ (accès restreint),
     * pas disparu. Modules/Directory/app/Console/CheckLinksCommand.php classait déjà ce cas
     * correctement en code : ses constantes AMBIGUS (401, 402, 403, 503) excluent explicitement
     * ces codes de DISPARU (réservé aux 404/410), et son commentaire cite littéralement
     * « walls.sh is private » comme exemple à ne pas confondre avec un arrêt. Le vocabulaire
     * manquait seulement côté AFFICHAGE : aucun des 8 statuts existants n'était juste ('paused'
     * annonce un retour promis nulle part, 'closed'/'scam' affirment une disparition qui n'a pas
     * eu lieu, 'active' ferait disparaître le signal). 'private' comble ce trou précis.
     * Classement (voir getIsLifecycleActiveAttribute() et getIsLifecycleDownAttribute()
     * ci-dessous) : 'private' n'apparaît dans AUCUNE des deux listes, donc ni actif (l'accès
     * public est refusé) ni « down » au sens fermeture/arnaque (le service existe toujours,
     * quelqu'un y accède) - même traitement neutre que acquired/renamed/pivoted/paused.
     */
    public const STATUS_PRIVATE = 'private';

    public static function lifecycleStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => [
                'label' => 'Actif',
                'color' => '#10b981',
                'icon' => 'fa-circle-check',
                'severity' => 1,
            ],
            self::STATUS_BETA => [
                'label' => 'Bêta',
                'color' => '#6366f1',
                'icon' => 'fa-flask',
                'severity' => 2,
            ],
            self::STATUS_PAUSED => [
                'label' => 'En pause',
                'color' => '#f59e0b',
                'icon' => 'fa-pause-circle',
                'severity' => 3,
            ],
            // Icône 'fa-lock' plutôt que le 'fa-pause-circle' de 'paused' : deux statuts qui
            // veulent dire des choses différentes ne doivent pas porter le même glyphe, sinon
            // seule la couleur les distingue et un daltonien ne voit plus qu'un seul statut.
            // Ce nom n'a PAS besoin d'être ajouté à lifecycleIconMap() ni à sa copie de
            // lifecycle-badge.blade.php : cette table traduit FA6 vers FA4, et son repli
            // (`$iconMap[$rawIcon] ?? $rawIcon`) laisse passer tel quel tout nom identique dans
            // les deux versions - ce qui est le cas de 'fa-lock'. Aucune duplication aggravée.
            self::STATUS_PRIVATE => [
                'label' => 'Accès privé',
                'color' => '#64748b',
                'icon' => 'fa-lock',
                'severity' => 3,
            ],
            self::STATUS_RENAMED => [
                'label' => 'Renommé',
                'color' => '#0ea5e9',
                'icon' => 'fa-tag',
                'severity' => 2,
            ],
            self::STATUS_PIVOTED => [
                'label' => 'Pivoté',
                'color' => '#8b5cf6',
                'icon' => 'fa-shuffle',
                'severity' => 3,
            ],
            self::STATUS_ACQUIRED => [
                'label' => 'Acquis',
                'color' => '#3b82f6',
                'icon' => 'fa-handshake',
                'severity' => 2,
            ],
            self::STATUS_CLOSED => [
                'label' => 'Plus en ligne',
                'color' => '#52586a',
                'icon' => 'fa-circle-xmark',
                'severity' => 4,
            ],
            self::STATUS_SCAM => [
                'label' => 'Arnaque',
                'color' => '#dc2626',
                'icon' => 'fa-triangle-exclamation',
                'severity' => 5,
            ],
        ];
    }

    public function getLifecycleLabelAttribute(): string
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['label'] ?? $this->lifecycle_status;
    }

    public function getLifecycleColorAttribute(): string
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['color'] ?? '#6b7280';
    }

    public function getLifecycleIconAttribute(): string
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['icon'] ?? 'fa-circle-question';
    }

    public function getLifecycleSeverityAttribute(): int
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['severity'] ?? 1;
    }

    public function getIsLifecycleActiveAttribute(): bool
    {
        return in_array($this->lifecycle_status, [
            self::STATUS_ACTIVE,
            self::STATUS_BETA,
        ], true);
    }

    public function getIsLifecycleDownAttribute(): bool
    {
        return in_array($this->lifecycle_status, [
            self::STATUS_CLOSED,
            self::STATUS_SCAM,
        ], true);
    }

    /**
     * Source UNIQUE du message du bandeau de statut, pour la fiche (lifecycle-banner.blade.php)
     * ET pour la carte de la liste (index.blade.php, via lifecycleBannerMsg). Avant ce correctif,
     * le composant Blade portait sa propre table $messages (7 clés) pendant que cet accesseur
     * n'en couvrait que 2 (closed, scam) - les deux textes avaient déjà divergé (point final,
     * formulation de « scam »). Les textes ci-dessous reprennent VERBATIM ceux du composant, qui
     * étaient les plus complets. 'beta' reste ici pour fidélité au composant d'origine, mais n'est
     * jamais affiché en pratique : is_lifecycle_active classe 'beta' comme actif, et les deux vues
     * consommatrices se ferment sur is_lifecycle_active / is_lifecycle_down avant d'y arriver -
     * comportement inchangé, pas une régression introduite ici.
     * Repli 'Statut : <libellé>' conservé pour tout statut inconnu du tableau (ex. 'archived',
     * présent en base sur des fiches mais absent de lifecycleStatuses()).
     */
    public function getLifecycleBannerMessageAttribute(): string
    {
        return match ($this->lifecycle_status) {
            self::STATUS_CLOSED => 'Cette plateforme a fermé ses portes.',
            self::STATUS_ACQUIRED => 'Cette plateforme a été acquise par une autre entreprise.',
            self::STATUS_RENAMED => 'Cette plateforme a été renommée.',
            self::STATUS_PIVOTED => 'Cette plateforme a pivoté vers un nouveau positionnement.',
            self::STATUS_PAUSED => 'Cette plateforme est temporairement en pause.',
            self::STATUS_PRIVATE => "Cet outil n'est plus accessible au public.",
            self::STATUS_SCAM => '⚠️ Cette plateforme est signalée comme arnaque – évitez-la.',
            self::STATUS_BETA => 'Cette plateforme est en phase bêta – fonctionnalités en développement.',
            default => 'Statut : ' . $this->lifecycle_label,
        };
    }

    /**
     * Correspondance FontAwesome 6 (identifiants utilisés dans lifecycleStatuses() ci-dessus) vers
     * FontAwesome 4 (bibliothèque chargée par le gabarit public). Source UNIQUE : cette table de
     * 8 paires était recopiée à l'identique dans lifecycle-banner.blade.php ET dans index.blade.php,
     * sans lien entre les deux copies.
     */
    public static function lifecycleIconMap(): array
    {
        return [
            'fa-circle-check' => 'fa-check-circle',
            'fa-flask' => 'fa-flask',
            'fa-pause-circle' => 'fa-pause-circle',
            'fa-tag' => 'fa-tag',
            'fa-shuffle' => 'fa-random',
            'fa-handshake' => 'fa-handshake-o',
            'fa-circle-xmark' => 'fa-times-circle',
            'fa-triangle-exclamation' => 'fa-exclamation-triangle',
        ];
    }

    public function scopeLifecycle(Builder $query, string $status): Builder
    {
        return $query->where('lifecycle_status', $status);
    }

    public function scopeNotActiveLifecycle(Builder $query): Builder
    {
        return $query->whereNotIn('lifecycle_status', [
            self::STATUS_ACTIVE,
            self::STATUS_BETA,
        ]);
    }

    public function hasReplacement(): bool
    {
        return ! empty($this->lifecycle_replacement_url) || ! empty($this->lifecycle_replacement_tool_id);
    }
}
