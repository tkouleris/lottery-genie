@extends('layouts.app')

@section('title', 'Eurojackpot Statistics')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/eurojackpot.jpg') }}" alt="Eurojackpot Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Eurojackpot Statistics</h1>
        <p class="text-slate-400 text-lg">Historical data analysis ({{ $stats['total_draws_analyzed'] }} draws)</p>
    </header>

    <div class="space-y-8">
        <!-- Number Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-blue-400">Number Frequency (1-50)</h2>
            <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                @foreach($stats['number_frequency'] as $num => $count)
                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                        <div class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">
                            {{ $num }}
                        </div>
                        <span class="text-xs text-slate-400">{{ $count }} times</span>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Joker Frequency -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-6 text-yellow-400">Joker Frequency (1-12)</h2>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-4">
                    @foreach($stats['joker_frequency'] as $num => $count)
                        <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                            <div class="ball joker-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold mb-1">
                                {{ $num }}
                            </div>
                            <span class="text-xs text-slate-400">{{ $count }} times</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Common Joker Combinations -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-6 text-pink-400">Top Joker Pairs</h2>
                <div class="space-y-4">
                    @foreach($stats['common_joker_combinations'] as $pair => $count)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                            <div class="flex gap-2">
                                @foreach(explode('-', $pair) as $num)
                                    <div class="ball joker-ball w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold">
                                        {{ $num }}
                                    </div>
                                @endforeach
                            </div>
                            <span class="font-bold text-slate-300">{{ $count }} times</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('eurojackpot') }}" class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            Get Lucky Predictions
        </a>
    </div>
@endsection
