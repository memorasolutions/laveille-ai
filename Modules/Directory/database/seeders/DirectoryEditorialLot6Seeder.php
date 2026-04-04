<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

/**
 * Enrichissement editorial lot 6 - Audio IA (5 outils).
 * Session 130 (2026-03-26).
 */
class DirectoryEditorialLot6Seeder extends Seeder
{
    public function run(): void
    {
        $articles = $this->getArticles();

        foreach ($articles as $slug => $description) {
            $tool = Tool::where('slug->fr_CA', $slug)->first()
                ?? Tool::where('slug->'.app()->getLocale(), $slug)->first();

            if ($tool) {
                $tool->setTranslation('description', 'fr_CA', $description);
                $tool->save();
                $this->command->info("Updated: {$slug}");
            } else {
                $this->command->warn("Not found: {$slug}");
            }
        }
    }

    private function getArticles(): array
    {
        return [
            'murf-ai' => $this->murfAi(),
            'play-ht' => $this->playHt(),
            'descript' => $this->descript(),
            'aiva' => $this->aiva(),
            'podcastle' => $this->podcastle(),
        ];
    }

    private function murfAi(): string
    {
        return <<<'MD'
L'évolution de l'intelligence artificielle générative a profondement transforme la manière dont les createurs de contenu et les entreprises produisent des ressources multimedia. Parmi les innovations les plus marquantes, la synthese vocale a atteint un niveau de maturite impressionnant. Murf AI se positionne comme l'un des leaders de ce marche en proposant une solution complete pour transformer du texte en voix-off de qualite studio en quelques minutes.

## A propos de Murf AI

Murf AI est une plateforme de generation de voix-off basee sur le cloud qui s'adresse aussi bien aux createurs individuels qu'aux grandes entreprises. Fondee avec l'objectif de démocratiser la production audio, la solution repose sur des algorithmes d'apprentissage profond capables de synthétiser des voix extrêmêment realistes. Contrairement aux anciens systèmes de synthese vocale qui produisaient des sons robotiques et monocordes, Murf AI utilise des modèles de langage sophistiques pour comprendre le contexte du texte et adapter la diction en consequence.

La plateforme se distingue par son interface utilisateur intuitive qui ressemble a un editeur de texte ou a un logiciel de montage video simplifié. Murf AI cible un large eventail d'applications : des videos explicatives, des modules d'apprentissage en ligne, des publicites, des podcasts, et même des presentations professionnelles.

## Fonctionnalites principales

Murf AI propose une bibliotheque impressionnante de plus de 200 voix reparties dans plus de 20 langues et accents. Cette diversite permet aux utilisateurs de cibler des audiences mondiales en adaptant la voix au ton local recherche.

L'une des forces majeures est son studio d'edition intègre. L'utilisateur peut ajuster précisement la vitesse, la hauteur (pitch) et l'emphase de chaque mot. Il est possible d'inserer des pauses specifiques pour donner du rythme a la narration. De plus, la plateforme permet de synchroniser directement la voix-off avec des images ou des videos importees.

L'intégration est un autre pilier. Le logiciel propose des extensions pour Canva et Google Slides, permettant d'ajouter des voix-off directement sur des supports de presentation. Pour les entreprises, les fonctions de collaboration en equipe sont cruciales : plusieurs membres peuvent travailler sur un même projet, partager des modèles et centraliser les actifs audio. Enfin, le "Voice Cloning" (disponible dans les offres superieures) permet de creer un double numerique d'une voix humaine specifique.

## Tarification

Le plan Creator Lite, propose a 29 dollars par mois, constitue l'entree de gamme pour les createurs individuels. Le plan Creator Plus, facture 49 dollars par mois, est destiné aux professionnels ayant des besoins plus reguliers avec plus d'heures de generation vocale.

Le plan Business Lite a 99 dollars par mois permet une première approche du travail collaboratif. Le plan Business Plus a 199 dollars par mois est la solution phare pour les equipes de production avec des capacites massives et un support prioritaire.

L'offre Enterprise est une solution sur mesure destinée aux grandes organisations necessitant le clonage de voix personnalise, une sécurité renforcee (SSO) et des conditions de licence adaptees.

## Comparaison avec les alternatives

élèvenLabs est le concurrent le plus sérieux en termes de qualite pure de la voix, souvent privilegie pour la narration de fiction. Cependant, Murf AI conserve l'avantage sur l'ergonomie de son studio d'edition et ses intégrations tierces.

PlayHT se concentre sur une immense variete de voix et une intégration API facile. Speechify a bati sa reputation sur l'accessibilite et la lecture de documents longs. Lovo.ai est un acteur polyvalent avec des fonctionnalites similaires. En résumé, si élèvenLabs domine sur l'aspect organique de la voix, Murf AI reste le champion de la productivité intègree pour les entreprises.

## Notre avis

Murf AI s'impose comme une solution incontournable pour produire des voix-off de qualite professionnelle sans les contraintes logistiques d'un enregistrement studio. Sa force reside dans l'equilibre entre la complexite technologique et la simplicite de l'interface utilisateur.

Les intégrations avec Canva et Google Slides sont des atouts majeurs pour les professionnels de la communication et les formateurs. Cependant, le coût peut représenter un frein pour les petits createurs, les plans superieurs etant relativement onereux. Malgre cela, pour une entreprise cherchant a industrialiser sa production, le retour sur investissement est evident. Murf AI permet de reduire les delais de production de plusieurs jours a quelques heures.
MD;
    }

