<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

namespace Modules\Core\Services;

/**
 * ACTION : source de vérité UNIQUE des préférences de fournisseur OpenRouter qui
 * refusent la collecte des données (data_collection=deny) et exigent une politique
 * de rétention nulle (zdr=true) - règle non négociable posée le 2026-08-13 : un texte
 * envoyé à un modèle ne doit JAMAIS être conservé par le sous-traitant IA.
 * MCP: SELF (assemblage de payload, < 5 lignes utiles)
 * RAISON: DRY explicite - une seule définition (pilotée par config, valeurs par
 * défaut protectrices), un seul endroit qui la transforme en fragment de payload.
 * Tout appel OpenRouter du projet (chat/completions comme embeddings) passe par
 * applyTo() - jamais de recopie du bloc 'provider' d'un service à l'autre.
 */
class OpenRouterPrivacy
{
    /**
     * @return array<string, mixed>
     */
    public static function providerPreferences(): array
    {
        return array_filter([
            'data_collection' => config('services.openrouter.data_collection', 'deny'),
            'zdr' => config('services.openrouter.zdr', true),
        ]);
    }

    /**
     * Injecte le bloc 'provider' dans un payload OpenRouter existant. Fusionne
     * superficiellement avec une clé 'provider' déjà présente plutôt que de l'écraser.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function applyTo(array $payload): array
    {
        $preferences = self::providerPreferences();
        if ($preferences === []) {
            return $payload;
        }

        $payload['provider'] = array_merge($preferences, $payload['provider'] ?? []);

        return $payload;
    }
}
