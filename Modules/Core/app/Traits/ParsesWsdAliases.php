<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * 2026-05-06 #158 : convertit textarea aliases (1 ligne par alias) en array PHP nettoyé.
 * Réutilisé par TermAdminController + AcronymAdminController (DRY).
 *
 * Format input : "Tokens\ntokenisation\n\ntokenization\n"
 * Format output : ["Tokens", "tokenisation", "tokenization"] (trim + filter empty)
 */
trait ParsesWsdAliases
{
    protected static function parseAliases(?string $raw): ?array
    {
        if (! $raw) return null;
        $lines = preg_split('/\r\n|\n|\r/', trim($raw));
        $aliases = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
        return empty($aliases) ? null : $aliases;
    }
}
