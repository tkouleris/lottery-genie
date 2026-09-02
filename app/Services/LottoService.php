<?php

namespace App\Services;

use App\Helpers\File;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LottoService
{
    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function getStats(string $folder = 'stats/lotto'): array
    {
        $files = File::load_xlsx_files($folder);
        $draws = [];

        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Παράλειψη επικεφαλίδας αν υπάρχει
                    if ($index === 0 && isset($row[0]) && !is_numeric($row[0])) {
                        continue;
                    }

                    // Φιλτράρισμα κενών κελιών
                    $data = array_values(array_filter($row, fn($cell) => $cell !== null && $cell !== ''));

                    if (count($data) >= 6) {
                        $numbers = array_map('intval', array_slice($data, 0, 6));
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

        if (empty($draws)) {
            throw new Exception("No data found in " . storage_path($folder));
        }

        return $this->calculateStatistics($draws);
    }

    private function calculateStatistics(array $draws): array
    {
        $numbers_freq = [];
        $differences_freq = [];
        $triples_freq = [];
        $even_odd_freq = [];
        $totalDraws = count($draws);

        foreach ($draws as $draw) {
            $numbers = $draw['numbers']; // Ήδη ταξινομημένα

            // 1. Συχνότητα εμφάνισης αριθμών
            foreach ($numbers as $num) {
                $numbers_freq[$num] = ($numbers_freq[$num] ?? 0) + 1;
            }

            // 2. Διαφορά (max - min) με ομαδοποίηση σε κλάσεις
            $diff = max($numbers) - min($numbers);
            if ($diff < 10) {
                $class = '<10';
            } else {
                $base = floor($diff / 10) * 10;
                $class = '>=' . $base;
            }
            $differences_freq[$class] = ($differences_freq[$class] ?? 0) + 1;

            // 3. Πιο συχνές 3άδες
            $triples = $this->getCombinations($numbers, 3);
            foreach ($triples as $triple) {
                $key = implode(',', $triple);
                $triples_freq[$key] = ($triples_freq[$key] ?? 0) + 1;
            }

            // 4. Συχνότητα Even / Odd
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

        arsort($numbers_freq);
        arsort($differences_freq);
        arsort($triples_freq);
        arsort($even_odd_freq);

        return [
            'top_numbers' => array_slice($numbers_freq, 0, 10, true),
            'top_differences' => array_slice($differences_freq, 0, 10, true),
            'top_triples' => array_slice($triples_freq, 0, 10, true),
            'even_odd_stats' => $even_odd_freq,
            'total_draws_analyzed' => $totalDraws,
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
     */
    public function run($folder = 'stats/lotto'): array
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

        $number = array_fill(1, 49, 0);

        $jokerSums = [];

        foreach ($finalStatistics as $draw) {

            for ($i = 0; $i < 6; $i++) {
                if (isset($draw[$i])) {
                    $number[$draw[$i]]++;
                }
            }


        }

        $stats = [];
        foreach ($number as $key => $value) {
            $stats = array_merge($stats, array_fill(0, $value, $key));
        }
        shuffle($stats);


        $draws = [];
        for ($i = 0; $i < 100; $i++) {
            $draw = [
                'numbers' => [],
            ];

            while (count($draw['numbers']) === 0) {
                $currentNumbers = [];
                while (count($currentNumbers) < 6) {
                    $val = $stats[array_rand($stats)];
                    if (!in_array($val, $currentNumbers)) {
                        $currentNumbers[] = $val;
                    }
                }
                sort($currentNumbers);

                $draw['numbers'] = $currentNumbers;
            }



            $draws[] = $draw;
        }

        $statisticsNumbers = array_fill(1, 49, 0);

        foreach ($draws as $d) {
            foreach ($d['numbers'] as $n) {
                $statisticsNumbers[$n]++;
            }
        }

        arsort($statisticsNumbers);
        $topNumbers = array_slice(array_keys($statisticsNumbers), 0, 10);
        $finalNumbers = array_slice($topNumbers, 0, 6);
        sort($finalNumbers);



        return [
            'numbers' => $finalNumbers,
        ];
    }
}
