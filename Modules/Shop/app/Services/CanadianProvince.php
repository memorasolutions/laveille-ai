<?php

declare(strict_types=1);

namespace Modules\Shop\Services;

/**
 * Province canadienne dérivée AUTORITATIVEMENT de la première lettre du code postal.
 *
 * Le champ « province » déclaré par le client n'est jamais fiable pour la taxe : un client de Laval (H7N) pouvait
 * déclarer AB et payer 5 % au lieu de 14,975 %. La première lettre du code postal détermine la province pour la
 * livraison (donc pour le lieu de fourniture), et donne le même résultat pour tous.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
final class CanadianProvince
{
    private const BY_LETTER = [
        'A' => 'NL', 'B' => 'NS', 'C' => 'PE', 'E' => 'NB',
        'G' => 'QC', 'H' => 'QC', 'J' => 'QC',
        'K' => 'ON', 'L' => 'ON', 'M' => 'ON', 'N' => 'ON', 'P' => 'ON',
        'R' => 'MB', 'S' => 'SK', 'T' => 'AB', 'V' => 'BC',
        'X' => 'NT', // X couvre NT et NU : même traitement fiscal ici, la valeur déclarée NU est conservée
        'Y' => 'YT',
    ];

    /** @return string|null code de province, ou null si la première lettre n'est pas un préfixe canadien valide. */
    public static function fromPostalCode(?string $postalCode): ?string
    {
        $postalCode = strtoupper(preg_replace('/\s+/', '', (string) $postalCode) ?? '');

        return self::BY_LETTER[substr($postalCode, 0, 1)] ?? null;
    }

    /** Province retenue : celle du code postal; le NU déclaré est conservé pour la zone X (NT/NU, même taxe). */
    public static function resolve(?string $postalCode, ?string $declared): ?string
    {
        $province = self::fromPostalCode($postalCode);

        if ($province === 'NT' && strtoupper(trim((string) $declared)) === 'NU') {
            return 'NU';
        }

        return $province;
    }
}
