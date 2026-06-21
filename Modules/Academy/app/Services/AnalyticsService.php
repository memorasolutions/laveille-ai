<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * D1 — Analytics actionnables PAR COURS (Phase D « pilotage »).
 *
 * Une SEULE source de vérité pour le calcul des métriques d'un cours. Toutes les
 * requêtes sont SCOPÉES à un `Course` donné (where course_id = $course->id) :
 * aucune agrégation cross-cours, aucune fuite (anti-IDOR au niveau données).
 *
 * Réutilise (DRY) les mécanismes de progression/complétion existants :
 *  - Progress.percent  : progression persistée par ProgressService::recalculate().
 *  - Completion        : items complétés (status='completed'), source du décrochage.
 *  - Enrollment.status : inscriptions actives, source des compteurs « inscrits ».
 *  - CertificateIssued : certificats émis (M6).
 *
 * Le contrôle d'accès (authorize) reste la responsabilité de l'appelant
 * (CourseAnalytics::mount) ; ce service ne calcule QUE sur un cours déjà autorisé.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;

final class AnalyticsService
{
    /**
     * Seuils de détection « apprenant à risque » (D2). Centralisés ici pour être
     * faciles à ajuster sans toucher à la logique.
     *
     * - NEVER_STARTED_DAYS : inscrit depuis plus de N jours sans aucune progression.
     * - STUCK_DAYS         : a commencé mais aucune activité depuis plus de N jours
     *                        (et moins que INACTIVE_DAYS) → « bloqué ».
     * - INACTIVE_DAYS      : aucune activité depuis plus de N jours → « inactif ».
     */
    public const NEVER_STARTED_DAYS = 7;

    public const STUCK_DAYS = 7;

    public const INACTIVE_DAYS = 14;

    /**
     * KPIs d'inscription : total actifs + nouveaux sur 7 / 30 jours.
     *
     * @return array{total:int, last7:int, last30:int}
     */
    public function enrollmentKpis(Course $course): array
    {
        $base = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active');

        return [
            'total' => (clone $base)->count(),
            'last7' => (clone $base)->where('enrolled_at', '>=', now()->subDays(7))->count(),
            'last30' => (clone $base)->where('enrolled_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * KPIs de complétion, calculés SUR LES INSCRITS ACTIFS de CE cours :
     *  - completed  : nb d'inscrits actifs à 100 % (Progress.percent >= 100) ;
     *  - rate       : % d'inscrits actifs ayant terminé (0 si aucun inscrit) ;
     *  - avgPercent : progression MOYENNE des inscrits actifs (0 si aucun inscrit).
     *
     * On part des inscriptions actives (et non de la table progresses) pour qu'un
     * inscrit n'ayant aucune progression enregistrée compte bien comme 0 %.
     *
     * @return array{enrolled:int, completed:int, rate:int, avgPercent:int}
     */
    public function completionKpis(Course $course): array
    {
        $activeUserIds = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique()
            ->all();

        $enrolled = count($activeUserIds);

        if ($enrolled === 0) {
            return ['enrolled' => 0, 'completed' => 0, 'rate' => 0, 'avgPercent' => 0];
        }

        // Pourcentages persistés, scopés à CE cours et à SES inscrits actifs.
        // Indexés par user_id ; un inscrit sans ligne progresses = 0 %.
        $percentByUser = Progress::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $activeUserIds)
            ->pluck('percent', 'user_id');

        $sum = 0;
        $completed = 0;

        foreach ($activeUserIds as $userId) {
            $percent = (int) ($percentByUser[$userId] ?? 0);
            $sum += $percent;

            if ($percent >= 100) {
                $completed++;
            }
        }

        return [
            'enrolled' => $enrolled,
            'completed' => $completed,
            'rate' => (int) round(($completed / $enrolled) * 100),
            'avgPercent' => (int) round($sum / $enrolled),
        ];
    }

    /**
     * Décrochage PAR LEÇON : pour chaque leçon contenant au moins un item REQUIS,
     * combien d'inscrits actifs ont complété TOUS ses items requis vs pas.
     *
     * Une leçon est « complétée » par un inscrit lorsqu'il a complété chacun de ses
     * items requis (cohérent avec la définition de progression de ProgressService,
     * qui compte les items requis). Le « point de décrochage » est la première leçon
     * (dans l'ordre chapitre→leçon) au plus faible taux de complétion.
     *
     * Anti-IDOR : tous les items/leçons sont scopés via leur cours parent ; seuls les
     * inscrits ACTIFS de CE cours sont comptés.
     *
     * @return array<int, array{
     *   lesson_id:int, title:string, chapter_title:string,
     *   completed:int, total:int, rate:int
     * }>
     */
    public function lessonDropoff(Course $course): array
    {
        $activeUserIds = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique()
            ->all();

        $enrolled = count($activeUserIds);

        // Structure ordonnée chapitre → leçon → items requis, scopée à CE cours.
        $course->loadMissing([
            'chapters' => fn ($q) => $q->orderBy('position')->orderBy('id'),
            'chapters.lessons' => fn ($q) => $q->orderBy('position')->orderBy('id'),
            'chapters.lessons.lessonItems' => fn ($q) => $q->where('is_required', true)->orderBy('position'),
        ]);

        // IDs de tous les items requis du cours (pour une SEULE requête de complétions).
        $requiredItemIds = [];
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $item) {
                    $requiredItemIds[] = $item->id;
                }
            }
        }

