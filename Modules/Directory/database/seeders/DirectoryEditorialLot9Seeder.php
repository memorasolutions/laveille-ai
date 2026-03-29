<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

/**
 * Enrichissement editorial lot 9 - Education (3 outils).
 * Session 130 (2026-03-26).
 */
class DirectoryEditorialLot9Seeder extends Seeder
{
    public function run(): void
    {
        $articles = $this->getArticles();

        foreach ($articles as $slug => $description) {
            $tool = Tool::where('slug->fr_CA', $slug)->first()
                ?? Tool::where('slug->'.app()->getLocale(), $slug)->first();

            if ($tool) {
                $tool->setTranslation('description', 'fr_CA', $description);
                $tool->save();
                $this->command->info("Updated: {$slug}");
            } else {
                $this->command->warn("Not found: {$slug}");
            }
        }
    }

    private function getArticles(): array
    {
        return [
            'khanmigo' => $this->khanmigo(),
            'duolingo-max' => $this->duolingoMax(),
            'photomath' => $this->photomath(),
        ];
    }

    private function khanmigo(): string
    {
        return <<<'MD'
L'evolution de l'intelligence artificielle dans le secteur educatif franchit une etape majeure avec le lancement de Khanmigo par Khan Academy. Alors que le debat sur l'utilisation des agents conversationnels en classe fait rage, cette solution se positionne non pas comme un outil de triche, mais comme un tuteur personnel sophistique.

## A propos de Khanmigo

Khanmigo est l'assistant pedagogique propulse par l'intelligence artificielle de Khan Academy, l'organisation a but non lucratif fondee par Sal Khan. Developpe en partenariat avec OpenAI, il integre la puissance de GPT-4 avec des garde-fous pedagogiques stricts. Son architecture repose sur la methode socratique : l'objectif n'est pas de faire le travail a la place de l'eleve, mais de l'amener a comprendre le raisonnement par lui-meme grace a un questionnement strategique.

L'outil est directement integre a la plateforme Khan Academy, ce qui lui permet d'avoir un contexte precis sur les exercices en cours. Il connait le parcours de l'utilisateur, ses difficultes passees et les objectifs du programme scolaire. Khanmigo couvre les mathematiques, les sciences, les humanites et la programmation informatique, avec des systemes de moderation pour garantir des interactions appropriees au cadre scolaire.

## Fonctionnalites principales

Pour les eleves, l'IA offre une aide en temps reel lors de la resolution de problemes. Si un etudiant bloque sur une equation, Khanmigo ne donnera pas la solution mais demandera : "Quelle est la premiere etape pour isoler l'inconnue ?". Cette interaction favorise une retention cognitive superieure a la simple lecture d'un corrige.

Khanmigo propose des experiences immersives : debats avec des personnages historiques simules (Marie Curie, Gandhi) et aide au debogage de scripts en pointant les erreurs de logique sans les corriger automatiquement.

Pour les enseignants, plus de 20 outils dedies sont disponibles : plans de cours personnalises, quiz adaptes, rubriques d'evaluation, rapports de progression et projets pedagogiques individualises (IEP). Un tableau de bord permet la surveillance des conversations pour assurer la securite maximale. Des alertes de securite detectent les contenus sensibles.

## Tarification

Pour les enseignants, Khanmigo est gratuit. Cette decision vise a soutenir le corps professoral sans barriere financiere. Pour les eleves et familles, le tarif est de 4 dollars par mois, destine a couvrir les frais de calcul GPT-4. Ce prix est relativement bas comparativement a un cours particulier traditionnel, offrant un tuteur disponible 24h/24.

Des programmes pour les ecoles et les districts scolaires sont egalement disponibles pour les deploiements a grande echelle, avec une projection de plus d'un million d'eleves K-12.

## Comparaison avec les alternatives

ChatGPT et Google Bard sont extremement capables mais souffrent de deux defauts en milieu educatif : la tendance a donner la reponse immediate et le risque d'hallucinations non cadrees. ChatGPT redigera une dissertation a la place de l'eleve, Khanmigo l'aidera a structurer son plan et affiner ses arguments.

Face a Socratic (Google), Khanmigo prend l'avantage par son integration profonde au curriculum de Khan Academy. Socratic utilise la reconnaissance visuelle pour resoudre des exercices, encourageant souvent une consommation passive de la solution. Khanmigo integre des alertes de securite et un historique transparent, crucial pour la protection des mineurs.

La note de 4 etoiles par Common Sense Media (au-dessus de ChatGPT et Bard) souligne sa qualite pedagogique.

## Notre avis

Khanmigo represente l'une des utilisations les plus intelligentes et ethiques de l'IA generative. En appliquant la methode socratique, Khan Academy evite le piege de la facilite technologique pour se concentrer sur le developpement des competences critiques.

L'outil ne remplace pas l'enseignant, il l'augmente en prenant en charge les taches repetitives et en offrant un soutien de premier niveau. Le cadre impose par Khan Academy limite les risques de maniere bien plus efficace que n'importe quel autre agent conversationnel. Pour les parents soucieux de donner un avantage academique tout en preservant l'autonomie de pensee, Khanmigo est un investissement hautement recommandable. C'est une demonstration que l'IA, au service d'une vision pedagogique claire, peut devenir un levier d'egalite des chances exceptionnel.
MD;
    }

