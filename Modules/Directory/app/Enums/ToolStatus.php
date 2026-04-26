<?php

declare(strict_types=1);

namespace Modules\Directory\Enums;

enum ToolStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    /**
     * Retourne le libellé en français québécois pour l'interface utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Published => 'Publié',
            self::Rejected => 'Rejeté',
            self::Archived => 'Archivé',
        };
    }

    /**
     * Retourne un tableau des valeurs string de l'enum.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retourne un tableau associatif valeur => libellé pour les listes déroulantes.
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }

    /**
     * Retourne la valeur par défaut pour un nouvel outil.
     */
    public static function default(): self
    {
        return self::Pending;
    }
}
