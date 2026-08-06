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
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        .number-ball {
            background: linear-gradient(135deg, #fbbf24, #d97706);
        }
        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-white flex flex-col items-center p-6">
<div class="max-w-4xl w-full">
    <header class="text-center mb-12">
        <img src="{{ asset('img/eurojackpot.jpg') }}" alt="Eurojackpot Logo" class="mx-auto">
        <p class="text-slate-400 text-lg">Your lucky predictions for the next draw</p>
    </header>

    <form action="{{ route('eurojackpot') }}" method="GET" class="card-glass rounded-3xl p-8 mb-8">
        <div class="flex flex-col items-center gap-6">
            Select Number of Draws:
            <div class="flex flex-wrap justify-center gap-4">
                @foreach([1, 2, 3, 4, 5, 6] as $i)
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" name="number_of_draws" value="{{ $i }}" {{ request('number_of_draws') == $i ? 'checked' : '' }} class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 focus:ring-blue-500">
                        <span class="text-slate-300 group-hover:text-white transition-colors">{{ $i }}</span>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded-full transition-all transform hover:scale-105">
                Predict
            </button>
        </div>
    </form>

    @foreach($draws as $draw)
        <div class="grid gap-8 md:grid-cols-1" style="margin-bottom: 20px; margin-top: 10px;">
            <div class="card-glass rounded-3xl p-8 transform transition hover:scale-105 duration-300">

                <div class="flex flex-wrap gap-4 mb-8 justify-center">
                    @foreach($draw['numbers'] as $number)
                        <div class="ball number-ball w-12 h-12 flex items-center justify-center rounded-full text-xl font-bold text-slate-900">
                            {{ $number }}
                        </div>
                    @endforeach
                    @foreach($draw['jokers'] as $joker)
                        <div class="ball joker-ball w-12 h-12 flex items-center justify-center rounded-full text-xl font-bold">
                            {{ $joker }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach


    <footer class="mt-16 text-center text-slate-500 text-sm">
        <p>Calculated based on historical data. Good luck!</p>
        <p class="mt-2">&copy; {{ date('Y') }} Lottery Genie</p>
    </footer>
</div>
</body>
</html>
