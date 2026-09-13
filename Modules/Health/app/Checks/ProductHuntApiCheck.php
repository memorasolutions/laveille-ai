<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Checks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Surveille la validite du jeton d'API ProductHunt utilise par ToolDiscoveryService.
 *
 * RAISON D'ETRE (2026-09-13) : ce jeton alimente la decouverte quotidienne de nouveaux outils.
 * Quand il devient invalide, fetchProductHunt() journalise un simple warning dans le canal
 * 'directory_discovery' puis retourne un tableau vide, et la commande de decouverte se termine
 * en SUCCES. La panne est donc totalement MUETTE - exactement le meme motif que l'epuisement de
 * credit OpenRouter (cf. OpenRouterCreditCheck). Mesure du jour : l'API repondait 401 chaque nuit
 * depuis AU MOINS 14 jours - la panne est en realite anterieure au plus ancien journal conserve,
 * et rien nulle part ne la signalait. Pendant ce temps, la decouverte refusait environ 20
 * candidats distincts par jour, a juste titre : sans l'API elle ne peut pas connaitre l'adresse
 * reelle du produit, et elle refuse plutot que d'enregistrer un lien vers producthunt.com.
 *
 * POURQUOI PAS ->hourly() : un controle Spatie non echu produit un resultat « skipped », et
 * `treat_skipped_as_failure` vaut true par defaut - le statut global du site passerait au rouge
 * 59 minutes sur 60. Le controle s'execute donc a CHAQUE passage, et c'est l'APPEL RESEAU qui
 * est etrangle par le cache.
 */
final class ProductHuntApiCheck extends Check
{
    /**
     * Nombre d'echecs reseau consecutifs tolere avant de passer de l'avertissement a l'echec.
     */
    private const SEUIL_ECHECS = 3;

    public function run(): Result
    {
        $result = Result::make();

        $jeton = config('directory.producthunt_token');
        if (empty($jeton)) {
            return $this->conclure($result, 'warning', 'Jeton ProductHunt absent : la découverte de nouveaux outils par ProductHunt ne fonctionne pas.', 'jeton absent', 'jeton');
        }

        $mesure = Cache::get($this->cleMesure());
        if (
            is_array($mesure) &&
            isset($mesure['statut'], $mesure['message'], $mesure['resume'], $mesure['horodatage']) &&
            time() - (int) $mesure['horodatage'] < $this->intervalleInterrogation()
        ) {
            return $this->rejouer($result, $mesure);
        }

        try {
            $reponse = Http::withHeaders([
                'Authorization' => 'Bearer '.$jeton,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://api.producthunt.com/v2/api/graphql', [
                'query' => 'query { posts(first: 1) { edges { node { id } } } }',
            ]);

            $code = $reponse->status();
            $corps = $reponse->json();

            // Un jeton mort se manifeste de DEUX facons distinctes, mesurees le 2026-09-13 : par le
            // code HTTP, et par une erreur 'invalid_oauth_token' dans le corps d'une reponse par
            // ailleurs bien formee. Ne tester que le code HTTP laisserait passer le second cas.
            if ($code === 401 || $code === 403 || (
                isset($corps['errors']) &&
                is_array($corps['errors']) &&
                count(array_filter($corps['errors'], fn ($e) => is_array($e) && ($e['error'] ?? null) === 'invalid_oauth_token')) > 0
            )) {
                return $this->conclure($result, 'failed', "Jeton ProductHunt invalide (HTTP {$code}) : la découverte de nouveaux outils est à l'arrêt, et les fiches déjà enregistrées vers producthunt.com ne peuvent pas être corrigées. Créer un nouveau jeton sur https://api.producthunt.com/v2/oauth/applications", 'jeton invalide', 'jeton');
            }

            if ($code === 429) {
                return $this->conclure($result, 'warning', "ProductHunt limite la cadence (HTTP 429). La découverte reprendra d'elle-même.", 'cadence limitée', 'cadence');
            }

            if ($reponse->successful()) {
                $edges = $corps['data']['posts']['edges'] ?? [];
                if (is_array($edges) && count($edges) > 0) {
                    return $this->conclure($result, 'ok', 'API ProductHunt fonctionnelle.', 'fonctionnelle', 'api');
                }

                return $this->conclure($result, 'warning', "L'API ProductHunt répond mais ne renvoie aucune publication.", 'réponse vide', 'api');
            }

            return $this->echecTransitoire($result, "HTTP {$code}");
        } catch (Throwable $e) {
            // ConnectionException est deja un Throwable : un seul bloc suffit, en ajouter un second
            // ne ferait que dupliquer le meme traitement.
            return $this->echecTransitoire($result, $e->getMessage());
        }
    }

