<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Group;
use App\Domain;
use Mail;
use App\Mail\generalMailer;
use App\Mail\RedSlug;
use App\Http\Controllers\LogController;
ini_set('max_execution_time', 3000000000000); // Evitar errores de timeout
class SaveController extends Controller
{
     public function curl_load($url){
                curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_REFERER, 'erixenmdwcrvuoldumoqyvoyntwnqyevwzjjmkq.io');
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            }

            public function get_option($name_option,$id_parent,$type_option){

                $get_option= DB::table('manages_options')->select('value_option')
                ->where('parent_option','=',$id_parent)
                ->where('name_option','=',$name_option)
                ->where('type_option','=',$type_option)
                ->first();

                $result = (array)$get_option;
                return end($result);
            }

    //getposttt
    ##################### Acciones de ADMIN-GROUP #########################
    //Envio de datos desde admin-group para crear un nuevo grupo, esto solo crea un grupo, mas no anade dominios.
    //Los datos son enviados desde el modal que despliega el boton de agregar nuevo grupo.
    public function createGroup(){
        ///Create Domain
        $NameGroup=$_POST['newgroup'];
        $Type=$_POST['Type-group'];

        DB::table('w59_group')->insert(
            ['id' => NULL, 'nombre' => $NameGroup, 'type' => $Type]
        );
       return back()->with('info','You added new items, follow next step!');

    }
    ##################### Borrar Grupo Creado // ADMIN-group #########################
    #Envio el ID de Grupo Creado para poder eliminarlo luego de esto voy a eliminar y# 
    #actualizar los sub grupos y paginas ligados a ellos.
        public function deleteGroup($id){
            /// Compruebo que no pertenesca a NoGroup (id = 2)
            if($id!=2){
            #Actualizar Paginas a valor 0 = no pertenece a un grupo/
            $result_Domain= DB::table('w59_page')
            ->select('w59_page.id')
            ->join('w59_groudpage', 'w59_groudpage.id', '=', 'w59_page.IDgroudpage')
            ->where('w59_groudpage.IDgroup','=',$id)
            ->get(); 
            if(count($result_Domain) > 0){
                foreach($result_Domain as $IDPage){
                    DB::table('w59_page')
                    ->where('id', $IDPage->id)
                    ->update(['IDgroudpage' => '0']);
                }
            }

            ////verificar si el grupo es nivel 2.
            $get_Type= DB::table('w59_group')->select('type')->where('id','=',$id)->first();
            if($get_Type->type==2){
                $ArrayDomain= DB::table('w59_dominio')
                ->select('url')->get(); 
                $hey = $ArrayDomain;
            }else{
                $ArrayDomain= DB::table('w59_dominio')
                ->select('url')->where('IDgroup', '=', $id)->get(); 
                $hey = $ArrayDomain;
            }
            
            #Actualizar Dominio a NoGroup con ID 2 como Predeterminado.
            DB::table('w59_dominio')->where('IDgroup','=', $id)->update(['IDgroup' => '2']);
            #Eliminar Grupos de pagina
            DB::table('w59_groudpage')->where('IDgroup', '=', $id)->delete();
            #Borrar Grupos
            DB::table('w59_group')->where('id', '=', $id)->delete();

            foreach($hey as $po){
                $url ="https://www.".$po->url."/wp-json/kb/v2/worked/14/";
                $this->curl_load($url);
            }
           return back()->withInput();
            
        }
        }

        ##################### Agregar Dominios a un  Grups  // ADMIN-group #########################
        public function addDomain(){
        
        $IDdomain_post=$_POST['g'];
        $idgroup=$_POST['h'];

        if(isset($IDdomain_post) && isset($idgroup)){

            foreach($IDdomain_post as $idDomain){
            
            DB::table('w59_dominio')
                ->where('id', $idDomain)
                ->update(['IDgroup' => $idgroup]);

            
            //// traer todos los post de este dominio.
                $get_Domain =DB::table('w59_dominio')->where('id', '=', $idDomain)->first();
                $result_Domain=$get_Domain->url;
                
            $url ="https://www.".$result_Domain."/wp-json/kb/v2/worked/12/";

                @$json = json_decode($this->curl_load($url), true);
    
        if($json != NULL){
        foreach($json as $po){
            $dateSAVE= date('d-m-Y');
            $hoy= date('Y');
            $dataTItle=$po['post_title'];
        $title_post = str_replace("[sc_year]", $hoy, $dataTItle);
        $Verification_URL = DB::table('w59_page')->select('id')->where('IDdominio','=',$idDomain)->where('idpost','=',$po['ID'])->get();


        if($Verification_URL == NULL || count($Verification_URL)==0){
      
            DB::table('w59_page')->insert(
                ['id' => NULL, 'title' => $title_post, 'descripcion' => '', 'url' => $po['permalink'], 'IDdominio' => $idDomain, 'idpost' => $po['ID'], 'IDgroudpage' => '0', 'last_update' => $dateSAVE ]
            );
        }
        }
        }
        }
       return back()->withInput();
        }
    }

    /////Esta funcion puede quitar dominios y eliminar groupPages de un Grupo estos datos se envia en array desde el admin-group
    public function actiongroup (){
        @$arrayDomain = isset($_POST['delD'])?  $_POST['delD'] : false;
        @$arrayGroup =  isset($_POST['valor'])?  $_POST['valor'] : false;
        @$arrayGroupType =  isset($_POST['typeValor'])?  $_POST['typeValor'] : false;

       
      foreach($arrayDomain as $IDdom){
          /// Cambio Dominio A no group
            DB::table('w59_dominio')
            ->where('id', $IDdom)
            ->update(['IDgroup' => '2']);

         // Cambio Paginas a sin grupo
            DB::table('w59_page')
            ->where('IDdominio','=', $IDdom)
            ->update(['IDgroudpage' => '0']);
      }
    
        $ArrayDOM= DB::table('w59_dominio')
        ->select('url')->whereIn('id',$arrayDomain)->get(); 
        foreach($ArrayDOM as $po){
            if($arrayGroupType==2){
                $GetURL= DB::table('w59_dominio')
                ->select('url')->get(); 
              
            }else{
                $GetURL= DB::table('w59_dominio')
                ->select('url')->where('IDgroup','=',$arrayGroup)->get(); 
               
            }
          

             foreach($GetURL as $pa){
              $url ="https://www.".$pa->url."/wp-json/kb/v2/worked/14/";
                $this->curl_load($url);
              
             } 
             $url ="https://www.".$po->url."/wp-json/kb/v2/worked/14/";
            $this->curl_load($url);
        }
       return back()->withInput();

        }



          //// Borrar cache de sitios seleccionados, estos datos viene del archivo cloud-flare.blade
          public function cloudFlarePost(){
            #////////#///////////#////////#
              @$Site = $_POST['CloudSite'];
              @$is_All = $_POST['isAll'];
              $array=array();
              $c=1;
              $start_t = microtime(1);
              if($is_All == "allDomian" ){
                $All = Domain::All();
               
                foreach($All as $po){
                    // all domain
                    $data = $this->get_option("cloudflare_api",$po->id,"clean_cloudflare");
                    if($data != false){

                        $arr = unserialize($data);
                        // send cloudflare peticion

                        $zoneId = $arr->setting->cloudflare_zone_id;
                        $authEmail= $arr->setting->cloudflare_email;
                        $authKey= $arr->setting->cloudflare_api_key;
                        $domain = $po->url;

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "DELETE",
                        CURLOPT_POSTFIELDS =>"{\"purge_everything\":true}",
                        CURLOPT_HTTPHEADER => array(
                            "Content-Type: application/json",
                            "X-Auth-Key: {$authKey}",
                            "X-Auth-Email: {$authEmail}"
                        ),
                        ));

                        $response = json_decode(curl_exec($curl),true);
                        
                        curl_close($curl);
                        $urlView = "https://www.".$po->url;
                        $result_purge = ($response['success'] == true) ? '<input type="checkbox" checked> ' : " ERROR -";
                        echo '<a href="'.$urlView .'" target="blank_">'.$result_purge."".$po->url.'</a><br>';
                    }
                }
                }
              if($Site  != '' && $is_All == null){
                /// select domain
                $Faind = Domain::find($Site);

                foreach($Faind as $po){
                    // single domain
                    $data = $this->get_option("cloudflare_api",$po->id,"clean_cloudflare");
                    if($data != false){

                        $arr = unserialize($data);
                        // send cloudflare peticion

                        $zoneId = $arr->setting->cloudflare_zone_id;
                        $authEmail= $arr->setting->cloudflare_email;
                        $authKey= $arr->setting->cloudflare_api_key;
                        $domain = $po->url;

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "DELETE",
                        CURLOPT_POSTFIELDS =>"{\"purge_everything\":true}",
                        CURLOPT_HTTPHEADER => array(
                            "Content-Type: application/json",
                            "X-Auth-Key: {$authKey}",
                            "X-Auth-Email: {$authEmail}"
                        ),
                        ));

                        $response = json_decode(curl_exec($curl),true);
                        
                        curl_close($curl);
                        $urlView = "https://www.".$po->url;
                        $result_purge = ($response['success'] == true) ? '<input type="checkbox" checked> ' : " ERROR -";
                        echo '<a href="'.$urlView .'" target="blank_">'.$result_purge."".$po->url.'</a><br>';
                    }
                 
                }
              }
              $end_t = microtime(1);
              $log = LogController::makeLog('Clean_Cache_All_site',  "https://api.cloudflare.com/client/v4/zones/", $response, '223', ($end_t - $start_t));

              echo "<p><a href='https://kb.kaufberater.io/public/admin/cloud-flare' style='background: #4b646f;
        padding: 10px;
        font-weight: bold;
        color: #FFF;
        text-decoration: none;
        margin-top: 30px; border-radius:10px;'>Return to previous page</a></p>";
          }

          #### Borrar Paginas de Grupos creados.
          public function DeletePages($id){
        
        /// take out of the group
        $porciones = explode(".", $id);
        $idpage = $porciones[0]; 
        $idgroup = $porciones[1]; 
        $domain = $porciones[2]; 
        $domain=base64_decode($domain);
        /// Update page for not group
        DB::table('w59_page')
        ->where('id', $idpage)
        ->update(['IDgroudpage' => '0']);
        
        ///Send Update 
        $result_Domain= DB::table('w59_groudpage')
                ->select('w59_groudpage.id','w59_dominio.url as urldomain', 'w59_dominio.id as idDOm')
                ->join('w59_dominio', 'w59_dominio.IDgroup', '=', 'w59_groudpage.IDgroup')
                ->where('w59_groudpage.id','=',$idgroup)
                ->get(); 
               
                $urlPRIN ="https://www.".$domain."/wp-json/kb/v2/worked/14/";
           
                 @$json = $this->curl_load($urlPRIN);   

               
                 foreach($result_Domain as $getdom){
                     $url ="https://www.".$getdom->urldomain."/wp-json/kb/v2/worked/14/";

                     DB::table('work_queue')->where('id_base', '=', $getdom->idDOm)->delete(); 
                     
                     DB::table('work_queue')->insert(
                        ['id' => NULL,'name_work' => 'push_domain' , 'id_base' => $getdom->idDOm, 'response' => '']
                     );
                
                    // @$json = $this->curl_load($url); 
                 }   
    
                 return back()->withInput();
          }

    ######################### Guardar Nuevos Grupos de Pagina #################
    function SavePages(){
        ////save page in group
    $pageGroup = $_POST['groupPP'];
    $idpage=$_POST['newpageGp'];
    $idgrouPAge=$_POST['namepages'];
    $titlenewgroup=$_POST['nametile']; 
    $count=[];
   
    //
    
    if(isset($idpage)){
        // Check if the domain exists
    
        if($idgrouPAge=='NG'){
            /// need create new group
            DB::table('w59_groudpage')->insert(
                ['id' => NULL, 'IDgroup' => $pageGroup, 'title'=> $titlenewgroup]
            );  
            $idgrouPAg = DB::table('w59_groudpage')
            ->select('id')
            ->orderBy('id', 'desc')
            ->first();
            $idgrouPAge = $idgrouPAg->id;
            }
       
    
        if(isset($idpage)){
  
            if($titlenewgroup==""){
                $get_group =DB::table('w59_groudpage')
                ->where('id', '=', $idgrouPAge)
                ->first(); 
            }else{
        $get_group =DB::table('w59_groudpage')
        ->where('title', '=', $titlenewgroup)
        ->orwhere('id', '=', $pageGroup)
        ->first(); 
        }
        $idGroup_RESUlt=$get_group->id;
    
        foreach($idpage as $po){
            ////Insert Group Page
            DB::table('w59_page')
            ->where('id', $po)
            ->update(['IDgroudpage' => $idGroup_RESUlt]);
    
    
            //////Send WP INFO
            
            $result_Domain= DB::table('w59_groudpage')
            ->select('w59_groudpage.id','w59_dominio.url as urldomain', 'w59_dominio.id as dominioid' )
            ->join('w59_dominio', 'w59_dominio.IDgroup', '=', 'w59_groudpage.IDgroup')
            ->where('w59_groudpage.id','=',$idgrouPAge)
            ->get(); 
         
              foreach($result_Domain as $pa){            
                    $count[]= $pa->dominioid;
              } 
          
        }
        foreach(array_unique($count) as $idpush){
            DB::table('work_queue')->where('id_base', '=', $idpush)->delete(); 
            DB::table('work_queue')->insert(
                ['id' => NULL,'name_work' => 'push_domain' , 'id_base' => $idpush, 'response' => '']
             );
        }
    
        
    }
    
    }
    return back()->withInput();
    }
    ############################# ENVIO DE INFORMACION A SITIOS WORDPRESS ##############################
    public function installWpSendData($id){
           /// $id = Domain        
           $get_domain= base64_decode($id);
        
           $get_infoDomain =DB::table('w59_dominio')->where('url', '=',$get_domain)->first();
           $idgroup=$get_infoDomain->IDgroup;

           $getInfoGroup =DB::table('w59_group')->where('id', '=',$idgroup)->first();
            if($getInfoGroup->type==2){
                $get_grouppage =DB::table('w59_groudpage')
                ->get();
        }else{
            // $get_grouppage =DB::table('w59_groudpage')
            // ->where('IDgroup', '=', $idgroup)
            // ->get(); 

              $get_grouppage =DB::table('w59_groudpage')
                ->get();
        }
          $array= array();
           foreach( $get_grouppage as $idG){
              // $get_page =DB::table('w59_page')->where('IDgroudpage', '=', $idG->id)->get();
              $get_page = DB::table('w59_page')
               ->select('w59_page.url', 'w59_page.id as pageid', 'w59_page.title','w59_page.idpost','w59_dominio.idioma','w59_dominio.url as domainurl','w59_page.IDgroudpage')
                ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id')   
                ->where('IDgroudpage', '=', $idG->id)        
               ->get();
               $array=$get_page->merge($array);
           }      
        // var_dump($array);
        return json_encode($array);

    }

    ################## CAptura de Informacion de un sitio al ser instalado un Plugin ###########################
    public function installWpInfo($id){

        $porciones = explode(":", $id);
        $dominio = $porciones[0]; // porción1
        $dominio= base64_decode($dominio);
        $name    =  $porciones[1]; // porción2
        $language    =  $porciones[2]; // porción3
        $language = str_replace('_','-',$language);
        $exist =DB::table('w59_dominio')->where('url', '=', $dominio)->get();
        
        if(count($exist)==0){
          DB::table('w59_dominio')->insert(
              ['id'=> NULL, 'nombre' => $name , 'idioma' => $language, 'url' => $dominio, 'IDgroup' => '2']
          );         
      }  


      $idDom = DB::table('w59_dominio')->where('url',$dominio)->first();

      /// lo agrego a la cola de Trabajo
      DB::table('work_queue')->insert(
        ['id' => NULL,'name_work' => 'get_pages' , 'id_base' => $idDom->id, 'response' => '']
     );

      /// get all pages

//       $get_infoDomain_list =DB::table('w59_page')
//       ->select('w59_page.idpost', 'w59_dominio.url as domainurl')
//       ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id') 
//       ->where('IDdominio', '=',338)->get();

//             $sendata='';
//             $result_Domain = '';
//             if($get_infoDomain_list == NULL){
//                 //    /// lleno los contenedores 
//                 foreach($get_infoDomain_list as $ids){
//                     $sendata = $sendata.$ids->idpost.",";
//                 }
//                 $sendata = json_encode($sendata);
//             }else{
//                     $sendata = '9999999';
//             }
//             //   /// URL de destino
//             $url ="https://".$dominio."/wp-json/kb/v3/worked/?key=1";

//             $curl = curl_init();

//             curl_setopt_array($curl, array(
//               CURLOPT_URL => $url,
//               CURLOPT_RETURNTRANSFER => true,
//               CURLOPT_ENCODING => "",
//               CURLOPT_MAXREDIRS => 10,
//               CURLOPT_TIMEOUT => 0,
//               CURLOPT_FOLLOWLOCATION => true,
//               CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//               CURLOPT_CUSTOMREQUEST => "POST",
//               CURLOPT_POSTFIELDS =>json_encode($sendata),
//               CURLOPT_HTTPHEADER => array(
//                 "Content-Type: application/json"
//               ),
//             ));
            
//             $successArray = curl_exec($curl);
            
//             curl_close($curl);
// var_dump($successArray);
//           if(is_array($successArray)){
//             foreach($successArray as $data){
//                 echo "<p>added url : https://www.".$result_Domain.$data->permalink."</p>";
//                 $title = $data->post_title;
//                 $date = date('Y');
//                 $Formate_title = str_replace('[sc_year]',$date, $title);
//                  DB::table('w59_page')->insert(
//                    ['id' => NULL, 'title' => $Formate_title, 'descripcion' => '', 'url' => $data->permalink, 'IDdominio' => $po, 'idpost' => $data->ID, 'IDgroudpage' => '0' ]
//                 );
//                 }
//           }
   
    }
    public function WpDeleteGroupPages($id){

                $result_Domain= DB::table('w59_groudpage')
                ->select('w59_dominio.url', 'w59_dominio.id' )
                ->join('w59_page', 'w59_groudpage.id', '=', 'w59_page.IDgroudpage')
                ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id')
                ->where('w59_groudpage.id','=',$id)
                ->get();
                $clean_dom = [];

                foreach($result_Domain  as $po){
                    $clean_dom[] = $po->id;
                   }

                DB::table('w59_page')
                    ->where('IDgroudpage', $id)
                    ->update(['IDgroudpage' => '0']);

                DB::table('w59_groudpage')->where('id', '=', $id)->delete(); 

                   foreach($clean_dom as $pa){

                        DB::table('work_queue')->where('id_base', '=', $pa)->delete(); 

                        DB::table('work_queue')->insert(
                            ['id' => NULL,'name_work' => 'push_domain' , 'id_base' => $pa, 'response' => '']
                        );

                   }
               return back()->withInput();
    }

    public function rmGroup(){
        @$arrayGroup =  isset($_POST['delG'])?  $_POST['delG'] : false;

        foreach($arrayGroup as $IDdom){
                $id= $IDdom;
            /// Obtengo las url de todos los dominios afectados.
            $result_Domain= DB::table('w59_groudpage')
            ->select('w59_dominio.url')
            ->join('w59_page', 'w59_groudpage.id', '=', 'w59_page.IDgroudpage')
            ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id')
            ->where('w59_groudpage.id','=',$id)
            ->get();
            $hey = $result_Domain;
            /// Url que estan dentro de grupo pasan a ser igual 0 (sin grupo)
            DB::table('w59_page')
                ->where('IDgroudpage', $id)
                ->update(['IDgroudpage' => '0']);

            DB::table('w59_groudpage')->where('id', '=', $id)->delete(); 

           foreach($hey  as $po){
            $url ="https://www.".$po->url."/wp-json/kb/v2/worked/14/";
             
                  $this->curl_load($url);
           }

        }
        return back()->withInput();
    }

    public function deleteDOM($id){
        $arrayDomain = base64_decode($id);

        $IDGroup= DB::table('w59_dominio')
        ->select('IDgroup as groupo','id')->where('url','=',$arrayDomain)->first(); 
        
        $idGrupo =  $IDGroup->groupo;
        $idDomain =  $IDGroup->id;

          /// Eliminar Dominio.
           DB::table('w59_dominio')->where('url', '=', $arrayDomain)->delete();

         //// Eliminar pagina
           DB::table('w59_page')->where('	IDdominio', '=', $idDomain)->delete();
     
          if($idGrupo != 2 ){
          $GetURL= DB::table('w59_dominio')
          ->select('url')->where('IDgroup','=',$idGrupo)->get(); 
           foreach($GetURL as $po){
            $url ="https://www.".$po->url."/wp-json/kb/v2/worked/14/";
              $this->curl_load($url);
           }
            }
        return back()->withInput();
    }

    public function startjob (){
        $getstart = DB::table('w59star')->get();

    return json_encode($getstart);
    }

    public function portada($id){
        $getstart = DB::table('w59_dominio')
        ->select('url','idioma')
        ->whereRaw("IDgroup = (SELECT IDgroup from w59_dominio WHERE url = '".$id."') and IDgroup <> 2")
        ->get();

        return json_encode($getstart);
      }

      public function StartLog($id){
        $data = base64_decode($id);
       
        $data = explode("@", $data);
    
        $total_average=$data[0];
        $total_us=$data[1];
        $total_score=$data[2];
        $SERVER=str_replace('www.', '', $data[3]);
        $idpost=$data[4];
        $title=$data[5];
        @$urlpost=$data[6];
        if($urlpost == ''){ $urlpost='--';}
    
        $exists_start = DB::table('w59star')
        ->select('dominio','idpost')
        ->where('dominio','=',$SERVER)
        ->where('idpost','=',$idpost)
        ->first(); 
        
        if($exists_start == null){
        DB::table('w59star')->insert(
          ['id'=> NULL, 'total_average' => $total_average , 'total_us' => $total_us, 'total_score' => $total_score, 'dominio' => $SERVER, 'idpost' => $idpost, 	'title' => $title, 'url' => $urlpost]
           );
          }else{
            ///update post 
            DB::table('w59star')
            ->where('dominio','=',$SERVER)
            ->where('idpost','=',$idpost)
                ->update(['total_average' => $total_average , 'total_us' => $total_us, 'total_score' => $total_score, 'dominio' => $SERVER, 'idpost' => $idpost, 	'title' => $title, 'url' => $urlpost]);
           }
    
      }

      public function checkDom(){
 
    $pts=$_POST['newDom'];
    
  
    foreach($pts as $po){
      $get_infoDomain_list =DB::table('w59_page')
          ->select('w59_page.idpost', 'w59_dominio.url as domainurl')
          ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id') 
          ->where('IDdominio', '=',$po)->get();
          $sendata='';
          $result_Domain = '';
        foreach($get_infoDomain_list as $ids){
          $sendata = $sendata.$ids->idpost.",";
          $result_Domain = $ids->domainurl;
        }
       
       //$sendata = base64_encode("(".$sendata."0)");
      //$sendata = base64_encode("(0,1)");
      $url ="https://www.".$result_Domain."/wp-json/kb/v2/worked/16?code=".$sendata;
     $json = json_decode($this->curl_load($url),true);

     if($json!=NULL){
   
     foreach($json as $data){
     echo "<p>added url : https://www.".$result_Domain.$data['permalink']."</p>";
     $title = $data['post_title'];
     $date = date('Y');
     $Formate_title = str_replace('[sc_year]',$date, $title);
      DB::table('w59_page')->insert(
        ['id' => NULL, 'title' => $Formate_title, 'descripcion' => '', 'url' => $data['permalink'], 'IDdominio' => $po, 'idpost' => $data['ID'], 'IDgroudpage' => '0' ]
     );
     }
    }
    }
  
    if(is_countable($json)==0){
      echo "<p>All urls are on the server...</p>";
    }
    echo "<a href='https://kb.kaufberater.io/public/admin/check-domain'>Return to the previous page</a>";
    }

    
    public function SavePol(){
       ?> <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script><?php  
     $name_policy= $_POST['namepolicy'];
     $type_policy= $_POST['typepolicy'];
     $lang_policy= $_POST['langpolicy'];
     $content_policy= $_POST['contentpolicy'];
     @$id_policy= $_POST['idpage'];
     $array= array();
    
     if(isset($name_policy)&& isset($type_policy) && isset($lang_policy)){
        if($id_policy==''){
            DB::table('w59_TypePoli')->insert(
                ['id' => NULL, 'content' => $content_policy, 'lang' => $lang_policy, 'type' => $type_policy, 'title' => $name_policy]
            );
       
        }else{
            DB::table('w59_TypePoli')
                    ->where('id', $id_policy)
                    ->update(['content' => $content_policy, 'lang' => $lang_policy, 'type' => $type_policy, 'title' => $name_policy]);
        }
    }
    
    //// end process update or insert
    
    ///send alert all site
        $MIN_idpolicy=strtolower($lang_policy);
        $result_Domain = DB::table('w59_dominio')
        ->select('url')
        ->orWhere('idioma', 'like', '%' . $MIN_idpolicy . '%')
        ->where("flag_domain", "<>", "4")
        ->get();
       

        foreach($result_Domain as $senurl){
             $urlPRIN ="https://www.".$senurl->url."/wp-json/kb/v2/worked/15/";
             $urlDom="https://www.".$senurl->url."/".$name_policy."/";
            echo "<a href='".$urlDom."' target='_blank'>".$urlDom."</a><br>";
             // Get cURL resource
             
            $this->curl_load($urlPRIN);

        }
        echo "<p><a href='https://kb.kaufberater.io/public/admin/site-policies' style='background: #4b646f;
        padding: 10px;
        font-weight: bold;
        color: #FFF;
        text-decoration: none;
        margin-top: 30px; border-radius:10px;'>Return to previous page</a></p>";
        
    
     ///redirect page
      //return redirect()->route('site-policies');  
    } 
    public function PolicyWpInfo($id){
                 
        $get_domain= base64_decode($id);
        
        $get_infoDomain =DB::table('w59_dominio')->select('idioma')->where('url', '=',$get_domain)->first();
        $idioma=$get_infoDomain->idioma;
      
        ///divide string
        $getcodelang = explode("-", $idioma);
        $langCode = $getcodelang[0]; // lang code
        $get_alldomain =DB::table('w59_TypePoli')->orWhere('lang', 'like', '%' . $langCode . '%')->get();         

 
 return json_encode($get_alldomain);

    }


	
    
    /// check url Insert/Delete/update
