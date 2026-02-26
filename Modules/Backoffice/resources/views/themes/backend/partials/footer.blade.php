<footer class="footer d-flex flex-column flex-md-row align-items-center justify-content-between px-4 py-3 border-top small">
    <p class="text-secondary mb-1 mb-md-0">Copyright &copy; {{ date('Y') }} <a href="{{ url('/') }}">{{ $branding['site_name'] ?? config('app.name') }}</a>.</p>
    <p class="text-secondary">{{ __('Propulsé par') }} <a href="https://laravel.com" target="_blank" rel="noopener">Laravel</a> v{{ app()->version() }}</p>
</footer>
