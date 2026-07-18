@extends('backoffice::layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title">{{ __('Sources RSS') }}</h4>
            <a href="{{ route('admin.news.sources.create') }}" class="btn btn-primary">{{ __('Ajouter une source') }}</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Nom') }}</th>
                        <th>URL</th>
                        <th>{{ __('Catégorie') }}</th>
                        <th>{{ __('Langue') }}</th>
                        <th>{{ __('Actif') }}</th>
                        <th>{{ __('Articles') }}</th>
                        <th>{{ __('Dernier fetch') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sources as $source)
                    <tr>
                        <td>{{ $source->name }}</td>
                        <td><a href="{{ $source->url }}" target="_blank" rel="noopener">{{ Str::limit($source->url, 40) }}</a></td>
                        <td>{{ $source->category ?? '-' }}</td>
                        <td>{{ strtoupper($source->language) }}</td>
                        <td>
                            <span class="badge bg-{{ $source->active ? 'success' : 'danger' }}">{{ $source->active ? __('Actif') : __('Inactif') }}</span>
                        </td>
                        <td>{{ $source->articles_count ?? 0 }}</td>
                        <td>{{ $source->last_fetched_at ? $source->last_fetched_at->diffForHumans() : __('Jamais') }}</td>
                        <td>
                            @include('core::components.action-menu', ['actions' => [
                                ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.news.sources.edit', $source)],
                                ['label' => __('Fetch'), 'icon' => 'refresh-cw', 'url' => route('admin.news.sources.fetch', $source), 'method' => 'POST'],
                                ['label' => $source->active ? __('Désactiver') : __('Activer'), 'icon' => $source->active ? 'pause' : 'play', 'url' => route('admin.news.sources.toggle', $source), 'method' => 'PATCH'],
                                ['divider' => true],
                                ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.news.sources.destroy', $source), 'method' => 'DELETE', 'confirm' => __('Supprimer cette source et tous ses articles ?'), 'danger' => true],
                            ]])
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">{{ __('Aucune source.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sources->links() }}
    </div>
</div>
@endsection
