<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : porte d'écriture bornée de la RÉTENTION de composition (colonne
 *          `composition_hold_until`, mécanisme imposé le 2026-09-12 après mesure - 17 fiches d'un
 *          lot éditorial en attente de composition ont toutes été supprimées par
 *          `news:prune-drafts`, faute de tout mécanisme distinguant un brouillon orphelin d'un
 *          brouillon RETENU pour un travail en cours). Pose la rétention à maintenant + `--days`
 *          jours (défaut 14, miroir de `NewsArticle::DEFAULT_COMPOSITION_HOLD_DAYS` - source
 *          unique du chiffre, voir son docblock) ; `--release` la remet à `null` plutôt que d'en
 *          poser une - les deux modes sont mutuellement exclusifs, `--release` prime si les deux
 *          sont fournis (aucun payload de date ambigu).
 *
 *          `news:apply --payload` pose cette même rétention AUTOMATIQUEMENT (14 jours) dès qu'un
 *          payload s'applique avec succès (voir la fin de NewsApplyCommand::applyPayload()) : cette
 *          commande sert à la PROLONGER au-delà de 14 jours, à la poser sur une fiche qui n'a reçu
 *          aucun payload, ou à la retirer avant terme (fiche abandonnée, qui redevient purgeable
 *          normalement). Les deux portes délèguent au MÊME point d'écriture du modèle
 *          (`NewsArticle::holdForComposition()`/`releaseCompositionHold()`), jamais deux
 *          implémentations de la même règle.
 *
 *          Sort un JSON `{id, composition_hold_until, released}` sur stdout - même convention que
 *          `news:brief` (lecture) : un appelant automatisé (skill /actu2) doit pouvoir vérifier le
 *          résultat sans reparser un message français.
 * MCP: SELF (<5 lignes utiles)
 * RAISON: design imposé - mécanisme de rétention pour que la purge nocturne ne supprime plus
 *         jamais une fiche sur laquelle un travail éditorial est en cours.
 */
class NewsHoldCommand extends Command
{
    protected $signature = 'news:hold
        {article : id de la fiche news_articles}
        {--days=14 : Nombre de jours de rétention à partir de maintenant (miroir de NewsArticle::DEFAULT_COMPOSITION_HOLD_DAYS)}
        {--release : Retire la rétention (composition_hold_until = null) au lieu d\'en poser une - prime sur --days si les deux sont fournis}';

    protected $description = 'Pose ou retire une rétention de composition sur une fiche (news_articles.composition_hold_until), que news:prune-drafts respecte.';

    public function handle(): int
    {
        $articleId = (int) $this->argument('article');
        $article = NewsArticle::find($articleId);

        if (! $article) {
            $this->error("Fiche introuvable : {$articleId}.");

            return self::FAILURE;
        }

        $release = (bool) $this->option('release');

        if ($release) {
            $article->releaseCompositionHold();
        } else {
            $rawDays = $this->option('days');

            // ACTION : valide la valeur BRUTE de --days (une chaîne venue d'un humain), jamais son
            // cast - (int) "3jours" === 3, donc un contrôle posé APRÈS le cast aurait laissé passer
            // cette faute de frappe. Une valeur acceptable est une suite de chiffres décimaux
            // (jamais vide, jamais négative, jamais décimale) valant au moins 1 ; « 0 », « -3 »,
            // « abc », « 3jours », « 2.5 » et « » sont tous refusés AVANT toute écriture.
            // MCP: SELF (<5 lignes)
            // RAISON: doctrine du projet - une entrée invalide est REFUSÉE explicitement, jamais
            // réinterprétée en silence. Défaut mesuré : --days=0 répondait SUCCESS et posait 1 jour
            // de rétention au lieu de la retirer, l'opérateur croyant avoir supprimé la rétention.
            if (! preg_match('/^\d+$/', (string) $rawDays)
                || (int) $rawDays < 1
                || (int) $rawDays > NewsArticle::MAX_COMPOSITION_HOLD_DAYS) {
                $this->error(
                    "Valeur de --days invalide : « {$rawDays} ». Attendu : un nombre entier de jours ".
                    "entre 1 et ".NewsArticle::MAX_COMPOSITION_HOLD_DAYS." (ex. --days=14). Pour RETIRER une ".
                    "rétention, utilise --release - ".
                    'jamais --days=0, qui pose 1 jour au lieu de la retirer. Rien n\'a été écrit.'
                );

                return self::FAILURE;
            }

            $article->holdForComposition((int) $rawDays);
        }

        // ACTION : SEULE ligne sur stdout (même contrat que news:brief) - aucun message français
        // avant le JSON, pour qu'un appelant automatisé (skill /actu2) puisse le parser sans le
        // reparser au milieu d'un texte.
        // MCP: SELF (<5 lignes)
        // RAISON: design imposé - « Sort un JSON {id, composition_hold_until, released}. »
        $this->line(json_encode([
            'id' => $article->id,
            'composition_hold_until' => $article->composition_hold_until?->toIso8601String(),
            'released' => $release,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
