<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests unitaires de SummaryQualityGate (design doc "Actus - zéro copie du texte source",
 * 2026-08-13, section 4.2 + section 6). Contrôles testés isolément, sans aucun appel réseau.
 */

use Modules\News\Services\SummaryQualityGate;

uses(Tests\TestCase::class);

/**
 * Résumé structuré complet, valide, qui passe les 7 contrôles par défaut. Couvre TOUS les
 * champs du contrat de prompt requis depuis le recalibrage 2026-08-13 (config
 * news.quality_gate.required_fields) - une fixture volontairement minimale masquerait des
 * régressions sur la structure/vacuité (voir consigne : corriger la fixture, jamais affaiblir
 * la porte pour la satisfaire).
 */
function sqgValidSummary(array $overrides = []): array
{
    return array_merge([
        'score' => 8,
        'score_justification' => 'Sujet technologique clairement pertinent pour le lectorat visé.',
        'category' => 'IA générative',
        'impact' => 'Moyen',
        'tldr' => 'Une entreprise technologique lance un nouvel outil francophone destiné aux équipes de développement au Québec cette semaine.',
        'hook' => 'Une entreprise technologique dévoile un nouvel outil pour les équipes francophones.',
        'key_points' => ['Premier fait détaillé du test.', 'Deuxième fait détaillé du test.'],
        'why_important' => 'Ce changement modifie concrètement le travail quotidien des professionnels visés.',
        'audience' => ['développeurs', 'entreprises'],
        'seo_title' => 'Titre SEO de test',
        'meta_description' => 'Description meta de test suffisamment courte.',
        'faq_question' => 'Pourquoi cet outil intéresse-t-il les équipes francophones ?',
        'faq_answer' => 'Parce qu\'il répond à un besoin concret de localisation resté sans solution jusqu\'ici.',
    ], $overrides);
}