    private function playHt(): string
    {
        return <<<'MD'
Le secteur de la synthese vocale a connu une révolution sans precedent avec l'avenement des modèles de deep learning. Parmi les acteurs majeurs, Play.ht se distingue par sa capacite a transformer du texte en parole avec un realisme deconcertant. Cette plateforme s'adresse aux createurs de contenus individuels comme aux grandes entreprises souhaitant automatiser leur production audio.

## A propos de Play.ht

Play.ht est une plateforme de generation de voix par intelligence artificielle qui s'appuie sur les technologies les plus avancées de synthese vocale (Text-to-Speech). Ce qui distingue initialement Play.ht, c'est son intégration massive de divers moteurs de synthese, incluant ceux de Google, IBM, Microsoft et Amazon, tout en developpant ses propres modèles proprietaires comme "Peregrine" et "Parrot".

La plateforme repond a des besoins varies : de la narration de videos YouTube a la creation de podcasts, en passant par l'accessibilite des sites web. L'interface utilisateur est pensee pour la productivité, permettant de gerer des projets complexes sans necessiter de competences techniques en ingénierie sonore.

## Fonctionnalites principales

Le catalogue de voix est l'atout le plus impressionnant. Play.ht propose plus de 800 voix IA couvrant plus de 140 langues et accents. Cette diversite permet de cibler des audiences locales avec une précision chirurgicale.

Le "Voice Cloning" permet de creer une replique numerique d'une voix humaine a partir d'un court echantillon audio. Le "word-level editing" permet d'ajuster la prononciation, l'intonation et le rythme mot par mot.

Pour les développéurs, Play.ht propose une API a faible latence (low-latency API) permettant d'intègrer la synthese vocale dans des applications tiers, des jeux video ou des systèmes de réponse vocale interactive. Les options d'exportation incluent MP3 et WAV avec gestion des droits d'utilisation commerciale.

## Tarification

Le plan "Free" permet de tester les fonctionnalites de base avec un usage non commercial. Le plan "Creator" a environ 31 dollars par mois débloque les droits commerciaux et un quota de mots plus genereux. Le plan "Unlimited" a 99 dollars par mois offre une generation de voix illimitee avec un acces prioritaire. Le plan "Enterprise" débute a 500 dollars par mois pour des fonctionnalites sur mesure, du clonage haute fidelite et des accords de niveau de service (SLA).

## Comparaison avec les alternatives

élèvenLabs est le concurrent le plus sérieux pour la qualite pure et l'expressivite emotionnelle. Play.ht conserve un avantage sur la diversite des voix et la gestion des langues moins repandues.

Murf AI se positionne comme un studio de creation complet, avec un editeur video intègre. Play.ht reste superieur pour ceux qui recherchent une API robuste et une flexibilite de manipulation du texte. MicMonster est une alternative economique mais n'atteint pas le niveau de sophistication de Play.ht pour le clonage de voix.

## Notre avis

Play.ht s'impose comme une solution incontournable pour la production de contenu audio par IA. Sa force reside dans son equilibre entre accessibilite et puissance technologique. Nous apprecions particulierement la possibilité de mixer differentes voix au sein d'un même projet, simulant des conversations convaincantes.

Le controle granulaire sur la prononciation est un point fort qui evite les frustrations liees aux erreurs de lecture de l'IA. Cependant, la tarification peut sembler élèvee pour les utilisateurs occasionnels. En conclusion, Play.ht est un outil robuste et polyvalent qui continue d'innover dans l'ecosystème de l'intelligence artificielle générative.
MD;
    }

