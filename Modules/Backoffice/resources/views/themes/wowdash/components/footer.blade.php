<footer class="d-footer">
    <div class="row align-items-center justify-content-between">
        <div class="col-auto">
            <p class="mb-0">
                @if(! empty($branding['footer_text'] ?? ''))
                    {{ str_replace(['{year}', '{app_name}'], [date('Y'), $branding['site_name'] ?? config('app.name')], $branding['footer_text']) }}
                @else
                    &copy; {{ date('Y') }} {{ $branding['site_name'] ?? config('app.name') }}. Tous droits réservés.
                @endif
            </p>
        </div>
        <div class="col-auto">
            <p class="mb-0">
                @if(! empty($branding['footer_right'] ?? ''))
                    {{ str_replace(['{version}', '{php_version}'], [app()->version(), PHP_VERSION], $branding['footer_right']) }}
                @else
                    Laravel v{{ app()->version() }} - PHP v{{ PHP_VERSION }}
                @endif
            </p>
        </div>
    </div>
</footer>
