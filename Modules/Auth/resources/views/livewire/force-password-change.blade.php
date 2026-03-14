<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Changer votre mot de passe'))
@section('content')
<h1 class="text-2xl font-bold leading-tight text-black">{{ __('Changer votre mot de passe') }}</h1>
<p class="mt-2 text-base text-gray-600">{{ __('Vous devez définir un nouveau mot de passe pour continuer.') }}</p>

@if(session('status'))
    <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded-md text-sm mb-6" role="alert">{{ session('status') }}</div>
@endif

<form action="{{ route('password.force-change.update') }}" method="POST">
    @csrf
    <div class="mb-5">
        <label for="force-password" class="text-base font-medium text-gray-900">{{ __('Nouveau mot de passe') }}</label>
        <div class="mt-2.5 relative text-gray-400 focus-within:text-gray-600">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <i class="ti ti-lock text-xl"></i>
            </div>
            <input id="force-password" name="password" type="password" class="block w-full py-4 ps-10 pe-12 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600" required autofocus>
            <button type="button" class="absolute inset-y-0 flex items-center cursor-pointer text-gray-400 hover:text-gray-600" style="right:0;padding-right:0.75rem" onclick="togglePasswordVisibility('force-password')" aria-label="{{ __('Afficher le mot de passe') }}">
                <i class="ti ti-eye text-xl"></i>
            </button>
        </div>
        @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="mb-5">
        <label for="force-password-confirm" class="text-base font-medium text-gray-900">{{ __('Confirmer le mot de passe') }}</label>
        <div class="mt-2.5 relative text-gray-400 focus-within:text-gray-600">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <i class="ti ti-lock text-xl"></i>
            </div>
            <input id="force-password-confirm" name="password_confirmation" type="password" class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600" required>
        </div>
    </div>

    <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0284c7">{{ __('Enregistrer le nouveau mot de passe') }}</button>
</form>

@push('scripts')
<script>
function togglePasswordVisibility(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ti-eye');
        icon.classList.add('ti-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('ti-eye-off');
        icon.classList.add('ti-eye');
    }
}
</script>
@endpush
@endsection
