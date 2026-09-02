<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif incident « redirection ProductHunt » (2026-08-22) : tools:discover-new créait des
 * fiches d'annuaire dont l'adresse pointait vers la redirection de suivi ProductHunt
 * (producthunt.com/r/p/ID?app_id=...) au lieu du site réel du produit - 21 fiches corrigées à la
 * main, 3 nouvelles apparues entre-temps. Trois défauts distincts, trois garanties prouvées ici :
 *   1. resolveProductHuntUrl() suit désormais plusieurs sauts et échoue BRUYAMMENT (journalisé,
 *      retourne null) plutôt que de retomber silencieusement sur l'URL de suivi.
 *   2. ingest() refuse d'enregistrer une fiche dont l'hôte final est une page de découverte
 *      (producthunt.com, news.ycombinator.com...) - mécanisme blockedHosts existant étendu,
 *      jamais dupliqué. github.com et huggingface.co restent acceptés (adresses de produit
 *      légitimes pour certaines fiches, ex. MemoryCustodian → dépôt GitHub).
 *   3. cleanUrl() retire aussi app_id. ToolNameCleanerService signale les titres qui sont en
 *      réalité des commandes shell (flux hnrss.org/show) ; ingest() les rejette entièrement.
 *
 * Correctif CI 2026-08-30 (6 échecs Linux, cause établie par isolement - voir CHANGELOG) : les six
 * assertions qui comptaient Tool::count() en VALEUR ABSOLUE (toBe(0), toBe(1)) supposaient une
 * table directory_tools vide au départ. La migration 2026_08_30_140000_add_canirun_ai_directory_tool
 * (tâche #1910, une fiche RÉELLE insérée en donnée de migration, pas un seeder de test) tourne
 * comme toute autre migration sous RefreshDatabase et fait déjà partir le compte à 1. Corrigé en
 * mesurant un $before juste avant l'action et en comparant le DELTA, jamais la valeur absolue -
 * immunisé contre CETTE migration et contre toute future migration du même genre.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ToolDiscoveryService;
use Modules\Directory\Services\ToolNameCleanerService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr_CA']);
});

// --- Défaut A / correctif 1 : résolution multi-sauts, échec bruyant -----------------------------

it('résout une redirection ProductHunt en un seul saut vers le vrai domaine, et enregistre l\'adresse correcte', function () {
    Http::fake([
        'https://www.producthunt.com/r/p/111111' => Http::response('', 301, [
            'Location' => 'https://realproduct.example.com/?ref=producthunt',
        ]),
    ]);

    $service = new ToolDiscoveryService();
    $resolved = $service->resolveProductHuntUrl('https://www.producthunt.com/r/p/111111');

    expect($resolved)->toBe('https://realproduct.example.com/?ref=producthunt');

    // Chaîne complète : la fiche enregistrée porte l'adresse réelle, nettoyée (ref retiré).
    $tool = $service->ingest([
        'name' => 'Real Product',
        'url' => $resolved,
        'source' => 'rss:test',
    ]);

    expect($tool)->not->toBeNull();
    expect($tool->url)->toBe('https://realproduct.example.com/');
});

it('résout une chaîne de 2 sauts internes à producthunt.com puis un vrai domaine, et enregistre l\'adresse correcte', function () {
    Http::fake([
        'https://www.producthunt.com/r/p/222222' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/222223',
        ]),
        'https://www.producthunt.com/r/p/222223' => Http::response('', 301, [
            'Location' => 'https://realproduct2.example.com/',
        ]),
    ]);

    $service = new ToolDiscoveryService();
    $resolved = $service->resolveProductHuntUrl('https://www.producthunt.com/r/p/222222');

    expect($resolved)->toBe('https://realproduct2.example.com/');

    $tool = $service->ingest([
        'name' => 'Real Product Two',
        'url' => $resolved,
        'source' => 'rss:test',
    ]);

    expect($tool)->not->toBeNull();
    expect($tool->url)->toBe('https://realproduct2.example.com/');
});

