@extends('layouts.app')

@section('title', 'About - Lottery Genie')

@section('content')
    <header class="text-center mb-12">
        <h1 class="text-4xl font-bold mb-4">About Lottery Genie</h1>
        <p class="text-slate-400 text-lg">Predicting your future, one draw at a time.</p>
    </header>

    <div class="card-glass rounded-3xl p-8 leading-relaxed text-slate-300">
        <p class="mb-6">
            Welcome to <span class="text-white font-semibold">Lottery Genie</span>, your ultimate companion for lottery predictions.
            Our sophisticated algorithms analyze historical draw data to provide you with the most probable numbers for the upcoming Eurojackpot draws.
        </p>

        <p class="mb-6">
            We believe in the power of data and statistics. While the lottery is a game of chance,
            understanding patterns and frequencies can give you an edge—or at least make the game more exciting!
        </p>

        <div class="grid md:grid-cols-2 gap-8 mt-12">
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                <h3 class="text-white font-bold mb-3">Our Mission</h3>
                <p class="text-sm">To provide a fun and data-driven way for lottery enthusiasts to explore number possibilities.</p>
            </div>
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                <h3 class="text-white font-bold mb-3">The Algorithm</h3>
                <p class="text-sm">We use historical frequency analysis and statistical weighting to generate each prediction.</p>
            </div>
        </div>
    </div>
@endsection
