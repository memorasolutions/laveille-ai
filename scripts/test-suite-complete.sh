#!/usr/bin/env bash
#
# test-suite-complete.sh
# ---------------------------------------------------------------------------
# POURQUOI CE SCRIPT EXISTE (mesuré le 2026-09-09) :
#
#   `php artisan test` lancé en UN SEUL processus meurt de faim mémoire au
#   test #5253, dans Modules/Directory/tests/Feature/
#   DispatchMarginRecaptureCommandTest.php (un appel imagecreatetruecolor
#   (6000, 4000)). Tout ce qui suit alphabétiquement après ce point n'est
#   JAMAIS exécuté : 37 modules, 1003 tests verts jamais lancés, sans que
#   rien ne le signale - le processus meurt avant d'avoir pu l'annoncer.
#
#   Un processus PAR SEGMENT (une suite native Architecture/Unit/Feature, ou
#   un module) remet le compteur mémoire PHP à zéro entre chaque appel. Le
#   test qui a fait mourir le processus unique ne contamine plus les
#   suivants : chaque segment se juge sur son propre sort, pas sur celui de
#   son voisin alphabétique.
#
# PIÈGES DÉJÀ MESURÉS DANS DES GÉNÉRATIONS PRÉCÉDENTES DE CE SCRIPT, à ne
# pas reproduire :
#   - `grep -E '^Tests:'` ne mord pas : Pest indente ("  Tests:    12
#     passed..."). Le motif correct est `^ *Tests:`, lu avec `tail -n 1`.
#   - L'option `--log-journal` n'existe PAS dans `php artisan test` : on
#     redirige la sortie standard nous-mêmes, un fichier par segment.
#   - `ls Modules/<Nom>/*Test.php` ne voit rien : les tests vivent sous
#     Modules/<Nom>/tests/Feature ou /Unit, il faut `find` en récursif.
#   - Aucun `set -e` : on veut continuer après l'échec d'un segment pour
#     juger TOUS les segments, pas s'arrêter au premier rouge.
#   - Aucun `((compteur++))` : cette forme retourne un code non nul quand le
#     compteur vaut 0, ce qui se lit comme une erreur alors qu'il n'y en a
#     pas. On utilise `x=$(( x + 1 ))`.
#   - Aucune expansion `"${tableau[@]}"` sur un tableau qui peut être vide :
#     le bash 3.2 livré par macOS considère ça comme une variable non liée
#     sous `set -u` (bogue corrigé seulement en bash 4.4). On parcourt les
#     tableaux par index (`${tableau[$i]}`, borné par `${#tableau[@]}`),
#     jamais par expansion `@`.
#
# Attribution : MEMORA solutions (https://memora.solutions, info@memora.ca)
# ---------------------------------------------------------------------------

set -uo pipefail

# ---------------------------------------------------------------------------
# Racine du dépôt = dossier parent de celui qui contient ce script. Toute la
# suite du script raisonne en chemins relatifs à cette racine.
# ---------------------------------------------------------------------------
DOSSIER_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RACINE_DEPOT="$(dirname "$DOSSIER_SCRIPT")"
cd "$RACINE_DEPOT" || {
    echo "Erreur : impossible de se placer à la racine du dépôt ($RACINE_DEPOT)." >&2
    exit 2
}

# ---------------------------------------------------------------------------
# Options (toutes déclarées ici : `set -u` exige une valeur par défaut avant
# tout usage).
# ---------------------------------------------------------------------------
MODULES_SEULEMENT=0
DEPUIS=""
DOSSIER_JOURNAUX=""
AFFICHER_AIDE=0

