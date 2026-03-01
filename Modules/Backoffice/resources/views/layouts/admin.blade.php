<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          sidebarOpen: true,
          darkMode: localStorage.getItem('darkMode') === 'true'
      }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }"
      class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - {{ config('app.name', 'CORE') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">
    <div class="flex h-full">
        {{-- Sidebar --}}
        @include('backoffice::partials.sidebar')

        {{-- Main content --}}
        <div class="flex flex-1 flex-col overflow-hidden" :class="sidebarOpen ? 'ml-64' : 'ml-16'">
            {{-- Topbar --}}
            @include('backoffice::partials.topbar')

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/50 dark:text-green-400" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/50 dark:text-red-400" x-data="{ show: true }" x-show="show">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @include('backoffice::partials.toast-notifications')

    @livewireScripts
    @stack('scripts')
</body>
</html>
