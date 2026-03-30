<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class EnrichAudioToolsSeeder extends Seeder
{
    private string $locale = 'fr_CA';

    public function run(): void
    {
        foreach ($this->tools() as $name => $data) {
            $tool = Tool::where('name->'.$this->locale, $name)->first();
            if (! $tool) {
                $this->command?->warn("Outil '{$name}' non trouvé, ignoré.");
                continue;
            }

            foreach (['description', 'core_features', 'use_cases', 'pros', 'cons'] as $field) {
                if (isset($data[$field])) {
                    $tool->setTranslation($field, $this->locale, $data[$field]);
                }
            }

            if (isset($data['faq'])) {
                $tool->faq = $data['faq'];
            }

            $tool->save();
            $this->command?->info("✅ {$name} enrichi (".mb_strlen($tool->getTranslation('description', $this->locale)).' car.)');
        }
    }

    private function tools(): array
    {
        return [
            'AIVA' => [
                'description' => <<<'MD'
AIVA (Artificial Intelligence Virtual Artist) est l'un des pionniers mondiaux de la composition musicale assistée par intelligence artificielle. Depuis son lancement, cette plateforme s'est imposée comme un outil incontournable pour les créateurs souhaitant générer rapidement des morceaux originaux, sans sacrifier la qualité artistique. En 2026, AIVA continue d'évoluer avec une base de plus de 250 styles musicaux et des fonctionnalités adaptées aussi bien aux professionnels qu'aux néophytes.

## À propos de AIVA

Lancée en 2016 par une équipe européenne passionnée de musique classique et d'intelligence artificielle, AIVA a marqué un tournant en devenant la première IA reconnue officiellement comme compositeur légal (notamment au Luxembourg). En 2026, elle s'est largement démocratisée, notamment grâce à son interface intuitive et à ses modèles entraînés sur des milliers d'œuvres classiques, contemporaines et cinématographiques. AIVA s'adresse autant aux compositeurs expérimentés cherchant à accélérer leur flux de travail qu'aux créateurs de contenu n'ayant aucune formation musicale. Son moteur, basé sur des réseaux de neurones profonds, permet de générer des partitions cohérentes, émotionnelles et structurées selon les principes de la théorie musicale. La plateforme est particulièrement appréciée dans les milieux du jeu vidéo, du cinéma indépendant et du marketing digital, où la rapidité et la personnalisation sont essentielles.

## Fonctionnalités principales

- **Composition IA en 250+ styles** : générez des morceaux dans des genres variés, allant du baroque au synthwave, en passant par le jazz lounge ou la musique d'ambiance.
- **Modèles de style personnalisés** : créez vos propres « empreintes » musicales en combinant des éléments de différents styles existants.
- **Upload audio/MIDI** : importez vos propres fichiers pour les analyser, les compléter ou les réharmoniser via l'IA.
- **Éditeur intégré** : modifiez les pistes, ajustez les instruments, changez la structure (couplet, refrain, pont) directement dans l'interface.
- **Export MP3/WAV/MIDI** : téléchargez vos compositions en formats haute qualité, prêts pour la production ou l'intégration dans des projets externes.
- **Collaboration en temps réel** : partagez des projets avec d'autres utilisateurs et travaillez ensemble, idéal pour les équipes de développement de jeux ou de films.
- **Licences commerciales claires** : avec le plan Pro, vous détenez les droits d'auteur complets et pouvez monétiser librement vos œuvres.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | 0 € | 3 téléchargements/mois, crédits AIVA obligatoires, pas de droits commerciaux |
| Standard | 11 €/mois | 15 téléchargements/mois, usage non commercial uniquement |
| Pro | 33 €/mois | 300 téléchargements/mois, droits d'auteur complets, usage commercial illimité |

## Comparaison avec les alternatives

Face à des concurrents comme **Udio**, **Soundful**, **Suno** ou **Boomy**, AIVA se distingue par sa spécialisation dans la musique instrumentale structurée, notamment orchestrale et cinématique. Udio et Suno excellent dans la génération de chansons avec paroles, mais offrent moins de contrôle sur la structure harmonique. Soundful cible surtout les créateurs de contenu YouTube avec des boucles courtes, tandis que Boomy mise sur la simplicité extrême, au détriment de la profondeur musicale. AIVA propose un équilibre rare entre accessibilité, qualité compositionnelle et flexibilité éditoriale.

## Notre avis

Du point de vue québécois, où la création culturelle indépendante est fortement valorisée, AIVA représente une opportunité précieuse pour les artistes, développeurs et entrepreneurs locaux. Son modèle tarifaire transparent et ses licences claires (surtout en version Pro) répondent aux besoins des créateurs soucieux de leurs droits. Nous recommandons particulièrement AIVA aux compositeurs de jeux vidéo indépendants, aux réalisateurs de courts métrages et aux podcasteurs québécois cherchant une identité sonore originale sans dépendre de banques de musique génériques.
MD,
                'core_features' => 'Composition IA en 250+ styles, Modèles de style personnalisés, Upload audio/MIDI, Éditeur intégré, Export MP3/WAV/MIDI, Collaboration, Licences commerciales',
                'use_cases' => 'Musique de film et jeux vidéo, Contenu YouTube et podcasts, Publicités, Musique d\'ambiance, Prototypage musical',
                'pros' => 'Interface simple pour débutants, 250+ styles musicaux, Copyright utilisateur sur plan Pro, Export en formats professionnels',
                'cons' => 'Plan gratuit très limité (3 téléchargements/mois), Orienté orchestral/cinématique surtout',
                'faq' => [
                    ['question' => 'Peut-on utiliser la musique AIVA commercialement ?', 'answer' => 'Oui, avec le plan Pro (33 €/mois), vous détenez les droits d\'auteur complets et pouvez monétiser sans restriction.'],
                    ['question' => 'AIVA compose-t-il dans tous les genres musicaux ?', 'answer' => 'AIVA propose 250+ styles incluant orchestral, pop, jazz, électronique et ambiance. Les styles orchestraux et cinématiques sont les plus aboutis.'],
                    ['question' => 'Faut-il des connaissances musicales pour utiliser AIVA ?', 'answer' => 'Non, l\'interface est conçue pour les débutants. Vous pouvez générer une pièce complète en quelques clics sans aucune formation musicale.'],
                ],
            ],

            'Descript' => [
                'description' => <<<'MD'
Descript est une plateforme révolutionnaire d'édition audio et vidéo basée sur l'intelligence artificielle, conçue pour simplifier radicalement le processus de montage. Plutôt que de manipuler des pistes et des calques comme dans les logiciels traditionnels, Descript permet aux utilisateurs d'éditer leur contenu en modifiant directement la transcription textuelle de leurs médias — chaque suppression ou modification dans le texte se traduit automatiquement par un changement dans la vidéo ou l'audio. Ce paradigme unique rend l'édition accessible même aux débutants, tout en offrant des outils puissants pour les professionnels.

## À propos de Descript

Fondé en 2018, Descript s'est rapidement imposé comme une solution incontournable pour les créateurs de contenu cherchant à gagner du temps sans sacrifier la qualité. Son approche « éditer le texte, pas la timeline » repose sur une transcription IA précise et quasi instantanée, supportant plusieurs langues et locuteurs. La plateforme intègre également des fonctionnalités avancées comme la synthèse vocale réaliste, la suppression automatique des mots parasites, et l'amélioration audio par IA.

## Fonctionnalités principales

- **Édition par transcription** : supprimez, déplacez ou modifiez des phrases dans le texte pour modifier automatiquement la vidéo ou l'audio correspondant.
- **Overdub** : clonez votre voix pour générer du discours synthétique réaliste, idéal pour corriger des erreurs sans reprendre l'enregistrement.
- **Studio Sound** : améliorez instantanément la qualité audio avec un traitement IA qui réduit le bruit de fond et égalise le volume.
- **Filler Word Removal** : supprimez automatiquement les « euh », « hein », silences gênants pour un discours plus fluide.
- **Collaboration en temps réel** : partagez des projets, commentez des sections et travaillez ensemble comme sur Google Docs.
- **Screen recording et multicam** : enregistrez votre écran, votre webcam, ou combinez plusieurs angles.

## Tarification

| Plan | Prix/mois | Heures de média | Fonctionnalités clés |
|------|-----------|-----------------|----------------------|
| Free | 0 $ | 1 heure | Édition texte, transcription, export 720p |
| Hobbyist | 24 $ | 10 heures | Overdub (1 voix), filler word removal, 1080p |
| Creator | 35 $ | 30 heures | Overdub illimité, collaboration, stock média |
| Business | 65 $ | 40 heures | Contrôle admin, priorité support, SSO |

## Comparaison avec les alternatives

Contrairement à **Adobe Premiere Pro**, qui exige une courbe d'apprentissage abrupte, Descript mise sur la simplicité. **CapCut** offre une interface intuitive mais manque de profondeur en édition audio. **Riverside** excelle dans l'enregistrement haute qualité mais n'intègre pas d'outils d'édition aussi complets. **Audacity**, bien que gratuit, reste un éditeur audio pur sans transcription ni IA. Descript se distingue par son intégration fluide entre transcription, édition multimédia et IA générative.

## Notre avis

Descript est un changement de paradigme pour les podcasters, YouTubers et agences de production québécoises. Il réduit drastiquement le temps de post-production — ce qui était autrefois une tâche technique devient presque aussi simple que corriger un document Word. Bien que les plans payants soient relativement chers pour les gros volumes, la productivité gagnée justifie l'investissement. Pour quiconque travaille régulièrement avec de la parole enregistrée au Québec, Descript est aujourd'hui l'un des outils les plus efficaces du marché.
MD,
                'core_features' => 'Édition par transcription textuelle, Overdub (clonage vocal IA), Studio Sound (amélioration audio IA), Suppression automatique des mots parasites, Enregistrement écran/webcam, Collaboration en temps réel, Export multiplateforme',
                'use_cases' => 'Montage de podcasts, Création de vidéos YouTube, Production de contenus marketing, Formation en ligne, Sous-titrage automatique, Correction de discours',
                'pros' => 'Interface ultra-intuitive, Gain de temps considérable, Transcription précise, Voix Overdub réaliste, Amélioration audio automatique',
                'cons' => 'Limites strictes d\'heures selon les abonnements, Overdub consomme des crédits rapidement, Moins adapté aux projets purement visuels',
                'faq' => [
                    ['question' => 'Puis-je utiliser Descript sans compétence en montage ?', 'answer' => 'Absolument ! Si vous savez éditer un texte, vous pouvez éditer une vidéo ou un podcast.'],
                    ['question' => 'Le clonage vocal Overdub est-il légal ?', 'answer' => 'Descript exige une vérification explicite pour cloner une voix humaine, destiné à un usage avec consentement.'],
                    ['question' => 'Combien de temps pour transcrire une heure d\'audio ?', 'answer' => 'Moins d\'une minute. La transcription IA est quasi instantanée avec une précision élevée.'],
                ],
            ],

            'Murf AI' => [
                'description' => <<<'MD'
Murf AI est une plateforme d'intelligence artificielle spécialisée dans la synthèse vocale (text-to-speech) qui permet de transformer du texte en voix humaine réaliste avec une grande précision émotionnelle et linguistique. Conçue pour les professionnels comme les marketeurs, les créateurs de contenu éducatif et les producteurs vidéo, elle se distingue par sa facilité d'utilisation et sa qualité audio exceptionnelle.

## À propos de Murf AI

Fondée en 2018, Murf AI s'est imposée comme l'une des solutions TTS les plus accessibles et performantes du marché. La plateforme offre des voix naturelles dans plus de 20 langues, dont le français canadien — un atout non négligeable pour les utilisateurs québécois. Son interface intuitive, combinée à des outils collaboratifs et multimédias, en fait un choix privilégié pour la création de contenus audio professionnels sans nécessiter de studio d'enregistrement.

## Fonctionnalités principales

- **Voix ultra-réalistes** : plus de 120 voix disponibles, incluant des variantes régionales comme le français du Québec.
- **Personnalisation émotionnelle** : ajustez le ton, le rythme, l'émotion et l'accentuation pour adapter le discours au contexte.
- **Clonage vocal** : créez une voix personnalisée à partir d'un échantillon audio (plans Pro et Enterprise).
- **Bibliothèque multimédia intégrée** : musiques libres de droits et effets sonores pour enrichir vos projets.
- **Édition vidéo native** : synchronisez automatiquement la voix avec des vidéos ou des diapositives.
- **API et intégrations** : automatisez la génération vocale dans vos applications.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free Trial | Gratuit | 10 minutes de voix générées, accès limité |
| Basic | 29 $/mois | 24 heures/an, toutes les voix, export MP3/WAV |
| Pro | 79 $/mois | 120 heures/an, voice cloning, collaboration, priorité support |
| Enterprise | Sur devis | Illimité, sécurité renforcée, SLA dédié |

## Comparaison avec les alternatives

Face à **ElevenLabs**, Murf AI propose une interface plus orientée vers la production multimédia, tandis qu'ElevenLabs excelle en naturalisme vocal pur. **Play.ht** mise davantage sur les développeurs avec son API, mais son interface est moins conviviale. **WellSaid Labs** cible principalement les entreprises américaines avec peu de support pour le français canadien. Murf AI se démarque par son équilibre entre simplicité, qualité et polyvalence pour les francophones.

## Notre avis

Pour les créateurs de contenu québécois, Murf AI représente une solution rarement égalée : elle propose une voix francophone authentique avec intonation locale, et intègre des outils directement exploitables. Le rapport qualité-prix reste excellent comparé aux coûts d'un narrateur humain. La possibilité de cloner une voix locale est un avantage stratégique majeur dans l'e-learning ou la publicité. Globalement, Murf AI est la meilleure option francophone réaliste et professionnelle disponible pour les Québécois.
MD,
                'core_features' => '120+ voix réalistes dans 20+ langues, Support du français canadien, Personnalisation du ton et des émotions, Voice cloning, Bibliothèque musique et effets sonores, Éditeur vidéo/audio synchronisé, API pour intégration',
                'use_cases' => 'Création de modules e-learning, Narration de vidéos publicitaires, Production de podcasts automatisés, Audiodescriptions, Prototypage vocal, Doublage multilingue',
                'pros' => 'Qualité audio exceptionnelle, Prise en charge du français québécois, Outils vidéo/audio tout-en-un, Voice cloning accessible',
                'cons' => 'Limite annuelle stricte d\'heures de voix, Plan gratuit très restreint (10 min), Coût élevé pour les micro-entreprises',
                'faq' => [
                    ['question' => 'Murf AI propose-t-il une voix en français québécois ?', 'answer' => 'Oui, Murf AI inclut des voix francophones avec des intonations adaptées au Canada.'],
                    ['question' => 'Puis-je utiliser Murf AI pour des projets commerciaux ?', 'answer' => 'Tous les plans payants incluent une licence commerciale complète.'],
                    ['question' => 'Le voice cloning fonctionne-t-il avec un accent québécois ?', 'answer' => 'Oui, à condition de fournir un échantillon audio clair de 1 à 5 minutes.'],
                ],
            ],

            'Play.ht' => [
                'description' => <<<'MD'
Play.ht s'est imposé comme l'une des plateformes de synthèse vocale IA les plus complètes du marché, avec un catalogue de plus de 900 voix dans 140 langues. Que vous soyez développeur, podcasteur ou créateur de contenu, Play.ht offre une combinaison rare de diversité vocale, de qualité audio et d'outils techniques avancés.

## À propos de Play.ht

Play.ht a rapidement évolué d'un simple outil text-to-speech vers une plateforme complète intégrant le streaming en temps réel, le clonage vocal et l'hébergement de podcasts. En 2026, la plateforme se distingue par son API REST robuste et son support avancé du SSML pour un contrôle fin sur la prononciation et le rythme. Son catalogue couvre un spectre impressionnant de langues et d'accents, bien que les voix francophones restent principalement calquées sur le français européen.

## Fonctionnalités principales

- **900+ voix réalistes** : un des plus grands catalogues du marché, couvrant 140+ langues et variantes régionales.
- **Streaming vocal temps réel** : génération à faible latence pour les applications interactives et chatbots.
- **Clonage vocal personnalisé** : créez une voix IA à partir de votre propre enregistrement.
- **API REST robuste** : intégrez la synthèse vocale dans vos applications avec une documentation claire.
- **Hébergement de podcasts intégré** : publiez vos podcasts générés par IA directement sur les grandes plateformes.
- **Support SSML avancé** : contrôlez la prononciation, les pauses, l'emphase et le rythme.

## Tarification

| Plan | Prix | Détails |
|------|------|---------|
| Free | 0 $ | 12 500 caractères/mois, voix limitées, usage non commercial |
| Personal | 31 $/mois | 600 000 caractères/mois, toutes les voix, usage commercial |
| Creator | 79 $/mois | Illimité, voice cloning, podcast hosting, priorité API |
| Enterprise | Sur devis | SLA, sécurité renforcée, support dédié |

## Comparaison avec les alternatives

Par rapport à **ElevenLabs**, Play.ht offre plus de voix (900+ vs ~100+) et un meilleur support multilingue, mais ElevenLabs excelle en expressivité émotionnelle. Face à **Amazon Polly** ou **Google Cloud TTS**, Play.ht se distingue par son interface conviviale et ses outils intégrés. Comparé à **Murf AI**, Play.ht est davantage orienté vers les développeurs grâce à son API.

## Notre avis

Pour les développeurs et créateurs québécois, Play.ht est un choix solide si la diversité des voix et l'intégration technique sont des priorités. Le plan Free permet de tester, et le Personal à 31 $/mois est compétitif. Toutefois, les voix francophones manquent encore de nuances québécoises — le clonage vocal peut compenser. En somme, Play.ht est la meilleure option pour les projets techniques nécessitant une API fiable et un large choix de voix.
MD,
                'core_features' => '900+ voix réalistes, Support de 140+ langues, Streaming vocal temps réel, Clonage vocal, API REST, Hébergement podcast intégré, Support SSML avancé',
                'use_cases' => 'Création de podcasts automatisés, Narration éducative, Assistants vocaux, Production de livres audio, Accessibilité numérique, Vidéos marketing',
                'pros' => 'Grande diversité de voix, Interface intuitive, Plan gratuit généreux, Hébergement podcast inclus, API bien documentée',
                'cons' => 'Voix francophones surtout français européen, Clonage vocal requiert abonnement payant, Pas de version desktop native',
                'faq' => [
                    ['question' => 'Play.ht propose-t-il des voix en français canadien ?', 'answer' => 'Oui, avec des variantes canadiennes, mais les intonations restent proches du français européen. Le clonage vocal peut compenser.'],
                    ['question' => 'Puis-je utiliser Play.ht gratuitement ?', 'answer' => 'Oui, le plan Free inclut 12 500 caractères/mois — suffisant pour tester.'],
                    ['question' => 'Comment Play.ht se compare-t-il à ElevenLabs ?', 'answer' => 'Play.ht offre plus de voix (900+) et un meilleur support multilingue, mais ElevenLabs excelle en expressivité.'],
                ],
            ],

            'Podcastle' => [
                'description' => <<<'MD'
Créer un podcast de qualité professionnelle sans être ingénieur de son, c'est exactement la promesse de Podcastle. Cette plateforme tout-en-un propulsée par l'intelligence artificielle permet d'enregistrer, de monter et de publier des épisodes de balado directement depuis un navigateur web. Que vous soyez un créateur qui lance son premier podcast ou un éducateur qui souhaite rejoindre son audience autrement, Podcastle se positionne comme une solution accessible, intuitive et étonnamment puissante.

## À propos de Podcastle

Podcastle est une plateforme de création de podcasts basée sur l'infonuagique, fondée en 2020 et ayant rapidement gagné en popularité grâce à son approche centrée sur l'intelligence artificielle. L'idée est simple : démocratiser la production de balados en éliminant les barrières techniques. Plutôt que de jongler entre plusieurs logiciels, Podcastle regroupe tout dans une seule interface. La plateforme s'adresse aux podcasters débutants et intermédiaires, aux éducateurs, aux créateurs solo et aux petites équipes.

## Fonctionnalités principales

- **Enregistrement multi-pistes** : enregistrez des conversations avec plusieurs participants sur des pistes distinctes.
- **Édition par transcription** : modifiez votre épisode dans la transcription textuelle — supprimez un mot et l'audio disparaît automatiquement.
- **AI Audio Enhancer** : améliorez automatiquement la qualité sonore, même avec un micro d'entrée de gamme.
- **Suppression du bruit de fond** : l'IA élimine les bruits ambiants pour ne conserver que la voix.
- **Voice Changer IA** : transformez votre voix ou générez des voix synthétiques pour créer des intros ou narrations.
- **Transcription automatique** : pour l'accessibilité, le SEO et la création de contenu dérivé.
- **Publication directe** : publiez directement sur Spotify et Apple Podcasts.

## Tarification

| Plan | Prix | Enregistrement | Fonctionnalités clés |
|------|------|----------------|----------------------|
| Free | 0 $/mois | 3 heures/mois | Enregistrement, édition de base, filigrane audio |
| Storyteller | 15 $/mois | Illimité | Toutes les fonctionnalités IA, export sans filigrane, publication directe |
| Pro | 30 $/mois | Illimité | Tout Storyteller + collaboration équipe, priorité support |

## Comparaison avec les alternatives

- **Descript** : concurrent direct, plus cher (24 $/mois) et inclut la vidéo. Podcastle est plus simple et plus abordable pour l'audio.
- **Riverside** : excelle en enregistrement à distance haute qualité, mais moins d'outils de montage intégrés.
- **Anchor/Spotify for Podcasters** : gratuit mais très limité en montage et amélioration audio.
- **Zencastr** : bon enregistrement multi-pistes mais fonctionnalités IA moins développées.

## Notre avis

Podcastle est l'une des meilleures options pour les podcasters débutants et intermédiaires. L'édition par transcription est un vrai changement de paradigme. Du point de vue québécois, la scène du balado est en pleine effervescence, et Podcastle répond au besoin d'outils accessibles. La transcription fonctionne en français, même si l'accent québécois peut réduire la précision. À 15 $/mois pour le plan Storyteller, c'est très compétitif. Pour un créateur solo ou une petite équipe au Québec, c'est une solution qu'on recommande chaudement.
MD,
                'core_features' => 'Enregistrement multi-pistes, Édition par transcription, Voice Changer IA, AI Audio Enhancer, Suppression du bruit de fond, Transcription automatique, Publication directe Spotify/Apple Podcasts',
                'use_cases' => 'Création de balados pour débutants, Enregistrement d\'entrevues à distance, Podcasts éducatifs, Production audio pour PME, Transcription pour le SEO',
                'pros' => 'Interface intuitive pour débutants, Édition par transcription révolutionnaire, Outils IA performants, Prix compétitif (15 $/mois), Solution tout-en-un',
                'cons' => 'Transcription en français québécois parfois imprécise, Collaboration réservée au plan Pro, Plan gratuit limité avec filigrane',
                'faq' => [
                    ['question' => 'Podcastle supporte-t-il la transcription en français ?', 'answer' => 'Oui, avec une précision qui peut varier selon l\'accent québécois. Il est recommandé de relire la transcription.'],
                    ['question' => 'Peut-on utiliser Podcastle gratuitement ?', 'answer' => 'Oui, le plan Free permet 3 heures/mois avec les fonctionnalités de base, mais les exports incluent un filigrane audio.'],
                    ['question' => 'Quelle est la différence entre Podcastle et Descript ?', 'answer' => 'Podcastle est spécialisé podcast audio (15 $/mois), Descript couvre aussi la vidéo (24 $/mois, plus complexe).'],
                ],
            ],
        ];
    }
}
