<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Lottery Genie')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    </style>
</head>
<body class="text-white flex flex-col items-center p-6">
    <nav class="max-w-4xl w-full mb-8">
        <div class="card-glass rounded-2xl px-6 py-4 flex justify-between items-center">
            <div class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-yellow-400">
                Lottery Genie
            </div>
            <div class="flex gap-6">
                <a href="/" class="text-slate-300 hover:text-white transition-colors">Home</a>
                <a href="{{ route('eurojackpot') }}" class="text-slate-300 hover:text-white transition-colors">Eurojackpot</a>
                <a href="{{ route('about') }}" class="text-slate-300 hover:text-white transition-colors">About</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl w-full">
        @yield('content')

        <footer class="mt-16 text-center text-slate-500 text-sm">
            <p>Calculated based on historical data. Good luck!</p>
            <p class="mt-2">&copy; {{ date('Y') }} Lottery Genie</p>
        </footer>
    </div>
</body>
</html>
