<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ENRICHISSEMENT de la fiche « GPU » (2026-09-13). Le fondateur a demande « /glossaire GPU » :
 * la fiche EXISTAIT DEJA, donc aucune fiche nouvelle n a ete creee - un doublon aurait divise le
 * referencement entre deux pages et casse l auto-lien, qui doit pouvoir choisir UNE cible.
 *
 * ANTI-DOUBLON, par famille de motifs contre la base de PRODUCTION
 * (gpu|carte graphique|processeur graphique|accelerateur|nvidia|cuda|tpu|npu|puce|silicium) :
 * 9 fiches de la famille existent (apple-silicon, calcul-neuromorphique, cuda, gpu, npu, nvidia,
 * token, tokens, tpu). La fiche « gpu » porte deja les alias « Graphics Processing Unit »,
 * « GPUs », « carte graphique » et « processeur graphique ». Rien a creer.
 *
 * POURQUOI ENRICHIR PLUTOT QUE LAISSER TEL QUEL - mesure comparative, pas une impression :
 *   definition de GPU ................ 418 caracteres
 *   moyenne des 530 fiches publiees .. 723 caracteres
 *   position ......................... 15e centile (78 fiches plus courtes sur 530)
 *   voisines directes ................ tpu 411, npu 621, nvidia 880, cuda 1192
 * GPU et TPU datent tous deux du 23 mars 2026, avant la stabilisation du standard editorial ;
 * nvidia, npu et cuda ont ete refaits en mai-juin et sont 1,5 a 3 fois plus longs. Et GPU est la
 * PLUS CONSULTEE des cinq (536 vues) : la fiche la plus lue du groupe etait la plus courte.
 *
 * CE QUI N EST PAS TOUCHE, parce que c est bon : l analogie (le CPU comme un professeur brillant,
 * le GPU comme une classe de 5 000 eleves), l exemple ancre au Quebec (centres de donnees de
 * Montreal et hydroelectricite), les 3 paires de FAQ, l icone, la difficulte, les alias et la
 * strategie de correspondance. On enrichit ce qui manque, on ne refait pas ce qui tient.
 *
 * TROIS CHAMPS CORRIGES :
 *   1. definition : 418 -> 1125 caracteres (163 mots, cible du standard 150-165). Elle explique
 *      desormais le MECANISME - parallelisme massif, unites dediees au calcul matriciel en basse
 *      precision, memoire HBM, interconnexions entre puces, ecosysteme logiciel - la ou l ancienne
 *      se contentait d affirmer que le GPU est adapte a l IA sans dire pourquoi.
 *      AUCUN chiffre d affaires, AUCUNE valeur boursiere, AUCUNE part de marche : ces chiffres
 *      perimeraient la fiche en quelques mois, et ceux qu un moteur de recherche a proposes
 *      arrivaient SANS AUCUNE SOURCE dans sa reponse - donc invérifiables.
 *   2. did_you_know : l ancien annoncait « la valeur boursiere de NVIDIA a depasse les 3 000
 *      milliards en 2024 », un fait date de deux ans. Remplace par un fait DURABLE et contre-
 *      intuitif : dans un tres grand entrainement, ce qui limite la performance n est souvent pas
 *      la puissance des GPU mais la vitesse de circulation des donnees entre les puces.
 *   3. sources : les deux anciennes sont remplacees.
 *      - « NVIDIA - GPU Computing » repondait 200 mais servait « About Us: Company Leadership,
 *        History, Jobs, News » : l adresse a ete redirigee vers une page generique. Un code 200
 *        ne prouve pas qu une source sert encore le document annonce - il faut lire le titre.
 *      - Wikipedia repond correctement mais n est pas une source primaire.
 *      Les deux nouvelles ont ete verifiees par requete reelle avec controle du titre servi :
 *        developer.nvidia.com/cuda-zone -> 200, « CUDA Platform for Accelerated Computing »
 *        docs.cloud.google.com/tpu/docs/intro-to-tpu -> 200, « Introduction to Cloud TPU »
 *      La seconde documente le contraste utile a la fiche : Google y decrit ses TPU comme des
 *      circuits integres specifiques a une application (ASIC), donc plus specialises qu un GPU.
 *
 * AUTO-LIEN : aucun changement de risque. Le terme etait deja lie (match_strategy « loose »), et
 * 156 actualites publiees citent « GPU », 5 citent « carte graphique ». Les alias ne bougent pas,
 * donc aucun lien nouveau n apparait ailleurs sur le site du fait de cette migration.
 *
 * REVERSIBLE : down() RESTAURE les anciennes valeurs, il ne supprime pas la fiche - une fiche
 * publiee depuis mars 2026 et vue 536 fois ne doit jamais disparaitre par un rollback.
 */
