<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Nouvelle exception')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">Nouvelle exception de date</h5></div>
    <div class="card-body">
        <form action="{{ route('admin.booking.date-overrides.store') }}" method="POST">
            @csrf
            @include('booking::admin.date-overrides._form')
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="{{ route('admin.booking.date-overrides.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
