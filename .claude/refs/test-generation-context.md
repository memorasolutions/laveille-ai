# Contexte à injecter dans TOUTE délégation de génération de tests (laveille.ai)

> Bloc unique, rappelé tel quel dans le prompt système de chaque délégation `task_type: test`.
> Ne jamais recopier ces règles en les paraphrasant dans un prompt : pointer vers ce fichier
> et l'injecter intégralement. Une règle qui change ici doit changer partout d'un seul coup.

**Origine (2026-07-30)** : sur 6 délégations réelles, le code applicatif généré était correct
3 fois sur 3, mais les 3 générations de tests avaient des défauts bloquants. La moitié venait
d'une méconnaissance des conventions du projet, l'autre moitié d'erreurs de compétence pure.
Ce fichier élimine la première moitié ; le tier `test` de Hermes et la vérification d'exécution
traitent la seconde.

---

## 1. Chemins réels (les erreurs les plus fréquentes)

| Ce qu'un modèle écrit spontanément | Ce qui est VRAI ici |
|---|---|
| `resource_path('views/modules/tools/constructeur-prompts.blade.php')` | `base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php')` |
| `tests/Feature/MonTest.php` à la racine | `Modules/Tools/tests/Feature/MonTest.php` pour un test de module |
| `app/Models/Tool.php` | `Modules/Tools/app/Models/Tool.php` |

Les vues de module ne sont **jamais** sous `resource_path()`. Toujours `base_path('Modules/...')`.

Chemins réels vérifiés par la suite (voir `tests/Feature/TestGenerationContextIntegrityTest.php`,
qui échoue si l'un d'eux est renommé) :

- `Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php`
- `public/assets/tools/anonymiseur/anonymizer-ui.js`
- `lang/fr.json` et `lang/en.json`
- `tests/js/` pour les tests JavaScript, `Modules/{Module}/tests/Feature/` pour les tests PHP

## 2. Traductions : la clé est la chaîne SOURCE FRANÇAISE

Laravel indexe les traductions JSON par le texte source. `lang/fr.json` et `lang/en.json`
partagent donc **exactement les mêmes clés** ; seule la valeur diffère.

```php
// CORRECT
expect($fr)->toHaveKey('Texte anonymisé inséré dans « %s ».');
expect($en)->toHaveKey('Texte anonymisé inséré dans « %s ».'); // même clé, valeur anglaise

// FAUX - invente une clé anglaise qui n'existe pas
expect($en)->toHaveKey('Anonymized text inserted into "%s".');
```

Invariant du round 117, à vérifier dans tout test qui touche aux traductions :
`expect(array_diff_key($fr, $en))->toBeEmpty();`

## 3. Tests JS : harnais CommonJS

Les fichiers de `public/assets/**` sont du JS navigateur, pas des modules Node. Les tests sont
en `.cjs` dans `tests/js/` et **jsdom n'est pas disponible** : le faux DOM se construit à la main.

```js
// `module` est DÉJÀ une liaison CommonJS dans un .cjs : le redéclarer est une erreur de syntaxe.
const _mod = { exports: {} };

// new Function(...)() ne RETOURNE rien : relire _mod.exports après l'appel.
new Function('document', 'window', 'module', src + '\nmodule.exports = MaClasse;')(
  fakeDocument, fakeWindow, _mod
);
const MaClasse = _mod.exports;

// Ne pas appeler le constructeur si on ne teste qu'une méthode :
const ui = Object.create(MaClasse.prototype);
```

Piège récurrent : plusieurs fichiers d'assets se terminent par un bloc d'auto-initialisation
(`if (document.getElementById('...')) { window.x = new MaClasse(); }`). Il s'exécute au moment de
l'évaluation et fait tourner tout le constructeur. Le retirer avant d'évaluer :

```js
const src = rawSrc.replace(/\n\/\/ Expose l'instance[\s\S]*$/, '\n');
```

Le lot est découvert automatiquement : `npm run test:js` énumère `tests/js/*.test.cjs`.
Aucun fichier à déclarer à la main.

## 4. Convention des tests de round adversarial

Un fichier par round : `Modules/{Module}/tests/Feature/Round{N}AdversarialFixesTest.php`.

En-tête obligatoire : un bloc de commentaires **en français** qui explique le défaut, sa cause
racine, et ce que l'utilisateur voyait par rapport à ce qui se passait vraiment. Ce commentaire
est le livrable principal du test : dans six mois il dira pourquoi la ligne existe.

Structure type :

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round N (date) : titre du défaut.
// ... explication ...

it('...', function () { /* ... */ });

// Toujours terminer par un rendu de page RÉEL, pas seulement des assertions de chaînes :
it('renders the page after the round N fix (real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $this->get('/outils/constructeur-prompts')->assertOk();
});
```

Créer les données via `Tool::firstOrCreate(...)`, pas via `User::factory()->create(['role' => ...])`
(le rôle ne se passe pas ainsi dans ce projet).

## 5. Assertions : viser le comportement, pas la présence d'une chaîne

Un test qui vérifie qu'une sous-chaîne existe dans un fichier reste vert même si la logique est
devenue inerte. Quand c'est possible, **exécuter** le code plutôt que l'inspecter. Quand seule
une assertion structurelle est possible, viser une propriété qui casse si l'intention casse :

```php
// Faible : passe même si l'ordre s'inverse
expect($js)->toContain('activeField = field;');

// Fort : c'est l'ORDRE qui porte la correction
expect(strpos($js, 'activeField = field;'))->toBeGreaterThan(strpos($js, 'toggleBtn.click();'));
```

## 6. Vérification obligatoire avant de livrer un test

1. `php -l` (PHP) ou `node --check` (JS) sur le fichier produit.
2. Exécuter le test : il doit être **vert**.
3. **Contrôle négatif** : casser volontairement le code visé, relancer, le test doit devenir
   **rouge**. Un test qui reste vert sur du code cassé ne prouve rien et doit être réécrit.
4. Restaurer le code, reconfirmer le vert.
5. Régression du périmètre touché avant de cocher terminé.

L'étape 3 n'est pas optionnelle. C'est elle, et elle seule, qui distingue un garde-fou d'un
test décoratif.

## 7. Français et typographie

Commentaires et libellés en français avec **tous** les accents. Le tiret cadratin est interdit :
utiliser `-` ou `:`. Guillemets français `« »` dans les chaînes destinées à l'utilisateur.
