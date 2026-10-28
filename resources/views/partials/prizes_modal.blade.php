{{-- Winners and prize per tier of a game's latest draw, shown by setting the parent's Alpine `prizesOpen` --}}
@php
    $prizes = $draw['prizes'] ?? [];
    $showGreece = collect($prizes)->contains(fn ($tier) => ! is_null($tier['winners_gr']));
    $greek = app()->getLocale() === 'el';
    $money = function (float $amount) use ($greek) {
        $decimals = floor($amount) == $amount ? 0 : 2;

        return $greek
            ? number_format($amount, $decimals, ',', '.').' €'
            : '€'.number_format($amount, $decimals);
    };
    $count = fn (int $winners) => $greek ? number_format($winners, 0, ',', '.') : number_format($winners);
@endphp

<div x-show="prizesOpen" style="display: none;" x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4"
    @click.self="prizesOpen = false" @keydown.escape.window="prizesOpen = false"
    role="dialog" aria-modal="true" aria-label="{{ __(':game winners & prizes', ['game' => $game]) }}">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-slate-700">
            <div>
                <h2 class="text-lg font-bold">{{ __(':game winners & prizes', ['game' => $game]) }}</h2>
                <p class="text-sm text-slate-400">{{ __('Draw #:id • :date', ['id' => $draw['id'] ?? '', 'date' => $draw['date'] ?? '']) }}</p>
            </div>
            <button type="button" @click="prizesOpen = false" class="text-slate-400 hover:text-white text-2xl leading-none"
                aria-label="{{ __('Close') }}">&times;</button>
        </div>

        <div class="overflow-y-auto px-5 py-4">
            @if (empty($prizes))
                <p class="text-center text-slate-400">{{ __('No prize data for this draw.') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-400 text-left border-b border-slate-700">
                            <th class="py-2 pr-2 font-medium">{{ __('Category') }}</th>
                            <th class="py-2 px-2 font-medium text-right">{{ __('Winners') }}</th>
                            @if ($showGreece)
                                <th class="py-2 px-2 font-medium text-right">{{ __('In Greece') }}</th>
                            @endif
                            <th class="py-2 pl-2 font-medium text-right">{{ __('Prize') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prizes as $tier)
                            <tr class="border-b border-slate-700/50 last:border-0">
                                <td class="py-2 pr-2 font-semibold whitespace-nowrap">{{ $tier['tier'] }}</td>
                                <td class="py-2 px-2 text-right tabular-nums">{{ $count($tier['winners'] ?? 0) }}</td>
                                @if ($showGreece)
                                    <td class="py-2 px-2 text-right tabular-nums">{{ is_null($tier['winners_gr']) ? '—' : $count($tier['winners_gr']) }}</td>
                                @endif
                                <td class="py-2 pl-2 text-right tabular-nums whitespace-nowrap">
                                    @if ($tier['jackpot'])
                                        <span class="font-bold text-yellow-400">{{ __('Jackpot') }}</span>
                                    @elseif (is_null($tier['prize']))
                                        —
                                    @else
                                        {{ $money($tier['prize']) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-3 text-xs text-slate-500">{{ __('Prize per winning ticket. "Jackpot" means nobody won the top tier and the prize rolls over.') }}</p>
            @endif
        </div>
    </div>
</div>
