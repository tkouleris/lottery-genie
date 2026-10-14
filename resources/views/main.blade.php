@extends('layouts.app')

@section('content')
    <div class="grid gap-8 md:grid-cols-3 items-stretch">
        <!-- Eurozahlen Section -->
        <div class="flex flex-col mb-8 md:mb-0">
            <header class="text-center mb-3">
                <img src="{{ asset('img/eurojackpot.jpg') }}" alt="{{ __(':game Logo', ['game' => 'Eurojackpot']) }}" class="mx-auto">
                <div class="mt-4 text-slate-400 font-medium">
                    <div>{{ __('Draw #:id • :date', ['id' => $euro['id'], 'date' => $euro['date']]) }}</div>
                    @if(\Carbon\Carbon::now()->format('d/m/Y') == $euro['next_draw_date'])
                        <div>{{ __('Next Draw:') }} <b>{{ __('Today') }}</b></div>
                    @else
                        <div>{{ __('Next Draw:') }} {{ $euro['next_draw_date'] }}</div>
                    @endif
                </div>
            </header>

            <div class="card-glass rounded-3xl p-6 md:p-8 transform transition hover:scale-105 duration-300 flex-1">
                <div class="flex flex-col gap-4 mb-8 items-center justify-center h-full">
                    <div class="flex flex-wrap gap-4 justify-center">
                        @foreach($euro['numbers'] as $number)
                            <div class="ball number-ball w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-full text-lg md:text-xl font-bold text-slate-900">
                                {{ $number }}
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-4 justify-center">
                        @foreach($euro['joker'] as $jokers)
                            <div class="ball joker-ball w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-full text-lg md:text-xl font-bold">
                                {{ $jokers }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Joker Section -->
        <div class="flex flex-col mb-8 md:mb-0">
            <header class="text-center mb-3">
                <img src="{{ asset('img/tzoker.jpg') }}" alt="{{ __(':game Logo', ['game' => 'Joker']) }}" class="mx-auto">
                <div class="mt-4 text-slate-400 font-medium">
                    <div>{{ __('Draw #:id • :date', ['id' => $joker['id'], 'date' => $joker['date']]) }}</div>
                    @if(\Carbon\Carbon::now()->format('d/m/Y') == $joker['next_draw_date'])
                        <div><b>{{ __('Today') }}</b></div>
                    @else
                        <div>{{ __('Next Draw:') }} {{ $joker['next_draw_date'] }}</div>
                    @endif
                </div>
            </header>

            <div class="card-glass rounded-3xl p-6 md:p-8 transform transition hover:scale-105 duration-300 flex-1">
                <div class="flex flex-col gap-4 mb-8 items-center justify-center h-full">
                    <div class="flex flex-wrap gap-4 justify-center">
                        @foreach($joker['numbers'] as $number)
                            <div class="ball number-ball w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-full text-lg md:text-xl font-bold text-slate-900">
                                {{ $number }}
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-4 justify-center">
                        @foreach($joker['joker'] as $jokers)
                            <div class="ball joker-ball w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-full text-lg md:text-xl font-bold">
                                {{ $jokers }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Lotto Section -->
        <div class="flex flex-col mb-8 md:mb-0">
            <header class="text-center mb-3">
                <img src="{{ asset('img/lotto.jpg') }}" alt="{{ __(':game Logo', ['game' => 'Lotto']) }}" class="mx-auto">
                <div class="mt-4 text-slate-400 font-medium">
                    <div>{{ __('Draw #:id • :date', ['id' => $lotto['id'], 'date' => $lotto['date']]) }}</div>
                    @if(\Carbon\Carbon::now()->format('d/m/Y') == $lotto['next_draw_date'])
                        <div><b>{{ __('Today') }}</b></div>
                    @else
                        <div>{{ __('Next Draw:') }} {{ $lotto['next_draw_date'] }}</div>
                    @endif
                </div>
            </header>

            <div class="card-glass rounded-3xl p-6 md:p-8 transform transition hover:scale-105 duration-300 flex-1">
                <div class="flex flex-wrap gap-4 mb-8 justify-center items-center h-full">
                    @foreach($lotto['numbers'] as $number)
                        <div class="ball number-ball w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-full text-lg md:text-xl font-bold text-slate-900">
                            {{ $number }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div style="margin-bottom: 10px;margin-top: 20px;">
        <hr>
    </div>
    <div class="mt-16 text-center max-w-2xl mx-auto">
        <p class="text-slate-400 text-base md:text-lg leading-relaxed px-4">
            {{ __('This application provides smart predictions for your favorite games Eurojackpot, Joker and Lotto, using statistics to help you choose your next lucky numbers.') }}
        </p>
    </div>
@endsection
