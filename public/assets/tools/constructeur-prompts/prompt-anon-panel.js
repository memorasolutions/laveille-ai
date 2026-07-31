// prompt-anon-panel.js — pont du panneau « Anonymiser » du constructeur de prompts.
// L'ÉDITEUR COMPLET (barre d'outils, bulle de sélection, surlignage, modes) est fourni par
// <x-tools::anonymizer-editor> + window.lvAnonUI (anonymizer-core/rich/ui.js, 100% local) —
// AUCUNE duplication. Ce script fait :
//   1) le MASQUAGE EN PLACE du champ principal #cpTaskObject (bouton #cpAnonToggle) : détecte les
//      infos perso via window.AnonymizerCore (detectEntities/buildRules/anonymize, DÉJÀ utilisés
//      ailleurs, aucune duplication du moteur) et REMPLACE directement le contenu du champ - il
//      n'ouvre plus jamais le panneau ni ne masque le champ (round 148, 2026-07-31, refonte
//      « anonymisation en place »). Voir maskTaskFieldInPlace() plus bas.
//   2) le toggle du panneau partagé (progressive disclosure), réservé désormais au garde-fou
//      proactif des AUTRES champs de texte libre du wizard (persona, audience, verbe, contraintes,
//      exemples, gabarits de carte),
//   3) l'insertion du texte anonymisé (lvAnonUI.anonPlain) dans le champ actif de CE panneau,
//   4) le handoff sessionStorage depuis l'anonymiseur (compat. ascendante),
//   5) le garde-fou proactif lui-même : si window.AnonymizerCore détecte des infos perso dans un
//      des champs de texte libre du wizard, un bandeau doux invite à les masquer d'abord (réutilise
//      l'anonymiseur déjà chargé, jamais d'erreur sinon).
// Auteur : MEMORA solutions, https://memora.solutions
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // i18n : window.promptBuilderConfig est défini en inline AVANT ce script deferred
    // (le bloc inline s'exécute au parsing, avant tout <script defer>) — voir Round 69.
    const i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
    const toggleBtn = document.getElementById('cpAnonToggle');
    const panel = document.getElementById('cpAnonPanel');
    const insertBtn = document.getElementById('cpAnonInsert');
    const taskField = document.getElementById('cpTaskObject');
    // Round 109 (2026-07-27, passe adversariale) : les 4 champs personnalisés ci-dessous
    // alimentent eux aussi le prompt final copié/partagé (get personaText/audienceText/prompt
    // dans constructeur-prompts-core.js) mais n'étaient surveillés par AUCUN garde-fou anti-PII.
    const personaCustomField = document.getElementById('cpPersonaCustom');
    const audienceCustomField = document.getElementById('cpAudienceCustom');
    const verbCustomField = document.getElementById('cpVerbCustom');
    const constraintCustomField = document.getElementById('cpConstraintCustom');
    // Round 125 (2026-07-30, passe adversariale) : 6e champ surveillé. Le textarea « Exemples »
    // (technique few-shot) n'avait aucun id - même défaut structurel que le round 119. Son
    // libellé invite pourtant explicitement à coller de vrais échanges (« Entrée : ... »), et son
    // contenu part verbatim dans le prompt final ET est persisté en base.
    const examplesField = document.getElementById('cpExamples');
    // Round 148 (2026-07-31) : éléments du récapitulatif de masquage EN PLACE du champ principal.
    const recapBox = document.getElementById('cpAnonRecap');
    const recapText = document.getElementById('cpAnonRecapText');
    const undoBtn = document.getElementById('cpAnonUndo');

    const showToast = (message, variant) => window.dispatchEvent(
      new CustomEvent('toast-show', { detail: { message, variant: variant || 'info', duration: 3000 } })
    );

    // HANDOFF : texte anonymisé importé de l'anonymiseur (sessionStorage, one-time, volatile).
    try {
      const handoffText = sessionStorage.getItem('lv_handoff_prompt_text');
      if (handoffText && handoffText.trim() !== '' && taskField) {
        taskField.value = handoffText;
        taskField.dispatchEvent(new Event('input', { bubbles: true })); // met à jour Alpine x-model
        sessionStorage.removeItem('lv_handoff_prompt_text');
        showToast(i18n.anonImported || 'Texte anonymisé importé de l\'anonymiseur.', 'info');
      }
    } catch (e) { /* sessionStorage indisponible : on ignore */ }

    // Round 145 (2026-07-30) : pré-remplissage FACTORISÉ. Il n'existait qu'en un exemplaire, dans
    // le chemin du bandeau anti-données-personnelles. L'autre porte d'entrée - le bouton « Masquer
    // mes infos personnelles d'abord » - ouvrait donc un panneau VIDE : la personne se retrouvait
    // avec deux zones de saisie pour une seule intention et écrivait son texte deux fois, ou dans
    // la mauvaise. Les deux portes partagent maintenant le même pré-remplissage.
    function prefillSourceFrom(field) {
      const src = document.getElementById('anonSource');
      if (!src || !field) return false;
      src.textContent = field.value;
      src.dispatchEvent(new Event('input', { bubbles: true }));
      return true;
    }

    // Toggle du panneau partagé (progressive disclosure) - RÉSERVÉ depuis le round 148
    // (2026-07-31) au garde-fou proactif des AUTRES champs (persona, audience, verbe, contraintes,
    // exemples, gabarits de carte). #cpAnonToggle ne l'appelle plus du tout : il agit directement
    // sur #cpTaskObject sans jamais ouvrir ce panneau (voir maskTaskFieldInPlace plus bas). Cette
    // fonction ne masque plus non plus #cpTaskField : décision tranchée « anonymisation en place »
    // (panel Perplexity/Gemini 95, Codex 82) - le champ principal reste TOUJOURS visible, quel que
    // soit le champ concerné par ce panneau.
    function basculerPanneau() {
      if (!panel) return;
      const expanded = panel.getAttribute('aria-hidden') === 'false';
      panel.style.display = expanded ? 'none' : 'block';
      panel.setAttribute('aria-hidden', String(expanded));
      if (!expanded) {
        const src = document.getElementById('anonSource');
        if (src) src.focus();
      }
    }

    // Round 109 : mémorise le champ qui a déclenché le garde-fou (null = usage manuel du
    // panneau, comportement d'origine inchangé -> insertIntoTask cible taskField par défaut).
    let activeField = null;

    // Round 140 (2026-07-30, passe adversariale) : ces trois déclarations vivaient à l'intérieur
    // du bloc try du garde-fou anti-PII (plus bas). insertIntoTask() est une fonction SOEUR de ce
    // bloc, pas une fonction imbriquée : `labelFor` y était donc hors de portée et l'appel de la
    // ligne « insertMsg = ... labelFor(targetField.id) » levait un ReferenceError non intercepté.
    // Conséquence observée : le texte anonymisé était bien écrit dans le champ (l'écriture a lieu
    // AVANT), mais tout ce qui suit était avorté - aucun toast, panneau qui reste ouvert, cible
    // jamais relâchée. L'utilisateur voyait un bouton « Insérer » sans aucun effet apparent.
    // Elles vivent maintenant dans la portée partagée du module : un seul jeu de libellés pour
    // les deux consommateurs (l'insertion et le bandeau d'avertissement), aucune duplication.
    const CARD_TEMPLATE_PREFIX = 'cpCardTemplate-';
    const fieldLabels = {
      cpTaskObject: 'Tâche',
      cpPersonaCustom: 'Rôle personnalisé',
      cpAudienceCustom: 'Audience personnalisée',
      cpVerbCustom: 'Verbe personnalisé',
      cpConstraintCustom: 'Contraintes personnalisées',
      cpExamples: i18n.anonFieldExamples || 'Exemples pour guider l\'IA'
    };
    const labelFor = (fieldId) => (typeof fieldId === 'string' && fieldId.indexOf(CARD_TEMPLATE_PREFIX) === 0)
      ? (i18n.anonFieldCardTemplate || 'Gabarit de requête de cette carte')
      : (fieldLabels[fieldId] || fieldId);

    // Round 141 (2026-07-30, passe adversariale) : POINT D'AFFECTATION UNIQUE de la cible.
    // Le libellé du bouton était figé à « Insérer dans la tâche », rendu une seule fois par Blade.
    // Le round 138 n'avait corrigé que le message affiché APRÈS le clic : la personne lisait donc
    // une promesse fausse AVANT de cliquer, alors que l'insertion partait dans le champ qui avait
    // déclenché le bandeau (Exemples, Rôle, Audience, Contraintes, gabarit de carte). Risque réel :
    // croire que le masquage a visé le mauvais champ et recopier l'info personnelle en clair dans
    // la Tâche, ce qui recréerait exactement la fuite que ce garde-fou existe pour empêcher.
    // Les trois affectations passent maintenant par ici : le libellé ne peut plus diverger.
    function setActiveField(field) {
      activeField = field || null;
      const labelEl = document.getElementById('cpAnonInsertLabel');
      if (!labelEl) return;
      labelEl.textContent = activeField
        ? (i18n.anonInsertInField || 'Insérer dans « %s »').replace('%s', labelFor(activeField.id))
        : (i18n.anonInsertInTask || 'Insérer dans la tâche');
    }

    // Insertion du texte anonymisé (produit par l'éditeur partagé) dans le champ actif.
    // AJOUTE à la suite (ne perd plus ce qui était déjà saisi). Round 109 : cible activeField
    // (le champ qui contenait la fuite détectée) au lieu de toujours écrire dans taskField -
    // sinon le texte original avec PII resterait intact dans son champ d'origine ET une copie
    // masquée serait dupliquée ailleurs : la fuite ne serait pas corrigée, juste dupliquée.
    // Round 142 (2026-07-30, passe adversariale) : la cible peut avoir été DÉMONTÉE entre le moment
    // où elle a été mémorisée et le clic sur « Insérer ». Le textarea d'un gabarit de carte vit dans
    // un <template x-if="editingCardPanelId === c.id"> : refermer le panneau de la carte, ou la
    // supprimer, détruit le noeud. Or `activeField` en gardait une RÉFÉRENCE DIRECTE : l'écriture
    // partait alors dans un noeud détaché du document. Le texte anonymisé disparaissait purement et
    // simplement, avec un message de succès affiché par-dessus - exactement le mensonge du round 140,
    // par un autre chemin.
    // On tente d'abord une résolution FRAÎCHE par identifiant. Si le champ n'existe vraiment plus,
    // on refuse l'insertion au lieu de la rediriger en silence vers la Tâche : réécrire ailleurs
    // dupliquerait la donnée personnelle au lieu de la corriger (défaut du round 138).
    function resolveTargetField() {
      if (!activeField) return taskField;
      if (activeField.isConnected) return activeField;
      return (activeField.id && document.getElementById(activeField.id)) || null;
    }

    function insertIntoTask() {
      const targetField = resolveTargetField();
      if (!targetField) {
        showToast(
          i18n.anonTargetGone || 'Ce champ n\'est plus affiché. Rouvrez-le, puis réessayez : rien n\'a été inséré.',
          'warning'
        );
        setActiveField(null);
        return;
      }
      const ui = window.lvAnonUI;
      const txt = ui && ui.anonPlain ? ui.anonPlain.trim() : '';
      if (txt === '') {
        showToast(i18n.anonNeedTextFirst || 'Anonymisez d\'abord un texte (bouton « Détecter et anonymiser »).', 'warning');
        return;
      }
      // Round 144 (2026-07-30, passe adversariale) : REMPLACER, pas ajouter, quand on vient du
      // bandeau anti-données-personnelles. Dans ce parcours, openAnonWithTask() a pré-rempli la
      // source avec le contenu ENTIER du champ ; ajouter à la suite laissait donc la donnée
      // personnelle intacte et collait une copie masquée en dessous. Le champ finissait avec le
      // vrai courriel ET sa version masquée, pendant que le message annonçait une anonymisation
      // réussie : exactement l'inverse de ce que l'outil promet.
      // `activeField` non nul = on vient de ce bandeau (resolveTargetField() ne renvoie taskField
      // que lorsqu'il est nul). On ne compare pas les noeuds : après un remontage Alpine, la cible
      // fraîche résolue par identifiant n'est plus le même objet que la référence mémorisée.
      if (activeField) {
        targetField.value = txt;
      } else {
        // AJOUT (append) : insertion générique dans la tâche - on préserve la saisie existante.
        const currentValue = targetField.value.trim();
        if (currentValue !== '') {
          targetField.value = currentValue + '\n' + txt;
        } else {
          targetField.value = txt;
        }
      }
      targetField.dispatchEvent(new Event('input', { bubbles: true })); // Alpine x-model
      // Round 147 (2026-07-30) : referme une fuite réelle. Le gabarit d'une carte peut avoir été
      // écrit en clair dans localStorage par un blur ANTÉRIEUR (avant tout masquage) -
      // commitCardPanelBlur -> persistCustomCards -> _saveLocalCustomCards pour les invités.
      // L'événement 'input' ci-dessus met x-model à jour (card.query_template = texte masqué)
      // mais ne déclenche PAS de blur : sans ce signal dédié, la copie locale pré-masquage
      // survivrait dans le navigateur jusqu'au prochain blur, ou indéfiniment si l'onglet se ferme
      // avant. On avise le composant Alpine (purgerCopieLocaleDesCartes(), câblé sur ce même
      // textarea via @cp-card-masked) pour réécrire immédiatement la copie locale avec l'état
      // courant, déjà masqué. Ne s'applique qu'aux gabarits de carte (seule surface confirmée
      // persistée en localStorage en clair) - jamais d'effet sur les autres champs surveillés.
      if (targetField.id && targetField.id.indexOf(CARD_TEMPLATE_PREFIX) === 0) {
        targetField.dispatchEvent(new CustomEvent('cp-card-masked'));
      }
      // Round 138 (2026-07-30) : le message NOMME le champ réellement visé. Il annonçait « la
      // tâche » quoi qu'il arrive, y compris quand l'insertion partait ailleurs.
      var insertMsg;
      if (targetField === taskField) {
        insertMsg = i18n.anonInserted || 'Texte anonymisé inséré dans la tâche.';
      } else {
        insertMsg = (i18n.anonInsertedInField || 'Texte anonymisé inséré dans « %s ».')
          .replace('%s', labelFor(targetField.id));
      }
      showToast(insertMsg, 'success');
      if (panel) {
        panel.style.display = 'none';
        panel.setAttribute('aria-hidden', 'true');
      }
      targetField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      targetField.focus();
      // Round 138 : on relâche la cible. activeField n'avait qu'UNE seule écriture dans tout le
      // fichier et n'était jamais remise à null : dès qu'un utilisateur avait emprunté une fois le
      // bandeau « Masquer mes infos » depuis un champ autre que la tâche (Exemples, Rôle, Audience,
      // Contraintes...), TOUTES ses insertions suivantes atterrissaient dans ce champ périmé, alors
      // que le bouton continuait d'afficher « Insérer dans la tâche ».
      setActiveField(null);
    }
    if (insertBtn) insertBtn.addEventListener('click', insertIntoTask);

    // ===== MASQUAGE EN PLACE (round 148, généralisé round 149, 2026-07-31) =====
    // Avant le round 148, cliquer #cpAnonToggle FAISAIT DISPARAÎTRE #cpTaskField et ouvrait le
    // panneau partagé en mode Split (« Votre texte » / « Texte anonymisé ») : la personne passait
    // d'une seule zone visible à deux pour une seule intention, et perdait de vue son champ.
    // Décision tranchée (recherche + panel Perplexity/Gemini 95/100, Codex 82/100, 2026-07-31) :
    // ANONYMISATION EN PLACE. Le round 148 ne l'avait câblée que sur #cpTaskObject - défaut #2
    // trouvé en passe adversariale (round 149) : les 5 AUTRES champs surveillés passaient encore
    // par l'ancien flux à 2 zones (panneau + « Insérer dans le champ »), sans récapitulatif ni
    // possibilité de revenir en arrière. maskFieldInPlace() ci-dessous est la fonction UNIQUE,
    // paramétrée par le champ, qui sert maintenant les 6 champs (voir son appel par #cpAnonToggle
    // plus bas, et par le bandeau anti-PII pour les 5 autres, tout en bas de ce fichier) - DRY
    // strict, aucune des 6 copies n'existe. Seuls les gabarits de carte personnalisée
    // (cpCardTemplate-*) restent volontairement sur l'ancien panneau : ce sont des champs créés/
    // détruits dynamiquement par Alpine (x-if dans x-for), hors du périmètre des 6 champs statiques
    // de cette unification, et un récapitulatif ancré sur #cpAnonRecap y serait fragile (le noeud
    // peut disparaître entre le masquage et le clic Annuler - même classe de défaut que le round
    // 142, déjà corrigé pour l'insertion via resolveTargetField()).

    // État du dernier masquage EN PLACE, par CHAMP (id) : texte d'origine ET texte masqué gardés
    // UNIQUEMENT en mémoire JS (jamais en stockage persistant, jamais dans le DOM). `masked` sert à
    // détecter si le champ a été modifié depuis le masquage (voir le handler d'Annuler plus bas -
    // défaut #1, round 149) : sans cette comparaison, un clic sur « Revenir à mon texte de départ »
    // réécrivait toujours la valeur MÉMORISÉE AVANT masquage, même si la personne avait complété ou
    // modifié son texte depuis - perte de données silencieuse et prouvée (texte ajouté après le
    // masquage disparaissait sans le moindre avertissement).
    const maskState = new Map(); // fieldId -> { previous: string, masked: string }
    // Champ actuellement décrit par le bloc récapitulatif UNIQUE (#cpAnonRecap) - permet au handler
    // d'Annuler de savoir quel champ restaurer sans dépendre d'une référence DOM qui pourrait être
    // périmée.
    let currentRecapFieldId = null;

    function hideRecap() {
      if (recapBox) recapBox.style.display = 'none';
      if (recapText) recapText.textContent = '';
      if (undoBtn) undoBtn.style.display = 'none';
      currentRecapFieldId = null;
    }

    // Écrit `newValue` dans `field` en préservant l'annuler/refaire NATIF du navigateur (Ctrl+Z).
    // Même pattern que anonymizer-ui.js (bindRichEditor, collage) : document.execCommand()
    // conservé VOLONTAIREMENT (déprécié mais seul mécanisme, en 2026, qui laisse le navigateur
    // gérer lui-même l'historique d'annulation d'un champ de formulaire - une écriture directe
    // sur .value casse cet historique. Repli sur .value si execCommand échoue ou n'est pas
    // supporté (navigateur minoritaire, environnement de test).
    function ecrireEnPreservantAnnuler(field, newValue) {
      field.focus();
      try { field.select(); } catch (e1) {
        try { field.setSelectionRange(0, field.value.length); } catch (e2) { /* repli plus bas */ }
      }
      let ok = false;
      try { ok = document.execCommand('insertText', false, newValue); } catch (e) { ok = false; }
      if (!ok || field.value !== newValue) {
        field.value = newValue;
      }
      field.dispatchEvent(new Event('input', { bubbles: true })); // met à jour Alpine x-model
    }

    // Avise le composant Alpine racine, s'il expose purgerCopieLocaleDesCartes() (referme la même
    // fuite que le round 147 : une copie locale pré-masquage pourrait autrement survivre dans le
    // navigateur). Silencieux si Alpine, le composant ou la méthode sont indisponibles.
    function purgerCopieLocaleSiPossible() {
      try {
        const root = taskField && taskField.closest ? taskField.closest('[x-data]') : null;
        if (!root || !window.Alpine || typeof window.Alpine.$data !== 'function') return;
        const composant = window.Alpine.$data(root);
        if (composant && typeof composant.purgerCopieLocaleDesCartes === 'function') {
          composant.purgerCopieLocaleDesCartes();
        }
      } catch (e) { /* purge optionnelle : aucune erreur ne doit remonter */ }
    }

    // Construit le récapitulatif humain (« 2 noms et 1 numéro de téléphone ont été masqués. »)
    // à partir des CATÉGORIES RÉELLES retournées par le moteur (entity.label), en français
    // correct (accord singulier/pluriel par catégorie ET accord du verbe final).
    // Round 149 (2026-07-31, défaut #3) : le verbe était figé au masculin (« une adresse a été
    // masqué ») même pour des catégories féminines (adresse, adresse IP, date). Le 3e élément de
    // chaque entrée d'anonPluralLabels (voir $anonPluralLabels côté blade) porte maintenant le
    // genre réel de la catégorie. Règle d'accord : une seule catégorie (répétée ou non) -> accord
    // du GENRE réel de cette catégorie ; plusieurs catégories DIFFÉRENTES jointes par « et » ->
    // masculin pluriel générique (règle grammaticale française standard pour un groupe mixte).
    function resumerMasquage(entities) {
      const pluralLabels = i18n.anonPluralLabels || {};
      const counts = new Map();
      const order = [];
      entities.forEach(function (ent) {
        const key = ent.label || ent.category || 'donnée personnelle';
        if (!counts.has(key)) { counts.set(key, 0); order.push(key); }
        counts.set(key, counts.get(key) + 1);
      });
      const parts = order.map(function (key) {
        const n = counts.get(key);
        const forms = pluralLabels[key];
        const mot = forms
          ? (n > 1 ? forms[1] : forms[0])
          : (n > 1 ? (key.toLowerCase() + 's') : key.toLowerCase());
        return n + ' ' + mot;
      });
      let joined = parts[0];
      if (parts.length > 1) {
        joined = parts.slice(0, -1).join(', ') + ' ' + (i18n.anonAnd || 'et') + ' ' + parts[parts.length - 1];
      }
      const singleCategory = order.length === 1;
      const singulier = singleCategory && entities.length === 1;
      const formsUniques = singleCategory ? pluralLabels[order[0]] : null;
      const feminin = !!(formsUniques && formsUniques[2]);
      let verbe;
      if (singulier) {
        verbe = feminin
          ? (i18n.anonMaskedSingularFeminine || 'a été masquée')
          : (i18n.anonMaskedSingular || 'a été masqué');
      } else if (singleCategory) {
        verbe = feminin
          ? (i18n.anonMaskedPluralFeminine || 'ont été masquées')
          : (i18n.anonMaskedPlural || 'ont été masqués');
      } else {
        verbe = i18n.anonMaskedPlural || 'ont été masqués';
      }
      return joined.charAt(0).toUpperCase() + joined.slice(1) + ' ' + verbe + '.';
    }

    // Round 149 (2026-07-31) : positionne le bloc récapitulatif UNIQUE (#cpAnonRecap) juste après
    // le champ concerné - même pattern DRY que le bandeau anti-PII plus bas (UN SEUL élément DOM
    // déplacé, jamais dupliqué 6 fois). Progressive enhancement : si le DOM ne fournit pas
    // parentNode (jamais le cas en navigateur réel - seulement dans un faux DOM de test), on
    // continue sans repositionner plutôt que de lever une exception.
    function positionRecapNear(field) {
      try {
        if (recapBox && field && field.parentNode && typeof field.parentNode.insertBefore === 'function') {
          field.parentNode.insertBefore(recapBox, field.nextSibling);
        }
      } catch (e) { /* positionnement optionnel : le récapitulatif reste fonctionnel sans */ }
    }

    // Fonction UNIQUE de masquage en place, paramétrée par le champ (round 149 - défaut #2 : DRY,
    // remplace les 6 copies qu'aurait exigées une extraction naïve). Appelée par #cpAnonToggle pour
    // #cpTaskObject, et par le bandeau anti-PII pour les 5 autres champs surveillés (plus bas).
    function maskFieldInPlace(field) {
      if (!field) return;
      const currentValue = field.value;

      // Efface tout récapitulatif/bouton Annuler PÉRIMÉ dès le clic, avant tout autre traitement -
      // si la personne a effacé son texte à la main après un masquage précédent, le récapitulatif
      // ne doit pas rester affiché pour un contenu qui n'existe plus.
      hideRecap();

      // Règle 1 : champ vide -> message doux, rien à masquer, aucun panneau.
      if (currentValue.trim() === '') {
        showToast(
          i18n.anonEmptyField || 'Écrivez d\'abord votre demande dans le champ ci-dessus, puis cliquez de nouveau sur ce bouton pour masquer vos informations personnelles.',
          'info'
        );
        return;
      }

      if (!window.AnonymizerCore) {
        showToast(i18n.anonUnavailable || 'Le masquage automatique n\'est pas disponible pour le moment.', 'warning');
        return;
      }

      let entities = [];
      try { entities = window.AnonymizerCore.detectEntities(currentValue) || []; } catch (e) { entities = []; }

      if (entities.length === 0) {
        if (recapBox && recapText) {
          positionRecapNear(field);
          recapText.textContent = i18n.anonNoneDetected || 'Aucune information personnelle trouvée dans votre texte. Vous pouvez continuer.';
          recapBox.style.display = '';
          currentRecapFieldId = field.id || null;
        }
        return;
      }

      let rules = [];
      try {
        rules = window.AnonymizerCore.buildRules(
          entities.map(function (e) { return { value: e.value, category: e.category }; })
        );
      } catch (e) { rules = []; }

      let masked = currentValue;
      try { masked = window.AnonymizerCore.anonymize(currentValue, rules) || currentValue; } catch (e) { masked = currentValue; }

      if (masked === currentValue) {
        // Entités détectées mais aucune substitution réelle (cas limite) : même honnêteté que
        // « rien détecté » plutôt qu'annoncer un masquage qui n'a rien changé.
        if (recapBox && recapText) {
          positionRecapNear(field);
          recapText.textContent = i18n.anonNoneDetected || 'Aucune information personnelle trouvée dans votre texte. Vous pouvez continuer.';
          recapBox.style.display = '';
          currentRecapFieldId = field.id || null;
        }
        return;
      }

      // Mémorise le texte d'origine ET le texte masqué qu'on s'apprête à écrire (règle 5 :
      // annulation en mémoire JS - `masked` sert à la comparaison anti-perte de données du handler
      // d'Annuler, plus bas).
      if (field.id) maskState.set(field.id, { previous: currentValue, masked: masked });
      ecrireEnPreservantAnnuler(field, masked);

      if (recapBox && recapText) {
        positionRecapNear(field);
        recapText.textContent = resumerMasquage(entities);
        recapBox.style.display = '';
        currentRecapFieldId = field.id || null;
      }
      if (undoBtn) undoBtn.style.display = '';

      // Règle 7 : purge la copie locale des gabarits de carte si le composant l'expose.
      purgerCopieLocaleSiPossible();

      showToast(i18n.anonMaskedInField || 'Vos informations personnelles ont été masquées, directement sur votre ordinateur.', 'success');
    }

    // Alias conservé pour lisibilité aux points d'appel : agit sur le champ Tâche.
    function maskTaskFieldInPlace() { maskFieldInPlace(taskField); }

    if (toggleBtn) toggleBtn.addEventListener('click', maskTaskFieldInPlace);

    // Règle 6 : après annulation, le récapitulatif disparaît et le bouton de masquage redevient
    // disponible (il n'a jamais été désactivé - un nouveau clic relance simplement une détection
    // sur le texte restauré).
    if (undoBtn) {
      undoBtn.addEventListener('click', function () {
        if (!currentRecapFieldId) return;
        const field = document.getElementById(currentRecapFieldId);
        const state = maskState.get(currentRecapFieldId);
        if (!field || !state) { hideRecap(); return; }

        const fieldIdAuMomentDuClic = currentRecapFieldId;
        function restaurer() {
          ecrireEnPreservantAnnuler(field, state.previous);
          maskState.delete(fieldIdAuMomentDuClic);
          hideRecap();
          showToast(i18n.anonUndone || 'Votre texte de départ est revenu, tel que vous l\'aviez écrit.', 'info');
        }

        // Round 149 (2026-07-31, défaut #1 - PERTE DE DONNÉES prouvée) : avant, ce handler
        // réécrivait toujours le champ avec la valeur MÉMORISÉE AVANT masquage, sans jamais la
        // comparer au contenu ACTUEL. Séquence prouvée : masquage -> la personne COMPLÈTE son
        // texte (ex. ajoute « Merci de traiter ce dossier avant vendredi. ») -> clic Annuler ->
        // l'ajout disparaissait silencieusement, sans le moindre avertissement.
        // On compare maintenant le contenu courant à ce qu'on a ÉCRIT au moment du masquage
        // (state.masked) : identique -> rien n'a changé depuis, restauration directe, comportement
        // inchangé pour le cas normal ; différent -> le champ a été modifié depuis le masquage, on
        // demande confirmation AVANT d'écraser quoi que ce soit (modale du thème
        // x-core::confirm-modal, montée une seule fois dans master.blade.php sous name="global" -
        // JAMAIS confirm() natif, interdit par la charte du projet).
        if (field.value === state.masked) {
          restaurer();
          return;
        }

        window.dispatchEvent(new CustomEvent('open-confirm-global', {
          detail: {
            message: i18n.anonUndoConfirm || 'Vous avez modifié ce texte depuis le masquage. Revenir en arrière effacera ce que vous avez écrit depuis. Continuer quand même ?',
            callback: restaurer
          }
        }));
      });
    }

    // GARDE-FOU PROACTIF : alerte douce si des infos perso sont détectées dans un des champs
    // surveillés. Silencieux si AnonymizerCore n'est pas chargé (ne crée jamais d'erreur).
    // Round 109 (2026-07-27, passe adversariale) : étendu de 1 champ (Tâche) à 5 champs
    // (Tâche + Rôle personnalisé + Audience personnalisée + Verbe personnalisé + Contraintes
    // personnalisées) - ces 4 derniers alimentaient déjà le prompt copié/partagé sans jamais
    // être scannés.
    const watchedFields = [taskField, personaCustomField, audienceCustomField, verbCustomField, constraintCustomField, examplesField].filter(Boolean);
    if (window.AnonymizerCore && watchedFields.length > 0) {
      try {
        // Libellés lisibles des champs (fieldLabels / labelFor) : déclarés dans la portée
        // partagée du module depuis le round 140 - ils servent AUSSI à insertIntoTask().

        // Round 119 (2026-07-27, passe adversariale) : le textarea « Gabarit de requête » des
        // cartes personnalisées n'avait AUCUN id, il était donc structurellement impossible au
        // garde-fou (qui résout ses champs par getElementById) de le surveiller. Or son contenu
        // est persisté en base par persistCustomCards() dès le blur, puis réinjecté
        // automatiquement dans de futurs prompts : c'est une source de PII RÉUTILISÉE, jamais
        // scannée - exactement la classe de manque corrigée au round 112 pour « Mon profil ».
        // Ces champs étant créés/détruits dynamiquement par Alpine (x-if dans x-for), on passe
        // par une écoute déléguée plutôt que par la liste statique watchedFields.
        const isCardTemplateField = (el) => !!el && typeof el.id === 'string' && el.id.indexOf(CARD_TEMPLATE_PREFIX) === 0;
        const isWatched = (el) => watchedFields.indexOf(el) !== -1 || isCardTemplateField(el);

        // Bandeau d'avertissement (charte ambre, dismissible) — UN SEUL élément DOM partagé,
        // repositionné dynamiquement juste avant le champ concerné (pas 5 bandeaux séparés).
        const warnBanner = document.createElement('div');
        warnBanner.setAttribute('role', 'alert');
        warnBanner.style.display = 'none';
        warnBanner.style.backgroundColor = '#FEF3C7';
        warnBanner.style.borderLeft = '3px solid #B7791F';
        warnBanner.style.borderRadius = '8px';
        warnBanner.style.padding = '.6rem .75rem';
        warnBanner.style.fontSize = '.82rem';
        warnBanner.style.color = '#5b4a1f';
        warnBanner.style.marginBottom = '.5rem';

        const textSpan = document.createElement('span');
        // Le contenu est mis à jour dynamiquement selon le champ concerné (voir checkEntities).

        const anonBtn = document.createElement('button');
        anonBtn.type = 'button';
        anonBtn.className = 'anon-btn';
        anonBtn.textContent = '🔒 ' + (i18n.anonMaskButton || 'Masquer mes infos →');
        anonBtn.style.marginTop = '.4rem';
        anonBtn.style.padding = '.35rem .7rem';
        anonBtn.style.fontSize = '.8rem';
        anonBtn.style.display = 'block';
        // A11y WCAG 2.2 AAA fix : ce bouton est hors de .anon-wrap donc --anon-primary est indéfini →
        // background transparent + color:#fff = 1.11:1 (échec AA). On fixe les couleurs en dur.
        // #5b4a1f sur #ffffff = 8.59:1 (AAA ✅) ; focus ring #9A2A06 sur #ffffff = 7.76:1 (AAA ✅).
        anonBtn.style.color = '#5b4a1f';
        anonBtn.style.backgroundColor = '#ffffff';
        anonBtn.style.border = '1px solid #B7791F';
        // Round 92 (2026-07-27, passe adversariale) : cible tactile WCAG 2.2 AAA SC 2.5.5 - ce
        // bouton créé dynamiquement n'avait ni min-height ni min-width (même défaut que dismissBtn
        // corrigé au round 90 ci-dessous).
        anonBtn.style.minHeight = '44px';
        anonBtn.style.minWidth = '44px';
        anonBtn.style.boxSizing = 'border-box';

        const dismissBtn = document.createElement('button');
        dismissBtn.type = 'button';
        dismissBtn.setAttribute('aria-label', i18n.close || 'Fermer');
        dismissBtn.innerHTML = '&times;';
        dismissBtn.style.border = 'none';
        dismissBtn.style.background = 'none';
        dismissBtn.style.color = '#5b4a1f';
        dismissBtn.style.fontSize = '1.1rem';
        dismissBtn.style.lineHeight = '1';
        dismissBtn.style.cursor = 'pointer';
        dismissBtn.style.float = 'right';
        // Round 90 (2026-07-27, passe adversariale) : cible tactile WCAG 2.2 AAA SC 2.5.5 -
        // ce bouton créé dynamiquement n'avait ni min-height ni min-width (≈18-22px effectif).
        dismissBtn.style.minWidth = '44px';
        dismissBtn.style.minHeight = '44px';
        dismissBtn.style.display = 'flex';
        dismissBtn.style.alignItems = 'center';
        dismissBtn.style.justifyContent = 'center';

        warnBanner.appendChild(dismissBtn);
        warnBanner.appendChild(textSpan);
        warnBanner.appendChild(anonBtn);

        // Round 109 : contenu ignoré PAR CHAMP (clé = id du champ), plus une seule variable
        // partagée - sinon fermer le bandeau pour un champ le fermerait aussi pour les autres.
        const dismissedFor = {};

        // Vérifie les entités PII dans UN champ spécifique (pas une concaténation de tous les
        // champs - on veut identifier précisément où est la fuite, pas juste « il y en a une »).
        function checkEntities(field) {
          try {
            const currentContent = field.value;
            const fieldId = field.id;
            const bannerShownForThisField = warnBanner.nextElementSibling === field;
            if (currentContent.trim() === '') {
              if (bannerShownForThisField) warnBanner.style.display = 'none';
              return;
            }
            // Ne réaffiche pas si l'utilisateur a fermé le bandeau pour CE contenu exact
            // DANS CE CHAMP (anti-harcèlement, par champ).
            if (currentContent === dismissedFor[fieldId]) {
              return;
            }
            const ents = window.AnonymizerCore.detectEntities(currentContent);
            if (ents && ents.length >= 1) {
              textSpan.textContent = '⚠️ ' + (i18n.anonPiiWarningField || 'On dirait qu\'il y a des infos personnelles dans « %s ». Pour ta sécurité, masque-les avant de copier ton prompt.').replace('%s', labelFor(fieldId));
              // Déplace le bandeau (élément unique) juste avant le champ concerné.
              if (field.parentNode) {
                field.parentNode.insertBefore(warnBanner, field);
              }
              warnBanner.style.display = '';
            } else if (bannerShownForThisField) {
              warnBanner.style.display = 'none';
            }
          } catch (e) { /* détection indisponible : on ignore */ }
        }

        // Fermeture : mémorise le contenu courant POUR LE CHAMP concerné par le bandeau
        // (le champ est le frère DOM suivant immédiat de warnBanner, puisqu'il y a été inséré
        // juste avant via insertBefore - ne jamais remonter par parentNode pour le retrouver,
        // ça pointerait sur un frère du conteneur, pas sur le champ lui-même).
        dismissBtn.addEventListener('click', function () {
          const currentField = warnBanner.nextElementSibling;
          if (currentField && currentField.id && isWatched(currentField)) {
            dismissedFor[currentField.id] = currentField.value;
          }
          warnBanner.style.display = 'none';
        });

        // « Masquer mes infos → » sur un GABARIT DE CARTE (cpCardTemplate-*) UNIQUEMENT depuis le
        // round 149 (2026-07-31) : ouvre le panneau partagé, pré-remplit la source avec le champ
        // concerné, lance la détection, et retient ce champ comme cible de l'insertion. Les 5
        // autres champs surveillés (Exemples, Rôle/Audience/Verbe/Contraintes personnalisés) sont
        // passés au masquage EN PLACE (voir handleMaskBannerClick plus bas) - ce parcours à 2 zones
        // reste réservé aux gabarits de carte, créés/détruits dynamiquement par Alpine, hors du
        // périmètre des 6 champs statiques unifiés (voir la justification au-dessus de
        // maskFieldInPlace()).
        function openAnonWithTask() {
          try {
            // (i) identifier le champ concerné (celui juste après le bandeau dans le DOM).
            const field = warnBanner.nextElementSibling;
            if (!field || !isWatched(field)) return;

            // (ii) ouvrir le panneau s'il est replié. Round 148 : la source de vérité de l'état
            // ouvert/fermé est maintenant le panneau lui-même (panel.style.display), plus
            // toggleBtn - #cpAnonToggle ne pilote plus ce panneau du tout depuis la refonte
            // « anonymisation en place » (il agit directement sur #cpTaskObject).
            if (panel && panel.style.display !== 'block') {
              basculerPanneau();
            }

            // (ii bis) Round 139 - défense en profondeur : la cible est mémorisée APRÈS l'ouverture.
            // Le paramètre d'intention suffit déjà, mais cet ordre rend le code correct même
            // si ce garde venait à sauter un jour. Les deux protections sont indépendantes.
            setActiveField(field); // mémorise le champ actif ET met le libellé du bouton à jour
            // (iii) pré-remplir #anonSource avec le contenu du champ concerné.
            // Round 145 : passe par la fonction partagée, plus par une copie locale - les deux
            // portes d'entrée du panneau doivent se comporter exactement pareil.
            prefillSourceFrom(field);
            // (iv) déclencher la détection (btnDetect = « Détecter seulement » ; repli sur btnDetectAnonAll)
            // Round 144 (2026-07-30, passe adversariale) : on privilégie « Détecter ET anonymiser ».
            // Le code déclenchait d'abord #btnDetect, qui se contente de SOULIGNER les données
            // repérées sans créer la moindre règle de masquage. Le volet « Texte anonymisé »
            // contenait donc encore le courriel en clair, et « Insérer » réinjectait la donnée
            // personnelle telle quelle dans le champ - sous un message annonçant « Texte anonymisé
            // inséré ». Le parcours guidé « Masquer mes infos → Insérer » ne masquait rien du tout.
            setTimeout(function () {
              let detectBtn = document.getElementById('btnDetectAnonAll');
              if (!detectBtn) detectBtn = document.getElementById('btnDetect');
              if (detectBtn) detectBtn.click();
            }, 150);
            // masquer le bandeau (l'utilisateur a pris en main l'anonymisation)
            warnBanner.style.display = 'none';
          } catch (e) { /* élément manquant : on ignore */ }
        }
        // Round 149 (2026-07-31, défaut #2) : point d'entrée UNIQUE du bandeau, qui branche vers le
        // bon mécanisme selon le champ concerné - masquage EN PLACE (uniforme avec #cpAnonToggle)
        // pour les 6 champs statiques, ancien panneau à 2 zones conservé pour les seuls gabarits de
        // carte (justification ci-dessus, à l'appel d'openAnonWithTask()).
        function handleMaskBannerClick() {
          try {
            const field = warnBanner.nextElementSibling;
            if (!field || !isWatched(field)) return;
            if (isCardTemplateField(field)) {
              openAnonWithTask();
              return;
            }
            warnBanner.style.display = 'none';
            maskFieldInPlace(field);
          } catch (e) { /* garde-fou optionnel : aucune erreur ne doit remonter */ }
        }
        anonBtn.addEventListener('click', handleMaskBannerClick);

        // Déclencheurs : input débounce ~600ms + blur immédiat, sur CHAQUE champ surveillé.
        watchedFields.forEach(function (field) {
          let debounceTimer = null;
          field.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { checkEntities(field); }, 600);
          });
          field.addEventListener('blur', function () { checkEntities(field); });
        });

        // Round 119 : écoute DÉLÉGUÉE pour le gabarit des cartes personnalisées. Ces textareas
        // sont montés/démontés par Alpine au fil des ouvertures de panneau : on ne peut pas leur
        // attacher un écouteur une fois pour toutes au chargement, contrairement aux 5 champs
        // fixes ci-dessus.
        // Round 141 (2026-07-30, passe adversariale) : UN minuteur PAR CARTE, pas un seul partagé.
        // Les 5 champs fixes ci-dessus reçoivent chacun leur propre `debounceTimer` grâce à la
        // fermeture du forEach ; l'écoute déléguée, elle, n'avait qu'une variable unique. Taper
        // dans une carte annulait donc le contrôle anti-PII en attente d'une AUTRE carte, dont le
        // contenu n'était jamais scanné. L'écoute « blur » rattrape le cas courant (taper puis
        // changer de champ), mais pas un glisser-déposer de texte entre deux cartes visibles.
        const cardDebounceTimers = new Map();
        document.addEventListener('input', function (e) {
          if (!isCardTemplateField(e.target)) return;
          const target = e.target;
          clearTimeout(cardDebounceTimers.get(target.id));
          cardDebounceTimers.set(target.id, setTimeout(function () {
            cardDebounceTimers.delete(target.id); // pas de fuite mémoire si la carte est détruite
            checkEntities(target);
          }, 600));
        });
        // Capture obligatoire (3e argument true) : 'blur' ne remonte pas dans l'arbre, un
        // écouteur délégué en phase de bouillonnement ne le verrait jamais.
        document.addEventListener('blur', function (e) {
          if (isCardTemplateField(e.target)) checkEntities(e.target);
        }, true);

      } catch (e) { /* garde-fou optionnel : aucune erreur ne doit remonter */ }
    }
  });
})();
