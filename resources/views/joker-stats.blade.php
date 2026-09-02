@extends('layouts.app')

@section('title', 'Joker Statistics')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/tzoker.jpg') }}" alt="Joker Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Joker Statistics</h1>
        <p class="text-slate-400 text-lg">Historical data analysis ({{ $stats['total_draws_analyzed'] }} draws)</p>
    </header>

    <div class="space-y-8">
        <!-- Median Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-blue-400">10 Most Frequent Medians (3rd number)</h2>
            <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                @foreach($stats['top_medians'] as $num => $count)
                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                        <div class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">
                            {{ $num }}
                        </div>
                        <span class="text-xs text-slate-400">{{ $count }} times</span>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Simple Number Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-green-400">10 Most Frequent Simple Numbers</h2>
            <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                @foreach($stats['top_numbers'] as $num => $count)
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
                <h2 class="text-2xl font-bold mb-6 text-yellow-400">10 Most Frequent Jokers</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach($stats['top_jokers'] as $num => $count)
                        <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                            <div class="ball joker-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold mb-1">
                                {{ $num }}
                            </div>
                            <span class="text-xs text-slate-400">{{ $count }} times</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Even / Odd Frequency -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-6 text-yellow-400">Even / Odd Combinations</h2>
                <div class="space-y-4">
                    @foreach($stats['even_odd_stats'] as $combo => $count)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                            <span class="text-sm font-bold text-slate-300">{{ $combo }}</span>
                            <span class="font-bold text-slate-300">{{ $count }} times</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('joker') }}" class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            Get Lucky Predictions
        </a>
    </div>
@endsection
