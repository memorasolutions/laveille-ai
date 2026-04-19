<?php
declare(strict_types=1);
namespace Database\Seeders\Standalone;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;

class ReassignConcentreCategoryS26 extends Seeder
{
    public function run(): void
    {
        // Catégorie existante "Le concentré" (3 articles déjà)
        $existing = Category::where('slug->fr_CA', 'le-concentre')
            ->orWhere('slug->fr', 'le-concentre')
            ->first();

        if (!$existing) {
            $this->command?->info('Existing "Le concentré" category NOT found, abort.');
            return;
        }

        // Catégorie nouvelle créée à tort "concentre-hebdo" (vide)
        $duplicate = Category::where('slug->fr_CA', 'concentre-hebdo')
            ->orWhere('slug->fr', 'concentre-hebdo')
            ->first();

        // Réassigner article Concentré hebdo à la catégorie existante
        $article = Article::where('slug->fr_CA', 'le-concentre-de-la-semaine-12-avril-au-19-avril-2026')
            ->orWhere('slug->fr', 'le-concentre-de-la-semaine-12-avril-au-19-avril-2026')
            ->withoutGlobalScopes()
            ->first();

        if ($article) {
            $article->category_id = $existing->id;
            $article->saveQuietly();
            $this->command?->info("Article id={$article->id} reassigned → category_id={$existing->id} (le-concentre)");
        }

        // Supprimer la catégorie duplicate vide (seulement si aucun article)
        if ($duplicate) {
            $count = Article::where('category_id', $duplicate->id)->withoutGlobalScopes()->count();
            if ($count === 0) {
                $duplicate->delete(); // SoftDelete si trait actif
                $this->command?->info("Duplicate category id={$duplicate->id} soft-deleted (empty)");
            } else {
                $this->command?->info("Duplicate category id={$duplicate->id} has {$count} articles, NOT deleted");
            }
        }
    }
}
