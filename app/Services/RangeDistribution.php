<?php

namespace App\Services;

/**
 * Distribution of the max - min spread of the numbers drawn in a lottery game.
 */
final class RangeDistribution
{
    /**
     * @param array<string, array{0: int, 1: int}> $buckets label => [min, max] spread
     */
    public function __construct(
        private readonly array $buckets,
    ) {
    }

    /**
     * One bucket per spread value, e.g. 1 – 11 for two Eurozahlen of 12
     */
    public static function perValue(int $min, int $max): self
    {
        $buckets = [];
        for ($range = $min; $range <= $max; $range++) {
            $buckets[(string)$range] = [$range, $range];
        }

        return new self($buckets);
    }

    /**
     * @param iterable<int[]> $draws the numbers of each draw
     * @return array
     */
    public function calculate(iterable $draws): array
    {
        $frequency = array_fill_keys(array_keys($this->buckets), 0);

        foreach ($draws as $numbers) {
            if (count($numbers) < 2) {
                continue;
            }
            $range = max($numbers) - min($numbers);
            foreach ($this->buckets as $label => [$min, $max]) {
                if ($range >= $min && $range <= $max) {
                    $frequency[$label]++;
                    break;
                }
            }
        }

        $total = array_sum($frequency);
        $maxCount = max($frequency);

        return [
            'buckets' => collect($frequency)->map(fn ($count, $label) => [
                'label' => (string)$label,
                'count' => $count,
                'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ])->values()->all(),
            'max_count' => $maxCount,
            'most_frequent' => (string)array_search($maxCount, $frequency),
            'total' => $total,
        ];
    }
}
