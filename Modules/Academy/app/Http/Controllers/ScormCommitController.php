<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - COMMIT du runtime. Reçoit le dump JSON des paires CMI captées
 * par le pont API (window.API / window.API_1484_11) côté navigateur, persiste
 * l'état (ScormRegistration) puis BRANCHE l'achèvement dans le système de
 * progression EXISTANT (CompletionService::markComplete / ProgressService),
 * DRY - aucun système de progression parallèle.
 *
 * SÉCURITÉ (anti-IDOR + gating) :
 *  - drapeau academy.scorm_enabled vérifié en tête (404 si désactivé) ;
 *  - authentification requise (403 sinon - la progression est TOUJOURS
 *    rattachée à un compte réel, jamais anonyme) ;
 *  - l'item est RE-RÉSOLU côté serveur + l'accès RE-VÉRIFIÉ intégralement
 *    (LessonAccessService, DRY avec ScormPlayerController/ScormAssetController) ;
 *  - le corps est borné (nombre de clés + longueur par valeur) - jamais de
 *    confiance aveugle dans la taille/le contenu envoyé par le SCO tiers.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\ScormRegistration;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\LessonAccessService;

class ScormCommitController extends Controller
{
    /** Nombre maximal de clés CMI acceptées par requête (anti-abus). */
    private const MAX_KEYS = 300;

    /** Longueur maximale d'une valeur CMI (octets, anti-abus - suspend_data inclus). */
    private const MAX_VALUE_LENGTH = 65535;

    public function store(Request $request, Course $course, Lesson $lesson, int $itemId): JsonResponse
    {
        abort_unless((bool) config('academy.scorm_enabled', false), 404);

        if (! $request->user()) {
            abort(403);
        }

        $item = LessonAccessService::resolveItem($course, $lesson, $itemId, 'scorm');
        if ($item === null) {
            abort(404);
        }

        if (! LessonAccessService::canAccessItem($request->user(), $course, $item)) {
            abort(403);
        }

        $cmi = $this->sanitizeCmiPayload($request->input());
        if ($cmi === null) {
            return response()->json(['ok' => false, 'message' => 'Données CMI invalides.'], 422);
        }

        $lessonStatus = $this->resolveLessonStatus($cmi);
        $scoreRaw     = $this->resolveScoreRaw($cmi);
        $location     = $this->truncate($cmi['cmi.core.lesson_location'] ?? $cmi['cmi.location'] ?? null, 255);
        $suspendData  = $this->truncate($cmi['cmi.suspend_data'] ?? null, self::MAX_VALUE_LENGTH);

        ScormRegistration::updateOrCreate(
            [
                'user_id'        => $request->user()->id,
                'lesson_item_id' => $item->id,
            ],
            [
                'lesson_status'      => $lessonStatus,
                'score_raw'          => $scoreRaw,
                'lesson_location'    => $location,
                'suspend_data'       => $suspendData,
                'cmi_data'           => $cmi,
                'last_committed_at'  => now(),
            ]
        );

        // Bridge DRY vers le système de progression EXISTANT : un statut
        // completed/passed complète l'item (idempotent) ; sinon on marque
        // seulement le « démarrage » (ne régresse jamais un item déjà complété).
        if (in_array($lessonStatus, ScormRegistration::COMPLETING_STATUSES, true)) {
            CompletionService::markComplete($request->user(), $item, $scoreRaw);
        } else {
            CompletionService::markStarted($request->user(), $item);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Valide + borne le corps reçu : doit être un tableau associatif de
     * chaînes scalaires (clés CMI SCORM), nombre de clés et longueur de valeur
     * bornés. Retourne null si le corps est invalide (jamais de 500).
     *
     * @return array<string, string>|null
     */
    private function sanitizeCmiPayload(mixed $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        if (count($input) > self::MAX_KEYS) {
            return null;
        }

        $clean = [];
        foreach ($input as $key => $value) {
            if (! is_string($key) || $key === '') {
                return null;
            }
            if (! is_scalar($value) && $value !== null) {
                return null;
            }
            $stringValue = $value === null ? '' : (string) $value;
            if (strlen($stringValue) > self::MAX_VALUE_LENGTH) {
                $stringValue = substr($stringValue, 0, self::MAX_VALUE_LENGTH);
            }
            $clean[$key] = $stringValue;
        }

        return $clean;
    }

    /**
     * Détermine le statut EFFECTIF à partir des clés SCORM 1.2
     * (cmi.core.lesson_status) OU 2004 (cmi.success_status / cmi.completion_status).
     * SCORM 2004 combine DEUX axes (réussite ET complétion) ; on les réduit à un
     * statut unique pour rester compatible avec le stockage 1.2 existant :
     *   - success_status = passed/failed  → priorité (résultat noté) ;
     *   - sinon completion_status = completed → complété (non noté) ;
     *   - sinon la valeur brute (incomplete/not attempted/…) ou 'unknown'.
     *
     * @param  array<string, string>  $cmi
     */
    private function resolveLessonStatus(array $cmi): string
    {
        $legacyStatus = $cmi['cmi.core.lesson_status'] ?? null;
        if (is_string($legacyStatus) && $legacyStatus !== '') {
            return $legacyStatus;
        }

        $successStatus = $cmi['cmi.success_status'] ?? null;
        if ($successStatus === 'passed' || $successStatus === 'failed') {
            return $successStatus;
        }

        $completionStatus = $cmi['cmi.completion_status'] ?? null;
        if ($completionStatus === 'completed') {
            return 'completed';
        }

        return is_string($completionStatus) && $completionStatus !== '' ? $completionStatus : 'unknown';
    }

    /**
     * Score brut (0..100), lu depuis cmi.core.score.raw (1.2) ou cmi.score.raw
     * (2004). null si absent/non numérique (jamais d'exception).
     *
     * @param  array<string, string>  $cmi
     */
    private function resolveScoreRaw(array $cmi): ?int
    {
        $raw = $cmi['cmi.core.score.raw'] ?? $cmi['cmi.score.raw'] ?? null;
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return max(0, min(100, (int) round((float) $raw)));
    }

    private function truncate(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }
}
