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
        
            $leads = Http::post($b24_link.'crm.lead.list.json',[
                    'select' => ['ID','TITLE','UF_CRM_1702559476','DATE_CREATE']
                ])->json()['result'];
    
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
}
