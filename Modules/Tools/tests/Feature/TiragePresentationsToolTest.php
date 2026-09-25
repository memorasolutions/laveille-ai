<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Le tirage lui-meme (secureRandom, selection sans doublon) est 100 % client-side (Alpine,
 * localStorage) : rien de testable cote serveur sur la LOGIQUE de tirage. Ce que CE test couvre,
 * cote serveur, c'est que les 4 nouvelles capacites (demande fondateur 2026-09-25) sont bien
 * PRESENTES dans le HTML rendu - meme principe que MinuteurVisuelToolTest/AnonymiseurToolTest
 * (assertions sur des marqueurs DOM stables), puisque la vue est generee par Blade cote serveur.
 *
 * Verification VISUELLE complementaire (hors portee de ce test automatise) : ouvrir
 * /outils/tirage-presentations en local, coller des apprenants SANS aucune question et verifier
 * que « Tirer au sort » reste actif (cas 1) ; coller 4-5 questions, en decocher deux dans le bloc
 * « Questions a utiliser dans le tirage » et verifier qu'elles ne sortent jamais (cas 2) ; cliquer
 * « Vider » sous Apprenants et verifier que Questions/sujets reste intact, puis l'inverse (cas 3) ;
 * regler « Questions par apprenant » a 3 et verifier qu'un tirage assigne 3 questions distinctes,
 * jamais deux fois la meme, a l'apprenant tire (cas 4).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'tirage-presentations'], [
        'name' => 'Tirage de présentations',
        'description' => "Tirez au sort l'ordre de vos présentations, avec ou sans question. Choisissez le nombre de questions par apprenant, sélectionnez celles à utiliser, et réinitialisez chaque liste séparément.",
        'answer_summary' => "Un outil gratuit et 100 % local pour tirer au sort l'ordre de passage d'apprenants, avec ou sans question associée, et en choisissant combien de questions attribuer à chacun.",
        'answer_points' => [
            'Fonctionne aussi sans aucune question, pour tirer au sort seulement les apprenants',
            'Sélectionnez un sous-ensemble des questions saisies à utiliser dans le tirage',
            'Choisissez le nombre de questions différentes attribuées à chaque apprenant, sans doublon',
            'Videz la liste des apprenants ou celle des questions indépendamment, sans tout perdre',
            '100 % gratuit et local, sans compte ni installation',
        ],
        'icon' => '🎤',
        'sort_order' => 4,
        'is_active' => true,
        'is_under_construction' => false,
    ]);
});

it('renders tirage-presentations tool page with the 4 new flexibility controls', function () {
    Tool::where('slug', 'tirage-presentations')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/tirage-presentations');

    $response->assertStatus(200);
    $response->assertSee('Tirage de présentations', escape: false);

    // Cas 3 : listes decouplees, chacune son propre bouton de remise a zero.
    $response->assertSee('id="tp-names"', escape: false);
    $response->assertSee('id="tp-questions"', escape: false);
    $response->assertSee('clearNames()', escape: false);
    $response->assertSee('clearQuestions()', escape: false);

    // Cas 1 : la liste de questions est maintenant explicitement optionnelle.
    $response->assertSee('Questions / sujets (optionnel)', escape: false);

    // Cas 2 : selection d'un sous-ensemble des questions saisies.
    $response->assertSee('Questions à utiliser dans le tirage', escape: false);
    $response->assertSee('selectAllQuestions()', escape: false);
    $response->assertSee('selectNoQuestions()', escape: false);
    $response->assertSee('toggleQuestionIncluded(q)', escape: false);

    // Cas 4 : nombre de questions par apprenant, parametrable.
    $response->assertSee('id="tp-qpd"', escape: false);
    $response->assertSee('questionsPerDraw', escape: false);

    // La boite-reponse AEO/GEO reflete desormais la description a jour.
    $response->assertSee('lv-answer-box', escape: false);
    $response->assertSee('sans aucune question, pour tirer au sort seulement', escape: false);
});

it('serves the updated tirage-presentations description in the tools public index', function () {
    Tool::where('slug', 'tirage-presentations')->update(['is_under_construction' => false, 'is_active' => true]);

    $response = $this->get('/outils');

    $response->assertStatus(200);
    $response->assertSee('avec ou sans question', escape: false);
});
