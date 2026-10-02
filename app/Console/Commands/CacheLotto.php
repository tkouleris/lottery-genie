<?php

namespace App\Console\Commands;

use App\Services\LottoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheLotto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-lotto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load Lotto draw files and cache their draws, stats, number delays and latest draw date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folder = 'stats/lotto';
        $obj = resolve(LottoService::class);
        $output = $obj->load_files($folder);

        Cache::forget('lotto_draws');
        $finalStatistics = $output['draws'];
        Cache::put('lotto_draws', $finalStatistics, now()->addDays(7));

        Cache::forget('lotto_stats');
        $draws = $output['stats'];
        Cache::put('lotto_stats', $draws, now()->addDays(7));

        Cache::forget('lotto_delays');
        $delays = $output['delays'];
        Cache::put('lotto_delays', $delays, now()->addDays(7));

        Cache::forget('lotto_latest_draw_date');
        $obj = resolve(LottoService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('lotto_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
