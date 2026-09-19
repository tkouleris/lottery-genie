<?php

namespace App\Console\Commands;

use App\Helpers\File;
use App\Services\EurojackpotService;
use Exception;
use Illuminate\Console\Command;
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
     */
    public function handle()
    {
        $folder = 'stats/euro';
        $files = File::load_xlsx_files($folder);


        Cache::forget('eurojackpot_draws');
        $finalStatistics = [];
        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Skip header rows (first 3 rows) and non-numeric rows
                    if ($index < 3 ) {
                        continue;
                    }

                    // The numbers start 2 columns after the date (which is at index 1)
                    // So numbers are at indices 2, 3, 4, 5, 6
                    // Jokers are at indices 7, 8
                    $drawData = [];
                    for ($i = 2; $i <= 8; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }

                    if (count($drawData) === 7) {
                        $finalStatistics[] = $drawData;
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        Cache::put('eurojackpot_draws', $finalStatistics, now()->addDays(7));


        Cache::forget('eurojackpot_stats');
        $allDraws = [];
        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Skip header rows (first 3 rows) and non-numeric rows
                    if ($index < 3) {
                        continue;
                    }

                    // Numbers at indices 2-6, Jokers at indices 7-8
                    $drawData = [];
                    for ($i = 2; $i <= 8; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }

                    if (count($drawData) === 7) {
                        $allDraws[] = $drawData;
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        Cache::put('eurojackpot_stats', $allDraws, now()->addDays(7));


        Cache::forget('eurojackpot_latest_draw_date');
        $obj = resolve(EurojackpotService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('eurojackpot_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
