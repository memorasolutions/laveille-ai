#!/usr/bin/env bash
#
# scripts/prod-artisan.sh - Runner prod local (SUPERVISEUR uniquement, JAMAIS déployé - ce
# fichier reste sur le Mac, il n'est ni rsyncé ni exécuté en prod).
#
# Contexte (design doc "Actus - composition manuelle assistée" 2026-08-15, section
# "Améliorations en attente", point 2) : le premier cycle /actu2 réel a exigé 8 scripts one-shot
# écrits à la main. Ce générateur remplace la rédaction manuelle par un squelette unique et
# éprouvé (scripts/templates/prod-oneshot.php.tpl) - même sécurité jeton + auto-suppression à
# chaque exécution.
#
# CE SCRIPT NE TOUCHE JAMAIS À LA PRODUCTION LUI-MÊME et NE DÉTIENT AUCUN SECRET/IDENTIFIANT :
# il GÉNÈRE localement (i) le fichier one-shot prêt à déposer et (ii), le cas échéant, la
# correspondance fichier local -> chemin prod pour --payload/--image, PUIS affiche les 3 étapes
# exactes que le superviseur Claude exécute lui-même avec ses propres outils (MCP cpanel pour le
# dépôt, curl pour le déclenchement et la vérification du 404).
#
# Usage :
#   scripts/prod-artisan.sh news:brief 33530
#   scripts/prod-artisan.sh news:apply 33530 --payload=/chemin/local.json
#   scripts/prod-artisan.sh news:apply 33530 --image=/chemin/local.jpg --credit="Photo par ..."
#   scripts/prod-artisan.sh news:apply 33530 --publish
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

# ── Quote un argument pour Symfony StringInput (même règle que le guillemetage simple-quote
#    POSIX : englobe TOUJOURS d'un seul quote, échappe les quotes internes en '\''), pour que
#    --credit="Photo par X" ou une valeur contenant des espaces reste UN seul jeton. ──
quote_arg() {
    local s="$1"
    printf "'%s'" "${s//\'/\'\\\'\'}"
}

UPLOAD_INSTRUCTIONS=()
CLEANUP_EXTRA_LINES=""
COMMAND_LINE="$ARTISAN_COMMAND"

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
            CLEANUP_EXTRA_LINES="${CLEANUP_EXTRA_LINES}\$cleanupPaths[] = storage_path('app/oneshot-uploads/${prod_basename}');"$'\n'
            COMMAND_LINE="${COMMAND_LINE} $(quote_arg "${opt_name}={{STORAGE}}/${prod_basename}")"
            ;;
        *)
            COMMAND_LINE="${COMMAND_LINE} $(quote_arg "$arg")"
            ;;
    esac
done

# ── Génère le one-shot depuis le squelette via PHP (pas sed : évite tout piège d'échappement
#    avec les apostrophes/guillemets déjà présents dans COMMAND_LINE, ex. --credit="..."). Les
#    deux placeholders __TOKEN__/__ARTISAN_CALL__ tombent DANS une chaîne PHP à guillemets
#    simples du squelette ('__TOKEN__', '__ARTISAN_CALL__') : addcslashes(..., "\\'") échappe
#    backslash et guillemet simple pour rester une chaîne PHP valide quelle que soit la valeur
#    (ex. --credit="Photo par ..." contient déjà des guillemets doubles, jamais un problème ici,
#    mais quote_arg() ci-dessus produit aussi des guillemets simples qu'il FAUT échapper). ──
TOKEN="$TOKEN" COMMAND_LINE="$COMMAND_LINE" CLEANUP_EXTRA="$CLEANUP_EXTRA_LINES" php -r '
$tpl = file_get_contents($argv[1]);
$tpl = str_replace("__TOKEN__", addcslashes(getenv("TOKEN"), "\\\x27"), $tpl);
$tpl = str_replace("__ARTISAN_CALL__", addcslashes(getenv("COMMAND_LINE"), "\\\x27"), $tpl);
$tpl = str_replace("__CLEANUP_MARKER__", getenv("CLEANUP_EXTRA"), $tpl);
file_put_contents($argv[2], $tpl);
' "$TEMPLATE_PATH" "$ONESHOT_LOCAL_PATH"

php -l "$ONESHOT_LOCAL_PATH" >/dev/null

ONESHOT_URL="${PROD_DOMAIN}/${ONESHOT_FILENAME}?token=${TOKEN}"

echo "════════════════════════════════════════════════════════════════════"
echo " Runner prod local - ${ARTISAN_COMMAND}"
echo "════════════════════════════════════════════════════════════════════"
echo
echo "Ligne artisan exécutée en prod (identique à un \`php artisan ...\` tapé en SSH) :"
echo "  $COMMAND_LINE"
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
echo "  curl -sS -o /dev/null -w '%{http_code}\n' '${ONESHOT_URL}'"
echo
echo "Dossier scratch local (jamais commité, jamais déployé) : $RUN_DIR"
