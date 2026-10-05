<?php

namespace App\Services;

use App\Helpers\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
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
        $finalStatistics = Cache::get('eurojackpot_draws');
        if(is_null($finalStatistics)){
            $output = $this->load_files();
            $finalStatistics = $output['stats'];
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
        $allDraws = Cache::get('eurojackpot_stats');
        $delays = Cache::get('eurojackpot_delays');
        if (is_null($allDraws)) {
            $output = $this->load_files();
            $allDraws = $output['stats'];
            $delays = $output['delays'];
        }

        if (empty($allDraws)) {
            throw new Exception("No data found for statistics.");
        }

        // The draws are loaded in chronological order (oldest to newest)
        // We reverse them so that the first element is the most recent draw (delay 0)
        $allDraws = array_reverse($allDraws);

        $numberFrequency = array_fill(1, 50, 0);
        $jokerFrequency = array_fill(1, 12, 0);
        $numberDelay = array_fill(1, 50, 0);
        $jokerDelay = array_fill(1, 12, 0);
        $numbersFound = array_fill(1, 50, false);
        $jokersFound = array_fill(1, 12, false);

        $jokerPairsFrequency = [];
        $even_odd_freq = [];
        foreach ($delays as $drawIndex => $draw) {

            $numbers = $draw['numbers'];
            for ($i = 0; $i < 5; $i++) {
                if (isset($numbers[$i]) && $numbers[$i] >= 1 && $numbers[$i] <= 50) {
                    $num = $numbers[$i];

                    if (!$numbersFound[$num]) {
                        $numberDelay[$num] = $drawIndex;
                        $numbersFound[$num] = true;
                    }
                }
            }
            $jokers = $draw['jokers'];
            for ($i = 0; $i <= 1; $i++) {
                if (isset($jokers[$i]) && $jokers[$i] >= 1 && $jokers[$i] <= 12) {
                    $jokerNum = $jokers[$i];
                    if (!$jokersFound[$jokerNum]) {
                        $jokerDelay[$jokerNum] = $drawIndex;
                        $jokersFound[$jokerNum] = true;
                    }
                }
            }
        }

        foreach ($allDraws as $drawIndex => $draw) {
            $evenCount = 0;
            $oddCount = 0;
            // Main numbers are at indices 0-4
            for ($i = 0; $i < 5; $i++) {
                if (isset($draw[$i]) && $draw[$i] >= 1 && $draw[$i] <= 50) {
                    $num = $draw[$i];
                    $numberFrequency[$num]++;
                    if ($num % 2 === 0) {
                        $evenCount++;
                    } else {
                        $oddCount++;
                    }

                }
            }
            $evenOddKey = "{$evenCount} even / {$oddCount} odd";
            $even_odd_freq[$evenOddKey] = ($even_odd_freq[$evenOddKey] ?? 0) + 1;

            $jokers = [];
            // Eurozahlen are at indices 5-6
            for ($i = 5; $i <= 6; $i++) {
                if (isset($draw[$i]) && $draw[$i] >= 1 && $draw[$i] <= 12) {
                    $jokerNum = $draw[$i];
                    $jokerFrequency[$jokerNum]++;
                    $jokers[] = $jokerNum;

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

        // For numbers not found in history, delay is equal to total draws
        foreach ($numbersFound as $num => $found) {
            if (!$found) {
                $numberDelay[$num] = count($allDraws);
            }
        }
        foreach ($jokersFound as $num => $found) {
            if (!$found) {
                $jokerDelay[$num] = count($allDraws);
            }
        }

        arsort($numberFrequency);
        arsort($jokerFrequency);
        arsort($jokerPairsFrequency);
        arsort($even_odd_freq);

        return [
            'number_frequency' => $numberFrequency,
            'joker_frequency' => $jokerFrequency,
            'number_delay' => $numberDelay,
            'joker_delay' => $jokerDelay,
            'common_joker_combinations' => array_slice($jokerPairsFrequency, 0, 10, true),
            'even_odd_stats' => $even_odd_freq,
            'sum_distribution' => $this->getSumDistribution($delays),
            'total_draws_analyzed' => count($allDraws),
            'latest_draw_date' => File::get_latest_file_date($folder),
        ];
    }

    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     */
    public function getLatestDrawDate(string $folder = 'stats/euro'): array
    {
        $out = Cache::get('eurojackpot_latest_draw_date');
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
            'numbers' => [$lastDraw[2], $lastDraw[3], $lastDraw[4], $lastDraw[5], $lastDraw[6]],
            'joker' => [$lastDraw[7],$lastDraw[8]],
            'next_draw_date' => $this->getNextDrawDate(),
        ];
    }

    /**
     * loading eurojackpot draws data from xlsx files
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     */
    public function load_files(string $folder = 'stats/euro'): array
    {
        $files = File::load_xlsx_files($folder);
        $finalStatistics = [];
        $lastDraw = [];
        $history = [];
        $delays = [];

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
                    // Eurozahlen are at indices 7, 8
                    $drawData = [];
                    for ($i = 2; $i <= 8; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int)$row[$i];
                        }
                    }

                    if (count($drawData) === 7) {
                        $finalStatistics[] = $drawData;
                    }

                    if(count($lastDraw) ==0) {
                        $lastDraw = $row;
                    }
                    $previous_date = Carbon::createFromFormat('d/m/Y', $lastDraw[1]);
                    $current_date = Carbon::createFromFormat('d/m/Y', $row[1]);
                    if($previous_date->lt($current_date)) {
                        $lastDraw = $row;
                    }

                    $drawNumbers = [];
                    for ($i = 2; $i <= 6; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawNumbers[] = (int)$row[$i];
                        }
                    }
                    $drawJokers = [];
                    for ($i = 7; $i <= 8; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawJokers[] = (int)$row[$i];
                        }
                    }

                    if (count($drawNumbers) === 5 && count($drawJokers) === 2) {
                        $history[] = [
                            'date' => $row[1],
                            'numbers' => $drawNumbers,
                            'jokers' => $drawJokers
                        ];
                        $delays[] = [
                            'date' => Carbon::createFromFormat('d/m/Y', $row[1]),
                            'numbers' => $drawNumbers,
                            'jokers' => $drawJokers
                        ];
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        $delays = collect($delays)
            ->sortByDesc(fn ($delay) => Carbon::parse($delay['date']))
            ->values();
        return ['stats' => $finalStatistics, 'lastDraw' => $lastDraw, 'history' => $history, 'delays' => $delays];
    }

    /**
     * @param array $userNumbers 5 main numbers
     * @param array $userJokers 2 Eurozahlen
     * @return array
     */
    public function checkCombination(array $userNumbers, array $userJokers): array
    {
        $history = Cache::get('eurojackpot_history');
        if(is_null($history)) {
            $output = $this->load_files();
            $history = $output['history'];
        }

        sort($userNumbers);
        sort($userJokers);

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
            $matchingJokers = array_intersect($userJokers, $draw['jokers']);

            $numCount = count($matchingNumbers);
            $jokerCount = count($matchingJokers);

            if ($numCount === 5 && $jokerCount === 2) {
                $results['exact_matches']++;
            }

            if ($numCount + $jokerCount >= 3 || ($numCount == 5) || ($numCount == 4 && $jokerCount >= 1)) {
                $tier = "{$numCount}+{$jokerCount}";
                $results['breakdown'][$tier] = ($results['breakdown'][$tier] ?? 0) + 1;

                $results['match_history'][] = [
                    'date' => $draw['date'],
                    'numbers' => $draw['numbers'],
                    'jokers' => $draw['jokers'],
                    'matching_numbers' => $matchingNumbers,
                    'matching_jokers' => $matchingJokers,
                    'tier' => $tier
                ];
            }
        }

        return $results;
    }

    /**
     * Sum distribution of the 5 main numbers across all draws.
     * Possible sums range from 15 (1+2+3+4+5) to 240 (46+47+48+49+50), theoretical mean 127.5.
     * @param iterable $draws draws with 'date' (Carbon) and 'numbers' keys
     * @return array
     */
    private function getSumDistribution(iterable $draws): array
    {
        $optimalRange = ['min' => 100, 'max' => 155];
        $moderateRange = ['min' => 80, 'max' => 175];

        $buckets = [
            ['label' => '< 80', 'name' => 'Very Low', 'min' => 15, 'max' => 79],
            ['label' => '80 – 99', 'name' => 'Low', 'min' => 80, 'max' => 99],
            ['label' => '100 – 119', 'name' => 'Mid-Low', 'min' => 100, 'max' => 119],
            ['label' => '120 – 139', 'name' => 'Core Average', 'min' => 120, 'max' => 139],
            ['label' => '140 – 159', 'name' => 'Mid-High', 'min' => 140, 'max' => 159],
            ['label' => '160 – 179', 'name' => 'High', 'min' => 160, 'max' => 179],
            ['label' => '>= 180', 'name' => 'Very High', 'min' => 180, 'max' => 240],
        ];
        foreach ($buckets as &$bucket) {
            $bucket['count'] = 0;
        }
        unset($bucket);

        $total = 0;
        $sumTotal = 0;
        $inOptimal = 0;
        $lowest = null;
        $highest = null;

        foreach ($draws as $draw) {
            if (count($draw['numbers']) !== 5) {
                continue;
            }
            $sum = array_sum($draw['numbers']);
            $total++;
            $sumTotal += $sum;

            foreach ($buckets as &$bucket) {
                if ($sum >= $bucket['min'] && $sum <= $bucket['max']) {
                    $bucket['count']++;
                    break;
                }
            }
            unset($bucket);

            if ($sum >= $optimalRange['min'] && $sum <= $optimalRange['max']) {
                $inOptimal++;
            }

            $numbers = $draw['numbers'];
            sort($numbers);
            $entry = [
                'sum' => $sum,
                'date' => Carbon::parse($draw['date'])->format('d/m/Y'),
                'numbers' => $numbers,
            ];
            if (is_null($lowest) || $sum < $lowest['sum']) {
                $lowest = $entry;
            }
            if (is_null($highest) || $sum > $highest['sum']) {
                $highest = $entry;
            }
        }

        $maxCount = 0;
        $mostFrequentIndex = null;
        foreach ($buckets as $index => &$bucket) {
            $bucket['percentage'] = $total > 0 ? round($bucket['count'] / $total * 100, 1) : 0;
            if ($bucket['count'] > $maxCount) {
                $maxCount = $bucket['count'];
                $mostFrequentIndex = $index;
            }
        }
        unset($bucket);

        return [
            'buckets' => $buckets,
            'most_frequent_index' => $mostFrequentIndex,
            'max_count' => $maxCount,
            'average' => $total > 0 ? round($sumTotal / $total, 2) : 0,
            'theoretical_mean' => 127.5,
            'lowest' => $lowest,
            'highest' => $highest,
            'optimal_range' => $optimalRange,
            'moderate_range' => $moderateRange,
            'optimal_percentage' => $total > 0 ? round($inOptimal / $total * 100, 1) : 0,
            'total' => $total,
        ];
    }

    private function getNextDrawDate()
    {
        $now = Carbon::now()->subDays(1);
        return collect([
            Carbon::FRIDAY,
            Carbon::TUESDAY,
        ])->map(fn ($day) => $now->copy()->next($day))
            ->sort()
            ->first()
            ->format('d/m/Y');
    }


}
