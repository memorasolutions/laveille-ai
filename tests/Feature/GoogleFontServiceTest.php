<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\GoogleFontService;

beforeEach(function () {
    $this->service = new GoogleFontService;
    $this->fontDir = public_path('fonts/roboto');
});

afterEach(function () {
    if (File::exists($this->fontDir)) {
        File::deleteDirectory($this->fontDir);
    }
});

test('download returns local CSS path when successful', function () {
    $fakeCss = '@font-face { font-family: "Roboto"; src: url(https://fonts.gstatic.com/s/roboto/v30/abc123.woff2) format("woff2"); }';

    Http::fake([
        'fonts.googleapis.com/*' => Http::response($fakeCss, 200),
        'fonts.gstatic.com/*' => Http::response('fake-woff2-data', 200),
    ]);

    $result = $this->service->download('Roboto');

    expect($result)->toBe('/fonts/roboto/font.css')
        ->and(File::exists("{$this->fontDir}/font.css"))->toBeTrue()
        ->and(File::exists("{$this->fontDir}/abc123.woff2"))->toBeTrue()
        ->and(File::get("{$this->fontDir}/font.css"))->toContain('/fonts/roboto/abc123.woff2');
});

test('download returns empty string on HTTP failure', function () {
    Http::fake([
        'fonts.googleapis.com/*' => Http::response('Server Error', 500),
    ]);

    $result = $this->service->download('Roboto');

    expect($result)->toBe('');
});

test('isDownloaded returns false for non-existent font', function () {
    expect($this->service->isDownloaded('Roboto'))->toBeFalse();
});

test('isDownloaded returns true after download', function () {
    Http::fake([
        'fonts.googleapis.com/*' => Http::response('@font-face { src: url(https://fonts.gstatic.com/test.woff2); }', 200),
        'fonts.gstatic.com/*' => Http::response('data', 200),
    ]);

    $this->service->download('Roboto');

    expect($this->service->isDownloaded('Roboto'))->toBeTrue();
});

test('getLocalCssPath returns correct path', function () {
    expect($this->service->getLocalCssPath('Open Sans'))->toBe('/fonts/open-sans/font.css')
        ->and($this->service->getLocalCssPath('Roboto'))->toBe('/fonts/roboto/font.css');
});
