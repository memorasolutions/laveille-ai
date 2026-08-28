@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modération')])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
            <li class="breadcrumb-item active">Modération communauté</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="mb-1">{{ $pendingComments->count() }}</h5>
                    <small class="text-muted">Commentaires en attente</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="mb-1">{{ $pendingReviews->count() }}</h5>
                    <small class="text-muted">Avis en attente</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm {{ count($overdueReportIds) > 0 ? 'border-danger' : '' }}">
                <div class="card-body text-center">
                    <h5 class="mb-1">{{ $pendingReports->count() }}</h5>
                    <small class="text-muted">
                        Signalements en attente
                        @if (count($overdueReportIds) > 0)
                            <span class="badge text-bg-danger">{{ count($overdueReportIds) }} en retard (&gt;48h)</span>
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#comments" type="button">Commentaires</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews" type="button">Avis</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reports" type="button">Signalements</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="comments">
            @if($pendingComments->isEmpty())
                <div class="text-center py-5">
                    <i data-lucide="check-circle" style="width: 48px; height: 48px; color: #059669;"></i>
                    <p class="text-muted mt-2">Aucun commentaire en attente</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>#</th><th>Auteur</th><th>Contenu</th><th>Sur</th><th>Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($pendingComments as $comment)
                                <tr>
                                    <td>{{ $comment->id }}</td>
                                    <td>{{ $comment->user?->name ?? $comment->guest_name }}</td>
                                    <td>{{ Str::limit($comment->content, 100) }}</td>
                                    <td>{{ class_basename($comment->commentable_type) }}</td>
                                    <td>{{ format_date($comment->created_at, 'datetime') }}</td>
                                    <td class="d-flex gap-1">
                                        <form action="{{ route('admin.community.moderate') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="type" value="comment">
                                            <input type="hidden" name="id" value="{{ $comment->id }}">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm"><i data-lucide="check" style="width:14px;height:14px;"></i></button>
                                        </form>
                                        <form action="{{ route('admin.community.moderate') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="type" value="comment">
                                            <input type="hidden" name="id" value="{{ $comment->id }}">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="x" style="width:14px;height:14px;"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="reviews">
            @if($pendingReviews->isEmpty())
                <div class="text-center py-5">
                    <i data-lucide="check-circle" style="width: 48px; height: 48px; color: #059669;"></i>
                    <p class="text-muted mt-2">Aucun avis en attente</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>#</th><th>Auteur</th><th>Note</th><th>Contenu</th><th>Sur</th><th>Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($pendingReviews as $review)
                                <tr>
                                    <td>{{ $review->id }}</td>
                                    <td>{{ $review->user?->name ?? $review->guest_name }}</td>
                                    <td style="color: #F59E0B;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                                    <td>{{ Str::limit($review->content, 100) }}</td>
                                    <td>{{ class_basename($review->reviewable_type) }}</td>
                                    <td>{{ format_date($review->created_at, 'datetime') }}</td>
                                    <td class="d-flex gap-1">
                                        <form action="{{ route('admin.community.moderate') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="type" value="review">
                                            <input type="hidden" name="id" value="{{ $review->id }}">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm"><i data-lucide="check" style="width:14px;height:14px;"></i></button>
                                        </form>
                                        <form action="{{ route('admin.community.moderate') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="type" value="review">
                                            <input type="hidden" name="id" value="{{ $review->id }}">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="x" style="width:14px;height:14px;"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="reports">
            @if($pendingReports->isEmpty())
                <div class="text-center py-5">
                    <i data-lucide="check-circle" style="width: 48px; height: 48px; color: #059669;"></i>
                    <p class="text-muted mt-2">Aucun signalement en attente</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>#</th><th>Signalé par</th><th>Motif</th><th>Détails</th><th>Contenu</th><th>Reçu le</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($pendingReports as $report)
                                <tr class="{{ in_array($report->id, $overdueReportIds, true) ? 'table-danger' : '' }}">
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user?->name ?? '–' }}</td>
                                    <td>{{ $report->reason }}</td>
                                    <td>{{ Str::limit($report->details, 100) }}</td>
                                    <td>{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</td>
                                    <td>{{ format_date($report->created_at, 'datetime') }}</td>
                                    <td class="d-flex gap-1">
                                        <form action="{{ route('admin.community.reports.resolve', $report) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="resolved">
                                            <button type="submit" class="btn btn-success btn-sm" title="Marquer traité"><i data-lucide="check" style="width:14px;height:14px;"></i></button>
                                        </form>
                                        <form action="{{ route('admin.community.reports.resolve', $report) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="dismissed">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Rejeter"><i data-lucide="x" style="width:14px;height:14px;"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
