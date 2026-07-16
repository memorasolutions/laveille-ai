<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — module Decido (sondages collectifs type Framadate amélioré).
 * Pattern calqué sur Modules\Books\Tests\Feature\BooksLibraryTest.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Decido\Models\Poll;
use Modules\Decido\Models\PollOption;
use Modules\Decido\Models\PollVote;
use Modules\Decido\Services\PollExportService;
use Modules\Decido\Services\SlotGenerationService;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->superadmin = User::factory()->create([
        'email' => config('app.superadmin_email'),
    ]);
    $this->superadmin->assignRole('super_admin');

    config()->set('decido.under_construction', true);
});

function decidoCreatePoll(array $overrides = []): Poll
{
    $poll = new Poll;
    $poll->title = $overrides['title'] ?? 'Sondage de test';
    $poll->type = $overrides['type'] ?? 'classic';
    $poll->vote_mode = $overrides['vote_mode'] ?? 'single_choice';
    $poll->timezone = $overrides['timezone'] ?? 'America/Toronto';
    $poll->status = $overrides['status'] ?? 'open';
    $poll->creator_id = $overrides['creator_id'] ?? User::factory()->create()->id;
    $poll->admin_token_hash = hash('sha256', $overrides['admin_token'] ?? 'plain-admin-token');

    if (isset($overrides['final_option_id'])) {
        $poll->final_option_id = $overrides['final_option_id'];
    }

    $poll->save();

    return $poll;
}

// ── Gate under_construction ────────────────────────────────────────────────

test('guest sur /decido/creer est redirigé vers la connexion (auth requise avant même le gate)', function (): void {
    // `auth` s'exécute avant le gate under_construction pour cette route (Laravel réordonne
    // les middlewares selon $middlewarePriority) : un guest est redirigé au login, jamais 503
    // directement. Le vrai périmètre de sécurité (bloquer les non-superadmin) est prouvé par
    // le test suivant (utilisateur CONNECTÉ non-superadmin → 503).
    $this->get(route('decido.create'))->assertRedirect();
});

test('utilisateur connecté non-superadmin reçoit aussi 503 (seul superadmin bypass)', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('decido.create'))->assertStatus(503);
});

test('superadmin voit la liste des sondages (200)', function (): void {
    $this->actingAs($this->superadmin)->get(route('decido.index'))->assertStatus(200);
});

test('superadmin voit l’assistant de création (200)', function (): void {
    $this->actingAs($this->superadmin)->get(route('decido.create'))->assertStatus(200);
});

test('la liste "Mes sondages" contient un lien Gérer fonctionnel (owner-bypass, sans jeton valide)', function (): void {
    // Round 4 adversarial : authorizeManage() a toujours un bypass propriétaire (Auth::id() ===
    // creator_id) mais aucune vue ne générait de lien l'utilisant - impasse UX pour le créateur
    // qui n'a plus son jeton admin. Le lien utilise un jeton placeholder, invalide en tant que
    // tel, mais accepté car l'utilisateur connecté est bien le créateur du sondage.
    $poll = decidoCreatePoll(['creator_id' => $this->superadmin->id]);

    $indexResponse = $this->actingAs($this->superadmin)->get(route('decido.index'));
    $indexResponse->assertStatus(200);
    $indexResponse->assertSee(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'proprietaire']), false);

    $this->actingAs($this->superadmin)
        ->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'proprietaire']))
        ->assertStatus(200);
});

// ── Création ────────────────────────────────────────────────────────────────

test('superadmin peut créer un sondage de dates', function (): void {
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Réunion équipe',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:30',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate],
    ]);

    $poll = Poll::where('title', 'Réunion équipe')->first();

    expect($poll)->not->toBeNull();
    $response->assertRedirect(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => $response->getSession()->get('admin_token_plain') ?? '']));

    $this->assertSame('open', $poll->status->value);
    $this->assertGreaterThan(0, $poll->options()->count());
    $this->assertNotNull($poll->options()->first()->starts_at);
    $this->assertNotNull($poll->options()->first()->ends_at);

    // Les paramètres de génération de créneaux doivent être persistés sur le sondage lui-même
    // (pas seulement utilisés en mémoire par SlotGenerationService), pour toute fonctionnalité
    // future de modification/régénération - trouvé NULL en base par une passe adversariale.
    $poll = $poll->fresh();
    $this->assertSame(30, $poll->duration_minutes);
    $this->assertSame('09:00', $poll->range_start_time);
    $this->assertSame('10:30', $poll->range_end_time);
    $this->assertSame(30, $poll->step_minutes);
});

test('superadmin peut créer un sondage classique', function (): void {
    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Choix du resto',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Sushi'],
    ]);

    $poll = Poll::where('title', 'Choix du resto')->first();

    expect($poll)->not->toBeNull();
    $response->assertStatus(302);

    $this->assertSame(2, $poll->options()->count());
    $this->assertNull($poll->options()->first()->starts_at);
    $this->assertNull($poll->options()->first()->ends_at);
});

test('création sans titre retourne une erreur de validation, pas une exception', function (): void {
    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Sushi'],
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('title');
});

test('61 dates candidates dépasse le plafond de validation (max:60)', function (): void {
    // Round 9 (skill /100) : aucune borne n'existait sur candidate_dates, contrairement au type
    // classique déjà plafonné à 20 options.
    $dates = collect(range(1, 61))->map(fn ($i) => now()->addDays($i)->format('Y-m-d'))->all();

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage trop de dates',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => $dates,
    ]);

    $response->assertSessionHasErrors('candidate_dates');
    expect(Poll::where('title', 'Sondage trop de dates')->exists())->toBeFalse();
});

