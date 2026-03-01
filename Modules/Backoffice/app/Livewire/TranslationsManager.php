<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AI\Services\AiService;
use Modules\Translation\Services\TranslationService;

class TranslationsManager extends Component
{
    use WithFileUploads;

    #[Url]
    public string $search = '';

    public string $targetLocale = 'en';

    public bool $showUntranslatedOnly = false;

    public int $perPage = 50;

    public int $page = 1;

    public string $newKey = '';

    public string $newSourceValue = '';

    public string $newTargetValue = '';

    public string $newLocale = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public mixed $importFile = null;

    public bool $translating = false;

    public string $translatingKey = '';

    public function boot(TranslationService $service): void
    {
        $this->service = $service;
    }

    private TranslationService $service;

    public function mount(): void
    {
        $locales = $this->service->getLocales();
        $nonFr = array_values(array_filter($locales, fn ($locale) => $locale !== 'fr'));

        if (! empty($nonFr)) {
            $this->targetLocale = $nonFr[0];
        }
    }

    public function updateTranslation(string $key, string $value): void
    {
        $this->service->setTranslation($this->targetLocale, $key, $value);
        $this->dispatch('toast', message: 'Traduction mise à jour.', type: 'success');
    }

    public function deleteKey(string $key): void
    {
        $this->service->deleteKey($key);
        $this->dispatch('toast', message: 'Clé supprimée.', type: 'success');
    }

    public function addKey(): void
    {
        $this->validate([
            'newKey' => 'required|string',
        ]);

        $this->service->addKey($this->newKey, [
            'fr' => $this->newSourceValue,
            $this->targetLocale => $this->newTargetValue,
        ]);

        $this->reset(['newKey', 'newSourceValue', 'newTargetValue']);
        $this->dispatch('toast', message: 'Clé ajoutée.', type: 'success');
    }

    public function addLocale(): void
    {
        $this->validate([
            'newLocale' => 'required|string|size:2|alpha',
        ]);

        $this->service->addLocale($this->newLocale);
        $this->targetLocale = $this->newLocale;
        $this->reset('newLocale');
        $this->dispatch('toast', message: 'Langue ajoutée.', type: 'success');
    }

    public function exportLocale()
    {
        $translations = $this->service->getTranslations($this->targetLocale);
        $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $this->targetLocale.'.json');
    }

    public function importLocale(): void
    {
        $this->validate([
            'importFile' => 'required|file',
        ]);

        $content = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->dispatch('toast', message: 'Fichier JSON invalide.', type: 'error');

            return;
        }

        $this->service->importFromArray($this->targetLocale, $data);
        $this->importFile = null;
        $this->dispatch('toast', message: 'Traductions importées.', type: 'success');
    }

    public function autoTranslate(string $key): void
    {
        $this->translatingKey = $key;

        $sourceTranslations = $this->service->getTranslations('fr');
        $sourceText = $sourceTranslations[$key] ?? '';

        if (empty($sourceText)) {
            $this->dispatch('toast', message: 'Aucun texte source à traduire.', type: 'error');
            $this->translatingKey = '';

            return;
        }

        $aiService = app(AiService::class);
        $translated = $aiService->translateContent($sourceText, 'fr', $this->targetLocale);

        if (! empty($translated) && $translated !== $sourceText) {
            $this->service->setTranslation($this->targetLocale, $key, $translated);
            $this->dispatch('toast', message: 'Traduction IA appliquée.', type: 'success');
        } else {
            $this->dispatch('toast', message: 'La traduction automatique a échoué.', type: 'error');
        }

        $this->translatingKey = '';
    }

    public function autoTranslateAll(): void
    {
        $this->translating = true;

        $filtered = $this->filteredTranslations();
        $untranslated = array_filter($filtered, fn ($t) => $t['target'] === '');

        if (empty($untranslated)) {
            $this->dispatch('toast', message: 'Toutes les clés visibles sont déjà traduites.', type: 'info');
            $this->translating = false;

            return;
        }

        $aiService = app(AiService::class);
        $count = 0;

        foreach ($untranslated as $key => $translation) {
            if (empty($translation['source'])) {
                continue;
            }

            $translated = $aiService->translateContent($translation['source'], 'fr', $this->targetLocale);

            if (! empty($translated) && $translated !== $translation['source']) {
                $this->service->setTranslation($this->targetLocale, $key, $translated);
                $count++;
            }
        }

        $this->translating = false;
        $this->dispatch('toast', message: "{$count} traduction(s) automatique(s) appliquée(s).", type: 'success');
    }

    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingShowUntranslatedOnly(): void
    {
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    #[Computed]
    public function filteredTranslations(): array
    {
        $sourceTranslations = $this->service->getTranslations('fr');
        $targetTranslations = $this->service->getTranslations($this->targetLocale);

        $filtered = [];

        foreach ($sourceTranslations as $key => $sourceValue) {
            $targetValue = $targetTranslations[$key] ?? '';

            if ($this->showUntranslatedOnly && $targetValue !== '') {
                continue;
            }

            if ($this->search !== '') {
                $searchLower = mb_strtolower($this->search);
                $keyMatch = str_contains(mb_strtolower($key), $searchLower);
                $sourceMatch = str_contains(mb_strtolower($sourceValue), $searchLower);
                $targetMatch = str_contains(mb_strtolower($targetValue), $searchLower);

                if (! $keyMatch && ! $sourceMatch && ! $targetMatch) {
                    continue;
                }
            }

            $filtered[$key] = [
                'source' => $sourceValue,
                'target' => $targetValue,
            ];
        }

        return $filtered;
    }

    public function render()
    {
        $filtered = $this->filteredTranslations();
        $total = count($filtered);
        $offset = ($this->page - 1) * $this->perPage;
        $paginated = array_slice($filtered, $offset, $this->perPage, true);
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        $locales = $this->service->getLocales();
        $count = $this->service->getTranslationCount($this->targetLocale);

        return view('backoffice::livewire.translations-manager', [
            'translations' => $paginated,
            'locales' => $locales,
            'totalCount' => $count['total'],
            'translatedCount' => $count['translated'],
            'progressPercentage' => $count['total'] > 0 ? round(($count['translated'] / $count['total']) * 100) : 0,
            'currentPage' => $this->page,
            'lastPage' => $lastPage,
            'totalFiltered' => $total,
        ]);
    }
}
