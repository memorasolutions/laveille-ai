@extends('fileattente::layout')
@section('title', __('fileattente::ui.manage.title'))
@section('content')
<h1>{{ $board->title ?: __('fileattente::ui.manage.title') }}</h1>
@if (session('fa_error'))<div class="err" role="alert">{{ session('fa_error') }}</div>@endif
@if ($errors->any())<div class="err" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<div id="fa-banner" class="err" style="display:none" role="alert"></div>
<div id="fa-undo" class="ok" style="display:none" role="status"></div>

<div class="row">
  <button class="btn" type="button" id="fa-next">{{ __('fileattente::ui.manage.call_next') }}</button>
  <a class="btn alt" href="{{ route('fileattente.pdf', $token) }}">{{ __('fileattente::ui.manage.export_pdf') }}</a>
</div>

<h2>{{ __('fileattente::ui.manage.in_queue') }}</h2>
<p class="muted">{{ __('fileattente::ui.manage.drag_help') }}</p>
<ul class="q" id="fa-list"></ul>
<p id="fa-empty" class="muted" style="display:none">{{ __('fileattente::ui.manage.nobody') }}</p>

<h2>{{ __('fileattente::ui.manage.add_heading') }}</h2>
<form method="POST" action="{{ route('fileattente.add', $token) }}" class="row">@csrf
  <label for="name" class="sr-only" style="position:absolute;left:-9999px">{{ __('fileattente::ui.manage.name_label') }}</label>
  <input type="text" id="name" name="name" maxlength="{{ config('file-attente.max_name_len') }}" required placeholder="{{ __('fileattente::ui.manage.name_placeholder') }}" style="flex:1;min-width:12rem">
  <button class="btn alt" type="submit">{{ __('fileattente::ui.manage.add') }}</button>
</form>

<h2>{{ __('fileattente::ui.manage.pending_heading') }}</h2>
<ul class="q" id="fa-pending"></ul>

<h2>{{ __('fileattente::ui.manage.links_heading') }}</h2>
<p class="muted">{{ __('fileattente::ui.manage.links_help') }} <code class="url">{{ route('fileattente.manage', $token) }}</code></p>
@if ($displayUrl)
<p class="muted">{{ __('fileattente::ui.manage.display_link_label') }} <code class="url" id="fa-display-url">{{ $displayUrl }}</code></p>
<div class="row">
  <button class="btn alt" type="button" id="fa-copy-display" data-copied="{{ __('fileattente::ui.manage.display_copied') }}" data-failed="{{ __('fileattente::ui.manage.display_copy_failed') }}" aria-label="{{ __('fileattente::ui.manage.display_copy_aria') }}">{{ __('fileattente::ui.manage.display_copy') }}</button>
  <span id="fa-copy-status" class="muted" role="status" aria-live="polite"></span>
</div>
@else
<p class="err" role="status">{{ __('fileattente::ui.manage.display_link_unavailable') }}</p>
@endif

<h2>{{ __('fileattente::ui.manage.end_heading') }}</h2>
<div id="fa-end">
  <button class="btn warn" type="button" id="fa-end-1">{{ __('fileattente::ui.manage.end_button') }}</button>
</div>
<form method="POST" action="{{ route('fileattente.terminate', $token) }}" id="fa-end-2" style="display:none">@csrf
  <p class="err" role="alert">{{ __('fileattente::ui.manage.end_warning') }}</p>
  <button class="btn warn" type="submit">{{ __('fileattente::ui.manage.end_confirm') }}</button>
  <button class="btn alt" type="button" id="fa-end-cancel">{{ __('fileattente::ui.manage.cancel') }}</button>
