<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Group;
use App\Domain;
use Mail;
use App\PostQueue;
use App\KbOption;

class ReportController extends Controller
{
    public function ReportBilliger(Request $request)
    {
  
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://kaufberater.io_prod_API:BlgBi1v1BV@solute.de/export/syndication/kaufberater.io_prod_API/?C=M&O=D",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // regex 
        $re = '/<td><a href="(.*?).gz">/m';
        $baseURl = "https://kaufberater.io_prod_API:BlgBi1v1BV@solute.de/export/syndication/kaufberater.io_prod_API/";
        preg_match_all($re, $response, $matches);
        $arr_content = [];
        foreach($matches[1] as $po){
 
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://solute.de/export/syndication/kaufberater.io_prod_API/".$po.".gz",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_HTTPHEADER => array(
                "Authorization: Basic a2F1ZmJlcmF0ZXIuaW9fcHJvZF9BUEk6QmxnQmkxdjFCVg=="
              ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);
            $unzipped = gzdecode($response);
            $conver_in = explode("\n", $unzipped);
            unset($conver_in[0]);
            $i = 0;
            $keys = array("category_id","category_name","log","timestamp","offer_id","shop_id","cpc");
            $arr = [];
            foreach($conver_in as $pa){
                $conver = explode(";", $pa);   
                $arr_content[] = $conver;
            }
        }
        $posts = $request->input('export');
        $date = $request->input('date');

        if($posts == 'true'){
            

            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=" . "Billiger" . "_report.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
    
            $columns =array("category_id","category_name","log","timestamp","offer_id","shop_id","cpc");
    
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
    
            foreach ($arr_content as $producto) {
                if(isset($date)){
                @$newDate = date("Y-m", strtotime($producto[3]));
                if($newDate ==$date ){
                    fputcsv($file, array(@$producto[0], @$producto[1], @$producto[2], @$producto[3],@$producto[4],@$producto[5],@$producto[6]));
                }
                 }else{
                fputcsv($file, array(@$producto[0], @$producto[1], @$producto[2], @$producto[3],@$producto[4],@$producto[5],@$producto[6]));
                }
            }
            fclose($file);
            exit();

            die;
            return redirect('reportBilliger');

        }else{
            return view('report-view')->with(
                array(
                    'data' => $arr_content
                )
            );
        }
        
    }
}
