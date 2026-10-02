<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CacheAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache Eurojackpot, Joker and Lotto statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commands = [
            'Eurojackpot' => 'app:cache-eurojackpot',
            'Joker' => 'app:cache-joker',
            'Lotto' => 'app:cache-lotto',
        ];

        foreach ($commands as $name => $command) {
            $this->info("Caching {$name}...");
            $this->call($command);
        }

        $this->info('All caches updated.');
    }
}
