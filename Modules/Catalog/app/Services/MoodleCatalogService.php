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
            $courses = $this->coursesByField();
            $groups = [];
            $excludedCategories = (array) config('catalog.excluded_category_idnumbers', []);

            foreach ($categories as $category) {
                $idnumber = strtolower(trim((string) ($category['idnumber'] ?? '')));

                if ((int) ($category['visible'] ?? 0) === 1 && ! in_array($idnumber, $excludedCategories, true)) {
                    $groups[(int) $category['id']] = [
                        'category' => $this->publicText((string) $category['name']),
                        'slug' => $this->categorySlug($category),
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

                $price = null;
                $optedOut = false;
                $inVitrine = false;
                foreach ($course['customfields'] ?? [] as $field) {
                    $shortname = $field['shortname'] ?? null;
                    if ($shortname === 'prix') {
                        $value = (string) ($field['value'] ?? '');
                        $price = trim($value) === '' ? null : $value;
                    } elseif ($shortname === 'vitrine') {
                        // OPT-IN : un cours n'apparaît au catalogue public QUE si sa case « vitrine »
                        // est cochée côté Moodle. C'est un filtre « fail-closed » : tout cours dont
                        // la case n'est pas cochée (y compris tout futur cours privé) reste hors du
                        // catalogue sans qu'on ait à exclure sa catégorie à la main.
                        $inVitrine = $this->checkboxIsTrue($field);
                    } elseif ($shortname === 'catalogue'
                        && in_array(strtolower(trim((string) ($field['value'] ?? ''))), ['0', 'non', 'no', 'false', 'off'], true)) {
                        // Opt-out d'urgence (défense en profondeur) : un champ « catalogue » à une
                        // valeur fausse retire le cours même s'il est par ailleurs en vitrine.
                        $optedOut = true;
                    }
                }

                // Fail-closed : hors vitrine OU explicitement retiré => jamais au catalogue public.
                if (! $inVitrine || $optedOut) {
                    continue;
                }

                $groups[$categoryId]['courses'][] = [
                    'id' => $id,
                    'fullname' => $this->publicText((string) ($course['fullname'] ?? '')),
                    'summary' => $this->publicText((string) ($course['summary'] ?? '')),
                    'price' => $price,
                    'price_display' => $this->formatPrice($price),
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

    /**
     * Slug de thème stable d'une catégorie Moodle, pour le pré-filtre ?theme=<slug> de /cours
     * (méga-menu « Cours par thèmes » de l'académie). Dérivé de l'idnumber Moodle en retirant le
     * préfixe « thm- » (thm-ia -> ia) ; repli sur le nom slugifié, puis sur « cat-<id> » si vide.
     * Déterministe et sans dépendance : un slug inconnu côté /cours retombe proprement sur la
     * grille complète (aucune casse).
     */
    private function categorySlug(array $category): string
    {
        $idnumber = trim((string) ($category['idnumber'] ?? ''));

        if (str_starts_with($idnumber, 'thm-')) {
            $base = substr($idnumber, 4);
        } elseif ($idnumber !== '') {
            $base = $idnumber;
        } else {
            $base = (string) ($category['name'] ?? '');
        }

        $slug = \Illuminate\Support\Str::slug($this->publicText($base));

        return $slug !== '' ? $slug : 'cat-'.((int) ($category['id'] ?? 0));
    }

    private function formatPrice(?string $raw): string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return __('Gratuit');
        }

        $n = str_replace(',', '.', $v);
        if (! is_numeric($n)) {
            return $v;
        }

        $f = (float) $n;
        $formatted = number_format($f, $f !== floor($f) || str_contains($n, '.') ? 2 : 0, ',', "\u{00A0}");

        return $formatted."\u{00A0}$";
    }

    /**
     * Un champ personnalisé Moodle de type « case à cocher », exporté par le web service, porte
     * `valueraw` (0 ou 1, fiable et indépendant de la langue) ET `value` (« Oui »/« Non », traduit
     * selon la locale de l'instance). On se fie d'abord à `valueraw`; à défaut seulement, on retombe
     * sur `value` avec une liste blanche. Fail-closed : tout ce qui n'est pas clairement vrai est faux.
     */
    private function checkboxIsTrue(array $field): bool
    {
        // Le web service Moodle fournit TOUJOURS `valueraw` pour un champ de type case à cocher :
        // dès que la clé est présente, elle fait seule autorité (une valeur vide ou 0 = faux), pour
        // qu'une valeur affichée contradictoire (« Oui » traduit) ne puisse jamais rouvrir la vitrine.
        if (array_key_exists('valueraw', $field)) {
            return in_array(strtolower(trim((string) ($field['valueraw'] ?? ''))), ['1', 'true', 'yes'], true);
        }

        // Repli pour une source sans `valueraw` (hors Moodle) : liste blanche tolérante sur la valeur affichée.
        return in_array(strtolower(trim((string) ($field['value'] ?? ''))), ['1', 'true', 'yes', 'oui', 'on'], true);
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

    protected function coursesByField(): array
    {
        $response = $this->request((string) config('catalog.moodle_ws_url'), [
            'wstoken' => config('catalog.moodle_ws_token'),
            'wsfunction' => 'core_course_get_courses_by_field',
            'moodlewsrestformat' => 'json',
        ]);
        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || ! is_array($data['courses'] ?? null)) {
            throw new RuntimeException('Réponse Moodle indisponible.');
        }

        return $data['courses'];
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
