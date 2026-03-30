<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class EnrichResearchToolsSeeder extends Seeder
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
            'Consensus' => [
                'description' => <<<'MD'
Dans le monde de la recherche académique, trouver rapidement des réponses fiables parmi des millions d'articles scientifiques représente un défi de taille. Consensus se positionne comme un moteur de recherche propulsé par l'intelligence artificielle qui révolutionne la façon dont on interroge la littérature scientifique.

## À propos de Consensus

Fondé en 2021, Consensus analyse plus de 200 millions d'articles scientifiques grâce au traitement du langage naturel (NLP). Sa fonctionnalité phare, le Consensus Meter, indique le pourcentage d'accord entre les études sur une question donnée, offrant ainsi une vue d'ensemble instantanée du consensus scientifique. Plutôt que de simplement lister des liens, la plateforme lit et synthétise le contenu des articles pour fournir des réponses directes et sourcées.

## Fonctionnalités principales

- **Consensus Meter** : visualisation du degré d'accord scientifique sur une question, exprimé en pourcentage.
- **Résumés IA avec citations** : chaque réponse est accompagnée de références directes aux articles sources.
- **Recherche en langage naturel** : posez vos questions comme à un collègue, sans mots-clés complexes.
- **Filtres par type d'étude** : affinez les résultats par essais contrôlés, méta-analyses, revues systématiques, etc.
- **Copilot IA conversationnel** : assistant premium pour approfondir une question ou explorer des sous-thèmes.
- **Listes de lecture** : sauvegardez et organisez les articles pertinents pour vos projets de recherche.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | 0 $ | 20 recherches/mois, résumés de base |
| Premium | 8,99 $/mois | Recherches illimitées, Copilot IA, filtres avancés |
| Team | Sur devis | Fonctionnalités collaboratives, administration centralisée |

## Comparaison avec les alternatives

Face à **Elicit**, qui excelle dans l'extraction de données structurées et les tableaux comparatifs, Consensus se distingue par son Consensus Meter unique qui donne une vue d'ensemble rapide. **Semantic Scholar** offre un excellent moteur de découverte gratuit avec son graphe de citations, mais ne synthétise pas les résultats. **Google Scholar** reste l'outil traditionnel le plus complet en termes de couverture, mais retourne des liens sans analyse du contenu.

## Notre avis

Du point de vue québécois, Consensus est un outil remarquablement abordable pour les étudiants aux cycles supérieurs et les chercheurs. Le plan Premium à 8,99 $/mois le rend accessible même avec un budget étudiant limité. Sa force se situe particulièrement dans les domaines biomédicaux et des sciences sociales, où la base d'articles est la plus riche. Seul bémol : l'interface est uniquement en anglais, ce qui peut représenter un frein pour certains utilisateurs francophones. Malgré cela, la qualité des synthèses et la fiabilité du Consensus Meter en font un investissement judicieux pour quiconque travaille régulièrement avec la littérature scientifique.
MD,
                'core_features' => 'Consensus Meter pour le degré d\'accord scientifique, Résumés IA avec citations, Recherche en langage naturel sur 200M+ articles, Filtres par type d\'étude, Copilot IA conversationnel, Listes de lecture',
                'use_cases' => 'Revue de littérature rapide, Vérification de faits scientifiques, Travaux universitaires cycles supérieurs, Exploration état des connaissances, Demandes de subvention',
                'pros' => 'Consensus Meter unique, Interface langage naturel, Prix abordable 8,99 $/mois, Résumés avec citations, Base 200M+ articles',
                'cons' => '20 recherches/mois en gratuit, Couverture inégale selon disciplines, Interface en anglais uniquement',
                'faq' => [
                    ['question' => 'Consensus est-il fiable pour la recherche académique?', 'answer' => 'Oui, il s\'appuie sur des articles revus par les pairs. Vérifiez toujours les sources originales.'],
                    ['question' => 'Différence avec Google Scholar?', 'answer' => 'Google Scholar retourne des liens. Consensus analyse le contenu et indique le consensus scientifique.'],
                    ['question' => 'Le plan gratuit suffit-il pour un étudiant?', 'answer' => '20 recherches/mois peut être insuffisant en rédaction intensive. Le Premium à 8,99 $/mois est recommandé.'],
                ],
            ],

            'Elicit' => [
                'description' => <<<'MD'
La recherche académique demande énormément de temps pour passer au crible des centaines d'articles. Elicit se présente comme un assistant de recherche IA qui automatise les tâches les plus chronophages : découverte d'articles, extraction de données et création de tableaux comparatifs.

## À propos de Elicit

Développé par Ought, un organisme sans but lucratif basé à San Francisco, Elicit donne accès à plus de 125 millions d'articles scientifiques via Semantic Scholar. Ce qui le distingue fondamentalement des autres outils, c'est sa capacité à extraire des données structurées directement depuis les articles et à les organiser dans des tableaux comparatifs personnalisables. Plutôt que de simplement trouver des articles, Elicit les analyse en profondeur pour en tirer l'information pertinente.

## Fonctionnalités principales

- **Extraction de données structurées** : l'IA identifie et extrait automatiquement les données clés des articles (taille d'échantillon, méthodologie, résultats principaux).
- **Tableaux comparatifs IA** : visualisez côte à côte les résultats de plusieurs études avec des colonnes personnalisables.
- **Résumés contextualisés** : synthèses adaptées à votre question de recherche, pas des résumés génériques.
- **Recherche conceptuelle** : trouvez des articles pertinents même sans utiliser les mots-clés exacts, grâce à la compréhension sémantique.
- **Colonnes personnalisables** : définissez exactement quelles informations extraire de chaque article.
- **Flux de travail automatisés** : enchaînez plusieurs étapes d'analyse sans intervention manuelle.
- **Export CSV/BibTeX** : exportez vos tableaux et bibliographies dans les formats standards.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | 0 $ | 5000 crédits non renouvelables à l'inscription |
| Plus | 12 $/mois | Crédits renouvelables mensuellement, toutes fonctionnalités |
| Team | Sur devis | Collaboration, administration, support prioritaire |