test('un volume total de créneaux excessif (>500) est refusé même sous 60 dates candidates', function (): void {
    // Round 9 (skill /100) : 60 dates x plage large x pas court peut encore générer des
    // centaines de créneaux par date - le plafond de dates seul ne suffit pas. 3800 créneaux
    // créés en test réel avant ce fix (40 dates x plage 00:00-23:45 x pas 15 min).
    config()->set('decido.under_construction', false);
    $dates = collect(range(1, 30))->map(fn ($i) => now()->addDays($i)->format('Y-m-d'))->all();

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage volume excessif',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 15,
        'range_start_time' => '00:00',
        'range_end_time' => '23:45',
        'step_minutes' => 15,
        'candidate_dates' => $dates,
    ]);

    $response->assertSessionHasErrors('candidate_dates');
    expect(Poll::where('title', 'Sondage volume excessif')->exists())->toBeFalse();
});

test('soumettre deux fois la même date candidate est rejeté (distinct) au lieu de créer des créneaux dupliqués', function (): void {
    // Round 11 (skill /100) : avant le fix, cette requête créait le sondage avec DEUX jeux de
    // créneaux strictement identiques pour la même date (labels et starts_at/ends_at
    // identiques), scindant silencieusement les votes entre deux PollOption distinctes.
    config()->set('decido.under_construction', false);
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage date dupliquée',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate, $futureDate],
    ]);

    $response->assertSessionHasErrors('candidate_dates.0');
    expect(Poll::where('title', 'Sondage date dupliquée')->exists())->toBeFalse();
});

test('soumettre deux options classiques au libellé identique est rejeté (distinct) au lieu de scinder les votes', function (): void {
    // Round 11 (skill /100) : même faille pour le type classique - "Pizza"/"Pizza" créait deux
    // PollOption distinctes, chacune accumulant sa propre part des votes séparément.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage option dupliquée',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Pizza'],
    ]);

    $response->assertSessionHasErrors('options.0');
    expect(Poll::where('title', 'Sondage option dupliquée')->exists())->toBeFalse();
});

// ── Vote public ─────────────────────────────────────────────────────────────

test('votant anonyme peut voir un sondage ouvert', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'classic', 'vote_mode' => 'single_choice']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->assertStatus(200);
});

test('les pages privées Décido (vote, résultats, Mes sondages, création) déclarent page_noindex', function (): void {
    // Round 10 (skill /100) : aucune vue Décido ne déclarait @section('page_noindex') - une fois
    // le module public (DECIDO_UNDER_CONSTRUCTION=false), les pages contenant des données privées
    // (pseudonymes/choix de vote) auraient été indexables par défaut. Preuve HTTP réelle : la
    // balise <meta name="robots"> ne passe en "noindex, follow" que si la section est déclarée
    // (Modules/FrontTheme/resources/views/layouts/master.blade.php:13, View::hasSection()).
    config()->set('decido.under_construction', false);
    config()->set('app.noindex', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-noindex', 'type' => 'classic', 'vote_mode' => 'single_choice']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $voteHtml = $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->getContent();
    $this->assertStringContainsString('name="robots" content="noindex', $voteHtml);

    $resultsHtml = $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-noindex']))->getContent();
    $this->assertStringContainsString('name="robots" content="noindex', $resultsHtml);

    $indexHtml = $this->actingAs($this->superadmin)->get(route('decido.index'))->getContent();
    $this->assertStringContainsString('name="robots" content="noindex', $indexHtml);

    $createHtml = $this->actingAs($this->superadmin)->get(route('decido.create'))->getContent();
    $this->assertStringContainsString('name="robots" content="noindex', $createHtml);
});

test('la page de gestion (jeton admin dans l’URL) ne transmet pas ce jeton à Google Analytics (no_analytics)', function (): void {
    // Round 12 (skill /100) : le jeton admin (contrôle total du sondage - clôture, export des
    // pseudonymes des votants, lien court) transite EN CLAIR dans le chemin de l'URL
    // (/decido/{poll}/gerer/{adminToken}). Le layout global (master.blade.php) charge GA4 avec
    // send_page_view:true sur toute page ne déclarant PAS @section('no_analytics') - le hit GA4
    // capture automatiquement page_location = window.location.href, donc sans ce gate, le jeton
    // admin était transmis à Google (tiers) et stocké indéfiniment dans la propriété GA4 à chaque
    // chargement (visite initiale, rafraîchissement, redirection post-clôture/lien court). Le
    // round 10 avait bloqué l'indexation (page_noindex) mais pas ce vecteur de fuite distinct.
    config()->set('decido.under_construction', false);
    config()->set('services.ga.measurement_id', 'G-TESTFAKE123');
    config()->set('services.ga.privacy_enabled', true);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-ga4-leak']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $manageHtml = $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ga4-leak']))->getContent();
    $this->assertStringNotContainsString('googletagmanager.com', $manageHtml);

    // Contrôle négatif : prouve que le gate GA4 est réellement actif dans cet environnement de
    // test (sinon l'assertion précédente passerait trivialement même sans le correctif) - une
    // page Décido dont l'URL propre ne porte aucun jeton (index, pas de no_analytics déclaré)
    // charge bien GA4 normalement.
    $indexHtml = $this->actingAs($this->superadmin)->get(route('decido.index'))->getContent();
    $this->assertStringContainsString('googletagmanager.com', $indexHtml);
});

