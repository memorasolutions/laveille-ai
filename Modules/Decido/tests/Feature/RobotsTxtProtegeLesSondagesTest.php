<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - le fichier robots.txt tient les sondages hors de portée des robots, y compris
 * les robots d'IA.
 *
 * Demande du fondateur (2026-09-17) : « que les pages de decido ne soient JAMAIS indexées par
 * les moteurs de recherche ou les IA ». Le mot « jamais » est une exigence permanente : elle
 * appelle un garde-fou, pas une vérification ponctuelle. C'est le rôle de ce fichier.
 *
 * Les pages de vote portent déjà une balise « noindex », verrouillée par DecidoPagePubliqueTest.
 * Ces tests-ci couvrent ce que la balise ne couvre PAS : la balise s'adresse aux moteurs de
 * recherche et ne lie pas la récolte d'entraînement, qui obéit a robots.txt.
 *
 * DEUX PIÈGES DU FORMAT, qui sont la raison d'être de ces tests :
 *  1. LES GROUPES N'HÉRITENT PAS (RFC 9309, section 2.2.1). Un robot n'obéit qu'a UN groupe, le
 *     plus spécifique qui le nomme. Un « Disallow » pose sous « User-agent: * » ne protège AUCUN
 *     robot possédant son propre groupe. Mesuré le 2026-09-17 : les 18 robots nommés du fichier
 *     n'avaient que « Allow: / » et étaient donc autorisés sur /decido/, /admin et /dashboard.
 *  2. L'ORDRE COMPTE. Beaucoup de robots appliquent « la première règle qui correspond gagne ».
 *     Un « Allow: / » place AVANT les interdictions les annule toutes, en silence. Mesuré le même
 *     jour avec le parseur de la bibliothèque standard de Python sur le fichier de production :
 *     /admin, /user, /dashboard et /api/ ressortaient tous « autorisés ».
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

/**
 * Analyse robots.txt en groupes.
 *
 * Des lignes « User-agent: » consécutives ouvrent un même groupe ; les règles qui suivent lui
 * appartiennent ; un « User-agent: » qui arrive APRÈS au moins une règle ouvre un groupe neuf.
 *
 * @return array<int, array{agents: list<string>, regles: list<string>}>
 */
function robotsGroupes(): array
{
    $groupes = [];
    $courant = null;

    foreach (robotsLignes() as $ligne) {
        if (str_starts_with($ligne, 'User-agent:')) {
            if ($courant !== null && $courant['regles'] !== []) {
                $groupes[] = $courant;
                $courant = null;
            }

            $agent = trim(substr($ligne, strlen('User-agent:')));
            $courant = $courant === null
                ? ['agents' => [$agent], 'regles' => []]
                : ['agents' => [...$courant['agents'], $agent], 'regles' => []];

            continue;
        }

        if ($courant !== null) {
            $courant['regles'][] = $ligne;
        }
    }

    if ($courant !== null) {
        $groupes[] = $courant;
    }

    return $groupes;
}

/**
 * Les lignes utiles du fichier : ni vides, ni commentaires, déjà rognées.
 *
 * @return list<string>
 */
function robotsLignes(): array
{
    $contenu = file_get_contents(base_path('public/robots.txt'));

    return array_values(array_filter(
        array_map(trim(...), explode("\n", $contenu)),
        static fn (string $ligne): bool => $ligne !== '' && ! str_starts_with($ligne, '#')
    ));
}

/**
 * Les groupes où une interdiction manquante se paie : tous ceux qui ne ferment pas tout.
 *
 * LA BORNE A ÉTÉ ÉLARGIE le 2026-09-17, après qu'une passe adversariale l'a percée. La première
 * version ne retenait que les groupes portant « Allow: / ». Le contre-exemple, reproduit avant
 * correction : ajouter un groupe « User-agent: Amazonbot » suivi du seul « Disallow: /admin »
 * laissait ce robot LIBRE sur /decido/, sans qu'aucun des cinq tests ne s'en aperçoive. Le
 * groupe n'ouvrait pas le site au sens littéral, donc il sortait du contrôle.
 *
 * La bonne question n'est pas « ce groupe ouvre-t-il tout ? » mais « ce groupe laisse-t-il
 * quelque chose d'accessible ? ». Seule une fermeture totale (« Disallow: / ») dispense des
 * interdictions ciblées ; tout le reste doit les porter, y compris un groupe futur que
 * personne n'a encore écrit.
 *
 * @return array<int, array{agents: list<string>, regles: list<string>}>
 */
function robotsGroupesNonFermes(): array
{
    return array_values(array_filter(
        robotsGroupes(),
        static fn (array $groupe): bool => ! in_array('Disallow: /', $groupe['regles'], true)
    ));
}