    private function duolingoMax(): string
    {
        return <<<'MD'
L'evolution des technologies d'intelligence artificielle transforme radicalement le secteur de l'EdTech. Au coeur de cette revolution, Duolingo a franchi une etape majeure avec le lancement de Duolingo Max. Cette offre haut de gamme, basee sur GPT-4 d'OpenAI, promet une experience immersive et personnalisee sans precedent pour l'apprentissage des langues.

## A propos de Duolingo Max

Duolingo Max represente le sommet de la gamme des services proposes par la firme de Pittsburgh. Ce forfait se distingue par l'integration profonde de l'intelligence artificielle generative au sein du parcours pedagogique. Contrairement aux versions precedentes basees sur des algorithmes de repetition espacee classiques, Duolingo Max exploite la puissance de GPT-4 pour offrir une interactivite dynamique.

Le service est disponible dans plus de 188 pays. Les parcours optimises par l'IA concernent principalement l'enseignement de l'espagnol et du francais pour les locuteurs anglophones. En souscrivant, l'utilisateur beneficie de l'integralite des avantages de Super Duolingo : absence de publicites, coeurs illimites et possibilite de tester les niveaux sans depenser de gemmes.

## Fonctionnalites principales

Le Video Call est une interface de conversation en temps reel avec Lily, l'un des personnages emblematiques de l'univers Duolingo. L'IA mene une discussion fluide, s'adapte aux reponses et relance le dialogue naturellement. Cette simulation reduit l'anxiete liee a la prise de parole dans une langue etrangere.

Le Roleplay plonge l'utilisateur dans des scenarios interactifs de la vie quotidienne : commander un cafe, planifier une excursion, negocier un achat. L'IA evalue la justesse grammaticale et la pertinence contextuelle. Un feedback detaille est fourni a la fin de chaque scenario.

La fonction Explain My Answer leve le voile sur les erreurs commises avec des explications detaillees sur les nuances de grammaire et syntaxe. Cette option est desormais accessible gratuitement pour tous les utilisateurs.

## Tarification

L'abonnement est de 30 dollars par mois, ou 168 dollars par an (soit 14 dollars par mois). Ce tarif inclut toutes les options de Super Duolingo. Bien que ce prix puisse paraitre eleve pour une application mobile, il reste competitif face au cout d'une heure de cours de langue avec un professeur particulier (25 a 30 dollars pour une seule session).

## Comparaison avec les alternatives

Rosetta Stone (36 dollars par mois) propose une approche d'immersion totale sans traduction, rigoureuse mais moins ludique. Babbel se positionne sur un creneau plus academique avec des classes virtuelles en direct. Busuu mise sur une communaute de locuteurs natifs pour corriger les exercices, mais manque de l'immediatete du Video Call de Duolingo Max qui repond instantanement 24h/24.

Duolingo Max se distingue par son interface utilisateur superieure et son integration technologique transparente, meme si certains concurrents conservent l'avantage pour les structures pedagogiques plus traditionnelles.

## Notre avis

Duolingo Max marque un tournant pour la plateforme. L'integration de GPT-4 transforme un jeu de repetition en un outil d'apprentissage serieux et interactif. Le Video Call avec Lily resout l'un des plus grands defis des applications de langues : la transition de la lecture passive a la production orale active.

Le tarif de 30 dollars par mois est justifie pour une utilisation quotidienne. L'aspect ludique combine a la puissance de l'IA cree un environnement ou l'on n'a plus peur de faire des erreurs. Cependant, le service reste limite a un petit nombre de langues. En conclusion, Duolingo Max prouve que l'IA generative est un veritable levier d'apprentissage capable de democratiser l'acces a un tutorat personnalise.
MD;
    }

