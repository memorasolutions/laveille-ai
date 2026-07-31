// anonymizer-core.js — moteur pur réversible (testé en Node + navigateur)

const FAKE_DATA = {
  firstNamesM: ['Jean', 'Pierre', 'Michel', 'André', 'Luc', 'Marc', 'Philippe', 'François', 'David', 'Mathieu'],
  firstNamesF: ['Marie', 'Julie', 'Sophie', 'Isabelle', 'Nathalie', 'Claire', 'Émilie', 'Caroline', 'Manon', 'Audrey'],
  lastNames: ['Tremblay', 'Gagnon', 'Bouchard', 'Gauthier', 'Morin', 'Lavoie', 'Fortin', 'Gagné', 'Pelletier', 'Bélanger'],
  streets: ['rue Principale', 'avenue du Parc', 'boulevard Saint-Joseph', 'chemin de la Rivière', 'rue des Érables', 'avenue Laurier', 'boulevard René-Lévesque', 'rue Saint-Denis', 'chemin Sainte-Foy', 'rue de la Gauchetière'],
  cities: ['Montréal', 'Québec', 'Laval', 'Gatineau', 'Longueuil', 'Sherbrooke', 'Saguenay', 'Lévis', 'Trois-Rivières', 'Terrebonne'],
  domains: ['gmail.com', 'hotmail.com', 'yahoo.ca', 'videotron.ca', 'bell.net'],
  companies: ['Constructions Boréal inc.', 'Groupe Solva', 'Entreprises Lemay-Côté', 'Gestion Riverin', 'Atelier Norjak', 'Services Permafort', 'Coopérative Verdelis', 'Industries Cap-Vert', 'Groupe Makila', 'Solutions Drakkar']
};

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function getAccentClass(char) {
  const base = char.toLowerCase();
  const map = {
    'a': '[aàâäAÀÂÄ]', 'e': '[eéèêëEÉÈÊË]', 'i': '[iîïIÎÏ]',
    'o': '[oôöOÔÖ]', 'u': '[uùûüUÙÛÜ]', 'c': '[cçCÇ]', 'n': '[nñNÑ]'
  };
  return map[base] || char;
}

// Construit une regex bornée, insensible casse + accents, espaces flexibles.
function buildAccentInsensitiveBoundedRegex(str) {
  let escaped = escapeRegex(str).replace(/\s+/g, '\\s+');
  let pattern = '';
  for (let i = 0; i < escaped.length; i++) {
    const char = escaped[i];
    if (char === '\\') { pattern += char + escaped[++i]; continue; } // garde \. \s etc.
    if (char === '+') { pattern += char; continue; }
    pattern += getAccentClass(char);
  }
  const startBoundary = /^\w/.test(str) ? '(?<![A-Za-zÀ-ÖØ-öø-ÿ0-9])' : '(?<!\\w)';
  const endBoundary = /\w$/.test(str) ? '(?![A-Za-zÀ-ÖØ-öø-ÿ0-9])' : '(?!\\w)';
  return new RegExp(startBoundary + pattern + endBoundary, 'gi');
}

// Variante sans boundary pour la restauration : les pseudos sont uniques par construction,
// inutile de risquer un échec de \b quand le texte IA est collé (textContent sans séparateurs).
function buildAccentInsensitiveUnboundedRegex(str) {
  let escaped = escapeRegex(str).replace(/\s+/g, '\\s+');
  let pattern = '';
  for (let i = 0; i < escaped.length; i++) {
    const char = escaped[i];
    if (char === '\\') { pattern += char + escaped[++i]; continue; }
    if (char === '+') { pattern += char; continue; }
    pattern += getAccentClass(char);
  }
  return new RegExp(pattern, 'gi');
}

// Mots courants (verbes d'introduction, salutations, connecteurs) qui précèdent souvent un vrai
// nom propre dans une phrase et qui, capitalisés en début de phrase, se font passer pour un prénom
// par la regex de détection des noms composés. Sans les exclure, le mot ignoré VOLE la fenêtre
// d'appariement à deux mots consécutifs et le vrai nom de famille qui suit reste orphelin, donc
// jamais détecté (ex. « Appelle Marc Tremblay » → seul « Appelle Marc » est capté).
const MOTS_IGNORES_SUPPLEMENTAIRES = ['appelle', 'appelez', 'contacte', 'contactez', 'informe', 'informez', 'joins', 'joignez', 'rejoins', 'rejoignez', 'veuillez', 'prière', 'écris', 'écrivez', 'envoie', 'envoyez', 'demande', 'demandez', 'rappelle', 'rappelez', 'préviens', 'prévenez', 'transmets', 'transmettez', 'remercie', 'remerciez', 'salue', 'saluez', 'bonjour', 'bonsoir', 'merci', 'cordialement', 'salutations', 'objet', 'sujet', 'note', 'attention', 'urgent', 'important', 'voici', 'voilà', 'ensuite', 'ainsi', 'donc', 'cependant', 'toutefois', 'aussi', 'enfin', 'bref'];

