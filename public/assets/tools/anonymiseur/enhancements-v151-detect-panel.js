/**
 * enhancements-v151-detect-panel.js — Sprint S131, Option A (hybride)
 * Panneau latéral des détections PII (groupé par catégorie + compteurs + navigation ◀▶)
 * synchronisé avec le surlignage inline Tiptap (window.anonymiseur*) + popover d'action
 * + retrait direct sans popup (Ignorer). Chargé en classic <script> APRÈS app.js.
 *
 * État « anonymisé » DÉRIVÉ de l'existence d'une règle (source de vérité = AppState.rules) :
 * les items anonymisés restent visibles dans une section « Anonymisés ✓ » (continuité,
 * demande user #13), surlignés en surligneur plein + ✓ dans le texte. « Annuler » supprime
 * la règle → l'item repasse en « À traiter ».
 *
 * Globals déjà disponibles : AppState, addDetection, addRule, deleteRule, generateFakeData,
 * getCategoryIcon, escapeHtml, showToast.
 */
(function () {
    'use strict';

    if (typeof window.lvUpdateDetections !== 'undefined') return;

    var categoryLabels = {
        identity: 'Identité',
        contact: 'Contact',
        location: 'Lieu',
        id: 'Identifiant',
        date: 'Date',
        money: 'Montant',
        other: 'Autre'
    };

    function isDetectionDone(d) {
        if (!d || !d.text || !AppState || !Array.isArray(AppState.rules)) return false;
        var textLower = d.text.toLowerCase();
        return AppState.rules.some(function (r) {
            return r && r.original && r.original.toLowerCase() === textLower;
        });
    }

    // Indices des détections « à traiter » : ni ignorées, ni déjà anonymisées (dérivé).
    function getPendingIndices() {
        var pending = [];
        if (!AppState.detections) return pending;
        AppState.detections.forEach(function (d, i) {
            if (!d._ignored && !isDetectionDone(d)) {
                pending.push(i);
            }
        });
        return pending;
    }

    function lvRenderList() {
        var listEl = document.getElementById('lvDetectList');
        var countEl = document.getElementById('lvDetectCount');
        var emptyEl = document.getElementById('lvDetectEmpty');
        if (!listEl || !countEl || !emptyEl) return;

        var detections = AppState.detections || [];
        var pending = [];
        var done = [];

        detections.forEach(function (d, i) {
            if (d._ignored) return;
            if (isDetectionDone(d)) {
                done.push({ index: i, detection: d });
            } else {
                pending.push({ index: i, detection: d });
            }
        });

        countEl.textContent = pending.length;
        if (pending.length === 0 && done.length === 0) {
            emptyEl.classList.remove('hidden');
        } else {
            emptyEl.classList.add('hidden');
        }

        var html = '';

        // === Section « À traiter » (groupée par catégorie) ===
        if (pending.length > 0) {
            var groups = {};
            var groupOrder = [];
            pending.forEach(function (item) {
                var cat = item.detection.category;
                if (!groups[cat]) {
                    groups[cat] = [];
                    groupOrder.push(cat);
                }
                groups[cat].push(item);
            });

            html += '<div class="lv-detect-section">';
            html += '<div class="lv-detect-sechead">À traiter <span class="lv-detect-seccount">' + pending.length + '</span></div>';

            groupOrder.forEach(function (cat) {
                var items = groups[cat];
                var label = categoryLabels[cat] || cat;
                var icon = getCategoryIcon(cat);
                html += '<div class="lv-detect-group" data-category="' + escapeHtml(cat) + '">' +
                    '<div class="lv-detect-grouphead">' +
                    '<span class="lv-detect-gico" aria-hidden="true">' + escapeHtml(icon) + '</span>' +
                    escapeHtml(label) +
                    '<span class="lv-detect-gcount">' + items.length + '</span>' +
                    '</div>' +
                    '<div class="lv-detect-items" role="list">';
                items.forEach(function (item) {
                    var d = item.detection;
                    var escText = escapeHtml(d.text);
                    html += '<div class="lv-detect-item" role="listitem" data-index="' + item.index + '" data-category="' + escapeHtml(cat) + '">' +
                        '<button type="button" class="lv-detect-jump" data-index="' + item.index + '" title="Voir dans le texte">' +
                        '<span class="lv-detect-swatch" aria-hidden="true"></span>' +
                        '<span class="lv-detect-text">' + escText + '</span>' +
                        '</button>' +
                        '<span class="lv-detect-actions">' +
                        '<button type="button" class="lv-detect-anon" data-index="' + item.index + '" title="Anonymiser" aria-label="Anonymiser ' + escText + '">＋</button>' +
                        '<button type="button" class="lv-detect-ignore" data-index="' + item.index + '" title="Ignorer" aria-label="Ignorer ' + escText + '">×</button>' +
                        '</span>' +
                        '</div>';
                });
                html += '</div></div>';
            });
            html += '</div>';
        }

        // === Section « Anonymisés ✓ » (liste plate, bouton Annuler) ===
        if (done.length > 0) {
            html += '<div class="lv-detect-section lv-detect-section--done">';
            html += '<div class="lv-detect-sechead">Anonymisés ✓ <span class="lv-detect-seccount">' + done.length + '</span></div>';
            html += '<div class="lv-detect-items" role="list">';
            done.forEach(function (item) {
                var d = item.detection;
                var cat = d.category;
                var escText = escapeHtml(d.text);
                html += '<div class="lv-detect-item is-done" role="listitem" data-index="' + item.index + '" data-category="' + escapeHtml(cat) + '">' +
                    '<button type="button" class="lv-detect-jump" data-index="' + item.index + '" title="Cliquer pour modifier l\'anonymisation">' +
                    '<span class="lv-detect-swatch" aria-hidden="true"></span>' +
                    '<span class="lv-detect-text">' + escText + '</span>' +
                    '</button>' +
                    '<span class="lv-detect-actions">' +
                    '<button type="button" class="lv-detect-edit" data-index="' + item.index + '" title="Modifier l\'anonymisation" aria-label="Modifier l\'anonymisation de ' + escText + '">✏️</button>' +
                    '<button type="button" class="lv-detect-undo" data-index="' + item.index + '" title="Annuler l\'anonymisation" aria-label="Annuler l\'anonymisation de ' + escText + '">↶</button>' +
                    '</span>' +
                    '</div>';
            });
            html += '</div></div>';
        }

        listEl.innerHTML = html;
    }

    function lvShowPanel() {
        var panel = document.getElementById('detectionsPanel');
        if (panel) {
            panel.classList.remove('hidden');
        }
    }

    function lvSyncHighlight() {
        if (typeof window.anonymiseurHighlightDetections !== 'function') return;
        var detections = AppState.detections || [];
        var highlightList = detections.map(function (d) {
            if (d._ignored) {
                return { text: '', category: d.category };
            }
            return { text: d.text, category: d.category, done: isDetectionDone(d) };
        });
        window.anonymiseurHighlightDetections(highlightList);
    }

    function lvSetActive(index, scroll) {
        AppState.activeDetectIndex = index;
        if (typeof window.anonymiseurSetActiveDetection === 'function') {
            window.anonymiseurSetActiveDetection(index, scroll);
        }

        var listEl = document.getElementById('lvDetectList');
        if (!listEl) return;

        var items = listEl.querySelectorAll('.lv-detect-item');
        items.forEach(function (item) {
            item.classList.remove('is-active');
        });

        var activeItem = listEl.querySelector('.lv-detect-item[data-index="' + index + '"]');
        if (activeItem) {
            activeItem.classList.add('is-active');
            activeItem.scrollIntoView({ block: 'nearest' });
        }
    }

    // Appelée après création d'une règle depuis une détection (état done dérivé) → re-rendu.
    function lvMarkDetectionDone(index) {
        lvRenderList();
        lvSyncHighlight();
    }

    // « Annuler l'anonymisation » : supprime la/les règle(s) du terme → repasse en À traiter.
    function lvUndoDetection(index) {
        var detections = AppState && AppState.detections;
        if (!detections || index < 0 || index >= detections.length) return;
        var d = detections[index];
        if (!d || !d.text) return;
        var textLower = d.text.toLowerCase();
        var idsToDelete = [];
        if (Array.isArray(AppState.rules)) {
            AppState.rules.forEach(function (r) {
                if (r && r.id && r.original && r.original.toLowerCase() === textLower) {
                    idsToDelete.push(r.id);
                }
            });
        }
        idsToDelete.forEach(function (id) { deleteRule(id); });
        lvRenderList();
        lvSyncHighlight();
        showToast('Anonymisation annulée', 'info');
    }

    // Édition au clic d'une anonymisation (demande #18) : ouvre le modal d'édition de la règle du terme.
    function lvEditDetection(index) {
        var d = AppState.detections && AppState.detections[index];
        if (!d || !d.text) return;
        var key = d.text.toLowerCase();
        var rule = (AppState.rules || []).find(function (r) {
            return r && r.original && r.original.toLowerCase() === key;
        });
        if (rule && typeof editRule === 'function') {
            lvHidePopover();
            editRule(rule.id);
        }
    }

    function lvIgnore(index) {
        if (!AppState.detections || !AppState.detections[index]) return;
        AppState.detections[index]._ignored = true;
        lvRenderList();
        lvSyncHighlight();
        lvHidePopover();
        showToast('Détection ignorée', 'info');
    }

    function lvNav(dir) {
        var pendingIndices = getPendingIndices();
        if (pendingIndices.length === 0) return;

        var currentIndex = AppState.activeDetectIndex;
        var pos = pendingIndices.indexOf(currentIndex);
        if (pos === -1) {
            pos = dir > 0 ? -1 : 0;
        }
        var nextPos = (pos + dir + pendingIndices.length) % pendingIndices.length;
        var nextIndex = pendingIndices[nextPos];
        lvSetActive(nextIndex);
    }

    function lvShowPopover(idx, anchorEl) {
        var pop = document.getElementById('lvDetectPopover');
        var d = AppState.detections && AppState.detections[idx];
        if (!pop || !d) return;

        var popCat = document.getElementById('lvDetectPopcat');
        var popText = document.getElementById('lvDetectPoptext');
        if (popCat && popText) {
            popCat.textContent = categoryLabels[d.category] || d.category;
            popText.textContent = d.text;
        }

        pop.dataset.index = idx;
        pop.classList.remove('hidden');

        var r = anchorEl.getBoundingClientRect();
        pop.style.position = 'fixed';
        pop.style.top = (r.bottom + 6) + 'px';
        var leftPos = Math.max(8, Math.min(r.left, window.innerWidth - pop.offsetWidth - 8));
        pop.style.left = leftPos + 'px';
    }

    function lvHidePopover() {
        var pop = document.getElementById('lvDetectPopover');
        if (pop) {
            pop.classList.add('hidden');
        }
    }

    function handleDocumentClick(e) {
        var pop = document.getElementById('lvDetectPopover');
        if (!pop || pop.classList.contains('hidden')) return;

        var target = e.target;
        var isInsidePopover = pop.contains(target);
        var isInsideMark = target.closest && target.closest('.anonym-detect[data-detect-index]');

        if (!isInsidePopover && !isInsideMark) {
            lvHidePopover();
        }
    }

    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            lvHidePopover();
        }
    }

    var listenersInitialized = false;

    function initListeners() {
        if (listenersInitialized) return;
        listenersInitialized = true;

        var prevBtn = document.getElementById('lvDetectPrev');
        var nextBtn = document.getElementById('lvDetectNext');
        var anonAllBtn = document.getElementById('lvAnonymizeAll');
        var listEl = document.getElementById('lvDetectList');
        var popEl = document.getElementById('lvDetectPopover');

        if (prevBtn) {
            prevBtn.addEventListener('click', function () { lvNav(-1); });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () { lvNav(1); });
        }
        if (anonAllBtn) {
            anonAllBtn.addEventListener('click', function () {
                var n = 0;
                if (!AppState.detections) return;
                AppState.detections.forEach(function (d) {
                    if (d._ignored || isDetectionDone(d)) return;
                    var repl = '';
                    try {
                        repl = generateFakeData(d.category, d.text) || '';
                    } catch (e) { }
                    if (!repl) {
                        repl = '[' + d.category.toUpperCase() + ']';
                    }
                    try {
                        addRule(d.text, repl, d.category);
                        n++;
                    } catch (e) { }
                });
                lvRenderList();
                lvSyncHighlight();
                showToast(n + ' règle(s) créée(s)', 'success');
            });
        }

        if (listEl) {
            listEl.addEventListener('click', function (e) {
                var jumpBtn = e.target.closest('.lv-detect-jump');
                if (jumpBtn) {
                    var jIndex = +jumpBtn.getAttribute('data-index');
                    var jItem = jumpBtn.closest('.lv-detect-item');
                    if (jItem && jItem.classList.contains('is-done')) { lvEditDetection(jIndex); return; }
                    lvSetActive(jIndex);
                    return;
                }
                var editBtn = e.target.closest('.lv-detect-edit');
                if (editBtn) {
                    lvEditDetection(+editBtn.getAttribute('data-index'));
                    return;
                }
                var anonBtn = e.target.closest('.lv-detect-anon');
                if (anonBtn) {
                    var aIndex = +anonBtn.getAttribute('data-index');
                    lvHidePopover();
                    addDetection(aIndex);
                    return;
                }
                var ignoreBtn = e.target.closest('.lv-detect-ignore');
                if (ignoreBtn) {
                    var iIndex = +ignoreBtn.getAttribute('data-index');
                    lvIgnore(iIndex);
                    return;
                }
                var undoBtn = e.target.closest('.lv-detect-undo');
                if (undoBtn) {
                    var uIndex = +undoBtn.getAttribute('data-index');
                    lvUndoDetection(uIndex);
                    return;
                }
            });
        }

        if (popEl) {
            popEl.addEventListener('click', function (e) {
                var actionBtn = e.target.closest('[data-pop-action]');
                if (!actionBtn) return;
                var action = actionBtn.getAttribute('data-pop-action');
                var index = +popEl.dataset.index;
                if (action === 'anonymize') {
                    lvHidePopover();
                    addDetection(index);
                } else if (action === 'ignore') {
                    lvIgnore(index);
                }
            });
        }

        document.addEventListener('click', handleDocumentClick);
        document.addEventListener('keydown', handleEscapeKey);

        // Clic sur une marque surlignée inline dans l'éditeur Tiptap → popover + activation
        document.addEventListener('click', function (e) {
            var mark = e.target.closest && e.target.closest('.anonym-detect[data-detect-index]');
            if (mark) {
                var idx = +mark.getAttribute('data-detect-index');
                lvShowPopover(idx, mark);   // positionne d'abord (mark encore valide)
                lvSetActive(idx, false);    // active sans scroller l'éditeur (marque déjà visible)
            }
        });
    }

    window.lvUpdateDetections = function (detections) {
        AppState.detections = (detections || []).map(function (d) {
            return {
                text: d.text,
                category: d.category,
                label: d.label,
                _ignored: false
            };
        });
        AppState.activeDetectIndex = -1;
        lvRenderList();
        lvShowPanel();
        lvSyncHighlight();

        var total = (AppState.detections || []).filter(function (d) { return !d._ignored; }).length;
        var pending = getPendingIndices().length;
        var done = total - pending;
        var msg, variant;
        if (total === 0) {
            msg = 'Aucune donnée sensible détectée';
            variant = 'warning';
        } else if (pending === 0) {
            msg = total + ' donnée(s) déjà anonymisée(s) ✓';
            variant = 'success';
        } else {
            msg = pending + ' à traiter' + (done > 0 ? ' · ' + done + ' déjà anonymisée(s)' : '');
            variant = 'success';
        }
        showToast(msg, variant);
    };

    window.lvMarkDetectionDone = lvMarkDetectionDone;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initListeners);
    } else {
        initListeners();
    }
})();
