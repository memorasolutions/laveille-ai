<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<footer class="footer d-flex flex-column flex-md-row align-items-center justify-content-between px-4 py-3 border-top small">
    <p class="text-secondary mb-1 mb-md-0">Copyright &copy; {{ date('Y') }} <a href="{{ url('/') }}">{{ $branding['site_name'] ?? config('app.name') }}</a>.</p>
    <p class="text-secondary mb-0">
        {{ __('Conçu et hébergé au Canada par') }} <a href="https://memora.solutions" target="_blank" rel="noopener">MEMORA solutions</a>
        <span class="text-muted ms-2" style="opacity:0.55;font-size:0.75rem;font-variant-numeric:tabular-nums;" title="{{ __('Version applicative') }}">{{ function_exists('lv_version') ? lv_version() : 'v?' }}</span>
    </p>
</footer>
