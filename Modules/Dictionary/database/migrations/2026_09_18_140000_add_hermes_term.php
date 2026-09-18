<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ajout du terme « Hermes » (2026-09-18), demande du fondateur.
 *
 * LEVER L AMBIGUITE A ETE LA PREMIERE TACHE, ET ELLE NE S EST PAS RESOLUE COMME PREVU. « Hermes »
 * pouvait designer la famille de modeles de Nous Research, ou l outil interne homonyme de MEMORA
 * (qui n a evidemment rien a faire dans un glossaire public). Le corpus a tranche, et il a revele
 * un troisieme terme : le site publie DEJA trois actualites sur « Hermes Agent » de Nous Research,
 * et l annuaire porte trois fiches (hermes-desktop, humalike-x-hermes, et maxhermes qui est de
 * MiniMax et sans aucun rapport). La demande porte donc bien sur Hermes de Nous Research.
 *
 * ANTI-DOUBLON par FAMILLE de motifs contre les 550 slugs du glossaire de PRODUCTION :
 * hermes, nous-research, openhermes, poids-ouverts, llama, mistral, affinage, appel-d-outil.
 * ZERO fiche de glossaire sur Hermes. Les trois entrees « hermes » trouvees sont dans l ANNUAIRE,
 * qui est un repertoire d OUTILS et non un glossaire de NOTIONS : elles ont ete ouvertes et lues
 * une par une. Aucune ne definit le terme, elles decrivent des produits. Fiche nouvelle, donc.
 *
 * L ANGLE, qui est aussi la correction du defaut le plus repandu sur ce sujet : chez Nous Research,
 * « Hermes » designe DEUX choses distinctes - une famille de MODELES post-entraines, et un AGENT
 * logiciel. Et le point contre-intuitif, celui qui rend la fiche utile : l AGENT N EST PAS LIE AUX
 * MODELES. Sa documentation officielle prevoit qu on lui branche le fournisseur de son choix. On
 * peut faire tourner Hermes Agent sans jamais appeler un modele Hermes.
 * Trois angles avaient ete notes avant de choisir : la distinction modeles/agent (88/100, retenue
 * comme ossature parce qu elle repond a la question que le lecteur se pose vraiment), l entrainement
 * distribue (85/100, retenu comme fait marquant), l alignement neutre (72/100, mentionne seulement -
 * c est une etiquette d editeur, pas une mesure).
 *
 * SIX DEFAUTS CORRIGES DANS LA REDACTION DELEGUEE, tous du meme genre ou presque :
 *   1-3. TROIS SURGENERALISATIONS : le contexte de 512K, la base Seed-OSS-36B-Base et la tenue en
 *        memoire video etaient attribues a TOUTE la famille Hermes, alors qu ils sont propres a la
 *        version 4.3. Hermes 4 70B repose sur une autre base. Reecrit en nommant la version.
 *   4. L exemple etait une reformulation abstraite de la definition, pas un cas concret. Remplace
 *      par un usage reel tire des faits verifies (une memoire unique sur plusieurs messageries, et
 *      le bac a sable a cinq moteurs).
 *   5. La reponse sur la gratuite induisait en erreur par OMISSION : elle disait « aucun abonnement
 *      requis » sans dire que les fournisseurs de modeles ont leurs propres tarifs, ce que la FAQ
 *      officielle precise pourtant. L agent est gratuit, ce qu il consomme ne l est pas forcement.
 *   6. La reponse sur « l alignement neutre » INVENTAIT une definition (« eviter les biais
 *      politiques ou ideologiques forts », « contraste avec d autres modeles »). La source ne dit
 *      rien de tel : elle emploie le mot sans le definir. Reecrit pour dire exactement cela.
 *   Et l icone proposee etait 🤖, que le standard du glossaire interdit explicitement comme
 *   generique. Remplacee par l aile, qui renvoie au messager sans etre un emoji passe-partout.
 *
 * UNE DIVERGENCE D ORACLE TRANCHEE PAR LA SOURCE PRIMAIRE : la recherche donnait la sortie de
 * Hermes 4.3 au « 30 novembre 2025 selon l annonce, 3 decembre selon le registre ». La page de
 * l annonce porte elle-meme datePublished 2025-12-01 et dateModified 2025-12-03. Aucune des deux
 * dates avancees n etait exacte. La fiche ne cite donc AUCUNE date de sortie : elle n en a pas
 * besoin, et une date fausse dans un glossaire survit des annees.
 *
 * CE QUI A ETE ECARTE FAUTE DE VERIFICATION A LA SOURCE, malgre sa presence dans nos propres
 * actualites : le nombre de modeles accessibles via le Nous Portal, le cout de 10 a 20 centimes par
 * million de jetons (qui designe d ailleurs les MODELES et non l agent, la confusion etant
 * precisement ce que cette fiche corrige), les numeros de version de l agent, et la date de son
 * lancement. Un relais n est pas une source primaire.
 *
 * ALIAS comptes dans le corpus PUBLIE avant d etre declares : « Hermes » 17 occurrences et
 * « Hermes Agent » 5 sont declares. ECARTES : « Hermes Desktop » (2 occurrences) parce que
 * l ANNUAIRE porte deja cette fiche et capte deja correctement la forme longue - la declarer ici
 * creerait deux cibles pour une meme expression ; « Nous Research » (6) parce que c est l EDITEUR
 * et non le terme, et qu il fait aussi Psyche et DisTrO ; « Hermes 4 » et « Hermes 3 » (0 chacun),
 * un alias sans occurrence etant du bruit.
 * match_strategy `case_sensitive` : « Hermes » est un nom propre, et la minuscule ne designe rien
 * ici. Le tri par longueur decroissante du linkifier protege « Hermes Desktop », qui reste dirige
 * vers l annuaire - verifie apres publication.
 *
 * Migration idempotente : un slug deja present n est pas recree. down() supprime le terme ajoute
 * et retire sa reference des narrower_slugs de ses deux parents.
 */