test('le jeton admin Décido dans l’URL est filtré avant tout envoi vers Sentry (before_send)', function (): void {
    // Round 13 (skill /100) : Sentry\Integration\RequestIntegration capture INCONDITIONNELLEMENT
    // l'URL complète de la requête (event.request.url), même avec send_default_pii=false (ce
    // flag ne protège que cookies/headers/IP, jamais l'URL - vérifié dans le code source du SDK,
    // vendor/sentry/sentry/src/Integration/RequestIntegration.php:129). La moindre exception
    // levée pendant le traitement d'une requête /decido/{poll}/gerer/{adminToken}(/...) aurait
    // donc envoyé le jeton admin (contrôle total du sondage) en clair vers Sentry, un tiers hors
    // UE. Correctif : config/sentry.php 'before_send' branché sur SentryUrlScrubber, qui
    // s'exécute (prouvé par Client::prepareEvent()) APRÈS que RequestIntegration a rempli
    // request.url mais AVANT l'envoi au transport - la fenêtre exacte pour scruber sans rien
    // casser d'autre. Même famille de fuite que le round 12 (GA4), angle distinct (télémétrie
    // d'erreurs serveur, pas analytics navigateur).
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-secret-sentry']);

    $url = route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-secret-sentry']);
    expect($url)->toContain('jeton-secret-sentry');

    $beforeSend = config('sentry.before_send');
    expect($beforeSend)->toBeCallable();

    $event = \Sentry\Event::createEvent();
    $event->setRequest(['url' => $url, 'method' => 'GET']);

    $scrubbed = $beforeSend($event, null);

    expect($scrubbed)->not->toBeNull();
    $scrubbedUrl = $scrubbed->getRequest()['url'];
    expect($scrubbedUrl)->not->toContain('jeton-secret-sentry');
    expect($scrubbedUrl)->toContain('/gerer/[jeton-filtre]');
});

test('SentryUrlScrubber préserve le chemin après le jeton admin (fermer/export/qr non tronqués)', function (): void {
    // Preuve que le scrub ne consomme QUE le segment du jeton, pas les sous-routes de gestion
    // qui suivent dans le chemin (fermer, export.csv, export.ics, lien-court, qr.png).
    $scrubbed = \Modules\Core\Services\SentryUrlScrubber::scrubUrl(
        'https://laveille.ai/decido/abc123def456/gerer/SUPERSECRETTOKEN1234567890/export.csv?foo=bar'
    );

    expect($scrubbed)
        ->toBe('https://laveille.ai/decido/abc123def456/gerer/[jeton-filtre]/export.csv?foo=bar')
        ->not->toContain('SUPERSECRETTOKEN1234567890');

    // Contrôle négatif : une URL Décido sans jeton (page publique de vote) n'est pas altérée.
    $publicUrl = 'https://laveille.ai/decido/abc123def456';
    expect(\Modules\Core\Services\SentryUrlScrubber::scrubUrl($publicUrl))->toBe($publicUrl);
});

test('votant anonyme peut voter (yes_no_maybe)', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'date', 'vote_mode' => 'yes_no_maybe']);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Alice',
        'votes' => [$option->id => 'yes'],
    ]);

    $this->assertDatabaseHas('decido_poll_votes', [
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_pseudonym' => 'Alice',
        'value' => 'yes',
    ]);
});

test('le même votant qui revote met à jour son vote sans le dupliquer', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'date', 'vote_mode' => 'yes_no_maybe']);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $cookieName = 'decido_voter_'.$poll->public_id;

    $first = $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Bob',
        'votes' => [$option->id => 'yes'],
    ]);

    // TestResponse::getCookie() déchiffre automatiquement (2e argument $decrypt = true par
    // défaut) - withCookie() re-chiffrera cette valeur brute pour simuler un aller-retour
    // navigateur normal.
    $voterToken = $first->getCookie($cookieName)->getValue();

    $this->withCookie($cookieName, $voterToken)->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Bob',
        'votes' => [$option->id => 'no'],
    ]);

    $this->assertSame(1, PollVote::where('option_id', $option->id)->count());
    $this->assertDatabaseHas('decido_poll_votes', [
        'option_id' => $option->id,
        'value' => 'no',
    ]);
});

test('voter sur un sondage fermé retourne 404', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'classic', 'vote_mode' => 'single_choice', 'status' => 'closed']);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Charlie',
        'votes' => (string) $option->id,
    ])->assertNotFound();
});

// ── Administration via lien admin ────────────────────────────────────────────

test('accès à la gestion avec un mauvais jeton admin retourne 403', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'le-vrai-jeton']);

    $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'mauvais-jeton']))
        ->assertForbidden();
});

test('accès à la gestion avec le bon jeton admin fonctionne sans compte connecté', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'le-vrai-jeton']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'le-vrai-jeton']))
        ->assertStatus(200);
});

// ── Intégration ShortUrl / QR code ──────────────────────────────────────────

test('créer un lien court associe un short_url_id au sondage et affiche le lien court', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-shortlink']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->post(route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => 'jeton-shortlink']))
        ->assertRedirect(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-shortlink']));

    $poll->refresh();
    expect($poll->short_url_id)->not->toBeNull();
    expect($poll->getShortUrlString())->not->toBeNull();

    $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-shortlink']))
        ->assertStatus(200)
        ->assertSee($poll->getShortUrlString(), escape: false);
});

