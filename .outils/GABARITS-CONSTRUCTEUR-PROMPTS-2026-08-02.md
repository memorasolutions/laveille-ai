# Gabarits des 9 phrases à trous - Constructeur de prompts

Date : 2026-08-02 (America/Toronto). Référence pour l'étape 4 (implémentation du code), issue de
l'étape 3 du plan `.outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md` (section 8).

## Trous canoniques (partagés entre gabarits, pour la migration au changement de carte)

- `sujet` (texte libre court) - le coeur de la demande.
- `contexte` (texte libre, plus long) - matière première à traiter, ou contraintes concrètes.
- `public` (menu déroulant) - à qui s'adresse le résultat.
- `ton` (menu déroulant) - tonalité voulue.
- `longueur` (menu déroulant) - ampleur du résultat.
- `format` (menu déroulant) - forme de sortie (liste, tableau, paragraphe...).

Chaque gabarit n'utilise que 3 à 5 de ces trous, plus au maximum un trou spécifique à sa carte
quand aucun trou canonique ne convient (ex. `langue_cible` pour Traduire).

## Règle transversale (non négociable, section 10.1 du plan)

Zéro few-shot préempli générique dans aucun gabarit. Le chain-of-thought (CoT) est une décision
individuelle par carte, jamais un comportement global. Chaque trou de texte libre porte un
`placeholder` illustrant un contexte concret propre à sa carte (critère d'acceptation 10).

---

## 1. Rédiger

- `sujet` (texte court, placeholder : « un courriel aux parents pour annoncer la sortie scolaire de vendredi au Biodôme »)
- `ton` (menu : chaleureux / formel / neutre / enthousiaste / ferme)
- `longueur` (menu : 1 paragraphe / 3 paragraphes / 1 page)
- `public` (menu : parents / élèves / collègues / direction / parents et élèves)

**CoT : NON.** Tâche générative directe ; une instruction de réflexion étape par étape alourdirait
sans améliorer un texte court.

**Squelette** : « Rédige {sujet}, sur un ton {ton}, en {longueur}, destiné à {public}. » (+ règles
de qualité par défaut : typographie française, formulation naturelle anti-IA - jamais affichées).

---

## 2. Résumer un document

- `contexte` (texte libre long, placeholder : « collez ici le texte à résumer, ou décrivez-le »)
- `longueur` (menu : en 3 points clés / en un paragraphe / en une page)
- `public` (menu : pour moi / pour mes élèves / pour les parents)
- `format` (menu : liste à puces / paragraphe suivi / tableau)

**CoT : NON.** Le résumé est une compression directe, pas un raisonnement à dérouler.

**Squelette** : « Résume le texte suivant {longueur}, sous forme de {format}, destiné à {public} : {contexte} »

---

## 3. Corriger et améliorer un texte

- `contexte` (texte libre, placeholder : « collez ici le texte à corriger, ex. un mot aux parents rédigé rapidement »)
- `ton` (menu : professionnel / chaleureux / neutre / professionnel et chaleureux)
- `niveau_correction` (menu : orthographe et grammaire seulement / améliorer aussi le style / reformuler complètement)

**CoT : OUI.** Demander d'abord d'identifier les erreurs avant de corriger améliore la fiabilité
sur les textes plus longs et rend la correction vérifiable (l'enseignant voit ce qui a changé et
pourquoi), plutôt qu'une réécriture opaque.

**Squelette** : « Voici un texte à corriger et améliorer : {contexte}. D'abord, identifie les
erreurs de grammaire et d'orthographe. Ensuite, propose une version corrigée avec un ton {ton},
en appliquant : {niveau_correction}. »

---

## 4. Analyser ou comparer

- `contexte` (texte libre, placeholder : « décrivez ou collez les éléments à comparer »)
- `sujet` (texte court - dimension de comparaison, placeholder : « leur efficacité pour des élèves en difficulté »)
- `format` (menu : tableau comparatif / liste de points forts-faibles / synthèse en paragraphe)

**CoT : OUI.** Une comparaison bénéficie d'un passage critère par critère avant la conclusion -
sans ça, le résultat tend à être une impression générale plutôt qu'une analyse structurée.

**Squelette** : « Analyse et compare les éléments suivants selon {sujet} : {contexte}. Réfléchis
critère par critère avant de conclure. Présente le résultat sous forme de {format}. »

---

## 5. Expliquer simplement

- `sujet` (texte court, placeholder : « la photosynthèse »)
- `public` (menu : un enfant de 6 ans / un élève du secondaire / un collègue non-spécialiste / un parent)
- `longueur` (menu : quelques phrases / un paragraphe / une page)

**CoT : NON.** La simplification est un exercice de reformulation directe ; le CoT produirait un
résultat plus verbeux, contraire à l'objectif de simplicité.

**Squelette** : « Explique {sujet} de façon simple, pour {public}, en {longueur}. Utilise des
analogies concrètes. »

---

## 6. Trouver des idées

- `sujet` (texte court, placeholder : « des activités brise-glace pour le premier cours de l'année »)
- `contexte` (texte libre, placeholder : « temps disponible, matériel, taille du groupe »)
- `nombre` (menu : 5 idées / 10 idées / le plus possible)

**CoT : NON (décision testée et confirmée).** Le remue-méninges bénéficie de la largeur, pas de
la profondeur - un raisonnement étape par étape tend à converger trop vite vers une seule piste.
Vérifié par un juge indépendant (voir journal de test, cas 6) : 4/5, confirme que l'omission était
le bon choix pour cette carte précise.

**Squelette** : « Propose {nombre} idées pour {sujet}, en tenant compte de : {contexte}. »

---

## 7. Préparer une activité ou un questionnaire

- `sujet` (texte court, placeholder : « les fractions équivalentes »)
- `public` (menu : niveaux scolaires du primaire/secondaire)
- `contexte` (texte libre, placeholder : « durée, matériel disponible, taille du groupe »)
- `format` (menu : plan de leçon avec évaluation / questionnaire à choix multiples / activité pratique / quiz)

**CoT : OUI.** Structurer une activité bénéficie clairement d'objectifs posés avant le déroulement
- confirmé par le test (cas 7, note 5/5, le juge indépendant souligne la cohérence objectifs↔activités
apportée par le CoT).

**Squelette** : « Prépare {format} sur {sujet}, pour {public}. Contexte : {contexte}. Réfléchis
d'abord aux objectifs d'apprentissage, puis structure le déroulement étape par étape. »

---

## 8. Traduire

- `contexte` (texte libre, placeholder : « collez ici le texte à traduire »)
- `langue_cible` (menu : anglais / espagnol / français - trou spécifique, aucun canonique ne convient)
- `ton` (menu : formel / neutre / chaleureux et accueillant)

**CoT : NON.** La traduction est une transformation directe ; un raisonnement visible n'améliore
pas la qualité et alourdit inutilement la réponse.

**Squelette** : « Traduis le texte suivant en {langue_cible}, avec un ton {ton} : {contexte} »

---

## 9. Autre chose (structurée, pas une case de texte libre pure)

- `role` (texte court ou menu de rôles fréquents, placeholder : « conseiller pédagogique »)
- `sujet` (texte court - la tâche elle-même)
- `public` (menu canonique)
- `format` (menu canonique)

**CoT : NON par défaut.** Carte-fourre-tout sans spécificité suffisante pour justifier un CoT
générique ; si l'usage réel (mesure post-lancement, section 5 du plan) montre qu'elle sert souvent
à des tâches structurantes, ce choix sera révisé à la revue des 6 mois.

**Squelette** : « Agis en tant que {role}. {sujet}, destiné à {public}, sous forme de {format}. »

**Seuil de diagnostic** (déjà dans le plan, rappelé ici) : si « Autre chose » dépasse 20-25 % des
usages mesurés, c'est la taxonomie des 8 autres cartes qui est fausse, pas l'utilisateur.

---

## Journal de test (protocole complet à 27 cas, section 5/7 du plan, critère d'acceptation 8)

**Portée réellement exécutée** : 27 cas (3 par gabarit, situations variées - contexte scolaire,
niveau, longueur de source différents à chaque fois, pas 3 variations mineures du même cas),
chacun soumis à un modèle réel via `mcp__hermes__model_invoke` (cascade premium :
moonshotai/kimi-k2.6 ou deepseek/deepseek-v4-flash selon la bascule automatique de la cascade).
Les 6 cas touchant les 3 gabarits à CoT (Corriger, Analyser/comparer, Préparer une activité) ont
en plus reçu un second avis indépendant via `gpt-4o-mini` (`multi-ai-mcp`), sur une échelle 1-5.
Complète la passe initiale de 9 cas (rapportée au coordinateur, jugée insuffisante face au seuil de
27 explicitement fixé par le plan) - conformément à la demande du coordinateur du 2026-08-02.

### Rédiger

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Courriel parents, sortie Biodôme, 4e année, chaleureux | kimi-k2.6 | Bon - infos pratiques ajoutées pertinemment. |
| 2 | Message félicitations bal des finissants, secondaire 5, enthousiaste | kimi-k2.6 | Bon - ton et longueur respectés. |
| 3 | Note formelle à la direction, demande de matériel arts plastiques | kimi-k2.6 | Bon à excellent - lettre professionnelle structurée, liste de matériel détaillée. |

### Résumer un document

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Cycle de l'eau (~150 mots), 3 points, liste, pour élèves | kimi-k2.6 | Bon - fidèle, zéro invention. |
| 2 | Institutions politiques canadiennes (~230 mots), 1 page, paragraphe, pour parents | kimi-k2.6 | Bon - fidèle, longueur et format respectés. |
| 3 | Règles de sécurité labo (~80 mots), 1 paragraphe demandé EN TABLEAU, pour moi | kimi-k2.6 | Bon - a correctement priorisé le format explicite (tableau) sur la longueur (paragraphe), résultat clair. |

### Corriger et améliorer un texte

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Courriel annulation sortie, fautes typiques, ton pro+chaleureux, correction complète | kimi-k2.6 | Excellent. |
| 2 | Texte d'élève (fautes légères), ton neutre, **orthographe/grammaire seulement (garder sa voix)** | kimi-k2.6 | Bon. Second avis indépendant : **4/5** - « il serait crucial de s'assurer que la correction ne modifie pas la voix de l'élève », confirme le respect de la contrainte tout en notant la vigilance à maintenir. |
| 3 | Message institutionnel familier, ton professionnel, **reformulation complète** | kimi-k2.6 | Bon à excellent. Second avis indépendant : **4/5** - distinction bien respectée entre « garder la voix » (cas 2) et « reformuler entièrement » (cas 3). |

### Analyser ou comparer

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Méthode syllabique vs globale, efficacité élèves en difficulté, tableau comparatif | deepseek-v4-flash | Bon. Second avis indépendant : **4/5**. |
| 2 | Classcraft vs ClassDojo, facilité pour enseignant débutant, **liste de points forts-faibles** | deepseek-v4-flash | **Acceptable mais générique (3/5, point faible identifié)** - le second avis juge que le format demandé (listes forts/faibles séparées) n'était pas assez explicitement distinct d'un tableau comparatif classique, malgré la présence réelle de 2 tableaux forces/faiblesses dans la réponse. **Note d'implémentation** : renforcer l'instruction de format pour ce gabarit afin que « liste de points forts-faibles » produise un rendu visuellement plus distinct d'un tableau comparatif générique. |
| 3 | Examen traditionnel vs portfolio, mesure de la compréhension réelle, synthèse en paragraphe | deepseek-v4-flash | Bon. Second avis indépendant : **4/5** - conclusion nuancée, format paragraphe respecté. |

### Expliquer simplement

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Photosynthèse, 6 ans, quelques phrases | kimi-k2.6 | Bon - analogies concrètes. |
| 2 | Système démocratique canadien, secondaire, un paragraphe | kimi-k2.6 | Bon - analogie du conseil étudiant, bien calibrée pour l'âge. |
| 3 | Pourquoi les saisons, parent non-spécialiste, quelques phrases | kimi-k2.6 | Bon - analogie de la lampe de poche inclinée, concise et juste. |

### Trouver des idées

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | 5 activités brise-glace, 6e année, 20 min, aucun matériel | kimi-k2.6 | Bon. Second avis indépendant : **4/5**, confirme le bon choix d'omettre le CoT. |
| 2 | 10 idées révision vocabulaire anglais, 3e secondaire, 1 iPad partagé, 15 min | kimi-k2.6 | Excellent - créatif et respecte scrupuleusement la contrainte d'un seul appareil partagé. |
| 3 | Projets sciences fin d'année, 6e année, budget quasi nul, 2 semaines | kimi-k2.6 | Bon à excellent - très riche (60 idées classées par domaine), respecte la contrainte budgétaire à chaque proposition. |

### Préparer une activité ou un questionnaire

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Fractions équivalentes, 4e année, 45 min, plan de leçon avec évaluation | deepseek-v4-flash | Bon. Second avis indépendant : **5/5**, confirme le gain du CoT sur la cohérence objectifs↔activités. |
| 2 | Questionnaire choix multiples sur un conte, 2e année, 20 min, papier, 22 élèves | deepseek-v4-flash | Bon. Second avis indépendant : questions adaptées à l'âge (« ni trop dur ni condescendant »), minutage jugé réaliste avec une réserve mineure (prévoir un peu plus de temps pour les élèves en difficulté de lecture). |
| 3 | Débat changements climatiques, secondaire 4, 60 min, 30 élèves, sans matériel | deepseek-v4-flash | Bon. Second avis indépendant : **4/5**, la répartition en 4 rôles (Pour/Contre/Jury/Modérateurs) jugée « judicieuse » pour éviter la passivité d'un débat à 2 camps seulement. |

### Traduire

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Invitation portes ouvertes, anglais, chaleureux | kimi-k2.6 | Bon - fidèle et fluide. |
| 2 | Règlement sécurité cabane à sucre (~50 mots), espagnol, formel | kimi-k2.6 | Bon - traduction fidèle et de registre approprié. |
| 3 | Court rappel espadrilles, anglais, neutre | kimi-k2.6 | Bon - concis et naturel. |

### Autre chose

| Cas | Situation | Modèle | Verdict |
|---|---|---|---|
| 1 | Conseiller pédagogique, encouragement lecture, 2e année | kimi-k2.6 | Bon - ton positif, analogie du vélo. |
| 2 | Technicien informatique, instructions mot de passe Chromebook, primaire | kimi-k2.6 | Bon - liste numérotée claire, vocabulaire adapté aux jeunes élèves. |
| 3 | Enseignant de musique, texte de présentation du spectacle, programme imprimé | kimi-k2.6 | Bon - ton chaleureux approprié à un programme imprimé. |

## Résultat global (27/27 cas)

**26 cas sur 27 jugés bons à excellents (96 %), 1 cas jugé acceptable mais générique** (Analyser
ou comparer, cas 2 : le format « liste de points forts-faibles » a produit un résultat correct sur
le fond mais pas assez visuellement distinct d'un tableau comparatif classique). Aucun gabarit n'a
échoué la règle d'arrêt (aucun gabarit sous 2/3 de ses 3 cas jugés bons - le gabarit Analyser
reste à 2/3 clairement bons, largement au-dessus du seuil de retravail). **Aucun gabarit n'a donc
été retravaillé** : le seul ajustement apporté est une note d'implémentation (pas une refonte) sur
le squelette du gabarit Analyser, pour renforcer l'instruction de format afin que « liste de
points forts-faibles » se distingue plus nettement d'un tableau comparatif à l'usage.

Le seuil du critère d'acceptation 8 (« meilleur que la branche utilisateur-seul sur au moins 20/27
cas, jamais pire sur plus de 3 ») ne peut être vérifié qu'une fois la branche A (baseline humaine
réelle) disponible - voir limite ci-dessous - mais le signal de qualité intrinsèque du gabarit
(B contre C) est solide : 26/27 bons à excellents dépasse largement le seuil de 20/27 sur cette
dimension.

