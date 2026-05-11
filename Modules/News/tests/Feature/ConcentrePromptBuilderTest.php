<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\News\Services\ConcentrePromptBuilder;

uses(Tests\TestCase::class);

test('build refuse week_start non lundi', function () {
    $builder = new ConcentrePromptBuilder();
    $start = Carbon::parse('2026-05-06'); // mercredi
    $end = Carbon::parse('2026-05-12');

    expect(fn () => $builder->build($start, $end, [], []))
        ->toThrow(InvalidArgumentException::class, 'weekStart doit être un lundi.');
});

test('build refuse week_end non dimanche', function () {
    $builder = new ConcentrePromptBuilder();
    $start = Carbon::parse('2026-05-04'); // lundi
    $end = Carbon::parse('2026-05-09'); // samedi

    expect(fn () => $builder->build($start, $end, [], []))
        ->toThrow(InvalidArgumentException::class, 'weekEnd doit être un dimanche.');
});

test('build avec URLs manuelles uniquement préserve ordre', function () {
    $builder = new ConcentrePromptBuilder();
    $start = Carbon::parse('2026-05-04');
    $end = Carbon::parse('2026-05-10');

    $manualUrls = [
        'https://example.com/article-3',
        'https://example.com/article-1',
        'https://example.com/article-2',
    ];

    $prompt = $builder->build($start, $end, [], $manualUrls);

    expect($prompt)->toContain('semaine du 4 au 10 mai 2026');
    expect($prompt)->toContain('concentre-ia-semaine-4-10-mai-2026');

    $pos3 = strpos($prompt, 'article-3');
    $pos1 = strpos($prompt, 'article-1');
    $pos2 = strpos($prompt, 'article-2');

    expect($pos3)->toBeLessThan($pos1);
    expect($pos1)->toBeLessThan($pos2);
});

test('build ignore URLs manuelles invalides', function () {
    $builder = new ConcentrePromptBuilder();
    $start = Carbon::parse('2026-05-04');
    $end = Carbon::parse('2026-05-10');

    $manualUrls = [
        'https://valid.example.com/ok',
        'pas-une-url',
        '   ',
        'ftp://aussi-rejete.example.com', // FILTER_VALIDATE_URL accepte ftp en fait
    ];

    $prompt = $builder->build($start, $end, [], $manualUrls);

    expect($prompt)->toContain('https://valid.example.com/ok');
    expect($prompt)->not->toContain('pas-une-url');
});

test('build format période traverse deux mois', function () {
    $builder = new ConcentrePromptBuilder();
    $start = Carbon::parse('2026-04-27'); // lundi
    $end = Carbon::parse('2026-05-03'); // dimanche

    $prompt = $builder->build($start, $end, [], ['https://example.com/x']);

    expect($prompt)->toContain('27 avril au 3 mai 2026');
});