test('Poll::getShortUrlString() ne ré-interroge pas la DB à chaque appel (fix N+1 round 7)', function (): void {
    // Round 7 adversarial (skill /100) : ::find() brut à chaque appel générait 3 requêtes
    // short_urls redondantes par chargement de results.blade.php (3 appels dans la vue).
    // $this->shortUrl (relation Eloquent) est mis en cache après le premier accès.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-n1']);
    PollOption::factory()->create(['poll_id' => $poll->id]);
    $this->post(route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => 'jeton-n1']));
    $poll->refresh();

    \Illuminate\Support\Facades\DB::enableQueryLog();
    $poll->getShortUrlString();
    $poll->getShortUrlString();
    $poll->getShortUrlString();
    $shortUrlQueries = collect(\Illuminate\Support\Facades\DB::getQueryLog())
        ->filter(fn ($q) => str_contains($q['query'], 'short_urls'));
    \Illuminate\Support\Facades\DB::disableQueryLog();

    expect($shortUrlQueries)->toHaveCount(1);
});

test('supprimer le compte créateur orpheline le sondage au lieu de le supprimer en cascade', function (): void {
    // Décision utilisateur (2026-07-16) : cascadeOnDelete détruisait intégralement un sondage
    // (créneaux + TOUS les votes de tiers) dès que le créateur supprimait son compte, sans
    // préavis possible pour les votants anonymes. Remplacé par nullOnDelete (orphelinage).
    $creator = User::factory()->create();
    $poll = decidoCreatePoll(['creator_id' => $creator->id, 'admin_token' => 'jeton-orphelin']);
    $option = $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);
    $option->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => 'token-tiers',
        'voter_pseudonym' => 'Un tiers',
        'value' => 'selected',
    ]);

    $creator->delete();

    $poll->refresh();
    expect($poll)->not->toBeNull();
    expect($poll->creator_id)->toBeNull();
    expect($poll->options()->count())->toBe(1);
    expect($option->fresh()->votes()->count())->toBe(1);
});

test('deux votants homonymes (même pseudonyme, voter_token différents) ne sont pas fusionnés dans les résultats', function (): void {
    // Round 6 adversarial (skill /100) : avant le fix, totalVoters/voterNames/matrix étaient
    // clés par voter_pseudonym (texte libre) au lieu de voter_token (identifiant réel) - un
    // deuxième votant "Marie" écrasait silencieusement le vote du premier "Marie".
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-homonymes', 'vote_mode' => 'single_choice']);
    $option = $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);
    PollOption::factory()->create(['poll_id' => $poll->id, 'label' => 'Option B', 'sort_order' => 1]);

    $option->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => 'token-marie-1',
        'voter_pseudonym' => 'Marie',
        'value' => 'selected',
    ]);
    $option->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => 'token-marie-2',
        'voter_pseudonym' => 'Marie',
        'value' => 'selected',
    ]);

    $response = $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-homonymes']));

    $response->assertStatus(200);
    $response->assertSee('2 participant(s)', false);
    // Les 2 votes doivent apparaître dans le total, pas être écrasés à 1.
    $response->assertSee('✓ 2 sur 2 participants', false);
});

test('demander un lien court deux fois ne crée pas deux short_url distincts', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-shortlink-2']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->post(route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => 'jeton-shortlink-2']));
    $poll->refresh();
    $firstShortUrlId = $poll->short_url_id;

    $this->post(route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => 'jeton-shortlink-2']));
    $poll->refresh();

    expect($poll->short_url_id)->toBe($firstShortUrlId);
});

// ── Round 15 (skill /100) : race condition réelle sur la création de lien court ────────────

test('Poll::claimShortUrl() sous interleaving réel (deux instances périmées du même sondage) ne crée qu’un seul ShortUrl', function (): void {
    // Round 15 (skill /100) : le test précédent ("demander un lien court deux fois...") rejoue
    // les deux requêtes STRICTEMENT l'une après l'autre, avec un $poll->refresh() explicite entre
    // les deux - il prouve seulement le comportement SÉQUENTIEL, pas une vraie concurrence.
    // L'ancien code (PollManageController::createShortLink) lisait `$pollModel->short_url_id` sur
    // l'instance Eloquent chargée en tout début de méthode, PUIS créait le ShortUrl, PUIS
    // écrivait - sans transaction ni verrou. Deux requêtes HTTP arrivant à quelques millisecondes
    // d'écart auraient chacune leur PROPRE copie mémoire de $pollModel, toutes deux avec
    // short_url_id=NULL au moment de la lecture (aucune ne voit l'écriture de l'autre avant sa
    // propre lecture) - ce test reproduit fidèlement cette fenêtre de course en fabriquant
    // délibérément deux instances Eloquent DISTINCTES et périmées du même sondage (chargées avant
    // toute écriture, comme le seraient deux requêtes concurrentes) et en appelant sur CHACUNE la
    // méthode de revendication du lien court.
    //
    // Avec l'ancien code (ou une implémentation naïve `if ($this->short_url_id) return; ... create
    // ...; $this->update(...)`), les DEUX appels passeraient le garde (chaque instance a sa propre
    // copie mémoire de short_url_id=NULL, jamais mise à jour par l'écriture de l'autre) et
    // créeraient chacune un ShortUrl distinct - ce test échouerait alors (count() === 2). Le
    // correctif (Poll::claimShortUrl(), DB::transaction + lockForUpdate) ignore délibérément l'état
    // en mémoire de l'instance passée et relit la VÉRITÉ FRAÎCHE depuis la base à l'intérieur du
    // verrou avant de décider - propriété exacte qui, sous une vraie concurrence MySQL en
    // production, ferait attendre la seconde transaction jusqu'à ce que la première commette.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-race-shortlink']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $pollForRequestA = Poll::find($poll->id);
    $pollForRequestB = Poll::find($poll->id);
    expect($pollForRequestA->short_url_id)->toBeNull();
    expect($pollForRequestB->short_url_id)->toBeNull();

    $service = app(\Modules\ShortUrl\Services\ShortUrlService::class);

    // Baseline scopée à CE sondage (pas un count() global) : la migration
    // Modules/ShortUrl/database/migrations/2026_06_14_120000_create_qt_short_link.php insère un
    // ShortUrl "qt" indépendant à chaque exécution de RefreshDatabase - compter la table entière
    // produirait un faux résultat sans rapport avec la race condition testée ici.
    $countShortUrlsForThisPoll = fn () => \Modules\ShortUrl\Models\ShortUrl::where('original_url', $poll->share_url)->count();
    expect($countShortUrlsForThisPoll())->toBe(0);

    $shortUrlFromA = $pollForRequestA->claimShortUrl($poll->creator_id, $service);
    $shortUrlFromB = $pollForRequestB->claimShortUrl($poll->creator_id, $service);

    expect($shortUrlFromA)->not->toBeNull();
    expect($shortUrlFromB)->not->toBeNull();
    expect($shortUrlFromB->id)->toBe($shortUrlFromA->id);
    expect($countShortUrlsForThisPoll())->toBe(1);
    expect($poll->fresh()->short_url_id)->toBe($shortUrlFromA->id);
});

