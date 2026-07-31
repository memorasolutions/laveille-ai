<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 141 (2026-07-30, passe adversariale) : chaînes du constructeur de prompts affichées en
// FRANÇAIS BRUT aux visiteurs anglophones.
//
// Cause racine. Laravel indexe les traductions JSON par la chaîne SOURCE française : `lang/fr.json`
// n'a donc pas besoin d'entrée, mais `lang/en.json` en a impérativement une, sinon `__()` retombe
// sur la clé, c'est-à-dire le texte français. Sur 284 appels `__()` de cette vue, 280 avaient leur
// traduction anglaise ; 4 avaient été oubliées au fil des rounds précédents.
//
// Ce que la personne voyait. Avec l'interface en anglais (le middleware app/Http/Middleware/
// SetLocale.php permet bien de basculer), 4 éléments restaient en français au milieu de l'anglais :
// le message de suppression d'une carte objectif, celui d'ouverture dans Mistral, la note « texte
// conservé » sous le champ de tâche, et surtout le nom du champ « Exemples » à l'intérieur du
// bandeau d'alerte anti-données-personnelles - une phrase de sécurité à moitié traduite.
//
// Pourquoi un test plutôt qu'un simple correctif : le défaut est INVISIBLE en français, la locale
// de travail quotidienne. Sans garde-fou, il revient au prochain libellé ajouté.
//
// Note : `array_diff_key(fr, en)` ne détecte PAS ce cas (les clés ne sont dans NI l'un NI l'autre).
// C'est la comparaison « valeur anglaise différente de la source française » qui le révèle.

it('translates every prompt-builder string that is surfaced to English visitors', function () {
    $en = json_decode(file_get_contents(base_path('lang/en.json')), true);

    expect($en)->toBeArray();

    // Les 4 chaînes trouvées au round 141, plus le libellé dynamique du bouton d'insertion ajouté
    // par ce même round (il aurait sinon reproduit exactement le même défaut).
    $chainesSources = [
        'Votre texte a été conservé. Effacez-le si vous voulez repartir du gabarit de cette carte.',
        'Prompt copié : colle-le dans Mistral (Ctrl/Cmd + V).',
        'Objectif supprimé',
        "Exemples pour guider l'IA",
        'Insérer dans « %s »',
    ];

    foreach ($chainesSources as $source) {
        expect(array_key_exists($source, $en))->toBeTrue(
            "Aucune traduction anglaise pour « {$source} » : un visiteur anglophone lira le français."
        );

        // Une entrée qui recopie la source française ne traduit rien : le défaut serait intact.
        expect($en[$source] !== $source)->toBeTrue(
            "La valeur anglaise de « {$source} » est identique au français : ce n'est pas une traduction."
        );
    }
});

it('keeps the French source strings and the English file in sync (round 117 invariant)', function () {
    $fr = json_decode(file_get_contents(base_path('lang/fr.json')), true);
    $en = json_decode(file_get_contents(base_path('lang/en.json')), true);

    expect(array_diff_key($fr, $en))->toBeEmpty();
});

it('renders the page after the round 141 fixes (real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $reponse = $this->get('/outils/constructeur-prompts');
    $reponse->assertOk();

    // Le libellé du bouton doit être dans un élément identifiable, sinon le JS du round 141 ne peut
    // pas le mettre à jour et le bouton retombe silencieusement sur sa promesse figée.
    $reponse->assertSee('id="cpAnonInsertLabel"', false);
});