it('refuse une redirection qui reste sur producthunt.com après 3 sauts, journalise l\'échec, et n\'enregistre rien', function () {
    Log::spy();
    // Les journaux de ToolDiscoveryService passent désormais par Log::channel('directory_discovery')
    // (config/logging.php) - un espion ne répond que null aux appels non déclarés, il faut donc que
    // channel() se retourne lui-même pour que le ->warning() chaîné qui suit reste observable.
    Log::shouldReceive('channel')->with('directory_discovery')->andReturnSelf();

    Http::fake([
        'https://www.producthunt.com/r/p/333330' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/333331',
        ]),
        'https://www.producthunt.com/r/p/333331' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/333332',
        ]),
        'https://www.producthunt.com/r/p/333332' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/333333',
        ]),
    ]);

    $service = new ToolDiscoveryService();
    $before = Tool::count();
    $resolved = $service->resolveProductHuntUrl('https://www.producthunt.com/r/p/333330');

    expect($resolved)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'producthunt.com après le nombre maximal de sauts')
                && is_array($context)
                && ($context['url_depart'] ?? null) === 'https://www.producthunt.com/r/p/333330';
        });

    // Défense en profondeur (correctif 2) : même si l'URL de suivi elle-même atteignait ingest()
    // sans passer par la résolution, elle serait refusée - jamais enregistrée en base.
    $tool = $service->ingest([
        'name' => 'Stuck Product',
        'url' => 'https://www.producthunt.com/r/p/333330',
        'source' => 'rss:test',
    ]);

    expect($tool)->toBeNull();
    expect(Tool::count())->toBe($before);
});

it('refuse une redirection ProductHunt en cas d\'exception réseau, journalise, et n\'enregistre aucune URL de suivi', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_discovery')->andReturnSelf();

    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connexion refusée (simulation test)');
    });

    $service = new ToolDiscoveryService();
    $resolved = $service->resolveProductHuntUrl('https://www.producthunt.com/r/p/444444');

    expect($resolved)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'exception réseau')
                && is_array($context)
                && str_contains((string) ($context['erreur'] ?? ''), 'Connexion refusée');
        });

    // La preuve qui compte : aucune fiche portant l'URL de suivi ne doit exister en base, même
    // en poussant explicitement cette URL de suivi (non résolue) dans ingest().
    $tool = $service->ingest([
        'name' => 'Exception Product',
        'url' => 'https://www.producthunt.com/r/p/444444',
        'source' => 'rss:test',
    ]);

    expect($tool)->toBeNull();
    expect(Tool::where('url', 'LIKE', '%producthunt.com%')->count())->toBe(0);
});

// --- Défaut B / correctif 1 (cleanUrl) : app_id ------------------------------------------------

it('retire app_id de l\'URL via cleanUrl() tout en conservant les autres paramètres', function () {
    $cleaned = ToolDiscoveryService::cleanUrl('https://realproduct.example.com/page?app_id=339&foo=bar&ref=producthunt');

    expect($cleaned)->toBe('https://realproduct.example.com/page?foo=bar');
});

// --- Défaut/correctif 2 (ingest refuse les agrégateurs, réutilise blockedHosts) ------------------

it('refuse d\'enregistrer une fiche dont l\'hôte final est producthunt.com, et journalise le refus', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_discovery')->andReturnSelf();

    $service = new ToolDiscoveryService();
    $before = Tool::count();
    $tool = $service->ingest([
        'name' => 'Produit Non Résolu',
        'url' => 'https://www.producthunt.com/posts/produit-non-resolu',
        'source' => 'producthunt',
    ]);

    expect($tool)->toBeNull();
    expect(Tool::count())->toBe($before);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'agrégateur')
                && is_array($context)
                && ($context['name'] ?? null) === 'Produit Non Résolu';
        });
});

it('NON-RÉGRESSION : une adresse github.com reste acceptée (adresse officielle légitime pour certains produits)', function () {
    $service = new ToolDiscoveryService();
    $tool = $service->ingest([
        'name' => 'MemoryCustodian',
        'url' => 'https://github.com/exemple-user/memorycustodian',
        'source' => 'rss:test',
    ]);

    expect($tool)->not->toBeNull();
    expect($tool->url)->toBe('https://github.com/exemple-user/memorycustodian');
});

it('NON-RÉGRESSION : une adresse huggingface.co reste acceptée', function () {
    $service = new ToolDiscoveryService();
    $tool = $service->ingest([
        'name' => 'HuggingFace Space Demo',
        'url' => 'https://huggingface.co/spaces/exemple/demo',
        'source' => 'rss:test',
    ]);

    expect($tool)->not->toBeNull();
});

