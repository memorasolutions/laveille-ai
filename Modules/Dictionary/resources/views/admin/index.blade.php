<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Glossaire')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="book-open" class="icon-md text-primary"></i> {{ __('Termes du glossaire') }}</h4>
    <a href="{{ route('admin.dictionary.create') }}" class="btn btn-primary btn-sm">+ {{ __('Nouveau terme') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Terme') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Catégorie') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($terms as $term)
                <tr>
                    <td><strong>{{ $term->name }}</strong></td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $term->type === 'acronym' ? __('Acronyme') : ($term->type === 'ai_term' ? __('Terme IA') : __('Vulgarisation')) }}</span></td>
                    <td>{{ $term->category?->name ?? '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('dictionary.show', $term->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i data-lucide="eye" class="icon-sm"></i></a>
                        <a href="{{ route('admin.dictionary.edit', $term) }}" class="btn btn-sm btn-outline-primary"><i data-lucide="pencil" class="icon-sm"></i></a>
                        <form action="{{ route('admin.dictionary.destroy', $term) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer ?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $terms->links() }}</div>
</div>
@endsection
