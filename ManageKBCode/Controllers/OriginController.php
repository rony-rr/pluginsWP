<?php

namespace App\Http\Controllers;
use App\Http\Controllers\LogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
ini_set('max_execution_time', 3000000000000); // Evitar errores de timeout

class OriginController extends Controller
{
    public function origin_pages (Request $request){

        $successArray = json_decode($request->getContent());

        /// contenedor PUSH
        $content_push = array();
        /// Variables
        $url_select = $successArray->url_select; // url del nuevo post a asignar.
        $url_origin = $successArray->url_origin; // url de origin
        $url_title = $successArray->title_post; // Titulo de asignar
        $id_post = $successArray->id_post; // id post asignar
        $nameNewGroup = $successArray->new_post; // Nombre grupo si no existe
        $CurrentDomain = $successArray->Current_Domain; //Dominio actual de la peticion


    
        /// variables de busqueda  
        $origin_search = explode("*", $url_origin);
        /// Formo las Variables de origen
        $url          = $origin_search[0];
        $idDom        = $origin_search[1]; /// Esto es el id del dominio de la pagina de origen
        $GrupoPagina  = $origin_search[2]; /// Esto me trae el grupo al cual la pagina de origen pertenece
        $GrupoDominio    = $origin_search[3]; /// ID groud dominio
        $ID_Origin    = $origin_search[4]; /// ID post

        /// Separo el Host y el SLUG
        $base_url     = explode("/", $url);
        /// Formo las Variables
        $slug         = "/".$base_url[1]."/";
        $host         = $base_url[0];


         /// ver info del dominio
         $isInGroup = DB::table('w59_dominio')->where('url',$CurrentDomain)->first();

            if($isInGroup != NULL  && $GrupoPagina > 0){
        /// Validaciones 
            /// Solo Aceptar una URL por DOMINIO.
            $is = DB::table('w59_page')->where('IDgroudpage',$GrupoPagina)->where('IDdominio',$isInGroup->id)->first();
            if($is != NULL){

            if(is_int($is->IDdominio)  &&  $is->IDdominio != 0){
                if($is->idpost !=$id_post ){
                       return "A URL of this domain already exists in this group. Please contact an administrator.";
                }
            }
            
            $count_pagesINgroup = DB::table('w59_page')->where('IDgroudpage',$GrupoPagina)->count();
            $count_dom = DB::table('w59_dominio')->where('IDgroup',$isInGroup->IDgroup)->count();
            
           if($count_pagesINgroup >= $count_dom && $isInGroup->IDgroup !=  41 ){
                   return "This group already contains the maximum amount of URLs. Please contact an administrator.";
           }
            }
        }

        /// Creo Grupo si la pagina de origen no existe 
        if($GrupoPagina == 0){
            $is = DB::table('w59_groudpage')->where('title',$nameNewGroup)->first();
            
            if($is == NULL){
                $a = DB::table('w59_groudpage')->insert(
                    ['id' => NULL, 'title' => $nameNewGroup , 'IDgroup' => $GrupoDominio]
                );
            }


             $verificar_grupo = DB::table('w59_groudpage')
            ->where('title',$nameNewGroup)
            ->where('IDgroup',$GrupoDominio)
            ->first();
            
            DB::table('w59_page')
                    ->where('id',$ID_Origin)                    
                    ->where('IDdominio',$idDom)                    
                    ->update(['IDgroudpage' => $verificar_grupo->id ]);

             $GrupoPagina = $verificar_grupo->id;
            
        }
        /// Agrego el dominio a grupo si no tienen.
        if($isInGroup->IDgroup == 2){

            DB::table('w59_dominio')
            ->where('url',$CurrentDomain)                     
            ->update(['IDgroup' => $GrupoDominio ]);
        }


        $isInGroup = DB::table('w59_dominio')->where('url',$CurrentDomain)->first();
        // PASO 1 Verificar si el Dominio Esta dentro del grup
       
            //parseo la url para poder extraer el host,slug...etc
            $slug_directory = parse_url("https://www.".$CurrentDomain,PHP_URL_PATH);
            $url_slug = parse_url($url_select,PHP_URL_PATH);
            $url_slug = str_replace($slug_directory, "", $url_slug);;

    
            
            if($isInGroup->IDgroup != 2){   
                /// el dominio si pertenece a un grupo.
                $isPagesGroup = DB::table('w59_page')
                ->where('url',$url_slug)
                ->where('IDdominio',$isInGroup->id)
                ->first();
                if($isPagesGroup == NULL){
                    DB::table('w59_page')->insert(
                        ['id' => NULL, 'title' => $url_title, 'descripcion' => '', 'url' => $url_slug, 'IDdominio' => $isInGroup->id, 'idpost' => $id_post, 'IDgroudpage' => $GrupoPagina , 'last_update' => date('d-m-Y')]
                     );
                }else{
                    DB::table('w59_page')
                    ->where('url',$url_slug)
                    ->where('IDdominio',$isInGroup->id)                    
                    ->update(['title' => $url_title, 'descripcion' => '', 'url' => $url_slug, 'IDdominio' => $isInGroup->id, 'idpost' => $id_post, 'IDgroudpage' => $GrupoPagina , 'last_update' => date('d-m-Y')]);
                }
            }else{
                return "Oops, something went wrong. Please contact an administrator.";
            }
        //    Push Site
            $allDomain_group = DB::table('w59_dominio')->where('IDgroup',$GrupoDominio)->get();
            
            foreach($allDomain_group as $po){
               DB::table('work_queue')->insert(
                ['id' => NULL,'name_work' => 'push_domain' , 'id_base' => $po->id, 'response' => '']
             );

            }

            return "true";
    }


