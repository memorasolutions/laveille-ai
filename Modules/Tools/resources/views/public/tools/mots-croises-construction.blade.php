@extends(fronttheme_layout())

@section('title', __('Outil mots croisés - En construction'))
@section('meta_description', __('Notre générateur de mots croisés est en phase finale de tests. Disponible publiquement très bientôt.'))

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 text-center">
      <div style="font-size:5rem;line-height:1;margin-bottom:1rem" aria-hidden="true">🚧</div>
      <h1 class="h2 mb-3" style="color:#053d4a;font-weight:700">{{ __('Outil en construction') }}</h1>
      <p class="lead mb-4" style="color:#475569;line-height:1.6">
        {{ __('Notre générateur de mots croisés est en phase finale de tests. Nous peaufinons les derniers détails pour vous offrir la meilleure expérience possible.') }}
      </p>
      <p class="mb-4" style="color:#64748b">
        {{ __('Cet outil sera disponible publiquement très bientôt. Merci pour votre patience !') }}
      </p>
      <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
        <a href="{{ url('/outils') }}" class="btn btn-primary btn-lg" style="background:#053d4a;border-color:#053d4a;min-width:200px">
          {{ __('Voir nos autres outils') }}
        </a>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-lg" style="min-width:200px">
          {{ __('Retour à l\'accueil') }}
        </a>
      </div>
    </div>
  </div>
</div>
@endsection
