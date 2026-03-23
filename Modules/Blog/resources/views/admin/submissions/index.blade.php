<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Soumissions d\'articles')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="file-text" class="icon-md text-primary"></i> {{ __('Soumissions d\'articles') }}</h4>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Titre') }}</th>
                    <th>{{ __('Auteur') }}</th>
                    <th>{{ __('Catégorie') }}</th>
                    <th>{{ __('Statut') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $article)
                <tr>
                    <td><strong>{{ Str::limit($article->title, 50) }}</strong></td>
                    <td>{{ $article->submittedByUser?->name ?? '-' }}</td>
                    <td>{{ $article->blogCategory?->name ?? '-' }}</td>
                    <td>
                        @if($article->submission_status === 'pending')
                            <span class="badge bg-warning text-dark">{{ __('En attente') }}</span>
                        @elseif($article->submission_status === 'approved')
                            <span class="badge bg-success">{{ __('Approuvé') }}</span>
                        @elseif($article->submission_status === 'rejected')
                            <span class="badge bg-danger">{{ __('Refusé') }}</span>
                        @endif
                    </td>
                    <td>{{ $article->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        @if($article->submission_status === 'pending')
                            <form action="{{ route('admin.blog.submissions.approve', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Approuver et publier cet article ?') }}')">
                                @csrf
                                <button class="btn btn-sm btn-success"><i data-lucide="check" class="icon-sm"></i> {{ __('Approuver') }}</button>
                            </form>
                            <form action="{{ route('admin.blog.submissions.reject', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Refuser cet article ?') }}')">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger"><i data-lucide="x" class="icon-sm"></i></button>
                            </form>
                        @else
                            <span class="text-muted">{{ __('Traité') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('Aucune soumission pour le moment.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $submissions->links() }}</div>
</div>
@endsection
