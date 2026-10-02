<?php

namespace App\Console\Commands;

use App\Services\JokerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheJoker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-joker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load Joker draw files and cache their draws, stats, number delays and latest draw date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folder = 'stats/joker';
        $obj = resolve(JokerService::class);
        $output = $obj->load_files($folder);

        Cache::forget('joker_draws');
        $finalStatistics = $output['draws'];
        Cache::put('joker_draws', $finalStatistics, now()->addDays(7));

        Cache::forget('joker_stats');
        $stats = $output['stats'];
        Cache::put('joker_stats', $stats, now()->addDays(7));

        Cache::forget('joker_delays');
        $delays = $output['delays'];
        Cache::put('joker_delays', $delays, now()->addDays(7));

        Cache::forget('joker_latest_draw_date');
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('joker_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
