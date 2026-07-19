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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Decido\Mail\PollExpiringSoonMail;
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

test('Option E : les 2 routes de formulaire dédié héritent bien du même gate (guest redirigé, non-superadmin 503, superadmin 200)', function (): void {
    // Les nouvelles routes decido.create.date/decido.create.classic sont déclarées dans le MÊME
    // groupe de middleware (auth + DecidoUnderConstruction) que decido.create - preuve qu'aucune
    // n'a été oubliée hors du gate lors de la scission des formulaires.
    $user = User::factory()->create();

    $this->get(route('decido.create.date'))->assertRedirect();
    $this->get(route('decido.create.classic'))->assertRedirect();

    $this->actingAs($user)->get(route('decido.create.date'))->assertStatus(503);
    $this->actingAs($user)->get(route('decido.create.classic'))->assertStatus(503);

    $this->actingAs($this->superadmin)->get(route('decido.create.date'))->assertStatus(200);
    $this->actingAs($this->superadmin)->get(route('decido.create.classic'))->assertStatus(200);
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

test('une date candidate peut personnaliser sa propre plage horaire (différente de la plage par défaut)', function (): void {
    // Avant ce fix, toutes les dates candidates partageaient obligatoirement la même plage
    // horaire globale - impossible de proposer "lundi seulement l'après-midi, mercredi seulement
    // le matin" comme le permet Framadate. candidate_date_ranges[index] (tableau d'objets
    // {start,end}, indexé comme candidate_dates[]) surcharge la plage par défaut pour une date
    // précise ; une entrée absente/vide hérite de range_start_time/range_end_time.
    $dateWithOverride = now()->addDays(10)->format('Y-m-d');
    $dateWithDefault = now()->addDays(11)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage plages mixtes',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 60,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 60,
        'candidate_dates' => [$dateWithOverride, $dateWithDefault],
        'candidate_date_ranges' => [
            0 => [['start' => '14:00', 'end' => '15:00']],
        ],
    ]);

    $poll = Poll::where('title', 'Sondage plages mixtes')->first();
    expect($poll)->not->toBeNull();
    $response->assertSessionDoesntHaveErrors();

    $options = $poll->options()->orderBy('starts_at')->get();
    // 09:00-10:00 (défaut) = exactement 1 créneau de 60 min ; 14:00-15:00 (surcharge) = exactement
    // 1 créneau de 60 min -> 2 créneaux au total, chacun sur l'heure attendue selon sa date.
    expect($options)->toHaveCount(2);

    // getRawOriginal() + Carbon::parse(..., 'UTC') : app.timezone = America/Toronto (non-UTC) fait
    // que le cast Eloquent datetime, à la relecture depuis la DB, réinterprète la valeur UTC brute
    // comme si elle était déjà en heure de Québec (bug documenté ailleurs dans ce fichier, cf. le
    // test « le DTSTART de l'export ICS reflète l'heure UTC réelle »).
    $localStartOf = fn ($option) => \Carbon\Carbon::parse($option->getRawOriginal('starts_at'), 'UTC')->setTimezone('America/Toronto');

    $overrideOption = $options->first(fn ($o) => $localStartOf($o)->format('Y-m-d') === $dateWithOverride);
    $defaultOption = $options->first(fn ($o) => $localStartOf($o)->format('Y-m-d') === $dateWithDefault);

    expect($localStartOf($overrideOption)->format('H:i'))->toBe('14:00');
    expect($localStartOf($defaultOption)->format('H:i'))->toBe('09:00');
});

test('une plage horaire personnalisée partielle (début sans fin) est rejetée par la validation', function (): void {
    // Vérifié empiriquement : la règle 'end' => required_with:...start capte déjà ce cas AVANT
    // d'atteindre store() - une chaîne vide est traitée comme "absente" côté required_with, donc
    // start='14:00' (présent) + end='' (absent) échoue la validation. Clé d'erreur exacte :
    // candidate_date_ranges.0.0.end (indices imbriqués Laravel, pas la clé générique candidate_dates).
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage surcharge incomplète',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate],
        'candidate_date_ranges' => [
            0 => [['start' => '14:00', 'end' => '']],
        ],
    ]);

    $response->assertSessionHasErrors('candidate_date_ranges.0.0.end');
    expect(Poll::where('title', 'Sondage surcharge incomplète')->exists())->toBeFalse();
});

test('Multi-plages (demande utilisateur 2026-07-17) : une date candidate peut proposer PLUSIEURS plages horaires (matin ET après-midi)', function (): void {
    // Veille pp_search juillet 2026, validée Perplexity + Codex + Gemini (95/100) : modéliser
    // chaque date candidate comme une LISTE de plages horaires, pas une seule paire début/fin -
    // permet de proposer "9h-12h ET 14h-17h" en sautant le dîner pour une même date.
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage matin et après-midi',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 60,
        'range_start_time' => '09:00',
        'range_end_time' => '17:00',
        'step_minutes' => 60,
        'candidate_dates' => [$futureDate],
        'candidate_date_ranges' => [
            0 => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '17:00'],
            ],
        ],
    ]);

    $poll = Poll::where('title', 'Sondage matin et après-midi')->first();
    expect($poll)->not->toBeNull();
    $response->assertSessionDoesntHaveErrors();

    // 09:00-12:00 (durée 60, pas 60) = 3 créneaux (9h,10h,11h) ; 14:00-17:00 = 3 créneaux
    // (14h,15h,16h) -> 6 créneaux au total, AUCUN entre 12h et 14h (le "dîner" est bien sauté).
    $options = $poll->options()->orderBy('starts_at')->get();
    expect($options)->toHaveCount(6);

    $localStartOf = fn ($option) => \Carbon\Carbon::parse($option->getRawOriginal('starts_at'), 'UTC')->setTimezone('America/Toronto');
    $localHours = $options->map(fn ($o) => $localStartOf($o)->format('H:i'))->sort()->values()->all();

    expect($localHours)->toBe(['09:00', '10:00', '11:00', '14:00', '15:00', '16:00']);
    expect($localHours)->not->toContain('12:00');
    expect($localHours)->not->toContain('13:00');
});

test('Multi-plages : deux plages qui se chevauchent pour la même date sont rejetées avec un message clair', function (): void {
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage plages chevauchantes',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '17:00',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate],
        'candidate_date_ranges' => [
            0 => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '11:00', 'end' => '14:00'],
            ],
        ],
    ]);

    $response->assertSessionHasErrors('candidate_dates');
    expect(Poll::where('title', 'Sondage plages chevauchantes')->exists())->toBeFalse();
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

test('une date candidate qui produit 0 créneau (passage à l\'heure d\'été) est rejetée, pas publiée sans options', function (): void {
    // Round 19 (skill /100) : SlotGenerationService::validateInputs() compare la plage horaire
    // à la durée sur une date de référence NEUTRE (2000-01-01, sans DST) - une plage/durée
    // nominalement valides (01h30-03h00 = 90 min, durée 60 min) passent donc la validation.
    // Mais le 14 mars 2027 (passage à l'heure d'été, America/Toronto), l'écart RÉEL entre
    // 01h30 et 03h00 heure locale n'est que de 30 minutes (l'heure 02h00-02h59 n'existe pas ce
    // jour-là) - inférieur à la durée de 60 min. Prouvé en réel avant le fix :
    // SlotGenerationService::generateSlots(['2027-03-14'], '01:30', '03:00', 60, 15,
    // 'America/Toronto') retourne un tableau VIDE. Avant ce fix, store() ne vérifiait que
    // count($slots) > 500 (jamais === 0) : le Poll était quand même sauvegardé avec
    // status='open' et ZÉRO PollOption - un sondage publié, partageable, sur lequel personne
    // ne peut voter, sans aucun message d'erreur pour le créateur.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage DST zéro créneau',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 60,
        'range_start_time' => '01:30',
        'range_end_time' => '03:00',
        'step_minutes' => 15,
        'candidate_dates' => ['2027-03-14'],
    ]);

    $response->assertSessionHasErrors('candidate_dates');
    expect(Poll::where('title', 'Sondage DST zéro créneau')->exists())->toBeFalse();
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

// ── Round 20 (skill /100) : contournement de `distinct` par variation de format ────────────

test('deux dates candidates dans des formats DIFFÉRENTS représentant le MÊME jour sont rejetées (contournement de distinct)', function (): void {
    // Round 20 (skill /100) : la règle Laravel `distinct` compare des CHAÎNES EXACTES, pas des
    // dates calendaires. Avant ce fix, `candidate_dates.*` portait la règle générique `date`
    // (accepte tout ce que strtotime() reconnaît) au lieu de `date_format:Y-m-d`. Preuve réelle
    // AVANT le fix (requête HTTP réelle rejouée pendant cet audit) : POST avec
    // candidate_dates=['2027-03-14', '2027-3-14'] (même jour calendaire, deux formats
    // différents - le second sans zéro de tête sur le mois) passait `distinct` intact (ce sont
    // deux chaînes différentes en octets) puis SlotGenerationService::generateSlots() ->
    // Carbon::createFromFormat('Y-m-d H:i', ...) parsait les DEUX chaînes vers exactement le
    // même instant UTC - créant 4 PollOption (2 créneaux x 2 dates "différentes") avec
    // starts_at/ends_at strictement identiques deux à deux, recréant exactement le bug de
    // scission de votes du round 11 par simple changement de format plutôt que par répétition
    // littérale. Le <input type="date"> du formulaire (create.blade.php) soumet TOUJOURS le
    // format canonique Y-m-d (spec HTML5) - ce durcissement ne restreint donc aucun usage
    // légitime, seulement les requêtes forgées hors formulaire.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage date format contourné',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => ['2027-03-14', '2027-3-14'],
    ]);

    $response->assertSessionHasErrors('candidate_dates.1');
    expect(Poll::where('title', 'Sondage date format contourné')->exists())->toBeFalse();
});

test('deux options classiques qui ne diffèrent que par la casse sont rejetées (contournement de distinct)', function (): void {
    // Round 20 (skill /100) : même famille de contournement que le test précédent, appliquée aux
    // options texte du type classique. Preuve réelle AVANT le fix : POST avec
    // options=['Pizza', 'pizza'] passait `distinct` (chaînes différentes en octets) et créait
    // bien 2 PollOption distinctes ("Pizza" et "pizza"), visuellement quasi-identiques pour un
    // votant - la même scission silencieuse de votes que le round 11, contournée via la casse
    // plutôt que la répétition exacte. Corrigé par Modules\Decido\Rules\DistinctNormalized,
    // ajoutée à la règle `options` (niveau tableau complet, nécessaire pour comparer chaque
    // élément à tous les autres).
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage option casse contournée',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'pizza'],
    ]);

    $response->assertSessionHasErrors('options');
    expect(Poll::where('title', 'Sondage option casse contournée')->exists())->toBeFalse();
});

test('deux options classiques qui ne diffèrent que par des espaces internes multiples sont rejetées (contournement de distinct)', function (): void {
    // Round 20 (skill /100) : troisième volet du même contournement - un navigateur affiche
    // "Pizza  4 fromages" (deux espaces) et "Pizza 4 fromages" (un espace) de façon strictement
    // identique (collapse HTML des espaces multiples), rendant les deux options indiscernables
    // pour un votant tout en étant deux chaînes différentes pour `distinct`. Preuve réelle AVANT
    // le fix : cette requête créait bien 2 PollOption distinctes.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage option espaces contournés',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza 4 fromages', 'Pizza  4 fromages'],
    ]);

    $response->assertSessionHasErrors('options');
    expect(Poll::where('title', 'Sondage option espaces contournés')->exists())->toBeFalse();
});

