<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Sauvegardes', 'subtitle' => 'Gestion'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Sauvegardes') }}</li>
    </ol>
</nav>

<div x-data="{
    selected: [],
    get allSelected() { return this.selected.length === {{ count($backups) }} && this.selected.length > 0 },
    toggleAll() { this.selected = this.allSelected ? [] : {{ Js::from(array_column($backups, 'path')) }} }
}">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="hard-drive" class="icon-md text-primary"></i>{{ __('Sauvegardes') }}
            @if(count($backups) > 0)
                @php $totalMB = round(array_sum(array_column($backups, 'size')) / 1024 / 1024, 1); @endphp
                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal fs-6">{{ $totalMB }} MB · {{ count($backups) }} {{ __('fichier(s)') }}</span>
            @endif
        </h4>
        <form action="{{ route('admin.backups.run') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="upload"></i>
                {{ __('Lancer une sauvegarde') }}
            </button>
        </form>
    </div>

    {{-- Bulk actions bar --}}
    <div x-show="selected.length > 0" x-cloak
         class="d-flex flex-wrap align-items-center gap-3 mb-3 px-3 py-2 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded">
        <span class="fw-medium text-body">
            <span x-text="selected.length"></span> {{ __('sélectionnée(s)') }}
        </span>
        <button type="button" @click="if(confirm('{{ __('Supprimer les sauvegardes sélectionnées ?') }}')) {
            const form = document.getElementById('bulkDeleteForm');
            form.querySelectorAll('input[name=\'paths[]\']').forEach(el => el.remove());
            selected.forEach(path => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'paths[]'; input.value = path;
                form.appendChild(input);
            });
            form.submit();
        }" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1">
            <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer la sélection') }}
        </button>
        <button type="button" @click="selected = []" class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
            <i data-lucide="x" class="icon-sm"></i> {{ __('Annuler') }}
        </button>
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
                                <th style="width:40px">
                                    <input type="checkbox" class="form-check-input" style="cursor:pointer"
                                           :checked="allSelected" @change="toggleAll()"
                                           aria-label="{{ __('Tout sélectionner') }}">
                                </th>
                                <th class="fw-semibold small text-muted">{{ __('Nom du fichier') }}</th>
                                <th class="fw-semibold small text-muted">{{ __('Taille') }}</th>
                                <th class="fw-semibold small text-muted">{{ __('Date') }}</th>
                                <th class="fw-semibold small text-muted" style="width:80px">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                            <tr :class="{ 'table-active': selected.includes('{{ $backup['path'] }}') }">
                                <td class="align-middle">
                                    <input type="checkbox" class="form-check-input" style="cursor:pointer"
                                           x-model="selected" value="{{ $backup['path'] }}"
                                           aria-label="{{ __('Sélectionner') }} {{ $backup['name'] }}">
                                </td>
                                <td class="align-middle small fw-medium text-body">{{ $backup['name'] }}</td>
                                <td class="align-middle small text-muted">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                                <td class="align-middle small text-muted">{{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('d/m/Y H:i') }}</td>
                                <td class="align-middle">
                                    <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                                        <button @click="open = !open"
                                                class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center"
                                                style="width:32px;height:32px;"
                                                aria-label="{{ __('Actions pour') }} {{ $backup['name'] }}">
                                            <i data-lucide="more-vertical" class="icon-sm"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" :class="{ show: open }" x-show="open" x-cloak style="min-width:140px">
                                            <li>
                                                <a href="{{ route('admin.backups.download', ['path' => $backup['path']]) }}"
                                                   class="dropdown-item d-flex align-items-center gap-2">
                                                    <i data-lucide="download" class="icon-sm text-info"></i>
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
                                                        <i data-lucide="trash-2" class="icon-sm"></i>
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

    <form id="bulkDeleteForm" method="POST" action="{{ route('admin.backups.bulk-delete') }}" class="d-none">
        @csrf @method('DELETE')
    </form>

</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-0">
    <i data-lucide="info" class="flex-shrink-0 mt-1"></i>
    <span>{{ __('Les sauvegardes sont gérées par') }} <strong>spatie/laravel-backup</strong> {{ __("et s'exécutent en arrière-plan via la file d'attente Laravel.") }}</span>
</div>

@endsection
