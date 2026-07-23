<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Services;

use Pdp\Rules;
use Throwable;

class EcosystemResolverService
{
    /**
     * Instance partagée (par process PHP) des règles PSL déjà parsées, pour éviter de
     * reparser le fichier public_suffix_list.dat (~16k lignes) à chaque appel de resolve()
     * — critique pour la commande de backfill qui boucle sur 400+ outils.
     */
    private static ?Rules $rules = null;

    /**
     * Résout le tag d'écosystème (ex. "openai") à partir d'une URL d'outil, en comparant le
     * domaine RACINE réel (via Public Suffix List, donc correct sur les TLD composés type
     * .co.uk) à la table config('ecosystems.domains'). Correspondance EXACTE uniquement —
     * jamais de str_contains/substring (piège : "fakeopenai.com" ne doit jamais matcher
     * "openai.com"). Retourne null si le domaine n'est pas reconnu, ne devine jamais.
     */
    public function resolve(string $url): ?string
    {
        $rootDomain = $this->extractRootDomain($url);

        if ($rootDomain === null) {
            return null;
        }

        $domains = (array) config('ecosystems.domains', []);

        return $domains[$rootDomain] ?? null;
    }

    /**
     * Extrait le domaine racine (registrable domain) normalisé (minuscule, sans "www.")
     * d'une URL. Retourne null si l'URL est invalide ou non résolvable.
     */
    public function extractRootDomain(string $url): ?string
    {
        $host = $this->extractHost($url);

        if ($host === null) {
            return null;
        }

        try {
            $registrable = self::rules()->resolve($host)->registrableDomain()->toString();
        } catch (Throwable) {
            return null;
        }

        if ($registrable === null || $registrable === '') {
            return null;
        }

        return mb_strtolower($registrable);
    }

    private function extractHost(string $url): ?string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return null;
        }

        $host = parse_url($trimmed, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            // Pas de schéma fourni (ex. "openai.com" plutôt que "https://openai.com") :
            // parse_url() ne remplit PHP_URL_HOST que si un schéma est présent.
            $host = parse_url('https://' . ltrim($trimmed, '/'), PHP_URL_HOST);
        }

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = mb_strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return $host === '' ? null : $host;
    }

    /**
     * Charge la Public Suffix List depuis le fichier vendorisé (jamais de fetch réseau à
     * l'exécution : le fichier est commité dans le module et rafraîchi périodiquement à la
     * main, cf. https://publicsuffix.org/list/public_suffix_list.dat).
     */
    private static function rules(): Rules
    {
        if (self::$rules === null) {
            self::$rules = Rules::fromPath(
                module_path('Directory', 'resources/data/public_suffix_list.dat')
            );
        }

        return self::$rules;
    }
}
