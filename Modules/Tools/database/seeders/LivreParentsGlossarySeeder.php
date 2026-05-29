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
            [
                'slug' => 'desinformation',
                'name' => 'Désinformation',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🎭',
                'category_id' => $catConcepts,
                'broader' => ['litteratie-ia'],
                'narrower' => ['ferme-de-trolls', 'microciblage'],
                'definition' => 'La désinformation est la création et la diffusion délibérées d’informations fausses dans le but explicite de tromper ou de nuire. Contrairement à une simple erreur, elle est le fruit d’une intention malveillante visant à manipuler l’opinion publique ou à déstabiliser des institutions. Avec l’avènement de l’intelligence artificielle, ces campagnes peuvent désormais être industrialisées à une échelle sans précédent. L’objectif final est souvent d’épuiser les citoyens et de saturer l’espace numérique jusqu’à ce que la vérité devienne impossible à distinguer du mensonge. Il s’agit d’une menace sérieuse pour le socle de faits communs nécessaire à toute démocratie saine.',
                'one_sentence' => 'La désinformation désigne la création et la propagation intentionnelle de contenus faux ou trompeurs dans le but de manipuler l’opinion ou de nuire à autrui, une pratique aujourd’hui amplifiée par l’intelligence artificielle qui permet d’industrialiser la production de mensonges crédibles pour saturer l’espace public et fragiliser nos institutions démocratiques.',
                'analogy' => 'C’est comme si quelqu’un empoisonnait volontairement le puits d’eau du village pour que plus personne ne puisse s’y abreuver en toute confiance.',
                'example' => 'Une organisation crée de faux articles de presse affirmant qu’une nouvelle loi va saisir les économies des parents afin de provoquer une panique sociale massive.',
                'did_you_know' => 'Les fermes de trolls utilisent maintenant l’intelligence artificielle pour générer des milliers de commentaires crédibles en quelques minutes seulement afin de simuler un mouvement populaire.',
                'faq' => [
                    ['question' => 'Quelle est la différence avec la mésinformation ?', 'answer' => 'La désinformation est intentionnelle et malveillante, tandis que la mésinformation est partagée par erreur sans volonté de nuire.'],
                    ['question' => 'Pourquoi l’IA rend-elle cela plus dangereux ?', 'answer' => 'L’IA permet de produire du contenu truqué (textes, images, vidéos) de manière massive, rapide et très convaincante.'],
                    ['question' => 'Qui crée la désinformation ?', 'answer' => 'Elle peut provenir d’États, de groupes politiques, d’organisations criminelles ou d’individus cherchant à générer du profit ou du chaos.'],
                    ['question' => 'Quel est le but recherché ?', 'answer' => 'L’objectif est souvent de semer le doute, de diviser la population ou de discréditer des sources d’information fiables.'],
                    ['question' => 'Comment la reconnaître ?', 'answer' => 'Vérifiez la source, cherchez si d’autres médias sérieux en parlent et méfiez-vous des contenus qui suscitent une émotion forte.'],
                    ['question' => 'Est-ce légal ?', 'answer' => 'Bien que la liberté d’expression protège beaucoup de discours, la diffamation et certaines formes d’incitation à la haine sont illégales.'],
                ],
                'sources' => [
                    ['label' => 'Gouvernement du Canada — Malinformation, désinformation et mésinformation (PDF)', 'url' => 'https://www.canada.ca/content/dam/dnd-mdn/documents/maple-leaf/decouvrez-la-verite-sur-la-mal-des-mes-information.pdf', 'year' => '2023', 'author' => 'Gouvernement du Canada'],
                    ['label' => 'Commission ontarienne des droits de la personne — Mésinformation, désinformation et malinformation', 'url' => 'https://droitsdelapersonne.ca/guide-de-ressources/mesinformation-desinformation-et-malinformation', 'year' => '2024', 'author' => 'Commission ontarienne des droits de la personne'],
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'HabiloMédias — Éducation aux médias et littératie numérique', 'url' => 'https://habilomedias.ca', 'year' => '2024', 'author' => 'HabiloMédias'],
                    ['label' => 'University of Cambridge (Sander van der Linden) — Inoculation theory / prebunking', 'url' => 'https://www.cam.ac.uk', 'year' => '2024', 'author' => 'University of Cambridge'],
                ],
            ],
            [
                'slug' => 'mesinformation',
                'name' => 'Mésinformation',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🤷',
                'category_id' => $catConcepts,
                'broader' => ['litteratie-ia'],
                'narrower' => [],
                'definition' => 'La mésinformation correspond au partage d’informations fausses ou trompeuses, mais sans intention malveillante de la part de celui qui les diffuse. Il s’agit souvent d’une erreur de bonne foi, comme un parent qui partage une astuce de santé miracle trouvée sur les réseaux sociaux. Bien que l’intention ne soit pas de nuire, les conséquences peuvent tout de même être graves pour la société. Ce phénomène est accentué par la rapidité des échanges numériques où l’on partage souvent avant de vérifier. Comprendre cette distinction est crucial pour éduquer les jeunes à la responsabilité numérique sans les culpabiliser.',
                'one_sentence' => 'La mésinformation est le partage involontaire d’informations fausses ou inexactes par une personne qui croit sincèrement à leur véracité, illustrant ainsi comment des erreurs commises de bonne foi peuvent se propager rapidement sur les réseaux sociaux et influencer négativement les perceptions sans qu’il n’y ait d’intention malveillante initiale.',
                'analogy' => 'C’est comme raconter un potin que l’on croit vrai à un ami, pour réaliser plus tard que l’histoire avait été totalement déformée en chemin.',
                'example' => 'Un parent partage sur un groupe Facebook une alerte concernant un enlèvement d’enfant qui s’avère être une vieille rumeur datant de plusieurs années.',
                'did_you_know' => 'Plus de la moitié des gens qui partagent de fausses nouvelles ne réalisent pas qu’elles sont inexactes au moment où ils cliquent sur « partager ».',
                'faq' => [
                    ['question' => 'Est-ce moins grave que la désinformation ?', 'answer' => 'L’intention est moins grave, mais l’impact global sur la société peut être tout aussi dévastateur si la fausse nouvelle circule largement.'],
                    ['question' => 'Pourquoi partage-t-on de la mésinformation ?', 'answer' => 'Souvent par désir d’aider, par émotion ou parce que l’information confirme nos croyances préexistantes.'],
                    ['question' => 'Les algorithmes favorisent-ils la mésinformation ?', 'answer' => 'Oui, car ils mettent en avant les contenus qui suscitent de fortes réactions, qu’ils soient vrais ou faux.'],
                    ['question' => 'Comment éviter d’en propager ?', 'answer' => 'Prenez l’habitude de lire l’article complet et de vérifier la date avant de partager un lien vers votre réseau.'],
                    ['question' => 'Que faire si j’ai partagé une fausse nouvelle ?', 'answer' => 'Supprimez la publication ou publiez un correctif pour informer ceux qui auraient pu être induits en erreur par votre message.'],
                    ['question' => 'Est-ce que tout le monde peut se faire piéger ?', 'answer' => 'Absolument, personne n’est à l’abri de la mésinformation, peu importe son niveau d’éducation ou son âge.'],
                ],
                'sources' => [
                    ['label' => 'Gouvernement du Canada — Malinformation, désinformation et mésinformation (PDF)', 'url' => 'https://www.canada.ca/content/dam/dnd-mdn/documents/maple-leaf/decouvrez-la-verite-sur-la-mal-des-mes-information.pdf', 'year' => '2023', 'author' => 'Gouvernement du Canada'],
                    ['label' => 'Commission ontarienne des droits de la personne — Mésinformation, désinformation et malinformation', 'url' => 'https://droitsdelapersonne.ca/guide-de-ressources/mesinformation-desinformation-et-malinformation', 'year' => '2024', 'author' => 'Commission ontarienne des droits de la personne'],
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'HabiloMédias — Éducation aux médias et littératie numérique', 'url' => 'https://habilomedias.ca', 'year' => '2024', 'author' => 'HabiloMédias'],
                ],
            ],
            [
                'slug' => 'ferme-de-trolls',
                'name' => 'Ferme de trolls',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🏗️',
                'category_id' => $catConcepts,
                'broader' => ['desinformation'],
                'narrower' => [],
                'definition' => 'Une ferme de trolls est une organisation coordonnée dont les membres utilisent de nombreux faux comptes pour manipuler l’opinion publique sur Internet. Ces structures opèrent souvent de manière industrielle, employant des individus pour commenter, liker et partager massivement certains contenus. Aujourd’hui, ces fermes s’équipent d’agents conversationnels basés sur l’IA pour automatiser leurs attaques. Elles peuvent ainsi générer des milliers d’articles, de vidéos et de commentaires en un temps record pour simuler un consensus populaire. Cette stratégie vise à donner l’illusion qu’une idée marginale est en réalité partagée par la majorité de la population.',
                'one_sentence' => 'Une ferme de trolls est une organisation structurée qui utilise une multitude de faux profils et d’outils d’intelligence artificielle pour manipuler l’opinion publique en diffusant massivement des messages coordonnés, créant ainsi une fausse impression de consensus populaire autour de thèmes politiques, sociaux ou commerciaux afin de déstabiliser les débats citoyens.',
                'analogy' => 'Imaginez une salle remplie de gens payés pour crier la même chose en même temps dans un mégaphone afin d’étouffer toutes les autres voix.',
                'example' => 'Lors d’une campagne électorale, des milliers de comptes automatisés harcèlent simultanément un candidat avec les mêmes fausses accusations pour miner sa crédibilité.',
                'did_you_know' => 'Certaines fermes de trolls ne sont pas politiques, mais servent simplement à discréditer des produits concurrents pour le compte d’entreprises peu scrupuleuses.',
                'faq' => [
                    ['question' => 'Est-ce que ce sont des robots ?', 'answer' => 'Parfois ce sont des humains (trolls), parfois des comptes automatisés (bots), et souvent un mélange des deux assisté par IA.'],
                    ['question' => 'Comment les repérer ?', 'answer' => 'Cherchez des comptes créés récemment, avec peu d’abonnés, qui publient de façon répétitive ou à des heures inhabituelles.'],
                    ['question' => 'Quel est leur objectif principal ?', 'answer' => 'Créer de la confusion, diviser les gens sur des sujets sensibles ou influencer les décisions politiques et économiques.'],
                    ['question' => 'Est-ce que l’IA a changé leur fonctionnement ?', 'answer' => 'Oui, l’IA leur permet de créer des profils beaucoup plus réalistes et de générer du contenu personnalisé à l’infini.'],
                    ['question' => 'Est-ce présent au Canada ?', 'answer' => 'Oui, des campagnes d’influence étrangère utilisant ces méthodes ont été documentées lors de divers scrutins et débats publics canadiens.'],
                    ['question' => 'Comment se protéger de leur influence ?', 'answer' => 'Évitez de réagir émotionnellement aux commentaires agressifs et vérifiez toujours si un compte semble appartenir à une personne réelle.'],
                ],
                'sources' => [
                    ['label' => 'Gouvernement du Canada — Malinformation, désinformation et mésinformation (PDF)', 'url' => 'https://www.canada.ca/content/dam/dnd-mdn/documents/maple-leaf/decouvrez-la-verite-sur-la-mal-des-mes-information.pdf', 'year' => '2023', 'author' => 'Gouvernement du Canada'],
                    ['label' => 'Commission ontarienne des droits de la personne — Mésinformation, désinformation et malinformation', 'url' => 'https://droitsdelapersonne.ca/guide-de-ressources/mesinformation-desinformation-et-malinformation', 'year' => '2024', 'author' => 'Commission ontarienne des droits de la personne'],
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'Bad News Game (jeu d’inoculation)', 'url' => 'https://www.getbadnews.com', 'year' => '2024', 'author' => 'Bad News Game'],
                ],
            ],
            [
                'slug' => 'chambre-d-echo',
                'name' => 'Chambre d\'écho',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🗣️',
                'category_id' => $catConcepts,
                'broader' => ['litteratie-ia'],
                'narrower' => [],
                'definition' => 'Une chambre d’écho est un environnement numérique où une personne n’est exposée qu’à des opinions qui confirment ses propres croyances. Ce phénomène est largement amplifié par les algorithmes de recommandation des réseaux sociaux qui nous proposent du contenu basé sur nos interactions passées. En ne voyant que ce qui nous plaît, nous finissons par croire que tout le monde pense comme nous. Cela renforce les certitudes et rend le dialogue avec ceux qui pensent différemment de plus en plus difficile. À long terme, cela fragilise le socle de faits communs nécessaire au bon fonctionnement d’une société démocratique.',
                'one_sentence' => 'Une chambre d’écho est un espace numérique fermé où les algorithmes de recommandation limitent l’exposition d’un utilisateur aux seules informations qui valident ses préjugés, créant ainsi un environnement qui renforce ses certitudes personnelles tout en occultant les perspectives divergentes et en fragmentant le socle de faits communs essentiels à la démocratie.',
                'analogy' => 'C’est comme si vous viviez dans une maison où toutes les fenêtres ont été remplacées par des miroirs qui ne reflètent que votre propre image.',
                'example' => 'Un utilisateur qui commence à regarder des vidéos sur une théorie spécifique se voit proposer uniquement des contenus similaires par l’algorithme, s’isolant ainsi totalement.',
                'did_you_know' => 'Le terme « bulle de filtres », inventé par Eli Pariser, décrit comment les moteurs de recherche personnalisent nos résultats au point de nous cacher la réalité.',
                'faq' => [
                    ['question' => 'Pourquoi les réseaux sociaux font-ils cela ?', 'answer' => 'Pour vous garder sur la plateforme le plus longtemps possible en vous montrant du contenu qui vous intéresse ou vous flatte.'],
                    ['question' => 'Quel est le danger pour les jeunes ?', 'answer' => 'Ils risquent de développer une vision du monde déformée et de devenir moins tolérants envers les opinions différentes des leurs.'],
                    ['question' => 'Est-ce la même chose qu’une bulle de filtres ?', 'answer' => 'Les deux sont liés : la bulle de filtres est créée par l’algorithme, alors que la chambre d’écho est le résultat social où l’on ne parle qu’à ses semblables.'],
                    ['question' => 'Comment sortir de sa chambre d’écho ?', 'answer' => 'Suivez volontairement des sources d’information variées et cherchez activement des points de vue opposés aux vôtres.'],
                    ['question' => 'Est-ce que cela influence les élections ?', 'answer' => 'Oui, car cela radicalise les positions et empêche les citoyens de s’entendre sur une base de faits partagés pour débattre.'],
                    ['question' => 'Est-ce que l’IA aggrave ce problème ?', 'answer' => 'Oui, car les systèmes d’IA sont de plus en plus performants pour prédire ce qui captivera votre attention et vous isoler dans votre bulle.'],
                ],
                'sources' => [
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'HabiloMédias — Éducation aux médias et littératie numérique', 'url' => 'https://habilomedias.ca', 'year' => '2024', 'author' => 'HabiloMédias'],
                    ['label' => 'Eli Pariser — The Filter Bubble: What the Internet Is Hiding from You (Penguin Press)', 'url' => 'https://www.thefilterbubble.com', 'year' => '2011', 'author' => 'Eli Pariser'],
                    ['label' => 'University of Cambridge (Sander van der Linden) — Inoculation theory / prebunking', 'url' => 'https://www.cam.ac.uk', 'year' => '2024', 'author' => 'University of Cambridge'],
                ],
            ],
            [
                'slug' => 'microciblage',
                'name' => 'Microciblage',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🎯',
                'category_id' => $catConcepts,
                'broader' => ['desinformation'],
                'narrower' => [],
                'definition' => 'Le microciblage est une technique marketing et politique sophistiquée consistant à segmenter la population en groupes très restreints pour leur envoyer des messages ultra-personnalisés. En analysant les données de navigation, les « likes » et les achats, les algorithmes créent des profils psychologiques précis de chaque citoyen. Ces messages sont souvent calibrés sur les peurs, les espoirs ou les biais spécifiques de chaque petit groupe identifié. Le danger réside dans le fait que deux voisins peuvent voir des messages totalement différents, voire contradictoires, sur le même sujet. Cette opacité rend difficile le débat public, car les arguments utilisés ne sont jamais visibles par l’ensemble de la population.',
                'one_sentence' => 'Le microciblage est une stratégie de communication utilisant l’analyse massive de données personnelles pour diffuser des messages publicitaires ou politiques ultra-personnalisés à des segments très précis de la population, permettant ainsi d’exploiter les biais psychologiques individuels tout en soustrayant ces arguments au débat public et à la vérification collective.',
                'analogy' => 'C’est comme si un politicien passait de porte en porte pour promettre à chaque personne exactement ce qu’elle veut entendre, sans que personne d’autre ne puisse l’écouter.',
                'example' => 'Une entreprise utilise vos données de localisation et vos intérêts pour les animaux pour vous montrer une publicité affirmant faussement qu’un parc local sera détruit.',
                'did_you_know' => 'Lors de certaines campagnes, des milliers de versions différentes d’une même publicité ont été créées pour cibler les électeurs selon leurs traits de personnalité spécifiques.',
                'faq' => [
                    ['question' => 'Comment mes données sont-elles collectées ?', 'answer' => 'Par vos recherches Web, vos interactions sur les réseaux sociaux, vos achats en ligne et parfois même vos applications mobiles gratuites.'],
                    ['question' => 'Est-ce légal au Québec ?', 'answer' => 'La Loi 25 encadre strictement la collecte de données, mais le microciblage reste possible si vous avez donné votre consentement général aux plateformes.'],
                    ['question' => 'Pourquoi est-ce un problème pour la démocratie ?', 'answer' => 'Parce que cela permet de manipuler les gens discrètement sans qu’un débat public transparent sur les promesses faites puisse avoir lieu.'],
                    ['question' => 'Est-ce que l’IA facilite le microciblage ?', 'answer' => 'Absolument, l’IA peut analyser des milliards de points de données pour identifier des tendances psychologiques que les humains ne verraient jamais.'],
                    ['question' => 'Les enfants sont-ils ciblés ?', 'answer' => 'Bien que la loi protège les mineurs, les algorithmes de recommandation pratiquent une forme de ciblage comportemental dès le plus jeune âge.'],
                    ['question' => 'Comment s’en protéger ?', 'answer' => 'Limitez les informations que vous partagez, utilisez des outils de blocage de traceurs et gérez soigneusement vos paramètres de confidentialité.'],
                ],
                'sources' => [
                    ['label' => 'Gouvernement du Canada — Malinformation, désinformation et mésinformation (PDF)', 'url' => 'https://www.canada.ca/content/dam/dnd-mdn/documents/maple-leaf/decouvrez-la-verite-sur-la-mal-des-mes-information.pdf', 'year' => '2023', 'author' => 'Gouvernement du Canada'],
                    ['label' => 'Commission ontarienne des droits de la personne — Mésinformation, désinformation et malinformation', 'url' => 'https://droitsdelapersonne.ca/guide-de-ressources/mesinformation-desinformation-et-malinformation', 'year' => '2024', 'author' => 'Commission ontarienne des droits de la personne'],
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'HabiloMédias — Éducation aux médias et littératie numérique', 'url' => 'https://habilomedias.ca', 'year' => '2024', 'author' => 'HabiloMédias'],
                ],
            ],
            [
                'slug' => 'economie-de-l-attention',
                'name' => 'Économie de l\'attention',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🎰',
                'category_id' => $catConcepts,
                'broader' => [],
                'narrower' => [],
                'definition' => 'L’économie de l’attention est un modèle d’affaires où le temps et l’attention des utilisateurs sont considérés comme des ressources rares et précieuses à exploiter. Dans ce système, les plateformes gratuites se rémunèrent en vendant l’accès à votre cerveau aux annonceurs publicitaires. Pour maximiser leurs revenus, elles utilisent des algorithmes conçus pour vous garder accroché le plus longtemps possible. L’indignation, la colère et le scandale sont souvent privilégiés par ces systèmes, car ils sont plus efficaces pour capter notre attention que les faits neutres. Il est crucial d’expliquer aux jeunes que leur temps de cerveau disponible est le véritable produit vendu par ces entreprises technologiques.',
                'one_sentence' => 'L’économie de l’attention désigne un modèle économique où le temps et la disponibilité mentale des utilisateurs constituent la ressource principale vendue aux annonceurs, poussant les plateformes numériques à utiliser des algorithmes de captation conçus pour maximiser l’engagement, souvent au détriment de la qualité de l’information ou du bien-être psychologique des individus.',
                'analogy' => 'C’est comme une machine à sous conçue pour vous donner juste assez de petites récompenses pour que vous restiez assis devant l’écran toute la journée.',
                'example' => 'Un réseau social affiche une vidéo choquante en haut de votre fil de nouvelles simplement parce qu’elle génère beaucoup de clics, ignorant son manque de véracité.',
                'did_you_know' => 'Le design des applications, comme le « défilement infini », est inspiré des techniques psychologiques utilisées dans les casinos pour faire perdre la notion du temps.',
                'faq' => [
                    ['question' => 'Si c\'est gratuit, quel est le prix ?', 'answer' => 'Comme on le dit souvent : « Si c’est gratuit, c’est que vous êtes le produit. » Votre attention est la marchandise vendue.'],
                    ['question' => 'Pourquoi l’indignation fonctionne-t-elle si bien ?', 'answer' => 'La colère déclenche une réaction biologique rapide qui nous pousse à commenter ou à partager, ce qui est très payant pour les plateformes.'],
                    ['question' => 'Quel est l’impact sur la santé mentale ?', 'answer' => 'Cela peut mener à l’épuisement numérique, à l’anxiété et à une diminution de la capacité de concentration chez les enfants et les adultes.'],
                    ['question' => 'Comment les algorithmes choisissent-ils quoi montrer ?', 'answer' => 'Ils privilégient ce qui a le plus de chances de provoquer une réaction immédiate de votre part pour prolonger votre session de navigation.'],
                    ['question' => 'Peut-on y échapper ?', 'answer' => 'On peut limiter l’impact en désactivant les notifications, en utilisant des minuteurs d’écran et en choisissant des services payants sans publicité.'],
                    ['question' => 'Est-ce que l’IA rend ce modèle plus puissant ?', 'answer' => 'Oui, l’IA apprend en temps réel exactement ce qui vous fait réagir, devenant de plus en plus efficace pour capturer votre attention.'],
                ],
                'sources' => [
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'HabiloMédias — Éducation aux médias et littératie numérique', 'url' => 'https://habilomedias.ca', 'year' => '2024', 'author' => 'HabiloMédias'],
                    ['label' => 'Eli Pariser — The Filter Bubble: What the Internet Is Hiding from You (Penguin Press)', 'url' => 'https://www.thefilterbubble.com', 'year' => '2011', 'author' => 'Eli Pariser'],
                    ['label' => 'Bad News Game (jeu d’inoculation)', 'url' => 'https://www.getbadnews.com', 'year' => '2024', 'author' => 'Bad News Game'],
                ],
            ],
            [
                'slug' => 'systeme-immunitaire-informationnel',
                'name' => 'Système immunitaire informationnel',
                'type' => 'concept',
                'difficulty' => 'beginner',
                'icon' => '🛡️',
                'category_id' => $catConcepts,
                'broader' => ['litteratie-ia'],
                'narrower' => [],
                'definition' => 'Le système immunitaire informationnel est une métaphore éducative inspirée de la biologie pour apprendre à se protéger contre la manipulation numérique. Plutôt que de simplement censurer le contenu, cette approche consiste à exposer l’enfant à de faibles doses contrôlées de désinformation pour qu’il apprenne à les reconnaître. On parle aussi de « prebunking » ou de théorie de l’inoculation. En comprenant comment les manipulateurs procèdent, l’utilisateur développe des réflexes critiques ou des « anticorps » intellectuels. Cette méthode s’avère bien plus efficace que la simple vérification des faits après coup, car elle prépare l’esprit à rejeter les mensonges avant même qu’ils ne s’installent.',
                'one_sentence' => 'Le système immunitaire informationnel est une approche pédagogique basée sur la théorie de l’inoculation qui vise à renforcer la résilience des individus en les exposant de manière préventive et encadrée à des techniques de manipulation afin qu’ils développent des réflexes critiques durables pour identifier et rejeter la désinformation avant qu’elle ne s’installe dans leur esprit.',
                'analogy' => 'C’est comme un vaccin qui prépare votre corps à combattre un virus en lui montrant une version affaiblie de celui-ci pour qu’il sache comment se défendre.',
                'example' => 'Un enseignant montre à ses élèves comment une ferme de trolls utilise des émotions fortes pour tromper, afin que les jeunes sachent repérer ces tactiques plus tard.',
                'did_you_know' => 'Des chercheurs de l’Université de Cambridge ont prouvé que jouer à des jeux comme « Bad News », où l’on incarne un manipulateur, aide vraiment à mieux détecter les fausses nouvelles.',
                'faq' => [
                    ['question' => 'Pourquoi ne pas simplement bloquer les fausses nouvelles ?', 'answer' => 'Parce qu’il est impossible de tout bloquer. Apprendre à les reconnaître est une protection qui vous suit partout, même sur les sites non filtrés.'],
                    ['question' => 'Qu’est-ce que le « prebunking » ?', 'answer' => 'C’est l’action de prévenir les gens contre une technique de manipulation avant qu’ils n’y soient confrontés, comme une forme de mise en garde.'],
                    ['question' => 'Est-ce adapté aux enfants ?', 'answer' => 'Oui, c’est l’une des meilleures façons d’enseigner la littératie numérique, car cela les rend acteurs de leur propre défense intellectuelle.'],
                    ['question' => 'Comment mettre cela en pratique à la maison ?', 'answer' => 'Regardez ensemble des publicités ou des titres sensationnalistes et essayez de deviner quelles émotions ils tentent de déclencher chez vous.'],
                    ['question' => 'Est-ce que cela fonctionne contre les deepfakes ?', 'answer' => 'Oui, en comprenant comment l’IA peut truquer une vidéo, on devient naturellement plus méfiant face aux images qui semblent trop incroyables pour être vraies.'],
                    ['question' => 'Qui a inventé cette méthode ?', 'answer' => 'Elle repose sur les travaux de psychologie sociale de Sander van der Linden et d’autres chercheurs étudiant la résilience face à la désinformation.'],
                ],
                'sources' => [
                    ['label' => 'UNESCO — Éducation aux médias et à l’information', 'url' => 'https://www.unesco.org/en/media-information-literacy', 'year' => '2024', 'author' => 'UNESCO'],
                    ['label' => 'HabiloMédias — Éducation aux médias et littératie numérique', 'url' => 'https://habilomedias.ca', 'year' => '2024', 'author' => 'HabiloMédias'],
                    ['label' => 'University of Cambridge (Sander van der Linden) — Inoculation theory / prebunking', 'url' => 'https://www.cam.ac.uk', 'year' => '2024', 'author' => 'University of Cambridge'],
                    ['label' => 'Bad News Game (jeu d’inoculation)', 'url' => 'https://www.getbadnews.com', 'year' => '2024', 'author' => 'Bad News Game'],
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
