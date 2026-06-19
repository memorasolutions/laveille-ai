<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

class AcademyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotent : firstOrCreate sur le slug du cours.
     */
    public function run(): void
    {
        $this->seedCourse1();
        $this->seedCourse2();
    }

    private function seedCourse1(): void
    {
        [$course, $created] = $this->firstOrCreateCourse('intro-ia-enseignants', [
            'title'            => "Introduction à l'IA pour enseignants",
            'subtitle'         => "Découvrez comment l'IA peut transformer votre pratique",
            'summary'          => "Un parcours pratique pour explorer l'intelligence artificielle en contexte pédagogique.",
            'language'         => 'fr-CA',
            'level'            => 'intro',
            'visibility'       => 'public',
            'access_type'      => 'free',
            'status'           => 'published',
            'published_at'     => now(),
            'duration_minutes' => 90,
        ]);

        if (! $created) {
            return;
        }

        // Chapitre 1 : Les bases de l'IA
        $ch1 = Chapter::create([
            'course_id' => $course->id,
            'title'     => "Les bases de l'IA",
            'position'  => 0,
        ]);

        // Leçon 1 : Qu'est-ce que l'IA ?
        $l1 = Lesson::create([
            'chapter_id' => $ch1->id,
            'title'      => "Qu'est-ce que l'IA ?",
            'slug'       => 'qu-est-ce-que-l-ia',
            'position'   => 0,
        ]);

        LessonItem::create([
            'lesson_id'    => $l1->id,
            'type'         => 'video',
            'title'        => "Vidéo d'introduction",
            'position'     => 0,
            'is_required'  => true,
            'external_ref' => 'sp-placeholder-001',
            'payload'      => [
                'player_url'       => 'https://share.screenpal.com/placeholder',
                'duration_seconds' => 300,
                'domain_lock'      => true,
            ],
        ]);

        LessonItem::create([
            'lesson_id'   => $l1->id,
            'type'        => 'doc',
            'title'       => 'Fiche de lecture',
            'position'    => 1,
            'is_required' => false,
            'payload'     => [
                'rich_text'   => "Lisez cette fiche de référence sur les concepts clés de l'IA.",
                'attachments' => [],
            ],
        ]);

        // Leçon 2 : Les grands modèles de langage
        $l2 = Lesson::create([
            'chapter_id' => $ch1->id,
            'title'      => 'Les grands modèles de langage',
            'slug'       => 'grands-modeles-langage',
            'position'   => 1,
        ]);

        LessonItem::create([
            'lesson_id'    => $l2->id,
            'type'         => 'quiz',
            'title'        => "Quiz : Vrai ou faux sur l'IA",
            'position'     => 0,
            'is_required'  => true,
            'external_ref' => 'qt-ia-bases',
            'payload'      => [
                'qt_bank_key'      => 'qt-questions',
                'passing_score'    => 60,
                'attempts_allowed' => 3,
            ],
        ]);

        // Chapitre 2 : IA en classe
        $ch2 = Chapter::create([
            'course_id' => $course->id,
            'title'     => 'IA en classe',
            'position'  => 1,
        ]);

        // Leçon 3 : Outils IA pour les enseignants
        $l3 = Lesson::create([
            'chapter_id' => $ch2->id,
            'title'      => 'Outils IA pour les enseignants',
            'slug'       => 'outils-ia-enseignants',
            'position'   => 0,
        ]);

        LessonItem::create([
            'lesson_id'    => $l3->id,
            'type'         => 'video',
            'title'        => "Tour d'horizon des outils",
            'position'     => 0,
            'is_required'  => true,
            'external_ref' => 'sp-placeholder-002',
            'payload'      => [
                'player_url'       => 'https://share.screenpal.com/placeholder2',
                'duration_seconds' => 420,
                'domain_lock'      => true,
            ],
        ]);

        // Leçon 4 : Éthique et limites de l'IA
        $l4 = Lesson::create([
            'chapter_id' => $ch2->id,
            'title'      => "Éthique et limites de l'IA",
            'slug'       => 'ethique-limites-ia',
            'position'   => 1,
        ]);

        LessonItem::create([
            'lesson_id'    => $l4->id,
            'type'         => 'doc',
            'title'        => 'Guide éthique IA',
            'position'     => 0,
            'is_required'  => true,
            'external_ref' => 'doc-guide-ethique-ia',
            'payload'      => [
                'rich_text'   => "Réflexions sur les enjeux éthiques de l'IA en éducation.",
                'attachments' => [],
            ],
        ]);

        LessonItem::create([
            'lesson_id'    => $l4->id,
            'type'         => 'quiz',
            'title'        => 'Quiz final',
            'position'     => 1,
            'is_required'  => true,
            'external_ref' => 'qt-ethique-ia',
            'payload'      => [
                'qt_bank_key'      => 'qt-questions',
                'passing_score'    => 70,
                'attempts_allowed' => 2,
            ],
        ]);
    }

    private function seedCourse2(): void
    {
        // M5 : cours payant démo — status 'published' pour permettre les tests locaux
        $this->firstOrCreateCourse('ia-avancee-pedagogie', [
            'title'        => "IA avancée pour la pédagogie",
            'subtitle'     => "Stratégies approfondies pour intégrer l'IA dans votre enseignement",
            'summary'      => "Formation payante démo pour tester le flux Stripe Checkout (paiement unique, prix dynamique).",
            'language'     => 'fr-CA',
            'level'        => 'avance',
            'visibility'   => 'public',
            'access_type'  => 'paid_one_time',
            'price_cents'  => 4900,
            'currency'     => 'CAD',
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * @return array{Course, bool} [course, wasCreated]
     */
    private function firstOrCreateCourse(string $slug, array $attributes): array
    {
        $existing = Course::where('slug', $slug)->first();

        if ($existing) {
            return [$existing, false];
        }

        $course = Course::create(array_merge(['slug' => $slug], $attributes));

        return [$course, true];
    }
}
