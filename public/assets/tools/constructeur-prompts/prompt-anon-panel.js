// prompt-anon-panel.js — pont du panneau « Anonymiser » du constructeur de prompts.
// L'ÉDITEUR COMPLET (barre d'outils, bulle de sélection, surlignage, modes) est fourni par
// <x-tools::anonymizer-editor> + window.lvAnonUI (anonymizer-core/rich/ui.js, 100% local) —
// AUCUNE duplication. Ce script ne fait QUE :
//   1) le toggle du panneau (progressive disclosure),
//   2) l'insertion du texte anonymisé (lvAnonUI.anonPlain) dans le champ « Tâche » (en AJOUT, pas en écrasement),
//   3) le handoff sessionStorage depuis l'anonymiseur (compat. ascendante),
//   4) un garde-fou proactif : si window.AnonymizerCore détecte des infos perso dans le champ « Tâche »,
//      un bandeau doux invite à les masquer d'abord (réutilise l'anonymiseur déjà chargé, jamais d'erreur sinon).
// Auteur : MEMORA solutions, https://memora.solutions
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('cpAnonToggle');
    const panel = document.getElementById('cpAnonPanel');
    const insertBtn = document.getElementById('cpAnonInsert');
    const taskField = document.getElementById('cpTaskObject');

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
        showToast('Texte anonymisé importé de l\'anonymiseur.', 'info');
      }
    } catch (e) { /* sessionStorage indisponible : on ignore */ }

    // Toggle du panneau (progressive disclosure).
    if (toggleBtn && panel) {
      toggleBtn.addEventListener('click', function () {
        const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
        toggleBtn.setAttribute('aria-expanded', String(!expanded));
        panel.style.display = expanded ? 'none' : 'block';
        panel.setAttribute('aria-hidden', String(expanded));
        if (!expanded) {
          const src = document.getElementById('anonSource');
          if (src) src.focus();
        }
      });
    }

    // Insertion du texte anonymisé (produit par l'éditeur partagé) dans le champ « Tâche ».
    // AJOUTE à la suite (ne perd plus ce qui était déjà saisi).
    function insertIntoTask() {
      if (!taskField) return;
      const ui = window.lvAnonUI;
      const txt = ui && ui.anonPlain ? ui.anonPlain.trim() : '';
      if (txt === '') {
        showToast('Anonymisez d\'abord un texte (bouton « Détecter et anonymiser »).', 'warning');
        return;
      }
      // AJOUT (append) : préserve la saisie existante, séparée par un saut de ligne.
      const currentValue = taskField.value.trim();
      if (currentValue !== '') {
        taskField.value = currentValue + '\n' + txt;
      } else {
        taskField.value = txt;
      }
      taskField.dispatchEvent(new Event('input', { bubbles: true })); // Alpine x-model
      showToast('Texte anonymisé inséré dans la tâche.', 'success');
      if (toggleBtn && panel) {
        toggleBtn.setAttribute('aria-expanded', 'false');
        panel.style.display = 'none';
        panel.setAttribute('aria-hidden', 'true');
      }
      taskField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      taskField.focus();
    }
    if (insertBtn) insertBtn.addEventListener('click', insertIntoTask);

    // GARDE-FOU PROACTIF : alerte douce si des infos perso sont détectées dans le champ « Tâche ».
    // Silencieux si AnonymizerCore n'est pas chargé (ne crée jamais d'erreur).
    if (window.AnonymizerCore && taskField) {
      try {
        // Bandeau d'avertissement (charte ambre, dismissible).
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
        textSpan.textContent = '⚠️ On dirait qu\'il y a des infos personnelles (un nom, un courriel, un numéro…). Pour ta sécurité, masque-les avant de copier ton prompt.';

        const anonBtn = document.createElement('button');
        anonBtn.type = 'button';
        anonBtn.className = 'anon-btn';
        anonBtn.textContent = '🔒 Masquer mes infos →';
        anonBtn.style.marginTop = '.4rem';
        anonBtn.style.padding = '.35rem .7rem';
        anonBtn.style.fontSize = '.8rem';
        anonBtn.style.display = 'block';

        const dismissBtn = document.createElement('button');
        dismissBtn.type = 'button';
        dismissBtn.setAttribute('aria-label', 'Fermer');
        dismissBtn.innerHTML = '&times;';
        dismissBtn.style.border = 'none';
        dismissBtn.style.background = 'none';
        dismissBtn.style.color = '#5b4a1f';
        dismissBtn.style.fontSize = '1.1rem';
        dismissBtn.style.lineHeight = '1';
        dismissBtn.style.cursor = 'pointer';
        dismissBtn.style.float = 'right';

        warnBanner.appendChild(dismissBtn);
        warnBanner.appendChild(textSpan);
        warnBanner.appendChild(anonBtn);

        // Insère le bandeau juste avant le champ « Tâche ».
        if (taskField.parentNode) {
          taskField.parentNode.insertBefore(warnBanner, taskField);
        }

        let dismissedFor = '';   // contenu pour lequel l'utilisateur a fermé le bandeau (anti-harcèlement)
        let debounceTimer = null;

        function checkEntities() {
          try {
            const currentContent = taskField.value;
            if (currentContent.trim() === '') {
              warnBanner.style.display = 'none';
              return;
            }
            // Ne réaffiche pas si l'utilisateur a fermé le bandeau pour CE contenu exact.
            if (currentContent === dismissedFor) {
              return;
            }
            const ents = window.AnonymizerCore.detectEntities(currentContent);
            if (ents && ents.length >= 1) {
              warnBanner.style.display = '';
            } else {
              warnBanner.style.display = 'none';
            }
          } catch (e) { /* détection indisponible : on ignore */ }
        }

        // Fermeture : mémorise le contenu courant pour ne pas harceler tant qu'il ne change pas.
        dismissBtn.addEventListener('click', function () {
          dismissedFor = taskField.value;
          warnBanner.style.display = 'none';
        });

        // « Masquer mes infos → » : ouvre le panneau, pré-remplit la source, lance la détection.
        function openAnonWithTask() {
          try {
            // (i) ouvrir le panneau s'il est replié
            if (toggleBtn && toggleBtn.getAttribute('aria-expanded') !== 'true') {
              toggleBtn.click();
            }
            // (ii) pré-remplir #anonSource (contenteditable) avec le contenu actuel de la tâche
            const src = document.getElementById('anonSource');
            if (src) {
              src.textContent = taskField.value;
              src.dispatchEvent(new Event('input', { bubbles: true }));
            }
            // (iii) déclencher la détection (btnDetect = « Détecter seulement » ; repli sur btnDetectAnonAll)
            setTimeout(function () {
              let detectBtn = document.getElementById('btnDetect');
              if (!detectBtn) detectBtn = document.getElementById('btnDetectAnonAll');
              if (detectBtn) detectBtn.click();
            }, 150);
            // masquer le bandeau (l'utilisateur a pris en main l'anonymisation)
            warnBanner.style.display = 'none';
          } catch (e) { /* élément manquant : on ignore */ }
        }
        anonBtn.addEventListener('click', openAnonWithTask);

        // Déclencheurs : input débounce ~600ms + blur immédiat.
        taskField.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(checkEntities, 600);
        });
        taskField.addEventListener('blur', function () { checkEntities(); });

      } catch (e) { /* garde-fou optionnel : aucune erreur ne doit remonter */ }
    }
  });
})();
