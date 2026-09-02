@extends('layouts.app')

@section('title', 'Lotto Statistics')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/lotto.jpg') }}" alt="Lotto Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Lotto Statistics</h1>
        <p class="text-slate-400 text-lg">Historical data analysis ({{ $stats['total_draws_analyzed'] }} draws)</p>
    </header>

    <div class="space-y-8">
        <!-- Number Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-blue-400">10 Most Frequent Numbers</h2>
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
            <!-- Differences Classes -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-6 text-green-400">Most Frequent Differences (Max-Min)</h2>
                <div class="space-y-4">
                    @foreach($stats['top_differences'] as $class => $count)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                            <span class="text-lg font-bold text-slate-300">{{ $class }}</span>
                            <span class="font-bold text-slate-300">{{ $count }} times</span>
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

        <!-- Top Triples -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-pink-400">Top 10 Most Frequent Triples</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($stats['top_triples'] as $triple => $count)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                        <div class="flex gap-2">
                            @foreach(explode(',', $triple) as $num)
                                <div class="ball number-ball w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold text-slate-900">
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

    <div class="mt-8 text-center">
        <a href="{{ route('lotto') }}" class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            Get Lucky Predictions
        </a>
    </div>
@endsection
