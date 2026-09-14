<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: resout les URL de l'annuaire qui pointent vers producthunt.com au lieu du site reel.
 * MCP: squelette genere par hermes (model_invoke code), corrige ici - le garde-fou anti-bannissement
 *      etait inoperant : une resolution nulle etait comptee « ignoree » et REMETTAIT le compteur
 *      d'echecs a zero, donc l'arret automatique ne se serait jamais declenche.
 * RAISON: 260 fiches, 254 publiees, 3011 clics sortants qui partent vers ProductHunt plutot que
 *         vers l'outil que le visiteur cherchait.
 */

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ToolDiscoveryService;

class ResolveProductHuntUrlsCommand extends Command
{
    protected $signature = 'directory:resolve-producthunt
                            {--limit=0 : Nombre maximum de fiches traitées (0 = aucun plafond)}
                            {--pause=1500 : Pause entre deux appels réseau, en millisecondes}
                            {--max-echecs=5 : Arrêt après N échecs consécutifs}
                            {--appliquer : Écrit réellement (sinon simulation)}';

    protected $description = "Résout les URL producthunt.com de l'annuaire vers le site réel de l'outil";

    public function __construct(private readonly ToolDiscoveryService $decouverte)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limite = (int) $this->option('limit');
        $pause = max(0, (int) $this->option('pause'));
        $maxEchecs = max(1, (int) $this->option('max-echecs'));
        $simulation = ! $this->option('appliquer');

        $requete = Tool::query()->where('url', 'like', '%producthunt.com%')->orderBy('id');
        if ($limite > 0) {
            $requete->limit($limite);
        }

        $fiches = $requete->get();
        $total = $fiches->count();

        if ($total === 0) {
            $this->info('Aucune fiche ne pointe vers producthunt.com : rien à faire.');

            return self::SUCCESS;
        }

        $this->info($simulation
            ? "SIMULATION sur {$total} fiche(s). Rien ne sera écrit. Ajouter --appliquer pour exécuter."
            : "ÉCRITURE sur {$total} fiche(s), pause de {$pause} ms entre deux appels.");

        $barre = $this->output->createProgressBar($total);
        $barre->start();

        $traitees = 0;
        $resolues = 0;
        $echecs = 0;
        $consecutifs = 0;
        $arretePrecoce = false;

        foreach ($fiches as $fiche) {
            // Etranglement : ProductHunt a deja banni cette IP par sur-sollicitation. La pause
            // n'est pas une politesse, c'est ce qui permet a la campagne d'aller au bout.
            if ($traitees > 0 && $pause > 0) {
                usleep($pause * 1000);
            }

            $traitees++;
            $barre->advance();

            try {
                $nouvelle = $this->decouverte->resolveProductHuntUrl((string) $fiche->url);
            } catch (\Throwable $e) {
                $nouvelle = null;
                Log::warning('directory:resolve-producthunt - exception', [
                    'fiche' => $fiche->id, 'erreur' => $e->getMessage(),
                ]);
            }

            // Une URL qui contient ENCORE producthunt.com est un faux succes : on la traite
            // comme un echec, sinon on ecraserait une adresse par une adresse equivalente.
            $echouee = $nouvelle === null
                || $nouvelle === ''
                || stripos($nouvelle, 'producthunt.com') !== false;

            if ($echouee) {
                $echecs++;
                $consecutifs++;

                // LE garde-fou : une serie d'echecs ne vient pas des fiches, elle vient du jeton
                // ou d'un bannissement. Poursuivre ne ferait qu'aggraver les deux.
                if ($consecutifs >= $maxEchecs) {
                    $arretePrecoce = true;
                    break;
                }

                continue;
            }

            $consecutifs = 0;

            if (! $simulation) {
                // save() et JAMAIS DB::table()->update() : le modele porte Spatie LogsActivity
                // avec 'url' dans logOnly, donc chaque changement est historise avec son ancienne
                // valeur. Une ecriture directe contournerait l'historique et supprimerait le
                // seul moyen de revenir en arriere.
                $fiche->url = $nouvelle;
                $fiche->save();
            }

            $resolues++;
        }

        $barre->finish();
        $this->newLine(2);

        if ($arretePrecoce) {
            $this->error("ARRÊT PRÉCOCE : {$maxEchecs} échecs consécutifs. Vérifier le jeton ProductHunt "
                .'ou un bannissement d\'IP avant de relancer.');
        }

        $this->table(
            ['Traitées', $simulation ? 'Seraient résolues' : 'Résolues', 'Échecs', 'Restantes'],
            [[$traitees, $resolues, $echecs, max(0, $total - $traitees)]]
        );

        Log::info('directory:resolve-producthunt', [
            'traitees' => $traitees, 'resolues' => $resolues,
            'echecs' => $echecs, 'arret_precoce' => $arretePrecoce, 'simulation' => $simulation,
        ]);

        return $arretePrecoce ? self::FAILURE : self::SUCCESS;
    }
}
