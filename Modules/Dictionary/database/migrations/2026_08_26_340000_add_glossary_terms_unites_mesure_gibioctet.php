<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de deux termes au glossaire (2026-08-26) : « Unités de mesure informatiques » (fiche
 * pivot : bit, octet, kilo/méga/giga/téraoctet - préfixes SI décimaux) et « Gibioctet » (préfixes
 * binaires CEI kibi/mébi/gibi/tébi, confusion binaire-décimale).
 *
 * DÉCOUPAGE VOLONTAIRE EN DEUX FICHES, PAS HUIT : un seul mécanisme (préfixe × unité de base)
 * explique kilo/méga/giga/téraoctet, donc une seule fiche pivot les porte toutes via ses
 * `aliases` plutôt qu'une fiche par préfixe (huit fiches quasi identiques se cannibaliseraient
 * au référencement). Le gibioctet reste une fiche À PART parce que c'est une notion réellement
 * distincte (norme CEI 80000-13, base 1024 contre base 1000) qui répond à une question propre
 * (« pourquoi mon disque de 1 To affiche-t-il 931 Go? ») - reliée à la première par
 * broader_slugs/narrower_slugs, jamais fusionnée.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction sur les 506 slugs RÉELS extraits du sitemap de
 * production (jamais en sondant une URL devinée) : `grep -iE "octet|byte"` → rien ;
 * `grep -iE "giga|mega|kilo|tera|peta"` → seulement deux faux positifs de sous-chaîne
 * (litteratie-ia, iteration, qui contiennent la suite de lettres « tera » sans rapport) ;
 * `grep -iE "gibi|binaire|unite"` → rien ; `grep -iE "bit"` (mot entier) → rien. Grep
 * complémentaire sur le TEXTE de toutes les migrations existantes (pas seulement les slugs) pour
 * « octet »/« byte » : un seul faux positif, la fiche « Tokenpocalypse » (aucune définition
 * d'unité). Aucun doublon. Un terme voisin existe déjà (« Mémoire IA », /glossaire/memoire-ia)
 * mais c'est la mémoire CONVERSATIONNELLE d'un assistant IA, un domaine sans rapport avec les
 * unités de stockage - pas de relation broader/narrower forcée entre les deux.
 *
 * FAITS VÉRIFIÉS À LA SOURCE PRIMAIRE (pp_search indisponible pour ce sous-agent - MCP
 * perplexity-pro-playwright non chargé -, repli documenté vers
 * mcp__openrouter__chat_with_model modèle perplexity/sonar-pro, PUIS chaque URL proposée
 * vérifiée une à une par une requête HTTP réelle avant d'être retenue ; deux URL institutionnelles
 * proposées par le modèle se sont révélées mortes - ibm.com/history/350-disk-storage et
 * computerhistory.org/storageengine/ibm-ships-first-hard-disk-drive, toutes deux 404 - et ont été
 * remplacées par des URL vivantes confirmées) :
 * - GDT/OQLF « octet » et « gigaoctet » (ce dernier indique LES DEUX valeurs, 1 000 000 000 ET
 *   1 073 741 824 octets - la fiche terminologique officielle porte elle-même la confusion que
 *   la fiche « gibioctet » explique).
 * - BIPM, Brochure SI 9e édition (2019) pour les préfixes décimaux.
 * - Nvidia H100 (80 Go de VRAM) et H200 (141 Go de VRAM), fiches techniques officielles - angle
 *   choisi pour l'exemple concret car ce lectorat (actualités IA) rencontre ces chiffres dans le
 *   contexte du matériel qui fait tourner les modèles, pas dans un cas générique interchangeable.
 * - Computer History Museum, IBM 350 RAMAC (1956, 3,75 Mo) - citation directe vérifiée par lecture
 *   du HTML de la page (« stored 5 million, 6-bit characters ... equivalent to 3.75 Megabytes »).
 * - NIST, page « Prefixes for binary multiples », citation directe vérifiée (« In December 1998
 *   the International Electrotechnical Commission (IEC) ... approved ... prefixes for binary
 *   multiples ») pour la norme CEI 60027-2 Amendement 2 (1998/1999), reprise par la CEI 80000-13
 *   (2008).
 * - Apple, « How storage capacity is measured on Apple devices » (support.apple.com/en-us/102119,
 *   titre confirmé par lecture du HTML), pour l'exemple du disque « 1 To » affiché « 931 Go ».
 *
 * ALIAS - AUCUN SYMBOLE COURT (règle absolue du fondateur, 2026-08-26) : « Go », « To », « ko »,
 * « Mo », « MB », « GB », « Gio », « Tio », « Mio », « Kio », « GiB », « TiB », le mot nu « bit »
 * et le mot nu « byte » sont TOUS écartés des `aliases` - ce sont soit des mots ordinaires
 * ambigus (Go = verbe anglais/interjection/langage Go ; bit = argot québécois « un bit » = « un
 * peu » ; Mo = prénom), soit des symboles trop courts pour être désambiguïsés par le linkifier
 * (< 3 caractères, sous le seuil MIN_LENGTH du GlossaryLinkifier de toute façon). Seules les
 * formes ENTIÈREMENT ÉCRITES et non ambiguës sont posées : octet(s), kilooctet(s),
 * mégaoctet(s), gigaoctet(s), téraoctet(s) + équivalents anglais kilobyte/megabyte/gigabyte/
 * terabyte (mot composé technique sans autre sens courant). Idem pour la fiche gibioctet :
 * kibioctet/mébioctet/tébioctet + kibibyte/mebibyte/gibibyte/tebibyte, mais PAS les symboles
 * courts Gio/Kio/Mio/Tio/GiB (Gio est notamment un prénom italien courant, « Tio » veut dire
 * « oncle » en espagnol).
 *
 * NOTE TECHNIQUE (lu dans Modules/Core/app/Services/GlossaryLinkifier.php avant de choisir la
 * liste) : le pluriel FR n'est auto-dérivé QUE pour le `name` principal d'une fiche, jamais pour
 * les `aliases` manuels - d'où le singulier ET le pluriel posés explicitement pour chaque alias
 * ci-dessous. La casse est en revanche gérée nativement par le drapeau /i du pattern compilé en
 * stratégie 'loose', donc aucune variante de casse à dupliquer manuellement.
 *
 * TYPOGRAPHIE : espace insécable réelle (U+00A0, jamais &nbsp;) posée par script avant chaque
 * groupe de milliers, entre un nombre et son unité (« 80 Go », « 1 000 000 000 octets ») et avant
 * les deux-points ; AUCUNE espace avant ; ! ? (norme OQLF). Contrôle
 * `grep -nP '(?<! ) :|\s+[;!?]'` exécuté sur le texte à plat des deux fiches : rien renvoyé.
 *
 * Images (unites-de-mesure-informatiques, gibioctet : .jpg + .webp, 1200x669) déposées dans
 * public/images/glossaire/ AVANT cette migration - has_image=true dès le départ. `/nanobanana`
 * hors d'atteinte pour ce sous-agent (outils browser_* absents) : repli documenté
 * mcp__stock-photos, aucun logo ni personne identifiable.
 *
 * Données dans database/data/glossaire-batch-2026-08-26-unites-mesure-gibioctet.json.
 * Anti-doublon À L'EXÉCUTION : skip si le slug existe déjà, la migration est donc rejouable.
 * RÉVERSIBLE : down() supprime UNIQUEMENT les deux slugs ajoutés ici et retire la relation
 * narrower_slugs posée sur la fiche pivot.
 */
return new class extends Migration
{
    private const SLUGS = ['unites-de-mesure-informatiques', 'gibioctet'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-26-unites-mesure-gibioctet.json';
        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modèle Term/Category absent, ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('concepts-fondamentaux');

        foreach ($this->terms() as $i => $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";

                continue;
            }

            $catId = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;

            $term = new Term();
            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }
            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'] ?? [];
            $term->broader_slugs = $t['broader_slugs'] ?? [];
            $term->narrower_slugs = $t['narrower_slugs'] ?? [];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->acronym_full = $t['acronym_full'] ?? null;
            $term->dictionary_category_id = $catId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 940 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::SLUGS as $slug) {
            Term::where('slug->fr_CA', $slug)->delete();
        }
    }
};
