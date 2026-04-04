<?php

declare(strict_types=1);

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Newsletter\Models\EditorialBank;

class EditorialBankSeeder extends Seeder
{
    public function run(): void
    {
        $editorials = [
            ['theme' => 'levee_fonds', 'content' => "Encore une levée de fonds d'un milliard et quelques dans le monde de l'IA cette semaine. On peut trouver ça vertigineux, mais ce que ça me dit surtout, c'est que les investisseurs ne parient plus sur le \"si\" l'IA va transformer les industries — ils parient sur le \"qui\" va le faire en premier. Reste à voir si l'argent va aux bonnes places. - Stéphane"],
            ['theme' => 'nouvel_outil', 'content' => "Je suis tombé sur un outil cette semaine qui m'a fait dire « ben voyons donc » à voix haute, tout seul devant mon écran. Sans exagérer, il m'a fait sauver deux heures sur une tâche que je repoussais depuis des jours. C'est exactement pour ça que je reste à l'affût : les petites pépites, c'est souvent celles qui changent vraiment votre quotidien. - Stéphane"],
            ['theme' => 'tendance', 'content' => "Avez-vous remarqué que de plus en plus d'outils IA se spécialisent au lieu d'essayer de tout faire? On passe tranquillement de l'ère du \"couteau suisse\" à celle de l'outil de précision, taillé pour un métier ou un besoin bien spécifique. Personnellement, je trouve ça rassurant — c'est signe que le marché mature. - Stéphane"],
            ['theme' => 'ethique', 'content' => "On ne peut pas juste s'émerveiller devant ce que l'IA peut faire sans se demander ce qu'elle *devrait* faire. La question des biais, du consentement des données, de la transparence — c'est pas du pelletage de nuages, c'est urgent. Si on ne s'en occupe pas maintenant, on va construire un futur qu'on n'aimera pas habiter. - Stéphane"],
            ['theme' => 'education', 'content' => "Apprendre l'IA en 2026, ce n'est plus un luxe réservé aux ingénieurs — c'est devenu aussi fondamental que de savoir utiliser un tableur l'était dans les années 2000. Vous n'avez pas besoin de coder pour comprendre et utiliser ces outils intelligemment. Le plus bel investissement que vous pouvez faire cette année, c'est dans votre propre curiosité. - Stéphane"],
            ['theme' => 'quebec', 'content' => "On a un écosystème IA au Québec qui est franchement impressionnant pour notre taille. Entre Mila, nos startups, nos universités et une communauté de passionnés qui ne lâche pas, on joue vraiment dans les ligues majeures. Je suis fier qu'on soit sur la map mondiale, et j'ai bien l'intention de continuer à le crier sur tous les toits. - Stéphane"],
            ['theme' => 'productivite', 'content' => "L'IA ne remplace pas mon travail — elle remplace les bouts plates de mon travail. Les comptes rendus, les premiers jets, le tri d'information : tout ça, je le délègue de plus en plus à mes assistants IA. Ce qui me reste? Le jugement, la créativité, les décisions. Autrement dit, le meilleur du métier. - Stéphane"],
            ['theme' => 'debutant', 'content' => "Si vous lisez cette newsletter et que vous vous sentez un peu perdu devant tout ce qui bouge en IA, sachez que c'est normal — et que vous êtes exactement à la bonne place. Personne n'est né expert là-dedans. Le simple fait d'être curieux et de vouloir comprendre, c'est déjà 80 % du chemin. - Stéphane"],
            ['theme' => 'surprise', 'content' => "Je pensais avoir vu pas mal de choses en IA, mais cette semaine, j'ai été pris de court. Des usages créatifs auxquels personne n'avait pensé émergent de partout, souvent de gens qui ne viennent même pas du milieu tech. C'est la preuve que quand on démocratise un outil puissant, la magie vient de ceux qui l'utilisent, pas de ceux qui l'ont construit. - Stéphane"],
            ['theme' => 'futur', 'content' => "Dans trois à cinq ans, on va regarder nos façons actuelles de travailler et on va trouver ça aussi archaïque qu'un fax. Les agents IA autonomes, la personnalisation poussée, l'IA embarquée partout — ça s'en vient vite. La question n'est pas de savoir si ça va arriver, c'est de savoir si vous allez surfer sur la vague ou la regarder passer. - Stéphane"],
            ['theme' => 'communaute', 'content' => "Chaque semaine, quand je vois le nombre de personnes qui ouvrent cette newsletter, qui la partagent, qui m'écrivent pour me poser des questions ou me suggérer des sujets, ça me touche sincèrement. Cette communauté-là, c'est vous qui la bâtissez autant que moi. Merci d'être là, pour vrai. - Stéphane"],
            ['theme' => 'personnel', 'content' => "L'autre soir, mon fils m'a demandé de l'aider avec un travail scolaire. On a utilisé l'IA ensemble pour explorer le sujet, structurer ses idées, puis il a tout réécrit dans ses mots. Voir ses yeux s'allumer en comprenant la puissance de l'outil — et surtout qu'il reste le cerveau derrière — c'est un de mes plus beaux moments de papa-geek. - Stéphane"],
        ];

        foreach ($editorials as $editorial) {
            EditorialBank::updateOrCreate(
                ['theme' => $editorial['theme']],
                $editorial
            );
        }
    }
}
