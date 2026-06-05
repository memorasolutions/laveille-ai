// anonymizer-ui.js — éditeur annoté inline (souligné = à anonymiser, surligné = anonymisé)
// Vanilla, sans dépendance. Consomme window.AnonymizerCore. Aucune popup native.

const _norm = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

class AnonymizerUI {
  constructor() {
    this.sourceText = '';
    this.candidates = [];
    this.rules = [];
    this.anonMode = 'pseudo';
    this.lastSelection = '';
    this.init();
  }

  init() {
    document.addEventListener('DOMContentLoaded', () => {
      if (!window.AnonymizerCore) { console.error('AnonymizerCore manquant'); return; }
      this.loadRules();
      this.anonMode = localStorage.getItem('lv_anon_mode') || 'pseudo';
      this.setMode('edit');
      this.updateOutput();
      this.goStep(1);
      this.bindEvents();
      this.bindActionMenu();
      this.bindSelectionBubble();
      this.bindSelectionBubbleCustom();
      this.bindAutoGrow();
      this.updateModeUI();
    });
  }

  escHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
  }

  saveRules() {
    try { localStorage.setItem('lv_anon_rules_v3', JSON.stringify(this.rules)); }
    catch (e) { console.warn('save', e); }
  }

  loadRules() {
    try { const s = localStorage.getItem('lv_anon_rules_v3'); if (s) this.rules = JSON.parse(s); }
    catch (e) { console.warn('load', e); this.rules = []; }
  }

  updateOutput() {
    const output = document.getElementById('anonOutput');
    if (output) { output.value = window.AnonymizerCore.anonymize(this.sourceText, this.rules); this.autoGrow(output); }
  }

  renderAnnotated() {
    const container = document.getElementById('anonAnnotated');
    if (!container) return;
    if (!this.sourceText.trim()) {
      container.innerHTML = '<span class="anon-placeholder">Saisissez ou collez du texte, puis « Détecter ».</span>';
      return;
    }

    const marks = [];
    const ruleSet = new Set();
    for (const rule of this.rules) {
      const norm = _norm(rule.original);
      ruleSet.add(norm);
      marks.push({ value: rule.original, category: rule.category, cls: 'anon-anon', priority: 1 });
    }
    for (const cand of this.candidates) {
      if (!ruleSet.has(_norm(cand.value))) {
        marks.push({ value: cand.value, category: cand.category, cls: 'anon-cand', priority: 0 });
      }
    }

    const intervals = [];
    for (const mark of marks) {
      const regex = window.AnonymizerCore.buildAccentInsensitiveBoundedRegex(mark.value);
      let match;
      while ((match = regex.exec(this.sourceText)) !== null) {
        intervals.push({ start: match.index, end: match.index + match[0].length, cls: mark.cls, value: mark.value, category: mark.category, priority: mark.priority, length: match[0].length });
        if (match[0].length === 0) { regex.lastIndex++; }
      }
    }

    intervals.sort((a, b) => {
      if (a.start !== b.start) return a.start - b.start;
      if (a.priority !== b.priority) return b.priority - a.priority;
      return b.length - a.length;
    });

    const selected = [];
    let lastEnd = 0;
    for (const iv of intervals) {
      if (iv.start >= lastEnd) { selected.push(iv); lastEnd = iv.end; }
    }

    let html = '', pos = 0;
    for (const iv of selected) {
      if (iv.start > pos) html += this.escHtml(this.sourceText.substring(pos, iv.start));
      const spanText = this.escHtml(this.sourceText.substring(iv.start, iv.end));
      const label = iv.cls === 'anon-anon' ? 'Anonymisé — cliquer pour annuler' : 'À anonymiser — cliquer pour anonymiser';
      html += `<span class="${iv.cls}" data-value="${this.escHtml(iv.value)}" data-category="${this.escHtml(iv.category)}" tabindex="0" role="button" aria-label="${label}">${spanText}</span>`;
      pos = iv.end;
    }
    if (pos < this.sourceText.length) html += this.escHtml(this.sourceText.substring(pos));
    container.innerHTML = html;
  }

  setMode(mode) {
    const wrap = document.getElementById('anonEditorWrap');
    const source = document.getElementById('anonSource');
    const btnEdit = document.getElementById('btnEditText');
    if (mode === 'edit') {
      if (wrap) { wrap.classList.remove('mode-annotate'); wrap.classList.add('mode-edit'); }
      if (source) { source.value = this.sourceText; this.autoGrow(source); }
      if (btnEdit) btnEdit.textContent = '👁️ Voir les annotations';
    } else {
      this.sourceText = (source && source.value) || this.sourceText;
      if (wrap) { wrap.classList.remove('mode-edit'); wrap.classList.add('mode-annotate'); }
      this.renderAnnotated();
      if (btnEdit) btnEdit.textContent = '✏️ Modifier le texte';
    }
  }

  detect() {
    const source = document.getElementById('anonSource');
    this.sourceText = (source && source.value) || this.sourceText;
    if (!this.sourceText.trim()) { this.toast('Collez d\'abord votre texte.', 'warning'); return; }
    const entities = window.AnonymizerCore.detectEntities(this.sourceText);
    const existing = new Set(this.rules.map(r => _norm(r.original)));
    this.candidates = entities.filter(ent => !existing.has(_norm(ent.value)));
    this.setMode('annotate');
    if (this.candidates.length) this.toast(this.candidates.length + ' donnée(s) repérée(s) — cliquez pour anonymiser.', 'info');
    else this.toast('Aucune donnée repérée automatiquement. Sélectionnez un passage à anonymiser.', 'info');
  }

  anonymizeValue(value, category) {
    const newRules = window.AnonymizerCore.buildRules([{ value, category }], { mode: this.anonMode, existing: this.rules });
    const normNew = new Set(newRules.map(r => _norm(r.original)));
    this.rules = [...this.rules.filter(r => !normNew.has(_norm(r.original))), ...newRules];
    const nv = _norm(value);
    this.candidates = this.candidates.filter(c => _norm(c.value) !== nv);
    this.saveRules();
    this.renderAnnotated();
    this.updateOutput();
  }

  deanonymizeValue(value) {
    const nv = _norm(value);
    const parts = new Set(value.split(/\s+/).map(_norm).filter(Boolean));
    this.rules = this.rules.filter(rule => {
      const nr = _norm(rule.original);
      if (nr === nv) return false;
      if ((rule.category === 'firstName' || rule.category === 'lastName') && parts.has(nr)) return false;
      return true;
    });
    this.candidates.push({ value, category: this.guessCategory(value) });
    this.saveRules();
    this.renderAnnotated();
    this.updateOutput();
  }

  guessCategory(text) {
    // Réutilise le moteur de détection sur le passage sélectionné → bonne catégorie (nom, date, RAMQ, courriel…)
    const t = (text || '').trim();
    const ents = window.AnonymizerCore.detectEntities(t) || [];
    if (ents.length) return ents.sort((a, b) => b.value.length - a.value.length)[0].category;
    if (/\d/.test(t)) return 'id';
    return /^[A-ZÀ-Ÿ]/.test(t) ? 'name' : 'other';
  }

  toggleAnonMode() {
    this.anonMode = this.anonMode === 'tokens' ? 'pseudo' : 'tokens';
    localStorage.setItem('lv_anon_mode', this.anonMode);
    const mainRules = this.rules
      .filter(r => r.category !== 'firstName' && r.category !== 'lastName')
      .map(r => ({ value: r.original, category: r.category }));
    this.rules = [];
    for (const m of mainRules) {
      const newRules = window.AnonymizerCore.buildRules([m], { mode: this.anonMode, existing: this.rules });
      this.rules = this.rules.concat(newRules);
    }
    this.saveRules();
    this.renderAnnotated();
    this.updateOutput();
    this.updateModeUI();
    this.toast(this.anonMode === 'tokens' ? 'Mode jetons stables activé.' : 'Mode pseudonymes réalistes activé.', 'info');
  }

  updateModeUI() {
    const btn = document.getElementById('btnModeToggle');
    const hint = document.getElementById('anonModeHint');
    if (btn) {
      btn.textContent = this.anonMode === 'tokens' ? '🏷️ Jetons' : '🎭 Réaliste';
      btn.setAttribute('aria-pressed', this.anonMode === 'tokens' ? 'true' : 'false');
    }
    if (hint) hint.style.display = this.anonMode === 'tokens' ? '' : 'none';
  }

  autoGrow(el) {
    if (!el || el.tagName !== 'TEXTAREA') return;
    el.style.height = 'auto';
    el.style.height = Math.max(el.scrollHeight, 160) + 'px';
  }

  bindAutoGrow() {
    const ids = ['anonSource', 'anonOutput', 'aiResponse', 'restoredOutput'];
    for (const id of ids) {
      const el = document.getElementById(id);
      if (el) { el.addEventListener('input', () => this.autoGrow(el)); this.autoGrow(el); }
    }
    // Recalcule au redimensionnement (le texte se reflowe → la hauteur change)
    window.addEventListener('resize', () => ids.forEach(id => this.autoGrow(document.getElementById(id))));
  }

  bindSelectionBubble() {
    const $ = id => document.getElementById(id);
    const bubble = $('anonSelBubble');
    const btn = $('anonSelBubbleBtn');
    const annotated = $('anonAnnotated');
    if (!bubble || !btn || !annotated) return;
    const showBubble = (rect) => {
      let top = rect.top - 44;
      if (top < 8) top = rect.bottom + 8;
      bubble.style.left = (rect.left + rect.width / 2) + 'px';
      bubble.style.top = top + 'px';
      bubble.classList.remove('hidden');
    };
    const hideBubble = () => bubble.classList.add('hidden');
    const handleSelection = () => {
      const sel = window.getSelection();
      const txt = (sel.toString() || '').trim();
      if (txt && sel.rangeCount && annotated.contains(sel.anchorNode)) {
        this.lastSelection = txt;
        btn.textContent = '🕵️ Anonymiser « ' + (txt.length > 22 ? txt.slice(0, 22) + '…' : txt) + ' »';
        const cust = $('anonSelBubbleCustom'); if (cust) cust.classList.add('hidden');
        showBubble(sel.getRangeAt(0).getBoundingClientRect());
      } else {
        hideBubble();
      }
    };
    annotated.addEventListener('mouseup', handleSelection);
    annotated.addEventListener('keyup', () => setTimeout(handleSelection, 0));
    btn.addEventListener('click', () => {
      if (this.lastSelection) { this.anonymizeValue(this.lastSelection, this.guessCategory(this.lastSelection)); this.lastSelection = ''; }
      window.getSelection().removeAllRanges();
      hideBubble();
      this.toast('Passage anonymisé.', 'success');
    });
    document.addEventListener('mousedown', (e) => { if (!bubble.contains(e.target) && !annotated.contains(e.target)) hideBubble(); });
    window.addEventListener('scroll', () => hideBubble(), { capture: true });
  }

  anonymizeCustom(value, replacement) {
    if (!value || !replacement) return;
    const normValue = _norm(value);
    this.rules = this.rules.filter(rule => _norm(rule.original) !== normValue);
    this.rules.push({
      id: `rule_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      original: value, replacement: replacement, category: this.guessCategory(value)
    });
    this.saveRules();
    this.renderAnnotated();
    this.updateOutput();
  }

  bindSelectionBubbleCustom() {
    const $ = id => document.getElementById(id);
    const bubbleCustomBtn = $('anonSelBubbleCustomBtn');
    const bubbleCustom = $('anonSelBubbleCustom');
    const bubbleInput = $('anonSelBubbleInput');
    const bubbleConfirm = $('anonSelBubbleConfirm');
    const bubble = $('anonSelBubble');
    if (!bubbleCustomBtn || !bubbleCustom || !bubbleInput || !bubbleConfirm || !bubble) return;
    bubbleCustomBtn.addEventListener('click', () => {
      bubbleCustom.classList.remove('hidden');
      const suggestion = window.AnonymizerCore.generateFake(this.guessCategory(this.lastSelection), this.lastSelection);
      bubbleInput.value = suggestion || '';
      bubbleInput.focus();
      bubbleInput.select();
    });
    const confirm = () => {
      const v = bubbleInput.value.trim();
      if (this.lastSelection && v) {
        this.anonymizeCustom(this.lastSelection, v);
        this.lastSelection = '';
        if (window.getSelection) window.getSelection().removeAllRanges();
      }
      bubble.classList.add('hidden');
      bubbleCustom.classList.add('hidden');
      this.toast('Remplacé par votre valeur.', 'success');
    };
    bubbleConfirm.addEventListener('click', confirm);
    bubbleInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); confirm(); } });
  }

  bindActionMenu() {
    const $ = id => document.getElementById(id);
    const openMenu = () => { const b = $('btnActionsMenu'), mu = $('actionsMenu'); if (b) b.setAttribute('aria-expanded', 'true'); if (mu) mu.classList.remove('hidden'); };
    const closeMenu = () => { const b = $('btnActionsMenu'), mu = $('actionsMenu'); if (b) b.setAttribute('aria-expanded', 'false'); if (mu) mu.classList.add('hidden'); };

    // Capture continue de la sélection (corrige : le clic d'un bouton efface la sélection avant lecture)
    const annotated = $('anonAnnotated');
    if (annotated) {
      const cap = () => { const s = (window.getSelection().toString() || '').trim(); if (s) this.lastSelection = s; };
      annotated.addEventListener('mouseup', cap);
      annotated.addEventListener('keyup', cap);
    }
    const source = $('anonSource');
    if (source) {
      const cap = () => { const v = source.value.substring(source.selectionStart, source.selectionEnd).trim(); if (v) this.lastSelection = v; };
      source.addEventListener('select', cap);
      source.addEventListener('mouseup', cap);
      source.addEventListener('keyup', cap);
    }

    const btn = $('btnActionsMenu'), menu = $('actionsMenu');
    if (btn) btn.addEventListener('click', (e) => { e.stopPropagation(); (menu && menu.classList.contains('hidden')) ? openMenu() : closeMenu(); });
    document.addEventListener('click', (e) => { if (btn && menu && !menu.contains(e.target) && e.target !== btn) closeMenu(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && menu && !menu.classList.contains('hidden')) { closeMenu(); if (btn) btn.focus(); } });
    if (menu) menu.addEventListener('click', (e) => { if (e.target.closest('button')) setTimeout(closeMenu, 0); });
  }

  toast(message, variant) {
    window.dispatchEvent(new CustomEvent('toast-show', { detail: { message, variant, duration: 3000 } }));
  }

  goStep(n) {
    document.querySelectorAll('.anon-step').forEach(el => el.classList.toggle('active', el.dataset.step == n));
    document.querySelectorAll('.anon-panel').forEach(el => el.classList.toggle('active', el.dataset.stepContent == n));
  }

  bindEvents() {
    const on = (id, ev, fn) => { const el = document.getElementById(id); if (el) el.addEventListener(ev, fn); };

    on('btnDetect', 'click', () => this.detect());
    on('btnModeToggle', 'click', () => this.toggleAnonMode());

    on('btnEditText', 'click', () => {
      const wrap = document.getElementById('anonEditorWrap');
      if (wrap && wrap.classList.contains('mode-edit')) this.setMode('annotate');
      else this.setMode('edit');
    });

    const annotated = document.getElementById('anonAnnotated');
    if (annotated) {
      annotated.addEventListener('click', (e) => {
        const span = e.target.closest('span.anon-cand, span.anon-anon');
        if (!span) return;
        const value = span.dataset.value;
        if (span.classList.contains('anon-cand')) this.anonymizeValue(value, span.dataset.category || 'other');
        else this.deanonymizeValue(value);
      });
      annotated.addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('span[role="button"]')) {
          e.preventDefault();
          e.target.click();
        }
      });
    }

    on('btnAnonymizeSelection', 'click', () => {
      // Lit la sélection courante, sinon celle du textarea, sinon la dernière sélection captée (le clic l'a effacée)
      let selText = (window.getSelection().toString() || '').trim();
      const source = document.getElementById('anonSource');
      if (!selText && source) selText = source.value.substring(source.selectionStart, source.selectionEnd).trim();
      if (!selText) selText = this.lastSelection || '';
      if (!selText) { this.toast('Sélectionnez d\'abord un passage dans votre texte, puis cliquez.', 'warning'); return; }
      const wrap = document.getElementById('anonEditorWrap');
      if (wrap && wrap.classList.contains('mode-edit') && source) { this.sourceText = source.value; this.setMode('annotate'); }
      this.anonymizeValue(selText, this.guessCategory(selText));
      this.lastSelection = '';
      this.toast('Passage anonymisé.', 'success');
    });

    on('btnAnonymizeAll', 'click', () => {
      const cands = [...this.candidates];
      if (!cands.length) { this.toast('Rien à anonymiser — détectez ou sélectionnez d\'abord.', 'info'); return; }
      for (const c of cands) this.anonymizeValue(c.value, c.category);
      this.toast(cands.length + ' donnée(s) anonymisée(s).', 'success');
    });

    on('btnResetAll', 'click', () => {
      this.rules = []; this.candidates = []; this.lastSelection = '';
      try { localStorage.removeItem('lv_anon_rules_v3'); } catch (e) {}
      this.setMode('edit');
      this.renderAnnotated();
      this.updateOutput();
      this.toast('Réinitialisé — vous pouvez repartir à zéro.', 'info');
    });

    on('btnCopyAnon', 'click', async () => {
      const output = document.getElementById('anonOutput');
      if (!output || !output.value) { this.toast('Rien à copier.', 'warning'); return; }
      try { await navigator.clipboard.writeText(output.value); this.toast('Texte anonymisé copié.', 'success'); }
      catch (e) { this.toast('Copie impossible — sélectionnez puis Ctrl+C.', 'danger'); }
    });

    on('btnRestore', 'click', () => {
      const ai = document.getElementById('aiResponse');
      const aiText = (ai && ai.value) || '';
      if (!aiText.trim()) { this.toast('Collez la réponse de l\'IA.', 'warning'); return; }
      if (!this.rules.length) { this.toast('Aucune règle : anonymisez d\'abord (étape 1).', 'warning'); return; }
      const res = window.AnonymizerCore.restore(aiText, this.rules);
      const out = document.getElementById('restoredOutput');
      const report = document.getElementById('restoreReport');
      if (out) { out.value = res.text; this.autoGrow(out); }
      const total = res.found.length + res.notFound.length;
      let msg = res.found.length + ' valeur(s) restaurée(s) sur ' + total + '.';
      if (res.notFound.length) {
        msg += ' Non retrouvées : ' + res.notFound.map(r => '« ' + r.original + ' »').join(', ');
        this.toast(res.notFound.length + ' valeur(s) non retrouvée(s).', 'warning');
      } else {
        this.toast('Toutes vos vraies données ont été restaurées.', 'success');
      }
      if (report) report.textContent = msg;
    });

    on('btnCopyRestored', 'click', async () => {
      const out = document.getElementById('restoredOutput');
      if (!out || !out.value) { this.toast('Rien à copier.', 'warning'); return; }
      try { await navigator.clipboard.writeText(out.value); this.toast('Résultat copié.', 'success'); }
      catch (e) { this.toast('Copie impossible — sélectionnez puis Ctrl+C.', 'danger'); }
    });

    document.querySelectorAll('.anon-step').forEach(btn => btn.addEventListener('click', () => {
      this.goStep(btn.dataset.step);
      const top = document.querySelector('.anon-steps') || document.querySelector('.tool-fullscreen-target');
      if (top) top.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
  }
}

new AnonymizerUI();
