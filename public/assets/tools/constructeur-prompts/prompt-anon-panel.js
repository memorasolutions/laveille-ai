// prompt-anon-panel.js — panneau « Anonymiser » in-page du constructeur de prompts.
// Réutilise window.AnonymizerCore (moteur partagé, 100% local) + handoff sessionStorage
// depuis l'anonymiseur. Vanilla, autonome. Aucune donnée ne quitte le navigateur.
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('cpAnonToggle');
    const panel = document.getElementById('cpAnonPanel');
    const input = document.getElementById('cpAnonInput');
    const runBtn = document.getElementById('cpAnonRun');
    const output = document.getElementById('cpAnonOutput');
    const report = document.getElementById('cpAnonReport');
    const insertBtn = document.getElementById('cpAnonInsert');
    const taskField = document.getElementById('cpTaskObject');

    const showToast = (message, variant) => window.dispatchEvent(
      new CustomEvent('toast-show', { detail: { message, variant: variant || 'info', duration: 3000 } })
    );

    // HANDOFF : texte anonymisé importé de l'anonymiseur (sessionStorage, one-time, volatile).
    try {
      const handoffKey = 'lv_handoff_prompt_text';
      const handoffText = sessionStorage.getItem(handoffKey);
      if (handoffText && handoffText.trim() !== '' && taskField) {
        taskField.value = handoffText;
        taskField.dispatchEvent(new Event('input', { bubbles: true })); // met à jour Alpine x-model
        sessionStorage.removeItem(handoffKey);
        showToast('Texte anonymisé importé de l\'anonymiseur.', 'info');
      }
    } catch (e) { /* sessionStorage indisponible : on ignore */ }

    // Garde : moteur non chargé → panneau désactivé (le handoff ci-dessus reste fonctionnel).
    if (typeof window.AnonymizerCore === 'undefined') {
      if (runBtn) { runBtn.disabled = true; runBtn.textContent = '🕵️ Anonymiser (indisponible)'; }
      return;
    }

    // Toggle du panneau (progressive disclosure).
    if (toggleBtn && panel) {
      toggleBtn.addEventListener('click', function () {
        const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
        toggleBtn.setAttribute('aria-expanded', String(!expanded));
        panel.style.display = expanded ? 'none' : 'block';
        panel.setAttribute('aria-hidden', String(expanded));
        if (!expanded && input) input.focus();
      });
    }

    function runAnonymization() {
      if (!input || !output || !report) return;
      const txt = input.value.trim();
      if (txt === '') { showToast('Veuillez saisir un texte à anonymiser.', 'warning'); return; }
      try {
        const ents = window.AnonymizerCore.detectEntities(txt);
        const rules = window.AnonymizerCore.buildRules(
          ents.map(e => ({ value: e.value, category: e.category })), { mode: 'pseudo' }
        );
        output.value = window.AnonymizerCore.anonymize(txt, rules, []);
        report.textContent = ents.length > 0
          ? ents.length + ' donnée(s) masquée(s).'
          : 'Aucune donnée détectée — vous pouvez quand même insérer.';
      } catch (err) {
        report.textContent = 'Erreur lors de l\'anonymisation.';
        showToast('Échec de l\'anonymisation.', 'danger');
      }
    }
    if (runBtn) runBtn.addEventListener('click', runAnonymization);

    function insertIntoTask() {
      if (!output || !taskField) return;
      if (output.value.trim() === '') { runAnonymization(); if (output.value.trim() === '') return; }
      taskField.value = output.value;
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
