<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "OpenAI Codex" au glossaire (2026-08-27). Mandat explicite : « Codex » recouvre
 * trois notions qu'un lecteur du site confond facilement - un modèle de code OpenAI de 2021
 * (retiré), un agent d'ingénierie logicielle OpenAI de 2025 (actif), et le sens premier du mot
 * (manuscrit ancien relié en cahiers, ancêtre du livre). La fiche assume cette ambiguïté au lieu
 * de la cacher : c'est le service qu'un glossaire rend.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur la liste RÉELLE des 516 slugs publiés extraite
 * du sitemap de production (jamais en sondant une URL devinée) : aucun slug ne contient "codex".
 * Recherche élargie par CONCEPT contre la production (pas seulement par slug, cf. piège du
 * 2026-08-21) : requête live /recherche/palette?q=codex sur l'index de recherche du site
 * (news+blog+glossaire+annuaire en production) -> total=13, sections news(108)/blog(3)/annuaire(4)
 * présentes, AUCUNE section "glossaire" dans la réponse (absente = 0 résultat glossaire). Lecture
 * complète des fiches "openai" et "gpt" (JSON-LD + grep du HTML) : ni l'une ni l'autre ne mentionne
 * "codex". Décision : aucun doublon, nouvelle fiche.
 *
 * L'AMBIGUÏTÉ EST LE COEUR DU RISQUE D'AUTO-LIEN (mandat explicite, danger jugé maximal) :
 * "codex" est un nom commun français courant, attesté par Larousse dans AU MOINS 3 sens distincts
 * du nôtre - manuscrit ancien, manuscrit méso-américain, et ancien nom de la Pharmacopée
 * française (d'où l'expression "produit codex" en pharmacie). S'y ajoute "Codex Alimentarius"
 * (organisme international de normes alimentaires), et jusqu'au groupe de piratage de jeux vidéo
 * CODEX - autant de sens qu'un site d'actualité IA peut croiser sans rapport avec OpenAI.
 * DÉCISION : `name` = "OpenAI Codex" (jamais "Codex" seul), à l'image du précédent
 * "Palisade Research" (2026-08-27, même mandat) : un nom bare aurait matché comme SOUS-CHAÎNE dans
 * "Codex Alimentarius" (la frontière de mot du linkifier accepte un espace après "Codex", donc
 * "Codex Alimentarius" aurait été partiellement lié) - vérifié en lisant
 * Modules/Core/app/Services/GlossaryLinkifier.php::matchInText(), aucune notion de "phrase complète"
 * n'existe, seule une frontière de mot. "OpenAI Codex" (2 mots) élimine ce risque à la racine, sans
 * toucher au code du linkifier : aucun texte plausible du site n'écrirait "OpenAI Codex" pour
 * désigner autre chose.
 * ALIASES : AUCUN retenu. "Codex" seul est explicitement écarté (le risque ci-dessus). "Codex CLI"
 * et "GitHub Copilot" écartés aussi : ce sont des notions VOISINES mais distinctes (Copilot
 * appartient à GitHub/Microsoft, multi-modèles depuis longtemps ; Codex CLI est un sous-produit
 * séparé lancé le 16 avril 2025) - les traiter comme synonymes aurait été un raccourci hors mandat,
 * elles sont mentionnées dans le texte/FAQ mais ne méritent pas d'alias au sens du linkifier.
 * match_strategy = case_sensitive (défense en profondeur ; la casse seule ne suffit pas contre
 * "Codex Alimentarius" majuscule, d'où le choix du nom complet en premier rempart).
 *
 * SOURCES - chaque URL RÉELLEMENT vue (Playwright, page rendue et lue) avant inscription, jamais
 * une adresse devinée. openai.com refuse curl/bot (403 sur toute UA) : vérification faite en
 * ouvrant chaque page dans le navigateur et en lisant son contenu affiché :
 *  - https://openai.com/index/openai-codex/ (annonce originale, lue intégralement) : "OpenAI Codex
 *    | OpenAI ... August 10, 2021 ... We've created an improved version of OpenAI Codex... The
 *    OpenAI Codex models were deprecated in March 2023 ... Update on April 16, 2025: We launched
 *    Codex CLI ... Update on May 16, 2025: We launched Codex, a cloud-based software engineering
 *    agent..." - source PRIMAIRE unique qui porte toute la chronologie.
 *  - https://openai.com/index/introducing-codex/ (redirige vers /fr-FR/, lue) : "16 mai 2025 ...
 *    Un agent d'ingénierie logicielle basé sur le cloud ... alimenté par codex-1."
 *  - https://openai.com/index/codex-now-generally-available/ (redirige vers /fr-FR/, lue) :
 *    "6 octobre 2025 ... Codex est maintenant disponible pour tous."
 *  - https://www.infoq.com/news/2021/08/openai-codex/ (HTTP 200 vérifié par curl, lue) : Anthony
 *    Alford, InfoQ, 31 août 2021 - source SECONDAIRE indépendante qui corrobore la date et la
 *    nature du modèle de 2021 (12 milliards de paramètres, descendant de GPT-3).
 *  - https://www.larousse.fr/dictionnaires/francais/codex/16897 (HTTP 200, lue) : définition
 *    française du sens manuscrit/pharmacopée - aucune date de publication affichée sur l'entrée
 *    elle-même (dictionnaire vivant, mis à jour en continu) ; year=2026 posé comme année de
 *    CONSULTATION, explicitement documenté ici plutôt que fabriqué.
 * Exemple daté (champ `example`) : Rakuten/Codex sourcé depuis l'actualité déjà publiée sur
 * laveille.ai (https://laveille.ai/actualites/rakuten-fixes-issues-twice-as-fast-with-codex,
 * datePublished JSON-LD vérifié = 2026-03-11), chiffre "réduit le MTTR de 50 %" repris de la
 * description JSON-LD de cette même page.
 *
 * RATTACHEMENT : broader_slugs=["openai"] (organisme éditeur), à l'image de gpt/dall-e/sora/
 * whisper/chatgpt qui sont TOUS narrower_slugs d'"openai" en production (JSON-LD vérifié) plutôt
 * que nichés sous "gpt" - même si le modèle de 2021 est décrit par OpenAI comme "a descendant of
 * GPT-3", le site traite les familles de produits OpenAI comme des soeurs, pas une hiérarchie
 * technique de filiation de modèle ; suivre ce précédent plutôt qu'inventer une exception.
 * "openai".narrower_slugs reçoit "codex" en retour (même mécanisme que
 * 2026_08_27_101000_add_linux_windows_terms.php pour open-source/linux).
 *
 * Image (codex.jpg / codex.webp, 1200x669, compressées selon le standard du skill) déposée dans
 * public/images/glossaire/ AVANT cette migration - has_image=true dès le départ. Métaphore visuelle
 * abstraite : manuscrit ancien relié (cuir patiné, feuillets de parchemin) qui se prolonge en un
 * flux de code/blocs géométriques lumineux, palette teal/orange, aucun texte lisible, aucun logo,
 * aucune personne - inspectée visuellement avant application.
 *
 * Données dans database/data/glossaire-batch-2026-08-27-codex.json.
 * Contrôle typographique OQLF passé sur le fichier de données :
 * grep -nP '(?<! ) :|\s+[;!?]' -> aucune correspondance.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() retire "codex" de openai.narrower_slugs puis supprime le terme.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-codex.json';
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

        $fallbackCatId = $this->resolveCategoryId('outils-et-techniques');

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
            $term->sort_order = 943 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Relation bidirectionnelle avec "openai" (pattern Docker/Socket, Linux/open-source) :
        // Codex partage l'éditeur d'OpenAI, comme GPT/DALL-E/Sora/Whisper/ChatGPT.
        $openai = Term::where('slug->fr_CA', 'openai')->first();
        if ($openai) {
            $narrower = is_array($openai->narrower_slugs) ? $openai->narrower_slugs : [];
            if (! in_array('codex', $narrower, true)) {
                $narrower[] = 'codex';
                $openai->narrower_slugs = array_values($narrower);
                $openai->save();
                echo "[glossaire] openai.narrower_slugs += codex\n";
            } else {
                echo "[glossaire] openai.narrower_slugs contient déjà codex, skip\n";
            }
        } else {
            echo "[glossaire] terme openai introuvable, skip relation\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $openai = Term::where('slug->fr_CA', 'openai')->first();
        if ($openai) {
            $narrower = is_array($openai->narrower_slugs) ? $openai->narrower_slugs : [];
            $narrower = array_values(array_diff($narrower, ['codex']));
            $openai->narrower_slugs = $narrower;
            $openai->save();
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
