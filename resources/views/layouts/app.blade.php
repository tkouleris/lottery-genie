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
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-BS7EGERJ29"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-BS7EGERJ29');
</script>
<body class="text-white flex flex-col items-center p-6">
    <nav class="max-w-4xl w-full mb-8 px-4 md:px-0">
        <div class="card-glass rounded-2xl px-4 md:px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-yellow-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Lottery Genie
            </div>
            <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                <a href="/" class="text-sm md:text-base text-slate-300 hover:text-white transition-colors">Home</a>
                <a href="{{ route('eurojackpot') }}" class="text-sm md:text-base text-slate-300 hover:text-white transition-colors">Eurojackpot</a>
                <a href="{{ route('joker') }}" class="text-sm md:text-base text-slate-300 hover:text-white transition-colors">Joker</a>
                <a href="{{ route('lotto') }}" class="text-slate-300 hover:text-white transition-colors">Lotto</a>
                <a href="{{ route('about') }}" class="text-sm md:text-base text-slate-300 hover:text-white transition-colors">About</a>
            </div>
        </div>
    </nav>

    <div id="loading-overlay" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/80 backdrop-blur-sm hidden">
        <div class="w-16 h-16 border-4 border-blue-400 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-xl font-semibold text-white">Calculating luck...</p>
    </div>

    <div class="max-w-4xl w-full">
        @yield('content')

        <footer class="mt-16 text-center text-slate-500 text-sm">
            <p>Calculated based on historical data. Good luck!</p>
            <p class="mt-2">&copy; {{ date('Y') }} Lottery Genie</p>
        </footer>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            const overlay = document.getElementById('loading-overlay');

            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    overlay.classList.remove('hidden');
                });
            });
        });
    </script>
</body>
</html>