    public function PushSite (Request $request){

        $site = DB::table('work_queue')
        ->where('state',"=", null)
        ->where("response", "=" ,'')->limit(5)->get();
   
        foreach($site as $po){
         //push_domain = actualizar sitios   
        $name_work = $po->name_work;
        $idWork = $po->id;
        $idbase = $po->id_base;

        

            switch ($name_work) {
                case 'push_domain':

                      $IDdominio = DB::table('w59_dominio')->where('id',$po->id_base)->first();

                      DB::table('work_queue')
                      ->where('id_base', $po->id_base)
                      ->update(['response' => "in_progress"]);

                      $url = "https://".$IDdominio->url."/wp-json/kb/v2/worked/14/";

                    //   save log
                    DB::table('logs_kb')->insert(
                        ['id' => NULL,'date' => gmdate("Y-m-d\TH:i:s\Z"), 'url' =>  $url, 'dominio' => $IDdominio->url, 'id_dominio' => $po->id_base, 'accion' => $name_work]
                    );
                    // 
                    try {
                         //code...
                         $start_t = microtime(1);
                         curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
                         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                         curl_setopt($ch, CURLOPT_REFERER, 'erixenmdwcrvuoldumoqyvoyntwnqyevwzjjmkq.io');
                         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                         $response = curl_exec($ch);
                         curl_close($ch);
                         $end_t = microtime(1);
                      if($response == 'true'){
                         DB::table('work_queue')
                         ->where('id', $idWork)
                         ->update(['response' => $response, 'state' => '1']);
                        }
                        $log = LogController::makeLog('Assignment_new_url_Hreflang',  $url, null, $IDdominio->id, ($end_t - $start_t));

                     } catch (\Throwable $th) {
                    //      //throw $th;
                     }
                     
                break;


                case 'get_pages':

                    $IDdominio = DB::table('w59_dominio')->where('id',$po->id_base)->first();

                    DB::table('work_queue')
                    ->where('id_base', $po->id_base)
                    ->update(['response' => "in_progress"]);

                    $get_infoDomain_list =DB::table('w59_page')
                    ->select('w59_page.idpost', 'w59_dominio.url as domainurl')
                    ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id') 
                    ->where('IDdominio', '=',$idbase)->get();

                    $a = json_encode($get_infoDomain_list);
                    $a = json_decode($get_infoDomain_list);
                   
                            $sendata='';
                            $result_Domain = '';
                            if(empty($a)){
                                $sendata = '9999999';
                            }else{
                                 //    /// lleno los contenedores 
                                 foreach($get_infoDomain_list as $ids){
                                    $sendata = $sendata.$ids->idpost.",";
                                }
                                $sendata = json_encode($sendata);
                            }
                            
                        //     //   /// URL de destino
                            $url ="https://".$IDdominio->url."/wp-json/kb/v3/worked/?key=1";

                             //   save log
                                DB::table('logs_kb')->insert(
                                    ['id' => NULL,'date' => gmdate("Y-m-d\TH:i:s\Z"), 'url' =>  $url, 'dominio' => $IDdominio->url, 'id_dominio' => $po->id_base, 'accion' => $name_work]
                                );
                            // 
                            
                            $curl = curl_init();
                            $start_t = microtime(1);
                            curl_setopt_array($curl, array(
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS =>json_encode($sendata),
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: application/json"
                            ),
                            ));
                            
                            $successArray = json_decode(curl_exec($curl));
                            $end_t = microtime(1);

                            $log = LogController::makeLog('Get_pages_unsynchronized',  $url, null, $IDdominio->id, ($end_t - $start_t));


                            if(curl_error($curl)!= ''){
                                $mssg = 'Request Error:' . curl_error($curl);
                            }else{
                                $mssg = "true";
                            }
                            curl_close($curl);
                    
                        if(is_array($successArray)){
                           
                            foreach($successArray as $data){
                             
                                $title = $data->post_title;
                                $date = date('Y');
                                $Formate_title = str_replace('[sc_year]',$date, $title);
                                DB::table('w59_page')->insert(
                                ['id' => NULL, 'title' => $Formate_title, 'descripcion' => '', 'url' => $data->permalink, 'IDdominio' => $idbase, 'idpost' => $data->ID, 'IDgroudpage' => '0' ]
                                );


                                }
                        }
                   
                        DB::table('work_queue')
                        ->where('id', $idWork)
                        ->update(['response' => $mssg, 'state' => '1']);
                break;
            }

            }
            
    }
    public function replace_url (Request $request){

        $successArray = json_decode($request->getContent());
    }
}