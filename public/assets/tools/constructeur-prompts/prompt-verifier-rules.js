// prompt-verifier-rules.js - règles de détection déterministes (zéro appel IA), partagées.
// Auteur : MEMORA solutions, https://memora.solutions ; info@memora.ca
//
// Fichier séparé et réutilisable à dessein (étape 4 de la refonte du Constructeur de prompts,
// voir .outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md section 5) : l'étape 7, plus tard,
// fera importer ce même fichier par /outils/anonymiseur, pour que les deux interfaces détectent
// exactement les mêmes motifs - un seul jeu de règles, jamais deux moteurs divergents derrière un
// simple bouton. Cette étape-ci NE MODIFIE PAS les fichiers de l'anonymiseur (anonymizer-core.js,
// anonymizer-ui.js, anonymizer-rich.js) : ce fichier vit seul, consommé pour l'instant uniquement
// par constructeur-prompts-core.js.
//
// Portée volontairement limitée (critère d'acceptation 3 du plan) : ce vérificateur repère des
// MOTIFS STRUCTURÉS ÉVIDENTS (courriel, téléphone, code permanent/RAMQ), jamais un nom ou une
// situation glissés dans un texte libre. Ce n'est jamais présenté comme une garantie - les
// libellés de rappel imparfait vivent dans constructeur-prompts-core.js, pas ici.
(function (root) {
  'use strict';

  // Indicatifs régionaux québécois (Numéroteur nord-américain) - couverture explicitement
  // demandée par le plan (section 3) : le public cible (enseignants) manipule ces numéros au
  // quotidien, un vérificateur qui les rate échoue précisément sur la donnée la plus fréquente.
  var QUEBEC_AREA_CODES = ['418', '581', '367', '819', '438', '514', '450', '579'];

  // Téléphone nord-américain (NANP), formats courants : 514-555-1234, (514) 555-1234,
  // 514.555.1234, 5145551234, avec ou sans indicatif pays +1. Bornes (?<!\d)/(?!\d) pour ne pas
  // capter un sous-segment d'un plus grand nombre (numéro de facture, RAMQ, etc.).
  var RE_PHONE = /(?<!\d)(?:\+?1[-.\s]?)?(?:\(\d{3}\)|\d{3})[-.\s]?\d{3}[-.\s]?\d{4}(?!\d)/g;

  // Courriel : forme standard, suffisante pour un vérificateur déterministe (pas une validation
  // RFC complète, hors scope d'un simple rappel local).
  var RE_EMAIL = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;

  // Code permanent (MEEQ) et numéro d'assurance maladie RAMQ : MÊME FORMAT (4 lettres + 8
  // chiffres, ex. TREM12345678) - un seul motif suffit pour les deux, même logique que la règle
  // RAMQ de l'anonymiseur (anonymizer-core.js).
  var RE_PERMANENT_RAMQ = /\b[A-ZÀ-Ÿ]{4}\s?\d{4}\s?\d{2}\s?\d{2}\b/g;

  // Patronymes/prénoms québécois composés (Marie-Ève, Jean-Philippe, Anne-Sophie...) : SEUL le
  // motif composé à trait d'union est retenu ici, volontairement - un simple mot capitalisé
  // accentué (ex. "École", "Élève") produirait un faux positif à quasi CHAQUE prompt d'un
  // enseignant et éroderait la confiance dans le vérificateur, exactement la leçon de l'incident
  // Schedule du 2026-08-02 (une alerte qui crie au loup trop souvent finit ignorée). Le trait
  // d'union entre deux mots capitalisés est un signal beaucoup plus fiable d'un prénom composé
  // réel, rarement autre chose en français courant.
  var RE_COMPOUND_NAME = /\b[A-ZÀ-Ý][a-zà-ÿ]+-[A-ZÀ-Ý][a-zà-ÿ]+\b/g;

  function collect(regex, text, category, label) {
    var out = [];
    var m;
    regex.lastIndex = 0;
    while ((m = regex.exec(text)) !== null) {
      out.push({ category: category, label: label, match: m[0], index: m.index });
      if (m.index === regex.lastIndex) regex.lastIndex += 1;
    }
    return out;
  }

  function isQuebecPhone(matchText) {
    var digits = matchText.replace(/\D/g, '');
    if (digits.length === 11 && digits.charAt(0) === '1') digits = digits.slice(1);
    if (digits.length !== 10) return false;
    return QUEBEC_AREA_CODES.indexOf(digits.slice(0, 3)) !== -1;
  }

  /**
   * Détecte les motifs structurés évidents dans un texte. Retourne un tableau d'entités
   * { category, label, match, index }, triées par ordre d'apparition. Jamais d'appel réseau,
   * jamais garanti à 100 % - un rappel imparfait, volontairement (voir critère d'acceptation 3).
   */
  function detect(text) {
    if (typeof text !== 'string' || text.trim() === '') return [];

    var found = []
      .concat(collect(RE_EMAIL, text, 'email', 'Courriel'))
      .concat(collect(RE_PERMANENT_RAMQ, text, 'id', 'Code permanent ou numéro RAMQ'))
      .concat(collect(RE_PHONE, text, 'phone', 'Téléphone'))
      .concat(collect(RE_COMPOUND_NAME, text, 'name', "Prénom composé (peut-être un nom d'élève)"));

    // Étiquette additionnelle si l'indicatif régional est québécois - repère utile pour
    // l'enseignant, ne change pas la catégorie de détection sous-jacente.
    found.forEach(function (ent) {
      if (ent.category === 'phone' && isQuebecPhone(ent.match)) {
        ent.label = 'Téléphone (indicatif québécois)';
      }
    });

    found.sort(function (a, b) { return a.index - b.index; });

    return found;
  }

  var api = { detect: detect, QUEBEC_AREA_CODES: QUEBEC_AREA_CODES };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  } else {
    root.PromptVerifierRules = api;
  }
})(typeof window !== 'undefined' ? window : this);
