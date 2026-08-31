#!/usr/bin/env bash
#
# scripts/prod-artisan.sh - Runner prod local (SUPERVISEUR uniquement, JAMAIS déployé - ce
# fichier reste sur le Mac, il n'est ni rsyncé ni exécuté en prod).
#
# Contexte (design doc "Actus - composition manuelle assistée" 2026-08-15, section
# "Améliorations en attente", point 2 ; générations du 2026-08-23 et du 2026-08-25 : durée de vie
# bornée dans le TEMPS plutôt qu'à l'usage, liste blanche de commandes, expiration testée AVANT
# le jeton) : ce générateur produit un squelette (scripts/templates/prod-oneshot.php.tpl) qui
# reste en service jusqu'à DUREE_DE_VIE_SECONDES du squelette (45 minutes) plutôt que de s'effacer
# après un seul appel - un cycle /actu2 complet enchaîne plusieurs commandes (news:brief, puis
# news:source, puis news:apply...) et redéposer un fichier par commande ne sécurise rien de plus,
# ça multiplie seulement les transferts.
#
# CE SCRIPT NE TOUCHE JAMAIS À LA PRODUCTION LUI-MÊME et NE DÉTIENT AUCUN SECRET/IDENTIFIANT :
# il GÉNÈRE localement (i) le fichier one-shot prêt à déposer et (ii), le cas échéant, la
# correspondance fichier local -> chemin prod pour --payload/--image, PUIS affiche les étapes
# exactes que le superviseur exécute lui-même avec ses propres outils (MCP cpanel pour le
# dépôt, curl pour le déclenchement et la vérification du 404).
#
# Usage :
#   scripts/prod-artisan.sh news:brief 33530
#   scripts/prod-artisan.sh news:apply 33530 --payload=/chemin/local.json
#   scripts/prod-artisan.sh news:apply 33530 --image=/chemin/local.jpg --credit="Photo par ..."
#   scripts/prod-artisan.sh news:apply 33530 --publish
#
# L'URL imprimée porte "&last=1" par défaut : ce déclenchement est traité comme LE DERNIER de son
# cycle et le runner s'efface aussitôt après avoir répondu. Pour enchaîner une AUTRE commande sur
# le MÊME fichier déjà déposé (éviter un redépôt), retirer "&last=1" des appels intermédiaires et
# reconstruire l'URL suivante à la main avec le MÊME token et un autre cmd=/args= - le fichier
# reste utilisable jusqu'à expiration (DUREE_DE_VIE_SECONDES du squelette) même sans last=1.
#
# @author MEMORA solutions <info@memora.ca> (https://memora.solutions)

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TEMPLATE_PATH="$PROJECT_ROOT/scripts/templates/prod-oneshot.php.tpl"
SCRATCH_ROOT="$PROJECT_ROOT/scripts/.scratch"
PROD_DOMAIN="https://laveille.ai"
# Chemin cPanel confirmé (memory/reference_chemin_prod_cpanel.md, compte gmemora) : le rsync de
# déploiement dépose le projet Laravel COMPLET (vendor/bootstrap/storage/public) directement
# sous ce dossier - public/ en est donc le document root réel.
PROD_PROJECT_REL_PATH="public_html/apps_diverses/laveille.ai"
PROD_PUBLIC_REL_PATH="${PROD_PROJECT_REL_PATH}/public"
PROD_STORAGE_UPLOAD_REL_PATH="${PROD_PROJECT_REL_PATH}/storage/app/oneshot-uploads"

if [ "$#" -lt 1 ]; then
    echo "Usage : scripts/prod-artisan.sh <commande:artisan> [arguments...]" >&2
    echo "Ex.   : scripts/prod-artisan.sh news:brief 33530" >&2
    exit 1
fi

if [ ! -f "$TEMPLATE_PATH" ]; then
    echo "Squelette introuvable : $TEMPLATE_PATH" >&2
    exit 1
fi

ARTISAN_COMMAND="$1"
shift

