<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - le compteur de vues du glossaire (dictionary_terms.views_count),
 * mort depuis toujours faute de colonne (ViewCounterService restait un no-op
 * silencieux via Schema::hasColumn). La colonne existe désormais ; ce test
 * couvre le point qui compte vraiment : le tri anti-robot doit rester actif
 * sur CE module, exactement comme sur Tools/Authors/News après l'incident
 * 2026-08-13 (compteur des actualités : 1,1M cumulé contre 666 vues réelles,
 * précisément parce que les robots étaient comptés). Le tri anti-robot lui-
 * même vit dans Modules\Core\Services\ViewCounterService (déjà couvert par
 * Modules/Core/tests/Feature/ViewCounterServiceTest.php) - ce fichier prouve
 * uniquement le CÂBLAGE réel sur la route publique du glossaire.
 */

use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helper ────────────────────────────────────────────────────────────────────

/**
 * Construction directe (pas de TermFactory dans ce module - même convention que
 * PublicListCachePurgeOnPublishTest dans ce même dossier). Publié d'office :
 * ce test cible la route publique dictionary.show, qui exige published().
 */
function makeViewCounterDictionaryTerm(string $suffixe): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-vc-'.$suffixe.'-'.uniqid();

    return Term::create([
        'name' => [$locale => 'Terme compteur '.$suffixe, 'fr' => 'Terme compteur '.$suffixe],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ]);
}

// ── Preuve comportementale de bout en bout : la vraie route, le vrai contrôleur ──

it('incrémente le compteur de vues du glossaire pour un visiteur ordinaire', function () {
    $term = makeViewCounterDictionaryTerm('humain');

    $reponse = $this->get('/glossaire/'.$term->slug, [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0',
    ]);
    $reponse->assertOk();

    $term->refresh();
    expect($term->views_count)->toBe(1);
});

it('n\'incrémente rien pour un robot déclaré (Googlebot) - le point qui compte', function () {
    $term = makeViewCounterDictionaryTerm('robot');

    $reponse = $this->get('/glossaire/'.$term->slug, [
        'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    ]);
    $reponse->assertOk();

    $term->refresh();
    expect($term->views_count)->toBe(0);
});

// ── Partie 2 (docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md + complément
//      coordinateur 2026-09-11) : le glossaire affiche AUSSI une date au lecteur - elle ne doit
//      ni bouger sous l'effet d'une simple consultation, ni se présenter comme une révision
//      quand aucune trace éditoriale réelle n'existe.

it('consulter un terme du glossaire ne fait pas avancer sa date éditoriale', function () {
    $term = makeViewCounterDictionaryTerm('fraicheur');
    $term->refresh();
    $editorialAvant = $term->editorialModifiedAt()->toIso8601String();

    Carbon\Carbon::setTestNow(now()->addDays(3));
    $reponse = $this->get('/glossaire/'.$term->slug, [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0',
    ]);
    Carbon\Carbon::setTestNow();

    $reponse->assertOk();
    $term->refresh();

    expect($term->views_count)->toBe(1)
        ->and($term->editorialModifiedAt()->toIso8601String())->toBe($editorialAvant);
});

it('un terme jamais révisé n\'affiche AUCUNE date de révision (jamais la date de création présentée comme une révision)', function () {
    $term = makeViewCounterDictionaryTerm('sans-revision');

    // Fraîchement créé : content_updated_at = created_at, aucune révision connue.
    expect($term->hasKnownEditorialRevision())->toBeFalse();

    $reponse = $this->get('/glossaire/'.$term->slug, [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0',
    ]);
    // Mord si la garde disparaît : l'ancien code affichait TOUJOURS "Mis à jour le [date]",
    // même pour un terme jamais révisé (updated_at existe toujours) - présenter la date de
    // création comme une date de mise à jour est le mensonge que ce correctif retire.
    $reponse->assertOk()->assertDontSee('Révisé le')->assertDontSee('Mis à jour le');
});

it('un terme réellement révisé affiche sa date de révision, sous le libellé « Révisé le »', function () {
    $term = makeViewCounterDictionaryTerm('revise');

    Carbon\Carbon::setTestNow(now()->addDays(10));
    $term->definition = ['fr_CA' => 'Nouvelle définition, réellement modifiée.', 'fr' => 'Nouvelle définition, réellement modifiée.'];
    $term->save();
    Carbon\Carbon::setTestNow();

    $term->refresh();
    expect($term->hasKnownEditorialRevision())->toBeTrue();

    $reponse = $this->get('/glossaire/'.$term->slug, [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0',
    ]);
    $reponse->assertOk()
        ->assertSee('Révisé le')
        ->assertDontSee('Mis à jour le');
});
