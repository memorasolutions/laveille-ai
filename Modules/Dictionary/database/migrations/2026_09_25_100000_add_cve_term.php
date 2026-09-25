<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ajout du terme GENERAL « CVE » (Common Vulnerabilities and Exposures), demande par brief du
 * 2026-09-25. Le sigle apparait dans environ 10 contenus publies (7 actualites, 3 billets de
 * blogue) sans qu'aucune fiche generale ne le definisse - seule une INSTANCE existait
 * (cve-2026-34197, une vulnerabilite Apache ActiveMQ precise).
 *
 * CONTROLE ANTI-DOUBLON EXECUTE LE 2026-09-25, par FAMILLE de motifs et non par un seul mot,
 * contre les 554 slugs du plan de site de PRODUCTION (glossaire) PLUS les familles acronymes et
 * annuaire : cve|vulnerab|faille|cvss|nvd|mitre|zero-day|0-day|exploit|correctif|patch|cwe|owasp|
 * bug-bounty|bounty, complete par vulnera|securite|cybersecurite|hack|breach|malware|ransomware|
 * attaque. Quatre candidats ouverts et LUS (pas seulement leur slug) :
 *   - cve-2026-34197 : une INSTANCE (cette vulnerabilite ActiveMQ precise), pas le terme general.
 *   - mitre-attck (MITRE ATT&CK) : notion VOISINE (tactiques d'attaquants), 0 occurrence de « CVE »
 *     dans sa page - pas un doublon.
 *   - zero-day : notion VOISINE (faille non divulguee), 0 occurrence de « CVE » - pas un doublon.
 *   - owasp-top-10 : notion VOISINE (categories de vulnerabilites web), 0 occurrence de « CVE ».
 *   - cybersecurite : terme PARENT legitime (3 occurrences de « CVE », dont cve-2026-34197 deja
 *     en narrower_slugs) - pas un doublon, c'est le bon broader_slugs.
 * Familles acronymes-education et annuaire egalement grattees : seul faux positif de sous-chaine
 * trouve, acronymes-education/qacve (« QACVE », un colloque en formation professionnelle
 * anglophone - aucun rapport), et annuaire/cve-2026-40369-... (une autre INSTANCE, dans le module
 * Directory, hors perimetre du glossaire). Aucune fiche generale « CVE » n'existe sous aucun nom.
 * Conclusion : fiche NOUVELLE.
 *
 * RELATIONS DE GRAPHE, mesurees sur le JSON-LD reellement servi en production avant modification :
 *   - cve-2026-34197.broader_slugs valait ['cybersecurite'] (lien direct, sans terme intermediaire).
 *   - cybersecurite.narrower_slugs listait sept enfants dont 'cve-2026-34197' directement.
 * Cette migration INSERE 'cve' avec broader_slugs=['cybersecurite'] et narrower_slugs
 * =['cve-2026-34197'], PUIS reroute l'instance existante et son parent pour que la hierarchie
 * devienne cybersecurite -> cve -> cve-2026-34197 plutot que cybersecurite -> cve-2026-34197 a
 * plat. Chaque reroutage verifie la valeur ATTENDUE avant de l'ecrire (jamais un remplacement
 * aveugle), et down() restaure exactement l'etat mesure ci-dessus.
 *
 * RECHERCHE : mcp__perplexity-pro-playwright__pp_search (date du jour 2026-09-25), sources MITRE
 * (cve.org) et NIST (nvd.nist.gov / nist.gov), plus Centre canadien pour la cybersecurite
 * (cyber.gc.ca) pour l'angle local. Les 4 URLs de `sources` ont ete verifiees par requete REELLE
 * (curl -sI, codes 200) juste avant redaction. cve.org est une application monopage : le serveur
 * renvoie la meme coquille HTML (200, 880 octets) pour toute route valide, ce qui ne prouve pas a
 * lui seul le contenu de la page - la teneur de chaque page a ete confirmee par le rendu navigateur
 * de Perplexity, pas seulement par le code HTTP. Un essai sur csrc.nist.gov/glossary/... a echoue
 * (404 reel, pas une coquille SPA) et a ete ECARTE plutot que force.
 *
 * ICONE : identifiant a 3 lettres tout-cap - le linkifier (GlossaryLinkifier::loadTerms, regle
 * #153) force AUTOMATIQUEMENT match_strategy=case_sensitive pour tout nom de 3-4 caracteres
 * majuscules, donc 'CVE' ne pourra jamais matcher un mot minuscule ni un homonyme moins capitalise
 * - le risque de faux positif type toponyme/patronyme (cf. piege « AMOS ») est neutralise par
 * construction, pas seulement par le choix de match_strategy pose explicitement ci-dessous.
 *
 * ALIAS : formes reellement documentees par MITRE/NIST (expansion complete, variantes courantes
 * « identifiant(s) CVE », « CVE ID ») - pas une liste generique, et toutes heritent du
 * case_sensitive du terme principal (voir ci-dessus), donc aucun risque de sur-liaison.
 *
 * Typographie quebecoise (OQLF) : espaces insecables (U+00A0) posees avant chaque deux-points,
 * aucune espace avant ; ! ?, guillemets droits uniquement. Controle
 * `grep -nP '(?<! ) :|\s+[;!?]'` execute sur le texte brut avant integration : aucune ligne
 * renvoyee.
 *
 * Migration idempotente : un slug deja present n'est pas recree. down() retire le terme ajoute et
 * restaure les deux relations reroutees a leur valeur mesuree avant cette migration.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'cve';

    private const INSTANCE_SLUG = 'cve-2026-34197';

    private const PARENT_SLUG = 'cybersecurite';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'CVE',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'securite-et-ethique',
            'definition' => 'CVE, pour Common Vulnerabilities and Exposures, est le programme et le catalogue public qui attribue un identifiant unique et normalisé à chaque vulnérabilité de cybersécurité divulguée publiquement. Créé par l\'organisme américain MITRE, il a été lancé en septembre 1999 avec une première liste de 321 entrées, pour régler un problème concret : avant lui, un même bogue de sécurité portait des noms différents selon l\'éditeur ou l\'outil de détection, ce qui compliquait la coordination. Un identifiant CVE suit le format CVE-année-numéro, par exemple CVE-2026-34197, et sert de clé commune entre les avis de fournisseurs, les scanneurs, les bases de menaces et les correctifs. Le programme ne mesure pas la gravité d\'une faille : ce rôle revient au score CVSS, la classification du type de faiblesse relève du CWE, et l\'enrichissement des fiches (produits touchés, sévérité) est confié à la National Vulnerability Database du NIST. Le catalogue dépasse aujourd\'hui 378 000 enregistrements publics, attribués par plus de 550 organisations réparties dans 43 pays.',
            'analogy' => 'Comme un numéro de dossier judiciaire : peu importe qui en parle, ce numéro pointe toujours vers exactement la même affaire.',
            'example' => 'En mai 2026, l\'identifiant CVE-2026-34197 a désigné une faille critique d\'Apache ActiveMQ permettant l\'exécution de code à distance, exploitée activement et ajoutée au catalogue KEV de l\'agence américaine CISA.',
            'did_you_know' => 'En avril 2025, un imprévu contractuel entre l\'agence américaine CISA et MITRE a menacé d\'interrompre le programme CVE quelques heures avant l\'échéance, provoquant la création de la CVE Foundation.',
            'one_sentence_answer' => 'CVE désigne à la fois le programme et le catalogue public qui attribue à chaque vulnérabilité informatique divulguée un identifiant unique du type CVE-année-numéro, afin que fournisseurs, chercheurs et outils de sécurité parlent tous du même problème.',
            'faq' => [
                    [
                        'question' => 'Un identifiant CVE dit-il si une faille est dangereuse?',
                        'answer' => 'Non. Un CVE identifie une vulnérabilité, il n\'en mesure pas la gravité. Cette évaluation relève du score CVSS, calculé séparément, tandis que la classification du type de faiblesse relève d\'un système distinct, le CWE. Un identifiant CVE peut donc désigner une faille mineure comme une faille critique.',
                    ],
                    [
                        'question' => 'Quelle est la différence entre CVE et la NVD du NIST?',
                        'answer' => 'Le CVE fournit l\'identifiant et une description minimale d\'une vulnérabilité connue. La National Vulnerability Database, gérée par l\'agence américaine NIST, reprend ensuite ces enregistrements et les enrichit : score de gravité, produits et versions touchés, catégorie de faiblesse. Depuis avril 2026, cet enrichissement est priorisé selon le risque plutôt qu\'appliqué systématiquement à chaque nouvelle entrée.',
                    ],
                    [
                        'question' => 'Qui a le droit d\'attribuer un identifiant CVE?',
                        'answer' => 'Seules les organisations reconnues comme CNA, pour CVE Numbering Authority, peuvent réserver et publier un identifiant CVE. Ce réseau regroupe plus de 550 éditeurs, projets libres, équipes d\'intervention et chercheurs répartis dans 43 pays, ce qui permet au programme de traiter un très grand volume de vulnérabilités sans dépendre d\'une seule équipe centrale.',
                    ],
            ],
            'sources' => [
                    [
                        'label' => 'Aperçu officiel du programme CVE, qui décrit sa mission et son fonctionnement',
                        'url' => 'https://www.cve.org/About/Overview',
                        'year' => 2026,
                        'author' => 'CVE Program (MITRE)',
                    ],
                    [
                        'label' => 'Historique du programme CVE, sa création en 1999 et sa première liste de 321 entrées',
                        'url' => 'https://www.cve.org/Resources/Media/Archives/OldWebsite/about/history.html',
                        'year' => 2026,
                        'author' => 'MITRE',
                    ],
                    [
                        'label' => 'Annonce du passage à un modèle d\'enrichissement de la NVD fondé sur le risque, en raison de la croissance record du nombre de CVE',
                        'url' => 'https://www.nist.gov/news-events/news/2026/04/nist-updates-nvd-operations-address-record-cve-growth',
                        'year' => 2026,
                        'author' => 'National Institute of Standards and Technology (NIST)',
                    ],
                    [
                        'label' => 'Page des alertes et avis du Centre canadien pour la cybersécurité, qui relie ses bulletins aux identifiants CVE correspondants',
                        'url' => 'https://www.cyber.gc.ca/fr/alertes-avis',
                        'year' => 2026,
                        'author' => 'Centre canadien pour la cybersécurité',
                    ],
            ],
            'aliases' => [
                    'Common Vulnerabilities and Exposures',
                    'identifiant CVE',
                    'identifiants CVE',
                    'CVE ID',
            ],
            'broader_slugs' => [self::PARENT_SLUG],
            'narrower_slugs' => [self::INSTANCE_SLUG],
            'difficulty' => 'beginner',
            'icon' => '🗂️',
            'type' => 'acronym',
            'match_strategy' => 'case_sensitive',
            'has_image' => true,
        ];
    }

    /**
     * Remplace $from par $to dans le tableau JSON $column du terme $slug, seulement si $from y est
     * present. Retourne true si une ecriture a eu lieu (utile pour l'echo de tracabilite).
     */
    private function swapInJsonSlugArray(string $slug, string $column, string $from, string $to): bool
    {
        $t = Term::where('slug->fr_CA', $slug)->first();
        if (! $t) {
            return false;
        }

        $values = is_array($t->{$column}) ? $t->{$column} : [];
        if (! in_array($from, $values, true)) {
            return false;
        }

        $values = array_values(array_unique(array_map(
            static fn ($v) => $v === $from ? $to : $v,
            $values
        )));

        $t->{$column} = $values;
        $t->save();

        return true;
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modele Term/Category absent, ignore\n";

            return;
        }

        $t = $this->term();
        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

        if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
            echo "[glossaire] slug deja present, skip : {$t['slug']}\n";
        } else {
            $term = new Term();

            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }

            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'];
            $term->broader_slugs = $t['broader_slugs'];
            $term->narrower_slugs = $t['narrower_slugs'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->match_strategy = $t['match_strategy'];
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            // Illustration validee par un controle visuel independant le 2026-09-25.
            $term->hero_image = 'images/glossaire/cve.webp';
            $term->is_published = true;
            $term->sort_order = 1010;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";
        }

        // Reroutage du graphe : l'instance pointait directement vers le parent, elle pointe
        // desormais vers le terme general nouvellement cree.
        if ($this->swapInJsonSlugArray(self::INSTANCE_SLUG, 'broader_slugs', self::PARENT_SLUG, self::NEW_SLUG)) {
            echo "[glossaire] broader_slugs reroute sur " . self::INSTANCE_SLUG . " : " . self::PARENT_SLUG . " -> " . self::NEW_SLUG . "\n";
        }

        if ($this->swapInJsonSlugArray(self::PARENT_SLUG, 'narrower_slugs', self::INSTANCE_SLUG, self::NEW_SLUG)) {
            echo "[glossaire] narrower_slugs reroute sur " . self::PARENT_SLUG . " : " . self::INSTANCE_SLUG . " -> " . self::NEW_SLUG . "\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        // Restaure les deux relations reroutees AVANT de supprimer le terme, dans l'ordre inverse.
        if ($this->swapInJsonSlugArray(self::PARENT_SLUG, 'narrower_slugs', self::NEW_SLUG, self::INSTANCE_SLUG)) {
            echo "[glossaire] narrower_slugs restaure sur " . self::PARENT_SLUG . " : " . self::NEW_SLUG . " -> " . self::INSTANCE_SLUG . "\n";
        }

        if ($this->swapInJsonSlugArray(self::INSTANCE_SLUG, 'broader_slugs', self::NEW_SLUG, self::PARENT_SLUG)) {
            echo "[glossaire] broader_slugs restaure sur " . self::INSTANCE_SLUG . " : " . self::NEW_SLUG . " -> " . self::PARENT_SLUG . "\n";
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo "[glossaire] terme retire : " . self::NEW_SLUG . "\n";
    }
};
