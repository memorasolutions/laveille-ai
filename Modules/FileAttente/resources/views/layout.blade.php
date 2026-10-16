{{-- MEMORA solutions - gabarit autonome du module FileAttente (exportable, aucune dépendance au thème, aucun script tiers) --}}
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
:root{--p:#064E5A;--p-dark:#032E36;--p-light:#F0FAFB;--a:#9A2A06;--ink:#1A1D23;--mut:#4B5563;--line:#D1D5DB;--ok:#14532D;}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--ink);background:#fff;line-height:1.5;font-size:1.0625rem}
main{max-width:860px;margin:0 auto;padding:1.25rem 1rem 3rem}
h1{color:var(--p);font-size:1.75rem;margin:.2rem 0 1rem}
h2{color:var(--p);font-size:1.25rem;margin:1.5rem 0 .5rem}
a{color:var(--p)}
label{display:block;font-weight:600;margin:.9rem 0 .25rem}
input[type=text],textarea,select{width:100%;padding:.7rem;border:2px solid var(--mut);border-radius:8px;font:inherit;color:var(--ink);background:#fff}
textarea{min-height:11rem}
:focus-visible{outline:3px solid var(--a);outline-offset:2px}
.btn{display:inline-block;min-height:48px;padding:.65rem 1.2rem;border:2px solid var(--p);border-radius:8px;background:var(--p);color:#fff;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;text-align:center}
.btn:hover{background:var(--p-dark)}
.btn.alt{background:#fff;color:var(--p)}
.btn.warn{background:var(--a);border-color:var(--a)}
.btn.sm{min-height:44px;min-width:44px;padding:.4rem .7rem}
.card{border:2px solid var(--line);border-radius:10px;padding:1rem;margin:1rem 0;background:var(--p-light)}
.err{color:#7F1D1D;background:#FEF2F2;border:2px solid #7F1D1D;border-radius:8px;padding:.6rem .8rem;margin:.8rem 0}
.ok{color:var(--ok);background:#F0FDF4;border:2px solid var(--ok);border-radius:8px;padding:.6rem .8rem;margin:.8rem 0}
.row{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center}
.muted{color:var(--mut);font-size:.95rem}
code.url{display:block;word-break:break-all;background:#fff;border:1px solid var(--line);padding:.5rem;border-radius:6px;font-size:.95rem}
ul.q{list-style:none;margin:0;padding:0}
ul.q li{display:flex;align-items:center;gap:.6rem;border:2px solid var(--line);border-radius:8px;padding:.5rem .7rem;margin:.4rem 0;background:#fff}
ul.q li[draggable=true]{cursor:grab}
ul.q li.called{border-color:var(--a);background:#FFF7ED}
ul.q li.over{outline:3px dashed var(--p)}
.pos{min-width:2.2rem;font-weight:800;color:var(--p)}
.grow{flex:1;min-width:0;overflow-wrap:anywhere}
.badge{font-size:.85rem;font-weight:700;color:#fff;background:var(--a);border-radius:99px;padding:.1rem .6rem}

.brand{border-bottom:3px solid var(--p);background:#fff}
.brand a{display:flex;align-items:center;gap:.6rem;max-width:860px;margin:0 auto;padding:.35rem 1rem;color:var(--p);text-decoration:none;font-weight:700;font-size:.95rem;line-height:1.2}
.brand img{height:24px;width:auto;display:block}
.hint{color:var(--mut);font-size:.9rem}
.info{color:#0C4A6E;background:#F0F9FF;border:2px solid #0C4A6E;border-radius:8px;padding:.6rem .8rem;margin:.8rem 0}
.grip{cursor:grab;color:var(--mut);font-size:1.4rem;line-height:1;user-select:none;flex:none;padding:0 .2rem}
.url-row{display:flex;gap:.6rem;align-items:stretch;flex-wrap:wrap}
.url-row code.url{flex:1;min-width:12rem;margin:0}
@media (prefers-reduced-motion:no-preference){.pulse{animation:pulse 1.2s ease-in-out 2}}
@keyframes pulse{50%{transform:scale(1.03)}}
</style>
@stack('head')
</head>
<body class="{{ $__env->hasSection('dark') ? 'fa-dark' : '' }}">
@include('fileattente::partials.brand', ['onDark' => $__env->hasSection('dark')])
<main class="@yield('main_class')">
@yield('content')
</main>
@stack('scripts')
</body>
</html>
