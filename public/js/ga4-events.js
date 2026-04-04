/**
 * GA4 custom events — scroll_depth, outbound_link, form_submit, error_404
 * @author MEMORA solutions <info@memora.ca>
 */
document.addEventListener('DOMContentLoaded', function () {
  if (typeof gtag === 'undefined') return;

  // 1. Scroll depth (25/50/75/90%)
  var scrollFired = {};
  var thresholds = [25, 50, 75, 90];

  window.addEventListener('scroll', function () {
    var scrollHeight = document.documentElement.scrollHeight;
    var scrollTop = document.documentElement.scrollTop;
    var clientHeight = document.documentElement.clientHeight;
    var pct = Math.round(((scrollTop + clientHeight) / scrollHeight) * 100);

    thresholds.forEach(function (t) {
      if (pct >= t && !scrollFired[t]) {
        gtag('event', 'scroll_depth', { percent_scrolled: t });
        scrollFired[t] = true;
      }
    });
  }, { passive: true });

  // 2. Outbound links
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (link && link.href) {
      try {
        var url = new URL(link.href);
        if (url.hostname !== location.hostname) {
          gtag('event', 'outbound_link', {
            link_url: link.href,
            link_text: (link.textContent || '').trim().substring(0, 100)
          });
        }
      } catch (err) {}
    }
  }, true);

  // 3. Form submit
  document.addEventListener('submit', function (e) {
    if (e.target.tagName === 'FORM') {
      gtag('event', 'form_submit', {
        form_id: e.target.id || '',
        form_name: e.target.name || ''
      });
    }
  }, true);

  // 4. Error 404
  var title = document.title.toLowerCase();
  if (title.indexOf('introuvable') !== -1 || title.indexOf('404') !== -1) {
    gtag('event', 'error_404', { page_location: location.href });
  }
});
