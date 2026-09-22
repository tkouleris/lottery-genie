<?php

namespace App\Console\Commands;

use App\Helpers\File;
use App\Services\EurojackpotService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $obj = resolve(EurojackpotService::class);

        Cache::forget('eurojackpot_draws');
        $output = $obj->load_files();
        $finalStatistics = $output['stats'];
        Cache::put('eurojackpot_draws', $finalStatistics, now()->addDays(7));


        Cache::forget('eurojackpot_stats');
        $allDraws = $finalStatistics;
        Cache::put('eurojackpot_stats', $allDraws, now()->addDays(7));


        Cache::forget('eurojackpot_latest_draw_date');
        $obj = resolve(EurojackpotService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('eurojackpot_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
