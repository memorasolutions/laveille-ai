<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="footer py-4 text-center">
    <div class="container-fluid">
        <p class="small text-muted mb-0">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Tous droits réservés.') }}
            <span style="opacity:0.55;font-size:0.75rem;margin-left:6px;font-variant-numeric:tabular-nums;" title="{{ __('Version applicative') }}">{{ function_exists('lv_version') ? lv_version() : 'v?' }}</span>
        </p>
    </div>
</div>
