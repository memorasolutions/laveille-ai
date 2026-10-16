# Évaluation des facteurs relatifs à la vie privée

## Projet : authentification unique (SSO) laveille.ai -> académie Moodle et vente de cours en ligne

> **Brouillon de travail 0.3 préparé sous l'autorité du RPRP le 13 octobre 2026, enrichi le 15 octobre 2026 (v0.2), puis révisé le 15 octobre 2026 contre le texte de loi (v0.3). Ne constitue pas un avis juridique et n'a fait l'objet d'aucune validation juridique professionnelle externe. ADOPTÉE par le RPRP le 16 octobre 2026, sous réserve des conditions de suivi 6.3 à 6.16.**
> **Document interne et confidentiel - Ne pas publier.**
> **Gate : l'ÉFVP est ADOPTÉE par le RPRP le 16 octobre 2026. Aucun flux n'est néanmoins câblé en production tant que les conditions de suivi 6.3 à 6.16 ne sont pas satisfaites et PROUVÉES, et tant que la coordination avec l'académie et le premier cours réel ne sont pas prêts. Le code d'intégration reste réversible et désactivé par défaut d'ici là.**

## 1. Fiche de contrôle

| Champ | Valeur |
|---|---|
| Organisation | 9307-6719 Québec inc. |
| Service visé | laveille.ai (fournisseur d'identité) et académie formations.laveille.ai (Moodle, moteur de formation) |
| Responsable de la protection des renseignements personnels | confidentialite@laveille.ai |
| Objet | Fédération d'identité par SSO (OAuth2/OpenID) de laveille.ai vers l'académie Moodle, et flux de vente de cours (paiement Stripe puis inscription au cours) |
| Date de l'évaluation | 13 octobre 2026 (enrichie puis révisée le 15 octobre 2026) |
| Version | 0.3 |
| Statut | adoptée sous conditions; aucun flux live avant satisfaction prouvée des conditions 6.3 à 6.16 |
| Classification | Interne et confidentiel |
| Décision | ADOPTÉE par le RPRP le 16 octobre 2026, sous réserve des conditions de suivi 6.3 à 6.16 à satisfaire et PROUVER avant tout allumage |
| Prochaine révision planifiée | Avant tout premier allumage réel du SSO ou de la vente |
| Déclencheur de révision | Activation du SSO, activation de la vente, changement de fournisseur de paiement, changement de fournisseur de courriel, changement d'hébergement de l'académie |

### 1.1 Portée

La présente ÉFVP examine un PROJET au sens de l'article 3.3 de la Loi 25 (modifiant la LPRPSP) : la mise en service d'un système d'information qui recueille, utilise, communique ou conserve des renseignements personnels. Deux flux nouveaux sont visés :

1. **Fédération d'identité (SSO)** : laveille.ai agit comme fournisseur d'identité (OAuth2/OpenID Connect, serveur Laravel Passport). L'académie Moodle (`formations.laveille.ai`) agit comme client. À la connexion, laveille communique à Moodle un jeu MINIMAL de revendications d'identité pour créer ou mettre à jour le compte de l'apprenant et ouvrir sa session, sans seconde saisie de mot de passe.
2. **Vente de cours** : l'achat d'un cours payant est traité par Stripe; après confirmation du paiement, laveille inscrit l'acheteur au cours dans Moodle par un appel de service web, selon le contrat arrêté avec la session de l'académie le 13 octobre 2026.

La présente évaluation NE réexamine PAS les destinataires déjà inventoriés dans l'ÉFVP "Communication de renseignements personnels hors Québec" (ci-après l'"ÉFVP de base", `docs/legal/EFVP-transferts-hors-quebec.md`). Elle s'y RÉFÈRE pour l'hébergement principal (section 4.A), Stripe (section 4.F), Cloudflare (4.B), les accès humains à distance (4.K) et les sauvegardes (4.L.3), ainsi que pour la méthodologie (section 2), l'échelle qualitative (2.3) et le cadre juridique (section 3). Elle ne recense ici que ce qui est NOUVEAU dans ces deux flux. Le préalable de connexion (code OTP par courriel) révèle un destinataire hors Québec non encore inventorié, traité en 4.9.

### 1.2 Responsabilités internes

Identiques à la section 1.2 de l'ÉFVP de base. S'y ajoute la frontière de propriété arrêtée avec la session de l'académie : laveille est la SOURCE DE VÉRITÉ des droits d'accès; Moodle ne décide jamais seul d'un accès payant.

## 2. Méthodologie

La démarche, l'échelle qualitative et les limites sont celles de l'ÉFVP de base (sections 2.1, 2.3, 2.4), suivant le Guide d'accompagnement à la réalisation des ÉFVP, version 3.1 d'avril 2024, de la Commission d'accès à l'information. L'analyse ci-dessous repose sur le contrat d'intégration convenu entre les deux sessions de développement le 13 octobre 2026, sur le code d'intégration en place mais désactivé, et sur une vérification de concordance avec le code réellement déployé le 15 octobre 2026 (voir condition 6.15). Aucune donnée réelle n'a circulé : les flux sont examinés AVANT leur premier allumage.

## 3. Cadre juridique

