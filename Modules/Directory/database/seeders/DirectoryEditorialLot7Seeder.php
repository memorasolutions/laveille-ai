<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

/**
 * Enrichissement editorial lot 7 - Recherche/Transcription (6 outils).
 * Session 130 (2026-03-26).
 */
class DirectoryEditorialLot7Seeder extends Seeder
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
            'otter-ai' => $this->otterAi(),
            'fireflies' => $this->fireflies(),
            'elicit' => $this->elicit(),
            'consensus' => $this->consensus(),
            'semantic-scholar' => $this->semanticScholar(),
            'humata' => $this->humata(),
        ];
    }

    private function otterAi(): string
    {
        return <<<'MD'
L'évolution constante des outils de collaboration numerique a transforme la manière dont les entreprises gerent leurs echanges verbaux. Au coeur de cette révolution se trouve Otter.ai, une plateforme de transcription et de gestion de réunions basee sur l'intelligence artificielle.

## A propos de Otter.ai

Otter.ai est une entreprise technologique basee a Mountain View, en Californie, spécialisée dans la reconnaissance vocale et le traitement du langage naturel. La plateforme utilise des algorithmes d'apprentissage profond pour convertir la parole en texte en temps reel. Initialement concue pour aider les journalistes et les etudiants, la solution a evolue pour devenir un assistant de reunion complet.

La force de Otter.ai reside dans sa capacite a traiter l'information comme un ensemble de données cohérentes. L'outil identifie les locuteurs, extrait les mots-cles et genere des résumés automatiques. En eliminant la corvee de la prise de notes manuelle, il permet aux participants de se concentrer pleinement sur les echanges.

## Fonctionnalites principales

OtterPilot est la fonctionnalite la plus emblematique. Cet agent intelligent rejoint automatiquement les réunions sur Zoom, Microsoft Teams ou Google Meet, même en l'absence de l'utilisateur. Il enregistre l'audio, capture les diapositives et redige une transcription synchronisee.

La transcription en temps reel affiche le texte instantanement pendant que les participants s'expriment. L'IA genere ensuite un résumé automatise mettant en lumiere les points cles et les actions a entreprendre. Otter AI Chat permet d'interagir directement avec la transcription en posant des questions specifiques. La recherche globale retrouve une information parmi des centaines d'heures d'enregistrements en quelques secondes.

## Tarification

Le plan Basic (gratuit) offre 300 minutes de transcription par mois avec des sessions de 30 minutes maximum. Le plan Pro a environ 8 dollars par mois (facturation annuelle) offre 1200 minutes et des sessions de 90 minutes. Le plan Business a 20 dollars par utilisateur et par mois offre 6000 minutes avec des sessions de 4 heures. Le plan Enterprise est une solution sur mesure avec quotas personnalises et sécurité renforcee.

## Comparaison avec les alternatives

Fireflies se distingue par une intégration poussee avec les outils CRM et une prise en charge de plus de 100 langues. tl;dv met l'accent sur la video et la creation d'extraits visuels. MeetGeek se concentre sur l'analyse de sentiment et la satisfaction des participants. Otter.ai conserve l'avantage de la maturite technologique et d'une interface extrêmêment fluide qui facilite l'adoption.

## Notre avis

Otter.ai représente une avancée majeure dans la gestion du temps de travail. La précision de la transcription est globalement excellente et necessite peu de corrections. L'intégration de l'IA générative via le chat permet d'interroger ses réunions comme une base de données, changeant radicalement la manière de travailler.

Cependant, le support des langues autres que l'anglais a longtemps ete un point faible. De plus, l'utilisation d'un bot qui rejoint les réunions peut soulever des questions de confidentialité. En conclusion, Otter.ai est un investissement rentable pour toute organisation cherchant a optimiser sa documentation interne et a transformer les echanges verbaux en ressources exploitables.
MD;
    }

    private function fireflies(): string
    {
        return <<<'MD'
Dans un monde professionnel de plus en plus numérisé, la gestion des réunions est devenue un enjeu majeur pour la productivité. Fireflies.ai s'est impose comme une solution de reference pour automatiser la capture des echanges vocaux et les transformer en ressources exploitables.

## A propos de Fireflies

Fireflies.ai est une plateforme logicielle concue pour automatiser la capture des echanges vocaux lors des réunions virtuelles ou physiques. Le fonctionnement repose sur un assistant intelligent nomme "Fred", qui rejoint les sessions de visioconference en tant que participant silencieux pour enregistrer et transcrire le flux audio en temps reel.

L'outil s'intègre de manière transparente avec Zoom, Google Meet, Microsoft Teams et Webex. Au-dela de la simple transcription, Fireflies utilise des algorithmes d'apprentissage profond pour comprendre le contexte, identifier les locuteurs et extraire les informations cles. Avec une prise en charge de plus de 100 langues, la solution s'adresse a un public international.

## Fonctionnalites principales

La detection multi-locuteurs segmente la transcription en attribuant chaque phrase a la bonne personne. Les "super-résumés" generent des syntheses structurees comprenant les points cles, les décisions prises et les actions a entreprendre.

"Ask Fred" est un agent conversationnel intègre permettant de poser des questions directes sur le contenu de la reunion. L'analyse de sentiment détecte le ton de la conversation pour identifier les moments de frustration ou de satisfaction. Plus de 200 mini-apps et intégrations automatisent les processus post-reunion (Slack, Asana, Salesforce). L'extension Chrome capture des conversations directement depuis le navigateur.

## Tarification

Le plan gratuit offre des transcriptions illimitees avec 20 credits IA par mois. Le plan Pro débloque des fonctionnalites de recherche avancées et un stockage plus important. Le plan Business offre un acces illimite aux fonctions IA, incluant les super-résumés et l'analyse de sentiment. L'offre Enterprise propose une sécurité de niveau bancaire et un support dedie.

## Comparaison avec les alternatives

Otter.ai est le concurrent le plus direct, tres performant en reconnaissance vocale anglaise mais plus limite en langues. Fireflies prend l'avantage sur la polyvalence linguistique et les intégrations CRM. tl;dv excelle dans la creation de clips video mais reste en retrait pour l'analyse textuelle. Grain est sérieux pour la recherche qualitative mais Fireflies propose un ecosystème d'applications tierces plus vaste.

## Notre avis

Fireflies.ai n'est pas seulement un outil de transcription, mais un veritable partenaire de productivité. La précision du moteur de reconnaissance vocale, combinée aux modèles de langage récents, en fait un atout indispensable. L'assistant rejoignant automatiquement les réunions elimine toute friction technique. Les super-résumés capturent l'essence des discussions sans perdre les details critiques.

Le plan gratuit est extrêmêment compétitif et permet de se familiariser sans pression. C'est dans ses versions payantes que Fireflies révèle tout son potentiel. En conclusion, Fireflies.ai se positionne comme le leader pour les equipes exigeantes qui souhaitent capitaliser sur leur intelligence collective.
MD;
    }

    private function elicit(): string
    {
        return <<<'MD'
Face a une explosion du volume de publications scientifiques, les méthodes traditionnelles de recherche bibliographique atteignent leurs limites. Elicit se positionne comme un assistant de recherche IA qui automatise les étapes les plus chronophages de l'analyse documentaire, avec une base de plus de 138 millions d'articles scientifiques.

## A propos de Elicit

Elicit est une plateforme de recherche assistee par intelligence artificielle concue pour le milieu academique et les secteurs de R&D. Contrairement aux moteurs de recherche généralistes, Elicit s'appuie sur des modèles de langage optimises pour la précision scientifique. Sa mission est de permettre aux utilisateurs de poser des questions complexes en langage naturel et d'obtenir des réponses fondées sur des preuves extraites de la littérature.

Le système repose sur une indexation massive de données issues de Semantic Scholar et d'autres partenaires, couvrant un spectre large de disciplines. L'outil synthétise des informations provenant de plusieurs articles simultanément.

## Fonctionnalites principales

L'extraction de données automatisee est l'atout le plus puissant. Elicit extrait des details specifiques de chaque article : taille d'echantillon, methodologie, resultats chiffres, limitations. Les tableaux personnalises permettent d'ajouter des colonnes specifiques pour organiser les resultats.

La plateforme facilite les revues systematiques en permettant d'importer des fichiers PDF (via Zotero ou chargement direct). Les alertes surveillent les nouveaux sujets de recherche et l'API permet d'intègrer les capacites d'extraction dans des flux institutionnels.

## Tarification

Le plan Basic (gratuit) offre 2 rapports par mois avec recherche illimitee sur 138M+ articles. Le plan Plus a 7 dollars par mois offre 4 rapports et les exports. Le plan Pro a 29 dollars par mois permet le criblage de 5000 articles et 144 rapports par an. Le plan Scale a 169 dollars par mois crible 40000 articles avec extraction de figures. Le plan Enterprise est sur mesure.

## Comparaison avec les alternatives

Consensus excelle pour obtenir un consensus scientifique rapide sur une question fermee, mais Elicit offre des capacites d'extraction plus approfondies. Semantic Scholar reste un moteur de recherche classique sans la couche analytique d'Elicit. Scispace est proche dans son approche mais Elicit est souvent percu comme plus précis pour les tableaux de comparaison.

## Notre avis

Elicit représente une avancée majeure dans l'outillage du chercheur. L'extraction automatisee de données, qui prenait des semaines, peut être ebauchee en minutes. Cependant, l'outil ne remplace pas l'expertise humaine : l'IA peut parfois mal interpreter un contexte. Le modèle economique reste accessible pour les chercheurs individuels. En conclusion, Elicit est un assistant indispensable pour naviguer efficacement dans la production scientifique contemporaine.
MD;
    }

    private function consensus(): string
    {
        return <<<'MD'
Dans un ecosystème ou la desinformation et les hallucinations des modèles generatifs posent probleme, Consensus s'impose comme une solution de confiance. Ce moteur de recherche indexe plus de 220 millions d'articles évalués par les pairs pour fournir des réponses basees exclusivement sur des preuves empiriques.

## A propos de Consensus

Consensus utilise le traitement du langage naturel pour extraire et synthétiser les conclusions issues de la recherche academique. Contrairement a un moteur classique qui privilegie le referencement SEO, Consensus interroge une base de publications scientifiques verifiees. L'outil transforme une question en langage naturel en une requete capable de balayer des millions de documents techniques.

Chaque affirmation est etayee par une citation directe vers une etude publiee, evitant les biais des IA génératives qui peuvent inventer des faits.

## Fonctionnalites principales

Le "Consensus Meter" affiche le degre de consensus parmi les chercheurs (oui, non, peut-être) sur une question fermee. Le "Deep Search" analyse entre 50 et 200 articles simultanément pour les revues de littérature approfondies.

Le "Study Snapshot" offre un résumé structure de chaque article (methodologie, echantillon, resultats). "Ask Paper" propulse par GPT-4 permet d'interagir avec un document specifique. ConsensusGPT intègre le moteur dans l'ecosystème OpenAI.

## Tarification

Le plan Free offre un acces limite avec 25 recherches Pro et 3 Deep Searches par mois. Le plan Pro a 15 dollars par mois offre des recherches illimitees et le Consensus Meter complet. Le plan Deep a 65 dollars par mois maximise les capacites de Deep Search. Les plans Teams et Enterprise sont sur devis.

## Comparaison avec les alternatives

Elicit est le concurrent le plus proche, tres performant pour l'extraction de données dans les tableaux. Consensus se distingue par son interface intuitive et le Consensus Meter. Semantic Scholar est gratuit et puissant pour la decouverte mais n'offre pas le même niveau de synthese automatique. Scite.ai se specialise dans l'analyse des citations (confirmation ou contestation).

## Notre avis

Consensus s'impose comme un outil indispensable pour manipuler de l'information scientifique. La précision des resultats, adossee a 220 millions d'articles, en fait une alternative rigoureuse aux moteurs généralistes. L'intégration GPT-4 facilite l'appropriation des concepts complexes. Le gain de productivité est reel. Toutefois, l'esprit critique de l'utilisateur reste essentiel. En conclusion, Consensus offre un equilibre parfait entre accessibilite et rigueur academique.
MD;
    }

    private function semanticScholar(): string
    {
        return <<<'MD'
La recherche scientifique traverse une phase de transformation majeure grace a l'intelligence artificielle. Au coeur de cette révolution se trouve Semantic Scholar, un outil de recherche academique développé par l'Allen Institute for AI (AI2), indexant plus de 200 millions d'articles de toutes les disciplines.

## A propos de Semantic Scholar

Semantic Scholar n'est pas simplement un index de publications : c'est un moteur de decouverte base sur l'intelligence artificielle. Lance en 2015 par l'Allen Institute for AI, il repose sur une philosophie d'acces libre a la connaissance. Contrairement aux bases de données commerciales, cette plateforme est entièrement gratuite.

La force reside dans la comprehension du contexte et des relations entre les publications. L'algorithme analyse la structure des documents pour en extraire le sens profond, filtrant le bruit informationnel pour presenter les articles les plus pertinents.

## Fonctionnalites principales

Les résumés "TL;DR" (Too Long; Didn't Read) proposent un résumé d'une seule phrase pour chaque article grace a l'IA. Le Semantic Reader est un lecteur PDF augmenté qui affiche des fiches descriptives des citations sans quitter la page.

L'analyse des "citations influentes" distingue les mentions simples des citations qui s'appuient réellement sur la methodologie ou les resultats. Les bibliotheques personnelles et le flux de recommandations personnalise agissent comme un veilleur technologique automatise. L'API ouverte permet l'intégration dans d'autres outils.

## Tarification

Semantic Scholar est entièrement gratuit pour les utilisateurs finaux. Il n'existe pas de version premium payante. L'Allen Institute for AI est finance par des dotations philanthropiques, maintenant ce service comme un bien public mondial. L'API est egalement gratuite pour la plupart des besoins academiques.

## Comparaison avec les alternatives

Google Scholar possede l'index le plus vaste mais manque de fonctionnalites d'analyse semantique, de résumés IA et de distinction entre types de citations. Elicit est performant pour les questions specifiques mais devient payant au-dela d'un certain usage. Scite.ai propose une analyse fine du sentiment des citations mais necessite un abonnement. Connected Papers offre une approche visuelle mais s'appuie souvent sur les données de Semantic Scholar via son API.

## Notre avis

Semantic Scholar s'est impose comme un outil indispensable pour la communaute scientifique. L'intégration de l'IA n'est pas un gadget mais une réponse concrete a l'infobesite qui frappe la recherche. Les TL;DR et la navigation intelligente dans les PDF représentent un gain de temps considerable.

L'algorithme de recommandation s'ameliore avec l'usage. L'ouverture des données via l'API témoigne d'une volonte de faire progresser la science collaborativement. Certes, le moteur peut generer des résumés imparfaits ou manquer certaines publications récentes. Mais pour tout professionnel ou etudiant sérieux, Semantic Scholar est une necessite pour rester a la pointe de son domaine.
MD;
    }

    private function humata(): string
    {
        return <<<'MD'
La gestion de l'information constitue un defi majeur pour les entreprises et les chercheurs. Humata AI s'impose comme une solution de reference pour l'analyse de documents PDF, transformant la manière dont nous interagissons avec les textes longs et complexes.

## A propos de Humata

Humata AI utilise l'intelligence artificielle pour simplifiér la lecture et l'analyse de documents. Contrairement a un moteur de recherche par mots-cles, cet outil comprend le contexte et la semantique des phrases. Il agit comme un assistant de recherche capable de lire des milliers de pages en quelques secondes pour fournir des réponses précises.

L'interface se distingue par sa sobriete et son efficacite. L'utilisateur telecharge ses documents puis utilise une fenêtre de discussion pour interroger le contenu. Humata s'adapte aux jargons techniques les plus pointus : analyses juridiques, revues scientifiques, rapports financiers.

## Fonctionnalites principales

Le système de questions-réponses (Q&A) est le coeur de Humata. Chaque affirmation est accompagnee de references directes vers les pages du document source, evitant les hallucinations. Le résumé automatique condense un rapport de cent pages en quelques points cles.

La comparaison de documents identifie les differences entre plusieurs versions d'un contrat. L'OCR (Reconnaissance Optique de Caracteres) rend les documents scannes analysables par l'IA. Les modèles de langage de derniere generation (GPT-5) garantissent une comprehension fine. La gestion des permissions d'equipe permet le partage controle des bases de connaissances.

## Tarification

Le plan Free offre 60 pages par mois. Le plan Student a 2 dollars par mois cible le monde academique. Le plan Expert a 10 dollars par mois leve la plupart des restrictions. Le plan Team a 49 dollars par utilisateur par mois inclut gestion d'equipe et uploads illimités. Le plan Enterprise est sur mesure avec déploiements specifiques et sécurité accrue.

## Comparaison avec les alternatives

ChatPDF est le concurrent le plus direct en simplicite, mais Humata prend l'avantage sur les documents longs et la précision des citations. AskYourPDF propose des extensions de navigateur mais son interface est moins professionnelle. NotebookLM de Google adopte une approche de prise de notes augmentée mais n'offre pas la même focalisation sur l'analyse de documents bruts.

## Notre avis

Humata AI s'impose comme un outil indispensable pour le travailleur du savoir moderne. L'intégration de modèles de langage de pointe permet des resultats d'une finesse etonnante. Le système de references cliquables apporte la sécurité necessaire aux professionnels.

Certaines fonctions avancées sont reservees aux plans onereux, mais la structure tarifaire reste cohérente. Pour un analyste ou un chercheur, le gain de temps genere rentabilise rapidement l'investissement. En conclusion, Humata est un levier de productivité reel qui automatise la partie la plus ingrate de la recherche documentaire.
MD;
    }
}
