@extends('fileattente::layout')
@section('title', __('fileattente::ui.created.title'))
@section('content')
<h1>{{ __('fileattente::ui.created.heading') }}</h1>
<div class="info" role="status">{{ __('fileattente::ui.created.warning') }}</div>

<div class="card">
  <h2>{{ __('fileattente::ui.created.manage_heading') }}</h2>
  <div class="url-row"><code class="url" id="fa-url-manage">{{ $manageUrl }}</code><button class="btn alt" type="button" data-copy="fa-url-manage" aria-label="{{ __('fileattente::ui.created.copy_aria') }}">{{ __('fileattente::ui.created.copy') }}</button></div>
  <p class="row"><a class="btn" href="{{ $manageUrl }}">{{ __('fileattente::ui.created.manage_open') }}</a></p>
</div>
<div class="card">
  <h2>{{ __('fileattente::ui.created.display_heading') }}</h2>
  <div class="url-row"><code class="url" id="fa-url-display">{{ $displayUrl }}</code><button class="btn alt" type="button" data-copy="fa-url-display" aria-label="{{ __('fileattente::ui.created.copy_aria') }}">{{ __('fileattente::ui.created.copy') }}</button></div>
  <p class="row"><a class="btn alt" href="{{ $displayUrl }}" target="_blank" rel="noopener noreferrer">{{ __('fileattente::ui.created.display_open') }}</a></p>
</div>
<div class="card">
  <h2>{{ __('fileattente::ui.created.cards_heading', ['count' => $count]) }}</h2>
  <p>{{ __('fileattente::ui.created.cards_help') }}</p>
  <p class="row"><a class="btn warn" href="{{ $pdfUrl }}">{{ __('fileattente::ui.created.export_pdf') }}</a></p>
</div>
<p id="fa-copy-status" class="muted" role="status" aria-live="polite"></p>
@endsection
@push('scripts')
<script>
(function(){
  var st=document.getElementById('fa-copy-status'),L=@json(__('fileattente::ui.created.js'));
  Array.prototype.forEach.call(document.querySelectorAll('[data-copy]'),function(b){
    b.addEventListener('click',function(){
      var u=document.getElementById(b.getAttribute('data-copy'));
      function ok(){st.textContent=L.copied;}
      function ko(){st.textContent=L.failed;var r=document.createRange();r.selectNodeContents(u);var s=window.getSelection();s.removeAllRanges();s.addRange(r);}
      if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(u.textContent.trim()).then(ok,ko);}else{ko();}
    });
  });
})();
</script>
@endpush
