<?php

declare(strict_types=1);

/**
 * Test d'architecture - interdiction formelle de la commande `php artisan config:cache`.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project la-veille-de-stef-v2
 *
 * CONTEXTE - Incident #2099 (2026-08-31) :
 *
 * Le 2026-06-30, un `php artisan config:cache` exécuté silencieusement dans un Dockerfile de développement
 * a refermé le module Académie en production. Le middleware AcademyUnderConstruction lit
 * config('academy.under_construction'), qui provient de env('ACADEMY_UNDER_CONSTRUCTION', true).
 * Sans config:cache, le .env est relu à chaque requête et la valeur est `false` (module ouvert).
 * Mais avec config:cache, tous les appels à env() (hors fichiers config/) retournent `null` à l'exécution,
 * ce qui affecte également les vues Blade, Google Analytics, AdSense, etc. Le défaut du gate tombe donc
 * sur son option fermée (true), bloquant l'accès public à la plateforme.
 *
 * Le fichier docker/php/Dockerfile contenait la ligne fautive :
 *   `RUN php artisan config:cache && php artisan route:cache`
 * Corrigée juste avant ce ticket en :
 *   `RUN php artisan route:cache`
 * ATTENTION : `route:cache` reste autorisé et ne doit pas être supprimé.
 *
 * Ce Dockerfile n'était référencé que localement (docker-compose.yml), jamais par le pipeline de production
 * (cPanel/rsync/SSH via .github/workflows/deploy.yml + scripts/deploy.sh). Il s'agissait d'un risque dormant.
 *
 * Ce test empêche que la commande interdite ne réapparaisse JAMAIS dans un fichier exécutable
 * (script, Dockerfile, définition de déploiement, Makefile, fichier .sh, script PHP autonome public/_lv*.php).
 * La règle ne doit plus reposer uniquement sur une consigne écrite : elle doit faire échouer la suite en cas
 * de violation.
 *
 * Portée volontairement restreinte aux fichiers EXÉCUTABLES - jamais la prose (CHANGELOG.md,
 * docs/HISTORIQUE-VERSIONS.md, ou les commentaires PHP qui expliquent déjà l'interdiction dans
 * Modules/Health et Modules/Backoffice). Un contrôle qui crie au loup sur une mention documentaire se fait
 * désactiver dans la semaine.
 *
 * Hors périmètre, constaté au passage mais volontairement NON corrigé ici (mandat borné à config:cache,
 * pas à `php artisan optimize` qui l'appelle en interne) : Makefile (cibles `cache` et `deploy`) lance
 * `php artisan optimize`, un outil de confort LOCAL non référencé par le pipeline de déploiement réel -
 * même classe de risque dormant que le Dockerfile corrigé ici, mais hors du périmètre nommé de ce ticket.
 *
 * RAPPEL DES CACHES AUTORISÉS (ne lisent pas env()) :
 * - route:cache-atomic
 * - event:cache
 * - view:cache
 *
 * Voir la documentation complète : docs/CONTRAINTES-SOUS-AGENTS.md
 */

/**
 * Retourne la liste des fichiers exécutables à scanner pour détecter des appels à `config:cache`.
 * Chemins absolus, dédoublonnés, triés.
 *
 * Périmètre :
 * 1. Dockerfile* à la racine (glob)
 * 2. docker-compose*.y*ml et compose*.y*ml à la racine (glob)
 * 3. Makefile à la racine si présent
 * 4. .github/workflows/*.{yml,yaml}
 * 5. public/_lv*.php (scripts PHP autonomes de déploiement/secours, auto-suppressibles - ce dossier a déjà
 *    porté cette exacte violation une fois, dans public/_lvgit.php, retirée le 2026-08-23)
 * 6. Tous les fichiers sous docker/ (récursif)
 * 7. Tous les fichiers .sh dans tout le dépôt (récursif, en élaguant AVANT d'y descendre les dossiers
 *    vendor, node_modules, storage, .git, public, bootstrap, test-results)
 *
 * @return list<string>
 */
function configCacheScanFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    // 1. Dockerfile* (racine)
    foreach (glob($root.'/Dockerfile*') ?: [] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }

    // 2. docker-compose*.y*ml et compose*.y*ml (racine)
    foreach (glob($root.'/docker-compose*.y*ml') ?: [] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }
    foreach (glob($root.'/compose*.y*ml') ?: [] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }

    // 3. Makefile (racine)
    $makefile = $root.'/Makefile';
    if (is_file($makefile)) {
        $files[] = $makefile;
    }

    // 4. .github/workflows/*.{yml,yaml}
    foreach (glob($root.'/.github/workflows/*.yml') ?: [] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }
    foreach (glob($root.'/.github/workflows/*.yaml') ?: [] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }

    // 5. public/_lv*.php (scripts de déploiement autonomes)
    foreach (glob($root.'/public/_lv*.php') ?: [] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }

    // 6. docker/ (récursif)
    $dockerDir = $root.'/docker';
    if (is_dir($dockerDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dockerDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $files[] = $fileInfo->getPathname();
            }
        }
    }

    // 7. Tous les .sh du dépôt, où qu'ils vivent (récursif, dossiers lourds/non pertinents élagués
    // AVANT d'y descendre - pas un filtre après coup, pour rester rapide).
    $prunedDirs = ['vendor', 'node_modules', 'storage', '.git', 'public', 'bootstrap', 'test-results'];
    $shFilter = new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $current) use ($prunedDirs): bool {
            if ($current->isDir()) {
                return ! in_array($current->getFilename(), $prunedDirs, true);
            }

            return str_ends_with($current->getFilename(), '.sh');
        }
    );
    foreach (new RecursiveIteratorIterator($shFilter) as $fileInfo) {
        if ($fileInfo->isFile()) {
            $files[] = $fileInfo->getPathname();
        }
    }

    $files = array_values(array_unique($files));
    sort($files);

    return $files;
}

