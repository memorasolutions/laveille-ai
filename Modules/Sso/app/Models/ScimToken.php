<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jeton Bearer SCIM d'une organisation. Le jeton EN CLAIR n'existe qu'au
 * moment de sa création (retourné une seule fois) — seul son hash SHA-256
 * est persisté (voir Services\ScimTokenService::issue()/resolve()).
 */
class ScimToken extends Model
{
    protected $table = 'sso_scim_tokens';

    protected $fillable = [
        'sso_configuration_id',
        'name',
        'token_hash',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function ssoConfiguration(): BelongsTo
    {
        return $this->belongsTo(SsoConfiguration::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
