(function ($, window, document) {
  'use strict';

  var MODAL_SEL = '#newsletterModal';
  var COOKIE_SUB = 'nl_subscribed';
  var SS_DISMISSED = 'nl_dismissed';
  var SS_SHOWN = 'nl_shown';

  function safeSessionGet(key) {
    try { return window.sessionStorage.getItem(key); } catch (e) { return null; }
  }
  function safeSessionSet(key, val) {
    try { window.sessionStorage.setItem(key, val); } catch (e) {}
  }

  function getCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  }
  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
  }

  function isBlockedPage() {
    var p = (window.location.pathname || '').toLowerCase();
    if (/\/(admin|login|magic-link|boutique\/panier|boutique\/paiement|boutique\/commander|boutique\/confirmation)(\/|$)/.test(p)) return true;
    return false;
  }

  function canShow() {
    if (!document.querySelector(MODAL_SEL)) return false;
    if (getCookie(COOKIE_SUB)) return false;
    if (safeSessionGet(SS_DISMISSED) === 'true') return false;
    if (safeSessionGet(SS_SHOWN) === 'true') return false;
    if (document.querySelector('[data-user-id]')) return false;
    if (isBlockedPage()) return false;
    return true;
  }

  function showModal() {
    if (!canShow()) return;
    var el = document.querySelector(MODAL_SEL);
    if (!el) return;
    el.removeAttribute('inert');
    safeSessionSet(SS_SHOWN, 'true');
    $(MODAL_SEL).modal('show');
  }

  function init() {
    if (!document.querySelector(MODAL_SEL)) return;

    // Quand la modale se ferme → marquer dismissed
    $(document).on('hidden.bs.modal', MODAL_SEL, function () {
      safeSessionSet(SS_DISMISSED, 'true');
    });

    // Quand inscription réussie → cookie 365 jours
    $(document).on('newsletter:subscribed', function () { setCookie(COOKIE_SUB, '1', 365); });
    var modalEl = document.querySelector(MODAL_SEL);
    if (modalEl && window.MutationObserver) {
      new MutationObserver(function () {
        if (modalEl.querySelector('.alert-success')) setCookie(COOKIE_SUB, '1', 365);
      }).observe(modalEl, { childList: true, subtree: true });
    }

    if (!canShow()) return;

    var isMobile = (window.innerWidth || 0) <= 768;

    if (isMobile) {
      // Mobile : scroll 50% + 30 secondes minimum
      var timeOk = false, scrollOk = false, done = false;
      function check() { if (!done && timeOk && scrollOk && canShow()) { done = true; showModal(); } }
      setTimeout(function () { timeOk = true; check(); }, 30000);
      window.addEventListener('scroll', function () {
        if (done) return;
        var ratio = window.pageYOffset / Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
        if (ratio >= 0.5) { scrollOk = true; check(); }
      }, { passive: true });
    } else {
      // Desktop : exit-intent (souris sort par le haut)
      var fired = false;
      document.addEventListener('mouseleave', function (e) {
        if (fired || !canShow()) return;
        if (e.clientY <= 0) { fired = true; showModal(); }
      }, true);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})(window.jQuery, window, document);
