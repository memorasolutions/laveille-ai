<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-12 px-4">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-4 text-gray-600">Bienvenue, {{ auth()->user()->name }} !</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-red-600 hover:underline">Se déconnecter</button>
        </form>
    </div>
</body>
</html>
