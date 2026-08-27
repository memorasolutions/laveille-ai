<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de deux termes au glossaire (2026-08-27) : Linux et Windows. Ce ne sont PAS des fiches
 * d'encyclopédie informatique - l'angle retenu répond à une seule question, celle que se pose le
 * lecteur non-spécialiste du site : pourquoi ce nom revient-il sans arrêt dans les actualités IA ?
 * Pour Linux : les serveurs et supercalculateurs qui entraînent/exécutent les modèles, les
 * conteneurs (Docker/Kubernetes), les pilotes NVIDIA CUDA, l'écosystème open source. Pour Windows :
 * l'ordinateur personnel du grand public (où la plupart des gens croisent l'IA en premier),
 * l'intégration de Copilot dans le système, ce qu'un particulier peut faire tourner localement
 * (WSL, Copilot+ PC/NPU).
 *
 * ANTI-DOUBLON (relevé complet du sitemap prod, 510 slugs glossaire, 2026-08-27) : aucun slug
 * "linux"/"windows" existant. Recherche élargie par motif ET par requête live sur l'index de
 * recherche du site (/recherche/palette, qui interroge news+blog+glossaire en production) :
 * aucune fiche glossaire nommée "Linux" ou "Windows" ne préexistait - seul "kernels" ressemblait
 * de loin (Hugging Face Kernel Hub, noyaux de calcul GPU précompilés - notion sans rapport avec le
 * noyau d'un système d'exploitation, vérifié par lecture de sa définition complète en base). Le
 * terme "microsoft" existe déjà (la définition en base porte sur l'entreprise : "Microsoft est un
 * acteur dominant de l'IA, intégrant des capacités génératives dans Windows et Office...") - une
 * entreprise n'est pas la même notion que le système d'exploitation qu'elle publie (même principe
 * que Menlo Research/Jan, migration 2026_08_27_090000) : pas de fusion, pas de broader_slugs vers
 * "microsoft" (un fabricant ne se confond pas avec ce qu'il publie). Notion voisine retenue pour
 * Linux : "open-source" (licence GPLv2 du noyau, principe fondateur de l'écosystème IA ouvert) -
 * relation broader_slugs/narrower_slugs posée dans cette migration, pattern Docker/Socket
 * (2026_07_26_120000) et poids-ouverts/Ollama-Jan (2026_08_27_090000). Aucun parent propre trouvé
 * pour Windows (pas de terme générique "système d'exploitation" dans le glossaire) - laissé sans
 * broader_slugs plutôt que de forcer une relation avec "microsoft".
 *
 * AMPLEUR DES AUTO-LIENS (mesurée en production le 2026-08-27, requête live reproductible) :
 *   curl -s "https://laveille.ai/recherche/palette?q=linux"   -> section news.total=24, blog.total=1
 *   curl -s "https://laveille.ai/recherche/palette?q=windows" -> section news.total=47, blog.total=4,
 *     glossaire.total=1 (mention incidente dans la fiche "nvm", pas un doublon)
 * Au moins 25 pages contiennent déjà "Linux", au moins 52 contiennent déjà "Windows" - volume
 * élevé, RECOMMANDATION EXPLICITE au superviseur : vérifier les auto-liens réellement posés après
 * déploiement (contrôle anti-faux-lien du skill /glossaire, section 5 point 6).
 *
 * ALIAS RETENUS - restrictifs par design (le mot "Windows" est très fréquent, cf. ampleur ci-dessus) :
 *   - Linux : "GNU/Linux" (nom formel équivalent, aucune autre signification possible) et
 *     "noyau Linux" (désigne précisément le même composant, sans ambiguïté). match_strategy=loose
 *     conservé - "linux" en minuscule n'a aucune autre signification en français, aucun risque de
 *     collision identifié.
 *   - Windows : "Microsoft Windows" seulement (nom complet non ambigu). match_strategy=case_sensitive
 *     choisi par prudence (contrairement à Linux) : la casse "Windows" majuscule est sans risque,
 *     mais la forme minuscule "windows" apparaît couramment dans du texte technique cité sur ce
 *     site (ex. clé YAML "os: windows" d'un exemple de workflow CI, nom de runner GitHub Actions
 *     "windows-latest") sans rapport avec le produit - la casse stricte évite ces faux liens dans
 *     du contenu de code cité, conformément à la consigne "en cas de doute, match_strategy sensible
 *     à la casse".
 * ALIAS ÉCARTÉS : "fenêtres" (ce n'est PAS la traduction utilisée pour désigner le produit en
 * français - aucun texte du site n'écrirait "fenêtres" pour parler de Windows, l'ajouter romprait
 * plutôt qu'aiderait). "WSL", "Windows 11", "Copilot+ PC" écartés comme alias de "Windows" : ce
 * sont des notions plus étroites que la fiche mentionne et explique (WSL en particulier a sa propre
 * FAQ dédiée dans la fiche), mais qui mériteraient leur propre fiche si un jour demandées - les
 * traiter comme un simple alias aurait été un raccourci hors du périmètre de cette migration (deux
 * termes seulement, mandat explicite). Idem pour les noms de distributions Linux (Ubuntu, Debian,
 * Red Hat...) : notions voisines mais distinctes, pas des alias de "Linux".
 *
 * RECHERCHE ET SOURCES (2-3 sources indépendantes par fiche, chaque URL vérifiée HTTP 200 par curl
 * avant citation - pp_search absent de cette session, repli documenté utilisé : chat_with_model
 * modèle perplexity/sonar-pro) :
 *   - Linux : TOP500.org (statistiques "Operating System Family", 100 % Linux depuis novembre 2017,
 *     consultées août 2026) + W3Techs (61-62 % des serveurs web à l'OS identifiable, données 2026)
 *     + NVIDIA (notes de version des conteneurs officiels PyTorch, base Ubuntu/CUDA, 2026).
 *   - Windows : StatCounter Global Stats (70-72 % du marché desktop mondial, 2025-2026, plusieurs
 *     relevés recoupés vu la controverse méthodologique sur la catégorie "Unknown" mi-2026) +
 *     Windows Experience Blog/Microsoft (touche Copilot dédiée, 4 janvier 2024) + Official Microsoft
 *     Blog (catégorie Copilot+ PC, NPU >= 40 TOPS, 20 mai 2024). Chiffre d'utilisateurs Copilot
 *     délibérément omis : les seuls chiffres trouvés (30 M de sièges payants, T4 FY2026) concernent
 *     Microsoft 365 Copilot en entreprise, portée différente de "Copilot dans Windows" grand public -
 *     mélanger les deux aurait été une erreur de portée, donc rien n'est avancé sur ce point precis.
 *
 * Angle retenu : UTILITAIRE/CONTEXTUEL (pourquoi ce mot est partout dans les actualités IA qu'un
 * lecteur du site croise), jamais un angle historique - conforme au mandat.
 *
 * Typographie OQLF appliquée par script déterministe (espace insécable U+00A0 réelle avant ':', '%'
 * et les unités; AUCUNE espace avant ';!?', contrairement à la convention française) - contrôle
 * grep -nP '(?<! ) :|\s+[;!?]' passé (aucune correspondance) sur le fichier de données.
 *
 * IMAGES PRODUITES (2026-08-27, session ultérieure) : has_image=true pour les deux termes.
 * Illustrations générées via le compte Gemini de l'utilisateur (skill /nanobanana, Playwright pour
 * google-antigravity ; session Playwright expirée en cours de tâche pour linux/windows, relayée via
 * le canal de secours documenté du skill - script Node/Playwright piloté avec le même compte, après
 * `ia-sync`, jamais un autre générateur). 3D isométrique teal/orange, sans texte ni logo : salle de
 * serveurs/GPU + engrenage ouvert pour Linux (aucun manchot), portable + sphère lumineuse pour
 * Windows (aucun logo à quatre carreaux). Fichiers déposés AVANT cette mise à jour :
 * public/images/glossaire/{linux,windows}.{webp,jpg} (1200x669, compressés, sous 90 Kio chacun).
 *
 * Données dans database/data/glossaire-batch-2026-08-27-linux-windows.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime uniquement ces deux
 * termes et retire "linux" de narrower_slugs sur "open-source" sans toucher au reste du tableau.
 * IDEMPOTENTE : rejouable sans effet si déjà appliquée.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-linux-windows.json';
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
            echo "[glossaire] modèle Term/Category absent - ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('outils-et-techniques');
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

        // Relation broader/narrower avec "open-source" (pattern Docker/Socket, poids-ouverts/
        // Ollama-Jan) : Linux est l'exemple le plus cité de la licence GPL qui a façonné la culture
        // de l'IA open source - relation thématique, pas une hiérarchie de licence stricte.
        $openSource = Term::where('slug->fr_CA', 'open-source')->first();
        if ($openSource) {
            $narrower = is_array($openSource->narrower_slugs) ? $openSource->narrower_slugs : [];
            if (! in_array('linux', $narrower, true)) {
                $narrower[] = 'linux';
                $openSource->narrower_slugs = array_values($narrower);
                $openSource->save();
                echo "[glossaire] open-source.narrower_slugs += linux\n";
            } else {
                echo "[glossaire] open-source.narrower_slugs contient déjà linux, skip\n";
            }
        } else {
            echo "[glossaire] terme open-source introuvable, skip relation\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $openSource = Term::where('slug->fr_CA', 'open-source')->first();
        if ($openSource) {
            $narrower = is_array($openSource->narrower_slugs) ? $openSource->narrower_slugs : [];
            $narrower = array_values(array_diff($narrower, ['linux']));
            $openSource->narrower_slugs = $narrower;
            $openSource->save();
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
