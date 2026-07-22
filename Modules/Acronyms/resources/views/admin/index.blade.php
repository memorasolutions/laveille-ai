<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Acronymes éducation')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="graduation-cap" class="icon-md text-primary"></i> {{ __('Acronymes de l\'éducation') }}</h4>
    @can('create_acronyms')
    <a href="{{ route('admin.acronyms.create') }}" class="btn btn-primary btn-sm">+ {{ __('Nouvel acronyme') }}</a>
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
                    <th>{{ __('Acronyme') }}</th>
                    <th>{{ __('Nom complet') }}</th>
                    <th>{{ __('Catégorie') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acronyms as $acronym)
                <tr>
                    <td><strong>{{ $acronym->acronym }}</strong></td>
                    <td>{{ Str::limit($acronym->full_name, 60) }}</td>
                    <td>
                        @if($acronym->category)
                            <span class="badge" style="background: {{ $acronym->category->color }}22; color: {{ $acronym->category->color }};">
                                {{ $acronym->category->icon }} {{ $acronym->category->name }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-end">
                        @php
                            $acronymActions = [
                                ['label' => __('Voir'), 'icon' => 'eye', 'url' => route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale())), 'target' => '_blank'],
                            ];
                            if (auth()->user()?->can('update_acronyms')) {
                                $acronymActions[] = ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.acronyms.edit', $acronym)];
                            }
                            if (auth()->user()?->can('delete_acronyms')) {
                                $acronymActions[] = ['divider' => true];
                                $acronymActions[] = ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.acronyms.destroy', $acronym), 'method' => 'DELETE', 'confirm' => __('Supprimer ?'), 'danger' => true];
                            }
                        @endphp
                        @include('core::components.action-menu', ['actions' => $acronymActions])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $acronyms->links() }}</div>
</div>
@endsection
