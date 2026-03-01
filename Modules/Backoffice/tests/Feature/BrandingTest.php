<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Models\Setting;
use Tests\TestCase;

class BrandingTest extends TestCase
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

    public function test_branding_page_is_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.branding.edit'));

        $response->assertOk();
        $response->assertViewIs('backoffice::branding.edit');
    }

    public function test_branding_page_requires_authentication(): void
    {
        $response = $this->get(route('admin.branding.edit'));

        $response->assertRedirect();
    }

    public function test_branding_can_be_updated_with_text_fields(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Mon site test',
            'site_description' => 'Description test',
            'primary_color' => '#FF5733',
            'font_family' => 'Poppins',
            'font_url' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
            'footer_text' => '(c) {year} {app_name}',
            'footer_right' => 'v{version}',
            'login_title' => 'Bienvenue',
            'login_subtitle' => 'Connectez-vous',
        ]);

        $response->assertRedirect(route('admin.branding.edit'));
        $response->assertSessionHas('success');

        $this->assertEquals('Mon site test', Setting::get('site_name'));
        $this->assertEquals('#FF5733', Setting::get('branding.primary_color'));
        $this->assertEquals('Poppins', Setting::get('branding.font_family'));
    }

    public function test_branding_validates_primary_color_format(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Test',
            'primary_color' => 'not-a-color',
            'font_family' => 'Inter',
        ]);

        $response->assertSessionHasErrors('primary_color');
    }

    public function test_branding_can_upload_logo(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Test',
            'primary_color' => '#487FFF',
            'font_family' => 'Inter',
            'logo_light' => UploadedFile::fake()->image('logo.png', 200, 60),
        ]);

        $response->assertRedirect(route('admin.branding.edit'));

        $logoPath = Setting::get('branding.logo_light');
        $this->assertNotEmpty($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }

    public function test_branding_cache_is_cleared_on_update(): void
    {
        Cache::put('branding_settings', ['site_name' => 'Old'], 3600);

        $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'New Site',
            'primary_color' => '#487FFF',
            'font_family' => 'Inter',
        ]);

        $this->assertNull(Cache::get('branding_settings'));
    }

    public function test_branding_view_composer_injects_branding_variable(): void
    {
        Setting::set('site_name', 'Composé', 'string', 'general');
        Cache::forget('branding_settings');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('branding');
    }
}
