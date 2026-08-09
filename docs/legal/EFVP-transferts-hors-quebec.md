# Évaluation des facteurs relatifs à la vie privée

## Communication de renseignements personnels hors Québec

> **Brouillon de travail 0.5 préparé sous l'autorité du RPRP le 7 août 2026. Ne constitue pas un avis juridique et n'a fait l'objet d'aucune validation juridique professionnelle externe.**
**Document interne et confidentiel - Ne pas publier**
## 1. Fiche de contrôle

| Champ | Valeur |
|---|---|
| Organisation | 9307-6719 Québec inc. |
| Service visé | laveille.ai |
| Responsable de la protection des renseignements personnels | confidentialite@laveille.ai |
| Objet | Communications et traitements de renseignements personnels hors Québec |
| Date de l'évaluation | 7 août 2026 |
| Version | 0.5 |
| Statut | brouillon de travail 0.5, décisions intérimaires entérinées par le RPRP |
| Classification | Interne et confidentiel |
| Décision | Décisions intérimaires rendues par le RPRP le 7 août 2026 |
| Prochaine révision planifiée | Réévaluation périodique par le RPRP |
| Déclencheur de révision | Tout changement de fournisseur, de flux ou de finalité |
### 1.1 Portée

La présente ÉFVP examine les flux actifs par lesquels 9307-6719 Québec inc. communique un renseignement personnel hors Québec ou confie à une personne ou à un organisme hors Québec le soin de le recueillir, de l'utiliser, de le communiquer ou de le conserver pour son compte.
Elle recense aussi les flux dormants susceptibles d'être activés ultérieurement.
Elle ne constitue ni un avis juridique ni le substitut d'un avis juridique professionnel.
Le 7 août 2026, le RPRP a décidé de rendre les décisions consignées dans le présent document sur recommandation d'un processus d'analyse multi-modèles, sans consulter de conseiller juridique externe. Une analyse multi-modèles n'équivaut pas à un avis juridique. Le RPRP assume et documente le risque résiduel découlant de l'absence de validation juridique professionnelle externe; une consultation juridique demeure recommandée si les moyens le permettent.
### 1.2 Responsabilités internes proposées

| Rôle | Responsabilité |
|---|---|
| Direction | Autoriser les fournisseurs et les mesures correctives |
| RPRP | Tenir l'ÉFVP à jour, documenter les décisions et vérifier les ententes |
| Équipe technique | Maintenir l'inventaire des flux, limiter les données et appliquer les réglages |
| Administration du site | Ne pas activer un flux dormant sans révision préalable |
| Processus d'analyse multi-modèles | Formuler des recommandations non juridiques au RPRP, sans se substituer à son jugement ni à un avis juridique professionnel |
## 2. Méthodologie

### 2.1 Démarche

La démarche suit la forme libre proposée par le Guide d'accompagnement à la réalisation des ÉFVP, version 3.1 d'avril 2024, de la Commission d'accès à l'information du Québec.
Elle est structurée autour des exigences particulières de l'article 17 de la Loi sur la protection des renseignements personnels dans le secteur privé, RLRQ, c. P-39.1, ci-après la « LPRPSP ».
L'analyse comprend :
1. l'inventaire des intégrations et des flux observables dans le code réel;
2. la vérification de configurations et d'états applicatifs en production le 7 août 2026;
3. des vérifications serveur et réseau effectuées à cette date;
4. l'examen des catégories de renseignements, des finalités et des destinations;
5. l'examen préliminaire des mesures techniques, administratives et contractuelles;
6. la consultation de sources juridiques et contractuelles primaires accessibles en ligne;
7. la consignation distincte des faits confirmés, des hypothèses et des vérifications restantes.
### 2.2 Indices techniques documentés

Les éléments suivants constituent des indices documentés dont la portée est bornée par la méthode et les limites indiquées, en date du 7 août 2026 :
| Indice documenté | État observé | Méthode, date et portée | Limites |
|---|---|---|---|
| Serveur `server.memora.pro`, IP `72.11.130.66`, WHOIS `HOSTP-7` | Adresse IP géolocalisée à Buffalo, États-Unis | Commandes `dig server.memora.pro` et `whois 72.11.130.66`, exécutées le 7 août 2026; IP publique et titulaire du bloc seulement | La géolocalisation de l'adresse IP est un indice, non une preuve de la localisation physique des disques; elle ne prouve ni les lieux de sauvegarde ni ceux des sous-traitants ou de la relève |
| Base de données principale | Entièrement hébergée sur ce serveur | Inspection de la configuration de production le 7 août 2026; connexion principale seulement | Répliques, exports, sauvegardes et copies ponctuelles non vérifiés |
| DNS de `laveille.ai` | Résolution Cloudflare confirmée à `172.64.80.1` | Commande `dig laveille.ai`, exécutée le 7 août 2026; réponse DNS publique observée | Résultat ponctuel, autres noms d'hôte et changements ultérieurs non vérifiés |
| Service d'envoi par défaut | `MAIL_MAILER=workspace` en production | Lecture ciblée de la variable de configuration active le 7 août 2026 | Routage de secours, règles propres à certaines fonctions et console Google non vérifiés |
| Stripe | Mode réel confirmé par une clé de type `sk_live` | Vérification du préfixe de la clé configurée le 7 août 2026, sans reproduire le secret | Activité transactionnelle, webhooks, compte Stripe et destinations ultérieures non vérifiés |
| OpenRouter | Usages administratifs actifs | Recherche des appels dans les fichiers suivis et inspection de la configuration applicative le 7 août 2026 | Journaux, données historiques, réglages du compte et routes réellement utilisées non vérifiés |
| Chatbot public et auto-modération | Désactivés en base | Lecture des valeurs applicatives en production le 7 août 2026 | État ponctuel; caches, tâches différées et changements ultérieurs non vérifiés |
| Capture de prospect du chatbot | Locale, non transmise à OpenRouter selon `ChatBot.php:341-383` | Revue statique de ce segment du fichier suivi le 7 août 2026 | Exécution réelle, autres chemins de code, journaux et modifications ultérieures non vérifiés |
| GitHub | Aucun renseignement personnel d'utilisateur repéré par recherche textuelle; `.env` exclu | Recherche textuelle sur les fichiers suivis seulement, le 7 août 2026 | Historique Git, branches autres que celle examinée, pièces jointes, issues, demandes de tirage, discussions, artefacts et fichiers non suivis non vérifiés |
Les secrets eux-mêmes ne sont ni reproduits ni annexés au présent document.
Un indice ponctuel n'établit pas que la configuration est demeurée identique après le 7 août 2026.
### 2.3 Échelle qualitative

| Niveau | Sens |
|---|---|
| Faible | Données limitées, impact vraisemblablement circonscrit, protections fortes |
| Modéré | Données identifiantes ou exposition significative nécessitant des contrôles documentés |
| Élevé | Volume, sensibilité, centralisation, portée ou régime de destination soulevant un risque important |
| À déterminer | Information insuffisante ou réévaluation par le RPRP requise |
Cette échelle sert à prioriser les mesures. Elle ne remplace pas le critère légal de protection adéquate.
### 2.4 Limites

Le brouillon ne contient pas d'audit indépendant des fournisseurs, de test d'intrusion, de copie signée de toutes les ententes, ni d'opinion de droit étranger.
Les sous-traitants ultérieurs, durées de conservation réelles, options de localisation et réglages de comptes doivent être validés contre les consoles et contrats applicables.
### 2.5 Divergence méthodologique consignée

Lors du premier cycle de réfutation croisée, un réviseur a soutenu que la conservation par un tiers relevait de l'article 18 plutôt que de l'article 17 de la LPRPSP. La position retenue est que les articles 17 et 18, troisième alinéa, se cumulent. L'article 17 vise expressément le fait de confier à une personne ou à un organisme à l'extérieur du Québec la tâche de recueillir, d'utiliser, de communiquer ou de conserver des renseignements personnels pour le compte de l'entreprise. Lorsqu'un fournisseur agit aussi comme mandataire ou exécutant d'un contrat de service, le mandat doit en outre respecter les exigences de l'article 18.3. Les flux de conservation demeurent donc dans le périmètre de la présente ÉFVP et les exigences propres au mandat s'ajoutent à celles de l'article 17. Cette interprétation est retenue par le RPRP sur recommandation du processus d'analyse multi-modèles, sans validation juridique professionnelle externe.

