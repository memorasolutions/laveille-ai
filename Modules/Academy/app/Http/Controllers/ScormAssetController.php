<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - PROXY D'ASSET PROTÉGÉ. Le paquet SCORM extrait vit sur le
 * disque PRIVÉ « local » (storage/app/private, jamais servi directement par
 * le serveur web) : CHAQUE fichier (page de lancement, JS, CSS, images,
 * médias…) passe par CETTE route, qui RE-VÉRIFIE l'inscription active au
 * cours (ou le rôle gérant/preview) à CHAQUE requête - anti-IDOR STRUCTUREL,
 * pas une simple obscurité d'URL comme le paquet H5P (disque public + UUID).
 *
 * Le chemin demandé est validé intégralement par
 * ScormPackageService::resolveAssetPath() (anti zip-slip / anti-traversal) :
 * aucune confiance dans le segment {path} fourni par le client au-delà de
 * cette validation stricte contre le dossier RÉELLEMENT extrait de CET item.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Services\LessonAccessService;
use Modules\Academy\Services\ScormPackageService;

class ScormAssetController extends Controller
{
    public function show(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId,
        string $path = ''
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        abort_unless((bool) config('academy.scorm_enabled', false), 404);

        $item = LessonAccessService::resolveItem($course, $lesson, $itemId, 'scorm');
        if ($item === null) {
            abort(404);
        }

        if (! LessonAccessService::canAccessItem($request->user(), $course, $item)) {
            abort(403);
        }

        $packageRelDir = $item->payload['scorm_path'] ?? null;

        $resolved = (new ScormPackageService())->resolveAssetPath(
            is_string($packageRelDir) ? $packageRelDir : null,
            $path
        );

        if ($resolved === null) {
            abort(404);
        }

        $disk = Storage::disk(ScormPackageService::DISK);
        $mime = $disk->mimeType($resolved) ?: 'application/octet-stream';

        // Diffusion en flux (StreamedResponse) : évite de charger en mémoire un
        // gros média (vidéo/audio embarqués dans le paquet SCORM).
        return response()->stream(function () use ($disk, $resolved): void {
            $stream = $disk->readStream($resolved);
            if ($stream !== null && $stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type'           => $mime,
            'Content-Length'         => (string) $disk->size($resolved),
            // Défense en profondeur : jamais d'exécution basée sur un sniff MIME
            // du navigateur, même si le fichier servi est du HTML/JS légitime.
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=3600',
        ]);
    }
}
