<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: marque retroactivement les actualites deja publiees sur Facebook ou LinkedIn.
 * MCP: squelette genere par hermes (model_invoke code), corrige sur trois points ici.
 * RAISON: 6337 actualites publiees, 12 marquees LinkedIn, 0 marquee Facebook. L'admin ne peut
 *         donc pas savoir ce qui est deja parti, et republie en double.
 */

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

class BackfillSocialSharesCommand extends Command
{
    protected $signature = 'news:backfill-social-shares
        {fichier : Fichier de relevé, une ligne « slug|AAAA-MM-JJ »}
        {--plateforme= : facebook ou linkedin}
        {--appliquer : Écrit réellement (sinon simulation)}
        {--ecraser : Autorise à remplacer une date déjà posée}';

    protected $description = 'Marque rétroactivement les actualités publiées sur Facebook ou LinkedIn';

    private const PLATEFORMES = ['facebook', 'linkedin'];

    public function handle(): int
    {
        $plateforme = (string) $this->option('plateforme');
        if (! in_array($plateforme, self::PLATEFORMES, true)) {
            $this->error("--plateforme est obligatoire et vaut « facebook » ou « linkedin ». Reçu : « {$plateforme} ».");

            return self::FAILURE;
        }

        $fichier = (string) $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }

        $lignes = file($fichier, FILE_IGNORE_NEW_LINES);
        if ($lignes === false) {
            $this->error("Lecture impossible : {$fichier}");

            return self::FAILURE;
        }

        $simulation = ! $this->option('appliquer');
        $ecraser = (bool) $this->option('ecraser');
        $colonne = $plateforme.'_shared_at';

        $lues = $marquees = $deja = $introuvables = $rejetees = 0;
        $problemes = [];

        foreach ($lignes as $i => $brute) {
            $ligne = trim($brute);

            // Les lignes vides et les commentaires ne sont pas des entrees : elles ne
            // comptent pas comme « lues », sinon le total final ne veut plus rien dire.
            if ($ligne === '' || str_starts_with($ligne, '#')) {
                continue;
            }

            $lues++;
            $numero = $i + 1;

            $parts = explode('|', $ligne);
            if (count($parts) !== 2) {
                $rejetees++;
                $problemes[] = "  ligne {$numero} : format attendu « slug|AAAA-MM-JJ »";

                continue;
            }

            $slug = trim($parts[0]);
            $texteDate = trim($parts[1]);

            // createFromFormat seul accepte des absurdites comme 2026-13-45 en les reportant
            // sur le mois suivant. On exige donc que la date RELUE soit identique a l'entree.
            $date = Carbon::createFromFormat('Y-m-d', $texteDate);
            if ($date === false || $date->format('Y-m-d') !== $texteDate) {
                $rejetees++;
                $problemes[] = "  ligne {$numero} : date invalide « {$texteDate} »";

                continue;
            }

            // Midi plutot que minuit : une date sans heure bascule d'un jour selon le fuseau,
            // et on afficherait alors une publication la veille de sa parution.
            $date = $date->setTime(12, 0, 0);

            $fiche = NewsArticle::query()->where('slug', $slug)->first();
            if ($fiche === null) {
                $introuvables++;
                $problemes[] = "  ligne {$numero} : slug introuvable « {$slug} »";

                continue;
            }

            if ($fiche->{$colonne} !== null && ! $ecraser) {
                $deja++;

                continue;
            }

            if (! $simulation) {
                // forceFill : ces colonnes ne sont pas dans $fillable, et c'est voulu - elles ne
                // doivent jamais etre renseignees par une requete utilisateur.
                $fiche->forceFill([$colonne => $date])->save();
            }

            $marquees++;
        }

        $this->newLine();
        $this->info($simulation
            ? "SIMULATION ({$plateforme}) : rien n'a été écrit. Ajouter --appliquer pour exécuter."
            : "ÉCRITURE ({$plateforme}) terminée.");

        $this->table(
            ['Entrées lues', $simulation ? 'Seraient marquées' : 'Marquées', 'Déjà marquées', 'Slugs introuvables', 'Lignes rejetées'],
            [[$lues, $marquees, $deja, $introuvables, $rejetees]]
        );

        // Le detail seulement en cas d'anomalie, et plafonne : une sortie de 300 lignes
        // n'est pas un rapport, c'est du bruit qui cache les vrais problemes.
        if ($problemes !== []) {
            $this->warn('Anomalies ('.count($problemes).' au total, 15 premières) :');
            foreach (array_slice($problemes, 0, 15) as $p) {
                $this->line($p);
            }
        }

        Log::info('news:backfill-social-shares', [
            'plateforme' => $plateforme, 'simulation' => $simulation,
            'lues' => $lues, 'marquees' => $marquees, 'deja' => $deja,
            'introuvables' => $introuvables, 'rejetees' => $rejetees,
        ]);

        return self::SUCCESS;
    }
}
