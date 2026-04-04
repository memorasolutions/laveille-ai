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

        if (! $editorial && class_exists(\Modules\Newsletter\Models\EditorialBank::class)) {
            try {
                $bankEditorial = \Modules\Newsletter\Models\EditorialBank::getNextEditorial();
                $editorial = $bankEditorial?->content;
            } catch (\Throwable) {}
        }

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
                            'content' => "Ecris un mini-editorial base sur cette actualite : {$highlightTitle}. Contexte : {$highlightSummary}. Format : 1 question provocante ou reflexion personnelle + 1-2 phrases de contexte. Termine par '- Stephane'. Maximum 50 mots. INTERDIT : pas de Markdown, pas de **, pas de *, pas de #. Texte brut uniquement.",
                        ],
                    ],
                    'temperature' => 0.8,
                    'max_tokens' => 150,
                ]);

            if ($response->successful()) {
                return self::stripMarkdown($response->json('choices.0.message.content') ?? '');
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

        // PRIORITE 2 : tous les autres termes → prompt généré dynamiquement par IA
        // Chaque semaine, une technique de prompting différente est utilisée (rotation)
        // L'IA génère un prompt unique, créatif et pédagogique basé sur les meilleures pratiques actuelles
        return self::generateDynamicPrompt($termName, (int) now()->weekOfYear);
    }

    /**
     * Explication grand public d'une technique de prompting (pour la newsletter).
     */
    public static function getTechniqueExplanation(string $technique): string
    {
        return match (mb_strtolower($technique)) {
            'chaîne de pensée' => "Cette technique demande à l'IA de réfléchir étape par étape. Essayez de lui demander : \"Explique-moi comment faire X, en détaillant chaque étape\".",
            'role prompting' => "On donne un rôle précis à l'IA, comme un expert ou un professeur. Demandez-lui : \"Agis comme un spécialiste en X et explique-moi Y\".",
            'few-shot' => "On montre à l'IA quelques exemples pour qu'elle comprenne ce qu'on attend. Donnez-lui 2-3 exemples avant de poser votre vraie question.",
            'analogie' => "On demande à l'IA de comparer un concept à quelque chose de la vie quotidienne. Dites : \"Explique-moi ce concept comme si c'était une recette de cuisine\".",
            'step-back' => "On part des principes généraux avant d'aborder le cas précis. Commencez par : \"Quels sont les grands principes de X avant de parler de Y ?\".",
            'décomposition' => "On découpe un gros problème en petites étapes faciles à suivre. Demandez : \"Décompose ce problème en étapes simples\".",
            'self-refine' => "On demande à l'IA de vérifier et d'améliorer sa propre réponse. Après sa réponse, ajoutez : \"Évalue ta réponse et améliore-la\".",
            'contraintes créatives' => "On donne des limites précises pour obtenir une réponse plus ciblée. Essayez : \"Réponds en 3 paragraphes, avec un exemple concret du Québec\".",
            default => "Cette technique aide l'IA à mieux comprendre votre demande. Essayez de reformuler votre question clairement pour de meilleurs résultats.",
        };
    }

    /**
     * Nettoie le Markdown des réponses IA (**, *, #, ```, etc.)
     */
    public static function stripMarkdown(string $text): string
    {
        $text = preg_replace('/\*{1,3}([^*]+)\*{1,3}/', '$1', $text); // **bold**, *italic*
        $text = preg_replace('/#{1,6}\s*/', '', $text); // # headers
        $text = preg_replace('/`{1,3}[^`]*`{1,3}/', '', $text); // `code`, ```blocks```
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text); // [links](url)
        $text = str_replace(['```', '`'], '', $text);

        return trim($text);
    }

    /**
     * Génère un prompt unique via OpenRouter deepseek-chat.
     * Chaque semaine, une technique de prompting différente est sélectionnée (rotation).
     */
    public static function generateDynamicPrompt(string $termName, int $weekNumber): array
    {
        $techniques = [
            'chaîne de pensée',
            'role prompting',
            'few-shot',
            'analogie',
            'step-back',
            'décomposition',
            'self-refine',
            'contraintes créatives',
        ];

        $selectedTechnique = $techniques[$weekNumber % count($techniques)];

        try {
            $apiKey = env('OPENROUTER_API_KEY');
            if (! $apiKey) {
                throw new \RuntimeException('OPENROUTER_API_KEY manquante');
            }

            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'deepseek/deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un expert en prompt engineering pour une infolettre éducative sur l'IA au Québec. Tu génères des prompts uniques, créatifs et pédagogiques. Tous les accents français doivent être présents. Style authentiquement humain, pas robotique.",
                        ],
                        [
                            'role' => 'user',
                            'content' => "Génère un prompt prêt à copier-coller dans ChatGPT, Claude ou Gemini, sur le terme IA '{$termName}', qui utilise la technique de prompting '{$selectedTechnique}'.\n\nRÈGLES STRICTES :\n- Le prompt commence directement par l'instruction à l'IA (pas de 'Salut', pas de 'Aujourd'hui on explore', pas d'explication de la technique)\n- Le lecteur doit pouvoir le coller tel quel et obtenir une réponse utile\n- En français, ton direct, entre 40 et 80 mots\n- La technique doit être IMPLICITE dans la structure du prompt, pas expliquée dedans\n\nExemple de BON prompt (technique self-refine) :\n'Explique le concept de tokenisation en IA en 3 phrases simples. Ensuite, évalue ta propre explication : est-elle claire pour un débutant ? Corrige les points faibles et propose une version améliorée.'\n\nExemple de MAUVAIS prompt :\n'Salut! Aujourd'hui on explore la tokenisation avec la technique self-refine. Voici comment...'\n\nRéponds UNIQUEMENT avec le texte du prompt. Rien d'autre. INTERDIT : pas de Markdown, pas de **, pas de *, pas de #, pas de backticks, pas d'introduction, pas d'explication de la technique.",
                        ],
                    ],
                    'temperature' => 0.8,
                    'max_tokens' => 400,
                ]);

            if ($response->successful()) {
                $text = self::stripMarkdown(trim($response->json('choices.0.message.content') ?? ''));
                if ($text) {
                    return [
                        'prompt' => $text,
                        'technique' => self::getTechniqueExplanation($selectedTechnique),
                    ];
                }
            }

            Log::warning('Newsletter dynamic prompt generation failed', ['status' => $response->status(), 'term' => $termName]);
        } catch (\Throwable $e) {
            Log::warning('Newsletter dynamic prompt error: '.$e->getMessage());
        }

        // Fallback statique si l'API échoue
        return [
            'prompt' => "Tu es un professeur passionné qui enseigne l'IA à des débutants depuis 20 ans. Explique le concept de {$termName} à un public adulte non-technique en utilisant uniquement des exemples de la vie de tous les jours. Décris le concept étape par étape : commence par une analogie simple, explique ensuite comment cela fonctionne de manière simplifiée, donne un exemple concret de son utilisation en 2026, et termine par une idée fausse courante à son sujet. Rédige ta réponse en 4 courts paragraphes, avec un ton amical et un total de moins de 300 mots.",
            'technique' => "Prompt structuré PCRF (fallback) : le prompt statique est utilisé car la génération dynamique a échoué.",
        ];
    }
}
