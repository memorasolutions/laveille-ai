// prompt-anon-panel.js — pont du panneau « Anonymiser » du constructeur de prompts.
// L'ÉDITEUR COMPLET (barre d'outils, bulle de sélection, surlignage, modes) est fourni par
// <x-tools::anonymizer-editor> + window.lvAnonUI (anonymizer-core/rich/ui.js, 100% local) —
// AUCUNE duplication. Ce script ne fait QUE :
//   1) le toggle du panneau (progressive disclosure),
//   2) l'insertion du texte anonymisé (lvAnonUI.anonPlain) dans le champ actif (en AJOUT, pas en écrasement),
//   3) le handoff sessionStorage depuis l'anonymiseur (compat. ascendante),
//   4) un garde-fou proactif : si window.AnonymizerCore détecte des infos perso dans un des champs
//      de texte libre du wizard, un bandeau doux invite à les masquer d'abord (réutilise
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

    // Toggle du panneau (progressive disclosure).
    //
    // Round 146 : la logique vit dans une fonction qui reçoit l'INTENTION en paramètre. Avant, elle
    // vivait dans le handler de clic, et les deux portes d'entrée se distinguaient par un signal
    // implicite (la provenance de l'événement, puis un drapeau de module). Ces approches faisaient dépendre
    // une règle métier de l'environnement d'exécution plutôt que de l'appelant : fragile face à
    // tout traitement différé, et invérifiable en test automatisé. Ici, chaque appelant DÉCLARE ce
    // qu'il fait, et `.click()` cesse d'être une API interne.
    function basculerPanneau(ouvertureManuelle) {
      if (!toggleBtn || !panel) return;
      const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
      toggleBtn.setAttribute('aria-expanded', String(!expanded));
      panel.style.display = expanded ? 'none' : 'block';
      panel.setAttribute('aria-hidden', String(expanded));

      // Round 145 : UNE SEULE surface d'écriture visible à la fois. Le champ principal s'efface
      // pendant que le panneau travaille, et revient à la fermeture. Le masquage s'applique aux
      // DEUX portes d'entrée, y compris à l'ouverture déclenchée par le bandeau d'alerte.
      const taskBlock = document.getElementById('cpTaskField');
      if (taskBlock) taskBlock.style.display = expanded ? '' : 'none';

      if (!expanded) {
        const src = document.getElementById('anonSource');
        if (src) src.focus();
        // Round 138/139 : une ouverture MANUELLE n'est rattachée à aucun champ, la cible repart
        // donc sur la tâche. Ce reset ne doit SURTOUT PAS s'appliquer à une ouverture programmatique :
        // openAnonWithTask() mémorise le champ qui a déclenché le garde-fou, et l'écraser ferait
        // atterrir le texte masqué dans la Tâche en laissant la donnée personnelle en place dans le
        // champ qui avait pourtant déclenché l'alerte - le garde-fou recopierait la fuite.
        if (ouvertureManuelle) {
          // Round 145 : si la demande est déjà écrite, on la recopie dans la zone de travail et on
          // vise ce champ - donc l'insertion REMPLACERA au lieu d'ajouter à la suite. Sans ce
          // second point, pré-remplir laisserait le texte original ET sa copie masquée dans le
          // même champ : la duplication de données personnelles corrigée au round 144, recréée
          // par l'autre porte.
          if (taskField && taskField.value.trim() !== '') {
            prefillSourceFrom(taskField);
            setActiveField(taskField);
          } else {
            setActiveField(null);
          }
        }
      }
    }

    if (toggleBtn && panel) {
      toggleBtn.addEventListener('click', function () { basculerPanneau(true); });
    }

    // Round 145 : sortie explicite vers le champ principal. Refermer le panneau le réaffiche, et le
    // travail de masquage déjà fait n'est pas perdu (les règles vivent dans l'éditeur, pas ici).
    const backToTaskBtn = document.getElementById('cpAnonBackToTask');
    if (backToTaskBtn) {
      backToTaskBtn.addEventListener('click', function () {
        basculerPanneau(true);
      });
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
      if (toggleBtn && panel) {
        toggleBtn.setAttribute('aria-expanded', 'false');
        panel.style.display = 'none';
        panel.setAttribute('aria-hidden', 'true');
        // Round 145 : cette fermeture court-circuite le handler du toggle, donc elle doit
        // RÉAFFICHER le champ principal elle-même. Sans cette ligne, insérer faisait disparaître
        // le champ : le texte masqué arrivait dans une zone devenue invisible.
        const taskBlockAfterInsert = document.getElementById('cpTaskField');
        if (taskBlockAfterInsert) taskBlockAfterInsert.style.display = '';
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

        // « Masquer mes infos → » : ouvre le panneau, pré-remplit la source avec le champ
        // concerné, lance la détection, et retient ce champ comme cible de l'insertion.
        function openAnonWithTask() {
          try {
            // (i) identifier le champ concerné (celui juste après le bandeau dans le DOM).
            const field = warnBanner.nextElementSibling;
            if (!field || !isWatched(field)) return;

            // (ii) ouvrir le panneau s'il est replié
            if (toggleBtn && toggleBtn.getAttribute('aria-expanded') !== 'true') {
              // Round 146 : appel DIRECT en déclarant l'intention (false = pas un geste manuel),
              // au lieu de simuler un clic. Plus de signal implicite à interpréter : la cible
              // mémorisée juste après ne peut plus être écrasée par le handler.
              basculerPanneau(false);
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
        anonBtn.addEventListener('click', openAnonWithTask);

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
