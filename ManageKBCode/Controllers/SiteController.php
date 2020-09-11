<?php

namespace App\Http\Controllers;

use App\Http\Controllers\LogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Group;
use App\Domain;
use App\KB_Post;
use App\KB_PostProduct;
use App\KB_Product;
use App\KB_ProductExtraData;
use App\KB_StoreProduct;
use App\KB_Store;
use App\KbOption;
use Mail;
use App\Mail\generalMailer;
use App\Mail\AlertRating;
use App\Mail\RedSlug;
use DateTime;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
//

ini_set('max_execution_time', 0); // Evitar errores de timeout
class SiteController extends Controller
{

    public function loadView(Request $request)
    {
        $all_sites = Domain::orderBy('state', 'desc')->get();
        return view('all-site-list')->with(
            array(
                'all_sites' => $all_sites
            )
        );
    }

    public function listProducts(Request $request, $post_id)
    {
        $myPost = KB_Post::find($post_id);
        return view('all-site-list-product')->with(
            array(
                'myPost' => $myPost
            )
        );
    }


    // PROCESO 0
    function ajaxRequestPostsGetNext(Request $request)
    {
        try {
            $input = $request->all();
            $currentSite = Domain::where("id", ">", $request->last_id)->where("state", "=", "1")->first();
            $nextSite = null;
            if ($currentSite != null) {
                $nextSite = Domain::where("id", ">", $currentSite->id)->where("state", "=", "1")->first();
                //* Check & Update Shop Data

                $response["last_id"] = $currentSite->id;
                $response["url"] = $currentSite->url;

                if ($nextSite != null) {
                    $response["nextURL"] =  $nextSite->url;
                }
            } else {
                $response["last_id"] = -1;
                $response["url"] = null;
            }

            return response()->json($response);
        } catch (Exception $ex) {

            $response["ex"] = $ex->getMessage();
            $response["url"] = $ex->getMessage();
            return response()->json($response);
        }
    }


    //PROCESO 1A
    function ajaxRequestGetPost(Request $request)
    {
        try {
            $currentSite = Domain::find($request->last_id);
            $response["ok"] = false;
            if ($currentSite != null) {

                //* Check & Update Shop Data
                $service_url = "https://www." . $currentSite->url . "/wp-json/sync-products-plugin/v2/sync_posts";
                
                $client = new Client([
                    'headers' => ['Content-Type' => 'application/json']
                ]);
                $body = "fromMKB";
                $start_t = microtime(1);
                $result = $client->post(
                    $service_url,
                    [
                        'body' => json_encode($body)
                    ]
                );
                $end_t = microtime(1);
                $log = LogController::makeLog('Sync_products',  $service_url, null, $currentSite->id, ($end_t - $start_t));
                $response["log"] = $log;
                $successArray = json_decode($result->getBody()->getContents());
                foreach ($successArray->posts as $singlePost) {

                    $exist = KB_Post::where("id_dominio", "=", $currentSite->id)->where("id_post", "=", $singlePost->ID)->get();

                    if (!isset($exist[0])) { //* SI NO EXISTE LO CREAMOS BRO

                        $newPost = new KB_Post();
                        $newPost->id_post = $singlePost->ID;
                        $newPost->title = $singlePost->post_title;
                        $newPost->id_dominio = $currentSite->id;
                        $newPost->url = $singlePost->guid;
                        $newPost->state = $singlePost->post_status;
                        $newPost->last_modified = $singlePost->post_modified_gmt;
                        $newPost->last_sync = new DateTime();
                        $newPost->id_kb = $singlePost->ID . '__' . $currentSite->id;
                        try {
                            if ($newPost->save()) {
                            } else {
                            }
                        } catch (Exception $ex) {
                            echo $ex;
                        };
                    }
                }
                //$response["last_id"] = $currentSite->id;
                $response["ok"] = true;
            }

            return response()->json($response);
        } catch (Exception $ex) {
            $response["url"] = $service_url;
            $response["ex"] = $ex->getMessage();
            return response()->json($response);
        }
    }