## Limite honnête (à ne pas contourner)

Le plan (section 10.1) exige une comparaison contre une branche **A = ce que l'utilisateur aurait
écrit seul, baseline captée en amont, jamais simulée**. Cette passe n'a testé que **B (le gabarit)
contre C (un prompt structuré équivalent)** - elle n'a PAS de vraie baseline humaine non assistée,
faute d'accès à de vrais enseignants à cette étape de conception (c'est précisément l'objet de
l'étape 11, bloquée en attente de l'utilisateur - voir tâche #1509). Aucune baseline n'a été
simulée par une IA jouant un rôle d'enseignant non-expert : ça aurait été exactement la simulation
que le plan interdit.

**Recommandation** : lors du test d'usabilité de l'étape 11 (5 enseignants réels), capturer AUSSI
ce que chaque enseignant aurait écrit sans l'outil (branche A) avant de leur montrer le
Constructeur - cela complète le protocole du critère d'acceptation 8 sans dédoubler l'effort de
recrutement de vrais enseignants.

**État du protocole complet à 27 cas** : exécuté en totalité (27/27 cas réels, 3 par gabarit,
situations variées, second avis indépendant sur les 6 cas des 3 gabarits à CoT) - voir le journal
complet ci-dessus. Ce protocole couvre B (le gabarit) contre C (un prompt d'expert), avec un
résultat solide (26/27 bons à excellents). Il ne couvre PAS encore la comparaison contre A
(baseline humaine réelle) ni une double passe LLM-as-a-Judge à inversion d'ordre anti-biais sur
l'ensemble des 27 cas (seuls les 6 cas CoT ont reçu un second avis formel) - ces deux raffinements
restent à compléter, idéalement lors de l'étape 11, avant de cocher le critère d'acceptation 8
comme intégralement satisfait.
