<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\News\Services;

/**
 * Tri éditorial déterministe de l'écran de composition (ticket #2358, mesuré le 2026-09-08).
 * L'écran de composition (Modules\News\Http\Controllers\Admin\NewsCompositionController::
 * candidates()) liste tout le jour trié par pub_date décroissant, sans aucun ordre de
 * pertinence : 662 articles en attente, 581 sans aucun relevance_score (le résumé machine est
 * éteint depuis le 2026-08-17, voir config('news.machine_summary') - on ne le rallume pas) et
 * aucun texte source disponible (politique zéro-copie #1810). Le TITRE et le MÉDIA sont les
 * seuls champs observables, exactement ce que l'humain regarde à l'écran.
 *
 * Cette classe calcule un score À LA VOLÉE, jamais persisté (jamais dans relevance_score, qui
 * reste réservé à Modules\News\Console\PruneSeoCommand - le brancher ici réveillerait un
 * mécanisme de suppression que cette fonctionnalité n'a pas le droit de toucher). Le score
 * ORDONNE la liste, il ne SUPPRIME jamais une fiche : un titre sans aucun signal obtient 0 et
 * reste affiché. Mais avec environ 236 articles par jour et une attention humaine limitée, le
 * bas d'une longue liste triée reste une exclusion DE FAIT - la revue adversariale du
 * 2026-09-08 l'a nommé explicitement, ce n'est pas qu'un détail de présentation. Aucun appel
 * réseau, aucun modèle IA, aucune liste noire de sujets - décider qu'un sujet est hors
 * périmètre est un jugement éditorial, pas un calcul, et une liste noire produit des faux
 * négatifs invisibles.
 *
 * Le signal 'portee' (revue adversariale du 2026-09-08, biais n°1) existe pour cette raison
 * précise : un score qui ne primerait que le vocabulaire IA et les chiffres ferait remonter
 * les COMMUNIQUÉS D'ENTREPRISE (riches en marques et en statistiques par construction) et
 * enterrerait systématiquement les enquêtes et les conséquences institutionnelles sur des
 * personnes - un titre d'enquête ne nomme pas toujours l'IA. 'portee' donne à ces titres une
 * chance de remonter même sans entité ni terme technique.
 *
 * CORRECTIF STRUCTUREL (revue adversariale 2026-09-08, cause 3, mesuré sur 40 titres réels de
 * production - 35/40 valaient 0, et le seul titre à 2 était une promo) : le bonus « nombre
 * porteur » et le signal « marqueur commercial » se contredisaient - un chiffre de PROMOTION
 * (« -70 %, fin de l'offre ») gagnait le bonus destiné aux faits mesurables. score() calcule
 * désormais les marqueurs commerciaux EN PREMIER et n'applique le bonus nombre porteur que si
 * AUCUN n'a été trouvé dans le même titre - voir scoreRabaisNumeriques() pour les gabarits
 * numériques (pourcentage négatif, perte en devise) qui, eux, appartiennent au camp commercial.
 *
 * Les listes de termes vivent dans config('news.editorial_triage'), jamais en dur ici (règle
 * projet - zéro code en dur) : les enrichir ne demande aucun déploiement de code.
 */
final class EditorialTriageScorer
{
    /** Poids d'une entité d'IA nommée trouvée dans le titre, et plafond cumulé de ce signal. */
    private const POIDS_ENTITE = 4;

    private const PLAFOND_ENTITE = 8;

    /** Poids d'un terme d'IA substantiel, et plafond cumulé de ce signal. */
    private const POIDS_TERME_IA = 3;

    private const PLAFOND_TERME_IA = 6;

    /**
     * Poids d'un marqueur de portée (enquête/conséquence), et plafond cumulé - revue
     * adversariale du 2026-09-08, biais n°1 (voir le doc-bloc de la classe).
     */
    private const POIDS_PORTEE = 3;

    private const PLAFOND_PORTEE = 6;

    /** Poids du « nombre porteur » - appliqué une seule fois par titre, jamais cumulé. */
    private const POIDS_NOMBRE_PORTEUR = 2;

    /** Poids d'un marqueur commercial - cumulable, aucun plafond. */
    private const POIDS_MARQUEUR_COMMERCIAL = 5;