Outre les articles 17 et 18.3 de la LPRPSP déjà analysés dans l'ÉFVP de base (communication hors Québec), le présent projet mobilise :

- **Art. 3.3 LPRPSP** : obligation d'ÉFVP pour tout projet d'acquisition, de développement ou de refonte de système d'information impliquant des renseignements personnels (al. 1); consultation du RPRP dès le début du projet (al. 2), satisfaite ici, le document étant préparé sous son autorité depuis le cadrage du 13 octobre 2026; obligation que le projet permette la remise à la personne de ses renseignements informatisés recueillis auprès d'elle dans un format technologique structuré et couramment utilisé (al. 3, voir aussi art. 27, al. 3), à satisfaire par l'exportation de compte de laveille et par l'exportation native des données personnelles de Moodle (outil tool_dataprivacy), vérifiées avant allumage (condition 6.13); proportionnalité de l'évaluation (al. 4).
- **Art. 4 et 5 LPRPSP** (fins déterminées avant la collecte; collecte limitée au nécessaire) : fondement de la minimisation du jeu de revendications et des champs transmis à Stripe et à Moodle.
- **Art. 8, 8.2 et 8.3 LPRPSP** (transparence) : avis au point de collecte, politique publiée et diffusée, avis de toute modification de la politique; consentement aux fins annoncées par la fourniture des renseignements.
- **Art. 12 LPRPSP** (utilisation au sein de l'entreprise) : laveille.ai et l'académie sont exploités par la même personne morale; le flux d'identité est donc une UTILISATION au sein de l'entreprise, non une communication à un tiers (art. 13 et 14). L'utilisation d'une identité laveille existante pour livrer un cours que la personne a elle-même demandé relève de l'exception de l'art. 12, al. 2, par. 4° (fourniture d'un produit ou prestation d'un service demandé). Le mot "communication" désigne dans ce document le transfert technique entre les deux applications.
- **Art. 9.1 LPRPSP** : les paramètres de confidentialité de l'académie, produit technologique offert au public, assurent par défaut le plus haut niveau de confidentialité (4.6, condition 6.16).
- **Art. 17 et 18.3 LPRPSP** : analysés dans l'ÉFVP de base. L'art. 18.3 encadre tout mandataire ou prestataire, au Québec comme ailleurs; le projet n'en ajoute aucun au Québec (Moodle est un logiciel libre auto-hébergé, non un prestataire), mais le courriel transactionnel révèle un destinataire hors Québec non inventorié (4.9).
- **Art. 23 LPRPSP** et Règlement sur l'anonymisation des renseignements personnels (RLRQ, c. A-2.1, r. 0.1) : destruction ou anonymisation à l'accomplissement des fins (4.5).
- **Art. 3.5 à 3.8 LPRPSP** et Règlement sur les incidents de confidentialité (RLRQ, c. A-2.1, r. 3.1) : incidents et registre (4.7).

## 4. Flux nouveaux analysés

### 4.1 Fédération d'identité par SSO (laveille -> Moodle)

**Nature.** À la connexion d'un apprenant déjà authentifié sur laveille.ai, le serveur d'identité Laravel Passport émet un jeton et communique à l'académie Moodle un jeu de revendications d'identité par le protocole OAuth2/OpenID Connect.

**Catégories de renseignements communiquées (jeu minimal).**

| Revendication OIDC | Finalité | Nécessité |
|---|---|---|
| `sub` (identifiant numérique laveille, stable, unique, jamais réattribué; recopié dans le champ `idnumber` du profil Moodle) | Clé unique de rattachement du compte Moodle (table `auth_oauth2_linked_login`, voir 4.8) | Nécessaire : seule clé d'appariement |
| `email` | Création du compte, notifications, rapprochement d'un compte créé par achat à la première connexion (4.8) | Nécessaire à la création du compte |
| `email_verified` (booléen) | Permettre au client de refuser une adresse non confirmée | Nécessaire; ne porte aucun renseignement nouveau |
| `given_name`, `family_name` | Affichage du nom dans l'académie et sur le certificat | Nécessaire à l'expérience et au certificat |

Le point de terminaison `/api/oauth/userinfo` émet aujourd'hui aussi `name` (redondant avec prénom et nom) et `picture` (URL de l'avatar, que le client Moodle copie dans le profil). Ni l'un ni l'autre n'est nécessaire à l'académie : ils sont retirés de la liste `idp.claims` (`Modules/Idp/config/config.php`) avant tout allumage (condition 6.3). Constat du 15 octobre 2026, fait par lecture du code et non du contrat : la version 0.2 déclarait trois revendications alors que sept étaient émises. Aucun mot de passe, aucun renseignement de paiement, aucun renseignement sensible au sens de l'art. 12, al. 4, par. 2° n'est communiqué par le SSO. Les renseignements pédagogiques produits ensuite dans Moodle (progression, résultats, contributions aux forums) sont des renseignements personnels non sensibles pour l'offre actuelle (formation professionnelle en intelligence artificielle); l'ajout d'un cours dont le thème ou les activités amèneraient l'apprenant à produire des renseignements sensibles au sens de l'art. 12, al. 4, par. 2° déclenche une révision de la présente ÉFVP.