// --- Défaut C / correctif 3 (titres qui sont des commandes shell) -------------------------------

it('détecte un titre ressemblant à une commande shell, sans faux positif sur un vrai nom de produit', function () {
    expect(ToolNameCleanerService::looksLikeShellCommand('npm i -g hotcell'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('npx create-app'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('pip install torch'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('pip3 install torch'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('yarn add left-pad'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('pnpm add left-pad'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('brew install ffmpeg'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('apt install ffmpeg'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('cargo install ripgrep'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('go get github.com/x/y'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('git clone https://github.com/x/y.git'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('curl https://get.example.sh'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('wget https://example.com/file'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('docker run -it ubuntu'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('$ rm -rf node_modules'))->toBeTrue()
        ->and(ToolNameCleanerService::looksLikeShellCommand('sudo apt install ffmpeg'))->toBeTrue()
        // Faux positifs à éviter : un vrai nom de produit ne doit jamais matcher un préfixe seul.
        ->and(ToolNameCleanerService::looksLikeShellCommand('Aptible'))->toBeFalse()
        ->and(ToolNameCleanerService::looksLikeShellCommand('Go Getter'))->toBeFalse()
        ->and(ToolNameCleanerService::looksLikeShellCommand('Dockerize'))->toBeFalse()
        ->and(ToolNameCleanerService::looksLikeShellCommand('Cleanlist AI'))->toBeFalse();
});

it('rejette entièrement une fiche dont le titre est une commande npm (décision : rejet complet, voir rapport)', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_discovery')->andReturnSelf();

    $service = new ToolDiscoveryService();
    $before = Tool::count();
    $tool = $service->ingest([
        'name' => 'npm i -g hotcell',
        'url' => 'https://hotcell.example.com',
        'source' => 'rss:show-hn',
    ]);

    expect($tool)->toBeNull();
    expect(Tool::count())->toBe($before);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'commande shell')
                && is_array($context)
                && ($context['name'] ?? null) === 'npm i -g hotcell';
        });
});

it('laisse passer un titre normal intact, et la fiche est acceptée', function () {
    expect(ToolNameCleanerService::clean('Cleanlist AI'))->toBe('Cleanlist AI');

    $service = new ToolDiscoveryService();
    $tool = $service->ingest([
        'name' => 'Cleanlist AI',
        'url' => 'https://cleanlist.example.com',
        'source' => 'rss:test',
    ]);

    expect($tool)->not->toBeNull();
    expect($tool->getTranslation('name', 'fr_CA', false))->toBe('Cleanlist AI');
});

// --- Canal dédié 'directory_discovery' (correctif 2026-08-22, config/logging.php) ---------------
//
// Même parade que #1840 (Modules/Directory/tests/Feature/DeriveMasterFromUploadTest.php,
// fonctions dmfuScreenshotsLogPath()/dmfuResetScreenshotsLog()) : un test qui se contenterait de
// vérifier qu'un Log::channel('directory_discovery') a été appelé (mock) ne prouverait PAS que le
// message survit à LOG_LEVEL=error en production - seul un niveau global forcé au plus restrictif
// possible puis une lecture du fichier RÉEL du canal dédié le prouve. C'est exactement le défaut
// que ce correctif corrige (voir docs/CONTRAINTES-SOUS-AGENTS.md, section 6).

/** Chemin du fichier daily du jour pour le canal 'directory_discovery' (voir config/logging.php). */
function tdurlDiscoveryLogPath(): string
{
    return storage_path('logs/directory_discovery-'.now()->format('Y-m-d').'.log');
}

/** Chemin du fichier daily du jour pour le canal PAR DÉFAUT du projet (.env : LOG_CHANNEL=daily). */
function tdurlDefaultLogPath(): string
{
    return storage_path('logs/laravel-'.now()->format('Y-m-d').'.log');
}

/** Repart de fichiers de log vides pour isoler le contenu produit par CE test. */
function tdurlResetLogs(): void
{
    @unlink(tdurlDiscoveryLogPath());
    @unlink(tdurlDefaultLogPath());
}

it('#2026-08-22 : l\'échec de résolution ProductHunt s\'écrit sur le canal directory_discovery, pas sur le canal par défaut, même avec un niveau de log global très restrictif', function () {
    // Simule la config de PRODUCTION diagnostiquée (LOG_LEVEL=error) - ici encore plus restrictif
    // ('emergency') - pour prouver que SEUL le hard-code 'level' => 'info' du canal
    // 'directory_discovery' (config/logging.php) rend la ligne observable, indépendamment de tout
    // réglage global. Un test qui ne ferait que vérifier l'appel (sans ce blindage) ne prouverait
    // rien : c'est précisément le défaut signalé par l'exécutant du chantier précédent.
    config([
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
    ]);

    tdurlResetLogs();

    Http::fake([
        'https://www.producthunt.com/r/p/666660' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/666661',
        ]),
        'https://www.producthunt.com/r/p/666661' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/666662',
        ]),
        'https://www.producthunt.com/r/p/666662' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/666663',
        ]),
    ]);

    $service = new ToolDiscoveryService();
    $resolved = $service->resolveProductHuntUrl('https://www.producthunt.com/r/p/666660');

    expect($resolved)->toBeNull();

    // Canal dédié : le fichier existe et porte la ligne attendue.
    expect(file_exists(tdurlDiscoveryLogPath()))->toBeTrue();
    $dedicated = file_get_contents(tdurlDiscoveryLogPath());
    expect($dedicated)->toContain('producthunt.com après le nombre maximal de sauts')
        ->and($dedicated)->toContain('666660');

    // Canal par défaut : rien n'y a fuité (fichier absent, ou présent mais sans cette ligne).
    if (file_exists(tdurlDefaultLogPath())) {
        expect(file_get_contents(tdurlDefaultLogPath()))->not->toContain('producthunt.com après le nombre maximal de sauts');
    }
});

it('finition 2026-08-22 : l\'avertissement "jeton ProductHunt absent" s\'écrit sur le canal directory_discovery, pas sur le canal par défaut, même avec un niveau de log global très restrictif', function () {
    // Même méthode EXACTE que le test précédent (niveau global forcé au plus restrictif possible,
    // puis lecture du VRAI fichier de journal) : un mock ne prouverait que l'appel à Log::warning,
    // jamais la survie à LOG_LEVEL=error en production, qui est tout l'enjeu. Cet avertissement est
    // le PLUS IMPORTANT des cinq du pipeline (voir commentaire dans ToolDiscoveryService::fetchProductHunt()) :
    // c'est l'absence de jeton qui fait basculer la découverte sur la seule voie RSS, celle qui
    // produisait les mauvaises adresses. S'il reste invisible, rien n'explique pourquoi la voie
    // ProductHunt n'est pas utilisée.
    config([
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
        'directory.producthunt_token' => null,
    ]);

    tdurlResetLogs();

    $service = new ToolDiscoveryService();
    $tools = $service->fetchProductHunt();

    expect($tools)->toBe([]);

    // Canal dédié : le fichier existe et porte la ligne attendue.
    expect(file_exists(tdurlDiscoveryLogPath()))->toBeTrue();
    $dedicated = file_get_contents(tdurlDiscoveryLogPath());
    expect($dedicated)->toContain('ProductHunt token non configuré');

    // Canal par défaut : rien n'y a fuité (fichier absent, ou présent mais sans cette ligne).
    if (file_exists(tdurlDefaultLogPath())) {
        expect(file_get_contents(tdurlDefaultLogPath()))->not->toContain('ProductHunt token non configuré');
    }
});

// --- Bilan de fin d'exécution (correctif 2026-08-22, point 3 du mandat) -------------------------

it('bilan de fin d\'exécution : les compteurs de getDiscoveryStats() sont exacts sur un lot mêlant des acceptés et un refus de chacun des 4 motifs', function () {
    Http::fake([
        // Reste bloqué sur producthunt.com après 3 sauts -> motif "adresse non résolue".
        'https://www.producthunt.com/r/p/888880' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/888881',
        ]),
        'https://www.producthunt.com/r/p/888881' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/888882',
        ]),
        'https://www.producthunt.com/r/p/888882' => Http::response('', 301, [
            'Location' => 'https://www.producthunt.com/r/p/888883',
        ]),
    ]);

    $service = new ToolDiscoveryService();

    // 1) et 2) Acceptés - deux fiches neuves, noms et domaines sans rapport entre eux (vérifié
    // hors test que similar_text() reste < 85 entre "Nimbostrat Deploy" et "Kelvinforge Sync").
    expect($service->ingest([
        'name' => 'Nimbostrat Deploy',
        'url' => 'https://nimbostrat-deploy.example.com',
        'source' => 'rss:test',
    ]))->not->toBeNull();

    expect($service->ingest([
        'name' => 'Kelvinforge Sync',
        'url' => 'https://kelvinforge-sync.example.com',
        'source' => 'rss:test',
    ]))->not->toBeNull();

    // 3) Refusé - doublon (même domaine que le candidat 1, nom sans rapport - la dédup par
    // domaine est vérifiée AVANT la dédup par nom flou, donc la similarité du nom n'entre pas
    // en jeu ici).
    expect($service->ingest([
        'name' => 'Zibsonic Metrics',
        'url' => 'https://nimbostrat-deploy.example.com/pricing',
        'source' => 'rss:test',
    ]))->toBeNull();

    // 4) Refusé - hôte d'agrégateur.
    expect($service->ingest([
        'name' => 'Bilan Produit Agregateur',
        'url' => 'https://news.ycombinator.com/item?id=999999',
        'source' => 'rss:test',
    ]))->toBeNull();

    // 5) Refusé - titre ressemblant à une commande shell.
    expect($service->ingest([
        'name' => 'npm i -g bilan-outil',
        'url' => 'https://bilan-outil.example.com',
        'source' => 'rss:show-hn',
    ]))->toBeNull();

    // 6) Refusé - adresse ProductHunt non résolue.
    expect($service->resolveProductHuntUrl('https://www.producthunt.com/r/p/888880'))->toBeNull();

    $stats = $service->getDiscoveryStats();

    expect($stats['accepted'])->toBe(2)
        ->and($stats['refused']['doublon'])->toBe(1)
        ->and($stats['refused']['agregateur'])->toBe(1)
        ->and($stats['refused']['titre_commande'])->toBe(1)
        ->and($stats['refused']['adresse_non_resolue'])->toBe(1)
        ->and($stats['refused_total'])->toBe(4)
        ->and($stats['examined'])->toBe(6);
});

