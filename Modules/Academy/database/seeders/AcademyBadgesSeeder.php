<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Badges de départ de l'Académie (Phase E / E1). IDEMPOTENT : updateOrCreate par
 * `key`, donc relançable sans créer de doublon ni écraser les badges déjà gagnés
 * (le pivot academy_user_badges n'est pas touché). N'est PAS lancé par le CI ;
 * le superviseur l'exécutera en prod.
 */

declare(strict_types=1);

namespace Modules\Academy\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Academy\Models\Badge;

class AcademyBadgesSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : si la table n'existe pas (migration non lancée), no-op.
        if (! Schema::hasTable('academy_badges')) {
            return;
        }

        foreach ($this->badges() as $badge) {
            Badge::updateOrCreate(
                ['key' => $badge['key']],
                [
                    'name'           => $badge['name'],
                    'description'    => $badge['description'],
                    'icon'           => $badge['icon'],
                    'criteria_type'  => $badge['criteria_type'],
                    'criteria_value' => $badge['criteria_value'] ?? null,
                    'is_active'      => true,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function badges(): array
    {
        return [
            [
                'key'            => 'first_course_completed',
                'name'           => 'Premier pas',
                'description'    => 'Vous avez complété votre toute première formation.',
                'icon'           => '🎉',
                'criteria_type'  => 'first_course_completed',
                'criteria_value' => null,
            ],
            [
                'key'            => 'perseverant',
                'name'           => 'Persévérant',
                'description'    => 'Vous avez complété 10 leçons. Bel élan !',
                'icon'           => '🔥',
                'criteria_type'  => 'lessons_completed',
                'criteria_value' => 10,
            ],
            [
                'key'            => 'assidu',
                'name'           => 'Assidu',
                'description'    => 'Vous avez complété 25 leçons. Quelle régularité !',
                'icon'           => '⭐',
                'criteria_type'  => 'lessons_completed',
                'criteria_value' => 25,
            ],
            [
                'key'            => 'diplome',
                'name'           => 'Diplômé',
                'description'    => 'Vous avez décroché votre premier certificat.',
                'icon'           => '🎓',
                'criteria_type'  => 'first_certificate',
                'criteria_value' => null,
            ],
            [
                'key'            => 'sans_faute',
                'name'           => 'Sans faute',
                'description'    => 'Vous avez réussi un quiz avec un score parfait.',
                'icon'           => '💯',
                'criteria_type'  => 'perfect_quiz',
                'criteria_value' => null,
            ],
        ];
    }
}
