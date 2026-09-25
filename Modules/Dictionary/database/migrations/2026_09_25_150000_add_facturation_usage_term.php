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
 * Ajout du terme GENERAL « Facturation à l'usage » (usage-based billing), demande par brief du
 * 2026-09-25. Terme ressorti du scan de la fiche d'actualite 59018 (Microsoft Copilot) comme non
 * couvert par le glossaire.
 *
 * CONTROLE ANTI-DOUBLON EXECUTE LE 2026-09-25, par FAMILLE de motifs et non par un seul mot,
 * contre le plan de site de PRODUCTION (3157 URL) sur les trois familles glossaire + acronymes +
 * annuaire : factur|usage|consomm|tarif|pay-as-you-go|abonnement|credit|jeton|token|quota|
 * forfait|prix|cout|pricing|metered|billing|on-demand|pay-per|saas|subscription. Seul candidat
 * de fond ouvert et LU (pas seulement son slug) : glossaire/saas (« Le SaaS est un modele de
 * distribution ou les applications d'IA sont hebergees dans le nuage et accessibles via un
 * abonnement internet ») - notion VOISINE (un mode de LIVRAISON logicielle) mais distincte d'un
 * mode de TARIFICATION; la page mentionne « facturation » une fois et « usage » deux fois en
 * passant, sans definir le mecanisme. Aucun terme « tarification »/« modele tarifaire »/
 * « cout par token » n'existe. Conclusion : fiche NOUVELLE, aucun lien broader_slugs/
 * narrower_slugs pose (aucune relation hierarchique reelle identifiee - forcer un lien vers
 * « saas » aurait ete une fausse parente : la facturation a l'usage existe aussi hors SaaS,
 * ex. cloud/telecom).
 *
 * RECHERCHE : mcp__perplexity-pro-playwright__pp_search (date du jour 2026-09-25). Les 4 URLs de
 * `sources` ont ete verifiees par requete REELLE (curl -sI -L, codes 200) juste avant redaction,
 * et leur date de publication/mise a jour reelle lue dans le HTML (JSON-LD datePublished ou
 * meta ms.date), jamais supposee.
 *
 * VALIDATION CROISEE : redaction deleguee a mcp__hermes__model_invoke (task_type=writing, sorti
 * chez moonshotai/kimi-k2.6 via la cascade OpenRouter), avec les faits de recherche transmis
 * comme CONTENU_TIERS_NON_FIABLE delimite (jamais comme instruction), interdiction explicite
 * d'inventer un fait hors de ce bloc. Chiffres cles (tarif 0,01 $ US/credit, date du
 * 2 novembre 2026, produits vises Copilot Cowork/Work IQ APIs/GitHub Copilot Harness) confirmes
 * par 2 sources Microsoft independantes (guide PDF + annonce Partner Center) qui convergent.
 *
 * ICONE : 🧮 (boulier/calcul), coherent avec un terme de mecanisme de calcul/facturation, jamais
 * generique.
 *
 * ALIAS : formes reellement en usage dans la litterature du domaine (anglicisme source, variantes
 * FR documentees) - toutes des locutions composees de 2 a 4 mots, aucun risque de collision avec
 * un mot commun isole (contrairement au piege « clefs d'acces »/« AMOS » deja mesure sur ce
 * projet). match_strategy laisse a 'loose' (defaut) : aucune de ces locutions ne designe autre
 * chose ailleurs sur le site.
 *
 * Typographie quebecoise (OQLF) : espaces insecables (U+00A0) posees avant chaque deux-points et
 * entre un nombre et le symbole $ (« 0,01 $ US »), aucune espace avant ; ! ?, guillemets droits
 * uniquement, aucun tiret cadratin. Controle `grep -nP '(?<! ) :|\s+[;!?]'` execute sur le texte
 * brut avant integration : aucune ligne renvoyee (verifie a nouveau apres generation du texte,
 * la delegation MCP a reproduit la ponctuation francaise standard, corrigee programmatiquement).
 *
 * Migration idempotente : un slug deja present n'est pas recree.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'facturation-a-l-usage';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => "Facturation à l'usage",
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'outils-et-techniques',
            'definition' => "La facturation à l'usage est un modèle où le client paie selon une mesure réelle de consommation plutôt qu'un montant récurrent fixe. Contrairement à l'abonnement à tarif fixe qui facture le même montant peu importe l'utilisation, ce modèle applique la formule quantité consommée multipliée par le prix unitaire, souvent avec des paliers de dégressivité. Dans le domaine de l'IA, l'unité dominante est le token, avec des tarifs distincts pour l'entrée (prompts) et la sortie (réponses générées), cette dernière étant généralement plus coûteuse en raison de la puissance de calcul requise. Les fournisseurs d'API de modèles de langage publient leurs prix au million de tokens. À compter du 2 novembre 2026, les nouvelles licences Microsoft 365 Copilot Business achetées via le programme CSP adoptent par défaut cette approche via des Copilot Credits mutualisés à l'échelle du tenant, au tarif de 0,01\u{00A0}\$ US par crédit, pour les charges de travail agentiques comme Copilot Cowork, les Work IQ APIs et GitHub Copilot Harness.",
            'analogy' => "C'est comme une imprimerie qui vous facture chaque page photocopiée au lieu d'exiger un forfait mensuel illimité.",
            'example' => "À compter du 2 novembre 2026, Microsoft facturera ses licences Copilot Business via des Copilot Credits à 0,01\u{00A0}\$ US l'unité, pour Copilot Cowork, les Work IQ APIs et GitHub Copilot Harness.",
            'did_you_know' => "Sous le modèle Microsoft 2026, les assistants dans Word, Excel, Teams ou Outlook restent inclus par utilisateur; seuls les agents autonomes et les appels API génèrent des crédits payants.",
            'one_sentence_answer' => "La facturation à l'usage fait payer le client en fonction d'une mesure réelle de consommation, comme les jetons traités par une API d'IA ou les crédits consommés par des agents autonomes, plutôt qu'un abonnement à tarif fixe.",
            'faq' => [
                    [
                        'question' => "Est-ce que ma facture peut grimper de façon imprévisible?",
                        'answer' => "Oui, c'est un risque concret\u{00A0}: si la consommation grimpe soudainement, la facture grimpe aussi, contrairement à un abonnement à tarif fixe dont le montant récurrent est identique chaque mois.",
                    ],
                    [
                        'question' => "Quelle est la différence avec un abonnement mensuel classique?",
                        'answer' => "Un abonnement facture le même montant récurrent peu importe l'usage. La facturation à l'usage applique plutôt la formule quantité consommée multipliée par le prix unitaire, souvent avec des paliers de dégressivité.",
                    ],
                    [
                        'question' => "Comment budgéter si mes coûts changent chaque mois?",
                        'answer' => "Les coûts suivent la valeur réelle créée, mais ils sont moins prévisibles qu'un montant récurrent fixe. Certains éditeurs proposent des paliers de dégressivité qui réduisent le prix unitaire lorsque la consommation augmente.",
                    ],
            ],
            'sources' => [
                    [
                        'label' => "Stratégie de tarification à l'usage pour le SaaS, qui définit le modèle et le compare à l'abonnement à tarif fixe",
                        'url' => 'https://stripe.com/ae/resources/more/usage-based-pricing-strategy-for-saas',
                        'year' => 2026,
                        'author' => 'Stripe',
                    ],
                    [
                        'label' => 'Guide officiel des Copilot Credits, qui détaille le tarif de 0,01 $ US par crédit et les charges de travail visées',
                        'url' => 'https://cdn-dynmedia-1.microsoft.com/is/content/microsoftcorp/microsoft/bade/documents/products-and-services/en-us/ai/Copilot-Credits-Guide-September-2026.pdf',
                        'year' => 2026,
                        'author' => 'Microsoft',
                    ],
                    [
                        'label' => 'Annonce Partner Center de la facturation à l\'usage par défaut pour les nouvelles licences Microsoft 365 Copilot Business dès le 2 novembre 2026',
                        'url' => 'https://learn.microsoft.com/en-us/partner-center/announcements/2026-september',
                        'year' => 2026,
                        'author' => 'Microsoft',
                    ],
                    [
                        'label' => "Analyse de la tarification par token des API d'OpenAI, entrée versus sortie",
                        'url' => 'https://www.cloudzero.com/blog/openai-pricing/',
                        'year' => 2026,
                        'author' => 'CloudZero',
                    ],
            ],
            'aliases' => [
                    'usage-based billing',
                    'facturation à la consommation',
                    "tarification à l'usage",
                    "paiement à l'usage",
                    'pay-as-you-go',
            ],
            'broader_slugs' => [],
            'narrower_slugs' => [],
            'difficulty' => 'beginner',
            'icon' => '🧮',
            'type' => 'ai_term',
            'match_strategy' => 'loose',
        ];
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

            return;
        }

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
        // Illustration validee par controle oracle en aveugle (deux familles de modeles independantes,
        // 2 questions fermees chacun) le 2026-09-25 : aucun texte, aucun logo, aucune personne,
        // metaphore devinee correctement sans indice ("mesure d'une consommation", "facturation
        // a l'usage").
        $term->hero_image = 'images/glossaire/facturation-a-l-usage.webp';
        $term->is_published = true;
        $term->sort_order = 1011;
        $term->save();

        echo "[glossaire] terme ajoute : {$t['slug']}\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo "[glossaire] terme retire : " . self::NEW_SLUG . "\n";
    }
};
