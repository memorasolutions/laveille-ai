<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * SOURCE UNIQUE (DRY) de la re-résolution + autorisation d'un item de leçon
 * pour les points d'entrée SERVEUR qui servent du contenu protégé DANS une
 * iframe (H5P, proxy vidéo signé). Centralise le pattern déjà éprouvé par
 * H5pPlayerController::play() : anti-IDOR (l'item doit appartenir à la
 * leçon, qui doit appartenir au cours), accès = gérant du cours (preview)
 * OU item marqué preview OU inscription active, cours publié sauf gérant
 * ou item preview, restrictions V5-d re-vérifiées.
 *
 * Ne remplace PAS le calcul $hasAccess de LessonController (affichage de la
 * page complète, avec prérequis/drip/etc.) : cette classe sert les contrôleurs
 * qui n'ont accès qu'à l'item ciblé (URL signée à courte portée), où la même
 * question doit être re-posée intégralement côté serveur, jamais déléguée à
 * la seule validité de la signature de l'URL.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

final class LessonAccessService
{
    /**
     * Re-résout un item de leçon en garantissant son appartenance à la leçon
     * et au cours donnés (anti-IDOR). Retourne null si l'item n'existe pas,
     * n'appartient pas à cette leçon, ou si la leçon n'appartient pas à ce
     * cours (via le chapitre).
     */
    public static function resolveItem(Course $course, Lesson $lesson, int $itemId, ?string $type = null): ?LessonItem
    {
        $lesson->loadMissing('chapter');
        if ((int) $lesson->chapter?->course_id !== $course->id) {
            return null;
        }

        $query = LessonItem::where('id', $itemId)->where('lesson_id', $lesson->id);
        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->first();
    }

    /**
     * Décide si l'utilisateur (potentiellement anonyme) peut consulter CET
     * item précis, re-vérifié intégralement côté serveur :
     *  - gérant du cours (can('update', $course)) : toujours autorisé (preview) ;
     *  - item marqué payload['preview']=true : toujours autorisé ;
     *  - sinon : inscription active exigée, cours publié exigé, restrictions
     *    d'accès (V5-d) re-évaluées.
     */
    public static function canAccessItem(?User $user, Course $course, LessonItem $item): bool
    {
        $isManager = $user !== null && $user->can('update', $course);
        if ($isManager) {
            return true;
        }

        $itemPreview = (bool) ($item->payload['preview'] ?? false);
        if ($itemPreview) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        $isEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if (! $isEnrolled) {
            return false;
        }

        // Cours non publié (draft/archived) : réservé au gérant ou à un item preview,
        // déjà autorisés ci-dessus. Un inscrit sur un cours dépublié perd l'accès.
        if ($course->status !== 'published') {
            return false;
        }

        if (class_exists(AccessRestrictionService::class)) {
            $restriction = AccessRestrictionService::evaluate($user, $item, $course);
            if (! $restriction['allowed']) {
                return false;
            }
        }

        return true;
    }
}
