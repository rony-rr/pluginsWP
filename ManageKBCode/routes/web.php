<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
  return view('welcome');
});

Route::group(['prefix' => 'admin'], function () {
  Voyager::routes();
  // ? Paginas de Administrador
  #Creation of domain groups
  Route::get('admin-group', function () {
    return view('admin-group');
  })->middleware('admin.user');
  Route::get('group-pages', function () {
    return view('group-pages');
  })->middleware('admin.user');
  Route::get('site-policies', function () {
    return view('site-policies');
  })->middleware('admin.user');
  Route::get('check-domain', function () {
    return view('check-domain');
  })->middleware('admin.user');
  Route::get('start', function () {
    return view('start');
  })->middleware('admin.user');
  Route::get('cloud-flare', function () {
    return view('cloud-flare');
  })->middleware('admin.user');
  Route::get('site-search', function () {
    return view('site-search');
  })->middleware('admin.user');
  Route::get('general-domain', function () {
    return view('general-domain');
  })->middleware('admin.user');
  Route::get('hiperloop-automatic', 'PostQueueController@HiperloopAutomatic')->middleware('admin.user');
  //Bad Redirect route
  Route::get('cronjob-detect-redirect', function () {
    return view('cronjob-detect-redirect');
  })->middleware('admin.user');

  Route::get("url-duplicate", 'SaveController@duplicateurl')->middleware('admin.user');



  // ? Funciones de Controller dentro del administrador
  #Borrar Grupo de Dominios = 
  #Actualizar paginas a IDgroud 0, Borrar Sub grupo y Actaulizar Dominios a valor 2 no Group
  Route::get('deletegroup/{id}', 'SaveController@deleteGroup')->middleware('admin.user');

  Route::post('savedominegroup', 'SaveController@addDomain')->middleware('admin.user');;
  #Elimina Dominios de un grupo padre.
  Route::post('actiongroup', 'SaveController@actiongroup')->middleware('admin.user');;
  #Crear Nuevo Grupo desde admin-group -> Modal
  Route::post('newgroup', 'SaveController@createGroup')->middleware('admin.user');
  #function AlertRating Cambio de Estrellas.
  // Route::post('startpost', 'SaveController@createGroup')->middleware('admin.user');
  #Envio de datos para eliminar Cache.
  Route::post('cloudFlareP', 'SaveController@cloudFlarePost')->middleware('admin.user');
  #Borrar Paginas de Grupos
  Route::get("delPage/{id}", 'SaveController@DeletePages')->middleware('admin.user');
  #Borrar Grupos de paginas creados.
  Route::get("dpg/{id}", 'SaveController@WpDeleteGroupPages')->middleware('admin.user');
  #Guardar Nuevo Grupo de Paginas
  Route::post("SavePages/", 'SaveController@SavePages')->middleware('admin.user');
  #Borra grupos en Admin Group
  Route::post("remove-group", 'SaveController@rmGroup')->middleware('admin.user');
  #Avisar de Politicas y avisos legales.
  Route::post("savepolicy", 'SaveController@SavePol')->middleware('admin.user');
  #Peticion de Cambio de Rate en sitios desde la pagina start
  Route::post("startpost", 'SaveController@startpost')->middleware('admin.user');
  # Peticion de Revision de uno o mas Dominios desde la pagina check-domain
  // Route::post("checkdom", 'SaveController@checkDom')->middleware('admin.user');
  Route::post("checkdom", 'CheckDomain@sendCheckDom')->middleware('admin.user');
  #Metodo para obtener las redirecciones rotas https/http/www
  Route::post("searchURL", 'PostQueueController@Https_Redirect')->middleware('admin.user');
  # Verififcar un grupo cuando esta fallando..
  Route::get("verificarPage/{id}", 'SaveController@verificarPage')->middleware('admin.user');
  # Guardar Por Method POST
  Route::post("save_data", 'SaveDataController@SaveData')->middleware('admin.user');
  # Eliminar Grupos con 1 entrada o sin entradas
  Route::get("grupo_clean/{id}", 'SaveController@CleanGroup');



  ########################## LLAMADAS DE DATOS #########################
  //? Funciones que realizan en comunicacion con Sitios Wordpress
  Route::get("key/{id}", 'SaveController@installWpSendData');
  Route::get("domaininfo/{id}", 'SaveController@installWpInfo');
  Route::get("policykey/{id}", 'SaveController@PolicyWpInfo');
  Route::get("rest-start/{id}", 'SaveController@Reststart');
  Route::get("checkurl/{id}", 'SaveController@checkurl');
  Route::get("dellpost/{id}", 'SaveController@dellpost');
  Route::get("portada/{id}", 'SaveController@portada');
  Route::get("deleteDOM/{id}", 'SaveController@deleteDOM');
  Route::get("startjob", 'SaveController@startjob');
  Route::get("startrating/{id}", 'SaveController@StartLog');
  Route::get("startmail", 'SaveController@Startmail');
  Route::get("test", 'SaveController@Testing');

  Route::post("cronjob-detect-redirect/", 'PostQueueController@Https_Redirect');

  Route::get("work_queue/", 'ApiController@FunctionQueue');

  Route::post("group-pages-ajax/", 'ApiController@functionGroupAjax');
  
  //!- Post Queue
  Route::get("post-hiperloop", 'PostQueueController@loadView')->middleware('admin.user');
  Route::post("sendToSandbox", 'PostQueueController@sendToSandbox')->middleware('admin.user');

  // Call all KB sites, requesting their unavailable products
  Route::get('getUnavailableProducts', 'UnavailableProductsController@getUnavailableProducts');
  Route::get('getUnavailableProductsCsv/{lang}', 'UnavailableProductsController@getUnavailableProductsCsv');
  ///Export CSV 
  Route::post('export-csv', 'UnavailableProductsController@Exportcsv');
  ///Notificaciones De Post 
  Route::get('cronjob-update-contentspost', 'SaveController@notificationPost');

  //Cronjob 
  Route::get('sendNotification', 'NotificationController@sendNotification');

  //UnaivalaibeProductsAdmin 
  Route::get('refreshProducts', 'UnavailableProductsController@refreshProducts')->middleware('admin.user');
  Route::get('unavailableProducts', 'UnavailableProductsController@loadView')->middleware('admin.user');
  Route::get('truncateProducts', 'UnavailableProductsController@truncateProducts')->middleware('admin.user');

  //Sites panel
  Route::get('allSites', 'SiteController@loadView')->middleware('admin.user');
  Route::get('see_post_{id}', 'SiteController@loadPostView')->middleware('admin.user');
  Route::get('refresh_domain_{id}', 'SiteController@refreshPostDomain')->middleware('admin.user');

  //Refresh products
  Route::get('refresh_products_{id}', 'SiteController@refreshProducts')->middleware('admin.user');
  Route::get('list_products_{post_id}', 'SiteController@listProducts')->middleware('admin.user');
  
 

  Route::post('ajaxRequestPostsGetNext', 'SiteController@ajaxRequestPostsGetNext');
  Route::post('ajaxRequestGetPost', 'SiteController@ajaxRequestGetPost');
  Route::post('ajaxRequestPosts', 'SiteController@ajaxRequestPost');
  
  Route::post('ajaxRequestGetProducts', 'SiteController@ajaxRequestGetProducts');
  Route::post('ajaxRequestGetBilliager', 'SiteController@ajaxRequestGetBilliager');
  Route::post('ajaxRequestGetCache', 'SiteController@ajaxRequestGetCache');

  // Extended routes
  Route::get('billiager_updater', 'SiteController@load_billiager_updater')->middleware('admin.user');
  Route::get('billiager_searcher', 'SiteController@load_billiager_searcher')->middleware('admin.user');
  Route::get('refrescar_metadata', 'SiteController@refrescar_metadata')->middleware('admin.user');
  

  Route::post('get_total_products', 'SiteController@get_total_products');
  Route::post('get_total_products_not_in_billiager', 'SiteController@get_total_products_not_in_billiager');
  Route::post('ajax_get_next_product', 'SiteController@get_next_product');
  Route::post('get_next_product_not_in_billiager', 'SiteController@get_next_product_not_in_billiager');


  Route::post('refresh_stores', 'SiteController@refresh_stores');
  Route::post('refresh_single_product', 'SiteController@refresh_single_product');
  Route::post('refreshSingleProducts', 'SiteController@refreshSingleProducts');


  // new log routes
  Route::get('seeWorkedLog', 'LogController@load_worker_log')->middleware('admin.user');
  Route::get('seeValidatorLog', 'LogController@load_validator_log')->middleware('admin.user');
  Route::get('legacyValidatorLog', 'LogController@legacy_validator_log');
  // report
  Route::get('reportBilliger', 'ReportController@ReportBilliger')->middleware('admin.user');
});
