<?php

namespace App\Console\Commands;

use App\Services\JokerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        Cache::forget('joker_latest_draw_date');
        $obj = resolve(JokerService::class);
        $output = $obj->getLatestDrawDate();
        dd($output);
    }
}
