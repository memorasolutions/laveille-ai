<?php

declare(strict_types=1);

namespace Modules\Authors\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Authors\Mail\AuthorReactivationMail;
use Modules\Authors\Models\AuthorActivityLog;
use Modules\Authors\Models\AuthorProfile;
use Throwable;

class AuthorActivityCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(): void
    {
        $authors = AuthorProfile::active()->with('user')->get();
        $processedCount = 0;

        foreach ($authors as $author) {
            $daysSince = $author->daysSinceLastPublish();

            $recentLog = AuthorActivityLog::where('author_profile_id', $author->id)
                ->where('event_type', 'reactivation_email_sent')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->exists();

            if ($recentLog) {
                continue;
            }

            $eventType = match (true) {
                $daysSince >= 30 && $daysSince < 60 => 'soft_nudge',
                $daysSince >= 60 && $daysSince < 90 => 'at_risk',
                $daysSince >= 90 && $daysSince < 180 => 'dormant',
                $daysSince >= 180 => 'final',
                default => null,
            };

            if ($eventType === null) {
                continue;
            }

            if (! $author->user || ! $author->user->email) {
                continue;
            }

            try {
                Mail::to($author->user->email)->send(new AuthorReactivationMail($author, $eventType));

                AuthorActivityLog::create([
                    'author_profile_id' => $author->id,
                    'event_type' => 'reactivation_email_sent',
                    'event_meta' => ['sequence' => $eventType, 'days_since' => $daysSince],
                ]);

                $processedCount++;
            } catch (Throwable $e) {
                Log::warning('Reactivation email failed for author '.$author->id.': '.$e->getMessage());
            }
        }

        Log::info("AuthorActivityCheckJob processed {$processedCount} authors");
    }

    public function failed(Throwable $e): void
    {
        Log::error('AuthorActivityCheckJob failed: '.$e->getMessage());
    }
}
