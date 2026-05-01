<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

dataset('auditable_models', [
    'Article' => fn () => \Modules\Blog\Models\Article::class,
    'Category (Blog)' => fn () => \Modules\Blog\Models\Category::class,
    'StaticPage' => fn () => \Modules\Pages\Models\StaticPage::class,
    'Campaign' => fn () => \Modules\Newsletter\Models\Campaign::class,
    'Subscriber' => fn () => \Modules\Newsletter\Models\Subscriber::class,
    'Plan' => fn () => \Modules\SaaS\Models\Plan::class,
    'Appointment' => fn () => \Modules\Booking\Models\Appointment::class,
    'BookingService' => fn () => \Modules\Booking\Models\BookingService::class,
    'Ticket' => fn () => \Modules\AI\Models\Ticket::class,
    'Team' => fn () => \Modules\Team\Models\Team::class,
    'Faq' => fn () => \Modules\Faq\Models\Faq::class,
    'EmailTemplate' => fn () => \Modules\Notifications\Models\EmailTemplate::class,
    'Setting' => fn () => \Modules\Settings\Models\Setting::class,
    'User' => fn () => \App\Models\User::class,
]);

test('model has LogsActivity trait and returns LogOptions', function (string $class) {
    $model = new $class;

    expect(in_array(LogsActivity::class, class_uses_recursive($model)))->toBeTrue()
        ->and(method_exists($model, 'getActivitylogOptions'))->toBeTrue()
        ->and($model->getActivitylogOptions())->toBeInstanceOf(LogOptions::class);
})->with('auditable_models');