// --- Finition 2026-08-22 (point 2 du mandat) : message par item aligné sur le VRAI motif --------
//
// Avant ce correctif, tools:discover-new affichait « Doublon, ignoré. » pour LES TROIS motifs de
// refus d'ingest(), y compris quand l'hôte est un agrégateur ou que le titre ressemble à une
// commande shell - trompeur pour qui lance la commande à la main. Les trois tests suivants
// exercent la vraie ligne affichée (DiscoverNewToolsCommand::refusalLabel(), pas seulement l'état
// interne du service) via --source=producthunt, chacun sur un seul candidat pour ne laisser aucune
// ambiguïté sur quelle ligne prouve quoi. --force évite toute dépendance à l'état du kill switch
// Pennant 'cron.directory-discovery' (défini à true par défaut, mais --force le rend inutile de le
// vérifier ici - hors périmètre de cette suite).

it('finition 2026-08-22 : motif "agregateur" - le message affiché nomme la vraie raison, jamais « Doublon »', function () {
    config(['directory.producthunt_token' => 'jeton-test-motifs']);

    Http::fake([
        'https://api.producthunt.com/v2/api/graphql' => Http::response([
            'data' => ['posts' => ['edges' => [[
                'node' => [
                    'name' => 'Fiche Motif Agregateur',
                    'tagline' => 'Test motif agrégateur',
                    'website' => 'https://news.ycombinator.com/item?id=555555',
                    'pricingType' => 'FREE',
                ],
            ]]]],
        ], 200),
    ]);

    $before = Tool::count();

    $this->artisan('tools:discover-new', ['--source' => 'producthunt', '--force' => true])
        ->expectsOutputToContain('Hôte agrégateur, ignoré.')
        ->doesntExpectOutputToContain('Doublon, ignoré.')
        ->assertSuccessful();

    expect(Tool::count())->toBe($before);
});