        // Map item_id => [user_id, ...] des complétions, restreinte aux inscrits actifs.
        // Aucune complétion d'un autre cours ne peut entrer : on filtre par items du cours
        // ET par utilisateurs inscrits actifs de ce cours.
        $completionsByItem = [];
        if (! empty($requiredItemIds) && $enrolled > 0) {
            $rows = Completion::query()
                ->where('status', 'completed')
                ->whereIn('lesson_item_id', $requiredItemIds)
                ->whereIn('user_id', $activeUserIds)
                ->get(['lesson_item_id', 'user_id']);

            foreach ($rows as $row) {
                $completionsByItem[$row->lesson_item_id][$row->user_id] = true;
            }
        }

        $result = [];

        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                $items = $lesson->lessonItems;

                // On n'inclut que les leçons ayant au moins un item requis (les autres
                // n'entrent pas dans le calcul de progression, donc pas de décrochage).
                if ($items->isEmpty()) {
                    continue;
                }

                $completedCount = 0;

                if ($enrolled > 0) {
                    foreach ($activeUserIds as $userId) {
                        $allDone = true;
                        foreach ($items as $item) {
                            if (! isset($completionsByItem[$item->id][$userId])) {
                                $allDone = false;
                                break;
                            }
                        }
                        if ($allDone) {
                            $completedCount++;
                        }
                    }
                }

