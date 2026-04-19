<?php
declare(strict_types=1);
namespace Database\Seeders\Standalone;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;

class AssignConcentreCategoryS26 extends Seeder
{
    public function run(): void
    {
        $cat = Category::where('slug->fr_CA', 'concentre-hebdo')
            ->orWhere('slug->fr', 'concentre-hebdo')
            ->first();

        if (!$cat) {
            $cat = Category::create([
                'name' => ['fr_CA' => 'Concentré hebdo', 'fr' => 'Concentré hebdo'],
                'slug' => ['fr_CA' => 'concentre-hebdo', 'fr' => 'concentre-hebdo'],
                'description' => ['fr_CA' => 'Récap hebdomadaire des actualités IA qui comptent pour nous autres au Québec.', 'fr' => 'Récap hebdomadaire des actualités IA qui comptent pour nous autres au Québec.'],
                'color' => '#0B7285',
                'is_active' => true,
            ]);
            $this->command?->info("Category created id={$cat->id}");
        } else {
            $this->command?->info("Category exists id={$cat->id}");
        }

        $article = Article::where('slug->fr_CA', 'le-concentre-de-la-semaine-12-avril-au-19-avril-2026')
            ->orWhere('slug->fr', 'le-concentre-de-la-semaine-12-avril-au-19-avril-2026')
            ->withoutGlobalScopes()
            ->first();

        if (!$article) {
            $this->command?->info('Article not found.');
            return;
        }

        $article->category_id = $cat->id;
        $article->saveQuietly();
        $this->command?->info("Article id={$article->id} → category_id={$cat->id}");
    }
}
