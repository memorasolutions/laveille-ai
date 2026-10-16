@extends($faLayout)
@section('page_noindex', '1')
@section('no_ads', '1')
@section('suppress_auto_ads', '1')
@section('no_newsletter', '1')
@push('styles')
@include('fileattente::partials.styles')
@endpush
@section('title', __('fileattente::ui.created.title'))
@section('breadcrumb')
@include('fileattente::partials.breadcrumb', ['crumb' => __('fileattente::ui.tool.name')])
@endsection
@section('content')
@include('fileattente::partials.page-open')
<h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ __('fileattente::ui.created.heading') }}</h1>
<div class="alert alert-info" role="status">{{ __('fileattente::ui.created.warning') }}</div>

<div class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-2" style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark);">{{ __('fileattente::ui.created.manage_heading') }}</h2>
  <div class="url-row"><code class="url" id="fa-url-manage">{{ $manageUrl }}</code><button class="ct-btn ct-btn-outline" type="button" data-copy="fa-url-manage" aria-label="{{ __('fileattente::ui.created.copy_aria') }}">{{ __('fileattente::ui.created.copy') }}</button></div>
  <p class="d-flex flex-wrap gap-2 mt-3 mb-0"><a class="ct-btn ct-btn-primary" href="{{ $manageUrl }}">{{ __('fileattente::ui.created.manage_open') }}</a></p>
</div></div>
<div class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-2" style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark);">{{ __('fileattente::ui.created.display_heading') }}</h2>
  <div class="url-row"><code class="url" id="fa-url-display">{{ $displayUrl }}</code><button class="ct-btn ct-btn-outline" type="button" data-copy="fa-url-display" aria-label="{{ __('fileattente::ui.created.copy_aria') }}">{{ __('fileattente::ui.created.copy') }}</button></div>
  <p class="d-flex flex-wrap gap-2 mt-3 mb-0"><a class="ct-btn ct-btn-outline" href="{{ $displayUrl }}" target="_blank" rel="noopener noreferrer">{{ __('fileattente::ui.created.display_open') }}</a></p>
</div></div>
<div class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-2" style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark);">{{ __('fileattente::ui.created.cards_heading', ['count' => $count]) }}</h2>
  <p class="mb-0">{{ __('fileattente::ui.created.cards_help') }}</p>
  <p class="d-flex flex-wrap gap-2 mt-3 mb-0"><a class="ct-btn ct-btn-primary" href="{{ $pdfUrl }}">{{ __('fileattente::ui.created.export_pdf') }}</a></p>
</div></div>
<p id="fa-copy-status" class="text-muted small" role="status" aria-live="polite"></p>
@include('fileattente::partials.page-close')
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
@push('scripts')
<script>
/* @author MEMORA solutions
 * Le tableau est persisté côté serveur : le brouillon du formulaire est effacé du navigateur. */
try{window.localStorage.removeItem('fileattente:create-draft');}catch(e){}
</script>
@endpush
