<?php

use App\Http\Controllers\Auth\LoghyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('home');
});

Auth::routes();

Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::group(['prefix' => 'loghy/callback', 'as' => 'loghy.callback.'], function () {
        Route::get('register', [LoghyController::class, 'handleRegisterCallback'])->name('register');
        Route::get('login', [LoghyController::class, 'handleLoginCallback'])   ->name('login');
        Route::get('error', [LoghyController::class, 'handleErrorCallback'])   ->name('error');
    });
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::delete('/loghy_history', [App\Http\Controllers\LoghyHistoryController::class, 'destroy'])
    ->name('loghy_history.destroy');
