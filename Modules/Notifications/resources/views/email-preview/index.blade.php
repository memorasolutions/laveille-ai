<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Aperçu des courriels'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('Aperçu des courriels') }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">{{ __('Prévisualisez les courriels envoyés par le système avant diffusion.') }}</p>
                <div class="row g-3">
                    @foreach($notifications as $key => $notification)
                    <div class="col-md-4">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                        <i data-lucide="mail" class="text-primary"></i>
                                    </div>
                                    <h6 class="mb-0">{{ $notification['name'] }}</h6>
                                </div>
                                <p class="text-muted small mb-0">{{ $notification['description'] }}</p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0 pt-0">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary w-100"
                                        onclick="previewEmail('{{ route('admin.email-preview.show', $key) }}', '{{ $notification['name'] }}')"
                                        aria-label="{{ __('Aperçu') }} {{ $notification['name'] }}">
                                    <i data-lucide="eye" class="me-1" style="width:14px;height:14px;"></i>
                                    {{ __('Aperçu') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modale de prévisualisation --}}
<div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-labelledby="emailPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailPreviewModalLabel">{{ __('Aperçu') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="emailPreviewFrame" style="width:100%;height:500px;border:none;" sandbox="allow-same-origin" title="{{ __('Aperçu du courriel') }}"></iframe>
            </div>
            <div class="modal-footer">
                <a href="#" id="openInNewTab" target="_blank" class="btn btn-sm btn-primary">
                    <i data-lucide="external-link" style="width:14px;height:14px;" class="me-1"></i>
                    {{ __('Ouvrir dans un nouvel onglet') }}
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Fermer') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewEmail(url, title) {
    document.getElementById('emailPreviewModalLabel').textContent = title;
    document.getElementById('emailPreviewFrame').src = url;
    document.getElementById('openInNewTab').href = url;
    new bootstrap.Modal(document.getElementById('emailPreviewModal')).show();
}
document.getElementById('emailPreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('emailPreviewFrame').src = '';
});
</script>
@endpush
