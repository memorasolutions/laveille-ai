# CONCLUSION (vérif WHM root 2026-05-30 12:02) — RIEN À SUPPRIMER
Le crontab réel de gmemora (146 lignes, vu en root via `crontab -l -u gmemora`) ne contient
AUCUN cron « Command: ? ». Le grep ciblé `^([^[:space:]]+[[:space:]]+){5}\?[[:space:]]*$`
retourne 0 ligne. → L'« orphelin ? » affiché par l'API cPanel (cpanel_cron_list / interface)
était un ARTEFACT de parsing (lignes `SHELL="/bin/bash"` et lignes vides insérées par cPanel
entre chaque cron). Aucun cron parasite réel. Crontab sain.
Backup root : /root/crontab-gmemora-20260530-120258.bak (146 lignes).
VÉRIFIÉ 2026-05-30 12:xx (WHM root) : la « coquille ligne 49 /dev/null2>&1 » N'EXISTE PAS.
`crontab -l -u gmemora | grep -cF '/dev/null2>&1'` = 0 ; grep -E '/dev/null[0-9&>]' = 0 ;
aucun fichier ~/null* . C'était un artefact d'affichage/wrap de la sortie `nl` (l'espace entre
« null » et « 2>&1 » tombe au copier-coller). La ligne réelle est correcte « >> /dev/null 2>&1 ».
=> AUCUNE correction de cron nécessaire. Crontab gmemora 100% sain (146 lignes, que des crons légitimes).

---
# Backup crons supprimés — 2026-05-30 (laveille.ai / compte cPanel gmemora)

Sauvegarde AVANT suppression (retour arrière possible via `cpanel_cron_add`).
Demande user : supprimer l'orphelin [69] + les 2 crons one-shot du 30 mai. Vérifiés sûrs.

## Orphelin `?` — NON SUPPRIMABLE VIA L'API (action manuelle requise)
- schedule : `* * * * *`
- command : `?`
- Note : ligne de crontab MALFORMÉE. cpanel_cron_remove exige un linekey (grand nombre hashé) ; cette ligne n'affiche que « Line 68/69 » = numéro de ligne, pas un linekey → l'API répond « Cron job not found ». C'est pourquoi cet orphelin persiste depuis plusieurs sessions.
- SUPPRESSION MANUELLE (seule option) : cPanel → Tâches Cron (Cron Jobs) → repérer la ligne dont la commande est « ? » → Supprimer. Inoffensif (commande vide qui échoue chaque minute).
- Ne PAS recréer.

## Cron [67/68] one-shot autossl _v51_test / _v6_test (tests SSL tiers, 30 mai)
- Les 2 que l'user visait (rm _s194 + _autossl_recap_v51_test) ont DISPARU d'eux-mêmes (déjà nettoyés).
- Un NOUVEAU est apparu pendant la session : `59 11 30 5 * ... _autossl_recap.php > _autossl_recap_v6_test.log` (linekey 4092374433) — créé par un processus TIERS actif (debug SSL versionné). NON SUPPRIMÉ volontairement : un autre processus travaille dessus ; one-shot daté 30 mai (ne tourne qu'une fois). Recréation inutile.

## Cron [67] — nettoyage one-shot session S194 (déjà obsolète)
- linekey : `1254105244`
- schedule : `46 11 30 5 *`  (one-shot 30 mai 11:46)
- command : `rm -f /home/gmemora/_s194_v2.log /home/gmemora/_validate_btnclose.php /home/gmemora/_validate_btnclose.log /home/gmemora/_s194_clear.log`
- Recréation :
  `cpanel_cron_add minute=46 hour=11 day=30 month=5 weekday=* command="rm -f /home/gmemora/_s194_v2.log /home/gmemora/_validate_btnclose.php /home/gmemora/_validate_btnclose.log /home/gmemora/_s194_clear.log"`

## Cron [68] — test one-shot autossl (30 mai)
- linekey : `1694294294`
- schedule : `52 11 30 5 *`  (one-shot 30 mai 11:52)
- command : `/opt/cpanel/ea-php84/root/usr/bin/php /home/gmemora/_autossl_recap.php > /home/gmemora/_autossl_recap_v51_test.log 2>&1`
- Recréation :
  `cpanel_cron_add minute=52 hour=11 day=30 month=5 weekday=* command="/opt/cpanel/ea-php84/root/usr/bin/php /home/gmemora/_autossl_recap.php > /home/gmemora/_autossl_recap_v51_test.log 2>&1"`
- NB : le cron RÉCURRENT de monitoring SSL [65] (`0 7 * * * _autossl_recap.php`) est CONSERVÉ (critique, ne pas toucher). [68] n'était qu'un test ponctuel.