it('accepte un résumé complet, en français, de longueur normale, sans copie du texte source', function () {
    $gate = new SummaryQualityGate();

    $result = $gate->check(sqgValidSummary(), 'Un texte source totalement différent du résumé, en anglais ou en français peu importe.');

    expect($result['ok'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});

it('refuse un résumé incomplet : structure (clé "hook" absente)', function () {
    $gate = new SummaryQualityGate();
    $summary = sqgValidSummary();
    unset($summary['hook']);

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toStartWith('structure:');
});

it('refuse un résumé incomplet : vacuité (hook réduit à des espaces)', function () {
    $gate = new SummaryQualityGate();
    $summary = sqgValidSummary(['hook' => '   ']);

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toStartWith('vacuite:');
});

it('refuse un résumé en anglais', function () {
    $gate = new SummaryQualityGate();
    // Les 5 champs PROSE_FIELDS basculent en anglais (pas seulement hook/why_important) : le
    // signal doit rester net, sans dépendre du dosage français/anglais résiduel de la fixture
    // de base sur les autres champs prose.
    $summary = sqgValidSummary([
        'tldr' => 'A tech company is launching a new tool for francophone teams this week in the industry.',
        'hook' => 'The company is launching a new tool for the market and this is a big deal for everyone in the industry.',
        'key_points' => ['A first detailed fact about the tool.', 'A second detailed fact about the launch.'],
        'why_important' => 'This is important because the tool changes how professionals work with their data every single day.',
        'faq_answer' => 'Because this tool answers a need that was not addressed before in the market.',
    ]);

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('langue:anglais_detecte');
});

it('accepte un résumé français contenant des noms propres anglophones isolés (OpenAI, ChatGPT)', function () {
    $gate = new SummaryQualityGate();
    $summary = sqgValidSummary([
        'hook' => 'OpenAI dévoile ChatGPT Enterprise pour les entreprises francophones du Québec.',
    ]);

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeTrue();
});

it('refuse un résumé trop court : hook sous le seuil minimal de mots', function () {
    $gate = new SummaryQualityGate();
    config(['news.quality_gate.hook_min_words' => 10]);
    $summary = sqgValidSummary(['hook' => 'Trop court.']);

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('longueur:hook');
});

it('refuse un résumé dont le seo_title dépasse la borne configurée', function () {
    $gate = new SummaryQualityGate();
    config(['news.quality_gate.seo_title_max_chars' => 20]);
    $summary = sqgValidSummary(['seo_title' => 'Un titre SEO beaucoup trop long pour la borne configurée']);

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('longueur:seo_title');
});

it('refuse un résumé qui reproduit littéralement une longue suite du texte source (non-copie)', function () {
    $gate = new SummaryQualityGate();
    config(['news.quality_gate.copy_max_words' => 8]);

    $sourceText = 'Le gouvernement du Québec annonce un nouveau programme de subvention pour les entreprises technologiques qui investissent dans la recherche appliquée en intelligence artificielle cette année.';
    $summary = sqgValidSummary([
        // Reproduit verbatim 9 mots consécutifs du texte source ci-dessus.
        'hook' => 'Le gouvernement du Québec annonce un nouveau programme de subvention, une annonce majeure.',
    ]);

    $result = $gate->check($summary, $sourceText);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('non_copie:hook');
});

it('tolère la citation verbatim dans le champ "quote" (exclu du contrôle anti-copie)', function () {
    $gate = new SummaryQualityGate();
    config(['news.quality_gate.copy_max_words' => 8]);

    $sourceText = 'Le gouvernement du Québec annonce un nouveau programme de subvention pour les entreprises technologiques qui investissent dans la recherche appliquée en intelligence artificielle cette année.';
    $summary = sqgValidSummary([
        'quote' => 'Le gouvernement du Québec annonce un nouveau programme de subvention pour les entreprises',
    ]);

    $result = $gate->check($summary, $sourceText);

    expect($result['ok'])->toBeTrue();
});

it('ne refuse jamais un résumé à cause d\'un texte source vide (aucune base de comparaison)', function () {
    $gate = new SummaryQualityGate();

    $result = $gate->check(sqgValidSummary(), '');

    expect($result['ok'])->toBeTrue();
});

it('la porte peut être désactivée entièrement par configuration', function () {
    $gate = new SummaryQualityGate();
    config(['news.quality_gate.enabled' => false]);
    $summary = sqgValidSummary(['hook' => '']); // aurait normalement échoué la vacuité

    $result = $gate->check($summary, '');

    expect($result['ok'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Contrôles 6 et 7 (2026-08-13) : cohérence des années, non-invention d'entités
|--------------------------------------------------------------------------
| Ajoutés suite à une mesure sur 47 fiches réelles confrontées à leur source :
| 27,7 % contenaient au moins un fait déformé ou inventé, motif le plus fréquent =
| millésime hallucié, 2e motif = entité inventée.
*/

it('refuse un résumé dont une année est absente de la source et incohérente avec la date de publication (millésime halluciné)', function () {
    $gate = new SummaryQualityGate();

    $sourceText = 'Une entreprise québécoise annonce un partenariat stratégique avec un fournisseur infonuagique pour moderniser ses services numériques destinés aux entreprises de la province.';
    $summary = sqgValidSummary([
        // "2024" n'apparaît nulle part dans la source ci-dessus, et l'article est publié en
        // août 2026 : écart de 2 ans, hors tolérance par défaut (+-1 an).
        'hook' => "L'entreprise avait signé un accord similaire en 2024 avec un autre partenaire technologique, selon la fiche.",
    ]);

    $result = $gate->check($summary, $sourceText, \Carbon\Carbon::create(2026, 8, 10));

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('annee_incoherente:hook:2024');
});

it('accepte une année absente de la source si elle reste dans la tolérance autour de la date de publication', function () {
    $gate = new SummaryQualityGate();

    $sourceText = 'Une entreprise québécoise annonce un partenariat stratégique avec un fournisseur infonuagique pour moderniser ses services numériques destinés aux entreprises de la province.';
    $summary = sqgValidSummary([
        // "2027" n'apparaît pas dans la source, mais l'article est publié en 2026 : écart de 1
        // an, dans la tolérance par défaut - tournure journalistique normale ("dès l'an prochain").
        'hook' => "Le déploiement complet est prévu pour 2027, précise l'entreprise dans son communiqué.",
    ]);

    $result = $gate->check($summary, $sourceText, \Carbon\Carbon::create(2026, 8, 10));

    expect($result['ok'])->toBeTrue();
});

it('refuse un résumé mentionnant une entité absente de la source (entité inventée)', function () {
    $gate = new SummaryQualityGate();

    $sourceText = "Une plateforme d'apprentissage en ligne annonce une mise à jour majeure de son moteur de recommandation, avec un accent sur l'accessibilité pour les enseignants du secondaire.";
    $summary = sqgValidSummary([
        // "TechCorp Global" n'est cité nulle part dans la source ci-dessus.
        'why_important' => 'Selon la fiche, TechCorp Global aurait été condamnée à une lourde amende pour manquement à la réglementation sur la protection des données.',
    ]);

    $result = $gate->check($summary, $sourceText);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('entite_absente:why_important:TechCorp Global');
});

it('accepte une entité présente dans la source même orthographiée différemment (déclinaison plurielle)', function () {
    $gate = new SummaryQualityGate();

    $sourceText = 'Les Créations Boréales annoncent un nouveau partenariat avec une entreprise européenne spécialisée dans le recyclage de matériaux.';
    $summary = sqgValidSummary([
        // "Création Boréale" (singulier) vs "Créations Boréales" (pluriel) dans la source :
        // tolérance par préfixe de 5 caractères, casse/accents ignorés.
        'why_important' => "Création Boréale confirme vouloir étendre ses activités à l'international dans les prochains mois.",
    ]);

    $result = $gate->check($summary, $sourceText);

    expect($result['ok'])->toBeTrue();
});

it('une fiche conforme au contrat complet, cohérente en années et en entités, passe les 7 contrôles', function () {
    $gate = new SummaryQualityGate();

    $sourceText = "Une entreprise technologique québécoise dévoile un nouvel outil destiné aux équipes francophones. Le projet, mené par Marie Dubois, a démarré en 2025 et sera généralisé d'ici 2027 selon l'entreprise.";
    $summary = sqgValidSummary([
        'hook' => "L'entreprise a débuté ce projet en 2025 avec l'objectif de le généraliser d'ici 2027.",
        'expert_name' => 'Marie Dubois',
        'quote' => "Nous avons démarré en 2025 avec l'objectif de généraliser cet outil",
    ]);

    $result = $gate->check($summary, $sourceText, \Carbon\Carbon::create(2026, 8, 10));

    expect($result['ok'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});
