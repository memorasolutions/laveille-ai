<?php

declare(strict_types=1);

namespace Modules\Notifications\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Mail\DigestMail;

class SendNotificationDigest extends Command
{
    protected $signature = 'notifications:send-digest {--frequency=daily}';

    protected $description = 'Envoie les notifications groupées par email';

    public function handle(): int
    {
        $frequency = $this->option('frequency');
        $cutoff = $frequency === 'weekly' ? now()->subWeek() : now()->subDay();

        $users = User::where('notification_frequency', $frequency)
            ->whereExists(function ($query) use ($cutoff) {
                $query->select(DB::raw(1))
                    ->from('notifications')
                    ->whereColumn('notifiable_id', 'users.id')
                    ->where('notifiable_type', User::class)
                    ->whereNull('read_at')
                    ->where('created_at', '>=', $cutoff);
            })
            ->get();

        $total = 0;

        foreach ($users as $user) {
            $notifications = $user->notifications()
                ->whereNull('read_at')
                ->where('created_at', '>=', $cutoff)
                ->get();

            if ($notifications->isEmpty()) {
                continue;
            }

            Mail::to($user)->send(new DigestMail($user, $notifications));

            $user->notifications()
                ->whereNull('read_at')
                ->where('created_at', '>=', $cutoff)
                ->update(['read_at' => now()]);

            $this->info("Digest envoyé à {$user->email} ({$notifications->count()} notifications)");
            $total++;
        }

        $this->info("Digest {$frequency} terminé. {$total} utilisateurs notifiés.");

        return self::SUCCESS;
    }
}
