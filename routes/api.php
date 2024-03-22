<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, B24Controller, YaDirectController, YaMetricController};
use App\Jobs\TestJob;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use Illuminate\Support\Facades\Redis;

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



Route::post('/ym-test', function (Request $request) {
    $baseUrlStatic = 'https://api-metrika.yandex.net/stat/v1/data';
    $token = 'y0_AgAAAAAiqK2-AAsxSAAAAAD5X6XUAABKkqTKy0xDgoEvYByF9To69hlhDA';
    $counter = '1226165';

    //Kuklin
    // $baseUrlStatic = 'https://api-metrika.yandex.net/stat/v1/data';
    // $token = 'y0_AgAAAAAQJVw7AAsxIgAAAAD5PZBlAAAcd4wJtNlN3pjFpgTe1mSWZRV7Iw';
    // $counter = '96302384';



    //DirectPlatformType - тип рекламной площадки
    //DirectPlatformTypeName - 
    //DirectPlatform - рекламная площадка
    //DirectSearchPhrase - поисковая фраза

    $res = Http::withToken($token)->get($baseUrlStatic,[
        'metrics'=>'ym:s:visits',
        // 'dimensions'=>'ym:s:lastSignDirectClickOrderName',
        // 'dimensions'=>'ym:s:lastSignDirectClickOrder',
        'dimensions'=>'ym:s:lastSignDirectClickOrder,ym:s:lastSignDirectSearchPhrase',
        // 'dimensions'=>'ym:s:lastSignDirectBannerGroup',DirectClickOrderName
        'ids'=>[$counter],
        'date1'=>Carbon::parse('12.01.2024')->isoFormat('YYYY-MM-DD'),
        'date2'=>Carbon::parse('17.02.2024')->isoFormat('YYYY-MM-DD'),
        'lang'=>'ru',
    ])->json();

    return $res;
});

Route::post('/test-middleware', function (Request $request) {

    return $request->all();

})->middleware('test:someData');


Route::get('/redirect-url', function (Request $request) {
    return 'redirect-url';
});

Route::get('/queue-test', function (Request $request) {
    $parameters = 'parameters';
    
    dispatch(function () use ($parameters) {
        for ($i=0;$i<1000000;$i++) {
            for ($j=0;$j<10000;$j++) {
            }
        }

        info($parameters);
    })->afterResponse();
    
    // Redis::set('name', 'Taylor');
    // return "ok";
    // info('ffffffffff');
    // TestJob::dispatch('Hello');

    //Или так
    // $job = new TestJob('Hello');
    // dispatch($job);

    return '55555';
});

Route::get('/queue-test-test', function (Request $request) {

    return 'queue-test-test';
});