# Prompt à transmettre - connecteur MCP Facebook, métriques périmées

> **Pourquoi ce fichier existe** : le connecteur vit dans
> `/Users/stephanelapointe/__IA__/_____SERVEUR_____/mcp_skills/mcp_facebook_social/server.py`,
> qui est **hors du projet laveille.ai**. Règle du fondateur du 2026-09-21 : on n'écrit pas dans
> un autre projet, on transmet un prompt. Le diagnostic et les mesures ci-dessous ont été faits
> depuis la session laveille.ai le 2026-09-21 ; il ne reste qu'à appliquer le correctif là-bas.
>
> Ticket de suivi côté laveille.ai : #2638.

---

## LE PROMPT À COLLER (tout ce qui suit)

Tu travailles sur le serveur MCP `memora-social-fb`, fichier
`~/__IA__/_____SERVEUR_____/mcp_skills/mcp_facebook_social/server.py`.

### Le défaut

L'outil `fb_get_page_insights` porte des valeurs par défaut **mortes** :
`metric="page_impressions,page_engaged_users"`. Meta a retiré ces métriques de l'API Graph.
Conséquence : l'appel le plus courant échoue avec
`(#100) The value must be a valid insights metric`, et **le message d'erreur ne dit pas quelle
métrique est fautive**. Comme l'API rejette la requête ENTIÈRE dès qu'une seule métrique de la
liste est invalide, une métrique morte en masque neuf bonnes.

### Ce qui a été MESURÉ le 2026-09-21 sur la page « La veille AI » (identifiant 309399956546897), API v25.0

Chaque métrique a été appelée réellement, en période `day`, et isolée une par une quand un lot
échouait. Ce ne sont pas des suppositions.

**ACCEPTÉES (10) :**

| Métrique | Ce qu'elle renvoie |
|---|---|
| `page_media_view` | vues du contenu de la Page (successeur de `page_impressions`) |
| `page_total_media_view_unique` | personnes uniques ayant vu le contenu (successeur de `page_impressions_unique`) |
| `page_post_engagements` | interactions avec les publications |
| `page_total_actions` | clics sur les coordonnées et le bouton d'action |
| `page_views_total` | visites de la Page elle-même |
| `page_follows` | total d'abonnés (cumul des abonnements moins les désabonnements) |
| `page_daily_follows` | nouveaux abonnements du jour (compte total) |
| `page_daily_follows_unique` | nouveaux abonnements du jour (comptes uniques) |
| `page_daily_unfollows_unique` | désabonnements du jour (comptes uniques) |
| `page_video_views` | vues vidéo de plus de 3 secondes |

**REFUSÉES (6), toutes avec le même `(#100)` :**
`page_impressions`, `page_engaged_users`, `page_impressions_unique`, `page_fans`,
`page_negative_feedback`, `page_cta_clicks_logged_in_total`.

⚠️ **Nuance à ne pas effacer** : « refusée » signifie ici *refusée sur CETTE page, avec CE jeton,
en période `day`, le 2026-09-21*. Ce n'est pas la preuve qu'elle est retirée de l'API pour tout le
monde : une permission manquante ou une période non supportée produirait exactement le même signal.
Ne pas écrire « Meta a retiré X » sur la seule foi de ce 400.

⚠️ **Et surtout** : `page_negative_feedback` et `page_cta_clicks_logged_in_total` sont
**recommandées par la documentation de migration** consultée le même jour, et elles échouent quand
même. C'est la raison pour laquelle cette liste a été mesurée au lieu d'être recopiée. Si tu
ajoutes une métrique, **appelle-la avant de l'inscrire en valeur par défaut.**

### Les correctifs demandés, dans cet ordre

**1. Remplacer les valeurs par défaut mortes.**
La valeur par défaut de `metric` devient :
`page_media_view,page_total_media_view_unique,page_post_engagements,page_follows`
Ces quatre répondent à la question utile : combien de vues, combien de personnes, combien
d'interactions, combien d'abonnés. Elles sont toutes mesurées vivantes.

**2. Rendre l'échec diagnosticable, c'est le correctif le plus important.**
Aujourd'hui, une métrique morte fait échouer tout l'appel sans dire laquelle. Quand l'API renvoie
le code 100, **réessayer métrique par métrique** et renvoyer un résultat partiel de la forme :

```json
{
  "data": [ "... les métriques qui ont répondu ..." ],
  "metriques_refusees": ["page_engaged_users"],
  "note": "Ces métriques ont été rejetées par l'API ; les autres sont dans data."
}
```

Un utilisateur doit obtenir ses neuf bonnes métriques même si la dixième est morte, et savoir
laquelle retirer. Ne fais ce deuxième tour **que** sur le code 100, jamais sur une erreur de jeton
ou de permission : sinon une panne d'authentification déclenche dix appels inutiles.

**3. Exposer l'engagement PAR PUBLICATION.**
`fb_list_posts` ne renvoie pas les compteurs, et c'est précisément la donnée qui manque pour juger
de l'effet d'une façon d'écrire. Ajouter aux champs demandés :
`likes.summary(true),comments.summary(true),shares`
puis exposer dans la réponse les trois totaux (`likes.summary.total_count`,
`comments.summary.total_count`, `shares.count`, ce dernier étant absent quand il vaut zéro - le
traiter comme 0, jamais comme une erreur).
Si tu ajoutes un outil d'insights par publication, sache que les métriques de publication ont subi
la même migration : `post_impressions` vers `post_media_view`,
`post_impressions_unique` vers `post_total_media_views_unique`, et `post_engaged_users` n'a pas de
remplaçant direct. **Mesure-les avant de les câbler**, comme ci-dessus.

**4. Vérifier le jeton dans les URL de pagination (ticket de sécurité #2565).**
Le ticket dit que ce connecteur renvoyait le jeton d'accès de la Page **en clair** dans ses
réponses. Dans les six appels faits le 2026-09-21, les URL `paging.previous` et `paging.next` de
`fb_get_page_insights` ne contenaient **aucun** `access_token`. Deux lectures sont possibles, et je
ne sais pas laquelle est la bonne : soit c'est déjà corrigé, soit le défaut est ailleurs (par
exemple dans `fb_list_posts` ou `fb_get_page_info`). **À vérifier dans le code, pas par essai** -
et surtout **ne PAS appeler `fb_get_page_info` pour le savoir** : c'est justement l'outil signalé
comme renvoyant le jeton en clair, et l'appeler ferait transiter le secret dans une conversation.
Règle qui s'applique : un secret ne doit jamais sortir d'un outil ; s'il sort, on filtre la sortie
à la source.

### Contraintes MEMORA à respecter

- Code attribué à **MEMORA solutions** (`info@memora.ca`, `https://memora.solutions`).
  **Aucune mention de Claude, d'Anthropic ou d'IA** dans le code, les commentaires ou les commits.
- Commentaires en français, tous les accents présents, **jamais de tiret cadratin** (utiliser `-`).
- Convention par action : `# ACTION:` puis `# RAISON:`.
- Zéro casse : le connecteur sert **26 pages clientes**, pas seulement laveille.ai. Un changement
  de valeur par défaut ne doit casser aucun appel existant qui passe une métrique explicite.
- Sauvegarde du fichier avant modification (retour en arrière rapide).
- Un redémarrage de Claude Code est nécessaire pour que le serveur MCP recharge son code.

### La donnée qui motive tout cela, pour le contexte

Mesure du 2026-09-21 sur la page « La veille AI », 511 abonnés :

| Jour | Vues du contenu | Personnes uniques | Interactions | Visites de la Page |
|---|---|---|---|---|
| 19 septembre | 14 | 4 | 0 | 0 |
| 20 septembre | 1 | 1 | 0 | 0 |

Ce n'est donc pas « l'écriture ne suscite pas d'interaction ». **Personne ne voit les
publications.** Zéro interaction sur quatre personnes atteintes n'est pas un échec éditorial,
c'est une absence de diffusion. Sans le correctif ci-dessus, cette distinction restait invisible.
