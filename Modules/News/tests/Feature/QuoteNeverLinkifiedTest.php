<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2317. MESURÉ en production le 2026-09-06 : 4 fiches publiées sur 20 portaient un lien
 * d'auto-glossaire À L'INTÉRIEUR d'une citation verbatim (cas capté sur la fiche CCQ,
 * « renseignements personnels » lié vers /glossaire/renseignement-personnel). Une citation est
 * intouchable éditorialement : on n'ajoute pas un lien dans les mots de quelqu'un d'autre, et
 * l'extrait externe est en plus couvert par l'article 29.2 de la Loi sur le droit d'auteur.
 *
 * La cause est STRUCTURELLE, pas un oubli de configuration : GlossaryLinkifier porte bien
 * « blockquote » dans ses $skipTags (ligne 1384), mais cette garde ne peut pas mordre ici, parce
 * que la directive @glossarize() reçoit le TEXTE SEUL de la citation - Blade n'ajoute la balise
 * <blockquote> qu'APRÈS le retour de la directive. Le DOM que voit linkify() n'a donc aucun
 * noeud blockquote à sauter. Le correctif retire @glossarize() des deux blocs de citation de
 * Modules/News/resources/views/public/show.blade.php (citation composée `quote.text`, et citation
 * verbatim externe `quote` de la branche machine/R10) au profit d'un simple {{ }} qui échappe.
 *
 * Ce fichier verrouille les DEUX branches, et porte sa CONTRE-ÉPREUVE : un terme cité hors
 * citation doit TOUJOURS être lié. Sans elle, un correctif trop large (qui éteindrait tous les
 * auto-liens de la fiche) passerait pour un succès.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixe Qnl = Quote Never Linkified) ────────────────────────────

function qnlTerm(string $name): string
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = \Illuminate\Support\Str::slug($name).'-qnl-'.uniqid();

    Term::create([
        'name' => [$locale => $name, 'fr' => $name],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test pour '.$name.'.', 'fr' => 'Définition de test pour '.$name.'.'],
        'is_published' => true,
        'match_strategy' => 'loose',
    ]);

    return $slug;
}

function qnlSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source citation intouchable',
        'url' => 'https://qnl-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function qnlArticle(int $sourceId, array $structuredSummary): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Article test citation intouchable',
        'guid' => 'guid-qnl-'.uniqid(),
        'url' => 'https://qnl-source.exemple.com/article',
        'resolved_url' => 'https://qnl-source.exemple.com/article-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli.',
        'slug' => 'citation-intouchable-'.uniqid(),
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
        'structured_summary' => $structuredSummary,
    ]);
}

/**
 * Isole le contenu du <blockquote class="nw-quote"> rendu, pour porter l'assertion sur
 * L'ÉLÉMENT TESTÉ et non sur la page entière : une assertion globale passerait même sans le
 * correctif, puisque le terme figure aussi ailleurs dans la page (mémoire projet
 * « assertion-globale-passe-sans-le-correctif-2026-09-03 »).
 */
