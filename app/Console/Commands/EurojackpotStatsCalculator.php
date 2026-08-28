<?php

namespace App\Console\Commands;

use App\Helpers\File;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EurojackpotStatsCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:eurojackpot-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Eurojackpot statistics: number frequency, joker frequency, and joker combinations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $folder = 'stats/euro';
        try {
            $files = File::load_xlsx_files($folder);
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $allDraws = [];

        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Skip header if it looks like one (e.g., contains non-numeric data in first column)
                    if ($index === 0 && !is_numeric($row[0])) {
                        continue;
                    }

                    // Filter out empty rows and ensure we have enough columns (5 numbers + 2 jokers)
                    $data = array_filter($row, fn($cell) => $cell !== null);
                    if (count($data) >= 7) {
                        $allDraws[] = array_map('intval', array_values($data));
                    }
                }
            } catch (Exception $e) {
                $this->error("Error reading file {$file}: " . $e->getMessage());
            }
        }

        if (empty($allDraws)) {
            $this->error("No data found.");
            return 1;
        }

        $numberFrequency = array_fill(1, 50, 0);
        $jokerFrequency = array_fill(1, 12, 0);
        $jokerPairsFrequency = [];

        foreach ($allDraws as $draw) {
            // Assume columns 0-4 are numbers, 5-6 are jokers based on EurojackpotService
            for ($i = 0; $i < 5; $i++) {
                if (isset($draw[$i]) && $draw[$i] >= 1 && $draw[$i] <= 50) {
                    $numberFrequency[$draw[$i]]++;
                }
            }

            $jokers = [];
            for ($i = 5; $i <= 6; $i++) {
                if (isset($draw[$i]) && $draw[$i] >= 1 && $draw[$i] <= 12) {
                    $jokerFrequency[$draw[$i]]++;
                    $jokers[] = $draw[$i];
                }
            }

            if (count($jokers) === 2) {
                sort($jokers);
                $pair = implode('-', $jokers);
                if (!isset($jokerPairsFrequency[$pair])) {
                    $jokerPairsFrequency[$pair] = 0;
                }
                $jokerPairsFrequency[$pair]++;
            }
        }

        // Sort frequencies
        arsort($numberFrequency);
        arsort($jokerFrequency);
        arsort($jokerPairsFrequency);

        $results = [
            'number_frequency' => $numberFrequency,
            'joker_frequency' => $jokerFrequency,
            'common_joker_combinations' => array_slice($jokerPairsFrequency, 0, 10, true),
            'total_draws_analyzed' => count($allDraws)
        ];

        dd(json_encode($results, JSON_PRETTY_PRINT));

        return 0;
    }
}
