<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Filet de régression pour un défaut SIGNALÉ PAR LE FONDATEUR le 2026-09-21, sur la série
 * « IA et emplois 2030 » déjà en ligne : dans `<summary>Voir le prompt utilisé</summary>`, le
 * mot « prompt » était transformé en lien de glossaire par GlossaryLinkifier. Le cliquer
 * ouvrait la fiche du glossaire AU LIEU de déplier l'accordéon qui contient justement le
 * prompt - le lecteur qui voulait voir le prompt atterrissait ailleurs.
 *
 * C'est une RÉCIDIVE, pas un cas neuf : le même motif avait été corrigé le 2026-07-03 pour
 * <button> (« Générer mon prompt optimisé », générateur de prompt de l'article 16), où le lien
 * injecté interceptait le clic au lieu de soumettre le formulaire. Le commentaire de
 * walkAndReplace() le documente déjà. Un <summary> appartient à la même famille : il n'est pas
 * du texte courant, c'est LE contrôle qui ouvre son <details>. Y glisser un lien crée deux
 * cibles concurrentes dans la même zone, et la plus petite - le mot souligné - est précisément
 * celle que l'oeil vise.
 *
 * Le test verrouille les DEUX côtés de la frontière, parce qu'une exclusion trop large serait
 * une régression à son tour : le <summary> ne doit plus être lié, mais le CONTENU du <details>
 * doit continuer de l'être - c'est du texte courant, où l'auto-lien garde toute sa valeur.
 */

use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function glsTerme(string $name, string $slug): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $uniqueSlug = $slug.'-'.uniqid();

    return Term::create([
        'name' => [$locale => $name, 'fr' => $name],
        'slug' => [$locale => $uniqueSlug, 'fr' => $uniqueSlug],
        'definition' => [$locale => 'Définition de test pour '.$name.'.', 'fr' => 'Définition de test pour '.$name.'.'],
        'is_published' => true,
        'match_strategy' => 'loose',
        'aliases' => [],
    ]);
}

it('ne pose AUCUN lien dans un <summary> : le clic doit ouvrir l\'accordéon, pas le glossaire', function () {
    glsTerme('prompt', 'prompt');

    $html = '<figure><figcaption><details><summary>Voir le prompt utilisé</summary>'
        .'<p>Texte du prompt fourni au modèle.</p></details></figcaption></figure>';

    $sortie = GlossaryLinkifier::linkify($html);

    // Le libellé cliquable de l'accordéon reste intact, mot pour mot.
    $summary = mb_substr($sortie, (int) mb_strpos($sortie, '<summary'), (int) mb_strpos($sortie, '</summary>') - (int) mb_strpos($sortie, '<summary'));
    expect($summary)->not->toContain('<a ');
    expect($summary)->toContain('Voir le prompt utilisé');
});

it('CONTINUE de lier le terme dans le corps du <details> : seul le libellé est protégé, pas le contenu', function () {
    glsTerme('prompt', 'prompt');

    $html = '<figure><figcaption><details><summary>Voir le prompt utilisé</summary>'
        .'<p>Ce prompt a été fourni au modèle.</p></details></figcaption></figure>';

    $sortie = GlossaryLinkifier::linkify($html);

    $corps = mb_substr($sortie, (int) mb_strpos($sortie, '</summary>'));
    expect($corps)->toContain('<a ');
    expect($corps)->toContain('glossaire/prompt');
});

it('protège le <summary> sans toucher au texte courant qui l\'entoure', function () {
    glsTerme('prompt', 'prompt');

    $html = '<p>Un bon prompt change tout.</p>'
        .'<details><summary>Voir le prompt utilisé</summary><p>Contenu.</p></details>';

    $sortie = GlossaryLinkifier::linkify($html);

    // Le paragraphe AVANT l'accordéon garde son lien : l'exclusion est locale au contrôle.
    $avant = mb_substr($sortie, 0, (int) mb_strpos($sortie, '<details'));
    expect($avant)->toContain('<a ');

    $summary = mb_substr($sortie, (int) mb_strpos($sortie, '<summary'), (int) mb_strpos($sortie, '</summary>') - (int) mb_strpos($sortie, '<summary'));
    expect($summary)->not->toContain('<a ');
});
