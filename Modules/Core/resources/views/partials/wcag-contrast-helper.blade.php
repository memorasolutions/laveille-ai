{{--
    2026-05-05 #106 : helper WCAG contrast réutilisable.
    Expose window.WcagContrast.ratio(fgHex, bgHex) globalement (cas usage : QR personnalisé crossword, futurs outils).
    À @include dans n'importe quelle page nécessitant un calcul live de contraste.
    DRY : remplace logique relativeLuminance/luminance dupliquée dans plusieurs Alpine inline data.
--}}
@once
<script>
(function() {
    if (window.WcagContrast) return;
    function hexToRgb(hex) {
        if (typeof hex !== 'string') return [0, 0, 0];
        hex = hex.trim().replace(/^#/, '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        if (!/^[0-9a-fA-F]{6}$/.test(hex)) return [0, 0, 0];
        return [parseInt(hex.slice(0,2),16), parseInt(hex.slice(2,4),16), parseInt(hex.slice(4,6),16)];
    }
    function relativeLuminance(rgb) {
        var lin = function(c) { var s = c / 255; return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4); };
        return 0.2126 * lin(rgb[0]) + 0.7152 * lin(rgb[1]) + 0.0722 * lin(rgb[2]);
    }
    window.WcagContrast = {
        ratio: function(fgHex, bgHex) {
            try {
                var l1 = relativeLuminance(hexToRgb(fgHex));
                var l2 = relativeLuminance(hexToRgb(bgHex));
                var lighter = Math.max(l1, l2), darker = Math.min(l1, l2);
                return Math.round(((lighter + 0.05) / (darker + 0.05)) * 100) / 100;
            } catch (e) { return 1; }
        },
        level: function(fgHex, bgHex) {
            var r = this.ratio(fgHex, bgHex);
            if (r >= 7) return 'AAA';
            if (r >= 4.5) return 'AA';
            return 'FAIL';
        },
    };
})();
</script>
@endonce
