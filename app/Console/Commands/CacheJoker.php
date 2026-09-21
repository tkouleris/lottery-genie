<?php

namespace App\Console\Commands;

use App\Helpers\File;
use App\Services\JokerService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folder = 'stats/joker';
        $output = $this->load_files($folder);

        Cache::forget('joker_draws');
        $finalStatistics = $output['draws'];
        Cache::put('joker_draws', $finalStatistics, now()->addDays(7));

        Cache::forget('joker_stats');
        $stats = $output['stats'];
        Cache::put('joker_stats', $stats, now()->addDays(7));

        Cache::forget('joker_latest_draw_date');
        $obj = resolve(JokerService::class);
        $latest_draw = $obj->getLatestDrawDate();
        Cache::put('joker_latest_draw_date', $latest_draw, now()->addDays(7));
    }

    private function load_files($folder = 'stats/joker')
    {
        $files = File::load_xlsx_files($folder);
        $finalStatistics = [];
        $stats = [];
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
                    // Jokers are at indices 7
                    $drawData = [];
                    for ($i = 2; $i <= 7; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }

                    if (count($drawData) >= 6) {
                        $finalStatistics[] = $drawData;
                    }

                    if (count($drawData) >= 6) {
                        $numbers = array_map('intval', array_slice($drawData, 0, 5));
                        $joker = intval($drawData[5]);
                        sort($numbers);
                        $stats[] = [
                            'numbers' => $numbers,
                            'joker' => $joker
                        ];
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        return ['draws' => $finalStatistics, 'stats' => $stats];
    }
}
