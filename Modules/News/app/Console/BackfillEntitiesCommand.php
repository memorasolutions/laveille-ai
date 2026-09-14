<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: extrait les entites nommees des fiches qui n'en ont aucune.
 * MCP: squelette genere par hermes (model_invoke code), corrige ici sur le plafond.
 * RAISON: le regroupement par entite existe et fonctionne, mais ne couvre que 311 fiches sur
 *         5091 - et AUCUNE des 1865 fiches faibles. Sans entites, pas de dossiers thematiques,
 *         donc pas de regroupement, donc pas de sortie du « contenu a faible valeur ».
 */

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

class BackfillEntitiesCommand extends Command
{
    protected $signature = 'news:backfill-entities
        {--limit=0 : Nombre maximal de fiches examinées (0 = aucun plafond)}
        {--appliquer : Écrit réellement (sinon simulation)}
        {--min-entites=1 : Nombre minimal d\'entités détectées pour écrire}';

    protected $description = "Extrait les entités nommées des fiches d'actualité qui n'en ont aucune";

    /**
     * Liste blanche. On ne devine pas les entites : on cherche celles qu'on connait.
     *
     * LE PIEGE, MESURE ET NON THEORIQUE : une analyse deleguee a compte « Intel : 85 occurrences »
     * avant de comprendre que « Intel » est une sous-chaine d'« intelligence ». De meme « meta »
     * vit dans « metadata ». Chaque variante exige donc une FRONTIERE DE MOT - sans quoi le
     * regroupement serait fausse des le depart, et invisible a la relecture.
     *
     * @var array<string, array<int, string>>
     */
    private const ENTITES = [
        'OpenAI' => ['openai', 'open ai'],
        'ChatGPT' => ['chatgpt', 'chat gpt'],
        'Anthropic' => ['anthropic'],
        'Claude' => ['claude'],
        'Google' => ['google'],
        'Gemini' => ['gemini'],
        'Meta' => ['meta', 'facebook', 'instagram'],
        'Microsoft' => ['microsoft', 'copilot'],
        'Apple' => ['apple'],
        'Amazon' => ['amazon', 'aws'],
        'Nvidia' => ['nvidia'],
        'Mistral' => ['mistral'],
        'xAI' => ['xai', 'grok'],
        'Perplexity' => ['perplexity'],
        'Hugging Face' => ['hugging face', 'huggingface'],
    ];

    public function handle(): int
    {
        $limite = max(0, (int) $this->option('limit'));
        $simulation = ! $this->option('appliquer');
        $minEntites = max(1, (int) $this->option('min-entites'));

        $base = fn () => NewsArticle::query()->whereNull('retired_at')->whereDoesntHave('entities');

        $total = $base()->count();
        if ($limite > 0) {
            $total = min($total, $limite);
        }

        if ($total === 0) {
            $this->info("Aucune fiche sans entité : rien à faire.");

            return self::SUCCESS;
        }

        $this->info($simulation
            ? "SIMULATION sur {$total} fiche(s). Rien ne sera écrit. Ajouter --appliquer pour exécuter."
            : "ÉCRITURE sur {$total} fiche(s), minimum {$minEntites} entité(s) par fiche.");

        $examinees = $avecEntites = $ecrites = $sansEntite = 0;
        $compteur = [];

        $barre = $this->output->createProgressBar($total);
        $barre->start();

        // chunkById IGNORE un limit() pose sur la requete : il repagine lui-meme par id.
        // Le plafond doit donc etre applique DANS la boucle, sinon --limit=10 traiterait
        // les 4780 fiches - exactement le genre d'option qui ment sur ce qu'elle fait.
        $base()->select('id', 'title')->chunkById(200, function ($lot) use (
            &$examinees, &$avecEntites, &$ecrites, &$sansEntite, &$compteur,
            $barre, $simulation, $minEntites, $limite
        ) {
            foreach ($lot as $fiche) {
                if ($limite > 0 && $examinees >= $limite) {
                    return false;
                }

                $examinees++;
                $barre->advance();

                $trouvees = $this->detecter((string) $fiche->title);

                if ($trouvees === []) {
                    $sansEntite++;

                    continue;
                }

                $avecEntites++;
                foreach ($trouvees as $e) {
                    $compteur[$e] = ($compteur[$e] ?? 0) + 1;
                }

                if (count($trouvees) < $minEntites) {
                    continue;
                }

                if (! $simulation) {
                    $fiche->syncEntities($trouvees);
                    $ecrites++;
                }
            }

            return ! ($limite > 0 && $examinees >= $limite);
        });

        $barre->finish();
        $this->newLine(2);

        $this->table(
            ['Examinées', 'Avec entités', $simulation ? 'Seraient écrites' : 'Écrites', 'Sans aucune entité'],
            [[$examinees, $avecEntites, $simulation ? $avecEntites : $ecrites, $sansEntite]]
        );

        if ($compteur !== []) {
            arsort($compteur);
            $this->info('Entités les plus détectées :');
            $lignes = [];
            foreach (array_slice($compteur, 0, 12, true) as $nom => $n) {
                $lignes[] = [$nom, $n];
            }
            $this->table(['Entité', 'Fiches'], $lignes);
        }

        Log::info('news:backfill-entities', [
            'examinees' => $examinees, 'avec_entites' => $avecEntites,
            'ecrites' => $ecrites, 'sans_entite' => $sansEntite, 'simulation' => $simulation,
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function detecter(string $titre): array
    {
        $trouvees = [];

        foreach (self::ENTITES as $canonique => $variantes) {
            foreach ($variantes as $variante) {
                if (preg_match('/\b'.preg_quote($variante, '/').'\b/iu', $titre) === 1) {
                    $trouvees[] = $canonique;
                    break;
                }
            }
        }

        return $trouvees;
    }
}
