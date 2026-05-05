{{--
    2026-05-05 #108 : helper slug réutilisable.
    Expose window.SlugHelper.slugify(str) globalement.
    Normalisation : NFD decompose accents, retire diacritiques, lowercase, espaces→tirets, alphanum-tirets only, collapse `--`, trim hyphens.
    DRY : remplace logique slug dupliquée dans plusieurs Alpine inline (mots-croises, futurs outils).
--}}
@once
<script>
(function() {
    if (window.SlugHelper) return;
    window.SlugHelper = {
        slugify: function(str) {
            if (!str || typeof str !== 'string') return '';
            return str
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-{2,}/g, '-')
                .replace(/^-+|-+$/g, '');
        },
    };
})();
</script>
@endonce
