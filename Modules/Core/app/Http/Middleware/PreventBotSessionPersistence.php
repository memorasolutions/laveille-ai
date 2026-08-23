<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bascule le pilote de session sur « array » (mémoire, jamais persisté) pour
 * les requêtes de robots connus - sans jamais modifier ce qui leur est
 * servi. La page rendue reste strictement identique ; seule l'écriture
 * d'une ligne dans la table `sessions` est évitée.
 *
 * Constat 2026-08-23 : la table `sessions` grossissait d'environ 32 000
 * lignes/jour (962 903 lignes, 737 Mo, 12 % de l'espace base de données du
 * compte). Sur un échantillon de 2000 sessions récentes, ~85 % provenaient
 * de robots (crawl/bot/curl/spider/facebookexternalhit...) et 12 seulement,
 * sur 962 903, étaient rattachées à un compte utilisateur. Cause : un robot
 * ne renvoie jamais le cookie de session, donc CHAQUE requête ouvre une
 * session neuve, écrite en base et jamais réclamée.
 *
 * Doit s'exécuter AVANT \Illuminate\Session\Middleware\StartSession - voir
 * bootstrap/app.php (`$middleware->web(prepend: [...])`), car c'est
 * StartSession qui lit `config('session.driver')` pour choisir son magasin.
 *
 * Les QUATRE conditions suivantes doivent toutes être réunies pour
 * basculer ; si une seule manque, comportement normal inchangé (pilote
 * configuré, `database` en production) :
 *
 *  1. Aucun cookie de session déjà présent sur la requête. Un client qui
 *     renvoie un cookie est un client à état : il garde toujours une vraie
 *     session, quel que soit son user-agent.
 *  2. Méthode GET ou HEAD uniquement. Jamais POST/PUT/PATCH/DELETE : le
 *     jeton CSRF exige une vraie session, et un humain mal détecté qui
 *     soumet un formulaire ne doit jamais casser.
 *  3. User-agent reconnu dans `config('view_counter.bot_patterns')` - la
 *     liste de motifs robots DÉJÀ utilisée par
 *     Modules\Core\Services\ViewCounterService pour exclure les robots du
 *     compteur de vues publiques. Réutilisée ici telle quelle (règle DRY
 *     du projet : ne pas dupliquer une connaissance métier) plutôt que
 *     dupliquée dans une seconde liste : c'est la seule liste de motifs
 *     robots du projet.
 *  4. Requête non authentifiée.
 */
final class PreventBotSessionPersistence
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypassSession($request)) {
            config(['session.driver' => 'array']);
        }

        return $next($request);
    }

    /**
     * Conditions ordonnées de la moins coûteuse à la plus coûteuse : la
     * vérification d'authentification (résolution du garde Auth) est
     * évaluée EN DERNIER, seulement si les trois autres tiennent déjà -
     * elle ne s'exécute donc jamais sur le trafic humain ordinaire.
     */
    private function shouldBypassSession(Request $request): bool
    {
        if ($request->hasCookie((string) config('session.cookie'))) {
            return false;
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if (! $this->isKnownBotUserAgent($request->userAgent())) {
            return false;
        }

        return $request->user() === null;
    }

    /**
     * Comparaison insensible à la casse contre `config('view_counter.bot_patterns')` -
     * même liste et même algorithme que
     * Modules\Core\Services\ViewCounterService::isKnownBot() (privée, donc
     * non appelable d'ici ; seule la LISTE est partagée, pas dupliquée).
     * Un user-agent absent/vide n'est PAS traité comme un robot : ambigu,
     * seuls les motifs connus excluent explicitement.
     */
    private function isKnownBotUserAgent(?string $userAgent): bool
    {
        if (! $userAgent || trim($userAgent) === '') {
            return false;
        }

        $userAgent = mb_strtolower($userAgent);

        foreach ((array) config('view_counter.bot_patterns', []) as $pattern) {
            $pattern = mb_strtolower((string) $pattern);
            if ($pattern !== '' && str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