return new class extends Migration
{
    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function terms(): array
    {
        return [
            [
                'name' => 'Hermes',
                'slug' => 'hermes',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'Chez Nous Research, « Hermes » désigne deux choses distinctes qu\'on confond facilement. D\'un côté, une famille de modèles de langage post-entraînés, que l\'éditeur qualifie d\'alignement neutre : la version 4.3, par exemple, accepte un contexte allant jusqu\'à 512K, repose sur Seed-OSS-36B-Base, et ses fichiers compressés tiennent dans la mémoire vidéo d\'une carte graphique grand public. Ces modèles ne partent pas de zéro : ils réajustent un modèle de base existant. De l\'autre, Hermes Agent, un logiciel libre sous licence MIT : un programme d\'orchestration qui exécute des outils, garde une mémoire persistante, génère tout seul des compétences réutilisables à mesure qu\'il travaille, et délègue à des sous-agents isolés. Il répond dans Telegram, Discord, Slack, WhatsApp, Signal, par courriel ou en ligne de commande, avec une seule mémoire pour toutes ces portes d\'entrée. Le point qui surprend : l\'agent n\'est pas lié aux modèles. Il fonctionne avec le fournisseur qu\'on lui donne, et peut donc tourner sans jamais appeler un modèle Hermes.',
                'analogy' => 'Le modèle est le moteur, l\'agent est le véhicule qui s\'en sert. On peut changer l\'un sans toucher l\'autre.',
                'example' => 'Le même agent répond dans Telegram, dans Slack et en ligne de commande avec une seule mémoire, et exécute son code dans un bac à sable local, Docker, SSH, Singularity ou Modal.',
                'did_you_know' => 'Pour prouver que l\'entraînement distribué sur Psyche fonctionnait, Nous Research a entraîné Hermes 4.3 deux fois : une fois sur Psyche, une fois de façon centralisée.',
                'one_sentence_answer' => 'Hermes désigne à la fois une famille de modèles de langage à alignement neutre et un agent logiciel libre qui orchestre outils et mémoire persistante, sans être lié à ces modèles.',
                'faq' => [
                    [
                        'question' => 'Quelle est la différence entre les modèles Hermes et Hermes Agent?',
                        'answer' => 'Un modèle Hermes génère du texte; Hermes Agent est le programme qui s\'en sert pour faire un travail : il appelle des outils, tient une mémoire, découpe une tâche et la confie à des sous-agents. La nuance qui compte, et que le nom commun masque : l\'agent n\'est pas limité aux modèles Hermes. Sa documentation prévoit qu\'on lui branche le fournisseur de modèles de son choix. On peut donc faire tourner Hermes Agent sans jamais appeler un modèle Hermes, et inversement utiliser un modèle Hermes sans l\'agent.',
                    ],
                    [
                        'question' => 'Hermes Agent est-il vraiment gratuit?',
                        'answer' => 'L\'agent lui-même, oui : il est open source sous licence MIT, avec une application de bureau pour macOS, Windows et Linux. Mais la facture ne s\'arrête pas là, et la documentation officielle le dit : les fournisseurs de modèles et les services hébergés optionnels ont leurs propres tarifs. Autrement dit, le programme est gratuit, ce qu\'il consomme ne l\'est pas forcément. Si vous le branchez sur un modèle payant, c\'est ce modèle que vous payez, pas Hermes Agent.',
                    ],
                    [
                        'question' => 'Que veut dire l\'alignement neutre annoncé pour les modèles Hermes?',
                        'answer' => 'C\'est le terme employé par Nous Research dans sa propre annonce, et il vaut mieux le prendre pour ce qu\'il est : une intention affichée par l\'éditeur, pas une propriété mesurée. L\'annonce présente Hermes 4.3 comme un modèle privé, puissant et à alignement neutre, sans publier de définition opératoire ni de protocole permettant à un tiers de le vérifier. Il n\'existe donc pas, à ce stade, de moyen de confronter l\'étiquette aux faits : c\'est une affirmation d\'un éditeur sur son propre produit, à lire comme telle.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Site officiel de Hermes Agent, qui détaille la licence MIT, les plateformes prises en charge et le bac à sable',
                        'url' => 'https://hermes-agent.nousresearch.com/',
                        'year' => 2026,
                        'author' => 'Nous Research',
                    ],
                    [
                        'label' => '« Introducing Hermes 4.3: Local Intelligence Globally Trained », annonce officielle du modèle et de son entraînement sur le réseau Psyche',
                        'url' => 'https://nousresearch.com/introducing-hermes-4-3',
                        'year' => 2025,
                        'author' => 'Nous Research',
                    ],
                    [
                        'label' => 'Organisation NousResearch sur Hugging Face, où sont déposés les poids des modèles',
                        'url' => 'https://huggingface.co/NousResearch',
                        'year' => 2026,
                        'author' => 'Nous Research',
                    ],
                ],
                'aliases' => [
                    'Hermes',
                    'Hermes Agent',
                ],
                'broader_slugs' => [
                    'agent-ia',
                    'poids-ouverts',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '🪽',
                'type' => 'ai_term',
                'match_strategy' => 'case_sensitive',
                'has_image' => true,
            ],
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modele Term/Category absent, ignore\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

        foreach ($this->terms() as $i => $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

                continue;
            }

            $term = new Term();

            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }

            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'];
            $term->broader_slugs = $t['broader_slugs'];
            $term->narrower_slugs = $t['narrower_slugs'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->match_strategy = $t['match_strategy'];
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->sort_order = 993 + $i;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";

            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();

                if (! $parent) {
                    continue;
                }

                $narrower = $parent->narrower_slugs ?? [];

                if (! in_array($t['slug'], $narrower, true)) {
                    $narrower[] = $t['slug'];
                    $parent->narrower_slugs = $narrower;
                    $parent->save();
                }
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();

                if (! $parent) {
                    continue;
                }

                $parent->narrower_slugs = array_values(array_diff($parent->narrower_slugs ?? [], [$t['slug']]));
                $parent->save();
            }

            Term::where('slug->fr_CA', $t['slug'])->delete();
            echo "[glossaire] terme retire : {$t['slug']}\n";
        }
    }
};
