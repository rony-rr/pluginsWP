<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Group;
use App\Domain;
use Mail;
use App\PostQueue;
use App\KbOption;

//
use GuzzleHttp\Client;
//

class ApiController extends Controller
{
    //
    public function get_url (Request $request){

        $successArray = json_decode($request->getContent());
        $successArray = json_encode($successArray,JSON_NUMERIC_CHECK);
        $successArray = json_decode($successArray,JSON_NUMERIC_CHECK);

        $result_Domain= DB::table('w59_dominio')
                ->select('w59_page.id as pageid','w59_dominio.url as urldomain','w59_page.IDdominio', 'w59_page.url as slug', 'w59_page.IDgroudpage as gPagina', 'w59_dominio.IDgroup as GrupoDominio', 'w59_page.state')
                ->join('w59_page', 'w59_dominio.id', '=', 'w59_page.IDdominio')
                ->whereIn('w59_dominio.IDgroup',$successArray)
                ->where('w59_page.assigned','=', NULL)
                ->get(); 

   return json_encode($result_Domain);
    }


    //// Get Group Domain
    public function get_group (Request $request){

        $successArray = json_decode($request->getContent());
        $successArray = json_encode($successArray,JSON_NUMERIC_CHECK);
        $successArray = json_decode($successArray,JSON_NUMERIC_CHECK);


        $result_Domain = DB::table('w59_group')->where('id', '<>',2)->get();


    return json_encode($result_Domain);
    }

    public function FunctionQueue(Request $request){
        
        $result_Domain= DB::table('work_queue')->where('state',"=", null)->where('response',"=", "in_progress")->inRandomOrder()->first();
       if($result_Domain != NULL){
        $IDdominio = DB::table('w59_dominio')->where('id', $result_Domain->id_base)->first();
          } 

        if($result_Domain == NULL ){
           $a =  $result_Domain = 'No queued processes';
        }else{
            $a = $result_Domain->name_work;
            switch ($a) {
                case 'push_domain':
                    $a = "updating Hreflang Mapping ".$IDdominio->url;
                break;

                case 'get_pages':
                    $a = "Bringing articles to the site ".$IDdominio->url;
                break;
            }
        }
        return $a;
    }


    public function CleanPermalink(Request $request){

        $dom = $request->input('text');
        $user_name = $request->input('user_name');
        $IDdominio = DB::table('w59_dominio')->where('url',$dom)->first();

        $url = "https://www.".$dom."/wp-json/kb/v2/worked/92817461811/";

        // valido si el dominio esta dentro de la bd.
        if($IDdominio == NULL || $IDdominio == "")
            return "Domain entered does not exist.";
        
        // SEND CURL
        curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return 'Hey!! '.$user_name .'  '.$dom.' has updated the permalinks.';
    }

    public function ChangeStatus(Request $request){
        $idPOST= $request->input('id');
        $domain = $request->input('domain');
        $op = $request->input('op');

     
        $result_Domain= DB::table('w59_dominio')
        ->select('w59_page.id as pageid','w59_dominio.url as urldomain','w59_page.IDdominio', 'w59_page.url as slug', 'w59_page.IDgroudpage as gPagina', 'w59_dominio.IDgroup as GrupoDominio')
        ->join('w59_page', 'w59_dominio.id', '=', 'w59_page.IDdominio')
        ->where('w59_dominio.url',$domain)
        ->where('w59_page.idpost',$idPOST)
        ->first(); 
  
        if($op == 1){
            DB::table('w59_page')
            ->where('id', $result_Domain->pageid)
            ->update(['assigned' => 'AO']);
        }else{
            DB::table('w59_page')
            ->where('id', $result_Domain->pageid)
            ->update(['assigned' => null]);
        }

       

    }

