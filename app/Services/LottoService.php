<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LottoService
{
    /**
     * @return array[]
     * @throws FileNotFoundException
     */
    public function run($folder = 'stats/lotto'): array
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
                    if (count($data) >= 6) {
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