Un réviseur approuvait la formulation réservant exclusivement au conseiller juridique l'appréciation de l'adéquation. Un autre a démontré que cette formulation était trompeuse au regard de l'article 17, qui impose l'évaluation et la décision à l'entreprise. Position retenue le 7 août 2026 : la décision d'adéquation appartient à l'entreprise, sous l'autorité de son RPRP. Le RPRP décide sur recommandation du processus d'analyse multi-modèles, en toute connaissance du fait qu'aucune validation juridique professionnelle externe n'a été obtenue et que ce processus ne constitue pas un avis juridique.
## 3. Cadre juridique

### 3.1 Articles 17 et 18.3 LPRPSP

Avant une communication hors Québec, l'entreprise doit procéder à une ÉFVP.
L'analyse doit notamment tenir compte de quatre facteurs :
1. la sensibilité du renseignement;
2. la finalité de son utilisation;
3. les mesures de protection, y compris les mesures contractuelles;
4. le régime juridique applicable dans l'État de destination, notamment ses principes de protection des renseignements personnels.
La communication peut avoir lieu si l'évaluation démontre que le renseignement bénéficierait d'une protection adéquate, notamment au regard des principes de protection généralement reconnus.
La communication doit faire l'objet d'une entente écrite tenant notamment compte des résultats de l'ÉFVP et, le cas échéant, des modalités d'atténuation des risques.
La même règle vise le fait de confier à une personne ou à un organisme hors Québec la collecte, l'utilisation, la communication ou la conservation pour le compte de l'entreprise.
Lorsque cette personne ou cet organisme reçoit les renseignements dans le cadre d'un mandat ou d'un contrat de service, l'article 18.3 s'applique aussi. Le mandat ou le contrat doit être écrit et comporter les clauses exigées par l'article 18.3, en plus de satisfaire aux exigences de l'article 17 applicables à la communication hors Québec.
### 3.2 Critère appliqué dans ce brouillon

Pour chaque flux, le document examine séparément les quatre facteurs, puis formule une conclusion provisoire.
Une certification, une clause contractuelle type ou un DPA constitue un élément de protection, mais n'est pas traité ici comme une preuve suffisante et automatique d'adéquation.
L'accès possible par des autorités étrangères, les recours disponibles, la transparence, la surveillance indépendante et l'effectivité des droits sont appréciés par le RPRP sur recommandation du processus d'analyse multi-modèles, sans validation juridique professionnelle externe.
L'entente écrite devrait comprendre les clauses exigées par l'article 18.3 et préciser au minimum :
- les renseignements visés et les finalités autorisées;
- les mesures de sécurité et de confidentialité;
- les limites de conservation et les modalités de suppression;
- les sous-traitants et les lieux de traitement;
- la notification des incidents et des demandes gouvernementales;
- les droits d'audit ou éléments de preuve équivalents;
- l'assistance à l'exercice des droits;
- la restitution ou destruction à la fin des services;
- les mesures supplémentaires découlant de la présente ÉFVP.
## 4. Flux actifs

### 4.A Hébergement principal - HostPapa

#### Description du flux

| Élément | Évaluation |
|---|---|
| Fournisseur | HostPapa, entreprise canadienne établie à Burlington, Ontario |
| Infrastructure | `server.memora.pro`, IP `72.11.130.66`, WHOIS `HOSTP-7` |
| Destination confirmée | Buffalo, État de New York, États-Unis |
| Migration demandée | Migration vers un centre de données HostPapa de Toronto, Ontario, demandée par le RPRP et en cours au 7 août 2026 |
| Données | Toute la base : comptes, abonnés à l'infolettre, commentaires, commandes et messages de contact |
| Finalité | Hébergement, stockage, disponibilité, sauvegarde et exploitation du site |
| Personnes | Utilisateurs, abonnés, clients, commentateurs et personnes qui écrivent à l'entreprise |
| Base contractuelle | Contrat d'hébergement et conditions de HostPapa, à obtenir et archiver |
| Entente écrite en place? | À vérifier; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
| Importance | Flux principal et structurel |
#### Évaluation des quatre facteurs

**1. Sensibilité.** Le flux concentre des identifiants, coordonnées, contenus de communications, historique commercial et données de compte. Certains messages ou commentaires peuvent contenir librement des renseignements sensibles. Le volume et la centralisation augmentent les conséquences d'un accès indu. Niveau préliminaire : élevé.
**2. Finalité.** L'hébergement est nécessaire à l'exploitation actuelle. La conservation de chaque catégorie doit toutefois respecter les calendriers de conservation et le principe de minimisation.
**3. Mesures de protection.** Les contrôles applicables doivent être documentés : chiffrement en transit et au repos, contrôle d'accès, authentification multifacteur, sauvegardes, journalisation, segmentation, correctifs, notification des incidents et suppression. Le contrat, le DPA, la liste des sous-traitants, les lieux de sauvegarde et les engagements de retour ou destruction restent à obtenir ou à confirmer.
**4. Régime juridique.** Pendant la migration, le stockage physique demeure soumis au droit américain pertinent, malgré l'établissement canadien du fournisseur. Après la migration, la conservation principale relèvera du régime canadien et ontarien, beaucoup plus proche du régime québécois. L'article 17 vise toutefois toute communication hors Québec : l'ÉFVP et l'encadrement contractuel resteront donc requis à Toronto.
**Équivalence avec les principes de protection généralement reconnus.** Les États-Unis n'ont pas de loi fédérale générale couvrant uniformément le secteur privé. Les droits d'accès et de recours des non-citoyens sont limités dans certains contextes, et des accès gouvernementaux peuvent être autorisés notamment sous la FISA, article 702, et le CLOUD Act.
**Conclusion.** Le serveur et la base se trouvent aux États-Unis, et les mesures, ententes et lieux de sauvegarde doivent être documentés. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d'analyse multi-modèles).** Poursuite avec atténuation. La mesure principale est la migration demandée et en cours vers le centre de données HostPapa de Toronto. Le plan d'action engagé comprend aussi le gel de toute nouvelle catégorie de données et l'obtention d'une entente conforme.
#### Mesures d'atténuation

- Achever la migration demandée vers le centre de données HostPapa de Toronto et en conserver les preuves.
- Vérifier que les bases, fichiers, journaux, sauvegardes et systèmes de relève migrent tous au Canada.
- Obtenir le contrat, le DPA, la liste des sous-traitants et les lieux exacts de traitement.
- Vérifier les protocoles de chiffrement natifs de l'hébergeur; à défaut de contrôle sur les sauvegardes de l'hébergeur, implémenter un chiffrement applicatif Laravel au moyen de `encrypted casts` pour les champs les plus sensibles avant l'écriture sur disque.
- Réduire les données historiques et formaliser un calendrier de suppression.
- Limiter les accès administratifs, imposer l'authentification multifacteur et revoir les comptes trimestriellement.
- Prévoir contractuellement les incidents, demandes gouvernementales, audits et suppressions.
- Obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.B Cloudflare et Turnstile

#### Description du flux

| Élément | Évaluation |
|---|---|
| Services | Proxy, CDN, sécurité périphérique et Turnstile |
| État | Proxy confirmé actif; Turnstile actif sur le formulaire infolettre auteurs |
| Données | Cloudflare effectue la terminaison TLS du proxy inverse. La totalité des données applicatives en transit, dont les identifiants, le contenu des formulaires et les témoins de session, est temporairement déchiffrée sur ses serveurs de périphérie mondiaux. Turnstile reçoit aussi les adresses IP et les signaux contre les abus. |
| Destination | Réseau mondial, incluant les États-Unis |
| Finalité | Acheminement, performance, sécurité et prévention automatisée des robots |
| Base contractuelle | DPA Cloudflare v6.4, effectif le 3 avril 2026, incorporé à l'entente principale |
| Entente écrite en place? | Conditions ou DPA d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
#### Évaluation des quatre facteurs

