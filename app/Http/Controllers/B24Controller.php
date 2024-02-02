<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class B24Controller extends Controller
{
    public function getAnalytics(Request $request) {
        try {
            $user = $request->user();
            $user->makeVisible('b24_link');
            $b24_link = $user->b24_link;
        
            $res = Http::post($b24_link.'crm.lead.list.json',[
                    'select' => ['ID','TITLE','UF_CRM_1702559476','DATE_CREATE']
            ]);

            if ($res->status() !== 200) {
                return response()->json(["status"=>"fail","message"=>"Что то не так с соединением c Bitrix24"],200);
            }
                
            $leads = $res->json()['result'];
    
            $lead_dailygrow_field_list = collect(Http::post($b24_link.'crm.lead.userfield.get',[
                    'ID' => 227
                ])->json()['result']['LIST']);
    
            foreach ($leads as &$item) {
                $field = $lead_dailygrow_field_list->firstWhere('ID',$item['UF_CRM_1702559476']);
                if ($field !== null) {
                    $item['dailygrow'] = $field['VALUE'];
                } else {
                    $item['dailygrow'] = 'Другое';
                }
            }
    
            $cost_field = "UF_CRM_1702790259";
    
            $deals = Http::post($b24_link.'crm.deal.list.json',[
                    'select' => ["ID","TITLE","UF_CRM_1702470298169","OPPORTUNITY",$cost_field,"STAGE_ID"]
                ])->json()['result'];
    
            $deal_dailygrow_field_list = collect(Http::post($b24_link.'crm.deal.userfield.get',[
                    'ID' => 219
                ])->json()['result']['LIST']);
    
            foreach ($deals as &$item) {
                $field = $deal_dailygrow_field_list->firstWhere('ID',$item['UF_CRM_1702470298169']);
                if ($field !== null) {
                    $item['dailygrow'] = $field['VALUE'];
                } else {
                    $item['dailygrow'] = 'Другое';
                }
            }
    
            $result_table = [];
    
            foreach ($lead_dailygrow_field_list as $field) {
                $result_table[]['dailygrow'] = $field['VALUE'];
            }
    
            foreach ($deal_dailygrow_field_list as $field) {
                if ( collect($result_table)->firstWhere('dailygrow',$field['VALUE']) == null) {
                    $result_table[]['dailygrow'] = $field['VALUE'];
                }
            }
    
            $result_table[]['dailygrow'] = 'Другое';
    
            //Заявки
            foreach ($result_table as &$item) {
                $count = 0;
                foreach($deals as $deal) {
                    if ($deal['dailygrow'] == $item['dailygrow']) {
                        $count++;
                    }
                }
    
                foreach($leads as $lead) {
                    if ($lead['dailygrow'] == $item['dailygrow']) {
                        $count++;
                    }
                }
    
                $item['requests'] = $count;
            }
    
            //Продажи
            foreach ($result_table as &$item) {
                $count = 0;
                foreach($deals as $deal) {
                    if ($deal['dailygrow'] == $item['dailygrow'] AND $deal['STAGE_ID'] == "WON") {
                        $count++;
                    }
                }
    
                $item['sales'] = $count;
            }
    
            //Конверсия в продажи Conversion to sales
            foreach ($result_table as &$item) {
                $item['conversion_to_sales'] = 0;
                if ($item['requests'] != 0) {
                    $item['conversion_to_sales'] = $item['sales'] * 100 / $item['requests'];
                }
            }
    
            //Выручка - Сумма сделок
            foreach ($result_table as &$item) {
                $sum = 0;
                foreach($deals as $deal) {
                    if ($deal['dailygrow'] == $item['dailygrow'] AND $deal['STAGE_ID'] == "WON") {
                        $sum = $sum + $deal['OPPORTUNITY'];
                    }
                }
    
                $item['total_sum'] = $sum;
            }
    
            //Средний чек
            foreach ($result_table as $i => &$item) {
                $item['average_check'] = 0;
                if ($result_table[$i]['sales'] != 0) {
                    $item['average_check'] = $result_table[$i]['total_sum'] / $result_table[$i]['sales'];
                }
            }
    
            //Прибыль
            foreach ($result_table as &$item) {
                $sum = 0;
                foreach($deals as $deal) {
                    if ($deal['dailygrow'] == $item['dailygrow'] AND $deal['STAGE_ID'] == "WON") {
                        $sum = $sum + ($deal['OPPORTUNITY'] - $deal[$cost_field]);
                    }
                }
    
                $item['profit'] = $sum;
            }
    
            return response()->json(["status"=>"success","data"=>$result_table],200);
        } catch(\Exception $e) {
            return response()->json(['status'=>'error',"message"=>"Что то пошло не так."],200);
        }
    }

    public function createLeads() {
        $link = "https://b24-oopwiw.bitrix24.ru/rest/1/iss07ljyd5xd58o9/";

        $dailygrow = [
            'Постоянный клиент',
            'Почта info',
            'Новый сайт',
            'Старый сайт',
            'Звонок',
            ''
        ];

        $deal_dailygrow = "UF_CRM_1706010654";
        $lead_dailygrow = "UF_CRM_1706010594";

        for ($i = 110; $i< 180; $i++) {
            $index = rand(0,5);
            $res = Http::post($link.'crm.lead.add',[
                'fields' => [
                    'TITLE' => 'Лид созданный автоматически '.$i,
                    // $lead_dailygrow => $dailygrow[$index],
                ]
            ]);
        }


        return $res->json();
    }

    public function getLeads() {
        $link = "https://b24-oopwiw.bitrix24.ru/rest/1/iss07ljyd5xd58o9/";
        $leadID = 0;
        $leads = [];
        $is_all = false;

        while (!$is_all) {
            $res = Http::post($link.'crm.lead.list.json',[
                'select' => ['ID','TITLE','DATE_CREATE',"OPPORTUNITY","STAGE_ID"],
                'start' => -1,
                'order' => ['ID' => 'ASC'],
                'filter' => ['>ID' => $leadID],
                // 'filter' => ['!STAGE_ID'=>$not_use_stage, '>=DATE_CREATE'=>$filterDate['dateFrom'], '<=DATE_CREATE' => $filterDate['dateTo']]
            ]);
            $res = $res->json()['result'];
            $leads = array_merge($leads,$res);
            $leadID = $res[count($res)-1]['ID'];
            if (count($res) < 50) {
                $is_all = true;
            }
        }

        return $leads;
    }

    public function createDeals() {
        $link = "https://b24-oopwiw.bitrix24.ru/rest/1/iss07ljyd5xd58o9/";

        $dailygrow = [
            'Постоянный клиент',
            'Почта info',
            'Новый сайт',
            'Старый сайт',
            'Звонок',
            ''
        ];
        // return $dailygrow;//crm.deal.fields
        // $res = Http::post($link.'crm.deal.fields');
        // return $res->json();
        $deal_dailygrow = "UF_CRM_1706010654";
        $lead_dailygrow = "UF_CRM_1706010594";
        $cost_field = 'UF_CRM_1706010827';

        $stage_id = [
            "WON",
            "C1:WON",
            "C5:WON",
            "C5:FINAL_INVOICE",
            "C1:FINAL_INVOICE"
        ];

        for ($i = 124; $i< 223; $i++) {
            $index = rand(0,5);
            $sum = rand(100,100000);
            $res = Http::post($link.'crm.deal.add',[
                'fields' => [
                    'TITLE' => 'Сделка созданная автоматически '.$i,
                    'OPPORTUNITY' => $sum,
                    $deal_dailygrow => $dailygrow[$index],
                    $cost_field => $sum * rand(60,90) / 100,
                    'STAGE_ID'=> $stage_id[rand(0,4)],
                    'CONTACT_ID'=>1
                ]
            ]);
        }


        return $res->json();
    }

    public function createDeal() {
        $link = "https://b24-oopwiw.bitrix24.ru/rest/1/iss07ljyd5xd58o9/";

        $dailygrow = [
            'Постоянный клиент',
            'Почта info',
            'Новый сайт',
            'Старый сайт',
            'Звонок',
            ''
        ];
        // return $dailygrow;//crm.deal.fields
        // $res = Http::post($link.'crm.deal.fields');
        // return $res->json();
        $deal_dailygrow = "UF_CRM_1706010654";
        $lead_dailygrow = "UF_CRM_1706010594";
        $cost_field = 'UF_CRM_1706010827';

        $stage_id = [
            "WON",
            "C1:WON",
            "C5:WON",
            "C5:FINAL_INVOICE",
            "C1:FINAL_INVOICE"
        ];

        $index = rand(0,5);
        $sum = rand(100,100000);
        $res = Http::post($link.'crm.deal.add',[
            'fields' => [
                'TITLE' => 'Сделка c utm_source = yandex ',
                'OPPORTUNITY' => $sum,
                $deal_dailygrow => $dailygrow[$index],
                $cost_field => $sum * rand(60,90) / 100,
                'STAGE_ID'=> $stage_id[rand(0,4)],
                'CONTACT_ID'=>1,
                'UTM_SOURCE'=>'yandex'
            ]
        ]);


        return $res->json();
    }
}