afficher_aide() {
    cat <<'AIDE'
Usage : scripts/test-suite-complete.sh [options]

Lance la suite de tests Laravel en plusieurs PROCESSUS SÉPARÉS - un par
segment - plutôt qu'un seul `php artisan test` qui meurt de faim mémoire
avant la fin et emporte silencieusement tout ce qui suit alphabétiquement.
Chaque segment obtient son propre journal, dans son propre processus PHP.

Segments, dans l'ordre :
  1. --testsuite=Architecture
  2. --testsuite=Unit
  3. --testsuite=Feature
  4. un segment par module de Modules/, en ordre alphabétique - retenu s'il
     contient au moins un fichier *Test.php quelque part sous
     Modules/<Nom>/tests (recherche récursive) ; les dossiers tests/ vides
     ou absents sont ignorés et comptés à part dans le bilan.

Options :
  --modules-seulement     Ignore Architecture/Unit/Feature ; ne lance que
                          les segments de modules.
  --depuis <Nom>          Reprend à partir du segment nommé <Nom> - par
                          POSITION dans la liste des segments retenus,
                          jamais par comparaison lexicographique. <Nom> est
                          soit Architecture, Unit, Feature, soit le nom
                          exact d'un module (ex. Webhooks).
  --journaux <dossier>    Dossier où écrire les journaux (un fichier par
                          segment). Par défaut :
                          storage/logs/test-suite-complete/<horodatage>/
  --aide                  Affiche ce message et quitte (code 0).

Code de sortie : 0 seulement si TOUS les segments retenus se terminent en
code 0. Sinon 1. Un segment tué par manque de mémoire (SIGKILL, code 137)
est rejoué UNE fois, seul ; les deux journaux sont conservés ; l'échec
n'est retenu que si le rejeu échoue aussi.

Exemples :
  scripts/test-suite-complete.sh
  scripts/test-suite-complete.sh --modules-seulement --depuis Webhooks
  scripts/test-suite-complete.sh --journaux /tmp/mes-journaux
AIDE
}

# ---------------------------------------------------------------------------
# Analyse des arguments.
# ---------------------------------------------------------------------------
while [ "$#" -gt 0 ]; do
    case "$1" in
        --modules-seulement)
            MODULES_SEULEMENT=1
            shift
            ;;
        --depuis)
            if [ "$#" -lt 2 ]; then
                echo "Erreur : --depuis exige un nom de segment." >&2
                exit 2
            fi
            DEPUIS="$2"
            shift 2
            ;;
        --journaux)
            if [ "$#" -lt 2 ]; then
                echo "Erreur : --journaux exige un chemin de dossier." >&2
                exit 2
            fi
            DOSSIER_JOURNAUX="$2"
            shift 2
            ;;
        --aide|-h|--help)
            AFFICHER_AIDE=1
            shift
            ;;
        *)
            echo "Erreur : option inconnue : $1" >&2
            echo "Utilisez --aide pour la liste des options." >&2
            exit 2
            ;;
    esac
done

if [ "$AFFICHER_AIDE" -eq 1 ]; then
    afficher_aide
    exit 0
fi

# ---------------------------------------------------------------------------
# Construction de la liste ordonnée des segments : NOMS[i] <-> CIBLES[i].
# Tableaux toujours parcourus par index (voir piège bash 3.2 en en-tête) -
# jamais par expansion "${tableau[@]}".
# ---------------------------------------------------------------------------
NOMS=()
CIBLES=()
MODULES_IGNORES=""

if [ "$MODULES_SEULEMENT" -eq 0 ]; then
    NOMS+=("Architecture")
    CIBLES+=("--testsuite=Architecture")
    NOMS+=("Unit")
    CIBLES+=("--testsuite=Unit")
    NOMS+=("Feature")
    CIBLES+=("--testsuite=Feature")
fi

while IFS= read -r NOM_MODULE; do
    [ -n "$NOM_MODULE" ] || continue
    DOSSIER_TESTS_MODULE="Modules/$NOM_MODULE/tests"
    if [ -d "$DOSSIER_TESTS_MODULE" ]; then
        NB_FICHIERS_TEST=$(find "$DOSSIER_TESTS_MODULE" -type f -name '*Test.php' 2>/dev/null | wc -l | tr -d ' ')
        if [ "$NB_FICHIERS_TEST" -gt 0 ]; then
            NOMS+=("$NOM_MODULE")
            CIBLES+=("$DOSSIER_TESTS_MODULE")
        else
            MODULES_IGNORES="$MODULES_IGNORES $NOM_MODULE"
        fi
    fi
done < <(find "Modules" -mindepth 1 -maxdepth 1 -type d -exec basename {} \; | LC_ALL=C sort)

