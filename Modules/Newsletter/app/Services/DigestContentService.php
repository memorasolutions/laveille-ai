<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\DB;
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

        return compact('highlight', 'topNews', 'toolOfWeek', 'featuredArticle', 'aiTerm', 'interactiveTool', 'weeklyPrompt', 'weekNumber');
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
        $weekNumber = $issue->week_number;

        return compact('highlight', 'topNews', 'toolOfWeek', 'featuredArticle', 'aiTerm', 'interactiveTool', 'weeklyPrompt', 'weekNumber');
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
     * Genere un prompt RCTFC adapte au terme IA avec technique nommee.
     */
    public static function generateWeeklyPrompt(string $termName, ?string $termType = null): array
    {
        $lower = mb_strtolower($termName);
        $search = $lower.' '.mb_strtolower($termType ?? '');

        $lawKw = ['loi', 'rgpd', 'regulation', 'gouvernance', 'ethique', 'audit', 'confidentialite', 'responsable'];
        foreach ($lawKw as $kw) {
            if (str_contains($search, $kw)) {
                return [
                    'prompt' => "Tu es un expert en conformite numerique. Analyse etape par etape les implications pratiques de {$termName} pour un site web quebecois. Presente 3 actions concretes a realiser cette semaine, classees par urgence.",
                    'technique' => "Role prompting + chaine de pensee : le role d'expert guide l'IA vers une analyse structuree etape par etape.",
                ];
            }
        }

        $techKw = ['apprentissage', 'fine-tuning', 'rag', 'prompt', 'chain', 'embedding', 'token', 'inference', 'distillation', 'quantification', 'rlhf', 'few-shot', 'zero-shot', 'attention', 'transformer', 'vectorisation', 'clustering', 'classification', 'regression', 'mlops', 'reseau', 'perceptron', 'encodeur', 'segmentation', 'detection'];
        foreach ($techKw as $kw) {
            if (str_contains($search, $kw)) {
                return [
                    'prompt' => "Propose une experience pratique de 15 minutes pour explorer {$termName} en utilisant uniquement des outils gratuits. Decris les etapes numerotees, du setup initial a l'interpretation des resultats.",
                    'technique' => "Zero-shot avec contraintes : aucun exemple fourni, mais des contraintes precises (15 minutes, gratuit, etapes numerotees) qui cadrent la reponse.",
                ];
            }
        }

        $toolKw = ['gpu', 'tpu', 'cloud', 'api', 'ocr', 'iot', 'benchmark', 'modele de', 'modele du'];
        foreach ($toolKw as $kw) {
            if (str_contains($search, $kw)) {
                return [
                    'prompt' => "Tu es un formateur en technologies. Explique {$termName} avec une analogie de la cuisine, puis montre comment je pourrais l'utiliser gratuitement cette semaine dans un projet personnel. Reponse en 3 paragraphes courts.",
                    'technique' => "Role prompting + analogie : la comparaison avec la cuisine rend le concept concret et memorable.",
                ];
            }
        }

        return [
            'prompt' => "Tu es un vulgarisateur scientifique. Explique le concept de {$termName} en utilisant une analogie tiree de la vie quotidienne. Structure ta reponse en trois paragraphes : l'analogie, la definition simple, et un exemple d'application en 2026.",
            'technique' => "Role prompting + demande d'analogie : le role de vulgarisateur force un langage accessible, et l'analogie ancre le concept dans le quotidien.",
        ];
    }
}
