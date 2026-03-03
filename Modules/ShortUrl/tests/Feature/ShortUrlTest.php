<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ShortUrl\Models\ShortUrl;
use Tests\TestCase;

class ShortUrlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_short_url_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.short-urls.index'));
        $response->assertRedirect();
    }

    public function test_short_url_index_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.short-urls.index'));
        $response->assertOk();
    }

    public function test_short_url_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.short-urls.store'), [
            'original_url' => 'https://example.com',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('short_urls', ['original_url' => 'https://example.com']);
    }

    public function test_short_url_validates_original_url(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.short-urls.store'), [
            'original_url' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors('original_url');
    }

    public function test_short_url_validates_slug_format(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.short-urls.store'), [
            'original_url' => 'https://example.com',
            'slug' => 'invalid slug!',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_short_url_slug_auto_generated_if_empty(): void
    {
        $this->actingAs($this->admin)->post(route('admin.short-urls.store'), [
            'original_url' => 'https://example.com/auto-slug',
        ]);

        $shortUrl = ShortUrl::where('original_url', 'https://example.com/auto-slug')->first();
        $this->assertNotNull($shortUrl);
        $this->assertNotEmpty($shortUrl->slug);
        $this->assertEquals(6, strlen($shortUrl->slug));
    }

    public function test_short_url_can_be_updated(): void
    {
        $shortUrl = ShortUrl::create([
            'user_id' => $this->admin->id,
            'original_url' => 'https://old-url.com',
            'slug' => 'oldslug',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.short-urls.update', $shortUrl), [
            'original_url' => 'https://new-url.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('short_urls', [
            'id' => $shortUrl->id,
            'original_url' => 'https://new-url.com',
        ]);
    }

    public function test_short_url_can_be_deleted(): void
    {
        $shortUrl = ShortUrl::create([
            'user_id' => $this->admin->id,
            'original_url' => 'https://example.com',
            'slug' => 'todelete',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.short-urls.destroy', $shortUrl));

        $response->assertRedirect();
        $this->assertSoftDeleted('short_urls', ['id' => $shortUrl->id]);
    }

    public function test_short_url_toggle_active(): void
    {
        $shortUrl = ShortUrl::create([
            'user_id' => $this->admin->id,
            'original_url' => 'https://example.com',
            'slug' => 'toggle1',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.short-urls.toggle', $shortUrl));

        $shortUrl->refresh();
        $this->assertFalse($shortUrl->is_active);
    }

    public function test_short_url_redirect_works(): void
    {
        ShortUrl::create([
            'user_id' => $this->admin->id,
            'original_url' => 'https://example.com',
            'slug' => 'testgo',
            'is_active' => true,
        ]);

        $response = $this->get('/s/testgo');
        $response->assertRedirect('https://example.com');
    }

    public function test_short_url_redirect_expired_returns_410(): void
    {
        ShortUrl::create([
            'user_id' => $this->admin->id,
            'original_url' => 'https://example.com',
            'slug' => 'expired1',
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get('/s/expired1');
        $response->assertStatus(410);
    }

    public function test_short_url_click_is_tracked(): void
    {
        $shortUrl = ShortUrl::create([
            'user_id' => $this->admin->id,
            'original_url' => 'https://example.com',
            'slug' => 'track1',
            'is_active' => true,
            'clicks_count' => 0,
        ]);

        $this->get('/s/track1');

        $shortUrl->refresh();
        $this->assertEquals(1, $shortUrl->clicks_count);
        $this->assertDatabaseHas('short_url_clicks', ['short_url_id' => $shortUrl->id]);
    }
}
