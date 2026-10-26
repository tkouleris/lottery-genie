<?php

namespace App\Console\Commands;

use App\Services\EurojackpotService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;

class CacheEurojackpot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-eurojackpot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load Eurojackpot draw files and cache their draws, stats, number delays and latest draw date';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $folder = 'stats/euro';
        $obj = resolve(EurojackpotService::class);
        $output = $obj->load_files($folder);

        Cache::forget('eurojackpot_draws');
        $finalStatistics = $output['draws'];
        Cache::put('eurojackpot_draws', $finalStatistics, now()->addDays(7));

        Cache::forget('eurojackpot_stats');
        $stats = $output['stats'];
        Cache::put('eurojackpot_stats', $stats, now()->addDays(7));

        Cache::forget('eurojackpot_delays');
        $delays = $output['delays'];
        Cache::put('eurojackpot_delays', $delays, now()->addDays(7));

        // Replaced by eurojackpot_stats
        Cache::forget('eurojackpot_history');

        Cache::forget('eurojackpot_latest_draw_date');
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('eurojackpot_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