**1. Sensibilité.** La totalité des données applicatives en transit peut être exposée temporairement en clair aux systèmes de périphérie, y compris du texte libre sensible. Niveau préliminaire : élevé.
**2. Finalité.** La prestation CDN, la sécurité et l'anti-abus sont légitimes et circonscrites, sous réserve de désactiver les fonctions non nécessaires.
**3. Mesures de protection.** Le DPA v6.4 prévoit des obligations de sous-traitant, des clauses contractuelles types et des mécanismes de transferts. Cloudflare annonce des certifications et une participation au Data Privacy Framework. Il faut conserver la version acceptée, examiner les annexes, la rétention, les sous-traitants et les réglages de journaux.
**4. Régime juridique.** Le réseau mondial rend possibles des traitements dans plusieurs États. Les protections contractuelles européennes sont pertinentes comme indice, sans trancher le critère québécois. Les lois américaines peuvent s'appliquer à Cloudflare, Inc.
**Équivalence avec les principes de protection généralement reconnus.** Aux États-Unis, il n'existe pas de loi fédérale générale uniforme; les recours des non-citoyens et les droits sectoriels varient, et la FISA, article 702, ainsi que le CLOUD Act encadrent certains accès gouvernementaux. Dans l'Espace économique européen, le RGPD prévoit un cadre général et des recours.
**Conclusion.** Le proxy déchiffre temporairement toutes les données applicatives en transit sur un réseau mondial; le DPA et les réglages doivent être archivés et vérifiés. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel recommandé pour les fonctions nécessaires de sécurité et d'acheminement, sous réserve de documenter les réglages, la rétention, les destinations et l'entente conforme; désactivation des fonctions superflues.
#### Mesures d'atténuation

- Inventorier les produits Cloudflare activés et désactiver toute fonction superflue.
- Réduire la conservation des journaux et restreindre les accès.
- Éviter le cache des pages et réponses contenant des renseignements personnels.
- Confirmer que Turnstile ne reçoit que les données nécessaires.
- Archiver le DPA v6.4 et les annexes applicables avec preuve d'acceptation.
- Surveiller les changements de sous-traitants, de réseau et de version contractuelle.
- Obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.C Google LLC - Workspace SMTP, GA4 et AdSense

#### 4.C.1 Workspace SMTP

| Élément | Évaluation |
|---|---|
| État | Service d'envoi par défaut confirmé par `MAIL_MAILER=workspace` |
| Données | Adresse courriel, nom, contenu et métadonnées des courriels transactionnels |
| Finalité | Envoi de confirmations, avis et communications transactionnelles |
| Destination | États-Unis et infrastructure Google; aucune région Canada offerte pour Workspace selon l'information examinée |
| Base contractuelle | Google Cloud Data Processing Addendum, applicable à vérifier au compte |
| Entente écrite en place? | Conditions ou CDPA d'adhésion acceptés en ligne; l'applicabilité au compte et la suffisance au sens de l'article 17, alinéa 2, doivent être appréciées et décidées par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Les coordonnées et contenus transactionnels peuvent révéler une relation commerciale ou le contenu d'une demande. Niveau préliminaire : modéré, parfois élevé selon le message.
**2. Finalité.** L'envoi transactionnel est nécessaire. Les modèles doivent exclure les données non indispensables.
**3. Mesures de protection.** Google agit comme sous-traitant selon le CDPA. Les réglages d'accès, l'authentification multifacteur, les journaux, la rétention et les sous-traitants doivent être vérifiés. L'absence d'option régionale Canada augmente la dépendance au cadre contractuel.
**4. Régime juridique.** Google LLC est américaine et des données sont susceptibles d'être traitées aux États-Unis. Les lois américaines, mécanismes de transfert invoqués et recours sont appréciés par le RPRP sur recommandation du processus d'analyse multi-modèles, sans validation juridique professionnelle externe.
**Équivalence avec les principes de protection généralement reconnus.** Les États-Unis n'ont pas de loi fédérale générale uniforme pour le secteur privé; les droits d'accès et de recours des non-citoyens sont limités dans certains contextes, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.
**Conclusion Workspace.** Le courriel transactionnel est traité sur l'infrastructure de Google, sans région canadienne offerte selon l'information examinée; le CDPA et les réglages du compte restent à confirmer. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel pour les courriels transactionnels nécessaires, avec minimisation stricte, confirmation du CDPA et comparaison documentée d'une solution canadienne.

Mesures : réduire le contenu envoyé, interdire les secrets et renseignements très sensibles, confirmer le CDPA applicable, configurer la rétention, imposer l'authentification multifacteur, évaluer une solution canadienne et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
#### 4.C.2 Google Analytics 4

| Élément | Évaluation |
|---|---|
| Données | Identifiants analytiques, événements, appareil et Client-ID stocké dans un témoin; `anonymize_ip:true` est présent dans `master.blade.php`, mais il est sans effet sous GA4 puisqu'il s'agit d'un paramètre hérité d'Universal Analytics |
| Traitement de l'adresse IP | La communication survient dès la transmission à Google. Selon l'affirmation du fournisseur, non vérifiable indépendamment dans le cadre de la présente méthode, GA4 ne consigne pas les adresses IP; le Client-ID demeure un renseignement personnel au sens de la LPRPSP |
| Finalité | Mesure d'audience et amélioration du service |
| Rôle du fournisseur | Traitement à ses propres fins pour certaines opérations; portée à confirmer |
| Fondement de la communication | Consentement conforme à l'article 14 à confirmer; article 8.1 (paramètres de confidentialité par défaut) à vérifier |
| Consentement | Consent Mode v2, refus par défaut |
| Destination | Google LLC, États-Unis et infrastructure mondiale |
| Base contractuelle | Google Ads Data Processing Terms, acceptation dans Admin GA4 à vérifier |
| Entente écrite en place? | Conditions de traitement acceptées en ligne, état à vérifier dans Admin GA4; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Le Client-ID, les événements et les caractéristiques de l'appareil permettent de suivre une activité. Niveau préliminaire : modéré.
**2. Finalité.** L'analytique est utile, mais non essentielle au service demandé par la personne. Le refus préalable constitue une mesure importante qui doit être testé régulièrement.
**3. Mesures de protection.** Sont pertinents : refus par défaut, conservation minimale, désactivation des signaux Google et de la publicité personnalisée, limitation des événements, absence de données directement identifiantes et contrat accepté. `anonymize_ip:true` ne constitue pas une mesure effective sous GA4.
**4. Régime juridique.** Des décisions et mises en demeure européennes défavorables aux transferts GA4 vers les États-Unis, notamment relayées par noyb, constituent un signal de risque comparatif. Elles ne déterminent pas automatiquement l'application de l'article 17 québécois, mais justifient une analyse prudente du régime américain et des mesures supplémentaires.
**Équivalence avec les principes de protection généralement reconnus.** Le régime américain est sectoriel, sans loi fédérale générale uniforme; les recours des non-citoyens sont limités dans certains contextes, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.
**Conclusion GA4.** La communication de l'adresse IP à Google survient au moment de la transmission, indépendamment de sa conservation ultérieure. L'énoncé selon lequel « GA4 ne consigne pas les adresses IP » est une affirmation du fournisseur qui n'est pas vérifiable indépendamment dans le cadre de la présente méthode. GA4 utilise aussi un Client-ID qui demeure un renseignement personnel; le blocage préalable et l'entente du compte doivent être vérifiés. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel seulement avec refus par défaut vérifié, collecte minimale et courte rétention; migrer vers une analytique canadienne si ces garanties ou la preuve contractuelle font défaut.

Mesures : vérifier l'acceptation contractuelle, tester le blocage avant consentement, minimiser les événements, raccourcir la rétention, désactiver les fonctions publicitaires inutiles, considérer une analytique hébergée au Canada et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
#### 4.C.3 AdSense

| Élément | Évaluation |
|---|---|
| Données | Identifiants publicitaires, appareil, interactions et signaux de diffusion |
| Finalité | Affichage et mesure publicitaires |
| Rôle du fournisseur | Traitement à ses propres fins pour certaines opérations; portée à confirmer |
| Fondement de la communication | Consentement conforme à l'article 14 à confirmer; article 8.1 (paramètres de confidentialité par défaut) à vérifier |
| Limitation actuelle | Désactivé sur les pages traitant des renseignements personnels par `no_ads` |
| Destination | Google LLC, États-Unis et réseau mondial |
| Base contractuelle | Conditions et modalités de traitement Google applicables au compte |
| Entente écrite en place? | Conditions de traitement acceptées en ligne, état précis à vérifier; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Les profils et identifiants publicitaires peuvent devenir sensibles par inférence ou combinaison. Niveau préliminaire : modéré.
**2. Finalité.** La monétisation est distincte de la prestation principale et commande un choix réel de la personne.
**3. Mesures de protection.** `no_ads` sur les pages sensibles réduit l'exposition. Le consentement préalable, la publicité non personnalisée, la minimisation et les paramètres du compte doivent être vérifiés.
**4. Régime juridique.** Le même contexte américain et mondial que pour les autres services Google s'applique, sous réserve des rôles contractuels propres à AdSense.
**Équivalence avec les principes de protection généralement reconnus.** Le régime américain est sectoriel et ne comporte pas de loi fédérale générale uniforme; les droits des non-citoyens varient, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.
**Conclusion AdSense.** La limitation `no_ads` est en place sur les pages désignées; les choix de consentement, les paramètres et les rôles contractuels restent à vérifier. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d'analyse multi-modèles).** Poursuite avec atténuation du flux actuellement limité. Le fondateur souhaite activer la monétisation publicitaire Google; ce projet est à l'étude au 7 août 2026. L'activation complète est soumise aux conditions suspensives suivantes : accepter les Google Ads Data Processing Terms, obtenir un consentement conforme à l'article 14 avant toute publicité personnalisée et mettre à jour la présente ÉFVP avant le lancement. Si le projet aboutit, le flux passera alors de limité à actif.

