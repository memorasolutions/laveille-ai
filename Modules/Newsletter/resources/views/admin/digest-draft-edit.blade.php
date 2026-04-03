@extends('backoffice::themes.backend.master')

@section('title', 'Brouillon newsletter #' . $issue->week_number)

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="font-size:1.5rem;">
                    Brouillon newsletter #{{ $issue->week_number }}
                    @if($issue->status === 'draft')
                        <span class="badge bg-warning text-dark">Brouillon</span>
                    @elseif($issue->status === 'ready')
                        <span class="badge bg-info">Prêt</span>
                    @elseif($issue->status === 'sent')
                        <span class="badge bg-success">Envoyé</span>
                    @endif
                </h1>
                <span class="text-muted">Semaine {{ $issue->week_number }}, {{ $issue->year }}</span>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>Aperçu du contenu</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Fait marquant :</strong> {{ $data['highlight']->seo_title ?? $data['highlight']->title ?? 'aucun' }}</p>
                            <p><strong>Actualités :</strong> {{ ($data['topNews'] ?? collect())->count() }} articles</p>
                            <p><strong>Outil semaine :</strong> {{ $data['toolOfWeek']->name ?? 'aucun' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Terme IA :</strong> {{ $data['aiTerm']->name ?? 'aucun' }}</p>
                            <p><strong>Outil gratuit :</strong> {{ $data['interactiveTool']->name ?? 'aucun' }}</p>
                            <p><strong>Prompt :</strong> {{ is_array($data['weeklyPrompt'] ?? null) ? Str::limit($data['weeklyPrompt']['prompt'] ?? '', 80) : 'aucun' }}</p>
                        </div>
                    </div>
                    @if($data['editorial'] ?? null)
                    <div class="mt-2 p-3 bg-light rounded">
                        <strong>Éditorial IA :</strong> <em>{{ $data['editorial'] }}</em>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>Modifier le brouillon</strong></div>
                <div class="card-body">
                    <form action="{{ route('admin.newsletter.digest.update', $issue) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet de l'infolettre</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject', $issue->subject) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="editorial_edited" class="form-label">Éditorial personnalisé <small class="text-muted">(laissez vide pour garder la version IA)</small></label>
                            <textarea class="form-control" id="editorial_edited" name="editorial_edited" rows="4" placeholder="Votre éditorial personnalisé...">{{ old('editorial_edited', $issue->editorial_edited ?? '') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                    </form>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.newsletter.digest.preview', $issue) }}" class="btn btn-outline-secondary" target="_blank">Voir l'aperçu email</a>
                @if($issue->status !== 'sent')
                <form action="{{ route('admin.newsletter.digest.send', $issue) }}" method="POST" onsubmit="return confirm('Envoyer cette newsletter à tous les abonnés maintenant ?');">
                    @csrf
                    <button type="submit" class="btn btn-success">Envoyer maintenant</button>
                </form>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
