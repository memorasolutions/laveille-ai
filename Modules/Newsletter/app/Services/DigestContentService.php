<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Newsletter\Models\NewsletterIssue;

/**
 * Service centralisant la collecte de contenu pour la newsletter digest.
 * Utilise par DigestCommand (envoi) et NewsletterWebController (affichage web).
 * Aucune duplication de code : toute la logique est ici.
 */
class DigestContentService
{
    /**
     * Collecte du contenu frais pour un nouveau digest (utilise par DigestCommand).
     */
    public static function gatherFreshContent(): array
    {
        $highlight = null;
        $topNews = collect();

        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            $highlight = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->subDays(7))
                ->orderByDesc('relevance_score')
                ->first();

            $topNews = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->subDays(7))
                ->when($highlight, fn ($q) => $q->where('id', '!=', $highlight->id))
                ->orderByDesc('relevance_score')
                ->take(5)
                ->get();
        }

        $toolOfWeek = null;
        if (class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = self::getUnsentItem('tool', \Modules\Directory\Models\Tool::where('status', 'published')->inRandomOrder());
        }

        $featuredArticle = null;
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::published()->latest('published_at')->first();
        }

        $interactiveTool = null;
        if (class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = self::getUnsentItem('interactive_tool', \Modules\Tools\Models\Tool::where('is_active', true)->inRandomOrder());
        }

        $aiTerm = null;
        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = self::getUnsentItem('term', \Modules\Dictionary\Models\Term::where('is_published', true)->inRandomOrder());
        }

        $weekNumber = (int) now()->weekOfYear;

        $weeklyPrompt = null;
        if ($weekNumber % 2 === 0 && $aiTerm) {
            $weeklyPrompt = self::generateWeeklyPrompt($aiTerm->name ?? '', $aiTerm->type ?? null);
        }

        // Mini-editorial auto-genere via IA
        $editorial = self::generateEditorial(
            $highlight?->seo_title ?? $highlight?->title,
            $highlight?->summary ?? null
        );

        return compact('highlight', 'topNews', 'toolOfWeek', 'featuredArticle', 'aiTerm', 'interactiveTool', 'weeklyPrompt', 'weekNumber', 'editorial');
    }

    /**
     * Reconstruit le contenu depuis une issue sauvegardee (utilise par le web controller).
     */
    public static function gatherFromIssue(NewsletterIssue $issue): array
    {
        $content = $issue->content ?? [];

        $highlight = null;
        if (($content['highlight_id'] ?? null) && class_exists(\Modules\News\Models\NewsArticle::class)) {
            $highlight = \Modules\News\Models\NewsArticle::find($content['highlight_id']);
        }

        $topNews = collect();
        if (! empty($content['top_news_ids']) && class_exists(\Modules\News\Models\NewsArticle::class)) {
            $topNews = \Modules\News\Models\NewsArticle::whereIn('id', $content['top_news_ids'])->get();
        }

        $toolOfWeek = null;
        if (($content['tool_id'] ?? null) && class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = \Modules\Directory\Models\Tool::find($content['tool_id']);
        }

        $featuredArticle = null;
        if (($content['article_id'] ?? null) && class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::find($content['article_id']);
        }

        $aiTerm = null;
        if (($content['term_id'] ?? null) && class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = \Modules\Dictionary\Models\Term::find($content['term_id']);
        }

        $interactiveTool = null;
        if (($content['interactive_tool_id'] ?? null) && class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = \Modules\Tools\Models\Tool::find($content['interactive_tool_id']);
        }

        $weeklyPrompt = $content['weekly_prompt'] ?? null;
        $editorial = $content['editorial'] ?? null;
        $weekNumber = $issue->week_number;

        return compact('highlight', 'topNews', 'toolOfWeek', 'featuredArticle', 'aiTerm', 'interactiveTool', 'weeklyPrompt', 'weekNumber', 'editorial');
    }

    /**
     * Genere un mini-editorial via OpenRouter deepseek-chat.
     */
    public static function generateEditorial(?string $highlightTitle, ?string $highlightSummary): ?string
    {
        if (! $highlightTitle) {
            return null;
        }

        try {
            $apiKey = env('OPENROUTER_API_KEY');
            if (! $apiKey) {
                return null;
            }

            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'deepseek/deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es Stephane Lapointe, fondateur de laveille.ai, passionne d'IA et d'education au Quebec. Tu ecris un mini-editorial de 50 mots maximum pour ta newsletter hebdomadaire. Ton style : curieux, direct, authentique, avec une touche quebecoise sans etre caricatural. Pas de tiret cadratin. Utilise des guillemets francais.",
                        ],
                        [
                            'role' => 'user',
                            'content' => "Ecris un mini-editorial base sur cette actualite : {$highlightTitle}. Contexte : {$highlightSummary}. Format : 1 question provocante ou reflexion personnelle + 1-2 phrases de contexte. Termine par '- Stephane'. Maximum 50 mots.",
                        ],
                    ],
                    'temperature' => 0.8,
                    'max_tokens' => 150,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Throwable $e) {
            Log::warning('Newsletter editorial generation failed: '.$e->getMessage());
        }

        return "Cette semaine en IA, une question me trotte dans la tete... Bonne lecture ! - Stephane";
    }

    /**
     * Selectionne un item non encore envoye. Reset le cycle quand tout est envoye.
     */
    public static function getUnsentItem(string $type, $query): ?object
    {
        if (! Schema::hasTable('newsletter_sent_items')) {
            return $query->first();
        }

        $sentIds = DB::table('newsletter_sent_items')
            ->where('type', $type)
            ->pluck('item_id')
            ->toArray();

        $item = (clone $query)->whereNotIn('id', $sentIds)->first();

        if (! $item) {
            DB::table('newsletter_sent_items')->where('type', $type)->delete();
            $item = (clone $query)->first();
        }

        if ($item) {
            DB::table('newsletter_sent_items')->insert([
                'type' => $type,
                'item_id' => $item->id,
                'week_number' => (int) now()->weekOfYear,
                'year' => (int) now()->year,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $item;
    }

    /**
     * Genere un prompt adapte au terme IA.
     * PRIORITE 1 : si le terme est une technique de prompting, le prompt DEMONTRE cette technique.
     * PRIORITE 2 : loi/regulation → prompt structure avec role expert.
     * PRIORITE 3 : concept general → prompt structure avec role vulgarisateur.
     */
    public static function generateWeeklyPrompt(string $termName, ?string $termType = null): array
    {
        $lower = mb_strtolower($termName);

        // PRIORITE 1 : le terme EST une technique de prompting → le prompt la demontre
        $promptExamples = [
            'chain-of-thought' => [
                'prompt' => "Résous cette énigme étape par étape : si j'ai 3 boîtes et chacune contient 2 balles rouges et 1 bleue, combien de balles bleues ai-je au total ? Pense à voix haute à chaque étape avant de donner ta réponse finale.",
                'explain' => 'Force le raisonnement pas à pas pour résoudre des problèmes complexes.',
            ],
            'chaine de pensee' => [
                'prompt' => "Résous cette énigme étape par étape : si j'ai 3 boîtes et chacune contient 2 balles rouges et 1 bleue, combien de balles bleues ai-je au total ? Pense à voix haute à chaque étape avant de donner ta réponse finale.",
                'explain' => 'Force le raisonnement pas à pas pour résoudre des problèmes complexes.',
            ],
            'step-back' => [
                'prompt' => "D'abord, énumère 3 principes fondamentaux de la cybersécurité. Ensuite, utilise ces principes pour expliquer pourquoi les mots de passe courts sont dangereux. Réponse en points.",
                'explain' => 'Commence par des principes généraux avant d\'aborder le cas spécifique.',
            ],
            'few-shot' => [
                'prompt' => "Classifie ces outils IA (exemples : ChatGPT = chatbot, Midjourney = image, Runway = video). Maintenant classifie : Suno = ?, ElevenLabs = ?, Cursor = ?",
                'explain' => 'Fournit quelques exemples pour guider la classification.',
            ],
            'zero-shot' => [
                'prompt' => "Classifie cette phrase comme positive, negative ou neutre : \"L'IA va transformer l'education au Quebec d'ici 2030.\"",
                'explain' => 'Demande une tâche sans aucun exemple préalable, testant la compréhension du modèle.',
            ],
            'role prompting' => [
                'prompt' => "Tu es un chef cuisinier étoilé. Explique le concept de base de données relationnelle en utilisant uniquement des métaphores culinaires. Maximum 100 mots.",
                'explain' => 'Attribue un rôle précis à l\'IA pour orienter le style et le contenu.',
            ],
            'tree-of-thought' => [
                'prompt' => "Propose 3 approches différentes pour apprendre à coder en 2026. Pour chaque approche, évalue les avantages et inconvénients, puis choisis la meilleure avec justification.",
                'explain' => 'Explore plusieurs branches de raisonnement en parallèle avant de converger.',
            ],
            'graph-of-thought' => [
                'prompt' => "Analyse les liens entre ces 3 concepts : IA générative, emploi et éducation. Pour chaque paire, décris la relation. Puis synthétise comment les 3 interagissent ensemble.",
                'explain' => 'Cartographie les relations entre concepts pour une compréhension systémique.',
            ],
            'react' => [
                'prompt' => "Tâche : trouver la population du Québec en 2026. Étape 1 : Réfléchis à la meilleure source. Étape 2 : Consulte ta connaissance. Étape 3 : Donne le chiffre avec ta source. Alterne réflexion et action.",
                'explain' => 'Combine raisonnement (Reason) et action (Act) de manière itérative.',
            ],
            'self-refine' => [
                'prompt' => "Écris un slogan pour une application de méditation IA. Puis évalue ton slogan sur 3 critères (clarté, émotion, mémorabilité). Améliore-le en fonction de ton évaluation.",
                'explain' => 'Demande à l\'IA d\'évaluer et d\'améliorer sa propre réponse.',
            ],
            'self-consistency' => [
                'prompt' => "Réponds 3 fois à cette question : \"Quel est le meilleur langage de programmation pour débuter en 2026 ?\" Puis compare tes 3 réponses et donne ta réponse finale la plus fiable.",
                'explain' => 'Genere plusieurs reponses et choisit la plus coherente par consensus.',
            ],
            'least-to-most' => [
                'prompt' => "Décompose cette tâche complexe du plus simple au plus difficile : créer un chatbot IA pour un site web. Commence par l'étape la plus basique et progresse vers la plus avancée.",
                'explain' => 'Décompose un problème du plus simple au plus complexe, étape par étape.',
            ],
            'meta-prompting' => [
                'prompt' => "Génère le meilleur prompt possible pour obtenir un résumé exécutif d'un article scientifique. Explique pourquoi chaque élément de ton prompt est important.",
                'explain' => 'Génère ou optimise d\'autres prompts de manière récursive.',
            ],
            'emotion prompting' => [
                'prompt' => "Cette réponse est vraiment importante pour ma carrière. Explique-moi les 3 compétences IA les plus recherchées en 2026 au Québec, avec des conseils concrets pour les développer.",
                'explain' => 'Intègre des éléments émotionnels pour influencer la qualité de la réponse.',
            ],
            'generated knowledge' => [
                'prompt' => "D'abord, génère 5 faits clés sur le changement climatique et l'IA. Ensuite, utilise ces faits pour rédiger un paragraphe expliquant comment l'IA aide à combattre le changement climatique.",
                'explain' => 'Fait generer des connaissances pertinentes avant de repondre a la question.',
            ],
            'analogical' => [
                'prompt' => "Explique le fonctionnement d'un réseau de neurones artificiels en utilisant l'analogie d'une équipe de soccer. Chaque joueur = un neurone. L'entraîneur = l'algorithme.",
                'explain' => 'Utilise des analogies pour transférer des connaissances entre domaines.',
            ],
            'contrastive' => [
                'prompt' => "Compare le fine-tuning et le RAG : similitudes, différences, et dans quelles situations choisir l'un plutôt que l'autre. Présente sous forme de tableau.",
                'explain' => 'Compare des cas similaires et différents pour clarifier les concepts.',
            ],
            'skeleton-of-thought' => [
                'prompt' => "D'abord, écris le squelette (plan en 5 points) d'un article sur l'avenir de l'éducation avec l'IA. Ensuite, développe chaque point en 2 phrases.",
                'explain' => 'Genere d\'abord une structure/squelette, puis developpe chaque partie.',
            ],
            'program-of-thought' => [
                'prompt' => "Résous ce problème comme un programme : si un abonnement coûte 12$/mois avec 20% de rabais annuel, combien je paie par an ? Écris le calcul étape par étape comme du pseudo-code.",
                'explain' => 'Structure le raisonnement comme un programme informatique avec des etapes logiques.',
            ],
            'chain-of-verification' => [
                'prompt' => "Réponds à cette question : quels sont les 3 plus grands modèles de langage en 2026 ? Puis vérifie chacune de tes affirmations : est-ce que c'est encore vrai ? Corrige si nécessaire.",
                'explain' => 'Génère une réponse puis vérifie systématiquement chaque affirmation.',
            ],
            'chain-of-density' => [
                'prompt' => "Resume cet article en 5 versions de plus en plus denses : version 1 = 50 mots generaux, version 5 = 50 mots ultra-precis avec tous les faits cles. Article : L'IA generative transforme l'education au Quebec.",
                'explain' => 'Génère des résumés de plus en plus denses et informatifs à chaque itération.',
            ],
            'directional stimulus' => [
                'prompt' => "Écris un courriel professionnel pour demander une augmentation. Indices : mentionne tes résultats du dernier trimestre, le marché actuel, et ta loyauté. Ton : assertif mais respectueux.",
                'explain' => 'Guide la génération avec des indices sémantiques ciblés pour orienter le contenu.',
            ],
        ];

        foreach ($promptExamples as $key => $data) {
            if (str_contains($lower, $key)) {
                return [
                    'prompt' => $data['prompt'],
                    'technique' => "Ce prompt est un exemple concret de {$termName}. {$data['explain']}",
                ];
            }
        }

        // PRIORITE 2 : loi/regulation → consultant conformité
        $lawKw = ['loi', 'rgpd', 'regulation', 'gouvernance', 'ethique', 'audit', 'confidentialite', 'responsable'];
        foreach ($lawKw as $kw) {
            if (str_contains($lower, $kw)) {
                return [
                    'prompt' => "Tu es un consultant en conformité numérique spécialisé dans les lois québécoises et canadiennes. Explique les implications pratiques de {$termName} pour un propriétaire de petite entreprise ayant un site web. Indique 3 actions concrètes et urgentes à entreprendre, ainsi que les risques encourus en cas de non-conformité. Présente ta réponse sous forme de liste numérotée, en utilisant un langage simple et des conseils actionnables.",
                    'technique' => "Prompt structuré avec rôle d'expert : le rôle de consultant spécialisé cadre l'IA vers des recommandations concrètes et pratiques.",
                ];
            }
        }

        // PRIORITE 2b : technologie/outil → formateur + analogie cuisine
        $toolKw = ['gpu', 'tpu', 'cloud', 'api', 'ocr', 'iot', 'benchmark', 'modèle de', 'modèle du'];
        foreach ($toolKw as $kw) {
            if (str_contains($lower, $kw)) {
                return [
                    'prompt' => "Tu es un formateur en technologies qui excelle dans les analogies créatives. Explique {$termName} à quelqu'un qui n'a jamais codé mais est curieux de technologie, en utilisant une analogie tirée de la cuisine. Montre un outil gratuit qu'il peut essayer cette semaine pour expérimenter ce concept. Explique pourquoi c'est important en 2026. Structure ta réponse en 3 paragraphes : l'analogie, l'aspect pratique avec l'outil, et l'importance future. Adopte un ton décontracté et amical.",
                    'technique' => "Prompt structuré avec analogie imposée : la contrainte de la cuisine rend le concept tangible et mémorable pour les non-initiés.",
                ];
            }
        }

        // PRIORITE 3 : concept général → professeur PCRF complet
        return [
            'prompt' => "Tu es un professeur passionné qui enseigne l'IA à des débutants depuis 20 ans. Explique le concept de {$termName} à un public adulte non-technique en utilisant uniquement des exemples de la vie de tous les jours. Décris le concept étape par étape : commence par une analogie simple, explique ensuite comment cela fonctionne de manière simplifiée, donne un exemple concret de son utilisation en 2026, et termine par une idée fausse courante à son sujet. Rédige ta réponse en 4 courts paragraphes, avec un ton amical et un total de moins de 300 mots.",
            'technique' => "Prompt structuré PCRF (persona, contexte, requête, format) : chaque élément guide l'IA vers une réponse pédagogique adaptée à l'audience.",
        ];
    }
}
