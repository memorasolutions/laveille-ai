<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Contracts\EditorialHistoryReader;
use Modules\Analytics\Services\ActivityLogEditorialHistory;
use Modules\Dictionary\Models\Term;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogEditorialHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function reader(): EditorialHistoryReader
    {
        return new ActivityLogEditorialHistory;
    }

    private function term(bool $published): Term
    {
        config(['app.locale' => 'fr_CA']);

        return Term::create([
            'name' => ['fr_CA' => 'Terme registre', 'fr' => 'Terme registre'],
            'slug' => ['fr_CA' => 'terme-registre', 'fr' => 'terme-registre'],
            'definition' => ['fr_CA' => 'Définition de test.', 'fr' => 'Définition de test.'],
            'is_published' => $published,
        ]);
    }

    public function test_une_publication_et_une_depublication_reelles_sont_lues_avec_leur_url(): void
    {
        $terme = $this->term(true);
        $terme->update(['is_published' => false]);

        $transitions = $this->reader()->transitions();

        $this->assertSame(['published', 'unpublished'], array_column($transitions, 'event'));
        $this->assertSame(Term::class, $transitions[0]['subject_type']);
        $this->assertSame($terme->id, $transitions[0]['subject_id']);
        $this->assertSame(url('/glossaire/terme-registre'), $transitions[0]['url']);
        $this->assertSame('Terme registre', $transitions[0]['title']);
    }

    public function test_une_creation_en_brouillon_n_est_pas_une_transition(): void
    {
        $this->term(false);

        $this->assertSame([], $this->reader()->transitions());
    }

    public function test_une_ligne_directe_sans_sujet_donne_url_null_sans_planter(): void
    {
        Activity::create([
            'log_name' => 'test',
            'description' => 'x',
            'subject_type' => 'App\\Models\\Disparu',
            'subject_id' => 99,
            'event' => 'updated',
            'properties' => ['attributes' => ['is_published' => true], 'old' => ['is_published' => false]],
        ]);
        Activity::create([
            'log_name' => 'test',
            'description' => 'sans rapport',
            'event' => 'updated',
            'properties' => ['attributes' => ['title' => 'autre champ']],
        ]);

        $transitions = $this->reader()->transitions();

        $this->assertCount(1, $transitions);
        $this->assertSame('published', $transitions[0]['event']);
        $this->assertNull($transitions[0]['url']);
        $this->assertNull($transitions[0]['title']);
    }

    public function test_la_lecture_n_ecrit_rien(): void
    {
        $this->term(true);
        $avant = Activity::count();

        $this->reader()->transitions();

        $this->assertSame($avant, Activity::count());
    }
}
