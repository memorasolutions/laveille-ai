<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest — LOT 5 (docs/specs/2026-08-16-decido-reste-a-faire.md) : notification à
 * l'organisateur quand son sondage reçoit de l'activité. Couvre le regroupement quotidien
 * (le piège central de ce lot), l'idempotence (aucun courriel en double pour la même activité),
 * l'interrupteur par sondage, et la garde "rien de nouveau = silence".
 *
 * NON EXÉCUTÉS par ce sous-agent (consigne CONTRAINTES-SOUS-AGENTS.md, section 2) - à exécuter
 * par le superviseur, une seule suite à la fois.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Decido\Mail\PollActivityDigestMail;
use Modules\Decido\Models\Poll;
use Modules\Decido\Models\PollComment;
use Modules\Decido\Models\PollDecline;
use Modules\Decido\Models\PollOption;
use Modules\Decido\Models\PollVote;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function activityCreatePoll(array $overrides = []): Poll
{
    $poll = new Poll;
    $poll->title = $overrides['title'] ?? 'Sondage de test - activité';
    $poll->type = $overrides['type'] ?? 'classic';
    $poll->vote_mode = $overrides['vote_mode'] ?? 'single_choice';
    $poll->timezone = $overrides['timezone'] ?? 'America/Toronto';
    $poll->status = $overrides['status'] ?? 'open';
    $poll->creator_id = array_key_exists('creator_id', $overrides)
        ? $overrides['creator_id']
        : User::factory()->create(['email' => 'organisateur@exemple.test'])->id;
    $poll->admin_token_hash = hash('sha256', $overrides['admin_token'] ?? 'plain-admin-token');

    if (array_key_exists('activity_notifications_enabled', $overrides)) {
        $poll->activity_notifications_enabled = $overrides['activity_notifications_enabled'];
    }

    $poll->save();

    return $poll;
}

// ── Le regroupement (piège central du lot) ──────────────────────────────────

test('un vote nouveau déclenche un résumé au passage de la commande, avec le bon décompte', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    // Poll créé, puis vote une seconde plus tard (garantit updated_at strictement postérieur au
    // curseur activity_notified_at ?? created_at utilisé par Poll::newActivitySince() - une
    // égalité de timestamp à la seconde près ferait manquer le vote, voir le commentaire de
    // NotifyPollActivityCommand).
    $this->travel(1)->seconds();
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'voter-alice',
        'voter_pseudonym' => 'Alice',
        'value' => 'selected',
    ]);

    $this->artisan('decido:notify-poll-activity')->assertExitCode(0);

    Mail::assertSent(PollActivityDigestMail::class, function ($mail) use ($poll) {
        return $mail->poll->is($poll)
            && $mail->newVoters === 1
            && $mail->newDeclines === 0
            && $mail->newComments === 0
            && $mail->hasTo('organisateur@exemple.test');
    });

    expect($poll->fresh()->activity_notified_at)->not->toBeNull();
});

test('LE PIÈGE : dix votants qui votent une fois chacun ne génèrent qu\'UN SEUL courriel groupé, pas dix', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    for ($i = 1; $i <= 10; $i++) {
        PollVote::create([
            'poll_id' => $poll->id,
            'option_id' => $option->id,
            'voter_token' => "voter-{$i}",
            'voter_pseudonym' => "Votant {$i}",
            'value' => 'selected',
        ]);
    }

    $this->artisan('decido:notify-poll-activity');

    Mail::assertSentCount(1);
    Mail::assertSent(PollActivityDigestMail::class, fn ($mail) => $mail->newVoters === 10);
});

test('LE PIÈGE : un même votant qui modifie sa réponse trois fois AVANT le passage de la commande ne compte qu\'une fois, pas treize fois', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();

    // 3 modifications successives du même votant (updateOrCreate, comme le fait réellement
    // PublicPollController::vote() - voir son commentaire "round 6, skill /100").
    foreach (['selected', 'selected', 'selected'] as $i => $value) {
        PollVote::updateOrCreate(
            ['option_id' => $option->id, 'voter_token' => 'voter-bob'],
            ['poll_id' => $poll->id, 'voter_pseudonym' => 'Bob', 'value' => $value]
        );
        $this->travel(1)->seconds();
    }

    $this->artisan('decido:notify-poll-activity');

    // Un seul courriel, et le compte de VOTANTS distincts (pas de lignes de vote) reste à 1.
    Mail::assertSentCount(1);
    Mail::assertSent(PollActivityDigestMail::class, fn ($mail) => $mail->newVoters === 1);
});

test('un second passage de la commande sans activité nouvelle ne renvoie AUCUN courriel (idempotence)', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'voter-alice',
        'voter_pseudonym' => 'Alice',
        'value' => 'selected',
    ]);

    $this->artisan('decido:notify-poll-activity');
    Mail::assertSentCount(1);

    // Passage suivant (ex. le lendemain), sans aucune nouvelle activité depuis le dernier envoi -
    // le silence est le comportement voulu, pas une erreur.
    $this->travel(1)->day();
    $this->artisan('decido:notify-poll-activity');

    Mail::assertSentCount(1);
});

