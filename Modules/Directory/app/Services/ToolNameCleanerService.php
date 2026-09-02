<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

final class ToolNameCleanerService
{
    private const HN_PREFIX_PATTERN = '/^Show\s*HN\s*[:\-–—]?\s*/iu';

    private const SEPARATORS = [' – ', ' — ', ' : ', ' | ', '–', '—'];

    /**
     * Préfixes de commande shell / installation (flux hnrss.org/show notamment : le titre brut
     * EST parfois la commande, ex. « npm i -g hotcell »). Comparaison insensible à la casse, avec
     * frontière de mot (le préfixe doit être suivi d'une espace ou de la fin de chaîne) pour ne
     * jamais confondre un vrai nom de produit qui commence par les mêmes lettres (« Aptible »,
     * « Go Getter », « Dockerize » ne doivent pas matcher « apt », « go get », « docker »).
     */
    private const SHELL_COMMAND_PREFIXES = [
        '$', 'sudo', 'npm', 'npx', 'pip', 'pip3', 'yarn', 'pnpm', 'brew', 'apt', 'cargo',
        'go get', 'git clone', 'curl', 'wget', 'docker',
    ];

    public static function clean(string $rawName): string
    {
        $trimmedOriginal = trim($rawName);
        $hadHnPrefix = self::isHnTitle($trimmedOriginal);

        $cleanedName = preg_replace(self::HN_PREFIX_PATTERN, '', $trimmedOriginal);
        $cleanedName = trim($cleanedName);

        if ($hadHnPrefix) {
            foreach (self::SEPARATORS as $sep) {
                if (mb_strpos($cleanedName, $sep) !== false) {
                    $cleanedName = trim(explode($sep, $cleanedName, 2)[0]);
                    break;
                }
            }
        }

        if ($cleanedName === '') {
            return $trimmedOriginal;
        }

        return $cleanedName;
    }

    public static function isHnTitle(string $rawName): bool
    {
        return (bool) preg_match(self::HN_PREFIX_PATTERN, $rawName);
    }

    /**
     * Normalise un nom pour une comparaison d'ÉGALITÉ STRICTE entre deux fiches (minuscules, sans
     * accents, sans ponctuation, espaces multiples réduits à un seul). Utilisé par
     * Tool::matchesNameExact() / ToolDiscoveryService::ingest() (ticket #2175, mesuré 2026-09-02).
     *
     * N'ALTÈRE PAS ToolDiscoveryService::ingest() : la mesure a établi que les 5 doublons réels du
     * catalogue (Voiser AI, CaseGap AI, Thinnest AI, NoMac.app, Animos App) portaient un nom
     * RIGOUREUSEMENT identique d'une fiche à l'autre - le contrôle flou existant (matchesName(),
     * seuil 85) les manquait quand même, car le suffixe générique («\u{a0}AI\u{a0}»/«\u{a0}App\u{a0}»/
     * «\u{a0}Tool\u{a0}») n'était retiré QUE du candidat entrant, jamais du nom déjà en base -
     * asymétrie qui abaissait artificiellement le score sous le seuil (75-84 % mesurés, jamais
     * 100 %). Volontairement SANS ce genre de troncature de suffixe : une égalité stricte n'a pas
     * besoin d'heuristique, et une heuristique asymétrique est précisément ce qui a causé le bug.
     */
    public static function normalizeForComparison(string $name): string
    {
        $ascii = \Illuminate\Support\Str::ascii($name);
        $lower = mb_strtolower($ascii);
        $alnumOnly = preg_replace('/[^a-z0-9]+/', ' ', $lower);

        return trim(preg_replace('/\s+/', ' ', $alnumOnly));
    }

    /**
     * Signale un titre qui ressemble à une commande shell ou une instruction d'installation
     * plutôt qu'à un nom de produit (ex. « npm i -g hotcell » depuis le flux hnrss.org/show).
     * N'altère PAS le comportement de clean() : les appelants existants (FixHnSlugsCommand)
     * conservent leur contrat inchangé - c'est à l'appelant (ToolDiscoveryService::ingest())
     * de décider quoi faire d'un titre signalé ici.
     */
    public static function looksLikeShellCommand(string $name): bool
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return false;
        }

        $lower = mb_strtolower($trimmed);

        foreach (self::SHELL_COMMAND_PREFIXES as $prefix) {
            if (! str_starts_with($lower, $prefix)) {
                continue;
            }

            $boundary = mb_substr($lower, mb_strlen($prefix), 1);
            if ($boundary === '' || $boundary === ' ') {
                return true;
            }
        }

        return false;
    }
}
