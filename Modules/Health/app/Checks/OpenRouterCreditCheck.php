<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Checks;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Surveille le solde de credit OpenRouter, qui finance l'enrichissement de l'annuaire.
 *
 * RAISON D'ETRE (2026-08-23) : quand le credit s'epuise, l'API repond 402, OpenRouterService
 * journalise dans un canal dedie, la commande d'enrichissement conclut « generation trop
 * courte » et le job se termine en SUCCES. Autrement dit, la panne est totalement SILENCIEUSE
 * - le meme mecanisme qui avait deja tue l'enrichissement pendant neuf jours sans que personne
 * ne le voie. Ce controle existe pour que l'epuisement se manifeste AVANT la panne.
 *
 * Deux signaux independants, le plus grave l'emporte :
 *  - le montant restant en dollars ;
 *  - l'autonomie estimee en jours, deduite de la baisse REELLE du solde entre deux mesures.
 * L'autonomie compte autant que le montant : « 31 $ » ne dit rien a un humain, « environ
 * 11 jours » dit quoi faire. Elle s'adapte seule a une rafale d'enrichissement qui brulerait le
 * solde en deux jours, la ou un seuil en dollars fige alerterait trop tard.
 *
 * POURQUOI PAS ->hourly() : un controle Spatie non echu produit un resultat « skipped », et
 * `treat_skipped_as_failure` vaut true par defaut - le statut global du site passerait au rouge
 * 59 minutes sur 60. On garde donc un controle qui s'execute a chaque passage, et c'est
 * l'APPEL RESEAU qui est etrangle par le cache. Bonus : la meme entree de cache sert d'ancre au
 * calcul d'autonomie, puisque l'intervalle d'interrogation EST l'intervalle d'echantillonnage.
 */
