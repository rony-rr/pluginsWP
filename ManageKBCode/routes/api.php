<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('CheckKaufberaterSite')->post('/hola', function (Request $request) {
    $object = json_decode($request);
    return response(json_encode($request), 200)
    ->header('Content-Type', 'application/json');
});

Route::post("addPackage", 'PostQueueController@addPackages');

Route::post("refreshProductsCache", 'SiteController@refreshProductsCache');

Route::get("sendQueue", 'PostQueueController@sendQueue');
// auto SEND
Route::get("autosend", 'PostQueueController@sendQueue_automatic');

Route::get("red_slug_cronjob", 'SaveController@RedSlug');

Route::get("abcdClenar", 'SaveController@ccallsite');

Route::get("get_all_cat/{id}", 'SaveController@GetAllCat');

Route::get("apidom/{id}", 'SaveController@ApiDom');

Route::get("republishpost", 'SaveController@republish_post');

Route::get("traking_id", 'SaveController@traking_ID');


Route::post("checkdomain", 'CheckDomain@checkDomain');

//// API get URL DOM
Route::post("api_get_url", 'ApiController@get_url');

//origin Site
Route::post("origin_pages", 'OriginController@origin_pages');

// get group domain
Route::post("api_get_group", 'ApiController@get_group');

// Send push every site.
Route::get("push_site", 'OriginController@PushSite');

// replace url
Route::post("urlreplace", 'OriginController@replace_url');

Route::post('refresh_all_products', 'SiteController@refreshAllProducts');

Route::post("slack_permalink", 'ApiController@CleanPermalink');

// Change Status by Default Post
Route::post("change_status", 'ApiController@ChangeStatus');

// clean pages
Route::post("clean_pages_kb", 'ApiController@CleanPages');

//Log errors on Validator Plugin in the same place.
Route::post("post_validator_log", 'LogController@make_post_validation_log');

//slack API
// Traer todos los dominios de un droplet
Route::post("get_server", 'SlackApi@SlackDroplet');
// Saber en que droplet esta un dominio
Route::post("get_info", 'SlackApi@SlackInfoDomain');
// save comment
Route::get("comentarios", 'SlackApi@savecommetn');

// end salack api
Route::get("c2xhY2s", 'SlackApi@SlackProduct');
// save comment
Route::get("detectgcm", 'SlackApi@DetectPluginGCM');
//save info site
Route::post("save_info_kb", 'SlackApi@save_infokb');

// export csv - product
Route::get("exportcsvproduct", 'ApiController@ExportCSVproductAPi');
