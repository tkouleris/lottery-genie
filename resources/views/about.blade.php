@extends('layouts.app')

@section('title', __('About - Lottery Genie'))

@section('content')
    <header class="text-center mb-12">
        <h1 class="text-4xl font-bold mb-4">{{ __('About Lottery Genie') }}</h1>
        <p class="text-slate-400 text-lg">{{ __('Predicting your future, one draw at a time.') }}</p>
    </header>

    <div class="card-glass rounded-3xl p-8 leading-relaxed text-slate-300">
        <p class="mb-6">
            {!! __('Welcome to :name, your ultimate companion for lottery analysis and predictions.', ['name' => '<span class="text-white font-semibold">Lottery Genie</span>']) !!}
            {!! __('Our platform supports major games including :euro, :joker, and :lotto.', ['euro' => '<span class="text-blue-400">Eurojackpot</span>', 'joker' => '<span class="text-yellow-500">Joker</span>', 'lotto' => '<span class="text-blue-500">Lotto</span>']) !!}
        </p>

        <p class="mb-6">
            {{ __('We offer a comprehensive suite of tools designed for lottery enthusiasts:') }}
        </p>

        <ul class="list-disc list-inside mb-6 space-y-2 text-sm md:text-base">
            <li><span class="text-white font-semibold">{{ __('Predictions:') }}</span> {{ __('Data-driven number generation based on historical frequency and statistical analysis.') }}</li>
            <li><span class="text-white font-semibold">{{ __('Statistics:') }}</span> {{ __('Detailed breakdown of number occurrences, cold/hot numbers, and draw patterns.') }}</li>
            <li><span class="text-white font-semibold">{{ __('Combination Checker:') }}</span> {{ __("Verify your favorite numbers against years of historical draw data to see if they've ever won.") }}</li>
        </ul>

        <p class="mb-6">
            {{ __('While the lottery is a game of chance, we believe that understanding patterns and historical data makes the experience more engaging and informed.') }}
        </p>

        <div class="grid md:grid-cols-2 gap-8 mt-12">
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                <h3 class="text-white font-bold mb-3">{{ __('Our Mission') }}</h3>
                <p class="text-sm">{{ __('To provide lottery enthusiasts with advanced statistical tools and data-driven insights in a user-friendly environment.') }}</p>
            </div>
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                <h3 class="text-white font-bold mb-3">{{ __('Data Accuracy') }}</h3>
                <p class="text-sm">{{ __('We process thousands of historical draws to ensure our statistics and checkers provide reliable historical context.') }}</p>
            </div>
        </div>
    </div>
@endsection
