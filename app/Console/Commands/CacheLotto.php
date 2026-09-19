<?php

namespace App\Console\Commands;

use App\Helpers\File;
use App\Services\EurojackpotService;
use App\Services\LottoService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $folder = 'stats/lotto';
        $files = File::load_xlsx_files($folder);

        Cache::forget('lotto_draws');
        $finalStatistics = [];
        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Skip header rows (first 3 rows) and non-numeric rows
                    if ($index < 4 ) {
                        continue;
                    }

                    // Φιλτράρισμα κενών κελιών
                    $drawData = [];
                    for ($i = 2; $i <= 7; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }
                    if (count($drawData) >= 6) {
                        $finalStatistics[] = array_map('intval', array_values($drawData));
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        Cache::put('lotto_draws', $finalStatistics, now()->addDays(7));

        Cache::forget('lotto_stats');
        $draws = [];
        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Skip header rows (first 3 rows) and non-numeric rows
                    if ($index < 4 ) {
                        continue;
                    }


                    // Φιλτράρισμα κενών κελιών
                    $drawData = [];
                    for ($i = 2; $i <= 7; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }

                    if (count($drawData) >= 6) {
                        $numbers = array_map('intval', array_slice($drawData, 0, 6));
                        sort($numbers);
                        $draws[] = [
                            'numbers' => $numbers
                        ];
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        Cache::put('lotto_stats', $draws, now()->addDays(7));

        Cache::forget('lotto_latest_draw_date');
        $obj = resolve(LottoService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('lotto_latest_draw_date', $latest_draw, now()->addDays(7));
    }
}