    //PROCESO 1B



    public function refreshProductsCache(Request $request)
    {
        $url = $request["url"];
        $main_container = (object) [];
        $main_container->kb_posts = [];
        // Creo que no importa que vayan productos repetidos, no esta validado pero los casos practicos son muy remotos para que valga la pena
        $myDomain = Domain::where("url", "=", $url)->first();
        //$main_container->domain = $myDomain;
        foreach ($myDomain->kb_posts as $post) {
            $simplePost = (object) [];
            $simplePost->id_post = $post->id_post;
            $simplePost->url = $post->url;
            $simplePost->products = [];
            foreach ($post->kb_post_products as $post) {
                $simpleProduct = (object) [];
                $simpleProduct->id_product = $post->id_product;
                $simpleProduct->asin = $post->product->asin;
                $simpleProduct->billiager_id = $post->product->billiager_id;
                $simpleProduct->stores = array();
                foreach ($post->product->kb_store as $store) {
                    $simpleStore = (object) [];
                    $simpleStore->kaufberater_id = $store->store->id;
                    $simpleStore->billiager_id = $store->store->billiager_id;
                    $simpleStore->name = $store->store->name;
                    $simpleStore->logo_url = $store->store->logo_url;
                    $simpleStore->price = $store->price;
                    $simpleStore->url = $store->url;
                    array_push($simpleProduct->stores, $simpleStore);
                }
                $post->product->kb_extra_data;
                array_push($simplePost->products, $simpleProduct);
            }
            array_push($main_container->kb_posts, $simplePost);
        }
        return response(json_encode($main_container), 200)
            ->header('Content-Type', 'application/json');
    }

    public function loadPostView(Request $request, $id)
    {
        $myDomain = Domain::find($id);
        return view('all-site-list-post')->with(
            array(
                'myDomain' => $myDomain
            )
        );
    }

    public function refreshPostDomain(Request $request, $id)
    {
        $myDomain = Domain::find($id);

        //foreach ($myDomain as $singleDomain) {
        $service_url = "https://www." . $myDomain->url . "/wp-json/sync-products-plugin/v2/sync_posts";
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);
        $body = "fromMKB";


        $result = $client->post(
            $service_url,
            [
                'body' => json_encode($body)
            ]
        );

        $successArray = json_decode($result->getBody()->getContents());
        echo '<textarea>' . json_encode($successArray->posts) . '</textarea>';

        foreach ($successArray->posts as $singlePost) {

            $exist = KB_Post::where("id_dominio", "=", $id)->where("id_post", "=", $singlePost->ID)->get();

            if (!isset($exist[0])) { //* SI NO EXISTE LO CREAMOS BRO

                $newPost = new KB_Post();
                $newPost->id_post = $singlePost->ID;
                $newPost->title = $singlePost->post_title;
                $newPost->id_dominio = $id;
                $newPost->url = $singlePost->guid;
                $newPost->state = $singlePost->post_status;
                $newPost->last_modified = $singlePost->post_modified_gmt;
                $newPost->last_sync = new DateTime();
                $newPost->id_kb = $singlePost->ID . '__' . $id;
                try {
                    if ($newPost->save()) {
                        echo "saved <br>";
                    } else {
                        echo "can[t save <br>";
                    }
                } catch (Exception $ex) {
                    echo $ex;
                };
            }
        }
        /* */


        // }
        die();


