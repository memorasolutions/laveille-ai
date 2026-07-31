// tests/js/constructeur-prompts-inplace-masking.test.cjs
// Round 148 (2026-07-31) : refonte « anonymisation en place ». Avant, cliquer #cpAnonToggle
// FAISAIT DISPARAÎTRE #cpTaskField et ouvrait le panneau partagé en mode Split (« Votre texte » /
// « Texte anonymisé ») - la personne passait d'une seule zone visible à deux pour une seule
// intention. Décision tranchée (recherche + panel Perplexity/Gemini 95/100, Codex 82/100) :
// #cpAnonToggle détecte et remplace directement le contenu de #cpTaskObject, qui reste TOUJOURS
// visible ; il n'ouvre plus jamais #cpAnonPanel.
//
// Ce test exerce le VRAI fichier de bout en bout (pas une réimplémentation) : champ vide, champ
// sans PII, champ avec PII (masquage + récapitulatif + annulation), et la preuve de découplage
// d'avec le panneau partagé (qui reste réservé au garde-fou des autres champs).
// Lancer avec : node tests/js/constructeur-prompts-inplace-masking.test.cjs
// Auteur : MEMORA solutions, https://memora.solutions
const fs = require('fs');
const path = require('path');

let pass = 0;
let fail = 0;

function assert(condition, label) {
  if (condition) {
    console.log('  ✅ ' + label);
    pass++;
  } else {
    console.log('  ❌ ' + label);
    fail++;
  }
}

function makeField(id) {
  const listeners = {};
  return {
    id,
    value: '',
    style: {},
    attrs: {},
    listeners,
    addEventListener(type, cb) { (listeners[type] = listeners[type] || []).push(cb); },
    dispatchEvent(evt) { (listeners[evt.type] || []).forEach((cb) => cb(evt)); },
    click() { (listeners.click || []).forEach((cb) => cb(undefined)); },
    setAttribute(name, value) { this.attrs[name] = value; },
    getAttribute(name) { return Object.prototype.hasOwnProperty.call(this.attrs, name) ? this.attrs[name] : null; },
    focus() {},
    // Round 148 : ni .select() ni .setSelectionRange() ne sont fournis - exerce volontairement le
    // repli défensif de ecrireEnPreservantAnnuler() vers .value (document.execCommand n'existe pas
    // non plus dans ce faux DOM, donc le chemin "navigateur minoritaire" est exercé pour de vrai).
    scrollIntoView() {},
    textContent: ''
  };
}

function makeElementStub() {
  return {
    style: {},
    attrs: {},
    setAttribute(name, value) { this.attrs[name] = value; },
    appendChild() {},
    addEventListener() {},
    dispatchEvent() {},
    remove() {},
    textContent: '',
    nextElementSibling: null
  };
}

const fields = {
  cpAnonToggle: makeField('cpAnonToggle'),
  cpAnonPanel: makeField('cpAnonPanel'),
  cpAnonInsert: makeField('cpAnonInsert'),
  cpTaskObject: makeField('cpTaskObject'),
  cpAnonRecap: makeField('cpAnonRecap'),
  cpAnonRecapText: makeField('cpAnonRecapText'),
  cpAnonUndo: makeField('cpAnonUndo'),
  anonSource: makeField('anonSource')
};
// États initiaux fidèles au rendu Blade réel (round 148) : panneau fermé, récapitulatif et bouton
// Annuler masqués.
fields.cpAnonPanel.style.display = 'none';
fields.cpAnonPanel.attrs['aria-hidden'] = 'true';
fields.cpAnonRecap.style.display = 'none';
fields.cpAnonUndo.style.display = 'none';

let domReady;

global.document = {
  addEventListener(type, cb) { if (type === 'DOMContentLoaded') domReady = cb; },
  getElementById(id) { return fields[id] || null; },
  createElement() { return makeElementStub(); }
  // Round 148 : PAS de execCommand ici (volontaire, voir makeField ci-dessus).
};

