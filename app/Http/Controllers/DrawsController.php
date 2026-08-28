<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use App\Services\JokerService;
use App\Services\LottoService;
use Illuminate\Http\Request;

class DrawsController extends Controller
{
    public function draw_eurojackpot(Request $request, EurojackpotService $eurojackpotService)
    {
        $draws = [];
        $number_of_draws = $request->input('number_of_draws', 0);
        for ($i = 0; $i < $number_of_draws; $i++)
        {
            $draws[] = $eurojackpotService->run();
        }

        return view('eurojackpot', compact('draws'));
    }

    public function eurojackpot_stats(EurojackpotService $eurojackpotService)
    {
        $stats = $eurojackpotService->get_stats();
        return view('eurojackpot-stats', compact('stats'));
    }

    public function draw_joker(Request $request, JokerService $jokerService)
    {
        $draws = [];
        $number_of_draws = $request->input('number_of_draws', 0);
        for ($i = 0; $i < $number_of_draws; $i++)
        {
            $draws[] = $jokerService->run();
        }

        return view('joker', compact('draws'));
    }

    public function draw_lotto(Request $request, LottoService $lottoService)
    {
        $draws = [];
        $number_of_draws = $request->input('number_of_draws', 0);
        for ($i = 0; $i < $number_of_draws; $i++)
        {
            $draws[] = $lottoService->run();
        }

        return view('lotto', compact('draws'));
    }
}
