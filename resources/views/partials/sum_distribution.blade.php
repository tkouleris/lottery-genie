{{-- Main numbers sum range distribution. Expects $sumStats (SumDistribution::calculate) and $sumCheckerRoute. --}}
<section class="card-glass rounded-3xl p-8">
    <h2 class="text-2xl font-bold mb-2 text-purple-400">Main Numbers Sum Range Distribution</h2>
    <p class="text-slate-400 text-sm mb-6">
        Sum of the {{ $sumStats['pick'] }} main numbers per draw (possible range {{ $sumStats['min_possible'] }} – {{ $sumStats['max_possible'] }}, theoretical mean {{ $sumStats['theoretical_mean'] }}).
        Sums cluster around the middle in a bell curve, so extreme combinations are rarely drawn.
    </p>

    <!-- Key Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="p-4 rounded-xl bg-slate-800/50 text-center">
            <span class="block text-xs text-slate-400 mb-1">Average Historical Sum</span>
            <span class="block text-2xl font-bold text-slate-100">{{ $sumStats['average'] }}</span>
            <span class="block text-xs text-slate-500 mt-1">{{ $sumStats['total'] }} draws</span>
        </div>
        <div class="p-4 rounded-xl bg-slate-800/50 text-center">
            <span class="block text-xs text-slate-400 mb-1">Lowest Recorded Sum</span>
            <span class="block text-2xl font-bold text-red-400">{{ $sumStats['lowest']['sum'] ?? '-' }}</span>
            @if($sumStats['lowest'])
                <span class="block text-xs text-slate-500 mt-1">{{ $sumStats['lowest']['date'] }}</span>
                <span class="block text-xs text-slate-500">{{ implode(' · ', $sumStats['lowest']['numbers']) }}</span>
            @endif
        </div>
        <div class="p-4 rounded-xl bg-slate-800/50 text-center">
            <span class="block text-xs text-slate-400 mb-1">Highest Recorded Sum</span>
            <span class="block text-2xl font-bold text-red-400">{{ $sumStats['highest']['sum'] ?? '-' }}</span>
            @if($sumStats['highest'])
                <span class="block text-xs text-slate-500 mt-1">{{ $sumStats['highest']['date'] }}</span>
                <span class="block text-xs text-slate-500">{{ implode(' · ', $sumStats['highest']['numbers']) }}</span>
            @endif
        </div>
        <div class="p-4 rounded-xl bg-green-500/10 border border-green-500/30 text-center">
            <span class="block text-xs text-slate-400 mb-1">Optimal Playing Range</span>
            <span class="block text-2xl font-bold text-green-400">{{ $sumStats['optimal_range']['min'] }} – {{ $sumStats['optimal_range']['max'] }}</span>
            <span class="block text-xs text-slate-500 mt-1">{{ $sumStats['optimal_percentage'] }}% of draws</span>
        </div>
    </div>

    <!-- Histogram -->
    <div class="flex items-end gap-2 md:gap-4 h-64 mb-2">
        @foreach($sumStats['buckets'] as $index => $bucket)
            @php
                $isTop = $index === $sumStats['most_frequent_index'];
                $height = $sumStats['max_count'] > 0 ? max(2, round($bucket['count'] / $sumStats['max_count'] * 100)) : 2;
            @endphp
            <div class="flex-1 flex flex-col items-center justify-end h-full">
                @if($isTop)
                    <span class="mb-1 px-2 py-0.5 rounded-full bg-purple-500/20 border border-purple-400/50 text-[10px] font-bold text-purple-300 whitespace-nowrap">Most Frequent</span>
                @endif
                <span class="text-xs font-bold mb-1 {{ $isTop ? 'text-purple-300' : 'text-slate-300' }}">{{ $bucket['percentage'] }}%</span>
                <div class="w-full rounded-t-lg {{ $isTop ? 'bg-gradient-to-t from-purple-600 to-purple-400 shadow-lg shadow-purple-500/30' : 'bg-gradient-to-t from-blue-700 to-blue-400' }}"
                     style="height: {{ $height }}%"
                     title="{{ $bucket['name'] }}: {{ $bucket['count'] }} draws"></div>
            </div>
        @endforeach
    </div>
    <div class="flex gap-2 md:gap-4 mb-8">
        @foreach($sumStats['buckets'] as $index => $bucket)
            <div class="flex-1 text-center">
                <span class="block text-xs font-bold {{ $index === $sumStats['most_frequent_index'] ? 'text-purple-300' : 'text-slate-300' }}">{{ $bucket['label'] }}</span>
                <span class="hidden md:block text-[10px] text-slate-500">{{ $bucket['name'] }}</span>
                <span class="block text-[10px] text-slate-400">{{ $bucket['count'] }} dr.</span>
            </div>
        @endforeach
    </div>
    <p class="text-center text-sm text-slate-400">
        Want to check your own ticket?
        <a href="{{ route($sumCheckerRoute) }}" class="font-bold text-purple-400 hover:text-purple-300 transition-colors">Open the Sum Checker →</a>
    </p>
</section>
