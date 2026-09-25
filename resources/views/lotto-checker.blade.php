@extends('layouts.app')

@section('title', 'Lotto Combination Checker')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/lotto.jpg') }}" alt="Lotto Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Lotto Combination Checker</h1>
        <p class="text-slate-400 text-lg">Check if your numbers have ever won in the past</p>
    </header>

    <div x-data="checkerApp()" class="space-y-8">
        <!-- Input Section -->
        <section class="card-glass rounded-3xl p-8">
            <form id="checker-form" action="{{ route('lotto.checker') }}" method="GET" @submit.prevent="validateForm($event)">
                @if($results)
                    <!-- Display Selected Numbers -->
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-semibold mb-6 text-slate-300">Your Combination</h3>
                        <div class="flex flex-wrap gap-4 justify-center">
                            @foreach($userNumbers as $n)
                                <div class="w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center text-xl font-bold shadow-lg shadow-blue-500/20">
                                    {{ $n }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="grid gap-8">
                        <!-- Main Numbers Selection -->
                        <div>
                            <h3 class="text-xl font-semibold mb-4 text-blue-400 text-center">Main Numbers (Select 6)</h3>
                            <div class="grid grid-cols-7 gap-2 max-w-2xl mx-auto">
                                <template x-for="n in 49">
                                    <button type="button"
                                        @click="toggleNumber(n)"
                                        :class="isSelected(n) ? 'bg-blue-500 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                                        class="w-8 h-8 md:w-10 md:h-10 rounded-full text-xs md:text-sm font-bold transition-colors flex items-center justify-center"
                                        x-text="n">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Hidden Inputs for Form Submission -->
                <template x-for="n in selectedNumbers">
                    <input type="hidden" name="numbers[]" :value="n">
                </template>

                <div class="mt-8 flex flex-wrap gap-4 justify-center">
                    @if(!$results)
                        <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-400 hover:from-blue-500 hover:to-blue-300 text-white px-8 py-3 rounded-full font-bold shadow-lg transition-all transform hover:scale-105">
                            Check Combination
                        </button>
                        <button type="button" @click="quickPick()" class="bg-slate-700 hover:bg-slate-600 text-white px-8 py-3 rounded-full font-bold transition-all">
                            Quick Pick / Randomize
                        </button>
                    @endif
                    <button type="button" @click="reset()" class="text-slate-400 hover:text-white px-4 py-3 transition-colors {{ $results ? 'bg-slate-700 hover:bg-slate-600 text-white px-8 py-3 rounded-full font-bold' : '' }}">
                        {{ $results ? 'Check Another Combination' : 'Clear / Reset' }}
                    </button>
                </div>

                <div x-show="error" class="mt-4 text-red-400 text-center font-semibold" x-text="error"></div>
            </form>
        </section>

        @if($results)
            <!-- Results Section -->
            <section class="space-y-8 animate-fadeIn">
                <!-- Exact Match Alert -->
                @if($results['exact_matches'] > 0)
                    <div class="bg-green-500/20 border border-green-500 text-green-100 p-6 rounded-3xl text-center">
                        <h2 class="text-2xl font-bold mb-2">🎉 JACKPOT MATCH! 🎉</h2>
                        <p>This exact combination has been drawn <strong>{{ $results['exact_matches'] }}</strong> time(s) before!</p>
                    </div>
                @else
                    <div class="card-glass p-6 rounded-3xl text-center border border-slate-700">
                        <p class="text-lg">This combination has never won a top tier prize in our historical dataset.</p>
                    </div>
                @endif

                <!-- Statistics Summary -->
                <div class="grid md:grid-cols-1 gap-6">
                    <div class="card-glass p-6 rounded-3xl text-center">
                        <h4 class="text-slate-400 text-sm uppercase mb-2">Total Draws Checked</h4>
                        <div class="text-3xl font-bold">{{ $results['total_draws'] }}</div>
                        <p class="text-xs text-slate-500 mt-1">{{ $results['date_range']['start'] }} to {{ $results['date_range']['end'] }}</p>
                    </div>
                </div>

                <!-- Match History Table -->
                @if(count($results['match_history']) > 0)
                    <div class="card-glass rounded-3xl overflow-hidden">
                        <div class="p-6 border-b border-slate-700">
                            <h3 class="text-xl font-bold">Historical Match Details</h3>
                            <p class="text-sm text-slate-400">Showing draws with significant matches (e.g. 6, 5, 4, 3)</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-800/50 text-slate-400 text-sm">
                                    <tr>
                                        <th class="px-6 py-4">Draw Date</th>
                                        <th class="px-6 py-4">Drawn Numbers</th>
                                        <th class="px-6 py-4">Prize Tier</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700">
                                    @foreach($results['match_history'] as $match)
                                        <tr class="hover:bg-slate-700/30 transition-colors">
                                            <td class="px-6 py-4 font-semibold text-slate-300">{{ $match['date'] }}</td>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-2">
                                                    @foreach($match['numbers'] as $n)
                                                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ in_array($n, $userNumbers) ? 'bg-blue-500 text-white' : 'bg-slate-800 text-slate-500' }}">
                                                            {{ $n }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $match['tier'] == '6' ? 'bg-yellow-500/20 text-yellow-500 border border-yellow-500' : 'bg-blue-500/20 text-blue-400 border border-blue-500' }}">
                                                    {{ $match['tier'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </section>
        @endif
    </div>

    <script>
        function checkerApp() {
            return {
                selectedNumbers: @json($userNumbers ?? []),
                error: '',

                isSelected(n) {
                    return this.selectedNumbers.includes(n);
                },

                toggleNumber(n) {
                    this.error = '';
                    if (this.isSelected(n)) {
                        this.selectedNumbers = this.selectedNumbers.filter(i => i !== n);
                    } else if (this.selectedNumbers.length < 6) {
                        this.selectedNumbers.push(n);
                        this.selectedNumbers.sort((a, b) => a - b);
                    }
                },

                quickPick() {
                    this.reset();
                    while (this.selectedNumbers.length < 6) {
                        let n = Math.floor(Math.random() * 49) + 1;
                        if (!this.isSelected(n)) this.selectedNumbers.push(n);
                    }
                    this.selectedNumbers.sort((a, b) => a - b);
                },

                reset() {
                    this.selectedNumbers = [];
                    this.error = '';
                    if (window.location.search) {
                        window.location.href = window.location.pathname;
                    }
                },

                validateForm(e) {
                    if (this.selectedNumbers.length !== 6) {
                        this.error = 'Please select exactly 6 numbers.';
                    } else {
                        const overlay = document.getElementById('loading-overlay');
                        if (overlay) overlay.classList.remove('hidden');
                        e.target.submit();
                    }
                }
            }
        }
    </script>

    <style>
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection
