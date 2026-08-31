<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Glossaire')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="book-open" class="icon-md text-primary"></i> {{ __('Termes du glossaire') }}</h4>
    @can('create_dictionary_terms')
    <a href="{{ route('admin.dictionary.create') }}" class="btn btn-primary btn-sm">+ {{ __('Nouveau terme') }}</a>
    @endcan
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
                        @php
                            $termActions = [
                                ['label' => __('Voir'), 'icon' => 'eye', 'url' => $term->getPublicUrl(), 'target' => '_blank'],
                            ];
                            if (auth()->user()?->can('update_dictionary_terms')) {
                                $termActions[] = ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.dictionary.edit', $term)];
                            }
                            if (auth()->user()?->can('delete_dictionary_terms')) {
                                $termActions[] = ['divider' => true];
                                $termActions[] = ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.dictionary.destroy', $term), 'method' => 'DELETE', 'confirm' => __('Supprimer ?'), 'danger' => true];
                            }
                        @endphp
                        @include('core::components.action-menu', ['actions' => $termActions])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $terms->links() }}</div>
</div>
@endsection
