<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Greg Brockman" au glossaire (2026-08-26), cofondateur et président d'OpenAI.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur la liste RÉELLE des 506 slugs publiés extraite
 * du sitemap de production (jamais en sondant une URL devinée - cf. le piège "gemini" /
 * "gemini-google", 2026-08-22). Résultat : aucun slug ne contient "brockman" ni "greg" ; "openai"
 * existe déjà (rattaché via broader_slugs) et "sam-altman" sert de gabarit de fiche-personne
 * (lu en production via la page rendue, la fiche n'existant pas en migration locale traçable).
 *
 * STATUT VÉRIFIÉ EN CROISÉ (obligatoire pour une personne réelle vivante, faits datés uniquement,
 * aucune caractérisation non attribuée) : Perplexity (sonar-pro, via openrouter), Codex
 * (recherche web réelle, plusieurs requêtes), Wikipédia (infobox + section Carrière, avec ses
 * propres notes de bas de page datées), et une lecture DIRECTE de la page WIRED elle-même
 * (Maxwell Zeff, 15 mai 2026, HTTP 200 vérifié) qui cite OpenAI : « OpenAI cofounder and
 * president Greg Brockman will now lead the company's product strategy, in addition to his work
 * on AI infrastructure ». Les quatre s'accordent : au 26 août 2026, Brockman est TOUJOURS
 * cofondateur et président d'OpenAI - son rôle s'est élargi (pas réduit) après le départ d'autres
 * dirigeants (dont le congé MÉDICAL de Fidji Simo, qu'il ne faut pas confondre avec son propre
 * congé SABBATIQUE de 2024, motif différent et vérifié séparément). La fiche ne contredit donc
 * pas une actualité parallèle sur les départs de dirigeants OpenAI : Brockman n'en fait pas
 * partie, il consolide au contraire son rôle.
 *
 * Un premier résultat (multi-ai-mcp, repli openrouter-free sans recherche live, coupure de
 * connaissances ~janvier 2025) affirmait à tort qu'il aurait quitté la présidence en janvier
 * 2025 - affirmation non sourcée, réfutée par les deux recherches web réelles (Perplexity et
 * Codex) et par la lecture directe de l'article WIRED. Écartée.
 *
 * Les 3 URLs de sources ont été appelées une par une (HTTP 200, en-tête navigateur complet)
 * avant d'être inscrites : TechCrunch et le blogue personnel de Brockman répondent 200
 * directement ; WIRED a été lu intégralement (body + JSON-LD datePublished/author). Les URLs
 * openai.com, axios.com, time.com et investing.com bloquent systématiquement curl (403/406,
 * protection anti-robot) malgré des en-têtes de navigateur complets - ce sont des faits
 * corroborés par 2 recherches web indépendantes avec citations, pas des URLs devinées ; elles ne
 * sont pas inscrites dans `sources` faute de vérification HTTP directe possible dans cette
 * session (outils browser_* absents).
 *
 * ALIAS - piège explicitement évité, et VÉRIFIÉ EMPIRIQUEMENT (pas seulement par précaution
 * théorique) : "Greg" seul est ÉCARTÉ d'office (prénom seul refusé, suite à l'incident du soir
 * même où "Sam", extrait de "Sam Bowman", a lié une actualité vers la fiche de Sam Altman).
 * "Brockman" seul a d'abord été envisagé par analogie avec "Altman" seul (déjà alias de la fiche
 * sam-altman), avec match_strategy=case_sensitive comme garde-fou. Test réel via
 * GlossaryLinkifier::linkify() sur la phrase "David Brockman, politologue à Stanford, a publié
 * une étude différente." : la phrase a été liée vers /glossaire/greg-brockman - case_sensitive ne
 * protège QUE contre une collision en minuscules, jamais contre un autre "Brockman" réel écrit
 * avec la casse normale. Alias RETIRÉ suite à cette preuve (section 6 du skill /glossaire :
 * vérifier les auto-liens déclenchés AILLEURS, pas seulement sur la page du terme). Seul
 * "G. Brockman" est conservé (forme abrégée distinctive, aucune collision plausible testée).
 * match_strategy reste case_sensitive par prudence résiduelle, sans coût : le nom complet
 * "Greg Brockman" et "G. Brockman" apparaissent toujours correctement capitalisés en texte réel.
 *
 * broader_slugs=['openai'] : rattache la fiche plutôt que de la laisser orpheline (openai existe
 * en production, confirmé dans le relevé sitemap).
 *
 * Images (greg-brockman.jpg / .webp, 1200x669, compressées selon le standard du skill) déposées
 * dans public/images/glossaire/ AVANT cette migration - has_image=true dès le départ. Métaphore
 * institutionnelle abstraite (serveur/infrastructure, aucune personne, aucun logo) via
 * stock-photos (Pexels, panumas nikhomkhai) - jamais de portrait synthétique d'une personne
 * réelle, conformément à la règle absolue du fondateur.
 *
 * Données dans database/data/glossaire-batch-2026-08-26-greg-brockman.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, la migration est donc rejouable.
 * RÉVERSIBLE : down() supprime UNIQUEMENT le slug ajouté ici.
 */
return new class extends Migration
{
    private const SLUGS = ['greg-brockman'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-26-greg-brockman.json';
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
