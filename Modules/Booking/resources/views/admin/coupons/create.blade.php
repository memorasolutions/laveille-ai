<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Nouveau coupon')

@section('content')
<div class="mb-4">
    <h4>Nouveau coupon</h4>
</div>

<form action="{{ route('admin.booking.coupons.store') }}" method="POST">
    @csrf
    @include('booking::admin.coupons._form')

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('admin.booking.coupons.index') }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" class="icon-sm me-1"></i> Retour
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="check" class="icon-sm me-1"></i> Créer le coupon
        </button>
    </div>
</form>
@endsection
