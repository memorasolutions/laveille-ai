<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\ContactMessage;
use App\Models\User;
use Modules\Blog\Models\Article;
use Modules\Faq\Models\Faq;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app(TenantService::class)->clear();
});

afterEach(function () {
    app(TenantService::class)->clear();
});

test('article auto assigns tenant on create', function () {
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->switchTo($tenant);

    $user = User::factory()->create();
    $article = Article::create([
        'title' => 'Test Article',
        'slug' => 'test-article',
        'content' => 'Hello World',
        'user_id' => $user->id,
        'status' => 'published',
    ]);

    expect($article->tenant_id)->toBe($tenant->id);
});

test('article scoped to current tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create();

    app(TenantService::class)->switchTo($tenant1);
    Article::create([
        'title' => 'T1 Article',
        'slug' => 't1-article',
        'content' => 'Content 1',
        'user_id' => $user->id,
        'status' => 'published',
    ]);

    app(TenantService::class)->switchTo($tenant2);
    Article::create([
        'title' => 'T2 Article',
        'slug' => 't2-article',
        'content' => 'Content 2',
        'user_id' => $user->id,
        'status' => 'published',
    ]);

    app(TenantService::class)->switchTo($tenant1);

    expect(Article::count())->toBe(1)
        ->and(Article::first()->title)->toBe('T1 Article');
});

test('faq auto assigns tenant', function () {
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->switchTo($tenant);

    $faq = Faq::create([
        'question' => 'What is this?',
        'answer' => 'A test.',
        'order' => 1,
    ]);

    expect($faq->tenant_id)->toBe($tenant->id);
});

test('contact message tenant isolation', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    app(TenantService::class)->switchTo($tenant1);
    ContactMessage::create([
        'name' => 'John',
        'email' => 'john@example.com',
        'subject' => 'Support T1',
        'message' => 'Help T1',
    ]);

    app(TenantService::class)->switchTo($tenant2);
    ContactMessage::create([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'subject' => 'Support T2',
        'message' => 'Help T2',
    ]);

    app(TenantService::class)->switchTo($tenant1);
    expect(ContactMessage::count())->toBe(1)
        ->and(ContactMessage::first()->subject)->toBe('Support T1');

    app(TenantService::class)->switchTo($tenant2);
    expect(ContactMessage::count())->toBe(1)
        ->and(ContactMessage::first()->subject)->toBe('Support T2');
});

test('withoutTenancy shows all records', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    app(TenantService::class)->switchTo($tenant1);
    Faq::create(['question' => 'Q1', 'answer' => 'A1', 'order' => 1]);

    app(TenantService::class)->switchTo($tenant2);
    Faq::create(['question' => 'Q2', 'answer' => 'A2', 'order' => 2]);

    app(TenantService::class)->switchTo($tenant1);
    expect(Faq::count())->toBe(1);
    expect(Faq::withoutTenancy()->count())->toBe(2);
});

test('forTenant filters specific tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    app(TenantService::class)->switchTo($tenant1);
    Faq::create(['question' => 'Q1', 'answer' => 'A1', 'order' => 1]);

    app(TenantService::class)->switchTo($tenant2);
    Faq::create(['question' => 'Q2', 'answer' => 'A2', 'order' => 2]);

    $results = Faq::forTenant($tenant1)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->question)->toBe('Q1');
});

test('admin no tenant sees all', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    app(TenantService::class)->switchTo($tenant1);
    Faq::create(['question' => 'Q1', 'answer' => 'A1', 'order' => 1]);

    app(TenantService::class)->switchTo($tenant2);
    Faq::create(['question' => 'Q2', 'answer' => 'A2', 'order' => 2]);

    app(TenantService::class)->clear();
    expect(Faq::count())->toBe(2);
});

test('tenant id nullable for global content', function () {
    app(TenantService::class)->clear();

    $faq = Faq::create([
        'question' => 'Global Question?',
        'answer' => 'Global Answer',
        'order' => 1,
    ]);

    expect($faq->tenant_id)->toBeNull();
    expect(Faq::count())->toBe(1);

    $this->assertDatabaseHas('faqs', [
        'id' => $faq->id,
        'tenant_id' => null,
    ]);
});
