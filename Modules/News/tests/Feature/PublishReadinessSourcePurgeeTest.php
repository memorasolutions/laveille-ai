<?php

declare(strict_types=1);

/**
 * publishReadinessCheck() et le texte source absent (2026-08-29).
 *
 * TROISIÈME appelant de la même règle, après news:apply (#1984) et
 * NewsCompositionController::storeProofPair(). Trois endroits qui avaient besoin de la même
 * précaution, trois oublis séparés : c'est ce qui justifie de verrouiller celui-ci par un test
 * plutôt que de se fier au fait que la règle est « connue ».
 *
 * Ce que la garde protège ici est plus lourd qu'ailleurs : publishReadinessCheck() commande la
 * PUBLICATION elle-même, par ses deux appelants (le bouton de l'écran d'administration et
 * news:apply --publish). Une fiche dont la source a été purgée après l'ajout de ses paires
 * deviendrait donc impubliable DÉFINITIVEMENT, puisque la source ne revient jamais.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function prspSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source prsp',
        'url' => 'https://exemple.com/prsp-'.uniqid(),
        'language' => 'fr',
        'active' => true,
    ]);
}

function prspArticle(string $sourceText, string $excerpt): NewsArticle
{
    $suffixe = uniqid();

    return NewsArticle::create([
        'news_source_id' => prspSource()->id,
        'title' => 'Titre prsp',
        // seo_title est un prérequis à part entière de publishReadinessCheck(), au même titre que
        // summary : sans lui, la méthode sort AVANT d'atteindre le contrôle des paires, et le test
        // ne mesurerait plus rien de ce qu'il prétend mesurer.
        'seo_title' => 'Titre pour Google, fiche prsp',
        'image_credit' => 'Photo : source de test',
        'guid' => 'guid-prsp-'.$suffixe,
        'url' => 'https://exemple.com/prsp-article-'.$suffixe,
        'description' => '',
        'summary' => 'Un résumé valide, non vide, pour passer les prérequis de base.',
        'slug' => 'prsp-'.$suffixe,
        'pub_date' => now(),
        'is_published' => false,
        'seo_status' => 'index',
        'internal_source_text' => $sourceText,
        'editorial_proof_pairs' => [
            ['type' => 'fact', 'statement' => 'Une affirmation.', 'excerpt' => $excerpt],
        ],
    ]);
}

// Le contrôle ne PEUT plus s'exécuter, ce n'est pas un échec de validation. Refuser ici
// condamnerait la fiche : la source purgée ne revient jamais.
it('laisse une fiche publiable quand le texte source a été purgé, plutôt que de la condamner', function () {
    $article = prspArticle('', 'un extrait qui ne peut plus etre controle');

    $verdict = $article->publishReadinessCheck();

    expect($verdict['ready'])->toBeTrue()
        ->and($verdict['invalid_pair'])->toBeNull();
});

// Le garde-fou ne doit PAS dégénérer en acceptation universelle : quand la source est là, la
// citation reste contrôlée, et une citation absente reste refusée.
it('refuse toujours une paire fact dont l\'extrait est absent d\'une source PRÉSENTE', function () {
    $article = prspArticle('Le texte source complet et bien présent, avec ses accents.', 'phrase totalement absente');

    $verdict = $article->publishReadinessCheck();

    expect($verdict['ready'])->toBeFalse()
        ->and($verdict['invalid_pair']['reason'])->toBe('fact_substring');
});
