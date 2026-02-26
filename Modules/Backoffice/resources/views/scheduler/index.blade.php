@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:calendar-outline" class="icon text-xl"></iconify-icon>
            {{ __('Tâches planifiées') }} ({{ count($tasks) }})
        </h6>
    </div>
    <div class="card-body p-0">
        @if(empty($tasks))
            <div class="text-center py-40">
                <iconify-icon icon="solar:calendar-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucune tâche planifiée configurée.') }}</p>
                @if($rawOutput)
                    <details class="mt-3">
                        <summary class="text-secondary-light text-sm cursor-pointer">{{ __('Sortie brute') }}</summary>
                        <pre class="text-start p-16 bg-neutral-50 text-sm mt-2 rounded">{{ $rawOutput }}</pre>
                    </details>
                @endif
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Expression cron') }}</th>
                            <th>{{ __('Commande') }}</th>
                            <th>{{ __('Prochaine exécution') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                            <tr>
                                <td><code class="text-primary-600 text-sm">{{ $task['expression'] }}</code></td>
                                <td>{{ $task['command'] }}</td>
                                <td>
                                    <span class="badge bg-neutral-200 text-neutral-600">
                                        {{ $task['next_due'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-secondary-light text-sm px-24 py-16">
                {{ count($tasks) }} {{ __('tâche(s) planifiée(s)') }}
            </div>
        @endif
    </div>
</div>
@endsection
