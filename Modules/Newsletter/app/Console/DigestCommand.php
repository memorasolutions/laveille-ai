<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Newsletter\Jobs\SendDigestJob;
use Modules\Newsletter\Models\NewsletterIssue;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WeeklyDigestNotification;
use Modules\Newsletter\Services\BrevoService;
use Modules\Newsletter\Services\DigestContentService;
use Modules\Settings\Models\Setting;

class DigestCommand extends Command
{
    protected $signature = 'newsletter:digest
        {--preview : Génère le brouillon et envoie un aperçu à l\'admin (dimanche)}
        {--send : Envoie le brouillon existant aux abonnés (lundi)}
        {--test-email= : Envoie un test du brouillon à cette adresse email}
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

        if ($testEmail = $this->option('test-email')) {
            return $this->handleTestEmail($year, $week, $testEmail);
        }

        if ($this->option('preview')) {
            return $this->handlePreview($year, $week);
        }

        return $this->handleSend($year, $week);
    }

    /**
     * MODE TEST : envoie le brouillon existant à une adresse email spécifique.
     *
     * 2026-05-06 #160 fix : utilise BrevoService::sendCampaignEmail() (API HTTP Brevo) au lieu de
     * Notification::route('mail') qui passait par SMTP Gmail bloqué (cert mismatch cPanel,
     * autoconfig.server.memora.pro vs smtp.gmail.com). La newsletter régulière passe déjà par
     * SendDigestJob → BrevoService, donc cohérent.
     */
    private function handleTestEmail(int $year, int $week, string $email): int
    {
        $issue = Schema::hasTable('newsletter_issues')
            ? NewsletterIssue::where('year', $year)->where('week_number', $week)->first()
            : null;

        $data = $issue
            ? DigestContentService::gatherFromIssue($issue)
            : DigestContentService::gatherFreshContent();

        $weekNumber = (int) ($data['weekNumber'] ?? $week);
        $customSubject = $issue?->content['subject'] ?? null;
        // Objet orienté bénéfice : on mène par le titre de la vedette (pas « #N »), sauf objet custom.
        $subject = '[TEST] '.($customSubject ?: ($data['highlight']?->seo_title ?? $data['highlight']?->title ?? 'Votre veille IA de la semaine'));

        $htmlContent = View::make('newsletter::emails.digest-weekly', [
            'subject' => $subject,
            'highlight' => $data['highlight'] ?? null,
            'topNews' => $data['topNews'] ?? collect(),
            'toolOfWeek' => $data['toolOfWeek'] ?? null,
            'featuredArticle' => $data['featuredArticle'] ?? null,
            'didYouKnow' => null,
            'aiTerm' => $data['aiTerm'] ?? null,
            'interactiveTool' => $data['interactiveTool'] ?? null,
            'weeklyPrompt' => $data['weeklyPrompt'] ?? null,
            'wellnessChallenge' => $data['wellnessChallenge'] ?? null,
            'editorial' => $data['editorial'] ?? null,
            'unsubscribeUrl' => '#test-mode-no-unsubscribe',
            'weekNumber' => $weekNumber,
        ])->render();

        // Override « aperçu validé » (même logique que l'envoi réel) pour tester le rendu EXACT.
        if (! empty($issue?->content['custom_html'] ?? null)) {
            $htmlContent = $issue->content['custom_html'];
        }

        $brevo = app(BrevoService::class);
        if (! $brevo->isConfigured()) {
            $this->components->error('BrevoService non configuré. Vérifiez BREVO_API_KEY dans .env.');
            return self::FAILURE;
        }

        $result = $brevo->sendCampaignEmail($email, null, $subject, $htmlContent);

        if (! ($result['success'] ?? false)) {
            $this->components->error("Échec envoi via Brevo : ".($result['error'] ?? 'erreur inconnue'));
            return self::FAILURE;
        }

        $this->components->info("Newsletter test W{$weekNumber} envoyée à {$email} via Brevo (message_id: ".($result['message_id'] ?? 'n/a').")");

        return self::SUCCESS;
    }