test('des options réellement distinctes (mots différents, accents différents) ne déclenchent pas de faux positif DistinctNormalized', function (): void {
    // Contrôle négatif indispensable : DistinctNormalized ne doit PAS confondre des options qui
    // sont légitimement différentes. "Café" et "Thé" ne partagent aucune racine ; "Pizza" et
    // "Sushi" non plus. La normalisation (mb_strtolower + collapse des espaces, + Normalizer NFC
    // depuis le round 21) ne touche PAS au CONTENU des mots - seule la FORME d'encodage d'un même
    // caractère (round 21, cas NFC/NFD) est désormais neutralisée ; ce test prouve que le
    // correctif reste chirurgical et ne bloque aucun sondage légitime à options variées.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage options réellement distinctes',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Café', 'Thé', 'Pizza', 'Sushi'],
    ]);

    $poll = Poll::where('title', 'Sondage options réellement distinctes')->first();
    expect($poll)->not->toBeNull();
    $this->assertSame(4, $poll->options()->count());
});

// ── Round 21 (skill /100) : contournement de DistinctNormalized par normalisation Unicode ──

test('deux options composées de code points Unicode différents (NFC vs NFD) mais visuellement identiques sont rejetées', function (): void {
    // Round 21 (skill /100) : le round 20 avait lui-même documenté cette limite dans son propre
    // test de contrôle négatif ("seul un cas de variation Unicode NFC/NFD - hors périmètre de ce
    // round - pourrait les confondre"). DistinctNormalized::validate() ne fait que trim() +
    // collapse des espaces + mb_strtolower() - AUCUNE normalisation de forme Unicode. Or un même
    // caractère accentué peut être encodé de deux façons strictement différentes en octets tout
    // en étant PARFAITEMENT identique à l'affichage dans tout navigateur/rendu texte :
    //   - NFC (forme précomposée)  : "é" = U+00E9 seul (1 code point)
    //   - NFD (forme décomposée)   : "é" = U+0065 (e) + U+0301 (accent aigu combinant, 2 code points)
    // Preuve réelle AVANT ce fix (requête HTTP rejouée pendant cet audit) : POST avec
    // options=["caf\u{E9}", "cafe\u{0301}"] (bytes strictement différents : 636166c3a9 vs
    // 63616665cc81) passait DistinctNormalized intact (mb_strtolower ne touche pas à la
    // composition Unicode) et créait bien 2 PollOption distinctes, rendues à l'identique "café"
    // par le navigateur - recréant exactement le bug de scission de votes des rounds 11/20, cette
    // fois via un vecteur invisible à l'oeil nu (aucune différence de casse ni d'espacement
    // visible, seulement l'encodage sous-jacent). Corrigé en ajoutant
    // Normalizer::normalize($str, Normalizer::FORM_C) AVANT le collapse d'espaces/minuscules dans
    // DistinctNormalized::validate() (extension intl confirmée chargée sur ce projet).
    config()->set('decido.under_construction', false);

    $nfc = "caf\u{00E9}";   // é précomposé (U+00E9), 1 code point
    $nfd = "cafe\u{0301}";  // e + accent combinant (U+0065 U+0301), 2 code points

    // Contrôle : les deux chaînes sont bien différentes en octets mais rendues identiquement.
    expect($nfc)->not->toBe($nfd);
    expect(mb_strtolower($nfc))->not->toBe(mb_strtolower($nfd));

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage option Unicode NFC-NFD',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => [$nfc, $nfd],
    ]);

    $response->assertSessionHasErrors('options');
    expect(Poll::where('title', 'Sondage option Unicode NFC-NFD')->exists())->toBeFalse();
});

test('deux dates candidates dans des formats Unicode NFC vs NFD sont également rejetées (garde-fou partagé)', function (): void {
    // Round 21 (skill /100) : DistinctNormalized n'est appliquée qu'au champ `options` (type
    // classique) - `candidate_dates.*` reste protégée par la règle Laravel `distinct` seule
    // (round 20), mais un format Y-m-d strict (imposé au round 20) ne contient que des chiffres
    // et des tirets, aucun caractère accentué : la variation NFC/NFD est structurellement
    // impossible sur ce champ. Ce test de contrôle documente ce raisonnement plutôt que de
    // supposer une exposition qui n'existe pas : soumettre une chaîne accentuée dans
    // candidate_dates échoue déjà à `date_format:Y-m-d`, avant même d'atteindre `distinct`.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage date avec accent invalide',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => ["2027-03-\u{00E9}4"],
    ]);

    $response->assertSessionHasErrors('candidate_dates.0');
    expect(Poll::where('title', 'Sondage date avec accent invalide')->exists())->toBeFalse();
});

test('des homoglyphes multi-scripts (cyrillique) et chiffres pleine chasse ne sont PAS détectés par DistinctNormalized (limite connue documentée)', function (): void {
    // Round 21 (skill /100) : contrôle documentant une limite ASSUMÉE, pas un bug corrigé. La
    // normalisation Unicode NFC résout la variation de COMPOSITION d'un même caractère (accent
    // précomposé vs combinant), mais ne résout PAS la confusion entre caractères de SCRIPTS
    // différents qui se ressemblent visuellement (homoglyphes) : la lettre latine "a" (U+0061) et
    // la lettre cyrillique "а" (U+0430) restent deux code points totalement distincts après
    // normalisation NFC - il n'existe aucune relation de canonicité Unicode entre eux (Cyrillique
    // et Latin sont des scripts indépendants). Une détection complète des homoglyphes
    // nécessiterait une table de correspondance multi-scripts substantielle (type
    // TR39/UTS#39 skeleton) et introduirait un risque de faux positifs sur des libellés
    // légitimes contenant des caractères non-latins - hors périmètre raisonnable pour ce module
    // (pas de correctif cosmétique fragile). Preuve réelle : "cafe" (latin) et "cafе" (dernier
    // caractère cyrillique U+0435 à la place de "e" latin U+0065) passent la validation et créent
    // 2 PollOption distinctes, quasi-indétectables à l'oeil. Documenté comme limite connue dans
    // le docblock de DistinctNormalized plutôt que laissé implicite.
    config()->set('decido.under_construction', false);

    $latin = 'cafe';
    $cyrillicLookalike = "caf\u{0435}"; // dernier "e" remplacé par le cyrillique U+0435

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage homoglyphe cyrillique',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => [$latin, $cyrillicLookalike, 'Thé', 'Pizza'],
    ]);

    // Comportement ACTUEL et ASSUMÉ : la validation passe (limite connue, pas un bug).
    $response->assertSessionHasNoErrors();
    $poll = Poll::where('title', 'Sondage homoglyphe cyrillique')->first();
    expect($poll)->not->toBeNull();
    $this->assertSame(4, $poll->options()->count());
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

    // Option E (skill /100 hors gate) : les 2 nouveaux formulaires dédiés (create-date.blade.php,
    // create-classic.blade.php) doivent respecter la même politique noindex que l'ancien
    // formulaire unique qu'ils remplacent.
    $createDateHtml = $this->actingAs($this->superadmin)->get(route('decido.create.date'))->getContent();
    $this->assertStringContainsString('name="robots" content="noindex', $createDateHtml);

    $createClassicHtml = $this->actingAs($this->superadmin)->get(route('decido.create.classic'))->getContent();
    $this->assertStringContainsString('name="robots" content="noindex', $createClassicHtml);
});

test('Option E : le choix de type (decido.create) redirige vers les 2 formulaires dédiés allégés', function (): void {
    // Veille pp_search juillet 2026, validée Perplexity + Codex + Gemini (95/100) : /decido/creer
    // n'est plus qu'un choix rapide de type, chaque type ayant son propre formulaire dédié et
    // allégé plutôt qu'une seule longue page avec rendu conditionnel x-show.
    config()->set('decido.under_construction', false);

    $chooserHtml = $this->actingAs($this->superadmin)->get(route('decido.create'))->getContent();
    $this->assertStringContainsString(route('decido.create.date'), $chooserHtml);
    $this->assertStringContainsString(route('decido.create.classic'), $chooserHtml);

    $dateResponse = $this->actingAs($this->superadmin)->get(route('decido.create.date'));
    $dateResponse->assertStatus(200);
    $dateResponse->assertSee('Dates proposées', false);
    $dateResponse->assertSee('Plus d\'options', false);

    $classicResponse = $this->actingAs($this->superadmin)->get(route('decido.create.classic'));
    $classicResponse->assertStatus(200);
    $classicResponse->assertSee('Mode de vote', false);
    $classicResponse->assertSee('Plus d\'options', false);
});

test('Option E : les 2 formulaires dédiés (date/classique) soumettent bien vers decido.store et créent le sondage', function (): void {
    // Preuve end-to-end que la scission des formulaires n'a rien cassé côté soumission : chaque
    // formulaire dédié pose un <input type="hidden" name="type"> et POST vers la même route
    // decido.store (inchangée), dont la logique de branchement sur "type" reste identique.
    config()->set('decido.under_construction', false);
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $dateResponse = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage via formulaire dédié date',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate],
    ]);
    $dateResponse->assertSessionDoesntHaveErrors();
    expect(Poll::where('title', 'Sondage via formulaire dédié date')->exists())->toBeTrue();

    $classicResponse = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage via formulaire dédié classique',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Sushi'],
    ]);
    $classicResponse->assertSessionDoesntHaveErrors();
    expect(Poll::where('title', 'Sondage via formulaire dédié classique')->exists())->toBeTrue();
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

