<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Prévisualisation import')
@section('content')
<div class="page-content">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.import.index') }}">Import</a></li>
            <li class="breadcrumb-item active">Prévisualisation</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Prévisualisation - {{ \Modules\Import\Services\ImportService::MODEL_TYPES[$modelType] ?? $modelType }}</h4>
        <a href="{{ route('admin.import.index') }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Retour
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Aperçu des données ({{ count($preview['rows']) }} lignes)</h5>
        </div>
        <div class="card-body">
            @if(empty($preview['rows']))
                <div class="alert alert-warning mb-0">Aucune donnée trouvée dans le fichier.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                @foreach($preview['headers'] as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['rows'] as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ Str::limit((string) $cell, 80) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Correspondance des colonnes</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.import.execute') }}" method="POST">
                @csrf
                <input type="hidden" name="model_type" value="{{ $modelType }}">
                <input type="hidden" name="file_path" value="{{ $filePath }}">
                <input type="hidden" name="format" value="{{ $format }}">

                <div class="table-responsive mb-3">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%">Colonne du fichier</th>
                                <th style="width: 40%">Correspond au champ</th>
                                <th style="width: 30%">Exemple</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['headers'] as $index => $header)
                                <tr>
                                    <td><strong>{{ $header }}</strong></td>
                                    <td>
                                        <select name="mapping[{{ $index }}]" class="form-select form-select-sm">
                                            <option value="">Ignorer</option>
                                            @foreach($availableFields as $field)
                                                <option value="{{ $field }}"
                                                    @if(strtolower((string) $header) === strtolower($field)) selected @endif
                                                >{{ $field }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-muted small">
                                        @if(isset($preview['rows'][0][$index]))
                                            {{ Str::limit((string) $preview['rows'][0][$index], 50) }}
                                        @else
                                            <em>-</em>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info">
                    <i data-lucide="info" style="width: 16px; height: 16px;"></i>
                    Les colonnes sans correspondance seront ignorées. Les articles et pages seront importés en statut brouillon.
                </div>

                <button type="submit" class="btn btn-success" onclick="return confirm('Lancer l\'import de ces données ?')">
                    <i data-lucide="play" style="width: 16px; height: 16px;"></i> Lancer l'import
                </button>
                <a href="{{ route('admin.import.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
@endsection