public function checkurl($id){
    $fecha_actual = date('Y-m-d');
     $data = base64_decode($id);
     $dataPART = explode("@", $data);
     $idpost = $dataPART[0];
     $domain = $dataPART[1];
     $url = $dataPART[2];
     $save_group_select = $dataPART[3];
     $save_name_group =$dataPART[4];
     $save_group = $dataPART[5];
     $title = $dataPART[6];
     

     # validando grupo
     if($save_group_select !='false'){
         $include =  $save_group_select;
     }else{
        $include =0;
     }
     #validando new group
     if(($save_name_group != 'false') && ($save_group != 'false')){
        // DB::table('w59_groudpage')->insert(
        //     ['id' => NULL, 'IDgroup' => $pageGroup, 'title'=> $save_name_group]
        // );    
     }

     //echo $data;
     $get_infoDomain =DB::table('w59_dominio')->select('id','url', 'IDgroup')->where('url', '=',$domain)->first();
    /// search url in data base
    $get_page = DB::table('w59_page')
    ->select('w59_page.url', 'w59_page.id as pageid', 'w59_page.url as pageurl','w59_page.idpost','w59_dominio.idioma','w59_dominio.url as domainurl','w59_page.IDgroudpage')
     ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id') 
     ->where('w59_dominio.url', '=', $domain)        
     ->where('w59_page.idpost', '=', $idpost)        
     ->first();
    
    if($get_page == NULL){
      
      if($get_infoDomain->IDgroup != 2 ){
         DB::table('w59_page')->insert(
           ['id' => NULL, 'title' => $title, 'descripcion' => $data, 'url' => $url, 'IDdominio' => $get_infoDomain->id, 'idpost' => $idpost, 'IDgroudpage' => $include , 'last_update' => $fecha_actual ]
        );
        
          }
  
          
    }else{
      if($get_page->pageurl != $url){
          
          function curl_load($url){
              curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              $response = curl_exec($ch);
              curl_close($ch);
              return $response;
          }
      /// Update url  and title 
      DB::table('w59_page')
              ->where('id', $get_page->pageid)
              ->update(['url' => $url, 'title' => $title, 'IDgroudpage' => $include]);
            }
            $get_infoDomain_list =DB::table('w59_page')
            ->select('w59_page.idpost','w59_page.url as pageurl','w59_dominio.url as domainurl')
            ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id') 
            ->where('IDgroudpage', '=',$get_page->IDgroudpage)->get();
         
            foreach($get_infoDomain_list as $data){
              $t=base64_encode($title);
              $u=base64_encode($url);
               $str="id=".$idpost."&&dom=".$domain."&&t=".$t."&&u=".$u;
               $urls ="https://".$data->domainurl."/wp-json/kb/v2/worked/112312?".$str;
               @$json = json_decode(curl_load($urls),true);
            }
            
            
    }
    }
    public function dellpost($id){
        $data = base64_decode($id);
        $dataPART = explode("@", $data);
        $idpost = $dataPART[0];
        $domain = $dataPART[1];
        $get_infoDomain =DB::table('w59_dominio')->select('id','url')->where('url', '=',$domain)->first();
        if($get_infoDomain!=NULL){
          DB::table('w59_page')->where('idpost', '=', $idpost)->where('IDdominio', '=', $get_infoDomain->id)->delete();
        }
      }

     

    //   Verificar Si Existen Slug Repetidos en Wordpress
    public function RedSlug(){
        $All = Domain::All('url');
        $o=0;
        foreach($All as $po){
            $url = "https://www.".$po['url']."/wp-json/kb/v2/worked/21";
            $content = json_decode($this->curl_load($url),true);

            if($content==NULL || $content == ""){ 
                $content = array();
            }else{
                $content = json_decode($content,true);
            }

           

           if(! empty($content)){
            $titles_po = str_replace("[sc_year]", date('Y'), $content[0]['title']);
            $content_END[] = array(
                'title' => $titles_po,
                'url'  => $content[0]['url']
            );
           }

        }

        $to = [
		
			['email' => 'florian.felsing@googlemail.com', 'name' => 'Florian Felsing'],
			['email' => 'danielgomezdvalle@gmail.com', 'name' => 'Daniel Gomez'],
			['email' => 'dennisalvarado89@gmail.com ', 'name' => 'Dennis Alvarado'],
		
        ];
      
           Mail::to($to)->send(new RedSlug($content_END));
    
    }
  
      public function Testing(){
      $get_page = DB::table('w59_page')
     ->select('w59_page.url', 'w59_page.id as pageid', 'w59_page.state', 'w59_page.url as pageurl','w59_page.idpost','w59_dominio.idioma','w59_dominio.url as domainurl','w59_page.IDgroudpage')
     ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id')  
         ->get();
     $r=0;
             foreach($get_page as $po){
             
                $url ="https://www.".$po->domainurl.$po->pageurl;
            echo $url."<br>";

            if($r++ == 299){
                $r=0;
            }
             }
     }

     public function ccallsite(){
        $All = Domain::All('url');
               
        foreach($All as $po){

            $url = "https://www.".$po->url."/wp-json/kb/v2/worked/92817461811/";
            
            $urlView = "https://www.".$po->url;
           
            $this->curl_load($url);
        }

        return "Mision Cumplida";
     }

     public function verificarPage($id){
        $porciones = explode(".", $id);

        $idgroup = $porciones[0]; 
        $domain = $porciones[1]; 
        $domain=base64_decode($domain);
        $url = "https://www.".$domain."/wp-json/kb/v2/worked/14/";
            
       
        $this->curl_load($url);
        return redirect('admin/group-pages?group='.$idgroup)->with(['message' => "Url verified successfully.", 'alert-type' => 'success']);

     }

     public function GetAllCat($id){
        if(isset($_GET["g"])){
            $get_page = DB::table('w59_group')
            ->select('nombre','id')
            ->where('type', 1)
            ->get();
        }else{
        header('Content-Type: application/json');
        $get_page = DB::table('w59_dominio')
        ->select('w59_dominio.IDgroup as IdGrupo', 'w59_group.nombre as nombreGrupo', 'w59_group.type as type', 'w59_groudpage.title as NameSub', 'w59_groudpage.id as idSub')
        ->join('w59_group', 'w59_dominio.IDgroup', '=', 'w59_group.id')  
        ->join('w59_groudpage', 'w59_group.id', '=', 'w59_groudpage.IDgroup')  
        ->where('w59_dominio.url',$id)
        ->get();
    }
     }

     public function ApiDom($id){
        if($id == "NOLANG"){
            $get_page = DB::table('w59_dominio')
            ->select('url', 'nombre')
            ->get();
        }else{
            $get_page = DB::table('w59_dominio')
            ->select('url', 'nombre')
            ->where('idioma', 'like',"%".$id."%")
            ->get();
        }
        echo json_encode($get_page);
     }

     public function republish_post(){
        $All = Domain::All('url');
        foreach( $All as $var_key)
        {
            $url = "https://www.".$var_key->url."/wp-json/kb/v2/worked/9805/";
            return $this->curl_load($url);
        }
     }

     public function traking_ID(){

        $All = Domain::where('idioma','=','de-DE')->get();
               
        foreach($All as $po){

            $url = "https://www.".$po->url."/wp-json/tracking/v3/tracking-id/";
           $this->curl_load($url);
        }

        
     }

      //  Clean Group Controller

    public function CleanGroup ($id){
        $all_group = DB::table('w59_groudpage')->get();
  
        $dom = []; /// agregar a la cola para actualizar su hreflang.
  
        foreach($all_group as $data){
  
          $langs_duplicate = DB::table('w59_dominio')
          ->select('w59_dominio.url as urldom', 'w59_dominio.idioma as lang', 'w59_page.IDdominio as Domain', 'w59_page.id as idpages')
          ->join('w59_page', 'w59_page.IDdominio', '=', 'w59_dominio.id')
          ->where('idgroudpage','=',$data->id)
          ->get();
  
          $articulos = [];
          
  
          foreach($langs_duplicate as $po){
          $articulos[] = $po->idpages."@".$po->Domain;          
          }
  
          /// Verifico si tiene mas de 1 entrada y borro el grupo y actualizo las paginas a estado 0;
          if(count($articulos) <= 1){
            
              foreach($articulos as $idpages){
                  $part = explode('@', $idpages);
                  $idpagina = $part[0];
                  $iddom = $part[1];
  
                  /// guardo los dominios para hacer el push en hreflang
                  $dom[] = $iddom;
  
                  // actualizo las entradas a cero
                  DB::table('w59_page')
                  ->where('id', $idpagina)
                  ->update(['IDgroudpage' => '0']);
  
              }
  
              /// borro el grupo de paginas 
               DB::table('w59_groudpage')->where('id', '=', $data->id)->delete();
  
          }
  
        }
  
        $pushdom = array_values(array_unique($dom));
       
        foreach($pushdom as $addworke){
  
          DB::table('work_queue')->insert(
              ['id' => NULL,'name_work' => 'push_domain' , 'id_base' => $addworke, 'response' => '']
           );
  
        }
  
        return redirect('admin/group-pages?group='.$id)->with(['message' => "Success! Groups with less than 2 entries have been eliminated.", 'alert-type' => 'success']);

      }



      public function duplicateurl(){
        $langs_duplicate = DB::table('w59_page')->get();

        foreach($langs_duplicate as $po){
            $duplicate = DB::table('w59_page')->where('title','=',$po->title)->where("IDdominio",'=',$po->IDdominio)->get()->toArray();
           
          if(count($duplicate) > 1){
            var_dump($duplicate);
            die;
          }
          
        }
      }
    ######################### END ACCIONES DE ADMIN GROUP #################
}