**Destination et hébergement.** L'académie Moodle (`formations.laveille.ai`) est hébergée sur le MÊME compte cPanel (`gmemora`) et le MÊME serveur que laveille.ai. La communication des revendications d'identité demeure donc à l'intérieur de l'environnement d'hébergement déjà inventorié à la section 4.A de l'ÉFVP de base (hébergement principal). **Le SSO n'introduit AUCUN destinataire nouveau par lui-même** (voir toutefois 4.9 pour le préalable de connexion). Le risque de transfert hors Québec de ce flux d'identité est déjà couvert et borné par l'analyse 4.A existante.

**Sens unique de l'identité.** Le profil d'identité de l'académie est en lecture seule : les revendications sont rafraîchies à chaque connexion depuis laveille (claims `given_name`/`family_name`, déployés en v1.337.0). L'apprenant modifie son identité sur laveille, jamais sur Moodle. Ceci réduit la dispersion de la donnée d'identité et le risque d'incohérence.

**Risques et mesures.**

| Risque | Mesure |
|---|---|
| Communication de revendications superflues | Jeu de revendications limité aux cinq ci-dessus; `name` et `picture` retirés de `idp.claims` avant allumage; aucune extension sans révision de la présente ÉFVP |
| Appariement erroné de deux comptes | Clé d'appariement = `sub` (identifiant laveille), jamais le seul courriel, qui peut changer; cas du compte créé par achat avant la première connexion traité en 4.8 |
| Session Moodle persistante après déconnexion de laveille | Déconnexion unique notée comme amélioration à basse priorité (section 8); risque résiduel PROPOSÉ à l'acceptation du RPRP pour l'intérim |
| Activation prématurée | Code Passport réversible et désactivé par défaut; allumage conditionné à la présente ÉFVP et au premier cours réel |

### 4.2 Vente de cours (paiement Stripe puis inscription Moodle)

**Nature.** L'achat d'un cours payant suit la chaîne : l'apprenant paie par Stripe sur laveille.ai; après confirmation du paiement (webhook Stripe reçu par laveille), laveille enregistre le droit d'accès (entitlement), puis appelle le service web Moodle pour créer au besoin le compte de l'apprenant (`core_user_create_users`, apparié par `idnumber` et courriel) et l'inscrire au cours (`enrol_manual_enrol_users`).

**Catégories de renseignements et destinations.**

| Renseignement | Destinataire | Déjà inventorié? |
|---|---|---|
| Renseignements de paiement (numéro de carte, etc.) | Stripe uniquement | Oui, ÉFVP de base section 4.F. **Ces renseignements ne transitent jamais par laveille ni par Moodle** : la saisie se fait dans l'interface Stripe. |
| Identifiant de client et de paiement Stripe, montant, statut | Conservés par laveille (droit d'accès) | Nouveau, conservé à l'hébergement principal (4.A), pas de nouveau destinataire externe |
| Métadonnées de la session de paiement (identifiant laveille `sub`, identifiant du cours, courriel pré-rempli) | Stripe (hors Québec, 4.F) | Mêmes catégories et même finalité (traitement d'un paiement) que celles évaluées en 4.F; seule la ligne de produit est nouvelle. La liste exacte des champs est figée et vérifiée avant allumage (6.12); aucun autre champ. |
| `idnumber`, courriel, prénom, nom | Moodle (même hébergement, 4.A) | Couvert par 4.1 ci-dessus |

**Point clé de minimisation.** Moodle ne reçoit JAMAIS de renseignement de paiement. Il reçoit uniquement l'ordre d'inscrire un apprenant identifié à un cours. La séparation paiement (Stripe) / livraison (Moodle) est une mesure de minimisation structurelle.

**Anti-contournement et intégrité.**

| Risque | Mesure |
|---|---|
| Inscription gratuite contournant le paiement | Pour un cours payant, la méthode d'inscription Moodle reste MANUELLE (inscription seulement après paiement confirmé); aucune auto-inscription ouverte sur un cours payant |
| Rejeu du webhook Stripe (double inscription) | Déduplication par l'identifiant d'événement Stripe (`event.id`) conservé avant tout traitement; octroi du droit d'accès idempotent (insertion ou mise à jour par `sub` + identifiant du cours, test automatisé existant); appel d'inscription Moodle idempotent |
| Achat précédant la première connexion SSO | Flux "créer-ou-récupérer" le compte Moodle par `idnumber`+courriel, puis inscrire, pour un accès immédiat sans attendre la première connexion (jonction détaillée en 4.8) |
| Communication de données de paiement à un tiers non nécessaire | Données de paiement cantonnées à Stripe; laveille ne conserve que les identifiants Stripe, le montant et le statut; Moodle ne reçoit ni ne conserve AUCUN élément de paiement, pas même un identifiant |
| Remboursement ou contestation | Événements Stripe `charge.refunded` et `charge.dispute.created` : suspension du droit d'accès (jamais de suppression de la ligne) et désinscription Moodle; conservation des identifiants Stripe selon 4.5 |

**Consentement et transparence.** Au moment de l'achat et de la première connexion à l'académie, l'apprenant est informé que son identité est communiquée à la composante de formation (Moodle, même hébergeur) et que le paiement est traité par Stripe (hors Québec, voir 4.F de l'ÉFVP de base), et il y consent. Le texte exact de l'avis de confidentialité au point de collecte reste à rédiger et à réviser (section 8).

### 4.3 Certificats de réussite

À la complétion d'un cours, le certificat est émis par le module customcert de Moodle, sur le même hébergement. La page de vérification affiche, à toute personne qui détient le code (aléatoire, non devinable), le nom complet de l'apprenant, le cours et la date d'émission : c'est une communication voulue par l'apprenant, qui choisit à qui il remet son code; la vérification sans code et toute liste de certificats restent fermées (réglages `verifyany` et `verifyallcertificates`, à confirmer par la session de l'académie). laveille tire, par service web et à la demande, l'état de complétion (oui ou non, date) et le code du certificat, qu'il conserve pour les afficher dans l'espace "mes cours" de l'apprenant; même hébergement, aucun destinataire nouveau. Point ouvert : exposition de l'URL de vérification au service web (suivi côté académie), gelée avec la vente.

