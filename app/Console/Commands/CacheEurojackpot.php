<?php

namespace App\Console\Commands;

use App\Services\EurojackpotService;
use Illuminate\Console\Command;
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::forget('eurojackpot_latest_draw_date');
        $obj = resolve(EurojackpotService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('eurojackpot_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
