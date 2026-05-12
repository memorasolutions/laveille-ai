<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Tools\Models\AvatarConfig;

class AvatarController extends Controller
{
    private const ADMIN_EMAILS = [
        'chatgptpro@gomemora.com',
        'stephane@memora.ca',
        'info@memora.ca',
    ];

    public function index(Request $request)
    {
        $isAdmin = $this->isAdmin($request);

        if (! $isAdmin) {
            return view('tools::avatar.construction');
        }

        $existing = AvatarConfig::where('user_email', $this->currentEmail($request))
            ->latest()
            ->first();

        return view('tools::avatar.editor', [
            'existingConfig' => $existing?->config,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'config' => 'required|array',
        ]);

        $cfg = AvatarConfig::create([
            'user_email' => $this->currentEmail($request),
            'config' => $validated['config'],
            'is_public' => false,
        ]);

        return response()->json([
            'ok' => true,
            'slug' => $cfg->slug,
            'id' => $cfg->id,
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        $email = $this->currentEmail($request);

        return $email !== null && in_array($email, self::ADMIN_EMAILS, true);
    }

    private function currentEmail(Request $request): ?string
    {
        if ($user = Auth::user()) {
            return $user->email ?? null;
        }

        return $request->query('preview_email') ?: null;
    }
}