final class OpenRouterCreditCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make();

        $cle = config('directory.openrouter_api_key');

        if (empty($cle)) {
            return $result->warning("Crédit OpenRouter non mesurable : aucune clé d'API n'est configurée. L'enrichissement de l'annuaire ne peut pas fonctionner.");
        }

        $precedente = $this->derniereMesure();
        $maintenant = time();

        // Mesure encore fraiche : on rend le verdict sans toucher au reseau. Le controle de
        // sante tourne chaque minute ; interroger OpenRouter 1440 fois par jour pour un solde
        // qui bouge de quelques dollars n'apporterait rien et multiplierait les occasions
        // d'echec transitoire.
        if ($precedente !== null && ($maintenant - $precedente['t']) < $this->intervalleInterrogation()) {
            return $this->verdict($result, $precedente['restant'], $precedente['total'], $precedente['consomme'], $precedente['jours']);
        }

        try {
            // Point d'acces VERIFIE le 2026-08-23 : /api/v1/credits repond bien avec la cle
            // ordinaire du site (is_management_key=false), contrairement a ce que decrit la
            // documentation courante. L'autre porte, /api/v1/key, ne convient PAS ici : elle ne
            // renvoie que le plafond PAR CLE, et ce plafond vaut null sur nos cles - donc aucun
            // solde exploitable.
            $reponse = Http::timeout((int) config('health.openrouter.timeout', 10))
                ->withToken($cle)
                ->acceptJson()
                ->get('https://openrouter.ai/api/v1/credits');
        } catch (ConnectionException $exception) {
            // Meme doctrine que OpcacheCheck : une coupure reseau isolee n'est PAS un signal.
            // Une alerte qui sonne pour un incident inexistant apprend surtout a ignorer le
            // tableau de bord. On compte, on se tait, et on ne parle qu'a la repetition.
            return $this->echecTransitoire($result, ['erreur' => $exception->getMessage()], 'la connexion a échoué');
        } catch (Throwable $exception) {
            return $result->meta(['erreur' => $exception->getMessage()])
                ->warning('Crédit OpenRouter non mesurable : la réponse est inexploitable.');
        }

        $statut = $reponse->status();

        // Une cle refusee n'est pas un incident passager : c'est une configuration cassee, et
        // l'enrichissement est deja mort. On le dit tout de suite, sans compteur.
        if ($statut === 401 || $statut === 403) {
            return $result->meta(['statut' => $statut])
                ->warning("Crédit OpenRouter non mesurable : la clé d'API est refusée (HTTP {$statut}). Vérifiez OPENROUTER_API_KEY.");
        }

        if (! $reponse->successful()) {
            return $this->echecTransitoire($result, ['statut' => $statut], "l'API a répondu {$statut}");
        }

        $total = $reponse->json('data.total_credits');
        $consomme = $reponse->json('data.total_usage');

        if (! is_numeric($total) || ! is_numeric($consomme)) {
            return $this->echecTransitoire($result, ['statut' => $statut], 'le JSON reçu ne portait pas total_credits et total_usage');
        }

        Cache::forever($this->cleEchecs(), 0);

        $total = (float) $total;
        $consomme = (float) $consomme;
        $restant = round($total - $consomme, 2);
        $jours = $this->autonomieEnJours($precedente, $restant, $maintenant);

        Cache::forever($this->cleMesure(), [
            't' => $maintenant,
            'restant' => $restant,
            'total' => $total,
            'consomme' => $consomme,
            'jours' => $jours,
        ]);

        return $this->verdict($result, $restant, $total, $consomme, $jours);
    }

    private function verdict(Result $result, float $restant, float $total, float $consomme, ?float $jours): Result
    {
        $result = $result
            ->meta([
                'restant' => $restant,
                'total' => $total,
                'consomme' => $consomme,
                'jours_estimes' => $jours,
            ])
            ->shortSummary($this->montant($restant).' restants');

        $rechargez = " Rechargez avant que l'enrichissement de l'annuaire ne s'arrête.";

        if ($restant <= (float) config('health.openrouter.fail_remaining_usd', 15)) {
            return $result->failed('Crédit OpenRouter presque épuisé : '.$this->resume($restant, $jours).$rechargez);
        }

        if ($jours !== null && $jours <= (float) config('health.openrouter.fail_remaining_days', 3)) {
            return $result->failed('Crédit OpenRouter bientôt épuisé : '.$this->resume($restant, $jours).$rechargez);
        }

        if ($restant <= (float) config('health.openrouter.warn_remaining_usd', 50)) {
            return $result->warning('Crédit OpenRouter bas : '.$this->resume($restant, $jours));
        }

        if ($jours !== null && $jours <= (float) config('health.openrouter.warn_remaining_days', 10)) {
            return $result->warning('Crédit OpenRouter à surveiller : '.$this->resume($restant, $jours));
        }

        // ok() SANS message, imperativement : un message non vide, meme au vert, part en
        // courriel a chaque passage (RunHealthChecksCommand filtre sur le message, pas sur le
        // statut, tant que only_on_failure reste a false). Le detail vit dans meta().
        return $result->ok();
    }

    /**
     * Echec repete plutot qu'incident isole : on ne parle qu'a partir du Nieme d'affilee.
     *
     * @param  array<string, mixed>  $meta
     */
    private function echecTransitoire(Result $result, array $meta, string $cause): Result
    {
        $echecs = (int) Cache::get($this->cleEchecs(), 0) + 1;
        Cache::forever($this->cleEchecs(), $echecs);

        $meta['echecs_consecutifs'] = $echecs;
        $result = $result->meta($meta);

        if ($echecs >= (int) config('health.openrouter.warn_after_consecutive_failures', 3)) {
            return $result->warning("Crédit OpenRouter non mesurable : {$cause} {$echecs} fois d'affilée.");
        }

        return $result->ok();
    }

    /**
     * Autonomie estimee, deduite de la baisse REELLE du solde entre deux mesures.
     *
     * Renvoie null - donc aucun verdict fonde sur l'autonomie - dans tous les cas ou la mesure
     * ne veut rien dire : premiere mesure, intervalle trop court pour que le bruit s'efface, ou
     * solde qui MONTE (une recharge n'est pas une consommation negative). Un signal absent vaut
     * mieux qu'un signal invente.
     *
     * @param  array{t: int, restant: float, total: float, consomme: float, jours: float|null}|null  $precedente
     */
    private function autonomieEnJours(?array $precedente, float $restant, int $maintenant): ?float
    {
        if ($precedente === null) {
            return null;
        }

        $heures = ($maintenant - $precedente['t']) / 3600;
        $brule = $precedente['restant'] - $restant;

        if ($heures < 0.5 || $brule <= 0) {
            return null;
        }

        $parJour = $brule / $heures * 24;

        return $parJour > 0 ? round($restant / $parJour, 1) : null;
    }

    /**
     * @return array{t: int, restant: float, total: float, consomme: float, jours: float|null}|null
     */
    private function derniereMesure(): ?array
    {
        $mesure = Cache::get($this->cleMesure());

        // Un cache peut contenir une entree d'une version anterieure, ou avoir ete vide entre
        // deux passages : on ne fait confiance qu'a une forme integralement verifiee.
        if (! is_array($mesure)) {
            return null;
        }

        foreach (['t', 'restant', 'total', 'consomme'] as $champ) {
            if (! isset($mesure[$champ]) || ! is_numeric($mesure[$champ])) {
                return null;
            }
        }

        $jours = $mesure['jours'] ?? null;

        return [
            't' => (int) $mesure['t'],
            'restant' => (float) $mesure['restant'],
            'total' => (float) $mesure['total'],
            'consomme' => (float) $mesure['consomme'],
            'jours' => is_numeric($jours) ? (float) $jours : null,
        ];
    }

    private function resume(float $restant, ?float $jours): string
    {
        $resume = $this->montant($restant).' restants';

        if ($jours !== null) {
            $resume .= ', soit environ '.$this->nombre($jours)." jours d'autonomie au rythme actuel";
        }

        return $resume.'.';
    }

    private function montant(float $valeur): string
    {
        return number_format($valeur, 2, ',', ' ').' $';
    }

    private function nombre(float $valeur): string
    {
        return number_format($valeur, 1, ',', ' ');
    }

    private function intervalleInterrogation(): int
    {
        return max(60, (int) config('health.openrouter.poll_seconds', 1800));
    }

    private function cleEchecs(): string
    {
        return (string) config('health.openrouter.connection_failures_cache_key', 'health:openrouter:echecs_consecutifs');
    }

    private function cleMesure(): string
    {
        return (string) config('health.openrouter.measurement_cache_key', 'health:openrouter:derniere_mesure');
    }
}
