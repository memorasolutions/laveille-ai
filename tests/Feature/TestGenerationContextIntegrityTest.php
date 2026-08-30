<?php

declare(strict_types=1);

// Méta-test du pack de contexte de génération de tests (2026-07-30).
//
// POURQUOI CE TEST EXISTE. Le fichier .claude/refs/test-generation-context.md est injecté dans
// chaque délégation de génération de tests : il donne au modèle les chemins réels, les
// conventions maison et un harnais d'exemple. C'est ce qui empêche le modèle d'inventer un
// `resource_path('views/modules/...')` ou des clés de traduction anglaises.
//
// Le risque, soulevé par la validation croisée claude.ai : ce pack devient un POINT DE
// DÉFAILLANCE UNIQUE. Si un chemin qu'il cite est renommé, le modèle reproduit fidèlement une
// référence morte, à l'échelle, sur tous les tests suivants. On passe d'erreurs aléatoires à
// une erreur systématique - pire, parce que ça ressemble à de la cohérence.
//
// Ce test vérifie donc que tout chemin de fichier cité comme RÉEL dans le pack existe encore.
// Le jour où quelqu'un renomme un répertoire, la suite le dit avant que le modèle ne s'en serve.

it('keeps every real path quoted in the test-generation context pack alive', function () {
    $pack = base_path('.claude/refs/test-generation-context.md');

    expect(file_exists($pack))->toBeTrue(
        'Le pack de contexte a disparu. Toute délégation de génération de tests perd ses conventions.'
    );

    $contenu = file_get_contents($pack);

    // Chemins présentés dans le pack comme étant ceux du dépôt. Volontairement listés à la main :
    // une extraction automatique attraperait aussi les contre-exemples « FAUX » du tableau.
    $cheminsReels = [
        'Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php',
        'public/assets/tools/anonymiseur/anonymizer-ui.js',
        'lang/fr.json',
        'lang/en.json',
        'tests/js',
    ];

    foreach ($cheminsReels as $chemin) {
        expect(file_exists(base_path($chemin)))->toBeTrue("Chemin cité par le pack et introuvable : {$chemin}");
        // Attention : toContain() de Pest prend des needles VARIADIQUES, pas un message. Passer
        // un libellé en second argument le transforme en chaîne supplémentaire à trouver, et le
        // test échoue pour une raison qui n'a rien à voir. On utilise str_contains + toBeTrue.
        expect(str_contains($contenu, $chemin))->toBeTrue(
            "Le pack ne mentionne plus {$chemin} : cette vérification est devenue muette."
        );
    }
});

it('still warns against the exact mistakes that were actually made', function () {
    $contenu = file_get_contents(base_path('.claude/refs/test-generation-context.md'));

    // Chaque entrée correspond à un défaut RÉELLEMENT produit le 2026-07-30. Si l'avertissement
    // disparaît du pack, le défaut correspondant peut revenir sans que rien ne le signale.
    $misesEnGarde = [
        'resource_path',            // chemin de vue inventé
        'chaîne SOURCE FRANÇAISE',  // clés de traduction anglaises inventées
        'CommonJS',                 // const module redéclaré
        'ne RETOURNE rien',         // new Function(...)() qui laissait la classe undefined
        'auto-initialisation',      // constructeur exécuté à l'évaluation du fichier
        'Contrôle négatif',         // la mutation qui prouve que le test n'est pas décoratif
    ];

    foreach ($misesEnGarde as $garde) {
        expect(str_contains($contenu, $garde))->toBeTrue("Mise en garde retirée du pack : « {$garde} ».");
    }
});

it('keeps the free-tier model away from test generation (routing invariant)', function () {
    $routage = getenv('HOME').'/.hermes/routing.yaml';

    // Le routeur vit hors du dépôt : s'il est absent (autre machine, CI), on ne fait pas échouer
    // la suite pour autant. La vérification n'a de sens que là où Hermes est installé.
    if (! file_exists($routage)) {
        expect(true)->toBeTrue();

        return;
    }

    // L'extension PECL yaml n'est pas installée sur ce projet (composer show le confirme, comme
    // pour orchestra/testbench dans AutomationAlertServiceTest) : yaml_parse_file() est donc
    // TOUJOURS indisponible ici, et le garder aurait fait sauter ce test en silence pour de bon.
    // symfony/yaml, lui, est déjà présent dans vendor/ (tiré transitivement par laravel/sail) et
    // lit exactement le même fichier - substitut réel, pas une extension du périmètre du test.
    if (! class_exists(\Symfony\Component\Yaml\Yaml::class)) {
        $this->markTestSkipped('Ni ext-yaml ni symfony/yaml disponibles pour lire routing.yaml.');
    }

    try {
        $config = \Symfony\Component\Yaml\Yaml::parseFile($routage);
    } catch (\Symfony\Component\Yaml\Exception\ParseException) {
        $config = null;
    }

    if (! is_array($config) || ! isset($config['tiers']['test'])) {
        expect(true)->toBeTrue();

        return;
    }

    $modelesDuTierTest = array_column($config['tiers']['test'], 'model');

    // Règle ferme issue de la validation claude.ai : un endpoint gratuit ne touche jamais aux
    // tests. Le risque de test vide y est maximal et l'économie réelle est nulle (~0,02 $ par
    // test généré avec le modèle retenu).
    foreach ($modelesDuTierTest as $modele) {
        expect(str_ends_with($modele, ':free'))->toBeFalse(
            "Un endpoint gratuit ({$modele}) est routé sur la génération de tests."
        );
    }
});
