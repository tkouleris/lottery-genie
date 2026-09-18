<?php

namespace App\Services;

use App\Helpers\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JokerService
{
    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function getStats(string $folder = 'stats/joker'): array
    {
        $out = Cache::get('joker_stats');

        $draws = [];
        if(is_null($out)) {
            $files = File::load_xlsx_files($folder);

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
                            $numbers = array_map('intval', array_slice($drawData, 0, 5));
                            $joker = intval($drawData[5]);
                            sort($numbers);
                            $draws[] = [
                                'numbers' => $numbers,
                                'joker' => $joker
                            ];
                        }
                    }
                } catch (Exception $e) {
                    Log::error("Error reading file {$file}: " . $e->getMessage());
                }
            }
        }else{
            $draws = $out;
        }


        if (empty($draws)) {
            throw new Exception("No data found in " . storage_path($folder));
        }

        return $this->calculateStatistics($draws, $folder);
    }

    private function calculateStatistics(array $draws, string $folder): array
    {
        $medians = [];
        $jokers = [];
        $numbers_freq = [];
        $even_odd_freq = [];
        $totalDraws = count($draws);

        foreach ($draws as $draw) {
            $numbers = $draw['numbers']; // Ήδη ταξινομημένα
            $joker = $draw['joker'];

            // 1. Διάμεσος (ο 3ος αριθμός στην πεντάδα)
            $median = $numbers[2];
            $medians[$median] = ($medians[$median] ?? 0) + 1;

            // 2. Τζόκερ
            $jokers[$joker] = ($jokers[$joker] ?? 0) + 1;

            // 3. Απλά νούμερα
            foreach ($numbers as $num) {
                $numbers_freq[$num] = ($numbers_freq[$num] ?? 0) + 1;
            }

            // 4. Συχνότητα Even / Odd (για τα 5 νούμερα)
            $evenCount = 0;
            $oddCount = 0;
            foreach ($numbers as $num) {
                if ($num % 2 === 0) {
                    $evenCount++;
                } else {
                    $oddCount++;
                }
            }
            $evenOddKey = "{$evenCount} even / {$oddCount} odd";
            $even_odd_freq[$evenOddKey] = ($even_odd_freq[$evenOddKey] ?? 0) + 1;
        }

        arsort($medians);
        arsort($jokers);
        arsort($numbers_freq);
        arsort($even_odd_freq);

        return [
            'top_medians' => array_slice($medians, 0, 10, true),
            'top_jokers' => array_slice($jokers, 0, 10, true),
            'top_numbers' => array_slice($numbers_freq, 0, 10, true),
            'even_odd_stats' => $even_odd_freq,
            'total_draws_analyzed' => $totalDraws,
            'latest_draw_date' => File::get_latest_file_date($folder),
        ];
    }

    /**
     * Helper to get combinations
     */
    private function getCombinations(array $base, int $n): array
    {
        $results = [];
        $count = count($base);

        if ($n === 1) {
            foreach ($base as $b) {
                $results[] = [$b];
            }
            return $results;
        }

        for ($i = 0; $i <= $count - $n; $i++) {
            $first = $base[$i];
            $remaining = array_slice($base, $i + 1);
            foreach ($this->getCombinations($remaining, $n - 1) as $combo) {
                array_unshift($combo, $first);
                $results[] = $combo;
            }
        }

        return $results;
    }

    /**
     * @return array[]
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function run($folder = 'stats/joker'): array
    {
        $out = Cache::get('joker_draws');
        if(is_null($out)) {
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
                    }
                } catch (Exception $e) {
                    Log::error("Error reading file {$file}: " . $e->getMessage());
                }
            }
        }else{
            $finalStatistics = $out;
        }


        if (empty($finalStatistics)) {
            $folderPath = storage_path($folder);
            throw new Exception("No data found in {$folderPath}. Using empty dataset.");
        }

        $jokerIndex = 5;

        $joker = array_fill(1, 20, 0);
        $number = array_fill(1, 45, 0);
//        $totalEven = array_fill(0, 6, 0);
//        $jokerEven = array_fill(0, 3, 0);
        $jokerSums = [];

        foreach ($finalStatistics as $draw) {

            for ($i = 0; $i < 5; $i++) {
                if (isset($draw[$i])) {
                    $number[$draw[$i]]++;
                }
            }

            if (isset($draw[$jokerIndex])) {
                $joker[$draw[$jokerIndex]]++;
            }

//            $tmpJokerDraw = array_slice($draw, 5, 2);
//            if (count($tmpJokerDraw) === 2) {
//                $jokerEvens = count(array_filter($tmpJokerDraw, fn($n) => $n % 2 === 0));
//                $jokerEven[$jokerEvens]++;
//
//                $drawJokerSum = array_sum($tmpJokerDraw);
//                $jokerSums[$drawJokerSum] = ($jokerSums[$drawJokerSum] ?? 0) + 1;
//            }
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

//        arsort($jokerSums);
//        $allowedJokerSums = array_slice(array_keys($jokerSums), 0, (int)(count($jokerSums) / 2));
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
                while (count($currentJokers) < 1) {
                    $val = $jokerStats[array_rand($jokerStats)];
                    if (!in_array($val, $currentJokers)) {
                        $currentJokers[] = $val;
                    }
                }
                sort($currentJokers);

                $draw['joker'] = $currentJokers;
            }

            $draws[] = $draw;
        }

        $statisticsNumbers = array_fill(1, 45, 0);
        $statisticsJoker = array_fill(1, 20, 0);

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
        $finalJokers = array_slice($topJokers, 0, 1);
        sort($finalJokers);


        return [
            'numbers' => $finalNumbers,
            'jokers' => $finalJokers,
        ];
    }

    public function getLatestDrawDate(string $folder = 'stats/joker'): array
    {
        $out = Cache::get('joker_latest_draw_date');
        if($out) {
            return $out;
        }

        $files = File::load_xlsx_files($folder);
        $lastDraw = [];

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

                    if(count($lastDraw) ==0) {
                        $lastDraw = $row;
                    }
                    $previous_date = Carbon::createFromFormat('d/m/Y', $lastDraw[1]);
                    $current_date = Carbon::createFromFormat('d/m/Y', $row[1]);
                    if($previous_date->lt($current_date)) {
                        $lastDraw = $row;
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        if(count($lastDraw) ==0) {
            return [];
        }

        return [
            'id' => $lastDraw[0],
            'date' => $lastDraw[1],
            'numbers' => [$lastDraw[2], $lastDraw[3], $lastDraw[4], $lastDraw[5], $lastDraw[6]],
            'joker' => [$lastDraw[7]],
        ];
    }
}
