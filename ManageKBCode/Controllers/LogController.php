<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Domain;
use App\KB_Log;
use App\KB_LogWorker;
use App\KB_LogValidatorPlugin;
use DateTime;
use Exception;
use GuzzleHttp\Client;

ini_set('max_execution_time', 0); // Evitar errores de timeout
class LogController extends Controller
{
    function load_worker_log(Request $request)
    {
        $allLogs = KB_LogWorker::all();
        $results = DB::select(DB::raw("SELECT count(1) as number, dominio, DATE_FORMAT(date, '%Y/%m/%d') as dates FROM `logs_kb` GROUP by dominio, dates order by dominio"));
        return view('all-worker-logs')->with(
            array(
                'allLogs' => $allLogs,
                'results' => $results
            )
        );
    }

    function load_validator_log(Request $request)
    {
        $allLogs = KB_LogValidatorPlugin::all();
        $allDomains = Domain::all();
        $results = DB::select(DB::raw("SELECT count(1) as number, dominio, DATE_FORMAT(date, '%Y/%m/%d') as dates FROM `logs_kb` GROUP by dominio, dates order by dominio"));
        return view('all-worker-logs')->with(
            array(
                'allLogs' => $allLogs,
                'results' => $results,
                'allDomains' => $allDomains
            )
        );
    }

    public static function makeLog($type, $url, $body, $id_domain, $time_lapse)
    {
        try {
            $newLog = new KB_Log();
            $newLog->type = ($type == null) ? "default" : $type;
            $newLog->url = ($url == null) ? "default" : $url;
            $newLog->body = ($body == null) ? "default" : $body;
            $newLog->status = 1;
            $newLog->id_domain = ($id_domain == null) ? null : $id_domain;
            $newLog->time_lapse = ($time_lapse == null) ? -1 : $time_lapse;
            return $newLog->save();
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function make_post_validation_log(Request $request)
    {

        try {
            $url= str_replace("http:www.","",$request->url);
            $requested_domain = Domain::where("url", "=", $url)->first();
            if ($requested_domain == null) {
                return response()->json(false);
            }
            $new_log = new KB_LogValidatorPlugin();
            $new_log->id_domain = $requested_domain->id;
            $new_log->body = $request->body;
            $new_log->user = $request->user;
            $new_log->timezone = $request->timezone;
            $new_log->request_url = $request->request_url;

            if ($new_log->save()) {
                return response()->json(true);
            } else {
                return response()->json(false);
            }
        } catch (Exception $ex) {
            return response()->json($ex);
        }
        return response()->json(false);
    }

    function legacy_validator_log(Request $reques)
    {
        try {
            $allDomains = Domain::all();
            foreach ($allDomains as $domain) {
                try {
                    $mainUrl = "https://www." . $domain->url . "/wp-admin/post.php?";
                    $service_url = "https://www." . $domain->url . "/wp-json/kb-alert/v2/trasient";
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
                    //$log = LogController::makeLog('Legacy_log',  $service_url, null, $currentSite->id, ($end_t - $start_t));
                    $successArray = json_decode($result->getBody()->getContents());
                    foreach ($successArray as $singlePost) {
                        $micro_time = str_replace("_transient_pv_debug_", "", $singlePost->option_name);
                        $singlePost->custom_date = date("Y/m/d H:i:s", $micro_time);
                        $pos = strpos($singlePost->option_value, "user: ", 0);
                        $len = strlen($singlePost->option_value);
                        $singlePost->user = substr($singlePost->option_value, $pos + 6, $len);
                        $singlePost->request_url = $mainUrl . "post=" . $singlePost->ID . "&action=edit";
                        $singlePost->post_title = $singlePost->post_title;
                        $singlePost->post_status = $singlePost->post_status;

                        /********** */
                        //$exist=  KB_LogValidatorPlugin::where("post_title","=",$singlePost->post_title)
                        $new_log = new KB_LogValidatorPlugin();
                        $new_log->id_domain = $domain->id; //ID
                        $new_log->body = $singlePost->option_value;
                        $new_log->user = $singlePost->user;
                        $new_log->timezone = $singlePost->custom_date;
                        $new_log->request_url = $singlePost->request_url;
                        $new_log->post_title = $singlePost->post_title;
                        $new_log->post_status = $singlePost->post_status;

                        if ($new_log->save()) {
                            // response()->json(true);
                        } else {
                            //return response()->json(false);
                        }
                    }
                } catch (Exception $th) {
                    // $th;
                }
            }

            return response()->json($successArray);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
