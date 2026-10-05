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
 * Ajout du terme « Environnement d'exécution de confiance (TEE) » (Trusted Execution Environment),
 * ressorti de la fiche d'actualité 65483 (système d'apprentissage fédéré de Google fondé sur des TEE,
 * adopté par Gboard).
 *
 * CONTROLE ANTI-DOUBLON (2026-10-05) : aucune fiche portant la notion (/glossaire/environnement-dexecution-de-confiance
 * répond 404 en production). Voisines EXISTANTES et distinctes, non dupliquées : apprentissage-federe et
 * confidentialite-differentielle (les deux répondent 200).
 *
 * RELATIONS : aucune parenté hiérarchique réelle (un TEE n'est ni le parent ni l'enfant de ces deux termes;
 * il en est un outil complémentaire), donc broader_slugs et narrower_slugs restent vides plutôt que de
 * fabriquer un lien de graphe faux. Les deux voisins sont nommés dans la FAQ, où l'auto-lien les relie.
 *
 * ALIAS : « TEE », « Trusted Execution Environment » et le pluriel francophone. Le mot nu « enclave » est
 * VOLONTAIREMENT absent (mot courant, risque de faux liens). « TEE » est un acronyme de 3 lettres, donc
 * match_strategy = case_sensitive pour que « tee » en minuscules ne morde pas.
 *
 * Garde-fou de contenu : un TEE RÉDUIT la confiance envers l'opérateur, il ne l'ÉLIMINE pas. Les promesses
 * de Google sont attribuées à Google.
 *
 * Typographie québécoise (OQLF) : espace insécable U+00A0 devant chaque deux-points, aucune espace avant
 * ; ! ?, guillemets droits, aucun tiret cadratin.
 *
 * Migration idempotente (slug déjà présent = ignoré). down() retire seulement ce terme.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'environnement-dexecution-de-confiance';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => "Environnement d'exécution de confiance (TEE)",
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'securite-et-ethique',
            'acronym_full' => 'Trusted Execution Environment',
            'definition' => "Un environnement d'exécution de confiance (TEE, pour Trusted Execution Environment) est une zone protégée d'un processeur où un programme s'exécute à l'abri du reste de la machine, système d'exploitation et administrateur compris. Le code et les données y sont isolés et, selon la technologie, chiffrés en mémoire pendant le calcul. Son atout pour la confidentialité est l'attestation à distance : le TEE prouve cryptographiquement quel programme exact il exécute, et un tiers peut le vérifier avant de lui confier des renseignements personnels. On contrôle ce qui tourne au lieu de croire l'opérateur sur parole, un atout pour une organisation qui doit démontrer ses pratiques de protection des renseignements personnels sous la Loi 25. Intel (SGX, TDX), AMD (SEV), Arm (TrustZone) et la Secure Enclave d'Apple en offrent des variantes, qui ne chiffrent pas toutes la mémoire au même degré. Un TEE réduit la confiance à accorder à l'opérateur, il ne l'élimine pas : il faut encore se fier au fabricant du processeur et à ses services d'attestation, des attaques par canaux auxiliaires ont percé certaines protections, et le programme attesté peut lui-même avoir des failles.",
            'analogy' => "C'est une chambre forte vitrée dans la banque : le personnel ne peut pas y toucher, et un inspecteur peut vérifier de l'extérieur ce qu'on y fait.",
            'example' => "Selon Google, Gboard entraîne désormais ses modèles de prédiction du mot suivant, en anglais et en japonais, dans des TEE. Les programmes autorisés sont publiés dans un journal de transparence public que des auditeurs peuvent consulter.",
            'did_you_know' => "Google reconnaît que son système reste soumis aux limites des TEE actuels, dont les attaques par canaux auxiliaires, et dit poursuivre la recherche pour les atténuer.",
            'one_sentence_answer' => "Un environnement d'exécution de confiance est une zone protégée d'un processeur qui isole un programme et permet à un tiers de vérifier à distance quel code tourne sur des données sensibles.",
            'faq' => [
                [
                    'question' => 'Un TEE rend-il un traitement de données personnelles automatiquement sûr?',
                    'answer' => "Non. Il réduit la confiance à accorder à l'opérateur du serveur, mais il ne l'élimine pas. Il faut encore se fier au fabricant du processeur, et des attaques par canaux auxiliaires ont déjà été démontrées. Il ne protège pas non plus contre un programme mal conçu qui serait correctement attesté.",
                ],
                [
                    'question' => 'Que veut dire "attestation à distance"?',
                    'answer' => "C'est une preuve signée, produite par le TEE, qui indique quel programme il exécute et dans quel état. Un tiers la vérifie avant d'envoyer des données ou de remettre une clé de déchiffrement. Elle prouve ce qui tourne, pas que ce programme est inoffensif.",
                ],
                [
                    'question' => 'Quel lien avec l\'apprentissage fédéré et la confidentialité différentielle?',
                    'answer' => "Ce sont des techniques voisines. L'apprentissage fédéré évite de centraliser les données brutes, et la confidentialité différentielle ajoute du bruit aux résultats. Le TEE permet de vérifier que le serveur applique réellement ces protections, comme dans le système de Google pour Gboard.",
                ],
            ],
            'sources' => [
                [
                    'label' => 'RFC 9334, Remote ATtestation procedureS (RATS) Architecture',
                    'url' => 'https://www.rfc-editor.org/rfc/rfc9334',
                    'year' => 2023,
                    'author' => 'Henk Birkholz, Dave Thaler, Michael Richardson, Ned Smith et Wei Pan (IETF)',
                ],
                [
                    'label' => 'What Is Remote Attestation? Enhancing Data Governance with Confidential Computing',
                    'url' => 'https://confidentialcomputing.io/2024/10/02/what-is-remote-attestation-enhancing-data-governance-with-confidential-computing/',
                    'year' => 2024,
                    'author' => 'Confidential Computing Consortium (Linux Foundation)',
                ],
            ],
            'aliases' => ['TEE', 'Trusted Execution Environment', "environnements d'exécution de confiance"],
            'broader_slugs' => [],
            'narrower_slugs' => [],
            'difficulty' => 'advanced',
            'icon' => '🔐',
            'type' => 'explainer',
            'match_strategy' => 'case_sensitive',
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

        if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
            echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

            return;
        }

        $term = new Term();

        foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
            $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
        }

        $term->acronym_full = $t['acronym_full'];
        $term->faq = $t['faq'];
        $term->sources = $t['sources'];
        $term->aliases = $t['aliases'];
        $term->broader_slugs = $t['broader_slugs'];
        $term->narrower_slugs = $t['narrower_slugs'];
        $term->difficulty = $t['difficulty'];
        $term->icon = $t['icon'];
        $term->type = $t['type'];
        $term->match_strategy = $t['match_strategy'];
        $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug'])
            ?? $this->resolveCategoryId('intelligence-artificielle');
        $term->hero_image = 'images/glossaire/'.self::NEW_SLUG.'.webp';
        $term->is_published = true;
        $term->sort_order = 1015;
        $term->save();

        echo "[glossaire] terme ajoute : {$t['slug']}\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo "[glossaire] terme retire : ".self::NEW_SLUG."\n";
    }
};
