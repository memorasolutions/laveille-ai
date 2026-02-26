@extends('backoffice::layouts.admin')

@section('page-title', $role->name)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $role->name }}</h2>
            <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Modifier</a>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <h3 class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-400">Permissions ({{ $role->permissions->count() }})</h3>
            <div class="flex flex-wrap gap-2">
                @forelse($role->permissions as $perm)
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $perm->name }}</span>
                @empty
                    <span class="text-sm text-gray-500">Aucune permission</span>
                @endforelse
            </div>
        </div>
    </div>
@endsection
