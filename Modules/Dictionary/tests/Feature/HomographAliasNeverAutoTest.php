<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Deux faux liens MESURÉS en production le 2026-08-29, même famille de défaut que Codex (voir
 * CodexAliasAutoLinkTest.php) mais deux causes distinctes dans loadTerms() :
 *
 *  1. Sur une actualité de journalisme, « CNN » (réseau de télévision) était lié quatre fois vers
 *     /glossaire/reseau-convolutif. Cause : extractQualifierAliases('Réseau convolutif (CNN)')
 *     dérive « CNN » comme alias, exactement comme conçu (voir son docblock) - CNN porte, hors de
 *     ce site, un second sens que le glossaire ignore.
 *  2. Sur une actualité de droit, « une requête en rejet » était lié vers /glossaire/prompt. Cause :
 *     la migration 2026_07_23_160000_add_requete_alias_to_prompt_term.php a curé « requête » et
 *     « requêtes » comme alias de « prompt » - vrai en contexte IA, mais « requête » est un nom
 *     commun français omniprésent hors de ce contexte.
 *
 * Correctif : GlossaryLinkifier::ALIAS_NEVER_AUTO (voir son docblock) - liste de blocage vérifiée
 * au moment où un ALIAS (curé, dérivé d'un qualifier, ou variante morphologique) entre dans
 * $terms, jamais le nom PRINCIPAL d'une fiche. Reproduit ICI la configuration exacte des fiches
 * réelles (aucune des deux n'existe dans cette suite : « reseau-convolutif » n'est dans AUCUN
 * seeder/migration versionné - créée directement en base de production - et « prompt » vient du
 * seeder original DictionarySeeder.php sans les alias posés ensuite par migration), même patron
 * que CodexAliasAutoLinkTest.php : appel PUBLIC GlossaryLinkifier::linkify(), jamais la réflexion
 * bas niveau de GlossaryLinkifierTest.php.
 *
 * Les tests GAN et « réseau convolutif » (sans CNN) verrouillent la frontière : élargir une
 * exclusion casse silencieusement les termes voisins (mesuré sur ce projet le 2026-08-27) - CNN
 * doit rester bloqué SEUL, ni son propre terme de base, ni un sigle acronyme voisin (GAN), ni le
 * mot « prompt » lui-même ne doivent perdre leur auto-lien légitime.
 *
 * Cas 4, ajouté le 2026-08-29 (mesure distincte, sur 900 pages de production) : l'alias curé
 * « dos » de la fiche « Déni de service (DoS) » a posé 3 liens, les trois faux (sac à dos, vue de
 * dos, objet porté sur le dos - trois usages ordinaires du mot français, aucun rapport avec la
 * cybersécurité). Bloqué par le même ALIAS_NEVER_AUTO, avec un coût assumé et documenté dans son
 * propre docblock : la comparaison insensible à la casse bloque aussi l'alias dérivé « DoS »
 * (qualifier de « Déni de service (DoS) », extractQualifierAliases()) - seule la base « déni de
 * service », vérifiée ci-dessous, reste un point d'entrée automatique vers la fiche.
 *
 * Cas 5, ajouté le 2026-08-30 : variante INÉDITE du même défaut, cette fois sur la BASE d'un
 * qualifier plutôt que sur le qualifier lui-même. La fiche réelle « Mistral (Le Chat) » (produit
 * de clavardage) dérive sa base « Mistral » de façon INCONDITIONNELLE via
 * extractQualifierAliases() - QUALIFIER_ORGANISATION (voir son docblock, correctif du
 * 2026-08-23 pour « Gemini (Google) ») ne protège QUE le qualifier entre parenthèses, jamais la
 * base. Ici la base EST le nom du fabricant : chaque mention de « Mistral » au sens de
 * l'entreprise ou de sa famille de modèles se retrouvait donc liée vers la fiche du seul produit
 * Le Chat (mesuré sur 24 pages de production : 13 pages, 53 liens, quasi tous au sens éditeur).
 * Une fiche « Mistral » (l'éditeur) a été créée séparément avec « Mistral » comme nom PRINCIPAL -
 * mais son propre nom, une fois passé par extractMorphologicalAliases(), dérive à son tour une
 * variante minuscule « mistral » qui hérite de la même match_strategy (case_sensitive) : sans
 * blocage, cette variante aurait pu lier tout « mistral » minuscule du site, y compris le nom
 * commun français (le vent du sud de la France) que l'entreprise revendique comme origine de son
 * propre nom. Un seul ajout à ALIAS_NEVER_AUTO neutralise les deux chemins.
 */

use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers (préfixe han = Homograph Alias Never-auto) ────────────────────────

function hanTerm(string $name, string $slug, array $aliases = [], string $matchStrategy = 'loose'): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $uniqueSlug = $slug.'-'.uniqid();

    return Term::create([
        'name' => [$locale => $name, 'fr' => $name],
        'slug' => [$locale => $uniqueSlug, 'fr' => $uniqueSlug],
        'definition' => [$locale => 'Définition de test pour '.$name.'.', 'fr' => 'Définition de test pour '.$name.'.'],
        'is_published' => true,
        'match_strategy' => $matchStrategy,
        'aliases' => $aliases,
    ]);
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

// ── Cas 1 : CNN (réseau de télévision) vs Réseau convolutif (CNN) ─────────────

it('ne lie PAS "CNN" - texte réel qui a produit le faux lien en production (journalisme, pas IA)', function () {
    hanTerm('Réseau convolutif (CNN)', 'reseau-convolutif-test');

    $html = GlossaryLinkifier::linkify('<p>Le réseau CNN rapportait le 22 août 2026 des informations sur des figurants payés.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/reseau-convolutif');
});

it('lie toujours "réseau convolutif" (la base, sans CNN) dans un vrai texte technique', function () {
    $terme = hanTerm('Réseau convolutif (CNN)', 'reseau-convolutif-test');

    $html = GlossaryLinkifier::linkify('<p>Le réseau convolutif reste une architecture classique en vision par ordinateur.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug);
});

it('lie toujours un sigle acronyme VOISIN (GAN) - la frontière ne doit bloquer que CNN', function () {
    $terme = hanTerm('Réseau antagoniste génératif (GAN)', 'reseau-antagoniste-generatif-test');

    $html = GlossaryLinkifier::linkify('<p>Un GAN peut générer des images synthétiques très réalistes.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>GAN</a>');
});

// ── Cas 2 : requête (nom commun juridique) vs alias curé de Prompt ────────────

it('ne lie PAS "requête" dans "une requête en rejet" - texte réel qui a produit le faux lien (droit, pas IA)', function () {
    hanTerm('Prompt', 'prompt-test', aliases: ['requête', 'requêtes']);

    $html = GlossaryLinkifier::linkify('<p>Une requête en rejet est une manoeuvre défensive utilisée par la défense.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/prompt');
});

it('lie toujours "prompt" lui-même - le nom principal de la fiche reste intact', function () {
    $terme = hanTerm('Prompt', 'prompt-test', aliases: ['requête', 'requêtes']);

    $html = GlossaryLinkifier::linkify('<p>Écris un bon prompt pour obtenir une réponse précise du modèle.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>prompt</a>');
});

// ── Cas 3 : témoin (personne qui témoigne) vs alias curé de Cookie, repéré par l'audit ─

it('ne lie PAS "témoin" dans un contexte judiciaire - même motif, corrigé par précaution', function () {
    hanTerm('Témoin de connexion (cookie)', 'cookie-test', aliases: ['cookie', 'cookies', 'témoin', 'témoin de connexion']);

    $html = GlossaryLinkifier::linkify('<p>Le témoin a déclaré ne rien savoir des événements survenus ce soir-là.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/cookie-test');
});

it('lie toujours "cookie" - le mot technique reste un alias valide du même terme', function () {
    $terme = hanTerm('Témoin de connexion (cookie)', 'cookie-test', aliases: ['cookie', 'cookies', 'témoin', 'témoin de connexion']);

    $html = GlossaryLinkifier::linkify('<p>Ce site dépose un cookie pour mémoriser vos préférences de langue.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug);
});

// ── Cas 4 : dos (sac à dos, vue de dos, sur le dos) vs alias curé de Déni de service (DoS) ────

it('ne lie PAS "dos" dans "sac à dos" - un des 3 faux liens mesurés sur 900 pages de production', function () {
    hanTerm('Déni de service (DoS)', 'deni-de-service-test', aliases: ['dos', 'ddos', 'déni de service distribué', 'attaque par déni de service']);

    $html = GlossaryLinkifier::linkify('<p>Cette fiche pour enfants illustre un sac à dos transparent rempli de crayons de couleur.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/deni-de-service');
});

it('ne lie PAS "dos" dans une vue "de dos" - deuxième faux lien mesuré en production (personnage 3D)', function () {
    hanTerm('Déni de service (DoS)', 'deni-de-service-test', aliases: ['dos', 'ddos', 'déni de service distribué', 'attaque par déni de service']);

    $html = GlossaryLinkifier::linkify('<p>Le personnage 3D est modélisé avec un grand soin, même vu de dos.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/deni-de-service');
});

it('ne lie PAS "dos" pour un objet porté "sur son dos" - troisième faux lien mesuré en production (skieur)', function () {
    hanTerm('Déni de service (DoS)', 'deni-de-service-test', aliases: ['dos', 'ddos', 'déni de service distribué', 'attaque par déni de service']);

    $html = GlossaryLinkifier::linkify('<p>Le skieur descend la piste avec son équipement attaché sur son dos.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/deni-de-service');
});

it('lie toujours "déni de service" (la base, sans DoS) dans un vrai texte de cybersécurité', function () {
    $terme = hanTerm('Déni de service (DoS)', 'deni-de-service-test', aliases: ['dos', 'ddos', 'déni de service distribué', 'attaque par déni de service']);

    $html = GlossaryLinkifier::linkify('<p>Un déni de service a rendu le site inaccessible pendant plusieurs heures cette nuit-là.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug);
});

// ── Cas 5 : Mistral (éditeur) vs base dérivée de Mistral (Le Chat), et vs le vent du même nom ──

it('ne lie PAS "Mistral" quand seul le produit "Mistral (Le Chat)" existe - reproduit le défaut mesuré en production', function () {
    hanTerm('Mistral (Le Chat)', 'mistral-le-chat-test');

    $html = GlossaryLinkifier::linkify('<p>Mistral a annoncé une nouvelle levée de fonds menée par ASML.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/mistral-le-chat');
});

it('lie "Mistral" vers la fiche ÉDITEUR (nom principal) quand les deux fiches coexistent, jamais vers le produit', function () {
    hanTerm('Mistral (Le Chat)', 'mistral-le-chat-test');
    $editeur = hanTerm('Mistral', 'mistral-test', aliases: ['Mistral AI'], matchStrategy: 'case_sensitive');

    $html = GlossaryLinkifier::linkify('<p>Mistral a annoncé une nouvelle levée de fonds menée par ASML.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$editeur->slug)
        ->and($html)->not->toContain('/glossaire/mistral-le-chat-test')
        ->and($html)->toContain('>Mistral</a>');
});

it('ne lie PAS "mistral" minuscule (le vent du sud de la France) même quand la fiche éditeur existe', function () {
    hanTerm('Mistral', 'mistral-test', aliases: ['Mistral AI'], matchStrategy: 'case_sensitive');

    $html = GlossaryLinkifier::linkify('<p>Un mistral soufflait fort sur la Provence ce jour-là.</p>');

    expect($html)->not->toContain('glossary-link');
});

it('lie toujours "Mistral AI" en entier (alias curé), jamais fragmenté avec un lien "AI" séparé', function () {
    $editeur = hanTerm('Mistral', 'mistral-test', aliases: ['Mistral AI'], matchStrategy: 'case_sensitive');

    $html = GlossaryLinkifier::linkify('<p>Mistral AI a levé 1,7 milliard d\'euros en 2025.</p>');

    expect($html)->toContain('>Mistral AI</a>')
        ->and($html)->toContain('/glossaire/'.$editeur->slug);
});
