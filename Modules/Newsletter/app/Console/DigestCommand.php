<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\Newsletter\Models\NewsletterIssue;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WeeklyDigestNotification;
use Modules\Newsletter\Services\DigestContentService;
use Modules\Settings\Models\Setting;

class DigestCommand extends Command
{
    protected $signature = 'newsletter:digest
        {--preview : Génère le brouillon et envoie un aperçu à l\'admin (dimanche)}
        {--send : Envoie le brouillon existant aux abonnés (lundi)}
        {--force : Envoie même si le digest est désactivé}';

    protected $description = 'Gestion de la newsletter hebdomadaire (preview dimanche, envoi lundi)';

    public function handle(): int
    {
        if (! Setting::get('newsletter.digest_enabled', false) && ! $this->option('force')) {
            $this->components->info('Digest désactivé. Utilisez --force pour envoyer quand même.');

            return self::SUCCESS;
        }

        $year = (int) now()->year;
        $week = (int) now()->weekOfYear;

        if ($this->option('preview')) {
            return $this->handlePreview($year, $week);
        }

        return $this->handleSend($year, $week);
    }

    /**
     * MODE PREVIEW (dimanche 8h) : génère le brouillon + envoie aperçu à l'admin.
     */
    private function handlePreview(int $year, int $week): int
    {
        $this->components->info("Génération du brouillon semaine #$week...");

        $data = DigestContentService::gatherFreshContent();

        if (! $data['highlight'] && $data['topNews']->isEmpty()) {
            $this->components->info('Pas d\'actualités cette semaine. Brouillon non créé.');

            return self::SUCCESS;
        }

        // Sauvegarder le brouillon
        if (Schema::hasTable('newsletter_issues')) {
            NewsletterIssue::updateOrCreate(
                ['year' => $year, 'week_number' => $week],
                [
                    'subject' => 'La veille IA #'.$week.' - '.config('app.name'),
                    'status' => 'draft',
                    'content' => [
                        'highlight_id' => $data['highlight']?->id,
                        'top_news_ids' => $data['topNews']->pluck('id')->toArray(),
                        'tool_id' => $data['toolOfWeek']?->id,
                        'article_id' => $data['featuredArticle']?->id,
                        'term_id' => $data['aiTerm']?->id,
                        'interactive_tool_id' => $data['interactiveTool']?->id,
                        'weekly_prompt' => $data['weeklyPrompt'],
                        'editorial' => $data['editorial'] ?? null,
                    ],
                ]
            );
        }

        // Envoyer l'aperçu à l'admin
        $adminEmail = env('SUPER_ADMIN_EMAIL');
        if ($adminEmail) {
            Notification::route('mail', $adminEmail)->notify(
                new WeeklyDigestNotification(
                    $data['highlight'], $data['topNews'], $data['toolOfWeek'],
                    $data['featuredArticle'], null, $data['weekNumber'],
                    $data['aiTerm'], $data['interactiveTool'],
                    $data['weeklyPrompt'], $data['editorial'] ?? null
                )
            );
            $this->components->info("Aperçu envoyé à $adminEmail");
        }

        $this->newLine();
        $this->components->twoColumnDetail('Terme IA', $data['aiTerm']?->name ?? 'aucun');
        $this->components->twoColumnDetail('Outil semaine', $data['toolOfWeek']?->name ?? 'aucun');
        $this->components->twoColumnDetail('Éditorial', $data['editorial'] ? 'généré' : 'non');
        $this->components->info("Brouillon #$week sauvegardé. Modifiable dans le backend avant lundi 8h.");

        return self::SUCCESS;
    }

    /**
     * MODE SEND (lundi 8h) : envoie le brouillon (ou la version éditée) aux abonnés.
     */
    private function handleSend(int $year, int $week): int
    {
        $this->components->info("Envoi newsletter semaine #$week...");

        // Chercher un brouillon existant
        $issue = null;
        if (Schema::hasTable('newsletter_issues')) {
            $issue = NewsletterIssue::where('year', $year)
                ->where('week_number', $week)
                ->whereIn('status', ['draft', 'ready'])
                ->first();
        }

        // Reconstruire le contenu
        $data = $issue
            ? DigestContentService::gatherFromIssue($issue)
            : DigestContentService::gatherFreshContent();

        if (! ($data['highlight'] ?? null) && ($data['topNews'] ?? collect())->isEmpty()) {
            $this->components->info('Pas de contenu. Newsletter non envoyée.');

            return self::SUCCESS;
        }

        // Si l'admin a édité l'éditorial, utiliser sa version
        if ($issue && $issue->editorial_edited) {
            $data['editorial'] = $issue->editorial_edited;
        }

        $subscribers = Subscriber::active()->get();
        if ($subscribers->isEmpty()) {
            $this->components->info('Aucun abonné actif.');

            return self::SUCCESS;
        }

        // Envoi aux abonnés
        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new WeeklyDigestNotification(
                $data['highlight'], $data['topNews'], $data['toolOfWeek'] ?? null,
                $data['featuredArticle'] ?? null, null, $data['weekNumber'],
                $data['aiTerm'] ?? null, $data['interactiveTool'] ?? null,
                $data['weeklyPrompt'] ?? null, $data['editorial'] ?? null
            ));
        }

        // Mettre à jour l'issue
        if ($issue) {
            $issue->update(['status' => 'sent', 'sent_at' => now(), 'subscriber_count' => $subscribers->count()]);
        } elseif (Schema::hasTable('newsletter_issues')) {
            NewsletterIssue::updateOrCreate(
                ['year' => $year, 'week_number' => $week],
                [
                    'subject' => 'La veille IA #'.$data['weekNumber'].' - '.config('app.name'),
                    'status' => 'sent',
                    'content' => [
                        'highlight_id' => $data['highlight']?->id,
                        'top_news_ids' => ($data['topNews'] ?? collect())->pluck('id')->toArray(),
                        'tool_id' => $data['toolOfWeek']?->id ?? null,
                        'article_id' => $data['featuredArticle']?->id ?? null,
                        'term_id' => $data['aiTerm']?->id ?? null,
                        'interactive_tool_id' => $data['interactiveTool']?->id ?? null,
                        'weekly_prompt' => $data['weeklyPrompt'] ?? null,
                        'editorial' => $data['editorial'] ?? null,
                    ],
                    'subscriber_count' => $subscribers->count(),
                    'sent_at' => now(),
                ]
            );
        }

        $this->newLine();
        $this->components->twoColumnDetail('Abonnés', (string) $subscribers->count());
        $this->components->twoColumnDetail('Depuis brouillon', $issue ? 'oui' : 'non (contenu frais)');
        $this->components->twoColumnDetail('Éditorial édité', ($issue && $issue->editorial_edited) ? 'oui' : 'non');
        $this->components->info("Newsletter #$week envoyée avec succès.");

        return self::SUCCESS;
    }
}
