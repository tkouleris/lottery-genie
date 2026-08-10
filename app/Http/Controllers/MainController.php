<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use App\Services\JokerService;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index(EurojackpotService $eurojackpotService, JokerService $jokerService)
    {
        $euro = [];
        $euro[] = $eurojackpotService->run();

        $joker = [];
        $joker[] = $jokerService->run();
        return view('main', compact('euro', 'joker'));
    }
}