Mesures : maintenir `no_ads`, bloquer avant choix valide, préférer les annonces non personnalisées, auditer les balises, documenter les rôles des parties et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.D OpenRouter Inc.

#### Description du flux

| Élément | Évaluation |
|---|---|
| État | En production, usages administratifs seulement |
| Usages actifs | Résumés, traductions et enrichissement de l'annuaire |
| Usages inactifs | Chatbot public et auto-modération |
| Données | Texte libre soumis aux fonctions d'IA et métadonnées techniques |
| Exclusion confirmée | Nom, courriel et téléphone du formulaire de prospect restent locaux selon `ChatBot.php:341-383` |
| Destination | États-Unis par défaut, puis fournisseurs de modèles selon le routage |
| Base contractuelle | DPA incorporé par renvoi aux Conditions d'OpenRouter |
| Entente écrite en place? | Conditions ou DPA d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
#### Évaluation des quatre facteurs

**1. Sensibilité.** Le texte libre peut accidentellement contenir des renseignements personnels ou sensibles. Les usages administratifs réduisent l'exposition publique, mais pas le risque lié au contenu source. Niveau préliminaire : modéré à élevé.
**2. Finalité.** Les finalités sont la production de résumés, de traductions et l'enrichissement éditorial. Toute réutilisation, entraînement ou journalisation dépasserait la finalité prévue sauf autorisation distincte.
**3. Mesures de protection.** Selon les Conditions, les prompts ne sont pas conservés par défaut sauf adhésion à la journalisation, tandis que certaines métadonnées sont conservées. Le routage vers des fournisseurs de modèles ajoute des sous-traitants et des régimes variables. Deux réglages doivent être vérifiés désactivés dans le compte : la journalisation des prompts et toute option permettant l'utilisation des données ou l'entraînement. Les fonctions de conservation temporaire nécessaires au traitement et les politiques propres aux modèles doivent aussi être examinées.
**4. Régime juridique.** OpenRouter Inc. est située aux États-Unis. Le fournisseur de modèle choisi peut se trouver dans un autre État. Cette chaîne rend nécessaire une analyse des destinations, des lois d'accès et des recours pour chaque route autorisée.
**Équivalence avec les principes de protection généralement reconnus.** Le régime américain ne comporte pas de loi fédérale générale uniforme, les recours des non-citoyens sont limités dans certains contextes, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux. Les autres destinations restent à cartographier.
**Conclusion.** Les usages actifs sont administratifs, mais le routage et les réglages de conservation ou d'entraînement restent à vérifier pour chaque modèle autorisé. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel des seuls usages administratifs, avec interdiction de renseignements personnels, routage restreint et preuve de rétention nulle; conserver les fonctions publiques désactivées.
#### Mesures d'atténuation

- Vérifier et capturer la preuve que les deux réglages de journalisation et d'utilisation des données sont désactivés.
- Imposer une politique interne interdisant les renseignements personnels dans les prompts, sauf nécessité documentée.
- Pseudonymiser ou caviarder les textes avant transmission.
- Restreindre le routage aux modèles offrant une rétention nulle et des engagements compatibles.
- Tenir une liste approuvée des modèles, fournisseurs et destinations.
- Archiver le DPA et les conditions applicables à la date d'utilisation.
- Maintenir le chatbot public et l'auto-modération désactivés jusqu'à révision de l'ÉFVP.
- Obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.E Brevo SAS

#### Description et analyse

| Élément | Évaluation |
|---|---|
| Données | Courriel, prénom, nom et statut d'abonnement |
| Finalité | Gestion et envoi de l'infolettre |
| Destination | France et Union européenne; bases d'emailing annoncées dans l'UE |
| Base contractuelle | DPA Brevo soumis au RGPD |
| Entente écrite en place? | Conditions ou DPA d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Les coordonnées et préférences d'abonnement sont généralement de sensibilité faible à modérée, mais peuvent révéler un intérêt thématique. **2. Finalité.** La gestion d'une infolettre est précise et prévisible. **3. Mesures.** Le DPA RGPD, les droits des personnes, la sécurité, la suppression et les sous-traitants sont pertinents, sous réserve de vérification de la version acceptée. **4. Régime.** Le droit français, le RGPD, les autorités indépendantes et les recours constituent des indices substantiels de protection élevée.
**Équivalence avec les principes de protection généralement reconnus.** Le RGPD établit un cadre général couvrant la finalité, la minimisation, la transparence, la sécurité, les droits d'accès, de rectification et d'effacement, la surveillance indépendante et les recours. Les transferts ultérieurs hors de l'Union européenne restent à vérifier.
**Conclusion.** Le fournisseur est établi en France et soumis au RGPD, indicateur favorable à documenter; le DPA accepté et les sous-traitants doivent être confirmés. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien recommandé, les indicateurs favorables devant être documentés, notamment la version contractuelle, les sous-traitants et les transferts ultérieurs.

Mesures : archiver le DPA, vérifier les sous-traitants hors UE, limiter les champs, synchroniser les désabonnements, supprimer les contacts expirés, protéger les comptes administratifs et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.F Stripe Inc.

#### Description et analyse

| Élément | Évaluation |
|---|---|
| État | Boutique en mode réel, confirmé par clé de type `sk_live` |
| Données transmises | Courriel client et détails du panier |
| Données non reçues par le site | Numéro complet de carte, saisi et traité chez Stripe |
| Finalité | Paiement, prévention de la fraude, reçus et obligations financières |
| Destination | Stripe Inc., États-Unis et infrastructure mondiale |
| Base contractuelle | Stripe Data Processing Agreement et conditions de services |
| Entente écrite en place? | Conditions ou DPA d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Le courriel et l'historique d'achat sont sensibles dans leur contexte. Les données de carte ne transitent pas par le site, ce qui réduit fortement le risque local. Niveau préliminaire : modéré à élevé. **2. Finalité.** Le paiement et la prévention de fraude sont nécessaires à la commande. **3. Mesures.** La tokenisation, l'hébergement du paiement, les normes de sécurité annoncées, le DPA et les contrôles d'accès sont pertinents. Les rôles de Stripe, parfois responsable indépendant pour certaines finalités, doivent être clarifiés. **4. Régime.** Le droit américain s'applique à Stripe Inc.; les transferts ultérieurs et mécanismes contractuels doivent être analysés.
**Équivalence avec les principes de protection généralement reconnus.** Les États-Unis n'ont pas de loi fédérale générale uniforme; les recours des non-citoyens varient, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux. Les régimes des destinations ultérieures restent à cartographier.
**Conclusion.** Le site ne reçoit pas le numéro complet de carte; Stripe reçoit néanmoins le courriel et les détails du panier, et ses rôles contractuels doivent être clarifiés. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel recommandé pour le paiement, sous réserve de clarifier les rôles, de minimiser les métadonnées et de documenter les destinations et la conservation.

Mesures : ne jamais journaliser de données de carte, minimiser les métadonnées, vérifier les webhooks et signatures, limiter les accès au tableau de bord, archiver le DPA, documenter la conservation fiscale et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.G Gelato Group AS

#### Description et analyse

