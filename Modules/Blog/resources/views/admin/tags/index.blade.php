<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Tags', 'subtitle' => 'Blog'])

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Tags ({{ $tags->total() }})</h5>
        <a href="{{ route('admin.blog.tags.create') }}" class="btn btn-sm btn-primary">Nouveau tag</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Couleur</th><th>Nom</th><th>Articles</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($tags as $tag)
                <tr>
                    <td><div class="rounded-circle" style="width:20px;height:20px;background:{{ $tag->color }}"></div></td>
                    <td>{{ $tag->name }}</td>
                    <td><span class="badge bg-primary">{{ $tag->articles_count }}</span></td>
                    <td>
                        <a href="{{ route('admin.blog.tags.edit', $tag) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                        <form action="{{ route('admin.blog.tags.destroy', $tag) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')">Supprimer</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">Aucun tag.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $tags->links() }}
    </div>
</div>
@endsection
