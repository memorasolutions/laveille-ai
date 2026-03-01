<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Étapes onboarding', 'subtitle' => 'Gestion'])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th>Slug</th>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($steps as $step)
                <tr>
                    <td>{{ $step->order }}</td>
                    <td><code>{{ $step->slug }}</code></td>
                    <td>{{ $step->title }}</td>
                    <td>{{ Str::limit($step->description, 50) }}</td>
                    <td>
                        <span class="badge {{ $step->is_active ? 'bg-success' : 'bg-danger' }}">{{ $step->is_active ? 'Oui' : 'Non' }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.onboarding-steps.edit', $step) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
