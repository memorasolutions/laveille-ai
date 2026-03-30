<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modération')])

@section('content')

    <div x-data="{ tab: 'reviews' }">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i data-lucide="shield-check" class="icon-md text-primary"></i> {{ __('Modération du répertoire') }}</h4>
        <a href="{{ route('admin.directory.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Retour au répertoire') }}</a>
    </div>

    {{-- Stat cards cliquables --}}
    <div class="row mb-4">
        <div class="col-md-3 col-6">
            <div class="card mb-3 shadow-sm" style="cursor:pointer;transition:all .2s;" :style="tab === 'reviews' ? 'border-left:4px solid var(--bs-primary);background:#f0f6ff' : ''" @click="tab = 'reviews'">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="star" class="text-primary"></i>
                        <span class="fw-semibold">{{ __('Avis') }}</span>
                    </div>
                    <span class="badge bg-primary fs-6">{{ $counts['reviews'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card mb-3 shadow-sm" style="cursor:pointer;transition:all .2s;" :style="tab === 'resources' ? 'border-left:4px solid var(--bs-info);background:#f0faff' : ''" @click="tab = 'resources'">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="book-open" class="text-info"></i>
                        <span class="fw-semibold">{{ __('Ressources') }}</span>
                    </div>
                    <span class="badge bg-info fs-6">{{ $counts['resources'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card mb-3 shadow-sm" style="cursor:pointer;transition:all .2s;" :style="tab === 'suggestions' ? 'border-left:4px solid var(--bs-warning);background:#fffbf0' : ''" @click="tab = 'suggestions'">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="pencil" class="text-warning"></i>
                        <span class="fw-semibold">{{ __('Suggestions') }}</span>
                    </div>
                    <span class="badge bg-warning fs-6">{{ $counts['suggestions'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card mb-3 shadow-sm" style="cursor:pointer;transition:all .2s;" :style="tab === 'reports' ? 'border-left:4px solid var(--bs-danger);background:#fff5f5' : ''" @click="tab = 'reports'">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="flag" class="text-danger"></i>
                        <span class="fw-semibold">{{ __('Signalements') }}</span>
                    </div>
                    <span class="badge bg-danger fs-6">{{ $counts['reports'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Onglets Alpine.js --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><button class="nav-link" :class="tab === 'reviews' && 'active'" @click.prevent="tab = 'reviews'">⭐ {{ __('Avis') }} ({{ $counts['reviews'] }})</button></li>
        <li class="nav-item"><button class="nav-link" :class="tab === 'resources' && 'active'" @click.prevent="tab = 'resources'">📚 {{ __('Ressources') }} ({{ $counts['resources'] }})</button></li>
        <li class="nav-item"><button class="nav-link" :class="tab === 'suggestions' && 'active'" @click.prevent="tab = 'suggestions'">✏️ {{ __('Suggestions') }} ({{ $counts['suggestions'] }})</button></li>
        <li class="nav-item"><button class="nav-link" :class="tab === 'reports' && 'active'" @click.prevent="tab = 'reports'">🚩 {{ __('Signalements') }} ({{ $counts['reports'] }})</button></li>
    </ul>

    {{-- TAB: Avis --}}
    <div x-show="tab === 'reviews'" x-cloak>
        @if($pendingReviews->isEmpty())
            <div class="text-center py-5 text-muted"><i data-lucide="check-circle" class="icon-lg mb-2"></i><p class="fs-5">{{ __('Aucun avis en attente') }} ✅</p></div>
        @else
            <div class="row">
            @foreach($pendingReviews as $review)
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $review->title }}</h6>
                                    <span class="text-warning">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                                </div>
                                <span class="badge bg-light text-dark">{{ $review->tool->name ?? '-' }}</span>
                            </div>
                            <p class="text-muted small mb-2">{{ Str::limit($review->body, 200) }}</p>
                            @if($review->pros)<p class="small mb-1"><span class="text-success fw-semibold">✅ :</span> {{ Str::limit($review->pros, 80) }}</p>@endif
                            @if($review->cons)<p class="small mb-1"><span class="text-danger fw-semibold">❌ :</span> {{ Str::limit($review->cons, 80) }}</p>@endif
                            <div class="d-flex justify-content-between align-items-center text-muted small mt-2">
                                <span>{{ $review->user->name ?? __('Anonyme') }}</span>
                                <span>{{ $review->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end gap-2">
                            <form action="{{ route('admin.directory.moderation.review.approve', $review->id) }}" method="POST">@csrf<button type="submit" class="btn btn-success btn-sm"><i data-lucide="check" class="icon-sm"></i> {{ __('Approuver') }}</button></form>
                            <form action="{{ route('admin.directory.moderation.review.reject', $review->id) }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Rejeter cet avis ?') }}')"><i data-lucide="x" class="icon-sm"></i> {{ __('Rejeter') }}</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        @endif
    </div>

    {{-- TAB: Ressources --}}
    <div x-show="tab === 'resources'" x-cloak>
        @if($pendingResources->isEmpty())
            <div class="text-center py-5 text-muted"><i data-lucide="check-circle" class="icon-lg mb-2"></i><p class="fs-5">{{ __('Aucune ressource en attente') }} ✅</p></div>
        @else
            <div class="row">
            @foreach($pendingResources as $resource)
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0">{{ $resource->title }}</h6>
                                <div class="d-flex gap-1">
                                    <span class="badge bg-primary">{{ ucfirst($resource->type) }}</span>
                                    <span class="badge {{ $resource->language === 'fr' ? 'bg-info' : 'bg-warning text-dark' }}">{{ strtoupper($resource->language) }}</span>
                                </div>
                            </div>
                            <a href="{{ $resource->url }}" target="_blank" class="text-decoration-none small d-block mb-2">{{ Str::limit($resource->url, 60) }} ↗</a>
                            @if($resource->video_summary)<p class="text-muted small mb-2">{{ Str::limit($resource->video_summary, 150) }}</p>@endif
                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span>{{ $resource->user->name ?? __('Anonyme') }} · {{ $resource->tool->name ?? '-' }}</span>
                                <span>{{ $resource->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end gap-2">
                            <form action="{{ route('admin.directory.moderation.resource.approve', $resource->id) }}" method="POST">@csrf<button type="submit" class="btn btn-success btn-sm"><i data-lucide="check" class="icon-sm"></i> {{ __('Approuver') }}</button></form>
                            <form action="{{ route('admin.directory.moderation.resource.reject', $resource->id) }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Rejeter cette ressource ?') }}')"><i data-lucide="x" class="icon-sm"></i> {{ __('Rejeter') }}</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        @endif
    </div>

    {{-- TAB: Suggestions --}}
    <div x-show="tab === 'suggestions'" x-cloak>
        @if($pendingSuggestions->isEmpty())
            <div class="text-center py-5 text-muted"><i data-lucide="check-circle" class="icon-lg mb-2"></i><p class="fs-5">{{ __('Aucune suggestion en attente') }} ✅</p></div>
        @else
            <div class="row">
            @foreach($pendingSuggestions as $suggestion)
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-secondary">{{ \Modules\Directory\Models\ToolSuggestion::fieldLabels()[$suggestion->field] ?? $suggestion->field }}</span>
                                    <strong class="ms-2">{{ $suggestion->tool->name ?? '-' }}</strong>
                                </div>
                            </div>
                            <div class="bg-light rounded p-2 mb-2 small"><strong>{{ __('Valeur suggérée :') }}</strong> {{ Str::limit($suggestion->suggested_value, 200) }}</div>
                            @if($suggestion->reason)<p class="text-muted small mb-1"><strong>{{ __('Raison :') }}</strong> {{ $suggestion->reason }}</p>@endif
                            <div class="d-flex justify-content-between align-items-center text-muted small mt-2">
                                <span>{{ $suggestion->user->name ?? __('Anonyme') }}</span>
                                <span>{{ $suggestion->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end gap-2">
                            <form action="{{ route('admin.directory.moderation.suggestion.approve', $suggestion->id) }}" method="POST">@csrf<button type="submit" class="btn btn-success btn-sm"><i data-lucide="check" class="icon-sm"></i> {{ __('Appliquer') }}</button></form>
                            <form action="{{ route('admin.directory.moderation.suggestion.reject', $suggestion->id) }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Rejeter cette suggestion ?') }}')"><i data-lucide="x" class="icon-sm"></i> {{ __('Rejeter') }}</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        @endif
    </div>

    {{-- TAB: Signalements --}}
    <div x-show="tab === 'reports'" x-cloak>
        @if($reports->isEmpty())
            <div class="text-center py-5 text-muted"><i data-lucide="check-circle" class="icon-lg mb-2"></i><p class="fs-5">{{ __('Aucun signalement') }} ✅</p></div>
        @else
            <div class="row">
            @foreach($reports as $report)
                @php $reasonLabels = ['spam' => 'Spam', 'inappropriate' => 'Inapproprié', 'off_topic' => 'Hors sujet', 'other' => 'Autre']; @endphp
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100 border-start border-danger border-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-danger">{{ $reasonLabels[$report->reason] ?? $report->reason }}</span>
                                    <span class="badge bg-dark ms-1">{{ class_basename($report->reportable_type) }}</span>
                                </div>
                            </div>
                            @if($report->comment)<p class="text-muted small mb-2">{{ $report->comment }}</p>@endif
                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span>{{ __('Signalé par') }} {{ $report->user->name ?? __('Anonyme') }}</span>
                                <span>{{ $report->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end gap-2">
                            <form action="{{ route('admin.directory.moderation.report.resolve', $report->id) }}" method="POST">@csrf<button type="submit" class="btn btn-warning btn-sm"><i data-lucide="check" class="icon-sm"></i> {{ __('Résolu') }}</button></form>
                            <form action="{{ route('admin.directory.moderation.report.delete', $report->id) }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Supprimer le contenu signalé ?') }}')"><i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        @endif
    </div>

    </div>{{-- /x-data --}}

@endsection
