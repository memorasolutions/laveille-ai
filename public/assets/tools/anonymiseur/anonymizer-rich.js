// anonymizer-rich.js — éditeur riche : collage sanitizé + surlignage sur les nœuds texte.
// Vanilla, additif. Expose window.AnonRich = { sanitizePastedHtml, highlightEntitiesInElement }.
// Consomme window.AnonymizerCore.buildAccentInsensitiveBoundedRegex pour le matching insensible accents/casse.
(function () {
  'use strict';

  // Balises de mise en forme conservées au collage (liste blanche stricte).
  const ALLOWED = new Set(['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'blockquote', 'a']);
  // Balises de bloc non conservées : on les déballe MAIS on insère un <br> pour garder le saut de ligne.
  const BLOCK_UNWRAP = new Set(['div', 'section', 'article', 'header', 'footer', 'tr', 'table', 'tbody', 'thead', 'pre']);

  /**
   * Nettoie le HTML collé (Word, Google Docs…) : ne garde que ALLOWED, retire tous les attributs
   * (sauf href valide sur <a>), supprime script/style/commentaires/balises Word (« : » dans le nom).
   * @param {string} html
   * @returns {string} HTML nettoyé (ou '')
   */
  function sanitizePastedHtml(html) {
    if (!html || typeof html !== 'string') return '';
    const doc = new DOMParser().parseFromString(html, 'text/html');
    if (!doc || !doc.body) return '';

    function unwrap(node, addBreak) {
      const parent = node.parentNode;
      if (!parent) return;
      while (node.firstChild) parent.insertBefore(node.firstChild, node);
      if (addBreak) parent.insertBefore(doc.createElement('br'), node);
      node.remove();
    }

    // Post-ordre : on nettoie les enfants AVANT le nœud (un script imbriqué dans un div est retiré
    // même si le div est déballé).
    function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(walk);

      if (node.nodeType === 8 /* COMMENT */) { node.remove(); return; }
      if (node.nodeType !== 1 /* ELEMENT */) return;

      const tag = node.tagName.toLowerCase();
      if (tag === 'script' || tag === 'style' || tag.indexOf(':') !== -1) { node.remove(); return; }

      if (ALLOWED.has(tag)) {
        const keepHref = tag === 'a' && /^(https?:\/\/|mailto:)/i.test(node.getAttribute('href') || '');
        Array.prototype.slice.call(node.attributes).forEach(function (a) {
          if (keepHref && a.name === 'href') return;
          node.removeAttribute(a.name);
        });
        if (tag === 'a' && !keepHref) unwrap(node, false); // lien sans href utile → déballer
        return;
      }
      unwrap(node, BLOCK_UNWRAP.has(tag)); // balise hors liste → déballer (bloc → + <br>)
    }

    Array.prototype.slice.call(doc.body.childNodes).forEach(walk);
    return doc.body.innerHTML;
  }

  /**
   * Surligne les entités DANS les nœuds texte de rootEl (in-place), en préservant la mise en forme.
   * @param {Element} rootEl
   * @param {Array<{value:string,category:string,cls:string,priority:number}>} marks  cls = 'anon-anon'|'anon-cand'
   * @param {(s:string)=>string} normFn  normalisation pour la clé d'occurrence
   */
  function highlightEntitiesInElement(rootEl, marks, normFn) {
    if (!rootEl || !Array.isArray(marks) || !marks.length || typeof normFn !== 'function') return;
    if (!window.AnonymizerCore || !window.AnonymizerCore.buildAccentInsensitiveBoundedRegex) return;

    const patterns = marks.map(function (m) {
      return { value: m.value, category: m.category, cls: m.cls, priority: m.priority || 0,
               regex: window.AnonymizerCore.buildAccentInsensitiveBoundedRegex(m.value) };
    });

    const occ = new Map(); // clé normalisée → prochain index d'occurrence (ordre document)

    function insideHighlight(textNode) {
      let p = textNode.parentElement;
      while (p && p !== rootEl) {
        if (p.classList && (p.classList.contains('anon-cand') || p.classList.contains('anon-anon'))) return true;
        p = p.parentElement;
      }
      return false;
    }

    // Snapshot des nœuds texte AVANT mutation.
    const walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_TEXT, null);
    const nodes = [];
    let n;
    while ((n = walker.nextNode())) nodes.push(n);

    nodes.forEach(function (textNode) {
      if (insideHighlight(textNode)) return;
      const text = textNode.textContent;
      if (!text) return;

      const hits = [];
      patterns.forEach(function (p) {
        p.regex.lastIndex = 0;
        let m;
        while ((m = p.regex.exec(text)) !== null) {
          if (m[0].length === 0) { p.regex.lastIndex++; continue; } // garde anti-boucle
          hits.push({ start: m.index, end: m.index + m[0].length, text: m[0],
                      value: p.value, category: p.category, cls: p.cls, priority: p.priority });
        }
      });
      if (!hits.length) return;

      // Résoudre les chevauchements dans le nœud : priorité décroissante puis longueur décroissante,
      // puis position croissante.
      hits.sort(function (a, b) {
        if (b.priority !== a.priority) return b.priority - a.priority;
        const la = a.end - a.start, lb = b.end - b.start;
        if (lb !== la) return lb - la;
        return a.start - b.start;
      });
      const chosen = [];
      hits.forEach(function (h) {
        for (let i = 0; i < chosen.length; i++) {
          if (h.start < chosen[i].end && h.end > chosen[i].start) return; // chevauche → ignorer
        }
        chosen.push(h);
      });
      chosen.sort(function (a, b) { return a.start - b.start; }); // ordre du texte pour le découpage

      const frag = document.createDocumentFragment();
      let pos = 0;
      chosen.forEach(function (h) {
        if (h.start > pos) frag.appendChild(document.createTextNode(text.slice(pos, h.start)));
        const span = document.createElement('span');
        span.className = h.cls;
        span.setAttribute('data-value', h.value);
        span.setAttribute('data-category', h.category || '');
        const key = normFn(h.value);
        const idx = occ.get(key) || 0;
        span.setAttribute('data-occ', String(idx));
        occ.set(key, idx + 1);
        span.setAttribute('tabindex', '0');
        span.setAttribute('role', 'button');
        span.setAttribute('aria-label', h.cls === 'anon-anon'
          ? 'Anonymisé — cliquer pour les options'
          : 'À anonymiser — cliquer pour anonymiser');
        span.textContent = h.text; // échappement sûr
        frag.appendChild(span);
        pos = h.end;
      });
      if (pos < text.length) frag.appendChild(document.createTextNode(text.slice(pos)));

      textNode.parentNode.replaceChild(frag, textNode);
    });
  }

  /**
   * Convertit un élément DOM riche en texte simple en PRÉSERVANT les listes
   * (puces « - », numéros « N. », indentation des listes imbriquées). Implémentation
   * manuelle (innerText n'est pas fiable et perd les marqueurs de liste générés par CSS).
   * @param {Element} root
   * @returns {string}
   */
  function richToText(root) {
    if (!root || root.nodeType !== 1) return '';
    const BLOCK = ['P', 'DIV', 'H1', 'H2', 'H3', 'BLOCKQUOTE', 'TR'];

    function walk(node, depth) {
      let out = '';
      Array.prototype.forEach.call(node.childNodes, function (child) {
        if (child.nodeType === 3) { out += child.textContent; return; } // texte
        if (child.nodeType !== 1) return;
        const tag = child.tagName.toUpperCase();
        if (tag === 'BR') { out += '\n'; return; }
        if (tag === 'UL' || tag === 'OL') { out += walk(child, depth); return; } // pas d'incrément ici
        if (tag === 'LI') {
          const parent = child.parentElement;
          let marker = '';
          if (parent && parent.tagName.toUpperCase() === 'OL') {
            const lis = Array.prototype.filter.call(parent.children, function (el) {
              return el.tagName && el.tagName.toUpperCase() === 'LI';
            });
            marker = (lis.indexOf(child) + 1) + '. ';
          } else if (parent && parent.tagName.toUpperCase() === 'UL') {
            marker = '- ';
          }
          out += '  '.repeat(depth) + marker + walk(child, depth + 1) + '\n';
          return;
        }
        out += walk(child, depth);
        if (BLOCK.indexOf(tag) !== -1) out += '\n';
      });
      return out;
    }

    return walk(root, 0).replace(/\n{3,}/g, '\n\n').trim();
  }

  window.AnonRich = {
    sanitizePastedHtml: sanitizePastedHtml,
    highlightEntitiesInElement: highlightEntitiesInElement,
    richToText: richToText
  };
})();
