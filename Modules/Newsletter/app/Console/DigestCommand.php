<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
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

        // Section 3 : outil de la semaine (rotation aleatoire)
        $toolOfWeek = null;
        if (class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = \Modules\Directory\Models\Tool::where('status', 'published')
                ->inRandomOrder()
                ->first();
        }

        // Section 4 : article blog vedette
        $featuredArticle = null;
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::published()
                ->latest('published_at')
                ->first();
        }

        // Section 5 : le saviez-vous (terme glossaire ou acronyme)
        $didYouKnow = null;
        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            $didYouKnow = \Modules\Dictionary\Models\Term::where('is_published', true)
                ->inRandomOrder()
                ->first();
        }
        if (! $didYouKnow && class_exists(\Modules\Acronyms\Models\Acronym::class)) {
            $didYouKnow = \Modules\Acronyms\Models\Acronym::inRandomOrder()->first();
        }

        // Section 6 : outil interactif gratuit (rotation)
        $interactiveTool = null;
        if (class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = \Modules\Tools\Models\Tool::where('is_active', true)
                ->inRandomOrder()
                ->first();
        }

        // Section 7 : terme IA de la semaine (1 seul, hero card educative)
        $aiTerm = null;
        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = \Modules\Dictionary\Models\Term::where('is_published', true)
                ->inRandomOrder()
                ->first();
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

        $this->newLine();
        $this->components->twoColumnDetail('Highlight', $highlight?->title ?? 'none');
        $this->components->twoColumnDetail('Top news', (string) $topNews->count());
        $this->components->twoColumnDetail('Tool of week', $toolOfWeek?->name ?? 'none');
        $this->components->twoColumnDetail('Featured article', $featuredArticle?->title ?? 'none');
        $this->components->twoColumnDetail('Did you know', $didYouKnow?->term ?? $didYouKnow?->name ?? 'none');
        $this->components->twoColumnDetail('Subscribers', (string) $subscribers->count());
        $this->components->info('Weekly digest #'.$weekNumber.' sent successfully.');

        return self::SUCCESS;
    }

    private static function generateWeeklyPrompt(string $termName, ?string $termType = null): string
    {
        $lower = mb_strtolower($termName);

        // Lois et reglements
        if (str_contains($lower, 'loi') || str_contains($lower, 'rgpd') || str_contains($lower, 'regulation') || str_contains($lower, 'gouvernance') || str_contains($lower, 'ethique') || str_contains($lower, 'audit')) {
            return "Analyse mon site web [collez votre URL] et identifie les 3 points les plus urgents a corriger pour etre conforme a {$termName}. Pour chaque point, propose une solution concrete et realisable en moins de 2 heures.";
        }

        // Techniques et methodes
        $techniques = ['apprentissage', 'fine-tuning', 'rag', 'prompt', 'chain', 'embedding', 'token', 'inference', 'distillation', 'quantification', 'rlhf', 'few-shot', 'zero-shot', 'attention', 'transformer', 'vectorisation', 'clustering', 'classification', 'regression', 'mlops'];
        foreach ($techniques as $kw) {
            if (str_contains($lower, $kw)) {
                return "Concois une experience pratique que je peux realiser en 15 minutes pour comprendre {$termName} : les outils gratuits a utiliser, un exemple concret avec mes propres donnees, et ce que je devrais observer comme resultat.";
            }
        }

        // Outils et technologies
        if (str_contains($lower, 'gpu') || str_contains($lower, 'tpu') || str_contains($lower, 'cloud') || str_contains($lower, 'api') || str_contains($lower, 'ocr') || str_contains($lower, 'iot')) {
            return "Je suis debutant. Explique-moi {$termName} avec une analogie simple, puis montre-moi comment je pourrais l'utiliser gratuitement cette semaine dans un projet personnel concret.";
        }

        // Concept general (defaut)
        return "Explique {$termName} comme si je n'y connaissais rien, puis propose-moi une idee originale pour l'appliquer dans un projet personnel cette semaine — quelque chose de faisable sans competences techniques avancees.";
    }
}