### 4.4 Avis de confidentialité au point de collecte (art. 8, 8.2, 8.3 et 14 LPRPSP)

Au moment de l'achat (page de paiement) et à la première connexion à l'académie, l'apprenant reçoit, en termes simples et clairs (art. 8 al. 4), un avis contextuel qui l'informe :

- des **fins** : lui donner accès au cours, créer et gérer son compte d'apprenant dans l'académie, et facturer l'achat;
- des **moyens** : le formulaire d'achat (paiement Stripe) et l'authentification unique (SSO) depuis son compte laveille;
- de ses **droits d'accès et de rectification** et de son **droit de retirer son consentement** à la communication ou à l'utilisation des renseignements;
- des **tiers** : Stripe traite le paiement (hors Québec, voir 4.F de l'ÉFVP de base); la composante de formation (Moodle) est co-hébergée (4.A). L'avis mentionne explicitement la **possibilité de communication hors Québec** (Stripe, et le fournisseur du code de connexion, voir 4.9);
- **sur demande** (art. 8, al. 3) : les renseignements personnels recueillis auprès d'elle, les catégories de personnes qui y ont accès au sein de l'entreprise (administration de laveille, administration de l'académie, soutien), la durée de conservation (4.5) et les coordonnées du RPRP (confidentialite@laveille.ai).

L'avis contextuel renvoie à la politique de confidentialité publiée sur le site (art. 8.2). La section ajoutée à la politique pour l'académie fait l'objet d'un AVIS DE MODIFICATION, publié et diffusé par un moyen propre à atteindre les personnes concernées (art. 8.2, 2e phrase), par exemple un bandeau à la connexion ou un courriel aux comptes existants. Fondement de l'utilisation de l'identité : pour les renseignements fournis au moment de l'achat ou de la première connexion après l'avis, art. 8.3; pour l'identité laveille préexistante, créée à d'autres fins, art. 12, al. 2, par. 4° (service demandé par la personne), l'avis assurant la transparence; le consentement à la communication hors Québec à Stripe demeure manifeste, libre et éclairé (art. 14). Le texte exact de l'avis et de la section de la politique est rédigé et révisé avant le premier allumage du SSO ou de la vente, selon le premier des deux (un cours gratuit ouvert par SSO peut précéder toute vente) (condition 6.5).

### 4.5 Durée de conservation et destruction (art. 23 LPRPSP)

Conformément à l'article 23, un renseignement est détruit lorsque les fins de sa collecte ou de son utilisation sont accomplies, sous réserve d'un délai de conservation prévu par une loi. L'anonymisation n'est une solution de remplacement que pour une utilisation à des fins sérieuses et légitimes (statistiques de complétion, par exemple) et seulement si elle est irréversible au sens de l'art. 23, al. 2, réalisée selon le Règlement sur l'anonymisation des renseignements personnels (RLRQ, c. A-2.1, r. 0.1). Par défaut, le projet DÉTRUIT.

| Renseignement | Durée de conservation | Fondement |
|---|---|---|
| Droit d'accès (entitlement) et inscription au cours | Tant que l'accès au cours est actif, plus un délai raisonnable après la fin pour le support et la reprise, puis destruction ou anonymisation | Art. 23 : fin des fins |
| Identifiants et statut Stripe (identifiant de client, de paiement, montant, statut) | Durée de conservation des pièces comptables et fiscales applicable (obligations fiscales du Québec et du Canada), puis destruction | Art. 23 : délai de conservation prévu par une loi (fiscale) |
| Revendications d'identité dans Moodle (sub, courriel, prénom, nom, et données pédagogiques produites dans Moodle) | Tant que la relation d'apprentissage est active; politique de suppression à l'inactivité à arrêter avec l'académie | Art. 23 |
| Journaux applicatifs et d'accès (adresses IP, actions, horodatages) | Durée fixée en 6.10, puis purge automatique | Art. 23 |
| Certificats émis (nom, cours, date, code) | Durée fixée en 6.10 | Art. 23 |

La durée exacte des pièces Stripe est confirmée avec la comptabilité avant l'allumage de la vente (condition de suivi, section 6).

### 4.6 Mesures de sécurité (art. 10 LPRPSP)

Mesures inventoriées avec la session de l'académie (document d'entrées Moodle, 2026-10-15). Au moment de la vérification de la condition 6.7, chaque mesure reçoit son statut [EN PLACE, preuve datée] ou [EXIGÉE avant allumage]; aucune mesure n'est réputée en place sans preuve (commande, capture ou test, avec date).

- **Jetons de service web** rangés dans 1Password, à portée restreinte, avec agent utilisateur explicite; jamais en clair dans le dépôt ni les journaux.
- **OTP d'entrée** à durée de vie courte; aucun mot de passe d'apprenant stocké côté académie (SSO forcé).
- **Webhooks signés** (signature Stripe et horodatage, fenêtre de tolérance) contre la falsification et le rejeu tardif; déduplication par `event.id` contre la redistribution légitime d'un même événement.
- **Journaux sans renseignement sensible** : aucun renseignement de paiement n'est journalisé.
- **Accès d'administration restreint**; pas de page de connexion de l'académie exposée publiquement.
- **HTTPS/HSTS**; fichiers authentifiés servis hors du cache du réseau de diffusion.
- **Paramètres de confidentialité par défaut au niveau le plus élevé** (art. 9.1) : adresse de courriel masquée aux autres apprenants, profil et liste des participants non visibles aux autres apprenants, aucun champ de profil facultatif exposé, blocs d'utilisateurs en ligne désactivés.
- **Sauvegardes** (hébergeur et copies locales, inventoriées en 4.L.3 de l'ÉFVP de base) avec historique; toute restauration à partir d'une sauvegarde est suivie du rejeu du registre des suppressions et des retraits de consentement intervenus depuis, pour qu'aucune destruction ne soit annulée par un retour en arrière.
- **Cloisonnement logique** : dossier, base de données et utilisateur de base dédiés à l'académie. Les deux applications partagent le même compte d'hébergement, donc le même utilisateur système : ce cloisonnement prévient les erreurs et les requêtes croisées, il ne constitue PAS une frontière de sécurité, et la compromission de l'une expose l'autre. Risque résiduel proposé à l'acceptation du RPRP (section 5); la séparation en deux comptes d'hébergement est une option à coût (8.1).

### 4.7 Procédure d'incident de confidentialité et registre (art. 3.5 à 3.8 LPRPSP)

- **Définition** (art. 3.6) : accès, utilisation ou communication non autorisés par la loi, perte ou toute autre atteinte à la protection d'un renseignement personnel.
- **Détection et confinement** : horodatage; révocation ou restauration selon le cas, sauvegarde de l'état avant toute action; mesures raisonnables pour diminuer le risque de préjudice et éviter qu'un incident de même nature se reproduise (art. 3.5, al. 1).
- **Évaluation du risque de préjudice sérieux** (art. 3.7) : sensibilité du renseignement, conséquences appréhendées de son utilisation, probabilité d'une utilisation préjudiciable; consultation obligatoire du RPRP.
- **Avis** (art. 3.5, al. 2) : si le risque est sérieux, avis AVEC DILIGENCE à la Commission d'accès à l'information et à chaque personne concernée, selon la teneur et les modalités du Règlement sur les incidents de confidentialité (RLRQ, c. A-2.1, r. 3.1, applicable aux entreprises par son article 1).
- **Registre** (art. 3.8) : tenu par le RPRP, teneur fixée à l'article 7 du Règlement; conservé au moins 5 ans à compter de la date ou de la période où l'entreprise a pris connaissance de l'incident (article 8 du Règlement; l'art. 3.8 de la Loi ne fixe aucune durée); copie transmise à la Commission sur demande.

### 4.8 Fédération d'identité : clé de rattachement et jonction des comptes

Mesuré avec l'académie le 2026-10-15 : Moodle (auth_oauth2) lie le compte de l'apprenant par le **claim `sub`** de l'émetteur OpenID Connect (table `auth_oauth2_linked_login`), PAS par le courriel. Exigence envers le serveur d'identité (Passport) : émettre un `sub` **stable, unique par personne et jamais réattribué**. L'identifiant laveille exposé comme `sub` joue ce rôle (et sert aussi d'`idnumber` d'appariement en 4.1); le courriel n'est qu'une clé secondaire d'affichage et de notification. Le tableau 4.1 est aligné sur ce mécanisme depuis la version 0.3.

**Jonction d'un compte créé par achat avec l'identité SSO.** Un compte créé par service web (4.2) ne possède aucune ligne `auth_oauth2_linked_login` : à la première connexion SSO, Moodle ne trouve pas le `sub`, puis cherche un compte existant par COURRIEL (comportement standard de auth_oauth2, à confirmer par la session de l'académie). Trois cas sont à traiter : (a) même courriel : liaison, immédiate ou après confirmation par courriel selon le réglage `requireconfirmation` de l'émetteur; (b) courriel changé sur laveille entre l'achat et la première connexion : création d'un second compte, l'inscription restant sur le premier; (c) courriel déjà porté par un autre compte Moodle : liaison de l'identité SSO au compte d'une autre personne si la confirmation est désactivée, ce qui constituerait un incident de confidentialité (art. 3.6). Mesures : compte créé par service web avec `auth = oauth2` (aucun mot de passe), `idnumber = sub` et courriel unique dans Moodle (`allowaccountssameemail` désactivé); confirmation par courriel exigée avant toute liaison à un compte existant, OU pré-liaison du `sub` par l'académie au moment de la création; contrôle d'égalité `idnumber = sub` à chaque connexion, écart journalisé et bloquant.

### 4.9 Courriels transactionnels et journaux

**Courriels de laveille.** Le code de connexion à usage unique (OTP), préalable obligé à toute session SSO, est envoyé par Postmark (ActiveCampaign, États-Unis) depuis le 2 octobre 2026, avec repli vers le SMTP Google Workspace (`Modules/Auth/app/Http/Controllers/MagicLinkController.php`). Postmark reçoit l'adresse de courriel, le code et l'horodatage. **Postmark n'est inventorié dans aucune ÉFVP** : l'ÉFVP de base (version 0.4, 7 août 2026) ne connaît que Workspace. L'affirmation de la version 0.2 selon laquelle le SSO n'introduit aucun destinataire hors Québec nouveau est remplacée par : le SSO n'introduit aucun destinataire nouveau PAR LUI-MÊME, mais son préalable de connexion repose sur un destinataire hors Québec non encore évalué. Décision du fondateur du 16 octobre 2026 : Postmark est CONSERVÉ, pour le seul envoi du code OTP. Une section Postmark est donc ajoutée à l'ÉFVP de base pour documenter ce destinataire hors Québec (art. 17 et 18.3; engagement contractuel Postmark; lieu de traitement; transparence au point de collecte); la décision d'adéquation est tranchée en faveur du maintien, et la condition 6.14 porte désormais sur la DOCUMENTATION, non sur une décision à rendre.

**Courriels de l'académie.** Moodle envoie des courriels de création de compte, de confirmation de liaison (4.8) et d'inscription : le serveur d'envoi qu'il utilise est identifié par la session de l'académie et rattaché à une section de l'ÉFVP de base avant allumage (6.14); leur contenu est minimisé (aucun code, aucun montant).

**Journaux.** laveille (journal applicatif, journal d'accès du serveur) et Moodle (`logstore_standard` : adresse IP, actions, pages consultées) journalisent des renseignements personnels; destinataires : hébergement principal (4.A) et sauvegardes (4.L.3); accès : administration seulement; durée : fixée en 6.10.

## 5. Synthèse du risque

Le projet, tel que cadré, maintient les deux traitements nouveaux (identité et livraison) à l'INTÉRIEUR de l'environnement d'hébergement déjà évalué, et confine le paiement à un fournisseur déjà inventorié. Il n'introduit, par lui-même, AUCUN destinataire nouveau; mais le préalable de connexion révèle un destinataire hors Québec non encore inventorié (Postmark, 4.9). Le risque résiduel principal n'est pas un transfert nouveau mais : (a) la justesse du jeu de revendications et de la clé d'appariement; (b) l'étanchéité paiement/livraison; (c) la qualité de l'avis de confidentialité au point de collecte; (d) l'adéquation du fournisseur de courriel hors Québec (4.9); (e) l'absence de validation juridique professionnelle externe, documentée et proposée à l'acceptation du RPRP comme pour l'ÉFVP de base.

**Cotation des scénarios** (échelle 2.3 de l'ÉFVP de base; cotes proposées par le processus d'analyse, à confirmer par le RPRP) :

| Scénario | Vraisemblance | Gravité | Risque initial | Mesures | Résiduel |
|---|---|---|---|---|---|
| Revendications superflues émises (`name`, `picture`) | Avérée (code au 15 octobre) | Faible | Modéré | Retrait de `idp.claims`; 6.3, 6.15 | Faible |
| Rattachement SSO au compte d'une autre personne (4.8, cas c) | Faible | Élevée | Modéré | Confirmation ou pré-liaison, courriel unique, contrôle `idnumber = sub`; 6.11 | Faible |
| Compte en double après changement de courriel (4.8, cas b) | Modérée | Faible | Modéré | Mêmes mesures; procédure de fusion côté académie | Faible |
| Donnée de paiement atteignant Moodle ou un journal | Faible | Élevée | Modéré | Saisie dans Stripe seul, journaux filtrés; 6.12 | Faible |
| Accès à un cours sans paiement | Modérée | Faible (préjudice commercial, non à la vie privée) | Faible | Inscription manuelle seule; 6.12 | Faible |
| Session Moodle persistante après déconnexion de laveille | Modérée | Faible | Faible | Déconnexion unique (section 8) | Faible, proposé à l'acceptation |
| Compromission croisée des deux applications (même utilisateur système) | Faible | Élevée | Modéré | Jetons vaultés, mises à jour, journaux; 4.6 | Modéré, proposé à l'acceptation |
| Courriel OTP chez un destinataire hors Québec non évalué (Postmark) | Avérée | Modérée | À déterminer | Section Postmark de l'ÉFVP de base, décision du RPRP; 6.14 | À déterminer |
| Avis au point de collecte absent au premier allumage | Faible | Modérée | Modéré | 6.5 (SSO ou vente, le premier des deux) | Faible |

## 6. Registre des mesures et conditions de suivi

1. Ne câbler aucun flux live (SSO ou Stripe -> service web Moodle) avant révision de la présente ÉFVP et décision du RPRP.
2. Conserver le code d'intégration réversible et désactivé par défaut d'ici là.
3. Limiter les revendications SSO aux cinq du tableau 4.1 (`sub`, `email`, `email_verified`, `given_name`, `family_name`); retirer `name` et `picture` de `idp.claims` avant allumage, test `UserInfoEnabledTest` ajusté; toute extension déclenche une révision.
4. Confirmer, avant allumage, que Moodle demeure co-hébergé (même compte cPanel) : si l'académie migrait vers un autre hébergeur, un destinataire nouveau apparaîtrait et la présente ÉFVP serait rouverte.
5. Rédiger et réviser l'avis de confidentialité au point de collecte (première connexion SSO et achat) avant le premier allumage du SSO ou de la vente, selon le premier des deux (ébauche en 4.4).
6. Garder le paiement cantonné à Stripe; vérifier qu'aucun renseignement de paiement n'atteint Moodle ni n'est journalisé par laveille.
7. Confirmer, avant allumage, que les mesures de sécurité de la section 4.6 sont en place et vérifiées (jetons vaultés à portée restreinte, webhooks signés, journaux sans renseignement sensible, cloisonnement de l'académie), chaque mesure recevant son statut daté.
8. S'assurer que la procédure d'incident et le registre de la section 4.7 sont opérationnels avant tout flux live, et que le RPRP tient le registre des incidents (au moins 5 ans à compter de la prise de connaissance, art. 8 du Règlement).
9. Émettre un `sub` OIDC stable, unique et jamais réattribué (section 4.8); le rattachement Moodle en dépend.
10. Arrêter avant la vente, avec la comptabilité, la durée exacte des pièces Stripe, et avec l'académie : le délai d'inactivité qui déclenche la suppression du compte apprenant, la durée de conservation des certificats émis et des droits d'accès échus, la durée des journaux Moodle (réglage `loglifetime`) et des journaux d'accès du serveur (lignes ajoutées au tableau 4.5).
11. Spécifier avec l'académie, tester et consigner avec preuve datée la jonction compte-créé-par-achat / identité SSO pour les trois cas de la section 4.8, avant tout allumage.
12. Vérifier et consigner avant allumage, avec preuve : profil d'identité Moodle en lecture seule (4.1); méthode d'inscription manuelle seule sur tout cours payant (4.2); idempotence de l'octroi, déduplication par `event.id`, idempotence de l'inscription (4.2); traitement d'un remboursement ou d'une contestation (4.2); liste exacte des champs transmis à Stripe et à Moodle.
13. Vérifier avant allumage que l'apprenant peut obtenir ses renseignements recueillis auprès de lui dans un format structuré et couramment utilisé, depuis laveille (exportation de compte) et depuis Moodle (exportation native des données personnelles), art. 3.3, al. 3 et art. 27, al. 3.
14. Avant l'allumage du SSO : documenter Postmark dans l'ÉFVP de base comme destinataire hors Québec du seul code OTP (décision du fondateur du 16 octobre 2026 : Postmark conservé; art. 17/18.3); identifier le serveur d'envoi des courriels de Moodle et le rattacher à une section de l'ÉFVP de base.
15. Avant la décision du RPRP, puis à chaque révision, exécuter et annexer avec preuve datée une vérification de concordance entre le système réellement déployé et la présente ÉFVP : revendications réellement émises par `/api/oauth/userinfo` (config `idp.claims` et test), champs réellement transmis à Stripe (session de paiement) et à Moodle (appels de service web), réglages réels de l'émetteur auth_oauth2 (`requireconfirmation`, correspondance des champs) et de customcert (vérification), serveur d'envoi réel des courriels. Première exécution le 15 octobre 2026 : écart trouvé sur les revendications (sept émises, trois déclarées), corrigé par la condition 6.3. Une ÉFVP n'est approuvée que sur l'état mesuré, jamais sur l'état décrit.
16. Configurer et vérifier, avec preuve, les paramètres par défaut de la puce 4.6 "art. 9.1" avant allumage; tout assouplissement est une décision du RPRP.

## 7. Flux dormants

Événements Moodle -> laveille (complétion, certificat) poussés par un plugin : NON activé. laveille tire l'information par service web à la demande. L'activation d'un tel poussé ajouterait un flux de données à réévaluer ici.

## 8. Questions ouvertes (à réévaluer par le RPRP; une consultation juridique reste recommandée)

- Avis de confidentialité au point de collecte : ÉBAUCHE rédigée en 4.4; reste à valider le texte final et son emplacement exact avant le premier allumage du SSO ou de la vente.
- Durée de conservation : cadre posé en 4.5; reste à confirmer les durées exactes (pièces Stripe, inactivité des comptes, certificats, journaux) avec la comptabilité et l'académie (6.10).
- Déconnexion unique (clore la session Moodle à la déconnexion de laveille) : opportunité et portée.
- Exposition au service web de l'URL/code de vérification du certificat (suivi côté académie).
- Opportunité d'une validation juridique professionnelle externe avant l'allumage de la vente.

### 8.1 Décisions qui relèvent du fondateur (hors du pouvoir de Claude)

1. **Adoption de la présente ÉFVP et décision du RPRP** : DÉCISION RENDUE le 16 octobre 2026 : ÉFVP ADOPTÉE sous conditions. Aucun flux live tant que les conditions 6.3 à 6.16 ne sont pas satisfaites et prouvées, ni avant la coordination avec l'académie et le premier cours réel. Claude prépare et prouve les conditions; il n'allume pas le SSO de lui-même.
2. **Validation juridique professionnelle externe** : recommandée en priorité sur deux points que l'écriture ne peut pas fermer : la décision d'adéquation de l'art. 17 pour Postmark (4.9), et la qualification "utilisation au sein de l'entreprise" de l'art. 12, al. 2, par. 4° retenue en section 3, qui allège le régime de consentement et doit être confirmée par un juriste.
3. **Fournisseur du code OTP (4.9)** : TRANCHÉ le 16 octobre 2026 : Postmark est conservé pour le seul code OTP. Reste la documentation de ce destinataire dans l'ÉFVP de base (préparation par Claude, suivi #3136).
4. **Durées de conservation chiffrées (4.5, 6.10)** : pièces Stripe (délai fiscal à confirmer avec la comptabilité), inactivité des comptes, certificats, journaux. Chaque chiffre engage l'entreprise devant un apprenant et devant la Commission.
5. **Avatar dans l'académie (4.1)** : la minimisation retire `picture` et `name` par défaut; si le fondateur veut l'avatar dans Moodle, le tableau 4.1 reçoit une ligne de plus avec sa finalité, au lieu du retrait.
6. **Confirmation par courriel à la première connexion (`requireconfirmation`, 4.8)** : l'exiger ajoute une friction mais protège contre le cas (c) de rattachement erroné. Choix produit à arrêter avec la session de l'académie (coordination, jamais chacun de son côté).
7. **Cloisonnement (4.6)** : accepter le risque résiduel du même utilisateur système, ou payer un second compte d'hébergement.
8. **Cotes de risque (section 5)** : proposées; le RPRP les entérine ou les corrige. Une cote d'ÉFVP n'est pas un fait, c'est un jugement signé.
9. **Confirmation du courriel du RPRP** (confidentialite@laveille.ai) comme coordonnée publiée dans l'avis et la politique.

## 9. Registre des révisions

| Version | Date | Auteur | Changement |
|---|---|---|---|
| 0.1 | 13 octobre 2026 | RPRP (brouillon préparé par processus d'analyse multi-modèles, sans avis juridique) | Création : cadrage du projet SSO + vente, analyse des flux nouveaux, gate d'allumage |
| 0.2 | 15 octobre 2026 | RPRP (enrichissement par processus d'analyse multi-modèles, sans avis juridique) | Ajout de l'avis au point de collecte (art. 8), de la durée de conservation (art. 23), des mesures de sécurité (art. 10), de la procédure d'incident et du registre (art. 3.5 à 3.8); correction de la clé de rattachement SSO (claim sub, et non le courriel); décisions du fondateur isolées en 8.1 |
| 0.3 | 15 octobre 2026 | RPRP (révision adversariale par processus d'analyse multi-modèles, sans avis juridique) | Alignement du jeu de revendications sur le code réel (7 émises, 5 retenues, 2 à retirer); jonction achat-SSO (4.8); Postmark et courriels de l'académie (4.9); art. 3.3 al. 2-3, 4, 5, 9.1, 12 al. 2 par. 4°; références exactes du registre d'incidents (Règlement, art. 7 et 8); cotation des scénarios; conditions 6.11 à 6.16 |
| (décision) | 16 octobre 2026 | Fondateur / RPRP | ÉFVP ADOPTÉE sous réserve des conditions 6.3 à 6.16; Postmark CONSERVÉ pour le seul code OTP (documentation à ajouter à l'ÉFVP de base, #3136) |

## Références

- ÉFVP de base : `docs/legal/EFVP-transferts-hors-quebec.md` (hébergement 4.A, Stripe 4.F, Cloudflare 4.B, accès à distance 4.K, sauvegardes 4.L.3, méthodologie, échelle, cadre juridique).
- Contrat d'intégration des deux plateformes : `docs/memora-portal-connecteur.md` et le document d'architecture de la session académie `docs/ARCHITECTURE-2-PLATEFORMES.md` (dépôt Moodle).
- Loi sur la protection des renseignements personnels dans le secteur privé, RLRQ, c. P-39.1 (telle que modifiée par la Loi 25), à jour au 10 juin 2026.
- Règlement sur les incidents de confidentialité, RLRQ, c. A-2.1, r. 3.1 (applicable aux entreprises par son art. 1), à jour au 1er mai 2026.
- Règlement sur l'anonymisation des renseignements personnels, RLRQ, c. A-2.1, r. 0.1.
- Guide d'accompagnement à la réalisation des ÉFVP, version 3.1, avril 2024, Commission d'accès à l'information du Québec.
