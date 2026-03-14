<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $level = $request->get('level', 'all');
        $entries = $this->parseLogEntries($level);

        return view('backoffice::logs.index', compact('entries', 'level'));
    }

    public function clear(): RedirectResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return back()->with('success', 'Journaux vidés.');
    }

    private function parseLogEntries(string $level): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile) || filesize($logFile) === 0) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', file_get_contents($logFile));
        $entries = [];

        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)/', $line, $m)) {
                if ($level === 'all' || strtolower($m[2]) === strtolower($level)) {
                    $entries[] = [
                        'date' => $m[1],
                        'level' => strtoupper($m[2]),
                        'message' => mb_substr($m[3], 0, 200),
                    ];
                }
            }
        }

        return array_slice(array_reverse($entries), 0, 100);
    }
}
