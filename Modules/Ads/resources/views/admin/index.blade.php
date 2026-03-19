@extends('backoffice::themes.backend.layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">{{ __('Gestion des publicités') }}</h4>
    <a href="{{ route('admin.ads.create') }}" class="btn btn-primary btn-icon-text">
        <i class="btn-icon-prepend" data-feather="plus"></i> {{ __('Ajouter') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Nom') }}</th>
                        <th>{{ __('Clé') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Ordre') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ads as $ad)
                    <tr>
                        <td><strong>{{ $ad->name }}</strong><br><small class="text-muted">{{ Str::limit($ad->description, 60) }}</small></td>
                        <td><code>{{ $ad->key }}</code></td>
                        <td>
                            @if($ad->is_external)
                                <span class="badge bg-info">{{ __('Externe') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('Interne') }}</span>
                            @endif
                        </td>
                        <td>{{ $ad->sort_order }}</td>
                        <td>
                            @if($ad->is_active)
                                <span class="badge bg-success">{{ __('Actif') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Inactif') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.ads.edit', $ad) }}" class="btn btn-outline-primary btn-icon btn-sm"><i data-feather="edit"></i></a>
                            <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette publicité ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-icon btn-sm"><i data-feather="trash-2"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">{{ __('Aucune publicité.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
