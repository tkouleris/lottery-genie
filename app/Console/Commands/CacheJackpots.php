<?php

namespace App\Console\Commands;

use App\Services\JackpotService;
use Illuminate\Console\Command;

class CacheJackpots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-jackpots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the next draw jackpots of Eurojackpot, Joker and Lotto from the OPAP API and cache them';

    /**
     * Execute the console command.
     */
    public function handle(JackpotService $jackpotService)
    {
        foreach ($jackpotService->refresh() as $game => $jackpot) {
            $this->line(sprintf('%s: draw #%d on %s, %s €', $game, $jackpot['draw_id'], $jackpot['draw_date'], number_format($jackpot['amount'], 0, ',', '.')));
        }
    }
}