test('clôturer un sondage avec le final_option_id d\'un AUTRE sondage (IDOR) est rejeté (404)', function (): void {
    // Round 19 (skill /100) : audit ciblé - PollManageController::close() ne valide
    // final_option_id qu'avec ['nullable', 'integer'] côté règles Laravel (aucune contrainte
    // exists:decido_poll_options,id,poll_id,{poll}). Le code applique cependant DÉJÀ un garde-fou
    // manuel juste après la validation : `$pollModel->options()->where('id', $finalOptionId)
    // ->exists()`, où options() est un HasMany scopé automatiquement par poll_id - un ID d'option
    // appartenant à un AUTRE sondage échoue donc ce exists() et déclenche abort(404), AVANT toute
    // écriture de final_option_id. Ce test verrouille ce comportement (déjà correct, vérifié en
    // conditions réelles avec un vrai jeton admin valide et une vraie option étrangère) pour
    // qu'une régression future soit détectée immédiatement - notamment tout export ICS qui
    // référencerait, sous IDOR, les dates/heures d'un sondage totalement différent.
    config()->set('decido.under_construction', false);

    $pollA = decidoCreatePoll(['title' => 'Sondage A', 'admin_token' => 'jeton-a']);
    $pollB = decidoCreatePoll(['title' => 'Sondage B', 'admin_token' => 'jeton-b']);
    $optionOfPollB = PollOption::factory()->create(['poll_id' => $pollB->id]);

    $response = $this->post(route('decido.close', ['poll' => $pollA->public_id, 'adminToken' => 'jeton-a']), [
        'final_option_id' => $optionOfPollB->id,
    ]);

    $response->assertStatus(404);

    $pollA->refresh();
    $this->assertNotSame('closed', $pollA->status->value, 'Le sondage A a été clôturé malgré une option étrangère (IDOR).');
    $this->assertNull($pollA->final_option_id);
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

// ── Round 22 (skill /100) : intégrité structurelle RFC4180 du CSV exporté ──────────────────

test('un voter_pseudonym contenant virgule/guillemets/point-virgule/saut de ligne/backslash+guillemet ne corrompt pas la structure ni le contenu du CSV exporté (RFC4180)', function (): void {
    // Round 22 (skill /100) : au-delà de l'injection de formule déjà neutralisée (round 5),
    // exportCsv() appelle fputcsv($handle, [...], ';', '"', '\\') - le 5e argument '\\' active
    // le mécanisme d'ÉCHAPPEMENT PROPRIÉTAIRE de PHP (non-RFC4180), qui échappe le caractère
    // SUIVANT le backslash au lieu de doubler les guillemets internes comme le veut la norme.
    // Bug réel trouvé par isolation directe de fputcsv/fgetcsv (hors framework, avant ce
    // correctif) : un voter_pseudonym texte libre contenant un backslash immédiatement suivi
    // d'un guillemet interne (ex. Jean\"Boss" - un votant qui se met des guillemets dans son
    // pseudonyme après un backslash, ex. un chemin Windows halluciné ou juste une frappe libre)
    // corrompt le champ de DEUX façons prouvées séparément : (a) relu avec le MÊME escape='\\'
    // (round-trip PHP), le guillemet fermant est lui-même échappé - le parseur avale alors le
    // reste de la ligne ET la ligne suivante entière dans le même champ (4 colonnes au lieu de
    // 3, un votant entier disparaît) ; (b) relu avec un lecteur RFC4180 STRICT (escape='',
    // comportement réel d'Excel/Google Sheets/Numbers, qui ignorent la convention backslash
    // propriétaire de PHP), le nombre de colonnes reste correct MAIS la VALEUR récupérée est
    // silencieusement GARBLED (`Jean\Boss"""` au lieu de `Jean\"Boss"`) - corruption de donnée
    // invisible, sans erreur, pire qu'un plantage. Ce test prouve (b), le scénario le plus
    // représentatif d'un usage réel (l'organisateur ouvre le CSV dans un tableur, pas avec du
    // PHP). Requête HTTP RÉELLE vers l'export CSV, RE-PARSE avec fgetcsv() (parseur CSV
    // standard, escape='' = RFC4180 strict), vérifie que la structure (lignes/colonnes) ET le
    // CONTENU (pseudonyme exact) survivent. Avec l'ancien code, la dernière assertion de valeur
    // échoue (contenu corrompu). Corrigé en passant une chaîne vide comme 5e argument à
    // fputcsv() (désactive le mécanisme propriétaire, revient au pur doublage RFC4180 des
    // guillemets internes) - vérifié : n'introduit AUCUNE régression sur les autres cas déjà
    // couverts (virgule+guillemets round 5, saut de ligne, point-virgule = délimiteur réel).
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-csv-rfc4180', 'vote_mode' => 'single_choice']);

    $optionA = $poll->options()->create(['label' => 'Option virgule/guillemets', 'sort_order' => 0]);
    $optionA->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => Str::uuid()->toString(),
        'voter_pseudonym' => 'Jean, "Le Boss"',
        'value' => 'selected',
    ]);

    $optionB = $poll->options()->create(['label' => 'Option saut de ligne', 'sort_order' => 1]);
    $optionB->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => Str::uuid()->toString(),
        'voter_pseudonym' => "Marie\nDupont",
        'value' => 'selected',
    ]);

    $optionC = $poll->options()->create(['label' => 'Option point-virgule (délimiteur réel)', 'sort_order' => 2]);
    $optionC->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => Str::uuid()->toString(),
        'voter_pseudonym' => 'Suzie; test',
        'value' => 'selected',
    ]);

    // Le cas pathologique réel : backslash immédiatement suivi d'un guillemet interne.
    $optionD = $poll->options()->create(['label' => 'Option backslash+guillemet', 'sort_order' => 3]);
    $optionD->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => Str::uuid()->toString(),
        'voter_pseudonym' => 'Jean\\"Boss"',
        'value' => 'selected',
    ]);

    // Vote témoin, placé APRÈS le cas pathologique : si la corruption existe, son pseudonyme
    // disparaît fusionné dans le champ précédent au lieu d'apparaître comme ligne distincte.
    $optionE = $poll->options()->create(['label' => 'Option témoin après', 'sort_order' => 4]);
    $optionE->votes()->create([
        'poll_id' => $poll->id,
        'voter_token' => Str::uuid()->toString(),
        'voter_pseudonym' => 'Témoin Après',
        'value' => 'selected',
    ]);

    $response = $this->get(route('decido.export.csv', ['poll' => $poll->public_id, 'adminToken' => 'jeton-csv-rfc4180']));
    $response->assertStatus(200);

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $response->getContent());
    rewind($stream);

    $rows = [];
    while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
        $rows[] = $row;
    }
    fclose($stream);

    // 1 ligne d'en-tête + 5 votes = 6 lignes, chacune à 3 colonnes exactement - aucune fusion.
    expect($rows)->toHaveCount(6);
    foreach ($rows as $row) {
        expect($row)->toHaveCount(3);
    }

    $pseudonyms = collect($rows)->skip(1)->pluck(1)->values()->all();
    expect($pseudonyms)->toBe([
        'Jean, "Le Boss"',
        "Marie\nDupont",
        'Suzie; test',
        'Jean\\"Boss"',
        'Témoin Après',
    ]);
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
        'decido.index', 'decido.create', 'decido.create.date', 'decido.create.classic', 'decido.store',
        'decido.manage', 'decido.close', 'decido.extend', 'decido.export.csv', 'decido.export.ics',
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

// ── Round 16 (skill /100) : atomicité de la création d'un sondage ──────────

test('un échec en cours de création (Nème option) ne laisse aucun sondage fantôme en base (atomicité, type date)', function (): void {
    // Round 16 (skill /100) : PollManageController::store() créait le Poll (INSERT immédiat via
    // $poll->save()) PUIS bouclait sur la création de chaque PollOption (jusqu'à 500 créneaux pour
    // le type "date") SANS aucune DB::transaction() - contrairement au pattern déjà en place pour
    // Poll::claimShortUrl() (round 15) et PublicPollController::vote() (lockForUpdate). Seule une
    // InvalidArgumentException (garde-fou >500 créneaux, round 9) était rattrapée pour supprimer
    // manuellement le sondage orphelin ; toute AUTRE exception survenant en cours de boucle (ex.
    // contrainte DB, perte de connexion, timeout - un risque réel avec des centaines d'INSERT
    // séquentiels) n'était rattrapée nulle part et laissait un sondage "fantôme" en base :
    // status='draft', options PARTIELLEMENT créées (les 2 premières), jamais supprimé ni jamais
    // promu à status='open', visible dans "Mes sondages" du créateur mais définitivement
    // inutilisable. Ce test force une exception injectée sur la création de la 3e PollOption (sur
    // 6 créneaux générés par la plage testée) et prouve que ni le Poll ni ses options partielles
    // ne survivent à l'échec, une fois DB::transaction() en place.
    config()->set('decido.under_construction', false);
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $created = 0;
    PollOption::creating(function () use (&$created) {
        $created++;
        if ($created === 3) {
            throw new RuntimeException('Panne DB simulée (round 16)');
        }
    });

    $pollCountBefore = Poll::count();
    $optionCountBefore = PollOption::count();

    try {
        $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
            'title' => 'Sondage atomicité date',
            'type' => 'date',
            'timezone' => 'America/Toronto',
            'duration_minutes' => 30,
            'range_start_time' => '09:00',
            'range_end_time' => '12:00', // 6 créneaux générés, largement > 3
            'step_minutes' => 30,
            'candidate_dates' => [$futureDate],
        ]);

        // La panne DB simulée n'est jamais une InvalidArgumentException (message convivial) : elle
        // remonte telle quelle et Laravel la convertit en réponse d'erreur serveur - preuve que le
        // code testé est réellement passé par l'exception injectée, pas contourné silencieusement.
        $response->assertServerError();

        expect(Poll::count())->toBe($pollCountBefore, 'Un sondage fantôme (draft, options incomplètes) a survécu à l’échec.');
        expect(PollOption::count())->toBe($optionCountBefore, 'Des options partielles ont survécu à l’échec en cours de boucle.');
        expect(Poll::where('title', 'Sondage atomicité date')->exists())->toBeFalse();
    } finally {
        PollOption::flushEventListeners();
    }
});

test('un échec en cours de création (2e option) ne laisse aucun sondage fantôme en base (atomicité, type classique)', function (): void {
    // Round 16 (skill /100) : même bug d'atomicité que le test précédent, sur le second chemin de
    // code (type "classique", options texte libre au lieu de créneaux générés) - la boucle de
    // création des PollOption est structurellement identique (foreach ... $poll->options()->create)
    // et n'était pas davantage protégée pour ce type.
    config()->set('decido.under_construction', false);

    $created = 0;
    PollOption::creating(function () use (&$created) {
        $created++;
        if ($created === 2) {
            throw new RuntimeException('Panne DB simulée (round 16, type classique)');
        }
    });

    $pollCountBefore = Poll::count();
    $optionCountBefore = PollOption::count();

    try {
        $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
            'title' => 'Sondage atomicité classique',
            'type' => 'classic',
            'timezone' => 'America/Toronto',
            'vote_mode' => 'single_choice',
            'options' => ['Pizza', 'Sushi', 'Poutine'],
        ]);

        $response->assertServerError();

        expect(Poll::count())->toBe($pollCountBefore);
        expect(PollOption::count())->toBe($optionCountBefore);
        expect(Poll::where('title', 'Sondage atomicité classique')->exists())->toBeFalse();
    } finally {
        PollOption::flushEventListeners();
    }
});

// ── Round 17 (skill /100) : atomicité du VOTE lui-même (multi-options + revote) ────────────

test('un échec en cours de vote multi-options (mode approval) ne laisse aucun vote partiel en base (atomicité)', function (): void {
    // Round 17 (skill /100) : angle structurellement analogue au bug de création de sondage
    // corrigé au round 16 (PollManageController::store() bouclait sur les PollOption sans
    // DB::transaction()), mais testé ici sur le chemin de VOTE - PublicPollController::vote()
    // boucle sur chaque option choisie (PollVote::updateOrCreate) pour les modes yes_no_maybe et
    // approval, où un votant peut sélectionner plusieurs options en une seule soumission (une
    // ligne PollVote par option). Lecture du code : contrairement à store() avant son correctif,
    // vote() enveloppe déjà TOUTE la logique (loop d'upsert + delete des options désélectionnées)
    // dans un DB::transaction() unique (ajouté au round 6 à l'origine pour la fenêtre TOCTOU sur
    // le statut du sondage, lockForUpdate) - ce test le PROUVE en injectant une exception à la
    // création du 3e vote sur 5 options sélectionnées : si la transaction protégeait réellement la
    // boucle, aucun des 5 votes ne doit persister (tout ou rien), jamais un vote partiel silencieux
    // (le votant croirait avoir voté pour 5 options alors qu'il n'en aurait obtenu que 2 en DB).
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'classic', 'vote_mode' => 'approval']);
    $options = PollOption::factory()->count(5)->create(['poll_id' => $poll->id]);

    $created = 0;
    PollVote::creating(function () use (&$created): void {
        $created++;
        if ($created === 3) {
            throw new RuntimeException('Panne DB simulée (round 17, vote multi-options)');
        }
    });

    try {
        $response = $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
            'voter_pseudonym' => 'Denise',
            'votes' => $options->pluck('id')->all(),
        ]);

        // La panne DB simulée n'est jamais une exception de validation (Laravel la convertit en
        // réponse d'erreur serveur) - preuve que le code testé est bien passé par l'exception
        // injectée, pas contourné silencieusement par un chemin de validation antérieur.
        $response->assertServerError();

        expect(PollVote::where('poll_id', $poll->id)->count())
            ->toBe(0, 'Des votes partiels ont survécu à l’échec en cours de boucle (tout-ou-rien violé).');
    } finally {
        PollVote::flushEventListeners();
    }
});

