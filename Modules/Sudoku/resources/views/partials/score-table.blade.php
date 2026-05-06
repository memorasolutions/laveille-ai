{{-- partials/score-table.blade.php : table reutilisable scores --}}
<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Joueur</th>
            @if(!empty($showDifficulty))<th>Niveau</th>@endif
            <th>Score</th>
            <th>Temps</th>
            <th>Indices</th>
            <th>Erreurs</th>
        </tr>
    </thead>
    <tbody>
    @forelse($rows as $i => $row)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $row->pseudo ?: 'Anonyme' }}</td>
            @if(!empty($showDifficulty))
                <td>{{ optional($row->puzzle)->getDifficultyLabel() ?? '-' }}</td>
            @endif
            <td><strong>{{ $row->score }}</strong></td>
            <td>{{ floor($row->time_seconds / 60) }}:{{ str_pad((string)($row->time_seconds % 60), 2, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $row->hints_used }}</td>
            <td>{{ $row->errors_count }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ !empty($showDifficulty) ? 7 : 6 }}" class="text-center text-muted py-3">Aucun score enregistre.</td></tr>
    @endforelse
    </tbody>
</table>
