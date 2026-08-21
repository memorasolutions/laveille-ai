<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\GlossaryLinkifier;

/**
 * Audit des COLLISIONS de l'auto-lien (2026-08-21, demande fondateur « mettre un système en place
 * pour que ce soit parfait »).
 *
 * Une collision = un même libellé revendiqué par deux fiches différentes, où l'entrée qui GAGNE
 * l'auto-lien n'est pas celle qui porte exactement ce nom. Trois critères de tri les préviennent
 * déjà automatiquement (longueur, spécificité de stratégie, origine - voir GlossaryLinkifier),
 * mais il reste les conflits que le CODE ne peut pas trancher : deux fiches distinctes qui portent
 * réellement le même nom (vrais doublons éditoriaux). Cette commande les met sous les yeux d'un
 * humain plutôt que de les laisser dormir.
 *
 * Pourquoi une commande et NON un blocage à l'écriture : refuser d'enregistrer un terme dont le
 * nom entre en collision bloquerait des ajouts légitimes et forcerait des titres artificiels
 * (réfutation de Gemini 3.1 Pro, retenue lors du panel du 2026-08-21). Pourquoi pas non plus un
 * test d'intégration continue sur les données réelles : la base de test est vide, et versionner un
 * instantané de la production serait à la fois périmé le lendemain et risqué. L'audit se lance
 * donc à la demande, ou par une tâche planifiée mensuelle.
 *
 * Usage :
 *   php artisan glossary:audit-collisions            # rapport lisible
 *   php artisan glossary:audit-collisions --strict   # code de sortie 1 s'il reste des collisions
 */
class GlossaryAuditCollisionsCommand extends Command
{
    protected $signature = 'glossary:audit-collisions {--strict : sort en échec (code 1) si au moins une collision subsiste} {--limit=50 : nombre maximum de collisions détaillées}';

    protected $description = "Liste les libellés que deux fiches se disputent dans l'auto-lien (glossaire, acronymes, outils).";

    public function handle(): int
    {
        $terms = GlossaryLinkifier::loadTerms();
        $this->line('Entrées de matching analysées : '.count($terms));

        if ($terms === []) {
            $this->warn('Aucune entrée : rien à auditer.');

            return self::SUCCESS;
        }

        $groups = [];
        foreach ($terms as $position => $term) {
            $groups[mb_strtolower($term['name'])][] = ['position' => $position] + $term;
        }

        $multiUrl = 0;
        $collisions = [];

        foreach ($groups as $entries) {
            if (count(array_unique(array_column($entries, 'url'))) < 2) {
                continue; // même destination : aucune ambiguïté pour le lecteur
            }
            $multiUrl++;

            // Une collision que le CODE ne peut pas trancher = deux entrées portant EXACTEMENT le
            // même libellé (même casse) mais visant des fiches différentes. Là, aucun critère de
            // tri ne fait sens : les deux ont la même longueur, souvent la même stratégie, et
            // départager relève de l'éditorial (retirer un alias, ou fusionner deux doublons).
            //
            // 2026-08-21, correction d'un défaut de cette commande elle-même : la première version
            // comparait le gagnant à « la première entrée dont le nom est exact », ce qui signalait
            // à tort des cas DÉJÀ RÉSOLUS (« modèle multimodal » pointe bien vers modele-multimodal
            // depuis le critère d'origine) simplement parce qu'une variante de casse existait
            // ailleurs. Elle gonflait le compte au lieu de le réduire.
            $exactGroups = [];
            foreach ($entries as $entry) {
                $exactGroups[$entry['name']][] = $entry;
            }

            foreach ($exactGroups as $forme => $sameLabel) {
                $urls = array_values(array_unique(array_column($sameLabel, 'url')));
                if (count($urls) < 2) {
                    continue; // un seul libellé exact -> le tri suffit à trancher
                }

                $winner = $this->firstMatching($sameLabel, (string) $forme);
                $others = array_values(array_filter($sameLabel, fn ($e) => $winner && $e['url'] !== $winner['url']));

                $collisions[] = [
                    'forme' => (string) $forme,
                    'gagnant' => $winner ?? $sameLabel[0],
                    'attendu' => $others[0] ?? $sameLabel[0],
                ];
            }
        }

        $this->line('Libellés visant plusieurs destinations : '.$multiUrl);
        $this->line('Collisions à trancher : '.count($collisions));

        if ($collisions === []) {
            $this->info('Aucune collision : chaque libellé pointe vers la fiche qui le porte.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('Ces libellés EXACTS sont portés par deux fiches différentes. Aucun critère de tri ne peut');
        $this->warn('trancher : il faut retirer l\'alias en trop, ou fusionner les fiches doublons (éditorial).');
        $this->newLine();

        foreach (array_slice($collisions, 0, (int) $this->option('limit')) as $collision) {
            $this->line(sprintf(
                '  « %s »  →  %s  [%s, origine %d]   au lieu de  %s  [%s, origine %d]',
                $collision['forme'],
                $collision['gagnant']['url'],
                $collision['gagnant']['match_strategy'] ?? '?',
                $collision['gagnant']['origin_rank'] ?? GlossaryLinkifier::ORIGIN_DERIVED_ALIAS,
                $collision['attendu']['url'],
                $collision['attendu']['match_strategy'] ?? '?',
                $collision['attendu']['origin_rank'] ?? GlossaryLinkifier::ORIGIN_DERIVED_ALIAS,
            ));
        }

        if (count($collisions) > (int) $this->option('limit')) {
            $this->line('  ... et '.(count($collisions) - (int) $this->option('limit')).' autres (voir --limit).');
        }

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Rejoue la sélection réelle du linkifier : la PREMIÈRE entrée du tableau trié dont la
     * stratégie accepte cette forme exacte l'emporte.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, mixed>|null
     */
    private function firstMatching(array $entries, string $forme): ?array
    {
        foreach ($entries as $entry) {
            $strategy = $entry['match_strategy'] ?? 'loose';
            $name = (string) $entry['name'];

            $matches = match ($strategy) {
                'loose' => mb_strtolower($name) === mb_strtolower($forme),
                'partial_case_sensitive' => mb_substr($name, 1) === mb_substr($forme, 1)
                    && mb_strtolower(mb_substr($name, 0, 1)) === mb_strtolower(mb_substr($forme, 0, 1)),
                default => $name === $forme,
            };

            if ($matches) {
                return $entry;
            }
        }

        return null;
    }
}
