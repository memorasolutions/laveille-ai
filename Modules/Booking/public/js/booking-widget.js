// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
(function() {
    var script = document.currentScript;
    var service = script.getAttribute('data-service') || '';
    var color = script.getAttribute('data-color') || '#0d6efd';
    var locale = script.getAttribute('data-locale') || 'fr';

    var origin = script.src.replace(/\/js\/booking-widget\.js.*$/, '');
    var iframe = document.createElement('iframe');
    iframe.src = origin + '/widget?service_id=' + service +
                 '&color=' + encodeURIComponent(color) +
                 '&locale=' + locale;
    iframe.style.cssText = 'width:100%;height:600px;border:none;border-radius:8px;';

    script.parentNode.insertBefore(iframe, script.nextSibling);

    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'booking-widget-resize') {
            iframe.style.height = (e.data.height || 600) + 'px';
        }
    });
})();
