<?php

namespace App\Services;

use App\Helpers\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
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
        $draws = Cache::get('lotto_stats');
        if(is_null($draws)) {
            $output = $this->load_files($folder);
            $draws = $output['stats'];
        }

        if (empty($draws)) {
            throw new Exception("No data found in " . storage_path($folder));
        }

        return $this->calculateStatistics($draws, $folder);
    }

    private function calculateStatistics(array $draws, string $folder): array
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
     * @throws FileNotFoundException|Exception
     */
    public function run($folder = 'stats/lotto'): array
    {
        $finalStatistics = Cache::get('lotto_draws');
        if(is_null($finalStatistics)) {
            $output = $this->load_files($folder);
            $finalStatistics = $output['draws'];
        }

        if (empty($finalStatistics)) {
            $folderPath = storage_path($folder);
            throw new Exception("No data found in {$folderPath}. Using empty dataset.");
        }

        $number = array_fill(1, 49, 0);

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

    public function getLatestDrawDate(string $folder = 'stats/lotto'): array
    {
        $out = Cache::get('lotto_latest_draw_date');
        if($out) {
            return $out;
        }
        $lastDraw = $this->load_files($folder)['lastDraw'];

        if(count($lastDraw) ==0) {
            return [];
        }

        return [
            'id' => $lastDraw[0],
            'date' => $lastDraw[1],
            'numbers' => [$lastDraw[2], $lastDraw[3], $lastDraw[4], $lastDraw[5], $lastDraw[6], $lastDraw[7]],
            'joker' => [],
            'next_draw_date' => $this->getNextDrawDate(),
        ];
    }

    /**
     * loading lotto draws data from xlsx files
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     */
    public function load_files(string $folder = 'stats/lotto'): array
    {
        $files = File::load_xlsx_files($folder);

        $finalStatistics = [];
        $stats = [];
        $lastDraw = [];
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

                    $drawData = [];
                    for ($i = 2; $i <= 7; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }
                    if (count($drawData) >= 6) {
                        $finalStatistics[] = array_map('intval', array_values($drawData));
                    }

                    if (count($drawData) >= 6) {
                        $numbers = array_map('intval', array_slice($drawData, 0, 6));
                        sort($numbers);
                        $stats[] = [
                            'date' => $row[1],
                            'numbers' => $numbers
                        ];
                    }

                    if(count($lastDraw) == 0) {
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
        return ['draws' => $finalStatistics, 'stats' => $stats, 'lastDraw' => $lastDraw];
    }

    public function checkCombination(array $userNumbers): array
    {
        $allData = $this->load_files();
        $history = $allData['stats'];

        sort($userNumbers);

        $results = [
            'exact_matches' => 0,
            'breakdown' => [],
            'match_history' => [],
            'total_draws' => count($history),
            'date_range' => [
                'start' => !empty($history) ? end($history)['date'] : null,
                'end' => !empty($history) ? $history[0]['date'] : null,
            ]
        ];

        foreach ($history as $draw) {
            $matchingNumbers = array_intersect($userNumbers, $draw['numbers']);
            $numCount = count($matchingNumbers);

            if ($numCount === 6) {
                $results['exact_matches']++;
            }

            // Lotto tiers: 6, 5, 4, 3
            if ($numCount >= 3) {
                $tier = (string)$numCount;
                $results['breakdown'][$tier] = ($results['breakdown'][$tier] ?? 0) + 1;

                $results['match_history'][] = [
                    'date' => $draw['date'],
                    'numbers' => $draw['numbers'],
                    'matching_numbers' => $matchingNumbers,
                    'tier' => $tier
                ];
            }
        }

        return $results;
    }

    private function getNextDrawDate()
    {
        $now = Carbon::now();
        return collect([
            Carbon::FRIDAY,
            Carbon::WEDNESDAY,
        ])->map(fn ($day) => $now->copy()->next($day))
            ->sort()
            ->first()
            ->format('d/m/Y');
    }
}
