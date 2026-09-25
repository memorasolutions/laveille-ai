// anonymizer-core.js — moteur pur réversible (testé en Node + navigateur)

const FAKE_DATA = {
  firstNamesM: ['Jean', 'Pierre', 'Michel', 'André', 'Luc', 'Marc', 'Philippe', 'François', 'David', 'Mathieu'],
  firstNamesF: ['Marie', 'Julie', 'Sophie', 'Isabelle', 'Nathalie', 'Claire', 'Émilie', 'Caroline', 'Manon', 'Audrey'],
  lastNames: ['Tremblay', 'Gagnon', 'Bouchard', 'Gauthier', 'Morin', 'Lavoie', 'Fortin', 'Gagné', 'Pelletier', 'Bélanger'],
  streets: ['rue Principale', 'avenue du Parc', 'boulevard Saint-Joseph', 'chemin de la Rivière', 'rue des Érables', 'avenue Laurier', 'boulevard René-Lévesque', 'rue Saint-Denis', 'chemin Sainte-Foy', 'rue de la Gauchetière'],
  // VILLES_QUEBEC : municipalités RÉELLES du Québec, des plus peuplées (Montréal) aux plus
  // petites retenues ici (Sainte-Agathe-des-Monts) — 63 entrées, Lévis incluse. Sert DEUX rôles à
  // la fois (jamais dupliqué, DRY) : (a) liste FERMÉE pour la DÉTECTION d'une ville connue dans le
  // texte source (voir detectEntities, section « Villes connues ») ; (b) pioche de VRAIES villes pour
  // generateFake('city', ...). Rangs 1 à 50 vérifiés auprès de l'Institut de la statistique du
  // Québec (estimations démographiques au 1er juillet 2024, tableau mis à jour 2026-01-14) ; les
  // 13 suivantes sont des municipalités réelles et constituées, vérifiées mais hors du seuil de
  // 25 000 habitants publié par l'ISQ. Corrige le défaut #2732 (ex. « succursale de Lévis » non
  // détecté).
  cities: [
    'Montréal', 'Québec', 'Laval', 'Gatineau', 'Longueuil', 'Sherbrooke', 'Lévis', 'Saguenay',
    'Trois-Rivières', 'Terrebonne', 'Saint-Jean-sur-Richelieu', 'Brossard', 'Repentigny',
    'Drummondville', 'Saint-Jérôme', 'Granby', 'Mirabel', 'Blainville', 'Saint-Hyacinthe',
    'Mascouche', 'Châteauguay', 'Shawinigan', 'Rimouski', 'Dollard-des-Ormeaux', 'Victoriaville',
    'Saint-Eustache', 'Salaberry-de-Valleyfield', 'Vaudreuil-Dorion', 'Rouyn-Noranda',
    'Boucherville', 'Côte-Saint-Luc', 'Pointe-Claire', 'Sorel-Tracy', 'Saint-Georges', "Val-d'Or",
    'Saint-Constant', 'Chambly', 'Sainte-Julie', 'Alma', 'Magog', 'Boisbriand', 'Sainte-Thérèse',
    'La Prairie', 'Thetford Mines', 'Saint-Bruno-de-Montarville', 'Saint-Lin-Laurentides',
    'Beloeil', "L'Assomption", 'Sept-Îles', 'Rivière-du-Loup', 'Joliette', 'Sainte-Catherine',
    'Candiac', 'Varennes', 'Deux-Montagnes', 'Beauharnois', 'Marieville', 'Baie-Comeau', 'Amos',
    'Cowansville', 'Gaspé', 'Matane', 'Sainte-Agathe-des-Monts'
  ],
  // Domaines RÉSERVÉS À LA DOCUMENTATION/AUX EXEMPLES par la RFC 2606 (example.com/.net/.org) :
  // jamais attribuables à une vraie boîte courriel, contrairement aux anciens domaines listés ici
  // (gmail.com, hotmail.com, yahoo.ca, videotron.ca, bell.net sont de VRAIS domaines actifs — un
  // faux courriel généré avec l'un d'eux pouvait désigner une boîte réelle). Corrige D6.
  domains: ['example.com', 'example.net', 'example.org'],
  companies: ['Constructions Boréal inc.', 'Groupe Solva', 'Entreprises Lemay-Côté', 'Gestion Riverin', 'Atelier Norjak', 'Services Permafort', 'Coopérative Verdelis', 'Industries Cap-Vert', 'Groupe Makila', 'Solutions Drakkar']
};

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Fragments d'adresse partagés entre la détection (detectEntities) et la génération de faux
// (generateFake/extraireNomVoieSeul) : hissés au niveau du module pour ne jamais les dupliquer
// (DRY). ARTICLE_ADRESSE = article optionnel entre le type de voie et le nom (« de la », « du »…).
// TYPE_VOIE = types de voie québécois reconnus, tolérants à la casse via alternance explicite.
// MOT_VOIE = un mot de nom de voie capitalisé (apostrophe/trait d'union gérés).
const ARTICLE_ADRESSE = "(?:de\\s+la\\s+|de\\s+l['’]|du\\s+|des\\s+|de\\s+)";
const TYPE_VOIE = "(?:[Rr]ue|[Aa]venue|[Aa]v\\.?|[Bb]oulevard|[Bb]oul\\.?|[Cc]hemin|[Cc]h\\.?|[Rr]ang|[Mm]ont[ée]e|[Pp]lace|[Ii]mpasse)";
const MOT_VOIE = "\\p{Lu}(?:\\p{Ll}+|(?=['’]))(?:['’]\\p{Lu}?\\p{Ll}*)*(?:-\\p{Lu}?\\p{Ll}*)*";
// Ordinal québécois (« 12e », « 5e », « 1er », « 2e »…) précédant le type de voie dans le motif
// très courant « NUMÉRO, ORDINALe TYPE » (« 300, 12e Avenue », « 42, 5e Rue »). Corrige D2.
const ORDINAL_VOIE = "\\d{1,3}(?:er|re|e)";

function getAccentClass(char) {
  const base = char.toLowerCase();
  const map = {
    'a': '[aàâäAÀÂÄ]', 'e': '[eéèêëEÉÈÊË]', 'i': '[iîïIÎÏ]',
    'o': '[oôöOÔÖ]', 'u': '[uùûüUÙÛÜ]', 'c': '[cçCÇ]', 'n': '[nñNÑ]'
  };
  return map[base] || char;
}

// Construit le CORPS de la regex insensible accents (motif seul, sans bornes ni drapeaux) :
// factorisation commune aux 3 fonctions qui suivent (bornée insensible casse, non bornée
// insensible casse, et bornée SENSIBLE À LA CASSE réservée aux villes connues) — jamais dupliqué.
// Les apostrophes (droite ' ou typographique ’ʼ`´) sont traitées comme une CLASSE au même titre
// que les accents : une ville embarquée avec ’ (« L'Assomption ») doit rester détectable si le
// texte collé par l'utilisateur utilise ' à la place, et inversement.
function buildAccentTolerantPattern(str) {
  let escaped = escapeRegex(str).replace(/\s+/g, '\\s+');
  let pattern = '';
  for (let i = 0; i < escaped.length; i++) {
    const char = escaped[i];
    if (char === '\\') { pattern += char + escaped[++i]; continue; } // garde \. \s etc.
    if (char === '+') { pattern += char; continue; }
    if (char === "'" || char === '’' || char === 'ʼ' || char === '`' || char === '´') { pattern += "['’ʼ`´]"; continue; }
    pattern += getAccentClass(char);
  }
  return pattern;
}

// Construit une regex bornée, insensible casse + accents, espaces flexibles.
function buildAccentInsensitiveBoundedRegex(str) {
  const pattern = buildAccentTolerantPattern(str);
  const startBoundary = /^\w/.test(str) ? '(?<![A-Za-zÀ-ÖØ-öø-ÿ0-9])' : '(?<!\\w)';
  const endBoundary = /\w$/.test(str) ? '(?![A-Za-zÀ-ÖØ-öø-ÿ0-9])' : '(?!\\w)';
  return new RegExp(startBoundary + pattern + endBoundary, 'gi');
}

// Variante sans boundary pour la restauration : les pseudos sont uniques par construction,
// inutile de risquer un échec de \b quand le texte IA est collé (textContent sans séparateurs).
function buildAccentInsensitiveUnboundedRegex(str) {
  return new RegExp(buildAccentTolerantPattern(str), 'gi');
}

