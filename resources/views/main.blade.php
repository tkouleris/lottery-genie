@extends('layouts.app')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/eurojackpot.jpg') }}" alt="Eurojackpot Logo" class="mx-auto">
        <p class="text-slate-400 text-lg">Your lucky predictions for the next draw</p>
    </header>

    <div class="grid gap-8 md:grid-cols-1">
        <div class="card-glass rounded-3xl p-8 transform transition hover:scale-105 duration-300">

            <div class="flex flex-wrap gap-4 mb-8 justify-center">
                @foreach($draws[0]['numbers'] as $number)
                    <div class="ball number-ball w-12 h-12 flex items-center justify-center rounded-full text-xl font-bold text-slate-900">
                        {{ $number }}
                    </div>
                @endforeach
                @foreach($draws[0]['jokers'] as $joker)
                    <div class="ball joker-ball w-12 h-12 flex items-center justify-center rounded-full text-xl font-bold">
                        {{ $joker }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
