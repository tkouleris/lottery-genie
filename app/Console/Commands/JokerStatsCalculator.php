<?php

namespace App\Console\Commands;

use App\Services\JokerService;
use Exception;
use Illuminate\Console\Command;

class JokerStatsCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:joker-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Joker statistics from XLSX files';

    /**
     * Execute the console command.
     */
    public function handle(JokerService $service): int
    {
        $this->info('Calculating Joker statistics...');

        try {
            $stats = $service->getStats();

            $this->newline();
            $this->info('10 Πιο Συχνοί Διάμεσοι (Median)');
            $this->table(['Διάμεσος', 'Συχνότητα'], $this->formatForTable($stats['top_medians']));

            $this->newline();
            $this->info('10 Πιο Συχνά Τζόκερ νούμερα');
            $this->table(['Τζόκερ', 'Συχνότητα'], $this->formatForTable($stats['top_jokers']));

            $this->newline();
            $this->info('10 Πιο Συχνοί Συνδυασμοί (3 Απλοί + Τζόκερ)');
            $this->table(['Συνδυασμός', 'Συχνότητα'], $this->formatForTable($stats['top_combinations']));

        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    private function formatForTable(array $data): array
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[] = [$key, $value];
        }
        return $formatted;
    }
}
