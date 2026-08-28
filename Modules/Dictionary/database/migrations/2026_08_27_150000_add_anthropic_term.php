<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Anthropic" au glossaire (2026-08-27) - l'ENTREPRISE, jamais le produit.
 *
 * CONTRÔLE ANTI-DOUBLON vérifié AVANT rédaction, sur les 516 slugs réels du sitemap de production
 * (jamais un sondage d'URL devinée). "claude-anthropic" existe déjà et a été LU en entier
 * (https://laveille.ai/glossaire/claude-anthropic) : 89 occurrences brutes de "Claude" contre 42
 * d'"Anthropic" dans le HTML complet, mais en isolant le contenu propre (hors tooltips d'auto-liens
 * d'autres fiches comme "openai" dont le texte de survol cite "San Francisco"/"entreprise"/
 * "Amodei"), la fiche ne consacre qu'UNE seule phrase à l'entreprise elle-même ("Anthropic, fondée
 * en 2021 par d'anciens d'OpenAI (Dario et Daniela Amodei)..."), noyée dans une définition et une
 * méta-description ("le stylo Montblanc" vs ChatGPT) entièrement centrées sur l'assistant Claude.
 * Verdict retenu : notion VOISINE mais DISTINCTE (cas d'école "PostgreSQL / licence PostgreSQL" du
 * skill /glossaire section 0bis) - fiche nouvelle sur l'entreprise, reliée à l'existante.
 *
 * Deux fiches déjà publiées sont ORPHELINES de leur auteur, vérifié en lisant leur HTML rendu :
 * "ia-constitutionnelle" (broader_slugs actuels = ["gouvernance-ia","alignement-ia"], aucun lien
 * vers l'entreprise) et "mcp" (aucune section "Termes liés" du tout, donc broader_slugs vide) citent
 * toutes deux "Anthropic" en texte NON cliquable. Cette migration corrige les deux, en ANNEXANT
 * "anthropic" à leur broader_slugs existant au moment de l'exécution (jamais en écrasant un tableau
 * codé en dur) - down() retire uniquement "anthropic" de ce qui existe alors, sans dépendre d'un
 * état figé. "rlhf" et "alignement-ia" n'ont PAS reçu ce traitement : ce sont des notions largement
 * antérieures et extérieures à Anthropic (popularisées avant sa fondation, notamment via Paul
 * Christiano - lui-même un des cinq fiduciaires fondateurs du Long-Term Benefit Trust cité plus
 * bas), pas des inventions de l'entreprise ; les y rattacher aurait surattribué la paternité.
 *
 * ALIAS : AUCUN retenu, name="Anthropic" seul. "Anthropic PBC"/"Anthropic, Inc" écartés (non requis :
 * le mot "Anthropic" est déjà un sous-segment de ces formes, un alias plus long n'ajoute rien pour
 * la casse case_sensitive retenue). Danger nommé par le mandat vérifié dans le code réel du
 * linkifier (Modules/Core/app/Services/GlossaryLinkifier.php) : la comparaison caractère à caractère
 * montre qu'« Anthropic » (a-n-t-h-r-o-p-i-c) N'EST PAS un sous-segment de l'adjectif français
 * « anthropique » (a-n-t-h-r-o-p-i-q-u-e) - ils divergent au 9e caractère (c vs q) - donc aucune
 * collision directe possible. Le risque réel identifié est ailleurs : extractMorphologicalAliases()
 * dérive automatiquement, pour TOUT terme de 4+ caractères non tout-cap, une forme minuscule
 * ("anthropic") et un pluriel ("Anthropics"/"anthropics") - ces dérivés HÉRITENT de la stratégie du
 * terme parent (escalateStrategyIfStopList ne fait jamais redescendre une stratégie déjà stricte,
 * lignes ~476-478 et docblock associé). D'où match_strategy=case_sensitive choisi explicitement :
 * la forme dérivée minuscule "anthropic" ne matche alors que l'orthographe anglaise EXACTE et
 * minuscule (jamais "anthropique"), et usort() par longueur (ligne ~493-494) garantit que
 * l'alias existant "Anthropic Claude" (sur claude-anthropic, 17 caractères) est toujours choisi
 * avant le simple "Anthropic" (9 caractères) partout où la phrase complète apparaît - aucun conflit
 * entre les deux fiches. Vérifié aussi : le mot "Anthropic" seul n'est PAS déjà un alias de
 * claude-anthropic (alternateName JSON-LD = ["Claude AI","Claude Sonnet","Claude Opus",
 * "Claude Haiku","Anthropic Claude"], jamais "Anthropic" isolé).
 *
 * RECHERCHE - mcp__perplexity-pro-playwright__pp_search (session connectée après cookie_sync),
 * validation croisée par au moins deux sources indépendantes par fait, chaque URL vérifiée HTTP 200
 * individuellement avant inscription (curl avec user-agent navigateur) :
 *  - Fondation (26 janvier 2021, San Francisco, Dario et Daniela Amodei + anciens d'OpenAI dont
 *    Jared Kaplan, Chris Olah, Tom Brown, Sam McCandlish, Jack Clark, Ben Mann) : Wikipédia EN
 *    "Anthropic" + Britannica "Anthropic-PBC" concordants (Britannica bloque curl en 403 malgré un
 *    contenu confirmé par citation directe dans la réponse Perplexity - non retenue comme URL de
 *    `sources` par prudence, uniquement comme corroboration de lecture).
 *  - Forme juridique (Public Benefit Corporation, droit du Delaware) et gouvernance (Long-Term
 *    Benefit Trust : fiducie de 5 fiduciaires indépendants sans intérêt financier, actions de
 *    catégorie T, pouvoir croissant d'élection du conseil) : page officielle Anthropic
 *    "The Long-Term Benefit Trust", publiée le 19 septembre 2023 (date confirmée sur la page et
 *    par une recherche dédiée à sa date de publication).
 *  - Positionnement sécurité déclaré (Responsible Scaling Policy, niveaux ASL inspirés des niveaux
 *    de biosécurité, v1.0 du 19 septembre 2023, v3.0 du 24 février 2026, v3.1 du 2 avril 2026) :
 *    page officielle Anthropic "Announcing Anthropic's Responsible Scaling Policy" - attribué
 *    explicitement comme une politique QUE L'ENTREPRISE DÉCLARE suivre, jamais énoncé comme un fait
 *    de sécurité établi.
 *  - Exemple situé et daté (Jan Leike, ex-corresponsable de la sécurité chez OpenAI, rejoint
 *    Anthropic en mai 2024) : CNBC, Hayden Field, 28 mai 2024, HTTP 200 vérifié.
 *  - Investissement Amazon (8 milliards $ US cumulés en novembre 2024, Amazon investisseur
 *    minoritaire) : page officielle Anthropic "Deepening our compute partnership with Amazon" +
 *    communiqué aboutamazon.com du 27 mars 2024 (confirme le premier palier de 4 milliards $ US),
 *    tous deux HTTP 200 vérifiés.
 *
 * ÉCARTÉ DÉLIBÉRÉMENT : rumeurs de premier appel public à l'épargne (IPO) et de structure à double
 * catégorie d'actions parues entre le 20 et le 26 août 2026 (Reuters/The Information/Malay Mail/
 * Euronews) - fait RÉEL mais non stabilisé au moment de la rédaction ("weighs"/"prepares"/
 * "potential"), donc exclu d'une fiche de référence pour éviter un fait daté d'hier qui se périme
 * en quelques jours. Signalé au rapport de session, pas dans la fiche.
 *
 * Angle retenu : ENTREPRISE (gouvernance, sécurité déclarée, contributions techniques) - le produit
 * Claude, ses capacités et sa comparaison à ChatGPT restent intégralement sur claude-anthropic,
 * conformément au mandat. Neutralité stricte : le positionnement sécurité est attribué ("se
 * présente comme", "censée limiter") jamais affirmé comme fait vérifié.
 *
 * Typographie OQLF (espace insécable U+00A0 réelle avant ":", aucune espace avant ;!?) - contrôle
 * grep -nP '(?<! ) :|\s+[;!?]' passé sur le fichier de données (aucune correspondance).
 *
 * Image : public/images/glossaire/anthropic.{webp,jpg} déposée AVANT cette migration (1200x669,
 * compressée magick+cwebp), générée via /nanobanana (Playwright, compte stephane@memora.ca,
 * gemini.google.com) - bouclier isométrique abstrait teal/orange protégeant un noyau lumineux de
 * réseau de neurones, aucun logo, aucune marque, aucun visage, aucune identité visuelle réelle
 * d'Anthropic ni de ses dirigeants. Volontairement distincte de l'image du produit sur
 * claude-anthropic.webp.
 *
 * Données dans database/data/glossaire-batch-2026-08-27-anthropic.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime ce terme ET retire
 * "anthropic" des broader_slugs de ia-constitutionnelle/mcp sans toucher au reste de leurs données.
 */
return new class extends Migration
{
    private const RELATED_SLUGS = ['ia-constitutionnelle', 'mcp'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-anthropic.json';
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
            echo "[glossaire] modèle Term/Category absent — ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');
        $allTerms = $this->terms();

        foreach ($allTerms as $i => $t) {
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
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 900 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Rattache les deux notions orphelines à leur auteur : ajoute "anthropic" à broader_slugs
        // SANS écraser le reste du tableau (append dynamique sur l'état lu au moment de la migration).
        foreach (self::RELATED_SLUGS as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                echo "[glossaire] terme lié absent, skip broader_slugs : {$slug}\n";

                continue;
            }
            $existing = is_array($related->broader_slugs) ? $related->broader_slugs : [];
            if (in_array('anthropic', $existing, true)) {
                continue;
            }
            $related->broader_slugs = array_values([...$existing, 'anthropic']);
            $related->save();
            echo "[glossaire] broader_slugs mis à jour : {$slug} (+anthropic)\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::RELATED_SLUGS as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                continue;
            }
            $existing = is_array($related->broader_slugs) ? $related->broader_slugs : [];
            $related->broader_slugs = array_values(array_diff($existing, ['anthropic']));
            $related->save();
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
