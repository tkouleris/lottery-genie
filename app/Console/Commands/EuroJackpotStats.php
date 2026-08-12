<?php

namespace App\Console\Commands;

use App\Services\EurojackpotService;
use App\Services\JokerService;
use Illuminate\Console\Command;

class EuroJackpotStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:euro';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate EuroJackpot statistics and predictions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
//        https://media.opap.gr/Excel/5104/Joker_2019.xls?utm_source=chatgpt.com
//        $obj = resolve(JokerService::class);
//        $output = $obj->run();
//        dd($output);
        return 0;
    }

    private function median(array $lst): float|int
    {
        sort($lst);
        $n = count($lst);
        if ($n === 0) return 0;
        $mid = (int)($n / 2);

        if ($n % 2 === 1) {
            return $lst[$mid];
        } else {
            return ($lst[$mid - 1] + $lst[$mid]) / 2;
        }
    }
}
