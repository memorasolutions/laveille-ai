<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mes contributions') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    <i class="fa fa-handshake-o"></i> {{ __('Mes contributions') }}
</h2>
<p style="color: #777; margin: 0 0 25px;">{{ __('Suivez vos suggestions et votes sur le site.') }}</p>

{{-- Cartes statistiques --}}
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-4" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-lightbulb-o fa-2x" style="color: #f0ad4e;"></i>
                <h3 style="margin: 5px 0 0;">{{ $suggestions->count() }}</h3>
                <small style="color: #777;">{{ __('Suggestions') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-4" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-thumbs-up fa-2x" style="color: #337ab7;"></i>
                <h3 style="margin: 5px 0 0;">{{ $votes->count() }}</h3>
                <small style="color: #777;">{{ __('Votes roadmap') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-4" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-book fa-2x" style="color: #5bc0de;"></i>
                <h3 style="margin: 5px 0 0;">{{ $resources->count() }}</h3>
                <small style="color: #777;">{{ __('Ressources') }}</small>
            </div>
        </div>
    </div>
</div>

{{-- Onglets --}}
<div class="panel panel-default">
    <div class="panel-heading" style="padding: 0;">
        <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 0; border-bottom: none;">
            <li role="presentation" class="active">
                <a href="#suggestions" aria-controls="suggestions" role="tab" data-toggle="tab">
                    <i class="fa fa-lightbulb-o"></i> {{ __('Suggestions') }} ({{ $suggestions->count() }})
                </a>
            </li>
            <li role="presentation">
                <a href="#votes" aria-controls="votes" role="tab" data-toggle="tab">
                    <i class="fa fa-thumbs-up"></i> {{ __('Votes') }} ({{ $votes->count() }})
                </a>
            </li>
            <li role="presentation">
                <a href="#resources" aria-controls="resources" role="tab" data-toggle="tab">
                    <i class="fa fa-book"></i> {{ __('Ressources') }} ({{ $resources->count() }})
                </a>
            </li>
        </ul>
    </div>
    <div class="panel-body" style="padding: 0;">
        <div class="tab-content">

            {{-- Onglet suggestions --}}
            <div role="tabpanel" class="tab-pane active" id="suggestions">
                @if($suggestions->isEmpty())
                    <div style="text-align: center; padding: 40px 20px; color: #999;">
                        <i class="fa fa-lightbulb-o fa-3x" style="margin-bottom: 10px; display: block;"></i>
                        <p>{{ __('Vous n\'avez pas encore soumis de suggestions.') }}</p>
                        <p><small>{{ __('Visitez le glossaire, le répertoire ou les acronymes pour proposer des modifications.') }}</small></p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped" style="margin-bottom: 0;">
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
                                        <a href="{{ $route }}">{{ $suggestion->getItemName() }}</a>
                                        <span class="label" style="background: {{ $source['color'] }}; color: #fff; margin-left: 5px;">{{ $source['name'] }}</span>
                                    </td>
                                    <td style="color: #777;">
                                        {{ \Modules\Directory\Models\ToolSuggestion::fieldLabels()[$suggestion->field] ?? $suggestion->field }}
                                    </td>
                                    <td>
                                        <span title="{{ $suggestion->suggested_value }}">
                                            {{ Str::limit($suggestion->suggested_value, 80) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($suggestion->status === 'pending')
                                            <span class="label label-warning">{{ __('En attente') }}</span>
                                        @elseif($suggestion->status === 'approved')
                                            <span class="label label-success">{{ __('Approuvée') }}</span>
                                        @elseif($suggestion->status === 'rejected')
                                            <span class="label label-danger">{{ __('Rejetée') }}</span>
                                        @endif
                                    </td>
                                    <td style="color: #777;">{{ $suggestion->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Onglet votes --}}
            <div role="tabpanel" class="tab-pane" id="votes">
                @if($votes->isEmpty())
                    <div style="text-align: center; padding: 40px 20px; color: #999;">
                        <i class="fa fa-thumbs-up fa-3x" style="margin-bottom: 10px; display: block;"></i>
                        <p>{{ __('Vous n\'avez pas encore voté sur la roadmap.') }}</p>
                        @if(Route::has('directory.roadmap'))
                            <a href="{{ route('directory.roadmap') }}" class="btn btn-primary btn-sm">
                                {{ __('Voir la roadmap') }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped" style="margin-bottom: 0;">
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
                                    <td style="font-weight: 600;">{{ $vote->idea->title }}</td>
                                    <td>
                                        @php $ideaColor = $vote->idea->status->color(); @endphp
                                        <span class="label" style="background: {{ $ideaColor }}; color: #fff;">
                                            {{ $vote->idea->status->label() }}
                                        </span>
                                    </td>
                                    <td style="color: #777;">{{ $vote->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Onglet ressources --}}
            <div role="tabpanel" class="tab-pane" id="resources">
                @if($resources->isEmpty())
                    <div style="text-align: center; padding: 40px 20px; color: #999;">
                        <i class="fa fa-book fa-3x" style="margin-bottom: 10px; display: block;"></i>
                        <p>{{ __('Vous n\'avez pas encore soumis de ressources.') }}</p>
                        @if(Route::has('directory.index'))
                            <a href="{{ route('directory.index') }}" class="btn btn-primary btn-sm">
                                {{ __('Voir le répertoire') }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>{{ __('Titre') }}</th>
                                    <th>{{ __('Outil') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Statut') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resources as $resource)
                                <tr>
                                    <td>
                                        <a href="{{ $resource->url }}" target="_blank" rel="nofollow noopener">
                                            {{ Str::limit($resource->title, 50) }}
                                        </a>
                                    </td>
                                    <td style="color: #777;">{{ $resource->tool->name ?? '—' }}</td>
                                    <td><span class="label label-info">{{ $resource->type }}</span></td>
                                    <td>
                                        @if($resource->is_approved)
                                            <span class="label label-success">{{ __('Approuvée') }}</span>
                                        @else
                                            <span class="label label-warning">{{ __('En attente') }}</span>
                                        @endif
                                    </td>
                                    <td style="color: #777;">{{ $resource->created_at->format('d/m/Y') }}</td>
                                </tr>
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
