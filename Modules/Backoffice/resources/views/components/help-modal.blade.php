<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@props([
    'id',
    'title',
    'icon' => 'help-circle',
    'buttonClass' => 'btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2',
    'buttonLabel' => null,
    'size' => 'modal-lg',
])

{{-- Bouton déclencheur --}}
<button type="button"
        class="{{ $buttonClass }}"
        data-bs-toggle="modal"
        data-bs-target="#{{ $id }}"
        aria-label="{{ $buttonLabel ?: __('Aide') }}">
    <i data-lucide="help-circle" class="icon-sm"></i>
    @if($buttonLabel)
        {{ $buttonLabel }}
    @endif
</button>

{{-- Modale --}}
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable {{ $size }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="{{ $id }}-label">
                    <i data-lucide="{{ $icon }}" class="text-primary"></i>
                    {{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __("J'ai compris") }}</button>
            </div>
        </div>
    </div>
</div>

@once
@push('css')
<style>
    .modal { display: none; position: fixed; top: 0; left: 0; z-index: 1055; width: 100%; height: 100%; overflow-x: hidden; overflow-y: auto; outline: 0; }
    .modal.show { display: block; }
    .modal-backdrop { position: fixed; top: 0; left: 0; z-index: 1050; width: 100vw; height: 100vh; background-color: #000; }
    .modal-backdrop.show { opacity: 0.5; }
    .modal-dialog { position: relative; width: auto; margin: 1.75rem auto; max-width: 800px; }
    .modal-content { position: relative; display: flex; flex-direction: column; width: 100%; background-color: var(--bs-body-bg, #fff); border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); }
    .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
    .modal-title { margin-bottom: 0; font-size: 1.1rem; font-weight: 600; }
    .modal-body { position: relative; flex: 1 1 auto; padding: 1.25rem; }
    .modal-footer { display: flex; align-items: center; justify-content: flex-end; padding: 0.75rem 1.25rem; border-top: 1px solid var(--bs-border-color, #dee2e6); gap: 0.5rem; }
    .modal-dialog-scrollable { max-height: calc(100% - 3.5rem); }
    .modal-dialog-scrollable .modal-content { max-height: calc(100vh - 3.5rem); overflow: hidden; }
    .modal-dialog-scrollable .modal-body { overflow-y: auto; }
    .btn-close { box-sizing: content-box; width: 1em; height: 1em; padding: 0.25em; background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat; border: 0; border-radius: 0.375rem; opacity: 0.5; cursor: pointer; }
    .btn-close:hover { opacity: 0.75; }
</style>
@endpush
@endonce
