<?php

/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Générateur de politique d'utilisation de l'IA (#2584).
 *
 * Le test central de ce fichier n'est pas l'affichage du formulaire : c'est celui qui vérifie
 * qu'AUCUN appel réseau ne figure dans la page. La promesse faite au visiteur est que ses réponses
 * ne quittent jamais son navigateur, et une promesse pareille doit être tenue par un test plutôt
 * que par une intention - c'est exactement ce que la conception exigeait.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Inscrit l'outil, en le sortant de construction par défaut pour que le formulaire soit rendu.
 * Écrit une seule fois : le brouillon délégué recopiait ces dix lignes dans chacun des quatre tests.
 */
function inscrireOutilPolitiqueIa(bool $enConstruction = false): void
{
    Tool::updateOrCreate(['slug' => 'politique-ia'], [
        'name' => 'Générateur de politique IA',
        'description' => "Crée en dix questions une politique d'utilisation de l'IA.",
        'icon' => '📋',
        'category' => 'productivite',
        'is_active' => true,
        'is_under_construction' => $enConstruction,
        'sort_order' => 17,
    ]);
}

it('rend les dix champs du formulaire', function () {
    inscrireOutilPolitiqueIa();

    $reponse = $this->get('/outils/politique-ia');

    $reponse->assertOk();

    foreach ([
        'pia-entreprise', 'pia-secteur', 'pia-taille', 'pia-approbation', 'pia-responsable',
        'pia-outils', 'pia-agents', 'pia-comptes', 'pia-revision', 'pia-courriel',
    ] as $champ) {
        $reponse->assertSee($champ, false);
    }
});

it('ne contient aucun appel reseau : les reponses ne quittent pas le navigateur', function () {
    // On contrôle le GABARIT de l'outil, pas la page rendue. La page embarque le layout du site,
    // qui utilise légitimement `fetch` ailleurs - défilement infini, infolettre. Mesuré : contrôler
    // la page entière rendait ce test rouge pour du code qui n'est pas le nôtre, et il aurait fini
    // par être désactivé plutôt que compris. Ce qui doit être garanti, c'est que CET outil
    // n'émet rien.
    $gabarit = file_get_contents(
        base_path('Modules/Tools/resources/views/public/tools/politique-ia.blade.php')
    );

    expect($gabarit)->not->toContain('fetch(')
        ->and($gabarit)->not->toContain('XMLHttpRequest')
        ->and($gabarit)->not->toContain('axios')
        ->and($gabarit)->not->toContain('navigator.sendBeacon')
        ->and($gabarit)->not->toContain('<form action')
        ->and($gabarit)->not->toContain('method="post"');
});

it('ne promet jamais la conformite', function () {
    inscrireOutilPolitiqueIa();

    $contenu = $this->get('/outils/politique-ia')->getContent();

    // Le document est un point de départ, jamais une attestation : premier des trois interdits.
    expect($contenu)->not->toContain('vous êtes conforme')
        ->and($contenu)->not->toContain('devient conforme')
        ->and($contenu)->not->toContain('garantit la conformité');
});

it('ne cite aucun numero d article de loi', function () {
    // On lit le GABARIT, pas la page rendue : celle-ci embarque le pied de page et des titres
    // d'actualités où « article 5 » peut apparaître légitimement. Contrôler la page entière
    // rendrait ce test dépendant du contenu éditorial du jour, donc faussement rouge un matin.
    $gabarit = file_get_contents(
        base_path('Modules/Tools/resources/views/public/tools/politique-ia.blade.php')
    );

    // Deuxième interdit : une référence fabriquée dans un document juridique est pire que pas de
    // référence du tout. Le motif attrape « article 3.1 », « art. 12 » et leurs variantes.
    expect($gabarit)->not->toMatch('/\b(article|art\.)\s*\d+/i');
});

it('reste masque au public tant qu il est en construction', function () {
    inscrireOutilPolitiqueIa(enConstruction: true);

    $reponse = $this->get('/outils/politique-ia');

    // Le visiteur reçoit le gabarit « en construction », jamais le formulaire : c'est ce qui
    // laisse au fondateur le temps de relire le document avant qu'il devienne public.
    $reponse->assertOk();
    $reponse->assertDontSee('pia-form', false);
});
