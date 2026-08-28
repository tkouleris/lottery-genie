<?php

namespace App\Console\Commands;

use App\Services\EurojackpotService;
use Exception;
use Illuminate\Console\Command;

class EurojackpotStatsCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:eurojackpot-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Eurojackpot statistics: number frequency, joker frequency, and joker combinations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $service = resolve(EurojackpotService::class);
            $results = $service->get_stats();
            dd($results);
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
