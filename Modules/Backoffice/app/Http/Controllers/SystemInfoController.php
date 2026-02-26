<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SystemInfoController extends Controller
{
    public function index(): View
    {
        $php = [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'extensions' => get_loaded_extensions(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache' => function_exists('opcache_get_status') && opcache_get_status() !== false,
        ];

        $laravel = [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
        ];

        $server = [
            'os' => PHP_OS_FAMILY.' '.php_uname('r'),
            'hostname' => (string) gethostname(),
            'disk_free' => disk_free_space(base_path()),
            'disk_total' => disk_total_space(base_path()),
        ];

        $modulesFile = base_path('modules_statuses.json');
        $modulesStatuses = file_exists($modulesFile)
            ? json_decode((string) file_get_contents($modulesFile), true)
            : [];
        $modules = is_array($modulesStatuses)
            ? array_filter($modulesStatuses, fn ($status) => $status === true)
            : [];

        return view('backoffice::system-info.index', compact('php', 'laravel', 'server', 'modules'));
    }
}
