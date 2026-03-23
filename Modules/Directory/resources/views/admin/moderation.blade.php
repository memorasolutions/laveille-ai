<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modération')])

@section('content')

    {{-- Header stats --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i data-lucide="shield-check" class="icon-md text-primary"></i> {{ __('Modération du répertoire') }}</h4>
        <a href="{{ route('admin.directory.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Retour au répertoire') }}</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h6 class="card-title mb-0">{{ __('Avis') }}</h6>
                    <span class="badge bg-white text-primary fs-6">{{ $counts['reviews'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h6 class="card-title mb-0">{{ __('Ressources') }}</h6>
                    <span class="badge bg-white text-info fs-6">{{ $counts['resources'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h6 class="card-title mb-0">{{ __('Suggestions') }}</h6>
                    <span class="badge bg-white text-warning fs-6">{{ $counts['suggestions'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h6 class="card-title mb-0">{{ __('Signalements') }}</h6>
                    <span class="badge bg-white text-danger fs-6">{{ $counts['reports'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Avis en attente --}}
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">⭐ {{ __('Avis en attente') }}</h5></div>
        <div class="card-body p-0">
            @if($pendingReviews->isEmpty())
                <div class="p-4 text-center text-muted">{{ __('Rien en attente') }} ✅</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>{{ __('Outil') }}</th><th>{{ __('Utilisateur') }}</th><th>{{ __('Note') }}</th><th>{{ __('Titre') }}</th><th>{{ __('Contenu') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($pendingReviews as $review)
                            <tr>
                                <td><strong>{{ $review->tool->name ?? '-' }}</strong></td>
                                <td>{{ $review->user->name ?? __('Anonyme') }}</td>
                                <td class="text-warning">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                                <td>{{ $review->title }}</td>
                                <td><small class="text-muted">{{ Str::limit($review->body, 60) }}</small></td>
                                <td>{{ $review->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form action="{{ route('admin.directory.moderation.review.approve', $review->id) }}" method="POST">@csrf<button type="submit" class="btn btn-success btn-sm">{{ __('Approuver') }}</button></form>
                                        <form action="{{ route('admin.directory.moderation.review.reject', $review->id) }}" method="POST">@csrf<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('Rejeter cet avis ?') }}')">{{ __('Rejeter') }}</button></form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Ressources en attente --}}
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">📚 {{ __('Ressources en attente') }}</h5></div>
        <div class="card-body p-0">
            @if($pendingResources->isEmpty())
                <div class="p-4 text-center text-muted">{{ __('Rien en attente') }} ✅</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>{{ __('Outil') }}</th><th>{{ __('Utilisateur') }}</th><th>{{ __('Titre') }}</th><th>{{ __('URL') }}</th><th>{{ __('Type') }}</th><th>{{ __('Langue') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($pendingResources as $resource)
                            <tr>
                                <td><strong>{{ $resource->tool->name ?? '-' }}</strong></td>
                                <td>{{ $resource->user->name ?? __('Anonyme') }}</td>
                                <td>{{ $resource->title }}</td>
                                <td><a href="{{ $resource->url }}" target="_blank" class="text-decoration-none">{{ __('Voir') }} ↗</a></td>
                                <td><span class="badge bg-secondary">{{ ucfirst($resource->type) }}</span></td>
                                <td>{{ strtoupper($resource->language) }}</td>
                                <td>{{ $resource->created_at->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form action="{{ route('admin.directory.moderation.resource.approve', $resource->id) }}" method="POST">@csrf<button type="submit" class="btn btn-success btn-sm">{{ __('Approuver') }}</button></form>
                                        <form action="{{ route('admin.directory.moderation.resource.reject', $resource->id) }}" method="POST">@csrf<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('Rejeter cette ressource ?') }}')">{{ __('Rejeter') }}</button></form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Signalements --}}
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">🚩 {{ __('Signalements') }}</h5></div>
        <div class="card-body p-0">
            @if($reports->isEmpty())
                <div class="p-4 text-center text-muted">{{ __('Aucun signalement') }} ✅</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>{{ __('Type') }}</th><th>{{ __('Signalé par') }}</th><th>{{ __('Raison') }}</th><th>{{ __('Commentaire') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                            <tr>
                                <td><span class="badge bg-dark">{{ class_basename($report->reportable_type) }}</span></td>
                                <td>{{ $report->user->name ?? __('Anonyme') }}</td>
                                <td>
                                    @php $reasonLabels = ['spam' => 'Spam', 'inappropriate' => 'Inapproprié', 'off_topic' => 'Hors sujet', 'other' => 'Autre']; @endphp
                                    {{ $reasonLabels[$report->reason] ?? $report->reason }}
                                </td>
                                <td><small class="text-muted">{{ $report->comment ?? '-' }}</small></td>
                                <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form action="{{ route('admin.directory.moderation.report.resolve', $report->id) }}" method="POST">@csrf<button type="submit" class="btn btn-warning btn-sm">{{ __('Résolu') }}</button></form>
                                        <form action="{{ route('admin.directory.moderation.report.delete', $report->id) }}" method="POST">@csrf<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('Supprimer le contenu signalé ?') }}')">{{ __('Supprimer') }}</button></form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Suggestions --}}
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">✏️ {{ __('Suggestions de modifications') }}</h5></div>
        <div class="card-body p-0">
            @if($pendingSuggestions->isEmpty())
                <div class="p-4 text-center text-muted">{{ __('Aucune suggestion') }} ✅</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>{{ __('Outil') }}</th><th>{{ __('Utilisateur') }}</th><th>{{ __('Champ') }}</th><th>{{ __('Suggestion') }}</th><th>{{ __('Raison') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($pendingSuggestions as $suggestion)
                            <tr>
                                <td><strong>{{ $suggestion->tool->name ?? '-' }}</strong></td>
                                <td>{{ $suggestion->user->name ?? __('Anonyme') }}</td>
                                <td><span class="badge bg-secondary">{{ \Modules\Directory\Models\ToolSuggestion::fieldLabels()[$suggestion->field] ?? $suggestion->field }}</span></td>
                                <td><small class="text-muted">{{ Str::limit($suggestion->suggested_value, 80) }}</small></td>
                                <td><small class="text-muted">{{ $suggestion->reason ?? '-' }}</small></td>
                                <td>{{ $suggestion->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form action="{{ route('admin.directory.moderation.suggestion.approve', $suggestion->id) }}" method="POST">@csrf<button type="submit" class="btn btn-success btn-sm">{{ __('Appliquer') }}</button></form>
                                        <form action="{{ route('admin.directory.moderation.suggestion.reject', $suggestion->id) }}" method="POST">@csrf<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('Rejeter cette suggestion ?') }}')">{{ __('Rejeter') }}</button></form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

@endsection
