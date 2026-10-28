<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use App\Services\JackpotService;
use App\Services\JokerService;
use App\Services\LottoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index(EurojackpotService $eurojackpotService, JokerService $jokerService, LottoService $lottoService, JackpotService $jackpotService)
    {

        $euro = $eurojackpotService->getLatestDrawDate();
        $joker = $jokerService->getLatestDrawDate();
        $lotto = $lottoService->getLatestDrawDate();
        $jackpots = $jackpotService->getJackpots();

        return view('main', compact('euro', 'joker', 'lotto', 'jackpots'));
    }
}
