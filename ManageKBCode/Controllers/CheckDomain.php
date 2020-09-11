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
class CheckDomain extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkDomain(Request $request)
    {
        $successArray = json_decode($result->getBody()->getContents());
        
    }

    public function sendCheckDom(Request $request)
    {
        $posts = $request->input('newDom');

        foreach($posts as $po){
            /// Traigo los id de los post del dominio enviado
            $get_infoDomain_list =DB::table('w59_page')
            ->select('w59_page.idpost', 'w59_dominio.url as domainurl')
            ->join('w59_dominio', 'w59_page.IDdominio', '=', 'w59_dominio.id') 
            ->where('IDdominio', '=',$po)->get();


            $sendata='';
            $result_Domain = '';

           /// lleno los contenedores 
          foreach($get_infoDomain_list as $ids){
            $sendata = $sendata.$ids->idpost.",";
            $result_Domain = $ids->domainurl;
          }
          /// URL de destino
          $url ="https://".$result_Domain."/wp-json/kb/v3/worked/?key=1";
         
          try {
            $client = new Client([
                'headers' => [ 'Content-Type' => 'application/json' ]
                ]);
                $body= json_encode($sendata);
            
                
                $result = $client->post($url, [
                    'body' => json_encode($sendata)
                    ]
                );
    
                $successArray = json_decode($result->getBody()->getContents());
          } catch (\Throwable $th){
            $url ="https://www.".$result_Domain."/wp-json/kb/v3/worked/?key=1";
            if($result_Domain != ""){
              $client = new Client([
                  'headers' => [ 'Content-Type' => 'application/json' ]
                  ]);
                  $body= json_encode($sendata);
              
                  
                  $result = $client->post($url, [
                      'body' => json_encode($sendata)
                      ]
                  );
      
                  $successArray = json_decode($result->getBody()->getContents()); 
                }
          }

          
          if(is_array($successArray)){
            foreach($successArray as $data){
                echo "<p>added url : https://www.".$result_Domain.$data->permalink."</p>";
                $title = $data->post_title;
                $date = date('Y');
                $Formate_title = str_replace('[sc_year]',$date, $title);
                 DB::table('w59_page')->insert(
                   ['id' => NULL, 'title' => $Formate_title, 'descripcion' => '', 'url' => $data->permalink, 'IDdominio' => $po, 'idpost' => $data->ID, 'IDgroudpage' => '0' ]
                );
                }
          }
        }
   
          echo "<a href='https://kb.kaufberater.io/public/admin/check-domain'>Return to the previous page</a>";
       
    }
}