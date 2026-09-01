<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 6 termes au glossaire (2026-09-01) - six souches d'infostealers (voleurs
 * d'informations) : Vidar, LummaC2, StealC, RedLine Stealer, Acreed, Atomic Stealer.
 *
 * MANDAT : « une fiche pour chaque ou 1 si pertinent, tu décides ». Décision documentée
 * intégralement dans storage/app/glossaire-runs/voleurs-2026-09-01/faits.md (checkpoint
 * écrit en cours de session) et résumée ici.
 *
 * ANTI-DOUBLON (fait AVANT rédaction, sur le sitemap de PRODUCTION réel, jamais une base
 * locale partielle) : `curl https://laveille.ai/sitemap.xml` -> 523 fiches glossaire,
 * grep -iE "steal|malware|virus|trojan|ransom|cyber|puffer|redline|vidar|lumma|acreed|
 * atomic" -> cybersecurite, pamstealer, malware, virus-informatique, jadepuffer,
 * cybermenaces. AUCUN des 6 slugs cibles n'existait. Aucune fiche générique
 * "infostealer" non plus (constat, pas une lacune comblée ici - hors mandat, voir plus bas).
 *
 * DÉCISION DE GRANULARITÉ : SIX FICHES DISTINCTES (pas une fiche générique, pas de
 * fusion partielle). Fondée sur des faits distincts et datés par souche (sources
 * Europol/DOJ/Microsoft/vendors, toutes vérifiées HTTP 200 avant rédaction) :
 *  - Vidar : 2018, jamais démantelé, v2.0 octobre 2025, en hausse en 2026.
 *  - LummaC2 : 2022, démantelé 21 mai 2025 (Microsoft+Europol+DOJ, 394 000+ machines),
 *    résurgence rapportée en 2026.
 *  - StealC : 2023, démantelé 24 juin 2026 (Europol, opération ENDGAME, avec SocGholish
 *    et Amadey) - le plus récent des six démantèlements.
 *  - RedLine Stealer : démantelé octobre 2024 (opération Magnus, administrateur présumé
 *    Maxim Rudometov inculpé par le DOJ américain).
 *  - Acreed : apparu 14 février 2025, ÉMERGÉ SPÉCIFIQUEMENT pour combler le vide laissé
 *    par les démantèlements de Lumma et RedLine.
 *  - Atomic Stealer (AMOS) : seul des six à cibler macOS (tous les autres visent
 *    Windows), jamais démantelé, actif depuis avril 2023.
 *
 * CONSULTATION DES ORACLES (1 round borné, comme demandé - pas un club des sages à 3
 * rounds, la question étant factuelle et bornée) : Codex (six fiches distinctes, reliées
 * à une fiche générique non créée ici) ; Perplexity (six fiches dédiées + suggestion
 * d'une fiche panorama, non créée ici) ; DeepSeek via Hermes (mélange : dédiées pour
 * Atomic Stealer/Vidar/Acreed, groupée pour LummaC2+RedLine+StealC) ; Gemini via agy
 * (mélange différent : dédiées pour Atomic Stealer/Vidar seulement, groupée pour
 * RedLine+LummaC2+StealC+Acreed). Divergence RÉELLE nommée, pas moyennée en silence :
 * les 4 oracles s'accordent SEULEMENT sur Atomic Stealer et Vidar en fiches dédiées : les
 * 2 oracles "groupeurs" ne s'accordent PAS entre eux sur le découpage du reste, ce qui est
 * en soi un signal que leur critère ("démantelé") ne suffit pas à rendre deux fiches
 * redondantes. Décision finale (Claude, éclairée mais pas dictée) : SIX FICHES
 * DISTINCTES, motifs détaillés dans le rapport de session et dans le fichier de
 * checkpoint - notamment le précédent local « PamStealer » (stealer macOS bien plus
 * mineur qu'aucun des six, et pourtant déjà doté de sa propre fiche sur ce glossaire) et
 * la logique AEO (one_sentence_answer doit rester vraie hors contexte, ce qu'une fiche
 * groupée ne permet plus pour un nom précis). Pas de 7e fiche générique "infostealer"
 * créée (suggérée par 2 oracles) : hors mandat explicite, notée comme bon candidat futur.
 *
 * ALIAS - dangers identifiés et écartés (voir skill /glossaire section « alias
 * dangereux ») :
 *  - RedLine : alias=["RedLine"] (camelCase, orthographiquement distinct de l'idiome
 *    "red line" et du terme "redlining"/discrimination bancaire). EXCLUS
 *    délibérément : "Redline" (une seule majuscule) et "red line" (minuscule, 2 mots).
 *  - Atomic Stealer : alias=["Atomic macOS Stealer"] uniquement. EXCLUS délibérément :
 *    "Atomic" seul (mot courant, collision énorme) ET "AMOS" - ce dernier écarté pour
 *    une raison qui dépasse le risque nommé dans le mandat : Amos est une VRAIE
 *    municipalité du Québec (Abitibi-Témiscamingue), un nom propre capitalisé au même
 *    titre que le sigle du stealer - le match_strategy=case_sensitive ne protège PAS
 *    ici (piège "nom propre contre nom propre" déjà documenté dans le skill pour
 *    "Codex"/"Codex Alimentarius"). "AMOS" reste mentionné en toutes lettres dans le
 *    texte de `definition` mais n'est jamais un alias cliquable/matchable.
 *  - Vidar, LummaC2, StealC, Acreed : aucune collision dangereuse identifiée ; aliases
 *    limités aux formes longues réellement rencontrées dans les sources (StealC et
 *    Acreed n'ont aucune variante confirmée par la recherche - aliases omis, pas oubliés).
 *  - match_strategy=case_sensitive posé UNIFORMÉMENT sur les 6 (posture défensive : ne
 *    coûte rien sur un nom propre déjà capitalisé, bloque toute collision minuscule).
 *
 * RELATIONS - broader_slugs=["malware"] pour les 6 (catégorie sécurité déjà utilisée par
 * pamstealer/cheval-de-troie/virus-informatique/ver-informatique). Réciproque posée dans
 * up() : les 6 slugs ajoutés au narrower_slugs de "malware" (précédent : jadepuffer sur
 * rancongiciel/ia-agentique). "Termes associés" de la vue publique sont auto-calculés par
 * catégorie (PublicDictionaryController::show, même dictionary_category_id, limit 5) :
 * les 6 termes se retrouveront automatiquement entre eux et avec leurs voisins de
 * sécurité, aucun champ manuel supplémentaire à remplir pour ça.
 *
 * SOURCES - chaque URL vérifiée HTTP 200 par curl direct avant rédaction (voir détail par
 * terme dans database/data/glossaire-batch-2026-09-01-infostealers.json, champ
 * `sources`) : Acronis TRU + Trend Micro (Vidar) ; Microsoft Digital Crimes Unit +
 * Microsoft Threat Intelligence (LummaC2) ; Europol + SEKOIA (StealC) ; département de la
 * Justice des États-Unis + ESET Research (RedLine Stealer) ; Bitsight + Intrinsec
 * (Acreed) ; Cyble + Trend Micro (Atomic Stealer). La date de publication de la source
 * Cyble a été vérifiée directement dans les métadonnées de la page (datePublished =
 * 2023-04-26), pas seulement dans le résumé du moteur de recherche.
 *
 * SUJET SENSIBLE (logiciels malveillants réels) : chaque fiche explique ce que c'est et
 * comment s'en protéger, ZÉRO indication opérationnelle sur l'obtention, la configuration
 * ou l'usage de ces outils, aucun lien vers un dépôt malveillant, aucun nom de place de
 * marché au-delà du fait historique déjà public (Russian Market, cité par les sources
 * elles-mêmes), aucun indicateur de compromission exploitable. Lecteur visé : enseignant
 * ou gestionnaire, jamais un attaquant.
 *
 * RÉDACTION : contenu drafté par mcp__hermes__model_invoke (task_type=reasoning, faits
 * verrouillés injectés dans le prompt, interdiction explicite d'inventer), VALIDÉ champ
 * par champ contre les faits vérifiés ci-dessus, corrigé à la main (casse Vidar/LummaC2
 * normalisée depuis une sortie tout-majuscule du modèle, nuance ajoutée sur la question
 * FAQ « StealC est-il toujours une menace » pour éviter un « non » catégorique non
 * prouvé, définitions de LummaC2 et StealC allongées pour atteindre la fourchette cible).
 * Typographie OQLF (espace insécable réelle U+00A0 avant ':', dans les milliers "394 000"
 * et autour des guillemets français, AUCUNE espace même insécable avant ';' '!' '?')
 * appliquée programmatiquement puis vérifiée par script - zéro tiret cadratin détecté.
 *
 * Données dans database/data/glossaire-batch-2026-09-01-infostealers.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime les 6 termes ET retire leurs slugs du narrower_slugs de
 * "malware", sans toucher au reste de ses données.
 */
return new class extends Migration
{
    private const SLUGS = ['vidar', 'lummac2', 'stealc', 'redline', 'acreed', 'atomic-stealer'];

    private const RELATED_BROADER = ['malware'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-09-01-infostealers.json';
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
            // Image : générée séparément via /nanobanana (Playwright, compte Gemini) et
            // committée par le même agent - hero_image posé seulement si has_image ET si
            // la paire webp/jpg est réellement suivie par git au moment du déploiement.
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 970 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Rattache les 6 nouveaux slugs en retour dans le narrower_slugs de "malware"
        // (append non destructif sur l'état lu à l'exécution, précédent jadepuffer).
        foreach (self::RELATED_BROADER as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                echo "[glossaire] terme lié absent, skip narrower_slugs : {$slug}\n";

                continue;
            }
            $existing = is_array($related->narrower_slugs) ? $related->narrower_slugs : [];
            $added = false;
            foreach (self::SLUGS as $newSlug) {
                if (! in_array($newSlug, $existing, true)) {
                    $existing[] = $newSlug;
                    $added = true;
                }
            }
            if ($added) {
                $related->narrower_slugs = array_values($existing);
                $related->save();
                echo "[glossaire] narrower_slugs mis à jour : {$slug} (+".implode(',', self::SLUGS).")\n";
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::RELATED_BROADER as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                continue;
            }
            $existing = is_array($related->narrower_slugs) ? $related->narrower_slugs : [];
            $related->narrower_slugs = array_values(array_diff($existing, self::SLUGS));
            $related->save();
        }

        foreach (self::SLUGS as $slug) {
            Term::where('slug->fr_CA', $slug)->delete();
        }
    }
};
