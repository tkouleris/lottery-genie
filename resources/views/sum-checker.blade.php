@extends('layouts.app')

@section('title', __(':game Sum Checker', ['game' => $game['name']]))
@section('description', __('Check how the sum of your :game numbers compares with the sums of historical draws.', ['game' => $game['name']]))
@section('keywords', __(':game sum checker, :game number sum, sum distribution, lottery sum', ['game' => $game['name']]))

@section('content')
    <header class="text-center mb-12">
        <img src="{{ asset($game['logo']) }}" alt="{{ __(':game Logo', ['game' => $game['name']]) }}" class="mx-auto" style="max-height: 150px;">
        <h1 class="text-3xl font-bold mt-4">{{ __(':game Sum Checker', ['game' => $game['name']]) }}</h1>
        <p class="text-slate-400 text-lg">{{ __("Check how your main numbers' sum compares with :count historical draws", ['count' => $sumStats['total']]) }}</p>
    </header>

    <section class="card-glass rounded-3xl p-8"
             x-data="sumCalculator({{ json_encode([
                 'optimal' => $sumStats['optimal_range'],
                 'moderate' => $sumStats['moderate_range'],
                 'buckets' => array_map(fn ($bucket) => ['name' => __($bucket['name'])] + $bucket, $sumStats['buckets']),
                 'numbers' => $initialNumbers,
                 'maxNumber' => $sumStats['max_number'],
             ]) }})">
        <h2 class="text-2xl font-bold mb-2 text-purple-400">{{ __('Check Ticket Sum') }}</h2>
        <p class="text-slate-400 text-sm mb-4">{{ __('Enter :pick main numbers (1-:max) to see how their sum compares with the historical draws.', ['pick' => $sumStats['pick'], 'max' => $sumStats['max_number']]) }}</p>
        <div class="flex flex-wrap justify-center gap-3 mb-6">
            <template x-for="(n, i) in numbers" :key="i">
                <input type="number" min="1" :max="maxNumber" x-model="numbers[i]"
                       class="w-16 h-16 text-center text-xl font-bold rounded-full bg-slate-900 border-2 border-slate-600 focus:border-purple-400 focus:outline-none text-white"
                       :class="{ 'border-red-500': isInvalid(i) }">
            </template>
            <button type="button" @click="numbers = numbers.map(() => '')"
                    class="px-4 rounded-xl bg-slate-700 hover:bg-slate-600 text-sm text-slate-300 transition-colors">
                {{ __('Clear') }}
            </button>
        </div>

        <template x-if="error">
            <p class="text-center text-sm text-red-400" x-text="error"></p>
        </template>
        <template x-if="!error && filled() < numbers.length">
            <p class="text-center text-sm text-slate-400">
                {{ __('Running sum:') }} <span class="font-bold text-slate-200" x-text="sum()"></span>
                (<span x-text="filled()"></span>/<span x-text="numbers.length"></span> {{ __('numbers') }})
            </p>
        </template>
        <template x-if="!error && filled() === numbers.length">
            <div class="flex flex-col md:flex-row items-center justify-center gap-4 p-4 rounded-xl border"
                 :class="status().box">
                <span class="text-4xl font-bold" x-text="sum()"></span>
                <div class="text-center md:text-left">
                    <span class="block font-bold" :class="status().text" x-text="status().label"></span>
                    <span class="block text-sm text-slate-300" x-text="status().message"></span>
                    <span class="block text-xs text-slate-400 mt-1" x-show="bucket()"
                          x-text="bucketText()"></span>
                </div>
            </div>
        </template>
    </section>

    @php
        $sumI18n = [
            'range' => __('Numbers must be between 1 and :max.'),
            'unique' => __('Numbers must be unique.'),
            'bucket' => __('Range :label (:name): :percentage% of historical draws'),
            'optimal' => __('Optimal'),
            'optimalMessage' => __('Within the high-probability range (:min–:max).'),
            'moderate' => __('Moderate'),
            'moderateMessage' => __('Acceptable, but sums in this range are drawn less often.'),
            'extreme' => __('Extreme / Low Probability'),
            'tooLow' => __('The sum is too low. Consider adding higher numbers.'),
            'tooHigh' => __('The sum is too high. Consider adding lower numbers.'),
        ];
    @endphp
    <script>
        const sumI18n = @json($sumI18n);

        function fmt(text, params) {
            return text.replace(/:(\w+)/g, (match, key) => key in params ? params[key] : match);
        }

        function sumCalculator(config) {
            return {
                numbers: config.numbers,
                maxNumber: config.maxNumber,
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
                    return !Number.isInteger(n) || n < 1 || n > this.maxNumber
                        || this.numbers.some((other, j) => j !== i && other !== '' && parseInt(other, 10) === n);
                },
                get error() {
                    const vals = this.values();
                    if (vals.some(n => !Number.isInteger(n) || n < 1 || n > this.maxNumber)) return fmt(sumI18n.range, { max: this.maxNumber });
                    if (new Set(vals).size !== vals.length) return sumI18n.unique;
                    return null;
                },
                bucket() {
                    const s = this.sum();
                    return config.buckets.find(b => s >= b.min && s <= b.max) || null;
                },
                bucketText() {
                    const b = this.bucket();
                    return b ? fmt(sumI18n.bucket, { label: b.label, name: b.name, percentage: b.percentage }) : '';
                },
                status() {
                    const s = this.sum();
                    if (s >= config.optimal.min && s <= config.optimal.max) {
                        return {
                            label: `🟢 ${sumI18n.optimal}`,
                            message: fmt(sumI18n.optimalMessage, { min: config.optimal.min, max: config.optimal.max }),
                            text: 'text-green-400',
                            box: 'bg-green-500/10 border-green-500/40',
                        };
                    }
                    if (s >= config.moderate.min && s <= config.moderate.max) {
                        return {
                            label: `🟡 ${sumI18n.moderate}`,
                            message: sumI18n.moderateMessage,
                            text: 'text-yellow-400',
                            box: 'bg-yellow-500/10 border-yellow-500/40',
                        };
                    }
                    return {
                        label: `🔴 ${sumI18n.extreme}`,
                        message: s < config.moderate.min ? sumI18n.tooLow : sumI18n.tooHigh,
                        text: 'text-red-400',
                        box: 'bg-red-500/10 border-red-500/40',
                    };
                },
            };
        }
    </script>

    <div class="mt-8 text-center">
        <a href="{{ route($game['stats_route']) }}"
           class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-700 text-white font-bold hover:from-blue-600 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25">
            {{ __('View Historical Statistics') }}
        </a>
    </div>
@endsection
