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

class EurojackpotService
{
    /**
     * Eurojackpot draws 5 main numbers of 50 and 2 Eurozahlen of 12
     */
    private const PICK = 5;

    private const MAX_NUMBER = 50;

    private const JOKER_PICK = 2;

    private const MAX_JOKER = 12;

    private const DRAW_DAYS = [Carbon::TUESDAY, Carbon::FRIDAY];

    private const FOLDER = 'stats/euro';

    /**
     * Cache keys filled by the app:cache-eurojackpot command
     */
    private const CACHE_DRAWS = 'eurojackpot_draws';

    private const CACHE_STATS = 'eurojackpot_stats';

    private const CACHE_DELAYS = 'eurojackpot_delays';

    private const CACHE_LATEST_DRAW_DATE = 'eurojackpot_latest_draw_date';

    /**
     * load_files() output per folder, so a request parses the xlsx files at most once
     */
    private array $loadedFiles = [];

    /**
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function get_stats(string $folder = self::FOLDER): array
    {
        $draws = $this->cachedOrLoaded(self::CACHE_STATS, 'stats', $folder);
        $delays = $this->cachedOrLoaded(self::CACHE_DELAYS, 'delays', $folder);

        if (empty($draws)) {
            throw new Exception('No data found in '.storage_path($folder));
        }

        return $this->calculateStatistics($draws, $delays, $folder);
    }

    private function calculateStatistics(array $draws, Collection $delays, string $folder): array
    {
        $numberFrequency = array_fill(1, self::MAX_NUMBER, 0);
        $jokerFrequency = array_fill(1, self::MAX_JOKER, 0);
        $jokerPairsFrequency = [];
        $even_odd_freq = [];

        foreach ($draws as $draw) {
            $numbers = $draw['numbers'];
            $jokers = $draw['jokers'];

            // 1. Main numbers
            foreach ($numbers as $num) {
                if (array_key_exists($num, $numberFrequency)) {
                    $numberFrequency[$num]++;
                }
            }

            // 2. Eurozahlen and their pairs
            foreach ($jokers as $joker) {
                if (array_key_exists($joker, $jokerFrequency)) {
                    $jokerFrequency[$joker]++;
                }
            }
            $pair = implode('-', $jokers);
            $jokerPairsFrequency[$pair] = ($jokerPairsFrequency[$pair] ?? 0) + 1;

            // 3. Even / Odd frequency (for the 5 numbers)
            $evenCount = count(array_filter($numbers, fn ($num) => $num % 2 === 0));
            $oddCount = count($numbers) - $evenCount;
            $evenOddKey = "{$evenCount} even / {$oddCount} odd";
            $even_odd_freq[$evenOddKey] = ($even_odd_freq[$evenOddKey] ?? 0) + 1;
        }

        arsort($numberFrequency);
        arsort($jokerFrequency);
        arsort($jokerPairsFrequency);
        arsort($even_odd_freq);

        return [
            'number_frequency' => $numberFrequency,
            'joker_frequency' => $jokerFrequency,
            'number_delay' => $this->delays($delays, fn ($draw) => $draw['numbers'], self::MAX_NUMBER),
            'joker_delay' => $this->delays($delays, fn ($draw) => $draw['jokers'], self::MAX_JOKER),
            'common_joker_combinations' => array_slice($jokerPairsFrequency, 0, 10, true),
            'even_odd_stats' => $even_odd_freq,
            'sum_distribution' => $this->sumDistribution()->calculate($delays),
            'range_distribution' => [
                'numbers' => $this->numberRangeDistribution()->calculate($delays->pluck('numbers')),
                'jokers' => RangeDistribution::perValue(1, self::MAX_JOKER - 1)->calculate($delays->pluck('jokers')),
            ],
            'total_draws_analyzed' => count($draws),
            'latest_draw_date' => File::get_latest_file_date($folder),
        ];
    }

    /**
     * Number of draws since each number was last drawn
     *
     * @param  Collection  $delays  draws sorted from newest to oldest
     * @param  callable  $numbersOf  returns the numbers of a draw to track
     * @param  int  $maxNumber  highest tracked number
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

        return array_map(fn ($value) => $value ?? $delays->count(), $delay);
    }

    /**
     * Picks the most frequent numbers out of 100 random draws weighted by the draw history
     *
     * @return array[]
     *
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function run(string $folder = self::FOLDER): array
    {
        $draws = $this->cachedOrLoaded(self::CACHE_DRAWS, 'draws', $folder);

        if (empty($draws)) {
            throw new Exception('No data found in '.storage_path($folder));
        }

        // Every drawn number appears once per draw, so a random pick is weighted by its frequency
        $numberPool = [];
        $jokerPool = [];
        $jokerSums = [];
        foreach ($draws as $draw) {
            array_push($numberPool, ...array_slice($draw, 0, self::PICK));
            $jokers = array_slice($draw, self::PICK, self::JOKER_PICK);
            array_push($jokerPool, ...$jokers);
            $jokerSum = array_sum($jokers);
            $jokerSums[$jokerSum] = ($jokerSums[$jokerSum] ?? 0) + 1;
        }

        // Only the more frequent half of the historical Eurozahlen sums is accepted
        arsort($jokerSums);
        $allowedJokerSums = array_slice(array_keys($jokerSums), 0, max(1, intdiv(count($jokerSums), 2)));

        $numberCounts = array_fill(1, self::MAX_NUMBER, 0);
        $jokerCounts = array_fill(1, self::MAX_JOKER, 0);
        for ($i = 0; $i < 100; $i++) {
            shuffle($numberPool);
            foreach ($this->pickDistinct($numberPool, self::PICK) as $num) {
                $numberCounts[$num]++;
            }

            shuffle($jokerPool);
            do {
                $jokers = $this->pickDistinct($jokerPool, self::JOKER_PICK);
            } while (! in_array(array_sum($jokers), $allowedJokerSums));
            foreach ($jokers as $joker) {
                $jokerCounts[$joker]++;
            }
        }

        arsort($numberCounts);
        $finalNumbers = array_slice(array_keys($numberCounts), 0, self::PICK);
        sort($finalNumbers);

        // Two of the four most frequent Eurozahlen
        arsort($jokerCounts);
        $topJokers = array_slice(array_keys($jokerCounts), 0, 4);
        shuffle($topJokers);
        $finalJokers = array_slice($topJokers, 0, self::JOKER_PICK);
        sort($finalJokers);

        return [
            'numbers' => $finalNumbers,
            'jokers' => $finalJokers,
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

    /**
     * @throws FileNotFoundException
     */
    public function getLatestDrawDate(string $folder = self::FOLDER): array
    {
        $out = Cache::get(self::CACHE_LATEST_DRAW_DATE);
        if ($out) {
            return $out;
        }
        $loaded = $this->loadFilesOnce($folder);
        $lastDraw = $loaded['lastDraw'];

        if (empty($lastDraw)) {
            return [];
        }

        return [
            'id' => $lastDraw[0],
            'date' => $lastDraw[1],
            'numbers' => [$lastDraw[2], $lastDraw[3], $lastDraw[4], $lastDraw[5], $lastDraw[6]],
            'joker' => [$lastDraw[7], $lastDraw[8]],
            'next_draw_date' => $this->getNextDrawDate(),
            'prizes' => $loaded['lastDrawPrizes'],
        ];
    }