test('chaque groupe qui ne ferme pas tout interdit les sondages', function (): void {
    // LE test de ce fichier. S'il tombe, un robot autorise sur tout le site peut récolter
    // /decido/{slug} : le titre du sondage, les pseudonymes des participants et leurs
    // disponibilités. Une fois absorbe dans un corpus d'entraînement, ce contenu ne se retire
    // plus. Poser l'interdiction uniquement sous « User-agent: * » ne suffit pas : les groupes
    // n'héritent de rien.
    $fautifs = [];

    foreach (robotsGroupesNonFermes() as $groupe) {
        if (! in_array('Disallow: /decido/', $groupe['regles'], true)) {
            $fautifs = [...$fautifs, ...$groupe['agents']];
        }
    }

    $this->assertEmpty($fautifs, 'Ces robots peuvent atteindre les sondages, faute d\'interdiction de /decido/ '
        .'dans leur propre groupe : '.implode(', ', $fautifs));
});

test('les robots d IA nommes sont tous couverts', function (): void {
    // Contrôle nominatif, volontairement redondant avec le précédent : il résiste au cas ou
    // quelqu'un retirerait le « Allow: / » d'un groupe d'IA, ce qui le ferait sortir du contrôle
    // ci-dessus tout en le laissant sans interdiction explicite. La liste est celle des robots
    // que le site accueille sciemment sur son contenu public.
    $attendus = [
        'GPTBot', 'ClaudeBot', 'anthropic-ai', 'Google-Extended', 'Applebot-Extended', 'CCBot',
        'cohere-ai', 'Diffbot', 'Meta-ExternalAgent', 'OAI-SearchBot', 'ChatGPT-User',
        'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'GeminiBot',
    ];

    $couverts = [];

    foreach (robotsGroupes() as $groupe) {
        if (in_array('Disallow: /decido/', $groupe['regles'], true)) {
            $couverts = [...$couverts, ...$groupe['agents']];
        }
    }

    $manquants = array_values(array_diff($attendus, $couverts));

    $this->assertEmpty($manquants, 'Ces robots d\'IA ne portent aucune interdiction de /decido/ : '
        .implode(', ', $manquants));
});

test('Allow: / est toujours la dernière règle de son groupe', function (): void {
    // Piège silencieux. Beaucoup de robots appliquent « la première règle qui correspond gagne ».
    // Remonter « Allow: / » en tête d'un groupe annule TOUTES les interdictions qui suivent, sans
    // qu'aucun autre test ne s'en aperçoive : le fichier contiendrait toujours les bonnes lignes.
    // Seule leur POSITION trahirait la panne.
    foreach (robotsGroupes() as $groupe) {
        $position = array_search('Allow: /', $groupe['regles'], true);

        if ($position === false) {
            continue;
        }

        $this->assertSame(
            count($groupe['regles']) - 1,
            $position,
            'Dans le groupe '.implode(', ', $groupe['agents']).', « Allow: / » précède des '
            .'interdictions : tout robot lisant dans l\'ordre les ignorera.'
        );
    }
});

test('les espaces privés sont interdits dans chaque groupe qui ne ferme pas tout', function (): void {
    // Même mécanique que le premier test, élargie aux autres espaces qui ne sont pas du contenu
    // public. Ils étaient interdits sous « User-agent: * » seulement, donc ouverts aux 18 robots
    // nommés. S'il tombe, ce sont les espaces d'administration et les comptes qui redeviennent
    // récoltables.
    $attendues = [
        'Disallow: /admin', 'Disallow: /user', 'Disallow: /login', 'Disallow: /dashboard',
        'Disallow: /s/', 'Disallow: /api/', 'Disallow: /media/social/',
    ];

    foreach (robotsGroupesNonFermes() as $groupe) {
        $manquantes = array_values(array_diff($attendues, $groupe['regles']));

        $this->assertEmpty($manquantes, 'Le groupe '.implode(', ', $groupe['agents'])
            .' laisse le site accessible sans interdire : '.implode(', ', $manquantes));
    }
});

test('les pages d acquisition restent ouvertes', function (): void {
    // Contrôle de FRONTIÈRE, celui qui empêche la correction de trop mordre. /decido (la page de
    // présentation) et /outils/decido (dans le plan de site) sont des pages d'acquisition : elles
    // doivent rester trouvables. Seul le motif avec barre finale les préserve.
    //
    // On compare des LIGNES ENTIÈRES, jamais des sous-chaînes : « Disallow: /decido » est contenu
    // dans « Disallow: /decido/ », donc un contrôle par sous-chaîne serait rouge sur un fichier
    // parfaitement correct. Ce défaut a réellement été écrit avant d'être intercepte.
    $lignes = robotsLignes();

    expect($lignes)->not->toContain('Disallow: /decido')
        ->and($lignes)->not->toContain('Disallow: /outils')
        ->and($lignes)->not->toContain('Disallow: /outils/decido')
        ->and($lignes)->toContain('Allow: /api/v1/directory/');
});
