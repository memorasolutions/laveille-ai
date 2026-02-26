@extends('backoffice::layouts.admin', ['title' => 'Infos système', 'subtitle' => 'Outils'])
@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Informations système</h3></div>
    <div class="card-body">
        <div class="datagrid">
            @foreach($systemInfo ?? [] as $key => $value)
            <div class="datagrid-item">
                <div class="datagrid-title">{{ $key }}</div>
                <div class="datagrid-content">{{ is_array($value) ? json_encode($value) : $value }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
