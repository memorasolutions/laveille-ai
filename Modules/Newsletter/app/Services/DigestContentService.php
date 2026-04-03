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
                'prompt' => "Resous cette enigme etape par etape : si j'ai 3 boites et chacune contient 2 balles rouges et 1 bleue, combien de balles bleues ai-je au total ? Pense a voix haute a chaque etape avant de donner ta reponse finale.",
                'explain' => 'Force le raisonnement pas a pas pour resoudre des problemes complexes.',
            ],
            'chaine de pensee' => [
                'prompt' => "Resous cette enigme etape par etape : si j'ai 3 boites et chacune contient 2 balles rouges et 1 bleue, combien de balles bleues ai-je au total ? Pense a voix haute a chaque etape avant de donner ta reponse finale.",
                'explain' => 'Force le raisonnement pas a pas pour resoudre des problemes complexes.',
            ],
            'step-back' => [
                'prompt' => "D'abord, enumere 3 principes fondamentaux de la cybersecurite. Ensuite, utilise ces principes pour expliquer pourquoi les mots de passe courts sont dangereux. Reponse en points.",
                'explain' => 'Commence par des principes generaux avant d\'aborder le cas specifique.',
            ],
            'few-shot' => [
                'prompt' => "Classifie ces outils IA (exemples : ChatGPT = chatbot, Midjourney = image, Runway = video). Maintenant classifie : Suno = ?, ElevenLabs = ?, Cursor = ?",
                'explain' => 'Fournit quelques exemples pour guider la classification.',
            ],
            'zero-shot' => [
                'prompt' => "Classifie cette phrase comme positive, negative ou neutre : \"L'IA va transformer l'education au Quebec d'ici 2030.\"",
                'explain' => 'Demande une tache sans aucun exemple prealable, testant la comprehension du modele.',
            ],
            'role prompting' => [
                'prompt' => "Tu es un chef cuisinier etoile. Explique le concept de base de donnees relationnelle en utilisant uniquement des metaphores culinaires. Maximum 100 mots.",
                'explain' => 'Attribue un role precis a l\'IA pour orienter le style et le contenu.',
            ],
            'tree-of-thought' => [
                'prompt' => "Propose 3 approches differentes pour apprendre a coder en 2026. Pour chaque approche, evalue les avantages et inconvenients, puis choisis la meilleure avec justification.",
                'explain' => 'Explore plusieurs branches de raisonnement en parallele avant de converger.',
            ],
            'graph-of-thought' => [
                'prompt' => "Analyse les liens entre ces 3 concepts : IA generative, emploi et education. Pour chaque paire, decris la relation. Puis synthetise comment les 3 interagissent ensemble.",
                'explain' => 'Cartographie les relations entre concepts pour une comprehension systemique.',
            ],
            'react' => [
                'prompt' => "Tache : trouver la population du Quebec en 2026. Etape 1 : Reflechis a la meilleure source. Etape 2 : Consulte ta connaissance. Etape 3 : Donne le chiffre avec ta source. Alterne reflexion et action.",
                'explain' => 'Combine raisonnement (Reason) et action (Act) de maniere iterative.',
            ],
            'self-refine' => [
                'prompt' => "Ecris un slogan pour une application de meditation IA. Puis evalue ton slogan sur 3 criteres (clarte, emotion, memorabilite). Ameliore-le en fonction de ton evaluation.",
                'explain' => 'Demande a l\'IA d\'evaluer et d\'ameliorer sa propre reponse.',
            ],
            'self-consistency' => [
                'prompt' => "Reponds 3 fois a cette question : \"Quel est le meilleur langage de programmation pour debuter en 2026 ?\" Puis compare tes 3 reponses et donne ta reponse finale la plus fiable.",
                'explain' => 'Genere plusieurs reponses et choisit la plus coherente par consensus.',
            ],
            'least-to-most' => [
                'prompt' => "Decompose cette tache complexe du plus simple au plus difficile : creer un chatbot IA pour un site web. Commence par l'etape la plus basique et progresse vers la plus avancee.",
                'explain' => 'Decompose un probleme du plus simple au plus complexe, etape par etape.',
            ],
            'meta-prompting' => [
                'prompt' => "Genere le meilleur prompt possible pour obtenir un resume executif d'un article scientifique. Explique pourquoi chaque element de ton prompt est important.",
                'explain' => 'Genere ou optimise d\'autres prompts de maniere recursive.',
            ],
            'emotion prompting' => [
                'prompt' => "Cette reponse est vraiment importante pour ma carriere. Explique-moi les 3 competences IA les plus recherchees en 2026 au Quebec, avec des conseils concrets pour les developper.",
                'explain' => 'Integre des elements emotionnels pour influencer la qualite de la reponse.',
            ],
            'generated knowledge' => [
                'prompt' => "D'abord, genere 5 faits cles sur le changement climatique et l'IA. Ensuite, utilise ces faits pour rediger un paragraphe expliquant comment l'IA aide a combattre le changement climatique.",
                'explain' => 'Fait generer des connaissances pertinentes avant de repondre a la question.',
            ],
            'analogical' => [
                'prompt' => "Explique le fonctionnement d'un reseau de neurones artificiels en utilisant l'analogie d'une equipe de soccer. Chaque joueur = un neurone. L'entraineur = l'algorithme.",
                'explain' => 'Utilise des analogies pour transferer des connaissances entre domaines.',
            ],
            'contrastive' => [
                'prompt' => "Compare le fine-tuning et le RAG : similitudes, differences, et dans quelles situations choisir l'un plutot que l'autre. Presente sous forme de tableau.",
                'explain' => 'Compare des cas similaires et differents pour clarifier les concepts.',
            ],
            'skeleton-of-thought' => [
                'prompt' => "D'abord, ecris le squelette (plan en 5 points) d'un article sur l'avenir de l'education avec l'IA. Ensuite, developpe chaque point en 2 phrases.",
                'explain' => 'Genere d\'abord une structure/squelette, puis developpe chaque partie.',
            ],
            'program-of-thought' => [
                'prompt' => "Resous ce probleme comme un programme : si un abonnement coute 12$/mois avec 20% de rabais annuel, combien je paie par an ? Ecris le calcul etape par etape comme du pseudo-code.",
                'explain' => 'Structure le raisonnement comme un programme informatique avec des etapes logiques.',
            ],
            'chain-of-verification' => [
                'prompt' => "Reponds a cette question : quels sont les 3 plus grands modeles de langage en 2026 ? Puis verifie chacune de tes affirmations : est-ce que c'est encore vrai ? Corrige si necessaire.",
                'explain' => 'Genere une reponse puis verifie systematiquement chaque affirmation.',
            ],
            'chain-of-density' => [
                'prompt' => "Resume cet article en 5 versions de plus en plus denses : version 1 = 50 mots generaux, version 5 = 50 mots ultra-precis avec tous les faits cles. Article : L'IA generative transforme l'education au Quebec.",
                'explain' => 'Genere des resumes de plus en plus denses et informatifs a chaque iteration.',
            ],
            'directional stimulus' => [
                'prompt' => "Ecris un courriel professionnel pour demander une augmentation. Indices : mentionne tes resultats du dernier trimestre, le marche actuel, et ta loyaute. Ton : assertif mais respectueux.",
                'explain' => 'Guide la generation avec des indices semantiques cibles pour orienter le contenu.',
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

        // PRIORITE 2 : loi/regulation
        $lawKw = ['loi', 'rgpd', 'regulation', 'gouvernance', 'ethique', 'audit', 'confidentialite', 'responsable'];
        foreach ($lawKw as $kw) {
            if (str_contains($lower, $kw)) {
                return [
                    'prompt' => "Tu es un expert en conformite numerique. Analyse les implications de {$termName} pour un site web quebecois. Presente 3 actions concretes, classees par urgence.",
                    'technique' => "Prompt structure avec role d'expert : l'attribution d'un role specialise guide l'IA vers une reponse autoritaire et contextuelle.",
                ];
            }
        }

        // PRIORITE 3 : concept general
        return [
            'prompt' => "Tu es un vulgarisateur scientifique. Explique {$termName} avec une analogie de la vie quotidienne. Structure : l'analogie, la definition simple, un exemple concret en 2026.",
            'technique' => "Prompt structure avec role et format : le role de vulgarisateur et la structure en 3 parties guident l'IA vers une explication accessible.",
        ];
    }
}
