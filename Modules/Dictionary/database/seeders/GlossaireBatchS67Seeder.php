<?php

declare(strict_types=1);

namespace Modules\Dictionary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

class GlossaireBatchS67Seeder extends Seeder
{
    public function run(): void
    {
        $categoryId = Category::query()
            ->whereJsonContains('slug->fr', 'concepts-fondamentaux')
            ->value('id');

        $terms = [
            [
                'name' => 'Déployeur',
                'slug' => 'deployeur',
                'type' => 'ai_term',
                'difficulty' => 'beginner',
                'icon' => '🔌',
                'sort_order' => 200,
                'definition' => "En PME, le déployeur est l'employé (souvent polyvalent) qui installe, sécurise et maintient les outils d'IA générative dans les processus quotidiens.",
                'analogy' => "C'est le \"plombier de l'IA\" : il branche l'intelligence artificielle là où elle doit couler.",
                'example' => "Un déployeur à Laval configure un copilote IA pour automatiser les réponses aux soumissions d'appels d'offres.",
                'did_you_know' => "42% des PME québécoises externalisent ce rôle en 2026 (Statistique Canada).",
                'acronym_full' => null,
            ],
            [
                'name' => 'GenAI Divide',
                'slug' => 'genai-divide',
                'type' => 'explainer',
                'difficulty' => 'intermediate',
                'icon' => '⚖️',
                'sort_order' => 201,
                'definition' => "Écart croissant entre les entreprises adoptant l'IA générative pour automatiser, innover et gagner en productivité, et celles qui en sont exclues par manque de ressources, de compétences ou par freins réglementaires.",
                'analogy' => "C'est la nouvelle fracture numérique, mais alimentée par des algorithmes.",
                'example' => "Une PME manufacturière québécoise intègre un copilote IA pour la gestion de la chaîne d'approvisionnement, réduisant ses coûts de 18%.",
                'did_you_know' => "Selon MIT Sloan (2025), 37% des PME utilisent déjà des outils d'IA générative, contre 72% des grandes entreprises.",
                'acronym_full' => null,
            ],
            [
                'name' => 'Gartner',
                'slug' => 'gartner',
                'type' => 'ai_term',
                'difficulty' => 'beginner',
                'icon' => '🧭',
                'sort_order' => 202,
                'definition' => "Pour une PME québécoise, Gartner est une source stratégique pour prioriser ses investissements en IA : ses rapports aident à éviter les bulles technologiques et à cibler des solutions stables, comme l'automatisation pilotée par l'IA ou la gouvernance des données.",
                'analogy' => "Gartner est le GPS des décideurs technos qui veulent éviter les embouteillages d'innovation.",
                'example' => "Une agroalimentaire de la Montérégie suit les recommandations Gartner pour choisir une plateforme d'IA générative conforme à la Loi 25.",
                'did_you_know' => "Moins de 15% des PME canadiennes consultent Gartner directement, mais beaucoup en consomment les insights via des partenaires locaux.",
                'acronym_full' => null,
            ],
            [
                'name' => 'AI Act',
                'slug' => 'ai-act',
                'type' => 'acronym',
                'difficulty' => 'intermediate',
                'icon' => '📋',
                'sort_order' => 203,
                'definition' => "Cadre réglementaire européen contraignant pour les PME utilisant ou développant de l'IA, exigeant analyse de risque, documentation technique et transparence, surtout si vente à des entités publiques québécoises après juin 2026.",
                'analogy' => "Un manuel d'entretien obligatoire pour toute IA \"haute performance\".",
                'example' => "Votre chatbot clientèle doit documenter ses biais si utilisé dans un appel d'offres public québécois.",
                'did_you_know' => "Les PME non conformes risquent des amendes jusqu'à 7% de leur chiffre d'affaires mondial.",
                'acronym_full' => 'Artificial Intelligence Act',
            ],
            [
                'name' => 'RGPD',
                'slug' => 'rgpd',
                'type' => 'acronym',
                'difficulty' => 'intermediate',
                'icon' => '✅',
                'sort_order' => 205,
                'definition' => "Pour une PME québécoise, le RGPD exige la mise en place de mesures concrètes (registre de traitements, consentement clair, droit à l'effacement) dès qu'elle interagit avec des clients ou prospects situés dans l'UE, souvent en complémentarité avec la Loi 25 du Québec.",
                'analogy' => "C'est une checklist obligatoire avant d'ouvrir la porte européenne.",
                'example' => "Un studio montréalais de design web doit obtenir un accord préalable avant d'inscrire un contact berlinois à sa newsletter.",
                'did_you_know' => "La conformité RGPD peut être un avantage concurrentiel dans les appels d'offres européens.",
                'acronym_full' => 'Règlement Général sur la Protection des Données',
            ],
            [
                'name' => 'Shadow AI',
                'slug' => 'shadow-ai',
                'type' => 'ai_term',
                'difficulty' => 'intermediate',
                'icon' => '🕵️',
                'sort_order' => 206,
                'definition' => "Le Shadow AI désigne l'usage non autorisé d'outils d'intelligence artificielle (comme ChatGPT ou Gemini) par des employés, sans supervision ni contrôle de l'équipe informatique. Ce phénomène expose l'entreprise à des fuites de données, des violations de la Loi 25 ou du RGPD, et à des risques liés à la propriété intellectuelle.",
                'analogy' => "C'est l'équivalent moderne des clés USB personnelles branchées en cachette sur les postes de travail.",
                'example' => "Un comptable d'une PME montréalaise utilise ChatGPT pour résumer des états financiers confidentiels, exposant des données clients.",
                'did_you_know' => "Près de 50% des employés de PME québécoises utilisent des outils IA personnels au travail sans en informer leur direction.",
                'acronym_full' => null,
            ],
            [
                'name' => 'ISO/IEC 42001',
                'slug' => 'iso-iec-42001',
                'type' => 'acronym',
                'difficulty' => 'beginner',
                'icon' => '🛠️',
                'sort_order' => 207,
                'definition' => "ISO/IEC 42001 est une norme volontaire permettant aux PME de structurer leur gouvernance IA via des politiques, rôles clés, audits et indicateurs de performance, tout en répondant aux exigences émergentes comme la Loi 25 ou l'AI Act européen.",
                'analogy' => "C'est la \"boîte à outils certifiée pour PME qui veulent faire de l'IA sans se brûler les doigts\".",
                'example' => "Une agence montréalaise de marketing certifie son chatbot client selon ISO/IEC 42001 pour gagner des contrats publics exigeant une IA traçable.",
                'did_you_know' => "Moins de 50 PME canadiennes étaient certifiées fin 2025, un avantage concurrentiel tangible.",
                'acronym_full' => 'ISO/IEC 42001:2023 - Information technology - Artificial intelligence - Management system',
            ],
            [
                'name' => 'Commission d\'accès à l\'information',
                'slug' => 'commission-acces-information',
                'type' => 'acronym',
                'difficulty' => 'intermediate',
                'icon' => '🔍',
                'sort_order' => 208,
                'definition' => "La Commission d'accès à l'information du Québec (CAI) veille à la protection des renseignements personnels et au droit d'accès à l'information dans les secteurs public et privé. Elle applique notamment la Loi 25, qui encadre l'usage de l'IA par les entreprises québécoises, et peut imposer des amendes allant jusqu'à 10 M$ ou 2% du chiffre d'affaires mondial en cas de non-conformité.",
                'analogy' => "La CAI, c'est le \"gendarme de la vie privée\" au Québec.",
                'example' => "Une PME montréalaise utilisant un chatbot IA pour traiter des demandes clients doit documenter ses pratiques et obtenir un consentement clair, sous peine de sanction par la CAI.",
                'did_you_know' => "La CAI peut lancer un audit de conformité sans même qu'une plainte ait été déposée.",
                'acronym_full' => 'Commission d\'accès à l\'information du Québec',
            ],
        ];

        foreach ($terms as $data) {
            Term::updateOrCreate(
                ['slug->fr' => $data['slug']],
                [
                    'name' => ['fr' => $data['name'], 'fr_CA' => $data['name']],
                    'slug' => ['fr' => $data['slug'], 'fr_CA' => $data['slug']],
                    'definition' => ['fr' => $data['definition'], 'fr_CA' => $data['definition']],
                    'analogy' => ['fr' => $data['analogy'], 'fr_CA' => $data['analogy']],
                    'example' => ['fr' => $data['example'], 'fr_CA' => $data['example']],
                    'did_you_know' => ['fr' => $data['did_you_know'], 'fr_CA' => $data['did_you_know']],
                    'type' => $data['type'],
                    'difficulty' => $data['difficulty'],
                    'icon' => $data['icon'],
                    'hero_image' => null,
                    'dictionary_category_id' => $categoryId,
                    'is_published' => true,
                    'sort_order' => $data['sort_order'],
                    'acronym_full' => $data['acronym_full'] ?? null,
                ]
            );
        }
    }
}