    private function descript(): string
    {
        return <<<'MD'
Le paysage de la creation de contenu numerique a connu une transformation radicale avec l'emergence de l'intelligence artificielle générative. Au coeur de cette révolution se trouve Descript, une plateforme qui a redefini les codes du montage audio et video en traitant les fichiers medias non pas comme des ondes sonores ou des sequences d'images, mais comme du texte simple.

## A propos de Descript

L'idee fondamentale de Descript est la suivante : si vous savez modifier un document Word, vous savez monter une video. Cette approche, appelee edition basee sur le texte, permet de manipuler des enregistrements en agissant directement sur la transcription generee automatiquement. En supprimant une phrase du texte, la sequence correspondante disparait de la timeline.

Descript ne se contente pas d'être un simple editeur. Il se positionne comme un ecosystème complet integrant l'enregistrement d'ecran, la transcription, l'edition multipiste et des outils de collaboration en temps reel. L'objectif est de reduire les frictions entre l'idee initiale et le produit fini.

## Fonctionnalites principales

La puissance de Descript repose sur "Underlord", un assistant IA co-editeur capable d'executer des commandes complexes en quelques secondes. La suppression des mots de remplissage (filler words) identifie et elimine tous les "euh" et hesitations en un clic.

Le Studio Sound utilise des reseaux de neurones pour regenerer la voix, eliminant bruits de fond, echo et distorsions. La fonction "Eye Contact" repositionne le regard pour qu'il semble regarder la camera. Les exports sont disponibles en résolution 4K.

Le clonage vocal Overdub permet de creer une replique numerique de sa propre voix pour corriger des erreurs sans reenregistrement. Le service de doublage supporte plus de 30 langues pour une audience globale.

## Tarification

Le plan Free est ideal pour tester l'interface avec un filigrane sur les exports. Le plan Hobbyist a 24 dollars par mois augmenté les limites de transcription avec exports sans filigrane en haute definition.

Le plan Creator a 35 dollars par mois est le plus populaire, offrant jusqu'a 30 heures de transcription, le Studio Sound illimite et le clonage vocal Overdub. Le plan Business a 65 dollars par mois est destiné aux equipes avec 45 heures de transcription, gestion d'equipe et support prioritaire.

## Comparaison avec les alternatives

Adobe Première Pro reste la reference pour le montage cinematographique mais avec une courbe d'apprentissage abrupte. La ou un utilisateur de Descript termine un montage en une heure, un debutant sur Première pourrait passer la journee.

Riverside excelle dans la capture haute qualite pour interviews a distance mais offre des outils de montage moins polyvalents. CapCut gagne du terrain pour les formats courts mais manque de profondeur pour les projets longs et les besoins professionnels.

## Notre avis

Descript représente le futur de la creation de contenu pour la majorite des utilisateurs. L'approche basee sur le texte n'est pas un gadget, c'est un gain de productivité reel. L'IA Underlord montre une vision claire : l'humain se concentre sur le message tandis que la machine gere les ajustements techniques.

La fonction Studio Sound est a elle seule un argument de vente majeur pour quiconque ne possede pas un environnement acoustique parfait. Cependant, la dependance a une connexion internet peut ralentir le flux de travail. En conclusion, Descript est probablement l'outil le plus performant du marche pour les podcasts, videos de formation et contenu pour les reseaux sociaux.
MD;
    }

