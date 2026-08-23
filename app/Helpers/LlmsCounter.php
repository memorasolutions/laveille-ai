<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Modules\Acronyms\Models\Acronym;
use Modules\Blog\Models\Article;
use Modules\Dictionary\Models\Term;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;

/**
 * Compte les contenus publiés, partagé entre LlmsController (/llms.txt) et LlmsFullController
 * (/llms-full.txt) pour ne pas dupliquer la logique entre deux contrôleurs à action unique.
 *
 * Modules désactivables gérés par class_exists() avant chaque comptage : un module retiré
 * ramène 0 et ne casse jamais la page.
 *
 * Un comptage qui ÉCHOUE est journalisé sur le canal dédié « llms », jamais avalé en silence :
 * ces compteurs alimentent un fichier public lu par les moteurs de réponse, et un zéro
 * silencieux leur annoncerait un site vide sans laisser la moindre trace pour le diagnostiquer.
 * Le canal est dédié parce que LOG_LEVEL=error en production avale tout ce qui est sous error.
 */
class LlmsCounter
{
    /**
     * @return array{tools:int,terms:int,articles:int,acronyms:int,news:int}
     */
    public static function compterPublies(): array
    {
        $modules = [
            'tools' => [Tool::class, static fn (string $c): int => $c::published()->notArchived()->count()],
            'terms' => [Term::class, static fn (string $c): int => $c::published()->count()],
            'articles' => [Article::class, static fn (string $c): int => $c::published()->count()],
            'acronyms' => [Acronym::class, static fn (string $c): int => $c::published()->count()],
            'news' => [NewsArticle::class, static fn (string $c): int => $c::published()->count()],
        ];

        $counts = [];

        foreach ($modules as $cle => [$classe, $compter]) {
            $counts[$cle] = 0;

            if (! class_exists($classe)) {
                continue;
            }

            try {
                $counts[$cle] = $compter($classe);
            } catch (\Throwable $e) {
                Log::channel('llms')->warning('Comptage impossible, le fichier llms annoncera 0.', [
                    'cle' => $cle,
                    'classe' => $classe,
                    'motif' => $e->getMessage(),
                ]);
            }
        }

        return $counts;
    }
}
