<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Group;
use App\Domain;
use App\ExpiredProducts;
use Mail;
use App\Mail\generalMailer;
use App\Mail\AlertRating;
use App\Mail\RedSlug;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;

ini_set('max_execution_time', 3000000000000); // Evitar errores de timeout
class UnavailableProductsController extends Controller
{

    public function loadView(Request $request){
        $all_expired_products = ExpiredProducts::all();
        $langs_expired_products = ExpiredProducts::all('lang')->unique('lang');
              
        return view('unavailable-products')->with(
            array(
                'all_expired_products' => $all_expired_products,
                'lang_expired_products' => $langs_expired_products
            )
        );
    }

    public function truncateProducts(Request $request){
        $cleared = ExpiredProducts::truncate();
        $all_sites = Domain::orderBy('state', 'desc')->get();
        foreach($all_sites as $currentSite){
            try{
                 //* Check & Update Shop Data
                $service_url = "https://www." . $currentSite->url . "/wp-json/kb/v2/worked/22/";
                        
                $client = new Client([
                    'headers' => ['Content-Type' => 'application/json']
                ]);
                $body = "fromMKB";
                $start_t = microtime(1);
                $result = $client->get(
                    $service_url,
                    [
                        'body' => json_encode($body)
                    ]
                );
                $end_t = microtime(1);
                $log = LogController::makeLog('Truncate _Unavailable_Products',  $service_url, null, $currentSite->id, ($end_t - $start_t));
            
            }catch(ClientException $ex){

            }catch(ConnectException $ex){

            }
           
        }
        
        return redirect('admin/unavailableProducts');
    }

    public function refreshProducts(Request $request){
       
        $timeout_per_domain = 10;
        $dominios = DB::table('w59_dominio')->select('url', 'idioma')->whereNotIn('flag_domain', [2,3])->get();
        ini_set('max_execution_time', $timeout_per_domain * count($dominios)); // Evitar errores de timeout
        $product_count = 0; //Total unavailable products count
        $idiomas = array(); //To tell the mailable what languages to generate the CSV download links for
        DB::table('expired_products')->truncate();
        foreach ($dominios as $dominio) {
            // just for testing in my local machine, as I don't have www subdomain
            try {
            $url = "https://www." . $dominio->url . "/wp-json/kb/v2/worked/19/";
            $response = $this->curl_load($url, $timeout_per_domain);

            if (!is_null($response) && !empty($response)) {
                $response = json_decode($response);
                if (is_string($response)) $response = json_decode($response);
                if (!is_object($response)) continue;

                foreach ((array) $response as $product => $info) {

                    @$info_product = json_encode($info[2]);

                    try {
                        DB::table('expired_products')->insert(
                            ['id' => NULL, 'product_name' => $product, 'post' => $info[0], 'times_seen' => $info[1], 'lang' => $dominio->idioma, 'data_product' =>$info_product]
                        );
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                   
                    if (!in_array($dominio->idioma, $idiomas)) array_push($idiomas, $dominio->idioma);
                    $product_count++;
                }
           
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        }
        return redirect('admin/unavailableProducts');
    }

    public function curl_load($url){
        curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'erixenmdwcrvuoldumoqyvoyntwnqyevwzjjmkq.io');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function getUnavailableProductsCsv($lang)
    {
        $productos = DB::table('expired_products')->where('lang', $lang)->get();

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=" . $lang . "_unavailable_products.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $columns = array('Product Name', 'URL', 'Times displayed unavailable', 'Lang');

        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);

        foreach ($productos as $producto) {
            fputcsv($file, array($producto->product_name, $producto->post, $producto->times_seen, $producto->lang));
        }
        fclose($file);
        exit();
    }


    public function getUnavailableProducts()
    {
        $timeout_per_domain = 10;
        $dominios = DB::table('w59_dominio')->select('url', 'idioma')->whereNotIn('flag_domain', [2,3])->get();
        ini_set('max_execution_time', $timeout_per_domain * count($dominios)); // Evitar errores de timeout
        $product_count = 0; //Total unavailable products count
        $idiomas = array(); //To tell the mailable what languages to generate the CSV download links for
        DB::table('expired_products')->truncate();
        foreach ($dominios as $dominio) {
            // just for testing in my local machine, as I don't have www subdomain
            $url = "https://www." . $dominio->url . "/wp-json/kb/v2/worked/19/";
            $response = $this->curl_load($url, $timeout_per_domain);

            if (!is_null($response) && !empty($response)) {
                $response = json_decode($response);
                if (is_string($response)) $response = json_decode($response);
                if (!is_object($response)) continue;

                foreach ((array) $response as $product => $info) {
                    
                    try {
                        @$info_product = json_encode($info[2]);
                        DB::table('expired_products')->insert(
                            ['id' => NULL, 'product_name' => $product, 'post' => $info[0], 'times_seen' => $info[1], 'lang' => $dominio->idioma, 'data_product' =>$info_product]
                        );
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                    
                    if (!in_array($dominio->idioma, $idiomas)) array_push($idiomas, $dominio->idioma);
                    $product_count++;
                }
            }
        }

        $to = [

            ['email' => 'danielgomezdvalle@gmail.com'],
            ['email' => 'dennisalvarado89@gmail.com'],
            ['email' => 'florian.felsing@googlemail.com', 'name' => 'Florian Felsing']
        ];

        Mail::to($to)->send(new \App\Mail\UnavailableProducts($product_count, $idiomas));
    }

    function Exportcsv(Request $request){
        $min = $request->input('min');
        $lang = $request->input('lang');

        if($min == null)
            $min = 0;

       
        if($lang != null){
            $productos = DB::table('expired_products')
                        ->where('lang', '=',$lang)
                        ->where('times_seen','>=', intval($min))
                        ->orderBy('times_seen', 'DESC')
                        ->get();
        }else{
            $productos = DB::table('expired_products')
            ->where('times_seen','>=', intval($min))
            ->orderBy('times_seen', 'DESC')
            ->get();
        }

        
        header("Content-Type: application/vnd.ms-excel; charset=ISO-8859-1");
        header("Content-Disposition: attachment; filename=" . $lang . "_unavailable_products.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $columns = array('Product Name', 'URL', 'ASIN', 'PRODUCTO URL', 'SEEN', 'Status', 'Agent', 'Fecha');

        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);

        foreach ($productos as $producto) {
            try {
                $variable = $producto;
                $jsonRecovered = json_decode( $variable->data_product );
                // echo '<pre>'; print_r($jsonRecovered); echo '</pre>';

                // $country = $jsonRecovered->country;
                $fecha_print; $ASIN; $url_product;

                $arrTmp = $jsonRecovered->items;

                foreach ( $arrTmp as $key => $value ){

                    // echo '<pre>'; print_r($value->date_updated); echo '</pre>';
                    $fecha_print = $value->date_updated;
                    $ASIN = $key;
                    $url_product = $value->url;

                }

                if( $producto->product_name == '' || $producto->product_name == null || $producto->product_name == NULL || isset($producto->product_name) ){
                    $productoName = 'Product: ' . $ASIN;
                }else{
                    $productoName = $producto->product_name;
                }

                fputcsv($file, array($productoName, $producto->post, $ASIN, $url_product, $producto->times_seen, '', '', $fecha_print));
            
            } catch (\Throwable $th) {
                //throw $th;
            }
           
            
        }
        fclose($file);
        exit();

        return redirect('unavailableProducts');
    }
}
