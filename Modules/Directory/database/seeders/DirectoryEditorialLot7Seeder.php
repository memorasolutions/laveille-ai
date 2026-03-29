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
L'evolution constante des outils de collaboration numerique a transforme la maniere dont les entreprises gerent leurs echanges verbaux. Au coeur de cette revolution se trouve Otter.ai, une plateforme de transcription et de gestion de reunions basee sur l'intelligence artificielle.

## A propos de Otter.ai

Otter.ai est une entreprise technologique basee a Mountain View, en Californie, specialisee dans la reconnaissance vocale et le traitement du langage naturel. La plateforme utilise des algorithmes d'apprentissage profond pour convertir la parole en texte en temps reel. Initialement concue pour aider les journalistes et les etudiants, la solution a evolue pour devenir un assistant de reunion complet.

La force de Otter.ai reside dans sa capacite a traiter l'information comme un ensemble de donnees coherentes. L'outil identifie les locuteurs, extrait les mots-cles et genere des resumes automatiques. En eliminant la corvee de la prise de notes manuelle, il permet aux participants de se concentrer pleinement sur les echanges.

## Fonctionnalites principales

OtterPilot est la fonctionnalite la plus emblematique. Cet agent intelligent rejoint automatiquement les reunions sur Zoom, Microsoft Teams ou Google Meet, meme en l'absence de l'utilisateur. Il enregistre l'audio, capture les diapositives et redige une transcription synchronisee.

La transcription en temps reel affiche le texte instantanement pendant que les participants s'expriment. L'IA genere ensuite un resume automatise mettant en lumiere les points cles et les actions a entreprendre. Otter AI Chat permet d'interagir directement avec la transcription en posant des questions specifiques. La recherche globale retrouve une information parmi des centaines d'heures d'enregistrements en quelques secondes.

## Tarification

Le plan Basic (gratuit) offre 300 minutes de transcription par mois avec des sessions de 30 minutes maximum. Le plan Pro a environ 8 dollars par mois (facturation annuelle) offre 1200 minutes et des sessions de 90 minutes. Le plan Business a 20 dollars par utilisateur et par mois offre 6000 minutes avec des sessions de 4 heures. Le plan Enterprise est une solution sur mesure avec quotas personnalises et securite renforcee.

## Comparaison avec les alternatives

Fireflies se distingue par une integration poussee avec les outils CRM et une prise en charge de plus de 100 langues. tl;dv met l'accent sur la video et la creation d'extraits visuels. MeetGeek se concentre sur l'analyse de sentiment et la satisfaction des participants. Otter.ai conserve l'avantage de la maturite technologique et d'une interface extremement fluide qui facilite l'adoption.

## Notre avis

Otter.ai represente une avancee majeure dans la gestion du temps de travail. La precision de la transcription est globalement excellente et necessite peu de corrections. L'integration de l'IA generative via le chat permet d'interroger ses reunions comme une base de donnees, changeant radicalement la maniere de travailler.

