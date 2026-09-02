<?php

use App\Http\Controllers\DrawsController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('/', [MainController::class, 'index']);
Route::get('/eurojackpot', [DrawsController::class, 'draw_eurojackpot'])->name('eurojackpot');
Route::get('/eurojackpot/stats', [DrawsController::class, 'eurojackpot_stats'])->name('eurojackpot.stats');
Route::get('/joker', [DrawsController::class, 'draw_joker'])->name('joker');
Route::get('/joker/stats', [DrawsController::class, 'joker_stats'])->name('joker.stats');
Route::get('/lotto', [DrawsController::class, 'draw_lotto'])->name('lotto');
Route::get('/lotto/stats', [DrawsController::class, 'lotto_stats'])->name('lotto.stats');
Route::get('/about', function () {
    return view('about');
})->name('about');
