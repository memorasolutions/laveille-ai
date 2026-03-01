<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Console;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Notifications\TrialEndingNotification;

class SendTrialExpiryNotifications extends Command
{
    protected $signature = 'saas:trial-expiry-notify';

    protected $description = 'Send trial expiry notifications to users whose trial ends in 3 days or today';

    public function handle(): int
    {
        $today = Carbon::today();
        $threeDaysFromNow = Carbon::today()->addDays(3);
        $sent = 0;

        // Users whose trial ends today
        $expiringToday = DB::table('subscriptions')
            ->where('stripe_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $today)
            ->pluck('user_id');

        foreach ($expiringToday as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new TrialEndingNotification($today->toDateString()));
                $sent++;
            }
        }

        // Users whose trial ends in 3 days
        $expiringInThreeDays = DB::table('subscriptions')
            ->where('stripe_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $threeDaysFromNow)
            ->pluck('user_id');

        foreach ($expiringInThreeDays as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new TrialEndingNotification($threeDaysFromNow->toDateString()));
                $sent++;
            }
        }

        $this->info("Sent {$sent} trial expiry notifications.");

        return self::SUCCESS;
    }
}
