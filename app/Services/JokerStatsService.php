<?php

namespace App\Services;

use App\Helpers\File;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JokerStatsService
{
    /**
     * @param string $folder
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function getStats(string $folder = 'stats/joker'): array
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
                        $numbers = array_map('intval', array_slice($data, 0, 5));
                        $joker = intval($data[5]);
                        sort($numbers);
                        $draws[] = [
                            'numbers' => $numbers,
                            'joker' => $joker
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
        $medians = [];
        $jokers = [];
        $combinations = [];

        foreach ($draws as $draw) {
            $numbers = $draw['numbers']; // Ήδη ταξινομημένα
            $joker = $draw['joker'];

            // 1. Διάμεσος (ο 3ος αριθμός στην πεντάδα)
            $median = $numbers[2];
            $medians[$median] = ($medians[$median] ?? 0) + 1;

            // 2. Τζόκερ
            $jokers[$joker] = ($jokers[$joker] ?? 0) + 1;

            // 3. Συνδυασμοί 3 απλών + Τζόκερ
            // Παίρνουμε όλους τους συνδυασμούς 3 από τα 5 νούμερα
            $tripleCombos = $this->getCombinations($numbers, 3);
            foreach ($tripleCombos as $combo) {
                $key = implode(',', $combo) . ' + [' . $joker . ']';
                $combinations[$key] = ($combinations[$key] ?? 0) + 1;
            }
        }

        arsort($medians);
        arsort($jokers);
        arsort($combinations);

        return [
            'top_medians' => array_slice($medians, 0, 10, true),
            'top_jokers' => array_slice($jokers, 0, 10, true),
            'top_combinations' => array_slice($combinations, 0, 10, true),
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
}
