<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Le test qui compte ici est celui du GARDE-FOU. La première version générée comptait une
 * résolution nulle comme « ignorée » et REMETTAIT le compteur d'échecs à zéro : l'arrêt
 * automatique ne se serait donc jamais déclenché, et une campagne lancée avec un jeton mort
 * aurait martelé ProductHunt 260 fois d'affilée - exactement ce qui a déjà valu un bannissement
 * d'IP à ce projet. Un défaut invisible à la relecture, mortel à l'exécution.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ToolDiscoveryService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function phTool(string $url, ?string $nom = null): Tool
{
    static $i = 0;
    $i++;

    return Tool::create([
        'name' => $nom ?? "Outil PH {$i}",
        'slug' => 'outil-ph-'.$i.'-'.uniqid(),
        'url' => $url,
        'status' => 'published',
        'short_description' => 'Description courte de test.',
    ]);
}

it('résout une URL ProductHunt vers le site réel', function () {
    $t = phTool('https://www.producthunt.com/r/p/123456');

    $this->mock(ToolDiscoveryService::class, function ($m) {
        $m->shouldReceive('resolveProductHuntUrl')->andReturn('https://vraioutil.example.com');
    });

    $this->artisan('directory:resolve-producthunt --appliquer --pause=0')->assertSuccessful();

    expect($t->fresh()->url)->toBe('https://vraioutil.example.com');
});

it("n'écrit rien sans --appliquer", function () {
    $t = phTool('https://www.producthunt.com/r/p/222');

    $this->mock(ToolDiscoveryService::class, function ($m) {
        $m->shouldReceive('resolveProductHuntUrl')->andReturn('https://autre.example.com');
    });

    $this->artisan('directory:resolve-producthunt --pause=0')->assertSuccessful();

    expect($t->fresh()->url)->toContain('producthunt.com');
});

// Un faux succès est plus dangereux qu'un échec : il écrase une adresse par son équivalent,
// consomme le quota, et fait croire que le rattrapage a fonctionné.
it('refuse une résolution qui renvoie ENCORE une URL producthunt.com', function () {
    $t = phTool('https://www.producthunt.com/r/p/333');

    $this->mock(ToolDiscoveryService::class, function ($m) {
        $m->shouldReceive('resolveProductHuntUrl')->andReturn('https://www.producthunt.com/posts/autre-chose');
    });

    $this->artisan('directory:resolve-producthunt --appliquer --pause=0')->assertSuccessful();

    expect($t->fresh()->url)->toBe('https://www.producthunt.com/r/p/333');
});

/**
 * LE test décisif. Avec 6 fiches et un service qui échoue toujours, la commande doit S'ARRÊTER
 * après 3 échecs consécutifs, donc appeler le service 3 fois et NON 6. Sans ce contrôle, le
 * défaut du code généré passait inaperçu : il comptait les nulls comme « ignorées » et
 * réinitialisait le compteur, si bien que l'arrêt n'arrivait jamais.
 */
it("S'ARRÊTE après N échecs consécutifs au lieu de marteler ProductHunt", function () {
    foreach (range(1, 6) as $n) {
        phTool("https://www.producthunt.com/r/p/90{$n}");
    }

    $appels = 0;
    $this->mock(ToolDiscoveryService::class, function ($m) use (&$appels) {
        $m->shouldReceive('resolveProductHuntUrl')->andReturnUsing(function () use (&$appels) {
            $appels++;

            return null;
        });
    });

    $this->artisan('directory:resolve-producthunt --appliquer --pause=0 --max-echecs=3')
        ->assertFailed(); // l'arrêt précoce doit être un ÉCHEC, pour qu'un cron le remarque

    expect($appels)->toBe(3);
});

it('ne touche jamais une fiche dont l\'URL ne vise pas ProductHunt', function () {
    $t = phTool('https://siteordinaire.example.com/outil');

    $this->mock(ToolDiscoveryService::class, function ($m) {
        $m->shouldReceive('resolveProductHuntUrl')->never();
    });

    $this->artisan('directory:resolve-producthunt --appliquer --pause=0')->assertSuccessful();

    expect($t->fresh()->url)->toBe('https://siteordinaire.example.com/outil');
});

// L'historique est le SEUL moyen de revenir en arrière sur 260 fiches : s'il ne s'écrit pas,
// le rattrapage devient irréversible.
it("historise l'ancienne URL, sinon le retour arrière est impossible", function () {
    $t = phTool('https://www.producthunt.com/r/p/777');

    $this->mock(ToolDiscoveryService::class, function ($m) {
        $m->shouldReceive('resolveProductHuntUrl')->andReturn('https://nouveau.example.com');
    });

    $this->artisan('directory:resolve-producthunt --appliquer --pause=0')->assertSuccessful();

    $activites = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Tool::class)->where('subject_id', $t->id)->get();

    expect($activites)->not->toBeEmpty();
    $trouve = $activites->contains(function ($a) {
        $old = data_get($a->properties, 'old.url');

        return is_string($old) && str_contains($old, 'producthunt.com');
    });
    expect($trouve)->toBeTrue('L\'ancienne URL n\'a pas été historisée : le rollback serait impossible.');
});
