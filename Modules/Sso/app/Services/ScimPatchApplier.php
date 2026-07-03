<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Services;

/**
 * Applique un PATCH SCIM (RFC 7644 §3.5.2 — PatchOp) à un tableau d'attributs
 * Laravel déjà résolus (voir ScimUserMapper::fromScim). Support DÉLIBÉRÉMENT
 * réduit aux opérations "replace" sur les attributs simples userName/name/
 * displayName/emails/active — c'est le cas d'usage quasi-exclusif des IdP
 * réels (Okta/Azure AD/Google Workspace) pour la désactivation de compte.
 * "add"/"remove" sur des attributs composés (ex. groups) sont HORS SCOPE.
 */
class ScimPatchApplier
{
    /**
     * @param array<int, array{op?: string, path?: string, value?: mixed}> $operations
     * @return array<string, mixed> attributs Laravel à fusionner (is_active, email, name)
     */
    public function apply(array $operations): array
    {
        $attributes = [];

        foreach ($operations as $operation) {
            $op = strtolower($operation['op'] ?? 'replace');
            $path = $operation['path'] ?? null;
            $value = $operation['value'] ?? null;

            if ($op !== 'replace') {
                continue; // add/remove sur attributs composés : hors scope V1.
            }

            // Path explicite (ex. "active") ou objet fusionné sans path
            // (ex. {"op":"replace","value":{"active":false}}) — les deux
            // formes sont utilisées par les IdP réels.
            if ($path === null && is_array($value)) {
                foreach ($value as $key => $val) {
                    $this->applyOne($attributes, (string) $key, $val);
                }

                continue;
            }

            if ($path !== null) {
                $this->applyOne($attributes, $path, $value);
            }
        }

        return $attributes;
    }

    private function applyOne(array &$attributes, string $path, mixed $value): void
    {
        match ($path) {
            'active' => $attributes['is_active'] = (bool) $value,
            'userName', 'emails', 'emails[type eq "work"].value' => $attributes['email'] = is_array($value) ? ($value[0]['value'] ?? null) : $value,
            'name.formatted', 'displayName' => $attributes['name'] = $value,
            default => null,
        };
    }
}