    /**
     * loading eurojackpot draws data from xlsx files
     *
     * @throws FileNotFoundException
     */
    public function load_files(string $folder = self::FOLDER): array
    {
        $files = File::load_xlsx_files($folder);
        $finalStatistics = [];
        $history = [];
        $lastDraw = [];
        $lastDrawPrizes = [];
        $lastDrawDate = null;
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
                    // Eurozahlen are at indices 7, 8
                    $drawData = [];
                    for ($i = 2; $i <= 8; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int) $row[$i];
                        }
                    }

                    // Skip incomplete rows, e.g. empty rows at the end of a sheet
                    if (count($drawData) < self::PICK + self::JOKER_PICK) {
                        continue;
                    }

                    $date = $this->parseDrawDate((string) $row[1]);
                    if (is_null($date)) {
                        Log::warning("Skipping Eurojackpot draw {$row[0]} in {$file}: invalid date '{$row[1]}'");

                        continue;
                    }
                    $row[1] = $date->format('d/m/Y');

                    $finalStatistics[] = $drawData;

                    $numbers = array_slice($drawData, 0, self::PICK);
                    sort($numbers);
                    $jokers = array_slice($drawData, self::PICK, self::JOKER_PICK);
                    sort($jokers);

                    $history[] = [
                        'date' => $date,
                        'label' => $row[1],
                        'numbers' => $numbers,
                        'jokers' => $jokers,
                    ];

                    if (is_null($lastDrawDate) || $lastDrawDate->lt($date)) {
                        $lastDraw = $row;
                        $lastDrawPrizes = PrizeTiers::parse(array_slice($rows, 0, 3), $row);
                        $lastDrawDate = $date;
                    }
                }
            } catch (Exception $e) {
                Log::error("Error reading file {$file}: ".$e->getMessage());
            }
        }

        // Each sheet lists its draws from newest to oldest, but the files are read from oldest to newest
        $history = collect($history)
            ->sortByDesc(fn ($draw) => $draw['date'])
            ->values();

        $stats = $history
            ->map(fn ($draw) => ['date' => $draw['label'], 'numbers' => $draw['numbers'], 'jokers' => $draw['jokers']])
            ->all();

        $delays = $history
            ->map(fn ($draw) => ['date' => $draw['date'], 'numbers' => $draw['numbers'], 'jokers' => $draw['jokers']]);

        return $this->loadedFiles[$folder] = ['draws' => $finalStatistics, 'stats' => $stats, 'lastDraw' => $lastDraw, 'lastDrawPrizes' => $lastDrawPrizes, 'delays' => $delays];
    }

    /**
     * Parses a d/m/Y draw date, fixing the dates some sheets write as m/d/Y (e.g. 10/25/2024)
     *
     * A draw takes place on a Tuesday or Friday, so the reading that falls on a draw day wins.
     * When neither does, the d/m/Y reading is kept.
     */
    private function parseDrawDate(string $value): ?Carbon
    {
        [$first, $second, $year] = array_map('intval', explode('/', $value)) + [0, 0, 0];

        $candidates = [];
        foreach ([[$first, $second], [$second, $first]] as [$day, $month]) {
            if (checkdate($month, $day, $year)) {
                $candidates[] = Carbon::create($year, $month, $day);
            }
        }

        foreach ($candidates as $date) {
            if (in_array($date->dayOfWeek, self::DRAW_DAYS)) {
                if ($date->day !== $first) {
                    Log::warning("Eurojackpot date '{$value}' read as month/day: {$date->format('d/m/Y')}");
                }

                return $date;
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * @param  string  $cacheKey  cache key filled by the app:cache-eurojackpot command
     * @param  string  $outputKey  matching load_files() output key, used on a cache miss
     *
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
     * @param  array  $userNumbers  5 main numbers
     * @param  array  $userJokers  2 Eurozahlen
     *
     * @throws FileNotFoundException
     */
    public function checkCombination(array $userNumbers, array $userJokers, string $folder = self::FOLDER): array
    {
        $history = $this->cachedOrLoaded(self::CACHE_STATS, 'stats', $folder);

        sort($userNumbers);
        sort($userJokers);

        $results = [
            'exact_matches' => 0,
            'breakdown' => [],
            'match_history' => [],
            'total_draws' => count($history),
            'date_range' => [
                'start' => ! empty($history) ? end($history)['date'] : null,
                'end' => ! empty($history) ? $history[0]['date'] : null,
            ],
        ];

        foreach ($history as $draw) {
            $matchingNumbers = array_intersect($userNumbers, $draw['numbers']);
            $matchingJokers = array_intersect($userJokers, $draw['jokers']);

            $numCount = count($matchingNumbers);
            $jokerCount = count($matchingJokers);

            if ($numCount === self::PICK && $jokerCount === self::JOKER_PICK) {
                $results['exact_matches']++;
            }

            // Eurojackpot tiers: 5+2 down to 3+0, 2+2, 2+1 and 1+2, i.e. at least 3 matches
            if ($numCount + $jokerCount >= 3) {
                $tier = "{$numCount}+{$jokerCount}";
                $results['breakdown'][$tier] = ($results['breakdown'][$tier] ?? 0) + 1;

                $results['match_history'][] = [
                    'date' => $draw['date'],
                    'numbers' => $draw['numbers'],
                    'jokers' => $draw['jokers'],
                    'matching_numbers' => $matchingNumbers,
                    'matching_jokers' => $matchingJokers,
                    'tier' => $tier,
                ];
            }
        }

        return $results;
    }

    /**
     * @throws FileNotFoundException
     */
    public function getSumDistribution(string $folder = self::FOLDER): array
    {
        return $this->sumDistribution()->calculate($this->cachedOrLoaded(self::CACHE_DELAYS, 'delays', $folder));
    }

    /**
     * 5 of 50: possible sums 15 – 240, theoretical mean 127.5
     */
    private function sumDistribution(): SumDistribution
    {
        return new SumDistribution(
            pick: self::PICK,
            maxNumber: self::MAX_NUMBER,
            bucketEdges: [80, 100, 120, 140, 160, 180],
            optimalRange: ['min' => 100, 'max' => 155],
            moderateRange: ['min' => 80, 'max' => 175],
        );
    }

    /**
     * 5 of 50: possible spread 4 – 49, theoretical mean 34
     */
    private function numberRangeDistribution(): RangeDistribution
    {
        return new RangeDistribution([
            '4-19' => [4, 19],
            '20-24' => [20, 24],
            '25-29' => [25, 29],
            '30-34' => [30, 34],
            '35-39' => [35, 39],
            '40-44' => [40, 44],
            '45-49' => [45, 49],
        ]);
    }

    private function getNextDrawDate(): string
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
