<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Palisade Research" au glossaire (2026-08-27), organisme américain à but non
 * lucratif de sécurité de l'IA. Déclencheur : l'actualité publiée le 2026-08-25
 * (deux-modeles-dopenai-testes-sans-garde-fous-ont-accede-seuls-a-hugging-face) cite Jeffrey
 * Ladish, « directeur de Palisade Research » ; la fiche rend ce nom d'organisme cliquable depuis
 * cette actualité et les suivantes.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur la liste RÉELLE des 510 slugs publiés extraite
 * du sitemap de production (jamais en sondant une URL devinée). Motifs cherchés : "palisade"
 * (aucun résultat), "ladish" (aucun résultat), "securite-ia"/"ai-safety" (aucun résultat), "metr"
 * (laboratoire-metr existe - organisme DISTINCT, voir plus bas). Décision : aucun doublon,
 * nouvelle fiche.
 *
 * SOURCES - chaque URL ouverte réellement (curl direct, HTTP 200 vérifié) avant inscription,
 * jamais une adresse devinée :
 *  - https://palisaderesearch.org/about (source PRIMAIRE, page officielle de l'organisme) :
 *    "Palisade Research is a nonprofit based in Berkeley, California... In 2022, Jeffrey was
 *    helping to build out the security team at Anthropic... So Jeffrey assembled a team..."
 *    Le pied de page affiche "© 2023–2026 Palisade Research", d'où la distinction retenue dans
 *    la fiche entre l'assemblage de l'équipe (2022) et la fondation formelle (2023). Équipe
 *    listée : Jeffrey Ladish (Executive Director), Benjamin Weinstein-Raun (Head of Research),
 *    Dave Kasten (Head of Policy), Eli Tyre (Head of Strategy), John Steidley (Chief of Staff),
 *    Jeremy Schlatter (Research Engineer), Melynna Garcia (Operations Manager), Kyle Scott et
 *    Blake Borgeson (Board Members).
 *  - https://aijourn.com/ai-is-like-nuclear-energy-the-benefits-are-immense-but-so-are-the-risks-palisades-research-lead-dmitrii-volkov-on-navigating-ai-risks/
 *    (source SECONDAIRE indépendante, The AI Journal, Alina Fooks, publié 2025-06-23, lu
 *    intégralement) : corrobore "Palisade was founded by cybersecurity expert Jeffrey Ladish...
 *    one of the few nonprofit organizations dedicated to AI safety... mission is to surface and
 *    demonstrate AI-related risks, and make these findings public."
 *  - https://palisaderesearch.org/blog/shutdown-resistance (source primaire, billet daté
 *    2025-07-05) : travail le plus documenté de l'organisme, la "résistance à l'arrêt" chez des
 *    modèles de raisonnement.
 *  - https://arxiv.org/abs/2509.14260 (préprint académique correspondant, "Incomplete Tasks
 *    Induce Shutdown Resistance in Some Frontier LLMs", Schlatter/Weinstein-Raun/Ladish).
 *  - Fait secondaire vérifié (Elon Musk commentant "concerning" sur X au sujet de cette étude,
 *    utilisé en FAQ) : corroboré par 2 sources indépendantes de la page officielle, Times of
 *    India (2025) et Kingy AI, toutes deux HTTP 200 vérifiées.
 * `pp_search` était indisponible cette session (absent des outils chargés) ; repli documenté
 * utilisé : mcp__openrouter__chat_with_model, modèle perplexity/sonar-pro.
 *
 * ALIAS - "Palisade" seul ÉCARTÉ, VÉRIFIÉ EMPIRIQUEMENT (pas seulement par précaution théorique)
 * via un terme de test temporaire (jamais publié) et GlossaryLinkifier::linkify() en tinker sur
 * des phrases pièges réelles : avec alias=["Palisade"] et match_strategy=case_sensitive, "Palisade,
 * une petite ville du Colorado..." ET "Palisade Corporation commercialise le logiciel @RISK..."
 * ont TOUTES DEUX été liées à tort vers la fiche de test. Alias retiré. Configuration finale
 * retenue (name="Palisade Research", aliases=[], match_strategy=case_sensitive) re-testée sur les
 * 6 mêmes phrases pièges : seules les occurrences réelles de "Palisade Research" (casse normale
 * ou tout en minuscules) déclenchent un lien ; "Palisade" seul, "Palisade Corporation" et
 * "Pacific Palisades" ne matchent plus. Aucun alias n'a donc été retenu, le nom complet suffit
 * (il vient du champ `name`, pas besoin de le dupliquer en alias).
 *
 * broader_slugs/narrower_slugs volontairement VIDES : METR (laboratoire-metr, production
 * uniquement, absent en local) est un organisme distinct et de même niveau, pas un parent ni un
 * enfant conceptuel de Palisade Research - les relier forcerait une hiérarchie inexistante. La
 * fiche laboratoire-metr elle-même n'a aucun broader_slugs/narrower_slugs en production
 * (confirmé par lecture du HTML rendu) : ce standalone est le précédent suivi ici plutôt qu'un
 * rattachement artificiel. "ai-red-teaming" (broader_slugs=["attaque-adversariale"] en
 * production) a aussi été envisagé puis écarté : c'est une PRATIQUE, pas une catégorie
 * hiérarchique à laquelle rattacher un ORGANISME.
 *
 * Images (palisade-research.jpg / .webp, 1200x669, compressées selon le standard du skill)
 * déposées dans public/images/glossaire/ AVANT cette migration - has_image=true dès le départ.
 * Métaphore institutionnelle abstraite : gros plan sur des pieux de palissade en bois taillés en
 * pointe (photo réelle, licence libre, Pixabay/MikeGoad) - aucune personne, aucun logo, lien
 * visuel direct avec le nom de l'organisme ET avec son activité (tester la solidité d'une
 * défense). Inspectée visuellement avant application.
 *
 * Données dans database/data/glossaire-batch-2026-08-27-palisade-research.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, la migration est donc rejouable.
 * RÉVERSIBLE : down() supprime UNIQUEMENT le slug ajouté ici.
 */
return new class extends Migration
{
    private const SLUGS = ['palisade-research'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-palisade-research.json';
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

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

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
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 942 + $i;
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
