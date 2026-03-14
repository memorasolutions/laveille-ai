<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Referral;
use Modules\SaaS\Services\ReferralService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->referrer = User::factory()->create();
    $this->referred = User::factory()->create();
    $this->service = new ReferralService;
});

it('generates a referral code for a user', function () {
    $referral = $this->service->generateCode($this->referrer);

    expect($referral)->toBeInstanceOf(Referral::class)
        ->and($referral->referrer_id)->toBe($this->referrer->id)
        ->and($referral->code)->toHaveLength(8)
        ->and($referral->status)->toBe('pending');
});

it('generates unique codes', function () {
    $r1 = $this->service->generateCode($this->referrer);
    $r2 = $this->service->generateCode($this->referrer);

    expect($r1->code)->not->toBe($r2->code);
});

it('tracks a referral when user signs up with valid code', function () {
    $referral = $this->service->generateCode($this->referrer);

    $tracked = $this->service->track($referral->code, $this->referred);

    expect($tracked)->not->toBeNull()
        ->and($tracked->referred_id)->toBe($this->referred->id)
        ->and($tracked->status)->toBe('converted')
        ->and($tracked->converted_at)->not->toBeNull();
});

it('returns null for invalid referral code', function () {
    expect($this->service->track('INVALID1', $this->referred))->toBeNull();
});

it('returns null for already converted referral', function () {
    $referral = $this->service->generateCode($this->referrer);
    $this->service->track($referral->code, $this->referred);

    $anotherUser = User::factory()->create();
    expect($this->service->track($referral->code, $anotherUser))->toBeNull();
});

it('rewards a converted referral', function () {
    $referral = $this->service->generateCode($this->referrer);
    $this->service->track($referral->code, $this->referred);

    $rewarded = $this->service->reward($referral->fresh(), 'credit', 10.00);

    expect($rewarded->status)->toBe('rewarded')
        ->and($rewarded->reward_type)->toBe('credit')
        ->and((float) $rewarded->reward_value)->toBe(10.00)
        ->and($rewarded->rewarded_at)->not->toBeNull();
});

it('returns correct stats for a user', function () {
    // Create 2 pending
    $this->service->generateCode($this->referrer);
    $this->service->generateCode($this->referrer);

    // Create 1 converted
    $r3 = $this->service->generateCode($this->referrer);
    $this->service->track($r3->code, $this->referred);

    $stats = $this->service->getStats($this->referrer);

    expect($stats['total_referrals'])->toBe(3)
        ->and($stats['pending'])->toBe(2)
        ->and($stats['converted'])->toBe(1);
});

it('gets user referrals with referred user loaded', function () {
    $referral = $this->service->generateCode($this->referrer);
    $this->service->track($referral->code, $this->referred);

    $referrals = $this->service->getUserReferrals($this->referrer);

    expect($referrals)->toHaveCount(1)
        ->and($referrals->first()->referred)->not->toBeNull()
        ->and($referrals->first()->referred->id)->toBe($this->referred->id);
});

it('expires old pending referrals', function () {
    // Old pending - forceFill car created_at n'est pas dans $fillable
    $old = Referral::forceCreate([
        'referrer_id' => $this->referrer->id,
        'code' => 'OLD12345',
        'status' => 'pending',
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ]);

    // Recent pending
    $this->service->generateCode($this->referrer);

    $expired = $this->service->expireOldReferrals(30);

    expect($expired)->toBe(1)
        ->and(Referral::where('code', 'OLD12345')->first()->status)->toBe('expired');
});