function normaliserMot(mot) {
  if (!mot) return '';
  return mot.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
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
  const ARTICLE_ADRESSE = "(?:de\\s+la\\s+|de\\s+l['’]|du\\s+|des\\s+|de\\s+)";
  const TYPE_VOIE = "(?:[Rr]ue|[Aa]venue|[Aa]v\\.?|[Bb]oulevard|[Bb]oul\\.?|[Cc]hemin|[Cc]h\\.?|[Rr]ang|[Mm]ont[ée]e|[Pp]lace|[Ii]mpasse)";
  const MOT_VOIE = "\\p{Lu}(?:\\p{Ll}+|(?=['’]))(?:['’]\\p{Lu}?\\p{Ll}*)*(?:-\\p{Lu}?\\p{Ll}*)*";
  const address = new RegExp(
    '\\b\\d{1,5},?\\s+' + TYPE_VOIE + '\\s+' +
    '(?:' + ARTICLE_ADRESSE + ')?' +
    MOT_VOIE + '(?:\\s+' + MOT_VOIE + ')*',
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
  // 9. Numéro de dossier
  const dossier = /(?:#|dossier\s*(?:n[°o]\s*)?)\d+/gi;
  while ((m = dossier.exec(text))) push(m[0], 'dossier', 'Numéro de dossier', 0.95);
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
  const NAME_WORD = "\\p{Lu}(?:\\p{Ll}+|(?=['’]))(?:['’]\\p{Lu}?\\p{Ll}*)*(?:-\\p{Lu}?\\p{Ll}*)*";
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
    if (!w1Ignore && !w2Ignore) push(`${m[1]} ${m[2]}`, 'name', 'Nom complet', 0.75);
  }
  // 2c. Inversion « Nom, Prénom » (fréquente dans les tableaux/listes triées alphabétiquement). Les
  // deux composantes sont poussées séparément (firstName/lastName) plutôt que le span complet avec
  // la virgule : chaque mot est alors remplacé indépendamment à sa position, la virgule reste
  // intacte, et la logique existante de buildRules()/nameMap (déjà écrite pour firstName/lastName)
  // s'applique sans code additionnel à dupliquer. FAKE_DATA.cities est réutilisé (jamais dupliqué)
  // comme liste d'exclusion : sans elle, « Trois-Rivières, Québec » serait pris pour un nom inversé.
  const VILLES_CONNUES = new Set(FAKE_DATA.cities.map(normalize));
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
  return entities;
}

function getRandomItem(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

function generateFake(category, original) {
  switch (category) {
    case 'dossier': {
      const num = original.match(/\d+/)[0];
      const fake = num.replace(/\d/g, () => Math.floor(Math.random() * 10).toString());
      return original.replace(/\d+/, fake);
    }
    case 'email': {
      return `${getRandomItem(FAKE_DATA.firstNamesM).toLowerCase()}.${getRandomItem(FAKE_DATA.lastNames).toLowerCase()}@${getRandomItem(FAKE_DATA.domains)}`;
    }
    case 'phone': return original.replace(/\d/g, () => Math.floor(Math.random() * 10).toString());
    case 'address': {
      const canadianPostalCode = /^[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d$/;
      if (original.trim().match(canadianPostalCode)) {
        const letters = 'ABCEGHJKLMNPRSTVXY';
        const rl = () => letters.charAt(Math.floor(Math.random() * letters.length));
        const rd = () => Math.floor(Math.random() * 10);
        return `${rl()}${rd()}${rl()} ${rd()}${rl()}${rd()}`;
      }
      const num = Math.floor(Math.random() * 990) + 10;
      return `${num} ${getRandomItem(FAKE_DATA.streets)}`;
    }
    case 'name': return `${getRandomItem([...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF])} ${getRandomItem(FAKE_DATA.lastNames)}`;
    case 'firstName': return getRandomItem([...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF]);
    case 'lastName': return getRandomItem(FAKE_DATA.lastNames);
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

// Garde-fou anti-fuite : garantit que le faux n'égale jamais l'original (insensible casse/accents)
function safeFake(category, original) {
  const norm = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const no = norm(original);
  let result = generateFake(category, original), attempts = 0;
  while (norm(result) === no && attempts < 8) { result = generateFake(category, original); attempts++; }
  return norm(result) === no ? result + '_x' : result;
}

// Unicité globale : aucun faux n'égale un original ni un autre faux déjà utilisé (réversibilité garantie)
const _normU = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
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
    dossier: 'DOSSIER', address: 'ADRESSE', email: 'COURRIEL',
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
