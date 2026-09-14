<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: resynchronise titre et langue des ressources vidéo depuis YouTube.
 * MCP: hermes → codex (squelette), corrigé ici sur un point qui rendait la commande inopérante.
 * RAISON: 37 titres français étaient stockés traduits en anglais - mesuré le 2026-09-14, l'API
 *         YouTube renvoie bien le titre français. La base mentait, pas la source.
 */

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Services\YouTubeService;

class ResyncVideoTitlesCommand extends Command
{
    protected $signature = 'directory:resync-video-titles {--apply : écrire réellement} {--limit=0 : plafond de ressources traitées, 0 = toutes} {--depublier-hors-langue : retire de l\'affichage les vidéos ni françaises ni anglaises}';

    protected $description = 'Resynchronise le titre et la langue des ressources vidéo depuis YouTube';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $depublier = (bool) $this->option('depublier-hors-langue');
        $limit = (int) $this->option('limit');

        $examinees = 0;
        $divergentes = 0;
        $disparues = 0;
        $ignorees = 0;
        $ecrites = 0;
        $horsLangue = 0;
        $depubliees = 0;
        $idsDepubliees = [];

        $exemplesDisparues = [];
        $exemplesDivergentes = [];
        $exemplesHorsLangue = [];

        $requete = ToolResource::query()
            ->whereNotNull('video_id')
            ->where('video_id', '<>', '')
            ->orderBy('id');

        if ($limit > 0) {
            $requete->limit($limit);
        }

        $ressources = $requete->get();
        $youtube = app(YouTubeService::class);

        // Lots de 50 : c'est le maximum d'identifiants que l'API YouTube accepte par appel, et
        // getVideoDetails() les concatène tels quels sans jamais découper elle-même.
        foreach ($ressources->chunk(50) as $lot) {
            // getVideoDetails() renvoie une LISTE indexée par entier, pas par identifiant de
            // vidéo. Sans ce keyBy, la recherche échouerait pour chaque ressource et toutes
            // seraient comptées « disparues » - la commande aurait l'air de tourner sans rien
            // corriger. Vérifié dans le service, jamais supposé.
            $details = collect($youtube->getVideoDetails($lot->pluck('video_id')->all()))
                ->keyBy('video_id');

            foreach ($lot as $ressource) {
                $examinees++;

                if (! $details->has($ressource->video_id)) {
                    $disparues++;

                    if (count($exemplesDisparues) < 15) {
                        $exemplesDisparues[] = "#{$ressource->id} : {$ressource->title}";
                    }

                    // Une vidéo absente de la réponse peut être privée, retirée temporairement,
                    // ou bloquée dans un pays. On la SIGNALE, on ne la supprime ni ne la
                    // dépublie jamais : la décision appartient à un humain.
                    continue;
                }

                $donnees = $details->get($ressource->video_id);
                $titreReel = trim((string) ($donnees['title'] ?? ''));

                if ($titreReel === '') {
                    $ignorees++;

                    continue;
                }

                $langueReelle = YouTubeService::detectLanguage($titreReel, $donnees['api_lang'] ?? null);

                // La langue DÉCLARÉE par la vidéo, qui n'est ni fr ni en dans certains cas : une
                // vidéo allemande (defaultLanguage « de-DE ») était publiée sous un titre anglais,
                // mesurée le 2026-09-14. detectLanguage() ne connaît que fr et en, elle ne peut
                // donc pas la signaler - ce compteur le fait, sans rien dépublier : ce que le site
                // affiche est une décision éditoriale, pas une décision de commande.
                $langueApi = strtolower((string) ($donnees['api_lang'] ?? ''));
                if ($langueApi !== '' && ! str_starts_with($langueApi, 'fr') && ! str_starts_with($langueApi, 'en')) {
                    $horsLangue++;

                    if (count($exemplesHorsLangue) < 15) {
                        $exemplesHorsLangue[] = "#{$ressource->id} [{$langueApi}] {$titreReel}";
                    }

                    // Dépublier n'est PAS supprimer : la ligne reste intacte, seul is_approved
                    // passe à false, et les identifiants sont journalisés pour un retour arrière
                    // exact. Une vidéo en hindi ou en vietnamien n'apporte rien à un lecteur
                    // québécois, et un lot de contenu tiers non relu expose au déclassement la
                    // SECTION entière, pas la seule fiche fautive.
                    if ($apply && $depublier && $ressource->is_approved) {
                        $ressource->update(['is_approved' => false]);
                        $depubliees++;
                        $idsDepubliees[] = $ressource->id;
                    }
                }

                if ($titreReel === $ressource->title && $langueReelle === $ressource->language) {
                    continue;
                }

                $divergentes++;

                if (count($exemplesDivergentes) < 15) {
                    $exemplesDivergentes[] = sprintf(
                        '%s [%s]  =>  %s [%s]',
                        $ressource->title,
                        $ressource->language ?? 'langue absente',
                        $titreReel,
                        $langueReelle
                    );
                }

                if ($apply) {
                    $ressource->update(['title' => $titreReel, 'language' => $langueReelle]);
                    $ecrites++;
                }
            }
        }

        $this->table(
            ['Examinées', 'Divergentes', 'Disparues', 'Hors fr/en', 'Dépubliées', 'Ignorées', 'Écrites'],
            [[$examinees, $divergentes, $disparues, $horsLangue, $depubliees, $ignorees, $ecrites]]
        );

        if ($exemplesDivergentes !== []) {
            $this->info('Exemples de titres divergents :');
            foreach ($exemplesDivergentes as $exemple) {
                $this->line('  '.$exemple);
            }
        }

        if ($exemplesDisparues !== []) {
            $this->info('Exemples de vidéos absentes de la réponse YouTube (aucune touchée) :');
            foreach ($exemplesDisparues as $exemple) {
                $this->line('  '.$exemple);
            }
        }

        if ($exemplesHorsLangue !== []) {
            $this->info('Vidéos dont la langue déclarée n\'est ni le français ni l\'anglais (aucune touchée) :');
            foreach ($exemplesHorsLangue as $exemple) {
                $this->line('  '.$exemple);
            }
        }

        if ($idsDepubliees !== []) {
            $fichier = storage_path('app/backups/depubliees-hors-langue-'.date('Ymd-His').'.txt');
            @mkdir(dirname($fichier), 0755, true);
            file_put_contents($fichier, implode("\n", $idsDepubliees)."\n");
            $this->info('Identifiants dépubliés consignés dans : '.$fichier);
            $this->line('  Retour arrière : remettre is_approved à 1 pour ces identifiants.');
        }

        if (! $apply) {
            $this->warn("Rien n'a été écrit. Relancer avec --apply pour enregistrer.");
        }

        return self::SUCCESS;
    }
}
