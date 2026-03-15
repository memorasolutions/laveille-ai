<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php use Modules\Roadmap\Enums\IdeaStatus; @endphp

@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Statistiques Roadmap'))

@section('content')
    @include('backoffice::themes.backend.components.breadcrumb', [
        'title' => __('Statistiques Roadmap'),
        'items' => [
            ['label' => 'Roadmap'],
            ['label' => __('Statistiques')],
        ],
    ])

    {{-- KPIs --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('Total idées') }}</h6>
                        <h2 class="mb-0">{{ number_format($totalIdeas) }}</h2>
                    </div>
                    <i data-lucide="lightbulb" style="width:32px;height:32px;opacity:.7;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('Total votes') }}</h6>
                        <h2 class="mb-0">{{ number_format($totalVotes) }}</h2>
                    </div>
                    <i data-lucide="thumbs-up" style="width:32px;height:32px;opacity:.7;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('Tableaux') }}</h6>
                        <h2 class="mb-0">{{ number_format($totalBoards) }}</h2>
                    </div>
                    <i data-lucide="layout-grid" style="width:32px;height:32px;opacity:.7;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('Participation') }}</h6>
                        <h2 class="mb-0">
                            {{ $totalIdeas > 0 ? round($totalVotes / $totalIdeas, 1) : 0 }} {{ __('votes/idée') }}
                        </h2>
                    </div>
                    <i data-lucide="trending-up" style="width:32px;height:32px;opacity:.7;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Ideas by status --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('Idées par statut') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Statut') }}</th>
                                <th class="text-end">{{ __('Nombre') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ideasByStatus as $statusValue => $count)
                                @php $s = IdeaStatus::from($statusValue); @endphp
                                <tr>
                                    <td><span class="badge" style="background-color: {{ $s->color() }};">{{ $s->label() }}</span></td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top ideas --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('Top 10 idées') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Titre') }}</th>
                                <th>{{ __('Tableau') }}</th>
                                <th class="text-end">{{ __('Votes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topIdeas as $idea)
                                <tr>
                                    <td><a href="{{ route('admin.roadmap.ideas.show', $idea) }}">{{ Str::limit($idea->title, 40) }}</a></td>
                                    <td>{{ $idea->board->name ?? '—' }}</td>
                                    <td class="text-end"><span class="badge bg-primary">{{ $idea->vote_count }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent ideas --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Idées récentes') }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Titre') }}</th>
                        <th>{{ __('Auteur') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentIdeas as $idea)
                        <tr>
                            <td>{{ Str::limit($idea->title, 50) }}</td>
                            <td>{{ $idea->user->name ?? __('Anonyme') }}</td>
                            <td><span class="badge" style="background-color: {{ $idea->status->color() }};">{{ $idea->status->label() }}</span></td>
                            <td>{{ $idea->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
