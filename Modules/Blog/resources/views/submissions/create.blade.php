<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Proposer un article') . ' - ' . config('app.name'))
@section('meta_description', __('Soumettez un article pour publication sur La veille. Partagez vos connaissances en IA et technologies avec notre communauté.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Proposer un article')])
@endsection

@push('styles')
<style>
    .sub-card {
        background: #fff; border-radius: var(--r-base); box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        padding: 36px; max-width: 720px; margin: 20px auto 60px; border: 1px solid #E5E7EB;
    }
    .sub-title { font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 20px; text-align: center; font-size: 1.6rem; font-weight: 700; }
    .sub-info { background: #F0F9FF; border-left: 4px solid var(--c-primary); padding: 14px 18px; margin-bottom: 24px; font-size: 14px; color: #4B5563; border-radius: 0 var(--r-base) var(--r-base) 0; }
    .sub-btn { background: var(--c-primary); color: #fff; border: none; padding: 12px 32px; border-radius: var(--r-btn); font-weight: 600; font-size: 16px; cursor: pointer; transition: background 0.2s; }
    .sub-btn:hover { background: var(--c-dark); color: #fff; }
    .sub-label { font-weight: 600; margin-bottom: 6px; display: block; color: var(--c-dark); }
    .sub-input, .sub-textarea, .sub-select { width: 100%; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 10px 14px; font-size: 15px; margin-bottom: 16px; }
    .sub-input:focus, .sub-textarea:focus, .sub-select:focus { border-color: var(--c-primary); outline: none; box-shadow: 0 0 0 2px rgba(11,114,133,0.15); }
    .sub-error { color: #DC2626; font-size: 13px; margin-top: -12px; margin-bottom: 12px; }
    .sub-guest { text-align: center; padding: 60px 20px; }
</style>
@endpush

@section('content')
<div class="container">
    <div class="sub-card">
        <h1 class="sub-title">✍️ {{ __('Proposer un article') }}</h1>

        @auth
            @if(session('success'))
                <div class="alert alert-success" style="text-align: center;">{{ session('success') }}</div>
            @endif

            <div class="sub-info">
                {{ __('Votre article sera examiné par notre équipe éditoriale. Si approuvé, il sera publié sous votre nom dans le blog. Vous serez notifié par courriel.') }}
            </div>

            <form action="{{ route('blog.submissions.store') }}" method="POST">
                @csrf

                <label class="sub-label" for="title">{{ __('Titre') }} <span style="color: #DC2626;">*</span></label>
                <input type="text" name="title" id="title" class="sub-input" value="{{ old('title') }}" maxlength="255" required aria-label="{{ __('Titre de l\'article') }}">
                @error('title') <p class="sub-error">{{ $message }}</p> @enderror

                <label class="sub-label" for="category_id">{{ __('Catégorie') }} <span style="color: #DC2626;">*</span></label>
                <select name="category_id" id="category_id" class="sub-select" required aria-label="{{ __('Catégorie de l\'article') }}">
                    <option value="">-- {{ __('Choisir une catégorie') }} --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="sub-error">{{ $message }}</p> @enderror

                <label class="sub-label" for="excerpt">{{ __('Résumé') }} <small style="color: #9CA3AF; font-weight: 400;">({{ __('optionnel') }})</small></label>
                <textarea name="excerpt" id="excerpt" class="sub-textarea" rows="3" maxlength="500" placeholder="{{ __('Résumé de votre article en 1-2 phrases') }}" aria-label="{{ __('Résumé') }}">{{ old('excerpt') }}</textarea>
                @error('excerpt') <p class="sub-error">{{ $message }}</p> @enderror

                <label class="sub-label" for="content">{{ __('Contenu de l\'article') }} <span style="color: #DC2626;">*</span></label>
                <textarea name="content" id="content" class="sub-textarea" rows="15" required placeholder="{{ __('Écrivez votre article ici... Minimum 200 caractères.') }}" aria-label="{{ __('Contenu de l\'article') }}">{{ old('content') }}</textarea>
                @error('content') <p class="sub-error">{{ $message }}</p> @enderror

                <div style="text-align: center; margin-top: 24px;">
                    <button type="submit" class="sub-btn">{{ __('Soumettre mon article') }}</button>
                </div>
            </form>
        @else
            <div class="sub-guest">
                <div style="font-size: 48px; margin-bottom: 16px;">✍️</div>
                <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('Connectez-vous pour proposer un article') }}</h3>
                <p style="color: #6B7280; margin-bottom: 20px;">{{ __('Partagez vos connaissances avec notre communauté.') }}</p>
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" style="background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none;">{{ __('Se connecter') }}</a>
                @endif
            </div>
        @endauth
    </div>
</div>
@endsection
