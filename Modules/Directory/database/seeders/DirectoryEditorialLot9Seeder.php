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
L'évolution de l'intelligence artificielle dans le secteur éducatif franchit une étape majeure avec le lancement de Khanmigo par Khan Academy. Alors que le débat sur l'utilisation des agents conversationnels en classe fait rage, cette solution se positionne non pas comme un outil de triche, mais comme un tuteur personnel sophistique.

## A propos de Khanmigo

Khanmigo est l'assistant pédagogique propulse par l'intelligence artificielle de Khan Academy, l'organisation a but non lucratif fondee par Sal Khan. Développé en partenariat avec OpenAI, il intègre la puissance de GPT-4 avec des garde-fous pédagogiques stricts. Son architecture repose sur la méthode socratique : l'objectif n'est pas de faire le travail a la place de l'élève, mais de l'amener a comprendre le raisonnement par lui-même grace a un questionnement stratégique.

L'outil est directement intègre a la plateforme Khan Academy, ce qui lui permet d'avoir un contexte précis sur les exercices en cours. Il connait le parcours de l'utilisateur, ses difficultés passees et les objectifs du programme scolaire. Khanmigo couvre les mathématiques, les sciences, les humanites et la programmation informatique, avec des systèmes de moderation pour garantir des interactions appropriees au cadre scolaire.

## Fonctionnalites principales

Pour les élèves, l'IA offre une aide en temps reel lors de la résolution de problemes. Si un etudiant bloque sur une equation, Khanmigo ne donnera pas la solution mais demandera : "Quelle est la première étape pour isoler l'inconnue ?". Cette interaction favorise une retention cognitive superieure a la simple lecture d'un corrige.

Khanmigo propose des experiences immersives : débats avec des personnages historiques simules (Marie Curie, Gandhi) et aide au débogage de scripts en pointant les erreurs de logique sans les corriger automatiquement.

Pour les enseignants, plus de 20 outils dédiés sont disponibles : plans de cours personnalises, quiz adaptes, rubriques d'évaluation, rapports de progression et projets pédagogiques individualises (IEP). Un tableau de bord permet la surveillance des conversations pour assurer la sécurité maximale. Des alertes de sécurité détectent les contenus sensibles.

## Tarification

Pour les enseignants, Khanmigo est gratuit. Cette décision vise a soutenir le corps professoral sans barriere financiere. Pour les élèves et familles, le tarif est de 4 dollars par mois, destiné a couvrir les frais de calcul GPT-4. Ce prix est relativement bas comparativement a un cours particulier traditionnel, offrant un tuteur disponible 24h/24.

Des programmes pour les ecoles et les districts scolaires sont egalement disponibles pour les déploiements a grande echelle, avec une projection de plus d'un million d'élèves K-12.

## Comparaison avec les alternatives

ChatGPT et Google Bard sont extrêmêment capables mais souffrent de deux défauts en milieu éducatif : la tendance a donner la réponse immédiate et le risque d'hallucinations non cadrees. ChatGPT redigera une dissertation a la place de l'élève, Khanmigo l'aidera a structurer son plan et affiner ses arguments.

Face a Socratic (Google), Khanmigo prend l'avantage par son intégration profonde au curriculum de Khan Academy. Socratic utilise la reconnaissance visuelle pour resoudre des exercices, encourageant souvent une consommation passive de la solution. Khanmigo intègre des alertes de sécurité et un historique transparent, crucial pour la protection des mineurs.

La note de 4 etoiles par Common Sense Media (au-dessus de ChatGPT et Bard) souligne sa qualite pédagogique.

## Notre avis

Khanmigo représente l'une des utilisations les plus intelligentes et ethiques de l'IA générative. En appliquant la méthode socratique, Khan Academy evite le piege de la facilite technologique pour se concentrer sur le développement des competences critiques.