NB_SEGMENTS=${#NOMS[@]}
if [ "$NB_SEGMENTS" -eq 0 ]; then
    echo "Erreur : aucun segment à exécuter (vérifiez --modules-seulement et la présence de Modules/)." >&2
    exit 2
fi

# ---------------------------------------------------------------------------
# --depuis : reprise par POSITION dans NOMS[], jamais par comparaison
# lexicographique.
# ---------------------------------------------------------------------------
INDICE_DEPART=0
if [ -n "$DEPUIS" ]; then
    INDICE_TROUVE=-1
    i=0
    while [ "$i" -lt "$NB_SEGMENTS" ]; do
        if [ "${NOMS[$i]}" = "$DEPUIS" ]; then
            INDICE_TROUVE=$i
            break
        fi
        i=$(( i + 1 ))
    done
    if [ "$INDICE_TROUVE" -lt 0 ]; then
        echo "Erreur : segment introuvable pour --depuis : $DEPUIS" >&2
        echo "Segments disponibles :" >&2
        j=0
        while [ "$j" -lt "$NB_SEGMENTS" ]; do
            echo "  - ${NOMS[$j]}" >&2
            j=$(( j + 1 ))
        done
        exit 2
    fi
    INDICE_DEPART=$INDICE_TROUVE
fi

# ---------------------------------------------------------------------------
# Dossier de journaux.
# ---------------------------------------------------------------------------
if [ -z "$DOSSIER_JOURNAUX" ]; then
    HORODATAGE_DOSSIER="$(date -u +%Y%m%d-%H%M%S)"
    DOSSIER_JOURNAUX="storage/logs/test-suite-complete/$HORODATAGE_DOSSIER"
fi

mkdir -p "$DOSSIER_JOURNAUX" || {
    echo "Erreur : impossible de créer le dossier de journaux : $DOSSIER_JOURNAUX" >&2
    exit 2
}

# ---------------------------------------------------------------------------
# Cumul des totaux depuis la ligne « Tests: » de Pest.
# Motif correct : `^ *Tests:` (Pest indente), lu avec `tail -n 1`.
# Exemples réels : "  Tests:    12 passed (40 assertions)",
#                  "  Tests:    2 skipped, 116 passed (258 assertions)".
# ---------------------------------------------------------------------------
TOTAL_PASSED=0
TOTAL_FAILED=0
TOTAL_SKIPPED=0
TOTAL_INCOMPLETE=0
TOTAL_RISKY=0
TOTAL_WARNINGS=0
TOTAL_ERRORS=0

extraire_compte() {
    printf '%s\n' "$1" | grep -oE "[0-9]+ $2" | grep -oE '^[0-9]+' | head -n 1
}

cumuler_resume() {
    local ligne="$1"
    local val
    val=$(extraire_compte "$ligne" "passed");     [ -n "$val" ] && TOTAL_PASSED=$(( TOTAL_PASSED + val ))
    val=$(extraire_compte "$ligne" "failed");     [ -n "$val" ] && TOTAL_FAILED=$(( TOTAL_FAILED + val ))
    val=$(extraire_compte "$ligne" "skipped");    [ -n "$val" ] && TOTAL_SKIPPED=$(( TOTAL_SKIPPED + val ))
    val=$(extraire_compte "$ligne" "incomplete"); [ -n "$val" ] && TOTAL_INCOMPLETE=$(( TOTAL_INCOMPLETE + val ))
    val=$(extraire_compte "$ligne" "risky");      [ -n "$val" ] && TOTAL_RISKY=$(( TOTAL_RISKY + val ))
    val=$(extraire_compte "$ligne" "warnings");   [ -n "$val" ] && TOTAL_WARNINGS=$(( TOTAL_WARNINGS + val ))
    val=$(extraire_compte "$ligne" "errors");     [ -n "$val" ] && TOTAL_ERRORS=$(( TOTAL_ERRORS + val ))
}

# ---------------------------------------------------------------------------
# Exécution des segments retenus, chacun dans son propre processus
# `php artisan test`, journal séparé. Le code de sortie est capturé
# IMMÉDIATEMENT après l'appel, jamais après une commande intermédiaire.
# ---------------------------------------------------------------------------
echo "Départ du lot : $(TZ=America/Toronto date +'%Hh%M') Québec ($(date -u +'%H:%M') UTC)"
echo "Segments retenus : $NB_SEGMENTS - exécution à partir de l'indice $INDICE_DEPART (${NOMS[$INDICE_DEPART]})."
echo "Journaux : $DOSSIER_JOURNAUX"
echo ""

SEGMENTS_OK=0
SEGMENTS_KO=0
NOMS_REJOUES=""

i=$INDICE_DEPART
while [ "$i" -lt "$NB_SEGMENTS" ]; do
    NOM="${NOMS[$i]}"
    CIBLE="${CIBLES[$i]}"
    NUM=$(( i + 1 ))
    BASE_JOURNAL=$(printf '%02d-%s' "$NUM" "$NOM")
    JOURNAL="$DOSSIER_JOURNAUX/$BASE_JOURNAL.log"

    echo "[$NUM/$NB_SEGMENTS] $NOM ..."
    php artisan test "$CIBLE" > "$JOURNAL" 2>&1
    CODE=$?
    JOURNAL_FINAL="$JOURNAL"

    if [ "$CODE" -eq 137 ]; then
        echo "    -> tué par manque de mémoire (SIGKILL, code 137). Rejeu seul en cours..."
        JOURNAL_REJEU="$DOSSIER_JOURNAUX/$BASE_JOURNAL.rejeu.log"
        php artisan test "$CIBLE" > "$JOURNAL_REJEU" 2>&1
        CODE=$?
        JOURNAL_FINAL="$JOURNAL_REJEU"
        NOMS_REJOUES="$NOMS_REJOUES $NOM"
    fi

    LIGNE_RESUME=$(grep -E '^ *Tests:' "$JOURNAL_FINAL" 2>/dev/null | tail -n 1)
    if [ -z "$LIGNE_RESUME" ]; then
        LIGNE_RESUME="(aucun bilan Pest lisible dans le journal : $JOURNAL_FINAL)"
    else
        cumuler_resume "$LIGNE_RESUME"
    fi

    if [ "$CODE" -eq 0 ]; then
        SEGMENTS_OK=$(( SEGMENTS_OK + 1 ))
        echo "    -> OK (code 0) - $LIGNE_RESUME"
    else
        SEGMENTS_KO=$(( SEGMENTS_KO + 1 ))
        echo "    -> ÉCHEC (code $CODE) - $LIGNE_RESUME"
    fi

    i=$(( i + 1 ))
done

# ---------------------------------------------------------------------------
# Bilan final. Code de sortie global : 0 seulement si TOUS les segments
# retenus sont sortis en 0 (après rejeu le cas échéant), sinon 1.
# ---------------------------------------------------------------------------
echo ""
echo "=================== BILAN ==================="
echo "Fin du lot : $(TZ=America/Toronto date +'%Hh%M') Québec ($(date -u +'%H:%M') UTC)"
echo "Segments exécutés : $(( NB_SEGMENTS - INDICE_DEPART )) (sur $NB_SEGMENTS retenus au total)."
echo "  OK    : $SEGMENTS_OK"
echo "  ÉCHEC : $SEGMENTS_KO"
if [ -n "$NOMS_REJOUES" ]; then
    echo "  Segments rejoués après SIGKILL :$NOMS_REJOUES"
fi
if [ -n "$MODULES_IGNORES" ]; then
    echo "  Modules ignorés (tests/ vide ou absent) :$MODULES_IGNORES"
fi
echo ""
echo "Totaux agrégés depuis les lignes « Tests: » lisibles :"
echo "  passed=$TOTAL_PASSED failed=$TOTAL_FAILED skipped=$TOTAL_SKIPPED incomplete=$TOTAL_INCOMPLETE risky=$TOTAL_RISKY warnings=$TOTAL_WARNINGS errors=$TOTAL_ERRORS"
echo ""

if [ "$SEGMENTS_KO" -eq 0 ]; then
    echo "Code de sortie global : 0"
    exit 0
else
    echo "Code de sortie global : 1"
    exit 1
fi