test('un jeton admin invalide ne peut pas créer de lien court (403)', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'le-vrai-jeton-3']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->post(route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => 'mauvais-jeton']))
        ->assertStatus(403);
});

test('le QR code du sondage renvoie une image PNG valide', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-qr']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $response = $this->get(route('decido.qr', ['poll' => $poll->public_id, 'adminToken' => 'jeton-qr']));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

// ── Exports ───────────────────────────────────────────────────────────────

test('export CSV disponible en tout temps', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-csv']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $response = $this->get(route('decido.export.csv', ['poll' => $poll->public_id, 'adminToken' => 'jeton-csv']));

    $response->assertStatus(200);
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
});

test('export ICS refusé si le sondage n’est pas fermé', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-ics', 'status' => 'open']);

    $response = $this->get(route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics']));

    $response->assertRedirect();
    $response->assertSessionHasErrors('export');
});

test('export ICS disponible après clôture avec créneau final', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-ics-2', 'type' => 'date', 'status' => 'closed']);
    $option = PollOption::factory()->create([
        'poll_id' => $poll->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addMinutes(30),
    ]);
    $poll->final_option_id = $option->id;
    $poll->save();

    $response = $this->get(route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics-2']));

    $response->assertStatus(200);
    $this->assertStringContainsString('text/calendar', $response->headers->get('Content-Type'));
});

test('le DTSTART de l’export ICS reflète l’heure UTC réelle, pas un fuseau mal interprété', function (): void {
    // Reproduit le stockage réel de SlotGenerationService : starts_at/ends_at écrits en UTC
    // brut. config('app.timezone') = America/Toronto fait que le cast Eloquent datetime, à la
    // relecture depuis la DB (d'où le fresh() ci-dessous, indispensable pour déclencher le bug),
    // réinterprète cette valeur comme si elle était déjà en heure de Québec sans la convertir.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-ics-tz', 'type' => 'date', 'status' => 'closed']);
    $option = PollOption::factory()->create([
        'poll_id' => $poll->id,
        'starts_at' => \Carbon\Carbon::create(2026, 8, 1, 13, 0, 0, 'UTC'),
        'ends_at' => \Carbon\Carbon::create(2026, 8, 1, 14, 0, 0, 'UTC'),
    ]);
    $poll->final_option_id = $option->id;
    $poll->save();
    $poll = $poll->fresh(['options']);

    $response = $this->get(route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics-tz']));

    $response->assertStatus(200);
    $this->assertStringContainsString('DTSTART:20260801T130000Z', $response->getContent());
    $this->assertStringContainsString('DTEND:20260801T140000Z', $response->getContent());
});

test('l’export ICS plie les lignes trop longues conformément à RFC 5545 (aucune ligne > 75 octets)', function (): void {
    // Round 9 (skill /100) : aucun pliage de ligne n'existait - un titre long/unicode produisait
    // une ligne SUMMARY de plusieurs centaines d'octets, dépassant largement la limite RFC 5545
    // §3.1 (75 octets/ligne), risquant une troncature par des lecteurs de calendrier stricts.
    $longTitle = str_repeat('Réunion café ☕ São Paulo 🎉 ', 6); // > 75 octets une fois encodé UTF-8, < 255 caractères
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-ics-fold', 'type' => 'date', 'status' => 'closed', 'title' => $longTitle]);
    $option = PollOption::factory()->create([
        'poll_id' => $poll->id,
        'starts_at' => \Carbon\Carbon::create(2026, 8, 1, 13, 0, 0, 'UTC'),
        'ends_at' => \Carbon\Carbon::create(2026, 8, 1, 14, 0, 0, 'UTC'),
    ]);
    $poll->final_option_id = $option->id;
    $poll->save();
    $poll = $poll->fresh(['options']);

    $response = $this->get(route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics-fold']));
    $response->assertStatus(200);

    foreach (explode("\r\n", $response->getContent()) as $physicalLine) {
        $this->assertLessThanOrEqual(75, strlen($physicalLine), "Ligne ICS physique > 75 octets : {$physicalLine}");
    }

    // Le titre complet doit rester reconstituable en dépliant (retirer CRLF + espace de continuation).
    $unfolded = str_replace("\r\n ", '', $response->getContent());
    $this->assertStringContainsString($longTitle, $unfolded);
});

// ── Clôture ───────────────────────────────────────────────────────────────

test('clôturer un sondage avec un créneau final fonctionne', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-close']);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->post(route('decido.close', ['poll' => $poll->public_id, 'adminToken' => 'jeton-close']), [
        'final_option_id' => $option->id,
    ]);

    $poll->refresh();
    $this->assertSame('closed', $poll->status->value);
    $this->assertSame($option->id, $poll->final_option_id);
});

