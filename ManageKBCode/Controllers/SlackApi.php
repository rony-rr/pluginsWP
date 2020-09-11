<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SlackApi extends Controller
{

    //  send product notification 
    public function SlackProduct(){

        $arr=[];
        $unavalible = DB::table('expired_products')->get();
        $content_text ="";
        foreach($unavalible as $po){
            $lang = $po->lang;
            $dom = parse_url($po->post, PHP_URL_HOST);

           if($dom != ""){
            if( isset( $arr[$lang][ $dom ] ) ) $arr[$lang][$dom][0] ++;
            else $arr[$lang][ $dom ] = [1];
           }
        }

        foreach($arr as $key => $item){
            
            $productos_count = DB::table('expired_products')
                        ->where('lang', 'like', '%'. $key .'%')
                        ->orderBy('times_seen', 'DESC')
                        ->count();
            
             $content_text = $content_text." ".$productos_count." products not available for ".strtoupper($key)." language | Download CSV file : https://kb.kaufberater.io/public/api/exportcsvproduct?lang=".$key."\n \n"; 
    

    
        }
        $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://hooks.slack.com/services/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",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\n    \"attachments\": [\n{\n\"fallback\": \"Unavailable Products GMC  \",\n\"color\": \"#36a64f\",\n \"text\":\" Products not available - ".date("d/m/Y")."  \n" . ".$content_text." . "\",\n   \"footer\": \"Slack API\",\n \"footer_icon\": \"https://news.images.itv.com/image/file/308797/image_update_img.jpg\",\n \"ts\": 123456789\n }\n]\n}\n",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
        
    }


    // Traer todos los dominios de un droplet
    public function SlackDroplet(Request $request){

        $dom = $request->input('text');
        $user_name = $request->input('user_name');
       
       

        return 'Hey!! '.$user_name .' Eres un error' ;
    }
    
    
    
    // Traer todos los dominios de un droplet
    public function SlackInfoDomain(Request $request){

        $dom = $request->input('text');
        $user_name = $request->input('user_name');
        $data = DB::table('server_kaufberater')->where('site','=',$dom)->first();

        if($data == NULL || $data == "")
        return "Domain entered does not exist.";

        return 'Hey!! '.$user_name .'  '.$dom.' is in the droplet of '.$data->droplet.' - '.$data->server;
    }

    public function savecommetn(Request $request){

        DB::table('temporal_comment')->insert(
            ['id' => NULL, 'domain' => $request->input('dominio'), 'commenti' => $request->input('numero'), 'date'=> date('d-m-Y')] 
        );
    }

    public function save_infokb (Request $request){
       $data = $request->getContent();

        $data = json_decode($data);
        $dom = DB::table('w59_dominio')->where('url','=',$data->domain)->first();
        
        if($dom != null){

                $option_id = DB::table('manages_options')->where('parent_option','=',$dom->id)->where('type_option','=',"clean_cloudflare")->first();

                if($option_id != null){
                    DB::table('manages_options')->where('id_option', '=', $option_id->id_option )->delete();
                }
    
           
           
           DB::table('manages_options')->insert(
                [
                'id_option' => NULL, 
                'name_option' => "cloudflare_api", 
                'parent_option' => $dom->id, 
                'value_option' => serialize($data), 
                'type_option' => "clean_cloudflare"
                ] 
                );
        }
        

    }

    public function DetectPluginGCM (){
        $content_active = "";
        $content_inactive = "\n";

        $arr = array(
            "https://fathersite.fahrradbook.de/",
"https://www.aboutwomen.com.au/",
"https://www.adviesjagers.nl/",
"https://www.allabouther.nl/",
"https://www.allesfuerdaheim.de/",
"https://www.arzneimittelfakten.de/",
"https://www.autobibel.de/",
"https://www.babywissen.com/",
"https://www.babywissen.com/de-at/",
"https://www.backbibel.de/",
"https://www.bluehendergarten.de/",
"https://botanikmeister.de/",
"https://www.cercotech.it/",
"https://www.cocondouillet.fr/",
"https://www.dasschoeneselbst.de/",
"https://www.diehaustierprofis.de/",
"https://www.diehifiberater.de/",
"https://www.dieprofibaecker.de/",
"https://www.dulcehogar.mx/",
"https://www.einrichtungsradar.de/",
"https://www.eisensupplements.de/",
"https://www.eldulcehogar.es/",
"https://www.elternbook.de/",
"https://www.erlesenerwein.de/",
"https://www.fahrradbook.de/",
"https://www.feinkostkenner.de/",
"https://www.fitforbeach.de/",
"https://www.fitforbeach.mx/",
"https://www.fitformoney.de/",
"https://fitformoney.it/",
"https://www.fitformoney.com.br/",
"https://www.fitformoney.es/",
"https://www.fitformoney.mx/",
"https://www.fitformoney.us/",
"https://www.foodlux.de/",
"https://www.fotospring.de/",
"https://www.gartenbook.de/",
"https://www.gartenspring.de/",
"https://www.glamourlux.de/",
"https://www.glamourpilot.com/",
"https://www.glamourpilot.co.uk/",
"https://gourmetminister.de/",
"https://www.grilltiger.de/",
"http://www.guia55.com.br/",
"http://guiadebemestar.com.br/",
"https://guiadesuplementos.mx/",
"https://guiadesuplementos.es/",
"https://guidedusupplement.fr/",
"https://www.gymbibel.de/",
"https://www.haustechnikradar.de/",
"https://www.healthspring.it/",
"https://www.heimkinoheld.de/",
"https://www.hobbylux.de/",
"https://www.kaufberater.io/de-de/",
"https://www.kitchenfibel.de/",
"https://www.kollagenwissen.de/",
"https://www.kreativbibel.de/",
"https://www.kuechenbook.de/",
"https://www.kurkumasupplements.de/",
"https://www.laspheretech.fr/",
"https://www.luftking.de/",
"https://www.magodatecnologia.com.br/",
"https://www.magodecasa.com.br/",
"https://www.medmeister.de/",
"https://www.meisterbob.de/",
"https://www.meistersauber.de/",
"https://www.messerbook.de/",
"https://monederosmart.com/",
"https://www.multivitaminratgeber.de/",
"https://www.musiklux.de/",
"https://www.outdoormeister.de/",
"https://www.petmeister.de/",
"https://platesandwires.com/",
"https://www.reviewbox.com.br/",
"https://www.reviewbox.es/",
"https://www.reviewbox.fr/",
"https://www.reviewbox.it/",
"https://reviewbox.com.mx/",
"https://www.saegebibel.de/",
"https://saudaveleforte.com.br/",
"https://www.schlafbook.de/",
"https://www.sichermeister.de/",
"https://www.sincable.mx/",
"https://www.sportwettenradar.de/",
"https://www.sternefood.de/",
"https://www.superbelle.it/",
"https://www.superbelles.fr/",
"https://supereltern.net/",
"https://www.superguapas.es/",
"https://www.supplementscouts.com/",
"http://supplementbibel.de/",
"https://www.supplementbook.de/",
"https://www.supplementnation.com.au/",
"https://www.supplementnation.ca/",
"https://www.supplementnation.co.uk/",
"https://www.supplementguide.nl/",
"https://sweetesthome.mx/",
"http://sweetesthome.com.br/",
"https://www.sweetesthome.com.au/",
"https://www.sweetesthome.ca/",
"https://www.sweetesthome.co.uk/",
"https://www.technikhiwi.de/",
"https://www.techreviews.com.br/",
"https://www.techspring.mx/",
"http://techspring.com.au/",
"https://www.thegoodestate.com/",
"https://thegoodestate.com/en-au/",
"https://www.travelspring.de/",
"https://www.travelspring.fr/",
"https://www.travelspring.it/",
"https://www.travelspring.com.br/",
"https://www.travelspring.es/",
"https://www.travelspring.mx/",
"https://www.universodelas.com.br/",
"https://www.utileincasa.it/",
"https://www.vitamin-b12-fakten.de/",
"https://www.vitamin-c-fakten.de/",
"https://www.vitamin-d-fakten.de/",
"https://www.welches-magnesium.de/",
"https://www.welches-probiotikum.de/",
"https://www.wellnessbibel.com/",
"https://www.werkzeugpilot.de/",
"https://www.werkzeugradar.de/",
"https://www.wiespartaner.de/",
"https://www.zinksupplements.de/",
"https://www.zonadamas.mx/",
           
        );

        foreach($arr as $po){
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $po."wp-json/kb/v1/detect/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            ));

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if($http_code == 200){
                try {
                $data = unserialize($response);
                $text = "Plugins desactivados de $po \n";
                $plugins_desactivados = "";

                if(is_array($data)){

                        if(count($data) > 0){

                            foreach($data as $po){
                                $plugins_desactivados = $plugins_desactivados . "".$po['Name']." - V".$po['Version']." - Path ".$po['Path']." - status ".$http_code." \n";
                            }
                            $content_inactive = $content_inactive.$text."\n".$plugins_desactivados."\n";

                        }else{
                            $content_inactive = $content_inactive ."Todos los plugins se encuentran Activos - status ".$http_code."\n";
                        }
                }else{

                }
                } catch (\Throwable $th) {
                    $content_inactive = $content_inactive.$po." ERROR BODY DE RESPUESTA : ".strval ($response)." \n Codigo de respuesta ".$http_code."\n"; 
                }
            }else{
                $text = "Error en la conexion con $po";
                $content_inactive = $content_inactive.$text." "."Codigo de respuesta ".$http_code."\n";
            }
        }

        $content_inactive = ($content_inactive == "") ? "Ningun sitio tiene el plugin desactivado" : $content_inactive;


      
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://hooks.slack.com/services/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",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n    \"attachments\": [\n        {\n            \"fallback\": \"GCM Verificacion.\",\n            \"color\": \"#36a64f\",\n            \"text\":\" Analisis de plugins \n" . "$content_inactive" . "\",\n   \"footer\": \"Slack API\",\n            \"footer_icon\": \"https://news.images.itv.com/image/file/308797/image_update_img.jpg\",\n            \"ts\": 123456789\n        }\n    ]\n}\n",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));
    
        $response = curl_exec($curl);
    
        curl_close($curl);

    }

}
