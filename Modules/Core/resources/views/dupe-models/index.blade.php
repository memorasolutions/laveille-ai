<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Dupe Models', 'subtitle' => 'Liste'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Dupe Models') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="list" class="icon-md text-primary"></i>{{ __('Dupe Models') }}</h4>
    <a href="{{ route('core.dupe-models.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
        <i data-lucide="plus"></i> {{ __('Ajouter') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dupeModels as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        
                        <td class="text-end">
                            <a href="{{ route('core.dupe-models.edit', $item->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                <i data-lucide="pencil" class="icon-sm"></i> {{ __('Modifier') }}
                            </a>
                            <form action="{{ route('core.dupe-models.destroy', $item->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" onclick="return confirm('Confirmer la suppression ?')">
                                    <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted py-4">{{ __('Aucun élément trouvé.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $dupeModels->links() }}
        </div>
    </div>
</div>

@endsection