<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * xAPI léger (couche d'abstraction actor-verb-object, standard 1EdTech/ADL) —
 * répond à la dette technique F16 (voir config/version.php, notée 2 fois).
 *
 * PRINCIPE : cette couche ne remplace ni ne duplique AUCUNE table métier
 * existante (academy_completions, academy_quiz_attempts, academy_xp_events,
 * academy_srs_cards, academy_live_session_attendance...). Elle REFORMATE
 * chaque événement pédagogique déjà persisté ailleurs dans un format standard
 * actor(user_id)-verb-object(type+id), pour servir de fondation future à un
 * tuteur IA ou un graphe de compétences (lecture uniquement pour l'instant).
 *
 * Branchée en TOUTE FIN des points d'émission existants (CompletionService,
 * QuizController, GamificationService, SrsService, CourseLiveSessions),
 * TOUJOURS APRÈS la ligne de persistance métier réelle — jamais avant, jamais
 * à la place. Suit exactement le même filet de sécurité que ces services :
 * try/catch (\Throwable) silencieux, jamais d'exception remontée à l'appelant.
 *
 * Drapeau maître — voir config('academy.xapi_enabled'), défaut false :
 * tant qu'il est désactivé, record() est un NO-OP total (aucune requête SQL,
 * aucun coût). Activer via ACADEMY_XAPI_ENABLED=true dans le .env.
 *
 * VOCABULAIRE CONTRÔLÉ (verb) — n'ajouter un nouveau verbe qu'ici, jamais une
 * chaîne libre au point d'appel :
 *   - completed : leçon/item ou cours complété (object_type = lesson|course)
 *   - attempted : tentative de quiz soumise (object_type = quiz)
 *   - earned    : XP crédité (object_type = xp_event)
 *   - reviewed  : carte SRS révisée (object_type = srs_card)
 *   - attended  : présence à une séance en direct (object_type = live_session)
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Academy\Models\XapiStatement;

final class XapiRecorderService
{
    public const VERB_COMPLETED = 'completed';

    public const VERB_ATTEMPTED = 'attempted';

    public const VERB_EARNED = 'earned';

    public const VERB_REVIEWED = 'reviewed';

    public const VERB_ATTENDED = 'attended';

    public const OBJECT_LESSON = 'lesson';

    public const OBJECT_COURSE = 'course';

    public const OBJECT_QUIZ = 'quiz';

    public const OBJECT_XP_EVENT = 'xp_event';

    public const OBJECT_SRS_CARD = 'srs_card';

    public const OBJECT_LIVE_SESSION = 'live_session';

    /** Drapeau maître — voir config('academy.xapi_enabled'), défaut false. */
    public function isEnabled(): bool
    {
        return (bool) config('academy.xapi_enabled', false);
    }

    /**
     * Enregistre un statement xAPI (actor-verb-object). NO-OP silencieux si le
     * drapeau est désactivé, ou si l'écriture échoue pour quelque raison que ce
     * soit (table absente, etc.) : ne casse JAMAIS l'action pédagogique appelante.
     *
     * IDEMPOTENCE APPLICATIVE ($onceOnly, défaut true) : certains points
     * d'émission (ex. complétion de cours réévaluée à chaque recalcul de
     * progression, ou XP crédité) peuvent rappeler record() plusieurs fois
     * pour le MÊME (actor, verb, object) — on ne réécrit alors jamais de
     * doublon exact. Passer $onceOnly=false pour les événements RÉPÉTABLES
     * par nature où chaque occurrence est une action pédagogique distincte
     * (ex. une tentative de quiz, une révision SRS successive de la même
     * carte) : chaque appel crée alors bien une nouvelle ligne.
     *
     * @param array<string, mixed>|null $result  Score, succès/échec, durée...
     * @param array<string, mixed>|null $context Course_id parent, cohort_id...
     */
    public function record(
        User $actor,
        string $verb,
        string $objectType,
        int $objectId,
        ?array $result = null,
        ?array $context = null,
        ?Carbon $occurredAt = null,
        bool $onceOnly = true,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            if ($onceOnly) {
                $alreadyRecorded = XapiStatement::query()
                    ->where('user_id', $actor->id)
                    ->where('verb', $verb)
                    ->where('object_type', $objectType)
                    ->where('object_id', $objectId)
                    ->exists();

                if ($alreadyRecorded) {
                    return;
                }
            }

            XapiStatement::create([
                'user_id'     => $actor->id,
                'verb'        => $verb,
                'object_type' => $objectType,
                'object_id'   => $objectId,
                'result'      => $result,
                'context'     => $context,
                'raw_payload' => [
                    'actor'       => ['user_id' => $actor->id],
                    'verb'        => $verb,
                    'object'      => ['type' => $objectType, 'id' => $objectId],
                    'result'      => $result,
                    'context'     => $context,
                    'occurred_at' => ($occurredAt ?? now())->toIso8601String(),
                ],
                'occurred_at' => $occurredAt ?? now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('[XapiRecorderService] Enregistrement statement échoué', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Fondation de lecture (futur tuteur IA / graphe de compétences) : requête
     * de base des statements d'un utilisateur, la plus récente en tête.
     * Anti-IDOR : toujours scopée à l'utilisateur passé explicitement.
     */
    public function statementsFor(User $user): Builder
    {
        return XapiStatement::query()
            ->forUser($user->id)
            ->orderByDesc('occurred_at');
    }
}
