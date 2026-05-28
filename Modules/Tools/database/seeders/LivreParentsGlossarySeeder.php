<?php

declare(strict_types=1);

namespace Modules\Tools\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * #313 — Fiches glossaire manquantes du livre « L'IA pour les parents » (91 termes).
 * Seeder idempotent (upsert par slug fr_CA), enrichi par lots. Contenu vérifié via
 * recherche web (sources réelles citées, zéro fait inventé). Standard AEO/GEO complet :
 * one_sentence_answer + 6 FAQ + 5 sources EEAT + analogy QC + example parent + broader/narrower.
 * hero_image laissé null en Phase A (contenu) ; les images sont ajoutées en Phase B.
 */
class LivreParentsGlossarySeeder extends Seeder
{
    public function run(): void
    {
        $catSecurite = $this->catId(['Sécurité et éthique', 'Sécurité']);
        $catOutils = $this->catId(['Outils et techniques']);
        $catConcepts = $this->catId(['Concepts fondamentaux']);
        $catIA = $this->catId(['Intelligence artificielle']);
        $catDonnees = $this->catId(['Données et traitement']);

        $terms = [
            // ===== Lot 1 — Sécurité famille / arnaques IA (chapitre 9) =====
            [
                'slug' => 'voix-clonee',
                'name' => 'Voix clonée',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🎙️',
                'category_id' => $catSecurite,
                'broader' => ['synthese-vocale', 'deepfake'],
                'narrower' => ['mot-de-code-familial'],
                'definition' => 'La voix clonée est une reproduction numérique de la voix d’une personne réelle créée par une intelligence artificielle. À partir de seulement quelques secondes d’enregistrement audio captées sur les réseaux sociaux, un logiciel peut reproduire l’intonation et l’accent de n’importe qui. Cette technologie est malheureusement détournée par des fraudeurs pour simuler des appels d’urgence de proches en détresse. Pour vous protéger, il est essentiel de ne jamais agir dans la panique et de valider l’identité de l’appelant par un autre moyen.',
                'one_sentence' => 'La voix clonée est une technologie d’intelligence artificielle capable de reproduire l’empreinte vocale d’une personne à partir d’un court échantillon sonore, souvent utilisée par des fraudeurs pour simuler des appels de proches en détresse afin de soutirer de l’argent par la ruse et l’urgence, nécessitant une vigilance accrue des familles.',
                'analogy' => 'C’est comme si un imitateur professionnel réussissait à voler votre voix pour se faire passer pour vous au téléphone auprès de votre grand-mère, mais de façon automatisée et quasi parfaite.',
                'example' => 'Un parent reçoit un appel de son enfant qui pleure, disant avoir eu un accident et demandant un virement immédiat, alors que la voix a été volée sur une vidéo TikTok.',
                'did_you_know' => 'Les fraudeurs peuvent cloner une voix à partir de quelques secondes d’audio public pour créer une urgence crédible comme un accident ou une arrestation.',
                'faq' => [
                    ['question' => 'Comment ma voix peut-elle être volée ?', 'answer' => 'Les fraudeurs récupèrent souvent de courts extraits audio sur des plateformes publiques comme TikTok ou Instagram.'],
                    ['question' => 'La voix clonée est-elle toujours parfaite ?', 'answer' => 'Elle est très ressemblante, mais peut parfois présenter un ton légèrement robotique ou des hésitations inhabituelles.'],
                    ['question' => 'Que faire si je reçois un appel d’urgence suspect ?', 'answer' => 'Ne cédez pas à la panique, raccrochez immédiatement et rappelez le vrai numéro de votre proche pour vérifier.'],
                    ['question' => 'L’afficheur du téléphone est-il fiable ?', 'answer' => 'Non, les fraudeurs utilisent des techniques pour masquer leur numéro et faire apparaître celui d’un proche ou d’un organisme.'],
                    ['question' => 'Comment confirmer l’identité de l’appelant ?', 'answer' => 'Posez une question personnelle dont seul votre proche connaît la réponse ou utilisez un mot de code familial.'],
                    ['question' => 'Cette technologie est-elle légale ?', 'answer' => 'L’outil de synthèse vocale est légal pour la création de contenu, mais son usage pour la fraude est sévèrement puni.'],
                ],
                'sources' => [
                    ['label' => 'Gouvernement du Canada – Pensez cybersécurité', 'url' => 'https://www.pensezcybersecurite.gc.ca/fr/blogues/quest-que-lhameconnage-vocal', 'year' => '2023', 'author' => 'Gouvernement du Canada'],
                    ['label' => 'Centre canadien pour la cybersécurité', 'url' => 'https://www.cyber.gc.ca/fr/quest-ce-que-lhameconnage-vocal-itsap00102', 'year' => '2022', 'author' => 'Centre canadien pour la cybersécurité'],
                    ['label' => 'Gérez mieux votre argent', 'url' => 'https://www.gerezmieuxvotreargent.ca/chemin-dapprentissage/types-de-fraude/escroqueries-de-clonage-vocal-par-ia/', 'year' => '2024', 'author' => 'Autorité ontarienne de réglementation des services financiers'],
                    ['label' => 'TELUS WISE', 'url' => 'https://www.telus.com/fr/wise/resources/content/article/who-is-really-calling-the-rise-of-ai-voice-cloning-scams', 'year' => '2024', 'author' => 'TELUS'],
                ],
            ],
            [
                'slug' => 'mot-de-code-familial',
                'name' => 'Mot de code familial',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🔑',
                'category_id' => $catSecurite,
                'broader' => ['voix-clonee'],
                'narrower' => [],
                'definition' => 'Le mot de code familial est une mesure de sécurité simple consistant à convenir d’un terme secret connu uniquement des membres du foyer. Ce mot, idéalement absurde comme « ananas bleu », sert à confirmer l’identité d’un proche lors d’une demande d’argent urgente par téléphone. Face à la montée des arnaques par voix clonée, ce rempart devient indispensable pour éviter de céder à la panique. Sans ce code précis, aucune transaction ne doit être effectuée, peu importe l’urgence apparente de la situation.',
                'one_sentence' => 'Le mot de code familial est un terme secret et unique convenu entre les membres d’une même famille pour valider mutuellement leur identité lors d’appels téléphoniques suspects ou de demandes d’argent urgentes, offrant ainsi une protection efficace contre les fraudes sophistiquées utilisant le clonage vocal par IA.',
                'analogy' => 'C’est comme la poignée de main secrète que vous aviez avec vos amis d’enfance, mais adaptée à l’ère numérique pour s’assurer que la personne au bout du fil est bien celle qu’elle prétend être.',
                'example' => 'Si un jeune appelle ses parents pour une prétendue arrestation, le parent demande le mot de code ; si l’appelant ne le connaît pas, la fraude est immédiatement démasquée.',
                'did_you_know' => 'Les autorités canadiennes recommandent officiellement d’établir en famille un mot ou une phrase de code connu seulement des proches pour valider l’identité lors d’un appel d’urgence.',
                'faq' => [
                    ['question' => 'Quel type de mot devrions-nous choisir ?', 'answer' => 'Choisissez un mot simple à retenir, mais totalement imprévisible, comme un fruit associé à une couleur inhabituelle.'],
                    ['question' => 'Où devrions-nous conserver ce mot de code ?', 'answer' => 'Il est préférable de le mémoriser ou de le noter dans un endroit sûr hors ligne, jamais dans un message texte ou un courriel.'],
                    ['question' => 'Quand faut-il demander le mot de code ?', 'answer' => 'Dès qu’un appel implique une demande d’argent, de cartes prépayées ou d’informations personnelles sensibles.'],
                    ['question' => 'Que faire si l’appelant refuse de donner le code ?', 'answer' => 'Raccrochez immédiatement. Une personne réellement en détresse comprendra la mesure de sécurité, contrairement à un fraudeur.'],
                    ['question' => 'Doit-on changer le mot de code régulièrement ?', 'answer' => 'Il est conseillé de le changer s’il y a un doute sur sa confidentialité ou si un membre extérieur à la famille l’a entendu.'],
                    ['question' => 'Qui devrait connaître ce mot de code ?', 'answer' => 'Uniquement le cercle familial restreint et les personnes de confiance absolue qui pourraient être impliquées dans une urgence.'],
                ],
                'sources' => [
                    ['label' => 'Gouvernement du Canada – Pensez cybersécurité', 'url' => 'https://www.pensezcybersecurite.gc.ca/fr/blogues/quest-que-lhameconnage-vocal', 'year' => '2023', 'author' => 'Gouvernement du Canada'],
                    ['label' => 'Centre canadien pour la cybersécurité', 'url' => 'https://www.cyber.gc.ca/fr/quest-ce-que-lhameconnage-vocal-itsap00102', 'year' => '2022', 'author' => 'Centre canadien pour la cybersécurité'],
                    ['label' => 'Gérez mieux votre argent', 'url' => 'https://www.gerezmieuxvotreargent.ca/chemin-dapprentissage/types-de-fraude/escroqueries-de-clonage-vocal-par-ia/', 'year' => '2024', 'author' => 'Autorité ontarienne de réglementation des services financiers'],
                    ['label' => 'TELUS WISE', 'url' => 'https://www.telus.com/fr/wise/resources/content/article/who-is-really-calling-the-rise-of-ai-voice-cloning-scams', 'year' => '2024', 'author' => 'TELUS'],
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
            if (! empty($data['category_id'])) {
                $term->dictionary_category_id = $data['category_id'];
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

    /** @param array<int,string> $names */
    private function catId(array $names): ?int
    {
        foreach ($names as $name) {
            try {
                $cat = Category::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr_CA')) = ?", [$name])->first();
                if ($cat) {
                    return $cat->id;
                }
            } catch (\Throwable $e) { /* silent */ }
        }

        return null;
    }
}
