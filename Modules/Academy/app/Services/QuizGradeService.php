<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-c - MÉTHODE DE NOTATION sur les tentatives (parité Moodle). SOURCE UNIQUE (DRY)
 * du calcul de la NOTE EFFECTIVE d'un item quiz à partir de l'historique réel des
 * tentatives (QuizAttempt, V1-b). Lu côté SERVEUR uniquement (jamais exposé tel quel
 * à l'étudiant ; le gradebook qui l'appelle est gâté manageEnrollments).
 *
 * Méthodes supportées (liste blanche, défaut Moodle = 'highest') :
 *   - highest : meilleure note (max percent) ;
 *   - average : moyenne des percent ;
 *   - first   : 1re tentative (par submitted_at) ;
 *   - last    : dernière tentative (par submitted_at).
 *
 * La méthode est portée par payload['grading_method'] de l'item (comme passing_score,
 * attempts_allowed, qt_bank_key). Aucune nouvelle table.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;

final class QuizGradeService
{
    /** Méthodes de notation autorisées (liste blanche). Défaut = 'highest' (Moodle). */
    public const METHODS = ['highest', 'average', 'first', 'last'];

    public const DEFAULT_METHOD = 'highest';

    /**
     * Note EFFECTIVE d'un item quiz pour un utilisateur, selon la méthode de l'item.
     *
     * ESSAI : la note effective ne tient compte que des tentatives FINALISÉES
     * (needs_grading=false : auto-notées OU correction terminée). Une tentative EN
     * ATTENTE de correction n'est PAS comptée comme 0 ni 100 (son percent auto est
     * provisoire) : on la signale via `pending`. Quand un quiz ne contient pas d'essai,
     * toutes les tentatives sont finalisées → comportement strictement INCHANGÉ.
     *
     * @return array{percent: int, attempts: int, method: string, pending: bool}
     *               percent=0 et attempts=0 si aucune tentative ;
     *               pending=true si des tentatives existent mais AUCUNE n'est finalisée.
     */
    public static function effectiveGrade(int $userId, LessonItem $item): array
    {
        $method = self::methodFor($item);

        $attempts = QuizAttempt::query()
            ->forUser($userId)
            ->forItem($item->id)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get(['percent', 'submitted_at', 'needs_grading']);

        $count = $attempts->count();

        if ($count === 0) {
            return ['percent' => 0, 'attempts' => 0, 'method' => $method, 'pending' => false];
        }

        // ESSAI : on ne note que les tentatives finalisées (note définitive).
        $finalized = $attempts->filter(static fn ($a): bool => ! (bool) $a->needs_grading);

        if ($finalized->isEmpty()) {
            // Toutes les tentatives sont en attente de correction → « à corriger »
            // (ni 0 ni 100 : on remonte pending pour que l'appelant le distingue).
            return ['percent' => 0, 'attempts' => $count, 'method' => $method, 'pending' => true];
        }

        $percents = $finalized->map(static fn ($a): int => (int) $a->percent);

        $percent = match ($method) {
            'average' => (int) round($percents->avg()),
            'first'   => (int) $percents->first(),
            'last'    => (int) $percents->last(),
            // 'highest' (et tout repli défensif).
            default   => (int) $percents->max(),
        };

        return ['percent' => $percent, 'attempts' => $count, 'method' => $method, 'pending' => false];
    }

    /**
     * Méthode de notation déclarée sur l'item (liste blanche), défaut 'highest'.
     * Une valeur absente/inconnue retombe sur le défaut Moodle (jamais d'erreur).
     */
    public static function methodFor(LessonItem $item): string
    {
        $raw = is_array($item->payload ?? null)
            ? ($item->payload['grading_method'] ?? null)
            : null;

        return in_array($raw, self::METHODS, true) ? (string) $raw : self::DEFAULT_METHOD;
    }
}
