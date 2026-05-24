<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

it('verifies module Authors is properly scaffolded', function () {
    expect(base_path('Modules/Authors/module.json'))->toBeFile();
    expect(base_path('Modules/Authors/composer.json'))->toBeFile();
});

it('verifies all 13 services files exist', function () {
    $services = [
        'ImagePipelineService',
        'ArticlePromptBuilderService',
        'ImagePromptBuilderService',
        'CurationInboxService',
        'ModerationPipelineService',
        'AuthorShortUrlService',
        'AuthorExportService',
        'MigrationImportService',
        'AnalyticsRecommendationService',
        'CrossPromotionService',
        'StripeTipsService',
        'TierManagementService',
        'QrCodeService',
        'GoogleDriveEmbedService',
    ];

    foreach ($services as $service) {
        expect(base_path("Modules/Authors/app/Services/{$service}.php"))->toBeFile();
    }
});

it('verifies BrevoProvider newsletter adapter exists', function () {
    expect(base_path('Modules/Authors/app/Services/Newsletter/BrevoProvider.php'))->toBeFile();
    expect(base_path('Modules/Authors/app/Contracts/NewsletterProvider.php'))->toBeFile();
});

it('verifies all 6 models exist', function () {
    foreach (['AuthorProfile', 'CurationItem', 'ImageVariant', 'ModerationLog', 'AuthorActivityLog', 'AuthorStatus'] as $model) {
        expect(base_path("Modules/Authors/app/Models/{$model}.php"))->toBeFile();
    }
});

it('verifies 2 mailables exist', function () {
    expect(base_path('Modules/Authors/app/Mail/ModerationAlertMail.php'))->toBeFile();
    expect(base_path('Modules/Authors/app/Mail/AuthorReactivationMail.php'))->toBeFile();
});

it('verifies 2 jobs exist', function () {
    expect(base_path('Modules/Authors/app/Jobs/ScanArticleJob.php'))->toBeFile();
    expect(base_path('Modules/Authors/app/Jobs/AuthorActivityCheckJob.php'))->toBeFile();
});

it('verifies 7 migrations exist', function () {
    $migrations = glob(base_path('Modules/Authors/database/migrations/*.php')) ?: [];
    expect(count($migrations))->toBeGreaterThanOrEqual(7);
});

it('verifies bookmarklet JS file exists', function () {
    expect(base_path('Modules/Authors/resources/assets/js/bookmarklet.js'))->toBeFile();
});

it('verifies 3 Blade components exist', function () {
    expect(base_path('Modules/Authors/resources/views/components/distribution-buttons.blade.php'))->toBeFile();
    expect(base_path('Modules/Authors/resources/views/components/public-share-buttons.blade.php'))->toBeFile();
    expect(base_path('Modules/Authors/resources/views/components/laveille-ad-banner.blade.php'))->toBeFile();
});

it('verifies MiniSiteController exists', function () {
    expect(base_path('Modules/Authors/app/Http/Controllers/MiniSiteController.php'))->toBeFile();
});

it('verifies module Authors key exists in modules_statuses', function () {
    $statuses = json_decode((string) file_get_contents(base_path('modules_statuses.json')), true);
    expect($statuses)->toHaveKey('Authors');
    expect($statuses['Authors'])->toBeBool();
});