| Élément | Évaluation |
|---|---|
| Données | Nom, adresse complète, courriel et éléments nécessaires à la commande |
| Finalité | Impression, exécution et livraison de commandes physiques |
| Destination | Norvège et réseau mondial d'imprimeurs et transporteurs |
| Base contractuelle | Data Processing Agreement de Gelato et conditions commerciales |
| Entente écrite en place? | Conditions ou DPA d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Une adresse résidentielle complète est un renseignement à protéger avec soin. Niveau préliminaire : élevé. **2. Finalité.** La fabrication et la livraison exigent certaines coordonnées, mais pas toutes les données de compte. **3. Mesures.** Le DPA, la sécurité, la sélection d'imprimeurs, la suppression et la traçabilité des sous-traitants doivent être confirmés commande par commande ou région par région. **4. Régime.** La Norvège applique un cadre aligné sur le RGPD, mais le réseau mondial peut entraîner des communications dans des États offrant des protections variables.
**Équivalence avec les principes de protection généralement reconnus.** La Norvège participe à l'Espace économique européen et applique le RGPD, cadre général comprenant la finalité, la minimisation, la transparence, la sécurité, des droits individuels et des recours. Les pays d'impression et de livraison doivent être évalués séparément.
**Conclusion.** L'entité est établie en Norvège et soumise au RGPD, mais les imprimeurs et transporteurs mondiaux ainsi que leurs destinations restent à cartographier. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel commande par commande, avec préférence pour une production proche du client et interdiction des destinations non évaluées.

Mesures : sélectionner l'imprimeur le plus proche lorsque possible, transmettre uniquement les champs nécessaires, obtenir la liste des destinations, encadrer les sous-traitants, fixer une suppression rapide, informer clairement le client et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.H Akismet et Automattic

#### Description et analyse

| Élément | Évaluation |
|---|---|
| Condition actuelle | Flux actif seulement si `AKISMET_KEY` est configurée; vérification requise |
| Données | Commentaire, courriel du commentateur, adresse IP et agent utilisateur |
| Finalité | Détection et prévention des commentaires indésirables |
| Destination | Automattic et infrastructure aux États-Unis |
| Base contractuelle | Conditions Akismet et Data Processing Addendum d'Automattic |
| Entente écrite en place? | À vérifier; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Le contenu libre peut être sensible; courriel et IP permettent l'identification. Niveau préliminaire : modéré à élevé. **2. Finalité.** L'anti-spam est circonscrit, mais une solution locale pourrait réduire le transfert. **3. Mesures.** Le DPA, la rétention, les données réellement exigées, les sous-traitants et l'usage pour améliorer le service doivent être vérifiés. **4. Régime.** Le droit américain et les règles d'accès applicables à Automattic doivent être examinés.
**Équivalence avec les principes de protection généralement reconnus.** Le régime américain est sectoriel, sans loi fédérale générale uniforme; les recours des non-citoyens varient, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.
**Conclusion.** L'activation d'Akismet et l'entente applicable ne sont pas confirmées; si la clé est configurée, des commentaires et identifiants sont transmis aux États-Unis. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Désactivation recommandée tant que l'activation, la nécessité et l'entente ne sont pas confirmées; privilégier un filtrage local si raisonnablement disponible.

Mesures : vérifier immédiatement `AKISMET_KEY`, désactiver si inutile, minimiser les champs, publier l'information appropriée, obtenir le DPA, évaluer un filtrage local et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.I Webhooks sortants administrables

#### Description et analyse

| Élément | Évaluation |
|---|---|
| Données | Charge utile variable pouvant contenir des renseignements personnels |
| Finalité | Intégrations configurées par un administrateur |
| Destination | Variable, selon l'URL inscrite |
| Base contractuelle | Dépend du destinataire; aucune base uniforme |
| Entente écrite en place? | À vérifier pour chaque destinataire; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Indéterminée et potentiellement élevée. **2. Finalité.** Variable, donc impossible à valider globalement. **3. Mesures.** Les contrôles d'accès, signatures, listes d'autorisation, journaux expurgés et contrats doivent précéder tout branchement. **4. Régime.** Inconnu jusqu'à l'identification de l'entité, des serveurs et sous-traitants destinataires.
**Équivalence avec les principes de protection généralement reconnus.** Aucun rapprochement factuel n'est possible avant l'identification de chaque destination et de son cadre général ou sectoriel, de ses droits individuels, de ses recours et de ses règles d'accès gouvernemental.
**Conclusion.** Les destinations, données, finalités et ententes varient selon la configuration et doivent être recensées avant activation. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Suspension de tout webhook non inventorié; autorisation au cas par cas seulement après évaluation de la destination, minimisation et preuve contractuelle.

Mesures : instaurer une procédure d'approbation RPRP, tenir un registre des URL et propriétaires, interdire les destinations non approuvées, limiter les champs, signer les messages, gérer les secrets, tester les suppressions et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.J GitHub et Microsoft

#### Description et analyse

| Élément | Évaluation |
|---|---|
| Données | Code source seulement |
| Renseignements personnels d'utilisateurs | Aucun repéré par recherche textuelle le 7 août 2026 |
| Secrets | Fichier `.env` exclu |
| Destination | GitHub, service de Microsoft, États-Unis |
| Finalité | Gestion de versions et collaboration technique |
| Base contractuelle | GitHub Data Protection Agreement et conditions applicables |
| Entente écrite en place? | Conditions ou DPA d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |
**1. Sensibilité.** Aucun renseignement personnel d'utilisateur n'a été repéré. Les identités professionnelles des contributeurs et métadonnées Git peuvent néanmoins constituer des renseignements personnels. **2. Finalité.** Gestion du code. **3. Mesures.** Dépôt privé, contrôle d'accès, authentification multifacteur, analyse de secrets et règles de revue. **4. Régime.** Droit américain applicable à GitHub, sans transfert utilisateur observé dans la portée vérifiée.
**Équivalence avec les principes de protection généralement reconnus.** Les États-Unis n'ont pas de loi fédérale générale uniforme; les droits et recours varient selon le contexte, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.
**Conclusion.** Aucun renseignement personnel d'utilisateur n'a été repéré dans le dépôt; des identités de contributeurs ou des données ajoutées par erreur peuvent néanmoins y être conservées. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien recommandé pour le code et les identités professionnelles nécessaires, avec interdiction stricte des données de production et vérification élargie de l'historique et des espaces collaboratifs.

Mesures : maintenir `.env` exclu, activer la détection de secrets, interdire les exports de production, revoir les accès, purger selon une procédure sécurisée toute donnée introduite par erreur et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).
### 4.K Accès humains à distance (transferts par visualisation)

| Élément | Évaluation |
|---|---|
| Personnes susceptibles d'accéder | Personnel de soutien technique de HostPapa, Cloudflare, Google et d'autres fournisseurs |
| Données | Renseignements visibles dans les systèmes, journaux, messages, comptes, formulaires ou interfaces de soutien |
| Destination | Lieu de travail du membre du personnel, potentiellement hors Québec, même si le serveur consulté se trouve au Canada |
| Finalité | Diagnostic, entretien, soutien, sécurité et résolution d'incidents |
| Entente écrite en place? | À vérifier dans chaque contrat de soutien; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |

**1. Sensibilité.** L'accès peut porter sur des identifiants, des communications, des données commerciales ou du texte libre sensible. Niveau préliminaire : élevé lorsque l'accès est étendu.

**2. Finalité.** L'accès devrait être limité aux interventions nécessaires, autorisées et consignées.

**3. Mesures de protection.** Vérifier les clauses de confidentialité des contrats de soutien, les contrôles d'accès ponctuel, la journalisation, l'approbation préalable, la supervision et la fermeture des accès.

**4. Régime juridique.** Le régime applicable dépend du lieu depuis lequel le membre du personnel visualise les renseignements et de l'entité qui l'emploie.

**Équivalence avec les principes de protection généralement reconnus.** Pour un accès depuis les États-Unis, le régime est sectoriel, sans loi fédérale générale uniforme, et certains accès gouvernementaux relèvent de la FISA, article 702, ou du CLOUD Act. Pour un accès depuis l'Union européenne ou la Norvège, le RGPD prévoit un cadre général, des droits individuels et des recours.

**Conclusion.** Une visualisation à distance depuis l'extérieur du Québec peut constituer une communication hors Québec même lorsque le serveur est au Canada; les lieux d'accès, les clauses et les contrôles ne sont pas encore cartographiés. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel des accès de soutien strictement nécessaires, ponctuels, approuvés et journalisés; suspendre les accès permanents ou dont le lieu demeure inconnu.

Mesures : cartographier les lieux d'accès, exiger une autorisation ponctuelle, limiter et journaliser chaque session, fermer les accès après intervention et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).

### 4.L Flux connexes

#### 4.L.1 Courriel entrant Google Workspace

| Élément | Évaluation |
|---|---|
| Données et finalité | Messages reçus dans les boîtes `confidentialite@laveille.ai` et autres boîtes Workspace, y compris les demandes d'exercice de droits; réception, traitement et conservation des communications |
| Destination | Infrastructure américaine et mondiale de Google |
| Entente écrite en place? | Conditions ou CDPA d'adhésion acceptés en ligne, applicabilité au compte à vérifier; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |

