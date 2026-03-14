<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Translation\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class TranslationService
{
    public function getLocales(): array
    {
        return collect(File::files(lang_path()))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->all();
    }

    public function getTranslations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
    }

    public function setTranslation(string $locale, string $key, string $value): void
    {
        $translations = $this->getTranslations($locale);
        $translations[$key] = $value;
        $this->saveTranslations($locale, $translations);
    }

    public function deleteTranslation(string $locale, string $key): void
    {
        $translations = $this->getTranslations($locale);
        unset($translations[$key]);
        $this->saveTranslations($locale, $translations);
    }

    public function addLocale(string $locale): void
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            File::put($path, json_encode((object) [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
        }
    }

    public function importFromArray(string $locale, array $translations): void
    {
        $existing = $this->getTranslations($locale);
        $merged = array_merge($existing, $translations);
        $this->saveTranslations($locale, $merged);
    }

    public function addKey(string $key, array $values): void
    {
        $locales = $this->getLocales();

        foreach ($locales as $locale) {
            $translations = $this->getTranslations($locale);
            $translations[$key] = $values[$locale] ?? '';
            $this->saveTranslations($locale, $translations);
        }
    }

    public function deleteKey(string $key): void
    {
        $locales = $this->getLocales();

        foreach ($locales as $locale) {
            $this->deleteTranslation($locale, $key);
        }
    }

    public function getTranslationCount(string $locale, string $sourceLocale = 'fr'): array
    {
        $sourceTranslations = $this->getTranslations($sourceLocale);
        $targetTranslations = $this->getTranslations($locale);
        $total = count($sourceTranslations);
        $translated = 0;

        foreach ($sourceTranslations as $key => $value) {
            if (isset($targetTranslations[$key]) && $targetTranslations[$key] !== '') {
                $translated++;
            }
        }

        return ['total' => $total, 'translated' => $translated];
    }

    public function removeLocale(string $locale): void
    {
        if ($locale === 'fr' || $locale === 'en') {
            throw new InvalidArgumentException("Cannot remove protected locale: {$locale}");
        }

        $path = lang_path("{$locale}.json");

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    protected function saveTranslations(string $locale, array $translations): void
    {
        ksort($translations);
        File::put(
            lang_path("{$locale}.json"),
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n"
        );
    }
}
