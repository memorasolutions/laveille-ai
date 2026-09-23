<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Directory\Models\Tool;

/**
 * Commande de LECTURE SEULE : compare le statut de cycle de vie AFFICHÉ sur chaque fiche
 * (lifecycle_status, choisi par un éditeur) au dernier code HTTP réellement mesuré
 * (url_last_status, écrit par directory:check-links) et signale les fiches où les deux se
 * contredisent. Elle ne corrige RIEN : une reclassification de statut est une décision
 * éditoriale, elle ne s'automatise pas.
 *
 * ORIGINE (v1.295.0, 2026-09-23) : la fiche Headroom affichait « Cette plateforme a fermé ses
 * portes. » (lifecycle_status='closed') alors que le service répondait 401 avec « walls.sh is
 * private » (url_last_status='401', déjà classé AMBIGUS par CheckLinksCommand) - il n'était pas
 * disparu, il était devenu privé. Le défaut a été trouvé par hasard, en préparant une
 * publication : rien ne le détectait tout seul. Cette commande comble ce trou.
 *
 * RÉUTILISE les constantes DISPARU et AMBIGUS de CheckLinksCommand (rendues publiques pour
 * l'occasion) plutôt que de les redéfinir : DRY, et surtout, une seule source de vérité sur ce
 * qu'un code HTTP veut dire pour ce projet - les regles du projet l'exigent pour une
 * connaissance métier comme celle-ci.
 *
 * Portée : Tool::published() uniquement, comme HealthCheckReportCommand - c'est le statut
 * réellement AFFICHÉ au public qui est en cause ici, pas l'état interne d'une fiche en brouillon.
 */
class CheckLifecycleConsistencyCommand extends Command
{
    protected $signature = 'directory:check-lifecycle-consistency
                            {--limit= : Nombre maximum de fiches à examiner (sous-ensemble)}
                            {--fail-on-contradiction : Sortie en échec (code 1) si au moins une contradiction est trouvée - prévu pour un futur planificateur/monitoring, jamais invoqué automatiquement par cette commande elle-même}';

    protected $description = 'Signale les fiches dont le statut de cycle de vie affiché contredit le dernier code HTTP mesuré (lecture seule, ne corrige rien).';

    /**
     * Nombre d'échecs FRANCS consécutifs (url_failure_streak, famille DISPARU uniquement - voir
     * CheckLinksCommand::recordCheckResult()) à partir duquel un lifecycle_status='active' est
     * jugé contredit par la mesure. directory:check-links tourne une fois par semaine et SANS
     * --fix (routes/console.php, dimanche 05h45 UTC) : un seuil de 3 correspond donc à environ
     * trois semaines consécutives où l'adresse a répondu 404/410 - assez pour écarter un accident
     * isolé (panne d'une nuit, faux positif de cadence), pas assez pour laisser une fiche morte
     * des mois avant d'être signalée.
     */
    private const SEUIL_ECHEC_DURABLE = 3;

