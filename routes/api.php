<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, B24Controller, YaDirectController, YaMetricController};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(AuthController::class)->group(function(){
    Route::post('/login','login');
    Route::get('/user','user')->middleware('auth:sanctum');
});

Route::controller(B24Controller::class)->middleware('auth:sanctum')->prefix('b24')->group(function(){
    Route::get('/get-analytics','getAnalytics');
});
Route::controller(B24Controller::class)->prefix('b24')->group(function(){
    Route::get('/create-leads','createLeads');
    Route::get('/create-deals','createDeals');
    Route::get('/get-leads','getLeads');
    Route::get('/create-deal','createDeal');
});

Route::controller(YaMetricController::class)->prefix('ya-metric')
                                        ->middleware('auth:sanctum')
                                        ->group(function(){
    Route::post('/get-token','getToken');
    Route::get('/get-counters','getCounters');
    Route::post('/get-analytics','getAnalytics');
});

Route::controller(YaDirectController::class)->prefix('ya-direct')
                                        ->middleware('auth:sanctum')
                                        ->group(function(){
    Route::post('/get-token','getToken');
    Route::post('/get-campaigns','getCampaigns');
});