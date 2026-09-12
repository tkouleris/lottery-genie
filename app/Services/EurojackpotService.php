<?php

namespace App\Services;

use App\Helpers\File;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EurojackpotService
{
    /**
     * @return array[]
     * @throws FileNotFoundException
     */
    public function run($folder = 'stats/euro'): array
    {
        $files = File::load_xlsx_files($folder);
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

        if (empty($finalStatistics)) {
            $folderPath = storage_path($folder);
            throw new Exception("No data found in {$folderPath}. Using empty dataset.");
        }

        $joker = array_fill(1, 12, 0);
        $number = array_fill(1, 50, 0);
        $jokerEven = array_fill(0, 3, 0);
        $jokerSums = [];

        foreach ($finalStatistics as $draw) {

            for ($i = 0; $i < 5; $i++) {
                if (isset($draw[$i])) {
                    $number[$draw[$i]]++;
                }
            }

            if (isset($draw[5])) {
                $joker[$draw[5]]++;
            }
            if (isset($draw[6])) {
                $joker[$draw[6]]++;
            }

            $tmpJokerDraw = array_slice($draw, 5, 2);
            if (count($tmpJokerDraw) === 2) {
                $jokerEvens = count(array_filter($tmpJokerDraw, fn($n) => $n % 2 === 0));
                $jokerEven[$jokerEvens]++;

                $drawJokerSum = array_sum($tmpJokerDraw);
                $jokerSums[$drawJokerSum] = ($jokerSums[$drawJokerSum] ?? 0) + 1;
            }
        }


        $jokerStats = [];
        foreach ($joker as $key => $value) {
            $jokerStats = array_merge($jokerStats, array_fill(0, $value, $key));
        }
        shuffle($jokerStats);

        $stats = [];
        foreach ($number as $key => $value) {
            $stats = array_merge($stats, array_fill(0, $value, $key));
        }
        shuffle($stats);

        arsort($jokerSums);
        $allowedJokerSums = array_slice(array_keys($jokerSums), 0, (int)(count($jokerSums) / 2));
        $draws = [];
        for ($i = 0; $i < 100; $i++) {
            $draw = [
                'numbers' => [],
                'joker' => []
            ];

            while (count($draw['numbers']) === 0) {
                $currentNumbers = [];
                while (count($currentNumbers) < 5) {
                    $val = $stats[array_rand($stats)];
                    if (!in_array($val, $currentNumbers)) {
                        $currentNumbers[] = $val;
                    }
                }
                sort($currentNumbers);

                $draw['numbers'] = $currentNumbers;
            }

            while (count($draw['joker']) === 0) {
                $currentJokers = [];
                while (count($currentJokers) < 2) {
                    $val = $jokerStats[array_rand($jokerStats)];
                    if (!in_array($val, $currentJokers)) {
                        $currentJokers[] = $val;
                    }
                }
                sort($currentJokers);

                if (!in_array(array_sum($currentJokers), $allowedJokerSums)) {
                    continue;
                }

                $draw['joker'] = $currentJokers;
            }

            $draws[] = $draw;
        }

        $statisticsNumbers = array_fill(1, 50, 0);
        $statisticsJoker = array_fill(1, 12, 0);

        foreach ($draws as $d) {
            foreach ($d['numbers'] as $n) {
                $statisticsNumbers[$n]++;
            }
            foreach ($d['joker'] as $j) {
                $statisticsJoker[$j]++;
            }
        }

        arsort($statisticsNumbers);
        $topNumbers = array_slice(array_keys($statisticsNumbers), 0, 10);
        $finalNumbers = array_slice($topNumbers, 0, 5);
        sort($finalNumbers);

        arsort($statisticsJoker);
        $topJokers = array_slice(array_keys($statisticsJoker), 0, 4);
        $finalJokers = array_slice($topJokers, 0, 2);
        sort($finalJokers);


        return [
            'numbers' => $finalNumbers,
            'jokers' => $finalJokers,
        ];
    }

    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     */
    public function get_stats(string $folder = 'stats/euro'): array
    {
        $files = File::load_xlsx_files($folder);
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

        if (empty($allDraws)) {
            throw new Exception("No data found for statistics.");
        }

        $numberFrequency = array_fill(1, 50, 0);
        $jokerFrequency = array_fill(1, 12, 0);
        $jokerPairsFrequency = [];
        $even_odd_freq = [];

        foreach ($allDraws as $draw) {
            $evenCount = 0;
            $oddCount = 0;
            for ($i = 0; $i < 5; $i++) {
                if (isset($draw[$i]) && $draw[$i] >= 1 && $draw[$i] <= 50) {
                    $numberFrequency[$draw[$i]]++;
                    if ($draw[$i] % 2 === 0) {
                        $evenCount++;
                    } else {
                        $oddCount++;
                    }
                }
            }
            $evenOddKey = "{$evenCount} even / {$oddCount} odd";
            $even_odd_freq[$evenOddKey] = ($even_odd_freq[$evenOddKey] ?? 0) + 1;

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

        arsort($numberFrequency);
        arsort($jokerFrequency);
        arsort($jokerPairsFrequency);
        arsort($even_odd_freq);

        return [
            'number_frequency' => $numberFrequency,
            'joker_frequency' => $jokerFrequency,
            'common_joker_combinations' => array_slice($jokerPairsFrequency, 0, 10, true),
            'even_odd_stats' => $even_odd_freq,
            'total_draws_analyzed' => count($allDraws),
            'latest_draw_date' => File::get_latest_file_date($folder),
        ];
    }

}
