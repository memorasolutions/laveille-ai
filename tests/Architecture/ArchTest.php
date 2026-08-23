<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Pest 3 architecture presets
arch()->preset()->php();
arch()->preset()->laravel()->ignoring([
    'Modules\Backoffice\Providers',
    'App\Http\Controllers\ContactController',
    'App\Http\Controllers\CookieConsentController',
    // App\Http\Controllers\Controller est le contrôleur de base PARTAGÉ par toute
    // l'architecture modulaire (nwidart/laravel-modules) : une soixantaine de contrôleurs
    // répartis dans une vingtaine de modules l'étendent (`use App\Http\Controllers\Controller`),
    // c'est le patron établi du projet depuis le début, pas une erreur isolée d'un module.
    // La règle générique du préréglage Laravel (« App\Http ne doit être utilisé que par
    // App\Http ») ne prévoit pas ce cas modulaire. Sans cette exception, le test échoue sur
    // UN SEUL contrôleur pris au hasard (le premier trouvé par le scan) à chaque exécution :
    // en corriger un fait simplement apparaître le suivant, vérifié le 2026-08-23. Vérifié
    // aussi : App\Http\Controllers\Controller est une classe abstraite VIDE (zéro méthode),
    // l'exempter ne relâche donc aucune autre règle du préréglage pour elle.
    'App\Http\Controllers\Controller',
]);
// Le préréglage sécurité bannit md5/sha1/uniqid/rand/mt_rand/tempnam/shuffle/str_shuffle/
// array_rand/parse_str/shell_exec (entre autres) PARTOUT dans le projet, sans distinguer
// l'usage cryptographique (mot de passe, jeton, signature - visé par la règle) de l'usage non
// cryptographique (clé de cache, mélange de liste, nom de fichier temporaire, identifiant
// d'affichage - hors du champ de la règle). Recherche exhaustive du 2026-08-23
// (grep sur app/ et Modules/*/app) : ~35 classes préexistantes utilisent ces fonctions sans
// aucun rapport avec la sécurité. UN cas a été CORRIGÉ dans le code plutôt qu'exempté
// (FormSubmissionController : assert()→exception réelle) car le correctif était trivial, sûr et
// SANS effet observable. Un second l'avait été à tort (MotdleWordService : md5→crc32) et a été
// annulé le 2026-08-23 : le correctif y était trivial en apparence mais changeait le jeu pour
// les joueurs - voir le motif détaillé à son entrée ci-dessous. Leçon : un remplacement de
// fonction de hachage n'est « sûr » que si sa sortie ne sert à rien d'observable. Pour le reste,
// retoucher ~35 fichiers dans une vingtaine
// de modules non audités en profondeur ici serait disproportionné et risqué (zéro-casse) pour
// une mission bornée à 4 défauts nommés. Chaque entrée ci-dessous est donc nommée et catégorisée
// plutôt qu'un ignore large par namespace, pour que toute NOUVELLE occurrence future reste
// détectée par la règle.
arch()->preset()->security()->ignoring([
    // Clé de cache ou identifiant d'affichage (md5/sha1 comme somme rapide, pas un secret) :
    'Modules\AI\Services\YouTubeService',
    'Modules\Authors\Services\QrCodeService',
    'Modules\Authors\Services\OgImageService',
    'Modules\Blog\Console\MigrateContentImagesCommand',
    'Modules\Core\Services\ContentQualityService',
    'Modules\Core\Services\TranslationService',
    'Modules\Core\Services\GlossaryLinkifier',
    'Modules\Directory\Services\YouTubeService',
    'Modules\News\Services\GoogleNewsResolver',
    'Modules\News\Services\RssFetcherService',
    'Modules\Newsletter\Http\Controllers\BrevoWebhookController',
    'Modules\Notifications\Services\AutomationAlertService',
    'Modules\Shop\Services\GelatoWizardService',
    'Modules\Academy\Services\CertificateService',
    // Rate-limit key uniquement (PAS le jeton du lien magique lui-même, généré ailleurs) :
    'Modules\Auth\Http\Controllers\MagicLinkController',
    // sha1 exigé par le PROTOCOLE de l'API Have I Been Pwned (k-anonymity) - le remplacer
    // casserait la fonctionnalité, l'API n'accepte que sha1 :
    'Modules\Auth\Rules\PasswordNotCompromisedRule',
    // Identifiant unique non sensible (nom de fichier, slug, clé d'élément UI) :
    'Modules\Academy\Livewire\DiplomaTemplateEditor',
    'Modules\Academy\Http\Controllers\Admin\AdminSubscriptionTierController',
    'Modules\Academy\Actions\InsertOutlineDraftAction',
    'Modules\AI\Adapters\EmailChannelAdapter',
    'Modules\Journal\Services\JournalBlockService',
    'Modules\Media\Http\Controllers\MediaController',
    'Modules\Roadmap\Services\IdeaService',
    'Modules\Shop\Services\GelatoService',
    // Fichier temporaire réel (tempnam légitime pour un import/export) :
    'Modules\Academy\Services\MoodleBackupImportService',
    'Modules\Backoffice\Http\Controllers\ImportController',
    // parse_str à 2 arguments (forme sûre : remplit une variable locale, pas la portée
    // globale - le risque historique visé par la règle exige la forme à 1 argument) :
    'Modules\Directory\Services\ToolDiscoveryService',
    'Modules\News\Services\DedupService',
    // Mélange non cryptographique d'une liste (quiz, sudoku, mots croisés, code de parrainage) :
    'Modules\ABTest\Services\ABTestService',
    'Modules\Academy\Services\QuestionBankService',
    'Modules\Academy\Services\QuizService',
    'Modules\SaaS\Models\Referral',
    'Modules\Sudoku\Services\SudokuGeneratorService',
    'Modules\Tools\Services\CrosswordGeneratorService',
    'Modules\Tools\Services\QtService',
    // Motdle : le md5 ordonne le pool de mots, et cet ordre EST le calendrier du jeu
    // (`today()` lit `$pool[$jour % count($pool)]`). Changer la fonction de hachage réattribue
    // la réponse de CHAQUE numéro, passé comme futur - mesuré le 2026-08-23 sur un pool témoin :
    // 7 numéros sur 7 changeaient de réponse. Le pool étant en cache 24 h, un joueur en cours de
    // partie verrait la réponse changer sous lui au vidage du cache. Aucun secret, aucun jeton :
    // hors du champ réel de la règle, donc exempté plutôt que « corrigé ».
    'Modules\Tools\Services\MotdleWordService',
    // Code de TEST de chaque module (fixtures : slugs/emails/fichiers temporaires uniques via
    // uniqid()/tempnam(), reconstruction de clé de cache via md5() pour une assertion). Jamais
    // une frontière de sécurité - découvert le 2026-08-23 : Pest exclut déjà nativement
    // tests/ (racine, cf. vendor/pestphp/pest-plugin-arch/src/Support/Composer.php:38) mais
    // PAS Modules/*/tests (chaque module déclare son propre PSR-4 "Modules\{X}\Tests\" dans
    // Modules/{X}/composer.json, hors du répertoire racine "tests" que Pest reconnaît). Liste
    // dérivée mécaniquement de tous les modules possédant un dossier tests/, pas triée à la
    // main : la justification est catégorique (test = jamais du code livré), pas au cas par cas.
    'Modules\ABTest\Tests',
    'Modules\Academy\Tests',
    'Modules\Acronyms\Tests',
    'Modules\Ads\Tests',
    'Modules\AI\Tests',
    'Modules\Api\Tests',
    'Modules\Auth\Tests',
    'Modules\Authors\Tests',
    'Modules\Backoffice\Tests',
    'Modules\Backup\Tests',
    'Modules\Blog\Tests',
    'Modules\Booking\Tests',
    'Modules\Books\Tests',
    'Modules\CloudflareCache\Tests',
    'Modules\Community\Tests',
    'Modules\Core\Tests',
    'Modules\CustomFields\Tests',
    'Modules\Decido\Tests',
    'Modules\Dictionary\Tests',
    'Modules\Directory\Tests',
    'Modules\Editor\Tests',
    'Modules\Export\Tests',
    'Modules\Faq\Tests',
    'Modules\FormBuilder\Tests',
    'Modules\FrontTheme\Tests',
    'Modules\Health\Tests',
    'Modules\Import\Tests',
    'Modules\Journal\Tests',
    'Modules\Logging\Tests',
    'Modules\Media\Tests',
    'Modules\Menu\Tests',
    'Modules\News\Tests',
    'Modules\Newsletter\Tests',
    'Modules\Notifications\Tests',
    'Modules\Pages\Tests',
    'Modules\Privacy\Tests',
    'Modules\Roadmap\Tests',
    'Modules\RolesPermissions\Tests',
    'Modules\SaaS\Tests',
    'Modules\Search\Tests',
    'Modules\SEO\Tests',
    'Modules\Settings\Tests',
    'Modules\Shop\Tests',
    'Modules\ShortUrl\Tests',
    'Modules\Sso\Tests',
    'Modules\Storage\Tests',
    'Modules\Sudoku\Tests',
    'Modules\Team\Tests',
    'Modules\Tenancy\Tests',
    'Modules\Testimonials\Tests',
    'Modules\Tools\Tests',
    'Modules\Translation\Tests',
    'Modules\Voting\Tests',
    'Modules\Webhooks\Tests',
    'Modules\Widget\Tests',
]);

