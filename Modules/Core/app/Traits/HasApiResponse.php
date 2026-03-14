<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;

trait HasApiResponse
{
    protected function respondSuccess(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function respondError(string $message = 'Erreur', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function respondCreated(mixed $data = null, string $message = 'Créé avec succès'): JsonResponse
    {
        return $this->respondSuccess($data, $message, 201);
    }

    protected function respondNotFound(string $message = 'Ressource introuvable'): JsonResponse
    {
        return $this->respondError($message, 404);
    }

    protected function respondUnauthorized(string $message = 'Non authentifié'): JsonResponse
    {
        return $this->respondError($message, 401);
    }

    protected function respondForbidden(string $message = 'Accès interdit'): JsonResponse
    {
        return $this->respondError($message, 403);
    }

    protected function respondNoContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
