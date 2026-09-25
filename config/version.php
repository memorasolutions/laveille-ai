<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Historique complet des versions et règles de bump SemVer : docs/HISTORIQUE-VERSIONS.md
 * (ce fichier ne contient plus que le code - allégé le 2026-08-28, 954 948 -> quelques centaines
 * d'octets, pour arrêter de saturer la mémoire à chaque rechargement de configuration en test).
 *
 * 2026-09-21 - CE FICHIER A ÉTÉ VIDÉ PAR ACCIDENT, et le défaut a vécu en production.
 * Le commit v1.292.3 a supprimé ses 29 lignes sans en ajouter aucune. Conséquence mesurée le
 * jour même sur laveille.ai/outils/constructeur-prompts : les gabarits rendaient « ?v= », vide,
 * parce que config('version.semver') renvoyait null. Le cache-bust de TOUS les JS et CSS des
 * outils était donc hors service, et un visiteur déjà venu continuait de recevoir les anciens
 * fichiers - y compris pour des correctifs livrés après coup.
 *
 * Ce qu'il faut en retenir : un fichier de configuration VIDE ne casse rien bruyamment. Aucune
 * page n'a planté, aucune erreur n'est remontée dans les journaux. Le seul signal était une
 * chaîne vide dans du HTML servi, que seule une requête réelle pouvait montrer. Un contrôle
 * après déploiement qui regarde « la page répond-elle 200 » ne voit strictement rien.
 */

$lvMajor = 1;
$lvMinor = 300;
$lvPatch = 0;

return [
    'major' => $lvMajor,
    'minor' => $lvMinor,
    'patch' => $lvPatch,

    // Codename optionnel (nom de la release courante). Vide ou null si pas de codename.
    'codename' => 'francais-dabord',

    // Format du SemVer assemblé - DÉRIVÉ automatiquement de major.minor.patch (source unique).
    // NE JAMAIS figer cette valeur en dur (incident déjà survenu - voir archive, #319).
    'semver' => $lvMajor.'.'.$lvMinor.'.'.$lvPatch,
];