// Variante RÉSERVÉE à la détection des villes CONNUES (VILLES_QUEBEC / FAKE_DATA.cities) : mêmes
// tolérances accent/apostrophe que ci-dessus, mais SANS le drapeau insensible-casse global.
// getAccentClass ne rend déjà insensible à la casse QUE les lettres a/e/i/o/u/c/n (voir sa table) ;
// toutes les autres lettres — donc la première lettre de la plupart des noms de villes québécoises
// (L, M, T, V, S, B, R, G, D, C…) — restent exigées dans leur casse EXACTE telle qu'écrite dans la
// liste (toujours capitalisée). But : un nom de ville qui est AUSSI un mot commun (« La Prairie »
// la ville / « la prairie » le champ) ne doit pas anonymiser du texte ordinaire qui ne parle
// d'aucune ville. Corrige le défaut #2732 sans introduire de nouveau faux positif.
function buildCityDetectionRegex(name) {
  const pattern = buildAccentTolerantPattern(name);
  const startBoundary = '(?<![A-Za-zÀ-ÖØ-öø-ÿ0-9])';
  const endBoundary = '(?![A-Za-zÀ-ÖØ-öø-ÿ0-9])';
  return new RegExp(startBoundary + pattern + endBoundary, 'g');
}

// Mots courants (verbes d'introduction, salutations, connecteurs) qui précèdent souvent un vrai
// nom propre dans une phrase et qui, capitalisés en début de phrase, se font passer pour un prénom
// par la regex de détection des noms composés. Sans les exclure, le mot ignoré VOLE la fenêtre
// d'appariement à deux mots consécutifs et le vrai nom de famille qui suit reste orphelin, donc
// jamais détecté (ex. « Appelle Marc Tremblay » → seul « Appelle Marc » est capté).
const MOTS_IGNORES_SUPPLEMENTAIRES = ['appelle', 'appelez', 'contacte', 'contactez', 'informe', 'informez', 'joins', 'joignez', 'rejoins', 'rejoignez', 'veuillez', 'prière', 'écris', 'écrivez', 'envoie', 'envoyez', 'demande', 'demandez', 'rappelle', 'rappelez', 'préviens', 'prévenez', 'transmets', 'transmettez', 'remercie', 'remerciez', 'salue', 'saluez', 'bonjour', 'bonsoir', 'merci', 'cordialement', 'salutations', 'objet', 'sujet', 'note', 'attention', 'urgent', 'important', 'voici', 'voilà', 'ensuite', 'ainsi', 'donc', 'cependant', 'toutefois', 'aussi', 'enfin', 'bref'];