**Évaluation des quatre facteurs.** La sensibilité varie de modérée à élevée, particulièrement pour les demandes d'exercice de droits. La finalité est le traitement des communications reçues. Les accès, la conservation, l'authentification multifacteur, le CDPA et les sous-traitants doivent être vérifiés. Le régime américain ne comporte pas de loi fédérale générale uniforme; les droits des non-citoyens varient, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.

**Équivalence avec les principes de protection généralement reconnus.** Le régime américain demeure sectoriel, avec des droits individuels et des recours variables selon le contexte, contrairement à un cadre général couvrant uniformément les traitements du secteur privé.

**Conclusion.** Le courriel entrant constitue un flux distinct du SMTP sortant et conserve des messages, dont des demandes d'exercice de droits, sur l'infrastructure de Google. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel pour les communications nécessaires, avec rétention minimale, accès renforcés et traitement local rapide des pièces sensibles.

Mesures : réduire la conservation, restreindre les accès, imposer l'authentification multifacteur, éviter les pièces sensibles lorsque possible et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).

#### 4.L.2 Registraire du domaine

| Élément | Évaluation |
|---|---|
| Fournisseur et destination | Porkbun LLC, Oregon, États-Unis, vérifié par WHOIS le 7 août 2026 |
| Données et finalité | Coordonnées du titulaire du domaine seulement; aucun renseignement personnel d'utilisateur; enregistrement et administration de `laveille.ai` |
| Entente écrite en place? | Conditions d'adhésion acceptées en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |

**Évaluation des quatre facteurs.** Les données sont limitées aux coordonnées du titulaire et servent l'enregistrement du domaine. Les protections du compte, la confidentialité WHOIS et les conditions doivent être vérifiées. Le régime américain est sectoriel, sans loi fédérale générale uniforme; les recours varient et certains accès gouvernementaux peuvent relever du CLOUD Act.

**Équivalence avec les principes de protection généralement reconnus.** Les droits individuels, la surveillance et les recours ne sont pas régis par une loi fédérale générale uniforme pour ce traitement.

**Conclusion.** Porkbun conserve aux États-Unis les coordonnées du titulaire du domaine, sans renseignement personnel d'utilisateur observé dans ce flux. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien recommandé pour les seules coordonnées organisationnelles nécessaires, sous réserve de vérifier la confidentialité WHOIS, les accès et l'entente.

Mesures : utiliser des coordonnées organisationnelles, activer la confidentialité WHOIS, renforcer le compte et obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).

#### 4.L.3 Sauvegardes

| Élément | Évaluation |
|---|---|
| Données et finalité | Copie des données hébergées; continuité, restauration et reprise |
| Destination | Sauvegardes de l'hébergeur sur la même infrastructure américaine; copies locales conservées au Québec par l'exploitant, constat à confirmer |
| Entente écrite en place? | Conditions ou DPA HostPapa d'adhésion acceptés en ligne; la suffisance au sens de l'article 17, alinéa 2, doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |

**Évaluation des quatre facteurs.** La copie peut regrouper toutes les catégories de la base et sa sensibilité est élevée. La finalité est la restauration. Les protocoles de chiffrement natifs, les accès, la fréquence, la conservation et la suppression doivent être vérifiés. Le régime américain est sectoriel, sans loi fédérale générale uniforme; les recours des non-citoyens varient, et la FISA, article 702, ainsi que le CLOUD Act permettent certains accès gouvernementaux.

**Équivalence avec les principes de protection généralement reconnus.** Les États-Unis ne disposent pas d'un cadre fédéral général couvrant uniformément les principes de finalité, de minimisation, de droits individuels, de surveillance et de recours pour ce traitement.

**Conclusion.** Les sauvegardes de l'hébergeur résident sur l'infrastructure américaine; l'existence et le lieu des copies locales québécoises restent à confirmer. La décision d'adéquation est à rendre par l'entreprise, sous l'autorité de son responsable de la protection des renseignements personnels. Cette décision est rendue par le responsable de la protection des renseignements personnels, sur recommandation d’un processus d’analyse multi-modèles, en toute connaissance du fait qu’aucune validation juridique professionnelle externe n’a été obtenue.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d’analyse multi-modèles).** Poursuite avec atténuation. Plan d’action engagé : Maintien conditionnel à court terme avec chiffrement applicatif des champs sensibles, rétention minimale et vérification immédiate des lieux; migrer ou suspendre les sauvegardes américaines si l'encadrement demeure insuffisant.

Mesures : vérifier les protocoles de chiffrement natifs de l'hébergeur; à défaut de contrôle sur ses sauvegardes, implémenter un chiffrement applicatif Laravel au moyen de `encrypted casts` pour les champs les plus sensibles avant l'écriture sur disque; confirmer et documenter les copies locales au Québec; obtenir un avenant ou une preuve contractuelle écrite tenant compte des résultats de la présente évaluation et des mesures d'atténuation (un DPA générique accepté avant l'évaluation ne reflète pas ses résultats).

#### 4.L.4 Outillage interne de l'exploitant

Les outils de travail de l'exploitant, notamment les assistants d'intelligence artificielle de développement faisant appel à plusieurs fournisseurs, les consoles d'administration et les copies locales de travail, peuvent accéder à des renseignements personnels ou à des informations sur l'infrastructure. Ces accès et traitements doivent être inventoriés et encadrés par une procédure interne précisant les outils autorisés, les données interdites, les contrôles d'accès, la conservation, les destinations et la suppression.

La production du présent document au moyen d'outils d'intelligence artificielle hébergés hors Québec constitue elle-même un traitement à documenter. Il s'agit d'une limite méthodologique du présent brouillon. Mesure immédiate : ne jamais soumettre de renseignements personnels réels aux outils de rédaction et utiliser uniquement des données fictives, caviardées ou suffisamment dépersonnalisées.

### 4.M Gravatar, Automattic, États-Unis

**État actuel : RÉSOLU.** Le flux a été supprimé en production le 7 août 2026 dans la version 1.152.0. `User.php` génère désormais un avatar SVG local encodé en URI de données; aucune requête vers `gravatar.com` n'est effectuée. La description ci-dessous est conservée à des fins rétrospectives.

#### Description du flux

| Élément | Évaluation |
|---|---|
| État historique | Confirmé avant le correctif par le code dans `app/Models/User.php:148` |
| Déclenchement | Pour tout utilisateur sans avatar téléversé, le navigateur du visiteur charge `https://www.gravatar.com/avatar/{md5 du courriel de l'utilisateur}` |
| Données | Empreinte MD5 du courriel de l'utilisateur, réversible par attaque par dictionnaire, et adresse IP du visiteur |
| Finalité | Affichage d'un avatar de remplacement |
| Destination | Automattic, États-Unis |
| Rôle du fournisseur | Traitement à ses propres fins pour certaines opérations; portée à confirmer |
| Fondement de la communication | Consentement conforme à l'article 14 à confirmer; article 8.1 (paramètres de confidentialité par défaut) à vérifier |
| Entente écrite en place? | À vérifier; la suffisance au regard des articles 17 et 18.3 doit être appréciée et décidée par le RPRP, sans validation juridique professionnelle externe |

**1. Sensibilité.** L'empreinte MD5 peut permettre de retrouver un courriel courant par attaque par dictionnaire; l'adresse IP identifie ou singularise aussi le visiteur. Niveau préliminaire : modéré.

**2. Finalité.** L'affichage d'un avatar de remplacement est accessoire et ne justifie pas clairement une communication externe automatique.

**3. Mesures de protection.** Le hachage MD5 ne constitue pas une anonymisation robuste. Le chargement distant expose automatiquement l'adresse IP du visiteur. La mesure recommandée consiste à générer localement un avatar par défaut, par exemple avec des initiales ou un fichier SVG, sans requête à Gravatar.

**4. Régime juridique.** Automattic est établie aux États-Unis, où le régime demeure sectoriel, sans loi fédérale générale uniforme; les droits et recours varient selon le contexte, et certains accès gouvernementaux peuvent relever de la FISA, article 702, ou du CLOUD Act.

**Conclusion rétrospective.** Avant le correctif, le code confirmait une communication à Automattic pour les utilisateurs sans avatar téléversé. Depuis la version 1.152.0 déployée le 7 août 2026, l'avatar est produit localement et le flux externe n'existe plus.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d'analyse multi-modèles).** Suppression du flux, mesure achevée par le déploiement de la version 1.152.0 le 7 août 2026.

