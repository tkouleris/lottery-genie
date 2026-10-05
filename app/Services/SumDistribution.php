<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Sum distribution of the main numbers of a lottery game (e.g. 5 of 50).
 * Sums of randomly drawn numbers follow a bell curve around the theoretical mean,
 * so the ranges below separate common sums from extreme, rarely drawn ones.
 */
final class SumDistribution
{
    private const BUCKET_NAMES = ['Very Low', 'Low', 'Mid-Low', 'Core Average', 'Mid-High', 'High', 'Very High'];

    /**
     * @param int $pick how many main numbers are drawn
     * @param int $maxNumber highest main number
     * @param int[] $bucketEdges 6 ascending sums where each of the 7 buckets after the first starts
     * @param array{min: int, max: int} $optimalRange
     * @param array{min: int, max: int} $moderateRange
     */
    public function __construct(
        private readonly int $pick,
        private readonly int $maxNumber,
        private readonly array $bucketEdges,
        private readonly array $optimalRange,
        private readonly array $moderateRange,
    ) {
    }

    /**
     * @param iterable $draws draws with 'date' and 'numbers' keys
     * @return array
     */
    public function calculate(iterable $draws): array
    {
        $buckets = $this->buckets();
        $total = 0;
        $sumTotal = 0;
        $inOptimal = 0;
        $lowest = null;
        $highest = null;

        foreach ($draws as $draw) {
            if (count($draw['numbers']) !== $this->pick) {
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

            if ($sum >= $this->optimalRange['min'] && $sum <= $this->optimalRange['max']) {
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
            'pick' => $this->pick,
            'max_number' => $this->maxNumber,
            'min_possible' => $this->minPossibleSum(),
            'max_possible' => $this->maxPossibleSum(),
            'buckets' => $buckets,
            'most_frequent_index' => $mostFrequentIndex,
            'max_count' => $maxCount,
            'average' => $total > 0 ? round($sumTotal / $total, 2) : 0,
            'theoretical_mean' => $this->pick * ($this->maxNumber + 1) / 2,
            'lowest' => $lowest,
            'highest' => $highest,
            'optimal_range' => $this->optimalRange,
            'moderate_range' => $this->moderateRange,
            'optimal_percentage' => $total > 0 ? round($inOptimal / $total * 100, 1) : 0,
            'total' => $total,
        ];
    }

    private function buckets(): array
    {
        $starts = [$this->minPossibleSum(), ...$this->bucketEdges];
        $buckets = [];
        foreach ($starts as $i => $min) {
            $isFirst = $i === 0;
            $isLast = $i === count($starts) - 1;
            $max = $isLast ? $this->maxPossibleSum() : $starts[$i + 1] - 1;
            $buckets[] = [
                'label' => match (true) {
                    $isFirst => '< ' . ($max + 1),
                    $isLast => '>= ' . $min,
                    default => "{$min} – {$max}",
                },
                'name' => self::BUCKET_NAMES[$i],
                'min' => $min,
                'max' => $max,
                'count' => 0,
            ];
        }

        return $buckets;
    }

    private function minPossibleSum(): int
    {
        return $this->pick * ($this->pick + 1) / 2;
    }

    private function maxPossibleSum(): int
    {
        return $this->pick * $this->maxNumber - $this->minPossibleSum() + $this->pick;
    }
}
