// prompt-anon-panel.js — pont du panneau « Anonymiser » du constructeur de prompts.
// L'ÉDITEUR COMPLET (barre d'outils, bulle de sélection, surlignage, modes) est fourni par
// <x-tools::anonymizer-editor> + window.lvAnonUI (anonymizer-core/rich/ui.js, 100% local) —
// AUCUNE duplication. Ce script ne fait QUE :
//   1) le toggle du panneau (progressive disclosure),
//   2) l'insertion du texte anonymisé (lvAnonUI.anonPlain) dans le champ « Tâche »,
//   3) le handoff sessionStorage depuis l'anonymiseur (compat. ascendante).
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
    function insertIntoTask() {
      if (!taskField) return;
      const ui = window.lvAnonUI;
      const txt = ui && ui.anonPlain ? ui.anonPlain.trim() : '';
      if (txt === '') {
        showToast('Anonymisez d\'abord un texte (bouton « Détecter et anonymiser »).', 'warning');
        return;
      }
      taskField.value = txt;
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
  });
})();