Mesure achevée : `User.php` génère un avatar SVG local encodé en URI de données, sans requête externe. La vérification de non-régression doit confirmer qu'aucune requête vers `gravatar.com` ne réapparaît.
## 5. Flux dormants

Ces flux ne doivent pas être activés avant la révision de l'ÉFVP, l'identification des destinations, la réévaluation par le RPRP et la mise en place de l'entente écrite requise.

**Décision intérimaire ENTÉRINÉE par le RPRP le 7 août 2026 (sur recommandation du processus d'analyse multi-modèles).** Maintien de la désactivation de tous les flux dormants ci-dessous.
| Fournisseur ou module | Condition d'activation | Exigence avant activation |
|---|---|---|
| Chatbot public OpenRouter | Activation de la fonction publique | Réviser l'ÉFVP avant activation |
| Auto-modération OpenRouter | Activation en base ou configuration | Réviser l'ÉFVP avant activation |
| Google reCAPTCHA | Activation de l'intégration actuellement désactivée | Réviser l'ÉFVP avant activation |
| Have I Been Pwned | Activation du contrôle de mots de passe | Réviser l'ÉFVP, malgré le k-anonymat conçu pour éviter l'envoi de RP |
| Sentry | Ajout d'une configuration ou clé | Réviser l'ÉFVP avant activation |
| OAuth social | Configuration d'un fournisseur d'identité | Réviser l'ÉFVP avant activation |
| Google Meet Academy | Réactivation du module ou des rencontres | Réviser l'ÉFVP avant activation |
| Booking webhooks | Activation du module Booking | Réviser l'ÉFVP avant activation |
| VoIP.ms SMS | Câblage de l'intégration, actuellement absent | Réviser l'ÉFVP; destination annoncée au Canada à confirmer |
| Module SaaS ou Cashier | Activation du module | Réviser l'ÉFVP avant activation |
## 6. Registre des mesures d'atténuation recommandées

| Priorité | Mesure | Flux | Responsable proposé | Échéance | Preuve attendue | État |
|---|---|---|---|---|---|---|
| Critique | Achever la migration demandée vers le centre de données HostPapa de Toronto | HostPapa | Direction et technique | En cours au 7 août 2026 | Preuve de migration, architecture et destinations des sauvegardes | En cours |
| Critique | Consigner la décision d'adéquation intérimaire et le risque résiduel sans validation juridique professionnelle externe | HostPapa | RPRP | Fait le 7 août 2026 | Décision du RPRP consignée | Fait |
| Haute | Archiver toutes les ententes et preuves d'acceptation | Tous | RPRP | 30 jours | Registre contractuel | Ouvert |
| Haute | Vérifier les deux réglages de confidentialité OpenRouter | OpenRouter | Administrateur | Immédiat | Captures datées et export des réglages | Ouvert |
| Haute | Restreindre OpenRouter aux modèles approuvés sans rétention | OpenRouter | Technique et RPRP | 30 jours | Liste d'autorisation | Ouvert |
| Haute | Vérifier l'acceptation des Google Ads Data Processing Terms | GA4 | Administrateur | Immédiat | Capture Admin GA4 | Ouvert |
| Haute | Tester automatiquement le refus GA4 avant consentement | GA4 | Technique | 30 jours | Rapport de test réseau | Ouvert |
| Haute | Remplacer Gravatar par un avatar SVG local | Gravatar | Technique | 7 août 2026 | Version 1.152.0 et test réseau | Fait |
| Haute | Encadrer l'outillage interne par une procédure interdisant les renseignements personnels réels dans les outils de rédaction | Outillage interne | Direction et RPRP | Immédiat | Procédure adoptée et inventaire des outils | Ouvert |
| Haute | Vérifier si `AKISMET_KEY` est configurée | Akismet | Technique | Immédiat | Constat de configuration sans divulguer la clé | Ouvert |
| Haute | Instituer l'approbation préalable des webhooks | Webhooks | Direction et RPRP | Immédiat | Procédure adoptée | Ouvert |
| Haute | Cartographier imprimeurs, transporteurs et pays | Gelato | Opérations | 30 jours | Liste des sous-traitants et destinations | Ouvert |
| Moyenne | Réduire les durées de conservation et documenter la suppression | Tous | RPRP | 60 jours | Calendrier approuvé | Ouvert |
| Moyenne | Vérifier la localisation des sauvegardes et journaux | HostPapa | Technique | 30 jours | Attestation ou configuration | Ouvert |
| Moyenne | Désactiver les fonctions Cloudflare inutiles et le cache sensible | Cloudflare | Technique | 30 jours | Inventaire de configuration | Ouvert |
| Moyenne | Revoir les modèles de courriel pour minimiser leur contenu | Google Workspace | Opérations | 60 jours | Modèles révisés | Ouvert |
| Moyenne | Clarifier les rôles contractuels propres à Stripe | Stripe | RPRP | 60 jours | Note contractuelle | Ouvert |
| Moyenne | Vérifier les transferts ultérieurs hors UE | Brevo | RPRP | 60 jours | Liste de sous-traitants | Ouvert |
| Moyenne | Activer et contrôler l'authentification multifacteur | Tous | Technique | 30 jours | Rapport d'accès | Ouvert |
| Faible | Répéter la recherche de secrets et de données dans GitHub | GitHub | Technique | Trimestriel | Rapport d'analyse | Ouvert |
### 6.1 Période intérimaire et conditions de suivi

Pendant la migration vers Toronto, les flux actifs se poursuivent avec les mesures d'atténuation entérinées. Le RPRP doit suivre l'exécution jusqu'à ce que :
- la migration de l'hébergement principal et des sauvegardes vers Toronto soit achevée et documentée;
- la présente ÉFVP soit réévaluée en fonction du régime canadien et ontarien, puisque l'article 17 continue de s'appliquer hors Québec;
- les ententes écrites applicables aient été obtenues, examinées et archivées;
- les réglages OpenRouter, GA4 et Akismet aient été vérifiés;
- les destinations variables de Gelato et des webhooks aient été suffisamment encadrées;
- les mesures critiques et hautes aient un responsable et une date ferme.
## 7. Transferts déjà effectués avant la présente évaluation (volet rétrospectif)

Les flux décrits comme actifs dans la présente évaluation étaient déjà en fonction avant la réalisation de toute ÉFVP les visant. Le présent brouillon ne permet pas, à lui seul, de qualifier rétroactivement chaque communication ni de conclure qu'une autorisation ou une protection adéquate existait aux dates pertinentes.

Actions requises :

- dater le début de chaque transfert, avec une preuve raisonnablement disponible;
- identifier et archiver les versions contractuelles alors applicables;
- faire décider par le RPRP, sur recommandation du processus d'analyse multi-modèles et sans validation juridique professionnelle externe, si les écarts constatés constituent une lacune de gouvernance ou des communications non autorisées à documenter;
- établir un plan de remédiation rétrospectif approuvé par le RPRP, assorti de responsables, d'échéances, de preuves et d'une piste d'audit traçable;
- conserver la preuve des décisions intérimaires entérinées le 7 août 2026 : suppression de Gravatar, poursuite avec atténuation des autres flux actifs et maintien de la désactivation des flux dormants;
- documenter la période intérimaire de l'hébergement américain et l'avancement de la migration demandée vers Toronto.

L'inaction prolonge l'exposition juridique et opérationnelle. Selon l'analyse retenue par le RPRP, sans validation juridique professionnelle externe, la loi prévoit des recours privés; des dommages-intérêts punitifs peuvent notamment être réclamés en cas d'atteinte illicite et intentionnelle ou de faute lourde. Aucun montant ni résultat ne peut être tenu pour acquis dans le présent brouillon.

## 8. Questions ouvertes (à réévaluer périodiquement par le RPRP; une consultation juridique reste recommandée si les moyens le permettent)

1. Le serveur d'hébergement situé aux États-Unis satisfait-il la norme de protection adéquate de l'article 17, ou une migration au Canada ou des mesures supplémentaires s'imposent-elles?
2. Les DPA d'adhésion d'OpenRouter, de Cloudflare et de Google valent-ils « entente écrite » au sens de l'article 17, alinéa 2, dans leur mode précis d'acceptation par 9307-6719 Québec inc.?
3. Comment apprécier l'adéquation du régime américain à la lumière de la jurisprudence européenne sur les transferts, sans transposer indûment le droit européen au Québec?
4. GA4, auquel l'adresse IP est transmise et dont le fournisseur affirme qu'il ne la consigne pas, mais qui utilise un Client-ID avec Consent Mode v2 et consentement préalable, constitue-t-il une communication de renseignements personnels hors Québec au sens de la LPRPSP?
5. L'article 17 exige-t-il une entente distincte ou des clauses particulières lorsque le fournisseur agit aussi comme responsable indépendant, notamment Stripe ou Google?
6. Les clauses contractuelles types européennes et le Data Privacy Framework constituent-ils des mesures suffisantes, pertinentes ou seulement indicatives pour l'analyse québécoise?
7. Quelles mesures supplémentaires seraient nécessaires contre l'accès gouvernemental américain pour l'hébergement, le courriel et les services d'IA?
8. Le réseau mondial de Gelato permet-il une conclusion par catégories de pays, ou faut-il évaluer chaque pays d'impression et de livraison?
9. Le texte libre transmis à OpenRouter doit-il être réputé sensible par défaut en raison du risque de contenu imprévisible?
10. La conservation de métadonnées par OpenRouter modifie-t-elle l'analyse lorsque les prompts ne sont pas conservés?
11. Le DPA de chaque fournisseur tient-il suffisamment compte des résultats particuliers de cette ÉFVP et des mesures d'atténuation, ou faut-il un avenant?
12. Quelles obligations d'information ou de consentement s'appliquent séparément à GA4, AdSense, Turnstile et Akismet?
13. Une destination canadienne hors Québec nécessite-t-elle les mêmes précautions contractuelles même lorsque son régime paraît comparable?
14. Quelle preuve documentaire faut-il conserver pour démontrer que l'ÉFVP a été réalisée avant les communications pertinentes?
15. Le flux GitHub concernant les identités professionnelles des contributeurs doit-il faire l'objet d'une analyse distincte relative aux employés ou mandataires?
16. Les clauses de confidentialité des contrats de soutien technique couvrent-elles adéquatement les transferts épisodiques par accès à distance?
17. Pour chaque flux déjà actif avant la présente ÉFVP, comment qualifier juridiquement l'absence d'évaluation préalable et quels faits permettent de distinguer une lacune de gouvernance d'une communication non autorisée?
18. Quelles mesures de remédiation rétrospective, notifications, consignations ou autres démarches sont requises, et quelles versions contractuelles historiques faut-il conserver comme preuve?
19. Pour les fournisseurs-mandataires, les ententes correspondent-elles, clause par clause, aux exigences de l'article 18.3, en plus de répondre aux exigences de l'article 17?
20. Lorsque GA4, AdSense ou Gravatar traite des renseignements à ses propres fins, le fondement de la communication repose-t-il sur un consentement conforme à l'article 14, et les paramètres de confidentialité par défaut respectent-ils l'article 8.1?
21. Le RPRP doit être une personne désignée, soit par défaut la personne ayant la plus haute autorité, avec possibilité de délégation écrite selon l'article 3.1. Qui doit être désigné nominalement, et comment publier son titre plutôt que de mentionner seulement une boîte courriel?
22. L'entreprise est-elle également assujettie à la Loi sur la protection des renseignements personnels et les documents électroniques du Canada (LPRPDE) pour certains flux ou certaines activités?
23. Compte tenu notamment d'un lectorat européen francophone, le RGPD s'applique-t-il à certaines activités, et la désignation d'un représentant dans l'Union européenne en vertu de son article 27 est-elle requise?

## 9. Registre des révisions

| Version | Date | Auteur ou responsable | Modification | Validation |
|---|---|---|---|---|
| 0.1 | 7 août 2026 | RPRP | Brouillon initial fondé sur l'inventaire et les vérifications datées | Ancien état : validation juridique alors envisagée, mais non obtenue |
| 0.2 | 7 août 2026 | RPRP | Corrections du premier cycle de réfutation : conclusions factuelles, terminaison TLS, régimes juridiques, GA4, accès à distance, flux connexes, ententes et arbitrage méthodologique | Ancien état : validation juridique alors envisagée, mais non obtenue |
| 0.3 | 7 août 2026 | RPRP | Brouillon de travail 0.3 : décisions provisoires recommandées, preuves bornées, volet rétrospectif, responsabilité décisionnelle de l'entreprise et mesures contractuelles propres à l'évaluation | Ancien état : entérinement et consultation juridique alors envisagés, mais non réalisés sous cette version |
| 0.4 | 7 août 2026 | RPRP | Brouillon de travail 0.4 : cumul des articles 17 et 18.3, fondement juridique par flux, indices documentés, ajout de Gravatar et de l'outillage interne, précision sur GA4, décisions intérimaires et questions juridiques additionnelles | Ancien état : entérinement et consultation juridique alors envisagés, mais non réalisés sous cette version |
| 0.5 | 7 août 2026 | RPRP | Brouillon de travail 0.5 : décisions intérimaires entérinées, migration HostPapa vers Toronto en cours, suppression de Gravatar, projet AdSense sous conditions suspensives et régime décisionnel sans consultation juridique externe | Entériné par le RPRP sur recommandation du processus d'analyse multi-modèles; aucune validation juridique professionnelle externe |

> **Note de méthode.** Les versions 0.1 à 0.4 ont bénéficié de réfutations croisées produites par plusieurs outils d'intelligence artificielle indépendants. Ces outils constituent une provenance méthodologique et non les auteurs, propriétaires ou décideurs du document.
Toute révision doit indiquer les fournisseurs ajoutés ou retirés, les flux modifiés, les nouvelles finalités, les changements de pays, les versions contractuelles et la décision du RPRP.
## Références primaires et documents fournisseurs

### Sources québécoises

- Légis Québec, Loi sur la protection des renseignements personnels dans le secteur privé, RLRQ, c. P-39.1, art. 3.1, 8.1, 14, 17 et 18.3 : <https://www.legisquebec.gouv.qc.ca/fr/document/lc/P-39.1>
- Commission d'accès à l'information du Québec, Guide d'accompagnement à la réalisation des évaluations des facteurs relatifs à la vie privée, version 3.1, avril 2024 : <https://www.cai.gouv.qc.ca/uploads/pdfs/CAI_GU_EFVP.pdf>
### Ententes et documentation des fournisseurs

- HostPapa, Addendum relatif au traitement des données : <https://www.hostpapa.com/privacy/data-processing-agreement/>
- Cloudflare, Data Processing Addendum, version 6.4, effectif le 3 avril 2026 : <https://www.cloudflare.com/cloudflare-customer-dpa/>
- Cloudflare, Data Privacy Framework : <https://www.cloudflare.com/trust-hub/privacy-and-data-protection/>
- Google, Cloud Data Processing Addendum : <https://cloud.google.com/terms/data-processing-addendum>
- Google, Google Ads Data Processing Terms : <https://business.safety.google/adsprocessorterms/>
- Google, lieux de stockage des données Workspace : <https://support.google.com/a/answer/9223653>
- OpenRouter, Conditions, incluant l'incorporation du DPA : <https://openrouter.ai/terms>
- OpenRouter, Data Processing Agreement : <https://openrouter.ai/dpa>
- OpenRouter, documentation sur la confidentialité et la rétention des fournisseurs : <https://openrouter.ai/docs/guides/privacy/provider-logging>
- Brevo, Data Processing Agreement : <https://www.brevo.com/legal/termsofuse/data-processing-agreement/>
- Stripe, Data Processing Agreement : <https://stripe.com/legal/dpa>
- Gelato, Data Processing Agreement : <https://www.gelato.com/legal/data-processing-terms>
- Automattic, Data Processing Addendum : <https://automattic.com/privacy/data-processing-addendum/>
- Akismet, politique de confidentialité : <https://akismet.com/privacy/>
- GitHub, Data Protection Agreement : <https://github.com/customer-terms/github-data-protection-agreement>
### Source comparative signalant un risque

- noyb, dossiers et décisions relatifs à Google Analytics et aux transferts vers les États-Unis : <https://noyb.eu/en/101-complaints-eu-us-transfers-filed>
Les URL, versions et conditions doivent être revérifiées lors de chaque réévaluation par le RPRP. Une source contractuelle publique ne prouve pas, à elle seule, que 9307-6719 Québec inc. l'a valablement acceptée ni qu'elle régit le compte utilisé.
> **Fin du brouillon interne - Les décisions sont rendues par le responsable de la protection des renseignements personnels, sur recommandation d'un processus d'analyse multi-modèles, en toute connaissance du fait qu'aucune validation juridique professionnelle externe n'a été obtenue.**
