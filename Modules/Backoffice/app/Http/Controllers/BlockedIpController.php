<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Auth\Models\BlockedIp;

class BlockedIpController
{
    public function index(): View
    {
        $blockedIps = BlockedIp::latest()->paginate(25);

        return view('backoffice::blocked-ips.index', [
            'title' => 'IPs bloquées',
            'subtitle' => 'Sécurité',
            'blockedIps' => $blockedIps,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        BlockedIp::updateOrCreate(
            ['ip_address' => $validated['ip_address']],
            [
                'reason' => $validated['reason'] ?? 'Blocage manuel',
                'auto_blocked' => false,
            ]
        );

        return back()->with('success', "IP {$validated['ip_address']} bloquée.");
    }

    public function destroy(BlockedIp $blockedIp): RedirectResponse
    {
        $ip = $blockedIp->ip_address;
        $blockedIp->delete();

        return back()->with('success', "IP {$ip} débloquée.");
    }
}
