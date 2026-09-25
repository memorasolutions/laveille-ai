<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Dictionary\Support;

/**
 * ACTION : ticket #2531 (plan glossaire, étape 4) - liste FERMÉE des 12 termes retenus pour le
 * lot pilote de pages de couverture (une page par terme, listant toutes ses actualités liées).
 * Choisis par MESURE en production (530 termes publiés, dont 335 portent au moins une actualité
 * et 200 en portent plus de trois) : construire au seuil de trois aurait ouvert 200 pages d'un
 * coup, ce que le socle de référencement du projet documente comme sanctionné sous le nom de
 * « publication de masse ». On mesure d'abord sur ce lot pilote, on étend seulement si ça sert -
 * jamais une initiative de code silencieuse.
 *
 * Point UNIQUE de cette liste (DRY strict, règle nº 11 du projet) : routes/web.php restreint la
 * route par cette même liste (routePattern(), aucune des 518 autres pages n'existe même au
 * niveau du routeur) et PublicDictionaryController::coverage() la revérifie en profondeur
 * (défense en profondeur, jamais une confiance aveugle dans le seul filtre de route). La vue ne
 * la consulte jamais - elle n'a besoin que du terme déjà chargé et validé par le contrôleur.
 * MCP: SELF (<5 lignes utiles)
 * RAISON: ticket #2531, contrainte explicite « N'ajoute aucune page hors de cette liste ».
 */
final class CoverageTerms
{
    private function __construct() {}

    public const SLUGS = [
        'google',
        'openai',
        'anthropic',
        'ia-generative',
        'chatgpt',
        'claude-anthropic',
        'gemini-google',
        'cybersecurite',
        'agent-ia',
        'cloud-computing',
        'apple',
        'microsoft',
    ];

    /**
     * Motif d'alternation regex consommé par la contrainte de route (Route::where('slug', ...)
     * dans routes/web.php) - construit UNE SEULE FOIS à partir de la liste ci-dessus, jamais
     * recopié à la main.
     */
    public static function routePattern(): string
    {
        return implode('|', array_map(
            static fn (string $slug): string => preg_quote($slug, '/'),
            self::SLUGS
        ));
    }
}
