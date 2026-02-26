@extends('backoffice::layouts.admin')

@section('page-title', 'Notifications')

@section('content')
    <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Notifications</h2>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($notifications as $notification)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $notification->data['title'] ?? $notification->type }}</p>
                        <p class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.notifications.destroy', $notification->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline dark:text-red-400">Supprimer</button>
                    </form>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-500">Aucune notification.</div>
            @endforelse
        </div>
        <div class="border-t border-gray-200 px-6 py-3 dark:border-gray-700">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
