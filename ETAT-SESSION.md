# État de session - la-veille-de-stef-v2

> Réécrit le **2026-09-24 à 19h28 Québec (23:28 UTC)**, sur demande de pause avant redémarrage
> du Mac. Ce fichier est TOUJOURS le même : on le réécrit, on n'empile pas.

---

## 🔴 Mise en pause : ce qui est fermé

| Élément | État vérifié |
|---|---|
| Navigateur Playwright | **fermé** (`browser_close`, aucun onglet restant) |
| Sous-agents | **aucun lancé** dans ce segment de session |
| Cron temporaire `2780396691` (passerelle artisan prod) | **RETIRÉ et vérifié** par un `cron_list` frais : il n'apparaît plus |
| Script `storage/app/a2_runner.sh` en prod | **neutralisé** (`exit 0`), contenu relu après écriture |
| `storage/app/a2_cmd.sh` en prod | **vidé** |
| Écriture en cours en prod | **aucune** |

### ⚠️ Correction d'une affirmation fausse faite plus tôt aujourd'hui

La tâche **#2762** était cochée « FAIT ET PROUVÉ - cron retiré et fichiers a2_* neutralisés ».
**C'était faux sur les deux points** : à 19h25 Québec (23:25 UTC), le cron était toujours dans la
liste et le script `a2_runner.sh` était intact et actif. Les deux ont été réellement fermés
maintenant, et vérifiés par relecture. Le mécanisme d'auto-expiration du script aurait fini par le
neutraliser, mais il n'était pas déclenché : l'auto-expiration n'est pas une preuve de retrait.

---

## ✅ Ce qui est terminé et prouvé (fin de journée)

- **Règle des oracles sur les images sociales** : chaînée dans `/publier`, imposée par le hook
  `guard-publication-sociale.py` (contrôle 5), outil de trace `tracer_oracle.py`. Prouvée par un
  appel réel bloqué.
- **Mémoire du projet** : `MEMORY.md` ramené de 26 840 à 21 154 octets, sous la limite; elle
  perdait ses 7 dernières lignes en silence.
- **Fiches publiées ce jour** : 57539, 58294, 58216 (+ 2 doublons écartés avec mesure : 57924, 57693).

---

## 🔄 En cours - LE POINT DE REPRISE EXACT

### #2786 - Recréer la publication Facebook de l'anonymiseur

La 343 est **déjà supprimée** (404 confirmé). La 341 (LinkedIn, carrousel PDF) est **conservée**,
programmée au 2026-09-25 à 11h00 Québec (15:00 UTC), média téléchargé sans erreur.

**Où j'en suis, précisément :**

1. ✅ Image vérifiée : `https://laveille.ai/images/social/anonymiseur-caricature-2026-09.jpg`
   - servie en 200, sans redirection, 231 056 octets, 1080x1350 (4:5)
   - empreinte SHA-256 identique entre le fichier servi, la copie locale et celle du projet
   - passe le contrôle mécanique `visuel.py`
2. ✅ **Oracle 1 sur 2 obtenu** : claude.ai (navigateur, compte du fondateur), description en
   aveugle consignée VERBATIM dans
   `~/.claude/skills/publier/controles/traces/25f7d40168027dbc.json`
   (copie dans `storage/app/travaux-session-2026-09-24/`).
3. ⛔ **Oracle 2 manquant** → le hook BLOQUERA `create_social_publication` tant qu'il n'y en a pas
   deux distincts. C'est le comportement voulu, pas une panne.

**🔎 CE QUE L'ORACLE 1 A TROUVÉ, et qui doit être tranché avant de publier :**

> L'image est **AMBIGUË** entre « anonymiser pour protéger » (lecture positive) et « camoufler,
> faire disparaître des preuves » (lecture négative). Selon l'oracle, trois éléments font pencher
> vers la lecture NÉGATIVE : la joie excessive du personnage, les taches de noir sur ses vêtements,
> et le fait qu'on ne voie **jamais** ce qui est effacé. Verbatim : *« quelqu'un qui efface des
> données avec un peu trop d'enthousiasme », sans savoir s'il faut s'en réjouir ou s'en inquiéter.*

C'est exactement le type de défaut que la règle des oracles existe pour attraper, et il n'aurait
pas été vu autrement : j'avais regardé cette image seul et je l'avais trouvée conforme.

**À faire à la reprise, dans cet ordre :**
1. Obtenir l'**oracle 2** (chatgpt.com au navigateur - jamais par Codex ni `agy`, règle du
   2026-09-19). Méthode qui a marché sur claude.ai : ouvrir le menu « Ajouter des fichiers »,
   puis `page.setInputFiles('input[type=file]', <chemin>)` - le `browser_file_upload` du MCP exige
   un état modal qu'on n'obtient pas. Le menu ouvert intercepte les clics : faire `Escape` avant
   de taper.
2. **Trancher l'ambiguïté** : soit le texte lève le doute dès la première ligne, soit l'image est
   refaite. Ne pas publier en espérant que le lecteur choisisse la bonne lecture.
3. Composer le texte Facebook (3 mouvements, max 3 lignes rendues par bloc, aucune adresse web
   dans le corps, lien en `first_comment`, champ `title` obligatoire), passer `crochet.py
   --reseau facebook` et `aeration.py`.
4. `create_social_publication` en `dry_run: true`, **relire l'heure stockée** (le portail a déjà
   stocké 4 h trop tôt - #2722), puis `dry_run: false` et relire avec `get_social_publication`.

---

## ⛔ Ce qui BLOQUE en attendant une action de Stéphane

| # | Ce qui est attendu |
|---|---|
| **#2735** | Supprimer les publications **334 à 342** dans le portail. Garder la **341**. L'API n'a aucune route DELETE : ce geste ne peut être fait que par toi. |
| **#2759** | Publier les **parties 2 et 3** de la série dans l'admin (la double authentification me bloque). |
| **#2585** | Approuver la publication **300**, refuser la **298**. L'heure prévue est passée, rien n'est parti. |
| **#2368** | Clé secrète Turnstile (elle te revient, règle 1Password). |

---

## 📋 Ce qui reste en file (aucun travail entamé dessus)

**16 cycles `/actu2`** : #2763, 2764, 2765, 2766, 2767, 2768, 2769, 2770, 2771, 2773, 2774, 2776,
2777, 2778, 2779, 2781, 2783, 2784.
Un piège connu : **#2780** (Le Monde) a une source VIDE derrière un mur d'abonnement - il faut
passer par l'étude elle-même, pas par le relais.

Le contrat de délégation est écrit une seule fois dans
`storage/app/travaux-session-2026-09-24/CONTRAT-COMPOSITION-ACTU2.md` : le donner par son chemin
aux agents, ne jamais recopier ses consignes dans les prompts.

⚠️ **La passerelle d'exécution artisan en production est fermée.** Toute reprise de `/actu2` qui
doit écrire en prod devra la reconstruire volontairement (cron + `a2_runner.sh`), et la refermer
ensuite - en le VÉRIFIANT, pas en le supposant.