    private function aiva(): string
    {
        return <<<'MD'
L'industrie de la creation musicale traverse une periode de transformation radicale avec l'avenement de l'intelligence artificielle générative. Parmi les pionniers de ce secteur, AIVA (Artificial Intelligence Virtual Artist) se distingue comme un outil de reference pour les compositeurs, les createurs de contenu et les développéurs de jeux video.

## A propos de AIVA

AIVA est nee d'une vision ambitieuse : creer un algorithme capable de composer des partitions originales en s'appuyant sur l'heritage des plus grands compositeurs de l'histoire. Reconnue officiellement par la SACEM comme compositeur a part entiere, cette IA analyse des milliers de partitions pour en extraire des regles mathématiques liees a l'harmonie, a la melodie et au rythme.

Le développement repose sur des reseaux de neurones recursifs qui permettent de generer des compositions structurees. L'outil s'adresse principalement aux secteurs de l'audiovisuel, notamment le cinema, la publicite et les jeux video. Au-dela du simple fichier MP3, l'outil genere des données MIDI, permettant une intégration parfaite dans les logiciels de MAO comme Ableton Live, Logic Pro ou FL Studio.

## Fonctionnalites principales

AIVA propose plus de 250 styles musicaux differents, allant de l'orchestration symphonique au jazz, en passant par l'electronica ou la musique de chambre. La creation de modèles de style personnalises permet d'importer ses propres fichiers MIDI pour entrainer l'IA sur une esthetique specifique.

L'editeur MIDI intègre permet de modifier chaque note, chaque accord et chaque nuance directement dans l'interface. Le système de stems permet de telecharger separement les couches de la composition (basse, batterie, cordes, piano), crucial pour le mixage audio professionnel.

## Tarification

Le plan Free (0 EUR par mois) est ideal pour decouvrir l'outil avec 3 telechargements par mois. Les droits d'auteur appartiennent a AIVA et l'usage est strictement personnel.

Le plan Standard (11 EUR par mois) autorise 15 telechargements avec licence commerciale pour les reseaux sociaux (YouTube, Twitch, Instagram). AIVA conserve les droits d'auteur.

Le plan Pro (33 EUR par mois) offre 300 telechargements et le transfert total du copyright a l'utilisateur. C'est la solution ultime pour les professionnels du cinema et du jeu video.

## Comparaison avec les alternatives

Suno est populaire pour generer des chansons completes avec voix et paroles, mais orienté divertissement. AIVA reste superieure pour les partitions instrumentales complexes et le controle via MIDI.

Soundraw se positionne sur la simplicite pour les createurs de videos mais offre moins de profondeur harmonique. Mubert se specialise dans la musique d'ambiance en temps reel. Boomy mise sur la rapidité extreme et la publication sur Spotify mais produit des resultats plus generiques. Pour la qualite d'orchestration symphonique, AIVA demeure le choix de reference.

## Notre avis

AIVA s'impose comme l'un des outils les plus aboutis pour la creation musicale assistee par ordinateur. Sa force reside dans l'equilibre entre l'automatisation par l'IA et le controle manuel. Ce n'est pas un outil qui remplace le compositeur, mais un assistant qui amplifie sa productivité et sa creativite.

La possibilité de recuperer des fichiers MIDI est l'argument majeur qui place AIVA au-dessus de la melee. Le plan Pro avec transfert total du copyright est largement justifie pour un studio independant ou un monteur freelance. En conclusion, si vous recherchez des compositions orchestrales riches avec une flexibilite d'edition totale, AIVA est la plateforme la plus robuste du marche actuel.
MD;
    }