    public function handle(): int
    {
        $query = Tool::published()
            ->whereNotNull('url_last_status')
            ->select(['id', 'name', 'url', 'lifecycle_status', 'url_last_status', 'url_last_note', 'url_failure_streak', 'url_last_checked_at']);

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $tools = $query->get();
        $contradictions = [];

        foreach ($tools as $tool) {
            $contradiction = $this->detecter($tool);

            if ($contradiction !== null) {
                $contradictions[] = $contradiction;
            }
        }

        $this->info("Fiches examinées (statut affiché vs dernier code HTTP mesuré) : {$tools->count()}.");

        if ($contradictions === []) {
            $this->info('Aucune contradiction détectée : le statut affiché concorde avec le dernier code HTTP mesuré sur toutes les fiches examinées.');

            return self::SUCCESS;
        }

        $rows = array_map(fn (array $c): array => [
            $c['id'], $c['fiche'], $c['url'], $c['affiche'], $c['mesure'], $c['suggestion'], $c['mesure_le'],
        ], $contradictions);

        $this->table(['ID', 'Fiche', 'URL', 'Statut affiché', 'Dernier code mesuré', 'Suggestion', 'Mesuré le'], $rows);

        // Doublon volontaire du tableau ci-dessus, en lignes PLEINES et COURTES (une donnée par
        // ligne) - jamais coupées par le retour à la ligne automatique des cellules du tableau,
        // sensible à la largeur détectée du terminal (un tableau à 7 colonnes se compresse fort
        // dans un terminal étroit ou une sortie non interactive, et un mot peut alors se
        // retrouver seul sur sa propre ligne de cellule). Sert aussi à un futur script de
        // surveillance qui grepperait la sortie plutôt que de parser un tableau à bordures -
        // même convention que HealthCheckReportCommand.
        foreach ($contradictions as $c) {
            $this->line("  - #{$c['id']} {$c['fiche']} ({$c['url']})");
            $this->line("      statut affiché : {$c['affiche']}");
            $this->line("      dernier code mesuré : {$c['mesure']}");
            $this->line("      suggestion : {$c['suggestion']} (mesuré le {$c['mesure_le']})");
        }

        $nombre = count($contradictions);
        $pluriel = $nombre > 1 ? 's' : '';
        $this->error("{$nombre} contradiction{$pluriel} détectée{$pluriel} - AUCUNE correction automatique appliquée, reclassification à faire à la main dans l'admin.");

        if ($this->option('fail-on-contradiction')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Applique les règles de contradiction à une fiche et renvoie le détail à afficher, ou null
     * si rien ne cloche. Un code HTTP non numérique (TIMEOUT/DNS - absence totale de réponse) ne
     * permet de prouver aucune des deux contradictions : on l'ignore plutôt que de deviner.
     */
    private function detecter(Tool $tool): ?array
    {
        $statutHttp = $tool->url_last_status;

        if (! is_numeric($statutHttp)) {
            return null;
        }

        $statutHttp = (int) $statutHttp;
        $mesureLe = $tool->url_last_checked_at?->format('Y-m-d') ?? '?';

        // Contradiction 1 - cas Headroom : la fiche annonce une disparition, mais le code mesuré
        // est AMBIGU (401/402/403/503), donc le service n'est pas forcément mort - il est
        // peut-être seulement devenu privé, protégé ou temporairement indisponible.
        if ($tool->lifecycle_status === Tool::STATUS_CLOSED
            && in_array($statutHttp, CheckLinksCommand::AMBIGUS, true)) {
            $note = $tool->url_last_note ? " (note du contrôle : « {$tool->url_last_note} »)" : '';

            return [
                'id' => $tool->id,
                'fiche' => $tool->name,
                'url' => $tool->url,
                'affiche' => 'Plus en ligne (closed)',
                'mesure' => "{$statutHttp} - ambigu{$note}",
                'suggestion' => 'Accès privé (private)',
                'mesure_le' => $mesureLe,
            ];
        }

        // Contradiction 2 - la fiche annonce un outil vivant, mais le code mesuré est DISPARU
        // (404/410) et l'échec dure (voir SEUIL_ECHEC_DURABLE) : la fiche annonce un outil actif
        // qui ne répond plus depuis plusieurs semaines.
        if ($tool->lifecycle_status === Tool::STATUS_ACTIVE
            && in_array($statutHttp, CheckLinksCommand::DISPARU, true)
            && (int) $tool->url_failure_streak >= self::SEUIL_ECHEC_DURABLE) {
            return [
                'id' => $tool->id,
                'fiche' => $tool->name,
                'url' => $tool->url,
                'affiche' => 'Actif (active)',
                'mesure' => "{$statutHttp} - disparu, {$tool->url_failure_streak} contrôles consécutifs en échec",
                'suggestion' => 'Plus en ligne (closed)',
                'mesure_le' => $mesureLe,
            ];
        }

        // Contradiction 3 - sens inverse du cas Headroom : la fiche annonce un accès privé, mais
        // le dernier contrôle a reçu un succès plein (2xx, sans même un redirect à interpréter) -
        // le service est peut-être redevenu public, ce que 'private' ne dit plus.
        if ($tool->lifecycle_status === Tool::STATUS_PRIVATE
            && $statutHttp >= 200 && $statutHttp < 300) {
            return [
                'id' => $tool->id,
                'fiche' => $tool->name,
                'url' => $tool->url,
                'affiche' => 'Accès privé (private)',
                'mesure' => "{$statutHttp} - réponse pleine, accessible publiquement",
                'suggestion' => 'À reconsidérer, peut-être Actif (active)',
                'mesure_le' => $mesureLe,
            ];
        }

        return null;
    }
}
