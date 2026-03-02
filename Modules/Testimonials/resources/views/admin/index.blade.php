<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Témoignages', 'subtitle' => 'Contenu'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Témoignages</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Témoignages ({{ $testimonials->count() }})</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm d-none" id="btnSaveOrder" onclick="saveOrder()">
                <i data-lucide="save" class="me-1"></i> Enregistrer l'ordre
            </button>
            <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="me-1"></i> Nouveau témoignage
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        @if($testimonials->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="message-square" class="icon-xxl text-muted mb-3 d-block mx-auto" style="width:48px;height:48px"></i>
                <h6 class="text-muted">Aucun témoignage pour le moment.</h6>
                <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary btn-sm mt-2">
                    <i data-lucide="plus" class="me-1"></i> Ajouter un témoignage
                </a>
            </div>
        @else
            <p class="text-muted small px-4 pt-3 mb-2">Glissez-déposez pour réorganiser l'ordre d'affichage.</p>
            <div id="testimonial-list" class="px-2 pb-3">
                @foreach($testimonials as $testimonial)
                <div class="testimonial-item d-flex align-items-center gap-3 p-3 mx-2 mb-2 border rounded" data-id="{{ $testimonial->id }}">
                    <i data-lucide="grip-vertical" class="text-muted" style="cursor:grab"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $testimonial->author_name }}</div>
                        @if($testimonial->author_title)
                            <small class="text-muted">{{ $testimonial->author_title }}</small>
                        @endif
                        <p class="mb-0 mt-1 text-muted small">{{ Str::limit(strip_tags($testimonial->content), 80) }}</p>
                    </div>
                    <div class="text-warning text-nowrap">
                        @for($i = 1; $i <= 5; $i++)
                            <span style="font-size:0.85rem">{{ $i <= $testimonial->rating ? '★' : '☆' }}</span>
                        @endfor
                    </div>
                    <span class="badge bg-{{ $testimonial->is_approved ? 'success' : 'warning' }}">
                        {{ $testimonial->is_approved ? 'Approuvé' : 'En attente' }}
                    </span>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                            <i data-lucide="pencil"></i>
                        </a>
                        <form action="{{ route('admin.testimonials.destroy', $testimonial) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer ce témoignage ?')">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection

@push('plugin-scripts')
@if($testimonials->count() > 1)
<script src="{{ asset('build/nobleui/plugins/sortablejs/Sortable.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('testimonial-list');
    if (!list) return;
    new Sortable(list, {
        animation: 150,
        handle: '[data-lucide="grip-vertical"]',
        ghostClass: 'opacity-25',
        onEnd: function() {
            document.getElementById('btnSaveOrder').classList.remove('d-none');
        }
    });
});

function saveOrder() {
    const btn = document.getElementById('btnSaveOrder');
    btn.disabled = true;
    const items = [...document.querySelectorAll('.testimonial-item')].map(el => parseInt(el.dataset.id));

    fetch('{{ route("admin.testimonials.reorder") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ items: items })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.classList.add('d-none');
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show mx-4 mt-3';
        alert.innerHTML = 'Ordre enregistré avec succès. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.querySelector('.card-body').prepend(alert);
    });
}
</script>
@endif
@endpush
