<?php

declare(strict_types=1);

/**
 * QT — Quotient Techno : questions à RÉPONSE COURTE (type 'court').
 * Champs : theme, difficulty, question, accepted (NORMALISÉES = minuscule, sans accents, [^a-z0-9 ]->espace, collapse — identique au normCourt() JS), display, explanation, term.
 */

return array (
  0 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'facile',
    'question' => 'Quel terme désigne un programme conçu pour dialoguer avec un humain en langage naturel ?',
    'accepted' =>
    array (
      0 => 'chatbot',
      1 => 'agent conversationnel',
      2 => 'robot conversationnel',
      3 => 'assistant vocal',
    ),
    'display' => 'Chatbot',
    'explanation' => 'Un chatbot simule une conversation avec un humain, par texte ou par la voix.',
    'term' => 'chatbot',
  ),
  1 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'facile',
    'question' => 'Quel assistant d\'OpenAI, lancé en 2022, a popularisé l\'IA conversationnelle auprès du grand public ?',
    'accepted' => 
    array (
      0 => 'chatgpt',
      1 => 'chat gpt',
      2 => 'gpt',
    ),
    'display' => 'ChatGPT',
    'explanation' => 'ChatGPT, d\'OpenAI, a rendu l\'IA conversationnelle accessible à tous.',
    'term' => 'chatgpt',
  ),
  2 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'moyen',
    'question' => 'Comment appelle-t-on une réponse d\'IA fausse mais présentée avec assurance comme un fait ?',
    'accepted' => 
    array (
      0 => 'hallucination',
      1 => 'une hallucination',
    ),
    'display' => 'Hallucination',
    'explanation' => 'Une hallucination, c\'est quand le modèle invente une information et la présente comme vraie.',
    'term' => 'hallucination',
  ),
  3 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'moyen',
    'question' => 'Comment nomme-t-on l\'ajustement d\'un modèle déjà entraîné sur des données spécialisées ?',
    'accepted' => 
    array (
      0 => 'fine tuning',
      1 => 'finetuning',
      2 => 'reglage fin',
      3 => 'ajustement fin',
    ),
    'display' => 'Fine-tuning',
    'explanation' => 'Le fine-tuning adapte un modèle pré-entraîné à une tâche précise avec peu de données.',
    'term' => 'fine-tuning',
  ),
  4 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'difficile',
    'question' => 'Quel concept désigne une IA hypothétique qui surpasserait l\'humain dans tous les domaines ?',
    'accepted' => 
    array (
      0 => 'superintelligence',
      1 => 'super intelligence',
      2 => 'superintelligence artificielle',
    ),
    'display' => 'Superintelligence',
    'explanation' => 'La superintelligence dépasserait les capacités cognitives humaines partout – encore théorique.',
    'term' => 'superintelligence',
  ),
  5 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'difficile',
    'question' => 'Quel institut de recherche en IA, fondé par Yoshua Bengio, est situé à Montréal ?',
    'accepted' => 
    array (
      0 => 'mila',
      1 => 'mila quebec',
    ),
    'display' => 'Mila',
    'explanation' => 'Mila est l\'institut québécois d\'IA cofondé par Yoshua Bengio, pionnier de l\'apprentissage profond.',
    'term' => 'mila',
  ),
  6 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'facile',
    'question' => 'Comment appelle-t-on le petit fichier qu\'un site dépose sur ton navigateur pour mémoriser des infos ?',
    'accepted' => 
    array (
      0 => 'cookie',
      1 => 'temoin',
      2 => 'cookies',
    ),
    'display' => 'Cookie',
    'explanation' => 'Un cookie (témoin) mémorise tes préférences ou ta session sur un site web.',
    'term' => 'cookie',
  ),
  7 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'facile',
    'question' => 'Quelle technologie sans fil à courte portée relie souvent un téléphone à des écouteurs ?',
    'accepted' =>
    array (
      0 => 'bluetooth',
      1 => 'bt',
    ),
    'display' => 'Bluetooth',
    'explanation' => 'Le Bluetooth connecte des appareils proches, comme des écouteurs ou un clavier.',
    'term' => 'bluetooth',
  ),
  8 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'moyen',
    'question' => 'Quelle est l\'unité de texte de base qu\'un modèle de langage traite (mot ou sous-mot) ?',
    'accepted' => 
    array (
      0 => 'token',
      1 => 'jeton',
      2 => 'tokens',
    ),
    'display' => 'Token',
    'explanation' => 'Un token est une unité de texte (mot, sous-mot ou caractère) manipulée par le modèle.',
    'term' => 'token',
  ),
  9 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'moyen',
    'question' => 'Quel sigle désigne un petit modèle de langage léger conçu pour tourner sur des appareils modestes ?',
    'accepted' => 
    array (
      0 => 'slm',
      1 => 'small language model',
      2 => 'petit modele de langage',
    ),
    'display' => 'SLM',
    'explanation' => 'Un SLM (Small Language Model) est compact et optimisé pour des ressources limitées.',
    'term' => 'slm',
  ),
  10 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'difficile',
    'question' => 'Quel concept désigne le traitement des données près de leur source plutôt que dans le nuage ?',
    'accepted' => 
    array (
      0 => 'edge computing',
      1 => 'informatique en peripherie',
      2 => 'informatique de peripherie',
    ),
    'display' => 'Edge computing',
    'explanation' => 'L\'edge computing traite les données localement, ce qui réduit la latence.',
    'term' => 'edge-computing',
  ),
  11 => 
  array (
    'theme' => 'numerique',
    'difficulty' => 'difficile',
    'question' => 'Quelle loi empirique prédit que le nombre de transistors d\'une puce double environ tous les deux ans ?',
    'accepted' => 
    array (
      0 => 'loi de moore',
      1 => 'moore',
    ),
    'display' => 'Loi de Moore',
    'explanation' => 'La loi de Moore observe le doublement régulier du nombre de transistors sur une puce.',
    'term' => 'loi-de-moore',
  ),
  12 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'facile',
    'question' => 'Comment appelle-t-on un courriel frauduleux qui imite une entreprise pour voler tes informations ?',
    'accepted' => 
    array (
      0 => 'hameconnage',
      1 => 'phishing',
    ),
    'display' => 'Hameçonnage',
    'explanation' => 'Le hameçonnage (phishing) piège l\'utilisateur avec un faux courriel pour voler ses données.',
    'term' => 'hameconnage',
  ),
  13 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'facile',
    'question' => 'Quel sigle désigne un réseau privé virtuel qui chiffre ta connexion Internet ?',
    'accepted' => 
    array (
      0 => 'vpn',
      1 => 'reseau prive virtuel',
    ),
    'display' => 'VPN',
    'explanation' => 'Un VPN chiffre ta connexion et masque ton adresse IP.',
    'term' => 'vpn',
  ),
  14 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'moyen',
    'question' => 'Comment nomme-t-on le logiciel malveillant qui chiffre tes fichiers et exige une rançon ?',
    'accepted' => 
    array (
      0 => 'rancongiciel',
      1 => 'ransomware',
    ),
    'display' => 'Rançongiciel',
    'explanation' => 'Un rançongiciel (ransomware) bloque l\'accès aux données jusqu\'au paiement d\'une rançon.',
    'term' => 'rancongiciel',
  ),
  15 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'moyen',
    'question' => 'Quel type d\'attaque submerge un serveur de requêtes pour le rendre indisponible ?',
    'accepted' => 
    array (
      0 => 'deni de service',
      1 => 'd eni de service',
      2 => 'dos',
      3 => 'ddos',
    ),
    'display' => 'Déni de service',
    'explanation' => 'Une attaque par déni de service (DoS) sature un service pour le rendre inaccessible.',
    'term' => 'deni-de-service',
  ),
  16 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'difficile',
    'question' => 'Quel modèle de sécurité ne fait confiance à aucun utilisateur ni appareil par défaut ?',
    'accepted' => 
    array (
      0 => 'zero trust',
      1 => 'zero confiance',
      2 => 'confiance zero',
    ),
    'display' => 'Zero Trust',
    'explanation' => 'Le Zero Trust exige une vérification continue, sans confiance implicite.',
    'term' => 'zero-trust',
  ),
  17 => 
  array (
    'theme' => 'securite',
    'difficulty' => 'difficile',
    'question' => 'Quel maliciel se déguise en logiciel légitime pour tromper l\'utilisateur ?',
    'accepted' => 
    array (
      0 => 'cheval de troie',
      1 => 'trojan',
    ),
    'display' => 'Cheval de Troie',
    'explanation' => 'Un cheval de Troie se fait passer pour un programme inoffensif pour s\'installer.',
    'term' => 'cheval-de-troie',
  ),
  18 => 
  array (
    'theme' => 'web',
    'difficulty' => 'facile',
    'question' => 'Quel protocole sécurise (chiffre) les échanges entre ton navigateur et un site web ?',
    'accepted' =>
    array (
      0 => 'https',
      1 => 'tls',
      2 => 'ssl',
    ),
    'display' => 'HTTPS',
    'explanation' => 'HTTPS chiffre les données échangées entre le navigateur et le site.',
    'term' => 'https',
  ),
  19 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'moyen',
    'question' => 'Comment appelle-t-on l\'instruction qu\'on donne à une IA générative pour obtenir une réponse ?',
    'accepted' =>
    array (
      0 => 'prompt',
      1 => 'invite',
      2 => 'requete',
      3 => 'instruction',
    ),
    'display' => 'Prompt',
    'explanation' => 'Un prompt est l\'instruction fournie au modèle pour orienter sa réponse.',
    'term' => 'prompt',
  ),
  20 => 
  array (
    'theme' => 'web',
    'difficulty' => 'moyen',
    'question' => 'Quel sigle désigne la structure en arbre d\'une page web, manipulable par JavaScript ?',
    'accepted' => 
    array (
      0 => 'dom',
      1 => 'document object model',
    ),
    'display' => 'DOM',
    'explanation' => 'Le DOM représente la structure d\'une page web et permet sa manipulation par script.',
    'term' => 'dom',
  ),
  21 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'facile',
    'question' => 'Comment appelle-t-on les données qui décrivent d\'autres données (auteur, date de création) ?',
    'accepted' => 
    array (
      0 => 'metadonnees',
      1 => 'm etadonn ees',
      2 => 'metadata',
      3 => 'metadonnee',
    ),
    'display' => 'Métadonnées',
    'explanation' => 'Les métadonnées donnent des informations sur d\'autres données.',
    'term' => 'metadonnees',
  ),
  22 => 
  array (
    'theme' => 'donnees',
    'difficulty' => 'moyen',
    'question' => 'Quel processus cherche à rendre l\'identification d\'une personne dans des données le plus difficile possible, idéalement irréversible ?',
    'accepted' =>
    array (
      0 => 'anonymisation',
      1 => 'anonymization',
    ),
    'display' => 'Anonymisation',
    'explanation' => 'L\'anonymisation transforme les données pour empêcher l\'identification des personnes.',
    'term' => 'anonymisation',
  ),
  23 => 
  array (
    'theme' => 'ia',
    'difficulty' => 'moyen',
    'question' => 'Quelle technique combine la recherche de documents et la génération par un modèle pour améliorer la précision ?',
    'accepted' =>
    array (
      0 => 'rag',
      1 => 'retrieval augmented generation',
      2 => 'generation augmentee par recuperation',
    ),
    'display' => 'RAG',
    'explanation' => 'Le RAG combine recherche d\'information et génération de texte pour ancrer les réponses.',
    'term' => 'rag',
  ),
);
