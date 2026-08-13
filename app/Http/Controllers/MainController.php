<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use App\Services\JokerService;
use App\Services\LottoService;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index(EurojackpotService $eurojackpotService, JokerService $jokerService, LottoService $lottoService)
    {
        $euro = [];
        $euro[] = $eurojackpotService->run();

        $joker = [];
        $joker[] = $jokerService->run();

        $lotto = [];
        $lotto[] = $lottoService->run();
        return view('main', compact('euro', 'joker', 'lotto'));
    }
}
