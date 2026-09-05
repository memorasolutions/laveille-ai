<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Filet de régression pour trois faux liens MESURÉS en production le 2026-08-29 (CNN, une
 * « requête en rejet ») et un correctif de frontière du 27 août qui avait cassé « Node.js »,
 * « Z.ai » et « jan.ai » en réglant trois autres faux liens. Modules/Dictionary/tests/Feature/
 * HomographAliasNeverAutoTest.php prouve déjà le mécanisme ALIAS_NEVER_AUTO côté glossaire
 * (Modules\Dictionary\Models\Term, URL /glossaire/). Ce fichier comble un angle mort distinct :
 * l'audit qui a découvert CNN et « requête » ne regardait que /glossaire/ et n'a jamais parcouru
 * /acronymes-education/ (Modules\Acronyms\Models\Acronym), le second module qui pose des liens
 * via GlossaryLinkifier::loadTerms(). Le code de production traite déjà les deux sources par le
 * même garde-fou (isNeverAutoAlias() est appelé sur les aliases ET les qualifiers dérivés des
 * deux modèles, voir loadTerms()) : ce test le PROUVE au lieu de le supposer, en reproduisant
 * les mêmes chaînes bloquées (CNN, témoin) mais servies par Acronym plutôt que par Term.
 *
 * Deuxième volet, symétrique et tout aussi important (risque numéro un identifié par
 * l'incident du 27 août) : un correctif qui bloque des faux liens mais en casse des vrais est
 * une régression, pas un progrès. Modules/Core/tests/Unit/GlossaryLinkifierTest.php verrouille
 * déjà Node.js/Z.ai/jan.ai/Gemini 3.5 au niveau du COMPOSANT (tableau $terms construit à la
 * main, walkAndReplace() atteint par réflexion). Les tests ci-dessous verrouillent la même
 * exigence de bout en bout : configuration RÉELLE des fiches (nom, alias, match_strategy tels
 * qu'ils existent en base au 2026-08-29, vérifiés via tinker) + appel PUBLIC
 * GlossaryLinkifier::linkify(), qui passe par loadTerms()/la base de données et donc par le tri
 * par longueur, la résolution de stratégie et la frontière de mot en même temps - rien de tout
 * cela n'est exercé par les tests bas niveau.
 *
 * Ni Modules\Dictionary\Models\Term ni Modules\Acronyms\Models\Acronym ne sont modifiés par ce
 * fichier : il vit dans Modules/Core/tests/Feature/ (périmètre de la tâche) et se contente de
 * CONSOMMER ces deux modèles, exactement comme GlossaryLinkifier lui-même le fait déjà.
 */

use Modules\Acronyms\Models\Acronym;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Aides (préfixe anr = Alias Never-auto Regression) ─────────────────────────

function anrTerm(string $name, string $slug, array $aliases = [], string $matchStrategy = 'loose'): Term
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

function anrAcronym(string $acronymeCourt, string $fullName, string $slug, array $aliases = [], string $matchStrategy = 'loose'): Acronym
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $uniqueSlug = $slug.'-'.uniqid();

    return Acronym::create([
        'acronym' => [$locale => $acronymeCourt, 'fr' => $acronymeCourt],
        'full_name' => [$locale => $fullName, 'fr' => $fullName],
        'slug' => [$locale => $uniqueSlug, 'fr' => $uniqueSlug],
        'description' => [$locale => 'Description de test pour '.$fullName.'.', 'fr' => 'Description de test pour '.$fullName.'.'],
        'is_published' => true,
        'match_strategy' => $matchStrategy,
        'aliases' => $aliases,
    ]);
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

// ═══════════════════════════════════════════════════════════════════════════
// VOLET 1 - le module /acronymes-education/ reçoit le MÊME garde-fou que
// /glossaire/. L'audit du 2026-08-25 avait manqué cet angle : un contrôle qui
// ne regarde qu'un des deux modules laisse l'autre sans preuve.
// ═══════════════════════════════════════════════════════════════════════════

it('ne lie pas "CNN" dérivé d\'un qualifier sur une fiche du module ACRONYMES (pas seulement Dictionary)', function () {
    // Même mécanisme que l'incident Dictionary (extractQualifierAliases sur "X (CNN)"), mais la
    // fiche vit cette fois dans Modules\Acronyms\Models\Acronym, servie par /acronymes-education/.
    anrAcronym('RCVT', 'Réseau convolutif (CNN)', 'reseau-convolutif-acr-test');

    $html = GlossaryLinkifier::linkify('<p>Le réseau CNN rapportait le 22 août 2026 des informations sur des figurants payés.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/acronymes-education/reseau-convolutif-acr-test');
});

it('lie toujours la base du qualifier ("Réseau convolutif", sans CNN) sur une fiche ACRONYMES', function () {
    $fiche = anrAcronym('RCVT', 'Réseau convolutif (CNN)', 'reseau-convolutif-acr-test');

    $html = GlossaryLinkifier::linkify('<p>Le réseau convolutif reste une architecture classique en vision par ordinateur.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/acronymes-education/'.$fiche->slug);
});

it('ne lie pas "témoin" (alias curé à la main) sur une fiche du module ACRONYMES', function () {
    // Même chaîne que l'incident Dictionary (cookie), mais posée comme alias curé d'une fiche
    // ACRONYMES sans rapport, pour prouver que isNeverAutoAlias() protège aussi ce chemin-là.
    anrAcronym('JSPT', 'Jeton de suivi pédagogique', 'jeton-suivi-pedagogique-acr-test', aliases: ['témoin', 'témoins']);

    $html = GlossaryLinkifier::linkify('<p>Le témoin a déclaré ne rien savoir des événements survenus ce soir-là.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/acronymes-education/jeton-suivi-pedagogique-acr-test');
});

it('la fiche ACRONYMES reste trouvable sous son propre sigle malgré l\'alias "témoin" bloqué', function () {
    $fiche = anrAcronym('JSPT', 'Jeton de suivi pédagogique', 'jeton-suivi-pedagogique-acr-test', aliases: ['témoin', 'témoins']);

    $html = GlossaryLinkifier::linkify('<p>La plateforme génère un JSPT pour chaque module terminé.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/acronymes-education/'.$fiche->slug);
});

// ═══════════════════════════════════════════════════════════════════════════
// VOLET 2 - symétrique et non négociable : les VRAIS liens survivent. Un
// correctif qui bloque 3 faux liens et en casse 3 vrais (mesuré le 27 août)
// est une régression. Configuration RÉELLE des fiches (vérifiée en base le
// 2026-08-29), appel PUBLIC linkify() donc via loadTerms()/BD au complet.
// ═══════════════════════════════════════════════════════════════════════════

it('lie toujours "Node.js" en entier - configuration réelle (alias curé "Node", stratégie loose)', function () {
    $terme = anrTerm('Node.js', 'node-js-reel-test', aliases: ['Node'], matchStrategy: 'loose');

    $html = GlossaryLinkifier::linkify('<p>Ce service tourne sous Node.js et répond vite.</p>');

    expect($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>Node.js</a>')
        ->and(str_contains($html, '>Node</a>.js'))->toBeFalse(
            'Node.js ne doit jamais être coupé par son propre alias "Node".'
        );
});

it('lie toujours "Z.ai" en fin de phrase - configuration réelle (alias curé "Zhipu AI", stratégie loose)', function () {
    $terme = anrTerm('Z.ai', 'z-ai-reel-test', aliases: ['Zhipu AI'], matchStrategy: 'loose');

    $html = GlossaryLinkifier::linkify("<p>Cette entreprise s'appelle Z.ai.</p>");

    expect($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>Z.ai</a>');
});

it('lie toujours "Jan.ai" - configuration réelle (case_sensitive, casse exacte "Jan.ai")', function () {
    // La fiche réelle est en case_sensitive avec un J majuscule : une phrase qui écrirait
    // "jan.ai" tout en minuscules ne devrait PAS se lier avec cette configuration - seule la
    // casse exacte de la fiche réelle est verrouillée ici.
    $terme = anrTerm('Jan.ai', 'jan-ai-reel-test', aliases: ['Jan AI'], matchStrategy: 'case_sensitive');

    $html = GlossaryLinkifier::linkify("<p>L'assistant Jan.ai fonctionne en local.</p>");

    expect($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>Jan.ai</a>');
});

// ═══════════════════════════════════════════════════════════════════════════
// CAPSTONE - les deux volets dans le MÊME texte : trois vrais liens (issus du
// module Dictionary) survivent, un faux lien (issu du module Acronyms) est
// bloqué, dans le même appel à linkify(). Preuve que les deux mécanismes
// coexistent sans interférence réciproque.
// ═══════════════════════════════════════════════════════════════════════════

it('bloque CNN (module Acronymes) et garde Node.js/Z.ai/Jan.ai (module Dictionary) dans le même texte', function () {
    $nodejs = anrTerm('Node.js', 'node-js-capstone-test', aliases: ['Node'], matchStrategy: 'loose');
    $zai = anrTerm('Z.ai', 'z-ai-capstone-test', aliases: ['Zhipu AI'], matchStrategy: 'loose');
    $janai = anrTerm('Jan.ai', 'jan-ai-capstone-test', aliases: ['Jan AI'], matchStrategy: 'case_sensitive');
    anrAcronym('RCVT', 'Réseau convolutif (CNN)', 'reseau-convolutif-capstone-test');

    $html = GlossaryLinkifier::linkify(
        "<p>Cette entreprise s'appelle Z.ai et propose une alternative à Jan.ai, tous deux ".
        'fonctionnant sur Node.js en coulisses. Pendant ce temps, CNN diffusait un reportage sur un tout autre sujet.</p>'
    );

    expect($html)->toContain('/glossaire/'.$zai->slug)
        ->and($html)->toContain('/glossaire/'.$janai->slug)
        ->and($html)->toContain('/glossaire/'.$nodejs->slug)
        ->and($html)->not->toContain('/acronymes-education/reseau-convolutif-capstone-test')
        ->and(substr_count($html, 'glossary-link'))->toBe(3);
});

// ═══════════════════════════════════════════════════════════════════════════
// VOLET 4 (2026-09-05, ticket #2238) - ACRONYM_NEVER_AUTO : le NOM PRINCIPAL
// d'un acronyme, qu'ALIAS_NEVER_AUTO ne peut pas atteindre.
//
// Mesure qui a motivé l'entrée « ai » : sur 40 fiches d'actualité tirées au
// hasard (1198 au total), 8 occurrences de « AI » linkifiées, 8 FAUSSES sur 8 -
// nom de média (« TechCrunch AI »), titre (« Mistral AI »), nom de loi
// (« AI Act »), nom de produit (« Google AI Ultra »). Zéro mention légitime.
// ═══════════════════════════════════════════════════════════════════════════

it('ne lie JAMAIS « AI » (nom principal d\'acronyme) mais continue de lier « GPU »', function () {
    // Les DEUX fiches sont creees ensemble, et l'assertion porte sur les DEUX :
    // c'est ce qui distingue une garde d'une panne. Un test qui ne verifie que
    // l'absence passerait aussi si le linkifier etait completement cassé.
    anrAcronym('AI', 'Artificial Intelligence', 'ai-garde', [], 'case_sensitive');
    $gpu = anrAcronym('GPU', 'Graphics Processing Unit', 'gpu-garde', [], 'case_sensitive');
    $slugGpu = $gpu->getTranslation('slug', 'fr_CA', false) ?: $gpu->slug;

    $sortie = GlossaryLinkifier::linkify('Un texte avec AI et GPU dedans pour comparer.');

    expect($sortie)
        ->not->toContain('/acronymes-education/ai-garde')   // la garde mord
        ->and($sortie)->toContain('/acronymes-education/'.$slugGpu); // et elle ne mord QUE ça

    // TEMOIN ROUGE execute le 2026-09-05 : filtre neutralise dans
    // GlossaryLinkifier.php:596, ce meme test relance -> « AI present dans les
    // termes : OUI » et le lien <a href="/acronymes-education/ai-...">AI</a> est
    // bien pose. Filtre actif -> AI absent des termes, aucun lien. La garde est
    // donc PROUVEE dans les deux sens, pas seulement supposee.
});

it('« AI » en ALIAS d\'une autre fiche ne lie pas non plus (chemin jumeau, trouve par Codex)', function () {
    // Le cas mesure en production passe par le NOM principal (test precedent). Celui-ci ferme la
    // porte que ACRONYM_NEVER_AUTO n'atteint pas : une fiche dont le nom est autre chose, mais
    // qui declare « AI » parmi ses variantes.
    anrAcronym('AGI', 'Artificial General Intelligence', 'agi-alias', ['AI'], 'case_sensitive');

    expect(GlossaryLinkifier::linkify('Un texte avec AI dedans.'))
        ->not->toContain('/acronymes-education/agi-alias');
});

it('les quatre formes mesurees en production ne posent aucun lien « AI »', function () {
    anrAcronym('AI', 'Artificial Intelligence', 'ai-formes', [], 'case_sensitive');

    foreach ([
        'Publie par TechCrunch AI le 25 juin 2026.',            // nom de media (5 cas sur 8)
        'L\'accord Caisse des Depots-Mistral AI est signe.',    // nom d\'entreprise, dans un titre
        'Les exigences de l\'AI Act europeen s\'appliquent.',    // nom de loi
        'Les abonnes Google AI Ultra paient 100 $ par mois.',   // nom de produit
    ] as $phrase) {
        expect(GlossaryLinkifier::linkify($phrase))
            ->not->toContain('/acronymes-education/ai-formes', "Faux lien pose dans : {$phrase}");
    }
});
