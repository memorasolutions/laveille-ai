<?php

declare(strict_types=1);

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

namespace Modules\Decido\Services;

use DateTime;
use DateTimeZone;

/**
 * Génère la liste des fuseaux horaires IANA proposée dans le combobox de description-timezone-fields.blade.php,
 * partagée par PollManageController::createDate() et createClassic() (DRY - évite deux copies de la même
 * boucle DateTimeZone dans le contrôleur).
 */
class TimezoneListService
{
    /**
     * Table préfixe IANA -> région française affichée en groupement secondaire dans le combobox.
     * Vérifié empiriquement (PHP 8.4, DateTimeZone::ALL, 419 identifiants) : les seuls préfixes
     * réellement retournés sont Africa/America/Antarctica/Arctic/Asia/Atlantic/Australia/Europe/
     * Indian/Pacific/UTC - aucun alias Etc/US/Canada résiduel sur cette version (le filtre
     * défensif dans list() reste en place au cas où un environnement différent en réintroduirait).
     * Arctic (identifiant unique : Arctic/Longyearbyen, Svalbard - territoire norvégien) et
     * Atlantic (îles nord-atlantiques majoritairement européennes : Islande, Féroé, Açores,
     * Madère, Canaries) sont regroupés sous Europe plutôt que d'ajouter deux régions
     * supplémentaires à la liste demandée (Amérique, Europe, Asie, Afrique, Océanie, Antarctique).
     * Indian (océan Indien) est regroupé sous Afrique : majorité des identifiants y réfèrent à des
     * territoires africains ou proches (Madagascar, Seychelles, Maurice, Comores, Mayotte,
     * Réunion : 6 identifiants sur 11, contre 1 pour l'Asie, 2 pour l'Australie, 1 pour
     * l'Antarctique). UTC (identifiant unique, sans région géographique) reçoit son propre libellé
     * "Universel" - le forcer dans l'une des 6 régions continentales aurait été trompeur.
     */
    private const REGION_MAP = [
        'Africa' => 'Afrique',
        'America' => 'Amérique',
        'Antarctica' => 'Antarctique',
        'Arctic' => 'Europe',
        'Asia' => 'Asie',
        'Atlantic' => 'Europe',
        'Australia' => 'Océanie',
        'Europe' => 'Europe',
        'Indian' => 'Afrique',
        'Pacific' => 'Océanie',
        'UTC' => 'Universel',
    ];

    /**
     * @return array<int, array{id: string, label: string, region: string, offset: string}>
     */
    public static function list(): array
    {
        // Un seul instant de référence (UTC) réutilisé pour tous les calculs d'offset : le
        // résultat de DateTimeZone::getOffset() ne dépend que de l'instant absolu passé (le fuseau
        // du DateTime lui-même n'affecte pas le calcul), donc une seule instanciation suffit au
        // lieu de reconstruire un DateTime par fuseau.
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $entries = [];

        foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $id) {
            // Garde défensive : voir note sur REGION_MAP - aucun Etc/* n'est retourné sur PHP 8.4
            // testé ici, mais ces alias (Etc/GMT+5...) n'ont aucune valeur pour un utilisateur
            // humain s'ils apparaissaient sur un environnement différent.
            if (str_starts_with($id, 'Etc/')) {
                continue;
            }

            $entries[] = self::buildEntry($id, $now);
        }

        // America/Montreal : alias legacy retiré de la base IANA tzdata en 2014 (fusionné dans
        // America/Toronto, mêmes règles HNE/HAE), donc absent de listIdentifiers() sur PHP
        // moderne - réintroduit manuellement pour préserver la continuité UX historique du
        // formulaire (voir PollManageController::store(), qui normalise déjà cet alias vers
        // America/Toronto avant validation - ce comportement n'est PAS touché ici).
        $entries[] = [
            'id' => 'America/Montreal',
            'label' => 'Montréal',
            'region' => 'Amérique',
            'offset' => self::formatOffset(new DateTimeZone('America/Toronto'), $now),
        ];

        // Tri alphabétique par label uniquement (pas par région ni par offset) - confirmé comme le
        // tri le plus efficace pour ce combobox (recherche UX du round de conception).
        usort($entries, static fn (array $a, array $b) => $a['label'] <=> $b['label']);

        return $entries;
    }

    /**
     * @return array{id: string, label: string, region: string, offset: string}
     */
    private static function buildEntry(string $id, DateTime $now): array
    {
        $segments = explode('/', $id);
        $prefix = $segments[0];
        $label = str_replace('_', ' ', (string) end($segments));

        return [
            'id' => $id,
            'label' => $label,
            'region' => self::REGION_MAP[$prefix] ?? $prefix,
            'offset' => self::formatOffset(new DateTimeZone($id), $now),
        ];
    }

    private static function formatOffset(DateTimeZone $tz, DateTime $referenceUtc): string
    {
        $seconds = $tz->getOffset($referenceUtc);
        $sign = $seconds < 0 ? '-' : '+';
        $abs = abs($seconds);
        $hours = intdiv($abs, 3600);
        $minutes = intdiv($abs % 3600, 60);

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }
}
