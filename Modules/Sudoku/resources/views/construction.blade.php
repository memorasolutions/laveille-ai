<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Sudoku — En construction')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #0B7285 0%, #094a56 100%); padding: 32px; text-align: center;">
                    <div style="font-size: 64px; line-height: 1; margin-bottom: 12px;" aria-hidden="true">🔧</div>
                    <h1 class="h3 mb-0" style="color: #fff; font-weight: 800;">{{ __('Sudoku quotidien') }}</h1>
                    <p class="mb-0 mt-2" style="color: rgba(255,255,255,0.9); font-size: 15px;">
                        {{ __('Outil en construction') }}
                    </p>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="lead mb-3" style="color: #1f2937;">
                        {{ __('Nous mettons la derniere main aux 5 difficultes, classements et streak quotidien.') }}
                    </p>
                    <p class="text-muted mb-4">
                        {{ __('Revenez tres bientot pour jouer la grille du jour.') }}
                    </p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="{{ url('/outils') }}" class="btn btn-primary" style="background:#0B7285; border-color:#0B7285;">
                            ← {{ __('Tous les outils gratuits') }}
                        </a>
                        <a href="{{ url('/outils/mots-croises') }}" class="btn btn-outline-secondary">
                            {{ __('Essayer les mots croises') }}
                        </a>
                    </div>

                    <hr class="my-4">
                    <small class="text-muted">
                        {{ __('Vous serez prevenu via la veille hebdo des que c\'est pret.') }}
                        <a href="{{ url('/') }}#newsletter" class="text-decoration-none">{{ __('S\'abonner') }}</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
