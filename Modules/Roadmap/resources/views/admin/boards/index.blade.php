@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Tableaux Roadmap'))

@section('content')
    @include('backoffice::themes.backend.components.breadcrumb', [
        'title' => __('Tableaux Roadmap'),
        'items' => [
            ['label' => 'Roadmap'],
            ['label' => __('Tableaux')],
        ],
    ])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Tableaux') }}</h5>
            <a href="{{ route('admin.roadmap.boards.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="me-1"></i> {{ __('Nouveau tableau') }}
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>{{ __('Nom') }}</th>
                            <th>{{ __('Slug') }}</th>
                            <th>{{ __('Idées') }}</th>
                            <th>{{ __('Public') }}</th>
                            <th>{{ __('Couleur') }}</th>
                            <th width="150">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boards as $board)
                            <tr>
                                <td>{{ $board->id }}</td>
                                <td>{{ $board->name }}</td>
                                <td><code class="text-muted">{{ $board->slug }}</code></td>
                                <td><span class="badge bg-info">{{ $board->ideas_count }}</span></td>
                                <td>
                                    @if($board->is_public)
                                        <span class="badge bg-success">{{ __('Oui') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Non') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($board->color)
                                        <div style="width: 20px; height: 20px; background-color: {{ $board->color }}; border: 1px solid #dee2e6; border-radius: 3px;" title="{{ $board->color }}"></div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.roadmap.boards.edit', $board) }}" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                            <i data-lucide="pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.roadmap.boards.destroy', $board) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer ce tableau ?') }}');">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Supprimer') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    {{ __('Aucun tableau trouvé.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($boards->hasPages())
            <div class="card-footer">
                {{ $boards->links() }}
            </div>
        @endif
    </div>
@endsection