    /**
     * MODE PREVIEW (dimanche 8h) : génère le brouillon + envoie aperçu à l'admin.
     */
    private function handlePreview(int $year, int $week): int
    {
        $this->components->info("Génération du brouillon semaine #$week...");

        // Vérifier si une issue existe déjà et est verrouillée (status 'ready' = contenu accepté).
        $existingIssue = Schema::hasTable('newsletter_issues')
            ? NewsletterIssue::where('year', $year)
                ->where('week_number', $week)
                ->where('status', 'ready')
                ->first()
            : null;

        if ($existingIssue) {
            // Contenu accepté/verrouillé : NE PAS régénérer ni écraser le travail validé.
            $data = DigestContentService::gatherFromIssue($existingIssue);
            $this->components->info("Contenu verrouillé trouvé (status ready). Aperçu renvoyé sans régénération.");
        } else {
            // Générer un nouveau brouillon
            $data = DigestContentService::gatherFreshContent();

            if (! $data['highlight'] && $data['topNews']->isEmpty()) {
                $this->components->info('Pas d\'actualités cette semaine. Brouillon non créé.');

                return self::SUCCESS;
            }

            // Sauvegarder le nouveau brouillon en statut 'draft'
            if (Schema::hasTable('newsletter_issues')) {
                NewsletterIssue::updateOrCreate(
                    ['year' => $year, 'week_number' => $week],
                    [
                        'subject' => ($data['highlight']?->seo_title ?? $data['highlight']?->title ?? 'Votre veille IA de la semaine'),
                        'status' => 'draft',
                        'content' => [
                            'highlight_id' => $data['highlight']?->id,
                            'top_news_ids' => $data['topNews']->pluck('id')->toArray(),
                            'tool_id' => $data['toolOfWeek']?->id,
                            'article_id' => $data['featuredArticle']?->id,
                            'term_id' => $data['aiTerm']?->id,
                            'interactive_tool_id' => $data['interactiveTool']?->id,
                            'weekly_prompt' => $data['weeklyPrompt'],
                            'wellness_challenge' => $data['wellnessChallenge'] ?? null,
                            'editorial' => $data['editorial'] ?? null,
                        ],
                    ]
                );
            }
        }

        // Envoyer l'aperçu à l'admin dans TOUS les cas
        $adminEmail = env('SUPER_ADMIN_EMAIL');
        if ($adminEmail) {
            Notification::route('mail', $adminEmail)->notify(
                new WeeklyDigestNotification(
                    $data['highlight'], $data['topNews'], $data['toolOfWeek'],
                    $data['featuredArticle'], null, $data['weekNumber'],
                    $data['aiTerm'], $data['interactiveTool'],
                    $data['weeklyPrompt'], $data['editorial'] ?? null,
                    $data['wellnessChallenge'] ?? null
                )
            );
            $this->components->info("Aperçu envoyé à $adminEmail");
        }

        $this->newLine();
        $this->components->twoColumnDetail('Terme IA', $data['aiTerm']?->name ?? 'aucun');
        $this->components->twoColumnDetail('Outil semaine', $data['toolOfWeek']?->name ?? 'aucun');
        $this->components->twoColumnDetail('Éditorial', $data['editorial'] ? 'généré' : 'non');
        $this->components->info($existingIssue
            ? "Contenu #$week verrouillé (ready) — c'est cette version qui sera envoyée."
            : "Brouillon #$week sauvegardé. Modifiable dans le backend avant l'envoi.");

        return self::SUCCESS;
    }

    /**
     * MODE SEND (lundi 8h) : envoie le brouillon (ou la version éditée) aux abonnés.
     */
    private function handleSend(int $year, int $week): int
    {
        // Kill switch Pennant : permet pause instantanée sans redeploy
        if (class_exists(\Laravel\Pennant\Feature::class)
            && ! \Laravel\Pennant\Feature::active('cron.newsletter-send')
            && ! $this->option('force')) {
            $this->components->warn('Kill switch cron.newsletter-send actif. Use --force pour bypasser.');
            return self::SUCCESS;
        }

        // Idempotence : empêche double envoi si cron rerun (lock 30 min)
        $lock = Cache::lock("newsletter-digest-send-{$year}-w{$week}", 1800);
        if (! $lock->get()) {
            $this->components->warn("Newsletter W{$week} déjà en cours d'envoi par un autre processus. Skip.");
            return self::SUCCESS;
        }

        try {
            return $this->doHandleSend($year, $week);
        } finally {
            optional($lock)->release();
        }
    }

    private function doHandleSend(int $year, int $week): int
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

