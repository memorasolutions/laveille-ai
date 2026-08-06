<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

/*
 * Note : Mail::fake() ne capture PAS Mail::raw (no-op, piège deja documente dans
 * Modules/FrontTheme/tests/Feature/ContactSpamTest.php). On utilise donc le transport
 * « array » (MAIL_MAILER=array dans phpunit.xml) et on inspecte les messages captures.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Privacy\Models\RightsRequest;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake([MessageSent::class]);
    Mail::mailer('array')->getSymfonyTransport()->flush();
});

function sentRemindSubjects(): array
{
    $subjects = [];
    foreach (Mail::mailer('array')->getSymfonyTransport()->messages() as $sent) {
        $subjects[] = $sent->getOriginalMessage()->getSubject();
    }

    return $subjects;
}

test('reminder command runs without error when no request is overdue', function () {
    $exitCode = Artisan::call('privacy:remind-overdue-requests');

    expect($exitCode)->toBe(0);
    expect(sentRemindSubjects())->toBeEmpty();
});

test('reminder command sends one summary email and marks requests as reminded', function () {
    $overdue = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->addDays(4),
    ]);
    DB::table('rights_requests')->where('id', $overdue->id)->update(['created_at' => now()->subDays(26)]);

    // Demande recente : ne doit pas declencher de rappel.
    $recent = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->addDays(28),
    ]);
    DB::table('rights_requests')->where('id', $recent->id)->update(['created_at' => now()->subDays(2)]);

    // Demande deja completee, ancienne : ne doit pas declencher de rappel.
    $completed = RightsRequest::factory()->create([
        'status' => 'completed',
        'deadline_at' => now()->subDays(5),
    ]);
    DB::table('rights_requests')->where('id', $completed->id)->update(['created_at' => now()->subDays(28)]);

    $exitCode = Artisan::call('privacy:remind-overdue-requests');

    expect($exitCode)->toBe(0);

    $subjects = sentRemindSubjects();
    expect($subjects)->toHaveCount(1);
    expect($subjects[0])->toContain('1 demande');

    $overdue->refresh();
    expect($overdue->reminded_at)->not->toBeNull();

    $recent->refresh();
    expect($recent->reminded_at)->toBeNull();

    $completed->refresh();
    expect($completed->reminded_at)->toBeNull();
});

test('reminder command dry-run does not send email or mark requests', function () {
    $overdue = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->addDays(4),
    ]);
    DB::table('rights_requests')->where('id', $overdue->id)->update(['created_at' => now()->subDays(26)]);

    Artisan::call('privacy:remind-overdue-requests', ['--dry-run' => true]);

    expect(sentRemindSubjects())->toBeEmpty();
    $overdue->refresh();
    expect($overdue->reminded_at)->toBeNull();
});

test('reminder command does not re-notify an already reminded request', function () {
    $overdue = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->addDays(4),
        'reminded_at' => now()->subDay(),
    ]);
    DB::table('rights_requests')->where('id', $overdue->id)->update(['created_at' => now()->subDays(26)]);

    Artisan::call('privacy:remind-overdue-requests');

    expect(sentRemindSubjects())->toBeEmpty();
});
