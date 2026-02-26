@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:danger-triangle-outline" class="icon text-xl"></iconify-icon>
            {{ __('Jobs échoués') }} ({{ $failedJobs->count() }})
        </h6>
        @if($failedJobs->isNotEmpty())
            <form action="{{ route('admin.failed-jobs.destroy-all') }}" method="POST" onsubmit="return confirm('{{ __('Supprimer tous les jobs échoués ?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-600 text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:trash-bin-trash-outline" class="icon text-xl line-height-1"></iconify-icon>
                    {{ __('Tout supprimer') }}
                </button>
            </form>
        @endif
    </div>
    <div class="card-body p-0">
        @if($failedJobs->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:check-circle-outline" class="text-6xl text-success-main mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucun job en échec. Tout fonctionne correctement.') }}</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('File') }}</th>
                            <th>Job</th>
                            <th>Exception</th>
                            <th>{{ __('Date') }}</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs as $job)
                            <tr>
                                <td>{{ $job->id }}</td>
                                <td><span class="badge bg-neutral-200 text-neutral-600">{{ $job->queue }}</span></td>
                                <td>
                                    @php
                                        $payload = json_decode($job->payload, true);
                                    @endphp
                                    <code class="text-primary-600 text-sm">{{ \Illuminate\Support\Str::limit($payload['displayName'] ?? 'N/A', 50) }}</code>
                                </td>
                                <td class="text-danger-main text-sm" style="max-width:300px;word-break:break-word">{{ \Illuminate\Support\Str::limit(explode("\n", $job->exception)[0] ?? '', 80) }}</td>
                                <td class="text-sm text-secondary-light">{{ $job->failed_at }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                            <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-12">
                                            <form action="{{ route('admin.failed-jobs.retry', $job->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <iconify-icon icon="solar:refresh-outline" class="icon"></iconify-icon> {{ __('Réessayer') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.failed-jobs.destroy', $job->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('{{ __('Supprimer ce job ?') }}')">
                                                    <iconify-icon icon="solar:trash-bin-trash-outline" class="icon"></iconify-icon> {{ __('Supprimer') }}
                                                </button>
                                            </form>
                                        </div>
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
@endsection
