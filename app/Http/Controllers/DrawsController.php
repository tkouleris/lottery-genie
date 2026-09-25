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

    public function joker_stats(JokerService $jokerService)
    {
        $stats = $jokerService->getStats();
        return view('joker-stats', compact('stats'));
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

    public function lotto_stats(LottoService $lottoService)
    {
        $stats = $lottoService->getStats();
        return view('lotto-stats', compact('stats'));
    }

    public function eurojackpot_checker(Request $request, EurojackpotService $eurojackpotService)
    {
        $results = null;
        $userNumbers = $request->input('numbers', []);
        $userJokers = $request->input('jokers', []);

        if (!empty($userNumbers) && !empty($userJokers)) {
            $request->validate([
                'numbers' => 'required|array|size:5',
                'numbers.*' => 'integer|min:1|max:50',
                'jokers' => 'required|array|size:2',
                'jokers.*' => 'integer|min:1|max:12',
            ]);

            // Ensure uniqueness
            if (count(array_unique($userNumbers)) !== 5 || count(array_unique($userJokers)) !== 2) {
                return back()->withErrors('Numbers must be unique.');
            }

            $results = $eurojackpotService->checkCombination($userNumbers, $userJokers);
        }

        return view('eurojackpot-checker', compact('results', 'userNumbers', 'userJokers'));
    }

    public function joker_checker(Request $request, JokerService $jokerService)
    {
        $results = null;
        $userNumbers = $request->input('numbers', []);
        $userJokers = $request->input('jokers', []);

        if (!empty($userNumbers) && !empty($userJokers)) {
            $request->validate([
                'numbers' => 'required|array|size:5',
                'numbers.*' => 'integer|min:1|max:45',
                'jokers' => 'required|array|size:1',
                'jokers.*' => 'integer|min:1|max:20',
            ]);

            // Ensure uniqueness
            if (count(array_unique($userNumbers)) !== 5 || count(array_unique($userJokers)) !== 1) {
                return back()->withErrors('Numbers must be unique.');
            }

            $results = $jokerService->checkCombination($userNumbers, $userJokers);
        }

        return view('joker-checker', compact('results', 'userNumbers', 'userJokers'));
    }

    public function lotto_checker(Request $request, LottoService $lottoService)
    {
        $results = null;
        $userNumbers = $request->input('numbers', []);

        if (!empty($userNumbers)) {
            $request->validate([
                'numbers' => 'required|array|size:6',
                'numbers.*' => 'integer|min:1|max:49',
            ]);

            // Ensure uniqueness
            if (count(array_unique($userNumbers)) !== 6) {
                return back()->withErrors('Numbers must be unique.');
            }

            $results = $lottoService->checkCombination($userNumbers);
        }

        return view('lotto-checker', compact('results', 'userNumbers'));
    }
}
