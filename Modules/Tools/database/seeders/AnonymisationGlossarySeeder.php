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
                'hero_image' => 'images/glossaire/anonymisation.png',
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
                'hero_image' => 'images/glossaire/pseudonymisation.png',
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
            [
                'slug' => 'k-anonymity',
                'name' => 'K-anonymité',
                'type' => 'compliance',
                'difficulty' => 'advanced',
                'icon' => '👥',
                'hero_image' => 'images/glossaire/k-anonymity.png',
                'broader' => ['anonymisation', 'protection-vie-privee'],
                'narrower' => ['differential-privacy'],
                'definition' => "La k-anonymité est une technique d'anonymisation qui garantit qu'au moins k personnes partagent les mêmes quasi-identifiants (comme l'âge, le sexe ou un code postal tronqué) dans un jeu de données, rendant ainsi impossible l'isolement d'un individu. Inventée par Latanya Sweeney à la fin des années 1990, elle vise à prévenir la réidentification, mais ne suffit pas à protéger contre les attaques d'inférence si k est trop faible ou si les données sont corrélées avec d'autres sources.",
                'one_sentence' => "La k-anonymité est une méthode d'anonymisation qui assure qu'au moins k individus partagent les mêmes caractéristiques quasi-identifiantes dans un jeu de données, rendant la réidentification individuelle impossible — une exigence de plus en plus centrale dans les projets de données publiques ou partagées en 2026.",
                'analogy' => "C'est comme être dans une foule au centre-ville de Montréal un vendredi midi : si tout le monde porte une tuque noire, un manteau gris et des bottes de neige, impossible de pointer une seule personne du doigt — vous faites partie d'un groupe indiscernable.",
                'example' => "Dans un jeu de données de santé québécois, on généralise l'âge en tranches (ex. 30-39 ans), on tronque le code postal à trois chiffres (ex. G1K), et on garde le sexe. Avec k=5, chaque combinaison (ex. [30-39, F, G1K]) apparaît au moins cinq fois, empêchant d'associer une fiche médicale à une personne précise.",
                'did_you_know' => "Latanya Sweeney a démontré en 1997 qu'87 % des Américains pouvaient être identifiés de façon unique avec seulement leur date de naissance, leur sexe et leur code postal — un cas de k=1. Cette découverte a directement mené à l'invention du modèle de k-anonymité pour corriger cette vulnérabilité.",
                'faq' => [
                    ['question' => "Qu'est-ce que k=5 ou k=10 signifie ?", 'answer' => "Cela signifie que chaque combinaison de quasi-identifiants (ex. âge tronqué + sexe + code postal partiel) doit apparaître au moins 5 ou 10 fois dans le jeu de données. Ainsi, même si un attaquant connaît ces informations, il ne peut pas distinguer un individu parmi les k équivalents. Plus k est élevé, plus la protection contre la réidentification est forte, mais cela peut réduire l'utilité statistique des données."],
                    ['question' => "K-anonymité est-elle suffisante pour respecter la Loi 25 ?", 'answer' => "Non, la k-anonymité seule ne suffit pas. Bien qu'elle empêche l'individualisation directe (un critère clé du CAI Québec), elle ne protège pas contre les attaques par corrélation ou inférence, surtout si k est faible ou si les données sont croisées avec d'autres sources. Une approche combinée (ex. avec la confidentialité différentielle) est souvent nécessaire."],
                    ['question' => "Quels sont les quasi-identifiants ?", 'answer' => "Les quasi-identifiants sont des attributs non uniques seuls, mais qui, combinés, peuvent identifier une personne (ex. date de naissance, sexe, code postal, profession, niveau de revenu). Contrairement aux identifiants directs (nom, NAS, courriel), ils ne désignent pas explicitement un individu, mais deviennent dangereux en combinaison — d'où la nécessité de les généraliser ou supprimer pour atteindre la k-anonymité."],
                    ['question' => "Comment atteindre la k-anonymité dans Excel ?", 'answer' => "Commencez par supprimer tous les identifiants directs (noms, numéros). Ensuite, généralisez les âges en tranches (ex. 20-29 ans), tronquez les codes postaux à 3 caractères (ex. H3A), et regroupez les catégories rares. Utilisez des tableaux croisés dynamiques pour vérifier que chaque combinaison de quasi-identifiants apparaît au moins k fois. Si ce n'est pas le cas, généralisez davantage ou supprimez les lignes problématiques."],
                    ['question' => "K-anonymity vs differential privacy ?", 'answer' => "La k-anonymité modifie les données pour cacher les individus dans des groupes, mais ne protège pas contre les attaques si l'attaquant possède des données externes. La confidentialité différentielle, elle, ajoute du bruit statistique aux résultats (pas aux données brutes) pour garantir qu'un individu ne puisse pas être détecté, même avec une connaissance auxiliaire. Cette dernière offre une garantie mathématique plus forte."],
                    ['question' => "Quelle valeur de k choisir en 2026 ?", 'answer' => "En 2026, les bonnes pratiques recommandent k>=5 minimum, et k>=10 pour les secteurs sensibles comme la santé ou les RH. La CNIL et le CAI du Québec insistent sur l'évaluation du risque : plus les données sont sensibles ou corrélables avec des sources publiques, plus k doit être élevé. Il faut aussi évaluer l'impact sur l'utilité des données."],
                ],
                'sources' => [
                    ['label' => 'Sweeney L. — k-Anonymity: A Model for Protecting Privacy (IJUFKS 2002)', 'url' => 'https://dataprivacylab.org/dataprivacy/projects/kanonymity/kanonymity.html', 'year' => '2002', 'author' => 'Latanya Sweeney, Carnegie Mellon University'],
                    ['label' => "CNIL — Techniques d'anonymisation", 'url' => 'https://www.cnil.fr/fr/lanonymisation-des-donnees-un-traitement-cle-pour-lopen-data', 'year' => '2024', 'author' => 'CNIL'],
                    ['label' => 'ENISA — Pseudonymisation Techniques and Best Practices (FR PDF)', 'url' => 'https://www.enisa.europa.eu/sites/default/files/all_files/Pseudonymisation%20Techniques%20and%20best%20practices_FR.pdf', 'year' => '2022', 'author' => 'ENISA'],
                    ['label' => 'NIST — Privacy Preserving Techniques', 'url' => 'https://www.nist.gov/privacy-framework', 'year' => '2024', 'author' => 'NIST'],
                    ['label' => 'CAI Québec — Anonymisation Loi 25', 'url' => 'https://www.cai.gouv.qc.ca/protection-renseignements-personnels/sujets-et-domaines-dinteret/principaux-changements-loi-25', 'year' => '2024', 'author' => "Commission d'accès à l'information du Québec"],
                ],
            ],
            [
                'slug' => 'differential-privacy',
                'name' => 'Confidentialité différentielle',
                'type' => 'compliance',
                'difficulty' => 'advanced',
                'icon' => '📊',
                'hero_image' => 'images/glossaire/differential-privacy.png',
                'broader' => ['anonymisation', 'protection-vie-privee'],
                'narrower' => ['k-anonymity'],
                'definition' => "La confidentialité différentielle est une technique mathématique qui ajoute du bruit statistique calibré aux résultats de requêtes sur des données sensibles, de façon à empêcher l'identification d'individus tout en conservant l'utilité statistique globale. Inventée par Cynthia Dwork en 2006, elle repose sur un paramètre epsilon (ε), appelé « budget de confidentialité », qui contrôle le compromis entre précision et protection de la vie privée. Elle est utilisée par Apple dans iOS, Google dans Chrome et le Bureau du recensement des États-Unis depuis 2020.",
                'one_sentence' => "La confidentialité différentielle est une méthode mathématique qui protège la vie privée en ajoutant du bruit contrôlé aux données, garantissant qu'aucun individu ne puisse être identifié, même avec des attaques futures. En 2026, elle demeure l'unique approche d'anonymisation prouvée résistante à toute forme d'inférence, y compris par l'IA.",
                'analogy' => "Imaginez un sondage anonyme où chaque répondant peut mentir volontairement avec une petite probabilité connue. Même si vous voyez le résultat global, vous ne pouvez pas savoir avec certitude ce qu'une personne spécifique a répondu — c'est le principe de la confidentialité différentielle.",
                'example' => "Apple utilise la confidentialité différentielle depuis iOS 10 (2016) pour améliorer les suggestions du clavier QuickType sans connaître ce que les utilisateurs tapent exactement. De même, le recensement américain de 2020 a appliqué cette méthode pour publier des données démographiques tout en empêchant la réidentification des répondants, même à partir de bases de données externes.",
                'did_you_know' => "La confidentialité différentielle est la seule technique d'anonymisation offrant une garantie mathématique rigoureuse de protection contre toute attaque future, y compris celles utilisant l'intelligence artificielle. C'est pourquoi le Bureau du recensement des États-Unis l'a adoptée officiellement en 2020 pour protéger les données du recensement.",
                'faq' => [
                    ['question' => "Comment fonctionne le « bruit » statistique ?", 'answer' => "Le « bruit » est un aléa mathématiquement calibré (souvent issu de distributions comme Laplace ou Gaussienne) ajouté aux résultats d'une requête. Il est conçu pour masquer la contribution d'un individu sans fausser significativement les tendances globales. Par exemple, si une requête compte 1000 personnes, un petit bruit (ex. ±15) rend impossible de savoir si une personne spécifique était présente, tout en gardant le total utile pour l'analyse statistique."],
                    ['question' => "Qu'est-ce que le paramètre epsilon (ε) ?", 'answer' => "Le paramètre epsilon (ε) quantifie le niveau de confidentialité : plus ε est petit, plus la protection est forte (mais plus le bruit est élevé, ce qui réduit la précision). À l'inverse, un ε grand signifie moins de bruit et donc moins de confidentialité. Un ε ≤ 1 est généralement considéré comme offrant une protection raisonnable dans les applications pratiques."],
                    ['question' => "Differential privacy vs k-anonymité ?", 'answer' => "La k-anonymité masque les identités en regroupant les individus en groupes de taille k, mais elle est vulnérable aux attaques par inférence ou au couplage avec d'autres données. En revanche, la confidentialité différentielle offre une garantie mathématique rigoureuse : même un adversaire disposant de toute l'information auxiliaire possible ne peut pas déterminer avec certitude si un individu est dans le jeu de données."],
                    ['question' => "Apple et Google utilisent-ils vraiment la differential privacy ?", 'answer' => "Oui. Apple l'utilise depuis 2016 dans iOS pour recueillir des données d'usage (comme les suggestions de mots dans QuickType) sans identifier les utilisateurs. Google a déployé la méthode RAPPOR (Randomized Aggregatable Privacy-Preserving Ordinal Response) dans Chrome pour mesurer l'usage de fonctionnalités. Le Bureau du recensement des États-Unis l'a aussi adoptée pour le recensement de 2020."],
                    ['question' => "Quels outils open source en 2026 ?", 'answer' => "En 2026, plusieurs bibliothèques open source sont matures : la bibliothèque Differential Privacy de Google (en C++ et Python), OpenDP développée par Harvard et Microsoft (orientée politiques publiques et statistiques), et diffprivlib d'IBM (intégrée à scikit-learn). Ces outils permettent d'implémenter facilement la confidentialité différentielle dans des projets de données."],
                    ['question' => "DP est-elle utilisable pour une PME québécoise ?", 'answer' => "Oui, une PME québécoise peut utiliser la confidentialité différentielle grâce aux bibliothèques open source comme celles de Google ou OpenDP. Un ε autour de 1,0 offre un bon équilibre entre protection et utilité. Google BigQuery propose aussi des fonctions DP intégrées. Toutefois, il faut une certaine expertise en statistiques et en protection des données pour l'appliquer correctement."],
                ],
                'sources' => [
                    ['label' => 'Dwork C. — Differential Privacy (ICALP 2006)', 'url' => 'https://www.microsoft.com/en-us/research/publication/differential-privacy/', 'year' => '2006', 'author' => 'Cynthia Dwork, Microsoft Research'],
                    ['label' => 'Apple — Differential Privacy Overview', 'url' => 'https://www.apple.com/privacy/docs/Differential_Privacy_Overview.pdf', 'year' => '2017', 'author' => 'Apple'],
                    ['label' => 'Google — Differential Privacy Library (open source)', 'url' => 'https://github.com/google/differential-privacy', 'year' => '2024', 'author' => 'Google'],
                    ['label' => 'US Census Bureau — Differential Privacy 2020', 'url' => 'https://www.census.gov/programs-surveys/decennial-census/decade/2020/planning-management/process/disclosure-avoidance/differential-privacy.html', 'year' => '2020', 'author' => 'US Census Bureau'],
                    ['label' => 'OpenDP — Open-source DP framework (Harvard/Microsoft)', 'url' => 'https://opendp.org/', 'year' => '2024', 'author' => 'OpenDP / Harvard University'],
                ],
            ],
            [
                'slug' => 'tokenisation',
                'name' => 'Tokenisation',
                'type' => 'compliance',
                'difficulty' => 'intermediate',
                'icon' => '🎰',
                'hero_image' => 'images/glossaire/tokenisation.png',
                'broader' => ['pseudonymisation', 'protection-vie-privee'],
                'narrower' => [],
                'definition' => "La tokenisation (ou tokenization) est une technique de protection des données sensibles qui consiste à remplacer une information sensible — comme un numéro de carte bancaire, un NAS ou une adresse courriel — par un jeton aléatoire sans valeur intrinsèque. Ce jeton n'a aucun lien mathématique avec la donnée originale et ne peut être reconverti qu'en consultant un « coffre » (vault) sécurisé où la correspondance est stockée. Contrairement au chiffrement, il n'existe pas de clé permettant de déchiffrer le jeton. Le standard PCI DSS recommande cette approche depuis 2011 pour réduire l'exposition des PAN (Primary Account Numbers).",
                'one_sentence' => "La tokenisation est une méthode de protection des données qui remplace une information sensible par un jeton aléatoire sans valeur intrinsèque, stocké dans un coffre sécurisé, sans lien mathématique réversible avec la donnée d'origine. En 2026, elle sera de plus en plus exigée pour les systèmes traitant des paiements numériques au Québec et ailleurs.",
                'analogy' => "C'est comme quand vous allez au Casino de Montréal : vous échangez votre argent réel contre des jetons de jeu. Ces jetons n'ont aucune valeur en dehors du casino, mais permettent de jouer. Si vous les perdez, ce n'est pas votre compte en banque qui est touché — juste les jetons. Le vrai argent reste en sécurité à la caisse.",
                'example' => "Une PME québécoise opérant une boutique en ligne utilise un service de paiement comme Stripe. Lorsqu'un client entre son numéro de carte, celui-ci est immédiatement converti en un jeton par le fournisseur de paiement. La PME stocke uniquement ce jeton dans sa base de données pour les futurs achats, jamais le vrai numéro de carte. Ainsi, même en cas de fuite, les données volées sont inutilisables.",
                'did_you_know' => "Apple Pay et Google Pay utilisent la tokenisation EMV pour chaque transaction : le commerçant reçoit un jeton unique (DPAN) au lieu du vrai numéro de carte, ce qui empêche toute réutilisation frauduleuse même si les données sont interceptées.",
                'faq' => [
                    ['question' => "Tokenisation vs chiffrement : quelle différence ?", 'answer' => "Le chiffrement crée une relation mathématique réversible entre les données originales et les données chiffrées, à l'aide d'une clé. La tokenisation, elle, remplace les données par un jeton aléatoire sans lien mathématique ; la seule façon de récupérer la donnée originale est de consulter un vault sécurisé."],
                    ['question' => "Tokenisation vs pseudonymisation ?", 'answer' => "La tokenisation est une forme spécifique de pseudonymisation. La pseudonymisation remplace une donnée identifiable par un pseudonyme, mais ce pseudonyme peut parfois être réversible sans vault. La tokenisation exige toujours un vault sécurisé et produit des jetons non dérivables."],
                    ['question' => "PCI DSS exige-t-il la tokenisation ?", 'answer' => "PCI DSS ne l'exige pas strictement, mais la recommande fortement comme méthode pour éviter le stockage de PAN en clair. Son utilisation réduit considérablement le périmètre d'audit PCI, car les systèmes qui ne manipulent que des tokens ne sont pas considérés comme dans le scope de stockage de données sensibles."],
                    ['question' => "Apple Pay utilise-t-il vraiment la tokenisation ?", 'answer' => "Oui, Apple Pay utilise la tokenisation EMV : chaque appareil reçoit un DPAN (Device Primary Account Number), un jeton unique qui remplace le vrai PAN. Ce jeton est spécifique à l'appareil et à la carte, et change même d'une transaction à l'autre dans certains cas."],
                    ['question' => "Comment implémenter la tokenisation pour une PME québécoise ?", 'answer' => "Il est fortement déconseillé d'implémenter sa propre solution. Les PME québécoises devraient utiliser des services éprouvés comme Stripe Vault, AWS KMS avec tokenization, HashiCorp Vault ou Auth0. Ces outils gèrent la sécurité, la conformité PCI DSS et la gestion du vault, ce qui réduit les risques et les coûts de développement."],
                    ['question' => "Format-Preserving Encryption (FF1/FF3-1) vs tokenisation ?", 'answer' => "Le chiffrement préservant le format (FPE) produit un texte chiffré qui garde la même structure que l'original (ex. 16 chiffres pour une CB), mais reste réversible avec une clé. La tokenisation génère un jeton aléatoire sans valeur, non réversible sans vault. Depuis les vulnérabilités publiées en 2018 contre FF3-1, la tokenisation est considérée comme plus sûre pour les PAN."],
                ],
                'sources' => [
                    ['label' => 'PCI Security Standards Council — Tokenization Guidelines', 'url' => 'https://www.pcisecuritystandards.org/documents/Tokenization_Guidelines_Info_Supplement.pdf', 'year' => '2024', 'author' => 'PCI Security Standards Council'],
                    ['label' => 'NIST SP 800-188 — De-Identifying Government Data', 'url' => 'https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-188.pdf', 'year' => '2023', 'author' => 'NIST'],
                    ['label' => 'ENISA — Pseudonymisation Techniques (FR PDF)', 'url' => 'https://www.enisa.europa.eu/sites/default/files/all_files/Pseudonymisation%20Techniques%20and%20best%20practices_FR.pdf', 'year' => '2022', 'author' => 'ENISA'],
                    ['label' => 'Visa — EMV Tokenisation overview', 'url' => 'https://usa.visa.com/products/visa-token-service.html', 'year' => '2024', 'author' => 'Visa'],
                    ['label' => 'Stripe — Tokens API documentation', 'url' => 'https://stripe.com/docs/tokens', 'year' => '2024', 'author' => 'Stripe'],
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
            if (! empty($data['hero_image']) && in_array('hero_image', $term->getFillable(), true)) {
                $term->hero_image = $data['hero_image'];
            }

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
