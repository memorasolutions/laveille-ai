<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class EnrichVideoToolsSeeder extends Seeder
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
            'D-ID' => [
                'description' => <<<'MD'
D-ID est une plateforme d'intelligence artificielle spécialisée dans la création de vidéos avec des avatars parlants ultra-réalistes. Fondée en Israël, elle permet de transformer du texte ou un fichier audio en vidéo professionnelle avec un présentateur virtuel animé par l'IA. La plateforme s'adresse aux entreprises, éducateurs, marketeurs et créateurs de contenu qui souhaitent produire rapidement des vidéos engageantes sans caméra ni studio.

## À propos de D-ID

D-ID a été fondée en 2017 en Israël avec une mission initiale axée sur la protection de la vie privée par la technologie de reconnaissance faciale. L'entreprise a depuis pivoté vers la création de vidéos IA, devenant l'une des plateformes les plus accessibles pour générer des avatars parlants réalistes. Son moteur d'animation faciale, combiné à la synthèse vocale en plus de 120 langues, permet de produire des vidéos professionnelles en quelques minutes. D-ID s'adresse aussi bien aux grandes entreprises qu'aux créateurs individuels, avec une API robuste pour l'intégration dans des flux de travail existants.

## Fonctionnalités principales

- **Avatars IA réalistes** : choisissez parmi une bibliothèque d'avatars prédéfinis ou créez le vôtre à partir d'une simple photo.
- **Synthèse vocale en 120+ langues** : générez une narration naturelle dans la langue et l'accent de votre choix, incluant le français.
- **Personnalisation d'avatar** : personnalisez l'apparence, la tenue et l'arrière-plan de votre présentateur virtuel.
- **Intégration API** : automatisez la production vidéo dans vos applications et plateformes existantes.
- **Éditeur vidéo intégré** : ajoutez du texte, des images et des éléments graphiques directement dans l'interface.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free Trial | Gratuit | 5 minutes de vidéo, filigrane, accès limité aux avatars |
| Lite | 26 $/mois | 10 minutes/mois, export HD sans filigrane, tous les avatars |
| Pro | 88 $/mois | 30 minutes/mois, avatar personnalisé, API, priorité support |
| Enterprise | Sur devis | Minutes illimitées, avatar sur mesure, SLA dédié |

## Comparaison avec les alternatives

Face à **Synthesia**, D-ID offre un prix d'entrée plus bas et une API plus accessible, mais Synthesia propose davantage d'avatars et l'export SCORM. **HeyGen** se distingue par ses fonctionnalités de traduction vidéo automatique, tandis que D-ID mise sur la simplicité et la rapidité. **Colossyan** offre plus de scénarios prédéfinis pour la formation, mais D-ID reste plus abordable pour les petits projets et les créateurs individuels.

## Notre avis

Pour les PME québécoises, les CEGEPs et les créateurs de contenu francophones, D-ID représente une porte d'entrée accessible dans le monde des vidéos IA avec avatars. Le support du français, l'interface intuitive et le prix abordable du plan Lite en font un choix judicieux pour produire rapidement des vidéos explicatives, des modules de formation ou du contenu marketing. Le plan Pro ou Enterprise est recommandé pour les institutions publiques soucieuses de la conformité avec la Loi 25 sur la protection des données personnelles.
MD,
                'core_features' => 'Avatars IA réalistes, Synthèse vocale en 120+ langues, Personnalisation d\'avatar, Intégration API, Éditeur vidéo intégré',
                'use_cases' => 'Marketing numérique, Formation e-learning, Communication RH, Contenu réseaux sociaux, Vidéos explicatives, Onboarding',
                'pros' => 'Prix abordable, Support du français canadien, Interface intuitive, Export HD sans filigrane dès le plan Lite',
                'cons' => 'Limite stricte de minutes vidéo, Avatar personnalisé uniquement sur Pro/Enterprise, Moins de scénarios prédéfinis que Colossyan',
                'faq' => [
                    ['question' => 'Peut-on utiliser D-ID en français québécois ?', 'answer' => 'Oui, D-ID supporte le français avec plusieurs accents, incluant des options proches du français canadien.'],
                    ['question' => 'Combien de temps pour créer une vidéo ?', 'answer' => 'Moins de 10 minutes : tapez un texte, choisissez un avatar et une voix, la vidéo est prête en quelques minutes.'],
                    ['question' => 'D-ID convient-il aux institutions publiques québécoises ?', 'answer' => 'Oui, grâce au respect du RGPD et aux options de confidentialité. Le plan Pro ou Enterprise est recommandé.'],
                ],
            ],

            'Haiper' => [
                'description' => <<<'MD'
Haiper est un outil de génération vidéo par intelligence artificielle conçu pour transformer rapidement du texte ou des images en vidéos dynamiques et réalistes. Fondé par d'anciens chercheurs de Google DeepMind, Haiper se distingue par sa simplicité, sa rapidité de rendu et la qualité visuelle de ses animations.

## À propos de Haiper

Lancé en 2023 par d'anciens chercheurs de Google DeepMind, Haiper s'est rapidement positionné comme un acteur prometteur dans le domaine de la génération vidéo par IA. La plateforme cible principalement les créateurs de contenu et les PME qui souhaitent produire des vidéos courtes et percutantes pour les réseaux sociaux. Son approche text-to-video et image-to-video permet de transformer une idée en clip vidéo en quelques secondes, avec un rendu HD même sur le plan gratuit.

## Fonctionnalités principales

- **Génération text-to-video** : transformez une description textuelle en vidéo animée en quelques secondes.
- **Animation image-to-video** : donnez vie à vos images statiques avec des animations fluides et réalistes.
- **Rendu HD** : qualité haute définition incluse même dans le plan gratuit.
- **Éditeur intégré** : ajustez et peaufinez vos créations directement dans l'interface.
- **Modèles pour réseaux sociaux** : templates optimisés pour TikTok, Instagram Reels et autres formats courts.
- **Styles visuels personnalisables** : appliquez différents styles artistiques à vos vidéos.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | Gratuit | 10 clips/jour, HD, filigrane |
| Explorer | 10 $/mois | Clips illimités, sans filigrane, priorité rendu |
| Pro | 25 $/mois | Tout Explorer + styles premium, durée étendue, API |

## Comparaison avec les alternatives

Face à **Kling AI**, Haiper offre une interface plus simple et un rendu plus rapide, mais Kling AI permet des vidéos jusqu'à 2 minutes contre 8 secondes pour Haiper. **Luma AI** excelle en cohérence de scène et en 3D, mais coûte beaucoup plus cher. **Runway** est plus puissant pour les professionnels du cinéma, mais nettement plus complexe et plus cher (15-95 $/mois). Haiper se positionne comme l'option la plus accessible et abordable pour du contenu court.

## Notre avis

Pour les créateurs de contenu québécois actifs sur TikTok et Instagram, Haiper est une excellente option d'entrée de gamme. Son prix abordable (25 $/mois pour le Pro) et sa rapidité de rendu en font un outil idéal pour les PME québécoises qui veulent produire du contenu vidéo social sans budget de production élevé. Le plan gratuit avec 10 clips par jour est suffisant pour tester et même pour un usage régulier modéré.
MD,
                'core_features' => 'Génération text-to-video, Animation image-to-video, Rendu HD, Éditeur intégré, Modèles pour réseaux sociaux, Styles visuels personnalisables',
                'use_cases' => 'Vidéos courtes TikTok et Instagram Reels, Prototypage marketing, Bannières animées, Contenu social PME québécoises, Support pour influenceurs',
                'pros' => 'Interface intuitive, Rendu en moins de 30 secondes, Fondé par ex-Google DeepMind, HD inclus même en gratuit, Prix abordable',
                'cons' => 'Durée max limitée à 8 secondes, Pas de voix off ni synchronisation labiale, Interface uniquement en anglais',
                'faq' => [
                    ['question' => 'Haiper est-il disponible en français ?', 'answer' => 'L\'interface est en anglais, mais les prompts en français fonctionnent bien.'],
                    ['question' => 'Peut-on utiliser Haiper pour des projets commerciaux ?', 'answer' => 'Oui, tous les plans permettent une utilisation commerciale.'],
                    ['question' => 'Comment Haiper se compare-t-il à Runway ?', 'answer' => 'Haiper est bien plus abordable (25 $/mois vs 15-95 $ pour Runway) et plus simple, idéal pour du contenu court.'],
                ],
            ],

            'Kling AI' => [
                'description' => <<<'MD'
Kling AI est un générateur vidéo propulsé par l'intelligence artificielle, développé par le géant technologique chinois Kuaishou. Capable de produire des vidéos d'une durée allant jusqu'à deux minutes avec une physique réaliste et un mode cinématique impressionnant, Kling AI attire l'attention des cinéastes, créateurs de contenu et professionnels du marketing à travers le monde.

## À propos de Kling AI

Développé par Kuaishou, l'un des plus grands groupes technologiques chinois, Kling AI utilise des modèles d'apprentissage profond avancés pour générer des vidéos à partir de texte ou d'images. Sa capacité à produire des vidéos allant jusqu'à deux minutes — bien au-delà de la plupart de ses concurrents — en fait un outil particulièrement attractif pour les professionnels. Le mode cinématique et la simulation physique réaliste placent Kling AI parmi les générateurs vidéo IA les plus ambitieux du marché.

## Fonctionnalités principales

- **Text-to-video** : générez des vidéos à partir de simples descriptions textuelles.
- **Image-to-video** : animez vos images statiques avec des mouvements réalistes.
- **Vidéos jusqu'à 2 minutes** : durée nettement supérieure à la plupart des concurrents.
- **Physique réaliste** : simulation crédible des mouvements, de la gravité et des interactions.
- **Mode cinématique** : rendu de style cinéma avec profondeur de champ et éclairage avancé.
- **Rendu haute résolution** : export en qualité HD pour un usage professionnel.
- **Interface accessible** : prise en main rapide même pour les débutants.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | Gratuit | 66 crédits/jour, résolution standard, filigrane |
| Standard | 8 $/mois | 660 crédits/mois, HD, sans filigrane |
| Pro | 28 $/mois | 3 000 crédits/mois, vidéos 2 min, mode cinématique |
| Premier | 68 $/mois | 8 000 crédits/mois, priorité rendu, toutes fonctionnalités |

## Comparaison avec les alternatives

Face à **Sora** d'OpenAI, Kling AI offre un plan gratuit généreux et des vidéos plus longues (2 min vs 1 min), mais Sora propose une meilleure compréhension sémantique. **Luma AI** excelle en génération 3D et en cohérence de scène, mais reste plus cher et limité en durée. **Runway** cible les professionnels du cinéma avec des outils plus avancés, mais à un prix nettement supérieur. Kling AI se distingue par son rapport qualité-prix et sa générosité en plan gratuit.

## Notre avis

Pour les professionnels québécois, notamment dans l'industrie des effets visuels florissante à Montréal, Kling AI représente un outil de prototypage rapide intéressant à un prix compétitif. Les étudiants en cinéma et les créateurs indépendants apprécieront le plan gratuit généreux (66 crédits/jour). Toutefois, comme l'outil est développé en Chine, les organisations soucieuses de la confidentialité des données devraient évaluer la conformité avec la Loi 25 avant un déploiement à grande échelle. L'interface en anglais ne devrait pas poser de problème, mais l'absence de support francophone reste un point à considérer.
MD,
                'core_features' => 'Text-to-video, Image-to-video, Vidéos jusqu\'à 2 minutes, Physique réaliste, Mode cinématique, Rendu haute résolution, Interface accessible',
                'use_cases' => 'Prototypage scènes cinématiques, Contenu vidéo réseaux sociaux, Vidéos promotionnelles, Storyboards animés, Présentations professionnelles, Projets étudiants',
                'pros' => 'Plan gratuit généreux (66 crédits/jour), Vidéos jusqu\'à 2 minutes, Mode cinématique, Tarification compétitive dès 8 $/mois, Interface intuitive',
                'cons' => 'Développé en Chine (préoccupations confidentialité), Interface en anglais, Résultats parfois incohérents sur scènes complexes',
                'faq' => [
                    ['question' => 'Kling AI est-il gratuit ?', 'answer' => 'Oui, le plan gratuit offre 66 crédits par jour, suffisant pour tester.'],
                    ['question' => 'Quelle durée maximale pour les vidéos ?', 'answer' => 'Jusqu\'à 2 minutes sur les plans Pro et Premier.'],
                    ['question' => 'Kling AI est-il adapté au Québec ?', 'answer' => 'Oui pour l\'usage commercial (plans payants), mais vérifiez les conditions de confidentialité avec la Loi 25.'],
                ],
            ],

            'Luma AI' => [
                'description' => <<<'MD'
Luma AI, avec son produit phare Dream Machine, est un générateur vidéo par intelligence artificielle qui s'est taillé une place de choix dans l'univers de la création visuelle assistée par IA. Conçu pour transformer du texte et des images en vidéos captivantes avec des capacités de génération 3D remarquables, Luma AI séduit particulièrement les artistes VFX et les professionnels de la visualisation.

## À propos de Luma AI

Luma AI s'est fait connaître grâce à sa technologie NeRF (Neural Radiance Fields) permettant de capturer et de recréer des scènes 3D à partir de simples photos ou vidéos. Cette compréhension spatiale unique se traduit par une cohérence de scène exceptionnelle dans ses générations vidéo. Dream Machine, son produit phare, combine cette expertise 3D avec la génération text-to-video pour offrir des résultats d'une qualité artistique remarquable, appréciés des studios VFX et des créateurs exigeants.

## Fonctionnalités principales

- **Text-to-video artistique** : générez des vidéos à partir de descriptions textuelles avec une qualité visuelle supérieure.
- **Image-to-video** : animez vos images avec des mouvements fluides et cohérents.
- **Génération 3D (NeRF)** : créez des modèles 3D à partir de photos, unique sur le marché.
- **Cohérence exceptionnelle des scènes** : les objets et personnages restent cohérents tout au long de la vidéo.
- **Qualité artistique supérieure** : rendu avec une esthétique raffinée, proche du cinéma d'animation.
- **Contrôle créatif** : paramètres avancés pour affiner le style, le mouvement et l'ambiance.
- **Compatible pipelines VFX** : intégrable dans les flux de travail professionnels existants.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | Gratuit | 30 générations/mois, résolution standard |
| Standard | 24 $/mois | 150 générations/mois, HD, priorité rendu |
| Pro | 96 $/mois | 2 000 générations/mois, toutes fonctionnalités, API |

## Comparaison avec les alternatives

Face à **Sora**, Luma AI offre une meilleure cohérence de scène et des capacités 3D uniques, mais Sora propose des vidéos plus longues (60 s). **Kling AI** est nettement plus abordable avec des vidéos jusqu'à 2 minutes, mais sans la qualité artistique de Luma AI. **Runway** partage un positionnement premium similaire, mais Luma AI se distingue par sa génération 3D et sa compréhension spatiale héritée de la technologie NeRF.

## Notre avis

Montréal étant l'une des capitales mondiales des effets visuels, Luma AI trouvera naturellement sa place dans l'écosystème créatif québécois. Les studios VFX, les artistes 3D et les professionnels de la visualisation architecturale apprécieront la qualité exceptionnelle et l'intégrabilité dans les pipelines existants. Le positionnement premium (96 $/mois pour le Pro) le réserve toutefois aux professionnels ou aux projets à budget conséquent. Le plan gratuit (30 générations/mois) permet de se faire une bonne idée du potentiel, mais planifiez soigneusement vos essais. Entreprise américaine, Luma AI offre une meilleure transparence sur la gestion des données que ses concurrents asiatiques.
MD,
                'core_features' => 'Text-to-video artistique, Image-to-video, Génération 3D (NeRF), Cohérence exceptionnelle des scènes, Qualité artistique supérieure, Contrôle créatif, Compatible pipelines VFX',
                'use_cases' => 'Prototypage VFX, Contenu vidéo artistique, Visualisation architecturale 3D, Animation de concept art, Exploration créative, Pitchs visuels',
                'pros' => 'Cohérence des scènes supérieure, Génération 3D unique, Qualité artistique remarquable, Intégrable dans pipelines VFX, Entreprise américaine (transparence données)',
                'cons' => 'Plan gratuit limité (30 gen/mois), Plan Pro cher (96 $/mois), Durée vidéo inférieure à Kling AI, Courbe d\'apprentissage',
                'faq' => [
                    ['question' => 'Quelle est la différence entre Luma AI et Dream Machine ?', 'answer' => 'Luma AI est l\'entreprise, Dream Machine est son produit phare de génération vidéo.'],
                    ['question' => 'Luma AI peut-il générer des modèles 3D ?', 'answer' => 'Oui, la génération 3D est un point fort historique, utile pour le prototypage et la prévisualisation.'],
                    ['question' => 'Le plan gratuit est-il suffisant pour évaluer l\'outil ?', 'answer' => '30 générations/mois permettent de se faire une bonne idée, mais planifiez soigneusement vos essais.'],
                ],
            ],

            'Sora' => [
                'description' => <<<'MD'
Sora est le modèle de génération vidéo par intelligence artificielle développé par OpenAI, lancé en décembre 2024. Capable de transformer de simples descriptions textuelles en vidéos réalistes d'une durée allant jusqu'à une minute, Sora représente une avancée majeure dans le domaine de l'IA générative. Que vous soyez créateur de contenu, entrepreneur québécois ou professionnel du marketing, cet outil promet de révolutionner la production de contenu vidéo.

## À propos de Sora

Développé par OpenAI, Sora repose sur un modèle de diffusion avancé capable de simuler la physique du monde réel pour générer des vidéos d'une fidélité impressionnante. Intégré directement dans l'écosystème ChatGPT, il permet aux abonnés Plus et Pro de créer des vidéos sans quitter leur interface habituelle. Sora comprend le français et peut générer des scènes complexes avec plusieurs personnages, des mouvements de caméra et des environnements détaillés.

## Fonctionnalités principales

- **Text-to-video 1080p** : générez des vidéos en haute résolution à partir de descriptions textuelles.
- **Vidéos jusqu'à 60 secondes** : durée maximale d'une minute sur le plan Pro.
- **Physique réaliste** : simulation crédible des mouvements, lumières et interactions physiques.
- **Support multi-scènes** : créez des vidéos avec plusieurs scènes enchaînées.
- **Storyboard intégré** : planifiez visuellement votre vidéo avant la génération.
- **Remix et édition** : modifiez et ajustez les vidéos générées.
- **Image-to-video** : animez des images statiques.
- **Intégration ChatGPT** : accessible directement depuis l'interface ChatGPT.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| ChatGPT Plus | 20 $ USD/mois | 720p, ~50 vidéos/mois, filigrane |
| ChatGPT Pro | 200 $ USD/mois | 1080p, ~500 vidéos/mois, sans filigrane |

*Aucun plan gratuit disponible.*

## Comparaison avec les alternatives

Face à **Runway Gen-3**, Sora offre une meilleure compréhension sémantique et des vidéos plus longues, mais Runway propose des outils d'édition plus avancés. **Kling AI** est nettement plus abordable avec un plan gratuit généreux et des vidéos jusqu'à 2 minutes, mais la qualité de rendu de Sora reste supérieure sur les scènes complexes. **Luma AI** excelle en cohérence 3D et en qualité artistique, mais ne peut pas rivaliser avec la durée et la polyvalence de Sora.

## Notre avis

Pour les entrepreneurs et créateurs québécois déjà abonnés à ChatGPT Plus, Sora représente un ajout naturel sans coût supplémentaire. Le plan Plus à 20 $ USD/mois (~28 $ CAD avec TPS/TVQ) offre un bon point d'entrée pour le prototypage et le contenu social. Le plan Pro à 200 $ USD/mois (~275 $ CAD) reste coûteux pour les PME, mais se justifie pour les agences et les créateurs prolifiques. La bonne compréhension du français est un atout, même si les prompts en anglais donnent souvent de meilleurs résultats. Sora excelle pour les publicités, le contenu B-roll et le prototypage créatif, mais ne remplace pas encore une équipe de production pour les projets haut de gamme.
MD,
                'core_features' => 'Text-to-video 1080p, Vidéos jusqu\'à 60 secondes, Physique réaliste, Support multi-scènes, Storyboard intégré, Remix et édition, Image-to-video, Intégration ChatGPT',
                'use_cases' => 'Publicités PME québécoises, Contenu réseaux sociaux, Prototypage créatif, B-roll documentaire, Contenu éducatif, Vidéos immobilières, Storyboarding animé',
                'pros' => 'Qualité visuelle impressionnante, Durée jusqu\'à 60 secondes, Intégration ChatGPT, Bonne compréhension du français, 1080p sur Pro',
                'cons' => 'Pro à 200 $ USD/mois (coûteux avec le change), Artefacts sur visages/mains, Pas d\'accès gratuit, Mouvements complexes parfois rigides',
                'faq' => [
                    ['question' => 'Sora est-il disponible au Québec ?', 'answer' => 'Oui, via ChatGPT Plus (20 $ USD/mo) ou Pro (200 $ USD/mo). Comptez le taux de change en CAD.'],
                    ['question' => 'Différence entre Sora sur Plus et Pro ?', 'answer' => 'Plus: 720p, ~50 vidéos/mois, filigrane. Pro: 1080p, ~500 vidéos/mois, sans filigrane.'],
                    ['question' => 'Sora peut-il remplacer une équipe de production ?', 'answer' => 'Pas entièrement. Excellent pour prototypage et contenu court, mais une équipe pro reste nécessaire pour les productions haut de gamme.'],
                ],
            ],

            'Synthesia' => [
                'description' => <<<'MD'
Synthesia se positionne comme le leader mondial des vidéos générées par intelligence artificielle avec des avatars ultra-réalistes, conçus pour transformer la manière dont les entreprises communiquent, forment et engagent leurs publics. Fondée en 2017 par des chercheurs de l'Université de Cambridge, Synthesia prend en charge plus de 140 langues, ce qui en fait un outil idéal pour les organisations multinationales — y compris celles œuvrant au Québec.

## À propos de Synthesia

Synthesia propose plus de 200 avatars réalistes capables de s'exprimer dans 140+ langues avec une synchronisation labiale précise. La plateforme se distingue par ses fonctionnalités orientées entreprise, notamment l'export SCORM pour les systèmes de gestion de l'apprentissage (LMS), la création d'avatars personnalisés et un éditeur visuel intuitif. Synthesia est particulièrement prisée dans les secteurs de la formation, des ressources humaines et de la communication d'entreprise.

## Fonctionnalités principales

- **Génération vidéo IA** : transformez du texte en vidéo professionnelle avec un avatar parlant en quelques minutes.
- **200+ avatars réalistes** : large choix de présentateurs virtuels diversifiés en genre, ethnie et style.
- **140+ langues incluant français canadien** : support multilingue complet avec voix adaptées.
- **Avatar personnalisé** : créez un avatar à votre image pour une communication de marque cohérente.
- **Export SCORM pour LMS** : intégration directe avec Moodle, Brightspace et autres plateformes d'apprentissage.
- **Éditeur visuel** : ajoutez des diapositives, images, textes et éléments graphiques sans logiciel externe.
- **Sous-titres automatiques** : génération et intégration de sous-titres en plusieurs langues.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Starter | 22 €/mois | 10 minutes/mois, avatars standards, export MP4 |
| Creator | 67 €/mois | 30 minutes/mois, avatar personnalisé, SCORM, collaboration |
| Enterprise | Sur devis | Minutes illimitées, avatars sur mesure, SSO, SLA dédié |

*Aucune version gratuite disponible.*

## Comparaison avec les alternatives

Face à **D-ID**, Synthesia offre une bibliothèque d'avatars plus vaste et l'export SCORM, mais D-ID est plus abordable. **HeyGen** propose des fonctionnalités similaires à un prix compétitif, mais Synthesia reste supérieur en qualité d'avatar et en intégrations LMS. **Colossyan** cible également la formation avec des scénarios prédéfinis, mais Synthesia domine par la diversité de ses avatars et son écosystème multilingue. **Elai** est une alternative budget, mais n'atteint pas le réalisme de Synthesia.

## Notre avis

Pour les CEGEPs, universités et entreprises québécoises qui investissent dans la formation en ligne, Synthesia est le choix premium. La compatibilité SCORM avec Moodle et Brightspace — les deux LMS les plus utilisés dans le réseau collégial québécois — est un avantage déterminant. Le support du français québécois avec des voix adaptées permet de créer du contenu bilingue professionnel. Le prix peut sembler élevé pour les petits budgets, mais le gain de temps par rapport à la production vidéo traditionnelle justifie l'investissement. Synthesia respecte le GDPR et le CCPA, ce qui facilite la conformité avec les exigences de la Loi 25.
MD,
                'core_features' => 'Génération vidéo IA, 200+ avatars réalistes, 140+ langues incluant français canadien, Avatar personnalisé, Export SCORM pour LMS, Éditeur visuel, Sous-titres automatiques',
                'use_cases' => 'Formation entreprise, Communications RH, Contenus pédagogiques, Vidéos marketing multilingues, Messages internes, Démonstrations produits',
                'pros' => 'Avatars les plus réalistes du marché, Support français québécois, Compatibilité SCORM, Interface intuitive, Respect de la vie privée (GDPR/CCPA)',
                'cons' => 'Prix élevé pour petits budgets, Minutes non reportables, Délai création avatar personnalisé, Pas de version gratuite',
                'faq' => [
                    ['question' => 'Synthesia supporte-t-il le français québécois ?', 'answer' => 'Oui, avec des voix adaptées au Québec, suffisantes pour la formation et les communications.'],
                    ['question' => 'Peut-on utiliser Synthesia dans un CEGEP ?', 'answer' => 'Oui, grâce à l\'export SCORM compatible Moodle et Brightspace.'],
                    ['question' => 'Synthesia vs HeyGen pour une PME montréalaise ?', 'answer' => 'Synthesia offre une meilleure qualité d\'avatar et l\'intégration SCORM. C\'est le choix premium pour un ton professionnel bilingue.'],
                ],
            ],
        ];
    }
}