# ── Liste blanche : source unique de vérité = COMMANDES_AUTORISEES du squelette lui-même (le
#    runner déployé revalide la MÊME liste à l'exécution - défense en profondeur, cf. squelette).
#    On distingue "commande hors liste" (rejet normal) de "squelette invalide" (bogue à signaler). ──
set +e
php -r '
$tpl = file_get_contents($argv[1]);
if (! preg_match("/const\s+COMMANDES_AUTORISEES\s*=\s*(\[[^;]*\]);/s", $tpl, $m)) {
    fwrite(STDERR, "COMMANDES_AUTORISEES introuvable dans le squelette\n");
    exit(2);
}
$commandes = eval("return {$m[1]};");
exit(in_array($argv[2], $commandes, true) ? 0 : 1);
' "$TEMPLATE_PATH" "$ARTISAN_COMMAND"
WHITELIST_STATUS=$?
set -e

if [ "$WHITELIST_STATUS" -eq 2 ]; then
    echo "Squelette invalide : COMMANDES_AUTORISEES introuvable dans $TEMPLATE_PATH" >&2
    exit 1
elif [ "$WHITELIST_STATUS" -ne 0 ]; then
    echo "Commande hors liste blanche (COMMANDES_AUTORISEES de $TEMPLATE_PATH) : $ARTISAN_COMMAND" >&2
    exit 1
fi

DUREE_DE_VIE_SECONDES="$(php -r '
$tpl = file_get_contents($argv[1]);
if (! preg_match("/const\s+DUREE_DE_VIE_SECONDES\s*=\s*(\d+);/", $tpl, $m)) {
    fwrite(STDERR, "DUREE_DE_VIE_SECONDES introuvable dans le squelette\n");
    exit(1);
}
echo $m[1];
' "$TEMPLATE_PATH")"
DUREE_DE_VIE_MINUTES=$((DUREE_DE_VIE_SECONDES / 60))

# ── Noms des arguments positionnels par commande (doit rester synchronisé avec le $signature réel
#    de chaque classe sous Modules/News/app/Console/ - une commande absente d'ici alors qu'elle est
#    dans COMMANDES_AUTORISEES est un bogue de CE script, pas de la liste blanche). ──
case "$ARTISAN_COMMAND" in
    news:brief)         POSITIONAL_NAMES=(article) ;;
    news:source)        POSITIONAL_NAMES=(article url) ;;
    news:apply)          POSITIONAL_NAMES=(article) ;;
    news:create-draft)   POSITIONAL_NAMES=(url) ;;
    news:backfill-auto-tools) POSITIONAL_NAMES=() ;;
    *)
        echo "Noms d'arguments positionnels inconnus pour ${ARTISAN_COMMAND} - complète POSITIONAL_NAMES dans ce script (scripts/prod-artisan.sh)." >&2
        exit 1
        ;;
esac

# ── Jeton (32 caractères hex) - openssl d'abord (quasi universel sur macOS), repli /dev/urandom ──
if command -v openssl >/dev/null 2>&1; then
    TOKEN="$(openssl rand -hex 16)"
else
    TOKEN="$(head -c16 /dev/urandom | od -An -tx1 | tr -d ' \n')"
fi
TOKEN_SHORT="${TOKEN:0:8}"
RUN_STAMP="$(date +%Y%m%d-%H%M%S)"
RUN_DIR="$SCRATCH_ROOT/${RUN_STAMP}-${TOKEN_SHORT}"
mkdir -p "$RUN_DIR"

ONESHOT_FILENAME="_oneshot-${TOKEN_SHORT}.php"
ONESHOT_LOCAL_PATH="$RUN_DIR/$ONESHOT_FILENAME"

UPLOAD_INSTRUCTIONS=()
ARGS_PAIRS=()          # liste à plat clé, valeur, clé, valeur... - assemblée en JSON par PHP plus bas
POSITIONAL_INDEX=0
BOOL_MARK=$'\x01BOOL\x01'

