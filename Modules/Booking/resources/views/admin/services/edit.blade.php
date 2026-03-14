<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Modifier le service')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">Modifier le service</h5></div>
    <div class="card-body">
        <form action="{{ route('admin.booking.services.update', $service) }}" method="POST">
            @csrf @method('PUT')
            @include('booking::admin.services._form', ['service' => $service])
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.booking.services.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
