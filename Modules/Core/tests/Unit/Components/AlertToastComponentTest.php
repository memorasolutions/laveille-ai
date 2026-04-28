<?php

declare(strict_types=1);

test('alert-toast blade component file exists', function () {
    expect(file_exists(dirname(__DIR__, 3) . '/resources/views/components/alert-toast.blade.php'))->toBeTrue();
});

test('alert-toast contains aria role status and alert', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/alert-toast.blade.php');
    expect($content)->toContain("'status'")
                    ->toContain("'alert'")
                    ->toContain('aria-live');
});

test('alert-toast supports 4 positions', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/alert-toast.blade.php');
    expect($content)->toContain('top-right')
                    ->toContain('top-left')
                    ->toContain('bottom-right')
                    ->toContain('bottom-left');
});

test('alert-toast supports 4 variants WCAG AAA colors', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/alert-toast.blade.php');
    expect($content)->toContain('#065F46')
                    ->toContain('#991B1B')
                    ->toContain('#92400E')
                    ->toContain('#1E3A8A');
});

test('alert-toast listens to toast-show window event', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/alert-toast.blade.php');
    expect($content)->toContain("'toast-show'");
});

test('alert-toast respects maxStack FIFO', function () {
    $content = file_get_contents(dirname(__DIR__, 3) . '/resources/views/components/alert-toast.blade.php');
    expect($content)->toContain('toasts.shift()');
});
