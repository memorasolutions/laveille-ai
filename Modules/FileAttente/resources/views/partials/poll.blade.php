{{-- Interrogation versionnée : une requête à la fois, pause si l'onglet est caché, temporisation après erreur. --}}
<script>
function faPoll(o){
  var v=o.version,busy=false,fails=0,timer=null,base=o.interval*1000;
  function schedule(){clearTimeout(timer);timer=setTimeout(tick,base*Math.min(1+fails,6));}
  function tick(){
    if(busy||document.hidden){schedule();return;}
    busy=true;
    fetch(o.url+'?v='+encodeURIComponent(v),{headers:{'Accept':'application/json'},credentials:'same-origin',cache:'no-store'})
      .then(function(r){
        if(r.status===204){fails=0;return null;}
        if(r.status===404){if(o.onGone){o.onGone();}return null;}
        if(!r.ok){throw new Error(r.status);}
        return r.json();
      })
      .then(function(d){fails=0;if(d){v=d.version;o.onData(d);}})
      .catch(function(){fails++;})
      .then(function(){busy=false;schedule();});
  }
  document.addEventListener('visibilitychange',function(){if(!document.hidden){clearTimeout(timer);tick();}});
  schedule();
  return {refresh:function(){clearTimeout(timer);tick();},setVersion:function(x){v=x;}};
}
function faCsrf(){var m=document.querySelector('meta[name=csrf-token]');return m?m.content:'';}
function faPost(url,body){
  return fetch(url,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':faCsrf()},body:JSON.stringify(body||{})});
}
</script>
