<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-safe user representation: exposes ONLY id and name.
 *
 * Use this anywhere a user is rendered to a public / third-party audience
 * (article authors, comment authors, etc.). It never leaks PII.
 *
 * Contrast with {@see UserResource}, which is admin-only and additionally
 * exposes email, email_verified_at and roles.
 *
 * @mixin \App\Models\User
 */
final class PublicUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
