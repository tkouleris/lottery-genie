@extends('layouts.app')

@section('title', __(':game Statistics', ['game' => 'Joker']))
@section('description', __(':game statistics: most frequent numbers, delays, even/odd, sum and range distributions and common triplets from historical draws.', ['game' => 'Joker']))
@section('keywords', __(':game statistics, :game frequent numbers, :game delays, :game results, lottery statistics', ['game' => 'Joker']))

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/tzoker.jpg') }}" alt="{{ __(':game Logo', ['game' => 'Joker']) }}" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">{{ __(':game Statistics', ['game' => 'Joker']) }}</h1>
        <p class="text-slate-400 text-lg">{{ __('Historical data analysis (:count draws)', ['count' => $stats['total_draws_analyzed']]) }}</p>
        @if (isset($stats['latest_draw_date']))
            <p class="text-slate-500 text-sm mt-2">{{ __('Latest update: :date', ['date' => $stats['latest_draw_date']]) }}</p>
        @endif
    </header>

    <div class="space-y-8">
        <!-- Simple Number Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-green-400">{{ __('Number Frequency') }}</h2>
            <p class="text-slate-400 text-sm mb-6">{{ __('The main numbers (1-45) that appear most often in the draw history.') }}</p>
            <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                @foreach ($stats['top_numbers'] as $num => $count)
                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                        <div
                            class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">
                            {{ $num }}
                        </div>
                        <span class="text-xs text-slate-400">{{ __(':count times', ['count' => $count]) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Joker Frequency -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-yellow-400">{{ __('Joker Frequency') }}</h2>
            <p class="text-slate-400 text-sm mb-6">{{ __('The Joker numbers (1-20) that appear most often in the draw history.') }}</p>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach ($stats['top_jokers'] as $num => $count)
                    <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                        <div
                            class="ball joker-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold mb-1">
                            {{ $num }}
                        </div>
                        <span class="text-xs text-slate-400">{{ __(':count times', ['count' => $count]) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Delays -->
        <section class="card-glass rounded-3xl p-8">
            <h2 class="text-2xl font-bold mb-2 text-red-400">{{ __('Numbers in Delay') }}</h2>
            <p class="text-slate-400 text-sm mb-6">{{ __('Number of draws since each number was last drawn (Current Delay).') }}</p>
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4 text-blue-300">{{ __('Main Numbers (1-:max)', ['max' => 45]) }}</h3>
                <div class="grid grid-cols-5 md:grid-cols-10 gap-4">
                    @php asort($stats['number_delay']); @endphp
                    @foreach ($stats['number_delay'] as $num => $delay)
                        <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                            <div
                                class="ball number-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold text-slate-900 mb-1">
                                {{ $num }}
                            </div>
                            <span
                                class="text-xs {{ $delay <= 4 ? 'text-green-400' : ($delay >= 5 && $delay <= 10 ? 'text-yellow-400' : ($delay > 10 ? 'text-red-400 font-bold' : 'text-slate-400')) }}">{{ __(':count dr.', ['count' => $delay]) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4 text-yellow-300">Joker (1-20)</h3>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-4">
                    @php asort($stats['joker_delay']); @endphp
                    @foreach ($stats['joker_delay'] as $num => $delay)
                        <div class="flex flex-col items-center p-2 rounded-xl bg-slate-800/50">
                            <div
                                class="ball joker-ball w-10 h-10 flex items-center justify-center rounded-full text-lg font-bold mb-1">
                                {{ $num }}
                            </div>
                            <span
                                class="text-xs {{ $delay < 4 ? 'text-green-400' : ($delay >= 5 && $delay <= 9 ? 'text-yellow-400' : ($delay > 10 ? 'text-red-400 font-bold' : 'text-slate-400')) }}">{{ __(':count dr.', ['count' => $delay]) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <!-- Main Numbers Sum Range Distribution -->
        @include('partials.sum_distribution', [
            'sumStats' => $stats['sum_distribution'],
            'sumCheckerRoute' => 'joker.sum-checker',
        ])
        <!-- Max - Min Range Distribution -->
        @include('partials.range_distribution', [
            'rangeExample' => __('Example: 1, 2, 3, 4, 5 gives a range of 4. The single joker number has no range.'),
            'rangeSeries' => [
                ['title' => __('Main Numbers (possible :min – :max)', ['min' => 4, 'max' => 44]), 'color' => 'text-blue-300', 'bar' => 'from-blue-700 to-blue-400', 'data' => $stats['range_distribution']],
            ],
        ])
        <div class="grid md:grid-cols-1 gap-8">


            <!-- Even / Odd Frequency -->
            <section class="card-glass rounded-3xl p-8">
                <h2 class="text-2xl font-bold mb-2 text-yellow-400">{{ __('Even / Odd Combinations') }}</h2>
                <p class="text-slate-400 text-sm mb-6">{{ __('Frequency of even and odd number counts in the same draw.') }}</p>
                <div class="space-y-4">
                    @foreach ($stats['even_odd_stats'] as $combo => $count)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50">
                            <span class="text-sm font-bold text-slate-300">{{ str_replace(['even', 'odd'], [__('even'), __('odd')], $combo) }}</span>
                            <span class="font-bold text-slate-300">{{ __(':count times', ['count' => $count]) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>


    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('joker') }}"
            class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            {{ __('Get Lucky Predictions') }}
        </a>
    </div>
@endsection