Cependant, le support des langues autres que l'anglais a longtemps ete un point faible. De plus, l'utilisation d'un bot qui rejoint les reunions peut soulever des questions de confidentialite. En conclusion, Otter.ai est un investissement rentable pour toute organisation cherchant a optimiser sa documentation interne et a transformer les echanges verbaux en ressources exploitables.
MD;
    }

    private function fireflies(): string
    {
        return <<<'MD'
Dans un monde professionnel de plus en plus numerise, la gestion des reunions est devenue un enjeu majeur pour la productivite. Fireflies.ai s'est impose comme une solution de reference pour automatiser la capture des echanges vocaux et les transformer en ressources exploitables.

## A propos de Fireflies

Fireflies.ai est une plateforme logicielle concue pour automatiser la capture des echanges vocaux lors des reunions virtuelles ou physiques. Le fonctionnement repose sur un assistant intelligent nomme "Fred", qui rejoint les sessions de visioconference en tant que participant silencieux pour enregistrer et transcrire le flux audio en temps reel.

L'outil s'integre de maniere transparente avec Zoom, Google Meet, Microsoft Teams et Webex. Au-dela de la simple transcription, Fireflies utilise des algorithmes d'apprentissage profond pour comprendre le contexte, identifier les locuteurs et extraire les informations cles. Avec une prise en charge de plus de 100 langues, la solution s'adresse a un public international.

## Fonctionnalites principales

La detection multi-locuteurs segmente la transcription en attribuant chaque phrase a la bonne personne. Les "super-resumes" generent des syntheses structurees comprenant les points cles, les decisions prises et les actions a entreprendre.

"Ask Fred" est un agent conversationnel integre permettant de poser des questions directes sur le contenu de la reunion. L'analyse de sentiment detecte le ton de la conversation pour identifier les moments de frustration ou de satisfaction. Plus de 200 mini-apps et integrations automatisent les processus post-reunion (Slack, Asana, Salesforce). L'extension Chrome capture des conversations directement depuis le navigateur.

## Tarification

Le plan gratuit offre des transcriptions illimitees avec 20 credits IA par mois. Le plan Pro debloque des fonctionnalites de recherche avancees et un stockage plus important. Le plan Business offre un acces illimite aux fonctions IA, incluant les super-resumes et l'analyse de sentiment. L'offre Enterprise propose une securite de niveau bancaire et un support dedie.

## Comparaison avec les alternatives

Otter.ai est le concurrent le plus direct, tres performant en reconnaissance vocale anglaise mais plus limite en langues. Fireflies prend l'avantage sur la polyvalence linguistique et les integrations CRM. tl;dv excelle dans la creation de clips video mais reste en retrait pour l'analyse textuelle. Grain est serieux pour la recherche qualitative mais Fireflies propose un ecosysteme d'applications tierces plus vaste.

## Notre avis

Fireflies.ai n'est pas seulement un outil de transcription, mais un veritable partenaire de productivite. La precision du moteur de reconnaissance vocale, combinee aux modeles de langage recents, en fait un atout indispensable. L'assistant rejoignant automatiquement les reunions elimine toute friction technique. Les super-resumes capturent l'essence des discussions sans perdre les details critiques.

Le plan gratuit est extremement competitif et permet de se familiariser sans pression. C'est dans ses versions payantes que Fireflies revele tout son potentiel. En conclusion, Fireflies.ai se positionne comme le leader pour les equipes exigeantes qui souhaitent capitaliser sur leur intelligence collective.
MD;
    }

    private function elicit(): string
    {
        return <<<'MD'
Face a une explosion du volume de publications scientifiques, les methodes traditionnelles de recherche bibliographique atteignent leurs limites. Elicit se positionne comme un assistant de recherche IA qui automatise les etapes les plus chronophages de l'analyse documentaire, avec une base de plus de 138 millions d'articles scientifiques.

## A propos de Elicit

Elicit est une plateforme de recherche assistee par intelligence artificielle concue pour le milieu academique et les secteurs de R&D. Contrairement aux moteurs de recherche generalistes, Elicit s'appuie sur des modeles de langage optimises pour la precision scientifique. Sa mission est de permettre aux utilisateurs de poser des questions complexes en langage naturel et d'obtenir des reponses fondees sur des preuves extraites de la litterature.

Le systeme repose sur une indexation massive de donnees issues de Semantic Scholar et d'autres partenaires, couvrant un spectre large de disciplines. L'outil synthetise des informations provenant de plusieurs articles simultanement.

## Fonctionnalites principales

L'extraction de donnees automatisee est l'atout le plus puissant. Elicit extrait des details specifiques de chaque article : taille d'echantillon, methodologie, resultats chiffres, limitations. Les tableaux personnalises permettent d'ajouter des colonnes specifiques pour organiser les resultats.

La plateforme facilite les revues systematiques en permettant d'importer des fichiers PDF (via Zotero ou chargement direct). Les alertes surveillent les nouveaux sujets de recherche et l'API permet d'integrer les capacites d'extraction dans des flux institutionnels.

## Tarification

Le plan Basic (gratuit) offre 2 rapports par mois avec recherche illimitee sur 138M+ articles. Le plan Plus a 7 dollars par mois offre 4 rapports et les exports. Le plan Pro a 29 dollars par mois permet le criblage de 5000 articles et 144 rapports par an. Le plan Scale a 169 dollars par mois crible 40000 articles avec extraction de figures. Le plan Enterprise est sur mesure.

## Comparaison avec les alternatives

Consensus excelle pour obtenir un consensus scientifique rapide sur une question fermee, mais Elicit offre des capacites d'extraction plus approfondies. Semantic Scholar reste un moteur de recherche classique sans la couche analytique d'Elicit. Scispace est proche dans son approche mais Elicit est souvent percu comme plus precis pour les tableaux de comparaison.

## Notre avis

Elicit represente une avancee majeure dans l'outillage du chercheur. L'extraction automatisee de donnees, qui prenait des semaines, peut etre ebauchee en minutes. Cependant, l'outil ne remplace pas l'expertise humaine : l'IA peut parfois mal interpreter un contexte. Le modele economique reste accessible pour les chercheurs individuels. En conclusion, Elicit est un assistant indispensable pour naviguer efficacement dans la production scientifique contemporaine.
MD;
    }

    private function consensus(): string
    {
        return <<<'MD'
Dans un ecosysteme ou la desinformation et les hallucinations des modeles generatifs posent probleme, Consensus s'impose comme une solution de confiance. Ce moteur de recherche indexe plus de 220 millions d'articles evalues par les pairs pour fournir des reponses basees exclusivement sur des preuves empiriques.

## A propos de Consensus

Consensus utilise le traitement du langage naturel pour extraire et synthetiser les conclusions issues de la recherche academique. Contrairement a un moteur classique qui privilegie le referencement SEO, Consensus interroge une base de publications scientifiques verifiees. L'outil transforme une question en langage naturel en une requete capable de balayer des millions de documents techniques.

Chaque affirmation est etayee par une citation directe vers une etude publiee, evitant les biais des IA generatives qui peuvent inventer des faits.

## Fonctionnalites principales

Le "Consensus Meter" affiche le degre de consensus parmi les chercheurs (oui, non, peut-etre) sur une question fermee. Le "Deep Search" analyse entre 50 et 200 articles simultanement pour les revues de litterature approfondies.

Le "Study Snapshot" offre un resume structure de chaque article (methodologie, echantillon, resultats). "Ask Paper" propulse par GPT-4 permet d'interagir avec un document specifique. ConsensusGPT integre le moteur dans l'ecosysteme OpenAI.

## Tarification

Le plan Free offre un acces limite avec 25 recherches Pro et 3 Deep Searches par mois. Le plan Pro a 15 dollars par mois offre des recherches illimitees et le Consensus Meter complet. Le plan Deep a 65 dollars par mois maximise les capacites de Deep Search. Les plans Teams et Enterprise sont sur devis.

## Comparaison avec les alternatives

Elicit est le concurrent le plus proche, tres performant pour l'extraction de donnees dans les tableaux. Consensus se distingue par son interface intuitive et le Consensus Meter. Semantic Scholar est gratuit et puissant pour la decouverte mais n'offre pas le meme niveau de synthese automatique. Scite.ai se specialise dans l'analyse des citations (confirmation ou contestation).

## Notre avis

Consensus s'impose comme un outil indispensable pour manipuler de l'information scientifique. La precision des resultats, adossee a 220 millions d'articles, en fait une alternative rigoureuse aux moteurs generalistes. L'integration GPT-4 facilite l'appropriation des concepts complexes. Le gain de productivite est reel. Toutefois, l'esprit critique de l'utilisateur reste essentiel. En conclusion, Consensus offre un equilibre parfait entre accessibilite et rigueur academique.
MD;
    }

    private function semanticScholar(): string
    {
        return <<<'MD'
La recherche scientifique traverse une phase de transformation majeure grace a l'intelligence artificielle. Au coeur de cette revolution se trouve Semantic Scholar, un outil de recherche academique developpe par l'Allen Institute for AI (AI2), indexant plus de 200 millions d'articles de toutes les disciplines.

## A propos de Semantic Scholar

Semantic Scholar n'est pas simplement un index de publications : c'est un moteur de decouverte base sur l'intelligence artificielle. Lance en 2015 par l'Allen Institute for AI, il repose sur une philosophie d'acces libre a la connaissance. Contrairement aux bases de donnees commerciales, cette plateforme est entierement gratuite.

La force reside dans la comprehension du contexte et des relations entre les publications. L'algorithme analyse la structure des documents pour en extraire le sens profond, filtrant le bruit informationnel pour presenter les articles les plus pertinents.

## Fonctionnalites principales

Les resumes "TL;DR" (Too Long; Didn't Read) proposent un resume d'une seule phrase pour chaque article grace a l'IA. Le Semantic Reader est un lecteur PDF augmente qui affiche des fiches descriptives des citations sans quitter la page.

L'analyse des "citations influentes" distingue les mentions simples des citations qui s'appuient reellement sur la methodologie ou les resultats. Les bibliotheques personnelles et le flux de recommandations personnalise agissent comme un veilleur technologique automatise. L'API ouverte permet l'integration dans d'autres outils.

## Tarification

Semantic Scholar est entierement gratuit pour les utilisateurs finaux. Il n'existe pas de version premium payante. L'Allen Institute for AI est finance par des dotations philanthropiques, maintenant ce service comme un bien public mondial. L'API est egalement gratuite pour la plupart des besoins academiques.

## Comparaison avec les alternatives

Google Scholar possede l'index le plus vaste mais manque de fonctionnalites d'analyse semantique, de resumes IA et de distinction entre types de citations. Elicit est performant pour les questions specifiques mais devient payant au-dela d'un certain usage. Scite.ai propose une analyse fine du sentiment des citations mais necessite un abonnement. Connected Papers offre une approche visuelle mais s'appuie souvent sur les donnees de Semantic Scholar via son API.

## Notre avis

Semantic Scholar s'est impose comme un outil indispensable pour la communaute scientifique. L'integration de l'IA n'est pas un gadget mais une reponse concrete a l'infobesite qui frappe la recherche. Les TL;DR et la navigation intelligente dans les PDF representent un gain de temps considerable.

L'algorithme de recommandation s'ameliore avec l'usage. L'ouverture des donnees via l'API temoigne d'une volonte de faire progresser la science collaborativement. Certes, le moteur peut generer des resumes imparfaits ou manquer certaines publications recentes. Mais pour tout professionnel ou etudiant serieux, Semantic Scholar est une necessite pour rester a la pointe de son domaine.
MD;
    }

    private function humata(): string
    {
        return <<<'MD'
La gestion de l'information constitue un defi majeur pour les entreprises et les chercheurs. Humata AI s'impose comme une solution de reference pour l'analyse de documents PDF, transformant la maniere dont nous interagissons avec les textes longs et complexes.

## A propos de Humata

Humata AI utilise l'intelligence artificielle pour simplifier la lecture et l'analyse de documents. Contrairement a un moteur de recherche par mots-cles, cet outil comprend le contexte et la semantique des phrases. Il agit comme un assistant de recherche capable de lire des milliers de pages en quelques secondes pour fournir des reponses precises.

L'interface se distingue par sa sobriete et son efficacite. L'utilisateur telecharge ses documents puis utilise une fenetre de discussion pour interroger le contenu. Humata s'adapte aux jargons techniques les plus pointus : analyses juridiques, revues scientifiques, rapports financiers.

## Fonctionnalites principales

Le systeme de questions-reponses (Q&A) est le coeur de Humata. Chaque affirmation est accompagnee de references directes vers les pages du document source, evitant les hallucinations. Le resume automatique condense un rapport de cent pages en quelques points cles.

La comparaison de documents identifie les differences entre plusieurs versions d'un contrat. L'OCR (Reconnaissance Optique de Caracteres) rend les documents scannes analysables par l'IA. Les modeles de langage de derniere generation (GPT-5) garantissent une comprehension fine. La gestion des permissions d'equipe permet le partage controle des bases de connaissances.

## Tarification

Le plan Free offre 60 pages par mois. Le plan Student a 2 dollars par mois cible le monde academique. Le plan Expert a 10 dollars par mois leve la plupart des restrictions. Le plan Team a 49 dollars par utilisateur par mois inclut gestion d'equipe et uploads illimites. Le plan Enterprise est sur mesure avec deploiements specifiques et securite accrue.

## Comparaison avec les alternatives

ChatPDF est le concurrent le plus direct en simplicite, mais Humata prend l'avantage sur les documents longs et la precision des citations. AskYourPDF propose des extensions de navigateur mais son interface est moins professionnelle. NotebookLM de Google adopte une approche de prise de notes augmentee mais n'offre pas la meme focalisation sur l'analyse de documents bruts.

## Notre avis

Humata AI s'impose comme un outil indispensable pour le travailleur du savoir moderne. L'integration de modeles de langage de pointe permet des resultats d'une finesse etonnante. Le systeme de references cliquables apporte la securite necessaire aux professionnels.

Certaines fonctions avancees sont reservees aux plans onereux, mais la structure tarifaire reste coherente. Pour un analyste ou un chercheur, le gain de temps genere rentabilise rapidement l'investissement. En conclusion, Humata est un levier de productivite reel qui automatise la partie la plus ingrate de la recherche documentaire.
MD;
    }
}