const toasts = [];
global.window = {
  promptBuilderConfig: { i18n: {} },
  dispatchEvent(evt) { toasts.push(evt); },
  AnonymizerCore: {
    // Mock déterministe mais réaliste : reproduit la forme exacte {value, category, label} rendue
    // par anonymizer-core.js (voir detectEntities réel), pour que resumerMasquage() soit exercée
    // avec de vraies clés de regroupement.
    detectEntities(txt) {
      const entities = [];
      if (/jean\.dupont@exemple\.com/.test(txt)) {
        entities.push({ value: 'jean.dupont@exemple.com', category: 'email', label: 'Courriel' });
      }
      if (/Jean Dupont/.test(txt)) {
        entities.push({ value: 'Jean Dupont', category: 'name', label: 'Nom complet' });
      }
      return entities;
    },
    buildRules(selections) {
      return selections.map((s, i) => ({ id: 'r' + i, original: s.value, replacement: '[MASQUE_' + i + ']', category: s.category }));
    },
    anonymize(text, rules) {
      let out = text;
      rules.forEach((r) => { out = out.split(r.original).join(r.replacement); });
      return out;
    }
  }
};

global.sessionStorage = { getItem() { return null; }, removeItem() {} };
global.CustomEvent = class { constructor(t, o) { this.type = t; this.detail = o && o.detail; } };
global.Event = class { constructor(t, o) { this.type = t; this.bubbles = o && o.bubbles; } };

const sourcePath = path.join(__dirname, '../../public/assets/tools/constructeur-prompts/prompt-anon-panel.js');
new Function(fs.readFileSync(sourcePath, 'utf8'))();
domReady();

// --- (1) Champ vide : message doux, rien à masquer, AUCUN panneau ouvert. ---
{
  fields.cpTaskObject.value = '';
  toasts.length = 0;
  let threw = null;
  try { fields.cpAnonToggle.click(); } catch (e) { threw = e; }

  assert(threw === null, 'champ vide : aucun clic ne lève d\'exception');
  assert(fields.cpAnonPanel.style.display === 'none', 'champ vide : le panneau partagé reste fermé (round 148 - ce bouton ne le pilote plus)');
  assert(fields.cpAnonRecap.style.display === 'none', 'champ vide : aucun récapitulatif ne s\'affiche (rien n\'a été fait)');
  const infos = toasts.filter((t) => t.detail && t.detail.variant === 'info');
  assert(infos.length === 1, 'champ vide : un message doux invite à écrire d\'abord');
}

// --- (2) Champ rempli SANS PII : récapitulatif honnête, pas de bouton Annuler. ---
{
  fields.cpTaskObject.value = 'Rédige un plan marketing pour le lancement d\'une application.';
  toasts.length = 0;
  fields.cpAnonToggle.click();

  assert(fields.cpAnonPanel.style.display === 'none', 'sans PII : le panneau partagé reste fermé');
  assert(fields.cpAnonRecap.style.display === '', 'sans PII : le récapitulatif s\'affiche quand même (honnêteté)');
  assert(
    fields.cpAnonRecapText.textContent === 'Aucune information personnelle trouvée dans votre texte. Vous pouvez continuer.',
    'sans PII : le récapitulatif dit clairement qu\'aucune info personnelle n\'a été détectée'
  );
  assert(fields.cpAnonUndo.style.display === 'none', 'sans PII : aucun bouton "Annuler" - rien n\'a été modifié');
  assert(
    fields.cpTaskObject.value === 'Rédige un plan marketing pour le lancement d\'une application.',
    'sans PII : le contenu du champ n\'a pas bougé'
  );
}