it('finition 2026-08-22 : motif "titre_commande" - le message affiché nomme la vraie raison, jamais « Doublon »', function () {
    config(['directory.producthunt_token' => 'jeton-test-motifs']);

    Http::fake([
        'https://api.producthunt.com/v2/api/graphql' => Http::response([
            'data' => ['posts' => ['edges' => [[
                'node' => [
                    'name' => 'npm i -g fiche-motif-titre',
                    'tagline' => 'Test motif titre commande',
                    'website' => 'https://fiche-motif-titre.example.com',
                    'pricingType' => 'FREE',
                ],
            ]]]],
        ], 200),
    ]);

    $before = Tool::count();

    $this->artisan('tools:discover-new', ['--source' => 'producthunt', '--force' => true])
        ->expectsOutputToContain('Titre ressemblant à une commande, ignoré.')
        ->doesntExpectOutputToContain('Doublon, ignoré.')
        ->assertSuccessful();

    expect(Tool::count())->toBe($before);
});

it('finition 2026-08-22 : motif "doublon" - le message affiché reste « Doublon, ignoré. » quand c\'en est vraiment un', function () {
    $service = new ToolDiscoveryService();
    $existing = $service->ingest([
        'name' => 'Fiche Existante Motif Doublon',
        'url' => 'https://fiche-motif-doublon.example.com',
        'source' => 'rss:test',
    ]);
    expect($existing)->not->toBeNull();
    $before = Tool::count();

    config(['directory.producthunt_token' => 'jeton-test-motifs']);

    Http::fake([
        'https://api.producthunt.com/v2/api/graphql' => Http::response([
            'data' => ['posts' => ['edges' => [[
                'node' => [
                    'name' => 'Fiche Motif Doublon Nom Different',
                    'tagline' => 'Test motif doublon',
                    'website' => 'https://fiche-motif-doublon.example.com/pricing',
                    'pricingType' => 'FREE',
                ],
            ]]]],
        ], 200),
    ]);

    $this->artisan('tools:discover-new', ['--source' => 'producthunt', '--force' => true])
        ->expectsOutputToContain('Doublon, ignoré.')
        ->doesntExpectOutputToContain('Hôte agrégateur, ignoré.')
        ->doesntExpectOutputToContain('Titre ressemblant à une commande, ignoré.')
        ->assertSuccessful();

    // Le compte n'a pas bougé depuis $before (capturé juste après la création de $existing) : le
    // candidat découvert a bien été refusé, pas inséré.
    expect(Tool::count())->toBe($before);
});

