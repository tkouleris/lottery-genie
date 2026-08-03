<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eurojackpot Numbers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top left, #1e293b, #0f172a);
            min-height: 100vh;
        }
        .ball {
            box-shadow: inset -5px -5px 15px rgba(0,0,0,0.3), 5px 5px 15px rgba(0,0,0,0.2);
        }
        .joker-ball {
            background: linear-gradient(135deg, #fbbf24, #d97706);
        }
        .number-ball {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-white flex flex-col items-center justify-center p-6">
    <div class="max-w-4xl w-full">
        <header class="text-center mb-12">
            <h1 class="text-5xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-yellow-400">
                Eurojackpot Numbers
            </h1>
            <p class="text-slate-400 text-lg">Your lucky predictions for the next draw</p>
        </header>

        <div class="grid gap-8 md:grid-cols-2">
            @foreach($draws as $index => $draw)
                <div class="card-glass rounded-3xl p-8 transform transition hover:scale-105 duration-300">
                    <h2 class="text-2xl font-semibold mb-6 text-slate-300 border-b border-white/10 pb-2">
                        Prediction #{{ $index + 1 }}
                    </h2>

                    <div class="flex flex-wrap gap-4 mb-8 justify-center">
                        @foreach($draw['numbers'] as $number)
                            <div class="ball number-ball w-12 h-12 flex items-center justify-center rounded-full text-xl font-bold">
                                {{ $number }}
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap gap-4 justify-center">
                        <span class="w-full text-center text-sm text-yellow-400/70 uppercase tracking-widest mb-2 font-semibold">Euro Numbers</span>
                        @foreach($draw['jokers'] as $joker)
                            <div class="ball joker-ball w-12 h-12 flex items-center justify-center rounded-full text-xl font-bold text-slate-900">
                                {{ $joker }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <footer class="mt-16 text-center text-slate-500 text-sm">
            <p>Calculated based on historical data. Good luck!</p>
            <p class="mt-2">&copy; {{ date('Y') }} Lottery Genie</p>
        </footer>
    </div>
</body>
</html>