test('une nouvelle activité APRÈS un premier résumé génère un second résumé, distinct du premier (le regroupement continue de fonctionner)', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'voter-alice',
        'voter_pseudonym' => 'Alice',
        'value' => 'selected',
    ]);
    $this->artisan('decido:notify-poll-activity');
    Mail::assertSentCount(1);

    $this->travel(1)->day();
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'voter-charlie',
        'voter_pseudonym' => 'Charlie',
        'value' => 'selected',
    ]);
    $this->artisan('decido:notify-poll-activity');

    Mail::assertSentCount(2);
    Mail::assertSent(PollActivityDigestMail::class, fn ($mail) => $mail->newVoters === 1 && $mail->poll->id === $poll->id);
});

// ── Silence quand il n'y a rien de neuf ──────────────────────────────────────

test('un sondage sans aucun vote/déclin/commentaire ne génère aucun courriel', function (): void {
    Mail::fake();

    activityCreatePoll();

    $this->artisan('decido:notify-poll-activity')->assertExitCode(0);

    Mail::assertNothingSent();
});

// ── Interrupteur par sondage ──────────────────────────────────────────────────

test('interrupteur désactivé : aucun courriel n\'est envoyé même avec de l\'activité nouvelle', function (): void {
    Mail::fake();

    $poll = activityCreatePoll(['activity_notifications_enabled' => false]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'voter-alice',
        'voter_pseudonym' => 'Alice',
        'value' => 'selected',
    ]);

    $this->artisan('decido:notify-poll-activity');

    Mail::assertNothingSent();
    // L'activité n'a pas été "consommée" par un envoi qui n'a jamais eu lieu : le curseur reste
    // intact, prêt à couvrir cette même activité si l'organisateur réactive plus tard.
    expect($poll->fresh()->activity_notified_at)->toBeNull();
});

test('le formulaire de la page de gestion bascule l\'interrupteur (activé -> désactivé -> activé)', function (): void {
    $poll = activityCreatePoll(['admin_token' => 'jeton-toggle']);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    // fresh() obligatoire : le défaut « true » vient de la base (default(true) de la migration),
    // et une instance créée en mémoire ne reflète pas les défauts de colonnes avant rechargement.
    expect($poll->fresh()->activity_notifications_enabled)->toBeTrue();

    $this->post(route('decido.notifications', ['poll' => $poll->public_id, 'adminToken' => 'jeton-toggle']), [
        'activity_notifications_enabled' => '0',
    ])->assertRedirect(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-toggle']));

    expect($poll->fresh()->activity_notifications_enabled)->toBeFalse();

    $this->post(route('decido.notifications', ['poll' => $poll->public_id, 'adminToken' => 'jeton-toggle']), [
        'activity_notifications_enabled' => '1',
    ]);

    expect($poll->fresh()->activity_notifications_enabled)->toBeTrue();
});

test('basculer l\'interrupteur avec un mauvais jeton admin retourne 403', function (): void {
    $poll = activityCreatePoll(['admin_token' => 'le-vrai-jeton']);

    $this->post(route('decido.notifications', ['poll' => $poll->public_id, 'adminToken' => 'mauvais-jeton']), [
        'activity_notifications_enabled' => '0',
    ])->assertForbidden();

    expect($poll->fresh()->activity_notifications_enabled)->toBeTrue();
});

// ── Déclins et commentaires comptent aussi (pas seulement les votes) ────────

test('un déclin ("aucune date ne me convient") est compté et déclenche un résumé', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    PollDecline::create([
        'poll_id' => $poll->id,
        'voter_token' => 'voter-diane',
        'voter_pseudonym' => 'Diane',
    ]);

    $this->artisan('decido:notify-poll-activity');

    Mail::assertSent(PollActivityDigestMail::class, fn ($mail) => $mail->newVoters === 0 && $mail->newDeclines === 1);
});

test('un commentaire seul (sans vote) déclenche aussi un résumé', function (): void {
    Mail::fake();

    $poll = activityCreatePoll();
    PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    PollComment::create([
        'poll_id' => $poll->id,
        'voter_token' => 'voter-eve',
        'voter_pseudonym' => 'Eve',
        'comment' => 'Je peux seulement après 18h.',
    ]);

    $this->artisan('decido:notify-poll-activity');

    Mail::assertSent(PollActivityDigestMail::class, fn ($mail) => $mail->newComments === 1);
});

// ── Créateur orphelin (compte supprimé) ──────────────────────────────────────

test('un sondage sans créateur (creator_id NULL) est ignoré silencieusement, sans erreur', function (): void {
    Mail::fake();

    $poll = activityCreatePoll(['creator_id' => null]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->travel(1)->seconds();
    PollVote::create([
        'poll_id' => $poll->id,
        'option_id' => $option->id,
        'voter_token' => 'voter-alice',
        'voter_pseudonym' => 'Alice',
        'value' => 'selected',
    ]);

    $this->artisan('decido:notify-poll-activity')->assertExitCode(0);

    Mail::assertNothingSent();
});
