(function () {
    'use strict';

    var COOKIE_DISMISSED = 'nl_scroll_dismissed';
    var COOKIE_SUB = 'nl_subscribed';
    var TRIGGER_SCROLL = 0.5;
    var TRIGGER_TIME_MS = 60000;
    var SESSION_KEY = 'nl_scroll_shown';

    var shown = false;
    var panel = null;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 86400000);
        document.cookie = name + '=' + encodeURIComponent(value) +
            ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
    }

    function isBlockedPage() {
        return /^\/(admin|login|magic-link|boutique\/(panier|paiement|commander|confirmation))/.test(
            window.location.pathname
        );
    }

    function canShow() {
        if (getCookie(COOKIE_DISMISSED) || getCookie(COOKIE_SUB)) return false;
        if (document.body.getAttribute('data-user-id')) return false;
        if (isBlockedPage()) return false;
        if (sessionStorage.getItem(SESSION_KEY)) return false;
        return true;
    }

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function show() {
        if (shown || !panel) return;
        shown = true;
        sessionStorage.setItem(SESSION_KEY, '1');

        if (prefersReducedMotion()) {
            panel.style.transform = 'translateY(0)';
            panel.style.transition = 'none';
            panel.style.display = 'block';
        } else {
            panel.style.display = 'block';
            setTimeout(function () {
                panel.style.transform = 'translateY(0)';
            }, 10);
        }

        var emailInput = document.getElementById('newsletterScrollEmail');
        if (emailInput) emailInput.focus();
    }

    function hide() {
        if (!panel) return;

        if (prefersReducedMotion()) {
            panel.style.display = 'none';
            return;
        }

        panel.style.transform = 'translateY(120%)';
        setTimeout(function () {
            panel.style.display = 'none';
        }, 400);
    }

    function showMessage(text, isSuccess) {
        var msg = document.getElementById('newsletterScrollMessage');
        if (!msg) return;
        msg.textContent = text;
        msg.className = '';
        msg.style.background = isSuccess ? '#d4edda' : '#f8d7da';
        msg.style.color = isSuccess ? '#155724' : '#721c24';
    }

    function hideMessage() {
        var msg = document.getElementById('newsletterScrollMessage');
        if (!msg) return;
        msg.textContent = '';
        msg.className = 'd-none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        panel = document.getElementById('newsletterScrollTrigger');
        if (!panel) return;
        if (!canShow()) return;

        var scrollTriggered = false;
        window.addEventListener('scroll', function () {
            if (scrollTriggered || shown) return;
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            if (docHeight > 0 && (scrollTop / docHeight) >= TRIGGER_SCROLL) {
                scrollTriggered = true;
                show();
            }
        }, { passive: true });

        setTimeout(function () {
            if (!shown) show();
        }, TRIGGER_TIME_MS);

        var closeBtn = document.getElementById('newsletterScrollClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                hide();
                setCookie(COOKIE_DISMISSED, '1', 90);
            });
        }

        var form = document.getElementById('newsletterScrollForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideMessage();

            var submitBtn = document.getElementById('newsletterScrollSubmit');
            if (submitBtn) submitBtn.disabled = true;

            var metaToken = document.querySelector('meta[name="csrf-token"]');
            var token = metaToken ? metaToken.getAttribute('content') : '';

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function (response) {
                if (submitBtn) submitBtn.disabled = false;

                if (response.ok) {
                    setCookie(COOKIE_SUB, '1', 365);
                    showMessage('Inscription confirmée, merci !', true);
                    setTimeout(hide, 3000);
                    return;
                }

                if (response.status === 422) {
                    showMessage('Adresse courriel invalide.', false);
                } else if (response.status === 429) {
                    showMessage('Trop de tentatives, réessaie plus tard.', false);
                } else {
                    showMessage('Une erreur est survenue, réessaie.', false);
                }
            })
            .catch(function () {
                if (submitBtn) submitBtn.disabled = false;
                showMessage('Erreur réseau, vérifie ta connexion.', false);
            });
        });
    });
})();
