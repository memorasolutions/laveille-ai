{{-- MEMORA solutions - gabarit autonome du module FileAttente (projection plein écran + repli d'export sans thème ; aucun script tiers). Les styles vivent dans partials/styles, poussés par chaque vue. --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('fileattente::ui.app_title'))</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#fff}
.brand{border-bottom:3px solid #064E5A;background:#fff}
.brand a{display:flex;align-items:center;gap:.6rem;max-width:860px;margin:0 auto;padding:.35rem 1rem;color:#064E5A;text-decoration:none;font-weight:700;font-size:.95rem;line-height:1.2}
.brand img{height:24px;width:auto;display:block}
</style>
{{-- Repli d'export (sans thème) : le strict minimum pour que les composants du site (ct-btn, card, form-control, alert) restent lisibles. Sur le site, charte.css prend le relais. --}}
<style>
body:not(.fa-dark){font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1A1D23;line-height:1.5;font-size:1.0625rem}
body:not(.fa-dark) .container{max-width:860px;margin:0 auto;padding:1.25rem 1rem 3rem}
body:not(.fa-dark) .card{border:1px solid #D1D5DB;border-radius:.75rem;background:#fff}
body:not(.fa-dark) .card-body{padding:1.5rem}
body:not(.fa-dark) h1,body:not(.fa-dark) h2{color:#064E5A}
body:not(.fa-dark) .ct-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:2px solid transparent;border-radius:8px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;background:transparent;color:#1A1D23}
body:not(.fa-dark) .ct-btn-primary{background:#064E5A;color:#fff}
body:not(.fa-dark) .ct-btn-outline{border-color:#064E5A;color:#064E5A}
body:not(.fa-dark) .ct-btn-danger{background:#9A2A06;color:#fff}
body:not(.fa-dark) .ct-btn-outline-danger{border-color:#9A2A06;color:#9A2A06}
body:not(.fa-dark) .form-control,body:not(.fa-dark) .form-select{display:block;width:100%;padding:.7rem;border:2px solid #4B5563;border-radius:8px;font:inherit}
body:not(.fa-dark) .form-label{display:block;font-weight:600;margin:.9rem 0 .25rem}
body:not(.fa-dark) .alert{padding:.6rem .8rem;border:2px solid;border-radius:8px;margin:.8rem 0}
body:not(.fa-dark) .alert-info{color:#0C4A6E;background:#F0F9FF}
body:not(.fa-dark) .alert-danger{color:#7F1D1D;background:#FEF2F2}
body:not(.fa-dark) .alert-success{color:#14532D;background:#F0FDF4}
body:not(.fa-dark) .text-muted{color:#4B5563}
body:not(.fa-dark) :focus-visible{outline:3px solid #9A2A06;outline-offset:2px}
</style>
@stack('styles')
@stack('head')
</head>
<body class="{{ $__env->hasSection('dark') ? 'fa-dark' : '' }}">
@include('fileattente::partials.brand', ['onDark' => $__env->hasSection('dark')])
<main class="fa @yield('main_class')">
@yield('content')
</main>
@stack('scripts')
</body>
</html>
