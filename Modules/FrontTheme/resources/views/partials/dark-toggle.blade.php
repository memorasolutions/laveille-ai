@once
<style>
#lv-dark-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 8990;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--sys-surface-raised, #fff);
    color: var(--sys-text-default, #1A1D23);
    border: 1px solid var(--sys-border-default, #D1D5DB);
    box-shadow: var(--sys-shadow-md, 0 4px 6px -1px rgba(0,0,0,0.1));
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    line-height: 1;
    transition: background-color 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s;
}
#lv-dark-toggle:hover { box-shadow: var(--sys-shadow-lg, 0 10px 15px -3px rgba(0,0,0,0.15)); }
#lv-dark-toggle:focus-visible {
    outline: 3px solid var(--sys-focus-ring, #9A2A06);
    outline-offset: 2px;
}
@media (max-width: 640px) { #lv-dark-toggle { bottom: 70px; } }
@media print { #lv-dark-toggle { display: none; } }
</style>
@endonce

<button id="lv-dark-toggle" type="button" aria-pressed="false" aria-label="{{ __('Activer le mode sombre') }}">🌙</button>

@once
<script>
(function () {
    var btn = document.getElementById('lv-dark-toggle');
    if (!btn) return;
    function isDark() { return document.documentElement.getAttribute('data-theme') === 'dark'; }
    function render(dark) {
        btn.textContent = dark ? '☀️' : '🌙';
        btn.setAttribute('aria-pressed', String(dark));
        btn.setAttribute('aria-label', dark ? @json(__('Désactiver le mode sombre')) : @json(__('Activer le mode sombre')));
    }
    render(isDark());
    btn.addEventListener('click', function () {
        var dark = !isDark();
        if (dark) { document.documentElement.setAttribute('data-theme', 'dark'); }
        else { document.documentElement.removeAttribute('data-theme'); }
        try { localStorage.setItem('lv-theme', dark ? 'dark' : 'light'); } catch (e) {}
        render(dark);
    });
})();
</script>
@endonce
