<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

/**
 * Rendement reel de chaque source d'actualites : volume, publications, delai de collecte.
 *
 * RAISON D'ETRE : l'ecran de composition liste des candidats, mais rien ne disait quelles
 * sources RAPPORTENT vraiment. Une source qui verse cinquante articles par semaine dont aucun
 * n'est jamais publie coute du temps de tri a chaque passage, sans rien produire ; une source
 * silencieuse depuis des semaines occupe une ligne pour rien. Sans mesure, on arbitre a
 * l'intuition, et l'intuition surestime toujours ce qui fait du bruit.
 *
 * LECTURE SEULE, sans exception : aucune ecriture, aucune suppression. La commande DIT, elle
 * ne decide pas - c'est a l'humain d'activer ou de retirer une source.
 *
 * Le delai mesure est celui de NOTRE collecte : ecart entre la date de publication annoncee par
 * la source (`pub_date`) et le moment ou nous l'avons recoltee (`created_at`). Il ne mesure donc
 * pas la vitesse de la source elle-meme, mais celle de la chaine complete telle qu'on la subit.
 */
final class SourcesReportCommand extends Command
{
    /**
     * Plafond d'echantillon pour la mediane, PAR SOURCE. Le pipeline collecte en continu :
     * charger quatre-vingt-dix jours d'articles pour toutes les sources d'un coup suffirait a
     * epuiser la memoire d'un hebergement mutualise. Une mediane sur les 2 000 plus recents dit
     * la meme chose que sur la totalite, et le rapport annonce quand l'echantillon a ete borne.
     */
    private const ECHANTILLON_MAX = 2000;

    protected $signature = 'news:sources-report {--jours=90 : Fenêtre d\'observation en jours}';

