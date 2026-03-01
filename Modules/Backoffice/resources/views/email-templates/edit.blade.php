@extends('backoffice::layouts.admin')
@section('title', 'Modifier template - ' . $emailTemplate->name)
@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.email-templates.index') }}">Templates email</a></li>
            <li class="breadcrumb-item active">{{ $emailTemplate->name }}</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-2">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card rounded-2">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $emailTemplate->name }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet</label>
                            <input type="text" name="subject" id="subject" class="form-control rounded-2" value="{{ old('subject', $emailTemplate->subject) }}" required>
                            @error('subject') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <x-editor::tiptap name="body_html" :value="old('body_html', $emailTemplate->body_html)" label="Corps du template" :required="true" />
                            @error('body_html') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $emailTemplate->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Activer ce template</label>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary rounded-2">Sauvegarder</button>
                            <a href="{{ route('admin.email-templates.preview', $emailTemplate) }}" target="_blank" class="btn btn-outline-primary rounded-2">Prévisualiser</a>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.email-templates.reset', $emailTemplate) }}" class="mt-3" x-data onsubmit="return confirm('Restaurer ce template aux valeurs par défaut ?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger rounded-2">Restaurer les valeurs par défaut</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card rounded-2">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Variables disponibles</h5>
                </div>
                <div class="card-body" x-data>
                    <p class="text-muted mb-3">Cliquez sur une variable pour la copier :</p>
                    @foreach($variables as $var => $desc)
                        @php $varStr = '{{'.$var.'}}'; @endphp
                        <div class="mb-2">
                            <button type="button"
                                    class="badge bg-primary bg-opacity-10 text-primary border-0"
                                    style="cursor:pointer"
                                    @click="navigator.clipboard.writeText('{{ $varStr }}'); $dispatch('toast', {message: 'Variable copiée !', type: 'success'})"
                                    title="Copier {{ $varStr }}">
                                {{ $varStr }}
                            </button>
<br><small class="text-muted">{{ $desc }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