    private function podcastle(): string
    {
        return <<<'MD'
L'industrie du podcasting a connu une transformation radicale, passee d'un simple enregistrement audio amateur a des productions multimedia complexes. Dans ce paysage en mutation, Podcastle se positionne comme un studio de creation tout-en-un qui promet de supprimer les barrieres techniques pour les createurs de contenu.

## A propos de Podcastle

Podcastle est une plateforme de creation audio et video intègree, concue specifiquement pour les podcasteurs, les createurs de contenu numerique et les entreprises. Contrairement aux logiciels de montage traditionnels, Podcastle mise sur une approche intuitive basee sur le navigateur web, centralisant chaque étape de la production, de l'enregistrement initial a la distribution finale.

La philosophie repose sur l'automatisation des taches ingrates. La plateforme utilise des algorithmes d'apprentissage profond pour traiter le son, generer des transcriptions et même cloner des voix humaines. Elle permet aux utilisateurs de se concentrer exclusivement sur la narration et la qualite de leur message.

## Fonctionnalites principales

Le coeur de Podcastle reside dans sa capacite d'enregistrement a distance accueillant jusqu'a 10 participants simultanément dans un studio virtuel. L'enregistrement local garantit une qualite studio constante, même avec une connexion internet instable. Les exports video supportent jusqu'au 4K.

Le "Magic Dust" est l'outil phare : en un clic, il supprime les bruits de fond, isole la voix et egalise les niveaux sonores. Il identifie et supprime automatiquement les mots de remplissage. La bibliotheque de plus de 450 voix TTS et le voice cloning rapide completent l'offre.

L'edition se fait via une interface double : timeline classique et editeur base sur le texte. Plus de 7000 musiques et effets sonores libres de droits sont disponibles. Le hub d'hebergement permet de publier directement sur les plateformes de diffusion.

## Tarification

Podcastle adopte un modèle freemium. Le plan gratuit offre un acces illimite a l'enregistrement et au montage avec une qualite audio élèvee. Les plans payants (Storyteller et Pro) débloquent l'acces complet au Magic Dust, au clonage de voix, a la bibliotheque musicale etendue et aux exports 4K. Des solutions sur mesure sont disponibles pour les entreprises.

## Comparaison avec les alternatives

Descript est le pionnier de l'edition basee sur le texte, mais Podcastle offre une interface web plus legere et une approche plus intègree pour l'enregistrement a distance. Riverside est la reference pour l'enregistrement video haute qualite mais offre des options de post-production IA moins développées.

élèvenLabs est le leader pour le realisme des voix synthetiques, mais c'est un outil specialise et non un studio de podcasting complet. Anchor (Spotify for Podcasters) se concentre sur l'hebergement et la distribution simplifiée, tandis que Podcastle offre des outils de creation bien plus robustes.

## Notre avis

Podcastle représente l'évolution logique de la production de contenu : une plateforme ou la technique s'efface devant la creativite. Le Magic Dust est réellement impressionnant pour sauver des enregistrements mediocres, une valeur ajoutee inestimable pour les createurs ne pouvant pas controler l'environnement acoustique de leurs invites.

L'aspect "tout-en-un" est a la fois sa plus grande force et son seul point de vigilance. Pour la majorite des podcasteurs independants et des services marketing, l'efficacite gagnee est majeure. En conclusion, Podcastle est une solution solide pour produire du contenu audio et video de qualite professionnelle sans investir dans du materiel ou des logiciels complexes.
MD;
    }
}