    /**
     * Calcule le score de tri d'un article à partir de son seul titre (original et traduit).
     * Ne lit ni n'écrit rien en base, n'effectue aucun appel réseau.
     *
     * @param  string  $titre  Titre original de l'article (news_articles.title).
     * @param  string|null  $titreFr  Titre traduit, s'il existe (news_articles.title_fr).
     * @param  string|null  $nomMedia  Nom du média source. Accepté pour la stabilité du contrat
     *                                 (ticket #2358) : aucun signal de la spec ne porte sur le
     *                                 média, la table des signaux ne balaie que les titres -
     *                                 ce paramètre n'intervient donc pas dans le calcul.
     * @return array{score: int, raisons: array<int, string>}
     */
    public function score(string $titre, ?string $titreFr, ?string $nomMedia): array
    {
        unset($nomMedia);

        $textes = array_values(array_filter(
            [$titre, $titreFr],
            static fn (?string $texte): bool => filled($texte)
        ));

        $score = 0;
        $raisons = [];

        [$scoreEntites, $raisonsEntites] = $this->scoreTermesPlafonnes(
            $textes,
            $this->listeConfig('entites_ia'),
            self::POIDS_ENTITE,
            self::PLAFOND_ENTITE,
            'entité'
        );
        $score += $scoreEntites;
        array_push($raisons, ...$raisonsEntites);

        [$scoreTermes, $raisonsTermes] = $this->scoreTermesPlafonnes(
            $textes,
            $this->listeConfig('termes_ia'),
            self::POIDS_TERME_IA,
            self::PLAFOND_TERME_IA,
            'terme IA'
        );
        $score += $scoreTermes;
        array_push($raisons, ...$raisonsTermes);

        [$scorePortee, $raisonsPortee] = $this->scoreTermesPlafonnes(
            $textes,
            $this->listeConfig('portee'),
            self::POIDS_PORTEE,
            self::PLAFOND_PORTEE,
            'portée'
        );
        $score += $scorePortee;
        array_push($raisons, ...$raisonsPortee);

        // ── Marqueurs commerciaux (liste + rabais numériques) - calculés AVANT le nombre
        // porteur, à dessein : voir le correctif structurel juste plus bas. ──
        [$scoreMarqueursListe, $raisonsMarqueursListe] = $this->scoreMarqueursCommerciaux(
            $textes,
            $this->listeConfig('marqueurs_commerciaux')
        );
        [$scoreRabais, $raisonsRabais] = $this->scoreRabaisNumeriques($textes);
        $raisonsMarqueurs = [...$raisonsMarqueursListe, ...$raisonsRabais];
        $score += $scoreMarqueursListe + $scoreRabais;
        array_push($raisons, ...$raisonsMarqueurs);

        // CORRECTIF STRUCTUREL (revue adversariale 2026-09-08, cause 3, mesuré sur 40 titres
        // réels de production) : le bonus « nombre porteur » récompensait le chiffre d'une
        // PROMOTION - « -70 %, fin de l'offre » gagnait +2 avant ce correctif. Un nombre dans un
        // titre commercial n'est pas un fait mesurable, c'est un argument de vente. Le bonus ne
        // s'applique donc PLUS dès qu'au moins un marqueur commercial (liste OU rabais
        // numérique) est détecté dans le même titre - jamais les deux signaux à la fois.
        if ($raisonsMarqueurs === []) {
            $nombrePorteur = $this->premierNombrePorteur($textes, $this->listeConfig('unites_nombre_porteur'));
            if ($nombrePorteur !== null) {
                $score += self::POIDS_NOMBRE_PORTEUR;
                $raisons[] = sprintf('+%d nombre porteur : %s', self::POIDS_NOMBRE_PORTEUR, $nombrePorteur);
            }
        }

        return ['score' => $score, 'raisons' => $raisons];
    }

    /**
     * Lit une liste de termes de config('news.editorial_triage.<clef>'), jamais en dur ici.
     * Une clé absente ou mal typée rend une liste vide plutôt qu'une erreur - un article sans
     * config lisible obtient simplement 0, il n'est jamais exclu (contrainte non négociable 3).
     *
     * @return array<int, string>
     */
    private function listeConfig(string $clef): array
    {
        $liste = config('news.editorial_triage.'.$clef, []);

        if (! is_array($liste)) {
            return [];
        }

        return array_values(array_filter($liste, static fn ($terme): bool => is_string($terme) && $terme !== ''));
    }

