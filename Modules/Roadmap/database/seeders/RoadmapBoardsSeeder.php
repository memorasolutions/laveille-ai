<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Roadmap\Models\Board;

class RoadmapBoardsSeeder extends Seeder
{
    public function run(): void
    {
        Board::updateOrCreate(
            ['slug' => 'idees'],
            [
                'name' => 'Idées et suggestions',
                'description' => 'Proposez vos idées pour améliorer La veille. La communauté vote et les meilleures idées sont priorisées.',
                'is_public' => true,
                'color' => '#0B7285',
                'sort_order' => 1,
            ]
        );

        Board::updateOrCreate(
            ['slug' => 'bugs'],
            [
                'name' => 'Signaler un bug',
                'description' => 'Vous avez trouvé un problème ? Signalez-le ici. Les rapports sont privés et visibles uniquement par les membres connectés.',
                'is_public' => false,
                'color' => '#DC2626',
                'sort_order' => 2,
            ]
        );
    }
}
