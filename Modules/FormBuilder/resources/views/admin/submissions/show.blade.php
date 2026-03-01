@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Soumission #' . $submission->id])

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <div>
        <h4 class="mb-3 mb-md-0">Soumission #{{ $submission->id }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.formbuilder.forms.index') }}">Formulaires</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.formbuilder.forms.submissions.index', $form) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">#{{ $submission->id }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.formbuilder.forms.submissions.index', $form) }}" class="btn btn-secondary">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Retour
        </a>
        <form action="{{ route('admin.formbuilder.forms.submissions.destroy', [$form, $submission]) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-danger" onclick="if(confirm('Supprimer définitivement ?')) this.form.submit()">
                <i data-lucide="trash-2" style="width:16px;height:16px;"></i> Supprimer
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Données soumises</h6>
                <dl class="row mb-0">
                    @foreach($submission->data as $key => $value)
                        <dt class="col-sm-4 text-muted">{{ $key }}</dt>
                        <dd class="col-sm-8">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                        @if(!$loop->last) <hr class="my-2"> @endif
                    @endforeach
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Métadonnées</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        Date <span>{{ $submission->created_at->format('d/m/Y H:i:s') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        IP <span>{{ $submission->ip_address }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        Statut
                        @if($submission->isNew())
                            <span class="badge bg-primary">Nouveau</span>
                        @else
                            <span class="badge bg-secondary">Lu</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
