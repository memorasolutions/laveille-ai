<footer class="footer footer-transparent d-print-none">
    <div class="container-xl">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-lg-auto ms-lg-auto">
                @php
                    $footerRight = \Modules\Settings\Models\Setting::get('branding.footer_right', '');
                @endphp
                @if($footerRight)
                    {!! str_replace(['{version}', '{php_version}'], [app()->version(), PHP_VERSION], $footerRight) !!}
                @else
                    <span class="text-secondary">Laravel v{{ app()->version() }} - PHP v{{ PHP_VERSION }}</span>
                @endif
            </div>
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                @php
                    $footerText = \Modules\Settings\Models\Setting::get('branding.footer_text', '');
                    $appName = \Modules\Settings\Models\Setting::get('branding.site_name', config('app.name'));
                @endphp
                @if($footerText)
                    {!! str_replace(['{year}', '{app_name}'], [date('Y'), $appName], $footerText) !!}
                @else
                    <span class="text-secondary">&copy; {{ date('Y') }} {{ $appName }}. Tous droits réservés.</span>
                @endif
            </div>
        </div>
    </div>
</footer>
