<?php

declare(strict_types=1);

namespace Modules\Authors\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Authors\Models\AuthorProfile;

class AuthorsHealthCommand extends Command
{
    protected $signature = 'authors:health';

    protected $description = 'Healthcheck Authors Platform Memora';

    public function handle(): int
    {
        $checks = [];

        $requiredTables = [
            'author_profiles', 'author_posts', 'author_comments',
            'author_subscribers', 'author_webmentions', 'author_affiliate_links',
            'author_push_subscriptions', 'author_activity_logs', 'activity_log',
        ];

        foreach ($requiredTables as $table) {
            $exists = Schema::hasTable($table);
            $checks[] = ['Table '.$table, $exists, $exists ? 'present' : 'MISSING'];
        }

        $hasStripe = ! empty(config('cashier.secret'));
        $checks[] = ['Stripe Cashier secret', $hasStripe, $hasStripe ? 'configured' : 'missing (Tips + Premium disabled)'];

        $hasBrevo = config('mail.default') === 'brevo' || ! empty(config('mail.mailers.brevo'));
        $checks[] = ['Brevo Mail transport', $hasBrevo, $hasBrevo ? 'configured' : 'fallback default'];

        $hasTurnstile = ! empty(config('services.turnstile.secret_key'));
        $checks[] = ['Cloudflare Turnstile', $hasTurnstile, $hasTurnstile ? 'configured' : 'graceful bypass (anti-bot fallback regex)'];

        $hasVapid = ! empty(config('services.webpush.vapid_public_key'));
        $checks[] = ['Web Push VAPID keys', $hasVapid, $hasVapid ? 'configured' : 'stub mode (no real push)'];

        $authorsCount = AuthorProfile::whereNull('archived_at')->count();
        $checks[] = ['Active Authors', $authorsCount > 0, $authorsCount.' active'];

        foreach ([
            'sitemap-authors.xml' => 'Sitemap',
            'manifest.webmanifest' => 'PWA Manifest',
            'sw-authors.js' => 'Service Worker',
        ] as $path => $label) {
            $fileExists = file_exists(public_path($path));
            $checks[] = [$label.' ('.$path.')', $fileExists, $fileExists ? 'file exists' : 'check route handler'];
        }

        $rows = array_map(fn ($c) => [$c[0], $c[1] ? '✅' : '❌', $c[2]], $checks);

        $this->info('🏥 Authors Platform Healthcheck');
        $this->newLine();
        $this->table(['Check', 'Status', 'Detail'], $rows);

        $failedCount = count(array_filter($checks, fn ($c) => ! $c[1]
            && ! str_contains((string) $c[2], 'graceful')
            && ! str_contains((string) $c[2], 'fallback')
            && ! str_contains((string) $c[2], 'stub')
        ));

        $this->newLine();

        if ($failedCount === 0) {
            $this->info('✅ All systems operational (or graceful fallback active)');
        } else {
            $this->error("❌ {$failedCount} critical checks failed");
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