test('un échec en cours de REVOTE (mode approval) ne perd pas les anciens votes et n’en laisse aucun nouveau partiel', function (): void {
    // Round 17 (skill /100) : second volet du même angle - le REVOTE (modification du choix d'un
    // votant déjà connu via son voter_token) crée d'abord les nouveaux votes (updateOrCreate) PUIS
    // supprime les anciens votes désélectionnés, dans la MÊME transaction. Hypothèse adverse : la
    // fenêtre la plus dangereuse n'est PAS entre le 1er nouveau vote et l'échec (rien n'a encore
    // été fait d'irréversible), mais après que PLUSIEURS nouveaux votes ont déjà été insérés et
    // qu'une panne survient sur le DERNIER avant que la suppression des anciens votes ne soit
    // même atteinte : sans transaction, ceci laisserait à la fois les 2 anciens votes (jamais
    // supprimés, cette ligne n'étant jamais exécutée) ET les nouveaux votes partiellement créés
    // (C, D) en base simultanément - un état incohérent en UNION (4 votes au lieu de 2), pas une
    // simple perte. Ce test simule un votant ayant déjà voté pour 2 options (A, B), qui revote
    // pour 3 options complètement différentes (C, D, E) ; l'exception est injectée sur le 3e (et
    // dernier) nouveau vote - preuve attendue : la transaction unique fait qu'AUCUNE écriture (ni
    // les nouveaux votes partiels C/D, ni la suppression des anciens) n'est retenue, donc les
    // votes originaux A et B - et EUX SEULS - doivent ressortir après l'échec.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'classic', 'vote_mode' => 'approval']);
    $options = PollOption::factory()->count(5)->create(['poll_id' => $poll->id]);
    [$optA, $optB, $optC, $optD, $optE] = $options->all();

    $voterToken = (string) Str::uuid();

    // Vote initial inséré directement via DB::table() (pas Eloquent create()) pour ne PAS
    // déclencher l'écouteur PollVote::creating installé plus bas, qui ne doit compter que les
    // créations déclenchées par le revote HTTP testé ci-dessous.
    \Illuminate\Support\Facades\DB::table('decido_poll_votes')->insert([
        ['poll_id' => $poll->id, 'option_id' => $optA->id, 'voter_token' => $voterToken, 'voter_pseudonym' => 'Edouard', 'value' => 'selected', 'created_at' => now(), 'updated_at' => now()],
        ['poll_id' => $poll->id, 'option_id' => $optB->id, 'voter_token' => $voterToken, 'voter_pseudonym' => 'Edouard', 'value' => 'selected', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $cookieName = 'decido_voter_'.$poll->public_id;

    $created = 0;
    PollVote::creating(function () use (&$created): void {
        $created++;
        if ($created === 3) {
            throw new RuntimeException('Panne DB simulée (round 17, revote)');
        }
    });

    try {
        $response = $this->withCookie($cookieName, $voterToken)->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
            'voter_pseudonym' => 'Edouard',
            'votes' => [$optC->id, $optD->id, $optE->id],
        ]);

        $response->assertServerError();

        // Les 2 votes originaux (A, B) doivent survivre INTACTS (rollback complet de la
        // transaction), pas seulement partiellement, et aucun nouveau vote (C, D, E) ne doit
        // exister.
        $remainingOptionIds = PollVote::where('voter_token', $voterToken)->pluck('option_id')->sort()->values()->all();
        expect($remainingOptionIds)->toBe(collect([$optA->id, $optB->id])->sort()->values()->all());
    } finally {
        PollVote::flushEventListeners();
    }
});

test('decido:purge-expired supprime les sondages expirés (peu importe le statut) et épargne les autres', function (): void {
    // Élargi le 2026-07-19 (politique de rétention complète) : avant ce changement, seul un
    // sondage 'closed' pouvait être purgé (filtre status='closed' retiré). Ce test couvre
    // désormais explicitement le cas OUVERT expiré, qui devait auparavant survivre
    // indéfiniment (contournement documenté par le round 5, corrigé ici).
    $expiredClosed = decidoCreatePoll(['status' => 'closed']);
    $expiredClosed->expires_at = now()->subDay();
    $expiredClosed->save();

    $notYetExpiredClosed = decidoCreatePoll(['status' => 'closed']);
    $notYetExpiredClosed->expires_at = now()->addMonths(3);
    $notYetExpiredClosed->save();

    $expiredOpen = decidoCreatePoll(['status' => 'open']);
    $expiredOpen->expires_at = now()->subDay();
    $expiredOpen->save();

    $stillOpenNoExpiry = decidoCreatePoll(['status' => 'open']);
    // expires_at reste NULL (helper decidoCreatePoll ne le définit pas par défaut) : ne doit
    // jamais être purgé (whereNotNull('expires_at') dans PurgeExpiredPollsCommand).

    $this->artisan('decido:purge-expired')->assertExitCode(0);

    expect(Poll::find($expiredClosed->id))->toBeNull();
    expect(Poll::find($expiredOpen->id))->toBeNull();
    expect(Poll::find($notYetExpiredClosed->id))->not->toBeNull();
    expect(Poll::find($stillOpenNoExpiry->id))->not->toBeNull();
});

// ── Round 18 (skill /100) : validation du fuseau horaire fourni par l'utilisateur ──────────

test('un fuseau horaire invalide (chaîne arbitraire non-IANA) est rejeté par une erreur de validation, pas un crash 500', function (): void {
    // Round 18 (skill /100) : avant le fix, la règle de validation de `timezone` était
    // `['required', 'string', 'max:60']` - AUCUNE vérification contre la vraie liste IANA
    // (timezone_identifiers_list()). Cette chaîne arbitraire atteignait ensuite directement
    // SlotGenerationService::generateSlots(), qui la transmet telle quelle à
    // Carbon::createFromFormat(..., $timezone) - lequel construit un DateTimeZone en interne.
    // DateTimeZone::__construct() lève une \Exception (jamais une InvalidArgumentException) sur
    // une chaîne invalide - donc NI la règle de validation NI le catch (InvalidArgumentException
    // $e) de PollManageController::store() n'interceptaient cette erreur : elle remontait telle
    // quelle jusqu'au gestionnaire d'exceptions global de Laravel, produisant un crash 500 brut
    // pour une simple erreur de saisie (ex. mauvaise valeur envoyée par un <select> altéré côté
    // client, ou requête forgée). Le DB::transaction() du round 16 évite qu'un sondage fantôme
    // survive à ce crash (rollback automatique), mais un 500 sur une entrée simplement invalide
    // reste un défaut de robustesse : ce test le prouve avec une vraie requête HTTP.
    config()->set('decido.under_construction', false);
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage fuseau invalide',
        'type' => 'date',
        'timezone' => 'Not/AZone',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:30',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate],
    ]);

    $response->assertSessionHasErrors('timezone');
    expect(Poll::where('title', 'Sondage fuseau invalide')->exists())->toBeFalse();
});

test('un fuseau horaire vide, à rallonge ou contenant des caractères spéciaux est rejeté sans exception PHP', function (): void {
    // Complète le test précédent avec des variantes adverses supplémentaires explicitement
    // demandées : chaîne vide (déjà couverte par `required`, mais vérifiée ici via le sondage
    // classique pour prouver que le champ reste bien exigé sur les deux chemins de création),
    // valeur à rallonge (> max:60) et caractères spéciaux/injection.
    config()->set('decido.under_construction', false);

    $tooLong = str_repeat('A/', 40).'Z'; // > 60 caractères, jamais un identifiant IANA valide
    $special = "America/Toronto'; DROP TABLE decido_polls; --";

    foreach ([$tooLong, $special, ''] as $badTimezone) {
        $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
            'title' => 'Sondage classique fuseau '.md5($badTimezone),
            'type' => 'classic',
            'timezone' => $badTimezone,
            'vote_mode' => 'single_choice',
            'options' => ['Pizza', 'Sushi'],
        ]);

        $response->assertSessionHasErrors('timezone');
        expect(Poll::where('title', 'Sondage classique fuseau '.md5($badTimezone))->exists())->toBeFalse();
    }
});

// ── Round 18 (skill /100) : idempotence de la clôture d'un sondage déjà fermé ──────────────

test('clôturer deux fois le même sondage (rejeu/double-clic) n’écrase pas le créneau final ni ne recule la date d’expiration', function (): void {
    // Round 18 (skill /100) : PollManageController::close() ne vérifiait jamais si le sondage
    // était DÉJÀ fermé avant de réappliquer status='closed', final_option_id=<valeur soumise> et
    // expires_at=now()+N mois. Un second appel (double-clic avant que l'UI ne masque le
    // formulaire de clôture - lequel n'est affiché QUE si status==='open', mais rien ne
    // l'empêche côté serveur - ou un simple rejeu de la requête POST) pouvait donc : (1)
    // remplacer silencieusement le créneau final déjà choisi et potentiellement déjà exporté en
    // ICS/communiqué aux participants, y compris le mettre à NULL si le second appel omet
    // final_option_id ; (2) repousser indéfiniment expires_at à chaque rejeu, contournant la
    // politique de purge automatique (decido:purge-expired) que le round 5 avait justement
    // instaurée. Preuve : premier appel clôture avec l'option A, second appel (rejeu) tente de
    // clôturer avec l'option B - le sondage doit rester sur l'option A et l'expiration ne doit
    // pas avoir bougé.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-close-idempotent']);
    $optionA = $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);
    $optionB = $poll->options()->create(['label' => 'Option B', 'sort_order' => 1]);

    $this->post(route('decido.close', ['poll' => $poll->public_id, 'adminToken' => 'jeton-close-idempotent']), [
        'final_option_id' => $optionA->id,
    ]);

    $poll->refresh();
    $this->assertSame('closed', $poll->status->value);
    $this->assertSame($optionA->id, $poll->final_option_id);
    $expiresAtAfterFirstClose = $poll->expires_at;

    // Rejeu/double-clic : tente de clôturer À NOUVEAU avec une option DIFFÉRENTE.
    $this->travel(1)->hour();
    $this->post(route('decido.close', ['poll' => $poll->public_id, 'adminToken' => 'jeton-close-idempotent']), [
        'final_option_id' => $optionB->id,
    ]);

    $poll->refresh();
    $this->assertSame('closed', $poll->status->value);
    $this->assertSame($optionA->id, $poll->final_option_id, 'Le créneau final a été écrasé par un second appel à close() - clôture non idempotente.');
    $this->assertTrue(
        $expiresAtAfterFirstClose->equalTo($poll->expires_at),
        'La date d’expiration a reculé suite à un second appel à close() - purge automatique contournable par rejeu.'
    );
});

