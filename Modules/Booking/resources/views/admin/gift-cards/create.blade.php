<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Nouvelle carte-cadeau')

@section('content')
<div class="mb-4">
    <h4>Nouvelle carte-cadeau</h4>
</div>

<form action="{{ route('admin.booking.gift-cards.store') }}" method="POST">
    @csrf
    @include('booking::admin.gift-cards._form')

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('admin.booking.gift-cards.index') }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" class="icon-sm me-1"></i> Retour
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="check" class="icon-sm me-1"></i> Créer la carte-cadeau
        </button>
    </div>
</form>
@endsection
