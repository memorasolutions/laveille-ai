<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class EnrichEducationToolsSeeder extends Seeder
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
            'Duolingo Max' => [
                'description' => <<<'MD'
Apprendre une nouvelle langue n'a jamais été aussi accessible grâce à l'intelligence artificielle. Duolingo Max repousse les limites de l'apprentissage linguistique en intégrant GPT-4 directement dans l'application la plus populaire au monde pour l'apprentissage des langues.

## À propos de Duolingo Max

Fondée en 2011, Duolingo est devenue l'application d'apprentissage des langues la plus téléchargée au monde avec plus de 500 millions d'utilisateurs. En 2023, l'entreprise a lancé Duolingo Max, une version premium intégrant GPT-4 d'OpenAI pour offrir deux fonctionnalités révolutionnaires : Roleplay, qui permet des conversations immersives avec des personnages IA dans des scénarios réalistes, et Explain My Answer, qui fournit des explications personnalisées pour chaque erreur commise. Duolingo Max couvre plus de 40 langues et s'appuie sur un système d'exercices adaptatifs qui s'ajuste au niveau et au rythme de chaque apprenant.

## Fonctionnalités principales

- **Roleplay conversationnel IA** : engagez des conversations réalistes avec des personnages propulsés par GPT-4 dans des mises en situation du quotidien.
- **Explain My Answer personnalisé** : recevez des explications détaillées et adaptées à votre niveau pour chaque erreur, plutôt qu'une simple correction.
- **Exercices adaptatifs** : le système ajuste la difficulté en fonction de vos performances pour maximiser l'apprentissage.
- **Histoires interactives** : pratiquez la compréhension écrite et orale à travers des récits engageants avec des choix narratifs.
- **Ludification et streaks** : système de points, classements et séries consécutives pour maintenir la motivation quotidienne.
- **Mode hors ligne** : téléchargez vos leçons pour apprendre sans connexion internet.
- **40+ langues** : du français à l'espagnol en passant par le japonais, le coréen et le gallois.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Gratuit | 0 $ | Exercices de base, publicités, vies limitées |
| Super | 7 $/mois | Sans publicités, vies illimitées, mode hors ligne |
| Max | 14 $/mois | Tout Super + Roleplay IA + Explain My Answer (GPT-4) |

## Comparaison avec les alternatives

Face à **Babbel**, qui propose un apprentissage plus structuré et orienté grammaire, Duolingo Max mise sur l'immersion conversationnelle grâce à l'IA. **Rosetta Stone** privilégie l'immersion totale sans traduction, une méthode efficace mais moins ludique. **Busuu** se distingue par sa communauté de correction entre pairs natifs. Duolingo Max combine le meilleur des deux mondes : la ludification qui maintient l'engagement et l'IA conversationnelle qui simule une immersion réelle.

## Notre avis

Du point de vue québécois, Duolingo Max est un outil particulièrement pertinent pour les étudiants au cégep souhaitant perfectionner leur anglais ou apprendre une troisième langue. La fonctionnalité Roleplay offre une pratique conversationnelle quotidienne accessible à 14 $/mois — une fraction du coût d'un tuteur privé. En revanche, l'application atteint ses limites pour les apprenants de niveau avancé qui ont besoin de nuances culturelles et linguistiques plus poussées. Duolingo Max excelle comme complément à un cours structuré, mais ne saurait le remplacer entièrement.
MD,
                'core_features' => 'Conversations roleplay IA (GPT-4), Explications personnalisées des erreurs, Exercices adaptatifs, Histoires interactives, Ludification et streaks, Mode hors ligne, 40+ langues',
                'use_cases' => 'Apprentissage d\'une langue pour voyager, Perfectionnement anglais/français étudiants québécois, Pratique conversationnelle quotidienne, Complément cours de langue cégep, Maintien acquis linguistiques',
                'pros' => 'GPT-4 pour conversations naturelles, Explications personnalisées, Ludification motivante, Prix abordable, Version gratuite généreuse, Interface intuitive',
                'cons' => 'Fonctionnalités Max limitées à quelques langues, Moins efficace niveaux avancés, Ludification parfois quantité sur qualité',
                'faq' => [
                    ['question' => 'Duolingo Max est-il disponible en français pour les Québécois?', 'answer' => 'Oui, cours depuis le français vers plusieurs langues. Vérifiez si votre combinaison supporte les fonctionnalités Max.'],
                    ['question' => 'Différence entre Super et Max?', 'answer' => 'Super: sans pub, vies illimitées, hors ligne (7$/mo). Max: tout Super + Roleplay IA et Explain My Answer (14$/mo).'],
                    ['question' => 'Peut-il remplacer un cours au cégep?', 'answer' => 'Non, c\'est un excellent complément mais pas un remplacement pour un programme structuré.'],
                ],
            ],

            'Khanmigo' => [
                'description' => <<<'MD'
L'intelligence artificielle transforme l'éducation, et Khanmigo se positionne comme l'un des outils les plus prometteurs. Développé par Khan Academy avec OpenAI, Khanmigo est un tuteur IA propulsé par GPT-4 qui guide les étudiants sans donner les réponses, utilisant l'approche socratique.

## À propos de Khanmigo

Khan Academy, fondée en 2008 par Sal Khan comme organisme à but non lucratif, est devenue l'une des plus grandes plateformes éducatives gratuites au monde. En 2023, Khan Academy a lancé Khanmigo en partenariat avec OpenAI, un tuteur IA qui se distingue fondamentalement des autres outils par son approche pédagogique : plutôt que de donner les réponses, il pose des questions pour guider l'étudiant vers la compréhension. Khanmigo couvre les mathématiques, les sciences, la rédaction et l'histoire, et intègre même des simulations de conversations avec des personnages historiques pour rendre l'apprentissage vivant.

## Fonctionnalités principales

- **Tutorat IA personnalisé** : propulsé par GPT-4, Khanmigo s'adapte au niveau de chaque étudiant et offre un accompagnement individualisé.
- **Approche socratique** : au lieu de fournir les réponses, l'IA pose des questions progressives pour développer la pensée critique.
- **Aide en math et sciences** : résolution guidée de problèmes en algèbre, calcul, physique, chimie et biologie.
- **Planification de cours pour enseignants** : outils dédiés pour créer des plans de leçon, des exercices et des évaluations.
- **Simulations de personnages historiques** : dialoguez avec des figures comme Marie Curie ou Martin Luther King Jr. pour explorer l'histoire de façon immersive.
- **Aide à la rédaction** : accompagnement dans la structuration et l'amélioration de textes, sans écrire à la place de l'étudiant.
- **Suivi des progrès** : tableaux de bord pour les enseignants et les parents permettant de suivre l'évolution des apprentissages.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Districts partenaires | Gratuit | Accès complet pour les districts scolaires partenaires |
| Individuel | 9 $/mois ou 44 $/an | Accès étudiant complet à toutes les fonctionnalités |
| Enseignants | Gratuit | Outils de planification et de suivi avec un compte enseignant |
| Districts | Sur devis | Déploiement personnalisé à l'échelle d'un district scolaire |

## Comparaison avec les alternatives

**Photomath** donne directement les réponses avec des étapes, ce qui peut encourager la dépendance plutôt que la compréhension. **Wolfram Alpha** excelle dans le calcul symbolique avancé, mais n'offre pas d'accompagnement pédagogique. **Chegg** fournit des réponses toutes faites et soulève des préoccupations d'intégrité académique. Khanmigo se démarque comme l'outil le plus éthique du marché éducatif : il respecte le processus d'apprentissage en guidant plutôt qu'en remplaçant l'effort intellectuel de l'étudiant.

## Notre avis

Du point de vue québécois, Khanmigo est probablement l'outil IA éducatif le plus éthique disponible. Pour les étudiants au cégep en STEM, c'est un tuteur disponible 24/7 à 9 $/mois — comparé aux 40 à 60 $ de l'heure pour un tuteur privé. L'interface est principalement optimisée en anglais, mais les mathématiques sont un langage universel, et GPT-4 peut interagir en français. Les enseignants québécois peuvent créer un compte gratuit pour explorer les outils de planification. C'est un investissement remarquable pour l'accessibilité éducative.
MD,
                'core_features' => 'Tutorat IA personnalisé (GPT-4), Approche socratique, Aide math/sciences, Planification cours pour enseignants, Simulations personnages historiques, Aide à la rédaction, Suivi des progrès',
                'use_cases' => 'Soutien devoirs math/sciences, Préparation examens calcul, Planification cours enseignants QC, Développement pensée critique, Tutorat accessible étudiants budget limité',
                'pros' => 'Approche socratique éthique, Respect intégrité académique, Gratuit pour districts partenaires, Outils enseignants, Prix abordable 9$/mois, Contenu Khan Academy',
                'cons' => 'Optimisé principalement en anglais, Limité hors math/sciences, Peut frustrer ceux cherchant réponses rapides',
                'faq' => [
                    ['question' => 'Khanmigo est-il disponible en français?', 'answer' => 'Il peut interagir en français via GPT-4, mais l\'expérience optimale est en anglais. Bon pour les maths où le langage est universel.'],
                    ['question' => 'Donne-t-il les réponses comme Chegg?', 'answer' => 'Non, c\'est sa force. Il guide par questions socratiques, respectant l\'intégrité académique.'],
                    ['question' => 'Les enseignants QC peuvent-ils l\'utiliser?', 'answer' => 'Oui, compte enseignant gratuit avec outils de planification. Contacter Khan Academy pour déploiement dans les CSS.'],
                ],
            ],

            'Photomath' => [
                'description' => <<<'MD'
Résoudre un problème de mathématiques en le photographiant semblait relever de la science-fiction. Photomath rend cela possible pour des millions d'étudiants. Cette application scanne, analyse et résout des problèmes mathématiques avec des solutions étape par étape.

## À propos de Photomath

Fondée en 2014 en Croatie, Photomath est rapidement devenue l'une des applications éducatives les plus téléchargées au monde avec plus de 300 millions de téléchargements. En 2022, Google a acquis l'entreprise, renforçant ses capacités en reconnaissance optique de caractères (OCR) et en intelligence artificielle. L'application utilise la caméra du téléphone pour scanner des problèmes mathématiques — manuscrits ou imprimés — et fournit instantanément des solutions détaillées avec plusieurs méthodes de résolution. Elle couvre un spectre allant de l'arithmétique de base au calcul intégral, en passant par l'algèbre, la trigonométrie et les statistiques.

## Fonctionnalités principales

- **Scan caméra** : photographiez un problème manuscrit ou imprimé pour une reconnaissance instantanée.
- **Solutions étape par étape** : chaque problème est décomposé en étapes claires et progressives.
- **Méthodes de résolution multiples** : découvrez différentes approches pour résoudre un même problème.
- **Graphiques interactifs** : visualisez les fonctions et équations sous forme graphique.
- **Tuteur IA** : posez des questions de suivi pour approfondir votre compréhension.
- **Calculatrice scientifique** : outil intégré pour les calculs rapides.
- **Couverture étendue** : de l'arithmétique au calcul intégral, en passant par l'algèbre et la trigonométrie.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Gratuit | 0 $ | Scan et réponses, étapes de base |
| Plus mensuel | 9,99 $/mois | Explications détaillées, méthodes multiples, tuteur IA, graphiques avancés |
| Plus annuel | 69,99 $/an | Tout Plus à prix réduit (5,83 $/mois) |

## Comparaison avec les alternatives

**Mathway** offre des fonctionnalités similaires avec un meilleur support pour les mathématiques très avancées (équations différentielles, algèbre abstraite). **Symbolab** excelle dans le calcul symbolique et les transformations algébriques détaillées. **Microsoft Math Solver** est entièrement gratuit et propose des solutions étape par étape, mais avec moins de profondeur dans les explications. Photomath se démarque par la précision de son scan caméra (manuscrit inclus), son interface particulièrement intuitive et la clarté de ses explications visuelles.

## Notre avis

Du point de vue québécois, Photomath est un outil incontournable pour les étudiants du secondaire et du cégep. Pour les cours de calcul différentiel et intégral au cégep, l'application décompose les problèmes complexes en étapes compréhensibles. Cependant, il est crucial de l'utiliser comme outil d'apprentissage et non comme raccourci : vérifier ses devoirs après les avoir faits, comprendre les étapes plutôt que les copier. Les parents québécois y trouveront également un allié pour aider leurs enfants. Vérifiez toujours les politiques d'intégrité académique de votre établissement avant de l'utiliser en contexte d'évaluation.
MD,
                'core_features' => 'Scan caméra problèmes math, Solutions étape par étape, Méthodes de résolution multiples, Graphiques interactifs, Tuteur IA, Calculatrice scientifique, Arithmétique au calcul intégral',
                'use_cases' => 'Vérification devoirs math secondaire/cégep, Compréhension étapes de résolution, Révision examens, Aide parents pour devoirs, Apprentissage autonome, Calcul différentiel/intégral cégep',
                'pros' => 'Scan rapide et précis, Explications étape par étape, Interface intuitive, Version gratuite généreuse, Couverture étendue, Disponible en français, Méthodes alternatives',
                'cons' => 'Peut encourager dépendance/tricherie, Version gratuite limite explications détaillées, Ne couvre pas toujours les maths très avancées',
                'faq' => [
                    ['question' => 'Photomath est-il de la tricherie au cégep?', 'answer' => 'Dépend de l\'usage. Pour comprendre les étapes = acceptable. Pendant un examen = tricherie. Vérifiez les règles de votre établissement.'],
                    ['question' => 'Résout-il le calcul intégral du cégep?', 'answer' => 'Oui, dérivées, intégrales, limites et séries. Avec Plus, explications détaillées.'],
                    ['question' => 'Différence gratuit vs Plus?', 'answer' => 'Gratuit: réponse + étapes de base. Plus (9.99$/mo): explications approfondies, méthodes multiples, tuteur IA, graphiques avancés.'],
                ],
            ],
        ];
    }
}
