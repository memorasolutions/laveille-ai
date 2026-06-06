// anonymizer-ui.js — éditeur annoté inline (souligné = à anonymiser, surligné = anonymisé)
// Vanilla, sans dépendance. Consomme window.AnonymizerCore. Aucune popup native.

const _norm = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

class AnonymizerUI {
  constructor() {
    this.sourceText = '';
    this.sourceHtml = ''; // version riche (mise en forme conservée) de l'éditeur
    this.anonPlain = '';  // texte anonymisé exact (pour la copie vers l'IA)
    this.restoredPlain = ''; // texte restauré exact (pour la copie du résultat)
    this.candidates = [];
    this.rules = [];
    this.anonMode = 'pseudo';
    this.lastSelection = '';
    this.overrides = []; // [{original, occ, replacement}] : remplacements par occurrence
    this.init();
  }

  init() {
    document.addEventListener('DOMContentLoaded', () => {
      if (!window.AnonymizerCore) { console.error('AnonymizerCore manquant'); return; }
      this.loadRules();
      this.loadSource(); // restaure le texte sauvegardé dans ce navigateur (100 % local)
      this.anonMode = localStorage.getItem('lv_anon_mode') || 'pseudo';
      this.setMode('edit');
      this.updateOutput();
      this.goStep(1);
      this.bindEvents();
      this.bindActionMenu();
      this.bindSelectionBubble();
      this.bindSelectionBubbleCustom();
      this.bindOccPopover();
      this.bindViewSwitch();
      this.bindAutoGrow();
      this.bindRichEditor();
      this.bindRestoredTooltip();
      this.bindToggleFakes();
      this.updateModeUI();
    });
  }

  escHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
  }

  // Texte simple de l'éditeur riche en CONSERVANT les listes (« - », « 1. »). Repli innerText.
  richText(el) {
    if (window.AnonRich && window.AnonRich.richToText) return window.AnonRich.richToText(el);
    return el ? (el.innerText || '') : '';
  }

  saveRules() {
    try {
      localStorage.setItem('lv_anon_rules_v3', JSON.stringify(this.rules));
      localStorage.setItem('lv_anon_overrides_v3', JSON.stringify(this.overrides));
    } catch (e) { console.warn('save', e); }
  }

  // Persistance du TEXTE de l'éditeur (100 % local). Clé stable NON purgée au changement de
  // version → le texte survit aux mises à jour de l'outil ; effacé seulement par Réinitialiser/Oublier.
  saveSource() {
    try { localStorage.setItem('lv_anon_source_v3', this.sourceHtml || ''); } catch (e) {}
  }

  loadSource() {
    try {
      const s = localStorage.getItem('lv_anon_source_v3');
      if (s && s.trim()) {
        this.sourceHtml = s;
        const tmp = document.createElement('div'); tmp.innerHTML = s;
        this.sourceText = this.richText(tmp);
      }
    } catch (e) {}
  }

  loadRules() {
    try {
      // Anti règles fantômes : si la version de l'outil a changé, on repart propre
      const ver = window.LV_ANON_VERSION || '';
      if (ver && localStorage.getItem('lv_anon_v') !== ver) {
        localStorage.removeItem('lv_anon_rules_v3');
        localStorage.removeItem('lv_anon_overrides_v3');
        localStorage.setItem('lv_anon_v', ver);
        this.rules = []; this.overrides = [];
        return;
      }
      const s = localStorage.getItem('lv_anon_rules_v3'); if (s) this.rules = JSON.parse(s);
      const o = localStorage.getItem('lv_anon_overrides_v3'); if (o) this.overrides = JSON.parse(o);
    } catch (e) { console.warn('load', e); this.rules = []; this.overrides = []; }
  }

  updateOutput() {
    const output = document.getElementById('anonOutput');
    if (!output) return;
    // Texte simple EXACT (avec overrides) — c'est CE texte qui est copié vers l'IA.
    this.anonPlain = window.AnonymizerCore.anonymize(this.sourceText, this.rules, this.overrides);
    // Vue riche miroir : faux SURLIGNÉS + candidats SOULIGNÉS → comparaison gauche↔droite facile.
    if (this.sourceText.trim() && this.sourceHtml && this.sourceHtml.trim() && window.AnonRich && window.AnonRich.highlightEntitiesInElement) {
      output.innerHTML = this.safeSourceHtml();
      const marks = [];
      const ruleSet = new Set();
      for (const r of this.rules) { ruleSet.add(_norm(r.original)); marks.push({ value: r.original, replacement: r.replacement, category: r.category, cls: 'anon-anon', priority: 1 }); }
      for (const c of this.candidates) { if (!ruleSet.has(_norm(c.value))) marks.push({ value: c.value, category: c.category, cls: 'anon-cand', priority: 0 }); }
      if (marks.length) window.AnonRich.highlightEntitiesInElement(output, marks, _norm, { interactive: false });
    } else {
      output.textContent = this.anonPlain; // repli texte simple
    }
  }

  async copyAnon(btn) {
    if (!this.anonPlain) { this.toast('Rien à copier.', 'warning'); return; }
    try {
      await navigator.clipboard.writeText(this.anonPlain);
      this.toast('Texte anonymisé copié.', 'success');
      if (btn) { const o = btn.innerHTML; btn.innerHTML = '✓ Copié'; setTimeout(() => { btn.innerHTML = o; }, 1500); }
    } catch (e) { this.toast('Copie impossible — sélectionnez puis Ctrl+C.', 'danger'); }
  }

  // Défense en profondeur : re-sanitize le HTML de l'éditeur avant toute injection innerHTML.
  safeSourceHtml() {
    return (window.AnonRich && window.AnonRich.sanitizePastedHtml)
      ? window.AnonRich.sanitizePastedHtml(this.sourceHtml) : this.sourceHtml;
  }

  // Vie privée : efface TOUT (texte + table de correspondance) de ce navigateur (poste partagé).
  forgetAll() {
    this.sourceText = ''; this.sourceHtml = ''; this.anonPlain = ''; this.restoredPlain = '';
    this.rules = []; this.candidates = []; this.overrides = []; this.lastSelection = '';
    try { localStorage.removeItem('lv_anon_rules_v3'); localStorage.removeItem('lv_anon_overrides_v3'); localStorage.removeItem('lv_anon_source_v3'); } catch (e) {}
    const src = document.getElementById('anonSource'); if (src) src.innerHTML = '';
    const ai = document.getElementById('aiResponse'); if (ai) ai.value = '';
    const ro = document.getElementById('restoredOutput'); if (ro) ro.textContent = '';
    const rep = document.getElementById('restoreReport'); if (rep) rep.innerHTML = '';
    this.setMode('edit'); this.renderAnnotated(); this.updateOutput();
    this.toast('Données oubliées — la table de correspondance a été effacée de ce navigateur.', 'success');
  }

  // Étape 2 : affiche le texte restauré avec les vraies données SURLIGNÉES ; la valeur anonyme
  // (le faux) est révélée au survol/focus (tooltip) et via la bascule globale.
  renderRestored(res) {
    const out = document.getElementById('restoredOutput');
    if (!out) return;
    this.restoredPlain = res.text;
    out.classList.remove('lv-show-fakes');
    const btn = document.getElementById('btnToggleFakes');
    if (btn) { btn.setAttribute('aria-pressed', 'false'); btn.innerHTML = '👁️ Voir les valeurs anonymes'; }
    if (!res.text) { out.textContent = ''; return; }
    out.textContent = res.text;
    const marks = (res.found || []).map(r => ({ value: r.original, category: r.category, cls: 'anon-restored', tip: r.replacement, priority: 1 }));
    if (marks.length && window.AnonRich && window.AnonRich.highlightEntitiesInElement) {
      window.AnonRich.highlightEntitiesInElement(out, marks, _norm, { interactive: false });
    }
  }

  // Tooltip accessible (WCAG 1.4.13 : survol + focus clavier, fermable Esc, survolable/persistant).
  bindRestoredTooltip() {
    const out = document.getElementById('restoredOutput');
    const tip = document.getElementById('anonTip');
    if (!out || !tip) return;
    let hideTimer = null;
    const cancelHide = () => { if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; } };
    const show = (span) => {
      const fake = span.getAttribute('data-fake');
      if (!fake) return;
      cancelHide();
      tip.textContent = 'Anonymisé : ' + fake;
      tip.classList.remove('hidden');
      const r = span.getBoundingClientRect();
      tip.style.left = (r.left + r.width / 2) + 'px';
      let top = r.top - tip.offsetHeight - 8;
      if (top < 8) top = r.bottom + 8;
      tip.style.top = top + 'px';
    };
    const scheduleHide = () => { cancelHide(); hideTimer = setTimeout(() => tip.classList.add('hidden'), 200); };
    out.addEventListener('mouseover', (e) => { const s = e.target.closest('.anon-restored'); if (s) show(s); });
    out.addEventListener('mouseout', (e) => { if (e.target.closest('.anon-restored')) scheduleHide(); });
    out.addEventListener('focusin', (e) => { const s = e.target.closest('.anon-restored'); if (s) show(s); });
    out.addEventListener('focusout', (e) => { if (e.target.closest('.anon-restored')) scheduleHide(); });
    tip.addEventListener('mouseover', cancelHide); // survolable
    tip.addEventListener('mouseout', scheduleHide);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tip.classList.add('hidden'); }); // fermable
    window.addEventListener('scroll', () => tip.classList.add('hidden'), { capture: true });
  }

  // Bascule globale (B) : révèle TOUTES les valeurs anonymes en ligne (relecture/audit).
  bindToggleFakes() {
    const btn = document.getElementById('btnToggleFakes');
    const out = document.getElementById('restoredOutput');
    if (!btn || !out) return;
    btn.addEventListener('click', () => {
      const on = out.classList.toggle('lv-show-fakes');
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.innerHTML = on ? '🙈 Masquer les valeurs anonymes' : '👁️ Voir les valeurs anonymes';
    });
  }

  renderAnnotated() {
    const container = document.getElementById('anonAnnotated');
    if (!container) return;
    if (!this.sourceText.trim()) {
      container.innerHTML = '<span class="anon-placeholder">Saisissez ou collez du texte, puis « Détecter ».</span>';
      return;
    }

    // Base = HTML riche (préserve gras/italique/listes), sinon repli texte simple échappé.
    if (this.sourceHtml && this.sourceHtml.trim()) {
      container.innerHTML = this.safeSourceHtml();
    } else {
      container.textContent = this.sourceText;
    }

    const marks = [];
    const ruleSet = new Set();
    for (const rule of this.rules) {
      ruleSet.add(_norm(rule.original));
      marks.push({ value: rule.original, category: rule.category, cls: 'anon-anon', priority: 1 });
    }
    for (const cand of this.candidates) {
      if (!ruleSet.has(_norm(cand.value))) {
        marks.push({ value: cand.value, category: cand.category, cls: 'anon-cand', priority: 0 });
      }
    }

    // Surlignage injecté DANS les nœuds texte (la mise en forme reste intacte).
    if (marks.length && window.AnonRich) {
      window.AnonRich.highlightEntitiesInElement(container, marks, _norm);
    }
  }

  setMode(mode) {
    const wrap = document.getElementById('anonEditorWrap');
    const source = document.getElementById('anonSource');
    const btnEdit = document.getElementById('btnEditText');
    if (mode === 'edit') {
      if (wrap) { wrap.classList.remove('mode-annotate'); wrap.classList.add('mode-edit'); }
      if (source) {
        if (this.sourceHtml && this.sourceHtml.trim()) source.innerHTML = this.sourceHtml;
        else source.innerHTML = this.escHtml(this.sourceText).replace(/\n/g, '<br>');
      }
      if (btnEdit) btnEdit.textContent = '👁️ Voir les annotations';
    } else {
      // Lire AVANT de masquer l'éditeur (innerText='' si display:none)
      if (source) { this.sourceHtml = source.innerHTML; this.sourceText = this.richText(source); }
      if (wrap) { wrap.classList.remove('mode-edit'); wrap.classList.add('mode-annotate'); }
      this.renderAnnotated();
      if (btnEdit) btnEdit.textContent = '✏️ Modifier le texte';
    }
  }

  detect(silent) {
    const source = document.getElementById('anonSource');
    if (source) { this.sourceHtml = source.innerHTML; this.sourceText = this.richText(source); }
    if (!this.sourceText.trim()) { this.toast('Collez d\'abord votre texte.', 'warning'); return; }
    const entities = window.AnonymizerCore.detectEntities(this.sourceText);
    const existing = new Set(this.rules.map(r => _norm(r.original)));
    this.candidates = entities.filter(ent => !existing.has(_norm(ent.value)));
    this.setMode('annotate');
    if (silent) return;
    if (this.candidates.length) this.toast(this.candidates.length + ' donnée(s) repérée(s) — cliquez pour anonymiser.', 'info');
    else this.toast('Aucune donnée repérée automatiquement. Sélectionnez un passage à anonymiser.', 'info');
  }

  // Bouton principal : détecte PUIS anonymise tout en un clic.
  detectAndAnonymizeAll() {
    this.detect(true);
    if (!this.sourceText.trim()) return; // detect a déjà averti (texte vide)
    const cands = [...this.candidates];
    for (const c of cands) this.anonymizeValue(c.value, c.category);
    if (cands.length) this.toast(cands.length + ' donnée(s) détectée(s) et anonymisée(s).', 'success');
    else this.toast('Aucune donnée détectée — sélectionnez un passage à anonymiser (menu Actions).', 'info');
  }

  anonymizeValue(value, category) {
    const newRules = window.AnonymizerCore.buildRules([{ value, category }], { mode: this.anonMode, existing: this.rules });
    const normNew = new Set(newRules.map(r => _norm(r.original)));
    this.rules = [...this.rules.filter(r => !normNew.has(_norm(r.original))), ...newRules];
    if (window.AnonymizerCore.relinkEmails) window.AnonymizerCore.relinkEmails(this.rules); // courriel ↔ faux nom cohérent
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
    this.overrides = (this.overrides || []).filter(o => _norm(o.original) !== nv); // retire les overrides de cette valeur
    this.candidates.push({ value, category: this.guessCategory(value) });
    this.saveRules();
    this.renderAnnotated();
    this.updateOutput();
  }

  addOverride(original, occ, replacement) {
    if (!original || !replacement) return;
    const no = _norm(original);
    this.overrides = (this.overrides || []).filter(o => !(_norm(o.original) === no && o.occ === occ));
    this.overrides.push({ original, occ, replacement });
    this.saveRules();
    this.renderAnnotated();
    this.updateOutput();
  }

  showOccPopover(span) {
    const pop = document.getElementById('anonOccPopover');
    if (!pop) { this.deanonymizeValue(span.dataset.value); return; } // repli si markup absent
    this.currentOcc = { original: span.dataset.value, occ: parseInt(span.dataset.occ, 10) || 0 };
    const custom = document.getElementById('anonOccCustom'); if (custom) custom.classList.add('hidden');
    const rect = span.getBoundingClientRect();
    pop.style.left = (rect.left + rect.width / 2) + 'px';
    let top = rect.top - 48; if (top < 8) top = rect.bottom + 8;
    pop.style.top = top + 'px';
    pop.classList.remove('hidden');
  }

  hideOccPopover() {
    const pop = document.getElementById('anonOccPopover');
    if (pop) pop.classList.add('hidden');
  }

  setCustomReplacement(value, replacement) {
    if (!value || !replacement) return;
    const nv = _norm(value);
    let done = false;
    this.rules.forEach(r => { if (_norm(r.original) === nv) { r.replacement = replacement; done = true; } });
    if (!done) this.rules.push({ id: 'rule_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9), original: value, replacement, category: this.guessCategory(value) });
    this.overrides = (this.overrides || []).filter(o => _norm(o.original) !== nv); // la valeur globale prime
    this.saveRules(); this.renderAnnotated(); this.updateOutput();
  }

  bindOccPopover() {
    const $ = id => document.getElementById(id);
    const pop = $('anonOccPopover');
    if (!pop) return;
    const cancel = $('anonOccCancel'), diff = $('anonOccDiff'), valueBtn = $('anonOccValue'), custom = $('anonOccCustom'), input = $('anonOccInput'), confirm = $('anonOccConfirm');
    const reveal = (mode) => {
      this.occMode = mode;
      if (custom) custom.classList.remove('hidden');
      if (input && this.currentOcc) {
        input.value = window.AnonymizerCore.generateFake(this.guessCategory(this.currentOcc.original), this.currentOcc.original);
        input.focus(); input.select();
      }
    };
    if (cancel) cancel.addEventListener('click', () => { const oc = this.currentOcc; this.hideOccPopover(); if (oc) this.deanonymizeValue(oc.original); });
    if (valueBtn) valueBtn.addEventListener('click', () => reveal('global'));
    if (diff) diff.addEventListener('click', () => reveal('occ'));
    const doConfirm = () => {
      const v = input ? input.value.trim() : '';
      const oc = this.currentOcc, mode = this.occMode;
      this.hideOccPopover();
      if (oc && v) {
        if (mode === 'global') { this.setCustomReplacement(oc.original, v); this.toast('Valeur remplacée partout.', 'success'); }
        else { this.addOverride(oc.original, oc.occ, v); this.toast('Cette occurrence est désormais différente.', 'success'); }
      }
    };
    if (confirm) confirm.addEventListener('click', doConfirm);
    if (input) input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doConfirm(); } });
    document.addEventListener('mousedown', (e) => { const a = $('anonAnnotated'); if (!pop.contains(e.target) && (!a || !a.contains(e.target))) this.hideOccPopover(); });
    window.addEventListener('scroll', () => this.hideOccPopover(), { capture: true });
  }

  guessCategory(text) {
    // Réutilise le moteur de détection sur le passage sélectionné → bonne catégorie (nom, date, RAMQ, courriel…)
    const t = (text || '').trim();
    const ents = window.AnonymizerCore.detectEntities(t) || [];
    if (ents.length) return ents.sort((a, b) => b.value.length - a.value.length)[0].category;
    if (/\d/.test(t)) return 'id';
    // Mot capitalisé seul → nom de famille (un seul faux), 2+ mots → nom complet.
    if (/^[A-ZÀ-Ÿ]/.test(t)) return /\s/.test(t) ? 'name' : 'lastName';
    return 'other';
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
    if (window.AnonymizerCore.relinkEmails) window.AnonymizerCore.relinkEmails(this.rules); // courriel ↔ faux nom cohérent
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

  bindViewSwitch() {
    const grid = document.querySelector('.anon-grid');
    const btns = document.querySelectorAll('.anon-viewswitch button[data-view]');
    if (!grid || !btns.length) return;
    const apply = (view) => {
      grid.classList.remove('view-editor', 'view-split', 'view-preview');
      grid.classList.add('view-' + view);
      btns.forEach(b => b.setAttribute('aria-pressed', b.dataset.view === view ? 'true' : 'false'));
      try { localStorage.setItem('lv_anon_view', view); } catch (e) {}
      ['anonSource', 'anonOutput'].forEach(id => this.autoGrow(document.getElementById(id)));
    };
    btns.forEach(b => b.addEventListener('click', () => apply(b.dataset.view)));
    let saved = 'split'; try { saved = localStorage.getItem('lv_anon_view') || 'split'; } catch (e) {}
    apply(saved);
  }

  bindAutoGrow() {
    // anonSource est un contenteditable (géré par bindRichEditor) : auto-extensible nativement.
    const ids = ['anonOutput', 'aiResponse', 'restoredOutput'];
    for (const id of ids) {
      const el = document.getElementById(id);
      if (el) { el.addEventListener('input', () => this.autoGrow(el)); this.autoGrow(el); }
    }
    // Après collage d'un long texte : rester en haut du champ (ne pas « tomber » en bas)
    for (const id of ['aiResponse']) {
      const el = document.getElementById(id);
      if (el) el.addEventListener('paste', () => setTimeout(() => {
        this.autoGrow(el);
        try { el.setSelectionRange(0, 0); } catch (e) {}
        const r = el.getBoundingClientRect();
        window.scrollTo({ top: Math.max(0, window.scrollY + r.top - 90), behavior: 'auto' });
      }, 0));
    }
    // Recalcule au redimensionnement (le texte se reflowe → la hauteur change)
    window.addEventListener('resize', () => ids.forEach(id => this.autoGrow(document.getElementById(id))));
  }

  // Éditeur riche : collage Word/Docs nettoyé (mise en forme conservée), synchro de l'état.
  bindRichEditor() {
    const src = document.getElementById('anonSource');
    if (!src) return;
    const sync = () => { this.sourceHtml = src.innerHTML; this.sourceText = this.richText(src); this.saveSource(); };
    src.addEventListener('input', sync);
    src.addEventListener('blur', sync);
    src.addEventListener('paste', (e) => {
      const cb = e.clipboardData || window.clipboardData;
      if (!cb) return; // laisse le collage natif
      e.preventDefault();
      const rawHtml = cb.getData('text/html');
      let clean = '';
      if (rawHtml && window.AnonRich) clean = window.AnonRich.sanitizePastedHtml(rawHtml);
      // execCommand conservé VOLONTAIREMENT (déprécié mais seul à préserver l'annuler/refaire
      // natif en 2026 ; Range.insertNode casserait l'undo — décision audit/recherche juin 2026).
      try {
        if (clean && clean.trim()) document.execCommand('insertHTML', false, clean);
        else document.execCommand('insertText', false, cb.getData('text/plain') || '');
      } catch (err) {
        // Repli : insertion texte brut
        try { document.execCommand('insertText', false, cb.getData('text/plain') || ''); } catch (e2) {}
      }
      setTimeout(() => {
        sync();
        const r = src.getBoundingClientRect();
        window.scrollTo({ top: Math.max(0, window.scrollY + r.top - 90), behavior: 'auto' });
      }, 0);
    });
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
    // Cmd/Ctrl+A : confiner la sélection au champ annoté (sinon le navigateur sélectionne toute la page)
    annotated.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && (e.key === 'a' || e.key === 'A')) {
        e.preventDefault();
        const r = document.createRange();
        r.selectNodeContents(annotated);
        const s = window.getSelection();
        s.removeAllRanges();
        s.addRange(r);
        handleSelection();
      }
    });
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
      const cap = () => {
        const sel = window.getSelection();
        const v = (sel.toString() || '').trim();
        if (v && sel.rangeCount && source.contains(sel.anchorNode)) this.lastSelection = v;
      };
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
    on('btnDetectAnonAll', 'click', () => this.detectAndAnonymizeAll());
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
        else this.showOccPopover(span);
      });
      annotated.addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('span[role="button"]')) {
          e.preventDefault();
          e.target.click();
        }
      });
    }

    on('btnAnonymizeSelection', 'click', () => {
      // Lit la sélection courante, sinon la dernière sélection captée (le clic l'a effacée)
      let selText = (window.getSelection().toString() || '').trim();
      const source = document.getElementById('anonSource');
      if (!selText) selText = this.lastSelection || '';
      if (!selText) { this.toast('Sélectionnez d\'abord un passage dans votre texte, puis cliquez.', 'warning'); return; }
      const wrap = document.getElementById('anonEditorWrap');
      if (wrap && wrap.classList.contains('mode-edit') && source) { this.sourceHtml = source.innerHTML; this.sourceText = this.richText(source); this.setMode('annotate'); }
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
      this.rules = []; this.candidates = []; this.lastSelection = ''; this.overrides = [];
      this.sourceText = ''; this.sourceHtml = ''; this.anonPlain = '';
      try { localStorage.removeItem('lv_anon_rules_v3'); localStorage.removeItem('lv_anon_overrides_v3'); localStorage.removeItem('lv_anon_source_v3'); } catch (e) {}
      const src = document.getElementById('anonSource'); if (src) src.innerHTML = '';
      this.setMode('edit');
      this.renderAnnotated();
      this.updateOutput();
      this.toast('Réinitialisé — tout le contenu a été effacé.', 'info');
    });

    on('btnForgetAll', 'click', () => this.forgetAll());

    on('btnCopyAnon', 'click', (e) => this.copyAnon(e.currentTarget));
    on('btnCopyAnonTop', 'click', (e) => this.copyAnon(e.currentTarget));

    // Handoff vers le constructeur de prompts : on passe SEULEMENT le texte anonymisé,
    // via sessionStorage (volatile, same-origin) — jamais dans l'URL (aucune fuite PII).
    on('btnToPromptBuilder', 'click', () => {
      if (!this.anonPlain) { this.toast('Anonymisez d\'abord un texte.', 'warning'); return; }
      try { sessionStorage.setItem('lv_handoff_prompt_text', this.anonPlain); } catch (e) {}
      window.location.href = '/outils/constructeur-prompts';
    });

    on('btnRestore', 'click', () => {
      const ai = document.getElementById('aiResponse');
      const aiText = (ai && ai.value) || '';
      if (!aiText.trim()) { this.toast('Collez la réponse de l\'IA.', 'warning'); return; }
      if (!this.rules.length) { this.toast('Aucune règle : anonymisez d\'abord (étape 1).', 'warning'); return; }
      const res = window.AnonymizerCore.restore(aiText, this.rules, this.overrides);
      const report = document.getElementById('restoreReport');
      this.renderRestored(res);
      if (res.notFound.length) this.toast(res.notFound.length + ' valeur(s) non retrouvée(s).', 'warning');
      else this.toast('Toutes vos vraies données ont été restaurées.', 'success');
      if (report) {
        if (window.AnonRich && window.AnonRich.buildRestoreReportHtml) {
          report.innerHTML = window.AnonRich.buildRestoreReportHtml(res, s => this.escHtml(s));
        } else {
          const total = res.found.length + res.notFound.length;
          report.textContent = res.found.length + ' valeur(s) restaurée(s) sur ' + total + '.';
        }
      }
    });

    on('btnCopyRestored', 'click', async () => {
      if (!this.restoredPlain) { this.toast('Rien à copier.', 'warning'); return; }
      try { await navigator.clipboard.writeText(this.restoredPlain); this.toast('Résultat copié.', 'success'); }
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
