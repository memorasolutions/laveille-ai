{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Mes sondages Décido · ' . config('app.name'))
@section('meta_description', "Gère tes sondages Décido : crée, consulte et exporte les résultats de tes consultations collectives.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mes sondages Décido'])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2">Mes sondages Décido</h1>
                    <x-core::button :href="route('decido.create')" variant="primary">+ Créer un sondage</x-core::button>
                </div>

                @if($polls->isEmpty())
                    <div class="alert alert-info d-flex flex-column align-items-center justify-content-center text-center py-5">
                        <p class="mb-3">Tu n'as pas encore créé de sondage Décido.</p>
                        <x-core::button :href="route('decido.create')" variant="primary">+ Créer un sondage</x-core::button>
                    </div>
                @else
                    <div class="list-group">
                        @foreach($polls as $poll)
                            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 py-3">
                                <div>
                                    <h3 class="h5 mb-1">{{ $poll->title }}</h3>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <span class="badge rounded-pill" style="background-color: {{ $poll->type->value === 'date' ? 'var(--c-primary)' : 'var(--c-accent)' }}; color: white;">
                                            {{ $poll->type->label() }}
                                        </span>
                                        @php
                                            // Décido round 6 (skill /100) : #6c757d (gris Bootstrap générique)
                                            // n'atteint que 4.69:1 en texte blanc - passe l'AA (4.5:1) mais
                                            // échoue l'AAA (7:1) exigé par la charte du projet. --c-dark est
                                            // déjà validé AAA sur blanc ailleurs sur le site.
                                            $statusColor = match ($poll->status->value) {
                                                'open' => 'var(--c-primary)',
                                                'closed' => 'var(--c-dark)',
                                                'draft' => 'var(--c-accent)',
                                                default => 'var(--c-dark)',
                                            };
                                        @endphp
                                        <span class="badge rounded-pill" style="background-color: {{ $statusColor }}; color: white;">
                                            {{ $poll->status->label() }}
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Créé le {{ $poll->created_at->locale('fr')->isoFormat('LL') }}
                                    </small>
                                </div>
                                <x-core::button :href="route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'proprietaire'])" variant="ghost" size="sm">Gérer</x-core::button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
