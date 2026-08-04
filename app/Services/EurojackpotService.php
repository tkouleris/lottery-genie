<?php

namespace App\Services;

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
        $folderPath = storage_path($folder);
        if (!is_dir($folderPath)) {
            throw new FileNotFoundException("Directory not found: {$folderPath}");
        }

        // Use PhpSpreadsheet to read .xlsx files
        $files = glob($folderPath . '/*.xlsx');
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
                    if (count($data) >= 7) {
                        $finalStatistics[] = array_map('intval', array_values($data));
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }

        if (empty($finalStatistics)) {
            throw new Exception("No data found in {$folderPath}. Using empty dataset.");
        }

        $jokerIndex1 = 5;
        $jokerIndex2 = 6;

        $joker = array_fill(1, 12, 0);
        $number = array_fill(1, 50, 0);
        $totalEven = array_fill(0, 6, 0);
        $jokerEven = array_fill(0, 3, 0);
        $jokerSums = [];
        $diffStats = [];
        $avgStats = [];

        foreach ($finalStatistics as $draw) {

            for ($i = 0; $i < 5; $i++) {
                if (isset($draw[$i])) {
                    $number[$draw[$i]]++;
                }
            }

            $tmp = array_slice($draw, 0, 5);
            sort($tmp);
            if (count($tmp) === 5) {
                $diff = $tmp[4] - $tmp[0];
                $diffStats[$diff] = ($diffStats[$diff] ?? 0) + 1;

                $evens = count(array_filter($tmp, fn($n) => $n % 2 === 0));
                $totalEven[$evens]++;

                $drawSum = array_sum($tmp);
                $avgIndex = (int)(floor(($drawSum / 5) / 10) * 10);
                $avgStats[$avgIndex] = ($avgStats[$avgIndex] ?? 0) + 1;

            }

            if (isset($draw[$jokerIndex1])) {
                $joker[$draw[$jokerIndex1]]++;
            }
            if (isset($draw[$jokerIndex2])) {
                $joker[$draw[$jokerIndex2]]++;
            }

            $tmpJokerDraw = array_slice($draw, 5, 2);
            if (count($tmpJokerDraw) === 2) {
                $jokerEvens = count(array_filter($tmpJokerDraw, fn($n) => $n % 2 === 0));
                $jokerEven[$jokerEvens]++;

                $drawJokerSum = array_sum($tmpJokerDraw);
                $jokerSums[$drawJokerSum] = ($jokerSums[$drawJokerSum] ?? 0) + 1;
            }
        }

        arsort($totalEven);
        $maxEvenValues = array_slice(array_keys($totalEven), 0, 2);

        arsort($avgStats);
        $maxAvgValues = array_slice(array_keys($avgStats), 0, 1);

        arsort($diffStats);
        $maxDiffValues = array_slice(array_keys($diffStats), 0, (int)(count($diffStats) / 2));

        $avgPool = [];
        foreach ($avgStats as $key => $value) {
            $avgPool = array_merge($avgPool, array_fill(0, $value, $key));
        }
        shuffle($avgPool);

        $diffPool = [];
        foreach ($diffStats as $key => $value) {
            $diffPool = array_merge($diffPool, array_fill(0, $value, $key));
        }
        shuffle($diffPool);

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

        $totalEvenStats = [];
        foreach ($totalEven as $key => $value) {
            $totalEvenStats = array_merge($totalEvenStats, array_fill(0, $value, $key));
        }
        shuffle($totalEvenStats);

        $jokerEvenStats = [];
        foreach ($jokerEven as $key => $value) {
            $jokerEvenStats = array_merge($jokerEvenStats, array_fill(0, $value, $key));
        }
        shuffle($jokerEvenStats);

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

                $evens = count(array_filter($currentNumbers, fn($n) => $n % 2 === 0));
                $evenNumbersPick = $totalEvenStats[array_rand($totalEvenStats)];
                if ($evens !== $evenNumbersPick) {
                    continue;
                }

                $diff = $currentNumbers[4] - $currentNumbers[0];
                $diffPick = $diffPool[array_rand($diffPool)];
                if ($diff !== $diffPick) {
                    continue;
                }

                $avg = (int)(floor((array_sum($currentNumbers) / 5) / 10) * 10);
                $avgPick = $avgPool[array_rand($avgPool)];
                if ($avg !== $avgPick) {
                    continue;
                }



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

                $jokerEvenNumberPick = $jokerEvenStats[array_rand($jokerEvenStats)];
                $jokerEvens = count(array_filter($currentJokers, fn($n) => $n % 2 === 0));

                if ($jokerEvens !== $jokerEvenNumberPick) {
                    // Python code had a bug here: draw['numbers'] = [] instead of draw['joker'] = []
                    // I will fix it to be consistent with the while loop condition
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
        $finalNumbers1 = array_slice($topNumbers, 0, 5);
        sort($finalNumbers1);
        $finalNumbers2 = array_slice($topNumbers, 5, 5);
        sort($finalNumbers2);

        arsort($statisticsJoker);
        $topJokers = array_slice(array_keys($statisticsJoker), 0, 4);
        $finalJokers1 = array_slice($topJokers, 0, 2);
        sort($finalJokers1);
        $finalJokers2 = array_slice($topJokers, 2, 2);
        sort($finalJokers2);

        $diff1 = $finalNumbers1[4] - $finalNumbers1[0];
        $validDiff1 = in_array($diff1, $maxDiffValues);

        $diff2 = $finalNumbers2[4] - $finalNumbers2[0];
        $validDiff2 = in_array($diff2, $maxDiffValues);

        $evens1 = count(array_filter($finalNumbers1, fn($n) => $n % 2 === 0));
        $validEven1 = in_array($evens1, $maxEvenValues);

        $evens2 = count(array_filter($finalNumbers2, fn($n) => $n % 2 === 0));
        $validEven2 = in_array($evens2, $maxEvenValues);

        $avg1 = (int)(floor((array_sum($finalNumbers1) / 5) / 10) * 10);
        $validAvg1 = in_array($avg1, $maxAvgValues);

        $avg2 = (int)(floor((array_sum($finalNumbers2) / 5) / 10) * 10);
        $validAvg2 = in_array($avg2, $maxAvgValues);


        return [
            [
                'numbers' => $finalNumbers1,
                'jokers' => $finalJokers1,
            ],
            [
                'numbers' => $finalNumbers2,
                'jokers' => $finalJokers2,
            ]
        ];
    }

    private function median(array $lst): float|int
    {
        sort($lst);
        $n = count($lst);
        if ($n === 0) return 0;
        $mid = (int)($n / 2);

        if ($n % 2 === 1) {
            return $lst[$mid];
        } else {
            return ($lst[$mid - 1] + $lst[$mid]) / 2;
        }
    }
}
