<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configuration SAML 2.0 + SCIM d'une organisation cliente (multi-tenant).
 *
 * `tenant_id` est une relation "SOUPLE" vers Modules\Tenancy\Models\Tenant :
 * AUCUNE contrainte FK dure en base (portabilité — le module Sso fonctionne
 * même si Tenancy est désactivé), résolue dynamiquement via class_exists().
 */
class SsoConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_slug',
        'tenant_id',
        'name',
        'is_active',
        'sp_entity_id',
        'idp_entity_id',
        'idp_sso_url',
        'idp_x509_cert',
        'attribute_mapping',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'attribute_mapping' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scimTokens(): HasMany
    {
        return $this->hasMany(ScimToken::class);
    }

    public function replayGuards(): HasMany
    {
        return $this->hasMany(SamlReplayGuard::class);
    }

    /**
     * Relation souple vers Modules\Tenancy\Models\Tenant (si le module est actif).
     * Retourne null proprement si Tenancy est désactivé — jamais de fatal.
     */
    public function tenant(): ?object
    {
        if ($this->tenant_id === null || ! class_exists(\Modules\Tenancy\Models\Tenant::class)) {
            return null;
        }

        return \Modules\Tenancy\Models\Tenant::find($this->tenant_id);
    }

    /**
     * Mapping d'attributs effectif (celui de l'organisation, ou le défaut config).
     *
     * @return array<string, string>
     */
    public function effectiveAttributeMapping(): array
    {
        $mapping = $this->attribute_mapping ?? [];

        return array_replace(
            config('sso.saml.default_attribute_mapping', []),
            $mapping
        );
    }

    protected static function newFactory(): \Modules\Sso\Database\Factories\SsoConfigurationFactory
    {
        return \Modules\Sso\Database\Factories\SsoConfigurationFactory::new();
    }
}
