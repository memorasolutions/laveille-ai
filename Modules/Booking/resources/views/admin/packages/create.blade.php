<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Nouveau forfait')

@section('content')
<div class="mb-4">
    <h4>Nouveau forfait</h4>
</div>

<form action="{{ route('admin.booking.packages.store') }}" method="POST">
    @csrf
    @include('booking::admin.packages._form')

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('admin.booking.packages.index') }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" class="icon-sm me-1"></i> Retour
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="check" class="icon-sm me-1"></i> Créer le forfait
        </button>
    </div>
</form>
@endsection