    /**
     * Score un signal « liste de termes plafonnée » (entités d'IA, termes d'IA) : chaque terme
     * de la liste, dans l'ordre de config, ajoute son poids s'il est trouvé dans au moins un des
     * textes - jamais deux fois pour le même terme même s'il apparaît dans le titre ET sa
     * traduction (les noms propres d'entreprises ne se traduisent pas). L'arrêt se fait DÈS que
     * le prochain terme ferait dépasser le plafond, jamais après coup : la somme des raisons
     * rendues égale toujours exactement le score rendu.
     *
     * @param  array<int, string>  $textes
     * @param  array<int, string>  $liste
     * @return array{0: int, 1: array<int, string>}
     */
    private function scoreTermesPlafonnes(array $textes, array $liste, int $poids, int $plafond, string $etiquette): array
    {
        $score = 0;
        $raisons = [];

        foreach ($liste as $terme) {
            if ($score + $poids > $plafond) {
                break;
            }

            if ($this->contientTerme($textes, $terme)) {
                $score += $poids;
                $raisons[] = sprintf('+%d %s : %s', $poids, $etiquette, $terme);
            }
        }

        return [$score, $raisons];
    }

    /**
     * Score le signal « marqueur commercial » : cumulable, aucun plafond - chaque marqueur
     * distinct trouvé retranche son poids, un titre qui en porte trois en perd trois fois
     * autant.
     *
     * @param  array<int, string>  $textes
     * @param  array<int, string>  $liste
     * @return array{0: int, 1: array<int, string>}
     */
    private function scoreMarqueursCommerciaux(array $textes, array $liste): array
    {
        $score = 0;
        $raisons = [];

        foreach ($liste as $terme) {
            if ($this->contientTerme($textes, $terme)) {
                $score -= self::POIDS_MARQUEUR_COMMERCIAL;
                $raisons[] = sprintf('-%d marqueur commercial : « %s »', self::POIDS_MARQUEUR_COMMERCIAL, $terme);
            }
        }

        return [$score, $raisons];
    }

