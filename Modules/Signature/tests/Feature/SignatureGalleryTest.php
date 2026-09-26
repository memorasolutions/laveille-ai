<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * LOT 4 (2026-09-26) - galerie de mises en page dans l'éditeur (bouton d'ouverture, modale du thème
 * avec onglets ARIA par catégorie et grille de vignettes). Ces tests couvrent la VUE rendue - le
 * moteur de rendu et le registre sont couverts par SignatureRenderTest.php.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignatureTemplateRegistry;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->superadmin->assignRole('super_admin');
    Tool::updateOrCreate(['slug' => 'signature-courriel'], [
        'name' => 'Signature de courriel', 'description' => 'x',
        'is_active' => true, 'is_under_construction' => false,
    ]);
});

test('la page de l\'éditeur porte le bouton d\'ouverture de la galerie et la modale (dialog ARIA)', function (): void {
    $html = $this->actingAs($this->superadmin)->get(route('signature.assistant'))->getContent();

    expect($html)->toContain('openGallery()')
        ->and($html)->toContain('Voir toutes les mises en page')
        ->and($html)->toContain('showGalleryModal')
        ->and($html)->toContain('role="dialog"')
        ->and($html)->toContain('sigGalleryModalTitle');
});

test('la modale de la galerie porte une sémantique ARIA tablist/tab/tabpanel complète', function (): void {
    $html = $this->actingAs($this->superadmin)->get(route('signature.assistant'))->getContent();

    expect($html)->toContain('role="tablist"')
        ->and($html)->toContain('role="tab"')
        ->and($html)->toContain('role="tabpanel"')
        ->and($html)->toContain('aria-selected')
        ->and($html)->toContain('galleryCategoriesWithAll()')
        ->and($html)->toContain('galleryMoveTab(1)')
        ->and($html)->toContain('galleryMoveTab(-1)');
});

test('la modale de la galerie réutilise le moteur JS registre-driven pour ses vignettes (aucune logique de rendu dupliquée)', function (): void {
    $html = $this->actingAs($this->superadmin)->get(route('signature.assistant'))->getContent();

    expect($html)->toContain('galleryThumbSrcdoc(tpl)')
        ->and($html)->toContain('selectGalleryTemplate(tpl)')
        ->and($html)->toContain('applyGalleryTemplate()')
        ->and($html)->toContain('Utiliser cette mise en page')
        // Jamais de popup native.
        ->and($html)->not->toMatch('/[^.]\balert\s*\(/')
        ->and($html)->not->toMatch('/[^.]\bconfirm\s*\(/')
        ->and($html)->not->toMatch('/[^.]\bprompt\s*\(/');
});

test('window.SIGNATURE_TEMPLATES sérialise category et hint pour les 14 gabarits - même registre que le moteur de rendu', function (): void {
    $html = $this->actingAs($this->superadmin)->get(route('signature.assistant'))->getContent();

    preg_match('/window\.SIGNATURE_TEMPLATES\s*=\s*(\{.*?\});/s', $html, $matches);
    expect($matches)->toHaveCount(2);

    $decoded = json_decode($matches[1], true);
    expect($decoded)->toBeArray()
        ->and($decoded)->toHaveCount(14)
        ->and(array_keys($decoded))->toBe(Signature::templates())
        ->and(array_keys($decoded))->toBe(SignatureTemplateRegistry::templates());

    foreach ($decoded as $template => $def) {
        expect($def['category'] ?? null)->not->toBeNull()
            ->and($def['hint'] ?? null)->not->toBeNull();
    }
});
