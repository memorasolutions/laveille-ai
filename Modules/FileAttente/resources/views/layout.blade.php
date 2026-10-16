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