function qnlBlockquote(string $html): string
{
    $start = mb_strpos($html, '<blockquote class="nw-quote"');
    expect($start)->not->toBeFalse('Aucun <blockquote class="nw-quote"> rendu : la fiche de test ne rend pas sa citation.');

    $end = mb_strpos($html, '</blockquote>', $start);
    expect($end)->not->toBeFalse('Le <blockquote class="nw-quote"> n\'est pas refermé dans le HTML rendu.');

    return mb_substr($html, $start, $end - $start);
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

// ── Branche 1 : fiche COMPOSÉE (composed:true, quote => {text, author}) ──────────────

it('ne pose AUCUN auto-lien de glossaire dans la citation d\'une fiche composée', function () {
    $source = qnlSource();
    // Le terme n'apparaît QUE dans la citation : sans le correctif, le seul lien de la page
    // naîtrait donc à l'intérieur du blockquote.
    $slug = qnlTerm('rançongiciel');

    $article = qnlArticle($source->id, [
        'composed' => true,
        'hook' => 'Un organisme public a interrompu ses services en ligne pendant deux jours.',
        'why_important' => 'Des milliers de dossiers étaient concernés.',
        'quote' => [
            'text' => 'Nous avons été victimes d\'un rançongiciel et nous avons coupé l\'accès.',
            'author' => 'Porte-parole de l\'organisme',
        ],
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $quote = qnlBlockquote($html);

    // Le texte de la citation reste intégralement lisible - on retire un LIEN, jamais du contenu.
    expect($quote)->toContain('rançongiciel')
        // ... mais aucune balise <a> ne vit à l'intérieur de la citation.
        ->and(substr_count($quote, '<a '))->toBe(0)
        ->and($quote)->not->toContain('glossary-link')
        ->and($quote)->not->toContain('/glossaire/'.$slug);
});

// ── Branche 2 : fiche MACHINE / R10 (quote => chaîne, attribution 29.2 LDA) ──────────

it('ne pose AUCUN auto-lien de glossaire dans la citation verbatim externe d\'une fiche machine (art. 29.2 LDA)', function () {
    $source = qnlSource();
    $slug = qnlTerm('hameçonnage');

    // Pas de `composed: true` : la vue emprunte la branche @else, celle du bloc R10 avec
    // <x-news::quote-attribution>.
    $article = qnlArticle($source->id, [
        'quote' => 'L\'attaque a commencé par une campagne d\'hameçonnage ciblée.',
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $quote = qnlBlockquote($html);

    expect($quote)->toContain('hameçonnage')
        ->and(substr_count($quote, 'glossary-link'))->toBe(0)
        ->and($quote)->not->toContain('/glossaire/'.$slug);
});

// ── Contre-épreuve : le correctif ne doit PAS éteindre les auto-liens hors citation ──

it('CONTRE-ÉPREUVE : un terme cité hors de la citation reste bien auto-lié sur la même fiche', function () {
    $source = qnlSource();
    $slug = qnlTerm('pare-feu');

    $article = qnlArticle($source->id, [
        'composed' => true,
        // Le terme vit DANS le corps rédactionnel ET dans la citation.
        'hook' => 'Le pare-feu de l\'organisme a été reconfiguré en urgence.',
        'why_important' => 'La reconfiguration a coupé plusieurs services internes.',
        'quote' => [
            'text' => 'Le pare-feu bloquait aussi nos propres outils, nous l\'avons découvert le lendemain.',
            'author' => 'Responsable des systèmes',
        ],
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $quote = qnlBlockquote($html);

    // Zéro lien dans la citation...
    expect(substr_count($quote, 'glossary-link'))->toBe(0)
        // ... et EXACTEMENT un lien légitime ailleurs sur la fiche (max_occ => 1, tâche #1350).
        ->and(substr_count($html, 'href="/glossaire/'.$slug.'"'))->toBe(1);
});

// ── Trous trouvés par la revue Codex du 2026-09-06, tous deux réels ──────────────────

/**
 * Objection 6 de Codex : « les assertions prouvent l'absence de liens, mais pas la conservation
 * du texte : une citation VIDÉE satisferait aussi zéro <a> ». Elle avait raison, et le contrôle
 * porte ici sur la citation ENTIÈRE - apostrophes comprises, qui s'échappent en `&#039;`.
 * Ce test remplace aussi un `grep withoutDoubleEncoding` revenu vide : un grep vide ne prouve
 * rien, un rendu réel qui montre `&#039;` UNE seule fois prouve que `{{ }}` échappe exactement
 * comme le `e()` explicite qu'il remplace (pas de double encodage en `&amp;#039;`).
 */
it('conserve la citation ENTIÈRE, échappée exactement une fois (le correctif retire un lien, jamais du texte)', function () {
    $source = qnlSource();
    qnlTerm('rançongiciel');

    $texte = 'Nous avons été victimes d\'un rançongiciel & nous avons coupé l\'accès.';

    $article = qnlArticle($source->id, [
        'composed' => true,
        'hook' => 'Un organisme public a interrompu ses services en ligne pendant deux jours.',
        'quote' => ['text' => $texte, 'author' => 'Porte-parole de l\'organisme'],
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();

    $quote = qnlBlockquote($response->getContent());

    // La citation complète, telle que Blade l'échappe - rien de retiré, rien de doublement encodé.
    expect($quote)->toContain(e($texte))
        ->and($quote)->not->toContain('&amp;#039;')
        ->and($quote)->not->toContain('&amp;amp;');
});

/**
 * Objection 3 de Codex : le compteur d'occurrences `self::$seenThisRequest` est STATIQUE et
 * partagé entre tous les appels @glossarize d'une même requête, avec `max_occ => 1` par terme
 * (tâche #1350). Une citation qui consommait ce quota unique ne le consomme plus. Ma
 * contre-épreuve précédente ne couvrait PAS ce scénario : son terme apparaissait dans le hook
 * (section 1), donc le quota était déjà consommé AVANT d'atteindre la citation (section 5).
 *
 * Ici le terme n'apparaît QUE dans la citation (section 5) puis dans « Action concrète »
 * (section 7, rendue APRÈS). Avant le correctif, le lien naissait dans la citation et la
 * section 7 n'en avait aucun ; après, il se déplace vers la section 7. C'est l'effet VOULU -
 * le lien va au corps rédactionnel plutôt qu'aux mots d'un tiers - mais il n'était prouvé
 * nulle part.
 */
it('déplace le lien vers le corps rédactionnel quand la citation PRÉCÈDE la seule autre mention', function () {
    $source = qnlSource();
    $slug = qnlTerm('hameçonnage');

    $article = qnlArticle($source->id, [
        'composed' => true,
        // Le hook ne contient PAS le terme : le quota est donc encore libre en section 5.
        'hook' => 'Un organisme public a interrompu ses services en ligne pendant deux jours.',
        'quote' => [
            'text' => 'Tout a commencé par un hameçonnage très bien imité.',
            'author' => 'Responsable des systèmes',
        ],
        // Section 7, rendue APRÈS la citation.
        'action_concrete' => 'Signalez toute tentative d\'hameçonnage à votre service informatique.',
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $quote = qnlBlockquote($html);

    expect(substr_count($quote, 'glossary-link'))->toBe(0)
        // Le quota libéré profite à la section rédactionnelle suivante : le lien existe bien,
        // et une seule fois (max_occ => 1 tient toujours).
        ->and(substr_count($html, 'href="/glossaire/'.$slug.'"'))->toBe(1);
});
