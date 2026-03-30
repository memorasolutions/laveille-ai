<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class EnrichProductivityToolsSeeder extends Seeder
{
    private string $locale = 'fr_CA';

    public function run(): void
    {
        foreach ($this->tools() as $name => $data) {
            $tool = Tool::where('name->'.$this->locale, $name)->first();
            if (! $tool) {
                $this->command?->warn("Outil '{$name}' non trouvé, ignoré.");
                continue;
            }

            foreach (['description', 'core_features', 'use_cases', 'pros', 'cons'] as $field) {
                if (isset($data[$field])) {
                    $tool->setTranslation($field, $this->locale, $data[$field]);
                }
            }

            if (isset($data['faq'])) {
                $tool->faq = $data['faq'];
            }

            $tool->save();
            $this->command?->info("✅ {$name} enrichi (".mb_strlen($tool->getTranslation('description', $this->locale)).' car.)');
        }
    }

    private function tools(): array
    {
        return [
            'ClickUp Brain' => [
                'description' => <<<'MD'
ClickUp Brain est l'assistant d'intelligence artificielle intégré à ClickUp, l'une des plateformes de gestion de travail les plus complètes du marché. Il permet d'automatiser des processus, de générer du contenu, de résumer des discussions et de retrouver l'information pertinente dans tout l'espace de travail. Lancé comme une extension naturelle de l'écosystème ClickUp, Brain transforme une simple plateforme de gestion de projets en un véritable assistant de productivité propulsé par l'IA.

## À propos de ClickUp Brain

ClickUp s'est imposé comme l'un des outils de gestion de projets les plus polyvalents, offrant des vues multiples (listes, Kanban, Gantt, calendrier), des documents collaboratifs, des objectifs et un suivi du temps. Avec l'ajout de Brain, la plateforme franchit un cap en intégrant l'intelligence artificielle directement dans chaque fonctionnalité. ClickUp Brain ne se limite pas à un chatbot : il comprend le contexte de vos projets, tâches, documents et conversations. Il peut rédiger des mises à jour de statut, résumer des fils de commentaires, proposer des sous-tâches pertinentes et automatiser des workflows complexes sans quitter l'interface. Cette intégration profonde signifie que l'IA a accès à l'ensemble de votre espace de travail, ce qui lui permet de fournir des réponses précises et contextuelles. Pour les équipes francophones, Brain supporte le français dans ses interactions, bien que l'interface principale reste en anglais.

## Fonctionnalités principales

- **Rédaction assistée par IA** : générez des descriptions de tâches, des résumés de projets, des courriels et des rapports directement dans ClickUp.
- **Résumé automatique** : obtenez des synthèses instantanées de fils de discussion, de documents longs ou de mises à jour de projets.
- **Recherche contextuelle en langage naturel** : posez des questions comme « Quelles tâches sont en retard dans le projet X ? » et obtenez des réponses précises.
- **Automatisation intelligente** : créez des automatisations complexes en décrivant simplement ce que vous voulez accomplir en langage naturel.
- **Suggestions basées sur l'historique** : Brain analyse vos habitudes de travail pour proposer des optimisations et des rappels pertinents.
- **Traduction et reformulation** : reformulez ou traduisez du contenu directement dans vos documents et tâches.
- **Création de sous-tâches automatique** : décrivez un objectif et Brain génère les sous-tâches nécessaires pour l'atteindre.

## Tarification

| Plan | Prix/mois | ClickUp Brain | Détails |
|------|-----------|---------------|---------|
| Free Forever | 0 $ | Non disponible | 100 Mo stockage, tâches illimitées |
| Unlimited | 7 $/utilisateur | +7 $/utilisateur (add-on) | Stockage illimité, intégrations, tableaux de bord |
| Business | 12 $/utilisateur | +7 $/utilisateur (add-on) | Tout Unlimited + automatisations avancées, timesheet |
| Enterprise | Sur devis | Inclus ou sur devis | SSO, HIPAA, gestionnaire dédié, formation |

## Comparaison avec les alternatives

Face à **Notion AI** (10 $/utilisateur/mois en add-on), ClickUp Brain offre une intégration plus profonde avec la gestion de projets structurée, tandis que Notion excelle dans la flexibilité documentaire. **Monday.com** propose aussi des fonctionnalités IA, mais son approche est plus rigide et orientée CRM. **Asana Intelligence** se concentre sur la priorisation et les objectifs, mais manque de la polyvalence rédactionnelle de Brain. ClickUp Brain se distingue par sa compréhension contextuelle de l'ensemble de l'espace de travail, là où les concurrents offrent souvent une IA plus cloisonnée.

## Notre avis

Pour les PME québécoises qui utilisent déjà ClickUp ou cherchent une plateforme tout-en-un, Brain représente un investissement judicieux à 7 $/mois par utilisateur. La compréhension contextuelle est impressionnante : pouvoir demander en français « résume les décisions de la semaine dernière » et obtenir une réponse pertinente basée sur vos vrais projets est un gain de temps considérable. Le principal bémol reste l'impossibilité d'utiliser Brain sur le plan gratuit, ce qui oblige les petites équipes à un engagement financier minimal de 14 $/utilisateur/mois (Unlimited + Brain). Pour les organisations déjà investies dans l'écosystème ClickUp, c'est un ajout naturel et puissant. Pour les autres, la courbe d'apprentissage de ClickUp lui-même mérite d'être prise en compte.
MD,
                'core_features' => 'Rédaction assistée, Résumé automatique, Recherche contextuelle en langage naturel, Automatisation intelligente, Suggestions basées sur l\'historique',
                'use_cases' => 'Gestion de projets PME, Rédaction de rapports, Synthèse de réunions, Récupération d\'informations, Automatisation administrative',
                'pros' => 'Intégration native ClickUp, Compréhension contextuelle, Support du français, Sécurité renforcée, Automatisation puissante',
                'cons' => 'Coût additionnel 7 $/mois/utilisateur, Indisponible plan Free, Courbe d\'apprentissage',
                'faq' => [
                    ['question' => 'ClickUp Brain fonctionne-t-il en français?', 'answer' => 'Oui, il gère le français canadien pour les documents et requêtes.'],
                    ['question' => 'Mes données sont-elles sécurisées?', 'answer' => 'Oui, l\'IA opère dans votre espace sécurisé, conforme à la Loi 25.'],
                    ['question' => 'Peut-on utiliser Brain sans plan payant?', 'answer' => 'Non, Brain nécessite minimum le plan Unlimited + add-on 7$/mois.'],
                ],
            ],

            'Fireflies' => [
                'description' => <<<'MD'
Fireflies est un assistant intelligent conçu pour transformer la gestion des réunions. Il capture automatiquement les conversations, les transcrit avec précision, génère des résumés intelligents et extrait les points d'action. Que vous soyez gestionnaire de projet, consultant ou membre d'une équipe agile, Fireflies élimine le besoin de prendre des notes manuellement et garantit qu'aucune information critique ne se perde après une rencontre.

## À propos de Fireflies

Fondée en 2016, Fireflies.ai s'est rapidement positionnée comme l'une des solutions de transcription et d'analyse de réunions les plus populaires au monde. Son assistant virtuel, nommé Fred, peut rejoindre automatiquement vos réunions sur Zoom, Google Meet, Microsoft Teams, Webex et bien d'autres plateformes. L'outil ne se contente pas de transcrire : il analyse la conversation pour en extraire les décisions, les tâches assignées et les moments clés. Fireflies utilise des modèles de traitement du langage naturel avancés pour offrir une compréhension sémantique du contenu, permettant une recherche intelligente dans l'historique des réunions. Pour les équipes distribuées ou hybrides, c'est un outil qui centralise la mémoire institutionnelle de l'organisation.

## Fonctionnalités principales

- **Transcription automatique temps réel** : Fred rejoint vos réunions et transcrit chaque mot avec identification des intervenants.
- **Résumés intelligents** : synthèse automatique des points clés, décisions et prochaines étapes après chaque réunion.
- **Extraction d'action items** : identification automatique des tâches assignées avec attribution aux participants.
- **Intégrations Zoom/Teams/Meet** : connexion native avec les principales plateformes de visioconférence.
- **Recherche sémantique** : retrouvez n'importe quelle information mentionnée dans n'importe quelle réunion passée.
- **Analyse de participation** : statistiques sur le temps de parole, la dynamique de conversation et l'engagement des participants.
- **Intégrations CRM et PM** : synchronisation avec Salesforce, HubSpot, Asana, Trello, Slack et plus encore.

## Tarification

| Plan | Prix/mois | Détails |
|------|-----------|---------|
| Free | 0 $ | 800 minutes de stockage, transcription limitée, résumés IA |
| Pro | 18 $/utilisateur | Transcription illimitée, résumés avancés, intégrations CRM |
| Business | 29 $/utilisateur | Analyse de conversation, insights équipe, API, support prioritaire |
| Enterprise | Sur devis | SSO, HIPAA, SLA dédié, gestionnaire de compte |

## Comparaison avec les alternatives

Face à **Otter.ai**, Fireflies offre de meilleures intégrations avec les outils de gestion de projets et les CRM, tandis qu'Otter excelle dans la collaboration en temps réel sur les transcriptions. **Fathom** propose une interface plus épurée et un plan gratuit plus généreux, mais manque de la profondeur analytique de Fireflies. **Grain** se spécialise dans le découpage de moments clés en vidéo, complémentaire mais moins complet pour la transcription intégrale. Fireflies se distingue par son écosystème d'intégrations et ses capacités d'analyse conversationnelle avancées.

## Notre avis

Pour les équipes québécoises travaillant en mode hybride ou à distance, Fireflies est un outil qui peut transformer la productivité des réunions. Le plan Pro à 18 $/mois par utilisateur est raisonnable si l'on considère le temps gagné en prise de notes et en suivi post-réunion. Le support multilingue, incluant le français, fonctionne correctement même si la précision peut varier avec les accents québécois prononcés. L'extraction automatique des tâches et la synchronisation avec des outils comme Asana ou Slack en font un allié précieux pour les PME. Le plan gratuit avec 800 minutes de stockage permet de tester l'outil avant de s'engager, ce qui est appréciable.
MD,
                'core_features' => 'Transcription automatique temps réel, Résumés intelligents, Extraction d\'action items, Intégrations Zoom/Teams/Meet, Recherche sémantique, Analyse de participation',
                'use_cases' => 'Suivi réunions clients, Documentation stand-ups agile, Archivage décisions, Rapports post-réunion, Formation et onboarding',
                'pros' => 'Interface intuitive, Forte automatisation post-réunion, Excellentes intégrations, Bon rapport qualité-prix, Support multilingue',
                'cons' => 'Plan gratuit limité (800 min stockage), Fonctions avancées en Business/Enterprise, Précision variable avec accents',
                'faq' => [
                    ['question' => 'Peut-on utiliser Fireflies sans être l\'hôte?', 'answer' => 'Oui, l\'assistant Fred peut rejoindre n\'importe quelle réunion.'],
                    ['question' => 'Les données sont-elles sécurisées?', 'answer' => 'Oui, chiffrement en transit et au repos. RGPD et SOC 2 sur Business/Enterprise.'],
                    ['question' => 'Combien de langues supportées?', 'answer' => 'Plus de 60 langues, dont le français.'],
                ],
            ],

            'Otter.ai' => [
                'description' => <<<'MD'
Otter.ai est l'un des pionniers de la transcription IA pour les réunions. Il fournit des transcriptions en temps réel, identifie les intervenants, génère des résumés et extrait les tâches à accomplir grâce à OtterPilot. Depuis son lancement, la plateforme s'est imposée comme une référence pour les professionnels et les étudiants cherchant à capturer et organiser l'information parlée de manière efficace et automatisée.

## À propos de Otter.ai

Fondée en 2016 par Sam Liang, ancien ingénieur chez Google, Otter.ai a été l'une des premières plateformes à démocratiser la transcription IA en temps réel. L'outil s'est rapidement fait connaître dans le milieu académique américain avant de s'étendre au monde professionnel. OtterPilot, son assistant IA phare, peut rejoindre automatiquement vos réunions Zoom, Google Meet et Microsoft Teams pour transcrire, résumer et extraire les éléments d'action sans intervention humaine. La force d'Otter réside dans sa capacité de diarisation (identification des intervenants), sa collaboration en temps réel sur les transcriptions et son interface épurée qui rend la relecture intuitive. La plateforme propose également un mode académique optimisé pour les cours magistraux et les séminaires.

## Fonctionnalités principales

- **Transcription temps réel avec identification des locuteurs** : diarisation distinguant jusqu'à 6 voix simultanément.
- **Résumés automatiques** : synthèse structurée avec points clés, décisions et questions soulevées.
- **Extraction d'action items** : identification et assignation automatique des tâches mentionnées en réunion.
- **OtterPilot auto-join** : l'assistant rejoint automatiquement vos réunions planifiées via la synchronisation calendrier.
- **Partage collaboratif** : surlignage, commentaires, annotations et partage de transcriptions en temps réel.
- **Intégrations Zoom/Meet/Teams** : connexion native avec les principales plateformes de visioconférence.
- **Export PDF/DOC/SRT** : téléchargez vos transcriptions dans les formats les plus courants pour archivage ou sous-titrage.

## Tarification

| Plan | Prix/mois | Détails |
|------|-----------|---------|
| Basic | 0 $ | 300 minutes/mois, transcription en temps réel, résumés IA |
| Pro | 16,99 $/utilisateur | 1 200 minutes/mois, OtterPilot, import audio/vidéo, vocabulaire personnalisé |
| Business | 30 $/utilisateur | 6 000 minutes/mois, analytiques, intégrations CRM, admin centralisé |

## Comparaison avec les alternatives

Comparé à **Fireflies**, Otter.ai offre une meilleure expérience de collaboration en temps réel sur les transcriptions, mais dispose de moins d'intégrations avec les outils de gestion de projets. Face à **Fathom**, Otter propose plus de minutes sur le plan gratuit et un meilleur support académique. **Rev** offre une transcription humaine plus précise, mais à un coût nettement supérieur. **Microsoft Copilot** dans Teams intègre la transcription nativement, mais nécessite un abonnement Microsoft 365 Copilot coûteux. Otter se distingue par son équilibre entre simplicité d'utilisation, collaboration et rapport qualité-prix.

## Notre avis

Pour les professionnels et étudiants québécois, Otter.ai est une solution fiable et bien rodée. Le plan Basic gratuit avec 300 minutes par mois est suffisant pour tester l'outil ou pour un usage occasionnel. Le Pro à 16,99 $ offre un excellent rapport qualité-prix pour les utilisateurs réguliers. L'interface est parmi les plus agréables du marché, et la collaboration en temps réel sur les transcriptions est un vrai plus pour les équipes. Le principal bémol pour les francophones reste que la transcription en français, bien que fonctionnelle, n'atteint pas encore la précision de l'anglais, surtout avec les particularités de l'accent québécois. Pour des réunions principalement en anglais ou en français standard, c'est un excellent choix.
MD,
                'core_features' => 'Transcription temps réel avec identification locuteurs, Résumés automatiques, Extraction d\'action items, OtterPilot auto-join, Partage collaboratif, Intégrations Zoom/Meet/Teams, Export PDF/DOC/SRT',
                'use_cases' => 'Notes en cours pour étudiants, Documentation entretiens clients, Archivage réunions CA, Sous-titrage vidéo, Suivi décisions R&D',
                'pros' => 'Haute précision, Interface épurée, OtterPilot pratique, Bon support académique, Synchronisation calendriers',
                'cons' => 'Plan gratuit limité à 300 min/mois, Minutes non reportables, Intégrations tierces moins développées que Fireflies',
                'faq' => [
                    ['question' => 'Otter.ai transcrit-il des fichiers préenregistrés?', 'answer' => 'Oui, sur les plans Pro et Business.'],
                    ['question' => 'Plusieurs personnes peuvent collaborer?', 'answer' => 'Oui, partage, commentaires, surlignage et assignation de tâches.'],
                    ['question' => 'Comment gère-t-il plusieurs intervenants?', 'answer' => 'Diarisation distinguant jusqu\'à 6 voix.'],
                ],
            ],

            'Motion' => [
                'description' => <<<'MD'
Motion est un calendrier intelligent propulsé par l'intelligence artificielle qui prend en charge la planification automatique de vos tâches, réunions et projets. L'IA analyse vos deadlines, priorités et disponibilités pour optimiser votre journée en temps réel. Plutôt que de passer du temps à organiser votre emploi du temps, Motion s'en charge pour vous, vous permettant de vous concentrer sur l'exécution.

## À propos de Motion

Lancé en 2019, Motion est né d'un constat simple : les professionnels passent trop de temps à planifier leur journée au lieu de travailler. L'approche de Motion est radicale — vous ajoutez vos tâches avec leurs deadlines et priorités, et l'IA construit automatiquement votre emploi du temps optimal. Si une réunion est ajoutée ou une tâche prend plus de temps que prévu, Motion replanifie dynamiquement le reste de votre journée en temps réel. Cette approche « set it and forget it » distingue Motion de tous les autres outils de productivité. La plateforme s'adresse aussi bien aux entrepreneurs solo qu'aux équipes, avec des fonctionnalités de gestion de projets Kanban et d'assignation de tâches. L'IA tient compte des fuseaux horaires, des préférences personnelles (comme éviter les réunions le matin) et des dépendances entre tâches.

## Fonctionnalités principales

- **Planification automatique par IA** : ajoutez tâches et deadlines, l'IA construit votre emploi du temps optimal.
- **Calendrier intelligent avec replanification dynamique** : chaque changement déclenche une réorganisation automatique de votre journée.
- **Priorisation automatique selon deadlines** : l'IA identifie les tâches urgentes et les place en priorité.
- **Gestion de projets Kanban** : visualisez et gérez vos projets avec des tableaux personnalisables.
- **Assignation tâches équipe** : distribuez le travail et l'IA optimise l'emploi du temps de chaque membre.
- **Protection blocs temps personnel** : définissez des créneaux protégés (travail profond, pause, sport) que l'IA respecte.
- **Intégrations Google Calendar/Outlook** : synchronisation bidirectionnelle avec vos calendriers existants.

## Tarification

| Plan | Prix/mois | Détails |
|------|-----------|---------|
| Individual | 19 $/utilisateur | Planification IA illimitée, calendrier intelligent, intégrations |
| Team | 12 $/utilisateur | Tout Individual + gestion de projets, assignation, collaboration |

## Comparaison avec les alternatives

Face à **Reclaim AI**, Motion offre une automatisation plus agressive — il planifie tout automatiquement, tandis que Reclaim se concentre sur la protection d'habitudes et la planification douce. **Clockwise** optimise principalement les réunions d'équipe, mais manque de gestion de tâches. **Todoist** et **TickTick** sont d'excellents gestionnaires de tâches, mais sans planification calendrier automatisée. **Google Calendar** avec ses fonctions IA reste basique comparé à Motion. Motion se distingue par son approche « tout automatiser » qui élimine la friction de la planification manuelle.

## Notre avis

Motion est une révélation pour les entrepreneurs, freelances et gestionnaires de projets québécois qui jonglent avec de multiples priorités. L'absence de plan gratuit (minimum 19 $/mois en individuel ou 12 $/mois en équipe) peut freiner, mais l'essai gratuit de 7 jours permet de constater rapidement la valeur ajoutée. Pour ceux qui passent 30 minutes ou plus par jour à réorganiser leur emploi du temps, Motion peut littéralement leur redonner des heures chaque semaine. Le principal défi est psychologique : il faut accepter de « lâcher prise » et faire confiance à l'IA pour organiser sa journée. Une fois cette barrière franchie, Motion devient un outil dont il est difficile de se passer. Recommandé pour les professionnels qui valorisent leur temps au-dessus de tout.
MD,
                'core_features' => 'Planification automatique par IA, Calendrier intelligent avec replanification dynamique, Priorisation automatique selon deadlines, Gestion de projets Kanban, Assignation tâches équipe, Protection blocs temps personnel, Intégrations Google Calendar/Outlook',
                'use_cases' => 'Planification quotidienne entrepreneurs, Gestion multi-clients freelances, Coordination projets équipe, Respect automatique deadlines, Équilibrage charge travail',
                'pros' => 'Planification entièrement automatisée, Replanification dynamique en temps réel, Gestion projets intégrée, Plan Team abordable 12$/mois, Interface intuitive',
                'cons' => 'Aucun plan gratuit, Courbe d\'apprentissage initiale, Peut sembler trop contrôlant, Dépend de la précision des infos entrées',
                'faq' => [
                    ['question' => 'Motion offre-t-il un essai gratuit?', 'answer' => 'Oui, 7 jours d\'essai gratuit, mais pas de plan gratuit permanent.'],
                    ['question' => 'Motion fonctionne-t-il avec Google Calendar?', 'answer' => 'Oui, synchronisation bidirectionnelle avec Google Calendar et Outlook.'],
                    ['question' => 'Motion convient-il aux grandes équipes?', 'answer' => 'Bien pour petites et moyennes équipes. Les grandes organisations peuvent combiner avec Asana ou Monday.'],
                ],
            ],

            'Reclaim AI' => [
                'description' => <<<'MD'
Reclaim AI est un outil de planification intelligente qui s'intègre à votre calendrier Google pour optimiser automatiquement votre emploi du temps. Il protège vos habitudes quotidiennes et planifie vos réunions de façon optimale. Contrairement aux outils qui imposent une planification rigide, Reclaim adopte une approche douce et progressive qui s'adapte à votre rythme de travail naturel.

## À propos de Reclaim AI

Fondé en 2019 par d'anciens ingénieurs de New Relic, Reclaim AI est né de la frustration de voir des journées entières dévorées par des réunions mal planifiées et des habitudes de travail constamment interrompues. L'outil se distingue par sa philosophie : plutôt que de tout automatiser de manière agressive, il protège intelligemment vos créneaux importants (travail profond, pause déjeuner, exercice) tout en trouvant les meilleurs moments pour vos réunions et tâches. Reclaim s'intègre nativement avec Google Calendar et, plus récemment, Outlook. Il se connecte également à des gestionnaires de tâches populaires comme Todoist, Asana, Jira, ClickUp et Linear, ce qui lui permet de planifier automatiquement vos tâches dans votre calendrier. L'IA apprend de vos préférences au fil du temps pour affiner ses suggestions.

## Fonctionnalités principales

- **Habitudes protégées avec repositionnement auto** : définissez des routines (travail profond, pause, sport) que l'IA protège et repositionne intelligemment en cas de conflit.
- **Planification intelligente tâches** : synchronisez vos tâches depuis Todoist, Asana ou Jira et Reclaim les place dans votre calendrier.
- **Smart Meeting Links** : trouvez automatiquement les meilleurs créneaux pour vos réunions en fonction des disponibilités de tous les participants.
- **Synchronisation multi-calendriers** : gérez plusieurs calendriers (professionnel, personnel) sans conflits.
- **Statistiques d'utilisation du temps** : visualisez comment vous passez vos journées avec des rapports détaillés.
- **Intégrations Todoist/Asana/Jira/ClickUp/Slack** : connectez vos outils de travail existants pour une planification unifiée.

## Tarification

| Plan | Prix/mois | Détails |
|------|-----------|---------|
| Free | 0 $ | 3 habitudes, planification de base, Smart Meeting Links |
| Starter | 10 $/utilisateur | Habitudes illimitées, tâches intelligentes, intégrations avancées |
| Business | 15 $/utilisateur | Analytiques équipe, planification collaborative, support prioritaire |
| Enterprise | Sur devis | SSO, sécurité renforcée, SLA dédié |

## Comparaison avec les alternatives

Face à **Motion**, Reclaim adopte une approche plus douce et progressive. Motion planifie tout automatiquement et de manière agressive, tandis que Reclaim protège d'abord vos habitudes et optimise ensuite. **Clockwise** se concentre sur l'optimisation des réunions d'équipe, mais offre moins de fonctionnalités pour les tâches individuelles. **Cal.com** est excellent pour la prise de rendez-vous, mais ne gère pas la planification de tâches. **Sunsama** propose une belle interface de planification quotidienne, mais manque d'automatisation IA. Reclaim se démarque par son équilibre entre automatisation intelligente et respect du rythme naturel de l'utilisateur.

## Notre avis

Pour les professionnels québécois qui veulent reprendre le contrôle de leur emploi du temps sans se sentir dirigés par une IA, Reclaim AI est un excellent choix. Le plan gratuit avec 3 habitudes protégées est suffisant pour découvrir la valeur de l'outil, et le Starter à 10 $/mois est très raisonnable. Les statistiques d'utilisation du temps sont révélatrices — beaucoup d'utilisateurs découvrent qu'ils passent bien plus de temps en réunion qu'ils ne le pensaient. L'intégration avec Todoist ou Asana est particulièrement précieuse : vos tâches se planifient automatiquement dans votre calendrier, ce qui élimine la friction entre « planifier » et « faire ». Le principal bémol reste la dépendance à Google Calendar, même si le support Outlook progresse. Recommandé pour ceux qui cherchent une automatisation progressive et respectueuse de leurs habitudes.
MD,
                'core_features' => 'Habitudes protégées avec repositionnement auto, Planification intelligente tâches, Smart Meeting Links, Synchronisation multi-calendriers, Statistiques d\'utilisation du temps, Intégrations Todoist/Asana/Jira/ClickUp/Slack',
                'use_cases' => 'Protection temps travail profond, Routines durables professionnels, Synchronisation tâches depuis Todoist/Asana, Planification optimale réunions, Analyse temps passé, Équilibrage charge équipe',
                'pros' => 'Plan gratuit disponible, Approche douce d\'automatisation, Habitudes protégées, Excellentes intégrations, Statistiques révélatrices, Tarification progressive',
                'cons' => 'Principalement Google Calendar, Plan gratuit limité à 3 habitudes, Moins de contrôle que Motion, Replanification parfois fragmentée',
                'faq' => [
                    ['question' => 'Reclaim AI fonctionne-t-il avec Outlook?', 'answer' => 'Oui, support ajouté récemment, mais plus limité que Google Calendar.'],
                    ['question' => 'Différence habitudes vs tâches?', 'answer' => 'Habitudes = récurrentes (travail profond chaque matin). Tâches = ponctuelles avec deadline.'],
                    ['question' => 'Le plan gratuit suffit-il?', 'answer' => 'Pour 3 habitudes et de la planification de base, oui. Au-delà, le Starter à 10$/mois devient nécessaire.'],
                ],
            ],

            'Taskade' => [
                'description' => <<<'MD'
Taskade se positionne comme la solution tout-en-un pour les équipes modernes qui veulent centraliser gestion de projets, notes, mind maps et intelligence artificielle sous un même toit. Avec des agents IA personnalisables, Taskade promet d'amplifier la productivité sans complexité. C'est un outil qui séduit particulièrement les startups et les freelances à la recherche d'une plateforme unifiée et abordable.

## À propos de Taskade

Lancé en 2017, Taskade a évolué d'un simple outil de listes de tâches vers une plateforme de productivité complète intégrant l'intelligence artificielle à chaque niveau. Ce qui distingue Taskade, c'est sa capacité à basculer instantanément entre différentes vues (listes, Kanban, calendrier, mind maps, organigrammes) tout en maintenant la même base de données. L'ajout d'agents IA personnalisables en 2024 a marqué un tournant : ces agents peuvent être configurés pour rédiger du contenu, résumer des documents, générer des plans de projet ou répondre à des questions spécifiques à votre contexte. Taskade mise sur la simplicité et la collaboration en temps réel, avec une interface épurée qui contraste avec la complexité de certains concurrents. La plateforme est multiplateforme (web, iOS, Android, desktop) et supporte le travail hors ligne.

## Fonctionnalités principales

- **Gestion de projets multi-vues** : basculez entre Kanban, listes, calendrier, mind maps et organigrammes.
- **Agents IA personnalisables** : créez des assistants IA spécialisés pour la rédaction, le résumé, la planification ou toute autre tâche.
- **Chat collaboratif intégré** : communication en temps réel directement dans vos projets.
- **Automatisations IA sans code** : créez des workflows automatisés en décrivant simplement ce que vous voulez accomplir.
- **Mind mapping intelligent** : brainstorming visuel avec suggestions IA pour développer vos idées.
- **Notes collaboratives temps réel** : édition simultanée avec curseurs visibles et historique des versions.
- **Génération de contenu par IA** : rédaction, reformulation, traduction et expansion de texte directement dans vos documents.

## Tarification

| Plan | Prix/mois | Détails |
|------|-----------|---------|
| Free | 0 $ | 1 000 crédits IA, projets limités, 250 Mo stockage |
| Pro | 8 $/utilisateur | Crédits IA étendus, projets illimités, 5 Go stockage, agents personnalisés |
| Business | 16 $/utilisateur | Tout Pro + permissions avancées, analytiques, 20 Go stockage, support prioritaire |

## Comparaison avec les alternatives

Face à **Notion**, Taskade offre une interface plus légère et des agents IA plus flexibles, mais Notion excelle avec ses bases de données relationnelles et son écosystème de templates. **ClickUp** est plus complet en gestion de projets pure, mais aussi plus complexe et plus cher. **Monday.com** cible davantage les moyennes entreprises avec des workflows structurés. **Coda** propose une approche document-first puissante mais avec une courbe d'apprentissage plus prononcée. Taskade se démarque par sa simplicité, son prix accessible et ses agents IA personnalisables, ce qui en fait un choix idéal pour les petites équipes et les freelances.

## Notre avis

Pour les startups et freelances québécois cherchant une plateforme tout-en-un abordable, Taskade est une découverte agréable. Le plan Pro à 8 $/utilisateur/mois avec agents IA personnalisés est probablement l'un des meilleurs rapports qualité-prix du marché. Le mind mapping intégré est un vrai plus pour les sessions de brainstorming, et la possibilité de basculer entre vues sans perdre de données est très pratique. Les agents IA, une fois bien configurés, peuvent accélérer significativement la rédaction et la planification. Le principal bémol est que Taskade ne peut pas encore rivaliser avec Notion pour les bases de données complexes ou avec ClickUp pour la gestion de projets d'envergure. Mais pour 80 % des besoins d'une petite équipe, c'est une solution élégante et complète.
MD,
                'core_features' => 'Gestion de projets multi-vues (Kanban, listes, calendrier, mind maps), Agents IA personnalisables, Chat collaboratif intégré, Automatisations IA sans code, Mind mapping intelligent, Notes collaboratives temps réel, Génération de contenu par IA',
                'use_cases' => 'Gestion projets startups, Brainstorming mind maps IA, Notes collaboratives réunion, Automatisation tâches répétitives, Planification sprints, Documentation wiki équipe',
                'pros' => 'Interface intuitive, Agents IA personnalisables, Excellent rapport qualité-prix plan Pro, Collaboration temps réel, Multiplateforme, Mind mapping intégré',
                'cons' => 'Crédits IA limités plan gratuit, Intégrations tierces restreintes, Moins adapté grandes organisations, Base de données moins avancée que Notion',
                'faq' => [
                    ['question' => 'Taskade peut-il remplacer Notion et Slack?', 'answer' => 'Pour la majorité des petites équipes et freelances, oui. Pour des bases de données complexes Notion ou intégrations avancées Slack, possiblement pas à 100%.'],
                    ['question' => 'Les agents IA sont-ils utiles?', 'answer' => 'Oui, configurables pour rédaction, résumé, plans de projet. Très pratiques une fois bien paramétrés.'],
                    ['question' => 'Taskade vaut-il la peine en solo?', 'answer' => 'Absolument, le plan Pro à 8$/mois avec agents IA est très raisonnable pour un freelance.'],
                ],
            ],

            'Zapier AI' => [
                'description' => <<<'MD'
Avec plus de 7 000 intégrations et une couche d'intelligence artificielle de plus en plus sophistiquée, Zapier AI s'impose comme le leader de l'automatisation no-code. Que vous soyez marketeur, gestionnaire d'opérations ou PME, Zapier transforme la façon de travailler en éliminant les tâches manuelles. En 2026, l'ajout de fonctionnalités IA comme la création d'automatisations en langage naturel et les chatbots propulsés par IA consolide sa position dominante.

## À propos de Zapier AI

Fondé en 2011, Zapier est le pionnier de l'automatisation no-code. Sa mission est simple : connecter les applications que vous utilisez déjà pour automatiser les tâches répétitives. Un « Zap » est une automatisation composée d'un déclencheur et d'une ou plusieurs actions. Par exemple : « quand un formulaire Google est soumis, créer une ligne dans Airtable, envoyer un courriel de confirmation et ajouter un contact dans HubSpot ». Avec l'intégration de l'IA, Zapier franchit un nouveau cap. Vous pouvez désormais décrire en français ce que vous voulez automatiser, et l'IA construit le Zap pour vous. Zapier Tables offre une base de données intégrée, et les chatbots IA permettent de créer des assistants qui interagissent avec vos données et déclenchent des automatisations. Le catalogue de 7 000+ applications fait de Zapier la plateforme la plus connectée du marché.

## Fonctionnalités principales

- **Connexion 7 000+ applications** : le plus grand catalogue d'intégrations du marché, couvrant CRM, marketing, comptabilité, e-commerce et plus.
- **Zaps intelligents alimentés par IA** : l'IA suggère des optimisations et détecte les erreurs dans vos automatisations.
- **Création d'automatisations en langage naturel** : décrivez ce que vous voulez en français et l'IA construit le Zap.
- **Zapier Tables** : base de données intégrée pour stocker et manipuler des données sans outil externe.
- **Chatbots IA** : créez des assistants conversationnels connectés à vos applications et données.
- **Chemins conditionnels et logique avancée** : créez des workflows avec des branchements si/sinon pour des automatisations complexes.
- **Auto-replay en cas d'erreur** : les tâches échouées sont automatiquement relancées pour garantir la fiabilité.

## Tarification

| Plan | Prix/mois | Tâches incluses | Détails |
|------|-----------|-----------------|---------|
| Free | 0 $ | 100 tâches | Zaps simples (1 déclencheur, 1 action), 5 Zaps actifs |
| Starter | 20 $ | 750 tâches | Zaps multi-étapes, filtres, mise en forme |
| Professional | 50 $ | 2 000 tâches | Chemins conditionnels, versions, auto-replay |
| Team | 70 $ | 2 000 tâches | Tout Professional + collaboration, permissions, dossiers partagés |

## Comparaison avec les alternatives

Face à **Make** (anciennement Integromat), Zapier offre un catalogue d'intégrations bien plus vaste (7 000+ vs ~1 500) et une interface plus intuitive, mais Make est souvent plus flexible et économique pour les workflows visuels complexes. **n8n** est une alternative open source puissante pour les développeurs, mais exige des compétences techniques. **Power Automate** de Microsoft s'intègre parfaitement à l'écosystème Microsoft 365, mais est limité en dehors de cet univers. **IFTTT** est plus simple mais beaucoup moins puissant, principalement orienté IoT et usage personnel. Zapier se distingue par son accessibilité, la taille de son catalogue et la maturité de sa plateforme depuis 2011.

## Notre avis

Pour les PME et marketeurs québécois, Zapier est souvent le premier outil d'automatisation adopté — et pour de bonnes raisons. La possibilité de décrire une automatisation en langage naturel et de la voir se construire automatiquement est impressionnante. Le plan gratuit avec 100 tâches par mois permet de tester, mais les PME actives auront rapidement besoin du Starter à 20 $/mois ou du Professional à 50 $/mois. La tarification par tâches peut devenir coûteuse pour les entreprises à haut volume — dans ce cas, Make peut être plus économique. Néanmoins, pour la majorité des PME qui automatisent entre 500 et 2 000 tâches par mois, Zapier offre le meilleur compromis entre facilité d'utilisation, fiabilité et étendue des intégrations. Un outil essentiel dans la boîte à outils numérique de toute entreprise moderne.
MD,
                'core_features' => 'Connexion 7 000+ applications, Zaps intelligents alimentés par IA, Création d\'automatisations en langage naturel, Zapier Tables (base de données), Chatbots IA, Chemins conditionnels et logique avancée, Auto-replay en cas d\'erreur',
                'use_cases' => 'Automatisation marketing, Synchronisation CRM, Automatisation processus RH, Gestion commandes e-commerce, Qualification leads par chatbot, Reporting automatisé, Support client automatisé',
                'pros' => 'Plus grand catalogue d\'intégrations (7 000+ apps), Interface intuitive, IA puissante, Fiabilité depuis 2011, Création en langage naturel, Documentation riche',
                'cons' => 'Tarification par tâches peut coûter cher, Plan gratuit limité (100 tâches), Moins flexible que Make pour workflows complexes, Fréquence 15 min sur plans entrée',
                'faq' => [
                    ['question' => 'Combien de tâches consomme une PME?', 'answer' => '500 à 2 000 tâches/mois typiquement. Chaque action dans un Zap = 1 tâche.'],
                    ['question' => 'Zapier vs Make pour un non-technicien?', 'answer' => 'Zapier est plus intuitif avec un plus grand catalogue. Make est plus flexible mais avec une courbe d\'apprentissage.'],
                    ['question' => 'Les chatbots remplacent-ils Intercom?', 'answer' => 'Pour des cas simples (FAQ, qualification leads), oui. Pour du support avancé, non.'],
                ],
            ],
        ];
    }
}
