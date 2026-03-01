<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Sauvegardes', 'subtitle' => 'Gestion'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Sauvegardes') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="hard-drive" class="icon-md text-primary"></i>{{ __('Sauvegardes') }}</h4>
    <form action="{{ route('admin.backups.run') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
            <i data-lucide="upload"></i>
            {{ __('Lancer une sauvegarde') }}
        </button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body p-0">
        @if(count($backups) === 0)
            <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center p-4">
                <i data-lucide="cloud" style="width:48px;height:48px;opacity:0.2;" class="text-muted mb-3"></i>
                <p class="text-muted fw-medium mb-1">{{ __('Aucune sauvegarde disponible.') }}</p>
                <p class="small text-muted mb-0">{{ __('Cliquez sur "Lancer une sauvegarde" pour créer votre première sauvegarde.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="fw-semibold small text-muted">{{ __('Nom du fichier') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Taille') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Date') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                        <tr>
                            <td class="align-middle small fw-medium text-body">{{ $backup['name'] }}</td>
                            <td class="align-middle small text-muted">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                            <td class="align-middle small text-muted">{{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('d/m/Y H:i') }}</td>
                            <td class="align-middle">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i data-lucide="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a href="{{ route('admin.backups.download', ['path' => $backup['path']]) }}"
                                               class="dropdown-item d-flex align-items-center gap-2">
                                                <i data-lucide="download"></i>
                                                {{ __('Télécharger') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.backups.delete') }}">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $backup['path'] }}">
                                                <button type="submit"
                                                        onclick="return confirm('{{ __('Supprimer cette sauvegarde ?') }}')"
                                                        class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                    <i data-lucide="trash-2"></i>
                                                    {{ __('Supprimer') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-0">
    <i data-lucide="info" class="flex-shrink-0 mt-1"></i>
    <span>{{ __('Les sauvegardes sont gérées par') }} <strong>spatie/laravel-backup</strong> {{ __("et s'exécutent en arrière-plan via la file d'attente Laravel.") }}</span>
</div>

@endsection
