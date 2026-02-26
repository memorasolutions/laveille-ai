<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Core\Shared\Traits\VerifiesPassword;

class UserSessionController extends Controller
{
    use VerifiesPassword;

    public function index(Request $request): View
    {
        $currentSessionId = $request->session()->getId();
        $userId = auth()->id();

        $sessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->get();

        $parsedSessions = $sessions->map(function ($session) use ($currentSessionId) {
            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'is_current' => $session->id === $currentSessionId,
                'parsed_agent' => $this->parseUserAgent($session->user_agent),
                'last_activity_formatted' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        });

        return view('auth::sessions.index', [
            'sessions' => $parsedSessions,
            'currentSessionId' => $currentSessionId,
        ]);
    }

    public function revoke(string $id): RedirectResponse
    {
        DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'Session révoquée.');
    }

    public function revokeOthers(Request $request): RedirectResponse
    {
        if ($failed = $this->verifyPasswordOrFail($request)) {
            return $failed;
        }

        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Toutes les autres sessions ont été révoquées.');
    }

    private function parseUserAgent(?string $ua): array
    {
        if (! $ua) {
            return ['browser' => 'Inconnu', 'os' => 'Inconnu'];
        }

        $browser = match (true) {
            stripos($ua, 'Edg') !== false => 'Edge',
            stripos($ua, 'Chrome') !== false => 'Chrome',
            stripos($ua, 'Firefox') !== false => 'Firefox',
            stripos($ua, 'Safari') !== false => 'Safari',
            stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false => 'Opera',
            default => 'Inconnu',
        };

        $os = match (true) {
            stripos($ua, 'Android') !== false => 'Android',
            stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false => 'iOS',
            stripos($ua, 'Windows') !== false => 'Windows',
            stripos($ua, 'Mac OS X') !== false => 'macOS',
            stripos($ua, 'Linux') !== false => 'Linux',
            default => 'Inconnu',
        };

        return compact('browser', 'os');
    }
}
