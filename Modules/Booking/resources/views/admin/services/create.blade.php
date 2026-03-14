<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Nouveau service')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">Nouveau service</h5></div>
    <div class="card-body">
        <form action="{{ route('admin.booking.services.store') }}" method="POST">
            @csrf
            @include('booking::admin.services._form')
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="{{ route('admin.booking.services.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
