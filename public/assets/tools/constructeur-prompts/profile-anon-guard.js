'use strict';

/**
 * Garde-fou anti-PII pour le formulaire de profil du constructeur de prompts
 * Auteur : MEMORA solutions, https://memora.solutions
 * Round 112 (2026-07-27, passe adversariale)
 *
 * Contexte : la page /user/prompts ("Mes prompts") permet la saisie libre de données
 * personnelles dans le panneau "Mon profil" (rôle, style, contraintes) sans aucun
 * avertissement - alors que ces 3 valeurs sont réutilisées automatiquement pour
 * pré-remplir les futurs prompts (constructeur-prompts-core.js, branche "Mon profil").
 * La saga PII des rounds 109-111 protège les champs DU WIZARD, mais jamais la SOURCE
 * de ces valeurs (ce formulaire). Ce script ajoute un garde-fou simple et dégradable,
 * inspiré de prompt-anon-panel.js mais simplifié :
 * - pas d'éditeur riche ni de bouton "Masquer mes infos" (absent de cette page) ;
 * - un seul bandeau partagé, repositionné dynamiquement ;
 * - mémorisation par champ du contenu ignoré (anti-harcèlement, comme round 109).
 */

(function () {
  if (!window.AnonymizerCore) {
    return;
  }

  // Round 114 (2026-07-27, passe adversariale) : ces textes étaient codés en dur en français -
  // la page /user/prompts a un vrai switch de langue (SetLocale + lang/en.json), donc tout se
  // traduisait SAUF ce bandeau. On lit maintenant l'i18n injectée par le Blade (même pattern que
  // le script jumeau prompt-anon-panel.js, qui lit window.promptBuilderConfig.i18n), avec repli
  // français si l'injection manque (le garde-fou ne doit jamais casser à cause de l'i18n).
  var i18n = window.promptProfileGuardI18n || {};

  var FIELD_LABELS = {
    'profile_role': i18n.fieldRole || 'Métier / rôle habituel',
    'profile_style': i18n.fieldStyle || 'Style d\'écriture préféré',
    'profile_constraints': i18n.fieldConstraints || 'Contraintes récurrentes'
  };

  var IGNORED_CONTENTS = {
    profile_role: '',
    profile_style: '',
    profile_constraints: ''
  };

  var currentBanner = null;
  var currentTargetFieldId = null;

  function createBanner(fieldId) {
    var banner = document.createElement('div');
    banner.setAttribute('role', 'alert');
    banner.style.backgroundColor = '#FEF3C7';
    banner.style.borderColor = '#B7791F';
    banner.style.color = '#5b4a1f';
    banner.style.border = '1px solid';
    banner.style.borderRadius = '0.5rem';
    banner.style.padding = '0.75rem 1rem';
    banner.style.marginBottom = '0.75rem';
    banner.style.display = 'flex';
    banner.style.alignItems = 'flex-start';
    banner.style.gap = '0.75rem';
    banner.style.fontSize = '0.875rem';
    banner.style.lineHeight = '1.25rem';

    var icon = document.createElement('span');
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '⚠️';
    icon.style.flexShrink = '0';

    var message = document.createElement('span');
    var fieldName = FIELD_LABELS[fieldId];
    // Round 114 : message traduisible (%s = nom du champ concerné), repli FR si i18n absente.
    message.textContent = (i18n.warning || 'On dirait qu\'il y a des infos personnelles dans « %s ». Retire-les avant d\'enregistrer ton profil - elles seront réutilisées automatiquement dans tes futurs prompts.').replace('%s', fieldName);

    var closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', i18n.close || 'Fermer l\'avertissement');
    closeButton.style.background = 'none';
    closeButton.style.border = 'none';
    closeButton.style.color = 'inherit';
    closeButton.style.fontSize = '1.25rem';
    closeButton.style.cursor = 'pointer';
    closeButton.style.padding = '0.25rem';
    closeButton.style.minWidth = '44px';
    closeButton.style.minHeight = '44px';
    closeButton.style.display = 'flex';
    closeButton.style.alignItems = 'center';
    closeButton.style.justifyContent = 'center';
    closeButton.textContent = '×';

    closeButton.addEventListener('click', function () {
      var el = document.getElementById(fieldId);
      IGNORED_CONTENTS[fieldId] = el ? el.value : '';
      banner.remove();
      currentBanner = null;
      currentTargetFieldId = null;
    });

    banner.appendChild(icon);
    banner.appendChild(message);
    banner.appendChild(closeButton);

    return banner;
  }

  function showBanner(fieldId) {
    if (currentTargetFieldId === fieldId && currentBanner) {
      return;
    }

    if (currentBanner) {
      currentBanner.remove();
    }

    var fieldElement = document.getElementById(fieldId);
    if (!fieldElement) return;

    var banner = createBanner(fieldId);
    fieldElement.parentNode.insertBefore(banner, fieldElement);
    currentBanner = banner;
    currentTargetFieldId = fieldId;

    // Round 115 (2026-07-27, passe adversariale) : le panneau « Mon profil » démarre REPLIÉ dès
    // qu'un profil existe déjà (x-data="{ open: false }") - c'est-à-dire exactement le cas où le
    // scan au chargement (round 113) a du contenu à analyser. La bannière était alors insérée
    // dans un conteneur display:none : invisible à l'oeil ET absente de l'arbre d'accessibilité
    // (un role="alert" sous display:none n'est jamais annoncé). On signale donc l'alerte à
    // l'accordéon pour qu'il se déplie, sinon l'avertissement ne serait jamais vu.
    window.dispatchEvent(new CustomEvent('profile-pii-detected'));
  }

  function hideBanner() {
    if (currentBanner) {
      currentBanner.remove();
      currentBanner = null;
      currentTargetFieldId = null;
    }
  }

  function checkField(fieldId) {
    var field = document.getElementById(fieldId);
    if (!field) return;

    var currentValue = field.value;
    if (currentValue === IGNORED_CONTENTS[fieldId]) {
      return;
    }

    var ents = window.AnonymizerCore.detectEntities(currentValue);
    if (ents && ents.length >= 1) {
      showBanner(fieldId);
    } else if (currentTargetFieldId === fieldId) {
      hideBanner();
    }
  }

  // IMPORTANT : le debounce ignore volontairement l'argument natif (Event) transmis
  // par 'input' - il ferme uniquement sur fieldId (chaîne connue à l'inscription du
  // champ), jamais sur l'événement lui-même, sinon checkField() recevrait un objet
  // Event au lieu d'un id de champ et échouerait silencieusement (document.getElementById
  // coercerait l'Event en chaîne "[object InputEvent]", introuvable, aucune détection).
  function setupField(fieldId) {
    var field = document.getElementById(fieldId);
    if (!field) return;

    var timer = null;

    field.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () { checkField(fieldId); }, 600);
    });
    field.addEventListener('blur', function () {
      checkField(fieldId);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var fieldIds = Object.keys(FIELD_LABELS);
    for (var i = 0; i < fieldIds.length; i++) {
      setupField(fieldIds[i]);
      // Round 113 (2026-07-27, passe adversariale) : setupField() n'écoute que les
      // interactions futures ('input'/'blur') - une valeur DÉJÀ en base (x-model Alpine,
      // assignation programmatique au chargement, jamais un événement 'input' natif) restait
      // invisible au garde-fou tant que l'utilisateur ne retouchait pas activement le champ.
      checkField(fieldIds[i]);
    }
  });
})();
