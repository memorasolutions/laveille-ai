<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * LOT 5 (2026-09-26) - refonte de l'éditeur en assistant par ÉTAPES + aperçu collant (bureau
 * collant à droite, bande collante réduite + feuille plein écran sur mobile). Ces tests couvrent
 * la VUE rendue (stepper accessible, navigation, aperçu) - le moteur de rendu et la galerie sont
 * couverts respectivement par SignatureRenderTest.php et SignatureGalleryTest.php.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

function getSignatureAssistantHtml(): string
{
    return test()->actingAs(test()->superadmin)->get(route('signature.assistant'))->getContent();
}

test('le stepper porte les 5 étapes avec une sémantique ARIA tablist/tab complète', function (): void {
    $html = getSignatureAssistantHtml();

    expect($html)->toContain('role="tablist"')
        ->and($html)->toContain('class="ct-stepper mb-3"')
        ->and($html)->toContain("role=\"tab\"")
        ->and($html)->toContain('aria-selected')
        // Directive Alpine brute (calculée côté client, jamais littérale dans le HTML serveur).
        ->and($html)->toContain(':aria-current="step === s[0] ? \'step\' : null"')
        ->and($html)->toContain('aria-controls')
        // Les 5 libellés d'étape, dans l'ordre.
        ->and($html)->toContain('Mise en page')
        ->and($html)->toContain('Vos informations')
        ->and($html)->toContain('Images')
        ->and($html)->toContain('Style et liens')
        ->and($html)->toContain('Finaliser');
});

test('les 5 panneaux d\'étape existent, chacun avec un titre focusable programmatique', function (): void {
    $html = getSignatureAssistantHtml();

    foreach ([1, 2, 3, 4, 5] as $n) {
        expect($html)->toContain("id=\"sig-step-panel-{$n}\"")
            ->and($html)->toContain("id=\"sigStepHeading{$n}\"")
            ->and($html)->toContain("step === {$n}");
    }
});

test('la navigation d\'étape (goToStep/nextStep/prevStep) et la validation minimale sont câblées dans la vue', function (): void {
    $html = getSignatureAssistantHtml();

    expect($html)->toContain('goToStep(s[0])')
        ->and($html)->toContain('nextStep()')
        ->and($html)->toContain('prevStep()')
        ->and($html)->toContain('stepState(s[0])')
        ->and($html)->toContain('stepStateLabel(s[0])')
        ->and($html)->toContain('showStepValidation')
        // Jamais de popup native.
        ->and($html)->not->toMatch('/[^.]\balert\s*\(/')
        ->and($html)->not->toMatch('/[^.]\bconfirm\s*\(/')
        ->and($html)->not->toMatch('/[^.]\bprompt\s*\(/');
});

test('l\'aperçu collant bureau et la bande + feuille mobile sont tous deux présents et réutilisent previewSrcdoc', function (): void {
    $html = getSignatureAssistantHtml();

    // Bureau (≥lg), collant.
    expect($html)->toContain('sig-preview-sticky')
        ->and($html)->toContain('d-none d-lg-block')
        ->and($html)->toContain('sig-email-frame')
        // Mobile (<lg) : bande collante + poignée + bouton Agrandir + feuille plein écran.
        ->and($html)->toContain('sig-mobile-preview-bar')
        ->and($html)->toContain('mobilePreviewCollapsed')
        ->and($html)->toContain('openPreviewSheet()')
        ->and($html)->toContain('closePreviewSheet()')
        ->and($html)->toContain('mobilePreviewSheetOpen')
        ->and($html)->toContain('sig-preview-sheet-overlay')
        // Aucune logique de rendu dupliquée : les 3 emplacements (bureau, bande, feuille) lisent
        // tous le même `previewSrcdoc`.
        ->and(substr_count($html, ':srcdoc="previewSrcdoc"'))->toBe(3);
});

test('aucun champ d\'origine n\'a disparu dans la réorganisation en étapes', function (): void {
    $html = getSignatureAssistantHtml();

    // Identité (étape 2).
    $expectedIds = [
        'sig-first-name', 'sig-last-name', 'sig-job-title', 'sig-organization', 'sig-pronouns',
        'sig-email', 'sig-phone', 'sig-mobile', 'sig-website', 'sig-address', 'sig-tagline',
        // Images (étape 3).
        'sig-logo-file', 'sig-logo-range', 'sig-portrait-file', 'sig-portrait-range',
        'sig-portrait-shape', 'sig-banniere-file', 'sig-banniere-range',
        // Style et liens (étape 4).
        'sig-accent-color', 'sig-font-family', 'sig-font-scale', 'sig-cta-text', 'sig-cta-url',
        'sig-social-links-label', 'sig-mention-lines-label',
        // Finaliser (étape 5).
        'sig-html-source',
        // Mise en page (étape 1).
        'sig-template-label',
    ];
    foreach ($expectedIds as $id) {
        expect($html)->toContain("id=\"{$id}\"");
    }

    $expectedModels = [
        'content.first_name', 'content.last_name', 'content.job_title', 'content.organization',
        'content.pronouns', 'content.email', 'content.phone', 'content.mobile', 'content.website',
        'content.address', 'content.tagline', 'content.cta_text', 'content.cta_url',
        'content.accent_color', 'content.font_family', 'content.portrait_shape', 'content.font_scale',
    ];
    foreach ($expectedModels as $model) {
        expect($html)->toContain("x-model=\"{$model}\"");
    }
    // Les répéteurs liens sociaux/mentions ne sont pas des x-model directs (x-for sur le tableau) -
    // vérifiés autrement : présence du tableau ET des champs internes de chaque ligne.
    expect($html)->toContain('in content.social_links')
        ->and($html)->toContain('in content.mention_lines')
        ->and($html)->toContain('x-model="link.platform"')
        ->and($html)->toContain('x-model="link.url"')
        ->and($html)->toContain('x-model="content.mention_lines[index]"');

    // Actions de l'étape « Finaliser ».
    expect($html)->toContain('copyToClipboard()')
        ->and($html)->toContain('downloadHtm()')
        ->and($html)->toContain('openHtmlCode()')
        ->and($html)->toContain('@click="save()"')
        ->and($html)->toContain('openGallery()');
});