// --- Ticket #2175 (mesuré 2026-09-02) : le pipeline fabrique un doublon quand le NOM est ---------
// identique mais le DOMAINE diffère entièrement (site officiel vs page ProductHunt / domaine
// miroir). Mesure sur les données réelles de production : 440 fiches créées par ce pipeline depuis
// juillet 2026 (cluster horaire 04h00, signature du cron tools:discover-new->dailyAt('04:00')),
// dont 2 auraient dû être refusées (Animos App, NoMac.app - les 3 autres paires connues, Voiser AI/
// CaseGap AI/Thinnest AI, datent de mai 2026, hors fenêtre "depuis juillet" du mandat). Cause
// établie par reproduction exacte du calcul de matchesName() sur les 5 paires réelles :
// similar_text() rend 75 à 84 % - jamais 100 %, toujours sous le seuil de 85 - à cause du suffixe
// («\u{a0}AI\u{a0}»/«\u{a0}App\u{a0}») retiré du SEUL candidat entrant (ligne ~422), jamais du nom déjà
// en base. La forme suggérée par le mandat (bloquer sur URL normalisée ET nom normalisé, les deux)
// a été mesurée et ÉCARTÉE : les 5 doublons réels n'ont JAMAIS le même domaine (c'est précisément
// pourquoi ils existent), une garde qui exige aussi l'URL n'en aurait bloqué AUCUN. D'où un
// contrôle par nom SEUL - vérifié séparément ci-dessous pour ne pas bloquer à tort deux produits
// d'une même famille qui partagent un domaine.
it('#2175 : refuse un second candidat dont le nom est identique mais dont le domaine diffère entièrement (motif réel juillet 2026)', function () {
    $service = new ToolDiscoveryService();

    $first = $service->ingest([
        'name' => 'Zumbrota AI',
        'url' => 'https://zumbrota.ai/product',
        'source' => 'producthunt',
    ]);
    expect($first)->not->toBeNull();

    // Même nom exact, domaine totalement différent (mimique un miroir/domaine secondaire, comme
    // « bagel.ai » vs « getbagel.com » observé pour de vrai le 2026-05-07 sur la fiche Bagel AI).
    // AVANT correctif : similar_text('zumbrota', 'zumbrota ai') = 80 % < 85 -> accepté à tort.
    $second = $service->ingest([
        'name' => 'Zumbrota AI',
        'url' => 'https://getzumbrota.example.net',
        'source' => 'rss:producthunt',
    ]);

    expect($second)->toBeNull();
    expect($service->getLastRefusalReason())->toBe('doublon');
});

