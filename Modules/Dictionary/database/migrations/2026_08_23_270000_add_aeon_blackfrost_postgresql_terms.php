<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout des termes "AEON", "Blackfrost" et "PostgreSQL" au glossaire (2026-08-23).
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur la liste RÉELLE des 502 slugs publiés
 * extraite du sitemap - jamais en sondant une URL devinée, qui ne prouve que la liberté d'un
 * slug et non l'absence d'une notion (cf. le cas "gemini" / "gemini-google", 2026-08-22).
 * Résultat : aucun terme "aeon" ni "blackfrost". En revanche "the-postgresql-license" existait
 * déjà, et un contrôle naïf sur le motif "postgres" aurait crié au doublon. Ce n'en est pas un :
 * une LICENCE et un LOGICIEL sont deux notions distinctes, et un lecteur qui cherche l'une n'est
 * pas satisfait par l'autre. Les deux fiches sont donc reliées par narrower_slugs plutôt que
 * fusionnées, et la FAQ de "postgresql" lève explicitement la confusion.
 *
 * FAITS VÉRIFIÉS À LA SOURCE PRIMAIRE, jamais sur un résumé d'oracle : les comptes, décomptes de
 * modèles et de téléchargements viennent d'appels directs à l'API de Hugging Face le 2026-08-23
 * (AEON-7, Blackfrost-AI 28 modèles, Blackfrost-Research 15 modèles, Qwen3.8-27B-ABLITERATED-GGUF
 * à 224 498 téléchargements). Les 6 URL de sources ont été appelées une à une (HTTP 200) avant
 * d'être inscrites : aucune n'est devinée.
 *
 * NOTE DE MÉTHODE : la recherche généraliste sur "AEON" n'avait remonté QUE les sens robot
 * humanoïde, bibliothèque Python et jeton cryptographique. Le sens réellement pertinent pour ce
 * site - l'étiquette de nommage des variantes décensurées - n'est apparu qu'en interrogeant
 * l'API de Hugging Face. L'oracle répond à la question posée ; la source primaire dit ce qui
 * existe. Les deux sens homonymes sont conservés dans la fiche, c'est le rôle d'un glossaire.
 *
 * match_strategy "case_sensitive" pour AEON, et c'est délibéré : quatre lettres, dont la forme
 * minuscule "aeon" est un mot latin courant et le nom d'un paquet Python. En "loose", l'auto-lien
 * du site aurait attrapé des occurrences sans rapport. Les deux autres termes sont des chaînes
 * distinctives et restent en "loose".
 *
 * Images (aeon, blackfrost, postgresql : .jpg + .webp, 1200x669) déposées dans
 * public/images/glossaire/ AVANT cette migration - has_image=true dès le départ.
 *
 * Données dans database/data/glossaire-batch-2026-08-23-aeon-blackfrost-postgresql.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, la migration est donc rejouable.
 * RÉVERSIBLE : down() supprime UNIQUEMENT les trois slugs ajoutés ici.
 */
return new class extends Migration
{
    private const SLUGS = ['aeon', 'blackfrost', 'postgresql'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-23-aeon-blackfrost-postgresql.json';
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
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 930 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Relation retour : la fiche de la licence pointe vers le logiciel, pour que le lecteur
        // arrivé sur l'une trouve l'autre. Additif : la liste existante est relue puis complétée,
        // jamais remplacée, afin de ne pas détruire une relation posée à la main.
        $licence = Term::where('slug->fr_CA', 'the-postgresql-license')->first()
            ?? Term::where('slug->fr', 'the-postgresql-license')->first();
        if ($licence) {
            $licence->broader_slugs = array_values(array_unique(array_merge(
                $licence->broader_slugs ?? [], ['postgresql']
            )));
            $licence->save();
            echo "[glossaire] the-postgresql-license relié à postgresql\n";
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

        // Retire UNIQUEMENT le slug ajouté par cette migration, en préservant le reste.
        $licence = Term::where('slug->fr_CA', 'the-postgresql-license')->first();
        if ($licence) {
            $licence->broader_slugs = array_values(array_diff($licence->broader_slugs ?? [], ['postgresql']));
            $licence->save();
        }
    }
};