    /**
     * Rabais numériques (revue adversariale 2026-09-08, cause 3, mesuré sur 40 titres réels de
     * production) : un pourcentage NÉGATIF (« -70 % ») ou une perte chiffrée en devise
     * (« perd 200 € ») sont des arguments de vente, pas des faits mesurables - contrairement au
     * « nombre porteur » (+2), ils rejoignent le camp des marqueurs commerciaux (-5, cumulable).
     * Deux gabarits, chacun compté au plus une fois par titre (comme les autres marqueurs) :
     *   1. Un signe moins collé à un nombre suivi de « % », HORS intervalle (« 20-30 % » ne mord
     *      pas depuis le resserrement du 2026-09-08 - voir le corps de la méthode).
     *   2. Un mot de perte (config('news.editorial_triage.marqueurs_commerciaux_rabais_mots'))
     *      suivi d'un nombre et d'une devise (config('...marqueurs_commerciaux_devises')).
     * Le vocabulaire vit en config, la structure numérique reste ici - même doctrine que
     * premierNombrePorteur().
     *
     * @param  array<int, string>  $textes
     * @return array{0: int, 1: array<int, string>}
     */
    private function scoreRabaisNumeriques(array $textes): array
    {
        $score = 0;
        $raisons = [];

        // Gabarit 1 - pourcentage NÉGATIF, resserré le 2026-09-08 après la revue Codex.
        // Codex signalait deux familles de faux positifs : la baisse chiffrée (« les émissions
        // reculent de -30 % ») et l'INTERVALLE (« les gains atteignent 20-30 % », dont le tiret
        // n'est pas un signe moins). J'ai d'abord retiré le gabarit en entier, et la remesure a
        // démenti cette décision : la promo « le Xiaomi Mi Mix Flip est à -70% » ne tombait pas
        // à 0 mais REMONTAIT à +2, parce que score() n'accorde le bonus « nombre porteur » qu'en
        // l'absence de marqueur commercial - retirer le marqueur libérait le bonus, et la
        // promotion passait AU-DESSUS des titres neutres. Exactement l'inverse du but.
        // Ce que la mesure dit vraiment, sur les 80 titres réels des deux échantillons de
        // production : le motif mord UNE fois, sur cette promo, et c'est un VRAI positif - ZÉRO
        // faux positif réel. Les deux familles de faux positifs de Codex étaient des titres que
        // j'avais FABRIQUÉS pour la sonde, jamais observés dans le flux.
        // Correctif retenu : garder le gabarit, et fermer la seule famille dont le faux positif
        // est STRUCTUREL - l'intervalle « 20-30 % », par le lookbehind (?<!\d) : dans un
        // intervalle, le tiret est COLLÉ au chiffre qui le précède.
        // DEUXIÈME REVUE CODEX (2026-09-08) : j'avais d'abord ajouté un second lookbehind
        // (?<!\d\s), et il créait un FAUX NÉGATIF sur la forme la plus courante des promos tech -
        // un nom de produit qui finit par un chiffre. Mesuré : « Samsung Galaxy S24 -30 % » et
        // « iPhone 15 -25% » n'étaient PAS pénalisés. Le second lookbehind est retiré.
        // CE QUE LA MESURE NE TRANCHE PAS, et il faut le dire : sur les 80 titres réels des deux
        // échantillons, les deux formes du motif mordent EXACTEMENT le même titre unique, et la
        // forme « chiffre espace tiret pourcentage » n'y apparaît pas une seule fois. Le choix
        // repose donc sur la fréquence ATTENDUE dans le domaine : une promo de produit numéroté
        // est le pain quotidien de 01net et Frandroid, alors qu'un intervalle écrit « 10 -15 % »
        // avec une espace avant le tiret est une faute de typographie française.
        // LIMITES ÉCRITES, à rouvrir sur une mesure et jamais sur un titre inventé : la baisse
        // chiffrée notée avec un signe moins (« amputé de -12 % ») reste pénalisée à tort, et
        // l'intervalle mal typographié « 10 -15 % » l'est désormais aussi.
        $motifPourcentageNegatif = '/(?<!\d)-\s?\d+(?:[.,]\d+)?\s?%/u';
        foreach ($textes as $texte) {
            if (preg_match($motifPourcentageNegatif, $texte, $correspondance) === 1) {
                $score -= self::POIDS_MARQUEUR_COMMERCIAL;
                $raisons[] = sprintf(
                    '-%d marqueur commercial : « %s »',
                    self::POIDS_MARQUEUR_COMMERCIAL,
                    trim($correspondance[0])
                );
                break;
            }
        }

        // Gabarit 2 - mot de perte + nombre + devise (« perd 220 € »). Beaucoup plus spécifique,
        // zéro faux positif sur les mêmes 80 titres : conservé tel quel.

        $motsRabais = $this->listeConfig('marqueurs_commerciaux_rabais_mots');
        $devises = $this->listeConfig('marqueurs_commerciaux_devises');
        if ($motsRabais !== [] && $devises !== []) {
            $alternanceMots = implode('|', array_map(
                static fn (string $mot): string => preg_quote($mot, '/'),
                $motsRabais
            ));
            $alternanceDevises = implode('|', array_map(
                static fn (string $devise): string => preg_quote($devise, '/'),
                $devises
            ));
            $motifPerte = '/(?<![\p{L}\p{N}])(?:'.$alternanceMots.')\s+\d+(?:[.,]\d+)?\s?(?:'.$alternanceDevises.')/iu';

            foreach ($textes as $texte) {
                if (preg_match($motifPerte, $texte, $correspondance) === 1) {
                    $score -= self::POIDS_MARQUEUR_COMMERCIAL;
                    $raisons[] = sprintf(
                        '-%d marqueur commercial : « %s »',
                        self::POIDS_MARQUEUR_COMMERCIAL,
                        trim($correspondance[0])
                    );
                    break;
                }
            }
        }

        return [$score, $raisons];
    }