/**
 * Scanne les fichiers exécutables pour trouver des invocations réelles de `config:cache` - jamais une
 * simple mention documentaire.
 *
 * Une ligne de commentaire entière (#, //, *) est toujours ignorée : ce dépôt documente DÉLIBÉRÉMENT
 * l'interdiction en commentaire à plusieurs endroits scannés (ex. scripts/deploy.sh explique que
 * config:cache en a été retiré) - ce commentaire ne doit jamais être signalé comme violation.
 *
 * Deux formes d'invocation réelle sont recherchées sur les lignes non-commentées :
 *   MOTIF 1 (forme shell/Dockerfile/YAML/Makefile) : `artisan` immédiatement suivi de `config:cache`,
 *     ex. `php artisan config:cache`. Ne capture pas une phrase comme « JAMAIS config:cache » ou
 *     « on retire config:cache (php artisan optimize) », où le mot artisan n'est pas immédiatement
 *     collé à config:cache.
 *   MOTIF 2 (forme tableau PHP, utilisée par public/_lvgit.php pour bâtir ses commandes, ex.
 *     `$commands[] = [$phpBin, 'artisan', 'view:clear'];`) : les éléments littéraux 'artisan' et
 *     'config:cache' adjacents dans un tableau.
 *
 * @return list<string> une entrée "chemin:ligne - contenu" par invocation interdite trouvée.
 */
function configCacheFindViolations(): array
{
    $violations = [];

    $shellInvocationPattern = '/\bartisan\s+config:cache\b/';
    $arrayInvocationPattern = '/[\'"]artisan[\'"]\s*,\s*[\'"]config:cache[\'"]/';

    foreach (configCacheScanFiles() as $path) {
        $lines = file($path);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $lineNumber => $line) {
            $trimmed = ltrim($line);

            if (str_starts_with($trimmed, '#') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
                continue;
            }

            if (preg_match($shellInvocationPattern, $line) || preg_match($arrayInvocationPattern, $line)) {
                $violations[] = $path.':'.($lineNumber + 1).' - '.trim($line);
            }
        }
    }

    return $violations;
}

test('aucun fichier exécutable ne lance php artisan config:cache (#2099)', function () {
    $violations = configCacheFindViolations();

    expect($violations)->toBe([], implode("\n", array_merge(
        ['Invocation interdite détectée (config:cache ferme des sections du site en production - env() runtime devient null) :'],
        $violations,
        ['', 'Remède : retirer l\'appel. Caches sûrs (ne lisent pas env()) : route:cache-atomic,',
            'event:cache, view:cache. Détail de l\'incident : docs/CONTRAINTES-SOUS-AGENTS.md.'],
    )));
});

test('le scanner voit au moins les fichiers exécutables connus (garde-fou anti faux-négatif du test lui-même)', function () {
    $files = configCacheScanFiles();

    expect($files)->not->toBeEmpty();

    $checkSuffixes = [
        'docker/php/Dockerfile',
        '.github/workflows/deploy.yml',
        'scripts/deploy.sh',
        '/Makefile',
    ];

    foreach ($checkSuffixes as $suffix) {
        $found = array_filter($files, fn (string $path): bool => str_ends_with($path, $suffix));
        expect($found)->not->toBeEmpty("Le chemin se terminant par '{$suffix}' est introuvable dans la liste des fichiers scannés.");
    }
});

test('le contrôle ne crie pas à tort sur une mention documentaire de config:cache', function () {
    // Négatif de contrôle : la chaîne « config:cache » existe encore, à dessein, dans des
    // commentaires/libellés de ces mêmes fichiers exécutables (elle y explique l'interdiction).
    // Si CE test échouait faute de matière, le négatif de contrôle ne contrôlerait plus rien.
    $haystack = [];
    foreach (configCacheScanFiles() as $path) {
        $contents = file_get_contents($path);
        if ($contents !== false && str_contains($contents, 'config:cache')) {
            $haystack[] = $path;
        }
    }

    expect($haystack)->not->toBeEmpty();
    expect(configCacheFindViolations())->toBe([]);
});