// Normalisation partagée : casse, accents ET apostrophes (droite ' ou typographique ')
// retirées, pour que « Rue des Érables » == « rue des erables » == « D'Amours » == « D’Amours ».
function normaliserMot(mot) {
  if (!mot) return '';
  return mot.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/['’ʼ`´]/g, '');
}

// Vérifie si un mot doit être ignoré comme candidat nom propre : combine les stopwords déjà
// connus du site d'appel (Set ou tableau) avec MOTS_IGNORES_SUPPLEMENTAIRES, insensible
// casse/accents. Utilisé pour empêcher un mot courant de « voler » la fenêtre d'appariement
// d'un nom composé (voir commentaire de MOTS_IGNORES_SUPPLEMENTAIRES ci-dessus).
function estMotIgnore(mot, stopwordsExistants) {
  if (!mot) return true;
  const motNormalise = normaliserMot(mot);
  let ensemble;
  if (stopwordsExistants && typeof stopwordsExistants.has === 'function') {
    ensemble = new Set([...stopwordsExistants].map(normaliserMot));
  } else if (Array.isArray(stopwordsExistants)) {
    ensemble = new Set(stopwordsExistants.map(normaliserMot));
  } else {
    ensemble = new Set();
  }
  MOTS_IGNORES_SUPPLEMENTAIRES.forEach(function (m) { ensemble.add(normaliserMot(m)); });
  return ensemble.has(motNormalise);
}

function detectEntities(text) {
  const STOPWORDS = new Set([
    'bonjour', 'bonsoir', 'salut', 'merci', 'cordialement', 'madame', 'monsieur',
    'docteur', 'clinique', 'hopital', 'centre', 'est', 'ouest', 'nord', 'sud',
    'quebec', 'canada', 'objet', 'suivi', 'nom', 'adresse',
    'dr', 'm', 'mme', 'me', 'pr', 'mr', 'mlle',
    // Mots courants des lettres (médicales/admin) qui précèdent un nom — ne pas les prendre pour un prénom
    'patient', 'patiente', 'usager', 'usagere', 'beneficiaire', 'medecin',
    'concernant', 'reference', 'sujet', 'destinataire', 'dossier', 'date'
  ]);
  const normalize = (str) => str.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  const entities = [];
  const seen = new Set();
  const push = (value, category, label, confidence) => {
    const k = (value || '').trim();
    if (k && !seen.has(normalize(k))) { seen.add(normalize(k)); entities.push({ value: k, category, label, confidence: confidence || 0.9 }); }
  };
  // VILLES_CONNUES : hissé ici (au lieu d'être redéclaré localement à l'étape 2c comme avant) pour
  // être réutilisé DANS la détection de nom elle-même (étapes 2 et 2b) — sans quoi une ville
  // composée de deux mots séparés par un espace ET grammaticalement valides comme prénom+nom
  // (« La Prairie », « Thetford Mines ») est happée comme un faux NOM DE PERSONNE avant même que
  // l'étape « Villes connues » (17) n'ait la chance de la voir : push() dédoublonne par valeur
  // exacte, donc la seconde tentative (ville) est alors silencieusement ignorée. Corrige un défaut
  // découvert PAR l'ajout des nouvelles villes du ticket #2732 (mesuré par exécution, pas supposé).
  const VILLES_CONNUES = new Set(FAKE_DATA.cities.map(normalize));
  let m;
  // 1. Noms avec titre de civilité (capture le nom, pas le titre). Un SEUL mot après le titre
  //    = nom de famille seul → catégorie 'lastName' (ne JAMAIS inventer un prénom + nom complet).
  const titled = /\b(?:Dr\.?|M\.?|Mme\.?|Me|Pr|Mr)[^\S\r\n]+([A-ZÀ-Ÿ][a-zà-ÿ'’\-]+)(?:[^\S\r\n]+([A-ZÀ-Ÿ][a-zà-ÿ'’\-]+))?/g;
  while ((m = titled.exec(text))) {
    if (m[2]) push(`${m[1]} ${m[2]}`, 'name', 'Nom complet', 0.85);
    else if (!STOPWORDS.has(normalize(m[1]))) push(m[1], 'lastName', 'Nom de famille', 0.8);
  }
  // 3. RAMQ
  const ramq = /\b[A-ZÀ-Ÿ]{4}\s?\d{4}\s?\d{2}\s?\d{2}\b/g;
  while ((m = ramq.exec(text))) push(m[0], 'id', 'RAMQ', 0.95);
  // 5. Numéro de permis / matricule (avec contexte)
  const permit = /(?:permis|oiiq|matricule|n[°o]\s*de\s*permis)[^\d]{0,15}(\d{5,})/gi;
  while ((m = permit.exec(text))) push(m[1], 'id', 'Numéro de permis', 0.9);
  // 6. Adresse civique — capture l'article optionnel (de, du, des, de la, de l') ET le nom de voie
  // COMPLET, qui peut compter plusieurs mots (« rue de la Grande Allée », « avenue des Pins Ouest »,
  // « boulevard René-Lévesque Est »). L'ancien motif ne capturait qu'UN SEUL mot après le type de
  // voie : tout nom de rue québécois introduit par un article laissait le dernier mot survivre en
  // clair (fuite prouvée par exécution : « 1234 rue des Érables » ne masquait que « rue », et
  // « Érables » restait visible tel quel dans le texte anonymisé).
  // Le drapeau global insensible-casse (gi) est volontairement abandonné ici : la répétition
  // multi-mots du nom de voie s'appuie sur la MAJUSCULE initiale de chaque mot pour savoir où
  // s'arrêter (sans cette contrainte, elle continuerait d'avaler des mots minuscules suivants et
  // déborderait sur le reste de la phrase). Les types de voie restent tolérants à la casse réelle
  // grâce à une alternance explicite Majuscule/minuscule sur leur première lettre.
  // MOT_VOIE utilise \p{Lu}/\p{Ll} (propriétés Unicode « lettre majuscule »/« lettre minuscule »)
  // plutôt que la plage littérale [A-ZÀ-Ÿ] utilisée ailleurs dans ce fichier : cette plage inclut
  // numériquement des MINUSCULES accentuées (à, é, ê…), ce qui aurait permis à un mot minuscule
  // suivant le nom de voie de se faire passer pour un nouveau mot capitalisé et de faire déborder
  // la répétition multi-mots sur le reste de la phrase. \p{Lu} exige une vraie majuscule.
  // ARTICLE_ADRESSE/TYPE_VOIE/MOT_VOIE/ORDINAL_VOIE sont définis au niveau du module (voir haut de
  // fichier) pour être réutilisés tels quels par generateFake()/extraireNomVoieSeul() — DRY.
  // Deux formes reconnues, alternées : la forme classique « TYPE [article] NOM » (« rue des
  // Érables ») et la forme ordinale très courante au Québec « NUMÉRO, ORDINALe TYPE » (« 300, 12e
  // Avenue », « 42, 5e Rue »), qui manquait entièrement à l'ancien motif (fuite 300/300 prouvée par
  // exécution). Corrige D2.
  const address = new RegExp(
    '\\b\\d{1,5},?\\s+(?:' +
    ORDINAL_VOIE + '\\s+' + TYPE_VOIE + '(?:\\s+' + MOT_VOIE + ')*' +
    '|' +
    TYPE_VOIE + '\\s+(?:' + ARTICLE_ADRESSE + ')?' + MOT_VOIE + '(?:\\s+' + MOT_VOIE + ')*' +
    ')',
    'gu'
  );
  while ((m = address.exec(text))) push(m[0], 'address', 'Adresse', 0.85);
  // 4. Code postal canadien
  const postal = /\b[A-Z]\d[A-Z]\s?\d[A-Z]\d\b/g;
  while ((m = postal.exec(text))) push(m[0], 'address', 'Code postal', 0.9);
  // 7. Courriel
  const email = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;
  while ((m = email.exec(text))) push(m[0], 'email', 'Courriel', 0.99);
  // 7.5 Carte bancaire / PAN (13-19 chiffres, validés Luhn) — AVANT le téléphone pour éviter qu'il capte un sous-segment.
  const luhnValid = (str) => {
    const digits = str.replace(/\D/g, '');
    if (digits.length < 2) return false;
    let sum = 0, isEven = false;
    for (let i = digits.length - 1; i >= 0; i--) {
      let d = parseInt(digits.charAt(i), 10);
      if (isEven) { d *= 2; if (d > 9) d -= 9; }
      sum += d; isEven = !isEven;
    }
    return sum % 10 === 0;
  };
  const card = /(?<!\d)(?:\d[ -]?){12,18}\d(?!\d)/g;
  while ((m = card.exec(text))) { if (luhnValid(m[0])) push(m[0], 'id', 'Carte bancaire', 0.95); }
  // 7.6 IBAN
  const iban = /\b[A-Z]{2}\d{2}[A-Z0-9]{10,30}\b/g;
  while ((m = iban.exec(text))) push(m[0], 'id', 'IBAN', 0.9);
  // 7.7 Adresse IP v4 (octets 0-255)
  const octet = '(?:25[0-5]|2[0-4]\\d|[01]?\\d?\\d)';
  const ip = new RegExp(`(?<![\\d.])${octet}\\.${octet}\\.${octet}\\.${octet}(?![\\d.])`, 'g');
  while ((m = ip.exec(text))) push(m[0], 'id', 'Adresse IP', 0.85);
  // 8. Téléphone CA
  const phone = /(?<!\d)(?:\(\d{3}\)|\d{3})[-.\s]?\d{3}[-.\s]?\d{4}(?!\d)/g;
  while ((m = phone.exec(text))) push(m[0], 'phone', 'Téléphone', 0.9);
  // 9. Numéro de dossier / code interne — élargi pour couvrir les codes lettre(s)+chiffres
  // (« D-4471 », « AB12345 ») et le préfixe « N° »/« No » utilisé SEUL (sans le mot « dossier »),
  // en plus des formes déjà couvertes (« #1234 », « dossier 1234 », « dossier n° 1234 »). Corrige
  // le défaut #2732 (« dossier D-4471 » jamais détecté : l'ancien motif exigeait un chiffre
  // IMMÉDIATEMENT après « dossier », donc échouait dès qu'une lettre de préfixe s'interposait).
  // Le préfixe de contexte reste obligatoire ici (mot « dossier », symbole « # », ou « N°»/« No ») :
  // un code alphanumérique nu, sans aucun contexte, sortirait du périmètre « numéro de dossier »
  // (pourrait être un code produit, une plaque…) — voir l'étape suivante pour ce cas plus strict.
  const dossier = /(?:#|dossier\s*(?:n[°o]\.?\s*)?|n[°o]\.?\s*(?:de\s+dossier\s*)?)[:\s]*([A-ZÀ-Ÿ]{0,4}-?\d[\d-]*)/gi;
  while ((m = dossier.exec(text))) push(m[0], 'dossier', 'Numéro de dossier', 0.95);
  // 9b. Code interne NU (sans mot « dossier »/« # »/« N° ») mais de forme distinctive : 2 à 4
  // lettres MAJUSCULES collées à 4 chiffres ou plus, sans séparateur (« AB12345 »). Forme rare en
  // prose ordinaire ; ne collisionne pas avec le RAMQ (exige exactement 4 lettres + 8 chiffres,
  // capté par l'étape 3 déjà exécutée — le Set `seen` empêche tout double-comptage du même texte).
  const codeInterne = /\b[A-ZÀ-Ÿ]{2,4}\d{4,8}\b/g;
  while ((m = codeInterne.exec(text))) push(m[0], 'dossier', 'Numéro de dossier', 0.75);
  // 10. Montant
  const amount = /\$\s*\d+(?:[ ,]\d{3})*(?:[.,]\d{2})?\s*(?:CAD|cad)?/g;
  while ((m = amount.exec(text))) push(m[0], 'amount', 'Montant', 0.95);
  // 11. Date FR/ISO
  const date = /\b(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4}|\d{1,2}\s+(?:janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s+\d{4})\b/gi;
  while ((m = date.exec(text))) push(m[0], 'date', 'Date', 0.9);
  // 12. NAS (numéro d'assurance sociale) — contextuel (étiquette NAS/assurance sociale) + isolé validé Luhn
  const luhnNAS = (d) => { let s = 0; for (let i = 0; i < 9; i++) { let x = parseInt(d[i], 10); if ((9 - i) % 2 === 0) { x *= 2; if (x > 9) x -= 9; } s += x; } return s % 10 === 0; };
  const nasCtx = /(?:N\.?A\.?S\.?|assurance\s+sociale|num[ée]ro\s+d['’]assurance(?:\s+sociale)?|\bSIN\b)\D{0,12}(\d{3}[ -]?\d{3}[ -]?\d{3})/gi;
  while ((m = nasCtx.exec(text))) push(m[1], 'id', 'NAS', 0.9);
  const nasStd = /(?<!\d)\d{3}[ -]?\d{3}[ -]?\d{3}(?!\d)/g;
  while ((m = nasStd.exec(text))) { const c = m[0].replace(/[ -]/g, ''); if (c.length === 9 && luhnNAS(c)) push(m[0], 'id', 'NAS', 0.9); }
  // 13. Montant avec le symbole $ APRÈS le nombre (format québécois : « 1 250,00 $ », « 2 750$ »)
  const amountFr = /\d{1,3}(?:[ .  ]\d{3})*(?:,\d{2})?\s*\$/g;
  while ((m = amountFr.exec(text))) push(m[0], 'amount', 'Montant', 0.9);
  // 14. Nom abrégé après titre de civilité (initiale + nom : « Mme L. Gagnon », « Dr. A. Roy »)
  const titledAbbrev = /\b(?:Dr\.?|M\.?|Mme\.?|Me|Pr|Mr)[^\S\r\n]+([A-ZÀ-Ÿ]\.?[^\S\r\n]+[A-ZÀ-Ÿ][a-zà-ÿ'’\-]+)/g;
  while ((m = titledAbbrev.exec(text))) push(m[1], 'name', 'Nom complet', 0.8);
  // 2. Noms sans titre — élargi pour fermer 4 classes de fuites prouvées par exécution : les
  // particules (« Jean de La Fontaine », « Pieter van der Berg »), les noms avec apostrophe suivie
  // d'une MAJUSCULE (« Patrick O'Neil », « Patrick D'Astous », apostrophe droite ou typographique),
  // les prénoms composés séparés par un espace (« Marie Ève Tremblay »), et les noms tout en
  // MAJUSCULES (« JEAN TREMBLAY ») ou inversés par une virgule (« Tremblay, Marc »).
  // Arbitrage retenu pour ce moteur : sur-masquer légèrement plutôt que sous-détecter — une
  // sous-détection est invisible pour la personne qui copie le texte anonymisé, donc plus
  // dangereuse pour sa vie privée qu'un faux positif occasionnel sur une expression figée en tête
  // de phrase (ex. « Le Centre… »). MOTS_IGNORES_SUPPLEMENTAIRES et estMotIgnore (déjà définis plus
  // haut dans ce fichier) sont réutilisés tels quels ci-dessous, jamais dupliqués.
  //
  // Jeton « nom » générique : Majuscule initiale, puis lettres, puis segments optionnels
  // d'apostrophe (« O'Neil » : apostrophe + Majuscule optionnelle + lettres) et de trait d'union
  // (« Saint-Pierre », « René-Lévesque »). Construit avec \p{Lu}/\p{Ll} (propriétés Unicode) plutôt
  // qu'avec la plage littérale [A-ZÀ-Ÿ] utilisée ailleurs dans ce fichier : cette plage inclut
  // numériquement des MINUSCULES accentuées (à, é, ê…), ce qui laissait un simple « à » isolé se
  // faire passer pour un second mot capitalisé (faux positif détecté en test : « Contactez-moi à
  // jean.tremblay@... » masquait « Contactez-moi à » en entier). \p{Lu} exige une vraie majuscule ;
  // le premier segment exige soit une minuscule après (mot normal), soit une apostrophe immédiate
  // (« O' », « D' »), jamais une majuscule isolée sans suite.
  // Deux ajouts corrigent D3 et D4 (fuites 300/300 prouvées par exécution) :
  // - préfixe élidé minuscule optionnel (?:d['’])? : couvre la particule « de » élidée devant une
  //   voyelle et restée minuscule, très fréquente dans les patronymes québécois (« d'Astous »,
  //   « d'Amours », « d'Anjou »). La forme majuscule (« D'Astous ») fonctionnait déjà via le
  //   segment apostrophe existant ; seule la forme minuscule manquait.
  // - segment interne (?:\\p{Lu}\\p{Ll}*)* : autorise une ou plusieurs majuscules COLLÉES à
  //   l'intérieur du même mot, sans apostrophe ni trait d'union (« MacDonald », « McDonald »,
  //   « DiCaprio », « LeBlanc »). Le premier segment \\p{Ll}* devient optionnel (zéro ou plus,
  //   au lieu d'exiger au moins une lettre) pour rester compatible avec « O' » (zéro lettre avant
  //   l'apostrophe) sans plus avoir besoin du lookahead séparé.
  const NAME_WORD = "(?:d['’])?\\p{Lu}\\p{Ll}*(?:\\p{Lu}\\p{Ll}*)*(?:['’]\\p{Lu}?\\p{Ll}*)*(?:-\\p{Lu}?\\p{Ll}*)*";
  // Particules courantes (françaises et néerlandaises/germaniques) pouvant relier un prénom à un nom
  // de famille sans casser la détection. Les alternatives multi-mots sont placées EN PREMIER
  // (van der/van den/von der avant van/von seuls) : sinon l'alternance s'arrête au premier mot et
  // « der »/« den » restent orphelins hors de la capture.
  const PARTICULE_NOM = "(?:van\\s+der|van\\s+den|von\\s+der|de\\s+la|des|du|de|van|von|di|dos|das|der|le|la)";
  const name = new RegExp(
    '(?<![A-Za-zÀ-ÿ])(' + NAME_WORD + ')' +               // g1 : premier mot (prénom)
    '((?:[^\\S\\r\\n]+' + PARTICULE_NOM + ')*)' +           // g2 : particule(s) minuscule(s) optionnelle(s)
    '((?:[^\\S\\r\\n]+' + NAME_WORD + '){1,2})' +           // g3 : 1 ou 2 mots majuscules finaux (nom, ou prénom composé + nom)
    '(?![A-Za-zÀ-ÿ])',
    'gu'
  );
  while ((m = name.exec(text))) {
    const suite = m[3].trim().split(/\s+/); // mots capitalisés de g3, hors particules de g2
    // estMotIgnore() couvre STOPWORDS + MOTS_IGNORES_SUPPLEMENTAIRES (verbes d'introduction,
    // salutations…) : un mot ignoré ne doit JAMAIS voler la fenêtre d'appariement, sinon le vrai
    // nom de famille qui suit reste orphelin et n'est plus jamais détecté.
    const w1Ignore = estMotIgnore(m[1], STOPWORDS);
    const w2Ignore = suite.some((w) => estMotIgnore(w, STOPWORDS));
    if (w1Ignore && !w2Ignore) {
      // 1er mot = mot courant (« Patient », « Concernant », « Appelle »…) : ne PAS consommer
      // la suite, rembobiner pour capter le vrai nom complet qui la suit (« Marc Tremblay »).
      name.lastIndex = m.index + m[1].length;
      continue;
    }
    if (!w1Ignore && !w2Ignore) {
      const valeur = (m[1] + m[2] + m[3]).replace(/\s+/g, ' ').trim();
      // Ville connue faite de deux mots séparés par un espace ET grammaticalement valides comme
      // prénom+nom (« La Prairie », « Thetford Mines ») : ne PAS la happer comme un faux nom de
      // personne, sinon push() (dédoublonnage par valeur exacte) empêche ensuite l'étape « Villes
      // connues » de la détecter comme ce qu'elle est réellement. Mesuré par exécution : sans ce
      // garde-fou, l'ajout des nouvelles villes du ticket #2732 en perdait deux sur 63.
      if (VILLES_CONNUES.has(normalize(valeur))) continue;
      push(valeur, 'name', 'Nom complet', 0.8);
    }
  }
  // 2b. Noms tout en MAJUSCULES (« JEAN TREMBLAY ») — fréquent dans les lignes d'identification des
  // lettres administratives/médicales québécoises. Longueur minimale de 2 lettres consécutives par
  // mot (évite de confondre une simple initiale avec un nom ; le motif titré ci-dessus gère déjà
  // « M. Tremblay »). \p{Lu}{2,} (propriété Unicode) plutôt que [A-ZÀ-Ÿ]{2,} pour la même raison
  // que NAME_WORD ci-dessus : la plage littérale inclut aussi des minuscules accentuées.
  const NAME_WORD_MAJ = "\\p{Lu}{2,}(?:['’]\\p{Lu}+)?(?:-\\p{Lu}{2,})*";
  const nameAllCaps = new RegExp(
    '(?<![A-Za-zÀ-ÿ])(' + NAME_WORD_MAJ + ')[^\\S\\r\\n]+(' + NAME_WORD_MAJ + ')(?![A-Za-zÀ-ÿ])',
    'gu'
  );
  while ((m = nameAllCaps.exec(text))) {
    const w1Ignore = estMotIgnore(m[1], STOPWORDS), w2Ignore = estMotIgnore(m[2], STOPWORDS);
    if (w1Ignore && !w2Ignore) { nameAllCaps.lastIndex = m.index + m[1].length; continue; }
    if (!w1Ignore && !w2Ignore && !VILLES_CONNUES.has(normalize(`${m[1]} ${m[2]}`))) push(`${m[1]} ${m[2]}`, 'name', 'Nom complet', 0.75);
  }
  // 2c. Inversion « Nom, Prénom » (fréquente dans les tableaux/listes triées alphabétiquement). Les
  // deux composantes sont poussées séparément (firstName/lastName) plutôt que le span complet avec
  // la virgule : chaque mot est alors remplacé indépendamment à sa position, la virgule reste
  // intacte, et la logique existante de buildRules()/nameMap (déjà écrite pour firstName/lastName)
  // s'applique sans code additionnel à dupliquer. VILLES_CONNUES (hissé plus haut, jamais
  // redéclaré ici) sert de liste d'exclusion : sans elle, « Trois-Rivières, Québec » serait pris
  // pour un nom inversé.
  const nameInverted = new RegExp(
    '(?<![A-Za-zÀ-ÿ])(' + NAME_WORD + '),[^\\S\\r\\n]+(' + NAME_WORD + ')(?![A-Za-zÀ-ÿ])',
    'gu'
  );
  while ((m = nameInverted.exec(text))) {
    if (VILLES_CONNUES.has(normalize(m[1])) || VILLES_CONNUES.has(normalize(m[2]))) continue;
    const w1Ignore = estMotIgnore(m[1], STOPWORDS), w2Ignore = estMotIgnore(m[2], STOPWORDS);
    if (!w1Ignore) push(m[1], 'lastName', 'Nom de famille', 0.75);
    if (!w2Ignore) push(m[2], 'firstName', 'Prénom', 0.75);
  }
  // 15. Prénoms / noms de famille VRAIMENT ISOLÉS des noms complets déjà détectés — couvre le mode
  //     jetons et les occurrences seules (« Geneviève » seul après « Geneviève Côté-Pelletier »).
  //     On ne pousse une composante que si elle apparaît HORS du nom complet ET HORS d'un courriel,
  //     pour ne pas créer de sous-règles incohérentes avec le faux nom complet (préserve la cohérence courriel).
  const _emailRe = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;
  for (const ent of entities.filter(e => e.category === 'name')) {
    const parts = ent.value.split(/\s+/);
    if (parts.length !== 2) continue;
    const fullRe = new RegExp('(?<![A-Za-zÀ-ÿ])' + escapeRegex(ent.value).replace(/\s+/g, '\\s+') + '(?![A-Za-zÀ-ÿ])', 'gi');
    const residual = text.replace(_emailRe, ' ').replace(fullRe, ' ');
    parts.forEach((word, i) => {
      if (word.length < 2 || STOPWORDS.has(normalize(word))) return;
      const re = new RegExp('(?<![A-Za-zÀ-ÿ])' + escapeRegex(word) + '(?![A-Za-zÀ-ÿ])', 'i');
      if (re.test(residual)) push(word, i === 0 ? 'firstName' : 'lastName', i === 0 ? 'Prénom' : 'Nom de famille', 0.7);
    });
  }
  // 16. Cohérence courriel ↔ nom : fuite du fragment MANQUANT (défaut #2732). relinkEmails()
  // (plus bas dans ce fichier) sait déjà rendre un faux courriel cohérent avec un faux nom — mais
  // seulement pour les fragments (prénom/nom) qui ont CHACUN leur propre entité détectée ailleurs
  // dans le texte. Si le texte ne nomme la personne que partiellement en clair (ex. « Mme Tremblay »
  // — un titre + le seul nom de famille) alors que son courriel complet « marie.tremblay@… »
  // contient AUSSI le prénom, ce prénom n'était détecté NULLE PART : relinkEmails changeait bien
  // « tremblay » mais laissait « marie » tel quel, RÉEL, dans le faux courriel généré — une fuite
  // pire qu'une incohérence, prouvée par exécution (voir repro2.cjs, scénario A). Le correctif :
  // si un jeton de la partie locale d'un courriel correspond à un fragment de nom DÉJÀ détecté,
  // et que l'AUTRE jeton du même courriel ne correspond à AUCUNE entité connue, ce dernier est
  // presque toujours l'autre moitié du même nom réel (motif prénom.nom très répandu) — on le pousse
  // comme entité à son tour, pour qu'il reçoive lui aussi un faux cohérent via le pipeline normal
  // (buildRules → uniqueFake → relinkEmails, tous réutilisés tels quels, DRY).
  const MOTS_LOCAUX_COURRIEL_IGNORES = ['info', 'contact', 'contacts', 'admin', 'administration', 'support', 'ventes', 'vente', 'rh', 'secretariat', 'secretaire', 'accueil', 'service', 'services', 'communication', 'communications', 'marketing', 'facturation', 'comptabilite', 'direction', 'noreply', 'webmaster', 'postmaster', 'general', 'commercial', 'reception', 'urgence', 'urgences'];
  const fragmentsNomsConnus = new Set();
  entities.filter((e) => ['name', 'firstName', 'lastName'].includes(e.category)).forEach((e) => {
    String(e.value).split(/[\s-]+/).forEach((w) => { if (normalize(w).length >= 2) fragmentsNomsConnus.add(normalize(w)); });
  });
  for (const ent of entities.filter((e) => e.category === 'email')) {
    const at = ent.value.indexOf('@');
    if (at < 1) continue;
    const local = ent.value.slice(0, at);
    const tokens = local.split(/[._+-]/).filter((t) => /^[a-zà-ÿ]{2,}$/i.test(t));
    if (tokens.length < 2) continue;
    const connu = (t) => fragmentsNomsConnus.has(normalize(t));
    const matches = tokens.some(connu);
    if (!matches) continue; // aucun fragment connu dans ce courriel : rien à relier, pas de fuite possible (voir commentaire de relinkEmails)
    tokens.forEach((tok, idx) => {
      if (connu(tok)) return;
      if (MOTS_LOCAUX_COURRIEL_IGNORES.includes(normalize(tok))) return; // « info.tremblay@… » : « info » n'est pas un prénom
      const valeur = tok.charAt(0).toUpperCase() + tok.slice(1).toLowerCase();
      push(valeur, idx === 0 ? 'firstName' : 'lastName', idx === 0 ? 'Prénom' : 'Nom de famille', 0.7);
    });
  }
  // 17. Villes CONNUES du Québec (VILLES_QUEBEC = FAKE_DATA.cities, ≥60 municipalités, Lévis
  // incluse — corrige le défaut #2732, ex. « succursale de Lévis » jamais détecté). Triée du nom
  // le plus LONG au plus court avant la boucle par discipline défensive (aucune des entrées
  // actuelles n'est préfixe d'une autre, vérifié, mais un futur ajout à la liste pourrait l'être).
  // buildCityDetectionRegex (défini plus haut, jamais dupliqué) exige la VRAIE casse de la
  // première lettre pour ne pas confondre un mot commun homographe avec la ville (« la prairie »
  // le champ vs « La Prairie » la municipalité).
  const villesTriees = [...FAKE_DATA.cities].sort((a, b) => b.length - a.length);
  for (const ville of villesTriees) {
    const cityRe = buildCityDetectionRegex(ville);
    while ((m = cityRe.exec(text))) push(m[0], 'city', 'Ville', 0.85);
  }
  // 18. Ville CAPITALISÉE INCONNUE de VILLES_QUEBEC, repérée par le CONTEXTE (« succursale de X »,
  // « bureau de X », « à X ») — couvre les municipalités absentes de la liste fermée (qui ne
  // retient que les ~60 plus peuplées). Exclusions pour ne pas confondre avec un prénom/nom de
  // personne déjà repéré ailleurs dans CE texte (entities est déjà rempli à ce stade, y compris par
  // l'étape 16 ci-dessus) ni avec un mot courant (estMotIgnore, déjà défini plus haut, jamais
  // dupliqué). Placée EN DERNIER : elle a besoin que toutes les entités « nom » du texte soient
  // déjà connues pour exclure correctement.
  const nomsPersonnesConnus = new Set();
  entities.filter((e) => ['name', 'firstName', 'lastName'].includes(e.category)).forEach((e) => {
    String(e.value).split(/[\s-]+/).forEach((w) => nomsPersonnesConnus.add(normalize(w)));
  });
  const cityContext = /\b(?:succursale\s+de\s+(?:l['’])?|bureau\s+de\s+(?:l['’])?|à\s+)([A-ZÀ-Ÿ][a-zà-ÿ'’]+(?:-[A-ZÀ-Ÿ]?[a-zà-ÿ'’]+)*)/gu;
  while ((m = cityContext.exec(text))) {
    const candidat = m[1];
    if (estMotIgnore(candidat, STOPWORDS)) continue;
    if (nomsPersonnesConnus.has(normalize(candidat))) continue;
    push(candidat, 'city', 'Ville', 0.7);
  }
  return entities;
}

function getRandomItem(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

// Détecte le genre grammatical d'un prénom réel en le cherchant (insensible casse/accents) dans
// les deux dictionnaires de référence déjà présents dans FAKE_DATA — jamais de nouvelle liste, on
// réutilise l'existant (DRY). Renvoie 'M', 'F', ou null si le prénom n'est dans aucune des deux
// listes (comportement alors neutre et documenté : voir generateFake, corrige D1 sans deviner).
function detectGenrePrenom(prenom) {
  const cible = normaliserMot(prenom);
  if (!cible) return null;
  if (FAKE_DATA.firstNamesF.some((p) => normaliserMot(p) === cible)) return 'F';
  if (FAKE_DATA.firstNamesM.some((p) => normaliserMot(p) === cible)) return 'M';
  return null;
}

// Isole le nom de voie SEUL (sans le type ni l'article), ex. « rang Saint-Joseph » → « Saint-
// Joseph ». Réutilise ARTICLE_ADRESSE/TYPE_VOIE (définis au niveau du module, jamais dupliqués).
// Sert à comparer uniquement le NOM propre de la voie pour l'anti-collision (corrige D5) : avant
// cette extraction, un faux « boulevard Saint-Joseph » n'était jamais reconnu comme collision avec
// le vrai « rang Saint-Joseph » puisque les DEUX chaînes complètes (type + nom) différaient, alors
// que le nom de voie qui fuite réellement au lecteur est le même.
function extraireNomVoieSeul(voieAvecType) {
  const chaine = String(voieAvecType || '').trim();
  const re = new RegExp('^' + TYPE_VOIE + '\\s+(?:' + ARTICLE_ADRESSE + ')?', 'u');
  return chaine.replace(re, '').trim();
}

// Tire un substitut dans une pioche en garantissant qu'il ne redevient jamais la valeur réelle
// (insensible casse/accents/apostrophes via normaliserMot). Boucle BORNÉE (jamais infinie) :
// après maxEssais tirages aléatoires infructueux, repli déterministe = premier élément de la
// pioche qui diffère réellement de la valeur réelle (garanti tant que la pioche a >1 entrée
// distincte, ce qui est toujours le cas ici) ; en dernier recours seulement (pioche à une seule
// valeur), on suffixe pour forcer la différence plutôt que de renvoyer un doublon.
// extracteur (optionnel) : fonction appliquée AUX DEUX côtés (candidat ET valeur réelle) avant
// comparaison — permet de comparer sur un sous-ensemble de la valeur plutôt que sur la chaîne
// entière (ex. extraireNomVoieSeul pour ne comparer que le nom de voie, jamais le type ; corrige
// D5). Par défaut, identité (comportement historique inchangé pour prénoms/noms de famille).
function tirerSubstitutDistinct(pioche, valeurReelle, maxEssais = 12, extracteur) {
  const extraire = typeof extracteur === 'function' ? extracteur : (x) => x;
  const reelleNorm = normaliserMot(extraire(valeurReelle));
  let candidat = getRandomItem(pioche);
  let essais = 0;
  while (normaliserMot(extraire(candidat)) === reelleNorm && essais < maxEssais) {
    candidat = getRandomItem(pioche);
    essais++;
  }
  if (normaliserMot(extraire(candidat)) === reelleNorm) {
    const repli = pioche.find((item) => normaliserMot(extraire(item)) !== reelleNorm);
    candidat = repli !== undefined ? repli : `${candidat}_x`;
  }
  return candidat;
}

// Isole le nom de la voie (type + nom, ex. « rue des Érables ») en retirant le numéro civique de
// tête, pour comparer uniquement la voie — le numéro n'a pas à différer de l'original (règle
// métier : seule la collision sur le nom de la voie doit être évitée, pas sur le numéro).
function extraireVoieAdresse(original) {
  return String(original || '').replace(/^\s*\d{1,5}\s*,?\s*/, '').trim();
}

function generateFake(category, original) {
  switch (category) {
    case 'dossier': {
      // Chaque groupe de chiffres est randomisé INDÉPENDAMMENT (/g, pas seulement le premier) :
      // un code comme « N° 2026-0042 » contient DEUX groupes séparés par un tiret, et l'ancienne
      // version (un seul .replace sans /g) ne changeait que le premier, laissant le second groupe
      // réel intact — fuite partielle. Les lettres de préfixe (« D-», « AB») restent inchangées :
      // « code de même forme », la donnée identifiante est le chiffre, pas la lettre de catégorie.
      return original.replace(/\d+/g, (grp) => grp.replace(/\d/g, () => Math.floor(Math.random() * 10).toString()));
    }
    case 'city': return tirerSubstitutDistinct(FAKE_DATA.cities, original);
    case 'email': {
      return `${getRandomItem(FAKE_DATA.firstNamesM).toLowerCase()}.${getRandomItem(FAKE_DATA.lastNames).toLowerCase()}@${getRandomItem(FAKE_DATA.domains)}`;
    }
    // Échangeur 555 forcé (réservé à la fiction en Amérique du Nord, jamais attribué à un abonné
    // réel) : seuls l'indicatif régional et le numéro d'abonné restent randomisés, l'échangeur
    // (3 chiffres du milieu) est toujours « 555 ». La ponctuation d'origine (parenthèses, tirets,
    // points, espaces) est préservée telle quelle pour ne pas dénaturer le format saisi. Corrige D6.
    case 'phone': {
      const m2 = String(original || '').match(/^(\(?\d{3}\)?)([^\d]*)(\d{3})([^\d]*)(\d{4})$/);
      if (!m2) return original.replace(/\d/g, () => Math.floor(Math.random() * 10).toString());
      const randDigits = (n) => Array.from({ length: n }, () => Math.floor(Math.random() * 10)).join('');
      const zoneAvecParens = /^\(\d{3}\)$/.test(m2[1]);
      const zone = zoneAvecParens ? `(${randDigits(3)})` : randDigits(3);
      return `${zone}${m2[2]}555${m2[4]}${randDigits(4)}`;
    }
    case 'address': {
      const canadianPostalCode = /^[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d$/;
      if (original.trim().match(canadianPostalCode)) {
        const letters = 'ABCEGHJKLMNPRSTVXY';
        const rl = () => letters.charAt(Math.floor(Math.random() * letters.length));
        const rd = () => Math.floor(Math.random() * 10);
        return `${rl()}${rd()}${rl()} ${rd()}${rl()}${rd()}`;
      }
      const num = Math.floor(Math.random() * 990) + 10;
      // La collision porte sur le NOM DE VOIE SEUL (extraireNomVoieSeul), jamais sur le numéro
      // civique ni sur le type de voie (règle métier) : sans cet extracteur, un faux « boulevard
      // Saint-Joseph » n'était jamais reconnu comme collision avec le vrai « rang Saint-Joseph »
      // puisque les chaînes complètes type+nom différaient (fuite 23-36/300 prouvée par exécution,
      // corrige D5).
      const street = tirerSubstitutDistinct(FAKE_DATA.streets, extraireVoieAdresse(original), 12, extraireNomVoieSeul);
      return `${num} ${street}`;
    }
    case 'name': {
      // Nom composé (particules « de », « La »… éventuelles) : seuls le premier mot (prénom réel)
      // et le dernier mot (nom de famille réel) comptent pour l'anti-collision, chacun comparé
      // séparément à sa propre pioche (une collision partielle, ex. faux prénom == vrai prénom
      // avec un faux nom différent, doit être bloquée tout autant qu'une collision totale).
      // Le faux prénom est tiré dans la MÊME liste de genre que le prénom réel (detectGenrePrenom,
      // dictionnaire = FAKE_DATA.firstNamesM/F déjà existants, jamais dupliqués) : un prénom
      // reconnu féminin reçoit un faux prénom féminin, et inversement — corrige D1 (152/300
      // substituts masculins pour un prénom féminin avant ce correctif). Un prénom INCONNU des
      // deux listes garde le comportement neutre historique (pioche combinée M+F), documenté :
      // on ne devine jamais le genre d'un prénom absent du dictionnaire.
      const mots = String(original || '').trim().split(/\s+/).filter(Boolean);
      const premierMotReel = mots[0] || '';
      const dernierMotReel = mots[mots.length - 1] || '';
      const genre = detectGenrePrenom(premierMotReel);
      const pisteFirst = genre === 'F' ? FAKE_DATA.firstNamesF
        : genre === 'M' ? FAKE_DATA.firstNamesM
        : [...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF];
      const fakeFirst = tirerSubstitutDistinct(pisteFirst, premierMotReel);
      const fakeLast = tirerSubstitutDistinct(FAKE_DATA.lastNames, dernierMotReel);
      return `${fakeFirst} ${fakeLast}`;
    }
    case 'firstName': {
      // Même règle de genre que pour 'name' ci-dessus (DRY : detectGenrePrenom réutilisé tel quel).
      const genre = detectGenrePrenom(original);
      const piste = genre === 'F' ? FAKE_DATA.firstNamesF
        : genre === 'M' ? FAKE_DATA.firstNamesM
        : [...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF];
      return tirerSubstitutDistinct(piste, original);
    }
    case 'lastName': return tirerSubstitutDistinct(FAKE_DATA.lastNames, original);
    case 'amount': return '$' + ((Math.floor(Math.random() * 9000) + 100)) + ',00';
    case 'date': {
      const year = Math.floor(Math.random() * (2024 - 1950 + 1)) + 1950;
      const month = Math.floor(Math.random() * 12) + 1;
      const day = Math.floor(Math.random() * 28) + 1;
      const pad = (n) => n.toString().padStart(2, '0');
      if (/^\d{4}-\d{2}-\d{2}$/.test(original)) return `${year}-${pad(month)}-${pad(day)}`;
      if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(original)) return `${pad(day)}/${pad(month)}/${year}`;
      const monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
      if (monthsFr.some(m => original.toLowerCase().includes(m))) return `${day} ${monthsFr[month - 1]} ${year}`;
      return `${year}-${pad(month)}-${pad(day)}`;
    }
    case 'id': // RAMQ, permis, matricule : on randomise chiffres ET lettres en gardant le format
      return original.replace(/\d/g, () => Math.floor(Math.random() * 10).toString())
                     .replace(/[A-ZÀ-Ÿ]/g, () => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)]);
    case 'organization':
    case 'other': return getRandomItem(FAKE_DATA.companies);
    default: return getRandomItem(FAKE_DATA.companies);
  }
}

// Garde-fou anti-fuite : garantit que le faux n'égale jamais l'original (insensible
// casse/accents/apostrophes — réutilise normaliserMot, ne duplique pas la normalisation).
function safeFake(category, original) {
  const no = normaliserMot(original);
  let result = generateFake(category, original), attempts = 0;
  while (normaliserMot(result) === no && attempts < 8) { result = generateFake(category, original); attempts++; }
  return normaliserMot(result) === no ? result + '_x' : result;
}

// Unicité globale : aucun faux n'égale un original ni un autre faux déjà utilisé (réversibilité
// garantie). Alias de normaliserMot (DRY) : même règle d'insensibilité que le reste du moteur.
const _normU = normaliserMot;
function uniqueFake(category, original, used) {
  let result = safeFake(category, original), attempts = 0;
  while (used.has(_normU(result)) && attempts < 12) { result = safeFake(category, original); attempts++; }
  if (used.has(_normU(result))) { let n = 1; while (used.has(_normU(result + ' ' + n))) n++; result = result + ' ' + n; }
  used.add(_normU(result));
  return result;
}

function tokenLabel(category) {
  const labelMap = {
    name: 'PERSONNE', firstName: 'PERSONNE', lastName: 'PERSONNE',
    dossier: 'DOSSIER', address: 'ADRESSE', email: 'COURRIEL', city: 'VILLE',
    phone: 'TEL', amount: 'MONTANT', date: 'DATE'
  };
  return labelMap[category] || 'ORG';
}

function buildRules(selections, opts = {}) {
  const mode = opts.mode || 'pseudo';
  const existing = opts.existing || [];
  if (mode === 'tokens') {
    const normalize = (str) => str.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const tokenIndex = new Map();
    const existingTokens = new Map();
    for (const rule of existing) {
      const match = /^\[([A-Z_]+)_(\d+)\]$/.exec(rule.replacement || '');
      if (match) {
        const label = match[1], idx = parseInt(match[2], 10);
        if (!existingTokens.has(label) || existingTokens.get(label) < idx) existingTokens.set(label, idx);
      }
    }
    const rules = [];
    const seen = new Map();
    for (const sel of selections) {
      const { value, category } = sel;
      const normValue = normalize(value);
      const label = tokenLabel(category);
      const id = `rule_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
      if (seen.has(normValue)) {
        rules.push({ id, original: value, replacement: seen.get(normValue), category });
        continue;
      }
      let currentIndex = tokenIndex.has(label) ? tokenIndex.get(label) : (existingTokens.get(label) || 0);
      currentIndex += 1;
      tokenIndex.set(label, currentIndex);
      const replacement = `[${label}_${currentIndex}]`;
      seen.set(normValue, replacement);
      rules.push({ id, original: value, replacement, category });
    }
    return rules;
  }
  const rules = [];
  const nameMap = new Map(); // partie réelle (minuscule) -> faux (cohérence + garde-fou)
  const subDone = new Set();  // parties ayant déjà une sous-règle
  const used = new Set();     // faux déjà utilisés (unicité globale)
  for (const r of existing) { used.add(_normU(r.original)); used.add(_normU(r.replacement)); }
  for (const sel of selections) {
    used.add(_normU(sel.value));
    // Anti-collision : un faux prénom/nom ne doit jamais réutiliser un VRAI composant de nom du texte.
    if (['name', 'firstName', 'lastName'].includes(sel.category)) {
      for (const token of String(sel.value).split(/[\s\-]+/)) {
        if (_normU(token).length >= 2) used.add(_normU(token));
      }
    }
  }
  for (const sel of selections) {
    const { value, category } = sel;
    const id = `rule_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    if (category === 'name') {
      const parts = value.split(/\s+/).filter(Boolean);
      if (parts.length === 1) {
        // Un seul mot étiqueté 'name' (ex. sélection manuelle d'un nom seul) → UN seul faux,
        // jamais un prénom + nom inventé. Cohérence via nameMap.
        const k = parts[0].toLowerCase();
        let fake = nameMap.get(k);
        if (fake === undefined) { fake = uniqueFake('lastName', parts[0], used); nameMap.set(k, fake); }
        rules.push({ id, original: value, replacement: fake, category: 'lastName' });
        continue;
      }
      if (parts.length === 2) {
        const [first, last] = parts;
        const fk = first.toLowerCase(), lk = last.toLowerCase();
        let fakeFirst = nameMap.get(fk); if (fakeFirst === undefined) { fakeFirst = uniqueFake('firstName', first, used); nameMap.set(fk, fakeFirst); }
        let fakeLast = nameMap.get(lk); if (fakeLast === undefined) { fakeLast = uniqueFake('lastName', last, used); nameMap.set(lk, fakeLast); }
        rules.push({ id, original: value, replacement: `${fakeFirst} ${fakeLast}`, category });
        if (!subDone.has(fk)) { subDone.add(fk); rules.push({ id: `${id}_first`, original: first, replacement: fakeFirst, category: 'firstName' }); }
        if (!subDone.has(lk)) { subDone.add(lk); rules.push({ id: `${id}_last`, original: last, replacement: fakeLast, category: 'lastName' }); }
        continue;
      }
    }
    // Prénom/nom ISOLÉ (mode pseudo) : s'il est déjà une composante d'un nom complet (présent dans
    // nameMap), la sous-règle correspondante le couvre DÉJÀ partout (y compris ses occurrences seules) ;
    // on n'ajoute donc PAS de règle parasite (qui désynchroniserait le faux courriel). Corrige D1.
    if (category === 'firstName' || category === 'lastName') {
      const k = value.toLowerCase();
      if (nameMap.has(k)) continue;
      const fake = uniqueFake(category, value, used); nameMap.set(k, fake);
      rules.push({ id, original: value, replacement: fake, category });
      continue;
    }
    rules.push({ id, original: value, replacement: uniqueFake(category, value, used), category });
  }
  // Garantie finale : tous les remplacements globalement uniques (réversibilité 100 %)
  const usedFinal = new Set();
  for (const rule of rules) {
    let rep = rule.replacement, tries = 0;
    while (usedFinal.has(_normU(rep)) && tries < 15) { rep = generateFake(rule.category, rule.original); tries++; }
    let n = 1; while (usedFinal.has(_normU(rep))) { rep = rule.replacement + ' ' + n; n++; }
    rule.replacement = rep; usedFinal.add(_normU(rep));
  }
  return rules;
}

function anonymize(text, rules, overrides = []) {
  if (!rules || rules.length === 0) return text;
  const intervals = [];
  const seen = new Set();
  for (const rule of rules) {
    if (seen.has(rule.original)) continue;
    seen.add(rule.original);
    const regex = buildAccentInsensitiveBoundedRegex(rule.original);
    let match;
    while ((match = regex.exec(text)) !== null) {
      if (match[0].length === 0) { regex.lastIndex = Math.max(regex.lastIndex + 1, match.index + 1); continue; }
      intervals.push({ start: match.index, end: match.index + match[0].length, original: rule.original, replacement: rule.replacement, len: match[0].length });
      if (regex.lastIndex === match.index) regex.lastIndex++;
    }
  }
  if (intervals.length === 0) return text;
  intervals.sort((a, b) => (a.start !== b.start) ? a.start - b.start : b.len - a.len);
  const nonOverlapping = [];
  let lastEnd = -1;
  for (const it of intervals) { if (it.start >= lastEnd) { nonOverlapping.push(it); lastEnd = it.end; } }
  const normalizeKey = (str) => str.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const overrideMap = new Map();
  for (const ov of (overrides || [])) {
    const key = normalizeKey(ov.original);
    if (!overrideMap.has(key)) overrideMap.set(key, []);
    overrideMap.get(key).push({ occ: ov.occ, replacement: ov.replacement });
  }
  const occ = new Map();
  const out = [];
  let lastIndex = 0;
  for (const it of nonOverlapping) {
    const key = normalizeKey(it.original);
    const count = occ.has(key) ? occ.get(key) : 0;
    occ.set(key, count + 1);
    let replacement = it.replacement;
    const ovs = overrideMap.get(key);
    if (ovs) { const o = ovs.find(x => x.occ === count); if (o) replacement = o.replacement; }
    out.push(text.slice(lastIndex, it.start), replacement);
    lastIndex = it.end;
  }
  out.push(text.slice(lastIndex));
  return out.join('');
}

function restore(aiText, rules, overrides = []) {
  if (!Array.isArray(rules)) rules = [];
  const ovRules = (overrides || []).map(o => ({ original: o.original, replacement: o.replacement, category: 'override' }));
  const all = [...ovRules, ...rules];
  if (all.length === 0) return { text: aiText, found: [], notFound: [] };
  // Intervalles des remplacements présents dans le texte IA (dédup par replacement)
  const intervals = [];
  const seen = new Set();
  for (const entry of all) {
    if (!entry.replacement || seen.has(entry.replacement)) continue;
    seen.add(entry.replacement);
    const regex = buildAccentInsensitiveUnboundedRegex(entry.replacement);
    let match;
    while ((match = regex.exec(aiText)) !== null) {
      if (match[0].length === 0) { regex.lastIndex = Math.max(regex.lastIndex + 1, match.index + 1); continue; }
      intervals.push({ start: match.index, end: match.index + match[0].length, replacement: entry.replacement, original: entry.original, len: match[0].length });
      if (regex.lastIndex === match.index) regex.lastIndex++;
    }
  }
  intervals.sort((a, b) => (a.start !== b.start) ? a.start - b.start : b.len - a.len);
  const nonOverlapping = [];
  let lastEnd = -1;
  for (const it of intervals) { if (it.start >= lastEnd) { nonOverlapping.push(it); lastEnd = it.end; } }
  let result = '', lastIndex = 0;
  const usedRepl = new Set();
  for (const it of nonOverlapping) {
    result += aiText.slice(lastIndex, it.start) + it.original;
    lastIndex = it.end;
    usedRepl.add(it.replacement);
  }
  result += aiText.slice(lastIndex);
  const found = [], notFound = [];
  for (const rule of rules) { (usedRepl.has(rule.replacement) ? found : notFound).push(rule); }
  return { text: result, found, notFound };
}

// Cohérence courriel ↔ nom : si le nom de la personne apparaît dans la partie locale d'un
// courriel (« martin.rousseau@… »), on remplace ces jetons par le MÊME faux nom (« marc.fortin@… »)
// au lieu d'un nom aléatoire. Réversibilité préservée (le replacement reste unique).
function relinkEmails(rules) {
  if (!Array.isArray(rules) || !rules.length) return rules;
  const norm = (s) => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const map = new Map(); // jeton réel (prénom/nom + sous-composantes de noms composés) → faux jeton
  for (const r of rules) {
    if (r.category === 'firstName' || r.category === 'lastName') {
      for (const part of String(r.original).split(/[\s\-]+/)) { if (norm(part).length >= 2) map.set(norm(part), r.replacement); }
    } else if (r.category === 'name') {
      const o = String(r.original).split(/\s+/), f = String(r.replacement || '').split(/\s+/);
      o.forEach((w, i) => { if (f[i]) { for (const sub of String(w).split(/[\s\-]+/)) { if (norm(sub).length >= 2) map.set(norm(sub), f[i]); } } });
    }
  }
  if (!map.size) return rules;
  const used = new Set(rules.map(r => norm(r.replacement)));
  for (const r of rules) {
    if (r.category !== 'email' || !String(r.original).includes('@')) continue;
    const at = r.original.indexOf('@');
    const local = r.original.slice(0, at);
    const repAt = String(r.replacement || '').indexOf('@');
    const fakeDomain = repAt >= 0 ? r.replacement.slice(repAt) : '@example.com';
    let changed = false;
    const newLocal = local.split(/([._+\-])/).map((tok) => {
      const f = map.get(norm(tok));
      if (f) { changed = true; return norm(f).replace(/\s+/g, ''); } // faux jeton sans accent/espace (courriel valide)
      return tok;
    }).join('');
    if (!changed) continue;
    used.delete(norm(r.replacement));
    let candidate = newLocal + fakeDomain, n = 1;
    while (used.has(norm(candidate))) { candidate = newLocal + n + fakeDomain; n++; }
    r.replacement = candidate;
    used.add(norm(candidate));
  }
  return rules;
}

// Filet de sécurité anti-fuite résiduelle : cette fonction NE CORRIGE RIEN, elle SIGNALE. Après
// l'anonymisation, un mot-source (venant d'une règle) peut encore survivre tel quel dans le texte
// (ex. règle « Marc Tremblay » → « Luc Fortin » détectée, mais un « Tremblay » isolé ailleurs dans
// le texte n'était couvert par aucune règle et reste en clair). On la lance en dernier recours pour
// avertir la personne AVANT qu'elle copie un texte incomplètement masqué, jamais pour bloquer.
function detecterFuitesResiduelles(texteAnonymise, regles) {
  if (!texteAnonymise || !Array.isArray(regles) || regles.length === 0) return [];
  function echapperRegex(chaine) { return chaine.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
  const motsSubstituts = new Set();
  regles.forEach(function (regle) {
    if (regle && regle.replacement) {
      String(regle.replacement).split(/[^\p{L}\p{N}]+/u).filter(Boolean).forEach(function (mot) {
        motsSubstituts.add(normaliserMot(mot));
      });
    }
  });
  const resultat = [];
  const dejaVus = new Set();
  regles.forEach(function (regle) {
    // Compatible avec les deux formes de règles du fichier : les sélections brutes (.value) et
    // les règles produites par buildRules()/utilisées par anonymize() (.original).
    const valeurSource = regle && (regle.original != null ? regle.original : regle.value);
    if (!regle || !valeurSource) return;
    String(valeurSource).split(/[^\p{L}\p{N}]+/u).filter(Boolean).forEach(function (mot) {
      if (mot.length < 3) return;
      const norm = normaliserMot(mot);
      if (motsSubstituts.has(norm) || dejaVus.has(norm)) return;
      const motif = new RegExp('(?<![\\p{L}\\p{N}])' + echapperRegex(mot) + '(?![\\p{L}\\p{N}])', 'iu');
      if (motif.test(texteAnonymise)) {
        dejaVus.add(norm);
        resultat.push({ fragment: mot, source: valeurSource });
      }
    });
  });
  return resultat;
}

const AnonymizerCore = { detectEntities, generateFake, buildRules, anonymize, restore, relinkEmails, detecterFuitesResiduelles, buildAccentInsensitiveBoundedRegex, buildAccentInsensitiveUnboundedRegex };
if (typeof module !== 'undefined' && module.exports) module.exports = AnonymizerCore;
if (typeof window !== 'undefined') window.AnonymizerCore = AnonymizerCore;
