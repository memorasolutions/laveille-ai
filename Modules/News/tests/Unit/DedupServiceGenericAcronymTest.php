<?php

declare(strict_types=1);

/**
 * Garde-fou du 2026-08-13.
 *
 * Sur un site de veille en intelligence artificielle, « AI » et « IA » figurent dans une grande
 * part des titres. Ils étaient pourtant absents des mots vides du calcul de Jaccard, ET comptés
 * comme entités nommées distinctives : leur présence dans la liste des acronymes connus leur
 * faisait contourner à la fois le minimum de 4 caractères et le filtre des mots vides. Mesuré
 * sur 1 365 paires réelles, ils étaient le principal contributeur de rapprochements entre
 * articles sans aucun rapport.
 *
 * Ce test verrouille les trois acquis : les deux acronymes ne sont plus des entités, ils ne
 * gonflent plus la similarité, et les acronymes réellement distinctifs (GPT, RAG, GPU) restent
 * comptés. Les listes vivent dans Modules/News/config/fusion.php, jamais en dur dans le service.
 */

use Modules\News\Services\DedupService;

uses(Tests\TestCase::class);

it('ne compte pas AI comme entité distinctive', function () {
    $entities = DedupService::extractKeyEntities('AI Startup Anthropic Raises Funding');
    expect($entities)->not->toContain('AI')
        ->and($entities)->not->toContain('ai')
        ->and($entities)->toContain('anthropic');
});

it('ne compte pas IA comme entité distinctive', function () {
    $entities = DedupService::extractKeyEntities('IA générative : Mistral lève des fonds');
    expect($entities)->not->toContain('IA')
        ->and($entities)->not->toContain('ia')
        ->and($entities)->toContain('mistral');
});

it('conserve les acronymes techniques réellement distinctifs', function () {
    $entities = DedupService::extractKeyEntities('GPT and RAG on GPU');
    expect($entities)->toContain('GPT')
        ->and($entities)->toContain('RAG')
        ->and($entities)->toContain('GPU');
});

it('ne rapproche plus deux articles qui ne partagent que le mot AI', function () {
    $similarity = DedupService::jaccardKeywords('AI is the new AI thing', 'The AI of AI');
    expect($similarity)->toBe(0.0);
});

it('garde une similarité élevée sur deux titres réellement identiques', function () {
    $similarity = DedupService::jaccardKeywords('Anthropic lance Claude Opus', 'Anthropic lance Claude Opus');
    expect($similarity)->toBe(1.0);
});

it('lit bien ses listes depuis la configuration du module', function () {
    $genericAcronyms = config('news.fusion.generic_acronyms');
    $knownAcronyms = config('news.fusion.known_acronyms');
    $stopWords = config('news.fusion.stop_words');

    expect($genericAcronyms)->toContain('AI')
        ->and($genericAcronyms)->toContain('IA')
        ->and($knownAcronyms)->not->toContain('AI')
        ->and($knownAcronyms)->not->toContain('IA')
        ->and($stopWords)->toContain('ai')
        ->and($stopWords)->toContain('ia');
});
