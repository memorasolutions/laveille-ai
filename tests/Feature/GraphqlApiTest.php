<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Faq\Models\Faq;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;
use Modules\Testimonials\Models\Testimonial;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

uses(RefreshDatabase::class, MakesGraphQLRequests::class);

// ============================================================
// PHASE 3A - Endpoint tests
// ============================================================

test('graphql endpoint is reachable', function () {
    $response = $this->graphQL('{ __typename }');

    $response->assertSuccessful();
    $response->assertJson(['data' => ['__typename' => 'Query']]);
});

test('graphql rejects malformed query', function () {
    $response = $this->postJson('/graphql', [
        'query' => '{ articles { id title }',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['errors']);
});

test('graphql requires no auth for public queries', function () {
    $response = $this->graphQL('{ plans { id name } }');

    $response->assertSuccessful();
    $response->assertJsonStructure(['data' => ['plans']]);
});

// ============================================================
// PHASE 3B - Public queries
// ============================================================

test('can query articles with pagination', function () {
    Article::factory()->count(3)->published()->for(User::factory(), 'user')->create();

    $response = $this->graphQL('
        {
            articles(first: 2) {
                data { id title slug }
                paginatorInfo { total count currentPage }
            }
        }
    ');

    $response->assertJsonCount(2, 'data.articles.data');
    $response->assertJsonPath('data.articles.paginatorInfo.total', 3);
});

test('can query article by slug', function () {
    $article = Article::factory()->published()->for(User::factory(), 'user')->create();

    $response = $this->graphQL('
        query($slug: String!) {
            article(slug: $slug) { id title slug }
        }
    ', ['slug' => $article->slug]);

    $response->assertJsonPath('data.article.title', $article->title);
    $response->assertJsonPath('data.article.slug', $article->slug);
});

test('draft articles are not returned', function () {
    Article::factory()->draft()->for(User::factory(), 'user')->create();

    $response = $this->graphQL('{ articles { data { id } } }');

    $response->assertJsonCount(0, 'data.articles.data');
});

test('can query pages', function () {
    StaticPage::factory()->count(2)->published()->for(User::factory(), 'user')->create();

    $response = $this->graphQL('{ pages { id title slug } }');

    $response->assertJsonCount(2, 'data.pages');
});

test('can query page by slug', function () {
    $page = StaticPage::factory()->published()->for(User::factory(), 'user')->create();

    $response = $this->graphQL('
        query($slug: String!) {
            page(slug: $slug) { id title slug }
        }
    ', ['slug' => $page->slug]);

    $response->assertJsonPath('data.page.title', $page->title);
});

test('can query faqs', function () {
    Faq::create(['question' => 'Q1?', 'answer' => 'A1', 'category' => 'general', 'order' => 1, 'is_published' => true]);
    Faq::create(['question' => 'Q2?', 'answer' => 'A2', 'category' => 'general', 'order' => 2, 'is_published' => true]);
    Faq::create(['question' => 'Q3?', 'answer' => 'A3', 'category' => 'tech', 'order' => 3, 'is_published' => true]);

    $response = $this->graphQL('{ faqs { id question answer } }');

    $response->assertJsonCount(3, 'data.faqs');
});

test('can filter faqs by category', function () {
    Faq::create(['question' => 'Q1?', 'answer' => 'A1', 'category' => 'general', 'order' => 1, 'is_published' => true]);
    Faq::create(['question' => 'Q2?', 'answer' => 'A2', 'category' => 'tech', 'order' => 2, 'is_published' => true]);

    $response = $this->graphQL('
        query($cat: String) {
            faqs(category: $cat) { id question }
        }
    ', ['cat' => 'tech']);

    $response->assertJsonCount(1, 'data.faqs');
});

test('can query plans', function () {
    Plan::factory()->count(2)->create(['is_active' => true]);

    $response = $this->graphQL('{ plans { id name price currency interval } }');

    $response->assertJsonCount(2, 'data.plans');
});

test('can query testimonials', function () {
    Testimonial::create(['author_name' => 'John', 'content' => 'Great!', 'rating' => 5, 'is_approved' => true, 'order' => 1]);
    Testimonial::create(['author_name' => 'Jane', 'content' => 'Awesome!', 'rating' => 4, 'is_approved' => true, 'order' => 2]);

    $response = $this->graphQL('{ testimonials { id author_name content rating } }');

    $response->assertJsonCount(2, 'data.testimonials');
});

test('can query categories', function () {
    Category::factory()->count(2)->create(['is_active' => true]);

    $response = $this->graphQL('{ categories { id name slug } }');

    $response->assertJsonCount(2, 'data.categories');
});

test('me query returns null without auth', function () {
    $response = $this->graphQL('{ me { id name } }');

    $response->assertJson(['data' => ['me' => null]]);
});

test('me query returns user when authenticated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('{ me { id name } }');

    $response->assertJsonPath('data.me.id', (string) $user->id);
    $response->assertJsonPath('data.me.name', $user->name);
});
