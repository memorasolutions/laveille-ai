{{-- MEMORA solutions - styles du module FileAttente, TOUS préfixés .fa pour ne rien heurter dans le gabarit du site (Bootstrap : .btn, .card, .row). --}}
<style>
.fa{--p:#064E5A;--p-dark:#032E36;--p-light:#F0FAFB;--a:#9A2A06;--ink:#1A1D23;--mut:#4B5563;--line:#D1D5DB;--ok:#14532D;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--ink);line-height:1.5;font-size:1.0625rem}
.fa *,.fa *::before,.fa *::after{box-sizing:border-box}
.fa-wrap{max-width:860px;margin:0 auto;padding:1.25rem 1rem 3rem}
.fa h1{color:var(--p);font-size:1.75rem;margin:.2rem 0 1rem}
.fa h2{color:var(--p);font-size:1.25rem;margin:1.5rem 0 .5rem}
.fa a{color:var(--p)}
.fa label{display:block;font-weight:600;margin:.9rem 0 .25rem}
.fa input[type=text],.fa textarea,.fa select{width:100%;padding:.7rem;border:2px solid var(--mut);border-radius:8px;font:inherit;color:var(--ink);background:#fff}
.fa textarea{min-height:11rem}
.fa :focus-visible{outline:3px solid var(--a);outline-offset:2px}
.fa .btn{display:inline-block;min-height:48px;padding:.65rem 1.2rem;border:2px solid var(--p);border-radius:8px;background:var(--p);color:#fff;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;text-align:center}
.fa .btn:hover{background:var(--p-dark);color:#fff}
.fa .btn.alt{background:#fff;color:var(--p)}
.fa .btn.warn{background:var(--a);border-color:var(--a);color:#fff}
.fa .btn.sm{min-height:44px;min-width:44px;padding:.4rem .7rem}
.fa .card{display:block;border:2px solid var(--line);border-radius:10px;padding:1rem;margin:1rem 0;background:var(--p-light)}
.fa .err{color:#7F1D1D;background:#FEF2F2;border:2px solid #7F1D1D;border-radius:8px;padding:.6rem .8rem;margin:.8rem 0}
.fa .ok{color:var(--ok);background:#F0FDF4;border:2px solid var(--ok);border-radius:8px;padding:.6rem .8rem;margin:.8rem 0}
.fa .row{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin-left:0;margin-right:0}
.fa .muted{color:var(--mut);font-size:.95rem}
.fa code.url{display:block;word-break:break-all;background:#fff;color:var(--ink);border:1px solid var(--line);padding:.5rem;border-radius:6px;font-size:.95rem}
.fa ul.q{list-style:none;margin:0;padding:0}
.fa ul.q li{display:flex;align-items:center;gap:.6rem;border:2px solid var(--line);border-radius:8px;padding:.5rem .7rem;margin:.4rem 0;background:#fff}
.fa ul.q li[draggable=true]{cursor:grab}
.fa ul.q li.called{border-color:var(--a);background:#FFF7ED}
.fa ul.q li.over{outline:3px dashed var(--p)}
.fa .pos{min-width:2.2rem;font-weight:800;color:var(--p)}
.fa .grow{flex:1;min-width:0;overflow-wrap:anywhere}
.fa .badge{font-size:.85rem;font-weight:700;color:#fff;background:var(--a);border-radius:99px;padding:.1rem .6rem}
.fa .hint{color:var(--mut);font-size:.9rem}
.fa .info{color:#0C4A6E;background:#F0F9FF;border:2px solid #0C4A6E;border-radius:8px;padding:.6rem .8rem;margin:.8rem 0}
.fa .grip{cursor:grab;color:var(--mut);font-size:1.4rem;line-height:1;user-select:none;flex:none;padding:0 .2rem}
.fa .url-row{display:flex;gap:.6rem;align-items:stretch;flex-wrap:wrap}
.fa .url-row code.url{flex:1;min-width:12rem;margin:0}
@media (prefers-reduced-motion:no-preference){.fa .pulse{animation:fa-pulse 1.2s ease-in-out 2}}
@keyframes fa-pulse{50%{transform:scale(1.03)}}
</style>