for arg in "$@"; do
    case "$arg" in
        --payload=*|--image=*)
            opt_name="${arg%%=*}"
            local_path="${arg#*=}"
            if [ ! -f "$local_path" ]; then
                echo "Fichier local introuvable pour ${opt_name} : $local_path" >&2
                exit 1
            fi
            ext="${local_path##*.}"
            suffix="${opt_name#--}"
            prod_basename="${TOKEN_SHORT}-${suffix}.${ext}"
            UPLOAD_INSTRUCTIONS+=("${local_path}  ->  ${PROD_STORAGE_UPLOAD_REL_PATH}/${prod_basename}")
            ARGS_PAIRS+=("$opt_name" "{{STORAGE}}/${prod_basename}")
            ;;
        --*=*)
            opt_name="${arg%%=*}"
            opt_value="${arg#*=}"
            ARGS_PAIRS+=("$opt_name" "$opt_value")
            ;;
        --*)
            ARGS_PAIRS+=("$arg" "$BOOL_MARK")
            ;;
        *)
            if [ "$POSITIONAL_INDEX" -ge "${#POSITIONAL_NAMES[@]}" ]; then
                echo "Trop d'arguments positionnels pour ${ARTISAN_COMMAND} : $arg" >&2
                exit 1
            fi
            ARGS_PAIRS+=("${POSITIONAL_NAMES[$POSITIONAL_INDEX]}" "$arg")
            POSITIONAL_INDEX=$((POSITIONAL_INDEX + 1))
            ;;
    esac
done

