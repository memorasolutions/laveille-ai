<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * "Module désactivé sans casse" (brief) - preuve STATIQUE plutôt qu'un basculement en direct de
 * modules_statuses.json. Un test qui appellerait Module::find('Signature')->disable() en direct
 * mutrait un fichier de configuration PARTAGÉ par toute la session (et par un éventuel agent
 * concurrent qui l'édite en ce moment, cf. brief) - même en try/finally, le risque de laisser le
 * site dans un état incohérent en cas d'échec du test l'emporte sur la valeur de la preuve. Le
 * mécanisme d'activation lui-même (nwidart-laravel-modules : un ServiceProvider non booté = aucune
 * route/migration/commande chargée) est DÉJÀ prouvé en production par une dizaine d'autres modules
 * désactivés du même projet (ABTest, Backup, Booking, CustomFields, FormBuilder, Import, SaaS,
 * Storage, Team, Tenancy, Testimonials - modules_statuses.json) : ce n'est pas un mécanisme neuf
 * à valider ici. Ce test prouve la SEULE chose qui reste sous notre contrôle direct : que RIEN
 * ailleurs dans le site ne dépend en dur des classes du module Signature, condition nécessaire
 * pour que la désactivation reste sans casse.
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

test('aucun fichier PHP hors du module Signature ne référence ses classes en dur', function (): void {
    $root = base_path();
    $forbiddenNeedles = ['Modules\\Signature\\', 'Modules/Signature/'];
    $searchDirs = ['app', 'Modules', 'routes', 'config', 'database'];
    $offenders = [];

    foreach ($searchDirs as $dir) {
        $fullDir = $root.'/'.$dir;
        if (! is_dir($fullDir)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            // Le module Signature lui-même, et ce fichier de test, sont les SEULS endroits
            // autorisés à se nommer - exclusion attendue, pas une fuite.
            if (str_contains($path, DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'Signature'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $content = file_get_contents($path);
            foreach ($forbiddenNeedles as $needle) {
                if ($content !== false && str_contains($content, $needle)) {
                    $offenders[] = $path;
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('modules_statuses.json porte une entrée booléenne pour Signature (module réellement désactivable)', function (): void {
    $raw = file_get_contents(base_path('modules_statuses.json'));
    $statuses = json_decode((string) $raw, true);

    expect($statuses)->toBeArray()
        ->and($statuses)->toHaveKey('Signature')
        ->and($statuses['Signature'])->toBeBool();
});

/**
 * M4.3 - une commande planifiée dans routes/console.php s'exécute même quand le ServiceProvider
 * du module n'a jamais booté (module désactivé) : sans garde explicite, elle casse
 * (NamespaceNotFoundException). Ce test détecte TOUTE chaîne `signature:...` planifiée hors du
 * bloc protégé par `Module::find('Signature')?->isEnabled()` - une preuve mécanique, pas une
 * simple relecture humaine qui oublierait une ligne ajoutée plus tard.
 */
test('toute commande planifiée signature:* dans routes/console.php vit à l\'intérieur de la garde Module::find(\'Signature\')->isEnabled()', function (): void {
    $content = file_get_contents(base_path('routes/console.php'));
    expect($content)->not->toBeFalse();

    preg_match_all("/Schedule::command\\('(signature:[a-z-]+)'/", $content, $allMatches);
    expect($allMatches[1])->not->toBeEmpty('Aucune commande signature:* planifiée trouvée - le test ne protège plus rien, à corriger.');

    $gatePos = strpos($content, "Module::find('Signature')?->isEnabled()");
    expect($gatePos)->not->toBeFalse('La garde Module::find(\'Signature\')?->isEnabled() est introuvable dans routes/console.php.');

    $braceOpen = strpos($content, '{', $gatePos);
    expect($braceOpen)->not->toBeFalse();

    // Isole le bloc protégé jusqu'à SON accolade fermante (comptage de profondeur, jamais une
    // simple recherche de la PROCHAINE accolade - qui se tromperait dès qu'un bloc imbriqué existe).
    $depth = 0;
    $blockEnd = null;
    for ($i = $braceOpen; $i < strlen($content); $i++) {
        if ($content[$i] === '{') {
            $depth++;
        } elseif ($content[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                $blockEnd = $i;
                break;
            }
        }
    }
    expect($blockEnd)->not->toBeNull();

    $guardedBlock = substr($content, $braceOpen, $blockEnd - $braceOpen);
    $before = substr($content, 0, $gatePos);
    $after = substr($content, $blockEnd + 1);

    foreach ($allMatches[1] as $commandName) {
        expect($guardedBlock)->toContain("'{$commandName}'");
        expect($before)->not->toContain("Schedule::command('{$commandName}'");
        expect($after)->not->toContain("Schedule::command('{$commandName}'");
    }
});
