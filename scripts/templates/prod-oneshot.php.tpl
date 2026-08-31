<?php

declare(strict_types=1);

/**
 * Runner HTTP de production, généré par scripts/prod-artisan.sh - JAMAIS commité, JAMAIS déployé
 * par le pipeline CI (le rsync de déploiement exclut déjà `/public/_*.php`). Contourne
 * l'indisponibilité du terminal cPanel et du gestionnaire de fichiers, et le mutisme de `tinker`
 * via SSH sur ce compte (docs/CONTRAINTES-SOUS-AGENTS.md, section 5).
 *
 * Contrairement à un one-shot qui s'efface après un seul appel, ce runner reste en service
 * jusqu'à DUREE_DE_VIE_SECONDES après son dépôt : un cycle complet enchaîne plusieurs commandes
 * (news:brief, puis news:source, puis news:apply...) et redéposer un fichier par commande ne
 * sécurise rien de plus, ça multiplie seulement les transferts (mesuré le 2026-08-23). Amorce
 * Laravel UNE fois, puis exécute la commande demandée en paramètre GET `cmd` - contre la liste
 * blanche COMMANDES_AUTORISEES ci-dessous, un jeton valide ne suffit pas à tout permettre - avec
 * ses arguments/options en JSON dans le paramètre `args` (une option porte son préfixe `--` DANS
 * la clé, ex. `{"article":"38306","--publish":true}`), chaque clé revalidée contre
 * ARGUMENTS_AUTORISES de SA commande : jeton valide et commande autorisée ne suffisent pas non
 * plus à tout permettre, une clé non déclarée est un refus pur et simple (403), jamais un
 * filtrage silencieux qui continuerait avec le sous-ensemble reconnu. Écrit sa sortie telle
 * quelle dans la réponse HTTP.
 *
 * Auto-suppression, dans cet ordre STRICT : (1) expiration testée EN TOUT PREMIER, avant même la
 * lecture du jeton - un jeton perdu ou une commande mal formée ne doit JAMAIS rendre ce fichier
 * ineffaçable, puisque cpanel_file_delete est hors service sur ce compte et qu'aucune autre porte
 * de secours ne fonctionne (incident du 2026-08-25) ; (2) sur demande explicite, dès qu'un appel
 * authentifié porte `last=1` (fin de cycle annoncée par l'appelant) - reste la norme à chaque
 * cycle, le TTL n'est que le filet de sécurité si elle est omise par erreur.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

// ACTION : durée de vie du runner - 45 minutes, calibrée sur la durée d'un cycle complet.
// MCP: SELF (<5 lignes)
// RAISON: borner dans le TEMPS plutôt qu'à l'usage évite de redéposer un fichier par commande
// (mesuré le 2026-08-23), tout en garantissant une porte de sortie même si personne ne repasse.
const DUREE_DE_VIE_SECONDES = 2700;

// ACTION : expiration vérifiée AVANT toute lecture de $_GET, jeton compris.
// MCP: SELF (<5 lignes)
// RAISON: incident du 2026-08-25 - vérifier le jeton avant l'expiration rend le fichier
// ineffaçable dès que le jeton est perdu, faute de porte de secours cPanel sur ce compte.
if ((time() - filemtime(__FILE__)) > DUREE_DE_VIE_SECONDES) {
    @unlink(__FILE__);
    http_response_code(410);
    echo "expiré\n";

    exit;
}

$expectedToken = '__TOKEN__';
$providedToken = $_GET['t'] ?? null;

if (! is_string($providedToken) || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "jeton invalide ou absent\n";

    exit(1);
}

// Liste blanche stricte : un jeton valide ne suffit pas à tout permettre. La famille /actu2 plus
// news:backfill-auto-tools (mandat #1929, 2026-08-31) - rattrapage BORNÉ et rejouable des outils
// liés manquants, jamais une commande destructive : dry-run mesure sans écrire, hors dry-run
// attachAuto() ne fait qu'ajouter, ne touche jamais une sélection manuelle existante.
// blog:verify (2026-08-31, module « vérification » étendu au blogue) : même doctrine que
// news:apply/fact_check - jamais d'Eloquent/SQL direct par l'agent, seule cette porte bornée
// pose/corrige/retire une vérification, et son propre contrat de validation (exclusivité
// verdict/inconclusive, vocabulaire fermé) vit entièrement dans Modules\Blog\Console\
// ArticleVerifyCommand, jamais recopié ici.
const COMMANDES_AUTORISEES = ['news:brief', 'news:source', 'news:apply', 'news:create-draft', 'news:backfill-auto-tools', 'blog:verify'];

// ACTION : liste blanche des arguments/options, CETTE FOIS PAR COMMANDE - une commande autorisée
// ne suffit pas non plus à tout permettre. Reflète exactement le $signature de chaque classe sous
// Modules/News/app/Console/ (une option porte son préfixe `--` dans la clé, comme dans $args plus
// bas). Brancher une commande hors /actu2 (ex. un traitement ponctuel) exige SA ligne ICI en plus
// de COMMANDES_AUTORISEES - une commande présente sans son entrée ci-dessous est refusée en bloc,
// jamais exécutée sans contrat déclaré.
// MCP: SELF (<5 lignes utiles, le reste est la liste elle-même)
// RAISON: le JSON `args` est arbitraire depuis la requête - sans cette liste, un mode forcé ou une
// sélection explicite d'identifiants restent constructibles depuis l'URL pour n'IMPORTE QUELLE
// commande dès qu'elle est dans COMMANDES_AUTORISEES (mesuré : un runner généré depuis ce même
// gabarit pour news:regenerate-fallback-images laissait passer --force/--ids sans que rien dans ce
// fichier ne les nomme).
const ARGUMENTS_AUTORISES = [
    'news:brief' => ['article'],
    'news:source' => ['article', 'url', '--replace'],
    'news:apply' => ['article', '--payload', '--image', '--credit', '--publish', '--enrich'],
    'news:create-draft' => ['url', '--title'],
    // Trois options, aucun argument positionnel - purement des réglages de mesure/rattrapage,
    // jamais un identifiant d'article ni un contenu (mandat #1929).
    'news:backfill-auto-tools' => ['--limit', '--dry-run', '--echantillon'],
    // Un seul argument positionnel (id d'article de blogue) plus --payload, jumeau exact de
    // news:apply pour ce besoin - le contrat fin (clé verification, sous-clés autorisées) reste
    // dans ArticleVerifyCommand, cette ligne ne fait qu'autoriser l'appel de la commande.
    'blog:verify' => ['article', '--payload'],
];

$commande = $_GET['cmd'] ?? null;

if (! is_string($commande) || ! in_array($commande, COMMANDES_AUTORISEES, true)) {
    http_response_code(403);
    echo "commande hors liste blanche\n";

    exit(1);
}

// Décodé et validé AVANT d'amorcer Laravel : une clé hors contrat n'a pas besoin du framework pour
// être rejetée - même philosophie que l'expiration testée avant le jeton plus haut.
$args = json_decode(is_string($_GET['args'] ?? null) ? $_GET['args'] : '{}', true);
$args = is_array($args) ? $args : [];

$argumentsAutorisesCommande = ARGUMENTS_AUTORISES[$commande] ?? null;

if ($argumentsAutorisesCommande === null) {
    // Commande listée dans COMMANDES_AUTORISEES mais oubliée ci-dessus : bogue de configuration de
    // CE fichier, pas une tentative d'abus - refusée quand même, jamais exécutée sans contrat.
    http_response_code(500);
    echo "configuration incomplète : aucun ARGUMENTS_AUTORISES pour cette commande\n";

    exit(1);
}

foreach (array_keys($args) as $cle) {
    if (! in_array($cle, $argumentsAutorisesCommande, true)) {
        http_response_code(403);
        echo "argument hors liste blanche : {$cle}\n";

        exit(1);
    }
}

// ACTION : plafond d'exécution PHP relevé à 120 s (mesuré le 2026-08-31 : le défaut de l'hôte
// coupe vers 30 s, ce qui tronque net un lot de plus d'une centaine de fiches sans laisser la
// moindre sortie - même le décompte final, écrit seulement APRÈS la boucle, disparaît avec la
// coupure). Sans effet si la fonction est désactivée côté hébergeur (silencieux, jamais fatal).
// MCP: SELF (<5 lignes)
// RAISON: garde-fou du mandat #1929 - mesurer avant d'écrire suppose un budget de temps qui
// laisse la commande RENDRE SON VERDICT, pas seulement s'exécuter.
@set_time_limit(120);

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// `{{STORAGE}}` dans une valeur d'argument ne peut être résolu qu'ICI, une fois Laravel amorcé
// (storage_path() n'existe pas avant) - remplacé dans chaque valeur texte de $args, déjà validées
// ci-dessus.
$storage = storage_path('app/oneshot-uploads');
foreach ($args as $cle => $valeur) {
    if (is_string($valeur)) {
        $args[$cle] = str_replace('{{STORAGE}}', $storage, $valeur);
    }
}

header('Content-Type: text/plain; charset=utf-8');

try {
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $exitCode = $kernel->call($commande, $args, $output);
    echo $output->fetch();
    if ($exitCode !== 0) {
        echo "\n[EXIT_CODE] {$exitCode}\n";
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'ERREUR : '.$e->getMessage()."\n";
}

// ACTION : nettoyage des fichiers --payload/--image consommés par CET appel, qu'il ait réussi ou
// non - ne pas attendre l'expiration du runner pour effacer une charge utile déjà consommée.
// MCP: SELF (<5 lignes)
// RAISON: garde-fou du mandat, "tout fichier temporaire retiré immédiatement après usage".
foreach ($args as $valeur) {
    if (is_string($valeur) && str_starts_with($valeur, $storage.'/') && is_file($valeur)) {
        @unlink($valeur);
    }
}

// Auto-suppression sur demande explicite (fin de cycle) - le TTL ci-dessus reste le filet de
// sécurité si l'appelant omet last=1 par erreur.
if (($_GET['last'] ?? null) === '1') {
    @unlink(__FILE__);
}
