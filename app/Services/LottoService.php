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

class LottoService
{
    /**
     * Lotto draws 6 numbers of 49
     */
    private const PICK = 6;
    private const MAX_NUMBER = 49;

    private const FOLDER = 'stats/lotto';

    /**
     * Cache keys filled by the app:cache-lotto command
     */
    private const CACHE_DRAWS = 'lotto_draws';
    private const CACHE_STATS = 'lotto_stats';
    private const CACHE_DELAYS = 'lotto_delays';
    private const CACHE_LATEST_DRAW_DATE = 'lotto_latest_draw_date';

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
        $numbers_freq = [];
        $triples_freq = [];
        $even_odd_freq = [];

        foreach ($draws as $draw) {
            $numbers = $draw['numbers']; // Already sorted

            // 1. Number frequency
            foreach ($numbers as $num) {
                $numbers_freq[$num] = ($numbers_freq[$num] ?? 0) + 1;
            }

            // 2. Most frequent triples
            foreach ($this->getCombinations($numbers, 3) as $triple) {
                $key = implode(',', $triple);
                $triples_freq[$key] = ($triples_freq[$key] ?? 0) + 1;
            }

            // 3. Even / Odd frequency
            $evenCount = count(array_filter($numbers, fn($num) => $num % 2 === 0));
            $oddCount = count($numbers) - $evenCount;
            $evenOddKey = "{$evenCount} even / {$oddCount} odd";
            $even_odd_freq[$evenOddKey] = ($even_odd_freq[$evenOddKey] ?? 0) + 1;
        }

        arsort($numbers_freq);
        arsort($triples_freq);
        arsort($even_odd_freq);

        return [
            'top_numbers' => $numbers_freq,
            'top_triples' => array_slice($triples_freq, 0, 10, true),
            'even_odd_stats' => $even_odd_freq,
            'sum_distribution' => $this->sumDistribution()->calculate($delays),
            'range_distribution' => $this->numberRangeDistribution()->calculate($delays->pluck('numbers')),
            'total_draws_analyzed' => count($draws),
            'number_delay' => $this->delays($delays),
            'latest_draw_date' => File::get_latest_file_date($folder),
        ];
    }

    /**
     * Number of draws since each number was last drawn
     *
     * @param Collection $delays draws sorted from newest to oldest
     * @return array<int, int> number => delay, the total draws for numbers never drawn
     */
    private function delays(Collection $delays): array
    {
        $delay = array_fill(1, self::MAX_NUMBER, null);

        foreach ($delays as $drawIndex => $draw) {
            foreach ($draw['numbers'] as $num) {
                if (array_key_exists($num, $delay)) {
                    $delay[$num] ??= $drawIndex;
                }
            }
        }

        return array_map(fn($value) => $value ?? $delays->count(), $delay);
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
     * Picks the most frequent numbers out of 100 random draws weighted by the draw history
     *
     * @return array[]
     * @throws FileNotFoundException|Exception
     */
    public function run(string $folder = self::FOLDER): array
    {
        $draws = $this->cachedOrLoaded(self::CACHE_DRAWS, 'draws', $folder);

        if (empty($draws)) {
            throw new Exception("No data found in " . storage_path($folder));
        }

        // Every drawn number appears once per draw, so a random pick is weighted by its frequency
        $numberPool = [];
        foreach ($draws as $draw) {
            array_push($numberPool, ...array_slice($draw, 0, self::PICK));
        }

        $numberCounts = array_fill(1, self::MAX_NUMBER, 0);
        for ($i = 0; $i < 100; $i++) {
            foreach ($this->pickDistinct($numberPool, self::PICK) as $num) {
                $numberCounts[$num]++;
            }
        }

        arsort($numberCounts);
        $finalNumbers = array_slice(array_keys($numberCounts), 0, self::PICK);
        sort($finalNumbers);

        return [
            'numbers' => $finalNumbers,
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
                    // Skip header rows (first 4 rows)
                    if ($index < 4) {
                        continue;
                    }

                    // The numbers start 2 columns after the date (which is at index 1)
                    // So numbers are at indices 2, 3, 4, 5, 6, 7
                    $drawData = [];
                    for ($i = 2; $i <= 7; $i++) {
                        if (isset($row[$i]) && is_numeric($row[$i])) {
                            $drawData[] = (int) $row[$i];
                        }
                    }

                    // Skip incomplete rows, e.g. empty rows at the end of a sheet
                    if (count($drawData) < self::PICK) {
                        continue;
                    }

                    $finalStatistics[] = $drawData;

                    $numbers = $drawData;
                    sort($numbers);
                    $date = Carbon::createFromFormat('d/m/Y', $row[1]);

                    $stats[] = [
                        'date' => $row[1],
                        'numbers' => $numbers
                    ];

                    $delays[] = [
                        'date' => $date,
                        'numbers' => $numbers
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
     * @param string $cacheKey cache key filled by the app:cache-lotto command
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
    public function checkCombination(array $userNumbers, string $folder = self::FOLDER): array
    {
        $history = $this->cachedOrLoaded(self::CACHE_STATS, 'stats', $folder);

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

            if ($numCount === self::PICK) {
                $results['exact_matches']++;
            }

            // Lotto tiers: 6, 5, 4, 3
            if ($numCount >= 3) {
                $tier = (string) $numCount;
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
     * 6 of 49: possible sums 21 – 279, theoretical mean 150
     */
    private function sumDistribution(): SumDistribution
    {
        return new SumDistribution(
            pick: self::PICK,
            maxNumber: self::MAX_NUMBER,
            bucketEdges: [100, 120, 140, 160, 180, 200],
            optimalRange: ['min' => 120, 'max' => 180],
            moderateRange: ['min' => 100, 'max' => 200],
        );
    }

    /**
     * 6 of 49: possible spread 5 – 48, theoretical mean 35.7
     */
    private function numberRangeDistribution(): RangeDistribution
    {
        return new RangeDistribution([
            '5-19' => [5, 19],
            '20-24' => [20, 24],
            '25-29' => [25, 29],
            '30-34' => [30, 34],
            '35-39' => [35, 39],
            '40-44' => [40, 44],
            '45-48' => [45, 48],
        ]);
    }

    private function getNextDrawDate(): string
    {
        $now = Carbon::now()->subDays(1);
        return collect([
            Carbon::SATURDAY,
            Carbon::WEDNESDAY,
        ])->map(fn($day) => $now->copy()->next($day))
            ->sort()
            ->first()
            ->format('d/m/Y');
    }
}
