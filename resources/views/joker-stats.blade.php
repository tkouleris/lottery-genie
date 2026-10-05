@extends('layouts.app')

@section('title', 'Joker Statistics')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/tzoker.jpg') }}" alt="Joker Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Joker Statistics</h1>
        <p class="text-slate-400 text-lg">Historical data analysis ({{ $stats['total_draws_analyzed'] }} draws)</p>
        @if(isset($stats['latest_draw_date']))
            <p class="text-slate-500 text-sm mt-2">Latest update: {{ $stats['latest_draw_date'] }}</p>
        @endif
    </header>

    <div class="space-y-8">
        <!-- Median Frequency -->
{{--        <section class="card-glass rounded-3xl p-8">--}}
{{--            <h2 class="text-2xl font-bold mb-6 text-blue-400">10 Most Frequent Medians (3rd number)</h2>--}}
{{--            <div class="grid grid-cols-5 md:grid-cols-10 gap-4">--}}
{{--                @foreach($stats['top_medians'] as $num => $count)--}}
{{--                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">--}}
{{--                        <div class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">--}}
{{--                            {{ $num }}--}}
{{--                        </div>--}}
{{--                        <span class="text-xs text-slate-400">{{ $count }} times</span>--}}
{{--                    </div>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        </section>--}}

        <!-- Simple Number Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-green-400">10 Most Frequent Simple Numbers</h2>
            <p class="text-slate-400 text-sm mb-6">The main numbers (1-45) that appear most often in the draw history.</p>
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

        <!-- Delays -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-red-400">Numbers in Delay</h2>
            <p class="text-slate-400 text-sm mb-6">Number of draws since each number was last drawn (Current Delay).</p>
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4 text-blue-300">Main Numbers (1-45)</h3>
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
                <h3 class="text-xl font-bold mb-4 text-yellow-300">Joker (1-20)</h3>
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
            <!-- Joker Frequency -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-2 text-yellow-400">10 Most Frequent Jokers</h2>
                <p class="text-slate-400 text-sm mb-6">The Joker numbers (1-20) that appear most often in the draw history.</p>
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
                <h2 class="text-2xl font-bold mb-2 text-yellow-400">Even / Odd Combinations</h2>
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
        </div>

        <!-- Main Numbers Sum Range Distribution -->
        @include('partials.sum_distribution', ['sumStats' => $stats['sum_distribution'], 'sumCheckerRoute' => 'joker.sum-checker'])
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('joker') }}" class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            Get Lucky Predictions
        </a>
    </div>
@endsection
