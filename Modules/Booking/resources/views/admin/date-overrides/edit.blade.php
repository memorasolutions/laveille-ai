<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Modifier l\'exception')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">Modifier l'exception</h5></div>
    <div class="card-body">
        <form action="{{ route('admin.booking.date-overrides.update', $dateOverride) }}" method="POST">
            @csrf @method('PUT')
            @include('booking::admin.date-overrides._form', ['override' => $dateOverride])
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.booking.date-overrides.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
