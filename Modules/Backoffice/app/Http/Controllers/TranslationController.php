<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Modules\Translation\Services\TranslationService;

class TranslationController
{
    public function __construct(private readonly TranslationService $service) {}

    public function index()
    {
        return view('backoffice::translations.index');
    }

    public function export(string $locale)
    {
        $translations = $this->service->getTranslations($locale);
        $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $locale.'.json', ['Content-Type' => 'application/json']);
    }
}
