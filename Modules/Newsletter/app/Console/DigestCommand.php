<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WeeklyDigestNotification;
use Modules\Settings\Models\Setting;

class DigestCommand extends Command
{
    protected $signature = 'newsletter:digest {--force : Send even if digest is disabled in settings}';

    protected $description = 'Send weekly digest of new articles to active subscribers';

    public function handle(): int
    {
        if (! Setting::get('newsletter.digest_enabled', false) && ! $this->option('force')) {
            $this->components->info('Digest is disabled in settings. Use --force to send anyway.');

            return self::SUCCESS;
        }

        // Section 1 : fait marquant (top news article de la semaine)
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

        // Section 3 : outil de la semaine (rotation sans repetition)
        $toolOfWeek = null;
        if (class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = self::getUnsentItem('tool', \Modules\Directory\Models\Tool::where('status', 'published')->inRandomOrder());
        }

        // Section 4 : article blog vedette (le plus recent)
        $featuredArticle = null;
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::published()
                ->latest('published_at')
                ->first();
        }

        // Section 5 : (reserve pour usage futur)
        $didYouKnow = null;

        // Section 6 : outil interactif gratuit (rotation sans repetition)
        $interactiveTool = null;
        if (class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = self::getUnsentItem('interactive_tool', \Modules\Tools\Models\Tool::where('is_active', true)->inRandomOrder());
        }

        // Section 7 : terme IA de la semaine (rotation sans repetition)
        $aiTerm = null;
        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = self::getUnsentItem('term', \Modules\Dictionary\Models\Term::where('is_published', true)->inRandomOrder());
        }

        $weekNumber = (int) now()->weekOfYear;

        // Section 8 : prompt de la quinzaine (semaines paires, lie au terme IA)
        $weeklyPrompt = null;
        if ($weekNumber % 2 === 0 && $aiTerm) {
            $weeklyPrompt = self::generateWeeklyPrompt($aiTerm->name ?? '', $aiTerm->type ?? null);
        }

        if (! $highlight && $topNews->isEmpty()) {
            $this->components->info('No news articles this week. Skipping digest.');

            return self::SUCCESS;
        }

        $subscribers = Subscriber::active()->get();

        if ($subscribers->isEmpty()) {
            $this->components->info('No active subscribers found.');

            return self::SUCCESS;
        }

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new WeeklyDigestNotification(
                $highlight, $topNews, $toolOfWeek, $featuredArticle, $didYouKnow, $weekNumber, $aiTerm, $interactiveTool, $weeklyPrompt
            ));
        }

        // Sauvegarder le numero de newsletter pour l'archivage web
        if (class_exists(\Modules\Newsletter\Models\NewsletterIssue::class) && Schema::hasTable('newsletter_issues')) {
            \Modules\Newsletter\Models\NewsletterIssue::updateOrCreate(
                ['year' => (int) now()->year, 'week_number' => $weekNumber],
                [
                    'subject' => 'Veille hebdo #'.$weekNumber.' - '.config('app.name'),
                    'content' => [
                        'highlight_id' => $highlight?->id,
                        'top_news_ids' => $topNews->pluck('id')->toArray(),
                        'tool_id' => $toolOfWeek?->id,
                        'article_id' => $featuredArticle?->id,
                        'term_id' => $aiTerm?->id,
                        'interactive_tool_id' => $interactiveTool?->id,
                        'weekly_prompt' => $weeklyPrompt,
                    ],
                    'subscriber_count' => $subscribers->count(),
                    'sent_at' => now(),
                ]
            );
        }

        $this->newLine();
        $this->components->twoColumnDetail('Highlight', $highlight?->title ?? 'none');
        $this->components->twoColumnDetail('Top news', (string) $topNews->count());
        $this->components->twoColumnDetail('Tool of week', $toolOfWeek?->name ?? 'none');
        $this->components->twoColumnDetail('Featured article', $featuredArticle?->title ?? 'none');
        $this->components->twoColumnDetail('AI term', $aiTerm?->name ?? 'none');
        $this->components->twoColumnDetail('Subscribers', (string) $subscribers->count());
        $this->components->info('Weekly digest #'.$weekNumber.' sent successfully.');

        return self::SUCCESS;
    }

    /**
     * Genere un prompt RCTFC (Role + Contexte + Tache + Format + Contrainte)
     * adapte au terme IA, avec le nom de la technique de prompting utilisee.
     */
    /**
     * Selectionne un item non encore envoye dans les newsletters precedentes.
     * Quand tous les items ont ete envoyes, reset et recommence le cycle.
     */
    private static function getUnsentItem(string $type, $query): ?object
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

    private static function generateWeeklyPrompt(string $termName, ?string $termType = null): array
    {
        $lower = mb_strtolower($termName);
        $search = $lower.' '.mb_strtolower($termType ?? '');

        // Lois et reglements → role prompting + chaine de pensee
        $lawKw = ['loi', 'rgpd', 'regulation', 'gouvernance', 'ethique', 'audit', 'confidentialite', 'responsable'];
        foreach ($lawKw as $kw) {
            if (str_contains($search, $kw)) {
                return [
                    'prompt' => "Tu es un expert en conformite numerique. Analyse etape par etape les implications pratiques de {$termName} pour un site web quebecois. Presente 3 actions concretes a realiser cette semaine, classees par urgence.",
                    'technique' => "Role prompting + chaine de pensee : le role d'expert guide l'IA vers une analyse structuree etape par etape.",
                ];
            }
        }

        // Techniques et methodes → zero-shot avec contraintes
        $techKw = ['apprentissage', 'fine-tuning', 'rag', 'prompt', 'chain', 'embedding', 'token', 'inference', 'distillation', 'quantification', 'rlhf', 'few-shot', 'zero-shot', 'attention', 'transformer', 'vectorisation', 'clustering', 'classification', 'regression', 'mlops', 'reseau', 'perceptron', 'encodeur', 'segmentation', 'detection'];
        foreach ($techKw as $kw) {
            if (str_contains($search, $kw)) {
                return [
                    'prompt' => "Propose une experience pratique de 15 minutes pour explorer {$termName} en utilisant uniquement des outils gratuits. Decris les etapes numerotees, du setup initial a l'interpretation des resultats.",
                    'technique' => "Zero-shot avec contraintes : aucun exemple fourni, mais des contraintes precises (15 minutes, gratuit, etapes numerotees) qui cadrent la reponse.",
                ];
            }
        }

        // Outils et technologies → role + analogie
        $toolKw = ['gpu', 'tpu', 'cloud', 'api', 'ocr', 'iot', 'benchmark', 'modele de', 'modele du'];
        foreach ($toolKw as $kw) {
            if (str_contains($search, $kw)) {
                return [
                    'prompt' => "Tu es un formateur en technologies. Explique {$termName} avec une analogie de la cuisine, puis montre comment je pourrais l'utiliser gratuitement cette semaine dans un projet personnel. Reponse en 3 paragraphes courts.",
                    'technique' => "Role prompting + analogie : la comparaison avec la cuisine rend le concept concret et memorable.",
                ];
            }
        }

        // Concept general (defaut) → role vulgarisateur + analogie
        return [
            'prompt' => "Tu es un vulgarisateur scientifique. Explique le concept de {$termName} en utilisant une analogie tiree de la vie quotidienne. Structure ta reponse en trois paragraphes : l'analogie, la definition simple, et un exemple d'application en 2026.",
            'technique' => "Role prompting + demande d'analogie : le role de vulgarisateur force un langage accessible, et l'analogie ancre le concept dans le quotidien.",
        ];
    }
}
