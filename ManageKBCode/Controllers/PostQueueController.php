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

class PostQueueController extends Controller
{
    public function loadView(){
        $posts= PostQueue::all();

        return view('post-queue')->with(
            array(
                'posts' => $posts
            )
        );
    }
    //Detectar redirecciones incorrectas https/http/www.
    public function Https_Redirect(){
        $All = Domain::All('url');
        $respuesta = "";
        foreach($All as $po){
        $variaciones = array(
            'http://www.'.$po->url,
            'www.'.$po->url,
            'https://'.$po->url,
            'http://'.$po->url,
        );
            $bad = 0;
            foreach($variaciones as $url){

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_TIMEOUT, '60'); // in seconds
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_NOBODY, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $res = curl_exec($ch);

                if(curl_getinfo($ch)['url'] == $url || curl_getinfo($ch)['url'] == $url."/"){
                  $respuesta.= "<center><h3>Redirection failed</h3></center>"."<br>";
                  $respuesta.=  curl_getinfo($ch)['url']."<br>";
                   
                }else {
                   
                }
            }
           
            }
            return view('cronjob-detect-redirect')->with(
                array(
                    'respuesta' => $respuesta
                )
            );
      }

    public function sendToSandbox(Request $request){
        $posts = $request->input('posts');
        $hub = $request->input('hub');
        $allPosts = PostQueue::whereIn("id",$posts)->get();

       // $url = "https://34spain.kbsandbox.com/wp-json/hiperloop_m/v2/package/";
       // $url = "https://34spain.kaufberater.io/wp-json/hiperloop_m/v2/package/";
       
       $url = "https://spain.kbsandbox.com/wp-json/hiperloop_m/v2/package/";
       $url = "https://sync.kbsandbox.com/wp-json/hiperloop_m/v2/package/";
       $url = "https://".$hub."/wp-json/hiperloop_m/v2/package/";
    //    $url = "https://34spain.kaufberater.io/wp-json/hiperloop_m/v2/package/";

        if(null !== ($request->input("sendToSandbox"))){

            $client = new Client([
                'headers' => [ 'Content-Type' => 'application/json' ]
            ]);
            $body= json_encode($allPosts);
    
            
            $result = $client->post($url, [
                'body' => json_encode($allPosts)
                ]
            );
    
            $successArray = json_decode($result->getBody()->getContents());
            $successPosts = PostQueue::whereIn("id",$posts)->delete();

        }

        if(null !== ($request->input("deleteToList"))){
            $successPosts = PostQueue::whereIn("id",$posts)->delete();
        }
        
        

        $posts= PostQueue::paginate(15);


        return back()->with('info','Articles Sent!!!');
        // return view('post-queue')->with(
        //     array(
        //         'posts' => $posts
        //     )
        // );
    }

    public function addPackages(Request $request){
        $counter =0;
        $totalTransaction=0;
        try {
            $content = json_decode($request->getContent());
            // $totalTransaction= count($content);
            $log = "";
            foreach ($content as $post) {
                $newPostQueue = new PostQueue();
                $newPostQueue->postq_title = $post->post_title;
                $newPostQueue->postq_url = $post->url;
                $newPostQueue->postq_parent = $post->domain;
                $newPostQueue->postq_author = $post->author;
                $newPostQueue->data_author = ($post->author_data == null) ? 'null' : $post->author_data ;
                $newPostQueue->cats = $post->cat;
                $newPostQueue->postq_content = ($post->content);
                $newPostQueue->postq_locale = $post->lang;
                $newPostQueue->postq_state = 1;
                $newPostQueue->postq_name = $post->Site_name;
                $newPostQueue->citations = $post->citations;
                $newPostQueue->state = '0';

                if(isset( $post->produktbezeichnung)){
                $newPostQueue->produktbezeichnung = $post->produktbezeichnung;
                }else{
                $newPostQueue->produktbezeichnung = '';
                }

                if(isset( $post->thumbnail)){
                    $newPostQueue->thumbnail = $post->thumbnail;
                }else{
                    $newPostQueue->thumbnail = "";
                }
                
                if(isset( $post->images)){
                $newPostQueue->images = json_encode($post->images);
                }else{
                    $newPostQueue->images = "";
                }
                
                if($newPostQueue->save()){
                    $counter++;
                }
            }
            

            return response("Exito " .  $counter, 200)
            ->header('Content-Type', 'application/json');
        } catch (Exception $th) {
            return response($th->getMessage(), 500)
            ->header('Content-Type', 'application/json');
        }
     
        return $log;
    }

    function sendQueue_automatic(){
 
        //Estado Actual.
        $estado_actual = KbOption::where('option_name', 'automatic-hiperloop')->first();
        $validacion = $estado_actual['option_value'];
    
        if($validacion == "true"){

        $allPosts = PostQueue::where('state', '=', 0)->take(10)->get();
    
        if(!isset($allPosts[0])) {
            $update = KbOption::where('option_name', 'automatic-hiperloop')->update(['option_value' => 'false']);
           
        }
        $posts = array();

        foreach($allPosts as $post){
            $arr = array($post->id);
            $id = $post->id;
            $update = PostQueue::where('id', $id)
            ->update(['state' => 1]);
            array_push($posts,$arr);
        }
    
        $url = "https://sync.kbsandbox.com/wp-json/hiperloop_m/v2/package/";

        $url = "https://slicedice.kbsandbox.com/wp-json/hiperloop_m/v2/package/";

        

            $client = new Client([
                'headers' => [ 'Content-Type' => 'application/json' ]
            ]);
            $body= json_encode($allPosts);
          
            
            $result = $client->post($url, [
                'body' => json_encode($allPosts)
                ]
            );
    
            $successArray = json_decode($result->getBody()->getContents());
            $successPosts = PostQueue::whereIn("id",$posts)->delete(); 
    }

    }

    function HiperloopAutomatic(){


        $posts= PostQueue::all();

        $state= KbOption::where('option_name','=','automatic-hiperloop')->first();

        return view('hiperloop-automatic')->with(
            array(
                'is_active' => $state->option_value,
                'post'      => $posts,

            )
        );
       
    }
}
