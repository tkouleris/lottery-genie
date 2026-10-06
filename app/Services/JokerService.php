<?php

namespace App\Services;

use App\Helpers\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JokerService
{
    /**
     * Joker draws 5 main numbers of 45 and 1 joker number of 20
     */
    private const PICK = 5;
    private const MAX_NUMBER = 45;
    private const MAX_JOKER = 20;

    private const FOLDER = 'stats/joker';

    /**
     * Cache keys filled by the app:cache-joker command
     */
    private const CACHE_DRAWS = 'joker_draws';
    private const CACHE_STATS = 'joker_stats';
    private const CACHE_DELAYS = 'joker_delays';
    private const CACHE_LATEST_DRAW_DATE = 'joker_latest_draw_date';

    /**
     * load_files() output per folder, so a request parses the xlsx files at most once
     */
    private array $loadedFiles = [];

    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function getStats(string $folder = self::FOLDER): array
    {
        $draws = $this->cachedOrLoaded(self::CACHE_STATS, 'stats', $folder);
        $delays = $this->cachedOrLoaded(self::CACHE_DELAYS, 'delays', $folder);

        if (empty($draws)) {
            throw new Exception("No data found in " . storage_path($folder));
        }

        return $this->calculateStatistics($draws, $delays, $folder);
    }

    private function calculateStatistics(array $draws, Collection $delays, string $folder): array
    {
        $jokers = [];
        $numbers_freq = [];
        $even_odd_freq = [];

        foreach ($draws as $draw) {
            $numbers = $draw['numbers'];
            $joker = $draw['joker'];

            // 1. Joker
            $jokers[$joker] = ($jokers[$joker] ?? 0) + 1;

            // 2. Main numbers
            foreach ($numbers as $num) {
                $numbers_freq[$num] = ($numbers_freq[$num] ?? 0) + 1;
            }

            // 3. Even / Odd frequency (for the 5 numbers)
            $evenCount = count(array_filter($numbers, fn($num) => $num % 2 === 0));
            $oddCount = count($numbers) - $evenCount;
            $evenOddKey = "{$evenCount} even / {$oddCount} odd";
            $even_odd_freq[$evenOddKey] = ($even_odd_freq[$evenOddKey] ?? 0) + 1;
        }

        arsort($jokers);
        arsort($numbers_freq);
        arsort($even_odd_freq);

        return [
            'top_jokers' => $jokers,
            'top_numbers' => $numbers_freq,
            'even_odd_stats' => $even_odd_freq,
            'sum_distribution' => $this->sumDistribution()->calculate($delays),
            'range_distribution' => $this->numberRangeDistribution()->calculate($delays->pluck('numbers')),
            'number_delay' => $this->delays($delays, fn($draw) => $draw['numbers'], self::MAX_NUMBER),
            'joker_delay' => $this->delays($delays, fn($draw) => [$draw['joker']], self::MAX_JOKER),
            'total_draws_analyzed' => count($draws),
            'latest_draw_date' => File::get_latest_file_date($folder),
        ];
    }

    /**
     * Number of draws since each number was last drawn
     *
     * @param Collection $delays draws sorted from newest to oldest
     * @param callable $numbersOf returns the numbers of a draw to track
     * @param int $maxNumber highest tracked number
     * @return array<int, int> number => delay, the total draws for numbers never drawn
     */
    private function delays(Collection $delays, callable $numbersOf, int $maxNumber): array
    {
        $delay = array_fill(1, $maxNumber, null);

        foreach ($delays as $drawIndex => $draw) {
            foreach ($numbersOf($draw) as $num) {
                if (array_key_exists($num, $delay)) {
                    $delay[$num] ??= $drawIndex;
                }
            }
        }

        return array_map(fn($value) => $value ?? $delays->count(), $delay);
    }

    /**
     * Picks the most frequent numbers out of 100 random draws weighted by the draw history
     *
     * @return array[]
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function run(string $folder = self::FOLDER): array
    {
        $draws = $this->cachedOrLoaded(self::CACHE_DRAWS, 'draws', $folder);

        if (empty($draws)) {
            throw new Exception("No data found in " . storage_path($folder));
        }

        // Every drawn number appears once per draw, so a random pick is weighted by its frequency
        $numberPool = [];
        $jokerPool = [];
        foreach ($draws as $draw) {
            array_push($numberPool, ...array_slice($draw, 0, self::PICK));
            if (isset($draw[self::PICK])) {
                $jokerPool[] = $draw[self::PICK];
            }
        }

        $numberCounts = array_fill(1, self::MAX_NUMBER, 0);
        $jokerCounts = array_fill(1, self::MAX_JOKER, 0);
        for ($i = 0; $i < 100; $i++) {
            foreach ($this->pickDistinct($numberPool, self::PICK) as $num) {
                $numberCounts[$num]++;
            }
            $jokerCounts[$jokerPool[array_rand($jokerPool)]]++;
        }

        arsort($numberCounts);
        $finalNumbers = array_slice(array_keys($numberCounts), 0, self::PICK);
        sort($finalNumbers);

        arsort($jokerCounts);

        return [
            'numbers' => $finalNumbers,
            'jokers' => [array_key_first($jokerCounts)],
        ];
    }

    private function pickDistinct(array $pool, int $count): array
    {
        $picked = [];
        while (count($picked) < $count) {
            $picked[$pool[array_rand($pool)]] = true;
        }

        return array_keys($picked);
    }

    public function getLatestDrawDate(string $folder = self::FOLDER): array
    {
        $out = Cache::get(self::CACHE_LATEST_DRAW_DATE);
        if ($out) {
            return $out;
        }
        $lastDraw = $this->loadFilesOnce($folder)['lastDraw'];

        if (empty($lastDraw)) {
            return [];
        }

        return [
            'id' => $lastDraw[0],
            'date' => $lastDraw[1],
            'numbers' => [$lastDraw[2], $lastDraw[3], $lastDraw[4], $lastDraw[5], $lastDraw[6]],
            'joker' => [$lastDraw[7]],
            'next_draw_date' => $this->getNextDrawDate(),
        ];
    }

    /**
     * loading joker draws data from xlsx files
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     */
    public function load_files(string $folder = self::FOLDER): array
    {
        $files = File::load_xlsx_files($folder);
        $finalStatistics = [];
        $stats = [];
        $lastDraw = [];
        $lastDrawDate = null;
        $delays = [];
        foreach ($files as $file) {
            try {
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                foreach ($rows as $index => $row) {
                    // Skip header rows (first 3 rows)
                    if ($index < 3) {
                        continue;
                    }

                    // The numbers start 2 columns after the date (which is at index 1)
                    // So numbers are at indices 2, 3, 4, 5, 6
                    // Joker is at index 7
                    $drawData = [];
                    for ($i = 2; $i <= 7; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int) $row[$i];
                        }
                    }

                    // Skip incomplete rows, e.g. empty rows at the end of a sheet
                    if (count($drawData) < self::PICK + 1) {
                        continue;
                    }

                    $finalStatistics[] = $drawData;

                    $numbers = array_slice($drawData, 0, self::PICK);
                    sort($numbers);
                    $joker = $drawData[self::PICK];
                    $date = Carbon::createFromFormat('d/m/Y', $row[1]);

                    $stats[] = [
                        'date' => $row[1],
                        'numbers' => $numbers,
                        'joker' => $joker
                    ];

                    $delays[] = [
                        'date' => $date,
                        'numbers' => $numbers,
                        'joker' => $joker
                    ];

                    if (is_null($lastDrawDate) || $lastDrawDate->lt($date)) {
                        $lastDraw = $row;
                        $lastDrawDate = $date;
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: " . $e->getMessage());
            }
        }
        $delays = collect($delays)
            ->sortByDesc(fn($delay) => $delay['date'])
            ->values();
        return ['draws' => $finalStatistics, 'stats' => $stats, 'lastDraw' => $lastDraw, 'delays' => $delays];
    }

    /**
     * @param string $cacheKey cache key filled by the app:cache-joker command
     * @param string $outputKey matching load_files() output key, used on a cache miss
     * @throws FileNotFoundException
     */
    private function cachedOrLoaded(string $cacheKey, string $outputKey, string $folder): mixed
    {
        return Cache::get($cacheKey) ?? $this->loadFilesOnce($folder)[$outputKey];
    }

    /**
     * @throws FileNotFoundException
     */
    private function loadFilesOnce(string $folder): array
    {
        return $this->loadedFiles[$folder] ??= $this->load_files($folder);
    }

    /**
     * @throws FileNotFoundException
     */
    public function checkCombination(array $userNumbers, array $userJokers, string $folder = self::FOLDER): array
    {
        $history = $this->cachedOrLoaded(self::CACHE_STATS, 'stats', $folder);

        sort($userNumbers);
        // Joker for Joker game is usually just one number, but we'll handle it as array for consistency
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
            // In Joker, user picks 1 joker. The service usually returns 1 joker.
            $drawJoker = [$draw['joker']];
            $matchingJokers = array_intersect($userJokers, $drawJoker);

            $numCount = count($matchingNumbers);
            $jokerCount = count($matchingJokers);

            if ($numCount === self::PICK && $jokerCount === 1) {
                $results['exact_matches']++;
            }

            // Joker tiers: 5+1, 5, 4+1, 4, 3+1, 3, 2+1, 1+1
            if ($numCount === self::PICK || ($numCount >= 1 && $jokerCount === 1) || $numCount >= 3) {
                $tier = "{$numCount}+{$jokerCount}";
                $results['breakdown'][$tier] = ($results['breakdown'][$tier] ?? 0) + 1;

                $results['match_history'][] = [
                    'date' => $draw['date'],
                    'numbers' => $draw['numbers'],
                    'jokers' => $drawJoker,
                    'matching_numbers' => $matchingNumbers,
                    'matching_jokers' => $matchingJokers,
                    'tier' => $tier
                ];
            }
        }

        return $results;
    }

    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     */
    public function getSumDistribution(string $folder = self::FOLDER): array
    {
        return $this->sumDistribution()->calculate($this->cachedOrLoaded(self::CACHE_DELAYS, 'delays', $folder));
    }

    /**
     * 5 of 45: possible sums 15 – 215, theoretical mean 115
     */
    private function sumDistribution(): SumDistribution
    {
        return new SumDistribution(
            pick: self::PICK,
            maxNumber: self::MAX_NUMBER,
            bucketEdges: [65, 85, 105, 125, 145, 165],
            optimalRange: ['min' => 90, 'max' => 140],
            moderateRange: ['min' => 70, 'max' => 160],
        );
    }

    /**
     * 5 of 45: possible spread 4 – 44, theoretical mean 30.7
     */
    private function numberRangeDistribution(): RangeDistribution
    {
        return new RangeDistribution([
            '4-14' => [4, 14],
            '15-19' => [15, 19],
            '20-24' => [20, 24],
            '25-29' => [25, 29],
            '30-34' => [30, 34],
            '35-39' => [35, 39],
            '40-44' => [40, 44],
        ]);
    }

    private function getNextDrawDate(): string
    {
        $now = Carbon::now()->subDays(1);
        return collect([
            Carbon::SUNDAY,
            Carbon::TUESDAY,
            Carbon::THURSDAY,
        ])->map(fn($day) => $now->copy()->next($day))
            ->sort()
            ->first()
            ->format('d/m/Y');
    }
}
