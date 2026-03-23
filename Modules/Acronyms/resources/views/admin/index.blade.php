<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Acronymes éducation')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="graduation-cap" class="icon-md text-primary"></i> {{ __('Acronymes de l\'éducation') }}</h4>
    <a href="{{ route('admin.acronyms.create') }}" class="btn btn-primary btn-sm">+ {{ __('Nouvel acronyme') }}</a>
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
                        <a href="{{ route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale())) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i data-lucide="eye" class="icon-sm"></i></a>
                        <a href="{{ route('admin.acronyms.edit', $acronym) }}" class="btn btn-sm btn-outline-primary"><i data-lucide="pencil" class="icon-sm"></i></a>
                        <form action="{{ route('admin.acronyms.destroy', $acronym) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer ?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $acronyms->links() }}</div>
</div>
@endsection
