<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 138 (2026-07-30) : deux occurrences du même motif - l'interface promet une chose, le code
// en fait une autre.
//
// DÉFAUT A : la cible d'insertion restait collée au dernier champ visité.
// Dans prompt-anon-panel.js, la variable de closure `activeField` n'avait qu'UNE seule écriture
// dans tout le fichier : celle du bandeau garde-fou de données personnelles, qui envoie
// l'utilisateur anonymiser un champ AUTRE que la tâche (Exemples, Rôle, Audience, Contraintes...).
// Elle n'était JAMAIS remise à null. Conséquence : dès qu'un utilisateur empruntait ce chemin une
// seule fois dans sa session, TOUTES ses insertions suivantes atterrissaient dans ce champ périmé
// - y compris après avoir rouvert le panneau lui-même - pendant que le bouton affichait
// « Insérer dans la tâche » et que le toast confirmait « inséré dans la tâche ».
//
// DÉFAUT B : une promesse rendue fausse par un correctif antérieur, le mien.
// Le texte d'aide du gabarit de carte affirmait « Ce texte pré-remplira AUTOMATIQUEMENT votre
// demande quand vous cliquez sur cette carte ». Or le round 128 a rendu ce remplissage
// CONDITIONNEL : selectTask() ne remplit le champ que s'il est vide ou contient encore un gabarit
// connu intact ; sinon le texte de l'utilisateur est préservé et un simple avis s'affiche. Le
// comportement a changé, la phrase affichée n'a pas suivi.

// Retrait du 2026-08-04 (demande explicite de l'utilisateur, séparation constructeur/anonymiseur) :
// les 3 tests DÉFAUT A/B ci-dessus (« releases the insertion target », « names the field actually
// targeted », « exposes the new i18n key ») testaient prompt-anon-panel.js et la clé i18n
// anonInsertedInField, tous deux retirés avec le panneau d'anonymisation intégré. Les tests
// restants ci-dessous (logique de non-écrasement du gabarit, tests de non-régression généraux)
// ne dépendaient pas de cette intégration et restent valides tels quels.

// Round 2026-08-03 (restauration du wizard 4 étapes fidèle à mi-juin) : le test round 138
// « stops promising an automatic prefill » vérifiait le texte d'aide du gabarit de carte
// personnalisée (« Ce texte remplira votre demande si elle est encore vide »). Ce gabarit de
// carte n'existe plus dans le wizard restauré (l'UI de cartes personnalisées a été retirée sur
// demande explicite de l'utilisateur) - le texte qu'il protégeait n'a plus d'emplacement où
// vivre. La logique JS conditionnelle qu'il référençait (isUntouchedTemplate/_showTaskNotice)
// reste couverte par le test suivant, toujours valide.

it('keeps the conditional prefill logic that made the old wording false (round 138)', function () {
    // Si cette logique conditionnelle du round 128 disparaissait un jour, le nouveau libellé
    // deviendrait faux à son tour et devrait être réévalué. Ce test le rappelle.
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('isUntouchedTemplate');
    expect($js)->toContain('_showTaskNotice()');
});

it('has the new string translated and the stale one removed (round 138)', function () {
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    // Laravel indexe les traductions JSON par la chaîne SOURCE (française) : la clé est donc la
    // même des deux côtés, seule la valeur diffère.
    foreach (['Texte anonymisé inséré dans « %s ».', 'Ce texte remplira votre demande si elle est encore vide. Si vous avez déjà écrit quelque chose, rien ne sera écrasé.'] as $key) {
        expect($fr)->toHaveKey($key);
        expect($en)->toHaveKey($key);
    }

    // L'ancienne promesse ne doit plus traîner nulle part.
    $stale = 'Ce texte pré-remplira automatiquement votre demande quand vous cliquez sur cette carte.';
    expect($fr)->not->toHaveKey($stale);
    expect($en)->not->toHaveKey($stale);

    // Invariant du round 117 : toute clé française a son équivalent anglais.
    expect(array_diff_key($fr, $en))->toBeEmpty();
});

it('renders the wizard after the round 138 fix (real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $this->get('/outils/constructeur-prompts')->assertOk();
});
