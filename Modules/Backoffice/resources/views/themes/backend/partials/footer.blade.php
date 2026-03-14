<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<footer class="footer d-flex flex-column flex-md-row align-items-center justify-content-between px-4 py-3 border-top small">
    <p class="text-secondary mb-1 mb-md-0">Copyright &copy; {{ date('Y') }} <a href="{{ url('/') }}">{{ $branding['site_name'] ?? config('app.name') }}</a>.</p>
    <p class="text-secondary">{{ __('Conçu et hébergé au Canada par') }} <a href="https://memora.solutions" target="_blank" rel="noopener">MEMORA solutions</a></p>
</footer>
