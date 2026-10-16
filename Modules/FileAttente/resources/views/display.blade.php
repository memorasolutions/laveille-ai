@extends('fileattente::layout')
@section('title', $board->title ?: __('fileattente::ui.display.default_title'))
@section('dark', '1')
@push('styles')
@include('fileattente::partials.styles')
@endpush
@section('main_class', 'wide')
@push('head')
<style>
/* Écran de projection : pleine largeur, fond sombre, contraste fort, texte lisible du fond d'une salle. */
html,body{min-height:100%}
body.fa-dark{background:#032E36;color:#fff}
.fa-dark .brand{background:transparent;border-bottom:2px solid #064E5A}
.fa-dark .brand a{max-width:none;margin:0;padding:.3rem 1rem;color:#CFE8EC;font-size:.8rem;font-weight:600}
.fa-dark .brand img{height:18px}
main.wide{max-width:none;margin:0;padding:1rem clamp(1rem,3vw,3rem) 2.5rem}
.fa-head{display:flex;justify-content:space-between;align-items:baseline;gap:1rem 2rem;flex-wrap:wrap;margin:0 0 1rem}
.fa-dark h1{color:#fff;font-size:clamp(1.75rem,3.6vw,3.5rem);line-height:1.1;margin:0}
.fa-count{font-size:clamp(1.25rem,2.4vw,2.25rem);font-weight:700;color:#A7F3D0;margin:0}
.fa-called{background:#FCD34D;color:#032E36;border-radius:20px;padding:clamp(1rem,3vw,2.5rem);margin:0 0 1.5rem;text-align:center}
.fa-called-line{font-size:clamp(2rem,4.5vw,4.5rem);font-weight:800;line-height:1.1}
.fa-called-name{font-size:clamp(3.5rem,10vw,10rem);font-weight:900;line-height:1.05;overflow-wrap:anywhere}
.fa-called-name + .fa-called-line,.fa-called-line + .fa-called-name{margin-top:.2em}
.fa-called-item + .fa-called-item{margin-top:1.5rem;padding-top:1.5rem;border-top:4px solid #032E36}
.fa-dark h2{color:#CFE8EC;font-size:clamp(1.25rem,2vw,2rem);margin:0 0 .75rem}
.fa-dark ul.q{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,26rem),1fr));gap:.6rem 1.5rem}
.fa-dark ul.q li{margin:0;padding:.35em .8em;font-size:clamp(1.9rem,3.2vw,3.25rem);line-height:1.15;font-weight:700;background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.18);color:#fff;gap:.8em}
.fa-dark .pos{min-width:1.8em;text-align:center;color:#032E36;background:#A7F3D0;border-radius:.4em;padding:0 .15em;font-weight:900}
.fa-dark .muted{color:#CFE8EC;font-size:clamp(1.25rem,2.2vw,2rem)}
.fa-dark .card{background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.25);color:#fff;max-width:56rem}
.fa-dark .card h2{margin-top:0}
.fa-dark .btn{background:#fff;color:#032E36;border-color:#fff;font-size:1.25rem}
.fa-dark .btn:hover{background:#CFE8EC}
.fa-dark .btn[disabled]{opacity:.7;cursor:wait}
.fa-dark .ok{background:#052E16;color:#BBF7D0;border-color:#BBF7D0;font-size:1.25rem}
.fa-dark #fa-cam-msg{font-size:1.25rem}
@media (max-width:640px){.fa-dark ul.q{grid-template-columns:1fr}}
@media (min-aspect-ratio:16/9) and (min-width:1400px){.fa-dark ul.q{grid-template-columns:repeat(auto-fill,minmax(32rem,1fr))}}
</style>
@endpush
@section('content')
<div class="fa-head">
  <h1 id="fa-title">{{ $board->title ?: __('fileattente::ui.display.default_title') }}</h1>
  <p id="fa-count" class="fa-count" style="display:none" role="status" aria-live="polite"></p>
</div>

<div id="fa-called" class="fa-called" style="display:none" role="status" aria-live="assertive"></div>
<h2>{{ __('fileattente::ui.display.in_queue') }}</h2>
<ul class="q" id="fa-list" aria-live="polite"></ul>
<p id="fa-empty" class="muted" style="display:none">{{ __('fileattente::ui.display.nobody') }}</p>
<p id="fa-gone" class="err" style="display:none" role="alert">{{ __('fileattente::ui.display.over') }}</p>

@if ($board->scan_mode === 'borne')
<div class="card" id="fa-borne">
  <h2>{{ __('fileattente::ui.display.kiosk_heading') }}</h2>
  <p id="fa-cam-msg" class="muted" role="status" aria-live="polite">{{ __('fileattente::ui.display.cam_off') }}</p>
  <video id="fa-video" playsinline muted style="display:none;width:100%;max-width:520px;border-radius:8px"></video>
  <p><button class="btn" type="button" id="fa-cam-btn">{{ __('fileattente::ui.display.cam_on') }}</button></p>
  <p id="fa-scan-msg" class="ok" style="display:none" role="status" aria-live="polite"></p>
</div>
@endif
@endsection
@push('scripts')
@include('fileattente::partials.poll')
<script>
(function(){
  var L=@json(__('fileattente::ui.display.js'));
  function fmt(s,r){Object.keys(r).forEach(function(x){s=s.split(':'+x).join(r[x]);});return s;}
  var list=document.getElementById('fa-list'),empty=document.getElementById('fa-empty'),called=document.getElementById('fa-called'),count=document.getElementById('fa-count');
  function div(cls,txt){var d=document.createElement('div');d.className=cls;d.textContent=txt;return d;}
  function render(d){
    list.textContent='';called.textContent='';var c=[],w=[];
    d.items.forEach(function(it){(it.status==='called'?c:w).push(it);});
    c.forEach(function(it){
      var h=document.createElement('div');h.className='fa-called-item pulse';
      h.appendChild(div('fa-called-line',L.your_turn));
      h.appendChild(div('fa-called-name',it.label));
      called.appendChild(h);
    });
    called.style.display=c.length?'block':'none';
    w.forEach(function(it){
      var li=document.createElement('li');
      var p=document.createElement('span');p.className='pos';p.textContent=it.position;
      var n=document.createElement('span');n.className='grow';n.textContent=it.label;
      li.appendChild(p);li.appendChild(n);list.appendChild(li);
    });
    count.style.display=w.length?'block':'none';
    count.textContent=w.length?fmt(w.length>1?L.waiting_many:L.waiting_one,{count:w.length}):'';
    empty.style.display=(c.length||w.length)?'none':'block';
  }
  var init=@json($state);render(init);
  faPoll({url:@json(route('fileattente.display.etat', $token)),version:init.version,interval:@json($cadence),onData:render,
    onGone:function(){document.getElementById('fa-gone').style.display='block';}});

  @if ($board->scan_mode === 'borne')
  var btn=document.getElementById('fa-cam-btn'),msg=document.getElementById('fa-cam-msg'),video=document.getElementById('fa-video'),out=document.getElementById('fa-scan-msg');
  var recent={},started=false;
  function note(t){out.textContent=t;out.style.display='block';setTimeout(function(){out.style.display='none';},4000);}
  function idle(){btn.disabled=false;btn.removeAttribute('aria-busy');}
  btn.addEventListener('click',function(){
    if(started){return;}
    // Retour immédiat, avant toute attente de permission : l'interface ne se bloque jamais.
    if(!('BarcodeDetector' in window)){msg.textContent=L.unsupported;return;}
    if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){msg.textContent=L.no_camera;return;}
    msg.textContent=L.cam_asking;btn.disabled=true;btn.setAttribute('aria-busy','true');
    navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'},audio:false}).then(function(stream){
      started=true;
      video.srcObject=stream;video.style.display='block';
      var pl=video.play();if(pl&&pl.catch){pl.catch(function(){});}
      btn.style.display='none';msg.textContent=L.cam_active;
      var det=new BarcodeDetector({formats:['qr_code']}),busy=false;
      setInterval(function(){
        if(busy||document.hidden||video.readyState<2){return;}
        busy=true;
        det.detect(video).then(function(codes){
          codes.forEach(function(c){
            var m=/\/f\/([A-Za-z0-9]{20,64})(?:[\/?#]|$)/.exec(c.rawValue||'');
            if(!m){return;}
            var tok=m[1],now=Date.now();
            if(recent[tok]&&now-recent[tok]<6000){return;}
            recent[tok]=now;
            faPost('/f/'+tok+'/join',{}).then(function(r){return r.ok?r.json():null;}).then(function(j){
              if(j&&j.ok){note(fmt(L.welcome,{label:j.label}));}else{note(L.unknown_card);}
            }).catch(function(){note(L.network);});
          });
        }).catch(function(){}).then(function(){busy=false;});
      },400);
    }).catch(function(e){
      msg.textContent=(e&&(e.name==='NotAllowedError'||e.name==='SecurityError'))?L.denied:L.no_camera;
      idle();
    });
  });
  @endif
})();
</script>
@endpush
