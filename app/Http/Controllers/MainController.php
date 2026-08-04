<?php

namespace App\Http\Controllers;

use App\Services\EurojackpotService;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index(EurojackpotService $eurojackpotService)
    {
        $draws = [];
        $draws[] = $eurojackpotService->run();

        return view('main', compact('draws'));
    }
}
