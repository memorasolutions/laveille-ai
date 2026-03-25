<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Mes contributions'))

@section('content')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="fw-semibold mb-1">
            <i data-lucide="heart-handshake" class="me-1"></i>
            {{ __('Mes contributions') }}
        </h1>
        <p class="text-muted mb-0">{{ __('Suivez vos suggestions et votes sur le site.') }}</p>
    </div>
</div>

{{-- Cartes statistiques --}}
<div class="row gy-4 mb-4">
    <div class="col-sm-6">
        <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i data-lucide="lightbulb" class="text-warning"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">{{ __('Suggestions soumises') }}</p>
                    <h4 class="fw-semibold text-warning mb-0">{{ $suggestions->count() }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i data-lucide="thumbs-up" class="text-primary"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">{{ __('Votes roadmap') }}</p>
                    <h4 class="fw-semibold text-primary mb-0">{{ $votes->count() }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Onglets --}}
<div class="card h-100 p-0 rounded-3">
    <div class="card-header border-bottom bg-body py-0 px-0">
        <ul class="nav nav-tabs" id="contributionsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="suggestions-tab" data-bs-toggle="tab" data-bs-target="#suggestions" type="button" role="tab" aria-controls="suggestions" aria-selected="true">
                    <i data-lucide="lightbulb" style="width:16px;height:16px;" class="me-1"></i>
                    {{ __('Suggestions') }} ({{ $suggestions->count() }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="votes-tab" data-bs-toggle="tab" data-bs-target="#votes" type="button" role="tab" aria-controls="votes" aria-selected="false">
                    <i data-lucide="thumbs-up" style="width:16px;height:16px;" class="me-1"></i>
                    {{ __('Votes roadmap') }} ({{ $votes->count() }})
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="contributionsTabContent">

            {{-- Onglet suggestions --}}
            <div class="tab-pane fade show active" id="suggestions" role="tabpanel" aria-labelledby="suggestions-tab">
                @if($suggestions->isEmpty())
                    <div class="py-5 text-center text-muted">
                        <i data-lucide="lightbulb" class="mb-2 d-block"></i>
                        <p class="mb-2">{{ __('Vous n\'avez pas encore soumis de suggestions.') }}</p>
                        <p class="small">{{ __('Visitez le glossaire, le répertoire ou les acronymes pour proposer des modifications.') }}</p>
                    </div>
                @else
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Fiche') }}</th>
                                    <th>{{ __('Champ') }}</th>
                                    <th>{{ __('Valeur suggérée') }}</th>
                                    <th>{{ __('Statut') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suggestions as $suggestion)
                                <tr>
                                    <td>
                                        @php
                                            $route = '#';
                                            if ($suggestion->suggestable) {
                                                $type = class_basename($suggestion->suggestable_type);
                                                $slug = $suggestion->suggestable->slug ?? '';
                                                if ($type === 'Tool' && Route::has('directory.show')) {
                                                    $route = route('directory.show', $slug);
                                                } elseif ($type === 'Term' && Route::has('dictionary.show')) {
                                                    $route = route('dictionary.show', $slug);
                                                } elseif ($type === 'Acronym' && Route::has('acronyms.show')) {
                                                    $route = route('acronyms.show', $slug);
                                                }
                                            }
                                            $source = $suggestion->getSourceLabel();
                                        @endphp
                                        <a href="{{ $route }}" class="fw-medium text-decoration-none">
                                            {{ $suggestion->getItemName() }}
                                        </a>
                                        <span class="badge fw-medium ms-1" style="background: {{ $source['color'] }}20; color: {{ $source['color'] }}; border: 1px solid {{ $source['color'] }}40;">
                                            {{ $source['name'] }}
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        {{ \Modules\Directory\Models\ToolSuggestion::fieldLabels()[$suggestion->field] ?? $suggestion->field }}
                                    </td>
                                    <td>
                                        <span title="{{ $suggestion->suggested_value }}">
                                            {{ Str::limit($suggestion->suggested_value, 80) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($suggestion->status === 'pending')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning fw-medium">{{ __('En attente') }}</span>
                                        @elseif($suggestion->status === 'approved')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success fw-medium">{{ __('Approuvée') }}</span>
                                        @elseif($suggestion->status === 'rejected')
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger fw-medium">{{ __('Rejetée') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $suggestion->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Onglet votes --}}
            <div class="tab-pane fade" id="votes" role="tabpanel" aria-labelledby="votes-tab">
                @if($votes->isEmpty())
                    <div class="py-5 text-center text-muted">
                        <i data-lucide="thumbs-up" class="mb-2 d-block"></i>
                        <p class="mb-2">{{ __('Vous n\'avez pas encore voté sur la roadmap.') }}</p>
                        @if(Route::has('directory.roadmap'))
                            <a href="{{ route('directory.roadmap') }}" class="btn btn-primary btn-sm rounded-2">
                                {{ __('Voir la roadmap') }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Idée') }}</th>
                                    <th>{{ __('Statut') }}</th>
                                    <th>{{ __('Date du vote') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($votes as $vote)
                                @if($vote->idea)
                                <tr>
                                    <td class="fw-medium">{{ $vote->idea->title }}</td>
                                    <td>
                                        @php $ideaColor = $vote->idea->status->color(); @endphp
                                        <span class="badge fw-medium" style="background: {{ $ideaColor }}20; color: {{ $ideaColor }}; border: 1px solid {{ $ideaColor }}40;">
                                            {{ $vote->idea->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $vote->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection
