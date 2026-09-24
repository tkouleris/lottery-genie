@extends('layouts.app')

@section('title', 'Joker Numbers')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/tzoker.jpg') }}" alt="Joker Logo" class="mx-auto" style="max-height: 150px;">
        <p class="text-slate-400 text-lg">Your lucky predictions for the next draw</p>
        <div class="mt-4">
            <a href="{{ route('joker.stats') }}" class="text-blue-400 hover:text-blue-300 font-semibold underline decoration-2 underline-offset-4 transition-colors">
                View Historical Statistics
            </a>
        </div>
    </header>

    <form action="{{ route('joker') }}" method="GET" class="card-glass rounded-3xl p-8 mb-8">
        @include('partials.select_predictions')
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

                <div class="flex justify-center">
                    <a href="{{ route('joker.checker', ['numbers' => $draw['numbers'], 'jokers' => $draw['jokers']]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-600 text-blue-400 hover:text-blue-300 px-6 py-2 rounded-full text-sm font-bold transition-all border border-slate-600 hover:border-blue-400/50 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Check Combination
                    </a>
                </div>
            </div>
        </div>
    @endforeach
@endsection