// ── Round 23 (skill /100) : limite de longueur de description, purge et ShortUrl orphelin,
//    export ICS sans créneau final choisi ────────────────────────────────────────────────────

test('une description de sondage dépassant 5000 caractères est rejetée par une erreur de validation, pas un crash', function (): void {
    // Round 23 (skill /100) : avant ce fix, 'description' n'avait AUCUNE limite de longueur
    // (['nullable', 'string'] seulement), contrairement à 'title' (max:255). Preuve réelle isolée
    // hors framework (INSERT PDO direct sur la DB MySQL/MariaDB locale, 'strict' => true comme en
    // prod - cf. config/database.php) : une description de 5 Mo ne produit PAS de troncature
    // silencieuse mais lève une PDOException SQLSTATE 22001 "Data too long for column
    // 'description'" (limite réelle de la colonne `text` : 65 535 octets). Cette exception
    // (Illuminate\Database\QueryException, jamais une InvalidArgumentException) n'était
    // interceptée NULLE PART dans store() - elle aurait remonté telle quelle jusqu'au
    // gestionnaire d'exceptions global (crash 500 brut), même défaut de robustesse que le fuseau
    // horaire invalide corrigé au round 18. Ce test prouve la couche de validation HTTP :
    // indépendant du moteur DB utilisé par les tests (SQLite ici, sans limite native), la règle
    // 'max:5000' doit à elle seule rejeter proprement une description trop longue.
    config()->set('decido.under_construction', false);

    $tooLongDescription = str_repeat('A', 5001);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage description trop longue',
        'description' => $tooLongDescription,
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Sushi'],
    ]);

    $response->assertSessionHasErrors('description');
    expect(Poll::where('title', 'Sondage description trop longue')->exists())->toBeFalse();
});

test('une description de sondage de 5000 caractères exactement (limite incluse) est acceptée', function (): void {
    // Complète le test précédent : la limite doit être inclusive (max:5000 accepte 5000, rejette
    // 5001), pas une régression accidentelle qui rejetterait des descriptions légitimes.
    config()->set('decido.under_construction', false);

    $maxDescription = str_repeat('B', 5000);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage description limite',
        'description' => $maxDescription,
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Sushi'],
    ]);

    $response->assertSessionHasNoErrors();
    $poll = Poll::where('title', 'Sondage description limite')->first();
    expect($poll)->not->toBeNull();
    expect(mb_strlen($poll->description))->toBe(5000);
});

test('decido:purge-expired soft-supprime le ShortUrl associé à un sondage expiré, au lieu de laisser un lien mort orphelin', function (): void {
    // Round 23 (skill /100) : decido_polls.short_url_id n'a AUCUNE contrainte de clé étrangère
    // (migration add_short_url_id_to_decido_polls : unsignedBigInteger nullable, ni constrained()
    // ni cascadeOnDelete()). Avant ce fix, decido:purge-expired supprimait le Poll sans jamais
    // toucher au ShortUrl associé (créé par Poll::claimShortUrl()) : celui-ci survivait
    // indéfiniment en base et continuait de rediriger (301, is_active toujours true) vers l'URL
    // du sondage désormais supprimée - un lien mort, potentiellement partagé publiquement (c'est
    // tout l'objet d'un lien court), qui aboutissait à un 404 brut sans jamais être nettoyé.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['status' => 'closed']);
    $poll->expires_at = now()->subDay();
    $poll->save();

    $service = app(\Modules\ShortUrl\Services\ShortUrlService::class);
    $shortUrl = $poll->claimShortUrl($poll->creator_id, $service);
    expect($shortUrl)->not->toBeNull();
    $shortUrlId = $shortUrl->id;

    $this->artisan('decido:purge-expired')->assertExitCode(0);

    expect(Poll::find($poll->id))->toBeNull();
    // Le scope global SoftDeletes du modèle ShortUrl exclut les enregistrements soft-supprimés :
    // ::find() ne le retrouve plus (donc ShortUrlService::resolve() non plus - la redirection
    // publique affichera désormais /lien-expire au lieu d'un 404 brut).
    expect(\Modules\ShortUrl\Models\ShortUrl::find($shortUrlId))->toBeNull();
    $trashed = \Modules\ShortUrl\Models\ShortUrl::withTrashed()->find($shortUrlId);
    expect($trashed)->not->toBeNull();
    expect($trashed->trashed())->toBeTrue();
});

test('decido:purge-expired épargne le ShortUrl d’un sondage clôturé et expiré mais SANS lien court (short_url_id null)', function (): void {
    // Garde-fou complémentaire : la requête ciblant les ShortUrl à nettoyer doit bien filtrer
    // whereNotNull('short_url_id') et ne jamais tenter de matcher/supprimer sur un ID null - un
    // sondage expiré sans lien court associé ne doit provoquer aucune erreur ni effet de bord.
    config()->set('decido.under_construction', false);
    $pollSansLien = decidoCreatePoll(['status' => 'closed']);
    $pollSansLien->expires_at = now()->subDay();
    $pollSansLien->save();

    $countShortUrlsBefore = \Modules\ShortUrl\Models\ShortUrl::count();

    $this->artisan('decido:purge-expired')->assertExitCode(0);

    expect(Poll::find($pollSansLien->id))->toBeNull();
    expect(\Modules\ShortUrl\Models\ShortUrl::count())->toBe($countShortUrlsBefore);
});

test('exportIcs via la route HTTP échoue proprement (redirection + message clair) si le sondage est clôturé sans créneau final choisi', function (): void {
    // Round 23 (skill /100) : PollManageController::close() accepte final_option_id=null (le
    // type yes_no_maybe notamment n'a pas nécessairement de "créneau final unique" au sens
    // classique). PollExportService::exportIcs() gère déjà ce cas proprement au niveau service
    // (RuntimeException levée dès que status!=='closed' OU final_option_id===null - cf. le test
    // "PollExportService::exportIcs lève une exception si le sondage n'est pas fermé" plus haut,
    // qui ne couvre que le premier terme du OU). Aucun test ne prouvait encore le parcours HTTP
    // complet du second terme (clôturé SANS créneau final) : ce test le comble en clôturant
    // réellement un sondage sans final_option_id puis en appelant la route d'export ICS - preuve
    // qu'elle redirige avec un message d'erreur clair (déjà géré par le catch (\RuntimeException)
    // de PollManageController::exportIcs()) plutôt que de produire un fichier ICS cassé/vide ou
    // un crash.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll([
        'admin_token' => 'jeton-ics-sans-final',
        'type' => 'classic',
        'vote_mode' => 'single_choice',
    ]);
    $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);
    $poll->options()->create(['label' => 'Option B', 'sort_order' => 1]);

    $closeResponse = $this->post(
        route('decido.close', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics-sans-final']),
        [] // final_option_id volontairement omis
    );
    $closeResponse->assertRedirect();

    $poll->refresh();
    expect($poll->status->value)->toBe('closed');
    expect($poll->final_option_id)->toBeNull();

    $icsResponse = $this->get(route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics-sans-final']));

    $icsResponse->assertRedirect(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-ics-sans-final']));
    $icsResponse->assertSessionHasErrors('export');
    expect(Poll::find($poll->id))->not->toBeNull(); // aucune donnée corrompue par l'échec d'export
});

// ── Round 24 (skill /100) : identité voter_token (votant anonyme) ──────────────

test('un voter_token brut deviné ou obtenu par un tiers (cookie non chiffré) ne permet ni de lire ni d\'écraser le vote de la victime - seul un cookie chiffré/signé par le serveur (APP_KEY) constitue une identité valide', function (): void {
    // Round 24 (skill /100) : angle jamais audité - le mécanisme d'identité d'un VOTANT anonyme
    // (voter_token), distinct de l'admin_token de l'organisateur déjà audité en profondeur. Lecture
    // du code : PublicPollController::vote()/show() ne lisent JAMAIS voter_token depuis un champ de
    // formulaire ou un paramètre de requête ($request->input()/query()) - uniquement depuis
    // $request->cookie('decido_voter_'.$poll->public_id), généré côté serveur (Str::uuid()) si
    // absent. Ce cookie n'est PAS dans la liste d'exception de bootstrap/app.php
    // (encryptCookies(except: ['consent_v1'])), donc il transite par
    // Illuminate\Cookie\Middleware\EncryptCookies (AES-256-CBC + HMAC via APP_KEY) comme tout autre
    // cookie applicatif, et n'est jamais imprimé dans aucune vue (public ou admin - results.blade.php
    // ne s'en sert que comme clé de tableau PHP côté serveur). Preuve réelle ci-dessous par requêtes
    // HTTP simulant un vrai attaquant : withUnencryptedCookie() envoie la valeur BRUTE (non chiffrée)
    // - exactement ce qu'obtiendrait un tiers qui aurait deviné/intercepté/lu ce token sans détenir
    // l'APP_KEY du serveur. EncryptCookies::decrypt() attrape le DecryptException et met le cookie à
    // null (traité comme absent), donc AUCUNE impersonation possible : ni lecture de l'état existant
    // de la victime (existingVotes vide, bandeau "déjà voté" absent), ni écrasement de son vote
    // (updateOrCreate scopé par voter_token crée une ligne distincte sous un nouveau token aléatoire
    // au lieu de toucher la ligne de la victime). Conclusion : conception saine, aucun correctif -
    // ce test verrouille la propriété en régression.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'classic', 'vote_mode' => 'single_choice']);
    $optionA = $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);
    $optionB = $poll->options()->create(['label' => 'Option B', 'sort_order' => 1]);

    $cookieName = 'decido_voter_'.$poll->public_id;

    // La victime vote une première fois (aucun cookie envoyé - le serveur génère un voter_token
    // aléatoire et le renvoie chiffré via Set-Cookie httpOnly).
    $victimResponse = $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Victime',
        'votes' => (string) $optionA->id,
    ]);

    // TestResponse::getCookie() déchiffre automatiquement (2e argument $decrypt = true par défaut)
    // - on récupère ainsi le voter_token BRUT, exactement ce qu'un attaquant obtiendrait via une
    // fuite (accès DB, capture réseau en clair, historique d'un ordinateur partagé...).
    $rawVoterToken = $victimResponse->getCookie($cookieName)->getValue();
    expect($rawVoterToken)->not->toBeEmpty();

    $victimVoteId = PollVote::where('voter_token', $rawVoterToken)->where('option_id', $optionA->id)->value('id');
    expect($victimVoteId)->not->toBeNull();

    // (c) L'ATTAQUANT tente de LIRE l'état existant de la victime avec ce token brut non chiffré.
    $attackerReadResponse = $this->withUnencryptedCookie($cookieName, $rawVoterToken)
        ->get(route('decido.vote.show', ['slug' => $poll->public_id]));

    $attackerReadResponse->assertOk();
    $attackerReadResponse->assertDontSee('Tu as déjà voté sous ce lien', escape: false);

    // (b) L'ATTAQUANT tente d'ÉCRASER le vote de la victime (revote Option B, pseudonyme différent)
    // en se faisant passer pour elle avec le même token brut non chiffré.
    $this->withUnencryptedCookie($cookieName, $rawVoterToken)
        ->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
            'voter_pseudonym' => 'Attaquant',
            'votes' => (string) $optionB->id,
        ]);

    // Le vote ORIGINAL de la victime doit être totalement intact - même ligne, même option, même
    // pseudonyme, même voter_token - jamais touché par la tentative de l'attaquant.
    $victimVote = PollVote::find($victimVoteId);
    expect($victimVote)->not->toBeNull();
    expect($victimVote->voter_pseudonym)->toBe('Victime');
    expect($victimVote->option_id)->toBe($optionA->id);
    expect($victimVote->voter_token)->toBe($rawVoterToken);

    // Le vote de "l'attaquant" a bien été enregistré (le serveur ne bloque pas un votant anonyme
    // légitime), mais sous un voter_token DIFFÉRENT - nouveau UUID aléatoire généré côté serveur,
    // puisque son cookie brut non chiffré a été rejeté par EncryptCookies et traité comme absent.
    $attackerVoteToken = PollVote::where('option_id', $optionB->id)
        ->where('voter_pseudonym', 'Attaquant')
        ->value('voter_token');
    expect($attackerVoteToken)->not->toBeNull();
    expect($attackerVoteToken)->not->toBe($rawVoterToken);

    expect(PollVote::where('poll_id', $poll->id)->count())->toBe(2);
    expect(PollVote::where('poll_id', $poll->id)->distinct('voter_token')->count('voter_token'))->toBe(2);
});

