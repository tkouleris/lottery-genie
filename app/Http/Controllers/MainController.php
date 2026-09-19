<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use App\Services\JokerService;
use App\Services\LottoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index(EurojackpotService $eurojackpotService, JokerService $jokerService, LottoService $lottoService)
    {

        $euro = $eurojackpotService->getLatestDrawDate();
        $joker = $jokerService->getLatestDrawDate();
        $lotto = $lottoService->getLatestDrawDate();
        return view('main', compact('euro', 'joker', 'lotto'));
    }
}
