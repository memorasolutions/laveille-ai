<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 78 (2026-07-27) : passe adversariale fraîche après le lot round 77 (newsletter-widget +
// helps/techniqueHints/diagnostic JS + accessibilité clavier indicateur d'étapes). 2 manques réels
// corrigés (sur 3 rapportés - voir décision de périmètre ci-dessous) :
//
// 1. public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - 3 chaînes UI codées en
//    dur en français, jamais pontées via window.promptBuilderConfig.i18n (contrairement à tout le
//    reste du fichier) : le titre par défaut d'une carte perso ("Nouvelle carte"), le titre de repli
//    si le champ est vidé ("Carte sans titre"), et le message de la modale de confirmation de
//    suppression ("Supprimer cette carte ?"). Fixé en ajoutant i18n.newCardTitle/untitledCard/
//    deleteCardConfirm côté Blade (+ repli français côté JS, même pattern que round 76-77).
// 2. constructeur-prompts.blade.php:709-744 (bloc $_swApp / JSON-LD SoftwareApplication) - name,
//    description et les 6 entrées de featureList étaient des littéraux PHP français JAMAIS passés
//    par __(), contrairement au reste du fichier (12 rounds d'audit i18n dédiés). Fixé en les
//    enveloppant de __().
//
// DÉCISION DE PÉRIMÈTRE (3e finding du round 78, non implémenté) : le modèle Tool (Modules/Tools)
// n'a AUCUNE colonne traduisible ($tool->name/$tool->description restent figés en français quelle
// que soit la locale, affectant le <title>/<h1>/bouton Partager). Vérifié indépendamment : ce gap
// affecte les 17 lignes de la table `tools` de façon IDENTIQUE - ce n'est pas une régression propre
// à constructeur-prompts, mais un gap architectural site-wide préexistant, partagé par tous les
// outils déjà en production. Le corriger nécessiterait une migration de colonnes (varchar/text ->
// JSON), l'ajout du trait Spatie\Translatable\HasTranslations, un backfill des 17 lignes existantes,
// et un audit de tous les usages bruts (where('name', ...), tri, recherche, sitemap, flux) à travers
// tout le module Tools - un changement de portée fondamentalement différente des ~78 rounds
// précédents (qui ont tous corrigé des `__()` manquants, jamais une migration de schéma). Non
// implémenté dans ce round ; à traiter comme une décision séparée si l'utilisateur le souhaite.
//
// De même, 'inLanguage' => 'fr-CA' du bloc JSON-LD reste EN DUR (non traduit) : c'est la convention
// appliquée telle quelle sur TOUS les blocs JSON-LD du site (Books, Dictionary, SEO, Academy -
// vérifié par grep), la changer uniquement ici créerait une incohérence plutôt que la résoudre.

it('has English translations for the JS custom-card strings (round 78)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Nouvelle carte',
        'Carte sans titre',
        'Supprimer cette carte ?',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }

    expect($en['Nouvelle carte'])->toBe('New card');
    expect($en['Carte sans titre'])->toBe('Untitled card');
    expect($en['Supprimer cette carte ?'])->toBe('Delete this card?');
});

// Round 152 (2026-08-02, passe adversariale) : les 2 chaînes JSON-LD ci-dessous décrivaient encore
// l'ancien accordéon (« repliés par défaut ») après son retrait au round 152 - le texte de vitrine
// (SEO/AEO) avait divergé de la réalité de l'interface. Mises à jour dans le Blade pour dire « en
// blocs toujours visibles » ; ce test re-ancré sur les nouvelles chaînes, même exigence (traduction
// anglaise réelle, pas une clé identique).
it('has English translations for the JSON-LD SoftwareApplication name/description/featureList (round 78, re-ancré round 152)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Constructeur de prompts IA',
        "Outil gratuit et interactif pour créer des prompts optimisés en partant de votre objectif (rédiger, résumer, analyser, apprendre...), avec réglages avancés en blocs toujours visibles (rôle de l'IA, audience, format de sortie). Compatible ChatGPT, Claude, Gemini, Mistral et tous les LLMs. Sauvegarde compte ou navigateur, partage natif, mode plein écran.",
        "Cartes d'objectifs cliquables (rédiger, résumer, analyser, apprendre...) pour démarrer sans jargon",
        "Réglages utiles regroupés en blocs toujours visibles (rôle de l'IA, verbe, format, exemples, contraintes)",
        'Sauvegarde locale (navigateur) ou compte utilisateur',
        'Partage natif (Web Share API) et copier-coller',
        'Mode plein écran sans distraction',
        'Compatible ChatGPT, Claude, Gemini, Mistral, DeepSeek, Qwen, Llama',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('the JS file falls back to window.promptBuilderConfig.i18n for the 3 custom-card strings (round 78)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("i18nNewCard.newCardTitle || 'Nouvelle carte'");
    expect($js)->toContain("i18nUntitled.untitledCard || 'Carte sans titre'");
    expect($js)->toContain("i18nDelete.deleteCardConfirm || 'Supprimer cette carte ?'");
});

it('injects newCardTitle/untitledCard/deleteCardConfirm i18n translated on the real page in EN locale (round 78)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('newCardTitle: "New card"');
    expect($html)->toContain('untitledCard: "Untitled card"');
    expect($html)->toContain('deleteCardConfirm: "Delete this card?"');
});

it('renders the JSON-LD SoftwareApplication block translated in EN locale on the real page (round 78)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Extraction du bloc JSON-LD pour vérifier son contenu réel (json_encode échappe les accents
    // en \uXXXX - cf. piège documenté aux rounds précédents, on cherche l'anglais, pas l'accent FR).
    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
    expect($matches)->not->toBeEmpty();

    $jsonLdBlocks = [];
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $allMatches);
    foreach ($allMatches[1] as $block) {
        $decoded = json_decode($block, true);
        if ($decoded && ($decoded['@type'] ?? null) === 'SoftwareApplication') {
            $jsonLdBlocks[] = $decoded;
        }
    }
    expect($jsonLdBlocks)->not->toBeEmpty();
    $swApp = $jsonLdBlocks[0];

    expect($swApp['name'])->toBe('AI Prompt Builder');
    expect($swApp['description'])->toContain('Free, interactive tool');
    expect($swApp['featureList'])->toContain('Distraction-free full-screen mode');
    // 'inLanguage' reste fr-CA en dur par décision délibérée (convention site-wide, voir commentaire
    // en tête de fichier) - ne PAS s'attendre à 'en-CA' ici.
    expect($swApp['inLanguage'])->toBe('fr-CA');
});
