<?php

declare(strict_types=1);

use Tests\TestCase;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Verrou contre la réintroduction d'une affirmation de conformité Loi 25 / RGPD portant sur NOTRE
 * anonymiseur. L'outil remplace des données personnelles par des jetons et garde la table de
 * correspondance chez la personne : c'est une PSEUDONYMISATION, réversible par conception, et une
 * donnée pseudonymisée reste une donnée personnelle au sens de la Loi 25 comme du RGPD.
 *
 * POURQUOI CE TEST EXISTE. Le défaut a été « corrigé » trois fois avant de tenir, et chaque échec
 * a été SILENCIEUX. Le trou réel n'était pas le correctif mais son absence de verrou : une
 * migration corrige la DONNÉE déjà en base, elle ne protège pas la SOURCE. Un reseed aurait
 * réinjecté le texte fautif sans que rien ne rougisse.
 *
 * ET CE FICHIER LUI-MÊME A ÉTÉ PRIS EN DÉFAUT DEUX FOIS, par une passe adversariale, ce qui est
 * la raison de ses deux particularités :
 *   1. Sa première version n'avait pas de `uses(TestCase::class)`. Le dossier
 *      Modules/Tools/tests/Feature n'est PAS couvert par tests/Pest.php : sans cette déclaration,
 *      l'application n'est jamais démarrée et `base_path()` échoue sur « Call to undefined method
 *      Container::basePath() ». Le test ne mesurait donc RIEN - un verrou qui ne tourne pas est
 *      exactement aussi utile qu'un verrou absent.
 *   2. Son motif attrapait « conformément à la Loi 25 », qui est un CONSEIL pédagogique parfaitement
 *      exact, présent dans le même fichier de seeder pour le terme « pseudonymisation ». Un verrou
 *      qui rougit sur du contenu juste finit par être désactivé, pas corrigé.
 *
 * Il ne remplace pas la vérification du texte réellement SERVI, seul juge de ce que voit un
 * visiteur : il empêche seulement que le défaut revienne par les fichiers du dépôt.
 */
uses(TestCase::class);

$fichiersSources = [
    'Modules/Tools/database/seeders/ToolSeeder.php',
    'Modules/Tools/database/seeders/AnonymisationGlossarySeeder.php',
    'Modules/Tools/resources/views/public/tools/anonymiseur.blade.php',
];

/**
 * Le motif vise l'AFFIRMATION D'ÉTAT (« conforme », « conformité ») et jamais l'adverbe de manière
 * (« conformément »), qui introduit un conseil sur la façon de procéder - deux choses opposées que
 * la première version confondait. La frontière de mot suffit à les séparer : « conformément »
 * porte un « é » là où « conforme » porte un « e ».
 *
 * La distance est bornée par [^.!?] pour ne jamais franchir une fin de phrase : sans cette borne,
 * un « responsable conformité » en début de paragraphe se relierait à un « RGPD » trois phrases
 * plus loin et produirait une accusation fausse.
 */
$motifInterdit = '/\bconform(?:e|es|ité|ités)\b[^.!?]{0,30}?(?:Loi\s*25|RGPD)/iu';

it('ne laisse aucun fichier source affirmer que notre anonymiseur est conforme à la Loi 25 ou au RGPD',
    function (string $chemin) use ($motifInterdit) {
        $absolu = base_path($chemin);

        expect(file_exists($absolu))->toBeTrue("Fichier introuvable : {$chemin}");

        preg_match_all($motifInterdit, file_get_contents($absolu), $trouvailles);

        // Le message d'échec cite le texte fautif : sans lui, un test rouge oblige à fouiller.
        expect($trouvailles[0])->toBe([], sprintf(
            '%s affirme une conformité que le mécanisme réversible dément : %s',
            $chemin,
            implode(' | ', $trouvailles[0])
        ));
    }
)->with($fichiersSources);

/**
 * LE TÉMOIN DU CONTRÔLE, dans les deux sens. Sans lui, un motif cassé - une regex qui ne compile
 * plus, un accent tapé littéralement dans une classe de caractères - laisserait ce test VERT en
 * permanence et le verrou ne protégerait plus rien sans que personne ne le sache. Défaut déjà
 * mesuré sur ce projet le 2026-09-18, sur trois contrôles typographiques d'un coup.
 *
 * Le volet NÉGATIF compte autant que le positif : il fige les trois formulations légitimes
 * réellement présentes dans les fichiers surveillés. Si quelqu'un élargit un jour le motif, c'est
 * ce volet qui l'arrêtera avant qu'il ne fasse supprimer du contenu exact.
 */
it('a un motif qui attrape les formulations fautives connues sans mordre sur les formulations exactes',
    function () use ($motifInterdit) {
        $fautives = [
            'Conforme à la Loi 25 et au RGPD.',               // description servie en production
            "un outil d'anonymisation conforme à la Loi 25",  // réponse de FAQ du glossaire
            '100 % local, conforme Loi 25 et RGPD.',           // texte d'origine du seeder
            'Conforme Loi 25',                                 // libellé d'accordéon
            'conforme au RGPD',                                // variante sans « la »
            'notre outil est en conformité avec la Loi 25',    // tournure par le nom
        ];

        foreach ($fautives as $forme) {
            expect(preg_match($motifInterdit, $forme))->toBe(1, "Le motif laisse passer : {$forme}");
        }

        // Verbatim EXTRAITS du seeder du glossaire (lignes 72, 79 et 169 au 2026-09-22), jamais
        // retapés : la première version de ce témoin PARAPHRASAIT la ligne 79 et lui ajoutait une
        // fin inventée (« et conforme aux exigences du RGPD ») qui, elle, était bel et bien
        // fautive. Le test a refusé cette paraphrase, et il avait raison - un témoin qu'on rédige
        // de mémoire ne témoigne de rien.
        $legitimes = [
            ', chiffrée et accessible uniquement aux employés autorisés, conformément à la Loi 25 et au RGPD.",',
            'r accès, comme un administrateur sécurité ou un responsable conformité. L\'accès doit être journalisé, protégé par authentific',
            'HashiCorp Vault ou Auth0. Ces outils gèrent la sécurité, la conformité PCI DSS et la gestion du vault, ce qui réduit les risq',
        ];

        foreach ($legitimes as $forme) {
            expect(preg_match($motifInterdit, $forme))->toBe(0, "Faux positif sur du contenu exact : {$forme}");
        }
    });
