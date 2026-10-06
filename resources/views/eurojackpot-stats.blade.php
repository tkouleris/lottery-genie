@extends('layouts.app')

@section('title', 'Eurojackpot Statistics')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/eurojackpot.jpg') }}" alt="Eurojackpot Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Eurojackpot Statistics</h1>
        <p class="text-slate-400 text-lg">Historical data analysis ({{ $stats['total_draws_analyzed'] }} draws)</p>
        @if(isset($stats['latest_draw_date']))
            <p class="text-slate-500 text-sm mt-2">Latest update: {{ $stats['latest_draw_date'] }}</p>
        @endif
    </header>

    <div class="space-y-8">
        <!-- Number Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-blue-400">Number Frequency (1-50)</h2>
            <p class="text-slate-400 text-sm mb-6">Total occurrences of each main number in the draw history.</p>
            <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                @foreach($stats['number_frequency'] as $num => $count)
                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                        <div
                            class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">
                            {{ $num }}
                        </div>
                        <span class="text-xs text-slate-400">{{ $count }} times</span>
                    </div>
                @endforeach
            </div>
        </section>
        <!-- Eurozahlen Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-yellow-400">Eurozahlen Frequency (1-12)</h2>
            <p class="text-slate-400 text-sm mb-6">Total occurrences of each Euro number in the draw history.</p>
            <div class="grid grid-cols-4 gap-4">
                @foreach($stats['joker_frequency'] as $num => $count)
                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                        <div
                            class="ball joker-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold mb-1">
                            {{ $num }}
                        </div>
                        <span class="text-xs text-slate-400">{{ $count }} times</span>
                    </div>
                @endforeach
            </div>
        </section>
        <!-- Delays -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-red-400">Numbers in Delay</h2>
            <p class="text-slate-400 text-sm mb-6">Number of draws since each number was last drawn (Current Delay).</p>
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4 text-blue-300">Main Numbers (1-50)</h3>
                <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                    @php asort($stats['number_delay']); @endphp
                    @foreach($stats['number_delay'] as $num => $delay)
                        <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                            <div class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">
                                {{ $num }}
                            </div>
                            <span class="text-xs {{ $delay <= 4 ? 'text-green-400' : ($delay >= 5 && $delay <= 10 ? 'text-yellow-400' : ($delay > 10 ? 'text-red-400 font-bold' : 'text-slate-400')) }}">{{ $delay }} dr.</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4 text-yellow-300">Eurozahlen (1-12)</h3>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-4">
                    @php asort($stats['joker_delay']); @endphp
                    @foreach($stats['joker_delay'] as $num => $delay)
                        <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                            <div class="ball joker-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold mb-1">
                                {{ $num }}
                            </div>
                            <span class="text-xs {{ $delay < 4 ? 'text-green-400' : ($delay >= 5 && $delay <= 9 ? 'text-yellow-400' : ($delay > 10 ? 'text-red-400 font-bold' : 'text-slate-400')) }}">{{ $delay }} dr.</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <div class="grid md:grid-cols-2 gap-8">


            <!-- Even / Odd Frequency -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-2 text-green-400">Even / Odd Combinations</h2>
                <p class="text-slate-400 text-sm mb-6">Frequency of even and odd number counts in the same draw.</p>
                <div class="space-y-4">
                    @foreach($stats['even_odd_stats'] as $combo => $count)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                            <span class="text-sm font-bold text-slate-300">{{ $combo }}</span>
                            <span class="font-bold text-slate-300">{{ $count }} times</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Common Eurozahlen Combinations -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-2 text-pink-400">Top Eurozahlen Pairs</h2>
                <p class="text-slate-400 text-sm mb-6">The most common pairs of Euro numbers appearing together.</p>
                <div class="space-y-4">
                    @foreach($stats['common_joker_combinations'] as $pair => $count)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                            <div class="flex gap-2">
                                @foreach(explode('-', $pair) as $num)
                                    <div
                                        class="ball joker-ball w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold">
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

        <!-- Main Numbers Sum Range Distribution -->
        @include('partials.sum_distribution', ['sumStats' => $stats['sum_distribution'], 'sumCheckerRoute' => 'eurojackpot.sum-checker'])

        <!-- Max - Min Range Distribution -->
        @include('partials.range_distribution', [
            'rangeExample' => 'Example: 1, 2, 3, 4, 5 and 1, 12 gives a main range of 4 and a Eurozahlen range of 11.',
            'rangeSeries' => [
                ['title' => 'Main Numbers (possible 4 – 49)', 'color' => 'text-blue-300', 'bar' => 'from-blue-700 to-blue-400', 'data' => $stats['range_distribution']['numbers']],
                ['title' => 'Eurozahlen (possible 1 – 11)', 'color' => 'text-yellow-300', 'bar' => 'from-yellow-600 to-yellow-400', 'data' => $stats['range_distribution']['jokers']],
            ],
        ])
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('eurojackpot') }}"
           class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            Get Lucky Predictions
        </a>
    </div>
@endsection