return new class extends Migration
{
    private const SLUG = 'gpu';

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            echo "[glossaire] terme gpu absent, enrichissement ignore\n";

            return;
        }

        $terme->setTranslations('definition', [
            'fr_CA' => 'Un GPU (Graphics Processing Unit, ou processeur graphique) est un circuit électronique initialement conçu pour le rendu d\'images, devenu indispensable à l\'intelligence artificielle. Son architecture permet d\'exécuter simultanément des milliers d\'opérations numériques simples, contrairement au CPU qui traite séquentiellement des tâches complexes avec peu de coeurs puissants. Ce parallélisme massif convient à l\'entraînement des réseaux de neurones, qui repose sur des multiplications matricielles répétées sans fin. Les GPU récents intègrent des unités dédiées au calcul matriciel en basse précision et une mémoire HBM à très grande bande passante. Pour les modèles de grande taille, les interconnexions rapides entre puces sont aussi déterminantes que la puissance brute : sans elles, les unités attendent les données au lieu de calculer. L\'écosystème logiciel compte autant que le matériel, la plateforme CUDA de NVIDIA étant intégrée à la plupart des cadriciels d\'apprentissage profond. Le GPU reste enfin polyvalent : le même matériel sert au préentraînement, au réglage fin, à la vision et à une partie de l\'inférence.',
            'fr' => 'Un GPU (Graphics Processing Unit, ou processeur graphique) est un circuit électronique initialement conçu pour le rendu d\'images, devenu indispensable à l\'intelligence artificielle. Son architecture permet d\'exécuter simultanément des milliers d\'opérations numériques simples, contrairement au CPU qui traite séquentiellement des tâches complexes avec peu de coeurs puissants. Ce parallélisme massif convient à l\'entraînement des réseaux de neurones, qui repose sur des multiplications matricielles répétées sans fin. Les GPU récents intègrent des unités dédiées au calcul matriciel en basse précision et une mémoire HBM à très grande bande passante. Pour les modèles de grande taille, les interconnexions rapides entre puces sont aussi déterminantes que la puissance brute : sans elles, les unités attendent les données au lieu de calculer. L\'écosystème logiciel compte autant que le matériel, la plateforme CUDA de NVIDIA étant intégrée à la plupart des cadriciels d\'apprentissage profond. Le GPU reste enfin polyvalent : le même matériel sert au préentraînement, au réglage fin, à la vision et à une partie de l\'inférence.',
        ]);
        $terme->setTranslations('did_you_know', [
            'fr_CA' => 'Dans l\'entraînement des très grands modèles, ce qui limite la performance n\'est souvent pas la puissance des GPU, mais la vitesse de circulation des données entre les puces.',
            'fr' => 'Dans l\'entraînement des très grands modèles, ce qui limite la performance n\'est souvent pas la puissance des GPU, mais la vitesse de circulation des données entre les puces.',
        ]);
        $terme->sources = [
            [
                'label' => 'NVIDIA, « CUDA Platform for Accelerated Computing »',
                'url' => 'https://developer.nvidia.com/cuda-zone',
                'year' => 2026,
                'author' => 'NVIDIA Developer',
            ],
            [
                'label' => 'Google Cloud, « Introduction to Cloud TPU » (contraste entre un GPU polyvalent et un ASIC spécialisé)',
                'url' => 'https://docs.cloud.google.com/tpu/docs/intro-to-tpu',
                'year' => 2026,
                'author' => 'Google Cloud Documentation',
            ],
        ];
        $terme->content_updated_at = now();
        $terme->save();

        echo "[glossaire] fiche gpu enrichie\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            return;
        }

        // Restauration des valeurs d origine, jamais une suppression de la fiche.
        $terme->setTranslations('definition', [
            'fr_CA' => 'Un GPU (unité de traitement graphique) est un processeur spécialisé conçu à l\'origine pour le rendu graphique. Sa capacité à effectuer des milliers de calculs simples simultanément le rend particulièrement adapté à l\'entraînement des modèles d\'IA. Les GPU sont devenus le matériel de référence pour l\'apprentissage profond et une ressource stratégique dans la course mondiale à l\'IA. NVIDIA domine largement ce marché.',
            'fr' => 'Un GPU (unité de traitement graphique) est un processeur spécialisé conçu à l\'origine pour le rendu graphique. Sa capacité à effectuer des milliers de calculs simples simultanément le rend particulièrement adapté à l\'entraînement des modèles d\'IA. Les GPU sont devenus le matériel de référence pour l\'apprentissage profond et une ressource stratégique dans la course mondiale à l\'IA. NVIDIA domine largement ce marché.',
        ]);
        $terme->setTranslations('did_you_know', [
            'fr_CA' => 'La valeur boursière de NVIDIA a dépassé les 3 000 milliards de dollars en 2024, directement grâce à la demande en IA. Le Québec possède un avantage concurrentiel majeur : son électricité parmi les moins chères et les plus propres en Amérique du Nord réduit le coût et l\'empreinte carbone du fonctionnement des centres de GPU.',
            'fr' => 'La valeur boursière de NVIDIA a dépassé les 3 000 milliards de dollars en 2024, directement grâce à la demande en IA. Le Québec possède un avantage concurrentiel majeur : son électricité parmi les moins chères et les plus propres en Amérique du Nord réduit le coût et l\'empreinte carbone du fonctionnement des centres de GPU.',
        ]);
        $terme->sources = [
            [
                'label' => 'NVIDIA - GPU Computing',
                'url' => 'https://www.nvidia.com/en-us/about-nvidia/ai-computing/',
                'year' => '2024',
                'author' => 'NVIDIA',
            ],
            [
                'label' => 'Wikipedia - Processeur graphique',
                'url' => 'https://fr.wikipedia.org/wiki/Processeur_graphique',
                'year' => '2024',
                'author' => 'Wikipedia',
            ],
        ];
        $terme->save();
    }
};
