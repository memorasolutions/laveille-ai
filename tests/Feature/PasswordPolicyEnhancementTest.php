<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Rules\PasswordHistoryRule;
use Modules\Auth\Rules\PasswordNotCompromisedRule;
use Modules\Settings\Facades\Settings;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function ruleError(object $rule, string $value): ?string
{
    $error = null;
    $rule->validate('password', $value, function (string $message) use (&$error) {
        $error = $message;
    });

    return $error;
}

// --- PasswordHistoryRule ---

it('blocks reused password via history rule', function () {
    $user = User::factory()->create();
    $hash = Hash::make('OldPass1!');

    DB::table('password_histories')->insert([
        'user_id' => $user->id,
        'password_hash' => $hash,
        'created_at' => now(),
    ]);

    $error = ruleError(new PasswordHistoryRule($user->id), 'OldPass1!');

    expect($error)->not->toBeNull()
        ->and($error)->toContain('mot de passe');
});

it('allows a new password not in history', function () {
    $user = User::factory()->create();

    DB::table('password_histories')->insert([
        'user_id' => $user->id,
        'password_hash' => Hash::make('OldPass1!'),
        'created_at' => now(),
    ]);

    $error = ruleError(new PasswordHistoryRule($user->id), 'BrandNewPass9!');

    expect($error)->toBeNull();
});

it('skips history check for null user id', function () {
    $error = ruleError(new PasswordHistoryRule(null), 'AnyPassword1!');

    expect($error)->toBeNull();
});

it('respects password history count setting', function () {
    Settings::shouldReceive('get')
        ->with('security.password_history_count', 5)
        ->andReturn(2)
        ->twice();

    $user = User::factory()->create();

    // Clear observer-inserted entries
    DB::table('password_histories')->where('user_id', $user->id)->delete();

    // Insert 3 controlled hashes
    DB::table('password_histories')->insert([
        ['user_id' => $user->id, 'password_hash' => Hash::make('Recent1!'), 'created_at' => now()],
        ['user_id' => $user->id, 'password_hash' => Hash::make('Recent2!'), 'created_at' => now()->subMinutes(5)],
        ['user_id' => $user->id, 'password_hash' => Hash::make('Old3!'), 'created_at' => now()->subHour()],
    ]);

    // 3rd oldest should pass (outside the 2 limit)
    expect(ruleError(new PasswordHistoryRule($user->id), 'Old3!'))->toBeNull();

    // Recent should still be blocked
    expect(ruleError(new PasswordHistoryRule($user->id), 'Recent1!'))->not->toBeNull();
});

// --- PasswordNotCompromisedRule ---

it('skips hibp check when disabled', function () {
    Settings::shouldReceive('get')
        ->with('security.password_check_hibp', false)
        ->andReturn(false);

    $error = ruleError(new PasswordNotCompromisedRule, 'AnyPass1!');

    expect($error)->toBeNull();
});

it('detects compromised password via hibp', function () {
    Settings::shouldReceive('get')
        ->with('security.password_check_hibp', false)
        ->andReturn(true);

    // SHA1("password") = 5BAA61E4C9B93F3F0682250B6CF8331B7EE68FD8
    // prefix=5BAA6, suffix=1E4C9B93F3F0682250B6CF8331B7EE68FD8
    Http::fake([
        'https://api.pwnedpasswords.com/range/5BAA6' => Http::response(
            "1D2DA4053E34E76F6576ED1CDE1677A2E67:2\n1E4C9B93F3F0682250B6CF8331B7EE68FD8:3861493\nFFF983A91443AE72BD98E59ADAB93B31974:2",
            200,
        ),
    ]);

    $error = ruleError(new PasswordNotCompromisedRule, 'password');

    expect($error)->not->toBeNull()
        ->and($error)->toContain('compromis');
});

it('fails open when hibp api errors', function () {
    Settings::shouldReceive('get')
        ->with('security.password_check_hibp', false)
        ->andReturn(true);

    Http::fake([
        'https://api.pwnedpasswords.com/range/*' => Http::response('', 500),
    ]);

    $error = ruleError(new PasswordNotCompromisedRule, 'SomePass1!');

    expect($error)->toBeNull();
});

// --- Observer integration ---

it('records password history when user password changes', function () {
    $user = User::factory()->create(['password' => 'InitialPass1!']);
    $originalHash = $user->fresh()->getAttributes()['password'];

    $user->password = Hash::make('NewSecurePass2!');
    $user->save();

    $history = DB::table('password_histories')
        ->where('user_id', $user->id)
        ->orderByDesc('created_at')
        ->get();

    // Should have at least the old hash (created + updated events)
    expect($history)->not->toBeEmpty();

    $hashes = $history->pluck('password_hash')->toArray();
    expect($hashes)->toContain($originalHash);
});

it('updates password_changed_at when password changes', function () {
    $user = User::factory()->create(['password' => 'InitialPass1!']);

    expect($user->fresh()->password_changed_at)->toBeNull();

    $user->password = Hash::make('ChangedPass3!');
    $user->save();

    $updatedAt = DB::table('users')->where('id', $user->id)->value('password_changed_at');

    expect($updatedAt)->not->toBeNull();
});

// --- Settings seeder ---

it('has new security settings in database after seeding', function () {
    $this->artisan('db:seed', ['--class' => 'Modules\\Settings\\Database\\Seeders\\SettingsDatabaseSeeder']);

    $hibp = DB::table('settings')->where('key', 'security.password_check_hibp')->first();
    $history = DB::table('settings')->where('key', 'security.password_history_count')->first();

    expect($hibp)->not->toBeNull()
        ->and($hibp->value)->toBe('false')
        ->and($hibp->type)->toBe('boolean')
        ->and($history)->not->toBeNull()
        ->and($history->value)->toBe('5')
        ->and($history->type)->toBe('number');
});