        // Honore la PAUSE et la PRÉFÉRENCE DE FRÉQUENCE (sinon « en pause » / « recevoir moins souvent »
        // = promesses non tenues offertes sur la page de désabo). Défaut (weekly / null / inconnu) =
        // toujours inclus → aucun changement pour les abonnés sans préférence.
        $nlNow = \Illuminate\Support\Carbon::now('America/Toronto');
        $subscribers = Subscriber::active()->notPaused()->get()->filter(function ($s) use ($week, $nlNow) {
            return match ($s->frequency_preference) {
                'biweekly' => $week % 2 === 0,
                'monthly' => $nlNow->day <= 7,
                default => true,
            };
        })->values();
        if ($subscribers->isEmpty()) {
            $this->components->info('Aucun abonné actif.');

            return self::SUCCESS;
        }

        // Pré-rendre le HTML une seule fois (placeholder pour unsubscribe)
        // Objet orienté bénéfice : on mène par le titre de la vedette (pas « #N »), sauf objet custom.
        $subject = ($issue?->content['subject'] ?? null) ?: ($data['highlight']?->seo_title ?? $data['highlight']?->title ?? 'Votre veille IA de la semaine');

        $htmlContent = View::make('newsletter::emails.digest-weekly', [
            'subject' => $subject,
            'highlight' => $data['highlight'] ?? null,
            'topNews' => $data['topNews'] ?? collect(),
            'toolOfWeek' => $data['toolOfWeek'] ?? null,
            'featuredArticle' => $data['featuredArticle'] ?? null,
            'didYouKnow' => null,
            'aiTerm' => $data['aiTerm'] ?? null,
            'interactiveTool' => $data['interactiveTool'] ?? null,
            'weeklyPrompt' => $data['weeklyPrompt'] ?? null,
            'wellnessChallenge' => $data['wellnessChallenge'] ?? null,
            'editorial' => $data['editorial'] ?? null,
            'unsubscribeUrl' => '{{UNSUBSCRIBE_URL}}',
            'weekNumber' => $data['weekNumber'],
        ])->render();

        // Override « aperçu validé » : si l'édition contient un HTML personnalisé (content.custom_html),
        // on l'envoie TEL QUEL au lieu de régénérer. Le placeholder {{UNSUBSCRIBE_URL}} y reste remplacé
        // par SendDigestJob pour chaque abonné. Défensif : sans custom_html, comportement inchangé.
        if (! empty($issue?->content['custom_html'] ?? null)) {
            $htmlContent = $issue->content['custom_html'];
        }

        // Sauvegarder l'issue si elle n'existe pas encore
        if (! $issue && Schema::hasTable('newsletter_issues')) {
            $issue = NewsletterIssue::updateOrCreate(
                ['year' => $year, 'week_number' => $week],
                [
                    'subject' => $subject,
                    'status' => 'draft',
                    'content' => [
                        'highlight_id' => $data['highlight']?->id,
                        'top_news_ids' => ($data['topNews'] ?? collect())->pluck('id')->toArray(),
                        'tool_id' => $data['toolOfWeek']?->id ?? null,
                        'article_id' => $data['featuredArticle']?->id ?? null,
                        'term_id' => $data['aiTerm']?->id ?? null,
                        'interactive_tool_id' => $data['interactiveTool']?->id ?? null,
                        'weekly_prompt' => $data['weeklyPrompt'] ?? null,
                        'wellness_challenge' => $data['wellnessChallenge'] ?? null,
                        'editorial' => $data['editorial'] ?? null,
                    ],
                ]
            );
        }

        // Dispatcher un Job par abonné (queue database, anti-doublon intégré)
        $dispatched = 0;
        foreach ($subscribers as $subscriber) {
            if (! $subscriber->email) {
                continue;
            }

            // Générer un token si manquant (abonnés importés sans token)
            if (empty($subscriber->token)) {
                $subscriber->update(['token' => \Illuminate\Support\Str::random(64)]);
            }

            SendDigestJob::dispatch(
                $subscriber->email,
                (string) ($subscriber->name ?? ''),
                $subject,
                $htmlContent,
                $issue?->id ?? 0,
                $subscriber->token,
            );
            $dispatched++;
        }

        // Mettre à jour l'issue
        if ($issue) {
            $issue->update(['status' => 'sent', 'sent_at' => now(), 'subscriber_count' => $dispatched]);
        }

        $this->newLine();
        $this->components->twoColumnDetail('Jobs dispatches', (string) $dispatched);
        $this->components->twoColumnDetail('Depuis brouillon', $issue ? 'oui' : 'non (contenu frais)');
        $this->components->twoColumnDetail('Éditorial édité', ($issue && $issue->editorial_edited) ? 'oui' : 'non');
        $this->components->info("Newsletter #$week : $dispatched jobs dispatches dans la queue.");

        return self::SUCCESS;
    }
}
