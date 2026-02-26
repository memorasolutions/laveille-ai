@extends('backoffice::layouts.admin')

@section('page-title', 'Rôles')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Rôles</h2>
        <a href="{{ route('admin.roles.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Nouveau</a>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-3">Nom</th>
                    <th class="px-6 py-3">Permissions</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($roles as $role)
                    <tr class="text-gray-900 dark:text-white">
                        <td class="px-6 py-4 font-medium">{{ $role->name }}</td>
                        <td class="px-6 py-4">{{ $role->permissions_count }}</td>
                        <td class="flex gap-2 px-6 py-4">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Modifier</a>
                            @unless(in_array($role->name, ['super_admin', 'admin']))
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Supprimer ce rôle ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline dark:text-red-400">Supprimer</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-gray-200 px-6 py-3 dark:border-gray-700">
            {{ $roles->links() }}
        </div>
    </div>
@endsection
