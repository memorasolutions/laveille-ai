<?php

declare(strict_types=1);

use Modules\Journal\Models\JournalBlock;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Faille trouvee le 2026-08-26 dans `Modules/Journal`, module jamais ouvert par les audits.
//
// La vue publique affichait `{!! $block->payload['html'] !!}` en BRUT. Or ce HTML est saisi par
// l'utilisateur (JournalBuilder:133), et surtout `JournalPolicy::view()` autorise la lecture a
// TOUT LE MONDE, visiteur anonyme compris, des que le journal est publie :
//
//     return $journal->isPublished() || ($user !== null && $user->id === $journal->user_id);
//
// La route `GET /journaux/{journal}` est d'ailleurs declaree HORS du groupe `auth`. N'importe quel
// inscrit pouvait donc publier un journal porteur de HTML malveillant, servi ensuite a tout
// visiteur. La policy est correcte : c'est le RENDU qui ne l'etait pas.
//
// Purification a l'affichage plutot qu'a l'ecriture : les blocs deja enregistres sont couverts
// eux aussi, sans migration ni reecriture de donnees existantes.

it('neutralise le HTML dangereux d un bloc de journal', function () {
    $bloc = new JournalBlock();
    $bloc->payload = ['html' => '<p>Texte legitime</p><img src=x onerror="document.title=1">'
        .'<script>document.title=2</script>'];

    $rendu = $bloc->safeHtml();

    expect($rendu)->toContain('Texte legitime');
    expect($rendu)->not->toContain('onerror');
    expect($rendu)->not->toContain('<script');
});

// Ce test a ete ELARGI apres la passe adversariale : il ne verifiait que <strong> et <em>, deux
// balises que meme le profil Purifier par defaut conserve. La regression etait donc invisible.
// L'editeur du journal (Tiptap) autorise les titres h2/h3 et les citations, et la vue porte du CSS
// dedie aux blockquote : ce sont CES balises qu'il faut verrouiller, pas les faciles.
it('conserve la mise en forme legitime, titres et citations compris', function () {
    $bloc = new JournalBlock();
    $bloc->payload = ['html' => '<h2>Un titre</h2><blockquote><p>Une citation</p></blockquote>'
        .'<ul><li>un item</li></ul><p>Un <strong>mot</strong> et un <em>autre</em>.</p>'];

    $rendu = $bloc->safeHtml();

    expect($rendu)->toContain('<h2>');
    expect($rendu)->toContain('blockquote');
    expect($rendu)->toContain('<ul>');
    expect($rendu)->toContain('<strong>');
    expect($rendu)->toContain('<em>');
});

it('supporte un bloc sans html', function () {
    $bloc = new JournalBlock();
    $bloc->payload = [];

    expect($bloc->safeHtml())->toBe('');
});

// Garde-fou structurel : c'est le rendu BRUT dans la vue qui constituait la faille.
it('interdit a la vue publique d afficher le html brut', function () {
    $vue = file_get_contents(base_path('Modules/Journal/resources/views/show.blade.php'));

    expect(str_contains($vue, "payload['html']"))->toBeFalse(
        'La vue publique doit passer par safeHtml(), jamais afficher payload[html] en brut.'
    );
});
