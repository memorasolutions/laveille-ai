<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Editor\Models\Shortcode;
use Modules\Editor\Services\ShortcodeService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a shortcode', function () {
    $shortcode = Shortcode::factory()->create([
        'tag' => 'button',
        'name' => 'CTA Button',
        'html_template' => '<a href="{{ $url }}">{{ $content }}</a>',
        'is_active' => true,
    ]);

    expect($shortcode)->toBeInstanceOf(Shortcode::class)
        ->and($shortcode->tag)->toBe('button')
        ->and($shortcode->name)->toBe('CTA Button')
        ->and($shortcode->is_active)->toBeTrue();

    $this->assertDatabaseHas('shortcodes', ['tag' => 'button']);
});

it('scope active returns only active shortcodes', function () {
    Shortcode::factory()->count(3)->create(['is_active' => true]);
    Shortcode::factory()->count(2)->inactive()->create();

    $active = Shortcode::active()->get();

    expect($active)->toHaveCount(3);
    $active->each(fn ($s) => expect($s->is_active)->toBeTrue());
});

it('renders shortcode in content', function () {
    Shortcode::factory()->create([
        'tag' => 'highlight',
        'html_template' => '<mark>{{ $content }}</mark>',
        'is_active' => true,
    ]);

    $service = new ShortcodeService;
    $result = $service->render('Hello [highlight]world[/highlight]!');

    expect($result)->toBe('Hello <mark>world</mark>!');
});

it('renders shortcode with attributes', function () {
    Shortcode::factory()->create([
        'tag' => 'badge',
        'html_template' => '<span class="{{ $color }}">{{ $content }}</span>',
        'is_active' => true,
    ]);

    $service = new ShortcodeService;
    $result = $service->render('[badge color="red"]Important[/badge]');

    expect($result)->toBe('<span class="red">Important</span>');
});

it('ignores inactive shortcodes', function () {
    Shortcode::factory()->inactive()->create([
        'tag' => 'secret',
        'html_template' => '<b>{{ $content }}</b>',
    ]);

    $service = new ShortcodeService;
    $original = '[secret]hidden[/secret]';
    $result = $service->render($original);

    expect($result)->toBe($original);
});

it('passes through content without shortcodes', function () {
    $service = new ShortcodeService;
    $content = 'This is plain text with no shortcodes.';

    $result = $service->render($content);

    expect($result)->toBe($content);
});

it('handles self-closing shortcodes', function () {
    Shortcode::factory()->selfClosing()->create([
        'tag' => 'divider',
        'html_template' => '<hr class="{{ $style }}">',
    ]);

    $service = new ShortcodeService;
    $result = $service->render('Before [divider style="dashed"] After');

    expect($result)->toBe('Before <hr class="dashed"> After');
});

it('parses attributes correctly', function () {
    $service = new ShortcodeService;

    $attributes = $service->parseAttributes(' color="blue" size="large" label="Click me"');

    expect($attributes)->toBe([
        'color' => 'blue',
        'size' => 'large',
        'label' => 'Click me',
    ]);
});

it('returns empty array for empty attribute string', function () {
    $service = new ShortcodeService;

    $attributes = $service->parseAttributes('');

    expect($attributes)->toBe([]);
});

it('leaves unknown shortcode tags unchanged', function () {
    // No shortcodes seeded in DB
    $service = new ShortcodeService;
    $content = '[unknown]some text[/unknown]';

    $result = $service->render($content);

    expect($result)->toBe($content);
});

it('renders multiple shortcodes in the same content', function () {
    Shortcode::factory()->create([
        'tag' => 'bold',
        'html_template' => '<strong>{{ $content }}</strong>',
        'is_active' => true,
    ]);
    Shortcode::factory()->create([
        'tag' => 'italic',
        'html_template' => '<em>{{ $content }}</em>',
        'is_active' => true,
    ]);

    $service = new ShortcodeService;
    $result = $service->render('[bold]hello[/bold] and [italic]world[/italic]');

    expect($result)->toBe('<strong>hello</strong> and <em>world</em>');
});
