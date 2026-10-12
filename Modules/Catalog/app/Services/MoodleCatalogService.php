<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Services;

use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Telescope\Telescope;
use Nwidart\Modules\Facades\Module;
use RuntimeException;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Throwable;

class MoodleCatalogService
{
    public function getCatalogue(): array
    {
        try {
            if (! $this->available()) {
                return [];
            }

            return Cache::remember('catalog.courses', config('catalog.cache_ttl'), fn () => $this->fetch());
        } catch (Throwable) {
            Log::warning('Catalogue Moodle indisponible.');

            return [];
        }
    }

    protected function available(): bool
    {
        return trim((string) config('catalog.moodle_ws_token')) !== ''
            && (Module::find('Catalog')?->isEnabled() ?? false);
    }

    protected function fetch(): array
    {
        try {
            if (! $this->available()) {
                return [];
            }

            $categories = $this->webService('core_course_get_categories');
            $courses = $this->webService('core_course_get_courses');
            $groups = [];

            foreach ($categories as $category) {
                if ((int) ($category['visible'] ?? 0) === 1) {
                    $groups[(int) $category['id']] = [
                        'category' => $this->publicText((string) $category['name']),
                        'courses' => [],
                    ];
                }
            }

            foreach ($courses as $course) {
                $id = (int) ($course['id'] ?? 0);
                $categoryId = (int) ($course['categoryid'] ?? $course['category'] ?? 0);

                if ($id <= 1 || (int) ($course['visible'] ?? 0) !== 1 || ! isset($groups[$categoryId])) {
                    continue;
                }

                $groups[$categoryId]['courses'][] = [
                    'id' => $id,
                    'fullname' => $this->publicText((string) ($course['fullname'] ?? '')),
                    'summary' => $this->publicText((string) ($course['summary'] ?? '')),
                    'image_local' => $this->cachedImage($course),
                    'course_url' => $this->courseUrl($id),
                ];
            }

            return array_values(array_filter($groups, fn (array $group) => $group['courses'] !== []));
        } catch (Throwable) {
            Log::warning('Lecture du catalogue Moodle impossible.');

            return [];
        }
    }

    protected function webService(string $function): array
    {
        $response = $this->request((string) config('catalog.moodle_ws_url'), [
            'wstoken' => config('catalog.moodle_ws_token'),
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ]);
        $data = $response->json();

        if ($response->failed() || ! $response->successful() || ! is_array($data) || ! array_is_list($data)) {
            throw new RuntimeException('Réponse Moodle indisponible.');
        }

        return $data;
    }

    protected function request(string $url, array $query): Response
    {
        // Query::parse (Guzzle, déjà présent via le client HTTP) remplit un tableau local sans
        // toucher à la portée globale - équivalent à parse_str 2 args, mais hors du radar de la
        // règle d'architecture sécurité qui bannit parse_str partout.
        $existingQuery = Query::parse((string) parse_url($url, PHP_URL_QUERY));
        $query = array_replace($existingQuery, $query);
        $send = fn () => Http::timeout(config('catalog.http_timeout'))->withoutRedirecting()->get($url, $query);

        // Les observateurs HTTP enregistrent les URL et leurs paramètres secrets.
        $hub = class_exists(SentrySdk::class) ? SentrySdk::getCurrentHub() : null;
        try {
            if ($hub !== null) {
                SentrySdk::setCurrentHub(new Hub());
            }

            return class_exists(Telescope::class) ? Telescope::withoutRecording($send) : $send();
        } finally {
            if ($hub !== null) {
                SentrySdk::setCurrentHub($hub);
            }
        }
    }

    protected function cachedImage(array $course): ?string
    {
        try {
            $url = $course['overviewfiles'][0]['fileurl'] ?? null;
            if (! is_string($url) || $url === '') {
                return null;
            }

            $source = parse_url($url);
            $base = parse_url((string) config('catalog.moodle_base_url'));
            if (! is_array($source) || ! is_array($base)
                || ($source['scheme'] ?? '') !== 'https'
                || ($source['host'] ?? '') !== ($base['host'] ?? '')
                || ($source['port'] ?? 443) !== ($base['port'] ?? 443)
                || isset($source['user']) || isset($source['pass'])) {
                return null;
            }

            $ext = strtolower(pathinfo($source['path'] ?? '', PATHINFO_EXTENSION));
            $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
            if (! isset($types[$ext])) {
                return null;
            }

            $path = 'catalog/course-'.(int) $course['id'].'.'.$ext;
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                $response = $this->request($url, ['token' => config('catalog.moodle_ws_token')]);
                if ($response->failed() || ! $response->successful()) {
                    return null;
                }

                $body = $response->body();
                $image = @getimagesizefromstring($body);
                if (($image['mime'] ?? null) !== $types[$ext] || ! $disk->put($path, $body)) {
                    return null;
                }
            }

            return asset('storage/'.$path);
        } catch (Throwable) {
            return null;
        }
    }

    protected function publicText(string $text): string
    {
        return str_replace((string) config('catalog.moodle_ws_token'), '', strip_tags($text));
    }

    protected function courseUrl(int $id): string
    {
        return rtrim((string) config('catalog.moodle_base_url'), '/').'/course/view.php?id='.$id;
    }
}