it('#2175 : matchesNameExact() distingue Stability AI de Stable Diffusion, et ElevenLabs de ElevenAgents Guardrails 2.0, même avec une URL identique', function () {
    // Reproduction directe des DEUX cas légitimes trouvés en production (mêmes noms, même URL
    // réelle stability.ai / elevenlabs.io) : une garde par URL seule les aurait bloqués à tort: ce
    // test vérifie explicitement que la garde par NOM (celle réellement livrée) ne les confond pas.
    $stableDiffusion = new Tool;
    $stableDiffusion->setTranslation('name', 'fr_CA', 'Stable Diffusion');
    $stableDiffusion->url = 'https://stability.ai';

    $elevenAgents = new Tool;
    $elevenAgents->setTranslation('name', 'fr_CA', 'ElevenAgents Guardrails 2.0');
    $elevenAgents->url = 'https://elevenlabs.io';

    $stabilityAiCandidate = ToolNameCleanerService::normalizeForComparison('Stability AI');
    $elevenLabsCandidate = ToolNameCleanerService::normalizeForComparison('ElevenLabs');

    expect($stableDiffusion->matchesNameExact($stabilityAiCandidate))->toBeFalse();
    expect($elevenAgents->matchesNameExact($elevenLabsCandidate))->toBeFalse();

    // Non-régression : ce même mécanisme reconnaît bien un nom réellement identique.
    $sameNameDifferentCase = ToolNameCleanerService::normalizeForComparison('stable diffusion');
    expect($stableDiffusion->matchesNameExact($sameNameDifferentCase))->toBeTrue();
});

it('#2175 : matchesNameExact() reconnaît aussi un alias normalisé, pas seulement le nom principal', function () {
    $tool = new Tool;
    $tool->setTranslation('name', 'fr_CA', 'GPT Chat Pro');
    $tool->aliases = ['ChatGPT Plus', 'GPT-4 Turbo'];

    expect($tool->matchesNameExact(ToolNameCleanerService::normalizeForComparison('chatgpt plus')))->toBeTrue();
    expect($tool->matchesNameExact(ToolNameCleanerService::normalizeForComparison('Something Else Entirely')))->toBeFalse();
});

it('#2175 (portée) : documente un comportement PRÉ-EXISTANT et hors mandat - le contrôle par domaine (host-substring, inchangé par ce correctif) bloque déjà un produit-frère qui partagerait EXACTEMENT le même domaine, même à nom différent', function () {
    // Ne prouve rien sur LE NOUVEAU contrôle (les noms diffèrent, il ne s'en mêle pas) : montre
    // que si Stability AI et Stable Diffusion partageaient un domaine ET arrivaient via CE
    // pipeline, le contrôle par domaine déjà en place (identique au cas « Zibsonic Metrics »
    // plus haut) les refuserait quand même - un fait pré-existant, pas un effet de bord de #2175.
    // Le corriger exigerait sa propre mesure de faux positifs, hors périmètre de ce ticket.
    $service = new ToolDiscoveryService();

    $first = $service->ingest([
        'name' => 'Stability AI',
        'url' => 'https://stability-demo.example.org',
        'source' => 'rss:test',
    ]);
    expect($first)->not->toBeNull();

    $second = $service->ingest([
        'name' => 'Stable Diffusion',
        'url' => 'https://stability-demo.example.org/stable-diffusion',
        'source' => 'rss:test',
    ]);

    expect($second)->toBeNull();
    expect($service->getLastRefusalReason())->toBe('doublon');
});