test('un voter_token injecté comme simple CHAMP de formulaire (sans cookie) est silencieusement ignoré - la seule source d\'identité acceptée est le cookie chiffré', function (): void {
    // Round 24 (skill /100), volet (a) : PublicPollController::vote() ne définit AUCUNE règle de
    // validation pour 'voter_token' et ne lit jamais $request->input('voter_token') /
    // $validated['voter_token'] - $request->validate($rules) ne retourne que les champs déclarés
    // dans $rules, donc un champ de formulaire 'voter_token' envoyé par un client malveillant est
    // purement et simplement ABSENT de $validated, jamais atteint par updateOrCreate(). Preuve
    // réelle : un troisième "votant" (sans cookie du tout) soumet volontairement
    // voter_token=<token brut de la victime> dans le corps de la requête POST, en tentant de se
    // faire passer pour elle sans même passer par un cookie forgé.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'classic', 'vote_mode' => 'single_choice']);
    $optionA = $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);
    $optionB = $poll->options()->create(['label' => 'Option B', 'sort_order' => 1]);

    $cookieName = 'decido_voter_'.$poll->public_id;

    $victimResponse = $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Victime2',
        'votes' => (string) $optionA->id,
    ]);
    $rawVoterToken = $victimResponse->getCookie($cookieName)->getValue();
    $victimVoteId = PollVote::where('voter_token', $rawVoterToken)->where('option_id', $optionA->id)->value('id');

    // Aucun cookie envoyé ici - uniquement le champ de formulaire spoofé.
    $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Attaquant2',
        'voter_token' => $rawVoterToken,
        'votes' => (string) $optionB->id,
    ]);

    $victimVote = PollVote::find($victimVoteId);
    expect($victimVote->voter_pseudonym)->toBe('Victime2');
    expect($victimVote->option_id)->toBe($optionA->id);

    $attackerVoteToken = PollVote::where('option_id', $optionB->id)
        ->where('voter_pseudonym', 'Attaquant2')
        ->value('voter_token');
    expect($attackerVoteToken)->not->toBeNull();
    expect($attackerVoteToken)->not->toBe($rawVoterToken);
});

// ── Round 25 (skill /100) : fuite du jeton admin via la barre de partage réseaux sociaux ───

test('la page de gestion (jeton admin dans l’URL) n’affiche pas la barre de partage Facebook/X/LinkedIn qui embarquerait ce jeton dans un lien sortant', function (): void {
    // Round 25 (skill /100) : angle jamais audité - le Referer HTTP natif du navigateur. Piste
    // initiale : master.blade.php (layout global) déclare bien Referrer-Policy:
    // strict-origin-when-cross-origin (Modules/Core/app/Http/Middleware/SecurityHeaders.php:26),
    // qui borne déjà correctement un Referer cross-origin à la seule origine (schéma+hôte), sans
    // le chemin - donc un clic sortant "ordinaire" ne fuit PAS le jeton via ce mécanisme. Mais
    // l'audit du layout global a révélé un vecteur bien plus direct et bien plus grave : la barre
    // de partage flottante (Modules/FrontTheme/resources/views/layouts/master.blade.php, "Floating
    // share bar") construit ses liens Facebook/X/LinkedIn avec
    // `$shareUrl = urlencode(request()->url())` - l'URL COURANTE COMPLÈTE, jeton admin inclus -
    // puis l'injecte EXPLICITEMENT en paramètre `u=`/`url=` des sharers (ex.
    // https://www.facebook.com/sharer/sharer.php?u=https://laveille.ai/decido/{poll}/gerer/{jeton}).
    // Ce n'est PAS une fuite Referer (que Referrer-Policy bornerait) mais une fuite par PARAMÈTRE
    // DE REQUÊTE explicite, invisible à toute politique Referrer-Policy - le jeton donnant un
    // contrôle total du sondage (clôture, export des pseudonymes des votants, lien court) serait
    // transmis à Facebook/X/LinkedIn (et exploré par leurs robots de prévisualisation OG même sans
    // clic complet de partage) au moindre clic accidentel sur "Partager" par l'organisateur. La
    // liste d'exclusion de la barre (`request()->is('user/*', 'dashboard*', ...)`) ne couvrait pas
    // le pattern `decido/*/gerer*` - contrairement à `admin*`, déjà exclu pour la même raison.
    // Correctif proportionné : ajout de `decido/*/gerer*` à la liste d'exclusion existante (aucune
    // réécriture de la politique Referrer-Policy globale, déjà correcte).
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-partage-fb-x-li']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $manageUrl = route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-partage-fb-x-li']);
    $manageHtml = $this->get($manageUrl)->getContent();

    // Le jeton admin ne doit apparaître dans AUCUN lien de partage sortant vers un tiers. Noter
    // que le jeton apparaît légitimement AILLEURS sur cette page (bandeau "lien d'administration à
    // conserver", boutons d'export/QR/lien court pointant vers les propres routes internes du
    // sondage) - ce n'est PAS une fuite, l'organisateur authentifié par ce jeton doit voir/copier
    // son propre lien. Seule la présence d'un sharer tiers est un problème ; on verrouille donc la
    // solution structurelle : la barre de partage entière est absente de cette page (pas
    // seulement masquée en CSS - le HTML ne doit contenir aucun sharer tiers).
    $this->assertStringNotContainsString('facebook.com/sharer/sharer.php', $manageHtml);
    $this->assertStringNotContainsString('twitter.com/intent/tweet', $manageHtml);
    $this->assertStringNotContainsString('linkedin.com/shareArticle', $manageHtml);

    // Contrôle négatif : prouve que l'exclusion est ciblée (pas une désactivation globale de la
    // barre de partage qui rendrait le test précédent trivial) - la page publique de vote, dont
    // l'URL propre ne porte aucun secret, affiche bien la barre de partage normalement.
    $voteHtml = $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->getContent();
    $this->assertStringContainsString('facebook.com/sharer/sharer.php', $voteHtml);
});

// ── Round 26 (skill /100) : fuite du jeton admin via og:url/canonical/hreflang du layout global ──

test('la page de gestion (jeton admin dans l’URL) n’expose pas ce jeton via og:url, canonical ou hreflang', function (): void {
    // Round 26 (skill /100) : le round 25 avait corrigé UN SEUL vecteur (barre de partage
    // Facebook/X/LinkedIn) parmi plusieurs mécanismes du layout global (master.blade.php) qui
    // embarquent l'URL courante complète. Grep exhaustif de request()->url()/fullUrl()/
    // url()->current() sur tout Modules/FrontTheme/resources/views/ : og:url (ligne 82) et
    // canonical + 2x hreflang (lignes 98-100) utilisaient TOUS url()->current() SANS AUCUNE
    // exclusion - contrairement à la barre de partage, l'exclusion 'decido/*/gerer*' ajoutée au
    // round 25 ne les couvrait pas du tout. Vecteur distinct : pas un clic explicite sur un
    // sharer, mais un "unfurl" AUTOMATIQUE - Slack/Discord/Teams/Messenger/WhatsApp/clients
    // courriel récupèrent og:url dès qu'un lien est collé dans une conversation pour générer un
    // aperçu, et mettent ce contenu en cache sur LEURS serveurs. Le simple fait, pour
    // l'organisateur, de coller son propre lien d'administration dans une messagerie pour se
    // l'envoyer ou le partager avec un co-organisateur suffisait donc à exfiltrer le jeton vers un
    // tiers - sans aucun clic de partage. Le noindex du round 10 (meta robots) ne bloque pas ce
    // mécanisme : les robots d'aperçu Open Graph l'ignorent largement.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-og-canonical-r26']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $manageUrl = route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-og-canonical-r26']);
    $manageHtml = $this->get($manageUrl)->getContent();

    $this->assertStringNotContainsString('og:url" content="' . $manageUrl, $manageHtml);
    $this->assertStringNotContainsString('rel="canonical" href="' . $manageUrl, $manageHtml);
    $this->assertStringNotContainsString('hreflang="fr-CA" href="' . $manageUrl, $manageHtml);
    $this->assertStringNotContainsString('hreflang="x-default" href="' . $manageUrl, $manageHtml);
    // Verrouillage structurel (pas seulement "le jeton n'est pas dans la valeur") : les balises
    // og:url et canonical doivent être ABSENTES du HTML sur cette route, pas juste réécrites avec
    // une valeur différente qui pourrait accidentellement recontenir le jeton plus tard.
    preg_match('/<meta property="og:url"[^>]*>/', $manageHtml, $ogMatch);
    preg_match('/<link rel="canonical"[^>]*>/', $manageHtml, $canonicalMatch);
    expect($ogMatch)->toBe([]);
    expect($canonicalMatch)->toBe([]);

    // Contrôle négatif : la page publique de vote (URL sans secret) conserve bien og:url et
    // canonical normalement - preuve que le correctif est ciblé sur la route à jeton, pas une
    // suppression globale de ces balises qui casserait le SEO/partage de tout le site.
    $voteUrl = route('decido.vote.show', ['slug' => $poll->public_id]);
    $voteHtml = $this->get($voteUrl)->getContent();
    $this->assertStringContainsString('og:url" content="' . $voteUrl, $voteHtml);
    $this->assertStringContainsString('rel="canonical" href="' . $voteUrl, $voteHtml);
});

