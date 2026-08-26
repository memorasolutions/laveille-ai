<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Core\Jobs\ResolveFaviconJob;
use Modules\Core\Services\FaviconResolverService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Mesure du 2026-08-26 : la premiere visite d'une fiche d'outil coutait 4,4 a 10,6 secondes en
// production, contre 0,5 s ensuite. Cause : le composant Blade `smart-favicon` appelait
// FaviconResolverService::resolve(), qui interroge jusqu'a 3 fournisseurs externes avec 3 secondes
// de delai chacun, soit 9 secondes par domaine inconnu, PENDANT LE RENDU.
//
// Apres correctif, mesure locale du meme rendu : 2 605 ms -> 232 ms, et 0 INSERT dans
// favicon_cache au lieu de 7 (preuve qu'aucun appel reseau n'a lieu).
//
// Ces tests verrouillent les trois proprietes qui font tenir le correctif. Si l'un casse, le site
// redevient lent a froid sans que rien d'autre ne le signale - et c'est Googlebot, qui explore
// surtout des pages froides, qui en paie le prix.

it('ne fait AUCUN appel reseau au rendu et confie le travail a la file', function () {
    Queue::fake();

    $resultat = FaviconResolverService::resolveCached('domaine-inconnu-du-cache.test', 64);

    // Domaine absent du cache : on ne devine rien, on ne bloque rien.
    expect($resultat)->toBeNull();

    // ... mais la resolution DOIT etre programmee, sinon un domaine jamais vu n'obtiendrait
    // jamais de favicon. C'est le defaut qui avait ete introduit puis corrige a la revue.
    Queue::assertPushed(ResolveFaviconJob::class);
});

it('ne programme qu une seule fois le meme domaine par requete', function () {
    Queue::fake();

    // Une fiche d'outil peut afficher des dizaines de favicons du meme domaine.
    for ($i = 0; $i < 5; $i++) {
        FaviconResolverService::resolveCached('domaine-repete.test', 64);
    }

    Queue::assertPushed(ResolveFaviconJob::class, 1);
});

// Garde-fou structurel : c'est l'appel DEPUIS UNE VUE qui causait la lenteur. Peu importe la
// qualite du service, si une vue rappelle la version bloquante, le probleme revient entier.
it('interdit a toute vue d appeler la version bloquante', function () {
    $vues = [];
    $racine = base_path('Modules');

    $iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine));

    foreach ($iterateur as $fichier) {
        if (! $fichier->isFile() || ! str_ends_with($fichier->getFilename(), '.blade.php')) {
            continue;
        }

        $contenu = file_get_contents($fichier->getPathname());

        if (str_contains($contenu, 'FaviconResolverService::resolve(')) {
            $vues[] = str_replace(base_path().'/', '', $fichier->getPathname());
        }
    }

    expect($vues)->toBe(
        [],
        'Ces vues appellent la version BLOQUANTE de la resolution de favicon, qui interroge le '
        .'reseau pendant le rendu (jusqu a 9 s par domaine). Utiliser resolveCached().'
    );
});