## Comparaison avec les alternatives

**Consensus** offre une vue d'ensemble rapide du consensus scientifique grâce à son Meter, mais ne permet pas l'extraction granulaire de données. **Semantic Scholar** est entièrement gratuit et excellent pour la découverte, mais ne propose pas de tableaux comparatifs. **Scite** se spécialise dans l'analyse des citations (supportives vs contradictoires), un angle complémentaire à Elicit.

## Notre avis

Du point de vue québécois, Elicit est un outil révolutionnaire pour quiconque réalise des revues systématiques ou des méta-analyses. Les 5000 crédits gratuits permettent de bien tester la plateforme avant de s'engager. Le système de crédits peut sembler complexe au début, mais il reflète la puissance des opérations effectuées. Un conseil : validez toujours les données extraites automatiquement, car l'IA peut parfois mal interpréter des tableaux ou des résultats nuancés. Pour les étudiants en maîtrise et doctorat, Elicit peut littéralement réduire de moitié le temps consacré à la revue de littérature. L'interface est en anglais uniquement.
MD,
                'core_features' => 'Extraction de données structurées, Tableaux comparatifs IA, Résumés contextualisés, Recherche conceptuelle sur 125M+ articles, Colonnes personnalisables, Flux de travail automatisés, Export CSV/BibTeX',
                'use_cases' => 'Revues systématiques, Méta-analyses, Mémoires et thèses, Exploration nouveaux domaines, Bibliographies annotées',
                'pros' => 'Extraction données révolutionnaire, Colonnes personnalisables, Recherche conceptuelle, 5000 crédits gratuits, Interface pensée pour l\'académique',
                'cons' => 'Système de crédits complexe, Crédits gratuits non renouvelables, Extraction parfois imprécise, Interface en anglais',
                'faq' => [
                    ['question' => 'Comment fonctionne le système de crédits?', 'answer' => 'Chaque action consomme des crédits selon sa complexité. 5000 gratuits à l\'inscription, Plus à 12 $/mois pour du renouvelable.'],
                    ['question' => 'Elicit peut-il remplacer une revue manuelle?', 'answer' => 'Il accélère énormément mais une validation humaine reste nécessaire.'],
                    ['question' => 'Différence avec Consensus?', 'answer' => 'Consensus donne le consensus global. Elicit excelle en extraction granulaire et tableaux comparatifs.'],
                ],
            ],

            'Humata' => [
                'description' => <<<'MD'
Analyser un contrat de 200 pages, décortiquer un article scientifique ou extraire les points clés d'un rapport financier gruge un temps considérable. Humata propose une IA capable de lire vos PDF et de répondre à vos questions avec des citations précises tirées du document.

## À propos de Humata

Fondé en 2023 à San Francisco, Humata est souvent décrit comme un « ChatGPT pour vos fichiers ». La plateforme utilise la technologie RAG (Retrieval-Augmented Generation) pour analyser les documents que vous téléversez et répondre à vos questions en s'appuyant strictement sur le contenu de ces fichiers. Contrairement aux chatbots généralistes, Humata ne fabule pas : chaque réponse est accompagnée d'une citation avec le numéro de page source.

## Fonctionnalités principales

- **Questions-réponses PDF avec citations** : posez des questions sur vos documents et obtenez des réponses avec numéros de pages précis.
- **Résumés automatiques** : générez des synthèses de documents longs en quelques secondes.
- **Comparaison multi-documents** : analysez et comparez plusieurs PDF simultanément.
- **Rédaction assistée** : utilisez vos documents comme base pour générer de nouveaux contenus.
- **Espaces collaboratifs** : partagez des documents et des analyses avec votre équipe.
- **Interface conversationnelle** : dialoguez naturellement avec vos documents comme dans une conversation.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | 0 $ | 60 pages maximum |
| Student | 1,99 $/mois | Accès étendu, idéal pour les étudiants |
| Expert | 9,99 $/mois | Documents volumineux, fonctionnalités avancées |
| Team | 49 $/mois | Espaces collaboratifs, administration équipe |

## Comparaison avec les alternatives

**ChatPDF** offre une expérience gratuite plus simple mais avec moins de profondeur d'analyse. **Adobe Acrobat AI** s'intègre nativement dans l'écosystème Adobe, idéal pour ceux qui y sont déjà. **Scholarcy** se spécialise dans les fiches de lecture académiques sous forme de flashcards. Humata se distingue par sa polyvalence (juridique, académique, financier) et ses citations précises avec numéros de pages.

## Notre avis

Du point de vue québécois, Humata est une aubaine pour les étudiants grâce à son plan Student à 1,99 $/mois — probablement le meilleur rapport qualité-prix du marché pour un outil d'analyse de documents IA. La plateforme fonctionne bien en français, ce qui est un avantage notable. Elle est particulièrement utile pour les étudiants en droit, en sciences de la santé ou en administration qui doivent régulièrement analyser des documents volumineux. Un conseil : vérifiez toujours les passages nuancés ou les interprétations complexes, car l'IA peut simplifier des arguments subtils. La qualité de l'analyse dépend aussi de la qualité de l'OCR du PDF source.
MD,
                'core_features' => 'Questions-réponses PDF avec citations et pages, Résumés automatiques, Comparaison multi-documents, Rédaction assistée, Espaces collaboratifs, Interface conversationnelle',
                'use_cases' => 'Analyse articles scientifiques, Extraction infos contrats juridiques, Synthèse rapports financiers, Préparation examens, Comparaison versions documents',
                'pros' => 'Plan Student à 1,99 $/mois, Citations avec numéros de pages, Polyvalent tout type PDF, Comparaison multi-docs, Interface simple',
                'cons' => 'Plan gratuit limité 60 pages, Ne cherche pas dans des bases externes, Qualité dépend de l\'OCR du PDF',
                'faq' => [
                    ['question' => 'Humata analyse-t-il des documents en français?', 'answer' => 'Oui, avec une bonne qualité de réponse pour le français.'],
                    ['question' => 'Mes documents sont-ils sécurisés?', 'answer' => 'Chiffrés et stockés de manière sécurisée. Vérifiez les conditions pour du contenu confidentiel.'],
                    ['question' => 'Différence avec ChatGPT pour analyser des PDF?', 'answer' => 'Humata offre des citations avec pages précises, la comparaison multi-docs et des espaces collaboratifs.'],
                ],
            ],

            'Semantic Scholar' => [
                'description' => <<<'MD'
Dans l'écosystème des outils de recherche académique, Semantic Scholar est un acteur incontournable qui sert de fondation à plusieurs autres plateformes d'IA. Développé par l'Allen Institute for AI, ce moteur de recherche académique gratuit utilise l'intelligence artificielle pour naviguer dans plus de 200 millions d'articles scientifiques.

## À propos de Semantic Scholar

Lancé en 2015 par l'Allen Institute for AI (fondé par Paul Allen, cofondateur de Microsoft), Semantic Scholar est un moteur de recherche académique 100 % gratuit qui utilise la compréhension sémantique pour aller au-delà de la simple correspondance de mots-clés. Avec plus de 200 millions d'articles indexés, il constitue l'une des plus grandes bases de données scientifiques au monde et sert de fondation à plusieurs autres outils comme Elicit. Sa mission d'intérêt public garantit un accès libre et sans restriction à la connaissance scientifique.

## Fonctionnalités principales

- **Graphe de citations interactif** : visualisez les relations entre les articles et identifiez les citations les plus influentes (highly influential citations).
- **TLDR résumés IA** : résumés ultra-courts générés par IA pour saisir l'essentiel d'un article en une phrase.
- **Recommandations personnalisées** : suggestions d'articles basées sur votre historique de lecture et vos intérêts.
- **Alertes de recherche** : recevez des notifications quand de nouveaux articles correspondent à vos sujets.
- **API ouverte** : accédez programmatiquement aux 200M+ articles et métadonnées pour vos propres analyses.
- **Pages d'auteurs enrichies** : profils avec métriques d'impact, publications et collaborations.
- **Research Feeds** : flux personnalisés de nouvelles publications dans vos domaines d'intérêt.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Gratuit | 0 $ | 100 % gratuit, toutes les fonctionnalités, aucune restriction |

## Comparaison avec les alternatives

**Google Scholar** couvre un spectre plus large de documents (livres, brevets, thèses) mais n'offre ni résumés IA, ni graphe de citations interactif, ni recommandations. **PubMed** est le spécialiste incontesté du domaine biomédical mais se limite à cette discipline. **Consensus** ajoute une couche d'analyse du consensus scientifique par-dessus les articles, mais c'est un outil payant. Semantic Scholar se distingue par sa gratuité totale combinée à des fonctionnalités IA avancées.

## Notre avis

Du point de vue québécois, Semantic Scholar est tout simplement le meilleur moteur de recherche académique gratuit disponible. Les TLDR sont un véritable changement de jeu pour le triage rapide d'articles lors d'une revue de littérature. Le graphe de citations interactif est le plus complet du marché et permet d'identifier rapidement les articles fondateurs d'un domaine. L'API ouverte est une mine d'or pour les bibliométriciens et les développeurs d'outils de recherche. La couverture en français reste toutefois limitée, et les profils d'auteurs peuvent parfois être incomplets ou fragmentés. Malgré ces limites, sa mission d'intérêt public et son accès totalement libre en font un outil indispensable pour tout chercheur.
MD,
                'core_features' => 'Graphe de citations interactif, TLDR résumés IA, Recommandations personnalisées, Alertes de recherche, API ouverte, Pages d\'auteurs enrichies, Research Feeds, 200M+ articles',
                'use_cases' => 'Découverte articles scientifiques, Analyse bibliométrique, Veille scientifique, Identification articles influents, Recherche collaborateurs, Construction d\'outils via API',
                'pros' => '100 % gratuit sans limitation, TLDR exceptionnellement utiles, Meilleur graphe de citations, API ouverte, Recommandations personnalisées, 200M+ articles, Mission d\'intérêt public',
                'cons' => 'Pas de synthèses approfondies, Interface moins moderne, Couverture limitée en français, Pas de consensus scientifique, Profils auteurs parfois incomplets',
                'faq' => [
                    ['question' => 'Semantic Scholar est-il vraiment gratuit?', 'answer' => 'Oui, 100 % gratuit sans restriction. Financé par l\'Allen Institute for AI.'],
                    ['question' => 'Différence avec Google Scholar?', 'answer' => 'Semantic Scholar offre TLDR IA, graphe de citations interactif et recommandations. Google Scholar couvre plus de types de documents.'],
                    ['question' => 'Peut-on utiliser l\'API pour la recherche?', 'answer' => 'Oui, API ouverte et gratuite avec accès aux 200M+ articles et métadonnées.'],
                ],
            ],
        ];
    }
}
