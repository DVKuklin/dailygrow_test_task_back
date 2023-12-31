<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class YaDirectController extends Controller
{
    public function getToken(Request $request) {
        if (isset($request->code)) {
            $res = Http::asForm()->post('https://oauth.yandex.ru/token',
                [
                    "grant_type"=>'authorization_code',
                    "code" => $request->code,
                    "client_id" => env('YA_DIRECT_ClientID'),
                    'client_secret' => env('YA_DIRECT_ClientSecret')
                ]
            );
            info($res->json());

            if (isset($res['access_token'])) {
                $user = $request->user();
                $user->makeVisible('ya_direct');
                if ($user->ya_direct != null) {
                    $access_data = json_decode($user->ya_direct,true);
                } else {
                    $access_data = [];
                }
                $access_data['access_token'] = $res['access_token'];
                $access_data['expires_in'] = $res['expires_in'];
                $access_data['refresh_token'] = $res['refresh_token'];
                $user->ya_direct = json_encode($access_data);
                $user->save();

                return response()->json(['status'=>'success'],200);
            }
            return response()->json(['status'=>'fail'],200);
        }
        return response()->json(['status'=>'fail'],200);
    }

    public function getCampaigns(Request $request) {

        $user = $request->user();
        $user->makeVisible('ya_direct');
        $options = json_decode($user->ya_direct,true);

        if (!isset($options['access_token'])) {
            return response()->json(['status'=>'notConnection','message'=>'Интеграция не подключена'],200);
        }

        $res = Http::
            withToken($options['access_token'])
            // withToken('sdf')
            ->withHeaders([
            "Accept-Language"=>'ru',
            "Content-Type"=>"application/json; charset=utf-8",
        ])->post('https://api-sandbox.direct.yandex.com/json/v5/campaigns',[
            "method"=>'get',
            "params"=>["FieldNames"=>["Id","Name"]]
        ]);
        $res = $res->json();
        if (isset($res['error'])) {
            return response()->json(['status'=>'fail','messge'=>'Соединение не настроено'],200);
        }

        $compaigns = $res['result']['Campaigns'];

        return response()->json(['status'=>'success','campaigns'=>$compaigns],200);
    }
}
