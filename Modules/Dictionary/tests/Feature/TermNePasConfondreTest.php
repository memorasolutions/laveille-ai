<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve HTTP de bout en bout de la section publique « À ne pas confondre avec » (ticket #2524,
 * étape 3 bis). Voir Modules\Dictionary\Models\Term (colonne `disambiguation_note`),
 * Modules\Dictionary\Database\Seeders\DisambiguationNoteSeeder et
 * Modules/Dictionary/resources/views/public/show.blade.php.
 *
 * Convention du module (même helpers que TermDansActualiteTest, ViewCounterDictionaryTest et
 * PublicDictionaryIndexPageTest) : pas de TermFactory, construction directe du modèle.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Database\Seeders\DisambiguationNoteSeeder;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helper local (préfixé Ncf pour éviter tout conflit inter-fichiers) ──────────────────────

function ncfTerm(array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = $overrides['slug'] ?? 'terme-ncf-'.uniqid();
    unset($overrides['slug']);

    return Term::create(array_merge([
        'name' => [$locale => 'Terme ne pas confondre '.uniqid(), 'fr' => 'Terme ne pas confondre'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test pour la section à ne pas confondre.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ], $overrides));
}

// ── Cas 1 : mise en garde présente → section affichée, titre + texte exact ─────────────────

it('affiche la section « À ne pas confondre avec » quand la mise en garde est renseignée', function () {
    $term = ncfTerm([
        'disambiguation_note' => 'Le « Nether Hub » de Minecraft, Azure Event Hub et HubSpot portent le même mot sans rapport avec ce concept.',
    ]);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('À ne pas confondre avec');
    $reponse->assertSee('Le « Nether Hub » de Minecraft, Azure Event Hub et HubSpot portent le même mot sans rapport avec ce concept.');
});

// ── Témoin négatif : un terme sans mise en garde n'émet AUCUN balisage de section ──────────

it('n\'émet AUCUN balisage pour un terme sans mise en garde (témoin négatif)', function () {
    $term = ncfTerm();

    expect($term->disambiguation_note)->toBeNull();

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertDontSee('À ne pas confondre avec');
});

// ── Seeder : idempotent, deux passages → une seule valeur, inchangée ────────────────────────

it('le seeder DisambiguationNoteSeeder pose la valeur une fois et ne la duplique ni ne la modifie au second passage', function () {
    $hub = ncfTerm(['slug' => 'hub']);
    $perplexite = ncfTerm(['slug' => 'perplexite-metrique']);
    $epoque = ncfTerm(['slug' => 'epoque']);
    $socket = ncfTerm(['slug' => 'socket']);
    $batch = ncfTerm(['slug' => 'batch']);

    (new DisambiguationNoteSeeder)->run();

    $hub->refresh();
    $perplexite->refresh();
    $epoque->refresh();
    $socket->refresh();
    $batch->refresh();

    expect($hub->disambiguation_note)->toBe('Le « Nether Hub » de Minecraft, Azure Event Hub et HubSpot portent le même mot sans rapport avec ce concept.')
        ->and($perplexite->disambiguation_note)->toBe("Perplexity AI, le moteur de recherche, est un produit commercial : il n'a aucun lien avec cette mesure.")
        ->and($epoque->disambiguation_note)->toBe('Le jeu vidéo « Last Epoch » occupe le même mot, sans rapport avec l\'entraînement d\'un modèle.')
        ->and($socket->disambiguation_note)->toBe("Socket.io est une bibliothèque JavaScript précise, pas le point de connexion réseau décrit ici.")
        ->and($batch->disambiguation_note)->toBe('En cuisine et en industrie, un « batch » désigne une fournée, pas un lot de données d\'entraînement.');

    // Toujours une seule fiche par slug - le seeder ne crée jamais de terme, il n'enrichit que
    // l'existant.
    expect(Term::where('slug->fr_CA', 'hub')->count())->toBe(1);

    // Second passage : même valeur, rien ne bouge.
    (new DisambiguationNoteSeeder)->run();

    $hub->refresh();
    $perplexite->refresh();

    expect($hub->disambiguation_note)->toBe('Le « Nether Hub » de Minecraft, Azure Event Hub et HubSpot portent le même mot sans rapport avec ce concept.')
        ->and($perplexite->disambiguation_note)->toBe("Perplexity AI, le moteur de recherche, est un produit commercial : il n'a aucun lien avec cette mesure.")
        ->and(Term::where('slug->fr_CA', 'hub')->count())->toBe(1);
});

// ── Le seeder ne doit JAMAIS écraser une valeur qu'un humain aurait modifiée entre-temps ────

it('le seeder ne remplace jamais une mise en garde déjà présente et différente (édition humaine protégée)', function () {
    $hub = ncfTerm([
        'slug' => 'hub',
        'disambiguation_note' => 'Texte corrigé à la main par un humain, différent du texte du seeder.',
    ]);

    (new DisambiguationNoteSeeder)->run();

    $hub->refresh();

    expect($hub->disambiguation_note)->toBe('Texte corrigé à la main par un humain, différent du texte du seeder.');
});