// ── Service de génération de créneaux ────────────────────────────────────────

test('le service de créneaux calcule le bon nombre pour une plage donnée', function (): void {
    $service = new SlotGenerationService;
    $futureDate = now()->addDays(5)->format('Y-m-d');

    $slots = $service->generateSlots([$futureDate], '09:00', '11:00', 45, 30, 'America/Toronto');

    $this->assertSame(3, count($slots));
});

test('le service de créneaux lève une exception si la plage horaire est inversée', function (): void {
    $service = new SlotGenerationService;
    $futureDate = now()->addDays(5)->format('Y-m-d');

    $this->expectException(InvalidArgumentException::class);
    $service->generateSlots([$futureDate], '11:00', '09:00', 30, 30, 'America/Toronto');
});

test('les créneaux traversant le passage à l’heure d’été durent exactement la durée configurée', function (): void {
    // Round 8 (skill /100) : l'ancienne implémentation additionnait des minutes sur une instance
    // Carbon localisée (America/Toronto) - le 8 mars 2026 (passage à l'heure d'été, 02h00-02h59
    // n'existe pas localement), un créneau de 30 min démarrant à 01h30 durait en réalité 90 min
    // une fois relu (l'addition sautait silencieusement l'heure inexistante). L'arithmétique se
    // fait désormais en UTC (pas de DST), garantissant une durée exacte quel que soit le jour.
    $service = new SlotGenerationService;

    $slots = $service->generateSlots(['2026-03-08'], '01:00', '04:00', 30, 30, 'America/Toronto');

    foreach ($slots as $slot) {
        $this->assertSame(
            30,
            (int) $slot['starts_at']->diffInMinutes($slot['ends_at']),
            "Le créneau {$slot['label']} ne dure pas 30 minutes exactement."
        );
    }
});

test('les créneaux ambigus au retour à l’heure normale sont désambiguïsés dans leur libellé', function (): void {
    // Round 8 (skill /100) : le 1er novembre 2026 (retour à l'heure normale), l'heure locale
    // 01h00-01h59 se produit deux fois - deux créneaux UTC distincts généraient auparavant un
    // libellé strictement identique, rendant impossible pour un votant de distinguer les deux
    // options. Le service ajoute désormais le décalage UTC en désambiguïsation sur collision.
    $service = new SlotGenerationService;

    $slots = $service->generateSlots(['2026-11-01'], '00:00', '03:00', 30, 30, 'America/Toronto');

    $labels = array_column($slots, 'label');
    $this->assertSame($labels, array_unique($labels), 'Deux créneaux partagent un libellé identique.');
});

test('Poll::shortUrl()/getShortUrlString() utilisent ModuleChecker (pas class_exists seul)', function (): void {
    // Round 8 (skill /100) : class_exists() reste vrai même module désactivé via
    // modules_statuses.json (nwidart garde les classes en autoload, seul le boot du
    // ServiceProvider est coupé) - un lien court "fantôme" pouvait être créé/affiché sans
    // avertissement (routes ShortUrl jamais enregistrées → 404 réel). ModuleChecker vérifie en
    // plus Module::has()/isEnabled(), le vrai état d'activation. Ce test prouve que le mécanisme
    // ModuleChecker::isAvailable() détecte correctement un module réellement absent/désactivé
    // (impossible de mocker un module tiers réel sans toucher au modules_statuses.json global,
    // ce qui romprait l'isolation des tests) et que le module ShortUrl, lui, est bien détecté
    // disponible en conditions normales (déjà prouvé par les tests de lien court ci-dessus).
    expect(\Modules\Core\Services\ModuleChecker::isAvailable('UnModuleQuiNexistePas'))->toBeFalse();
    expect(\Modules\Core\Services\ModuleChecker::isAvailable('ShortUrl'))->toBeTrue();

    $poll = decidoCreatePoll();
    $this->assertNull($poll->getShortUrlString());
});

// ── Export CSV/ICS (service, tests unitaires directs) ────────────────────────

test('PollExportService::exportIcs lève une exception si le sondage n’est pas fermé', function (): void {
    $poll = decidoCreatePoll(['status' => 'open']);

    $this->expectException(RuntimeException::class);
    (new PollExportService)->exportIcs($poll);
});

test('export CSV neutralise l’injection de formule (voter_pseudonym contrôlé par un votant anonyme)', function (): void {
    // Round 5 adversarial (skill /100) : sans neutralisation, une valeur commençant par =/+/-/@
    // est interprétée comme une formule active par Excel/Google Sheets à l'ouverture par
    // l'organisateur (OWASP CSV Injection).
    $poll = decidoCreatePoll();
    $option = $poll->options()->create(['label' => '=cmd|"/c calc"!A1', 'sort_order' => 0]);
    $option->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => Str::uuid()->toString(),
        'voter_pseudonym' => '=HYPERLINK("http://evil.example/steal","clique ici")',
        'value' => 'selected',
    ]);

    $csv = (new PollExportService)->exportCsv($poll->fresh(['options.votes']));

    expect($csv)->toContain("'=HYPERLINK");
    expect($csv)->toContain("'=cmd|");
    expect($csv)->not->toContain('"=HYPERLINK');
});

