<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Models\NewsletterIssue;

/**
 * One-shot command to update W16 defi. Delete after use.
 */
class UpdateDefiW16Command extends Command
{
    protected $signature = 'newsletter:update-defi-w16';

    protected $description = 'Met à jour le défi W16 avec le nouveau format court (one-shot)';

    public function handle(): int
    {
        $issue = NewsletterIssue::where('week_number', 16)->where('year', 2026)->first();
        if (! $issue) {
            $this->error('Aucune issue W16/2026');

            return self::FAILURE;
        }

        $content = $issue->content ?? [];
        $content['weekly_prompt'] = [
            'prompt' => "Tu es un assistant personnel expert en productivité et en automatisation.\n\nMon contexte : chaque [semaine/jour], je perds environ [nombre] minutes à [décris ta tâche plate — ex. : trier mes courriels, planifier mes repas, classer mes factures]. Je travaille comme [ton rôle] et j'utilise déjà [outils que tu connais — ex. : Google Agenda, Notion, Excel].\n\nPropose-moi un système étape par étape pour automatiser cette tâche au maximum. Pour chaque étape, dis-moi exactement quoi faire, quel outil gratuit utiliser, et donne-moi les instructions comme si j'avais jamais touché à ça.",
            'technique' => "🎭 Role prompting — « Tu es un assistant personnel expert en productivité et en automatisation. » On donne un rôle précis à l'IA pour qu'elle réponde comme une vraie personne spécialisée, pas comme un robot générique.\n\n🧩 Context engineering — « Mon contexte : chaque [semaine/jour], je perds [nombre] minutes à [tâche]... j'utilise déjà [outils]. » On nourrit l'IA avec TON contexte réel. Plus elle en sait, plus sa réponse est utile pour toi.\n\n💡 Pour aller plus loin : on aurait pu ajouter du negative prompting pour dire à l'IA quoi ne pas faire — genre « Ne me suggère pas d'outils payants ». On explore ça la semaine prochaine !",
        ];
        $issue->content = $content;
        $issue->save();

        $this->info('Défi W16 mis à jour avec le nouveau format court (2 techniques).');

        return self::SUCCESS;
    }
}
