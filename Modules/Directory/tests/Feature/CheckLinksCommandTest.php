<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests `php artisan directory:check-links --fix` - corrige la confusion entre « la fiche a
 * vraiment disparu » (404/410, seule famille qui justifie la quarantaine) et « le site est
 * vivant mais refuse notre robot » (401/403/405/429) ou « ennui serveur probablement
 * transitoire » (5xx, timeouts). Avant ce correctif, tout code >= 400 mettait la fiche en
 * quarantaine (status=draft) sans distinction, retirant de l'annuaire public des outils bien
 * vivants (ProductHunt, tout ce qui est derrière Cloudflare) simplement parce qu'ils bloquent
 * les robots identifiables (User-Agent LaVeilleBot/1.0).
 *
 * Lot du 2026-09-13 : la commande existait deja et classait correctement, mais n'ecrivait rien
 * sur la fiche (le resultat disparaissait a chaque execution) et n'etait planifiee nulle part.
 * Les tests ci-dessous couvrent l'observation ecrite sur `directory_tools` (url_last_checked_at,
 * url_last_status, url_last_note, url_failure_streak), avec CONTRE-EPREUVE : chaque assertion
 * nouvelle a ete verifiee rouge avant le correctif, verte apres (voir rapport de session).
 *
 * Chaque test 404 fake AUSSI la variante www.-prefixee : depuis ce lot, un 404 declenche un
 * essai de cette variante avant de conclure (voir CheckLinksCommand::checkWwwVariant()) - sans
 * ce fake, le test tenterait un vrai appel reseau vers un domaine .invalid inexistant.
 *
 * IMPORTANT - tous les motifs Http::fake() ci-dessous incluent le schema complet
 * ("https://hote.../*") et jamais seulement l'hote nu ("hote.../*") : Http::fake() prefixe
 * chaque motif d'un "*" implicite (Illuminate\Http\Client\Factory::stubUrl()) et le compare par
 * SOUS-CHAINE. Un motif nu "outil-x.../*" correspond alors AUSSI a "www.outil-x.../*" (sous-
 * chaine incluse), et - pire - Http::fake() EVALUE tous les motifs enregistres pour chaque
 * requete (Collection::map() n'est pas paresseux) : meme un motif "perdant" dont la reponse est
 * ignoree consomme quand meme un element s'il s'agit d'un Http::sequence(). Mesure en direct le
 * 2026-09-13 : sans le schema, une sequence de 3 reponses etait epuisee apres 1 seule execution
 * de la commande (2 requetes internes - le controle principal + la variante www - consommaient
 * chacune un element). Ancrer le motif sur le schema complet elimine la collision de sous-chaine
 * entre un hote et sa variante www., sans dependre de l'ordre d'enregistrement.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function makeCheckLinksTestTool(string $slug, string $url): Tool
{
    $tool = new Tool();
    $tool->url = $url;
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', ucfirst($slug));
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Outil de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Test.');
    $tool->save();

    return $tool;
}

test('un 404 avec --fix met la fiche en quarantaine (disparu confirme)', function () {
    $tool = makeCheckLinksTestTool('outil-404', 'https://outil-404.exemple-test.invalid/page');

    Http::fake([
        'https://outil-404.exemple-test.invalid/*' => Http::response('', 404),
        'https://www.outil-404.exemple-test.invalid/*' => Http::response('', 404),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('draft');
});

test('un 410 avec --fix met la fiche en quarantaine (disparu confirme)', function () {
    $tool = makeCheckLinksTestTool('outil-410', 'https://outil-410.exemple-test.invalid/page');

    Http::fake([
        'https://outil-410.exemple-test.invalid/*' => Http::response('', 410),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('draft');
});

test('un 403 avec --fix ne met PAS la fiche en quarantaine (site vivant qui refuse le robot)', function () {
    $tool = makeCheckLinksTestTool('outil-403', 'https://outil-403.exemple-test.invalid/page');

    Http::fake([
        'https://outil-403.exemple-test.invalid/*' => Http::response('', 403),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('un 429 avec --fix ne met PAS la fiche en quarantaine (limitation de debit, transitoire)', function () {
    $tool = makeCheckLinksTestTool('outil-429', 'https://outil-429.exemple-test.invalid/page');

    Http::fake([
        'https://outil-429.exemple-test.invalid/*' => Http::response('', 429),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('un 500 avec --fix ne met PAS la fiche en quarantaine (ennui serveur probablement transitoire)', function () {
    $tool = makeCheckLinksTestTool('outil-500', 'https://outil-500.exemple-test.invalid/page');

    Http::fake([
        'https://outil-500.exemple-test.invalid/*' => Http::response('', 500),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('sans --fix, meme un 404 ne modifie rien', function () {
    $tool = makeCheckLinksTestTool('outil-404-sans-fix', 'https://outil-404-sans-fix.exemple-test.invalid/page');

    Http::fake([
        'https://outil-404-sans-fix.exemple-test.invalid/*' => Http::response('', 404),
        'https://www.outil-404-sans-fix.exemple-test.invalid/*' => Http::response('', 404),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('le resultat du controle est ecrit sur les 4 colonnes de la fiche (un 200 simple)', function () {
    $tool = makeCheckLinksTestTool('outil-observation', 'https://outil-observation.exemple-test.invalid/page');

    Http::fake([
        'https://outil-observation.exemple-test.invalid/*' => Http::response('OK', 200),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->url_last_checked_at)->not->toBeNull();
    expect($fresh->url_last_status)->toBe('200');
    expect($fresh->url_last_note)->toBeNull();
    expect($fresh->url_failure_streak)->toBe(0);
});

test('url_failure_streak monte sur des 404 consecutifs et retombe a 0 des que l\'adresse repond', function () {
    $tool = makeCheckLinksTestTool('outil-streak', 'https://outil-streak.exemple-test.invalid/page');

    // Un seul Http::fake() pour toute la sequence (404, 404, 200 sur 3 executions successives) :
    // Http::fake() ACCUMULE les motifs au lieu de les remplacer (Factory::fake() fait un merge,
    // jamais un reset) et REEVALUE tous les motifs a chaque requete, donc un Http::sequence()
    // enregistre plus tot serait consomme aussi par les requetes de la variante www si les motifs
    // n'etaient pas ancres sur le schema complet (voir le commentaire d'en-tete du fichier).
    Http::fake([
        'https://www.outil-streak.exemple-test.invalid/*' => Http::response('', 404),
        'https://outil-streak.exemple-test.invalid/*' => Http::sequence()
            ->push('', 404)
            ->push('', 404)
            ->push('OK', 200),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);
    expect($tool->fresh()->url_failure_streak)->toBe(1);
    expect($tool->fresh()->url_last_status)->toBe('404');

    // Deuxieme 404 consecutif : le compteur continue de monter.
    $this->artisan('directory:check-links')->assertExitCode(0);
    expect($tool->fresh()->url_failure_streak)->toBe(2);

    // L'adresse repond enfin (200) : le compteur retombe a 0.
    $this->artisan('directory:check-links')->assertExitCode(0);
    expect($tool->fresh()->url_failure_streak)->toBe(0);
});

test('un 403 repete ne fait jamais progresser url_failure_streak et enregistre l\'extrait du corps', function () {
    $tool = makeCheckLinksTestTool('outil-403-streak', 'https://outil-403-streak.exemple-test.invalid/page');

    Http::fake([
        'https://outil-403-streak.exemple-test.invalid/*' => Http::response('Just a moment...', 403),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);
    $fresh = $tool->fresh();
    expect($fresh->url_failure_streak)->toBe(0);
    expect($fresh->url_last_note)->toBe('Just a moment...');

    $this->artisan('directory:check-links')->assertExitCode(0);
    expect($tool->fresh()->url_failure_streak)->toBe(0);
});

test('un extrait du corps est enregistre pour un 503 (distingue panne reelle et service en demarrage)', function () {
    $tool = makeCheckLinksTestTool('outil-503', 'https://outil-503.exemple-test.invalid/page');

    Http::fake([
        'https://outil-503.exemple-test.invalid/*' => Http::response('{"status": "warming_up"}', 503),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->url_last_status)->toBe('503');
    expect($fresh->url_last_note)->toBe('{"status": "warming_up"}');
});

test('avant de conclure a un 404, la variante www est tentee et notee sans modifier l\'URL', function () {
    $tool = makeCheckLinksTestTool('outil-www-vivant', 'https://outil-www-vivant.exemple-test.invalid/page');

    Http::fake([
        'https://outil-www-vivant.exemple-test.invalid/*' => Http::response('', 404),
        'https://www.outil-www-vivant.exemple-test.invalid/*' => Http::response('OK', 200),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->url_last_status)->toBe('404');
    expect($fresh->url_last_note)->toBe('variante www répond 200');
    // La logique de quarantaine existante n'est jamais modifiee par cette observation : sans
    // --fix, rien ne bouge, exactement comme avant ce lot.
    expect($fresh->url)->toBe('https://outil-www-vivant.exemple-test.invalid/page');
    expect($fresh->status)->toBe('published');
});

test('un controle ecrit le resultat sans faire avancer updated_at de la fiche', function () {
    $tool = makeCheckLinksTestTool('outil-updated-at', 'https://outil-updated-at.exemple-test.invalid/page');
    $updatedAtAvant = $tool->fresh()->updated_at;

    // On avance l'horloge pour que le test soit probant : si le code touchait updated_at par
    // erreur (ex. via Eloquent save()), la nouvelle valeur serait necessairement differente et
    // le test le detecterait - une simple egalite de timestamps identiques pourrait sinon n'etre
    // qu'un hasard de vitesse d'execution.
    $this->travel(2)->hours();

    Http::fake([
        'https://outil-updated-at.exemple-test.invalid/*' => Http::response('OK', 200),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->url_last_checked_at)->not->toBeNull();
    expect($fresh->updated_at->equalTo($updatedAtAvant))->toBeTrue();
});

test('--limit restreint le nombre de fiches controlees', function () {
    makeCheckLinksTestTool('outil-limit-1', 'https://outil-limit-1.exemple-test.invalid/page');
    makeCheckLinksTestTool('outil-limit-2', 'https://outil-limit-2.exemple-test.invalid/page');

    Http::fake([
        'https://outil-limit-1.exemple-test.invalid/*' => Http::response('OK', 200),
        'https://outil-limit-2.exemple-test.invalid/*' => Http::response('OK', 200),
    ]);

    $this->artisan('directory:check-links', ['--limit' => 1])->assertExitCode(0);

    $controles = Tool::query()->whereNotNull('url_last_checked_at')->count();
    expect($controles)->toBe(1);
});
