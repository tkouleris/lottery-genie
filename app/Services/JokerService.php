<?php

namespace App\Services;

use App\Helpers\File;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JokerService
{
    /**
     * @return array[]
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function run($folder = 'stats/joker'): array
    {
        $files = File::load_xlsx_files($folder);
        $finalStatistics = [];

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

                    // Filter out empty rows and ensure we have enough columns
                    $data = array_filter($row, fn($cell) => $cell !== null);
                    if (count($data) >= 6) {
                        $finalStatistics[] = array_map('intval', array_values($data));
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
}
