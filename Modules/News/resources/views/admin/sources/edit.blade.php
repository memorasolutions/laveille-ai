@extends('backoffice::layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-4">{{ __('Modifier la source') }} : {{ $source->name }}</h4>

        <form action="{{ route('admin.news.sources.update', $source) }}" method="POST">
            @csrf @method('PUT')
            @include('news::admin.sources._form', ['source' => $source])
        </form>
    </div>
</div>
@endsection
