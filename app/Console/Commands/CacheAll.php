<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

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
     *
     * Each command runs in its own process so memory used by one
     * (e.g. loaded spreadsheets) is released before the next starts.
     */
    public function handle()
    {
        $commands = [
            'Eurojackpot' => 'app:cache-eurojackpot',
            'Joker' => 'app:cache-joker',
            'Lotto' => 'app:cache-lotto',
        ];

        $failed = false;

        foreach ($commands as $name => $command) {
            $this->info("Caching {$name}...");

            $result = Process::forever()
                ->path(base_path())
                ->run([PHP_BINARY, 'artisan', $command], function (string $type, string $output) {
                    $this->output->write($output);
                });

            if ($result->failed()) {
                $this->error("Caching {$name} failed (exit code {$result->exitCode()}).");
                $failed = true;
            }
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('All caches updated.');

        return self::SUCCESS;
    }
}