# ── Liste blanche des arguments/options : source unique de vérité = ARGUMENTS_AUTORISES du
#    squelette (le runner déployé revalide la MÊME liste à l'exécution - défense en profondeur,
#    cf. squelette). Échoue ICI, localement, avant tout dépôt - plutôt que de découvrir un 403
#    après upload. Tableau vide = rien à vérifier (bash 3.2 de macOS lève "unbound variable" sur
#    l'expansion d'un tableau vide sous `set -u`, donc on l'évite plutôt que de la garder). ──
ARGS_KEYS_ONLY=()
for ((i = 0; i < ${#ARGS_PAIRS[@]}; i += 2)); do
    ARGS_KEYS_ONLY+=("${ARGS_PAIRS[$i]}")
done

if [ "${#ARGS_KEYS_ONLY[@]}" -gt 0 ]; then
    set +e
    php -r '
    $tpl = file_get_contents($argv[1]);
    if (! preg_match("/const\s+ARGUMENTS_AUTORISES\s*=\s*(\[[^;]*\]);/s", $tpl, $m)) {
        fwrite(STDERR, "ARGUMENTS_AUTORISES introuvable dans le squelette\n");
        exit(2);
    }
    $parCommande = eval("return {$m[1]};");
    $commande = $argv[2];
    if (! array_key_exists($commande, $parCommande)) {
        fwrite(STDERR, "aucune entree ARGUMENTS_AUTORISES pour {$commande}\n");
        exit(2);
    }
    $autorisees = $parCommande[$commande];
    $horsListe = [];
    for ($i = 3; $i < count($argv); $i++) {
        if (! in_array($argv[$i], $autorisees, true)) {
            $horsListe[] = $argv[$i];
        }
    }
    if ($horsListe) {
        fwrite(STDERR, "hors liste : ".implode(", ", $horsListe)."\n");
    }
    exit($horsListe ? 1 : 0);
    ' "$TEMPLATE_PATH" "$ARTISAN_COMMAND" "${ARGS_KEYS_ONLY[@]}"
    ARGS_WHITELIST_STATUS=$?
    set -e
else
    ARGS_WHITELIST_STATUS=0
fi

if [ "$ARGS_WHITELIST_STATUS" -eq 2 ]; then
    echo "Squelette invalide ou incomplet : ARGUMENTS_AUTORISES ne couvre pas ${ARTISAN_COMMAND} dans $TEMPLATE_PATH" >&2
    exit 1
elif [ "$ARGS_WHITELIST_STATUS" -ne 0 ]; then
    echo "Argument(s) hors liste blanche (ARGUMENTS_AUTORISES de $TEMPLATE_PATH) pour ${ARTISAN_COMMAND}." >&2
    exit 1
fi

# ── Construit l'objet JSON `args` via PHP (pas de bricolage d'échappement bash) : le marqueur
#    BOOL_MARK devient `true`, toute autre valeur reste une chaîne.
#    Le "--" avant ARGS_PAIRS est nécessaire dès qu'une commande sans argument positionnel (ex.
#    news:backfill-auto-tools) fait commencer ARGS_PAIRS par un flag ("--dry-run") : sans lui, le
#    SAPI CLI de PHP essaie d'interpréter ce premier "-r" argument comme SA PROPRE option et
#    échoue ("no argument for option -"), avant même que le code -r ne s'exécute. Bogue latent
#    du gabarit, jamais déclenché avant car les 4 commandes /actu2 existantes ont toutes un
#    identifiant positionnel en première position (jamais un flag). Vérifié isolément :
#    array_slice($argv, 1) reste identique avec ou sans le "--", qui n'entre jamais dans $argv. ──
# Même piège que ARGS_KEYS_ONLY plus haut : "${ARGS_PAIRS[@]}" sur un tableau vide lève "unbound
# variable" sous bash 3.2 + set -u (vérifié isolément) - cas réel dès qu'une commande sans aucun
# argument positionnel est appelée sans option (news:backfill-auto-tools seul, valeurs par défaut).
if [ "${#ARGS_PAIRS[@]}" -gt 0 ]; then
    ARGS_JSON="$(BOOL_MARK="$BOOL_MARK" php -r '
$pairs = array_slice($argv, 1);
$boolMark = getenv("BOOL_MARK");
$out = [];
for ($i = 0; $i < count($pairs); $i += 2) {
    $out[$pairs[$i]] = ($pairs[$i + 1] === $boolMark) ? true : $pairs[$i + 1];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
' -- "${ARGS_PAIRS[@]}")"
else
    ARGS_JSON='{}'
fi

# ── Génère le one-shot depuis le squelette : seul __TOKEN__ reste à substituer - cmd/args/last
#    voyagent désormais en paramètres GET à l'exécution, plus rien n'est figé dans le fichier. ──
TOKEN="$TOKEN" php -r '
$tpl = file_get_contents($argv[1]);
$tpl = str_replace("__TOKEN__", addcslashes(getenv("TOKEN"), "\\\x27"), $tpl);
file_put_contents($argv[2], $tpl);
' "$TEMPLATE_PATH" "$ONESHOT_LOCAL_PATH"

php -l "$ONESHOT_LOCAL_PATH" >/dev/null

CMD_ENC="$(php -r 'echo rawurlencode($argv[1]);' "$ARTISAN_COMMAND")"
ARGS_ENC="$(php -r 'echo rawurlencode($argv[1]);' "$ARGS_JSON")"
ONESHOT_BASE_URL="${PROD_DOMAIN}/${ONESHOT_FILENAME}"
ONESHOT_URL="${ONESHOT_BASE_URL}?t=${TOKEN}&cmd=${CMD_ENC}&args=${ARGS_ENC}&last=1"

echo "════════════════════════════════════════════════════════════════════"
echo " Runner prod local - ${ARTISAN_COMMAND}"
echo "════════════════════════════════════════════════════════════════════"
echo
echo "Commande exécutée en prod (kernel->call, équivalent à \`php artisan ${ARTISAN_COMMAND} ...\` en SSH) :"
echo "  cmd  = ${ARTISAN_COMMAND}"
echo "  args = ${ARGS_JSON}"
echo
echo "── ÉTAPE 1 - Déposer via le MCP cpanel ──"
echo "  Fichier one-shot (texte -> cpanel_file_write) :"
echo "    local : $ONESHOT_LOCAL_PATH"
echo "    prod  : ${PROD_PUBLIC_REL_PATH}/${ONESHOT_FILENAME}"
if [ "${#UPLOAD_INSTRUCTIONS[@]}" -gt 0 ]; then
    echo
    echo "  Fichier(s) d'accompagnement (--payload -> cpanel_file_write ; --image -> binaire,"
    echo "  cpanel_file_upload_binary) - créer d'abord le dossier storage/app/oneshot-uploads/"
    echo "  s'il n'existe pas encore :"
    for line in "${UPLOAD_INSTRUCTIONS[@]}"; do
        echo "    $line"
    done
fi
echo
echo "── ÉTAPE 2 - Déclencher l'exécution (la réponse HTTP EST la sortie brute de la commande) ──"
echo "  curl -sS '${ONESHOT_URL}'"
echo
echo "── ÉTAPE 3 - Vérifier l'auto-suppression (doit répondre 404, sinon retirer le fichier à la main) ──"
echo "  curl -sS -o /dev/null -w '%{http_code}\n' '${ONESHOT_BASE_URL}'"
echo
echo "Pour enchaîner une AUTRE commande sur ce MÊME fichier avant expiration (${DUREE_DE_VIE_MINUTES} min),"
echo "retirer \"&last=1\" de l'URL de l'étape 2 et répéter avec un autre cmd=/args=, même t=${TOKEN}."
echo
echo "Dossier scratch local (jamais commité, jamais déployé) : $RUN_DIR"
