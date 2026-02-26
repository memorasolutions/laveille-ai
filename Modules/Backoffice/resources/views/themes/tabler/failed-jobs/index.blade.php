@extends('backoffice::layouts.admin', ['title' => 'Jobs échoués', 'subtitle' => 'Outils'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Jobs échoués</h3>
        <form action="{{ route('admin.failed-jobs.destroy-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="ti ti-refresh me-1"></i> Relancer tous
            </button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Queue</th>
                    <th>Classe</th>
                    <th>Exception</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($failedJobs ?? [] as $job)
                <tr>
                    <td>{{ $job->id }}</td>
                    <td><span class="badge bg-secondary">{{ $job->queue }}</span></td>
                    <td>
                        <code class="text-truncate d-block" style="max-width: 200px;">
                            {{ Str::limit(class_basename(json_decode($job->payload)->displayName ?? ''), 40) }}
                        </code>
                    </td>
                    <td>
                        <code class="text-truncate d-block text-danger" style="max-width: 200px;">
                            {{ Str::limit($job->exception, 60) }}
                        </code>
                    </td>
                    <td class="text-muted">{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <form action="{{ route('admin.failed-jobs.retry', $job->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Relancer">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.failed-jobs.destroy', $job->id) }}" method="POST" onsubmit="return confirm('Supprimer ce job ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-circle-check mb-2 d-block text-success" style="font-size: 2rem;"></i>
                        Aucun job échoué
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
