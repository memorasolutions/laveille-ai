{{-- Ticket #2524 (2026-09-13) - écran d'administration MINIMAL des liaisons glossaire↔actualité.
     Gabarit repris tel quel de admin/sources/index.blade.php (même module, même patron table +
     badges + formulaire de bascule) : aucune charte inventée. Une seule action possible par
     ligne (basculer l'approbation) - un simple formulaire POST/PATCH suffit, le composant
     core::components.action-menu (kebab à plusieurs actions) serait sur-dimensionné pour une
     ligne à un seul geste. Aucun alert()/confirm()/prompt() natif : le bouton bascule
     directement, sans confirmation, exactement comme sources.toggle et articles.toggle
     ci-contre (une bascule reste réversible en un second clic - désapprouver n'est jamais une
     suppression, doctrine du projet).

     @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
     @project laveille.ai --}}
@extends('backoffice::layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">{{ __('Termes de glossaire liés') }}</h4>
            <a href="{{ route('admin.news.articles.index') }}" class="btn btn-secondary btn-sm">{{ __('Retour aux articles') }}</a>
        </div>

        <div class="mb-3 p-3 bg-light rounded">
            <strong>{{ $article->seo_title ?: $article->title }}</strong><br>
            <small class="text-muted"><a href="{{ route('news.show', $article) }}" target="_blank" rel="noopener">{{ __('Voir la fiche') }}</a></small>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Terme') }}</th>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Approbation') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($terms as $term)
                    <tr>
                        <td>
                            <a href="{{ $term->getPublicUrl() }}" target="_blank" rel="noopener">
                                {{ $term->getTranslation('name', app()->getLocale(), false) ?: $term->getTranslation('name', 'fr_CA', false) ?: (string) $term->name }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-{{ $term->pivot->source === 'auto' ? 'info text-dark' : 'secondary' }}">
                                {{ $term->pivot->source === 'auto' ? __('Automatique') : __('Manuelle') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $term->pivot->is_approved ? 'success' : 'danger' }}">
                                {{ $term->pivot->is_approved ? __('Approuvée') : __('Désapprouvée') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <form action="{{ route('admin.news.articles.terms.toggle', ['article' => $article, 'term' => $term]) }}" method="POST" class="d-inline m-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $term->pivot->is_approved ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                    {{ $term->pivot->is_approved ? __('Désapprouver') : __('Approuver') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Aucun terme de glossaire lié à cette actualité.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
