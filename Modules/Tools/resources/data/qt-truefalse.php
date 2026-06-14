<?php

declare(strict_types=1);

/**
 * QT — Quotient Techno : énoncés VRAI/FAUX (type 'vraifaux').
 * Champs : theme, difficulty, statement, answer (bool), explanation, term (slug glossaire).
 * Rendu = QCM à 2 choix ['Vrai','Faux'] dans QtService. Règles : faits exacts, énoncé non ambigu.
 */

return array (
  0 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'facile',
    'statement' => 'L\'intelligence artificielle générative peut créer des images à partir de descriptions textuelles.',
    'answer' => true,
    'explanation' => 'Les modèles comme DALL-E ou Midjourney génèrent des images à partir de prompts textuels.',
    'term' => 'dall-e',
  ),
  1 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'facile',
    'statement' => 'Un mot de passe fort devrait être long et mélanger lettres, chiffres et symboles.',
    'answer' => true,
    'explanation' => 'La longueur et la variété de caractères rendent un mot de passe beaucoup plus difficile à deviner.',
    'term' => 'mot-de-passe-fort',
  ),
  2 => 
  array (
    'theme' => 'web',
    'difficulty' => 'facile',
    'statement' => 'Le protocole HTTPS assure une connexion chiffrée entre le navigateur et le serveur.',
    'answer' => true,
    'explanation' => 'HTTPS utilise TLS pour chiffrer les données échangées.',
    'term' => 'https',
  ),
  3 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'facile',
    'statement' => 'Les métadonnées sont des données sur les données, comme la date de création d\'un fichier.',
    'answer' => true,
    'explanation' => 'Les métadonnées décrivent le contexte des données principales.',
    'term' => 'metadonnees',
  ),
  4 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'facile',
    'statement' => 'Un VPN cache votre adresse IP et chiffre votre trafic internet.',
    'answer' => true,
    'explanation' => 'Un VPN crée un tunnel chiffré et masque l\'adresse IP réelle.',
    'term' => 'vpn',
  ),
  5 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'moyen',
    'statement' => 'Le modèle GPT-4 d\'OpenAI est un exemple de modèle de langage de grande taille.',
    'answer' => true,
    'explanation' => 'GPT-4 est un LLM (large language model) développé par OpenAI.',
    'term' => 'chatgpt',
  ),
  6 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'moyen',
    'statement' => 'Le rançongiciel chiffre les fichiers de la victime et demande une rançon pour les déchiffrer.',
    'answer' => true,
    'explanation' => 'Les rançongiciels sont des logiciels malveillants qui bloquent l\'accès aux données.',
    'term' => 'rancongiciel',
  ),
  7 => 
  array (
    'theme' => 'web',
    'difficulty' => 'moyen',
    'statement' => 'Un cookie est un petit fichier stocké par le navigateur, qui peut servir à retenir une session.',
    'answer' => true,
    'explanation' => 'Les cookies sont utilisés pour la session et le suivi.',
    'term' => 'cookie',
  ),
  8 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'moyen',
    'statement' => 'L\'anonymisation des données supprime toute possibilité de réidentification.',
    'answer' => false,
    'explanation' => 'L\'anonymisation réduit le risque mais la réidentification reste possible dans certains cas.',
    'term' => 'anonymisation',
  ),
  9 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'moyen',
    'statement' => 'La 5G offre une latence plus faible que la 4G.',
    'answer' => true,
    'explanation' => 'La 5G vise une latence bien inférieure à celle de la 4G.',
    'term' => '5g',
  ),
  10 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'difficile',
    'statement' => 'L\'apprentissage par renforcement utilise des récompenses pour entraîner un agent à prendre des décisions.',
    'answer' => true,
    'explanation' => 'L\'agent apprend par essais et erreurs en maximisant la récompense cumulative.',
    'term' => 'apprentissage-par-renforcement',
  ),
  11 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'difficile',
    'statement' => 'L\'authentification à deux facteurs (2FA) repose uniquement sur un mot de passe et un code envoyé par SMS.',
    'answer' => false,
    'explanation' => 'Le 2FA peut utiliser une appli d\'authentification ou une clé physique, pas seulement un SMS.',
    'term' => '2fa',
  ),
  12 => 
  array (
    'theme' => 'web',
    'difficulty' => 'difficile',
    'statement' => 'Le DOM est une représentation structurée du document HTML que le navigateur manipule.',
    'answer' => true,
    'explanation' => 'Le DOM est une interface de programmation pour les documents HTML et XML.',
    'term' => 'dom',
  ),
  13 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'difficile',
    'statement' => 'La confidentialité différentielle ajoute du bruit aux données pour protéger la vie privée des individus.',
    'answer' => true,
    'explanation' => 'Elle garantit que la sortie d\'une analyse ne révèle pas d\'information sur un individu précis.',
    'term' => 'confidentialite-differentielle',
  ),
  14 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'difficile',
    'statement' => 'L\'edge computing traite les données près de leur source plutôt que dans un nuage centralisé.',
    'answer' => true,
    'explanation' => 'L\'edge computing réduit la latence en traitant les données localement.',
    'term' => 'edge-computing',
  ),
  15 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'facile',
    'statement' => 'Les agents conversationnels comme ChatGPT ressentent de vraies émotions.',
    'answer' => false,
    'explanation' => 'Ces systèmes simulent des conversations mais n\'ont ni conscience ni émotions.',
    'term' => 'chatbot',
  ),
  16 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'facile',
    'statement' => 'Un logiciel antivirus protège contre absolument toutes les cyberattaques.',
    'answer' => false,
    'explanation' => 'Aucun antivirus n\'offre une protection à 100 % contre toutes les menaces.',
    'term' => 'cybersecurite',
  ),
  17 => 
  array (
    'theme' => 'web',
    'difficulty' => 'facile',
    'statement' => 'Le HTML est un langage de programmation.',
    'answer' => false,
    'explanation' => 'Le HTML est un langage de balisage, pas un langage de programmation.',
    'term' => 'code-html',
  ),
  18 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'facile',
    'statement' => 'Les données d\'entraînement d\'un modèle d\'IA sont toujours parfaitement représentatives de la réalité.',
    'answer' => false,
    'explanation' => 'Les données d\'entraînement peuvent contenir des biais et ne pas représenter toute la diversité.',
    'term' => 'donnees-dentrainement',
  ),
  19 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'facile',
    'statement' => 'Un navigateur web ne fonctionne que sur ordinateur, jamais sur téléphone.',
    'answer' => false,
    'explanation' => 'Les navigateurs existent aussi sur mobiles et tablettes.',
    'term' => 'navigateur',
  ),
  20 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'moyen',
    'statement' => 'L\'apprentissage supervisé n\'a pas besoin de données étiquetées.',
    'answer' => false,
    'explanation' => 'L\'apprentissage supervisé utilise justement des données étiquetées pour s\'entraîner.',
    'term' => 'apprentissage-supervise',
  ),
  21 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'moyen',
    'statement' => 'Le hameçonnage ne vise que les grandes entreprises.',
    'answer' => false,
    'explanation' => 'Le hameçonnage peut cibler n\'importe qui, y compris les particuliers.',
    'term' => 'hameconnage',
  ),
  22 => 
  array (
    'theme' => 'web',
    'difficulty' => 'moyen',
    'statement' => 'Le Web sémantique est déjà pleinement réalisé et utilisé par tous les sites.',
    'answer' => false,
    'explanation' => 'Le Web sémantique est un objectif en développement, pas encore universellement adopté.',
    'term' => 'web-semantique',
  ),
  23 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'moyen',
    'statement' => 'La minimisation des données consiste à collecter le maximum de données « au cas où ».',
    'answer' => false,
    'explanation' => 'Au contraire, elle consiste à ne collecter que les données strictement nécessaires.',
    'term' => 'minimisation-des-donnees',
  ),
  24 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'moyen',
    'statement' => 'Le cloud computing stocke les données uniquement sur des serveurs locaux.',
    'answer' => false,
    'explanation' => 'Le cloud computing repose sur des serveurs distants accessibles via Internet.',
    'term' => 'cloud-computing',
  ),
  25 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'difficile',
    'statement' => 'L\'alignement de l\'IA est un problème résolu : aucune IA ne présente plus de risque.',
    'answer' => false,
    'explanation' => 'L\'alignement reste un défi de recherche ouvert; aucun système n\'est parfaitement aligné.',
    'term' => 'alignement-ia',
  ),
  26 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'difficile',
    'statement' => 'Le modèle Zero Trust suppose qu\'aucune vérification n\'est nécessaire à l\'intérieur du réseau.',
    'answer' => false,
    'explanation' => 'Zero Trust exige au contraire une vérification continue, même à l\'intérieur du réseau.',
    'term' => 'zero-trust',
  ),
  27 => 
  array (
    'theme' => 'web',
    'difficulty' => 'difficile',
    'statement' => 'Le format JPEG est un format sans perte (lossless).',
    'answer' => false,
    'explanation' => 'JPEG utilise une compression AVEC perte pour réduire la taille du fichier.',
    'term' => 'jpeg',
  ),
  28 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'difficile',
    'statement' => 'En théorie de l\'information, l\'entropie mesure la redondance des données.',
    'answer' => false,
    'explanation' => 'L\'entropie mesure l\'incertitude (le contenu informationnel), pas la redondance.',
    'term' => 'entropie',
  ),
  29 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'difficile',
    'statement' => 'La loi de Moore est une loi physique immuable, toujours parfaitement exacte aujourd\'hui.',
    'answer' => false,
    'explanation' => 'C\'est une observation empirique qui ralentit; elle n\'est plus aussi précise.',
    'term' => 'loi-de-moore',
  ),
);
