<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

class DataExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function export(): JsonResponse
    {
        $user = auth()->user();
        $data = [
            'export_date' => now()->toIso8601String(),
            'platform' => config('app.name'),
        ];

        $data['profile'] = [
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toIso8601String(),
        ];

        try {
            if (class_exists(\Modules\Tools\Models\SavedPrompt::class)) {
                $data['saved_prompts'] = \Modules\Tools\Models\SavedPrompt::forUser($user->id)
                    ->get(['name', 'prompt_text', 'params', 'created_at'])
                    ->toArray();
            }
        } catch (\Throwable) {}

        try {
            if (class_exists(\Modules\Core\Models\Bookmark::class)) {
                $data['bookmarks'] = \Modules\Core\Models\Bookmark::where('user_id', $user->id)
                    ->get(['bookmarkable_type', 'bookmarkable_id', 'created_at'])
                    ->toArray();
            }
        } catch (\Throwable) {}

        try {
            if (class_exists(\Modules\Newsletter\Models\Subscriber::class)) {
                $subscriber = \Modules\Newsletter\Models\Subscriber::where('email', $user->email)->first();
                $data['newsletter'] = $subscriber ? [
                    'subscribed' => !$subscriber->unsubscribed_at,
                    'confirmed_at' => $subscriber->confirmed_at,
                    'unsubscribed_at' => $subscriber->unsubscribed_at,
                ] : ['subscribed' => false];
            }
        } catch (\Throwable) {}

        try {
            if (Schema::hasTable('login_attempts')) {
                $data['login_history'] = DB::table('login_attempts')
                    ->where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get(['ip_address', 'created_at'])
                    ->map(fn ($a) => [
                        'ip' => preg_replace('/\d+$/', 'xxx', $a->ip_address ?? ''),
                        'created_at' => $a->created_at,
                    ])->toArray();
            }
        } catch (\Throwable) {}

        try {
            if (class_exists(\Modules\Privacy\Models\UserConsent::class)) {
                $data['consents'] = \Modules\Privacy\Models\UserConsent::where('ip_hash', hash('sha256', request()->ip()))
                    ->get(['choices', 'jurisdiction', 'created_at'])
                    ->toArray();
            }
        } catch (\Throwable) {}

        $filename = 'laveille-export-' . now()->format('Y-m-d') . '.json';

        return Response::json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
