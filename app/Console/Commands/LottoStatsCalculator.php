<?php

namespace App\Console\Commands;

use App\Services\LottoService;
use Exception;
use Illuminate\Console\Command;

class LottoStatsCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lotto-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Lotto statistics from XLSX files';

    /**
     * Execute the console command.
     */
    public function handle(LottoService $service): int
    {
        $this->info('Υπολογισμός στατιστικών Λόττο...');

        try {
            $stats = $service->getStats();

            $this->info("Αναλύθηκαν {$stats['total_draws_analyzed']} κληρώσεις.");

            $this->newline();
            $this->info('10 Πιο Συχνά Νούμερα');
            $this->table(['Νούμερο', 'Συχνότητα'], $this->formatForTable($stats['top_numbers']));

            $this->newline();
            $this->info('10 Πιο Συχνές Διαφορές (Max - Min) - Ομαδοποιημένες σε Κλάσεις');
            $this->table(['Κλάση Διαφοράς', 'Συχνότητα'], $this->formatForTable($stats['top_differences']));

            $this->newline();
            $this->info('10 Πιο Συχνές 3άδες');
            $this->table(['3άδα', 'Συχνότητα'], $this->formatForTable($stats['top_triples']));

            $this->newline();
            $this->info('Συχνότητα Συνδυασμών Even / Odd');
            $this->table(['Συνδυασμός', 'Συχνότητα'], $this->formatForTable($stats['even_odd_stats']));

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
