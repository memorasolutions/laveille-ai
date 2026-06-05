// anonymizer-ui.js — contrôleur UI (vanilla, sans dépendance)
// Consomme window.AnonymizerCore + toasts du thème (CustomEvent 'toast-show'). Aucune popup native.

let rules = [];

function goStep(n) {
  document.querySelectorAll('.anon-step').forEach(btn => btn.classList.toggle('active', btn.dataset.step == n));
  document.querySelectorAll('.anon-panel').forEach(p => p.classList.toggle('active', p.dataset.stepContent == n));
}

function showToast(message, variant = 'info', duration = 3000) {
  window.dispatchEvent(new CustomEvent('toast-show', { detail: { message, variant, duration } }));
}

function saveRules() {
  try { localStorage.setItem('lv_anon_rules_v3', JSON.stringify(rules)); }
  catch (e) { console.warn('saveRules', e); }
}

function loadRules() {
  try { const s = localStorage.getItem('lv_anon_rules_v3'); if (s) rules = JSON.parse(s); }
  catch (e) { console.warn('loadRules', e); rules = []; }
}

function esc(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function renderMapping() {
  const container = document.getElementById('rulesMapping');
  if (!container) return;
  container.innerHTML = '';
  if (!rules.length) {
    container.innerHTML = '<p class="anon-empty">Aucune règle pour l\'instant.</p>';
    return;
  }
  rules.forEach((rule, idx) => {
    const div = document.createElement('div');
    div.className = 'anon-rule-item';
    div.innerHTML = '<span class="anon-rule-text">« ' + esc(rule.original) + ' » → « ' + esc(rule.replacement) + ' »</span>'
      + '<button type="button" class="anon-rule-remove" data-id="' + idx + '" aria-label="Retirer cette règle">✕</button>';
    container.appendChild(div);
  });
}

function dedupeRules(newRules) {
  const map = new Map();
  [...rules, ...newRules].forEach(r => map.set(r.original, r));
  return Array.from(map.values());
}

function reanonymize() {
  const source = document.getElementById('anonSource');
  const output = document.getElementById('anonOutput');
  if (source && output) output.value = window.AnonymizerCore.anonymize(source.value, rules);
}

document.addEventListener('DOMContentLoaded', () => {
  if (!window.AnonymizerCore) { console.error('AnonymizerCore manquant'); return; }
  loadRules();
  renderMapping();
  goStep(1);

  document.querySelectorAll('.anon-step').forEach(btn => {
    btn.addEventListener('click', () => goStep(btn.dataset.step));
  });

  let manualSelections = [];

  const btnAddManual = document.getElementById('btnAddManual');
  const manualRow = document.getElementById('manualRow');
  if (btnAddManual && manualRow) {
    btnAddManual.addEventListener('click', () => manualRow.classList.remove('hidden'));
  }

  const btnSaveManual = document.getElementById('btnSaveManual');
  if (btnSaveManual) {
    btnSaveManual.addEventListener('click', () => {
      const orig = document.getElementById('manualOriginal');
      const cat = document.getElementById('manualCategory');
      const original = orig && orig.value.trim();
      const category = cat && cat.value;
      if (original && category) {
        manualSelections.push({ value: original, category });
        showToast('Élément ajouté à anonymiser : « ' + original + ' »', 'success');
        if (orig) orig.value = '';
        if (manualRow) manualRow.classList.add('hidden');
      } else {
        showToast('Saisissez le texte à anonymiser.', 'warning');
      }
    });
  }

  const btnDetect = document.getElementById('btnDetect');
  if (btnDetect) {
    btnDetect.addEventListener('click', () => {
      const source = document.getElementById('anonSource');
      const results = document.getElementById('detectResults');
      if (!source || !results) return;
      const text = source.value;
      if (!text.trim()) { showToast('Collez d\'abord votre texte.', 'warning'); return; }
      const entities = window.AnonymizerCore.detectEntities(text);
      if (!entities.length) {
        results.innerHTML = '<p class="anon-empty">Aucune donnée sensible détectée automatiquement. Ajoutez-en manuellement.</p>';
        showToast('Aucune donnée sensible détectée.', 'info');
        return;
      }
      results.innerHTML = entities.map(ent =>
        '<label class="anon-detect-row"><input type="checkbox" checked data-value="' + esc(ent.value) + '" data-category="' + esc(ent.category) + '"> '
        + '<span class="anon-detect-cat">' + esc(ent.label) + '</span> : « ' + esc(ent.value) + ' »</label>'
      ).join('');
      showToast(entities.length + ' donnée(s) détectée(s).', 'success');
    });
  }

  const btnAnonymize = document.getElementById('btnAnonymize');
  if (btnAnonymize) {
    btnAnonymize.addEventListener('click', () => {
      const source = document.getElementById('anonSource');
      if (!source) return;
      const text = source.value;
      if (!text.trim()) { showToast('Collez d\'abord votre texte.', 'warning'); return; }
      const selections = [];
      document.querySelectorAll('#detectResults input[type="checkbox"]:checked').forEach(cb => {
        selections.push({ value: cb.dataset.value, category: cb.dataset.category });
      });
      selections.push(...manualSelections);
      if (!selections.length) { showToast('Sélectionnez au moins une donnée à anonymiser.', 'warning'); return; }
      const newRules = window.AnonymizerCore.buildRules(selections);
      rules = dedupeRules(newRules);
      saveRules();
      reanonymize();
      renderMapping();
      manualSelections = [];
      goStep(2);
      showToast('Texte anonymisé. Copiez-le pour votre IA.', 'success');
    });
  }

  const btnCopyAnon = document.getElementById('btnCopyAnon');
  if (btnCopyAnon) {
    btnCopyAnon.addEventListener('click', async () => {
      const output = document.getElementById('anonOutput');
      if (!output) return;
      try { await navigator.clipboard.writeText(output.value); showToast('Texte anonymisé copié.', 'success'); }
      catch (e) { showToast('Copie impossible — sélectionnez puis Ctrl+C.', 'danger'); }
    });
  }

  const rulesMapping = document.getElementById('rulesMapping');
  if (rulesMapping) {
    rulesMapping.addEventListener('click', (e) => {
      const btn = e.target.closest('.anon-rule-remove');
      if (!btn) return;
      const idx = parseInt(btn.dataset.id, 10);
      if (idx >= 0 && idx < rules.length) {
        rules.splice(idx, 1);
        saveRules();
        reanonymize();
        renderMapping();
      }
    });
  }

  const btnRestore = document.getElementById('btnRestore');
  if (btnRestore) {
    btnRestore.addEventListener('click', () => {
      const ai = document.getElementById('aiResponse');
      if (!ai) return;
      const text = ai.value;
      if (!text.trim()) { showToast('Collez la réponse de l\'IA.', 'warning'); return; }
      if (!rules.length) { showToast('Aucune règle : anonymisez d\'abord un texte (étape 1).', 'warning'); return; }
      const res = window.AnonymizerCore.restore(text, rules);
      const out = document.getElementById('restoredOutput');
      const report = document.getElementById('restoreReport');
      if (out) out.value = res.text;
      if (report) {
        const found = res.found.length, total = found + res.notFound.length;
        let msg = found + ' valeur(s) restaurée(s) sur ' + total + '.';
        if (res.notFound.length) msg += ' Non retrouvées (l\'IA les a peut-être reformulées) : ' + res.notFound.map(r => '« ' + r.original + ' »').join(', ');
        report.textContent = msg;
      }
      if (res.notFound.length) showToast(res.notFound.length + ' valeur(s) non retrouvée(s).', 'warning');
      else showToast('Toutes vos vraies données ont été restaurées.', 'success');
    });
  }

  const btnCopyRestored = document.getElementById('btnCopyRestored');
  if (btnCopyRestored) {
    btnCopyRestored.addEventListener('click', async () => {
      const out = document.getElementById('restoredOutput');
      if (!out) return;
      try { await navigator.clipboard.writeText(out.value); showToast('Texte restauré copié.', 'success'); }
      catch (e) { showToast('Copie impossible — sélectionnez puis Ctrl+C.', 'danger'); }
    });
  }
});
