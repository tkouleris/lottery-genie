<?php

namespace App\Console\Commands;

use App\Services\EurojackpotService;
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::forget('lotto_latest_draw_date');
        $obj = resolve(LottoService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('lotto_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
