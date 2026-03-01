<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Gestion des FAQ')
@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">FAQ</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">FAQ</h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success d-none" id="btnSaveOrder" onclick="saveOrder()">
                <i data-lucide="save"></i> Enregistrer l'ordre
            </button>
            <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary">
                <i data-lucide="plus"></i> Ajouter une question
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($faqs->count() > 0)
            <p class="text-muted small mb-3">Glissez-déposez pour réorganiser l'ordre d'affichage.</p>
            <div id="faq-list">
                @foreach($faqs as $faq)
                <div class="faq-item d-flex align-items-center gap-3 p-3 mb-2 border rounded" data-id="{{ $faq->id }}">
                    <i data-lucide="grip-vertical" class="text-muted cursor-grab"></i>
                    <div class="flex-grow-1">
                        <div class="fw-medium">{{ $faq->question }}</div>
                        <small class="text-muted">
                            @if($faq->category)
                                <span class="badge bg-light text-dark">{{ $faq->category }}</span>
                            @endif
                        </small>
                    </div>
                    <div>
                        @if($faq->is_published)
                            <span class="badge bg-success">Publié</span>
                        @else
                            <span class="badge bg-secondary">Brouillon</span>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                            <i data-lucide="edit"></i>
                        </a>
                        <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette question ?');">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="if(confirm('Supprimer cette question ?')) this.closest('form').submit()">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5">
                <i data-lucide="help-circle" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">Aucune question FAQ</h5>
                <p class="text-muted mb-4">Créez votre première question pour la page FAQ publique.</p>
                <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i> Ajouter une question
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
@if($faqs->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('faq-list');
    if (!list) return;
    const btn = document.getElementById('btnSaveOrder');

    new Sortable(list, {
        animation: 150,
        handle: '[data-lucide="grip-vertical"]',
        ghostClass: 'opacity-25',
        onEnd: function() { btn.classList.remove('d-none'); }
    });
});

function saveOrder() {
    const btn = document.getElementById('btnSaveOrder');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';

    const items = [...document.querySelectorAll('.faq-item')].map(el => parseInt(el.dataset.id));

    fetch('{{ route("admin.faqs.reorder") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save"></i> Enregistrer l\'ordre';
        btn.classList.add('d-none');
        lucide.createIcons();
        if (data.success) {
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-success border-0 show';
            toast.setAttribute('style', 'position:fixed;top:1rem;right:1rem;z-index:9999');
            toast.innerHTML = '<div class="d-flex"><div class="toast-body">Ordre enregistré.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save"></i> Enregistrer l\'ordre';
        lucide.createIcons();
    });
}
</script>
@endif
@endpush