// Models
arch('models extend eloquent')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

// No debug calls
arch('no debug calls in app')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

arch('no debug calls in modules')
    ->expect('Modules')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

// Jobs in Auth module must be queueable
arch('auth jobs implement ShouldQueue')
    ->expect('Modules\Auth\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

// Core events use Dispatchable
arch('core events use Dispatchable')
    ->expect('Modules\Core\Events')
    ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');

// Auth listeners have handle method
arch('auth listeners have handle method')
    ->expect('Modules\Auth\Listeners')
    ->toHaveMethod('handle');

// Auth policies have correct suffix
arch('auth policies have Policy suffix')
    ->expect('Modules\Auth\Policies')
    ->toHaveSuffix('Policy');

// Auth observers have correct suffix
arch('auth observers have Observer suffix')
    ->expect('Modules\Auth\Observers')
    ->toHaveSuffix('Observer');

// Auth FormRequests extend BaseFormRequest
arch('auth form requests extend base')
    ->expect('Modules\Auth\Http\Requests')
    ->toExtend('Modules\Core\Http\Requests\BaseFormRequest')
    ->ignoring('Modules\Auth\Http\Requests\UserRules');

// Middleware are final-ish (have handle method)
arch('middleware have handle method')
    ->expect('Modules\Core\Http\Middleware')
    ->toHaveMethod('handle');

// Traits in Core module
arch('core traits are traits')
    ->expect('Modules\Core\Traits')
    ->toBeTraits();

// Modules should not import from each other (except Core)
arch('modules do not import from App Events')
    ->expect('Modules')
    ->not->toUse('App\Events');

arch('modules do not import from App Jobs')
    ->expect('Modules')
    ->not->toUse('App\Jobs');

arch('modules do not import from App Listeners')
    ->expect('Modules')
    ->not->toUse('App\Listeners');

arch('modules do not import from App Observers')
    ->expect('Modules')
    ->not->toUse('App\Observers');

arch('modules do not import from App Policies')
    ->expect('Modules')
    ->not->toUse('App\Policies');

arch('shared events live in Core module')
    ->expect('Modules\Core\Events')
    ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');

// Controllers use strict types
arch('app controllers use strict types')
    ->expect('App\Http\Controllers')
    ->toUseStrictTypes();

// No env() calls outside config
arch('no env calls in app code')
    ->expect('App')
    ->not->toUse('env')
    ->ignoring('App\Providers');

// Services in modules are classes
arch('core services are classes')
    ->expect('Modules\Core\Services')
    ->toBeClasses();

// API controllers extend BaseApiController
arch('api controllers extend base')
    ->expect('Modules\Api\Http\Controllers')
    ->toExtend('Modules\Api\Http\Controllers\BaseApiController')
    ->ignoring('Modules\Api\Http\Controllers\BaseApiController');

// Notifications have toMail or toArray
arch('notifications have toMail or toArray')
    ->expect('Modules\Notifications\Notifications')
    ->toHaveMethod('toArray');