L'outil ne remplace pas l'enseignant, il l'augmenté en prenant en charge les taches repetitives et en offrant un soutien de premier niveau. Le cadre impose par Khan Academy limite les risques de manière bien plus efficace que n'importe quel autre agent conversationnel. Pour les parents soucieux de donner un avantage academique tout en preservant l'autonomie de pensee, Khanmigo est un investissement hautement recommandable. C'est une demonstration que l'IA, au service d'une vision pédagogique claire, peut devenir un levier d'egalite des chances exceptionnel.
MD;
    }

    private function duolingoMax(): string
    {
        return <<<'MD'
L'évolution des technologies d'intelligence artificielle transforme radicalement le secteur de l'EdTech. Au coeur de cette révolution, Duolingo a franchi une étape majeure avec le lancement de Duolingo Max. Cette offre haut de gamme, basee sur GPT-4 d'OpenAI, promet une experience immersive et personnalisee sans precedent pour l'apprentissage des langues.

## A propos de Duolingo Max

Duolingo Max représente le sommet de la gamme des services proposes par la firme de Pittsburgh. Ce forfait se distingue par l'intégration profonde de l'intelligence artificielle générative au sein du parcours pédagogique. Contrairement aux versions precedentes basees sur des algorithmes de repetition espacée classiques, Duolingo Max exploite la puissance de GPT-4 pour offrir une interactivité dynamique.

Le service est disponible dans plus de 188 pays. Les parcours optimises par l'IA concernent principalement l'enseignement de l'espagnol et du français pour les locuteurs anglophones. En souscrivant, l'utilisateur bénéficie de l'intégralité des avantages de Super Duolingo : absence de publicites, coeurs illimités et possibilité de tester les niveaux sans depenser de gemmes.

## Fonctionnalites principales

Le Video Call est une interface de conversation en temps reel avec Lily, l'un des personnages emblematiques de l'univers Duolingo. L'IA mene une discussion fluide, s'adapte aux réponses et relance le dialogue naturellement. Cette simulation reduit l'anxiete liee a la prise de parole dans une langue etrangere.

Le Roleplay plonge l'utilisateur dans des scénarios interactifs de la vie quotidienne : commander un cafe, planifier une excursion, negocier un achat. L'IA évalué la justesse grammaticale et la pertinence contextuelle. Un feedback detaille est fourni a la fin de chaque scénario.

La fonction Explain My Answer leve le voile sur les erreurs commises avec des explications détaillées sur les nuances de grammaire et syntaxe. Cette option est désormais accessible gratuitement pour tous les utilisateurs.

## Tarification

L'abonnement est de 30 dollars par mois, ou 168 dollars par an (soit 14 dollars par mois). Ce tarif inclut toutes les options de Super Duolingo. Bien que ce prix puisse paraître élève pour une application mobile, il reste compétitif face au coût d'une heure de cours de langue avec un professeur particulier (25 a 30 dollars pour une seule session).

## Comparaison avec les alternatives

Rosetta Stone (36 dollars par mois) propose une approche d'immersion totale sans traduction, rigoureuse mais moins ludique. Babbel se positionne sur un creneau plus academique avec des classes virtuelles en direct. Busuu mise sur une communaute de locuteurs natifs pour corriger les exercices, mais manque de l'immédiatete du Video Call de Duolingo Max qui repond instantanement 24h/24.

Duolingo Max se distingue par son interface utilisateur superieure et son intégration technologique transparente, même si certains concurrents conservent l'avantage pour les structures pédagogiques plus traditionnelles.

## Notre avis

Duolingo Max marque un tournant pour la plateforme. L'intégration de GPT-4 transforme un jeu de repetition en un outil d'apprentissage sérieux et interactif. Le Video Call avec Lily resout l'un des plus grands défis des applications de langues : la transition de la lecture passive a la production orale active.

Le tarif de 30 dollars par mois est justifie pour une utilisation quotidienne. L'aspect ludique combine a la puissance de l'IA cree un environnement ou l'on n'a plus peur de faire des erreurs. Cependant, le service reste limite a un petit nombre de langues. En conclusion, Duolingo Max prouve que l'IA générative est un veritable levier d'apprentissage capable de démocratiser l'acces a un tutorat personnalise.
MD;
    }

    private function photomath(): string
    {
        return <<<'MD'
L'évolution des technologies éducatives a franchi un palier décisif avec Photomath, une application mobile devenue incontournable pour les élèves, les etudiants et les enseignants du monde entier. Le concept est d'une simplicite desarmante : utiliser la camera d'un smartphone pour scanner un probleme mathématique et obtenir une solution immédiate avec des explications détaillées.

## A propos de Photomath

Lancee initialement par Microblink, une societe spécialisée en reconnaissance optique de caracteres, Photomath a su transformer un simple outil de lecture de texte en un moteur de résolution mathématique complexe. Grace a des algorithmes de vision par ordinateur et de deep learning, l'application identifie les symboles, les chiffres et les structures pour proposer une solution. En 2022, Google a finalise l'acquisition de Photomath, soulignant l'importance stratégique de cette technologie.

L'objectif ne se limite pas a fournir un resultat brut. Photomath se positionne comme un tuteur numerique capable de deconstruire la complexite des mathématiques. L'application couvre un spectre large, de l'arithmétique et la pre-algebre jusqu'au calcul differentiel, aux statistiques et a la trigonometrie.

## Fonctionnalites principales

La technologie OCR (Optical Character Recognition) est reputee pour sa précision, même face a des ecritures manuscrites desordonnées. La résolution étape par étape explique "quoi" faire, "comment" et "pourquoi" chaque operation est effectuee, favorisant la comprehension et la capacite a reproduire le raisonnement.

La version Photomath Plus enrichit l'experience avec des tutoriels animes qui visualisent le deplacement des variables et les transformations d'equations. Une base de données impressionnante de solutions de manuels scolaires verifiees par des experts est accessible en scannant le code-barres d'un livre. Des graphiques interactifs generent des courbes et des representations pour les fonctions et la geometrie analytique.

## Tarification

La version de base est entièrement gratuite avec un nombre illimite de scans et les solutions avec les étapes de calcul fondamentales. L'abonnement Photomath Plus est propose a un tarif abordable et débloque les explications pédagogiques détaillées, les solutions de manuels scolaires et les aides visuelles avancées. Le coût varie selon la duree d'engagement (mensuel ou annuel). L'absence de publicite intrusive dans la version gratuite preserve la concentration.

## Comparaison avec les alternatives

Microsoft Math Solver est entièrement gratuit avec des fonctionnalites similaires de scan et résolution, mais certains utilisateurs trouvent Photomath plus intuitif avec une reconnaissance manuscrite légèrement plus performante.

Mathway (propriete de Chegg) dispose d'un moteur puissant couvrant chimie et physique en plus des mathématiques, mais restreint souvent l'affichage des étapes derriere un mur de paiement plus onereux.

Wolfram Alpha reste la reference absolue pour le calcul symbolique et les mathématiques universitaires de haut niveau, mais demande une certaine maîtrise de la syntaxe et est moins orienté vers l'accompagnement scolaire simplifié. Si Wolfram Alpha est l'outil des ingénieurs, Photomath s'impose comme le compagnon ideal de l'élève du secondaire.

## Notre avis

Photomath représente une avancée technologique majeure dans l'EdTech. Son acquisition par Google témoigne de la pertinence de son modèle. L'application reussit l'equilibre délicat entre aide au travail et pédagogie. La manière dont elle structure les explications étape par étape tend a minimiser le risque que l'élève se contente de copier sans comprendre.

L'interface est exemplaire de clarté et la rapidité d'execution impressionnante. Les explications de la version Plus se rapprochent réellement d'une seance de tutorat prive. Pour les parents, c'est un outil de soutien precieux permettant de verifier les devoirs sans maîtriser le calcul integral. Pour les enseignants, c'est un levier de differenciation pédagogique.

En conclusion, Photomath est une application indispensable pour quiconque interagit avec les mathématiques au quotidien. Sa facilite d'utilisation et la robustesse de son moteur de reconnaissance en font la reference du secteur sur mobile.
MD;
    }
}