        return view('all-site-list-post')->with(
            array(
                'myDomain' => $myDomain
            )
        );
    }

    public function refreshProducts(Request $request, $id)
    {
        $myDomain = Domain::find($id);
        $service_url = "https://www." . $myDomain->url . "/wp-json/sync-products-plugin/v2/product";
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);
        $body = "fromMKB";
        $result = $client->get(
            $service_url,
            [
                'body' => json_encode($body)
            ]
        );

        $successArray = json_decode($result->getBody()->getContents());
        echo '<textarea>' . ($service_url) . '</textarea>';
        foreach ($successArray->products as $singleProduct) {
            $exist = KB_Product::where("asin", "=", $singleProduct->asin)->get();
            $idProduct = 0;
            if (!isset($exist[0])) { //* SI NO EXISTE LO CREAMOS BRO
                echo "<br><b>Exception<b><br> can't " . $singleProduct->asin . " save because already exist <br>";
                $newProduct = new KB_Product();
                $newProduct->title = $singleProduct->ID;
                $newProduct->asin = $singleProduct->asin;
                $newProduct->post_modified = $singleProduct->post_modified;
                $newProduct->state = "LIVE";
                try {
                    if ($newProduct->save()) {
                        $idProduct = $newProduct->$id;
                        echo "saved new product " . $singleProduct->asin . " <br>";
                    } else {
                        $idProduct = 0;
                    }
                } catch (Exception $ex) {
                    $idProduct = 0;
                    echo $ex;
                };
            } else {
                $idProduct = $exist[0]->id;
            }
            echo " <br> valor de $ idProduct " . $idProduct . "";
            if ($idProduct > 0) {

                foreach ($singleProduct->parent_post as $parentPost) {
                    $newPostProduct = new KB_PostProduct();
                    $idPost = KB_Post::where("id_post", "=", $parentPost)->where("id_dominio", "=", $id)->get();
                    if (isset($idPost[0])) {
                        $existRelation = KB_PostProduct::where("id_post", "=", $idPost[0]->id)->where("id_product", "=", $idProduct)->get();
                        $newPostProduct->id_post = $idPost[0]->id;
                        $newPostProduct->id_product = $idProduct;
                        $newPostProduct->state = "1";
                        try {
                            if (!isset($existRelation[0])) {
                                if ($newPostProduct->save()) {
                                    echo "saved relationship " . $newPostProduct->id_post . "  y " . $newPostProduct->id_product . " <br>";
                                } else {
                                    $idProduct = 0;
                                    echo "can[t save relationship " . $newPostProduct->id_post . "  y " . $newPostProduct->id_product . " <br>";
                                }
                            }
                        } catch (Exception $ex) {
                            echo $ex;
                        };
                    }
                }
            }
        }
        /* */
        die();
        return view('all-site-list-post')->with(
            array(
                'myDomain' => $myDomain
            )
        );
    }

    // PROCESO 3B
    function refreshAllProducts(Request $request)
    {
        //this method it's too heavy
        return "ok";
        //* billiager_auth_credentials (!!!TODO -> Save on database)
        $billiager_auth = ['auth' => ['kaufberater.io_prod_API', 'BlgBi1v1BV']];

        //* Check & Update Shop Data
        $serviceURL_all_shops = "https://api.billiger.de/content/2.0/get_shops";
        $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
        $result = $client->get($serviceURL_all_shops, $billiager_auth);
        $response_all_shops = json_decode($result->getBody()->getContents());



        foreach ($response_all_shops as $shopBilliager) {
            $existingStore = KB_Store::where("billiager_id", "=", $shopBilliager->shop_id)->first();
            if ($existingStore == null) {
                $newStore = new KB_Store();
                $newStore->name = $shopBilliager->company_name;
                $newStore->logo_url = $shopBilliager->logo_url;
                $newStore->billiager_id = $shopBilliager->shop_id;
                if ($newStore->save()) {
                    echo "Shop " . $newStore->name . " Saved w/ success";
                }
            }
        }

        //* Check & Update KB_Product Data from billiager
        //! TODO, Check if the product already has billiager_id, because can be a waste ask again this field ¿?
        $allProducts = KB_Product::all();

        //* to know the main process
        $main_progress_billiager_sync = KbOption::where('option_name', '=', 'main_progress_billiager_sync')->first();
        $total_products_billiager = KbOption::where('option_name', '=', 'total_products_billiager')->first();
        $total_products_found_billiager = KbOption::where('option_name', '=', 'total_products_found_billiager')->first();

        $total_products_billiager->option_value = count($allProducts);
        $total_products_billiager->save();
        $main_progress_billiager_sync->option_value = 0;
        $main_progress_billiager_sync->save();

        //$allProducts = KB_Product::paginate(5);
        foreach ($allProducts as $product) {
            $service_url = "https://api.billiger.de/content/2.0/search?asin=" . $product->asin;
            $service_url = str_replace(" ", "", $service_url);
            $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
            $result = $client->get($service_url, $billiager_auth);
            /* */

            $successArray = json_decode($result->getBody()->getContents());


            //* Check for offers
            if (isset($successArray->hits[0])) {
                $product->billiager_id = $successArray->hits[0]->product_id;
                if ($product->save()) {
                    $total_products_found_billiager->option_value = $total_products_found_billiager->option_value++;
                    $total_products_found_billiager->save();
                    echo "UPDATED BILLIAGER_ID " .  $product->asin . " --> " . $product->billiager_id . "<br><br>";

                    //* Second Request GET OFFERS ;
                    $service_url2 = "https://api.billiger.de/content/2.0/get_product_offers?id=" . $product->billiager_id . "&n=3";
                    $client = new Client(['headers' => ['Content-Type' => 'application/json']]);

                    $result2 = $client->get($service_url2, $billiager_auth);
                    $successArray2 = json_decode($result2->getBody()->getContents());
                    $saveKeys = array(
                        "shop",
                        "shop_id",
                        "description",
                        "price",
                        "image_url_large",
                        "image_url",
                        "total_price",
                        "name"
                    );
                    //clear all old Alternatives
                    $old_alternatives = KB_StoreProduct::where("id_kb_product", "=", $product->id)->get();
                    foreach ($old_alternatives as $alt) {
                        $alt->delete();
                    }

                    foreach ($successArray2 as $shop) {
                        //* HERE COMES THE kb_store_product inserts
                        $store = KB_Store::where("billiager_id", "=", $shop->shop_id)->first();
                        //add new alternatives

                        $newAlternative = new KB_StoreProduct();
                        $newAlternative->price = $shop->price;
                        $newAlternative->url = $shop->clickout_link;
                        $newAlternative->id_kb_store = $store->id;
                        $newAlternative->id_kb_product = $product->id;
                        if ($newAlternative->save()) {
                            echo "New Alternative added " . json_encode($newAlternative) . "<br><br>";
                        }


                        //Savvig & updating
                        foreach ($saveKeys as $key) {
                            $extraData = KB_ProductExtraData::where("field_key", "=", $key)
                                ->where("id_kb_product", "=", $product->id)
                                ->where("origin", "=", "billiager")
                                ->first();
                            if ($extraData == null)
                                $extraData = new KB_ProductExtraData();
                            $extraData->origin = "billiager";
                            $extraData->field_key = $key;
                            $extraData->value = $shop->$key;
                            $extraData->id_kb_product = $product->id;
                            if ($extraData->save()) {
                                echo "ExtraData '" . $extraData->field_key . "' -> " . $extraData->value . " saved ";
                            }
                        }
                    }
                }
            } else { //it's not on billiager, or the offer expires, we have to clear all
                $product->billiager_id = 0;
                $old_alternatives = KB_StoreProduct::where("id_kb_product", "=", $product->id)->get();
                foreach ($old_alternatives as $alt) {
                    $alt->delete();
                    echo "<br><br> <span style='color:red'> El producto " . $product->billiager_id . " ha desaparecido de billiager <span> <br><br>";
                }
                $product->save();
            }
            $main_progress_billiager_sync->option_value = $main_progress_billiager_sync->option_value++;
            $main_progress_billiager_sync->save();
        }
    }

    function refreshSingleProducts(Request $request)
    {
        //return "ok";
        //* billiager_auth_credentials (!!!TODO -> Save on database)
        $billiager_auth = ['auth' => ['kaufberater.io_prod_API', 'BlgBi1v1BV']];
        $id_product = $request->last_id;
     
        $singleProduct = KB_Product::find($id_product);

        //$allProducts = KB_Product::paginate(5);
        //foreach ($singleProduct as $product) {
            $service_url = "https://api.billiger.de/content/2.0/search?asin=" . $singleProduct->asin;
            $service_url = str_replace(" ", "", $service_url);
            $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
            $result = $client->get($service_url, $billiager_auth);
            /* */

            $successArray = json_decode($result->getBody()->getContents());

            try{
                //* Check for offers
                if (isset($successArray->hits[0])) {
                    $singleProduct->billiager_id = $successArray->hits[0]->product_id;
                    if ($singleProduct->save()) {
                        $response["last_id"] = $singleProduct->id;
                        $response["added"] = 1;
                        $response["asin"] = $singleProduct->asin;  
                        $response["billiager_id"] = $singleProduct->billiager_id;   
                        return response()->json($response);
                    }
                } else { //it's not on billiager, or the offer e
                    $response["last_id"] = $singleProduct->id;
                    $response["asin"] = $singleProduct->asin;
                    $response["added"] = 0;
                    $response["billiager_id"] = $singleProduct->billiager_id;   
                    return response()->json($response);
                }
            }catch( Exception $ex){
                $response["ex"] = $ex;   
                return response()->json($ex->getResponse());
            }
        //}
    }




    //METODO PARA INVOCAR LA EXPRESION REGULAR QUE RECOPILA LOS ASINS Y ALIMENTA EL CPT
    function ajaxRequestPost(Request $request)
    {
        try {
            $input = $request->all();
            $currentSite = Domain::find($request->last_id);

            $nextSite = null;
            if ($currentSite != null) {
                $nextSite = Domain::where("id", ">", $currentSite->id)->where("state", "=", "1")->first();
                //* Check & Update Shop Data
                $service_url = "https://www." . $currentSite->url . "/wp-json/sync-products-plugin/v2/refresh_post_products";
                $client = new Client([
                    'headers' => ['Content-Type' => 'application/json']
                ]);
                $body = "fromMKB";
                $result = $client->post(
                    $service_url,
                    [
                        'body' => json_encode($body)
                    ]
                );
                $successArray = json_decode($result->getBody()->getContents());
                $response["last_id"] = $currentSite->id;
                $response["url"] = $currentSite->url;

                $response["total"] = count($successArray->posts);
                $response["total_posts"] = $successArray->total_posts;
                $response["total_inserts"] = $successArray->total_inserts;
                if ($nextSite != null) {
                    $response["nextURL"] =  $nextSite->url;
                }
            } else {
                $response["last_id"] = -1;
                $response["url"] = null;
            }

            return response()->json($response);
        } catch (ClientException $ex) {
            $response["url"] = $service_url;
            $response["ex"] = $ex->getResponse();
            return response()->json($response);
        }
    }




    function ajaxRequestGetProducts(Request $request)
    {
        try {
            $currentSite = Domain::find($request->last_id);
            $response["ok"] = false;
            if ($currentSite != null) {

                //* Check & Update Shop Data
                $service_url = "https://www." . $currentSite->url . "/wp-json/sync-products-plugin/v2/product";
                $client = new Client([
                    'headers' => ['Content-Type' => 'application/json']
                ]);
                $body = "fromMKB";
                $result = $client->post(
                    $service_url,
                    [
                        'body' => json_encode($body)
                    ]
                );
                $successArray = json_decode($result->getBody()->getContents());

                foreach ($successArray->products as $singleProduct) {
                    $exist = KB_Product::where("asin", "=", $singleProduct->asin)->get();
                    $idProduct = 0;
                    if (!isset($exist[0])) { //* SI NO EXISTE LO CREAMOS BRO
                        $newProduct = new KB_Product();
                        $newProduct->title = $singleProduct->ID;
                        $newProduct->asin = $singleProduct->asin;
                        $newProduct->post_modified = $singleProduct->post_modified;
                        $newProduct->state = "LIVE";
                        try {
                            if ($newProduct->save()) {
                                $idProduct = $newProduct->id;
                            } else {
                                $idProduct = 0;
                            }
                        } catch (Exception $ex) {
                            $idProduct = 0;
                            echo $ex;
                        };
                    } else {
                        $idProduct = $exist[0]->id;
                    }
                    if ($idProduct > 0) {

                        foreach ($singleProduct->parent_post as $parentPost) {
                            $newPostProduct = new KB_PostProduct();
                            $idPost = KB_Post::where("id_post", "=", $parentPost)->where("id_dominio", "=", $currentSite->id)->get();
                            if (isset($idPost[0])) {
                                $existRelation = KB_PostProduct::where("id_post", "=", $idPost[0]->id)->where("id_product", "=", $idProduct)->get();
                                $newPostProduct->id_post = $idPost[0]->id;
                                $newPostProduct->id_product = $idProduct;
                                $newPostProduct->state = "1";
                                try {
                                    if (!isset($existRelation[0])) {
                                        if ($newPostProduct->save()) {
                                            //echo "saved relationship " . $newPostProduct->id_post . "  y " . $newPostProduct->id_product . " <br>";
                                        } else {
                                            $idProduct = 0;
                                            //echo "can[t save relationship " . $newPostProduct->id_post . "  y " . $newPostProduct->id_product . " <br>";
                                        }
                                    }
                                } catch (Exception $ex) {
                                    echo $ex;
                                };
                            }
                        }
                    }
                }
                $response["last_id"] = $request->last_id;
                $response["ok"] = true;
                $response["total"] = count($successArray->products);
                //$response["jsonText"] = json_encode($successArray);

            }

            return response()->json($response);
        } catch (Exception $ex) {
            $response["url"] = $service_url;
            $response["ex"] = $ex->getMessage() . ' line ' . $ex->getLine();
            return response()->json($response);
        }
    }
    /**/

    function ajaxRequestGetBilliager(Request $request)
    {
        try {
            //* Check & Update Shop Data
            $service_url = "https://kb.kaufberater.io/public/api/refresh_all_products";
            $client = new Client([
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $body = "fromMKB";
            $result = $client->post(
                $service_url,
                [
                    'body' => json_encode($body)
                ]
            );
            $successArray = json_decode($result->getBody()->getContents());
            //$response["last_id"] = $currentSite->id;
            $response["ok"] = true;
            return response()->json($response);
        } catch (Exception $ex) {
            $response["url"] = $service_url;
            $response["ex"] = $ex->getMessage();
            return response()->json($response);
        }
    }

    function ajaxRequestGetCache(Request $request)
    {
        try {
            $currentSite = Domain::find($request->last_id);
            $response["ok"] = false;
            $response["last_id"] = $request->last_id;
            if ($currentSite != null) {

                //* Check & Update Shop Data
                $service_url = "https://www." . $currentSite->url . "/wp-json/sync-products-plugin/v2/refreshMyCache";
                $client = new Client([
                    'headers' => ['Content-Type' => 'application/json']
                ]);
                $body = "fromMKB";
                $result = $client->post(
                    $service_url,
                    [
                        'body' => json_encode($body)
                    ]
                );
                $successArray = json_decode($result->getBody()->getContents());
                $response["last_id"] = $currentSite->id;
                $response["ok"] = true;
                $response["service_url"] = $service_url;
            }

            return response()->json($response);
        } catch (Exception $ex) {
            $response["url"] = $service_url;
            $response["ex"] = $ex->getMessage();
            return response()->json($response);
        }
    }



    // new controllers

    function load_billiager_updater(Request $request)
    {
        $all_sites = Domain::orderBy('state', 'desc')->get();
        return view('billiager-updater')->with(
            array(
                'all_sites' => $all_sites
            )
        );
    }

    function load_billiager_searcher(Request $request)
    {
        $all_sites = Domain::orderBy('state', 'desc')->get();
        return view('billiager-searcher')->with(
            array(
                'all_sites' => $all_sites
            )
        );
    }

    function get_total_products(Request $request)
    {
        $total = KB_Product::where('billiager_id', '>', 0)->count();
        $response["total"] = $total;
        return response()->json($response);
    }

    function get_total_products_not_in_billiager(Request $request)
    {
        $total = KB_Product::where('billiager_id', '=', 0)->where('id', '>', $request->last_id)->count();
        $response["total"] = $total;
        return response()->json($response);
    }

    function get_next_product(Request $request)
    {
        try {
            $currentProduct = null;
            $currentProduct = KB_Product::where("id", ">", $request->last_id)->where("billiager_id", '>', 0)->first();

            if ($currentProduct != null) {
                $nextProduct = KB_Product::where("id", ">", $currentProduct->id)->where("billiager_id", '>', 0)->first();
                //* Check & Update Shop Data

                $response["last_id"] = $currentProduct->id;
                $response["title"] = $currentProduct->title;

                if ($nextProduct != null) {
                    $response["nextTitle"] =  $nextProduct->title;
                }
            } else {
                $response["last_id"] = -1;
                $response["url"] = null;
            }

            return response()->json($response);
        } catch (Exception $ex) {

            $response["ex"] = $ex->getMessage() . ' - - - ' . $ex->getLine();
            $response["url"] = $ex->getMessage();
            return response()->json($response);
        }
    }

    function get_next_product_not_in_billiager(Request $request)
    {
        try {
            $currentProduct = null;
            $currentProduct = KB_Product::where("id", ">", $request->last_id)->where("billiager_id", '=', 0)->first();

            if ($currentProduct != null) {
                $nextProduct = KB_Product::where("id", ">", $currentProduct->id)->where("billiager_id", '=', 0)->first();
                //* Check & Update Shop Data

                $response["last_id"] = $currentProduct->id;
                $response["title"] = $currentProduct->title;

                if ($nextProduct != null) {
                    $response["nextTitle"] =  $nextProduct->title;
                }
            } else {
                $response["last_id"] = -1;
                $response["url"] = null;
            }

            return response()->json($response);
        } catch (Exception $ex) {

            $response["ex"] = $ex->getMessage() . ' - - - ' . $ex->getLine();
            $response["url"] = $ex->getMessage();
            return response()->json($response);
        }
    }

    function refresh_stores(Request $request)
    {
        $billiager_auth = ['auth' => ['kaufberater.io_prod_API', 'BlgBi1v1BV']];

        $response = (object) [];
        $response->new_stores = 0;
        $response->updated_stores = 0;

        //* Check & Update Shop Data
        $serviceURL_all_shops = "https://api.billiger.de/content/2.0/get_shops";
        $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
        $result = $client->get($serviceURL_all_shops, $billiager_auth);
        $response_all_shops = json_decode($result->getBody()->getContents());

        foreach ($response_all_shops as $shopBilliager) {
            $existingStore = KB_Store::where("billiager_id", "=", $shopBilliager->shop_id)->first();
            if ($existingStore == null) {
                $newStore = new KB_Store();
                $newStore->name = $shopBilliager->company_name;
                $newStore->logo_url = $shopBilliager->logo_url;
                $newStore->billiager_id = $shopBilliager->shop_id;
                if ($newStore->save()) {
                    $response->new_stores++;
                }
            } else {
                //* TODO .. Make the update of the store info.
                $response->updated_stores++;
            }
        }
        return response()->json($response);
    }

    function refresh_single_product(Request $request)
    {
        try {
            $response = (object) [];
            $response->update = false;
            $response->deleted = false;
            $response->asin = "";
            $response->billiager_id = 0;
            $response->id = 0;
            $response->alternatives = array();
            $response->extra_data = array();
            $billiager_auth = ['auth' => ['kaufberater.io_prod_API', 'BlgBi1v1BV']];

            $product = KB_Product::find($request->last_id);
            $response->id = $request->last_id;

            if ($product == null) {
                $response->ex = "No se encontro el producto";
                return response()->json($response);
            }
            $response->asin = $product->asin;
            $service_url = "https://api.billiger.de/content/2.0/search?asin=" . $product->asin;
            $service_url = str_replace(" ", "", $service_url);
            $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
            $result = $client->get($service_url, $billiager_auth);
            /* */

            $successArray = json_decode($result->getBody()->getContents());

            //* Check for offers
            if (isset($successArray->hits[0])) {
                $response->update = true;
                $product->billiager_id = $successArray->hits[0]->product_id;
                $response->billiager_id = $product->billiager_id;
                if ($product->save()) {

                    //* Second Request GET OFFERS ;
                    $service_url2 = "https://api.billiger.de/content/2.0/get_product_offers?id=" . $product->billiager_id . "&n=3";
                    $client = new Client(['headers' => ['Content-Type' => 'application/json']]);

                    $result2 = $client->get($service_url2, $billiager_auth);
                    $successArray2 = json_decode($result2->getBody()->getContents());
                    $saveKeys = array(
                        "shop",
                        "shop_id",
                        "description",
                        "price",
                        "image_url_large",
                        "image_url",
                        "total_price",
                        "name"
                    );
                    //clear all old Alternatives
                    $old_alternatives = KB_StoreProduct::where("id_kb_product", "=", $product->id)->get();
                    foreach ($old_alternatives as $alt) {
                        $alt->delete();
                    }

                    foreach ($successArray2 as $shop) {
                        //* HERE COMES THE kb_store_product inserts
                        $store = KB_Store::where("billiager_id", "=", $shop->shop_id)->first();
                        //add new alternatives

                        $newAlternative = new KB_StoreProduct();
                        $newAlternative->price = $shop->price;
                        $newAlternative->url = $shop->clickout_link;
                        $newAlternative->id_kb_store = $store->id;
                        $newAlternative->id_kb_product = $product->id;
                        if ($newAlternative->save()) {
                            array_push($response->alternatives, $newAlternative);
                        }

                        //* TODO, Borrar la data antigua.

                        //Savvig & updating
                        foreach ($saveKeys as $key) {
                            $extraData = KB_ProductExtraData::where("field_key", "=", $key)
                                ->where("id_kb_product", "=", $product->id)
                                ->where("origin", "=", "billiager")
                                ->first();
                            if ($extraData == null)
                                $extraData = new KB_ProductExtraData();
                            $extraData->origin = "billiager";
                            $extraData->field_key = $key;
                            $extraData->value = $shop->$key;
                            $extraData->id_kb_product = $product->id;
                            if ($extraData->save()) {
                                array_push($response->extra_data, $extraData);
                            }
                        }
                    }
                }
            } else { //it's not on billiager, or the offer expires, we have to clear all
                $product->billiager_id = 0;
                $response->deleted = true;

                //* TODO, cambiar el estado del producto
                $old_alternatives = KB_StoreProduct::where("id_kb_product", "=", $product->id)->get();
                foreach ($old_alternatives as $alt) {
                    $alt->delete();
                    //echo "<br><br> <span style='color:red'> El producto " . $product->billiager_id . " ha desaparecido de billiager <span> <br><br>";
                }
                $product->save();
            }
        } catch (Exception $ex) {
            $response->ex = $ex->getMessage() . ' - - - ' . $ex->getLine();
        }

        return response()->json($response);
    }

    function refrescar_metadata(Request $request){
        echo "Hola este es el metodo para refrescar la metadata";
    }
}