    /**
     * Verdict DEFINITIF : l'appel reseau a abouti (meme s'il conclut a un echec). Le compteur
     * d'echecs consecutifs est donc remis a zero - il ne compte que les pannes de TRANSPORT, et
     * un jeton refuse n'en est pas une.
     */
    private function conclure(Result $result, string $statut, string $message, string $resume, string $cause): Result
    {
        Cache::forever($this->cleEchecs(), 0);
        $this->memoriser($statut, $message, $resume, $cause);

        return $this->appliquer($result, $statut, $message, $resume, $cause);
    }

    private function memoriser(string $statut, string $message, string $resume, string $cause): void
    {
        Cache::forever($this->cleMesure(), [
            'statut' => $statut,
            'message' => $message,
            'resume' => $resume,
            'cause' => $cause,
            'horodatage' => time(),
        ]);
    }

    /**
     * La CAUSE voyage jusqu'au courriel d'alerte, qui choisit sa marche a suivre dessus : un
     * jeton refuse appelle « cree un nouveau jeton », une panne de transport appelle « attends,
     * ca se resorbe seul ». Afficher la premiere sur la seconde serait la meme faute que celle
     * corrigee le 2026-08-01 sur OPcache, ou un simple timeout declenchait « augmente la
     * directive saturee ».
     */
    private function appliquer(Result $result, string $statut, string $message, string $resume, string $cause): Result
    {
        $result->meta(['cause' => $cause]);

        $result = match ($statut) {
            'ok' => $result->ok($message),
            'warning' => $result->warning($message),
            default => $result->failed($message),
        };

        $result->shortSummary($resume);

        if ($statut !== 'ok') {
            Log::channel('directory_discovery')->warning('[ProductHuntApiCheck] '.$message);
        }

        return $result;
    }

    /**
     * Rejeu d'une mesure encore fraiche, sans aucun appel reseau ni reecriture du cache.
     * Le rejeu journalise a nouveau, volontairement : une panne qui dure doit laisser une trace
     * repetee dans le canal, sinon elle disparait du journal des qu'on cesse d'interroger.
     */
    private function rejouer(Result $result, array $mesure): Result
    {
        return $this->appliquer($result, (string) $mesure['statut'], (string) $mesure['message'], (string) $mesure['resume'], (string) ($mesure['cause'] ?? 'api'));
    }

    /**
     * Panne de TRANSPORT (exception reseau, ou code HTTP inattendu). On ne crie pas au loup au
     * premier incident : avertissement tant que le seuil n'est pas atteint, echec ensuite.
     *
     * La mesure est memorisee ICI AUSSI, et c'est essentiel : sans cela, le controle rappellerait
     * ProductHunt a chaque minute pendant toute la duree de la panne, soit 1440 fois par jour.
     */
    private function echecTransitoire(Result $result, string $cause): Result
    {
        $n = (int) Cache::get($this->cleEchecs(), 0) + 1;
        Cache::forever($this->cleEchecs(), $n);

        $persistant = $n >= self::SEUIL_ECHECS;
        $statut = $persistant ? 'failed' : 'warning';
        $message = $persistant
            ? "Échec persistant de contact avec ProductHunt ({$cause}), {$n} essais consécutifs."
            : "Échec temporaire de contact avec ProductHunt ({$cause}), {$n} essai(s) consécutif(s).";
        $resume = $persistant ? 'échec persistant' : 'échec temporaire';

        $this->memoriser($statut, $message, $resume, 'transport');

        return $this->appliquer($result, $statut, $message, $resume, 'transport');
    }

    private function intervalleInterrogation(): int
    {
        return (int) config('health.producthunt.check_interval_seconds', 3600);
    }

    private function cleEchecs(): string
    {
        return (string) config('health.producthunt.connection_failures_cache_key', 'health:producthunt:echecs_consecutifs');
    }

    private function cleMesure(): string
    {
        return (string) config('health.producthunt.measurement_cache_key', 'health:producthunt:derniere_mesure');
    }
}