    private function photomath(): string
    {
        return <<<'MD'
L'evolution des technologies educatives a franchi un palier decisif avec Photomath, une application mobile devenue incontournable pour les eleves, les etudiants et les enseignants du monde entier. Le concept est d'une simplicite desarmante : utiliser la camera d'un smartphone pour scanner un probleme mathematique et obtenir une solution immediate avec des explications detaillees.

## A propos de Photomath

Lancee initialement par Microblink, une societe specialisee en reconnaissance optique de caracteres, Photomath a su transformer un simple outil de lecture de texte en un moteur de resolution mathematique complexe. Grace a des algorithmes de vision par ordinateur et de deep learning, l'application identifie les symboles, les chiffres et les structures pour proposer une solution. En 2022, Google a finalise l'acquisition de Photomath, soulignant l'importance strategique de cette technologie.

L'objectif ne se limite pas a fournir un resultat brut. Photomath se positionne comme un tuteur numerique capable de deconstruire la complexite des mathematiques. L'application couvre un spectre large, de l'arithmetique et la pre-algebre jusqu'au calcul differentiel, aux statistiques et a la trigonometrie.

## Fonctionnalites principales

La technologie OCR (Optical Character Recognition) est reputee pour sa precision, meme face a des ecritures manuscrites desordonnees. La resolution etape par etape explique "quoi" faire, "comment" et "pourquoi" chaque operation est effectuee, favorisant la comprehension et la capacite a reproduire le raisonnement.

La version Photomath Plus enrichit l'experience avec des tutoriels animes qui visualisent le deplacement des variables et les transformations d'equations. Une base de donnees impressionnante de solutions de manuels scolaires verifiees par des experts est accessible en scannant le code-barres d'un livre. Des graphiques interactifs generent des courbes et des representations pour les fonctions et la geometrie analytique.

## Tarification

La version de base est entierement gratuite avec un nombre illimite de scans et les solutions avec les etapes de calcul fondamentales. L'abonnement Photomath Plus est propose a un tarif abordable et debloque les explications pedagogiques detaillees, les solutions de manuels scolaires et les aides visuelles avancees. Le cout varie selon la duree d'engagement (mensuel ou annuel). L'absence de publicite intrusive dans la version gratuite preserve la concentration.

## Comparaison avec les alternatives

Microsoft Math Solver est entierement gratuit avec des fonctionnalites similaires de scan et resolution, mais certains utilisateurs trouvent Photomath plus intuitif avec une reconnaissance manuscrite legerement plus performante.

Mathway (propriete de Chegg) dispose d'un moteur puissant couvrant chimie et physique en plus des mathematiques, mais restreint souvent l'affichage des etapes derriere un mur de paiement plus onereux.

Wolfram Alpha reste la reference absolue pour le calcul symbolique et les mathematiques universitaires de haut niveau, mais demande une certaine maitrise de la syntaxe et est moins oriente vers l'accompagnement scolaire simplifie. Si Wolfram Alpha est l'outil des ingenieurs, Photomath s'impose comme le compagnon ideal de l'eleve du secondaire.

## Notre avis

Photomath represente une avancee technologique majeure dans l'EdTech. Son acquisition par Google temoigne de la pertinence de son modele. L'application reussit l'equilibre delicat entre aide au travail et pedagogie. La maniere dont elle structure les explications etape par etape tend a minimiser le risque que l'eleve se contente de copier sans comprendre.

L'interface est exemplaire de clarte et la rapidite d'execution impressionnante. Les explications de la version Plus se rapprochent reellement d'une seance de tutorat prive. Pour les parents, c'est un outil de soutien precieux permettant de verifier les devoirs sans maitriser le calcul integral. Pour les enseignants, c'est un levier de differenciation pedagogique.

En conclusion, Photomath est une application indispensable pour quiconque interagit avec les mathematiques au quotidien. Sa facilite d'utilisation et la robustesse de son moteur de reconnaissance en font la reference du secteur sur mobile.
MD;
    }
}
