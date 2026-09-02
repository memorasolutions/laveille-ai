# Mac 2 - deux correctifs de sauvegarde prêts à appliquer (ticket #2171)

**État : rédigés et vérifiés en lecture, NON appliqués.** Le verrou `MAC2_READ_ONLY=true` bloque
l'écriture sur le Mac 2 (mesuré le 2026-09-01 : `mac2_exec` refusé, `mac2_read_file` et
`mac2_list_dir` fonctionnent). Les deux correctifs ci-dessous sont donc écrits, prêts à coller,
et attendent uniquement la levée du verrou.

Fichier cible : `/Users/memora/scripts/backup-cpanel-nightly.sh` sur le Mac 2 (662 lignes,
lancé par `com.memora.cpanel-backup.plist` à 02h30 Québec).

**Convention du fichier cible : les commentaires y sont écrits SANS accents.** Les blocs
ci-dessous la respectent, volontairement - c'est l'usage établi de ce script, pas un oubli.

---

## Préalable obligatoire avant toute application

Vérifier qu'aucune exécution n'est en cours. Un run du 28 au 31 août a tenu le verrou
**84,5 heures** et absorbé quatre nuits en silence (ticket #2083). Modifier le script pendant
qu'il tourne produirait un comportement mixte, moitié ancien code, moitié nouveau.

```bash
ps aux | grep -E "backup-cpanel-nightly|rsync" | grep -v grep
ls -la /tmp/cpanel-backup.lock
tail -3 ~/logs/cpanel-backup-heartbeat.log
```

Et faire une copie de sauvegarde avant d'écrire, comme le fait déjà l'historique du dossier :

```bash
cp ~/scripts/backup-cpanel-nightly.sh ~/scripts/backup-cpanel-nightly.sh.bak-$(date +%Y%m%d)-integrite
```

---

## Correctif 1 - le contrôle d'intégrité ne couvre qu'une base sur toutes

### Ce qui a été mesuré

Le script exécute bien un `gzip -t`, mais à **un seul endroit**, dans le bloc
« LucidNest backup:verify » :

```bash
elif ! gzip -t "$LN_LATEST_LOCAL" 2>/dev/null; then ln_problem="Dump corrompu (gzip -t)"; fi
```

LucidNest bénéficie donc de trois contrôles : âge supérieur à 26 h, taille inférieure à 1 Mo,
et lisibilité réelle de l'archive.

**Aucune autre base n'a le moindre de ces trois contrôles.** Ni les dumps Laravel multi-app
(qui contiennent laveille.ai), ni les autres bases (WordPress, Dolibarr, Moodle), ni le dump
générique. Pour ces trois familles, le script se contente de constater que le `rsync` a rendu
un code de succès - ce qui prouve que des octets sont arrivés, jamais qu'ils sont exploitables.

### Pourquoi c'est le pire type de défaut pour une sauvegarde

Un dump tronqué (coupure SSH en cours de transfert, disque plein, `mysqldump` interrompu)
produit un fichier **présent, de taille plausible, et inutilisable**. Il ne se découvre que le
jour de la restauration, c'est-à-dire le seul jour où il ne reste aucune marge de manoeuvre.
Le fichier rassure jusqu'au moment précis où il devrait sauver.

### Le bloc à insérer

**Emplacement : juste AVANT le bloc `--- RESUME PAR BRIQUE + ALERTE IMMEDIATE ---`**, pour que
le résultat entre dans le résumé et déclenche l'alerte existante. Le placer après le résumé le
rendrait muet.

```bash
# --- CONTROLE D'INTEGRITE DES ARCHIVES DU JOUR (ajoute 2026-09-01, ticket #2171) ---
# Pourquoi : jusqu'ici, SEUL LucidNest passait un gzip -t (bloc "LucidNest backup:verify" plus
# haut). Les dumps Laravel multi-app, Autres DB et generique etaient ecrits sans qu'aucune
# verification ne confirme qu'ils sont seulement lisibles. Un dump tronque produit un fichier
# present, de taille plausible, et INUTILISABLE - defaut qui ne se decouvre que le jour de la
# restauration. Le controle porte sur les archives ECRITES CE JOUR (-mtime -1) : verifier tout
# l'historique a chaque nuit couterait des heures sur ce disque, sans rien apprendre de neuf.
step_begin "Controle d integrite des archives du jour"
integrite_corrompues=()
while IFS= read -r archive; do
    [ -z "$archive" ] && continue
    gzip -t "$archive" 2>/dev/null || integrite_corrompues+=("$(basename "$archive")")
done < <(find "$DEST/db/$TODAY" "$DEST/db/laravel-auto" "$DEST/db/other-auto" \
             -type f -name '*.sql.gz' -mtime -1 2>/dev/null)
integrite_nb=${#integrite_corrompues[@]}
if [ "$integrite_nb" -eq 0 ]; then
    step_end "Controle d integrite des archives du jour" 0
else
    step_end "Controle d integrite des archives du jour" 1
    log ERROR "Archives illisibles (gzip -t): ${integrite_corrompues[*]}"
fi
# --- fin controle d integrite ---
```

Puis **une seule ligne à ajouter** dans la liste `resume_parts`, à la suite des autres :

```bash
[ "$integrite_nb" -gt 0 ] && resume_parts+=("${integrite_nb} archive(s) illisible(s): ${integrite_corrompues[*]}")
```

### Les trois pièges évités, et pourquoi ils comptent

1. **`set -euo pipefail` est actif en tête de script.** Un `gzip -t` qui échoue tuerait le
   script entier au lieu de signaler l'archive. D'où la forme `gzip -t ... || tableau+=(...)`,
   qui neutralise `set -e` pour cette ligne précise. Le script porte déjà la trace d'un incident
   de ce type : un commentaire de la section LucidNest raconte qu'une substitution de commande
   non protégée faisait mourir le script **avant même** d'atteindre le bloc d'alerte.
2. **Boucle par `while read` et non `for $(find)`.** Un nom de fichier contenant une espace
   casserait la seconde. Le script voisin de la clinique manipule justement un chemin distant
   avec des espaces et des accents.
3. **`-mtime -1` borne le contrôle au jour même.** Sans cette borne, chaque nuit revérifierait
   trente jours d'archives, sur un disque où un simple `du -sh` a déjà pris jusqu'à quatre
   heures. Le coût grimperait sans rien apprendre de neuf.

---

## Correctif 2 - rien ne dit jamais qu'un run s'est TERMINÉ

### Ce qui a été mesuré

La ligne 3 du script écrit un battement de coeur :

```bash
echo "$(date "+%Y-%m-%d %H:%M:%S") declenchement pid=$$" >> "$HOME/logs/cpanel-backup-heartbeat.log"
```

Le mot est exact et le défaut est là : il enregistre le **déclenchement**, jamais l'**issue**.
Un run parti puis bloqué est donc, vu de l'extérieur, strictement indistinguable d'un run sain.

C'est précisément ce qui a permis au run du 28 août de tenir le verrou **84,5 heures** en
absorbant quatre nuits, sans que rien ne l'annonce : `launchd` ne relance pas un travail dont
l'exécution précédente n'est pas finie, donc l'absence de nouvelle ligne ressemblait au silence
d'un système au repos plutôt qu'au symptôme d'un blocage.

Le script voisin `clinique-alexandre-blouin-backup/backup.sh` a déjà résolu ce problème avec un
fichier `last_status.txt`. Le script principal ne l'a pas.

### Le bloc à insérer

**Emplacement : à la toute fin, après le calcul de `SIZE` et `DUR`, avant `exit $exit_code`.**

```bash
# --- TEMOIN DE FIN (ajoute 2026-09-01, ticket #2171) ---
# Pourquoi : le battement de coeur de la ligne 3 enregistre le DECLENCHEMENT, jamais l ISSUE.
# Un run parti puis bloque est donc indistinguable d un run sain, vu de l exterieur - c est ce
# qui a laisse un run tenir le verrou 84,5 h du 28 au 31 aout 2026 en absorbant quatre nuits en
# silence. Ce fichier, lisible d un coup d oeil, dit toujours QUAND le dernier run s est
# TERMINE et AVEC QUEL resultat. Modele : ~/clinique-alexandre-blouin-backup/last_status.txt.
printf '%s resultat=%s code=%s duree=%ss taille=%s\n' \
    "$(date '+%Y-%m-%d %H:%M:%S')" "$resume" "$exit_code" "$DUR" "$SIZE" \
    > "$HOME/logs/cpanel-backup-last-status.txt"
# --- fin temoin de fin ---
```

### Ce que ce témoin permet, et sa limite honnête

**Il permet** de répondre en une seconde à « la sauvegarde a-t-elle fini cette nuit, et bien
fini ? ». Un fichier daté d'il y a trois jours sur un travail quotidien est un signal immédiat,
là où il fallait auparavant croiser des journaux et inspecter des processus.

**Sa limite, qu'il faut dire** : il n'alerte personne tout seul. Un run bloqué laisse ce fichier
figé, mais encore faut-il aller le lire. Le rendre actif demanderait un mécanisme distinct - un
contrôle indépendant qui s'inquiète de sa vieillesse - et c'est un chantier séparé, pas un
correctif de dix lignes. **Ne pas confondre les deux : ce correctif rend le problème VISIBLE, il
ne le rend pas BRUYANT.**

---

## Vérification après application

```bash
bash -n ~/scripts/backup-cpanel-nightly.sh   # controle de syntaxe, sans rien executer
```

Puis, au lendemain de la première nuit :

```bash
cat ~/logs/cpanel-backup-last-status.txt                       # doit dater de la nuit meme
grep "integrite" ~/logs/cpanel-backup-$(date +%Y%m%d).log      # doit montrer l etape et son issue
```

**Le contrôle qui compte vraiment**, et qu'aucune lecture de journal ne remplace : provoquer
délibérément une archive corrompue dans un dossier de test et vérifier que le résumé la nomme.
Un contrôle d'intégrité qui n'a jamais rien attrapé n'est pas prouvé - il est seulement silencieux,
ce qui ressemble beaucoup trop à ce qu'on cherche justement à corriger.
