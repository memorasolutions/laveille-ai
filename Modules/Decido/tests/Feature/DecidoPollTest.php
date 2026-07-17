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
