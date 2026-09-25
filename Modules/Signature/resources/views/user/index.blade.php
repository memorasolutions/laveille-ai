<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Mes signatures - ' . config('app.name'))
@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Mes signatures'), 'breadcrumbItems' => [__('Outils'), __('Signature de courriel'), __('Mes signatures')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">✍️ {{ __('Mes signatures') }}</h1>
                    <a href="{{ route('signature.assistant') }}" class="btn btn-primary btn-sm">+ {{ __('Nouvelle signature') }}</a>
                </div>

                @if($signatures->isEmpty())
                    <p class="text-muted">{{ __("Vous n'avez pas encore de signature enregistrée.") }}</p>
                @else
                    <div class="list-group">
                        @foreach($signatures as $signature)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $signature->content['first_name'] ?? '' }} {{ $signature->content['last_name'] ?? '' }}</strong>
                                    <span class="text-muted small d-block">{{ ucfirst($signature->template) }} · {{ __('Modifiée le') }} {{ optional($signature->updated_at)->format('d/m/Y') }}</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('signature.user.edit', $signature) }}" class="btn btn-outline-primary btn-sm">{{ __('Modifier') }}</a>
                                    <form method="POST" action="{{ route('signature.user.destroy', $signature) }}" id="sig-delete-form-{{ $signature->id }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#sigDeleteModal" data-form-id="sig-delete-form-{{ $signature->id }}">{{ __('Supprimer') }}</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Modale de confirmation (thème Bootstrap) - JAMAIS confirm() natif (règle projet). --}}
<div class="modal fade" id="sigDeleteModal" tabindex="-1" aria-labelledby="sigDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sigDeleteModalLabel">{{ __('Supprimer cette signature') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ __('Cette action est définitive' . "\u{00A0}" . ': la signature et ses images seront supprimées immédiatement.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                <button type="button" class="btn btn-danger" id="sigDeleteConfirmBtn">{{ __('Supprimer') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('sigDeleteModal');
    var targetFormId = null;
    modalEl.addEventListener('show.bs.modal', function (event) {
        targetFormId = event.relatedTarget.getAttribute('data-form-id');
    });
    document.getElementById('sigDeleteConfirmBtn').addEventListener('click', function () {
        if (targetFormId) { document.getElementById(targetFormId).submit(); }
    });
});
</script>
@endpush
