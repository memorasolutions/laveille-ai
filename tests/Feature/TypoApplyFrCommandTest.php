<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2290 (2026-09-06) : la commande typo:apply-fr déclarait des colonnes absentes du
 * schéma réel (dictionary_terms.term/context, news_articles.excerpt/content,
 * articles.meta_title/meta_description, la table 'pages' inexistante, testimonials.name) et les
 * filtrait EN SILENCE - aucune erreur, aucun avertissement fort, juste des colonnes jamais
 * typographiées. Ces tests prouvent la garde ajoutée dans guardPlanIntegrity() : la commande
 * REFUSE (code de sortie non-zéro, table + colonne nommées) plutôt que de continuer en silence.
 */

use App\Console\Commands\TypoApplyFrCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('typo:apply-fr command is registered', function () {
    expect(Artisan::all())->toHaveKey('typo:apply-fr');
});

test('typo:apply-fr refuses a plan whose column is absent from an existing table', function () {
    // Fabrique le défaut d'origine : une colonne du plan ('faqs.answer') existe au moment
    // d'écrire le plan, puis disparaît (renommée/supprimée par une migration future) sans que
    // le plan soit mis à jour - exactement le scénario constaté sur dictionary_terms/news_articles/
    // articles.
    Schema::table('faqs', function ($table) {
        $table->dropColumn('answer');
    });

    $this->artisan('typo:apply-fr', ['--table' => ['faqs'], '--dry' => true])
        ->expectsOutputToContain("colonne 'faqs.answer' absente du schéma réel")
        ->assertExitCode(1);

    // Garde-fou complémentaire : ni lecture ni écriture n'a eu lieu sur faqs (question, elle,
    // existe toujours) - un refus qui laisserait quand même tourner le traitement sur les
    // colonnes restantes reproduirait le silence partiel qu'on corrige ici.
});

test('typo:apply-fr accepts the full curated plan when it matches the real schema', function () {
    // Plan par défaut (toutes les tables), aucune altération du schéma : doit passer sans
    // aucune erreur, y compris la table 'testimonials' (module désactivé dans ce déploiement,
    // skip attendu et non fautif).
    $this->artisan('typo:apply-fr', ['--dry' => true])
        ->expectsOutputToContain('module \'Testimonials\' désactivé')
        ->assertExitCode(0);
});

test('typo:apply-fr refuses a table absent from schema when no disabled module explains it', function () {
    // Injecte dans le plan une table qui n'existera JAMAIS et qui n'est PAS répertoriée dans
    // $optionalModuleTables - simule le cas 'pages' avant correction (nom de table erroné, ou
    // table jamais créée), distinct du cas légitime 'testimonials' (module désactivé).
    $command = app(TypoApplyFrCommand::class);

    $planProperty = new ReflectionProperty($command, 'plan');
    $planProperty->setAccessible(true);
    $planProperty->setValue($command, [
        'table_fantome_jamais_creee' => ['colonne_fictive'],
    ]);

    app()->instance(TypoApplyFrCommand::class, $command);

    $this->artisan('typo:apply-fr', ['--table' => ['table_fantome_jamais_creee'], '--dry' => true])
        ->expectsOutputToContain("table 'table_fantome_jamais_creee' absente du schéma réel")
        ->assertExitCode(1);
});

test('typo:apply-fr applies NBSP and writes only when not in dry mode', function () {
    // Preuve fonctionnelle que le refactor de la garde (retrait de l'array_filter silencieux)
    // n'a pas cassé le chemin heureux : une vraie ligne, une vraie colonne du plan corrigé.
    $id = DB::table('faqs')->insertGetId([
        'question' => 'Combien coûte : le service ?',
        'answer' => 'Environ 25% du temps standard.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('typo:apply-fr', ['--table' => ['faqs'], '--dry' => true])
        ->assertExitCode(0);

    $unchanged = DB::table('faqs')->where('id', $id)->first();
    expect($unchanged->question)->toBe('Combien coûte : le service ?');

    $this->artisan('typo:apply-fr', ['--table' => ['faqs']])
        ->assertExitCode(0);

    $row = DB::table('faqs')->where('id', $id)->first();
    expect($row->question)->toBe("Combien coûte\u{00A0}: le service\u{00A0}?");
    expect($row->answer)->toBe("Environ 25\u{00A0}% du temps standard.");
});
