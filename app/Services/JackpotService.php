<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class JackpotService
{
    /**
     * OPAP game ids of the draws API (https://api.opap.gr/draws/v3.0/{gameId})
     */
    private const GAMES = [
        'euro' => 5149,
        'joker' => 5104,
        'lotto' => 5103,
    ];

    /**
     * Cache key filled by the app:cache-jackpots command
     */
    private const CACHE_JACKPOTS = 'jackpots';

    /**
     * Fetches the jackpot of the next (active) draw of every game
     *
     * @throws RequestException
     */
    public function fetch(): array
    {
        $jackpots = [];
        foreach (self::GAMES as $game => $gameId) {
            $active = Http::timeout(20)
                ->get("https://api.opap.gr/draws/v3.0/{$gameId}/last-result-and-active")
                ->throw()
                ->json('active');

            // The first category is the jackpot one; OPAP guarantees a minimum amount,
            // which can be higher than the money actually carried over
            $first = $active['prizeCategories'][0];
            $jackpots[$game] = [
                'draw_id' => $active['drawId'],
                'draw_date' => Carbon::createFromTimestampMs($active['drawTime'], 'Europe/Athens')->format('d/m/Y'),
                'amount' => max($first['jackpot'] ?? 0, $first['minimumDistributed'] ?? 0, $first['fixed'] ?? 0),
            ];
        }

        return $jackpots;
    }

    /**
     * The cached jackpots, so page requests never wait on the OPAP API
     */
    public function getJackpots(): array
    {
        return Cache::get(self::CACHE_JACKPOTS, []);
    }

    /**
     * @throws RequestException
     */
    public function refresh(): array
    {
        $jackpots = $this->fetch();
        Cache::put(self::CACHE_JACKPOTS, $jackpots, now()->addDays(7));

        return $jackpots;
    }
}
