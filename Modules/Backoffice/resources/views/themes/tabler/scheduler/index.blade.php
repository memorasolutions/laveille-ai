@extends('backoffice::layouts.admin', ['title' => 'Scheduler', 'subtitle' => 'Outils'])
@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Tâches planifiées</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Commande</th><th>Expression</th><th>Prochaine exécution</th><th>Description</th></tr></thead>
            <tbody>
                @forelse($tasks ?? [] as $task)
                <tr>
                    <td><code>{{ $task['command'] ?? '' }}</code></td>
                    <td><code>{{ $task['expression'] ?? '' }}</code></td>
                    <td class="text-muted">{{ $task['next_run'] ?? '-' }}</td>
                    <td>{{ $task['description'] ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">Aucune tâche planifiée</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
