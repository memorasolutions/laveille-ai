<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Models\AnalyticsCollectionRun;
use Modules\Analytics\Models\Ga4Daily;
use Modules\Analytics\Models\GscDaily;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_un_visiteur_anonyme_est_redirige_vers_la_connexion(): void
    {
        $this->get('/admin/mesure-contenu')->assertRedirect();
    }

    public function test_un_utilisateur_non_admin_recoit_403(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/mesure-contenu')->assertForbidden();
    }

    public function test_un_admin_voit_les_quatre_sections_a_l_etat_vide(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.mesure_contenu.dashboard'))
            ->assertOk()
            ->assertSee('Ce qui attire')
            ->assertSee('Ce qu&#039;on trouve sur Google', false)
            ->assertSee('Ce qui a été publié récemment')
            ->assertSee('Collectes')
            ->assertSee('data-empty="ga4"', false)
            ->assertSee('data-empty="gsc"', false)
            ->assertSee('data-empty="publications"', false)
            ->assertSee('data-empty="runs"', false);
    }

    public function test_les_agregats_sont_corrects_avec_des_donnees(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $old = now()->subDays(90)->toDateString();

        // A : 30 sessions à 60 s + 10 sessions à 120 s => (1800+1200)/40 = 75 s = 1 min 15 s
        Ga4Daily::create(['date' => $today, 'url' => 'https://x.test/a', 'sessions' => 30, 'active_users' => 20, 'screen_page_views' => 50, 'avg_engagement_time_seconds' => 60]);
        Ga4Daily::create(['date' => $yesterday, 'url' => 'https://x.test/a', 'sessions' => 10, 'active_users' => 8, 'screen_page_views' => 12, 'avg_engagement_time_seconds' => 120]);
        Ga4Daily::create(['date' => $today, 'url' => 'https://x.test/b', 'sessions' => 5, 'active_users' => 5, 'screen_page_views' => 5, 'avg_engagement_time_seconds' => 10]);
        Ga4Daily::create(['date' => $old, 'url' => 'https://x.test/hors-periode', 'sessions' => 999, 'active_users' => 1, 'screen_page_views' => 1, 'avg_engagement_time_seconds' => 1]);

        // G : 10 clics/100 impr. pos 2 + 30 clics/300 impr. pos 6 => CTR 10 %, position (200+1800)/400 = 5,0
        GscDaily::create(['date' => $today, 'url' => 'https://x.test/g', 'clicks' => 10, 'impressions' => 100, 'ctr' => 0.1, 'position' => 2]);
        GscDaily::create(['date' => $yesterday, 'url' => 'https://x.test/g', 'clicks' => 30, 'impressions' => 300, 'ctr' => 0.1, 'position' => 6]);

        AnalyticsCollectionRun::create(['source' => 'ga4', 'collected_for' => $yesterday, 'ran_at' => now(), 'status' => 'skipped', 'rows_upserted' => 0, 'message' => 'identifiants absents']);

        $r = $this->actingAs($this->admin())->get(route('admin.mesure_contenu.dashboard'));

        $r->assertOk()->assertDontSee('hors-periode');
        $r->assertSeeInOrder(['https://x.test/a', '40', '28', '62', '1 min 15 s', 'https://x.test/b']);
        $r->assertSeeInOrder(['https://x.test/g', '40', '400', '10,00 %', '5,0']);
        $r->assertSee('skipped')->assertSee('identifiants absents')->assertSee('GA4');
        $r->assertDontSee('data-empty="ga4"', false)->assertDontSee('data-empty="gsc"', false)->assertDontSee('data-empty="runs"', false);
    }

    public function test_la_periode_par_query_string_filtre_et_une_date_invalide_retombe_sur_le_defaut(): void
    {
        Ga4Daily::create(['date' => now()->subDays(90)->toDateString(), 'url' => 'https://x.test/ancienne', 'sessions' => 7, 'active_users' => 1, 'screen_page_views' => 1, 'avg_engagement_time_seconds' => 1]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.mesure_contenu.dashboard'))->assertDontSee('https://x.test/ancienne');
        $this->actingAs($admin)
            ->get(route('admin.mesure_contenu.dashboard', ['from' => now()->subDays(100)->toDateString(), 'to' => now()->subDays(80)->toDateString()]))
            ->assertSee('https://x.test/ancienne');
        $this->actingAs($admin)->get('/admin/mesure-contenu?from=nimporte&to=quoi')->assertOk();
    }
}