test('decido.store et decido.vote.store portent un middleware throttle (anti-abus)', function (): void {
    $storeMiddleware = collect(Route::getRoutes()->getByName('decido.store')->gatherMiddleware());
    $voteMiddleware = collect(Route::getRoutes()->getByName('decido.vote.store')->gatherMiddleware());

    expect($storeMiddleware->contains(fn ($m) => str_starts_with($m, 'throttle:')))->toBeTrue();
    expect($voteMiddleware->contains(fn ($m) => str_starts_with($m, 'throttle:')))->toBeTrue();
});

// ── Round 14 (skill /100) : injection d'en-tête HTTP, ResponseCache, N+1 volumétrique ──────

test('un titre de sondage malveillant (CRLF, guillemets) n’est jamais reflété dans les en-têtes HTTP des exports (filename = identifiant opaque, jamais le titre)', function (): void {
    // Round 14 (skill /100) : le titre du sondage est fourni par un créateur authentifié (mais
    // texte libre non validé pour l'usage "nom de fichier") et réutilisé dans le CONTENU de
    // l'ICS (SUMMARY, déjà neutralisé par escapeIcsText au round 9). Hypothèse testée ici :
    // est-il AUSSI injecté dans l'en-tête Content-Disposition (filename="...") des exports
    // CSV/ICS/QR, ce qui permettrait une injection d'en-tête HTTP (CRLF) ou une confusion de
    // nom de fichier via des guillemets non échappés ? Vérification du code
    // (PollManageController::exportCsv/exportIcs/qrCode) : les trois `Content-Disposition`
    // utilisent exclusivement `$pollModel->public_id` (Str::random(12), alphanumérique pur,
    // généré serveur, jamais dérivé du titre) - le titre n'apparaît JAMAIS dans un en-tête.
    // Ce test le PROUVE avec un titre réellement malveillant, via une requête HTTP réelle.
    config()->set('decido.under_construction', false);
    $maliciousTitle = "Réunion\"; x=\r\nX-Injected: pwned\r\n\r\n<script>alert(1)</script>";

    $poll = decidoCreatePoll([
        'admin_token' => 'jeton-header-injection',
        'title' => $maliciousTitle,
        'type' => 'date',
        'status' => 'closed',
    ]);
    $option = $poll->options()->create([
        'label' => 'Créneau',
        'sort_order' => 0,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addMinutes(30),
    ]);
    $poll->final_option_id = $option->id;
    $poll->save();

    $csvResponse = $this->get(route('decido.export.csv', ['poll' => $poll->public_id, 'adminToken' => 'jeton-header-injection']));
    $icsResponse = $this->get(route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => 'jeton-header-injection']));
    $qrResponse = $this->get(route('decido.qr', ['poll' => $poll->public_id, 'adminToken' => 'jeton-header-injection']));

    foreach (['csv' => $csvResponse, 'ics' => $icsResponse, 'qr' => $qrResponse] as $kind => $response) {
        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');

        // Aucun caractère du titre malveillant ne doit apparaître dans l'en-tête.
        $this->assertStringNotContainsString('X-Injected', $disposition, "Export {$kind} : injection d'en-tête détectée.");
        $this->assertStringNotContainsString('<script>', $disposition, "Export {$kind} : titre reflété dans l'en-tête.");
        $this->assertStringNotContainsString("\r", $disposition, "Export {$kind} : CR brut dans l'en-tête (aurait cassé la réponse HTTP).");
        $this->assertStringNotContainsString("\n", $disposition, "Export {$kind} : LF brut dans l'en-tête (aurait cassé la réponse HTTP).");
        // Le filename attendu ne contient que l'identifiant public opaque (alphanumérique).
        $this->assertMatchesRegularExpression('/filename="[a-zA-Z0-9\-]+(-votes)?\.(csv|ics|png)"/', $disposition);
    }

    // Contrôle positif : le titre malveillant apparaît bien QUELQUE PART (dans le corps ICS,
    // neutralisé par escapeIcsText - round 9), preuve que ce n'est pas juste un test qui ne
    // passe jamais par le code concerné.
    $this->assertStringContainsString('Réunion', $icsResponse->getContent());
});

test('aucune route de gestion/vote/résultats Décido ne porte le middleware cacheResponse (incident #683 Modules/Tools : page personnalisée figée et servie à tous)', function (): void {
    // Round 14 (skill /100) : Modules/Tools a déjà eu un incident réel où une page personnalisée
    // par rôle/jeton était mise en cache HTTP serveur (Spatie ResponseCache) et servie ensuite à
    // TOUS les visiteurs suivants. Décido est structurellement identique (page /gerer/{adminToken}
    // affichant pseudonymes et jeton admin propres à CHAQUE sondage) - si `cacheResponse` était
    // appliqué par erreur, le premier chargement de la page de gestion d'un sondage A figerait
    // cette réponse et la SERVIRAIT à quiconque visite ensuite /decido/{n'importe-quel-poll}/gerer/...
    // (le cache Spatie clé généralement par URL complète, donc le risque réel dépend des query
    // strings, mais zéro tolérance ici : cacheResponse n'a AUCUNE légitimité sur du contenu privé
    // par jeton). Confirmé par lecture directe de Modules/Decido/routes/web.php : aucune route ne
    // déclare `cacheResponse`. Ce test le fige pour empêcher toute régression future.
    $decidoRouteNames = [
        'decido.index', 'decido.create', 'decido.store',
        'decido.manage', 'decido.close', 'decido.export.csv', 'decido.export.ics',
        'decido.shortlink', 'decido.qr', 'decido.vote.show', 'decido.vote.store',
    ];

    foreach ($decidoRouteNames as $name) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull("Route {$name} introuvable.");

        $middleware = collect($route->gatherMiddleware());
        expect($middleware->contains(fn ($m) => str_starts_with($m, 'cacheResponse')))
            ->toBeFalse("La route {$name} porte le middleware cacheResponse - fuite de données inter-sondages possible.");
    }

    // Contrôle négatif : prouve que la détection fonctionne réellement (sinon l'assertion
    // ci-dessus passerait trivialement même avec une détection cassée) - une route connue pour
    // porter réellement cacheResponse ailleurs sur le site le déclenche bien.
    $toolsRoute = Route::getRoutes()->getByName('tools.index');
    if ($toolsRoute) {
        $toolsMiddleware = collect($toolsRoute->gatherMiddleware());
        expect($toolsMiddleware->contains(fn ($m) => str_starts_with($m, 'cacheResponse')))->toBeTrue();
    }
});

