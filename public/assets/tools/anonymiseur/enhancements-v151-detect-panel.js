/**
 * enhancements-v151-detect-panel.js — Sprint S131, Option A (hybride)
 * Panneau latéral des détections PII (groupé par catégorie + compteurs + navigation ◀▶)
 * synchronisé avec le surlignage inline Tiptap (window.anonymiseur*) + popover d'action
 * + retrait direct sans popup (Ignorer). Chargé en classic <script> APRÈS app.js.
 * Globals déjà disponibles : AppState, addDetection, addRule, generateFakeData,
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

    function getPendingIndices() {
        var pending = [];
        if (!AppState.detections) return pending;
        AppState.detections.forEach(function (d, i) {
            if (!d._ignored && !d._done) {
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
        var pendingIndices = getPendingIndices();
        var pendingCount = pendingIndices.length;

        countEl.textContent = pendingCount;

        if (pendingCount === 0) {
            emptyEl.classList.remove('hidden');
            listEl.innerHTML = '';
            return;
        }

        emptyEl.classList.add('hidden');

        var groups = {};
        var groupOrder = [];

        pendingIndices.forEach(function (i) {
            var d = detections[i];
            if (!groups[d.category]) {
                groups[d.category] = [];
                groupOrder.push(d.category);
            }
            groups[d.category].push({ index: i, detection: d });
        });

        var html = '';
        groupOrder.forEach(function (cat) {
            var items = groups[cat];
            var label = categoryLabels[cat] || cat;
            var icon = getCategoryIcon(cat);
            var count = items.length;

            html += '<div class="lv-detect-group" data-category="' + escapeHtml(cat) + '">' +
                '<div class="lv-detect-grouphead">' +
                '<span class="lv-detect-gico" aria-hidden="true">' + escapeHtml(icon) + '</span>' +
                escapeHtml(label) +
                '<span class="lv-detect-gcount">' + count + '</span>' +
                '</div>' +
                '<div class="lv-detect-items" role="list">';

            items.forEach(function (item) {
                var d = item.detection;
                var escapedText = escapeHtml(d.text);
                html += '<div class="lv-detect-item" role="listitem" data-index="' + item.index + '" data-category="' + escapeHtml(cat) + '">' +
                    '<button type="button" class="lv-detect-jump" data-index="' + item.index + '" title="Voir dans le texte">' +
                    '<span class="lv-detect-swatch" aria-hidden="true"></span>' +
                    '<span class="lv-detect-text">' + escapedText + '</span>' +
                    '</button>' +
                    '<span class="lv-detect-actions">' +
                    '<button type="button" class="lv-detect-anon" data-index="' + item.index + '" title="Anonymiser" aria-label="Anonymiser ' + escapedText + '">＋</button>' +
                    '<button type="button" class="lv-detect-ignore" data-index="' + item.index + '" title="Ignorer" aria-label="Ignorer ' + escapedText + '">×</button>' +
                    '</span>' +
                    '</div>';
            });

            html += '</div></div>';
        });

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
            if (d._ignored || d._done) {
                return { text: '', category: d.category };
            }
            return { text: d.text, category: d.category };
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

    function lvMarkDetectionDone(index) {
        if (!AppState.detections || !AppState.detections[index]) return;
        AppState.detections[index]._done = true;
        lvRenderList();
        lvSyncHighlight();
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

    function handleScroll() {
        lvHidePopover();
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
                AppState.detections.forEach(function (d, i) {
                    if (d._ignored || d._done) return;
                    var repl = '';
                    try {
                        repl = generateFakeData(d.category, d.text) || '';
                    } catch (e) { }
                    if (!repl) {
                        repl = '[' + d.category.toUpperCase() + ']';
                    }
                    try {
                        addRule(d.text, repl, d.category);
                        d._done = true;
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
                    lvSetActive(jIndex);
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
        // sans capture : n'attrape que le scroll de page, pas les scrolls internes (sidebar/éditeur)
        window.addEventListener('scroll', handleScroll);

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
                _ignored: false,
                _done: false
            };
        });
        AppState.activeDetectIndex = -1;
        lvRenderList();
        lvShowPanel();
        lvSyncHighlight();

        var pending = getPendingIndices().length;
        showToast(
            pending > 0 ? pending + ' donnée(s) sensible(s) détectée(s)' : 'Aucune donnée sensible détectée',
            pending > 0 ? 'success' : 'warning'
        );
    };

    window.lvMarkDetectionDone = lvMarkDetectionDone;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initListeners);
    } else {
        initListeners();
    }
})();