                $result[] = [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'chapter_title' => $chapter->title,
                    'completed' => $completedCount,
                    'total' => $enrolled,
                    'rate' => $enrolled > 0 ? (int) round(($completedCount / $enrolled) * 100) : 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Identifie le « point de décrochage » : la leçon au plus faible taux de
     * complétion (à égalité, la PREMIÈRE dans l'ordre du cours). Retourne null si
     * aucune leçon requise ou aucun inscrit (rien à signaler).
     *
     * @param  array<int, array<string, mixed>>|null  $dropoff  résultat de lessonDropoff() (évite un recalcul)
     * @return array<string, mixed>|null
     */
    public function dropoffPoint(Course $course, ?array $dropoff = null): ?array
    {
        $rows = $dropoff ?? $this->lessonDropoff($course);

        if (empty($rows)) {
            return null;
        }

        $worst = null;
        foreach ($rows as $row) {
            if ($worst === null || $row['rate'] < $worst['rate']) {
                $worst = $row;
            }
        }

        return $worst;
    }

    /**
     * Activité récente : dernières complétions et dernières inscriptions de CE cours.
     * Scopé course_id ; inclut le nom de l'apprenant (donnée nominative réservée au
     * gérant). Trié du plus récent au plus ancien.
     *
     * @return array{completions: \Illuminate\Support\Collection, enrollments: \Illuminate\Support\Collection}
     */
    public function recentActivity(Course $course, int $limit = 8): array
    {
        $completions = Completion::query()
            ->where('course_id', $course->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->with(['user:id,name', 'lessonItem:id,title'])
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get();

        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->whereNotNull('enrolled_at')
            ->with(['user:id,name'])
            ->orderByDesc('enrolled_at')
            ->limit($limit)
            ->get();

        return [
            'completions' => $completions,
            'enrollments' => $enrollments,
        ];
    }

    /**
     * D2 — Apprenants « à risque » de CE cours (qui a besoin d'attention).
     *
     * Ne considère QUE les inscrits ACTIFS de ce cours (anti-IDOR : scopé course_id
     * + users inscrits actifs). Un apprenant à 100 % n'est JAMAIS à risque.
     *
     * Trois catégories, par gravité décroissante (1 = plus grave) :
     *  1. « Jamais commencé » : inscrit depuis > NEVER_STARTED_DAYS jours, progression 0 %.
     *     Source de « dernière activité » = date d'inscription (aucune progression).
     *  2. « Inactif »         : progression 1-99 %, aucune activité depuis > INACTIVE_DAYS jours.
     *  3. « Bloqué »          : progression 1-99 %, a commencé mais n'avance plus
     *     (aucune activité depuis > STUCK_DAYS et <= INACTIVE_DAYS jours).
     *
     * « Dernière activité » = Progress.last_activity_at (horodatage canonique posé à
     * chaque recalcul de progression). Repli sur Enrollment.enrolled_at si aucune
     * ligne de progression (cas « jamais commencé »).
     *
     * Note données quiz : un échec de quiz NE crée PAS de ligne d'historique dédiée
     * (la table `completions` est clé (user_id, lesson_item_id) en upsert, donc une
     * seule ligne par item, réutilisée à chaque tentative) — il n'existe pas de
     * « quiz_attempts ». On ne peut donc PAS compter d'« échecs répétés au même
     * quiz » de façon fiable ; la détection « bloqué » se base uniquement sur la
     * stagnation de la progression (inactivité avec progression 1-99 %).
     *
     * Tri : gravité (jamais commencé > inactif > bloqué), puis ancienneté
     * (le plus de jours « depuis » en premier).
     *
     * @return \Illuminate\Support\Collection<int, array{
     *   user_id:int, name:string, email:string,
     *   reason:string, reason_key:string, severity:int,
     *   percent:int, since:\Illuminate\Support\Carbon, days_since:int,
     *   action:string
     * }>
     */
    public function atRiskLearners(Course $course): \Illuminate\Support\Collection
    {
        // Inscrits actifs de CE cours, avec leurs infos nominatives (réservées au gérant).
        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->whereNotNull('enrolled_at')
            ->with(['user:id,name,email'])
            ->get();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $userIds = $enrollments->pluck('user_id')->unique()->all();

        // Progression persistée scopée à CE cours et à SES inscrits actifs.
        $progressByUser = Progress::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $now = now();
        $atRisk = collect();

        foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            if ($user === null) {
                continue; // utilisateur supprimé : rien à signaler
            }

            $progress = $progressByUser->get($enrollment->user_id);
            $percent = (int) ($progress->percent ?? 0);

            // Un apprenant à 100 % (ou plus) n'est jamais à risque.
            if ($percent >= 100) {
                continue;
            }

            // Dernière activité : progression si disponible, sinon date d'inscription.
            $lastActivity = $progress?->last_activity_at ?? $enrollment->enrolled_at;
            $daysSince = (int) $lastActivity->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());

            $reasonKey = null;
            $reason = null;
            $severity = null;
            $action = null;
            $since = null;

            if ($percent === 0) {
                // « Jamais commencé » : ancré sur la date d'inscription.
                $daysSinceEnrolled = (int) $enrollment->enrolled_at->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());

                if ($daysSinceEnrolled > self::NEVER_STARTED_DAYS) {
                    $reasonKey = 'never_started';
                    $reason = 'Jamais commencé';
                    $severity = 1;
                    $action = 'Relancer / souhaiter la bienvenue';
                    $since = $enrollment->enrolled_at;
                    $daysSince = $daysSinceEnrolled;
                }
            } else {
                // Progression 1-99 % : inactif (gravité 2) ou bloqué (gravité 3).
                if ($daysSince > self::INACTIVE_DAYS) {
                    $reasonKey = 'inactive';
                    $reason = 'Inactif';
                    $severity = 2;
                    $action = 'Relancer';
                    $since = $lastActivity;
                } elseif ($daysSince > self::STUCK_DAYS) {
                    $reasonKey = 'stuck';
                    $reason = 'Bloqué';
                    $severity = 3;
                    $action = 'Proposer de l\'aide';
                    $since = $lastActivity;
                }
            }

            if ($reasonKey === null) {
                continue; // pas à risque
            }

            $atRisk->push([
                'user_id'    => (int) $user->id,
                'name'       => (string) ($user->name ?? 'Apprenant'),
                'email'      => (string) ($user->email ?? ''),
                'reason'     => $reason,
                'reason_key' => $reasonKey,
                'severity'   => $severity,
                'percent'    => $percent,
                'since'      => $since,
                'days_since' => $daysSince,
                'action'     => $action,
            ]);
        }

        // Tri : gravité croissante (1 = plus grave en tête), puis ancienneté décroissante.
        return $atRisk
            ->sortBy([
                ['severity', 'asc'],
                ['days_since', 'desc'],
            ])
            ->values();
    }

    /** Nombre de certificats émis pour CE cours (M6). */
    public function certificatesCount(Course $course): int
    {
        return CertificateIssued::query()
            ->where('course_id', $course->id)
            ->count();
    }
}
