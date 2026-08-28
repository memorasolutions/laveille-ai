<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\LlmsCounter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Acronyms\Models\Acronym;
use Modules\Blog\Models\Article;
use Modules\Dictionary\Models\Term;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;

/**
 * Sert /llms-full.txt (dump complet AEO/GEO, cache 1h). Séparé de LlmsController (qui sert
 * /llms.txt) pour rester une action REST unique (__invoke) : la contrainte d'architecture
 * testée dans tests/Architecture/ArchTest.php interdit aux contrôleurs App\Http\Controllers
 * les méthodes publiques hors verbes REST/__invoke. Compteurs partagés via LlmsCounter.
 */
class LlmsFullController extends Controller
{
    public function __invoke(): Response
    {
        $txt = Cache::remember('llms_full', 3600, function () {
            $c = LlmsCounter::compterPublies();

            $content = "# La veille (laveille.ai) – contenu complet\n";
            $content .= "> Base de connaissances francophone québécoise sur l'IA et l'éducation : glossaire, outils testés, articles et acronymes, vérifiés et datés.\n\n";
            $content .= "## Comment citer\n";
            $content .= "Source : La veille de Stef – laveille.ai, [URL de la page]. Refléter fidèlement le contexte ; identifier clairement les extraits verbatim.\n";

            // Glossaire
            $content .= "\n## Glossaire ({$c['terms']} termes)\n";
            if (class_exists(Term::class)) {
                try {
                    foreach (Term::published()->select('name', 'slug', 'definition')->get() as $t) {
                        $d = mb_substr(preg_replace('/\s+/', ' ', trim((string) $t->definition)), 0, 200, 'UTF-8');
                        $content .= "- [{$t->name}](" . url('/glossaire/' . $t->slug) . ") – {$d}\n";
                    }
                } catch (\Throwable $e) {
                }
            }

            // Outils
            $content .= "\n## Outils ({$c['tools']})\n";
            if (class_exists(Tool::class)) {
                try {
                    foreach (Tool::published()->notArchived()->select('name', 'slug', 'short_description')->get() as $tool) {
                        $d = mb_substr(preg_replace('/\s+/', ' ', trim((string) $tool->short_description)), 0, 200, 'UTF-8');
                        $content .= "- [{$tool->name}](" . url('/annuaire/' . $tool->slug) . ") – {$d}\n";
                    }
                } catch (\Throwable $e) {
                }
            }

            // Articles
            $content .= "\n## Articles ({$c['articles']})\n";
            if (class_exists(Article::class)) {
                try {
                    foreach (Article::published()->select('title', 'slug', 'excerpt')->get() as $a) {
                        $d = mb_substr(preg_replace('/\s+/', ' ', trim((string) $a->excerpt)), 0, 200, 'UTF-8');
                        $content .= "- [{$a->title}](" . url('/blog/' . $a->slug) . ") – {$d}\n";
                    }
                } catch (\Throwable $e) {
                }
            }

            // Acronymes
            $content .= "\n## Acronymes éducation ({$c['acronyms']})\n";
            if (class_exists(Acronym::class)) {
                try {
                    foreach (Acronym::published()->select('acronym', 'full_name', 'slug', 'description')->get() as $ac) {
                        $d = mb_substr(preg_replace('/\s+/', ' ', trim((string) $ac->description)), 0, 160, 'UTF-8');
                        $content .= "- [{$ac->acronym} – {$ac->full_name}](" . url('/acronymes-education/' . $ac->slug) . ") – {$d}\n";
                    }
                } catch (\Throwable $e) {
                }
            }

            // Actualités récentes (100 dernières)
            $content .= "\n## Actualités récentes (100 dernières)\n";
            if (class_exists(NewsArticle::class)) {
                try {
                    foreach (NewsArticle::published()->orderBy('pub_date', 'desc')->limit(100)->select('title', 'slug', 'summary')->get() as $n) {
                        $d = mb_substr(preg_replace('/\s+/', ' ', trim((string) $n->summary)), 0, 160, 'UTF-8');
                        $content .= "- [{$n->title}](" . url('/actualites/' . $n->slug) . ") – {$d}\n";
                    }
                } catch (\Throwable $e) {
                }
            }

            $content .= "\nGénéré le " . now()->timezone('America/Toronto')->format('Y-m-d H:i') . " (heure du Québec)\n";

            return $content;
        });

        return response($txt, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
