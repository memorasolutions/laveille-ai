<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Core\Traits\HasMeta;
use Modules\Core\Traits\HasUuid;

// Test HasUuid trait
test('HasUuid generates uuid on creating', function () {
    $model = new class extends Model
    {
        use HasUuid;

        protected $table = 'test';

        public $uuid;
    };

    // Simulate the creating event
    $model->uuid = null;
    $model->uuid = (string) Str::uuid();

    expect($model->uuid)->not->toBeNull();
    expect(Str::isUuid($model->uuid))->toBeTrue();
});

test('HasUuid uses uuid as route key', function () {
    $model = new class extends Model
    {
        use HasUuid;

        protected $table = 'test';
    };

    expect($model->getRouteKeyName())->toBe('uuid');
});

// Test HasMeta trait
test('HasMeta can get and set meta values', function () {
    $model = new class extends Model
    {
        use HasMeta;

        protected $table = 'test';

        protected $casts = ['meta' => 'array'];

        public $meta = [];
    };

    $model->setMeta('color', 'blue');
    expect($model->getMeta('color'))->toBe('blue');
    expect($model->getMeta('nonexistent', 'default'))->toBe('default');

    $model->removeMeta('color');
    expect($model->getMeta('color'))->toBeNull();
});
