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
 * Garde anti-rejeu : un InResponseTo consommé une fois ne peut plus l'être
 * (contrainte unique composite en base — voir migration + AcsController).
 */
class SamlReplayGuard extends Model
{
    protected $table = 'sso_saml_replay_guards';

    protected $fillable = [
        'sso_configuration_id',
        'in_response_to',
        'assertion_id',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'consumed_at' => 'datetime',
        ];
    }

    public function ssoConfiguration(): BelongsTo
    {
        return $this->belongsTo(SsoConfiguration::class);
    }
}
