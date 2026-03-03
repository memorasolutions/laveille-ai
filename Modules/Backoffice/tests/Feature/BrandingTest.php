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

    public function test_branding_validates_all_color_formats(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Test Site',
            'primary_color' => '#6571ff',
            'font_family' => 'Inter',
            'secondary_color' => 'invalid',
        ]);

        $response->assertSessionHasErrors('secondary_color');
    }

    public function test_branding_saves_all_palette_colors(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Test Site',
            'primary_color' => '#6571ff',
            'font_family' => 'Inter',
            'secondary_color' => '#7987a1',
            'success_color' => '#05a34a',
            'warning_color' => '#fbbc06',
            'danger_color' => '#ff3366',
            'info_color' => '#66d1d1',
            'sidebar_bg' => '#0c1427',
            'header_bg' => '#ffffff',
            'body_bg' => '#ffffff',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('#7987a1', Setting::get('branding.secondary_color'));
        $this->assertEquals('#05a34a', Setting::get('branding.success_color'));
        $this->assertEquals('#0c1427', Setting::get('branding.sidebar_bg'));
    }

    public function test_branding_css_variables_are_injected_in_layout(): void
    {
        Setting::set('branding.secondary_color', '#aabbcc', 'string', 'branding');
        Cache::forget('branding_settings');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('--bs-secondary: #aabbcc', false);
    }

    public function test_branding_saves_topbar_typography_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Mon Site',
            'primary_color' => '#336699',
            'font_family' => 'Roboto',
            'topbar_font_family' => 'Montserrat',
            'topbar_font_size' => '1.5rem',
            'topbar_font_weight' => '500',
            'topbar_letter_spacing' => '0.5px',
            'topbar_word_spacing' => '1px',
            'topbar_text_transform' => 'uppercase',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('Montserrat', Setting::get('branding.topbar_font_family'));
        $this->assertEquals('1.5rem', Setting::get('branding.topbar_font_size'));
        $this->assertEquals('500', Setting::get('branding.topbar_font_weight'));
        $this->assertEquals('0.5px', Setting::get('branding.topbar_letter_spacing'));
        $this->assertEquals('1px', Setting::get('branding.topbar_word_spacing'));
        $this->assertEquals('uppercase', Setting::get('branding.topbar_text_transform'));
    }

    public function test_branding_validates_topbar_font_size_format(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.branding.update'), [
            'site_name' => 'Mon Site',
            'primary_color' => '#336699',
            'font_family' => 'Roboto',
            'topbar_font_size' => 'invalid',
        ]);

        $response->assertSessionHasErrors('topbar_font_size');
    }

    public function test_branding_topbar_css_variables_are_injected(): void
    {
        Setting::set('branding.topbar_font_family', 'Montserrat', 'string', 'branding');
        Setting::set('branding.topbar_font_size', '1.5rem', 'string', 'branding');
        Setting::set('branding.topbar_font_weight', '500', 'string', 'branding');
        Cache::forget('branding_settings');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('--topbar-font-family: Montserrat, sans-serif', false);
        $response->assertSee('--topbar-font-size: 1.5rem', false);
        $response->assertSee('--topbar-font-weight: 500', false);
    }
}
