<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Experiences A/B')
@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Experiences A/B</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Experiences A/B</h4>
        <a href="{{ route('admin.experiments.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i> Nouvelle experience
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($experiments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Variantes</th>
                            <th>Statut</th>
                            <th>Date de creation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($experiments as $experiment)
                        <tr>
                            <td>{{ $experiment->name }}</td>
                            <td>
                                @foreach($experiment->variants as $variant)
                                    <span class="badge bg-light text-dark border me-1">{{ $variant }}</span>
                                @endforeach
                            </td>
                            <td>
                                @php
                                    $statusClasses = ['draft' => 'secondary', 'running' => 'primary', 'completed' => 'success'];
                                @endphp
                                <span class="badge bg-{{ $statusClasses[$experiment->status] ?? 'secondary' }}">
                                    {{ $experiment->status }}
                                </span>
                            </td>
                            <td>{{ $experiment->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.experiments.show', $experiment) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <form action="{{ route('admin.experiments.destroy', $experiment) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="if(confirm('Supprimer cette experience ?')) this.closest('form').submit()">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $experiments->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i data-lucide="flask-conical" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">Aucune experience</h5>
                <p class="text-muted mb-4">Creez votre premiere experience A/B pour optimiser vos conversions.</p>
                <a href="{{ route('admin.experiments.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i> Nouvelle experience
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
