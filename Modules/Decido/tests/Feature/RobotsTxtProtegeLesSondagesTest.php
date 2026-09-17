<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - le fichier robots.txt tient les sondages hors de portee des robots, y compris
 * les robots d'IA.
 *
 * Demande du fondateur (2026-09-17) : « que les pages de decido ne soient JAMAIS indexees par
 * les moteurs de recherche ou les IA ». Le mot « jamais » est une exigence permanente : elle
 * appelle un garde-fou, pas une verification ponctuelle. C'est le role de ce fichier.
 *
 * Les pages de vote portent deja une balise « noindex », verrouillee par DecidoPagePubliqueTest.
 * Ces tests-ci couvrent ce que la balise ne couvre PAS : la balise s'adresse aux moteurs de
 * recherche et ne lie pas la recolte d'entrainement, qui obeit a robots.txt.
 *
 * DEUX PIEGES DU FORMAT, qui sont la raison d'etre de ces tests :
 *  1. LES GROUPES N'HERITENT PAS (RFC 9309, section 2.2.1). Un robot n'obeit qu'a UN groupe, le
 *     plus specifique qui le nomme. Un « Disallow » pose sous « User-agent: * » ne protege AUCUN
 *     robot possedant son propre groupe. Mesure le 2026-09-17 : les 18 robots nommes du fichier
 *     n'avaient que « Allow: / » et etaient donc autorises sur /decido/, /admin et /dashboard.
 *  2. L'ORDRE COMPTE. Beaucoup de robots appliquent « la premiere regle qui correspond gagne ».
 *     Un « Allow: / » place AVANT les interdictions les annule toutes, en silence. Mesure le meme
 *     jour avec le parseur de la bibliotheque standard de Python sur le fichier de production :
 *     /admin, /user, /dashboard et /api/ ressortaient tous « autorises ».
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

/**
 * Analyse robots.txt en groupes.
 *
 * Des lignes « User-agent: » consecutives ouvrent un meme groupe ; les regles qui suivent lui
 * appartiennent ; un « User-agent: » qui arrive APRES au moins une regle ouvre un groupe neuf.
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
 * Les lignes utiles du fichier : ni vides, ni commentaires, deja rognees.
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
 * Les groupes qui ouvrent le site, c'est-a-dire ceux ou une interdiction manquante se paie.
 *
 * @return array<int, array{agents: list<string>, regles: list<string>}>
 */
function robotsGroupesOuverts(): array
{
    return array_values(array_filter(
        robotsGroupes(),
        static fn (array $groupe): bool => in_array('Allow: /', $groupe['regles'], true)
    ));
}

test('chaque groupe qui ouvre le site interdit aussi les sondages', function (): void {
    // LE test de ce fichier. S'il tombe, un robot autorise sur tout le site peut recolter
    // /decido/{slug} : le titre du sondage, les pseudonymes des participants et leurs
    // disponibilites. Une fois absorbe dans un corpus d'entrainement, ce contenu ne se retire
    // plus. Poser l'interdiction uniquement sous « User-agent: * » ne suffit pas : les groupes
    // n'heritent de rien.
    $fautifs = [];

    foreach (robotsGroupesOuverts() as $groupe) {
        if (! in_array('Disallow: /decido/', $groupe['regles'], true)) {
            $fautifs = [...$fautifs, ...$groupe['agents']];
        }
    }

    $this->assertEmpty($fautifs, 'Ces robots sont autorises sur tout le site sans interdiction '
        .'de /decido/, donc libres de recolter les sondages : '.implode(', ', $fautifs));
});

test('les robots d IA nommes sont tous couverts', function (): void {
    // Controle nominatif, volontairement redondant avec le precedent : il resiste au cas ou
    // quelqu'un retirerait le « Allow: / » d'un groupe d'IA, ce qui le ferait sortir du controle
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

test('Allow: / est toujours la derniere regle de son groupe', function (): void {
    // Piege silencieux. Beaucoup de robots appliquent « la premiere regle qui correspond gagne ».
    // Remonter « Allow: / » en tete d'un groupe annule TOUTES les interdictions qui suivent, sans
    // qu'aucun autre test ne s'en apercoive : le fichier contiendrait toujours les bonnes lignes.
    // Seule leur POSITION trahirait la panne.
    foreach (robotsGroupes() as $groupe) {
        $position = array_search('Allow: /', $groupe['regles'], true);

        if ($position === false) {
            continue;
        }

        $this->assertSame(
            count($groupe['regles']) - 1,
            $position,
            'Dans le groupe '.implode(', ', $groupe['agents']).', « Allow: / » precede des '
            .'interdictions : tout robot lisant dans l\'ordre les ignorera.'
        );
    }
});

test('les espaces prives sont interdits dans chaque groupe ouvert', function (): void {
    // Meme mecanique que le premier test, elargie aux autres espaces qui ne sont pas du contenu
    // public. Ils etaient interdits sous « User-agent: * » seulement, donc ouverts aux 18 robots
    // nommes. S'il tombe, ce sont les espaces d'administration et les comptes qui redeviennent
    // recoltables.
    $attendues = [
        'Disallow: /admin', 'Disallow: /user', 'Disallow: /login', 'Disallow: /dashboard',
        'Disallow: /s/', 'Disallow: /api/', 'Disallow: /media/social/',
    ];

    foreach (robotsGroupesOuverts() as $groupe) {
        $manquantes = array_values(array_diff($attendues, $groupe['regles']));

        $this->assertEmpty($manquantes, 'Le groupe '.implode(', ', $groupe['agents'])
            .' ouvre le site sans interdire : '.implode(', ', $manquantes));
    }
});

test('les pages d acquisition restent ouvertes', function (): void {
    // Controle de FRONTIERE, celui qui empeche la correction de trop mordre. /decido (la page de
    // presentation) et /outils/decido (dans le plan de site) sont des pages d'acquisition : elles
    // doivent rester trouvables. Seul le motif avec barre finale les preserve.
    //
    // On compare des LIGNES ENTIERES, jamais des sous-chaines : « Disallow: /decido » est contenu
    // dans « Disallow: /decido/ », donc un controle par sous-chaine serait rouge sur un fichier
    // parfaitement correct. Ce defaut a reellement ete ecrit avant d'etre intercepte.
    $lignes = robotsLignes();

    expect($lignes)->not->toContain('Disallow: /decido')
        ->and($lignes)->not->toContain('Disallow: /outils')
        ->and($lignes)->not->toContain('Disallow: /outils/decido')
        ->and($lignes)->toContain('Allow: /api/v1/directory/');
});
