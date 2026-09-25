@extends('layouts.app')

@section('title', 'About - Lottery Genie')

@section('content')
    <header class="text-center mb-12">
        <h1 class="text-4xl font-bold mb-4">About Lottery Genie</h1>
        <p class="text-slate-400 text-lg">Predicting your future, one draw at a time.</p>
    </header>

    <div class="card-glass rounded-3xl p-8 leading-relaxed text-slate-300">
        <p class="mb-6">
            Welcome to <span class="text-white font-semibold">Lottery Genie</span>, your ultimate companion for lottery analysis and predictions.
            Our platform supports major games including <span class="text-blue-400">Eurojackpot</span>, <span class="text-yellow-500">Joker</span>, and <span class="text-blue-500">Lotto</span>.
        </p>

        <p class="mb-6">
            We offer a comprehensive suite of tools designed for lottery enthusiasts:
        </p>

        <ul class="list-disc list-inside mb-6 space-y-2 text-sm md:text-base">
            <li><span class="text-white font-semibold">Predictions:</span> Data-driven number generation based on historical frequency and statistical analysis.</li>
            <li><span class="text-white font-semibold">Statistics:</span> Detailed breakdown of number occurrences, cold/hot numbers, and draw patterns.</li>
            <li><span class="text-white font-semibold">Combination Checker:</span> Verify your favorite numbers against years of historical draw data to see if they've ever won.</li>
        </ul>

        <p class="mb-6">
            While the lottery is a game of chance, we believe that understanding patterns and historical data makes the experience more engaging and informed.
        </p>

        <div class="grid md:grid-cols-2 gap-8 mt-12">
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                <h3 class="text-white font-bold mb-3">Our Mission</h3>
                <p class="text-sm">To provide lottery enthusiasts with advanced statistical tools and data-driven insights in a user-friendly environment.</p>
            </div>
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                <h3 class="text-white font-bold mb-3">Data Accuracy</h3>
                <p class="text-sm">We process thousands of historical draws to ensure our statistics and checkers provide reliable historical context.</p>
            </div>
        </div>
    </div>
@endsection