test('la page de gestion (jeton admin dans l’URL) n’expose pas ce jeton via le JSON-LD BreadcrumbList du fil d’Ariane', function (): void {
    // Round 26 (skill /100) : le partial fronttheme::partials.breadcrumb (inclus par
    // results.blade.php via @section('breadcrumb')) pousse un bloc <script
    // type="application/ld+json"> BreadcrumbList dont les items intermédiaires/finaux utilisent
    // url()->current() (Modules/FrontTheme/resources/views/partials/breadcrumb.blade.php lignes
    // 77 et 84), SANS AUCUNE exclusion pour les routes à jeton. Vérifié RÉEL vs INERTE par requête
    // HTTP : sur la page de gestion Décido, l'include ne passe que 'breadcrumbTitle' (pas
    // 'breadcrumbItems'), donc la condition `@if(!empty($breadcrumbItems))` qui encadre les
    // ListItem à url()->current() est actuellement TOUJOURS fausse sur cette route - le vecteur
    // existe dans le code mais n'est PAS exploitable aujourd'hui. Aucun correctif de code n'est
    // donc justifié (le round 26 n'invente pas de fuite fictive), mais ce test verrouille le
    // constat en dur : si une future modification de results.blade.php se met à passer
    // 'breadcrumbItems' à ce partial, ce test échouera immédiatement et signalera la
    // réintroduction du vecteur.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-breadcrumb-inerte-r26']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $manageUrl = route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-breadcrumb-inerte-r26']);
    $manageHtml = $this->get($manageUrl)->getContent();

    $this->assertStringContainsString('BreadcrumbList', $manageHtml);
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $manageHtml, $ldJsonMatches);
    $breadcrumbBlock = collect($ldJsonMatches[1] ?? [])->first(fn ($block) => str_contains($block, 'BreadcrumbList'));
    expect($breadcrumbBlock)->not->toBeNull();
    $this->assertStringNotContainsString('jeton-breadcrumb-inerte-r26', $breadcrumbBlock);
});

// ── Round 27 (skill /100, revue adversariale) : persistance old() et feedback d'erreur ─────

test('Round 27 (bug 1a) : après un échec de validation (options dupliquées), les 2 valeurs saisies restent visibles dans le formulaire classique (old() relu par x-data)', function (): void {
    // Avant le fix, x-data="{ options: ['', ''], ... }" (create-classic.blade.php) ignorait
    // old() - contrairement à tous les autres champs du même formulaire. La saisie de
    // l'utilisateur disparaissait totalement au réaffichage après une erreur DistinctNormalized.
    config()->set('decido.under_construction', false);

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage options dupliquées round 27',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Test', 'Test'],
    ]);

    $response->assertSessionHasErrors('options.0');
    $response->assertSessionHasInput('options', ['Test', 'Test']);
    expect(Poll::where('title', 'Sondage options dupliquées round 27')->exists())->toBeFalse();

    $createHtml = $this->actingAs($this->superadmin)->get(route('decido.create.classic'))->getContent();

    // json_encode() est interpolé via {{ }} (échappement Blade HTML) - les guillemets du JSON
    // sont donc rendus en entités &quot; dans l'attribut x-data="...", ce qui est le comportement
    // SÉCURITAIRE attendu (le navigateur les décode avant qu'Alpine ne parse le JSON).
    $this->assertStringContainsString('options: [&quot;Test&quot;,&quot;Test&quot;]', $createHtml);
});

test('Round 27 (bug 1b) : après un échec de validation (plages chevauchantes), les dates et plages horaires saisies restent visibles dans le formulaire de dates (old() relu par x-data)', function (): void {
    // Avant le fix, x-data="{ candidateDates: [''], candidateDateRanges: [[]], ... }"
    // (create-date.blade.php) ignorait old() - une date + ses plages personnalisées disparaissaient
    // totalement au réaffichage après une erreur de chevauchement/doublon/DST.
    config()->set('decido.under_construction', false);
    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage dates round 27',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '17:00',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate],
        'candidate_date_ranges' => [
            0 => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '11:00', 'end' => '14:00'],
            ],
        ],
    ]);

    $response->assertSessionHasErrors('candidate_dates');
    expect(Poll::where('title', 'Sondage dates round 27')->exists())->toBeFalse();

    $createHtml = $this->actingAs($this->superadmin)->get(route('decido.create.date'))->getContent();

    // Même logique d'échappement HTML que le bug 1a : guillemets JSON rendus en &quot;.
    $this->assertStringContainsString('candidateDates: [&quot;'.$futureDate.'&quot;]', $createHtml);
    $this->assertStringContainsString('{&quot;start&quot;:&quot;09:00&quot;,&quot;end&quot;:&quot;12:00&quot;}', $createHtml);
    $this->assertStringContainsString('{&quot;start&quot;:&quot;11:00&quot;,&quot;end&quot;:&quot;14:00&quot;}', $createHtml);
});

test('Round 27 (bug 2) : quand des votants ont répondu mais qu\'aucun créneau n\'a de "Oui", un message clair remplace la section vide (au lieu de rien afficher)', function (): void {
    // Avant le fix, $bestOptions pouvait être vide (bestCount === 0) alors que $totalVoters > 0
    // (scénario réaliste : tout le monde répond "Peut-être"/"Non") - la section "Meilleurs
    // créneaux" ne montrait alors RIEN, sans aucun message pour l'organisateur.
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-round27-bestcount0', 'type' => 'date', 'vote_mode' => 'yes_no_maybe']);
    $option = PollOption::factory()->create([
        'poll_id' => $poll->id,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHour(),
    ]);
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'tok-round27',
        'voter_pseudonym' => 'Alice',
        'value' => 'maybe',
    ]);

    $manageHtml = $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-round27-bestcount0']))->getContent();

    $this->assertStringContainsString('1 personne a voté, mais aucun créneau n\'a encore de réponse « Oui »', $manageHtml);
    $this->assertStringNotContainsString('Aucun vote pour l\'instant.', $manageHtml);
});

test('Round 27 (bug 3) : voter sans rien cocher (mode oui/non/peut-être) affiche désormais un message d\'erreur visible sur la page de vote', function (): void {
    // Avant le fix, seules les erreurs @error("votes.{id}") (une par carte d'option) étaient
    // rendues - aucun bloc n'affichait l'erreur portant sur la clé racine 'votes'
    // (required/min:1). Un votant qui soumettait sans rien cocher voyait la page se recharger
    // SANS AUCUN feedback (violation WCAG 3.3.1).
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['type' => 'date', 'vote_mode' => 'yes_no_maybe']);
    PollOption::factory()->create([
        'poll_id' => $poll->id,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHour(),
    ]);

    $response = $this->post(route('decido.vote.store', ['slug' => $poll->public_id]), [
        'voter_pseudonym' => 'Dana',
        // 'votes' intentionnellement omis.
    ]);
    $response->assertSessionHasErrors('votes');

    $voteHtml = $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->getContent();

    $expectedMessage = trans('validation.required', ['attribute' => 'votes']);
    $this->assertStringContainsString('<div class="text-danger mt-2">'.$expectedMessage.'</div>', $voteHtml);
});

// ── Fuseaux horaires complets + adaptation locale (2026-07-18) ─────────────

/**
 * La liste de fuseaux est embarquée en JSON.parse('...') par Illuminate\Support\Js::from()
 * (voir description-timezone-fields.blade.php) : REQUIRED_FLAGS de Js inclut JSON_HEX_QUOT, donc
 * les guillemets deviennent " (pas des guillemets littéraux), et le slash de "America/Montreal"
 * est doublement échappé par le passage JSON.parse (échappement JSON standard + ré-échappement lors
 * du wrapping en chaîne JS). Vérifié empiriquement (php artisan tinker, rendu réel de la vue) avant
 * d'écrire cette extraction plutôt que de deviner le texte brut échappé - une simple recherche de
 * '"id":"' échouerait silencieusement (zéro correspondance) sur le HTML réellement généré.
 */
function decidoExtractTimezonesFromHtml(string $html): array
{
    preg_match("/JSON\.parse\('(.+?)'\)/", $html, $matches);
    if (! isset($matches[1])) {
        return [];
    }

    return json_decode(json_decode('"'.$matches[1].'"'), true) ?? [];
}

test('formulaire de création date contient le combobox de recherche de fuseau et la liste complète IANA', function (): void {
    $response = $this->actingAs($this->superadmin)->get(route('decido.create.date'));
    $content = $response->getContent();

    $this->assertStringContainsString('role="combobox"', $content);
    $this->assertStringContainsString('id="timezone_search"', $content);
    $this->assertStringContainsString('type="hidden" name="timezone"', $content);

    $timezones = decidoExtractTimezonesFromHtml($content);
    $this->assertGreaterThan(400, count($timezones));
});

test('formulaire de création classic contient le combobox de recherche de fuseau et la liste complète IANA', function (): void {
    $response = $this->actingAs($this->superadmin)->get(route('decido.create.classic'));
    $content = $response->getContent();

    $this->assertStringContainsString('role="combobox"', $content);
    $this->assertStringContainsString('id="timezone_search"', $content);
    $this->assertStringContainsString('type="hidden" name="timezone"', $content);

    $timezones = decidoExtractTimezonesFromHtml($content);
    $this->assertGreaterThan(400, count($timezones));
});

test('America/Montreal est bien présent dans les options de fuseau horaire', function (): void {
    $response = $this->actingAs($this->superadmin)->get(route('decido.create.date'));
    $timezones = decidoExtractTimezonesFromHtml($response->getContent());

    $ids = array_column($timezones, 'id');
    $this->assertContains('America/Montreal', $ids);
});

test('le fuseau horaire soumis est préservé après une erreur de validation', function (): void {
    $invalidData = [
        'title' => '', // déclenche une erreur de validation sur 'title', pas sur 'timezone'
        'type' => 'date',
        'vote_mode' => 'yes_no_maybe',
        'timezone' => 'Asia/Tokyo',
    ];

    $this->actingAs($this->superadmin)
        ->from(route('decido.create.date'))
        ->post(route('decido.store'), $invalidData)
        ->assertSessionHasErrors(['title'])
        ->assertSessionHasInput('timezone', 'Asia/Tokyo');
});

test('page de vote date affiche les attributs UTC et charge decidoSlotTimezone', function (): void {
    config()->set('decido.under_construction', false);

    $poll = decidoCreatePoll([
        'type' => 'date',
        'vote_mode' => 'yes_no_maybe',
        'timezone' => 'America/Toronto',
    ]);

    PollOption::factory()->create([
        'poll_id' => $poll->id,
        'starts_at' => now()->addDays(3)->setTime(14, 0)->utc(),
        'ends_at' => now()->addDays(3)->setTime(16, 0)->utc(),
    ]);

    $content = $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->getContent();

    $this->assertStringContainsString('data-starts-at-utc="', $content);
    $this->assertStringContainsString('data-ends-at-utc="', $content);
    $this->assertStringContainsString('function decidoSlotTimezone', $content);
});

test('page de vote classique n\'affiche ni attributs UTC ni decidoSlotTimezone', function (): void {
    config()->set('decido.under_construction', false);

    $poll = decidoCreatePoll([
        'type' => 'classic',
        'vote_mode' => 'single_choice',
        'timezone' => 'America/Toronto',
    ]);

    PollOption::factory()->create(['poll_id' => $poll->id]);

    $content = $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->getContent();

    $this->assertStringNotContainsString('data-starts-at-utc="', $content);
    $this->assertStringNotContainsString('data-ends-at-utc="', $content);
    $this->assertStringNotContainsString('function decidoSlotTimezone', $content);
});

// ── Politique de rétention complète (2026-07-19, recherche pp_search + validation Codex/Gemini,
//    approuvée par l'utilisateur) : expires_at calculé dès la création (plus seulement à la
//    clôture), avertissement courriel unique J-14, prolongation plafonnée, purge élargie. ──────

