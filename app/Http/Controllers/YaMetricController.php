<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Request;

class YaMetricController extends Controller
{
    public function getToken(Request $request) {
        if (isset($request->code)) {
            $res = Http::asForm()->post('https://oauth.yandex.ru/token',
                [
                    "grant_type"=>'authorization_code',
                    "code" => $request->code,
                    "client_id" => env('YA_METRIC_ClientID'),
                    'client_secret' => env('YA_METRIC_ClientSecret')
                ]
            );

            if (isset($res['access_token'])) {
                $token = $res['access_token'];
                $expires_in = $res['expires_in'];
                $refresh_token = $res['refresh_token'];
                $user = $request->user();
                $user->makeVisible('ya_metric_token');
                $user->ya_metric_token = $token;
                $user->save();
                return response()->json(['status'=>'success'],200);
            }
            return response()->json(['status'=>'fail'],200);
        }
        return response()->json(['status'=>'fail'],200);
    }

    public function checkConnection(Request $request) {
        $user = $request->user();
        $user->makeVisible('ya_metric_token');
        if ($user->ya_metric_token == null) {
            return response()->json(['status'=>'notConnection','messge'=>'Соединение не настроено']);
        }
        $res = Http::withToken($user->ya_metric_token)->get('https://api-metrika.yandex.net/management/v1/counters');
        if ($res->status() == 403) {
            return response()->json(['status'=>'notConnection'],200);
        }
        return response()->json(['status'=>'success'],200);
    }
}
