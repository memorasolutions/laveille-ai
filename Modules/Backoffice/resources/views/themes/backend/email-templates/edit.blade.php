<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Templates email', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.email-templates.index') }}">{{ __('Templates email') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Modifier') }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="icon-sm"></i>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i data-lucide="alert-circle" class="icon-sm"></i>
        {{ session('error') }}
    </div>
@endif

<div class="row g-4">

    {{-- Formulaire principal --}}
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="mail" class="icon-md text-primary"></i>
                        <h4 class="fw-bold mb-0">{{ $emailTemplate->name }}</h4>
                    </div>
                    <a href="{{ route('admin.email-templates.index') }}" class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-2">
                        <i data-lucide="arrow-left" class="icon-sm"></i>
                        Retour
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="subject" class="form-label fw-medium">
                            Sujet <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="subject" id="subject"
                            class="form-control @error('subject') is-invalid @enderror"
                            value="{{ old('subject', $emailTemplate->subject) }}" required>
                        @error('subject')
                            <span class="text-danger small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <x-editor::tiptap name="body_html" :value="old('body_html', $emailTemplate->body_html)" label="Corps du template" :required="true" />
                        @error('body_html')
                            <span class="text-danger small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="border rounded-3 p-4 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <span class="fw-medium">Activer ce template</span>
                                <p class="text-muted small mb-0">Le template sera utilisé pour les envois automatiques</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" id="is_active"
                                    class="form-check-input"
                                    {{ old('is_active', $emailTemplate->is_active) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i data-lucide="check" class="icon-sm"></i>
                            Sauvegarder
                        </button>
                        <a href="{{ route('admin.email-templates.preview', $emailTemplate) }}" target="_blank"
                            class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                            <i data-lucide="eye" class="icon-sm"></i>
                            Prévisualiser
                        </a>
                    </div>
                </form>

                <hr class="my-4">

                <form method="POST" action="{{ route('admin.email-templates.reset', $emailTemplate) }}"
                    onsubmit="return confirm('Restaurer ce template aux valeurs par défaut ?')">
                    @csrf
                    <button type="submit" class="btn btn-danger d-inline-flex align-items-center gap-2">
                        <i data-lucide="rotate-ccw" class="icon-sm"></i>
                        Restaurer les valeurs par défaut
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Variables disponibles --}}
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="code" class="icon-md text-primary"></i>
                    <h4 class="fw-semibold mb-0">Variables disponibles</h4>
                </div>
            </div>
            <div class="card-body p-4" x-data>
                <p class="text-muted small mb-3">Cliquez sur une variable pour la copier :</p>
                @foreach($variables as $var => $desc)
                    @php $varStr = '{{'.$var.'}}'; @endphp
                    <div class="mb-3">
                        <button type="button"
                            class="badge bg-primary bg-opacity-10 text-primary font-monospace border border-primary border-opacity-25 fw-medium"
                            style="cursor: pointer;"
                            @click="navigator.clipboard.writeText('{{ $varStr }}'); $dispatch('toast', {message: 'Variable copiée !', type: 'success'})"
                            title="Copier {{ $varStr }}">
                            {{ $varStr }}
                        </button>
                        <p class="text-muted small mt-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@endsection