    public function functionGroupAjax(Request $request){

        $grupo = $request->input('grupo');
        $type = $request->input('type');
      
        switch ($type) {
            case '1':

                $get_group = DB::table('w59_group')->where('id','=',$grupo )->first();

                
                $group_pages = ($get_group->type==2) ? DB::table('w59_groudpage')->get() : DB::table('w59_groudpage')->where('IDgroup','=',$grupo)->get();



                $get_lang = DB::table('w59_dominio')->select('idioma')->where('IDgroup','=',$grupo )->get();

                $count_lang = [];

                foreach($get_lang as $polang){
                    $count_lang[] = explode("-", $polang->idioma)[0];
                }

               $count_lang = array_count_values($count_lang);
               $alang = max($count_lang);
               $alang = array_search($alang, $count_lang);



               
                $data = '';
                foreach($group_pages as $dataTable){

                    $assigned = DB::table('w59_page')->where('idgroudpage','=',$dataTable->id)->count();
                    
                    if($get_group->type==2){
                        $langs_duplicate = DB::table('w59_dominio')
                        ->select('w59_dominio.url as urldom', 'w59_dominio.idioma as lang', 'w59_page.IDdominio as Domain', 'w59_group.nombre as name_fgroup')
                        ->join('w59_page', 'w59_page.IDdominio', '=', 'w59_dominio.id')
                        ->join('w59_group', 'w59_group.id', '=', 'w59_dominio.IDgroup')
                        ->where('idgroudpage','=',$dataTable->id)
                        ->where('w59_dominio.idioma', 'like', ''.$alang.'%')
                        ->get();
                    }else{
                        $langs_duplicate = DB::table('w59_dominio')
                        ->select('w59_dominio.url as urldom', 'w59_dominio.idioma as lang', 'w59_page.IDdominio as Domain', 'w59_group.nombre as name_fgroup')
                        ->join('w59_page', 'w59_page.IDdominio', '=', 'w59_dominio.id')
                        ->join('w59_group', 'w59_group.id', '=', 'w59_dominio.IDgroup')
                        ->where('idgroudpage','=',$dataTable->id)
                        ->get();
                    }
                  

                    $string_lang = "";
                    $arr = [];
                    foreach($langs_duplicate as $po){
                      
                      $string_lang = ($string_lang == '') ? explode("-", $po->lang)[1] : $string_lang." | ".explode("-", $po->lang)[1];
                      $arr[]= $po->lang;   
                     }
                   $count= count($arr);
                   
                   $is_duplicate = (count(array_unique($arr)) < $count) ? "<button style='font-size: 9px; background: #f56f54; color: #fff; font-weight: bold; pointer-events: none; padding: 2px 20px; border-radius: 10px;'>YES</button>" : "<button style='font-size: 9px; background: #46be8a; color: #fff; font-weight: bold; pointer-events: none; padding: 2px 20px; border-radius: 10px;'>NO</button>";

                   $clean_group = "<a class='icon-del actionBottom' href='dpg/".$dataTable->id."'>Delete</a>";
                   if($string_lang != ""){
                    $data = $data.'
                    {
                        "id": "'.$dataTable->id.'",
                        "Name": "'.$dataTable->title.'",
                        "#assigned pages": "'.$assigned.'",
                        "Lang": "'.$string_lang.'",
                        "Duplicate Language": "'.$is_duplicate.'",
                        "Action": "'.$clean_group.'",
                        "Group": "'.$po->name_fgroup.'"
                        
                    },';   
                }
                }

            return '{
                "data": [
                    '.substr($data, 0, -1).'
                ]
            }';
            break;

            case '2':
                $langs_duplicate = DB::table('w59_dominio')
                ->select('w59_dominio.url as urldom', 'w59_dominio.idioma as lang', 'w59_page.url as urlpage', 'w59_page.title as titlepages', 'w59_page.id as idpages', 'w59_page.idgroudpage as idgpages', 'w59_dominio.IDgroup as idgrupoDom' )
                ->join('w59_page', 'w59_page.IDdominio', '=', 'w59_dominio.id')
                ->where('idgroudpage','=',$grupo)
                ->get();
                return json_encode($langs_duplicate);
            break;
            
        }

    }


    //// api csv Export Product 
    public function ExportCSVproductAPi(Request $request){

        // $min = 20;
        $lang = $request->input('lang');

        // echo "hola ". $url ." " . $min . "<br />";

        // if($min == null){ $min = 0; }

       
        if($lang != null){
            $productos = DB::table('expired_products')
                        ->where('lang', 'like', '%'. $lang .'%')
                        // ->where('times_seen','>=', intval($min))
                        ->orderBy('times_seen', 'DESC')
                        ->get();
        }else{
            $productos = DB::table('expired_products')
            // ->where('times_seen','>=', intval($min))
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

    }

}
