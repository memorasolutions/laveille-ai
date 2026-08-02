<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 76 (2026-07-27) : passe adversariale fraîche. Découverte majeure : les 4 partials partagés
// (header/footer/sidebar/search-palette.blade.php), rendus sur QUASI TOUTE page publique du site,
// ont ~131 clés __() uniques (nav, footer, sidebar, palette de recherche) SANS AUCUNE traduction EN
// dans lang/en.json - seules les 5 aria-labels déjà fixées au round 75 existaient. Extraction
// programmatique exhaustive (regex __() + diff contre lang/en.json, pas un échantillon manuel) sur
// header.blade.php (87 manquantes), footer.blade.php (36), sidebar.blade.php (5),
// search-palette.blade.php (12), + 1 finding hors-partials : le placeholder du textarea "Exemples"
// (constructeur-prompts.blade.php:451) était non traduit alors que son aria-label voisin l'était.
//
// Bug supplémentaire trouvé en vérifiant indépendamment (au-delà du verdict du sous-agent) :
// header.blade.php avait __('Tous les outils ('.$directoryCount.')') et
// __('Glossaire Techno ('.$dictionaryCount.')') - le COMPTE DYNAMIQUE était concaténé DANS la clé
// de traduction elle-même, rendant toute traduction statique impossible à maintenir (la clé change
// à chaque outil/terme ajouté au catalogue). Corrigé en interpolation Laravel standard :
// __('Tous les outils (:count)', ['count' => $directoryCount]).

it('has English translations for the shared header nav keys (round 76)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Actualités', 'Annuaire', 'Apprendre', 'Répertoire techno', 'Glossaire Techno',
        'Classement', 'Collections', 'Communauté', 'Propositions', 'Jouer',
        'Ressources', 'Tous les outils (:count)', 'Glossaire Techno (:count)',
        'Se déconnecter', 'Rechercher (Ctrl+K)',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
    }
});

it('has English translations for the shared footer keys (round 76)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Ressources', 'Toutes les ressources', 'Répertoire techno', 'Glossaire Techno',
        'Statut des services', 'Conditions de vente', 'Cookies', 'Demande de retrait',
        'Plan du site', 'Communauté', 'Classement', 'Modération',
        'Conçu et hébergé par', 'Entreprise canadienne',
        "Certains liens sont des liens d'affiliation. Nous pouvons recevoir une commission sans frais pour vous.",
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
    }
});

it('has English translations for the shared sidebar keys (round 76)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        "Votre veille sur l'IA, les technologies et la transformation numérique au Québec.",
        'Rechercher sur le site',
        'Comment nous aider ?',
        'Contactez-nous pour toute question ou suggestion.',
        'Articles récents',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }

    // Round 76 fix (verified independently) : "Comment nous aider ?" est le titre d'un widget de
    // contact (« comment POUVONS-NOUS vous aider »), pas une demande d'aide de l'utilisateur envers
    // le site - une 1re traduction automatique erronée ("How can you help us?") a été corrigée
    // manuellement avant fusion dans lang/en.json.
    expect($en['Comment nous aider ?'])->toBe('How can we help?');
});

it('has English translations for the shared search-palette keys (round 76)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Rechercher sur le site',
        'Rechercher outils, glossaire, articles…',
        'Commencez à taper',
        'Recherche en cours…',
        "Octopus n'a rien trouvé. Essayez avec un synonyme ou un terme plus court.",
        'Voir tous les résultats',
        'fermer', 'naviguer', 'ouvrir', 'pour rouvrir',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders the shared header/footer/search-palette translated in EN locale on the home page (round 76)', function () {
    $html = $this->withSession(['locale' => 'en'])->get('/')->assertOk()->getContent();

    // Header nav
    expect($html)->toContain('>News<');
    expect($html)->toContain('>Directory<');
    expect($html)->toContain('>Learn<');
    expect($html)->toContain('Tech Directory');
    expect($html)->toContain('Tech Glossary');

    // Footer
    expect($html)->toContain('Service status');
    expect($html)->toContain('Terms of Sale');
    expect($html)->toContain('Designed and hosted by');

    // Search palette
    expect($html)->toContain('Search tools, glossary, articles');

    // French originals must not leak through in EN locale
    expect($html)->not->toContain('>Actualités<');
    expect($html)->not->toContain('>Répertoire techno<');
    expect($html)->not->toContain('Statut des services');
    expect($html)->not->toContain('Conçu et hébergé par');
});

it('renders the dynamic tool/dictionary counts translated without leaking the :count placeholder (round 76)', function () {
    $html = $this->withSession(['locale' => 'en'])->get('/')->assertOk()->getContent();

    expect($html)->not->toContain(':count');
    expect($html)->toMatch('/All tools \(\d+\)/');
    expect($html)->not->toContain('Tous les outils (');
});

it('renders the shared sidebar translated in EN locale (round 76)', function () {
    // Rendu direct du partial (pas via route('blog.index')) : ce dernier déclenche une requête
    // withCount/HAVING pré-existante, non liée à l'i18n, qui échoue sous SQLite (hors périmètre
    // round 76) - view() isole le rendu du partial de ce bug indépendant.
    // Note : 'newsletter.subscribe' existe toujours dans cette app (Modules/Newsletter), donc la
    // branche @if(Route::has('newsletter.subscribe')) rend newsletter-widget, jamais le widget de
    // contact "Comment nous aider ?" - ce dernier reste couvert par le test de clés ci-dessus.
    app()->setLocale('en');
    $html = view('fronttheme::partials.sidebar')->render();

    expect($html)->toContain('Your watch on AI, technology, and digital transformation in Quebec.');
    expect($html)->toContain('Search the site');
    expect($html)->not->toContain('Rechercher sur le site');
    expect($html)->not->toContain("Votre veille sur l'IA");
});

// Étape 9 (2026-08-02) : les 2 tests qui verrouillaient la traduction du placeholder de l'ancien
// champ "Exemples" (few-shot) ont été retirés - ce champ n'existe plus dans la réécriture complète
// du Constructeur de prompts (plan section 10.1 : le few-shot préempli générique a été identifié
// comme un piège qui dégrade la qualité du prompt, retiré des 9 nouveaux gabarits). Les tests
// header/footer/sidebar/search-palette ci-dessus restent valides tels quels (composants partagés,
// non affectés par la réécriture).