// --- (3) Champ rempli AVEC PII (courriel + nom) : masquage réel EN PLACE. ---
const texteOriginal = 'Contactez Jean Dupont à jean.dupont@exemple.com pour ce dossier.';
{
  fields.cpTaskObject.value = texteOriginal;
  toasts.length = 0;
  fields.cpAnonToggle.click();

  assert(
    fields.cpTaskObject.value.indexOf('jean.dupont@exemple.com') === -1 && fields.cpTaskObject.value.indexOf('Jean Dupont') === -1,
    'avec PII : les données personnelles d\'origine ont disparu du champ (masquage en place)'
  );
  assert(
    fields.cpTaskObject.value.indexOf('[MASQUE_') !== -1,
    'avec PII : le champ contient bien le texte masqué produit par AnonymizerCore.anonymize()'
  );
  assert(fields.cpAnonPanel.style.display === 'none', 'avec PII : le panneau Split ne s\'est JAMAIS ouvert (coeur de la refonte round 148)');
  assert(fields.cpAnonRecap.style.display === '', 'avec PII : le récapitulatif est affiché');
  assert(
    fields.cpAnonRecapText.textContent === '1 courriel et 1 nom complet ont été masqués.',
    'avec PII : le récapitulatif nomme les VRAIES catégories détectées, en français correct - obtenu : "' + fields.cpAnonRecapText.textContent + '"'
  );
  assert(fields.cpAnonUndo.style.display === '', 'avec PII : le bouton "Annuler le masquage" est bien visible');
  const succes = toasts.filter((t) => t.detail && t.detail.variant === 'success');
  assert(succes.length === 1, 'avec PII : un toast de succès confirme le masquage');
}

// --- (4) Annulation : restaure le texte d'origine, referme le récapitulatif. ---
{
  toasts.length = 0;
  fields.cpAnonUndo.click();

  assert(fields.cpTaskObject.value === texteOriginal, 'annulation : le texte d\'origine (avec les vraies infos) est restauré à l\'identique');
  assert(fields.cpAnonRecap.style.display === 'none', 'règle 6 : le récapitulatif disparaît après annulation');
  assert(fields.cpAnonUndo.style.display === 'none', 'règle 6 : le bouton "Annuler" se cache lui-même après usage');
  const infos = toasts.filter((t) => t.detail && t.detail.variant === 'info');
  assert(infos.length === 1, 'annulation : un message confirme la restauration');
}

// --- (5) Un nouveau masquage après annulation fonctionne normalement (bouton "redevient disponible"). ---
{
  fields.cpTaskObject.value = texteOriginal;
  toasts.length = 0;
  fields.cpAnonToggle.click();
  assert(
    fields.cpTaskObject.value.indexOf('jean.dupont@exemple.com') === -1,
    'règle 6 : #cpAnonToggle redevient pleinement fonctionnel après une annulation (jamais désactivé)'
  );
}

// --- (6) Récapitulatif PÉRIMÉ : si la personne efface son texte à la main après un masquage, le
// prochain clic sur #cpAnonToggle (champ vide) doit faire disparaître le récapitulatif/bouton
// Annuler laissés par le masquage précédent - ils décriraient un texte qui n'existe plus.
{
  // État de départ : un masquage vient de réussir (recap + undo visibles), simulé directement.
  fields.cpAnonRecap.style.display = '';
  fields.cpAnonRecapText.textContent = '1 courriel a été masqué.';
  fields.cpAnonUndo.style.display = '';

  fields.cpTaskObject.value = ''; // la personne a tout effacé à la main
  toasts.length = 0;
  fields.cpAnonToggle.click();

  assert(fields.cpAnonRecap.style.display === 'none', 'récapitulatif périmé : disparaît même sur la branche "champ vide" (pas seulement sur un nouveau masquage réussi)');
  assert(fields.cpAnonRecapText.textContent === '', 'récapitulatif périmé : le texte de l\'ancien récapitulatif est bien vidé');
  assert(fields.cpAnonUndo.style.display === 'none', 'récapitulatif périmé : le bouton "Annuler" périmé disparaît aussi');
}

console.log(pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
