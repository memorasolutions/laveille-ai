{{-- MEMORA solutions - styles spécifiques à la file d'attente (liste, rangs, poignées, bandeau « appelé »). Tout le reste vient des composants du thème (ct-btn, card, form-control, alert). Préfixés .fa. --}}
<style>
.fa ul.q{list-style:none;margin:0;padding:0}
.fa ul.q li{display:flex;align-items:center;gap:.6rem;border:2px solid #D1D5DB;border-radius:var(--r-base,.75rem);padding:.5rem .7rem;margin:.4rem 0;background:#fff}
.fa ul.q li[draggable=true]{cursor:grab}
.fa ul.q li.called{border-color:#9A2A06;background:#FFF7ED}
.fa ul.q li.over{outline:3px dashed var(--c-primary,#064E5A)}
.fa .pos{min-width:2.2rem;font-weight:800;color:var(--c-primary,#064E5A)}
.fa .grow{flex:1;min-width:0;overflow-wrap:anywhere}
.fa .badge-called{font-size:.85rem;font-weight:700;color:#fff;background:#9A2A06;border-radius:99px;padding:.1rem .6rem}
.fa .grip{cursor:grab;color:#4B5563;font-size:1.4rem;line-height:1;user-select:none;flex:none;padding:0 .2rem}
.fa code.url{display:block;word-break:break-all;background:#fff;color:var(--c-dark,#1A1D23);border:1px solid #D1D5DB;padding:.5rem;border-radius:6px;font-size:.95rem}
.fa .url-row{display:flex;gap:.6rem;align-items:stretch;flex-wrap:wrap}
.fa .url-row code.url{flex:1;min-width:12rem;margin:0}
.fa .ct-btn{min-height:44px;min-width:44px;justify-content:center}
@media (prefers-reduced-motion:no-preference){.fa .pulse{animation:fa-pulse 1.2s ease-in-out 2}}
@keyframes fa-pulse{50%{transform:scale(1.03)}}
</style>
