<?php

declare(strict_types=1);

test('confirm-modal blade component file exists', function () {
    expect(file_exists(dirname(__DIR__, 3) . '/resources/views/components/confirm-modal.blade.php'))->toBeTrue();
});

test('confirm-modal contains required ARIA attributes', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/confirm-modal.blade.php');
    expect($content)->toContain('role="dialog"')
                    ->toContain('aria-modal="true"')
                    ->toContain('aria-labelledby');
});

test('confirm-modal uses CSS grid centered overlay class', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/confirm-modal.blade.php');
    expect($content)->toContain('.ct-confirm-overlay')
                    ->toContain('display: grid')
                    ->toContain('place-items: center');
});

test('confirm-modal supports 3 variants danger warning info', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/confirm-modal.blade.php');
    expect($content)->toContain('ct-confirm-btn-confirm-danger')
                    ->toContain('ct-confirm-btn-confirm-warning')
                    ->toContain('ct-confirm-btn-confirm-info');
});

test('confirm-modal listens to open-confirm-{name} window event', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/confirm-modal.blade.php');
    expect($content)->toContain('open-confirm-')
                    ->toContain('.window=');
});

test('confirm-modal supports escape key close', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/confirm-modal.blade.php');
    expect($content)->toContain('keydown.escape.window');
});
