{{-- Max - min range distribution. Expects $rangeSeries (list of title, color, bar, data from RangeDistribution::calculate) and $rangeExample. --}}
<section class="card-glass rounded-3xl p-8">
    <h2 class="text-2xl font-bold mb-2 text-cyan-400">Range Distribution (Max − Min)</h2>
    <p class="text-slate-400 text-sm mb-6">
        Spread between the highest and lowest number of each draw. {{ $rangeExample }}
    </p>
    <div class="grid {{ count($rangeSeries) > 1 ? 'md:grid-cols-2' : '' }} gap-8">
        @foreach($rangeSeries as $range)
            <div>
                <h3 class="text-xl font-bold mb-4 {{ $range['color'] }}">{{ $range['title'] }}</h3>
                <div class="space-y-2">
                    @foreach($range['data']['buckets'] as $bucket)
                        @php $isTop = $bucket['label'] === $range['data']['most_frequent']; @endphp
                        <div class="flex items-center gap-3">
                            <span class="w-12 text-right text-sm font-bold {{ $isTop ? 'text-cyan-300' : 'text-slate-300' }}">{{ $bucket['label'] }}</span>
                            <div class="flex-1 h-5 rounded-lg bg-slate-800/50 overflow-hidden">
                                <div class="h-full rounded-lg bg-gradient-to-r {{ $isTop ? 'from-cyan-600 to-cyan-400' : $range['bar'] }}"
                                     style="width: {{ $range['data']['max_count'] > 0 ? max(1, round($bucket['count'] / $range['data']['max_count'] * 100)) : 1 }}%"></div>
                            </div>
                            <span class="w-28 text-right text-xs text-slate-400">{{ $bucket['count'] }} dr. ({{ $bucket['percentage'] }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
