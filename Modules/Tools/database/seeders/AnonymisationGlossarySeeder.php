<?php

declare(strict_types=1);

namespace Modules\Tools\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * #313 Sprint S129 — Création des 2 termes glossaire « Anonymisation » et « Pseudonymisation »
 * référencés par la bannière confiance + JSON-LD de l'outil anonymiseur (404 actuellement).
 * Contenu vérifié pp_search 2026 (CNIL, CAI Québec, ENISA, EPFL, NIST FF1/FF3-1).
 * SEO+AEO+GEO complet : one_sentence_answer + faq + sources + analogy + broader/narrower.
 */
class AnonymisationGlossarySeeder extends Seeder
{
    public function run(): void
    {
        $catId = null;
        try {
            $cat = Category::whereRaw("JSON_EXTRACT(name, '$.fr_CA') = ?", ['"Sécurité et éthique"'])->first()
                ?: Category::whereRaw("JSON_EXTRACT(name, '$.fr_CA') = ?", ['"Sécurité"'])->first();
            $catId = $cat?->id;
        } catch (\Throwable $e) { /* silent */ }

        $terms = [
            [
                'slug' => 'anonymisation',
                'name' => 'Anonymisation',
                'type' => 'compliance',
                'difficulty' => 'intermediate',
                'icon' => '🕵️',
                'broader' => ['loi-25', 'rgpd', 'protection-vie-privee'],
                'narrower' => ['pseudonymisation', 'k-anonymity', 'differential-privacy'],
                'definition' => "L'anonymisation est un traitement irréversible qui rend impossible, par des moyens raisonnablement probables, toute identification directe ou indirecte d'une personne à partir de données. Contrairement à la pseudonymisation (où l'identification reste possible avec une clé), l'anonymisation fait sortir les données du champ d'application de la Loi 25 et du RGPD. Elle doit résister aux techniques d'individualisation, de corrélation et d'inférence, tel que défini par la CNIL, le CAI et le Commissariat à la vie privée du Canada.",
                'one_sentence' => "L'anonymisation est un processus irréversible qui, en 2026, doit rendre impossible toute identification d'une personne selon les exigences combinées de la Loi 25 au Québec et du RGPD en Europe, en supprimant ou transformant les données au point qu'aucun individu ne puisse être reconnu, même indirectement.",
                'analogy' => "C'est comme passer un registre paroissial au déchiqueteur industriel : même si les morceaux sont conservés, il devient impossible de reconstituer les noms ou de savoir qui a été baptisé, marié ou enterré.",
                'example' => "Une clinique de physiothérapie à Sherbrooke souhaite publier des statistiques sur les motifs de consultation. Elle supprime les noms, dates de naissance exactes et adresses, regroupe les âges en tranches (ex. 30-39 ans), et agrège les diagnostics par région administrative. Le résultat ne permet plus d'identifier un patient, même en croisant avec d'autres sources — les données sont donc anonymes et hors champ de la Loi 25.",
                'did_you_know' => "En 2026, le CAI et la CNIL insistent : une donnée n'est vraiment anonyme que si elle résiste aux trois risques — individualisation (reconnaître une personne unique), corrélation (relier des jeux de données) et inférence (déduire des infos non présentes). La simple suppression du nom ne suffit plus.",
                'faq' => [
                    ['question' => "Quelle est la différence entre anonymisation et pseudonymisation ?", 'answer' => "L'anonymisation rend toute identification impossible, même pour le détenteur des données, et fait sortir les données du champ du RGPD et de la Loi 25. La pseudonymisation, elle, remplace les identifiants par des pseudos (ex. ID123), mais une clé permet encore de réidentifier les personnes. C'est donc un traitement de sécurité, pas une sortie du cadre juridique."],
                    ['question' => "L'anonymisation est-elle obligatoire selon la Loi 25 ?", 'answer' => "Non, la Loi 25 ne rend pas l'anonymisation obligatoire, mais elle l'encourage fortement comme mesure de protection dès qu'on n'a plus besoin d'identifier les personnes (ex. pour la recherche ou la statistique). Si une entreprise anonymise correctement ses données, celles-ci ne sont plus considérées comme des renseignements personnels et échappent ainsi aux obligations de la loi."],
                    ['question' => "Comment vérifier qu'une donnée est vraiment anonyme ?", 'answer' => "Selon la CNIL et l'ENISA, une donnée est anonyme si elle résiste à trois risques : 1) l'individualisation (identifier une personne unique), 2) la corrélation (relier cette donnée à d'autres sources) et 3) l'inférence (deviner des infos sensibles non présentes). Si un attaquant raisonnable ne peut franchir ces seuils, l'anonymisation est valide."],
                    ['question' => "Peut-on anonymiser un texte avant de l'envoyer à ChatGPT ?", 'answer' => "Oui, mais attention : remplacer un nom par « CLIENT_01 » est de la pseudonymisation, pas de l'anonymisation — la clé de correspondance reste chez vous. Une vraie anonymisation supprimerait toute trace permettant de relier le texte à une personne réelle, même indirectement. Pour les PME, laveille.ai propose un outil d'anonymisation conforme à la Loi 25 : /outils/anonymiseur."],
                    ['question' => "Quelles techniques d'anonymisation existent en 2026 ?", 'answer' => "En 2026, les techniques valides incluent la suppression (effacer les champs identifiants), la généralisation (remplacer des valeurs précises par des plages), la k-anonymité (s'assurer qu'au moins k personnes partagent les mêmes attributs), la confidentialité différentielle (ajouter du bruit statistique), le chiffrement préservant le format (NIST FF1/FF3-1), le hachage avec sel aléatoire, et la rédaction (masquage manuel ou automatique)."],
                    ['question' => "Une donnée hashée (SHA-256) est-elle anonyme ?", 'answer' => "Non. Un hachage simple comme SHA-256 sans sel est considéré comme de la pseudonymisation, car un attaquant peut deviner l'entrée originale via des attaques par dictionnaire ou force brute (ex. hacher tous les noms courants). Même avec un sel, si celui-ci est conservé, la réidentification reste possible. Pour être anonyme, il faut que la transformation soit irréversible et résiste à toute inférence raisonnable."],
                ],
                'sources' => [
                    ['label' => "CNIL — L'anonymisation des données", 'url' => 'https://www.cnil.fr/fr/lanonymisation-des-donnees-un-traitement-cle-pour-lopen-data', 'year' => '2024', 'author' => 'CNIL'],
                    ['label' => 'CAI Québec — Loi 25 changements', 'url' => 'https://www.cai.gouv.qc.ca/protection-renseignements-personnels/sujets-et-domaines-dinteret/principaux-changements-loi-25', 'year' => '2024', 'author' => "Commission d'accès à l'information du Québec"],
                    ['label' => 'Commissariat à la vie privée du Canada — Loi 25 et RGPD', 'url' => 'https://www.priv.gc.ca/fr/protection-de-la-vie-privee-et-transparence-au-commissariat/divulgation-proactive/cpvp-parl-bp/indu_20231019/q-r_20231019/', 'year' => '2024', 'author' => 'Commissariat à la vie privée du Canada'],
                    ['label' => 'EPFL — Anonymisation des données de recherche', 'url' => 'https://www.epfl.ch/campus/services/data-protection/fr/en-pratique/le-respect-de-la-vie-privee-dans-la-recherche/anonymisation-des-donnees/', 'year' => '2024', 'author' => 'EPFL Data Protection'],
                    ['label' => 'ENISA — Pseudonymisation Techniques and Best Practices (PDF FR)', 'url' => 'https://www.enisa.europa.eu/sites/default/files/all_files/Pseudonymisation%20Techniques%20and%20best%20practices_FR.pdf', 'year' => '2022', 'author' => 'ENISA'],
                ],
            ],
            [
                'slug' => 'pseudonymisation',
                'name' => 'Pseudonymisation',
                'type' => 'compliance',
                'difficulty' => 'intermediate',
                'icon' => '🎭',
                'broader' => ['loi-25', 'rgpd', 'protection-vie-privee'],
                'narrower' => ['anonymisation', 'tokenisation', 'hash-sha-256'],
                'definition' => "La pseudonymisation est un procédé visé à l'article 4(5) du RGPD par lequel les données à caractère personnel sont traitées de manière à ce qu'elles ne puissent plus être attribuées à une personne concernée sans l'utilisation d'informations supplémentaires, tenues séparément et soumises à des mesures techniques et organisationnelles strictes. Contrairement à l'anonymisation, la pseudonymisation est réversible et les données restent personnelles, donc soumises au RGPD et à la Loi 25 du Québec. Elle constitue une mesure de protection obligatoire dès lors qu'elle est appropriée au risque.",
                'one_sentence' => "La pseudonymisation est un traitement des données personnelles qui les rend inidentifiables sans une information supplémentaire (comme une clé ou une table de correspondance), contrairement à l'anonymisation qui est irréversible. En 2026, elle reste soumise au RGPD (art.4(5)) et à la Loi 25, car les données peuvent être ré-identifiées.",
                'analogy' => "C'est comme un vestiaire de patinoire : on vous remet un jeton numéroté au lieu de noter votre nom. Le numéro seul ne révèle rien, mais avec le registre du préposé, on peut retrouver à qui appartient le manteau.",
                'example' => "Une PME québécoise de livraison remplace les noms et courriels de ses clients par des identifiants aléatoires (ex. : CLI-8a3f9b) dans son système de facturation. La correspondance entre ces identifiants et les vraies coordonnées est conservée dans une base de données distincte, chiffrée et accessible uniquement aux employés autorisés, conformément à la Loi 25 et au RGPD.",
                'did_you_know' => "En 2026, la pseudonymisation est une mesure technique obligatoire selon l'article 32 du RGPD dès qu'elle réduit les risques pour les personnes concernées. Pourtant, elle ne fait pas sortir les données du champ du RGPD ni de la Loi 25 — contrairement à l'anonymisation, qui, si elle est irréversible, dispense de ces obligations.",
                'faq' => [
                    ['question' => "Pseudonymisation vs anonymisation : laquelle choisir ?", 'answer' => "Choisissez la pseudonymisation si vous devez conserver la possibilité de ré-identifier les données (ex. : pour des analyses internes ou un service client). Optez pour l'anonymisation si les données ne doivent plus jamais être liées à une personne (ex. : données publiques ou statistiques). La pseudonymisation reste soumise au RGPD et à la Loi 25, l'anonymisation non — à condition qu'elle soit irréversible et robuste."],
                    ['question' => "La pseudonymisation est-elle imposée par le RGPD ?", 'answer' => "Oui, l'article 32 du RGPD exige des mesures techniques et organisationnelles appropriées pour assurer un niveau de sécurité adapté au risque. La pseudonymisation est explicitement citée comme l'une de ces mesures. Bien qu'elle ne soit pas obligatoire dans tous les cas, elle devient nécessaire dès qu'elle permet de réduire significativement les risques liés au traitement de données personnelles, notamment en cas de fuite."],
                    ['question' => "Comment pseudonymiser un fichier client Excel ?", 'answer' => "Pour pseudonymiser un fichier Excel, remplacez les noms, courriels et autres identifiants directs par des ID opaques (ex. : UUID ou codes aléatoires). Conservez la table de correspondance (qui lie chaque ID à la vraie identité) dans un autre fichier, protégé par un mot de passe fort et stocké séparément (ex. : coffre numérique chiffré). Idéalement, chiffrez les deux fichiers et limitez l'accès aux seules personnes autorisées, en tenant un journal d'accès."],
                    ['question' => "Hash SHA-256 = pseudonymisation ou anonymisation ?", 'answer' => "Un simple hachage SHA-256 constitue de la pseudonymisation, pas de l'anonymisation, car les valeurs originales (ex. : courriels courants) peuvent être devinées via des attaques par dictionnaire ou tables arc-en-ciel. Pour renforcer la protection, on ajoute un « sel » secret (HMAC-SHA256) et on garde ce secret hors du système. Même alors, tant que la clé existe, la donnée reste pseudonymisée — elle ne devient anonyme que si la clé est définitivement détruite."],
                    ['question' => "Qui peut accéder à la table de correspondance ?", 'answer' => "Seules les personnes strictement nécessaires (principe du moindre privilège) doivent y avoir accès, comme un administrateur sécurité ou un responsable conformité. L'accès doit être journalisé, protégé par authentification forte (ex. : MFA), et la table doit être chiffrée au repos (at-rest) et en transit (in-transit). En vertu de la Loi 25 et du RGPD, toute tentative d'accès non autorisée doit déclencher une alerte et être documentée."],
                    ['question' => "Quelles techniques de pseudonymisation utilise-t-on en 2026 ?", 'answer' => "En 2026, les techniques courantes incluent la tokenisation (remplacement par jetons aléatoires), le chiffrement préservant le format (FF1/FF3-1 selon NIST), les hachages sécurisés avec sel (HMAC), les identifiants UUID v4 aléatoires, et le masquage partiel (ex. : afficher seulement les derniers chiffres d'un NAS). Le choix dépend du contexte : FF1/FF3-1 est idéal pour les bases transactionnelles, tandis que la tokenisation convient bien aux systèmes de paiement ou CRM."],
                ],
                'sources' => [
                    ['label' => 'ENISA — Pseudonymisation Techniques and Best Practices (FR PDF)', 'url' => 'https://www.enisa.europa.eu/sites/default/files/all_files/Pseudonymisation%20Techniques%20and%20best%20practices_FR.pdf', 'year' => '2022', 'author' => 'ENISA'],
                    ['label' => 'CNIL — Pseudonymisation', 'url' => 'https://www.cnil.fr/fr/lanonymisation-des-donnees-un-traitement-cle-pour-lopen-data', 'year' => '2024', 'author' => 'CNIL'],
                    ['label' => 'CAI Québec — Loi 25 mesures de protection', 'url' => 'https://www.cai.gouv.qc.ca/protection-renseignements-personnels/sujets-et-domaines-dinteret/principaux-changements-loi-25', 'year' => '2024', 'author' => "Commission d'accès à l'information du Québec"],
                    ['label' => 'NIST SP 800-38G — Format-Preserving Encryption FF1/FF3-1', 'url' => 'https://csrc.nist.gov/publications/detail/sp/800-38g/rev-1/draft', 'year' => '2024', 'author' => 'NIST'],
                    ['label' => 'EPFL — Anonymisation et pseudonymisation des données', 'url' => 'https://www.epfl.ch/campus/services/data-protection/fr/en-pratique/le-respect-de-la-vie-privee-dans-la-recherche/anonymisation-des-donnees/', 'year' => '2024', 'author' => 'EPFL'],
                ],
            ],
        ];

        foreach ($terms as $data) {
            $term = Term::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.fr_CA')) = ?", [$data['slug']])->first();

            if (! $term) {
                $term = new Term;
                $term->is_published = true;
            }

            $term->type = $data['type'];
            $term->difficulty = $data['difficulty'];
            $term->icon = $data['icon'];
            $term->dictionary_category_id = $catId;

            $term->setTranslation('name', 'fr_CA', $data['name']);
            $term->setTranslation('name', 'fr', $data['name']);
            $term->setTranslation('slug', 'fr_CA', $data['slug']);
            $term->setTranslation('slug', 'fr', $data['slug']);
            $term->setTranslation('definition', 'fr_CA', $data['definition']);
            $term->setTranslation('definition', 'fr', $data['definition']);
            $term->setTranslation('analogy', 'fr_CA', $data['analogy']);
            $term->setTranslation('analogy', 'fr', $data['analogy']);
            $term->setTranslation('example', 'fr_CA', $data['example']);
            $term->setTranslation('example', 'fr', $data['example']);
            $term->setTranslation('did_you_know', 'fr_CA', $data['did_you_know']);
            $term->setTranslation('did_you_know', 'fr', $data['did_you_know']);
            $term->setTranslation('one_sentence_answer', 'fr_CA', $data['one_sentence']);
            $term->setTranslation('one_sentence_answer', 'fr', $data['one_sentence']);

            // FAQ + sources si colonnes existent (post-migration S125 P1 AEO/GEO)
            if (in_array('faq', $term->getFillable(), true)) {
                $term->faq = $data['faq'];
            }
            if (in_array('sources', $term->getFillable(), true)) {
                $term->sources = $data['sources'];
            }
            if (in_array('broader_slugs', $term->getFillable(), true)) {
                $term->broader_slugs = $data['broader'];
            }
            if (in_array('narrower_slugs', $term->getFillable(), true)) {
                $term->narrower_slugs = $data['narrower'];
            }

            $term->save();
        }
    }
}
