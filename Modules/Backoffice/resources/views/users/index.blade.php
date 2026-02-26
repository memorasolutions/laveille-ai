@extends('backoffice::layouts.admin')

@section('page-title', 'Utilisateurs')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Utilisateurs</h2>
        <div class="flex gap-2">
            <a href="{{ route('admin.export.users') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Export CSV
            </a>
            <a href="{{ route('admin.import.users') }}" class="rounded-lg border border-teal-300 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-300 dark:hover:bg-teal-900">
                Importer CSV
            </a>
            <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Nouveau
            </a>
        </div>
    </div>

    @livewire('backoffice::users-table')
@endsection
