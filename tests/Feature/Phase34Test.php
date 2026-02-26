<?php

declare(strict_types=1);

test('tiptap packages are installed', function () {
    $pkg = json_decode(file_get_contents(base_path('package.json')), true);
    $all = array_merge($pkg['dependencies'] ?? [], $pkg['devDependencies'] ?? []);
    expect($all)->toHaveKey('@tiptap/core')
        ->and($all)->toHaveKey('@tiptap/starter-kit')
        ->and($all)->toHaveKey('@tiptap/extension-image')
        ->and($all)->toHaveKey('@tiptap/extension-link')
        ->and($all)->toHaveKey('@tiptap/extension-table');
});

test('tiptap editor js file exists', function () {
    expect(file_exists(resource_path('js/tiptap-editor.js')))->toBeTrue();
});

test('tiptap blade component exists', function () {
    expect(file_exists(module_path('Editor', 'resources/views/components/tiptap.blade.php')))->toBeTrue();
});

test('editor module is enabled', function () {
    $statuses = json_decode(file_get_contents(base_path('modules_statuses.json')), true);
    expect($statuses['Editor'])->toBeTrue();
});
