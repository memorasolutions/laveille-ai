@extends('backoffice::layouts.admin')
@section('title', __('Ressources / tutoriels'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📚 {{ __('Ressources et tutoriels') }}</h4>
    <span class="badge bg-primary">{{ $resources->total() }} {{ __('total') }}</span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Outil') }}</th>
                        <th>{{ __('Titre') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Langue') }}</th>
                        <th>{{ __('Durée') }}</th>
                        <th>{{ __('Résumé IA') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Soumis par') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resources as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td><strong>{{ is_array($r->tool->name ?? null) ? ($r->tool->name['fr_CA'] ?? '') : ($r->tool->name ?? '-') }}</strong></td>
                        <td style="max-width:250px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $r->title }}</div>
                            @if($r->video_id)<small class="text-muted">YT: {{ $r->video_id }}</small>@endif
                        </td>
                        <td><span class="badge bg-secondary">{{ ucfirst($r->type) }}</span></td>
                        <td>{{ strtoupper($r->language) }}</td>
                        <td>{{ $r->duration_seconds ? gmdate($r->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $r->duration_seconds) : '-' }}</td>
                        <td>
                            @if($r->video_summary)
                                <span class="badge bg-success">{{ strlen($r->video_summary) }} chars</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ __('Aucun') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($r->is_approved)
                                <span class="badge bg-success">{{ __('Approuvé') }}</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ __('En attente') }}</span>
                            @endif
                        </td>
                        <td>{{ $r->user->name ?? '-' }}</td>
                        <td>{{ $r->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">
                            @include('core::components.admin-action-menu', ['actions' => array_filter([
                                ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.directory.resources.edit', $r->id)],
                                !$r->is_approved ? ['label' => __('Approuver'), 'icon' => 'check', 'url' => route('admin.directory.moderation.resource.approve', $r->id), 'method' => 'POST'] : null,
                                ['divider' => true],
                                ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.directory.moderation.resource.delete', $r->id), 'method' => 'POST', 'confirm' => __('Supprimer ?'), 'danger' => true],
                            ])])
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center p-4 text-muted">{{ __('Aucune ressource') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $resources->links() }}</div>
@endsection
