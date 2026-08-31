<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Garde-fou d'architecture (#2092, 2026-08-31). Le 18 juillet 2026, une boucle du plan de site
 * construisait une adresse avec route('directory.show', $tool->slug) - un accès BRUT à un champ
 * Spatie Translatable, sans repli - et a fait tomber le plan de site ENTIER dès qu'UN outil n'avait
 * pas de traduction pour la locale courante (trafic de recherche -95 % pendant un mois, remonté
 * seulement après resoumission manuelle). Le correctif de juillet n'avait couvert qu'un modèle sur
 * quatre (Tool). Ce test balaie tout le dépôt (vues ET contrôleurs/services, pas seulement le
 * fichier déjà incriminé) à la recherche du MÊME patron fautif, pour qu'il ne puisse plus jamais
 * réapparaître ailleurs sans faire échouer la suite - un test par cas ne suffit pas : il en
 * faudrait un nouveau à chaque appel ajouté par erreur, et personne n'y pense a priori.
 *
 * config/translatable.php n'étant pas publié dans ce projet, le repli automatique de
 * spatie/laravel-translatable ne s'applique JAMAIS ici (cf. commentaires de
 * Modules\Directory\Models\Tool::getPublicUrl(), le tout premier correctif). Deux formes du
 * même défaut sont balayées :
 *
 *   1. route(<route protégée>, $x->slug) - accès magique brut, jamais de repli.
 *   2. $x->getTranslation('slug', app()->getLocale()) à EXACTEMENT 2 arguments, sans troisième
 *      paramètre ni chaîne `?:` de repli immédiatement après.
 *
 * Remède attendu partout : $model->getPublicUrl() (fiche canonique) ou
 * $model->resolveTranslatedSlug() (toute autre route utilisant le même slug), fournis par
 * Modules\Core\Traits\HasFallbackTranslatedSlug - ou par l'implémentation historique équivalente
 * de Tool/Article, strictement identique dans son résultat.
 */

/**
 * Noms de route dont le paramètre est le slug traduisible d'un des 5 modèles protégés
 * (StaticPage, Term, Acronym, Tool, Article). Une route dont le modèle a un slug NON
 * traduisible (Modules\Tools\Models\Tool, Modules\News\Models\NewsArticle...) n'a pas sa place
 * ici - leur accès brut est sûr et ne doit pas être signalé.
 */
const TRANSLATABLE_SLUG_ROUTE_NAMES = [
    'page.show', 'admin.pages.update', 'admin.pages.preview',
    'dictionary.show', 'dictionary.suggestions.store',
    'acronyms.show', 'acronyms.suggestions.store',
    'directory.show', 'directory.suggestions.store', 'directory.visit',
    'directory.takedown.create', 'directory.reviews.store', 'directory.discussions.store',
    'directory.youtube-meta', 'directory.resources.store', 'directory.screenshots.store',
    'directory.pricing-report',
    'blog.show',
];

/**
 * @return list<string> chemins absolus des fichiers .php et .blade.php de tous les modules,
 *                       hors tests, hors sauvegardes (.bak), hors vendor.
 */
function translatableSlugScanFiles(): array
{
    $root = dirname(__DIR__, 2);
    $dirs = array_merge(
        glob($root.'/Modules/*/app', GLOB_ONLYDIR) ?: [],
        glob($root.'/Modules/*/resources/views', GLOB_ONLYDIR) ?: [],
    );

    $files = [];
    foreach ($dirs as $dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            $path = $fileInfo->getPathname();
            if (str_contains($path, '/tests/') || str_contains($path, '.bak')) {
                continue;
            }
            if (str_ends_with($path, '.blade.php') || str_ends_with($path, '.php')) {
                $files[] = $path;
            }
        }
    }

    return $files;
}

/**
 * @return list<string> une entrée "chemin:ligne — contenu" par violation trouvée.
 */
