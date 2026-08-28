<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "WorkOS" au glossaire (2026-08-27) - l'entreprise américaine qui vend aux
 * éditeurs de logiciels les briques d'authentification d'entreprise (enterprise readiness).
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur les 516 slugs RÉELS du sitemap de production
 * (jamais un sondage d'URL devinée) : aucun slug ne contient "workos", "sso", "oauth", "saml",
 * "scim" ni "identite"/"authentification" en tant que TERME - seuls "sso" et "mfa" existent déjà,
 * comme notions génériques distinctes (ni l'une ni l'autre ne mentionne "WorkOS" en texte, lu dans
 * leur HTML rendu). Recherche élargie par CONCEPT contre la production (pas seulement par slug,
 * cf. piège du 2026-08-21) : requête live /recherche/palette?q=workos -> total=0, AUCUNE section
 * "glossaire". Une seconde requête casse-sensible /recherche/palette?q=WorkOS -> total=1, mais dans
 * la section "annuaire" (fiche "MonoCloud for Startups", un concurrent qui cite WorkOS), jamais en
 * "glossaire". Décision : aucun doublon, notion absente, fiche nouvelle.
 *
 * RELATIONS : broader_slugs=[] et narrower_slugs=[], choix délibéré et non un oubli. "sso" et "mfa"
 * sont des notions GÉNÉRIQUES bien plus anciennes que WorkOS (SAML date de 2005, le MFA bien avant) -
 * les traiter comme narrower_slugs de WorkOS aurait surattribué leur paternité à l'entreprise
 * (même logique que le refus d'attacher "rlhf"/"alignement-ia" à "anthropic" dans la migration
 * soeur du même jour). Et WorkOS n'est pas un "type de" SSO au sens où Codex est un produit
 * d'OpenAI : c'est un fournisseur parmi d'autres d'une brique parmi plusieurs (SSO, SCIM, MFA,
 * audit), donc aucune relation broader non plus. La connectivité se fait par le TEXTE : la
 * définition mentionne littéralement "authentification unique (SSO)" et "authentification
 * multifacteur (MFA)", que le linkifier devrait relier automatiquement aux fiches existantes
 * /glossaire/sso et /glossaire/mfa EN PRODUCTION (ces deux termes n'existent PAS dans la base
 * LOCALE - confirmé par une requête Term::where() qui renvoie null - donc ce lien précis n'a PAS
 * pu être vérifié empiriquement ici, seulement raisonné : sso/mfa forcent case_sensitive comme
 * tout acronyme court tout-cap 3-4 caractères, et le texte écrit "(SSO)"/"(MFA)" respecte cette
 * casse exacte) - sans avoir besoin de forcer un lien hiérarchique inexact dans les champs
 * structurés.
 *
 * L'AMBIGUÏTÉ DE NOM EST LE COEUR DU RISQUE D'AUTO-LIEN (mandat explicite) : "WorkOS" est visible
 * à l'oeil comme une variante capitalisée de "works"/"work"/"workflow", des mots anglais/dérivés
 * courants dans la prose technique du site. VÉRIFIÉ dans le code réel de
 * Modules/Core/app/Services/GlossaryLinkifier.php avant de conclure :
 *  - extractMorphologicalAliases("WorkOS") ne dérive QUE "workos" (minuscule) et "Workos"
 *    (ucfirst/titled) - le mot se termine par "OS", donc le test /(s|x)$/iu le traite comme déjà
 *    pluriel et AUCUN pluriel supplémentaire n'est généré (pas de "WorkOSs").
 *  - matchInText() construit un motif avec frontières de mot strictes de part et d'autre
 *    ((?<![\p{L}\p{N}._\-\/]) avant, (?![\p{L}\p{N}_\-\/]|\.\w) après) : le nom complet "WorkOS"
 *    (6 caractères, casse mixte) ne peut matcher comme SOUS-CHAÎNE d'aucun mot plus long, et la
 *    séquence littérale "workos"/"Workos"/"WorkOS" n'apparaît dans AUCUN des mots "work"/"works"/
 *    "workflow" (lettres différentes après "work"). Le risque narré est donc, à la lecture du code,
 *    structurellement nul - mais VÉRIFIÉ EMPIRIQUEMENT quand même via GlossaryLinkifier::linkify()
 *    sur des phrases pièges réelles (résultats collés dans le rapport de session, pas dans ce
 *    docblock pour ne pas le dupliquer) : "ce workflow fonctionne" et "il works bien" ressortent
 *    SANS lien, "WorkOS lève 100 M$ US" ressort AVEC lien vers /glossaire/workos.
 * ALIAS : AUCUN retenu (name="WorkOS" seul, comme les fiches soeurs "Anthropic" et "OpenAI Codex").
 * match_strategy = case_sensitive choisi en défense en profondeur, à l'image des deux migrations
 * du même jour, même si l'analyse ci-dessus montre qu'un "loose" n'aurait pas non plus collisionné
 * avec "work(s)"/"workflow" (les frontières de mot l'en empêchent) - la casse stricte reste le
 * choix le plus sûr et le moins coûteux à maintenir si le site introduit un jour un mot comme
 * "workos" en minuscule dans un tout autre sens.
 *
 * RECHERCHE - mcp__perplexity-pro-playwright__pp_search, chaque fait recoupé par lecture DIRECTE
 * de la page source (curl avec user-agent navigateur, contenu HTML/JSON-LD lu, pas seulement le
 * résumé Perplexity) :
 *  - Fondation (20 mai 2019, San Francisco, par Michael Grinich, ex-cofondateur de Nylas quitté en
 *    2017) : JSON-LD de https://workos.com/about lu directement ("foundingDate":"2019-05-20",
 *    "founders":[{"name":"Michael Grinich"}]), recoupé par une recherche indépendante sur la
 *    biographie de Grinich (LinkedIn + interview techblogwriter.co.uk).
 *  - Positionnement "enterprise readiness"/"fossé de l'entreprise" et produits d'origine (SSO
 *    SAML, Directory Sync SCIM, Admin Portal) : page officielle
 *    https://workos.com/blog/workos-raises-15m-to-build-stripe-for-enterprise-ready-features lue
 *    intégralement (titre confirmé tel quel, Série A de 15 M$ US annoncée le 10 mars 2021, menée
 *    par Lachy Groom, "deux ans" après la fondation - cohérent avec 2019).
 *  - AuthKit (produit d'authentification, lancé le 28 novembre 2023) : page officielle
 *    https://workos.com/blog/introducing-authkit-and-user-management, lue intégralement.
 *  - Série C (100 M$ US, valorisation 2 Md$ US, 2 mars 2026, menée par Meritech et Sapphire) :
 *    JSON-LD de https://workos.com/blog/series-c lu directement (description exacte confirmée),
 *    recoupé par une source secondaire indépendante lue en entier : SiliconANGLE, Duncan Riley,
 *    3 mars 2026 (siliconangle.com/2026/03/03/...). Fenwick (cabinet juridique) corrobore les mêmes
 *    montants dans la réponse Perplexity mais renvoie HTTP 403 au user-agent navigateur - NON
 *    retenu comme URL de `sources`, seulement comme corroboration de lecture (même prudence que la
 *    fiche "Anthropic" du même jour avec Britannica).
 *  - Clients affichés (OpenAI, Anthropic, Cursor, Perplexity, Replit, Snowflake, Laravel) :
 *    https://workos.com/customers, la liste de noms lue directement dans le HTML/JSON embarqué de
 *    la page (data-name="Cursor", data-name="Perplexity", etc.) - PAS depuis les pages individuelles
 *    /customers/{slug}, qui se sont révélées rendues côté client (curl n'y affiche qu'un gabarit
 *    vide avec un témoignage d'un AUTRE client affiché par erreur de lecture initiale) : une
 *    citation attribuée à Cursor a donc été ÉCARTÉE de la fiche faute de confirmation directe,
 *    remplacée par le seul fait vérifiable (la présence du logo).
 *  - Sens du nom ("Work" + "OS", métaphore de système d'exploitation pour le logiciel d'entreprise,
 *    jamais "operating system" au sens littéral) : synthèse Perplexity sourcée sur sacra.com et sur
 *    la page officielle de la Série A elle-même (le texte "operating system" n'apparaît pas mot
 *    pour mot sur cette page ; l'explication du nom vient de la source secondaire sacra.com, non
 *    retenue en `sources` faute de date de publication claire - traité comme lecture de fond,
 *    formulé prudemment dans `did_you_know` sans citer sacra.com comme source primaire).
 *
 * ÉCARTÉ DÉLIBÉRÉMENT : le chiffre "plus de 200 clients payants" (juin 2022, Série B) et le rachat
 * de Modulz/Radix Primitives, anciens et hors du cadre "entreprise + faits datés récents" retenu
 * pour une fiche de longueur standard - non nécessaires pour comprendre ce qu'est WorkOS aujourd'hui.
 *
 * Angle retenu : ENTREPRISE + produit (comme "Anthropic"/"OpenAI Codex" du même jour) - fondation,
 * positionnement "enterprise readiness", produits (SSO/SCIM/MFA/AuthKit), traction récente (Série C
 * 2026). Neutralité : le positionnement "enterprise readiness" est présenté comme le pari commercial
 * de l'entreprise, jamais comme une nécessité technique universelle incontestée.
 *
 * Typographie OQLF (espace insécable U+00A0 RÉELLE avant ":", jamais l'entité &nbsp; - piège nommé
 * dans la mémoire du projet, une variable NB mal transportée avait d'abord produit une espace ASCII
 * ordinaire, corrigé par échappement Unicode explicite (le caractère U+00A0 posé via son point
 * de code, jamais tapé littéralement) puis revérifié octet par octet) :
 * contrôle grep -nP '(?<! ) :|\s+[;!?]' passé sur le fichier de données -> aucune correspondance.
 *
 * Image : public/images/glossaire/workos.{webp,jpg} déposée AVANT cette migration (1200x669,
 * compressée magick+cwebp), générée via /nanobanana (Playwright, compte stephane@memora.ca,
 * gemini.google.com), inspectée visuellement avant application. Métaphore visuelle abstraite :
 * aucun logo WorkOS réel, aucune marque, aucun texte lisible.
 *
 * Données dans database/data/glossaire-batch-2026-08-27-workos.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime uniquement ce terme (aucune relation externe créée par up()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-workos.json';
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
            $term->sort_order = 960 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
