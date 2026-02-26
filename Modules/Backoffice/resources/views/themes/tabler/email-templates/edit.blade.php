@extends('backoffice::layouts.admin', ['title' => 'Templates email', 'subtitle' => 'Modifier'])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">
                    <i class="ti ti-mail me-2"></i> {{ $emailTemplate->name }}
                </h3>
                <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i> Retour
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="subject" class="form-label required">Sujet</label>
                        <input type="text" name="subject" id="subject"
                            class="form-control @error('subject') is-invalid @enderror"
                            value="{{ old('subject', $emailTemplate->subject) }}" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <x-editor::tiptap name="body_html" :value="old('body_html', $emailTemplate->body_html)" label="Corps du template" :required="true" />
                        @error('body_html')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                class="form-check-input"
                                {{ old('is_active', $emailTemplate->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">Activer ce template</span>
                        </label>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Sauvegarder
                        </button>
                        <a href="{{ route('admin.email-templates.preview', $emailTemplate) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="ti ti-eye me-1"></i> Prévisualiser
                        </a>
                    </div>
                </form>

                <hr class="my-4">

                <form method="POST" action="{{ route('admin.email-templates.reset', $emailTemplate) }}"
                    onsubmit="return confirm('Restaurer ce template aux valeurs par défaut ?')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="ti ti-refresh me-1"></i> Restaurer les valeurs par défaut
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-variable me-2"></i> Variables disponibles
                </h3>
            </div>
            <div class="card-body" x-data>
                <p class="text-muted small mb-3">Cliquez sur une variable pour la copier :</p>
                @foreach($variables as $var => $desc)
                    @php $varStr = '{{'.$var.'}}'; @endphp
                    <div class="mb-3">
                        <button type="button"
                            class="badge bg-primary-lt text-primary border-0"
                            style="cursor: pointer;"
                            @click="navigator.clipboard.writeText('{{ $varStr }}'); $dispatch('toast', {message: 'Variable copiée !', type: 'success'})"
                            title="Copier {{ $varStr }}">
                            {{ $varStr }}
                        </button>
                        <br>
                        <small class="text-muted">{{ $desc }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
