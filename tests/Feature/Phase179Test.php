<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

test('admin can view import plans form', function () {
    $response = $this->get(route('admin.import.plans'));
    $response->assertOk();
});

test('admin can import plans CSV', function () {
    $csv = "name,price,interval,features\nPro Plan,29.99,monthly,Feature A\nBasic Plan,9.99,yearly,Feature B";
    $file = UploadedFile::fake()->createWithContent('plans.csv', $csv);
    $response = $this->post(route('admin.import.plans.store'), ['file' => $file]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Plan::count())->toBe(2);
    expect(Plan::where('slug', 'pro-plan')->exists())->toBeTrue();
    expect(Plan::where('slug', 'basic-plan')->exists())->toBeTrue();
});

test('plans import skips duplicate slugs', function () {
    Plan::factory()->create(['name' => 'Existing Plan', 'slug' => 'existing-plan']);
    $csv = "name,price,interval,features\nExisting Plan,19.99,monthly,Feature X\nNew Plan,15.99,monthly,Feature Y";
    $file = UploadedFile::fake()->createWithContent('plans.csv', $csv);
    $this->post(route('admin.import.plans.store'), ['file' => $file]);
    expect(Plan::count())->toBe(2);
    expect(Plan::where('slug', 'new-plan')->exists())->toBeTrue();
});

test('plans import defaults interval to monthly for invalid value', function () {
    $csv = "name,price,interval,features\nTest Plan,49.99,invalid,Feature Z";
    $file = UploadedFile::fake()->createWithContent('plans.csv', $csv);
    $this->post(route('admin.import.plans.store'), ['file' => $file]);
    expect(Plan::where('slug', 'test-plan')->first()->interval)->toBe('monthly');
});

test('admin can view import pages form', function () {
    $response = $this->get(route('admin.import.pages'));
    $response->assertOk();
});

test('admin can import pages CSV', function () {
    $csv = "title,content,status\nAbout Us,Welcome content,published\nContact,Contact info,draft";
    $file = UploadedFile::fake()->createWithContent('pages.csv', $csv);
    $response = $this->post(route('admin.import.pages.store'), ['file' => $file]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(StaticPage::count())->toBe(2);
    $locale = app()->getLocale();
    expect(StaticPage::where("slug->{$locale}", 'about-us')->first()->status)->toBe('published');
});

test('pages import defaults status to draft', function () {
    $csv = "title,content,status\nDefault Page,Some content,badstatus";
    $file = UploadedFile::fake()->createWithContent('pages.csv', $csv);
    $this->post(route('admin.import.pages.store'), ['file' => $file]);
    $locale = app()->getLocale();
    expect(StaticPage::where("slug->{$locale}", 'default-page')->first()->status)->toBe('draft');
});

test('admin can view import comments form', function () {
    $response = $this->get(route('admin.import.comments'));
    $response->assertOk();
});

test('admin can import comments CSV', function () {
    $article = Article::factory()->create();
    $csv = "article_id,guest_name,guest_email,content\n{$article->id},John Doe,john@example.com,Great article!\n{$article->id},Jane Smith,jane@example.com,Nice read.";
    $file = UploadedFile::fake()->createWithContent('comments.csv', $csv);
    $response = $this->post(route('admin.import.comments.store'), ['file' => $file]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Comment::count())->toBe(2);
});

test('comments import skips invalid article_id', function () {
    $article = Article::factory()->create();
    $csv = "article_id,guest_name,guest_email,content\n999999,John Doe,john@example.com,Invalid article\n{$article->id},Jane Smith,jane@example.com,Valid comment";
    $file = UploadedFile::fake()->createWithContent('comments.csv', $csv);
    $this->post(route('admin.import.comments.store'), ['file' => $file]);
    expect(Comment::count())->toBe(1);
    expect(Comment::first()->guest_email)->toBe('jane@example.com');
});

test('non-admin cannot import plans', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $csv = "name,price,interval,features\nPro Plan,29.99,monthly,Feature A";
    $file = UploadedFile::fake()->createWithContent('plans.csv', $csv);
    $response = $this->actingAs($user)->post(route('admin.import.plans.store'), ['file' => $file]);
    $response->assertForbidden();
});

test('template download works for plans type', function () {
    $response = $this->get(route('admin.import.template', 'plans'));
    $response->assertDownload('plans_template.csv');
});

test('template download works for pages type', function () {
    $response = $this->get(route('admin.import.template', 'pages'));
    $response->assertDownload('pages_template.csv');
});

test('template download works for comments type', function () {
    $response = $this->get(route('admin.import.template', 'comments'));
    $response->assertDownload('comments_template.csv');
});

test('template redirects for invalid type', function () {
    $response = $this->get(route('admin.import.template', 'invalid'));
    $response->assertRedirect();
});
