<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use Illuminate\Http\Request;

class EurojackpotController extends Controller
{
    public function index(Request $request, EurojackpotService $eurojackpotService)
    {
        $draws = [];
        $number_of_draws = $request->input('number_of_draws', 0);
        for ($i = 0; $i < $number_of_draws; $i++)
        {
            $draws[] = $eurojackpotService->run();
        }

        return view('eurojackpot', compact('draws'));
    }
}
