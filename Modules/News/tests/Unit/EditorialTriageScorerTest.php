<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests unitaires de EditorialTriageScorer (ticket #2358 - tri éditorial déterministe de
 * l'écran de composition). Convention du projet : uses(Tests\TestCase::class) sans
 * RefreshDatabase (service purement statique, aucun modèle/BD requis) - même patron que
 * Modules\News\tests\Unit\SummaryQualityGateTest.php / CompositionPayloadNormalizerTest.php.
 *
 * Un cas par signal, PLUS le cas de conservation exigé par la spec (un titre sans aucun signal
 * obtient 0, jamais une exclusion - l'exclusion vit au niveau du contrôleur, qui affiche TOUJOURS
 * tous les articles, quel que soit leur score) et deux cas de frontière de mot (le défaut mesuré
 * quatre fois sur ce projet, docs/CONTRAINTES-SOUS-AGENTS.md) : « loi » ne doit ni manquer en mot
 * isolé, ni être compté à tort à l'intérieur d'« emploi ».
 *
 * AJOUT (revue adversariale 2026-09-08, mesure sur 40 titres réels DIFFÉRENTS de ceux qui ont
 * servi à écrire ce fichier - je n'ai jamais vu cet échantillon) : 3 causes corrigées, chacune
 * avec son test dédié - (1) « IA »/« AI »/« intelligence artificielle » et les grands acteurs
 * seuls rejoignent entites_ia, avec la preuve de frontière de mot exigée explicitement (« les
 * médias sociaux » ne doit rien marquer, « l'IA générative » doit marquer une entité) ; (2)
 * portee gagne les mots d'institution manquants (lycée, université...) ; (3) correctif
 * STRUCTUREL - un marqueur commercial supprime désormais le bonus « nombre porteur » dans le
 * MÊME titre (un chiffre de promotion n'est pas un fait mesurable).
 */

use Modules\News\Services\EditorialTriageScorer;

uses(Tests\TestCase::class);

function editorialScorer(): EditorialTriageScorer
{
    return new EditorialTriageScorer();
}

// ── Entité d'IA nommée (+4 chacune, plafond +8) ─────────────────────────────────────

it('détecte une entité d\'IA nommée dans le titre', function () {
    $resultat = editorialScorer()->score('OpenAI dévoile un nouvel outil pour les entreprises', null, null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : OpenAI']);
});

it('plafonne les entités d\'IA à deux, dans l\'ordre de la config - une troisième présente ne compte plus', function () {
    // Ordre config('news.editorial_triage.entites_ia') : OpenAI, Anthropic, Claude... Mistral
    // vient après Claude - il est présent dans le titre mais le plafond est déjà atteint avant
    // que la boucle ne l'examine.
    $resultat = editorialScorer()->score('OpenAI, Anthropic et Mistral annoncent un partenariat', null, null);

    expect($resultat['score'])->toBe(8)
        ->and($resultat['raisons'])->toBe([
            '+4 entité : OpenAI',
            '+4 entité : Anthropic',
        ]);
});

it('ne compte une entité qu\'une seule fois même si elle apparaît dans le titre ET sa traduction', function () {
    // Les noms propres d'entreprises ne se traduisent pas : OpenAI se retrouve identique dans
    // les deux textes, mais ne doit jamais faire gagner 8 points à la place de 4.
    $resultat = editorialScorer()->score('OpenAI announces breakthrough', 'OpenAI annonce une percée', null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : OpenAI']);
});

// ── Terme d'IA substantiel (+3 chacun, plafond +6) ──────────────────────────────────

it('détecte un terme d\'IA substantiel dans le titre', function () {
    $resultat = editorialScorer()->score('Un nouveau modèle de langage impressionne les chercheurs', null, null);

    expect($resultat['score'])->toBe(3)
        ->and($resultat['raisons'])->toBe(['+3 terme IA : modèle']);
});

it('lit le signal dans le titre TRADUIT quand l\'original ne le porte pas', function () {
    $resultat = editorialScorer()->score(
        'New model unveiled by a startup',
        'OpenAI dévoile un nouveau modèle',
        null
    );

    expect($resultat['score'])->toBe(7) // +4 entité (OpenAI) + 3 terme IA (modèle)
        ->and($resultat['raisons'])->toBe([
            '+4 entité : OpenAI',
            '+3 terme IA : modèle',
        ]);
});

// ── Portée : enquête et conséquence (+3 chacun, plafond +6) - revue adversariale 2026-09-08 ──

it('détecte un marqueur de portée (enquête/conséquence) dans le titre', function () {
    $resultat = editorialScorer()->score('Une enquête sur les conditions de travail', null, null);

    expect($resultat['score'])->toBe(3)
        ->and($resultat['raisons'])->toBe(['+3 portée : enquête']);
});

it('plafonne la portée à deux marqueurs, comme les entités et les termes IA', function () {
    $resultat = editorialScorer()->score('Une enquête et une poursuite contre le fabricant', null, null);

    expect($resultat['score'])->toBe(6)
        ->and($resultat['raisons'])->toBe([
            '+3 portée : enquête',
            '+3 portée : poursuite',
        ]);
});

it('BIAIS N°1 (revue adversariale 2026-09-08) : un titre d\'enquête SANS entité ni terme d\'IA obtient un score positif grâce à la portée', function () {
    // Avant l'ajout de la famille 'portee', ce titre valait exactement 0 - un communiqué
    // d'entreprise riche en marques et en chiffres le dépassait systématiquement, alors que
    // c'est exactement le genre d'article que ce site doit remonter.
    $resultat = editorialScorer()->score(
        'Une enquête révèle un licenciement massif chez un sous-traitant',
        null,
        null
    );

    expect($resultat['score'])->toBe(6)
        ->and($resultat['raisons'])->toBe([
            '+3 portée : enquête',
            '+3 portée : licenciement',
        ]);
});

// ── Frontière de mot (docs/CONTRAINTES-SOUS-AGENTS.md - défaut mesuré 4 fois sur ce projet) ──

it('« loi » compte comme mot isolé', function () {
    $resultat = editorialScorer()->score('Une nouvelle loi encadre les algorithmes', null, null);

    expect($resultat['score'])->toBe(3)
        ->and($resultat['raisons'])->toBe(['+3 portée : loi']);
});

it('« loi » n\'est PAS compté à tort à l\'intérieur d\'« emploi » (correspondance en sous-chaîne)', function () {
    // "emploi" se termine littéralement par "l-o-i" : sans frontière de mot, "loi" y matcherait
    // à tort et doublerait le score. Seul "emploi" doit compter, une seule fois.
    $resultat = editorialScorer()->score('Un emploi menacé par l\'automatisation', null, null);

    expect($resultat['score'])->toBe(3)
        ->and($resultat['raisons'])->toBe(['+3 portée : emploi']);
});

// ── Nombre porteur (+2, une seule fois, jamais cumulé) ──────────────────────────────

it('détecte un nombre porteur (montant avec unité)', function () {
    $resultat = editorialScorer()->score('Une entreprise lève 12,93 G$ pour ses centres de données', null, null);

    expect($resultat['score'])->toBe(2)
        ->and($resultat['raisons'])->toBe(['+2 nombre porteur : 12,93 G$']);
});

it('détecte un pourcentage collé au nombre, sans espace', function () {
    $resultat = editorialScorer()->score('Une hausse de 95% des couts de calcul', null, null);

    expect($resultat['score'])->toBe(2)
        ->and($resultat['raisons'])->toBe(['+2 nombre porteur : 95%']);
});

it('ne confond pas un nombre suivi d\'une lettre quelconque avec un nombre porteur', function () {
    // "3D" n'est l'unité d'aucune entrée de config('news.editorial_triage.unites_nombre_porteur') :
    // aucun signal ne doit se déclencher.
    $resultat = editorialScorer()->score('Un jeu vidéo en 3D annoncé pour l\'automne', null, null);

    expect($resultat['score'])->toBe(0)
        ->and($resultat['raisons'])->toBe([]);
});

// ── Marqueur commercial (-5 chacun, cumulable, aucun plafond) ───────────────────────

it('détecte un marqueur commercial', function () {
    $resultat = editorialScorer()->score('Bon plan : la meilleure tablette de l\'année', null, null);

    expect($resultat['score'])->toBe(-5)
        ->and($resultat['raisons'])->toBe(['-5 marqueur commercial : « bon plan »']);
});

it('cumule plusieurs marqueurs commerciaux distincts, sans plafond', function () {
    $resultat = editorialScorer()->score('Bon plan et unboxing complet du nouvel appareil', null, null);

    expect($resultat['score'])->toBe(-10)
        ->and($resultat['raisons'])->toBe([
            '-5 marqueur commercial : « bon plan »',
            '-5 marqueur commercial : « unboxing »',
        ]);
});

it('« promo » et « code promo » sont deux entrées distinctes de la config et se cumulent toutes les deux si le titre porte les deux mots - comportement voulu (cumulable, aucun plafond)', function () {
    $resultat = editorialScorer()->score('Bon plan et code promo pour le Black Friday', null, null);

    expect($resultat['score'])->toBe(-15)
        ->and($resultat['raisons'])->toBe([
            '-5 marqueur commercial : « bon plan »',
            '-5 marqueur commercial : « promo »',
            '-5 marqueur commercial : « code promo »',
        ]);
});

// ── Conservation (contrainte non négociable de la spec : AUCUNE exclusion) ──────────

it('un titre sans aucun signal obtient 0, jamais une exception ni une valeur négative implicite', function () {
    $resultat = editorialScorer()->score('Le nouveau centre commercial ouvre ses portes ce printemps', null, null);

    expect($resultat['score'])->toBe(0)
        ->and($resultat['raisons'])->toBe([]);
});

it('les trois titres de bruit commercial réels cités dans la spec du ticket #2358 obtiennent tous 0', function () {
    // Mesurés en production le 2026-09-08 (spec-tri-editorial.md) : aucun signal d'IA, ce score
    // ne les EXCLUT pas de l'écran - il les laisse simplement à 0, en bas d'eux-mêmes.
    foreach ([
        'Decathlon remplacé par un Lidl',
        'SteelSeries lance sa manette Xbox',
        'Les CD font un retour',
    ] as $titreReel) {
        $resultat = editorialScorer()->score($titreReel, null, null);
        expect($resultat['score'])->toBe(0)->and($resultat['raisons'])->toBe([]);
    }
});

// ── Contrat du paramètre nomMedia (accepté, sans effet sur le score - aucun signal de la spec ne porte sur le média) ──

it('accepte un nom de média sans qu\'il influence le score', function () {
    $avecMedia = editorialScorer()->score('Titre neutre sans aucun signal détectable.', null, 'Le Devoir');
    $sansMedia = editorialScorer()->score('Titre neutre sans aucun signal détectable.', null, null);

    expect($avecMedia)->toBe($sansMedia)
        ->and($avecMedia['score'])->toBe(0);
});

// ── CAUSE 1 (revue adversariale 2026-09-08, mesure sur 40 titres réels) : « IA »/« AI » ─────
// étaient absents d'entites_ia - 35/40 titres réels valaient 0. Frontière de mot OBLIGATOIRE :
// « IA » et « AI » sont des sous-chaînes de mots courants (médIA, mAIson).

it('« IA » et « AI » sont des sous-chaînes de mots courants - "les médias sociaux" ne marque AUCUNE entité', function () {
    $resultat = editorialScorer()->score('les médias sociaux', null, null);

    expect($resultat['score'])->toBe(0)
        ->and($resultat['raisons'])->toBe([]);
});

it('« IA » compte comme mot isolé - "l\'IA générative" marque bien une entité', function () {
    $resultat = editorialScorer()->score('l\'IA générative', null, null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : IA']);
});

it('CAUSE 1 (mesuré en production) : "L\'IA en Europe" valait 0 avant le correctif, obtient désormais un score positif', function () {
    $resultat = editorialScorer()->score('L\'IA en Europe', null, null);

    expect($resultat['score'])->toBe(7) // +4 entité (IA) + 3 portée (Europe)
        ->and($resultat['raisons'])->toBe([
            '+4 entité : IA',
            '+3 portée : Europe',
        ]);
});

it('CAUSE 1 : un grand acteur seul (sans "AI" accolé) est désormais une entité - "Meta lance Muse Spark 1.3" valait 0 avant le correctif', function () {
    $resultat = editorialScorer()->score('Meta lance Muse Spark 1.3', null, null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : Meta']);
});

// ── CAUSE 2 (revue adversariale 2026-09-08) : portee était une liste écrite à la main, ──────
// donc incomplète par construction - "lycée" en était absent alors qu'"école" y était.

it('CAUSE 2 : "Interdiction du smartphone au lycée" valait 0 avant le correctif ("lycée" absent de portee)', function () {
    $resultat = editorialScorer()->score('Interdiction du smartphone au lycée', null, null);

    expect($resultat['score'])->toBe(3)
        ->and($resultat['raisons'])->toBe(['+3 portée : lycée']);
});

// ── CAUSE 3, LA PLUS IMPORTANTE (revue adversariale 2026-09-08) : le bonus « nombre porteur » ──
// et le marqueur commercial se contredisaient - un chiffre de promotion gagnait le bonus destiné
// aux faits mesurables. Correctif STRUCTUREL : un marqueur commercial supprime le bonus.

it('CAUSE 3 : un pourcentage négatif de promotion ne gagne PLUS le bonus nombre porteur - "à -70 %" est un marqueur commercial, pas un fait mesurable', function () {
    // Titre réel mesuré par le superviseur : "Xiaomi Mi Mix Flip à -70 %" valait +2 avant ce
    // correctif (le bonus nombre porteur récompensait le chiffre de la promotion).
    $resultat = editorialScorer()->score('Xiaomi Mi Mix Flip à -70 %', null, null);

    expect($resultat['score'])->toBe(-5)
        ->and($resultat['raisons'])->toBe(['-5 marqueur commercial : « -70 % »']);
});

it('RESSERREMENT 2026-09-08 : un INTERVALLE de pourcentage ne mord plus - le tiret de « 20-30 % » n\'est pas un signe moins', function () {
    // Revue adversariale Codex : le motif d\'origine lisait le tiret d\'un intervalle comme un
    // signe moins, et pénalisait de -5 un titre de mesure. Les deux lookbehind du motif ferment
    // cette famille. Sans eux, ce test rougit à -5.
    $resultat = editorialScorer()->score('Les gains de productivité atteignent 20-30 % selon le rapport', null, null);

    // L'assertion porte sur l'élément TESTÉ (l'absence de pénalité commerciale), pas sur le total
    // du titre : celui-ci vaut 5 parce qu'il porte aussi « rapport » (+3 portée) et « 30 % »
    // (+2 nombre porteur). Un attendu sur le total aurait été recalibré à chaque enrichissement
    // de la config, sans jamais rien prouver sur l'intervalle.
    $penalites = array_filter($resultat['raisons'], static fn (string $r) => str_contains($r, 'marqueur commercial'));

    expect($penalites)->toBe([])
        ->and($resultat['score'])->toBeGreaterThan(0);
});

it('RESSERREMENT 2026-09-08 : le RETRAIT complet du gabarit était une régression - sans lui, la promo REMONTE à +2 au lieu de tomber à 0', function () {
    // Mémoire de la mesure qui a démenti une décision déjà prise. J\'avais retiré le gabarit au
    // nom de faux positifs FABRIQUÉS ; la remesure a montré que score() n\'accorde le bonus
    // « nombre porteur » qu\'en l\'absence de marqueur commercial - retirer le marqueur libérait
    // le bonus, et la promotion passait AU-DESSUS des titres neutres. Ce test garde la promo
    // sous zéro, quelle que soit la forme du pourcentage (collé ou espacé).
    expect(editorialScorer()->score('Erreur de prix : le Xiaomi Mi Mix Flip est à -70%, fin de l\'offre à minuit', null, null)['score'])
        ->toBeLessThan(0);
});

it('CAUSE 3 : une perte chiffrée en devise ("perd 200 €") est un marqueur commercial', function () {
    $resultat = editorialScorer()->score('Une entreprise perd 200 € sur chaque vente', null, null);

    expect($resultat['score'])->toBe(-5)
        ->and($resultat['raisons'])->toBe(['-5 marqueur commercial : « perd 200 € »']);
});

it('CAUSE 3 : les marqueurs commerciaux élargis ("chute à", "prix cassé") se cumulent normalement avec une entité', function () {
    $resultat = editorialScorer()->score('Google chute à la Bourse, prix cassé sur ses actions', null, null);

    expect($resultat['score'])->toBe(-6) // +4 entité (Google) - 5 - 5
        ->and($resultat['raisons'])->toBe([
            '+4 entité : Google',
            '-5 marqueur commercial : « chute à »',
            '-5 marqueur commercial : « prix cassé »',
        ]);
});

it('CAUSE 3 : GATING - un nombre légitime présent dans un titre par ailleurs commercial ne déclenche PAS le bonus nombre porteur', function () {
    // "3 millions" serait normalement un nombre porteur (+2) : la présence de DEUX marqueurs
    // commerciaux dans le même titre doit le supprimer entièrement, pas seulement le neutraliser
    // partiellement.
    $resultat = editorialScorer()->score('Bon plan : 3 millions de clients d\'ici la fin de l\'offre', null, null);

    expect($resultat['score'])->toBe(-10)
        ->and($resultat['raisons'])->toBe([
            '-5 marqueur commercial : « bon plan »',
            "-5 marqueur commercial : « fin de l'offre »",
        ]);
});

it('CAUSE 3 : le bonus nombre porteur reste intact quand AUCUN marqueur commercial n\'est présent (non-régression du gating)', function () {
    $resultat = editorialScorer()->score('Une entreprise lève 12,93 G$ pour ses centres de données', null, null);

    expect($resultat['score'])->toBe(2)
        ->and($resultat['raisons'])->toBe(['+2 nombre porteur : 12,93 G$']);
});

// ── CORRECTIF 1 (ticket #2358, mesuré en production) : 'GPT' rejoint entites_ia - avant, ──
// seul 'ChatGPT' y figurait, donc un titre qui ne nomme que le sigle 'GPT' valait 0.

it('CORRECTIF 1 : "GPT-6 Astra a battu Portal de bout en bout" marque l\'entité GPT', function () {
    $resultat = editorialScorer()->score('GPT-6 Astra a battu Portal de bout en bout', null, null);

    expect($resultat['score'])->toBeGreaterThan(0)
        ->and($resultat['raisons'])->toBe(['+4 entité : GPT']);
});

// ── CORRECTIF 2 (ticket #2358, mesuré en production) : un terme ENTIÈREMENT EN MAJUSCULES ──
// dans la config est désormais comparé en respectant la casse (sigle) - le verbe français
// « j'ai » ne doit plus jamais être confondu avec le sigle « AI ».

it('CORRECTIF 2 : le verbe français « j\'ai » ne marque jamais l\'entité AI - titre réel mesuré en production', function () {
    // Titre réel qui gagnait à tort "+4 entité : AI" avant le correctif (l'apostrophe est une
    // frontière de mot, et la comparaison ignorait la casse).
    $resultat = editorialScorer()->score(
        "J'ai factorisé les clés RSA d'une autorité de certification des années 90",
        null,
        null
    );

    expect($resultat['score'])->toBe(0)
        ->and($resultat['raisons'])->toBe([]);
});

it('CORRECTIF 2 : le verbe français « j\'ai » ne marque jamais l\'entité AI - second titre distinct', function () {
    $resultat = editorialScorer()->score("J'ai déménagé dans un nouvel appartement en banlieue", null, null);

    expect($resultat['score'])->toBe(0)
        ->and($resultat['raisons'])->toBe([]);
});

// ── CONSERVATION (obligatoire) : les correctifs ci-dessus ne doivent RIEN retirer aux ──
// détections légitimes déjà en place - sans ces trois cas, un test qui passe ne prouverait rien.

it('CONSERVATION : « L\'IA générative arrive dans les écoles » marque toujours l\'entité IA', function () {
    $resultat = editorialScorer()->score("L'IA générative arrive dans les écoles", null, null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : IA']);
});

it('CONSERVATION : un titre anglais portant « AI » en majuscules marque toujours l\'entité AI', function () {
    $resultat = editorialScorer()->score('AI startups race to raise funding', null, null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : AI']);
});

it('CONSERVATION : « LLM » en majuscules marque toujours son terme', function () {
    $resultat = editorialScorer()->score('Ce nouveau LLM surpasse ses concurrents', null, null);

    expect($resultat['score'])->toBe(3)
        ->and($resultat['raisons'])->toBe(['+3 terme IA : LLM']);
});

it('CONSERVATION : un terme à minuscules reste insensible à la casse - « openai » en minuscules marque toujours l\'entité OpenAI', function () {
    $resultat = editorialScorer()->score('openai lance un nouvel outil pour les entreprises', null, null);

    expect($resultat['score'])->toBe(4)
        ->and($resultat['raisons'])->toBe(['+4 entité : OpenAI']);
});
