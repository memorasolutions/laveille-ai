<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Settings\Models\Setting;

class DataRetentionController extends Controller
{
    public function index(): View
    {
        $tables = [
            [
                'table' => 'login_attempts',
                'label' => 'Tentatives de connexion',
                'column' => 'logged_in_at',
                'setting' => 'retention.login_attempts_days',
                'default' => 90,
            ],
            [
                'table' => 'sent_emails',
                'label' => 'Emails envoyés',
                'column' => 'sent_at',
                'setting' => 'retention.sent_emails_days',
                'default' => 90,
            ],
            [
                'table' => 'activity_log',
                'label' => 'Logs d\'activité',
                'column' => 'created_at',
                'setting' => 'retention.activity_log_days',
                'default' => 180,
            ],
            [
                'table' => 'blocked_ips',
                'label' => 'IPs bloquées',
                'column' => 'created_at',
                'setting' => 'retention.blocked_ips_days',
                'default' => 365,
            ],
            [
                'table' => 'magic_login_tokens',
                'label' => 'Jetons magic link',
                'column' => 'expires_at',
                'setting' => null,
                'default' => 0,
            ],
        ];

        $stats = [];
        foreach ($tables as $config) {
            $days = $config['setting']
                ? (int) Setting::get($config['setting'], $config['default'])
                : $config['default'];

            try {
                $total = DB::table($config['table'])->count();
                $eligible = $config['setting']
                    ? DB::table($config['table'])->where($config['column'], '<', now()->subDays($days))->count()
                    : DB::table($config['table'])->where($config['column'], '<', now())->count();
            } catch (\Throwable) {
                $total = 0;
                $eligible = 0;
            }

            $stats[] = [
                'table' => $config['table'],
                'label' => $config['label'],
                'total' => $total,
                'eligible' => $eligible,
                'retention_days' => $days,
            ];
        }

        return view('backoffice::data-retention.index', compact('stats'));
    }
}
