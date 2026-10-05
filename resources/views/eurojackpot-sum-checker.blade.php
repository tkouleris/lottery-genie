@extends('layouts.app')

@section('title', 'Eurojackpot Sum Checker')

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset('img/eurojackpot.jpg') }}" alt="Eurojackpot Logo" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">Eurojackpot Sum Checker</h1>
        <p class="text-slate-400 text-lg">Check how your main numbers' sum compares with {{ $sumStats['total'] }} historical draws</p>
    </header>

    <section class="card-glass rounded-3xl p-8"
             x-data="sumCalculator({{ json_encode([
                 'optimal' => $sumStats['optimal_range'],
                 'moderate' => $sumStats['moderate_range'],
                 'buckets' => $sumStats['buckets'],
             ]) }})">
        <h2 class="text-2xl font-bold mb-2 text-purple-400">Check Ticket Sum</h2>
        <p class="text-slate-400 text-sm mb-4">Enter 5 main numbers (1-50) to see how their sum compares with the historical draws.</p>
        <div class="flex flex-wrap justify-center gap-3 mb-6">
            <template x-for="(n, i) in numbers" :key="i">
                <input type="number" min="1" max="50" x-model="numbers[i]"
                       class="w-16 h-16 text-center text-xl font-bold rounded-full bg-slate-900 border-2 border-slate-600 focus:border-purple-400 focus:outline-none text-white"
                       :class="{ 'border-red-500': isInvalid(i) }">
            </template>
            <button type="button" @click="numbers = ['', '', '', '', '']"
                    class="px-4 rounded-xl bg-slate-700 hover:bg-slate-600 text-sm text-slate-300 transition-colors">
                Clear
            </button>
        </div>

        <template x-if="error">
            <p class="text-center text-sm text-red-400" x-text="error"></p>
        </template>
        <template x-if="!error && filled() < 5">
            <p class="text-center text-sm text-slate-400">
                Running sum: <span class="font-bold text-slate-200" x-text="sum()"></span>
                (<span x-text="filled()"></span>/5 numbers)
            </p>
        </template>
        <template x-if="!error && filled() === 5">
            <div class="flex flex-col md:flex-row items-center justify-center gap-4 p-4 rounded-xl border"
                 :class="status().box">
                <span class="text-4xl font-bold" x-text="sum()"></span>
                <div class="text-center md:text-left">
                    <span class="block font-bold" :class="status().text" x-text="status().label"></span>
                    <span class="block text-sm text-slate-300" x-text="status().message"></span>
                    <span class="block text-xs text-slate-400 mt-1" x-show="bucket()"
                          x-text="bucket() ? `Range ${bucket().label} (${bucket().name}): ${bucket().percentage}% of historical draws` : ''"></span>
                </div>
            </div>
        </template>
    </section>

    <script>
        function sumCalculator(config) {
            return {
                numbers: ['', '', '', '', ''],
                values() {
                    return this.numbers.filter(n => n !== '' && n !== null).map(n => parseInt(n, 10));
                },
                filled() {
                    return this.values().length;
                },
                sum() {
                    return this.values().reduce((a, b) => a + b, 0);
                },
                isInvalid(i) {
                    const raw = this.numbers[i];
                    if (raw === '' || raw === null) return false;
                    const n = parseInt(raw, 10);
                    return !Number.isInteger(n) || n < 1 || n > 50
                        || this.numbers.some((other, j) => j !== i && other !== '' && parseInt(other, 10) === n);
                },
                get error() {
                    const vals = this.values();
                    if (vals.some(n => !Number.isInteger(n) || n < 1 || n > 50)) return 'Numbers must be between 1 and 50.';
                    if (new Set(vals).size !== vals.length) return 'Numbers must be unique.';
                    return null;
                },
                bucket() {
                    const s = this.sum();
                    return config.buckets.find(b => s >= b.min && s <= b.max) || null;
                },
                status() {
                    const s = this.sum();
                    if (s >= config.optimal.min && s <= config.optimal.max) {
                        return {
                            label: '🟢 Optimal',
                            message: `Within the high-probability range (${config.optimal.min}–${config.optimal.max}).`,
                            text: 'text-green-400',
                            box: 'bg-green-500/10 border-green-500/40',
                        };
                    }
                    if (s >= config.moderate.min && s <= config.moderate.max) {
                        return {
                            label: '🟡 Moderate',
                            message: 'Acceptable, but sums in this range are drawn less often.',
                            text: 'text-yellow-400',
                            box: 'bg-yellow-500/10 border-yellow-500/40',
                        };
                    }
                    return {
                        label: '🔴 Extreme / Low Probability',
                        message: s < config.moderate.min ? 'The sum is too low. Consider adding higher numbers.' : 'The sum is too high. Consider adding lower numbers.',
                        text: 'text-red-400',
                        box: 'bg-red-500/10 border-red-500/40',
                    };
                },
            };
        }
    </script>

    <div class="mt-8 text-center">
        <a href="{{ route('eurojackpot.stats') }}"
           class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            View Sum Distribution Stats
        </a>
    </div>
@endsection
