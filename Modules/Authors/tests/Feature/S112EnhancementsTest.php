<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Models\AuthorAffiliateLink;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorWebmention;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS112(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's112-'.strtolower(Str::random(6)),
        'display_name' => 'S112 Test',
        'tier' => 'free',
    ]);
}

it('AuthorAffiliateLink model fillable creates with valid attributes', function () {
    $author = makeAuthorS112();
    $link = AuthorAffiliateLink::create([
        'author_profile_id' => $author->id,
        'slug' => 'amazon-test',
        'destination_url' => 'https://amazon.com/dp/B0xxx',
        'label' => 'Test Amazon',
    ]);

    expect($link->slug)->toBe('amazon-test');
    expect($link->fresh()->clicks_count)->toBe(0);
});

it('affiliate /go/{slug} 302 redirects to destination + increments clicks', function () {
    $author = makeAuthorS112();
    $link = AuthorAffiliateLink::create([
        'author_profile_id' => $author->id,
        'slug' => 'go-test',
        'destination_url' => 'https://example.com/product',
    ]);

    $response = $this->get('/go/go-test');

    $response->assertStatus(302);
    $response->assertRedirect('https://example.com/product');

    $link->refresh();
    expect($link->clicks_count)->toBe(1);
});

it('affiliate /go/{slug} 404 on unknown slug', function () {
    $response = $this->get('/go/unknown-slug');
    $response->assertStatus(404);
});

it('AuthorTipsService isEnabled returns true when cashier secret configured', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $service = new \Modules\Authors\Services\AuthorTipsService();
    expect($service->isEnabled())->toBeTrue();
});

it('AuthorTipsService isEnabled returns false when secret missing', function () {
    config(['cashier.secret' => null]);
    $service = new \Modules\Authors\Services\AuthorTipsService();
    expect($service->isEnabled())->toBeFalse();
});

it('tip endpoint returns 503 when not configured', function () {
    config(['cashier.secret' => null]);
    $author = makeAuthorS112();
    $response = $this->post('/auteur/'.$author->slug.'/tip', ['amount_cents' => 500]);
    expect($response->status())->toBe(503);
});

it('tip endpoint rejects invalid amount under 100 cents', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS112();
    $response = $this->post('/auteur/'.$author->slug.'/tip', ['amount_cents' => 50]);
    expect($response->status())->toBe(422);
});

it('tip endpoint rejects invalid amount over 50000 cents', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS112();
    $response = $this->post('/auteur/'.$author->slug.'/tip', ['amount_cents' => 100000]);
    expect($response->status())->toBe(422);
});

it('post page displays webmentions section with verified mentions', function () {
    $author = makeAuthorS112();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'webm-display',
        'title' => 'Webmention Display Test',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    AuthorWebmention::create([
        'author_post_id' => $post->id,
        'target_url' => 'x',
        'source_url' => 'https://mastodon.social/@user/123',
        'source_author_name' => 'Alice Mastodon',
        'source_excerpt' => 'Great article!',
        'type' => 'mention',
        'received_at' => now(),
        'verified_at' => now(),
    ]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertSee('Mentions du web', false);
    $response->assertSee('Alice Mastodon', false);
});

it('Livewire AuthorSearch finds posts by title LIKE', function () {
    $author = makeAuthorS112();

    AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'searchable-x',
        'title' => 'Mon article unique XYZ',
        'body_markdown' => 'Body',
        'body_html' => '<p>Body</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $component = Livewire::test(\Modules\Authors\Livewire\AuthorSearch::class, ['authorProfileId' => $author->id])
        ->set('query', 'unique XYZ');

    $results = $component->viewData('results');
    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Mon article unique XYZ');
});

it('Livewire AuthorSearch returns empty results for query < 2 chars', function () {
    $author = makeAuthorS112();
    AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'test-x',
        'title' => 'Test',
        'body_markdown' => 'Body',
        'body_html' => '<p>Body</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $component = Livewire::test(\Modules\Authors\Livewire\AuthorSearch::class, ['authorProfileId' => $author->id])
        ->set('query', 'a');

    $results = $component->viewData('results');
    expect($results)->toHaveCount(0);
});
