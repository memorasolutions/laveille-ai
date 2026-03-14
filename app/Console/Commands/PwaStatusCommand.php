<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PwaStatusCommand extends Command
{
    protected $signature = 'pwa:status';

    protected $description = 'Vérifie l\'état de la configuration PWA';

    public function handle(): int
    {
        $this->info('Vérification de la configuration PWA...');

        $results = [];

        $results[] = ['PWA activé dans config', config('pwa.enabled') ? '✅' : '❌'];
        $results[] = $this->checkRoute('/manifest.webmanifest', 'Route /manifest.webmanifest');
        $results[] = $this->checkRoute('/offline', 'Route /offline');
        $results[] = $this->checkFile('public/icons/icon-192x192.png', 'Icône 192x192');
        $results[] = $this->checkFile('public/icons/icon-512x512.png', 'Icône 512x512');
        $results[] = $this->checkFile('public/icons/apple-touch-icon-180x180.png', 'Apple Touch Icon 180x180');
        $results[] = $this->checkPackageJson();
        $results[] = $this->checkFile('resources/js/sw-source.js', 'Source Service Worker');

        $this->table(['Critère', 'Statut'], $results);

        $ok = count(array_filter($results, fn (array $r): bool => str_contains($r[1], '✅')));
        $total = count($results);

        if ($ok === $total) {
            $this->info("✅ Tous les critères PWA sont satisfaits ({$ok}/{$total})");
        } else {
            $this->warn("⚠️  Critères satisfaits : {$ok}/{$total}");
        }

        return $ok === $total ? self::SUCCESS : self::FAILURE;
    }

    private function checkRoute(string $uri, string $label): array
    {
        try {
            $request = Request::create($uri, 'GET');
            $response = app()->handle($request);

            return [$label, $response->getStatusCode() === 200 ? '✅' : '❌'];
        } catch (\Throwable) {
            return [$label, '❌'];
        }
    }

    private function checkFile(string $path, string $label): array
    {
        return [$label, File::exists(base_path($path)) ? '✅' : '❌'];
    }

    private function checkPackageJson(): array
    {
        $path = base_path('package.json');

        if (! File::exists($path)) {
            return ['vite-plugin-pwa dans package.json', '❌'];
        }

        $json = json_decode(File::get($path), true);
        $found = isset($json['devDependencies']['vite-plugin-pwa'])
            || isset($json['dependencies']['vite-plugin-pwa']);

        return ['vite-plugin-pwa dans package.json', $found ? '✅' : '❌'];
    }
}