test('la page de résultats ne génère pas de requêtes SQL supplémentaires proportionnelles au nombre de votes (200+ votes réels, pas d’échantillon)', function (): void {
    // Round 14 (skill /100) : les rounds précédents (7, 11) avaient déjà corrigé des N+1 ciblés
    // (getShortUrlString, distinct sur candidate_dates/options) mais jamais mesuré le comportement
    // de la page de résultats à un volume réaliste de votes. PollManageController::manage() fait
    // `$pollModel->load(['options.votes'])` (eager loading) puis toute l'agrégation
    // (results.blade.php) se fait en mémoire sur les collections déjà chargées (flatMap/unique/
    // groupBy) - aucune requête supplémentaire ne devrait apparaître, quel que soit le nombre de
    // votes. Ce test le PROUVE avec 210 votes réellement insérés en DB (pas un échantillon de 2-3
    // votes comme les tests fonctionnels ci-dessus), en comparant le compte de requêtes à faible
    // et fort volume.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-volume-n1', 'vote_mode' => 'single_choice']);
    $options = PollOption::factory()->count(5)->create(['poll_id' => $poll->id]);

    // Volume faible : 5 votes.
    foreach (range(1, 5) as $i) {
        $options->random()->votes()->create([
            'poll_id' => $poll->id,
            'voter_token' => "petit-volume-{$i}",
            'voter_pseudonym' => "Votant {$i}",
            'value' => 'selected',
        ]);
    }

    // Ne compter que les requêtes touchant les tables decido_* : le reste de la requête HTTP
    // (settings applicatifs, cookies de consentement, IP bloquées, cache de session...) peut
    // varier en nombre de requêtes d'un appel à l'autre indépendamment du volume de votes
    // (mise en cache applicative interne réchauffée après le premier appel) - compter le TOTAL
    // brut produirait un faux positif/négatif sans rapport avec l'hypothèse N+1 testée ici.
    $countDecidoQueries = fn () => collect(\Illuminate\Support\Facades\DB::getQueryLog())
        ->filter(fn ($q) => str_contains($q['query'], 'decido_'))
        ->count();

    \Illuminate\Support\Facades\DB::enableQueryLog();
    $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-volume-n1']))->assertStatus(200);
    $smallVolumeQueryCount = $countDecidoQueries();
    \Illuminate\Support\Facades\DB::flushQueryLog();

    // Volume élevé : 210 votes supplémentaires répartis sur les 5 options (215 votants distincts
    // au total), insertion réelle en DB.
    $rows = [];
    foreach (range(1, 210) as $i) {
        $rows[] = [
            'poll_id' => $poll->id,
            'option_id' => $options->random()->id,
            'voter_token' => "gros-volume-{$i}",
            'voter_pseudonym' => "Votant volume {$i}",
            'value' => 'selected',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    \Illuminate\Support\Facades\DB::table('decido_poll_votes')->insert($rows);

    expect(PollVote::where('poll_id', $poll->id)->count())->toBe(215);

    // flushQueryLog() vide le tampon mais NE désactive PAS la capture (déjà active depuis plus
    // haut) - sans ce flush, l'INSERT en masse ci-dessus serait lui-même comptabilisé comme une
    // requête « decido_ » et fausserait la comparaison à la hausse indépendamment de tout N+1 réel.
    \Illuminate\Support\Facades\DB::flushQueryLog();
    $response = $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-volume-n1']));
    $largeVolumeQueryCount = $countDecidoQueries();
    \Illuminate\Support\Facades\DB::disableQueryLog();

    $response->assertStatus(200);
    $response->assertSee('215 participant(s)', false);

    // Le nombre de requêtes SQL ne doit PAS croître avec le nombre de votes (eager loading
    // options.votes = 1 requête poll + 1 options + 1 votes, quel que soit le volume).
    $this->assertSame(
        $smallVolumeQueryCount,
        $largeVolumeQueryCount,
        "Le nombre de requêtes SQL a changé entre 5 votes ({$smallVolumeQueryCount}) et 215 votes ({$largeVolumeQueryCount}) - N+1 potentiel."
    );
    $this->assertLessThan(10, $largeVolumeQueryCount, 'Nombre de requêtes SQL anormalement élevé pour une page de résultats.');
});

test('decido:purge-expired supprime les sondages clôturés expirés et épargne les autres', function (): void {
    $expired = decidoCreatePoll(['status' => 'closed']);
    $expired->expires_at = now()->subDay();
    $expired->save();

    $notYetExpired = decidoCreatePoll(['status' => 'closed']);
    $notYetExpired->expires_at = now()->addMonths(3);
    $notYetExpired->save();

    $stillOpen = decidoCreatePoll(['status' => 'open']);

    $this->artisan('decido:purge-expired')->assertExitCode(0);

    expect(Poll::find($expired->id))->toBeNull();
    expect(Poll::find($notYetExpired->id))->not->toBeNull();
    expect(Poll::find($stillOpen->id))->not->toBeNull();
});
