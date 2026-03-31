@extends('backoffice::layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-4">{{ __('Ajouter une source RSS') }}</h4>

        <form action="{{ route('admin.news.sources.store') }}" method="POST">
            @csrf
            @include('news::admin.sources._form')
        </form>
    </div>
</div>
@endsection
