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
                $user = $request->user();
                $user->makeVisible('ya_metrika');
                if ($user->ya_metrika != null) {
                    $access_data = json_decode($user->ya_metrika,true);
                } else {
                    $access_data = [];
                }
                $access_data['access_token'] = $res['access_token'];
                $access_data['expires_in'] = $res['expires_in'];
                $access_data['refresh_token'] = $res['refresh_token'];
                $user->ya_metrika = json_encode($access_data);
                $user->save();
                $this->updateCounters($request);
                return response()->json(['status'=>'success'],200);
            }
            return response()->json(['status'=>'fail'],200);
        }
        return response()->json(['status'=>'fail'],200);
    }

    public function getCounters(Request $request) {
        $user = $request->user();
        $user->makeVisible('ya_metrika');
        $options = json_decode($user->ya_metrika,true);

        if (!isset($options['access_token'])) {
            return response()->json(['status'=>'notConnection','messge'=>'Интеграция не настроена']);
        }

        $res = Http::withToken($options['access_token'])->get('https://api-metrika.yandex.net/management/v1/counters');
        if ($res->status() == 403) {
            return response()->json(['status'=>'notConnection','message'=>'Интеграция не подключена'],200);
        }

        $counters = $res->json()['counters'];
        if (!isset($options['counters'])){
            $options['counters'] = [];
        }

        foreach($counters as $counter) {
            $options['counters'][$counter['id']]['name'] = $counter['name'];
            if (!isset($options['counters'][$counter['id']]['is_checked'])) {
                $options['counters'][$counter['id']]['is_checked'] = false;
            }
            $res = Http::withToken($options['access_token'])->get('https://api-metrika.yandex.net/management/v1/counter/'.$counter['id'].'/goals');
            if ($res->status() == 403) {
                return response()->json(['status'=>'notConnection'],200);
            }

            $goals = $res->json()['goals'];
            if (!isset($options['counters'][$counter['id']]['goals'])) {
                $options['counters'][$counter['id']]['goals'] = [];
            }
            
            foreach ($goals as $goal) {
                $options['counters'][$counter['id']]['goals'][$goal['id']]['name'] = $goal['name'];
                if (!isset($options['counters'][$counter['id']]['goals'][$goal['id']]['is_checked'])) {
                    $options['counters'][$counter['id']]['goals'][$goal['id']]['is_checked'] = false;
                }
            }

            //Удаляем цели, которых больше нет
            foreach ($options['counters'][$counter['id']]['goals'] as $index => $goal) {
                $is_exists = false;
                foreach($goals as $item) {
                    if ($item['id'] == $index) {
                        $is_exists = true;
                    }
                }
                if (!$is_exists){
                    unset($options['counters'][$counter['id']]['goals'][$index]);
                }
            }
        }

        //Удаляем счетчики, которых нет
        foreach ($options['counters'] as $index => $counter) {
            $is_exists = false;
            foreach ($counters as $item) {
                if ($item['id'] == $index) {
                    $is_exists = true;
                }
            }
            if (!$is_exists) {
                unset($options['counters'][$index]);
            }
        }

        $user->ya_metrika = json_encode($options);
        $user->save();

        return response()->json(['status'=>'success','counters'=>$options['counters']],200);
    }

    public function getAnalytics(Request $request) {
        if (!isset($request->counters)) {
            return response()->json(['status'=>'badData','message'=>'Неправильные данные запроса'],200);
        }

        $user = $request->user();
        $user->makeVisible('ya_metrika');
        $options = json_decode($user->ya_metrika,true);

        $back_counters = $options['counters'];
        $front_counters = $request['counters'];

        //синхронизация
        foreach ($back_counters as $i => &$back_counter) {
            $back_counter['is_checked'] = $front_counters[$i]['is_checked'];
            foreach ($back_counter['goals'] as $j => &$back_goal) {
                $back_goal['is_checked'] = $front_counters[$i]['goals'][$j]['is_checked'];
            }
        }
        $options['counters'] = $back_counters;

        $user->ya_metrika = json_encode($options);
        $user->save();
        //end синхронизация

        $ids = [];
        $goals = [];
        foreach ($back_counters as $counter_id => $counter) {
            if ($counter['is_checked']) {
                $ids[] = $counter_id;
                foreach ($counter['goals'] as $goal_id => $goal) {
                    if ($goal['is_checked']) {
                        $goals[] = $goal_id;
                    }
                }
            }
        }

        $message = '';
        if (count($ids) == 0) {
            $message = "Должен быть выбран хотя бы один счетчик. ";
        }
        if (count($goals) == 0) {
            $message .= "Должна быть выбрана хотя бы одна цель";
        }

        if ($message !== '') {
            return response()->json(['status'=>'nodData','message'=>$message],200);
        }
        // return ['ids'=>$ids,'goals'=>$goals];

        //Примечание количество заявок здесь считаем "Количество визитов, в рамках которых произошло достижение цели" (ym:s:goal319539803visits)
        
        $url = 'https://api-metrika.yandex.net/stat/v1/data';
        $dimensions = 'ym:s:trafficSource';
        // $metrics = 'ym:s:users,ym:s:goal30606879conversionRate,ym:s:goal30606884conversionRate';

        $ymGoals = '';
        foreach ($goals as $i => $item) {
            $ymGoals .= "ym:s:goal{$item}visits";
            if ($i < count($goals)-1) {
                $ymGoals .= ',';
            }
        }

        $goal_count = count($goals);

        // return $ids;

        // $metrics = 'ym:s:visits,ym:s:goal319539803visits';

        $res = Http::withToken($options['access_token'])->get($url,[
            'metrics'=>$ymGoals,
            'ids'=>$ids,//,'95954663'],
            'dimensions'=>$dimensions,
            'lang'=>'ru'
        ]);
        
        // return $res->json();

        if ($res->status() == 403) {
            return response()->json(['status'=>'notConnection','message'=>'Интеграция не подключена'],200);
        }

        $res = $res->json()['data'];
        $analitics = [];
        // return $res[0]['dimensions'][0]['name'];
        foreach ($res as $item) {
            $ym_goals_count = 0;
            for ($i=0;$i<$goal_count;$i++) {
                $ym_goals_count += $item['metrics'][$i];
            }
            $analitics[] = [
                'dailygrow'=>$item['dimensions'][0]['name'],
                'requests'=>$ym_goals_count,
                'conversion_to_sales'=>'?',
                'sales'=>'?',
                'total_sum'=>'?',
                'average_check'=>'?',
                'profit'=>'?',
            ];
        }
        // $sdf = $result;
        // $sdf = $res;

        return response()->json(['status'=>'success','analitics'=>$analitics],200);
    }
}
