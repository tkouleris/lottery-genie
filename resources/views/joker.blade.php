@extends('layouts.app')

@section('title', 'Eurojackpot Numbers')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/tzoker.jpg') }}" alt="Eurojackpot Logo" class="mx-auto">
        <p class="text-slate-400 text-lg">Your lucky predictions for the next draw</p>
    </header>

    <form action="{{ route('joker') }}" method="GET" class="card-glass rounded-3xl p-8 mb-8">
        <div class="flex flex-col items-center gap-6">
            Select Number of Draws:
            <div class="flex flex-wrap justify-center gap-4">
                @foreach([1, 2, 3, 4, 5, 6] as $i)
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" name="number_of_draws" value="{{ $i }}" {{ request('number_of_draws') == $i ? 'checked' : '' }} class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 focus:ring-blue-500">
                        <span class="text-slate-300 group-hover:text-white transition-colors">{{ $i }}</span>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded-full transition-all transform hover:scale-105">
                Predict
            </button>
        </div>
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
            </div>
        </div>
    @endforeach
@endsection