test('la création d\'un sondage de dates fixe expires_at à la dernière date candidate + 2 mois', function (): void {
    config()->set('decido.under_construction', false);
    $futureDate1 = now()->addDays(10)->format('Y-m-d');
    $futureDate2 = now()->addDays(20)->format('Y-m-d');

    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage rétention date',
        'type' => 'date',
        'timezone' => 'America/Toronto',
        'duration_minutes' => 30,
        'range_start_time' => '09:00',
        'range_end_time' => '10:00',
        'step_minutes' => 30,
        'candidate_dates' => [$futureDate1, $futureDate2],
    ]);
    $response->assertSessionDoesntHaveErrors();

    $poll = Poll::where('title', 'Sondage rétention date')->firstOrFail();
    expect($poll->expires_at)->not->toBeNull();

    $lastSlotEndsAt = $poll->options()->max('ends_at');
    $expected = \Carbon\Carbon::parse($lastSlotEndsAt)->addMonths(2);

    expect($poll->expires_at->equalTo($expected))->toBeTrue();
});

test('la création d\'un sondage classique fixe expires_at à la date de création + 3 mois', function (): void {
    $response = $this->actingAs($this->superadmin)->post(route('decido.store'), [
        'title' => 'Sondage rétention classique',
        'type' => 'classic',
        'timezone' => 'America/Toronto',
        'vote_mode' => 'single_choice',
        'options' => ['Pizza', 'Sushi'],
    ]);
    $response->assertSessionDoesntHaveErrors();

    $poll = Poll::where('title', 'Sondage rétention classique')->firstOrFail();
    expect($poll->expires_at)->not->toBeNull();
    expect($poll->created_at->addMonths(3)->diffInSeconds($poll->expires_at))->toBeLessThan(2);
});

test('clôturer un sondage fixe désormais expires_at à clôture + 30 jours (remplace l\'ancienne règle de 6 mois)', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-close-30j']);
    $option = $poll->options()->create(['label' => 'Option A', 'sort_order' => 0]);

    $this->post(route('decido.close', ['poll' => $poll->public_id, 'adminToken' => 'jeton-close-30j']), [
        'final_option_id' => $option->id,
    ]);

    $poll->refresh();
    expect($poll->status->value)->toBe('closed');
    expect($poll->expires_at->between(now()->addDays(29), now()->addDays(31)))->toBeTrue();
    // Contrôle négatif explicite : ne doit plus jamais retomber sur l'ancienne règle des 6 mois.
    expect($poll->expires_at->lessThan(now()->addMonths(2)))->toBeTrue();
});

test('"Prolonger de 3 mois" ajoute 3 mois à expires_at, incrémente extension_count et réinitialise expiry_warned_at', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-extend-1']);
    $originalExpiresAt = now()->addDays(10);
    $poll->expires_at = $originalExpiresAt;
    $poll->expiry_warned_at = now()->subDay();
    $poll->save();

    $response = $this->post(route('decido.extend', ['poll' => $poll->public_id, 'adminToken' => 'jeton-extend-1']));
    $response->assertRedirect(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-extend-1']));
    $response->assertSessionHasNoErrors();

    $poll->refresh();
    expect($poll->extension_count)->toBe(1);
    expect($poll->expiry_warned_at)->toBeNull();
    // diffInSeconds (tolérance) plutôt qu'equalTo() : le round-trip DB tronque les microsecondes
    // de la colonne datetime, une comparaison stricte échouerait sur la précision sub-seconde.
    expect($poll->expires_at->diffInSeconds($originalExpiresAt->copy()->addMonths(3)))->toBeLessThan(2);
});

test('le plafond de 2 prolongations est appliqué : la 3e tentative est refusée avec un message clair', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'jeton-extend-max']);
    $poll->expires_at = now()->addDays(10);
    $poll->extension_count = 2; // plafond déjà atteint (decido.max_extensions par défaut = 2)
    $poll->save();

    $expiresAtBefore = $poll->expires_at;

    $response = $this->post(route('decido.extend', ['poll' => $poll->public_id, 'adminToken' => 'jeton-extend-max']));
    $response->assertSessionHasErrors('extend');

    $poll->refresh();
    expect($poll->extension_count)->toBe(2);
    expect($poll->expires_at->equalTo($expiresAtBefore))->toBeTrue();
});

test('prolonger un sondage avec un jeton invalide et sans être le créateur connecté retourne 403', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['admin_token' => 'le-vrai-jeton-extend']);
    $poll->expires_at = now()->addDays(10);
    $poll->save();

    $this->post(route('decido.extend', ['poll' => $poll->public_id, 'adminToken' => 'mauvais-jeton']))
        ->assertForbidden();

    $poll->refresh();
    expect($poll->extension_count)->toBe(0);
});

test('decido:warn-expiring-polls envoie UN SEUL courriel à J-14 et ne le renvoie pas au second appel (idempotence)', function (): void {
    config()->set('decido.under_construction', false);
    Mail::fake();

    $creator = User::factory()->create(['email' => 'createur-warn@example.test']);
    $poll = decidoCreatePoll(['creator_id' => $creator->id, 'admin_token' => 'jeton-warn-1']);
    $poll->expires_at = now()->addDays(7); // dans la fenêtre J-14
    $poll->save();

    $hors_fenetre = decidoCreatePoll(['creator_id' => $creator->id, 'admin_token' => 'jeton-warn-2']);
    $hors_fenetre->expires_at = now()->addDays(30); // hors fenêtre J-14
    $hors_fenetre->save();

    $this->artisan('decido:warn-expiring-polls')->assertExitCode(0);

    Mail::assertSent(PollExpiringSoonMail::class, 1);
    $poll->refresh();
    expect($poll->expiry_warned_at)->not->toBeNull();

    $hors_fenetre->refresh();
    expect($hors_fenetre->expiry_warned_at)->toBeNull();

    // Second appel (rejeu quotidien du cron) : ne doit RIEN renvoyer pour ce même sondage,
    // expiry_warned_at étant désormais non-NULL.
    $this->artisan('decido:warn-expiring-polls')->assertExitCode(0);
    Mail::assertSent(PollExpiringSoonMail::class, 1);
});

test('decido:warn-expiring-polls ignore silencieusement un sondage sans créateur (compte supprimé) sans erreur ni envoi', function (): void {
    config()->set('decido.under_construction', false);
    Mail::fake();

    $poll = decidoCreatePoll(['admin_token' => 'jeton-warn-orphelin']);
    $poll->expires_at = now()->addDays(5);
    $poll->creator_id = null;
    $poll->save();

    $this->artisan('decido:warn-expiring-polls')->assertExitCode(0);

    Mail::assertNothingSent();
    $poll->refresh();
    expect($poll->expiry_warned_at)->toBeNull();

    // Le sondage orphelin (sans avertissement possible) continue néanmoins son cycle de purge
    // normal - expires_at n'a jamais dépendu de expiry_warned_at.
    $poll->expires_at = now()->subDay();
    $poll->save();
    $this->artisan('decido:purge-expired')->assertExitCode(0);
    expect(Poll::find($poll->id))->toBeNull();
});

test('une prolongation remet expiry_warned_at à NULL, permettant un futur avertissement sur la nouvelle échéance', function (): void {
    config()->set('decido.under_construction', false);
    Mail::fake();

    $creator = User::factory()->create(['email' => 'createur-reavertissement@example.test']);
    $poll = decidoCreatePoll(['creator_id' => $creator->id, 'admin_token' => 'jeton-reavertir']);
    $poll->expires_at = now()->addDays(7);
    $poll->expiry_warned_at = now()->subDay(); // déjà averti pour l'échéance actuelle
    $poll->save();

    // Sans prolongation, un second passage du cron ne renverrait rien (idempotence).
    $this->artisan('decido:warn-expiring-polls');
    Mail::assertNothingSent();

    $this->post(route('decido.extend', ['poll' => $poll->public_id, 'adminToken' => 'jeton-reavertir']));
    $poll->refresh();
    expect($poll->expiry_warned_at)->toBeNull();
    // La nouvelle échéance (+3 mois) est hors fenêtre J-14 : pas d'avertissement immédiat, mais
    // le fait que expiry_warned_at soit redevenu NULL prouve qu'un futur avertissement pourra
    // être émis quand cette nouvelle échéance entrera à son tour dans la fenêtre.
});

test('le bouton "Prolonger de 3 mois" est visible pour le créateur connecté avec des prolongations restantes, absent au plafond', function (): void {
    config()->set('decido.under_construction', false);
    $poll = decidoCreatePoll(['creator_id' => $this->superadmin->id, 'admin_token' => 'jeton-bouton-extend']);
    $poll->expires_at = now()->addDays(10);
    $poll->save();
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $html = $this->actingAs($this->superadmin)
        ->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-bouton-extend']))
        ->getContent();
    $this->assertStringContainsString('Prolonger de 3 mois', $html);

    $poll->extension_count = 2; // plafond atteint
    $poll->save();

    $htmlAuPlafond = $this->actingAs($this->superadmin)
        ->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-bouton-extend']))
        ->getContent();
    $this->assertStringNotContainsString('Prolonger de 3 mois', $htmlAuPlafond);
});

test('le rétro-remplissage de la migration calcule expires_at pour les sondages existants sans en avoir', function (): void {
    // Simule l'état "avant" (sondages créés sous l'ancienne règle, expires_at NULL tant que non
    // clôturés) puis rejoue le backfill de la migration
    // 2026_07_19_120000_add_retention_fields_to_decido_polls directement (les colonnes existent
    // déjà dans cet environnement de test via RefreshDatabase - les garde-fous hasColumn() du
    // up() rendent la partie schéma idempotente, seule la partie backfill s'exécute réellement).
    $classicLegacy = decidoCreatePoll(['type' => 'classic', 'status' => 'open']);
    $classicLegacy->expires_at = null;
    $classicLegacy->save();

    $dateLegacy = decidoCreatePoll(['type' => 'date', 'status' => 'open']);
    $dateLegacy->expires_at = null;
    $dateLegacy->save();
    $lastEndsAt = now()->addDays(15);
    PollOption::factory()->create(['poll_id' => $dateLegacy->id, 'starts_at' => now()->addDays(15)->subHour(), 'ends_at' => $lastEndsAt]);
    PollOption::factory()->create(['poll_id' => $dateLegacy->id, 'starts_at' => now()->addDays(5)->subHour(), 'ends_at' => now()->addDays(5)]);

    $closedLegacy = decidoCreatePoll(['type' => 'classic', 'status' => 'closed']);
    $closedLegacy->expires_at = now()->addMonths(6); // ancienne règle déjà fixée : ne doit PAS être touché
    $closedLegacy->save();
    $closedExpiresAtBefore = $closedLegacy->expires_at;

    $migration = require base_path('Modules/Decido/database/migrations/2026_07_19_120000_add_retention_fields_to_decido_polls.php');
    $migration->up();

    $classicLegacy->refresh();
    expect($classicLegacy->expires_at)->not->toBeNull();
    expect($classicLegacy->created_at->addMonths(3)->diffInSeconds($classicLegacy->expires_at))->toBeLessThan(2);

    $dateLegacy->refresh();
    expect($dateLegacy->expires_at)->not->toBeNull();
    // diffInSeconds (tolérance) : $lastEndsAt est une valeur EN MÉMOIRE (microsecondes incluses)
    // comparée à une valeur relue depuis la DB (colonne datetime, tronquée à la seconde).
    expect($dateLegacy->expires_at->diffInSeconds(\Carbon\Carbon::parse($lastEndsAt)->addMonths(2)))->toBeLessThan(2);

    $closedLegacy->refresh();
    expect($closedLegacy->expires_at->diffInSeconds($closedExpiresAtBefore))->toBeLessThan(2);
});