function translatableSlugFindViolations(): array
{
    $violations = [];
    $routeNamesPattern = implode('|', array_map(
        fn (string $r): string => preg_quote($r, '/'),
        TRANSLATABLE_SLUG_ROUTE_NAMES
    ));

    // Forme 1 : route('<route protégée>', $x->slug) - le slug est bien la SEULE propriété visée
    // (frontière posée par \s*[,)] juste après "slug", jamais un mot plus long comme "sluggish").
    $rawSlugPattern = '/route\(\s*[\'"]('.$routeNamesPattern.')[\'"]\s*,\s*\$[a-zA-Z_][a-zA-Z0-9_]*->slug\s*[,)]/';

    // Forme 2 : getTranslation('slug', app()->getLocale()) à 2 arguments EXACTEMENT (le
    // lookahead négatif écarte la forme protégée à 3 arguments ..., false) et toute chaîne de
    // repli ?: immédiatement à la suite).
    $unguardedGetTranslationPattern = '/->getTranslation\(\s*[\'"]slug[\'"]\s*,\s*app\(\)->getLocale\(\)\s*\)(?!\s*\?:)/';

    foreach (translatableSlugScanFiles() as $path) {
        $lines = file($path);
        if ($lines === false) {
            continue;
        }

        // État tenu PAR FICHIER : un commentaire Blade {{-- ... --}} peut s'étendre sur plusieurs
        // lignes alors que seule la PREMIÈRE porte le marqueur d'ouverture - un simple test par
        // ligne (str_starts_with) le manque (trouvé le 2026-08-31 en rodant ce scanner : il se
        // signalait lui-même sur sa propre ligne de commentaire de correctif, deuxième ligne d'un
        // bloc {{-- sur plusieurs lignes).
        $insideBladeComment = false;

        foreach ($lines as $lineNumber => $line) {
            $trimmed = ltrim($line);

            if ($insideBladeComment) {
                if (str_contains($line, '--}}')) {
                    $insideBladeComment = false;
                }
                continue;
            }

            // Une ligne de commentaire (PHP //, docblock *, Blade {{--) ne compte jamais comme
            // une violation : ce test lui-même documente l'ancien patron fautif en commentaire
            // dans les fichiers corrigés le 2026-08-31 (#2092).
            if (str_starts_with($trimmed, '//')
                || str_starts_with($trimmed, '*')
                || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, '{{--')) {
                if (! str_contains($line, '--}}')) {
                    $insideBladeComment = true;
                }
                continue;
            }

            if (preg_match($rawSlugPattern, $line) || preg_match($unguardedGetTranslationPattern, $line)) {
                $violations[] = $path.':'.($lineNumber + 1).' — '.trim($line);
            }
        }
    }

    return $violations;
}

test('aucune boucle ne reconstruit une adresse à partir d\'un slug traduisible sans repli (#2092)', function () {
    $violations = translatableSlugFindViolations();

    expect($violations)->toBe([], implode("\n", array_merge(
        ['Patron fautif détecté (accès brut à un slug traduisible, sans repli de locale) :'],
        $violations,
        ['', 'Remède : appeler $model->getPublicUrl() ou $model->resolveTranslatedSlug()',
            '(Modules\\Core\\Traits\\HasFallbackTranslatedSlug) au lieu de $model->slug ou',
            'getTranslation(\'slug\', app()->getLocale()) sans troisième paramètre ni repli.'],
    )));
});

test('le scanner voit au moins les fichiers connus (garde-fou anti faux-négatif du test lui-même)', function () {
    $files = translatableSlugScanFiles();

    expect($files)->not->toBeEmpty();
    expect(array_filter($files, fn (string $f): bool => str_ends_with($f, 'SitemapController.php')))->not->toBeEmpty();
    expect(array_filter($files, fn (string $f): bool => str_ends_with($f, 'home.blade.php')))->not->toBeEmpty();
});
