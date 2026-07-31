# Comparatif Académie (laveille.ai) vs Moodle 2026 (5.2)

Date : 13 juillet 2026 · Source : inventaire du code réel (Modules/Academy) + état des flags en production + veille pp_search/sonar-pro sur Moodle 5.2.

## ⚠️ Blocage critique préalable

> `config('academy.under_construction') = TRUE` en production : l'Académie entière est actuellement invisible au public (503 pour tout visiteur non-superadmin). Peu importe le nombre de fonctionnalités codées ou activées, **aucun utilisateur réel n'y accède actuellement**. C'est le seul blocage qui prime sur toute discussion de fonctionnalités manquantes.

## Tableau comparatif par catégorie

| Catégorie | Statut Académie | Détail |
|---|---|---|
| Gestion de cours | Équivalent | Structure hiérarchique complète (chapitres/leçons/items), éditeur front-end drag&drop, drip content, pré-requis, cours-gabarits. Manque : catégories multi-niveaux (codé mais désactivé), actions par lot (dupliquer/déplacer en masse). |
| Évaluation/Quiz | Équivalent | Banque de questions réutilisable, feedback immédiat, historique des tentatives, rubriques. Moodle ajoute des modes de comportement adaptatif et la re-notation sélective de questions. |
| Certification/Badges | **Supérieur** | Certificats vérifiables PDF et Open Badges 3.0/Verifiable Credentials natifs et actifs en prod. Moodle nécessite des plugins tiers pour l'équivalent. |
| Social/Collaboratif | Équivalent | Forum par leçon, wiki, base de données collaborative, sondages, commentaires notés. Manque réel : messagerie interne (codée, désactivée), webconférence embarquée type BigBlueButton (Academy = liens externes planifiés seulement). |
| Interopérabilité | Inférieur | SCORM/LTI/xAPI/SSO tous codés mais désactivés en prod. Même activés, limitations réelles : LTI consumer seulement (jamais provider, pas de Deep Linking/AGS/NRPS), SCORM single-SCO (1.2 complet, 2004 basique sans séquencement IMS SS). |
| Mobile | Absent | Aucune application mobile native, pas d'accès hors-ligne aux contenus de cours (le PWA global du site existe hors-Academy, non spécifique au module). |
| IA pédagogique | **Supérieur** | Tuteur IA ancré au cours (RAG), feedback IA, authoring IA, traduction IA, TTS - tous actifs en production. Moodle 5.2 n'a que des fournisseurs IA génériques (Ollama/ChatGPT) sans tuteur pédagogique intégré au core. |
| Admin/Gestion | Inférieur | Dashboard role-aware, analytics par cours, rapports CSV existent. Manque : MFA, intégration LDAP native, rapports personnalisés partageables par rôle/cohorte, calendrier global (codé, désactivé). |
| Gamification | **Supérieur** | XP/niveaux/classement natifs et actifs, scopés par cohorte (opt-out Loi 25). Moodle dépend entièrement de plugins tiers pour l'équivalent. |

## Ce qui manque réellement (non codé, ou fonctionnellement incomplet même une fois activé)

| Item | Priorité | Raison | Effort estimé |
|---|---|---|---|
| Application mobile native (Android/iOS) avec accès hors-ligne | Moyenne | Non codé du tout (le PWA du site est hors-périmètre Academy) - Moodle a une app officielle mature | Élevé |
| Banque de questions partagée à l'échelle de l'organisation (institution-wide) | Moyenne | Banque actuellement owner-scopée par formateur ; Moodle permet le partage inter-cours à l'échelle de l'établissement | Moyen |
| MFA (authentification multi-facteurs) | Moyenne | Non mentionné dans l'inventaire du code Academy ; standard attendu en 2026, notamment pour les organismes de formation | Moyen |
| LTI comme fournisseur d'outils (provider) + Deep Linking/AGS/NRPS | Basse | Academy n'est consumer que jamais provider ; même le flag activé, l'implémentation LTI Advantage reste incomplète face à Moodle | Élevé |
| SCORM 2004 complet (séquencement IMS SS) + multi-SCO | Basse | Implémentation actuelle limitée à single-SCO ; réel manque fonctionnel même une fois le flag `scorm_enabled` activé | Élevé |
| Webconférence embarquée (type BigBlueButton intégré) | Basse | Academy gère des liens Zoom/Meet/Teams planifiés, pas une salle de classe virtuelle intégrée dans la page | Élevé |
| Intégration LDAP native pour les inscriptions | Basse | Non codé ; pertinent seulement pour un futur usage institutionnel/entreprise (lié aux paliers d'abonnement, aussi désactivés) | Moyen |
| Actions par lot (dupliquer/déplacer/supprimer plusieurs éléments en une fois) | Basse | Non trouvé dans l'inventaire ; confort d'édition pour les formateurs gérant beaucoup de contenu | Faible |

## Ce qui est codé mais désactivé (ce n'est PAS un manque, juste une décision d'activation)

| Flag | État |
|---|---|
| `scorm_enabled` | SCORM 1.2/2004 basique déjà implémenté, juste éteint |
| `moodle_import_enabled` | Import .mbz déjà implémenté (MVP page/resource/label) |
| `xapi_enabled` | Émission de statements xAPI déjà implémentée |
| `competency_graph_enabled` | Graphe de compétences pondéré déjà implémenté |
| `diploma_editor_enabled` | Éditeur visuel Konva.js de diplômes déjà implémenté |
| `video_discussion_enabled` | Discussion sociale horodatée déjà implémentée (réutilise le forum) |
| `direct_messaging_enabled` | Messagerie directe formateur-apprenant déjà implémentée |
| `subscription_tiers_enabled` | Paliers freemium/pro/organisation déjà implémentés (sans Stripe live) |
| `lti_enabled` | LTI 1.3 consumer déjà implémenté |
| `kiosk_mode_enabled` | Mode kiosque anti-triche déjà implémenté |
| `course_categories_enabled` | Taxonomie de catégories déjà implémentée |
| `global_calendar_enabled` | Agrégation multi-cours déjà implémentée |
| `notifications.enabled` | Transport courriel déjà implémenté (préférences opt-in/out) |
| `google_meet_autocreate_enabled` | Auto-création de lien Meet déjà implémentée |
| `sso_enabled` | SAML 2.0 + provisioning SCIM 2.0 déjà implémentés (module Sso séparé) |

## Avantages de l'Académie face à Moodle

- Tuteur IA ancré au cours (RAG, zéro hallucination revendiqué) - Moodle n'a rien d'équivalent nativement
- Certificats + Open Badges 3.0/Verifiable Credentials natifs sans dépendance à un plugin tiers
- Gamification native scopée par cohorte avec opt-out Loi 25 (protection renseignements personnels dès la conception)
- Export/import de cours en JSON portable (vs le format .mbz propriétaire de Moodle)
- Éditeur de cours front-end unifié (pas de séparation admin/front comme sur Moodle)
- Répétition espacée (SRS, SM-2) et test de positionnement adaptatif (CAT) natifs - fonctionnalités avancées absentes du core Moodle
- Analytiques prédictifs (score de risque d'abandon) + nudges comportementaux natifs
