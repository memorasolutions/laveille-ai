<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Models\NewsletterIssue;

/**
 * One-shot command to update W18 defi. Delete after use.
 */
class UpdateDefiW18Command extends Command
{
    protected $signature = 'newsletter:update-defi-w18';

    protected $description = 'Met à jour le défi W18 (LinkedIn 100% IA enrichi) avec best practices 2026 (one-shot)';

    public function handle(): int
    {
        $issue = NewsletterIssue::where('week_number', 18)->where('year', 2026)->first();
        if (! $issue) {
            $this->error('Aucune issue W18/2026');

            return self::FAILURE;
        }

        $content = $issue->content ?? [];

        // Terme IA W18 = Gartner (id 183 confirmé prod, fallback like %gartner% si id introuvable)
        $gartnerTerm = \Modules\Dictionary\Models\Term::find(183);
        if (! $gartnerTerm) {
            $gartnerTerm = \Modules\Dictionary\Models\Term::where('slug', 'like', '%gartner%')->first();
        }
        if ($gartnerTerm) {
            $content['term_id'] = $gartnerTerm->id;
        }

        $content['weekly_prompt'] = [
            'intro' => 'Ce prompt te génère ton premier post LinkedIn percutant en moins de 2 minutes — copie, personnalise, publie.',
            'cta_intro' => '💡 Pour construire d\'autres prompts personnalisés (au-delà de ce défi), utilise notre constructeur de prompts :',
            'cta_label' => 'Découvrir le constructeur de prompts →',
            'prompt' => "Écris un post LinkedIn percutant en deux parties :\n\n1. Hook (max 210 caractères) : intrigue avec « problème > promesse » ou anecdote perso.\n2. Corps (300-500 caractères) : raconte une petite victoire ou leçon de la semaine en tant que [Prénom], entrepreneur(e) à [Ville du Québec] dans [mon secteur].\n\nUtilise un ton humain, une touche d'autodérision, des paragraphes aérés (1-2 lignes max), et 1-2 emojis sobres.\n\nTermine par un CTA qualitatif (ex. : « Raconte-moi ton cas »).\n\nPas de lien, pas de jargon, aligné sur mes piliers éditoriaux.",
            'technique' => "🎯 Hook calibré 210 caractères = LinkedIn affiche tout sans clic « voir plus » (dwell time max). Corps aéré 300-500 chars = sweet spot 2026. CTA qualitatif (pas « Mettez ❤️ ») = génère des commentaires (15× plus de poids qu'un like dans l'algo LinkedIn 2026).",
            'best_practices' => [
                'title' => '📊 Algorithme LinkedIn 2026 — ce qui marche vraiment',
                'to_do_label' => 'À FAIRE',
                'to_avoid_label' => 'À ÉVITER',
                'to_do' => [
                    'Mettre les liens externes EN COMMENTAIRE (pas dans le post)',
                    'Hook accrocheur ≤ 210 caractères (sans « ... voir plus »)',
                    'Répondre aux commentaires en moins de 2h (+30% engagement)',
                    'CTA qualitatif (« Raconte-moi ton cas ») + emojis sobres',
                    'Publier 80% sur tes 2-3 piliers (ex : ton secteur + IA + Québec)',
                ],
                'to_avoid' => [
                    'Liens dans le post → tue la portée immédiatement',
                    'Hook trop long ou bateau → personne ne clique « voir plus »',
                    'Ignorer les commentaires ou répondre 2 jours après',
                    'CTA générique « Like ❤️ » ou pavés de 10 emojis',
                    'Changer de thème chaque semaine → algo te trouve « illisible »',
                ],
            ],
        ];
        $issue->content = $content;
        $issue->save();

        $this->info('Défi W18 mis à jour avec section Bonnes pratiques 2026 LinkedIn.');

        return self::SUCCESS;
    }
}