    protected $description = 'Rapport de rendement des sources d\'actualités : volume, publications, délai de collecte.';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('jours'));
        $depuis = now()->subDays($jours);

        // Tous les comptages se font en SQL, en UNE requete : aucun modele d'article n'est
        // charge en memoire pour cette partie.
        $sources = NewsSource::query()
            ->withCount([
                'articles as collectes' => fn (Builder $q) => $q->where('created_at', '>=', $depuis),
                'articles as publiees' => fn (Builder $q) => $q->where('created_at', '>=', $depuis)
                    ->where('is_published', true)->whereNull('retired_at'),
                'articles as retirees' => fn (Builder $q) => $q->where('created_at', '>=', $depuis)
                    ->whereNotNull('retired_at'),
                'articles as composees' => fn (Builder $q) => $q->where('created_at', '>=', $depuis)
                    ->whereNotNull('reviewed_at'),
            ])
            ->orderBy('name')
            ->get();

        $lignes = [];
        $muettes = [];
        $steriles = [];
        $borne = false;
        $meilleurTaux = null;
        $meilleurDelai = null;

        foreach ($sources as $source) {
            [$delai, $echantillonBorne] = $this->delaiMedianEnMinutes((int) $source->id, $depuis);
            $borne = $borne || $echantillonBorne;

            $dernier = NewsArticle::where('news_source_id', $source->id)->max('created_at');
            $actif = (bool) $source->active;
            $taux = $source->collectes > 0 ? $source->publiees / $source->collectes * 100 : null;

            $lignes[] = [
                mb_strimwidth((string) $source->name, 0, 30, '…'),
                $actif ? 'actif' : 'INACTIF',
                $source->collectes,
                $source->publiees,
                $taux === null ? '-' : $this->nombre($taux, 1).' %',
                $source->composees,
                $source->retirees,
                $this->delai($delai),
                $dernier ? substr((string) $dernier, 0, 16) : 'jamais',
            ];

            if (! $actif) {
                continue;
            }

            if ($source->collectes === 0) {
                $muettes[] = (string) $source->name;
            } elseif ($source->publiees === 0) {
                $steriles[] = (string) $source->name;
            }

            if ($taux !== null && ($meilleurTaux === null || $taux > $meilleurTaux[1])) {
                $meilleurTaux = [(string) $source->name, $taux];
            }

            if ($delai !== null && ($meilleurDelai === null || $delai < $meilleurDelai[1])) {
                $meilleurDelai = [(string) $source->name, $delai];
            }
        }

        $this->info("Fenêtre d'observation : {$jours} jours, depuis le ".$depuis->format('Y-m-d').".");
        $this->newLine();
        $this->table(
            ['Source', 'État', 'Collectés', 'Publiés', 'Taux', 'Composés', 'Retirés', 'Délai médian', 'Dernier'],
            $lignes
        );

        $this->newLine();
        $this->info('Constats');

        $this->line($muettes === []
            ? '  Sources actives sans aucune collecte : aucune.'
            : '  Sources actives sans aucune collecte sur la fenêtre : '.implode(', ', $muettes).'.');

        $this->line($steriles === []
            ? '  Sources actives qui collectent sans jamais aboutir à une publication : aucune.'
            : '  Sources actives qui collectent sans jamais aboutir à une publication : '.implode(', ', $steriles).'.');

        $this->line($meilleurTaux === null
            ? '  Meilleur taux de publication : non calculable, aucune source active n\'a collecté.'
            : '  Meilleur taux de publication : '.$meilleurTaux[0].' ('.$this->nombre($meilleurTaux[1], 1).' %).');

        $this->line($meilleurDelai === null
            ? '  Délai de collecte le plus court : non calculable, aucune date de publication exploitable.'
            : '  Délai de collecte le plus court : '.$meilleurDelai[0].' ('.$this->delai($meilleurDelai[1]).').');

        if ($borne) {
            $this->newLine();
            $this->comment('  Note : au moins une médiane a été calculée sur un échantillon borné à '
                .self::ECHANTILLON_MAX.' articles récents, pas sur la totalité de la fenêtre.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: float|null, 1: bool} la mediane en minutes, et si l'echantillon a ete borne
     */
    private function delaiMedianEnMinutes(int $sourceId, \DateTimeInterface $depuis): array
    {
        $lignes = NewsArticle::query()
            ->where('news_source_id', $sourceId)
            ->where('created_at', '>=', $depuis)
            ->whereNotNull('pub_date')
            ->orderByDesc('created_at')
            ->limit(self::ECHANTILLON_MAX)
            ->get(['pub_date', 'created_at']);

        $ecarts = [];

        foreach ($lignes as $ligne) {
            // Difference calculee sur les horodatages bruts plutot que par diffInMinutes, dont
            // le SIGNE a change entre deux versions majeures de Carbon : ici l'intention est
            // explicite et ne depend d'aucune version.
            $ecart = ($ligne->created_at->getTimestamp() - $ligne->pub_date->getTimestamp()) / 60;

            // Un ecart negatif signifie une date de publication dans le futur, annoncee par la
            // source : c'est une donnee fausse, pas un delai. On l'ecarte au lieu de la moyenner.
            if ($ecart >= 0) {
                $ecarts[] = $ecart;
            }
        }

        if ($ecarts === []) {
            return [null, false];
        }

        sort($ecarts);
        $n = count($ecarts);
        $milieu = intdiv($n, 2);
        $mediane = $n % 2 === 0 ? ($ecarts[$milieu - 1] + $ecarts[$milieu]) / 2 : $ecarts[$milieu];

        return [(float) $mediane, $lignes->count() >= self::ECHANTILLON_MAX];
    }

    private function delai(?float $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        if ($minutes < 60) {
            return round($minutes).' min';
        }

        if ($minutes < 60 * 48) {
            return $this->nombre($minutes / 60, 1).' h';
        }

        return $this->nombre($minutes / 1440, 1).' j';
    }

    private function nombre(float $valeur, int $decimales): string
    {
        return number_format($valeur, $decimales, ',', ' ');
    }
}
