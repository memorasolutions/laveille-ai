@extends('backoffice::layouts.admin')

@section('page-title', $user->name)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Modifier</a>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rôles</dt>
                    <dd class="mt-1 flex flex-wrap gap-2">
                        @forelse($user->roles as $role)
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-400">{{ $role->name }}</span>
                        @empty
                            <span class="text-sm text-gray-500">Aucun rôle</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Inscrit le</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