</form>
@endsection
@push('scripts')
@include('fileattente::partials.poll')
<script>
(function(){
  var T=@json($token),U={
    reorder:@json(route('fileattente.reorder', $token)),
    next:@json(route('fileattente.callnext', $token)),
    arrive:function(id){return @json(url('/g/'.$token.'/participants')).concat('/',id,'/arrive');},
    remove:function(id){return @json(url('/g/'.$token.'/participants')).concat('/',id,'/remove');},
    undo:function(id){return @json(url('/g/'.$token.'/undo-call')).concat('/',id);}
  };
  var L=@json(__('fileattente::ui.manage.js')),state=@json($state),poller,undoTimer=null;
  var list=document.getElementById('fa-list'),pend=document.getElementById('fa-pending'),empty=document.getElementById('fa-empty'),
      banner=document.getElementById('fa-banner'),undo=document.getElementById('fa-undo'),dragId=null;

  function fmt(s,r){Object.keys(r).forEach(function(x){s=s.split(':'+x).join(r[x]);});return s;}
  function warn(t){banner.textContent=t;banner.style.display='block';setTimeout(function(){banner.style.display='none';},5000);}
  function btn(txt,label,cls,fn){var b=document.createElement('button');b.type='button';b.className='btn sm '+cls;b.textContent=txt;b.setAttribute('aria-label',label);b.addEventListener('click',fn);return b;}

  function move(id,afterKey){
    faPost(U.reorder,{id:id,afterSortKey:afterKey,expectedVersion:state.version}).then(function(r){
      if(r.status===409){warn(L.stale);}
      poller.refresh();
    }).catch(function(){warn(L.network);});
  }
  function keyBefore(items,idx){ // clé de la personne qui précède la position idx (dans la liste sans l'élément déplacé)
    return idx<=0?null:items[idx-1].sort_key;
  }
  function render(d){
    state=d;list.textContent='';pend.textContent='';
    d.items.forEach(function(it,i){
      var li=document.createElement('li');li.draggable=true;if(it.status==='called'){li.className='called';}
      li.addEventListener('dragstart',function(e){dragId=it.id;e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',String(it.id));});
      li.addEventListener('dragover',function(e){e.preventDefault();li.classList.add('over');});
      li.addEventListener('dragleave',function(){li.classList.remove('over');});
      li.addEventListener('drop',function(e){
        e.preventDefault();li.classList.remove('over');
        if(dragId===null||dragId===it.id){return;}
        var rest=state.items.filter(function(x){return x.id!==dragId;});
        var idx=rest.findIndex(function(x){return x.id===it.id;});
        var id=dragId;dragId=null;move(id,keyBefore(rest,idx));
      });
      var gr=document.createElement('span');gr.className='grip';gr.setAttribute('aria-hidden','true');gr.textContent='≡';
      var p=document.createElement('span');p.className='pos';p.textContent=it.position;
      var n=document.createElement('span');n.className='grow';n.textContent=it.full_name;
      if(it.label!==it.full_name){ // pas de « X (X) » quand le libellé masqué est identique au nom
        var lab=document.createElement('span');lab.className='muted';lab.textContent=' ('+it.label+')';n.appendChild(lab);
      }
      if(it.status==='called'){var bd=document.createElement('span');bd.className='badge';bd.textContent=' '+L.called_badge;n.appendChild(document.createTextNode(' '));n.appendChild(bd);}
      li.appendChild(gr);li.appendChild(p);li.appendChild(n);
      li.appendChild(btn('▲',fmt(L.up,{name:it.full_name}),'alt',function(){
        if(i===0){return;}
        var rest=d.items.filter(function(x){return x.id!==it.id;});move(it.id,keyBefore(rest,i-1));
      }));
      li.appendChild(btn('▼',fmt(L.down,{name:it.full_name}),'alt',function(){
        if(i>=d.items.length-1){return;}
        move(it.id,d.items[i+1].sort_key);
      }));
      li.appendChild(btn(L.remove,fmt(L.remove_aria,{name:it.full_name}),'warn',function(){
        faPost(U.remove(it.id),{}).then(function(){poller.refresh();});
      }));
      list.appendChild(li);
    });
    // zone de dépôt « en dernier »
    if(d.items.length>1){
      var end=document.createElement('li');end.className='muted';end.textContent=L.drop_last;
      end.addEventListener('dragover',function(e){e.preventDefault();end.classList.add('over');});
      end.addEventListener('dragleave',function(){end.classList.remove('over');});
      end.addEventListener('drop',function(e){e.preventDefault();end.classList.remove('over');
        if(dragId===null){return;}var rest=state.items.filter(function(x){return x.id!==dragId;});
        var id=dragId;dragId=null;move(id,rest.length?rest[rest.length-1].sort_key:null);});
      list.appendChild(end);
    }
    empty.style.display=d.items.length?'none':'block';
    d.pending.forEach(function(it){
      var li=document.createElement('li');var n=document.createElement('span');n.className='grow';
      n.textContent=it.full_name+(it.label!==it.full_name?' ('+it.label+')':'');li.appendChild(n);
      li.appendChild(btn(L.arrive,fmt(L.arrive_aria,{name:it.full_name}),'',function(){
        faPost(U.arrive(it.id),{}).then(function(){poller.refresh();}).catch(function(){warn(L.network);});
      }));
      li.appendChild(btn(L.remove,fmt(L.remove_aria,{name:it.full_name}),'warn',function(){
        faPost(U.remove(it.id),{}).then(function(){poller.refresh();}).catch(function(){warn(L.network);});
      }));
      pend.appendChild(li);
    });
  }
  render(state);
  poller=faPoll({url:@json(route('fileattente.manage.etat', $token)),version:state.version,interval:@json($cadence),onData:render,
    onGone:function(){warn(L.gone);}});

  document.getElementById('fa-next').addEventListener('click',function(){
    faPost(U.next,{}).then(function(r){return r.json();}).then(function(j){
      poller.refresh();
      if(!j.called){warn(L.nobody_to_call);return;}
      clearTimeout(undoTimer);undo.textContent='';
      undo.appendChild(document.createTextNode(fmt(L.called,{name:j.called.full_name})+' '));
      undo.appendChild(btn(L.undo,L.undo_aria,'alt',function(){
        faPost(U.undo(j.called.id),{}).then(function(){undo.style.display='none';poller.refresh();});
      }));
      undo.style.display='block';undoTimer=setTimeout(function(){undo.style.display='none';},5000);
    }).catch(function(){warn(L.network);});
  });
  var cp=document.getElementById('fa-copy-display');
  if(cp){cp.addEventListener('click',function(){
    var u=document.getElementById('fa-display-url'),st=document.getElementById('fa-copy-status');
    function ok(){st.textContent=cp.getAttribute('data-copied');}
    function ko(){st.textContent=cp.getAttribute('data-failed');var r=document.createRange();r.selectNodeContents(u);var s=window.getSelection();s.removeAllRanges();s.addRange(r);}
    if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(u.textContent.trim()).then(ok,ko);}else{ko();}
  });}
  var e1=document.getElementById('fa-end-1'),e2=document.getElementById('fa-end-2');
  e1.addEventListener('click',function(){e1.parentNode.style.display='none';e2.style.display='block';});
  document.getElementById('fa-end-cancel').addEventListener('click',function(){e2.style.display='none';e1.parentNode.style.display='block';});
})();
</script>
@endpush