    /**
     * Premier « nombre porteur » trouvé : un nombre suivi (espace optionnel) d'une des unités
     * de config('news.editorial_triage.unites_nombre_porteur') - pourcentage, montant ou
     * multiplicateur (« 3 milliards », « 95 % », « 12,93 G$ »). Une seule occurrence compte,
     * jamais cumulée (contrairement au marqueur commercial) : la spec ne prévoit pas de
     * plafond pour ce signal parce qu'il n'est déjà appliqué qu'une fois.
     *
     * @param  array<int, string>  $textes
     * @param  array<int, string>  $unites
     */
    private function premierNombrePorteur(array $textes, array $unites): ?string
    {
        if ($unites === []) {
            return null;
        }

        // Les unités les plus longues d'abord : une frontière de mot protège déjà "milliard"
        // de matcher à tort dans "milliards" (le 's' suivant casse le lookahead), mais trier
        // ainsi documente l'intention et coûte rien.
        usort($unites, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $alternance = implode('|', array_map(
            static fn (string $unite): string => preg_quote($unite, '/'),
            $unites
        ));

        // Frontières Unicode (lettre ou chiffre) des deux côtés du nombre ET du groupe d'unité -
        // voir docs/CONTRAINTES-SOUS-AGENTS.md, section sur la correspondance en sous-chaîne :
        // chercher un motif sans borner ses frontières le trouve à l'intérieur d'un mot plus long.
        $motif = '/(?<![\p{L}\p{N}])(\d+(?:[.,]\d+)?)\s?(?:'.$alternance.')(?![\p{L}\p{N}])/iu';

        foreach ($textes as $texte) {
            if (preg_match($motif, $texte, $correspondance) === 1) {
                return trim($correspondance[0]);
            }
        }

        return null;
    }

    /**
     * Vrai si $terme apparaît, en frontière de mot (Unicode), dans au moins un des textes
     * fournis. Une correspondance en sous-chaîne non bornée est le défaut mesuré quatre fois sur
     * ce projet (docs/CONTRAINTES-SOUS-AGENTS.md) - toujours borner.
     *
     * SENSIBILITÉ À LA CASSE (mesurée le 2026-09-08 sur l'échantillon de contrôle, 40 titres de
     * production que le rédacteur du service n'avait jamais vus) : la frontière de mot ne suffit
     * PAS pour un sigle de deux ou trois lettres. « J'ai factorisé les clés RSA d'une autorité de
     * certification des années 90 » gagnait +4 « entité : AI », parce que l'apostrophe EST une
     * frontière de mot et que la comparaison était insensible à la casse - le verbe français le
     * plus courant qui soit devenait une entité d'intelligence artificielle. Le commentaire de
     * config prévoyait « médIA » et « mAIson », c'est-à-dire l'intérieur d'un mot ; il n'avait pas
     * prévu le mot entier.
     *
     * La règle est GÉNÉRALE, pas un cas particulier ajouté pour ce titre-là : un terme écrit
     * entièrement en majuscules dans la config est un SIGLE, et un sigle ne se reconnaît qu'en
     * majuscules (« AI », « IA », « GPT », « LLM »). Tout terme qui porte au moins une minuscule
     * est un nom ou une expression ordinaire, et reste comparé sans égard à la casse
     * (« OpenAI », « Hugging Face », « intelligence artificielle »). Aucune liste nouvelle à
     * tenir : la règle vaut d'office pour tout sigle ajouté plus tard.
     *
     * Coût assumé et mesuré : un titre qui écrirait « ai » ou « llm » en minuscules ne serait plus
     * détecté. Sur les 80 titres réels des deux échantillons, ce cas n'apparaît pas une seule fois
     * - les titres de presse capitalisent leurs sigles.
     *
     * @param  array<int, string>  $textes
     */
    private function contientTerme(array $textes, string $terme): bool
    {
        $motif = '/(?<![\p{L}\p{N}])'.preg_quote($terme, '/').'(?![\p{L}\p{N}])/u'
            .($this->estUnSigle($terme) ? '' : 'i');

        foreach ($textes as $texte) {
            if (preg_match($motif, $texte) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Un terme est traité comme un SIGLE - donc comparé en respectant la casse - s'il porte au
     * moins une lettre et AUCUNE minuscule. Voir contientTerme() pour la mesure qui l'a motivé.
     */
    private function estUnSigle(string $terme): bool
    {
        return preg_match('/\p{L}/u', $terme) === 1
            && preg_match('/\p{Ll}/u', $terme) !== 1;
    }
}
