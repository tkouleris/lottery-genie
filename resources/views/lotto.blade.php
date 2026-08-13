@extends('layouts.app')

@section('title', 'Lotto Numbers')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/lotto.jpg') }}" alt="Lotto Logo" class="mx-auto">
        <p class="text-slate-400 text-lg">Your lucky predictions for the next draw</p>
    </header>

    <form action="{{ route('lotto') }}" method="GET" class="card-glass rounded-3xl p-8 mb-8">
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
                </div>
            </div>
        </div>
    @endforeach
@endsection
