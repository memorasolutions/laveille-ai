<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Questions d\'accueil')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.booking.services.index') }}">Services</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.booking.services.edit', $service) }}">{{ $service->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Questions d'accueil</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Questions d'accueil pour {{ $service->name }}</h5>
            <a href="{{ route('admin.booking.intake-questions.create', $service) }}" class="btn btn-primary">
                <i data-lucide="plus-circle" class="me-1"></i> Ajouter
            </a>
        </div>

        <div class="card-body">
            @if($questions->isEmpty())
                <div class="text-center py-5">
                    <i data-lucide="help-circle" style="width: 48px; height: 48px;" class="text-muted"></i>
                    <p class="mt-3 text-muted">Aucune question configurée</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="80">Ordre</th>
                                <th>Label</th>
                                <th width="150">Type</th>
                                <th width="100">Requis</th>
                                <th width="120" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questions->sortBy('sort_order') as $question)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $question->sort_order }}</span></td>
                                    <td>{{ $question->label }}</td>
                                    <td>
                                        @php
                                            $typeLabels = ['text' => 'Texte', 'textarea' => 'Zone de texte', 'select' => 'Liste déroulante', 'checkbox' => 'Case à cocher', 'radio' => 'Boutons radio'];
                                        @endphp
                                        <span class="badge bg-info">{{ $typeLabels[$question->type] ?? $question->type }}</span>
                                    </td>
                                    <td>
                                        @if($question->is_required)
                                            <span class="badge bg-danger">Oui</span>
                                        @else
                                            <span class="badge bg-secondary">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.booking.intake-questions.edit', $question) }}" class="btn btn-outline-primary" title="Modifier">
                                                <i data-lucide="edit-2"></i>
                                            </a>
                                            <form action="{{ route('admin.booking.intake-questions.destroy', $question) }}" method="POST" class="d-inline" data-confirm="Supprimer cette question ?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Supprimer">
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
            @endif
        </div>
    </div>
</div>
@endsection
