<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Soumissions : ' . $form->title])

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <div>
        <h4 class="mb-3 mb-md-0">Soumissions : {{ $form->title }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.formbuilder.forms.index') }}">Formulaires</a></li>
                <li class="breadcrumb-item active">Soumissions</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.formbuilder.forms.submissions.export', $form) }}" class="btn btn-outline-success btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="download"></i>
            Exporter CSV
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.formbuilder.forms.submissions.index', $form) }}" method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Nouveau</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Lu</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i data-lucide="search" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>IP</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr class="{{ $submission->isNew() ? 'fw-bold' : '' }}">
                            <td>{{ $submission->id }}</td>
                            <td>{{ $submission->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($submission->isNew())
                                    <span class="badge bg-primary">Nouveau</span>
                                @else
                                    <span class="badge bg-secondary">Lu</span>
                                @endif
                            </td>
                            <td>{{ $submission->ip_address }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.formbuilder.forms.submissions.show', [$form, $submission]) }}" class="btn btn-sm btn-info text-white" title="Voir">
                                        <i data-lucide="eye" style="width:16px;height:16px;"></i>
                                    </a>
                                    <form action="{{ route('admin.formbuilder.forms.submissions.destroy', [$form, $submission]) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Supprimer ?')) this.form.submit()">
                                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Aucune soumission.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $submissions->links() }}</div>
    </div>
</div>
@endsection
