<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Auth\Models\BlockedIp;
use Modules\Auth\Models\LoginAttempt;

class SecurityDashboardController
{
    public function index(): View
    {
        $last24h = now()->subHours(24);

        $stats = [
            'total_logins' => LoginAttempt::where('logged_in_at', '>=', $last24h)->count(),
            'successful' => LoginAttempt::where('logged_in_at', '>=', $last24h)->where('status', 'success')->count(),
            'failed' => LoginAttempt::where('logged_in_at', '>=', $last24h)->where('status', 'failed')->count(),
            'blocked_ips' => BlockedIp::where(function ($q) {
                $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now());
            })->count(),
        ];

        $suspiciousIps = DB::table('login_attempts')
            ->select('ip_address', DB::raw('COUNT(*) as fail_count'))
            ->where('status', 'failed')
            ->where('logged_in_at', '>=', $last24h)
            ->groupBy('ip_address')
            ->orderByDesc('fail_count')
            ->limit(5)
            ->get();

        $recentAttempts = LoginAttempt::with('user')
            ->latest('logged_in_at')
            ->limit(10)
            ->get();

        return view('backoffice::security.index', [
            'title' => 'Sécurité',
            'subtitle' => 'Tableau de bord',
            'stats' => $stats,
            'suspiciousIps' => $suspiciousIps,
            'recentAttempts' => $recentAttempts,
        ]);
    }
}
