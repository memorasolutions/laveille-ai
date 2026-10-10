<?php

declare(strict_types=1);

/**
 * HeaderNavService (ticket #3013) : arbre de navigation unique, gating des routes et des drapeaux.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Modules\FrontTheme\Services\HeaderNavService;

uses(Tests\TestCase::class);

beforeEach(function () {
    foreach (['directory_tools_count', 'dictionary_terms_count', 'acronyms_count'] as $cle) {
        cache()->put($cle, 42, 60);
    }
    $this->service = app(HeaderNavService::class);
});

function noeud(array $arbre, string $id): ?array
{
    return collect($arbre)->firstWhere('id', $id);
}

it("sert les entrées de 1er niveau dans l'ordre du site", function () {
    $ids = array_column($this->service->tree(), 'id');

    expect(array_slice($ids, 0, 4))->toBe(['home', 'outils', 'annuaire', 'apprendre']);
    expect($ids)->not->toContain('formations');
});

it('compte les entrées des quatre groupes Outils (plein) et du CTA', function () {
    config(['tools.quest.enabled' => true]);
    $outils = noeud($this->service->tree(), 'outils');
    $comptes = array_map(fn ($g) => count($g['items']), $outils['children']);

    // Productivité 4 (comparateur présent), Création 3, Détente 3, Pratique 3 (quête activée).
    expect($comptes)->toBe([4, 3, 3, 3]);
    expect($outils['cta']['label'])->toBe('Voir tous les outils gratuits');
});

it('retire la Quête narrative quand son drapeau est coupé, sans toucher au reste', function () {
    config(['tools.quest.enabled' => false]);
    $pratique = noeud($this->service->tree(), 'outils')['children'][3];

    expect(array_column($pratique['items'], 'label'))->toBe(['Calculatrice taxes QC', 'Simulateur fiscal Québec']);
});

it('respecte le gating du Classement (route ET drapeau)', function () {
    config(['directory.leaderboard.enabled' => false]);
    $labels = fn () => array_column(noeud($this->service->tree(), 'annuaire')['children'][1]['items'], 'label');

    expect($labels())->not->toContain('Classement');

    config(['directory.leaderboard.enabled' => true]);
    expect($labels())->toContain('Classement');
});

it("alimente l'Annuaire avec les fiches stars et le compteur en cache", function () {
    $annuaire = noeud($this->service->tree(), 'annuaire');

    expect(array_column($annuaire['children'][0]['items'], 'label'))
        ->toBe(['Poe', 'ChatGPT', 'Canva AI', 'Wooclap', 'Claude Design']);
    expect($annuaire['children'][1]['items'][0]['subtitle'])->toStartWith('42 ');
    expect($annuaire)->not->toHaveKey('cta');
});

it("sépare Apprendre en contenu éditorial et référence, sans CTA", function () {
    $apprendre = noeud($this->service->tree(), 'apprendre');

    expect(array_column($apprendre['children'], 'group'))->toBe(['Contenu éditorial', 'Référence']);
    expect($apprendre)->not->toHaveKey('cta');
});

it("masque Académie et Livres tant que leur porte est fermée", function () {
    config(['academy.under_construction' => true, 'books.under_construction' => true]);
    $ids = array_column($this->service->tree(), 'id');

    expect($ids)->not->toContain('academie')->not->toContain('livres');
});

it("expose le repli mobile trié par rang, sans entrée absente de ce repli", function () {
    config(['tools.quest.enabled' => true]);
    $mobile = HeaderNavService::surfaceItems(noeud($this->service->tree(), 'outils'), 'mobile');
    $labels = array_column($mobile, 'label');

    expect($labels[0])->toBe('Brain Dump 2026');
    expect($labels)->toContain('Mots croisés', 'Sudoku');
    // Code QR, Raccourcir un lien et Grilles partagées n'ont jamais figuré dans le repli mobile.
    expect($labels)->not->toContain('Code QR')->not->toContain('Grilles partagées');
});

it('fournit les liens légaux du pied de page', function () {
    $legal = $this->service->toApi()['footer']['legal'];

    expect($legal)->not->toBeEmpty();
    expect(array_keys($legal[0]))->toBe(['label', 'url']);
});
